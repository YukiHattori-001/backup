<?php
// ============================================
// PAYMONGO PAYMENT GATEWAY INTEGRATION
// ============================================
// Native cURL client (no Composer/SDK required) plus the DB helpers that turn a
// confirmed PayMongo Checkout Session into a normal POS sale. The flow is:
//
//   1. Cashier builds a cart and picks a PayMongo method (GCash/Maya/Card/...).
//   2. create_paymongo_checkout validates the cart server-side, stores a pending
//      snapshot in `paymongo_checkouts`, and asks PayMongo for a Checkout Session.
//   3. The customer pays on PayMongo's hosted page (or by scanning the QR).
//   4. Either the webhook (paymongo_webhook.php, production) or the POS poller
//      (`get_paymongo_checkout_status`, works locally) confirms payment and we
//      finalize the sale exactly once through SaleManager::createSale().
//
// Amounts are always converted to centavos (PHP): ₱10.00 === 1000.

if (!function_exists('paymongoSecretKey')) {

function paymongoBaseUrl() { return 'https://api.paymongo.com/v1'; }

// Credentials resolve from real environment variables first (secure server-side
// config / .env), then fall back to the DB settings saved from the gateway page.
function paymongoEnv($key) {
    $val = getenv($key);
    if ($val === false || $val === null || trim((string)$val) === '') { return ''; }
    return trim((string)$val);
}
function paymongoSecretKey() { return paymongoEnv('PAYMONGO_SECRET_KEY') !== '' ? paymongoEnv('PAYMONGO_SECRET_KEY') : trim(getSetting('paymongo_secret_key', '')); }
function paymongoPublicKey() { return paymongoEnv('PAYMONGO_PUBLIC_KEY') !== '' ? paymongoEnv('PAYMONGO_PUBLIC_KEY') : trim(getSetting('paymongo_public_key', '')); }
function paymongoWebhookSecret() { return paymongoEnv('PAYMONGO_WEBHOOK_SECRET') !== '' ? paymongoEnv('PAYMONGO_WEBHOOK_SECRET') : trim(getSetting('paymongo_webhook_secret', '')); }
function paymongoMode() {
    $envMode = paymongoEnv('PAYMONGO_MODE');
    $mode = $envMode !== '' ? $envMode : getSetting('paymongo_mode', 'test');
    return in_array($mode, ['live', 'demo'], true) ? $mode : 'test';
}
function paymongoEnabled() { return getSetting('paymongo_enabled', '0') === '1'; }

// Demo mode = the "fake PayMongo" simulator: no real keys, no real money. Every
// PayMongo API call is answered locally and the hosted checkout page is served
// by paymongo_simulator.php so the whole flow can be demoed offline.
function paymongoIsDemo() { return paymongoMode() === 'demo'; }

// PayMongo credentials have a fixed prefix. Checking it before we ever reach
// the API turns a cryptic production error ("Invalid merchant key format") into
// an instruction the person filling in the form can act on. Blank values are
// allowed — they mean "keep whatever is already saved".
//
// $mode matters for two reasons: demo mode deliberately uses made-up
// pk_demo_/sk_demo_ credentials that must never be flagged, and a test-mode
// config must not silently accept a pk_live_ key (a live key can move real money
// the moment it reaches api.paymongo.com).
function paymongoKeyFormatError($publicKey, $secretKey, $webhookSecret, $mode) {
    if ($mode === 'demo') { return null; }
    $env = ($mode === 'live') ? 'live' : 'test';
    $checks = [
        ['Public key',             $publicKey,     ['pk_' . $env . '_']],
        ['Secret key',             $secretKey,     ['sk_' . $env . '_']],
        ['Webhook signing secret', $webhookSecret, ['whsec_']],
    ];
    foreach ($checks as $check) {
        list($label, $value, $prefixes) = $check;
        if ($value === null || $value === '') { continue; }
        $matched = false;
        foreach ($prefixes as $prefix) {
            if (strncmp($value, $prefix, strlen($prefix)) === 0) { $matched = true; break; }
        }
        if ($matched) { continue; }
        $where = ($label === 'Webhook signing secret')
            ? 'Settings → Webhooks'
            : 'Settings → Product Settings → Developers';
        return $label . ' does not match this mode: it starts with "' . substr($value, 0, 8)
            . '…" but mode "' . $mode . '" needs a key starting with ' . $prefixes[0]
            . '. Copy it from ' . $where . ' in the PayMongo dashboard.';
    }
    return null;
}


function paymongoIsConfigured() {
    // Demo mode counts as configured on its own — the keys are simulated.
    return paymongoEnabled() && (paymongoIsDemo() || (paymongoSecretKey() !== '' && paymongoPublicKey() !== ''));
}

// Best-effort absolute URL to the public webhook endpoint. In local dev this
// resolves to http://127.0.0.1:8099/paymongo_webhook.php which PayMongo cannot
// reach — that is expected; use the POS "Check status" poller locally.
function paymongoWebhookUrl() {
    return paymongoAbsoluteUrl('/paymongo_webhook.php');
}

// Where PayMongo sends the customer after paying / cancelling. Used when no
// explicit success/cancel URL is configured (handy for local testing).
function paymongoLocalReturnUrl($reference, $result) {
    return paymongoAbsoluteUrl('/index.php?page=cart&paymongo_return=' . rawurlencode($reference) . '&result=' . rawurlencode($result));
}

// The dedicated confirmation page (success / pending / failed / expired /
// cancelled). The per-checkout token lets the page safely look the order up
// without exposing other customers' orders to reference guessing.
function paymongoResultUrl($reference, $result, $token) {
    return paymongoAbsoluteUrl('/index.php?page=payment_result'
        . '&ref=' . rawurlencode($reference)
        . '&result=' . rawurlencode($result)
        . '&token=' . rawurlencode($token));
}

// Random opaque token stored on the checkout row and echoed in the return URL.
function paymongoNewReturnToken() {
    return bin2hex(random_bytes(16));
}

function paymongoAbsoluteUrl($path) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    // Windows PHP's dirname() can return a stray backslash for root-level
    // scripts (dirname('/index.php') === '\'), so normalize separators.
    $dir = str_replace('\\', '/', dirname($script));
    if ($dir === '.' || $dir === '/') { $dir = ''; }
    return $scheme . '://' . $host . $dir . $path;
}

// The "hosted checkout page" used in demo mode. It is the local simulator page
// instead of a real PayMongo checkout_url, but everything else is identical.
function paymongoSimulatorUrl($sessionId) {
    return paymongoAbsoluteUrl('/paymongo_simulator.php?cs=' . rawurlencode($sessionId));
}

// Human label for a PayMongo payment method code.
function paymongoMethodLabel($code) {
    $labels = [
        'gcash' => 'GCash (PayMongo)',
        'maya' => 'Maya (PayMongo)',
        'grab_pay' => 'GrabPay (PayMongo)',
        'card' => 'Card (PayMongo)',
        'qrph' => 'QR Ph (PayMongo)',
        'paymongo' => 'PayMongo',
    ];
    return $labels[$code] ?? 'PayMongo';
}

// Which of our payment_method hints are valid sales.payment_method values.
function paymongoSaleMethod($hint) {
    $allowed = ['cash', 'card', 'gcash', 'other', 'maya', 'grab_pay', 'qrph', 'paymongo'];
    return in_array($hint, $allowed, true) ? $hint : 'paymongo';
}

class PayMongo {
    // Perform an authenticated request against the PayMongo API.
    // Returns ['ok'=>bool, 'status'=>int, 'data'=>array|null, 'error'=>string|null].
    public static function request($method, $path, $body = null, $secretKey = null) {
        // Demo mode: answer the request from the local simulator instead of
        // calling the real PayMongo API.
        if (paymongoIsDemo()) {
            return PayMongoSimulator::handle($method, $path, $body);
        }

        $secret = $secretKey !== null ? $secretKey : paymongoSecretKey();
        $headers = ['Accept: application/json', 'Content-Type: application/json'];
        if ($secret !== '') {
            $headers[] = 'Authorization: Basic ' . base64_encode($secret . ':');
        }

        $ch = curl_init(paymongoBaseUrl() . $path);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 15,
        ];
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body);
        }
        curl_setopt_array($ch, $opts);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $errstr = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => 'Network error: ' . $errstr];
        }

        $data = json_decode($response, true);
        $ok = $status >= 200 && $status < 300;
        $message = null;
        if (!$ok) {
            if (is_array($data) && !empty($data['errors'][0])) {
                $message = $data['errors'][0]['detail'] ?? $data['errors'][0]['code'] ?? null;
            }
            if (!$message) { $message = 'PayMongo request failed (HTTP ' . $status . ')'; }
        }
        return ['ok' => $ok, 'status' => $status, 'data' => $data, 'error' => $ok ? null : $message];
    }

    public static function createCheckoutSession($attributes) {
        return self::request('POST', '/checkout_sessions', ['data' => ['attributes' => $attributes]]);
    }

    public static function getCheckoutSession($id) {
        return self::request('GET', '/checkout_sessions/' . rawurlencode($id));
    }

    public static function expireCheckoutSession($id) {
        return self::request('POST', '/checkout_sessions/' . rawurlencode($id) . '/expire');
    }

    // Cheap authenticated call used by the "Test connection" button.
    // Uses GET /payments (not /checkout_sessions): PayMongo now requires a
    // payment_intent_id on the checkout_sessions list endpoint, which returns a
    // 400 for a bare list call. /payments?limit=1 is a stable authenticated read
    // that proves the secret key is valid without creating anything.
    public static function testConnection($secretKey = null) {
        return self::request('GET', '/payments?limit=1', null, $secretKey);
    }

    // Verify the `Paymongo-Signature` header (t=...,te=...,li=...).
    // PayMongo signs "{timestamp}.{rawBody}" with HMAC-SHA256 using the webhook
    // secret; `te` is used in test mode and `li` in live mode.
    public static function verifyWebhookSignature($payload, $signatureHeader, $secret) {
        if ($secret === '' || $signatureHeader === '') { return false; }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '' || strpos($chunk, '=') === false) { continue; }
            list($k, $v) = explode('=', $chunk, 2);
            $parts[trim($k)] = trim($v);
        }

        if (empty($parts['t'])) { return false; }
        $expected = hash_hmac('sha256', $parts['t'] . '.' . $payload, $secret);

        foreach (['te', 'li'] as $key) {
            if (!empty($parts[$key]) && hash_equals($expected, $parts[$key])) {
                // Reject replays older than 5 minutes.
                if (abs(time() - (int)$parts['t']) > 300) { return false; }
                return true;
            }
        }
        return false;
    }
}

// ============================================
// CART VALIDATION (server-side authority)
// ============================================
// Never trust client prices/stock. Returns a normalized snapshot used both for
// the PayMongo line items and for the eventual createSale() call.
function paymongoValidateCart($items, $customerId, $discount, $loyaltyPoints) {
    global $pdo;

    if (!is_array($items) || empty($items)) {
        return ['ok' => false, 'message' => 'Cart is empty.'];
    }

    $normalized = [];
    $subtotal = 0.0;

    foreach ($items as $item) {
        $id = isset($item['id']) ? (int)$item['id'] : 0;
        $qty = isset($item['qty']) ? (int)$item['qty'] : 0;
        if ($id <= 0 || $qty <= 0) {
            return ['ok' => false, 'message' => 'Invalid cart item.'];
        }

        $stmt = $pdo->prepare("SELECT id, name, selling_price, unit, stock_quantity, archived FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if (!$product) {
            return ['ok' => false, 'message' => "A product in the cart no longer exists."];
        }
        if (!empty($product['archived'])) {
            return ['ok' => false, 'message' => "\"{$product['name']}\" is archived and cannot be sold."];
        }
        if ($qty > (int)$product['stock_quantity']) {
            return ['ok' => false, 'message' => "Not enough stock for {$product['name']} (only {$product['stock_quantity']} left)."];
        }

        $price = round((float)$product['selling_price'], 2);
        $normalized[] = [
            'id'    => (int)$product['id'],
            'name'  => $product['name'],
            'price' => $price,
            'qty'   => $qty,
            'unit'  => $product['unit'] ?: 'pc',
        ];
        $subtotal += $price * $qty;
    }

    $subtotal = round($subtotal, 2);
    $discount = max(0, min(round((float)$discount, 2), $subtotal));
    $loyaltyPoints = max(0, (int)$loyaltyPoints);

    if ($loyaltyPoints > 0) {
        $customerId = $customerId ? (int)$customerId : null;
        if (!$customerId) {
            return ['ok' => false, 'message' => 'Select a customer before using loyalty points.'];
        }
        $stmt = $pdo->prepare("SELECT loyalty_points FROM customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $available = (int)($stmt->fetchColumn() ?: 0);
        if ($loyaltyPoints > $available) {
            return ['ok' => false, 'message' => "Not enough loyalty points (available: {$available})."];
        }
    }

    $total = round($subtotal - $discount, 2);
    if ($total <= 0) {
        return ['ok' => false, 'message' => 'Total due must be greater than zero.'];
    }

    return [
        'ok' => true,
        'snapshot' => [
            'items' => $normalized,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'customer_id' => $customerId ? (int)$customerId : null,
            'loyalty_points_used' => $loyaltyPoints,
        ],
    ];
}

// Build PayMongo line items from the snapshot. When a discount is present it is
// spread proportionally across items so the line totals still sum to the exact
// amount charged (PayMongo line items cannot be negative).
//
// We always send quantity=1 with the full line total as `amount`. PayMongo's
// treatment of amount-vs-quantity is ambiguous, and this keeps the sum of the
// amounts equal to the exact grand total no matter how it is interpreted.
function paymongoLineItems($snapshot) {
    $lines = [];
    $subtotal = (float)$snapshot['subtotal'];
    $discount = (float)$snapshot['discount'];
    $target = (int)round(((float)$snapshot['total']) * 100);
    $running = 0;
    $items = $snapshot['items'];
    $last = count($items) - 1;

    foreach ($items as $i => $item) {
        $raw = $item['price'] * $item['qty'];
        $scale = ($subtotal > 0) ? ($raw / $subtotal) : 0;
        $amount = (int)round(($raw - $discount * $scale) * 100);
        if ($amount < 1) { $amount = 1; }
        $running += $amount;

        if ($i === $last) {
            // Fix rounding drift on the final line so the sum matches exactly.
            $amount += ($target - $running);
            if ($amount < 1) { $amount = 1; }
        }

        $lines[] = [
            'currency'    => 'PHP',
            'amount'      => $amount,
            'name'        => mb_substr($item['name'], 0, 100),
            'quantity'    => 1,
            'description' => mb_substr($item['qty'] . ' x ₱' . number_format($item['price'], 2) . ' per ' . $item['unit'], 0, 200),
        ];
    }

    return $lines;
}

// ============================================
// SALE FINALIZATION
// ============================================
// Idempotently convert a confirmed checkout into a real sale. The conditional
// UPDATE is the concurrency guard: only one caller can move a pending checkout
// into `processing`, so a webhook + poller racing each other cannot double-sell.
function paymongoFinalizeCheckout($pdo, $saleManager, $checkoutId, $payment = []) {
    $checkoutId = (int)$checkoutId;

    $claim = $pdo->prepare("UPDATE paymongo_checkouts SET status = 'processing', updated_at = NOW() WHERE id = ? AND status IN ('pending','failed')");
    $claim->execute([$checkoutId]);

    if ($claim->rowCount() === 0) {
        $stmt = $pdo->prepare("SELECT status, sale_id FROM paymongo_checkouts WHERE id = ?");
        $stmt->execute([$checkoutId]);
        $row = $stmt->fetch();
        return [
            'success'  => true,
            'already'  => true,
            'status'   => $row['status'] ?? 'unknown',
            'sale_id'  => $row['sale_id'] ?? null,
        ];
    }

    $stmt = $pdo->prepare("SELECT * FROM paymongo_checkouts WHERE id = ?");
    $stmt->execute([$checkoutId]);
    $checkout = $stmt->fetch();
    if (!$checkout) {
        return ['success' => false, 'message' => 'Checkout not found.'];
    }

    $snapshot = json_decode($checkout['cart_snapshot'], true);
    if (!is_array($snapshot) || empty($snapshot['items'])) {
        $pdo->prepare("UPDATE paymongo_checkouts SET status = 'failed', error_message = ? WHERE id = ?")
            ->execute(['Cart snapshot missing', $checkoutId]);
        return ['success' => false, 'message' => 'Cart snapshot missing.'];
    }

    $items = [];
    foreach ($snapshot['items'] as $it) {
        $items[] = [
            'id'    => (int)$it['id'],
            'name'  => $it['name'],
            'price' => (float)$it['price'],
            'qty'   => (int)$it['qty'],
            'unit'  => $it['unit'] ?? 'pc',
        ];
    }

    $paymentId = $payment['id'] ?? $checkout['payment_id'];
    // The method PayMongo actually used (falls back to the cashier's hint).
    $paymentType = $payment['payment_type'] ?? ($checkout['payment_type'] ?? '') ?: $checkout['payment_method_hint'];
    $method = paymongoSaleMethod($paymentType);
    $livemode = !empty($payment['livemode']) ? 1 : 0;

    $result = $saleManager->createSale(
        $items,
        (float)$snapshot['subtotal'],                     // gross (VAT-inclusive)
        $snapshot['customer_id'] ?? null,
        (float)$snapshot['total'],                        // amount paid
        0,                                                // change
        (float)$snapshot['discount'],
        (int)($snapshot['loyalty_points_used'] ?? 0),
        $method,
        null,
        $checkout['cashier_id'] ? (int)$checkout['cashier_id'] : null
    );

    if (empty($result['success'])) {
        $pdo->prepare("UPDATE paymongo_checkouts SET status = 'failed', error_message = ? WHERE id = ?")
            ->execute([mb_substr((string)$result['message'], 0, 250), $checkoutId]);
        logActivity('paymongo_checkout_failed', "PayMongo checkout {$checkout['reference']} could not be finalized: " . $result['message']);
        return ['success' => false, 'message' => $result['message']];
    }

    $saleId = (int)$result['sale_id'];

    $pdo->prepare("UPDATE sales SET paymongo_checkout_id = ?, paymongo_session_id = ?, paymongo_payment_id = ?, paymongo_reference = ?, paymongo_status = 'paid', paymongo_paid_at = NOW(), paymongo_payment_type = ?, paymongo_livemode = ? WHERE id = ?")
        ->execute([$checkoutId, $checkout['checkout_session_id'], $paymentId, $checkout['reference'], $paymentType, $livemode, $saleId]);

    // Carry PayMongo's fee/settlement figures across so Finance can reconcile the
    // gross sale against what actually settles. COALESCE keeps any value already
    // recorded by an earlier webhook or poll.
    $fee = array_key_exists('fee', $payment) ? $payment['fee'] : null;
    $net = array_key_exists('net_amount', $payment) ? $payment['net_amount'] : null;

    $pdo->prepare("UPDATE paymongo_checkouts SET status = 'paid', sale_id = ?, payment_id = ?, payment_type = ?, livemode = ?, fee = COALESCE(?, fee), net_amount = COALESCE(?, net_amount), paid_at = NOW(), updated_at = NOW(), error_message = NULL WHERE id = ?")
        ->execute([$saleId, $paymentId, $paymentType, $livemode, $fee, $net, $checkoutId]);

    logActivity('paymongo_payment_paid', "PayMongo payment received: {$checkout['reference']} ({$method}, ₱" . number_format((float)$checkout['amount'], 2) . ") -> {$result['invoice']}");

    return [
        'success'   => true,
        'sale_id'   => $saleId,
        'invoice'   => $result['invoice'],
        'reference' => $checkout['reference'],
    ];
}

// Inspect a checkout-session attributes payload and report whether it is paid.
function paymongoSessionIsPaid($attrs) {
    if (!empty($attrs['payments']) && is_array($attrs['payments'])) {
        foreach ($attrs['payments'] as $payment) {
            $status = $payment['attributes']['status'] ?? ($payment['status'] ?? '');
            if ($status === 'paid') { return true; }
            // PayMongo sometimes nests payment resources under data[].
            if (!empty($payment['data']) && is_array($payment['data'])) {
                foreach ($payment['data'] as $p) {
                    if (($p['attributes']['status'] ?? '') === 'paid') { return true; }
                }
            }
        }
    }
    if (($attrs['payment_intent']['attributes']['status'] ?? '') === 'succeeded') {
        return true;
    }
    return false;
}

function paymongoExtractPaymentId($attrs) {
    if (!empty($attrs['payments']) && is_array($attrs['payments'])) {
        foreach ($attrs['payments'] as $payment) {
            if (($payment['attributes']['status'] ?? '') === 'paid' && !empty($payment['id'])) {
                return $payment['id'];
            }
        }
    }
    if (!empty($attrs['payment_intent']['id'])) {
        return $attrs['payment_intent']['id'];
    }
    return null;
}

// Pull the concrete payment method (gcash, card, ...) out of a checkout-session
// or payment resource, tolerating both the v1 and v2 shapes.
function paymongoExtractPaymentMethod($attrs) {
    // Checkout session: payments[].attributes.source.type / payment_method_used
    if (!empty($attrs['payments']) && is_array($attrs['payments'])) {
        foreach ($attrs['payments'] as $payment) {
            $pa = $payment['attributes'] ?? [];
            foreach (['source', 'payment_method_used', 'payment_method'] as $k) {
                if (!empty($pa[$k])) {
                    if (is_array($pa[$k])) {
                        if (!empty($pa[$k]['type'])) { return (string)$pa[$k]['type']; }
                        if (!empty($pa[$k]['attributes']['type'])) { return (string)$pa[$k]['attributes']['type']; }
                    } else {
                        return (string)$pa[$k];
                    }
                }
            }
            if (!empty($pa['payment_intent']['attributes']['payment_method_allowed'][0])) {
                return (string)$pa['payment_intent']['attributes']['payment_method_allowed'][0];
            }
        }
    }
    if (!empty($attrs['payment_method_used'])) { return (string)$attrs['payment_method_used']; }
    if (!empty($attrs['payment_method'])) { return (string)$attrs['payment_method']; }
    if (!empty($attrs['payment_method_types'][0])) { return (string)$attrs['payment_method_types'][0]; }
    return null;
}

// The amount (in centavos) carried by a payment resource, if present.
function paymongoExtractAmount($attrs) {
    if (!empty($attrs['payments']) && is_array($attrs['payments'])) {
        foreach ($attrs['payments'] as $payment) {
            if (isset($payment['attributes']['amount'])) {
                return (int)$payment['attributes']['amount'];
            }
        }
    }
    if (isset($attrs['amount'])) { return (int)$attrs['amount']; }
    if (isset($attrs['payment_intent']['attributes']['amount'])) {
        return (int)$attrs['payment_intent']['attributes']['amount'];
    }
    return null;
}

// What PayMongo charged for this payment, and what settles to us (in centavos).
// Reported on the paid payment resource; not every method reports one, so both
// values are optional.
function paymongoExtractFees($attrs) {
    $out = ['fee' => null, 'net_amount' => null];

    $candidates = [];
    if (!empty($attrs['payments']) && is_array($attrs['payments'])) {
        foreach ($attrs['payments'] as $payment) {
            if (!empty($payment['attributes']) && is_array($payment['attributes'])) {
                $candidates[] = $payment['attributes'];
            }
        }
    }
    $candidates[] = $attrs;

    foreach ($candidates as $a) {
        if ($out['fee'] === null && isset($a['fee'])) {
            $out['fee'] = (float)$a['fee'] / 100;
        }
        if ($out['net_amount'] === null && isset($a['net_amount'])) {
            $out['net_amount'] = (float)$a['net_amount'] / 100;
        }
        // Charges/fees may be nested one level down on a checkout session.
        $charges = $a['charges']['data'] ?? ($a['charges'] ?? null);
        if ($out['fee'] === null && is_array($charges)) {
            foreach ($charges as $c) {
                if (isset($c['fee'])) { $out['fee'] = (float)$c['fee'] / 100; break; }
                if (isset($c['attributes']['fee'])) { $out['fee'] = (float)$c['attributes']['fee'] / 100; break; }
            }
        }
        if ($out['fee'] !== null && $out['net_amount'] === null && isset($a['amount'])) {
            $out['net_amount'] = ((float)$a['amount'] - (float)($out['fee'] * 100)) / 100;
        }
    }
    if ($out['fee'] === null && $out['net_amount'] === null) {
        return ['fee' => null, 'net_amount' => null];
    }
    return $out;
}

// Which payment method types to request for a checkout.
//
// The cashier picks a method (GCash, Maya, ...) before the session is created,
// so the hosted page opens pre-focused on exactly that method rather than
// offering a method the sale was never set up for. The generic "paymongo"
// choice deliberately means "let the payer choose", so every configured method
// is passed through. Anything configured but not known to PayMongo is dropped.
function paymongoCheckoutMethodTypes($hint, $configured) {
    $configured = array_values(array_unique(array_filter(array_map('trim', (array)$configured))));
    $known = ['card', 'gcash', 'maya', 'grab_pay', 'qrph'];
    $configured = array_values(array_intersect($configured, $known));
    if ($configured && $hint !== 'paymongo' && in_array($hint, $configured, true)) {
        return [$hint];
    }
    return $configured;
}

// Normalise PayMongo webhook envelopes into one shape.
//
// Canonical (documented) form:
//   { data: { id, type: "event", attributes: { type, livemode, data: {...} } } }
// Flattened form seen in some delivery templates:
//   { event_type, data: { type, livemode, data: {...} } }
//
// Recognising both matters: an envelope we fail to read leaves the sale
// un-finalised, because this is the server-side source of truth for "paid".
function paymongoParseEvent($payload) {
    $out = ['event_id' => null, 'event_type' => '', 'resource' => [], 'resource_id' => null, 'livemode' => false];
    if (!is_array($payload)) { return $out; }

    $node  = is_array($payload['data'] ?? null) ? $payload['data'] : [];
    $attrs = is_array($node['attributes'] ?? null) ? $node['attributes'] : [];

    if ($attrs) {
        $out['event_id']   = $payload['data']['id'] ?? null;
        $out['event_type'] = (string)($attrs['type'] ?? '');
        $resource          = $attrs['data'] ?? null;
        $out['livemode']   = !empty($attrs['livemode']);
    } else {
        // Flattened form. The real event type sits at data.type; some templates
        // also carry a top-level event_type of "send.webhook", which is a
        // template marker rather than an event, so it is never used as one.
        $candidates = [
            (string)($node['type'] ?? ''),
            (string)($payload['event_type'] ?? ''),
        ];
        foreach ($candidates as $c) {
            if ($c !== '' && strtolower($c) !== 'send.webhook') {
                $out['event_type'] = $c;
                break;
            }
        }
        $resource          = $node['data'] ?? null;
        $out['livemode']   = !empty($payload['livemode']) || !empty($node['livemode']);
    }

    $out['resource'] = is_array($resource) ? $resource : [];
    $out['resource_id'] = $out['resource']['id'] ?? null;
    return $out;
}

// Mark a finalized online sale as refunded without creating a second (negative)
// sale. Finance reporting keeps the original sale row; only its status changes.
function paymongoApplyRefund($pdo, $checkout, $attrs = []) {
    $checkoutId = (int)$checkout['id'];
    $saleId = (int)$checkout['sale_id'];

    if (!$saleId) {
        $pdo->prepare("UPDATE paymongo_checkouts SET status = 'refunded', updated_at = NOW() WHERE id = ?")
            ->execute([$checkoutId]);
        return ['success' => true, 'sale_id' => null, 'refunded' => true];
    }

    $pdo->prepare("UPDATE sales SET paymongo_status = 'refunded', paymongo_refunded_at = NOW() WHERE id = ?")
        ->execute([$saleId]);
    $pdo->prepare("UPDATE paymongo_checkouts SET status = 'refunded', updated_at = NOW() WHERE id = ?")
        ->execute([$checkoutId]);

    logActivity('paymongo_payment_refunded', "PayMongo payment refunded: {$checkout['reference']}");
    return ['success' => true, 'sale_id' => $saleId, 'refunded' => true];
}

// Ask PayMongo for the latest state of a checkout and finalize if paid.
function paymongoPollCheckout($pdo, $saleManager, $checkout) {
    if (in_array($checkout['status'], ['paid', 'expired', 'cancelled'], true)) {
        return ['success' => true, 'status' => $checkout['status'], 'paid' => $checkout['status'] === 'paid', 'sale_id' => $checkout['sale_id']];
    }
    if (empty($checkout['checkout_session_id'])) {
        return ['success' => false, 'message' => 'Checkout session was never created.'];
    }

    $res = PayMongo::getCheckoutSession($checkout['checkout_session_id']);
    if (!$res['ok']) {
        return ['success' => false, 'status' => $checkout['status'], 'paid' => false, 'message' => $res['error']];
    }

    $attrs = $res['data']['data']['attributes'] ?? [];
    if (paymongoSessionIsPaid($attrs)) {
        $fees = paymongoExtractFees($attrs);
        $final = paymongoFinalizeCheckout($pdo, $saleManager, (int)$checkout['id'], [
            'id' => paymongoExtractPaymentId($attrs),
            'payment_type' => paymongoExtractPaymentMethod($attrs),
            'livemode' => !empty($attrs['livemode']),
            'fee' => $fees['fee'],
            'net_amount' => $fees['net_amount'],
        ]);
        return [
            'success'  => !empty($final['success']),
            'status'   => !empty($final['success']) ? 'paid' : 'failed',
            'paid'     => !empty($final['success']),
            'sale_id'  => $final['sale_id'] ?? null,
            'reference' => $checkout['reference'],
            'message'  => $final['message'] ?? null,
        ];
    }

    $status = $attrs['status'] ?? 'active';
    if ($status === 'expired') {
        $pdo->prepare("UPDATE paymongo_checkouts SET status = 'expired', updated_at = NOW() WHERE id = ? AND status = 'pending'")
            ->execute([(int)$checkout['id']]);
        return ['success' => true, 'status' => 'expired', 'paid' => false, 'sale_id' => null];
    }

    return ['success' => true, 'status' => 'pending', 'paid' => false, 'sale_id' => null];
}

// ============================================
// FAKE PAYMONGO (DEMO / SIMULATOR MODE)
// ============================================
// In demo mode the app acts as PayMongo: it answers API calls locally, hands
// out fake `cs_demo_...` sessions whose checkout_url is the local simulator
// page, and lets a staff member approve/decline the payment there. The response
// shapes mirror the real PayMongo API so the rest of the code path is unchanged
// — "almost the same as the real thing".
class PayMongoSimulator {
    public static function handle($method, $path, $body = null) {
        global $pdo;
        $method = strtoupper((string)$method);
        $path = rtrim((string)$path, '/');

        // POST /checkout_sessions — create a fake hosted checkout session.
        if ($method === 'POST' && $path === '/checkout_sessions') {
            return self::createSession($pdo, is_array($body) ? $body : []);
        }

        // POST /checkout_sessions/<id>/expire
        if ($method === 'POST' && preg_match('#^/checkout_sessions/([A-Za-z0-9_\-]+)/expire$#', $path, $m)) {
            return self::expireSession($pdo, $m[1]);
        }

        // GET /checkout_sessions/<id>
        if ($method === 'GET' && preg_match('#^/checkout_sessions/([A-Za-z0-9_\-]+)$#', $path, $m)) {
            return self::getSession($pdo, $m[1]);
        }

        // GET /checkout_sessions (the "Test connection" call).
        if ($method === 'GET' && strpos($path, '/checkout_sessions') === 0) {
            return ['ok' => true, 'status' => 200, 'data' => ['data' => []], 'error' => null];
        }

        return ['ok' => false, 'status' => 404, 'data' => null, 'error' => 'Demo simulator: PayMongo endpoint not implemented (' . $method . ' ' . $path . ')'];
    }

    private static function createSession($pdo, $body) {
        $attrs = $body['data']['attributes'] ?? [];
        $metaRef = $attrs['metadata']['checkout_reference'] ?? null;

        $stmt = $pdo->prepare("SELECT * FROM paymongo_checkouts WHERE reference = ? AND status = 'pending' ORDER BY id DESC LIMIT 1");
        $stmt->execute([$metaRef]);
        $checkout = $stmt->fetch();
        if (!$checkout) {
            return ['ok' => false, 'status' => 400, 'data' => null, 'error' => 'Demo simulator: no matching checkout found.'];
        }

        $sessionId = 'cs_demo_' . $checkout['id'] . '_' . substr(bin2hex(random_bytes(4)), 0, 10);
        $pdo->prepare("UPDATE paymongo_checkouts SET checkout_session_id = ?, updated_at = NOW() WHERE id = ? AND status = 'pending'")
            ->execute([$sessionId, (int)$checkout['id']]);

        return self::sessionResponse($pdo, $sessionId, $checkout, $attrs);
    }

    private static function getSession($pdo, $sessionId) {
        $stmt = $pdo->prepare("SELECT * FROM paymongo_checkouts WHERE checkout_session_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$sessionId]);
        $checkout = $stmt->fetch();
        if (!$checkout) {
            return ['ok' => false, 'status' => 404, 'data' => null, 'error' => 'Checkout session not found.'];
        }
        return self::sessionResponse($pdo, $sessionId, $checkout, []);
    }

    private static function expireSession($pdo, $sessionId) {
        $stmt = $pdo->prepare("SELECT * FROM paymongo_checkouts WHERE checkout_session_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$sessionId]);
        $checkout = $stmt->fetch();
        if (!$checkout) {
            return ['ok' => false, 'status' => 404, 'data' => null, 'error' => 'Checkout session not found.'];
        }
        if ($checkout['status'] === 'pending') {
            $pdo->prepare("UPDATE paymongo_checkouts SET status = 'expired', updated_at = NOW() WHERE id = ? AND status = 'pending'")
                ->execute([(int)$checkout['id']]);
        }
        return self::sessionResponse($pdo, $sessionId, $checkout, []);
    }

    private static function sessionResponse($pdo, $sessionId, $checkout, $attrs) {
        $payments = [];
        if ($checkout['status'] === 'paid') {
            $paymentId = $checkout['payment_id'] ?: ('pay_demo_' . $checkout['id']);
            $payments[] = [
                'id' => $paymentId,
                'type' => 'payment',
                'attributes' => [
                    'id' => $paymentId,
                    'type' => 'payment',
                    'status' => 'paid',
                    'amount' => (int)round((float)$checkout['amount'] * 100),
                    'currency' => $checkout['currency'] ?: 'PHP',
                    'paid_at' => $checkout['paid_at'],
                    'metadata' => ['checkout_reference' => $checkout['reference']],
                ],
            ];
        }

        $apiStatus = ($checkout['status'] === 'expired' || $checkout['status'] === 'cancelled') ? 'expired' : 'active';

        $attributes = [
            'id' => $sessionId,
            'type' => 'checkout_session',
            'livemode' => false,
            'status' => $apiStatus,
            'checkout_url' => paymongoSimulatorUrl($sessionId),
            'reference_number' => $checkout['reference'],
            'amount' => (int)round((float)$checkout['amount'] * 100),
            'currency' => $checkout['currency'] ?: 'PHP',
            'billing' => null,
            'payments' => $payments,
            'payment_intent' => null,
            'payment_method_types' => (array)($attrs['payment_method_types'] ?? []),
            'description' => $attrs['description'] ?? ('Order ' . $checkout['reference']),
            'metadata' => ['checkout_reference' => $checkout['reference']],
        ];

        return [
            'ok' => true,
            'status' => 200,
            'data' => [
                'data' => ['id' => $sessionId, 'type' => 'checkout_session', 'attributes' => $attributes],
            ],
            'error' => null,
        ];
    }
}

// Simulate a successful payment. Builds a checkout_session.payment.paid event
// and, when a webhook signing secret exists, delivers it through the real local
// webhook endpoint (signed, exactly like production). Without a secret it calls
// the same finalize function the webhook would call. Either way the sale is
// recorded exactly once.
function paymongoSimulatePaid($pdo, $saleManager, $checkout, $paymentId = null) {
    $checkoutId = (int)$checkout['id'];
    $sessionId = $checkout['checkout_session_id'];
    $paymentId = $paymentId ?: ('pay_demo_' . $checkoutId);
    $eventId = 'evt_demo_' . $checkoutId . '_' . time();

    $payload = [
        'data' => [
            'id' => $eventId,
            'type' => 'event',
            'attributes' => [
                'livemode' => false,
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => $sessionId,
                    'type' => 'checkout_session',
                    'attributes' => [
                        'status' => 'paid',
                        'amount' => (int)round((float)$checkout['amount'] * 100),
                        'currency' => $checkout['currency'] ?: 'PHP',
                        'payments' => [
                            [
                                'id' => $paymentId,
                                'type' => 'payment',
                                'attributes' => [
                                    'status' => 'paid',
                                    'amount' => (int)round((float)$checkout['amount'] * 100),
                                    'currency' => 'PHP',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $finalized = false;
    $result = null;

    $webhookSecret = paymongoWebhookSecret();
    if ($webhookSecret !== '') {
        try {
            $raw = json_encode($payload);
            $t = time();
            $signature = 't=' . $t . ',te=' . hash_hmac('sha256', $t . '.' . $raw, $webhookSecret);

            $ch = curl_init(paymongoWebhookUrl());
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST            => true,
                CURLOPT_POSTFIELDS      => $raw,
                CURLOPT_HTTPHEADER      => ['Content-Type: application/json', 'Paymongo-Signature: ' . $signature],
                CURLOPT_TIMEOUT         => 5,
                CURLOPT_CONNECTTIMEOUT  => 3,
            ]);
            $body = curl_exec($ch);
            curl_close($ch);

            $decoded = json_decode($body, true);
            if (is_array($decoded) && !empty($decoded['finalized'])) {
                $stmt = $pdo->prepare("SELECT * FROM paymongo_checkouts WHERE id = ?");
                $stmt->execute([$checkoutId]);
                $fresh = $stmt->fetch();
                $result = ['success' => true, 'sale_id' => $fresh['sale_id'] ? (int)$fresh['sale_id'] : null, 'reference' => $fresh['reference']];
                $finalized = true;
            }
        } catch (Exception $e) {
            error_log('Demo PayMongo webhook delivery failed: ' . $e->getMessage());
        }
    }

    if (!$finalized) {
        // Direct path: identical finalize call, plus an audit event row.
        $result = paymongoFinalizeCheckout($pdo, $saleManager, $checkoutId, ['id' => $paymentId]);
        try {
            $pdo->prepare("INSERT IGNORE INTO paymongo_webhook_events (event_id, event_type, checkout_id, processed, payload) VALUES (?, ?, ?, 1, ?)")
                ->execute([$eventId, 'checkout_session.payment.paid', $checkoutId, json_encode($payload)]);
        } catch (Exception $e) {
            error_log('Demo PayMongo event log error: ' . $e->getMessage());
        }
    }

    return $result;
}

} // function_exists guard
