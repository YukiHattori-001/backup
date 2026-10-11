<?php
// ============================================
// PAYMONGO WEBHOOK ENDPOINT (public)
// ============================================
// PayMongo POSTs signed events here (e.g. checkout_session.payment.paid). This
// file is intentionally public — it does NOT include index.php, so there is no
// login redirect. Configure the URL shown on the Payment Gateway settings page
// as the webhook endpoint in the PayMongo dashboard.
//
// NOTE: PayMongo cannot reach a local XAMPP install. On local/sandbox setups the
// POS "Check status" poller finalizes payments instead; the webhook is for
// production or a tunnel (e.g. ngrok).

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/managers.php';
require_once __DIR__ . '/app/paymongo.php';

header('Content-Type: application/json; charset=utf-8');

function paymongoWebhookRespond($code, $body) {
    http_response_code($code);
    echo json_encode($body);
    exit();
}

$raw = file_get_contents('php://input');
$signature = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';
$secret = paymongoWebhookSecret();

if ($secret === '') {
    paymongoWebhookRespond(503, ['success' => false, 'message' => 'Webhook signing secret is not configured.']);
}

if (!PayMongo::verifyWebhookSignature($raw, $signature, $secret)) {
    paymongoWebhookRespond(401, ['success' => false, 'message' => 'Invalid signature.']);
}

$payload = json_decode($raw, true);
if (!is_array($payload)) {
    paymongoWebhookRespond(400, ['success' => false, 'message' => 'Invalid JSON payload.']);
}

// PayMongo documents two shapes for the same event (see paymongoParseEvent).
// Parsing both here means a payload we understand is never silently ignored —
// an unreadable envelope would leave the payment unfinalised server-side.
$pmEvent = paymongoParseEvent($payload);
$eventId = $pmEvent['event_id'];
$eventType = $pmEvent['event_type'];
$resource = $pmEvent['resource'];
$resourceId = $pmEvent['resource_id'];
// Event-level idempotency: ignore re-delivered events.
if ($eventId) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO paymongo_webhook_events (event_id, event_type, payload) VALUES (?, ?, ?)");
        $stmt->execute([$eventId, $eventType, $raw]);
        if ($stmt->rowCount() === 0) {
            paymongoWebhookRespond(200, ['success' => true, 'message' => 'Duplicate event ignored.']);
        }
    } catch (Exception $e) {
        error_log('PayMongo webhook log error: ' . $e->getMessage());
    }
}

$saleManager = new SaleManager($pdo);

// Resolve the matching checkout row.
$checkout = null;
if ($resourceId && strpos($resourceId, 'cs_') === 0) {
    $stmt = $pdo->prepare("SELECT * FROM paymongo_checkouts WHERE checkout_session_id = ?");
    $stmt->execute([$resourceId]);
    $checkout = $stmt->fetch();
} elseif ($resourceId) {
    // Payment resource: prefer our metadata reference, then fall back to amount.
    $metaRef = $resource['attributes']['metadata']['checkout_reference'] ?? null;
    if ($metaRef) {
        $stmt = $pdo->prepare("SELECT * FROM paymongo_checkouts WHERE reference = ?");
        $stmt->execute([$metaRef]);
        $checkout = $stmt->fetch();
    }
    if (!$checkout && isset($resource['attributes']['amount'])) {
        $stmt = $pdo->prepare("SELECT * FROM paymongo_checkouts WHERE status = 'pending' AND ROUND(amount * 100) = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([(int)$resource['attributes']['amount']]);
        $checkout = $stmt->fetch();
    }
}

$type = strtolower($eventType);
$resourceAttrs = $resource['attributes'] ?? [];
$livemode = $pmEvent['livemode'];

// Events we intentionally act on. Anything else is acknowledged (200) but
// ignored, so PayMongo stops retrying unknown/unrelated events.
$paidTypes = ['checkout_session.payment.paid', 'payment.paid', 'checkout_session.paid'];
$failedTypes = ['checkout_session.payment.failed', 'payment.failed'];
$refundTypes = ['payment.refunded', 'refund.succeeded', 'checkout_session.payment.refunded'];

$isRefund = in_array($type, $refundTypes, true) || strpos($type, 'refund') !== false;
$isPaid = in_array($type, $paidTypes, true) || (strpos($type, 'paid') !== false) || paymongoSessionIsPaid($resourceAttrs);
$isFailed = in_array($type, $failedTypes, true) || (strpos($type, 'failed') !== false);

$result = ['success' => true, 'event' => $eventType, 'livemode' => $livemode];

if ($checkout) {
    if ($eventId) {
        try { $pdo->prepare("UPDATE paymongo_webhook_events SET checkout_id = ? WHERE event_id = ?")->execute([(int)$checkout['id'], $eventId]); } catch (Exception $e) {}
    }

    if ($isRefund) {
        $ref = paymongoApplyRefund($pdo, $checkout, $resourceAttrs);
        $result['refunded'] = true;
        $result['sale_id'] = $ref['sale_id'] ?? null;
    } elseif ($isPaid) {
        // Validate the paid amount against the amount we asked for. PayMongo's
        // authoritative amount must match our snapshot (in centavos).
        $expected = (int)round((float)$checkout['amount'] * 100);
        $paidAmount = paymongoExtractAmount($resourceAttrs);
        if ($paidAmount !== null && $paidAmount !== $expected) {
            error_log("PayMongo webhook amount mismatch for {$checkout['reference']}: expected {$expected}, got {$paidAmount}");
            $result['amount_mismatch'] = true;
            // Do NOT finalize; flag for reconciliation.
            $pdo->prepare("UPDATE paymongo_checkouts SET error_message = ?, updated_at = NOW() WHERE id = ?")
                ->execute([mb_substr("Paid amount mismatch (expected {$expected}, got {$paidAmount})", 0, 250), (int)$checkout['id']]);
        } else {
            $paymentId = paymongoExtractPaymentId($resourceAttrs) ?: $resourceId;
            $fees = paymongoExtractFees($resourceAttrs);
            $final = paymongoFinalizeCheckout($pdo, $saleManager, (int)$checkout['id'], [
                'id' => $paymentId,
                'payment_type' => paymongoExtractPaymentMethod($resourceAttrs) ?: $checkout['payment_method_hint'],
                'livemode' => $livemode,
                'fee' => $fees['fee'],
                'net_amount' => $fees['net_amount'],
            ]);
            $result['finalized'] = !empty($final['success']);
            $result['sale_id'] = $final['sale_id'] ?? null;
        }
    } elseif ($isFailed && $checkout['status'] === 'pending') {
        $pdo->prepare("UPDATE paymongo_checkouts SET status = 'failed', error_message = 'Payment failed at PayMongo', updated_at = NOW() WHERE id = ? AND status = 'pending'")
            ->execute([(int)$checkout['id']]);
        $result['failed'] = true;
    } else {
        $result['handled'] = false;
    }
} else {
    $result['matched'] = false;
}

if ($eventId) {
    try { $pdo->prepare("UPDATE paymongo_webhook_events SET processed = 1 WHERE event_id = ?")->execute([$eventId]); } catch (Exception $e) {}
}

paymongoWebhookRespond(200, $result);
