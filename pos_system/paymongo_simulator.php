<?php
// ============================================
// PAYMONGO CHECKOUT SIMULATOR (demo mode only)
// ============================================
// This page impersonates PayMongo's hosted checkout page so the whole payment
// flow can be demoed and trained on locally — no real API keys, no real money.
//
// The POST /checkout_sessions call in demo mode returns cs_demo_... sessions
// whose checkout_url points here. Opening that URL (or scanning the QR shown in
// the cart) loads this page, which looks and behaves like PayMongo: the staff
// member picks an outcome (approve / decline / expire) or a PayMongo-style test
// card, and the payment then flows through the REAL webhook/finalize code path
// (signed event), so the sale, stock, receipt and audit trail are identical to
// a genuine GCash / Maya / card transaction.
//
// Like paymongo_webhook.php, this file is intentionally public (no login).

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/managers.php';
require_once __DIR__ . '/app/paymongo.php';

$saleManager = new SaleManager($pdo);

$cs = trim($_GET['cs'] ?? ($_POST['cs'] ?? ''));
$outcome = trim($_POST['outcome'] ?? '');

$checkout = null;
if ($cs !== '') {
    $stmt = $pdo->prepare("SELECT * FROM paymongo_checkouts WHERE checkout_session_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$cs]);
    $checkout = $stmt->fetch();
}

$pageError = null;
$saleRef = null;
$saleId = null;

if ($checkout && !paymongoIsDemo()) {
    $pageError = 'Demo mode is not enabled. Switch the payment gateway to Demo / Simulator in Settings → Payment Gateway.';
    $checkout = null;
}

if ($checkout && $outcome !== '' && $checkout['status'] === 'pending') {
    $methodLabel = paymongoMethodLabel($checkout['payment_method_hint'] ?: 'paymongo');
    if ($outcome === 'paid' || $outcome === 'success') {
        $final = paymongoSimulatePaid($pdo, $saleManager, $checkout);
        if (!empty($final['success'])) {
            $saleId = $final['sale_id'] ?? null;
            $saleRef = $final['reference'] ?? $checkout['reference'];
        } else {
            $pageError = 'Simulated payment could not be finalized: ' . ($final['message'] ?? 'unknown error');
        }
    } elseif ($outcome === 'failed' || $outcome === 'declined') {
        $reason = trim($_POST['reason'] ?? '');
        if ($reason === '') { $reason = 'Payment declined by the bank or wallet provider.'; }
        $pdo->prepare("UPDATE paymongo_checkouts SET status = 'failed', error_message = ?, updated_at = NOW() WHERE id = ? AND status = 'pending'")
            ->execute([mb_substr($reason, 0, 250), (int)$checkout['id']]);
    } elseif ($outcome === 'expired') {
        $pdo->prepare("UPDATE paymongo_checkouts SET status = 'expired', updated_at = NOW() WHERE id = ? AND status = 'pending'")
            ->execute([(int)$checkout['id']]);
    } elseif ($outcome === 'cancelled') {
        $pdo->prepare("UPDATE paymongo_checkouts SET status = 'cancelled', updated_at = NOW() WHERE id = ? AND status = 'pending'")
            ->execute([(int)$checkout['id']]);
    }

    // Reload the checkout after the simulated event so the page reflects it.
    $stmt = $pdo->prepare("SELECT * FROM paymongo_checkouts WHERE checkout_session_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$cs]);
    $checkout = $stmt->fetch();
}

if ($checkout) {
    $storeName = getSetting('store_name', 'My Store');
    $amount = (float)$checkout['amount'];
    $currency = $checkout['currency'] ?: 'PHP';
    $method = $checkout['payment_method_hint'] ?: 'paymongo';
    $snapshot = json_decode($checkout['cart_snapshot'], true);
    $items = (is_array($snapshot) && !empty($snapshot['items'])) ? $snapshot['items'] : [];

    $methodMeta = [
        'gcash'    => ['label' => 'GCash',        'bg' => '#007DFE',  'fg' => '#ffffff', 'glyph' => '₱'],
        'maya'     => ['label' => 'Maya',         'bg' => '#E51E31',  'fg' => '#ffffff', 'glyph' => 'M'],
        'card'     => ['label' => 'Credit / Debit Card', 'bg' => '#1E293B', 'fg' => '#ffffff', 'glyph' => '💳'],
        'grab_pay' => ['label' => 'GrabPay',      'bg' => '#00B14F',  'fg' => '#ffffff', 'glyph' => 'G'],
        'qrph'     => ['label' => 'QR Ph',        'bg' => '#111827',  'fg' => '#ffffff', 'glyph' => '▦'],
        'paymongo' => ['label' => 'PayMongo',     'bg' => '#0EA5E9',  'fg' => '#ffffff', 'glyph' => '◈'],
    ];
    $meta = $methodMeta[$method] ?? $methodMeta['paymongo'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pay with PayMongo — Checkout</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #F1F5F9; color: #0F172A; min-height: 100vh; display: flex; flex-direction: column; }
    .pm-topbar { background: linear-gradient(135deg, #0F172A 0%, #1E293B 55%, #312E81 100%); color: #fff; }
    .pm-topbar-inner { max-width: 520px; margin: 0 auto; padding: 18px 20px; display: flex; align-items: center; justify-content: space-between; }
    .pm-brand { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 17px; letter-spacing: .2px; }
    .pm-brand-logo { width: 30px; height: 30px; border-radius: 8px; background: linear-gradient(135deg, #6366F1, #2563EB); display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 900; color: #fff; }
    .pm-demo-pill { background: #EF4444; color: #fff; font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 999px; letter-spacing: .4px; }
    .pm-secure { font-size: 12px; color: #A5B4FC; display: flex; align-items: center; gap: 6px; }
    .pm-shell { max-width: 520px; margin: 0 auto; padding: 28px 16px 50px; flex: 1; width: 100%; }
    .pm-card { background: #fff; border-radius: 14px; box-shadow: 0 10px 30px rgba(15,23,42,.08); overflow: hidden; }
    .pm-card-head { padding: 22px 24px 18px; border-bottom: 1px solid #E2E8F0; }
    .pm-merchant { font-size: 13px; color: #64748B; font-weight: 600; }
    .pm-amount { font-size: 34px; font-weight: 800; color: #0F172A; margin-top: 4px; }
    .pm-meta { display: flex; gap: 16px; font-size: 12px; color: #64748B; margin-top: 10px; flex-wrap: wrap; }
    .pm-meta b { color: #334155; }
    .pm-body { padding: 20px 24px 24px; }
    .pm-method-chip { display: inline-flex; align-items: center; gap: 8px; background: #F8FAFC; border: 1px solid #E2E8F0; padding: 6px 12px; border-radius: 999px; font-size: 13px; font-weight: 700; color: #334155; }
    .pm-method-dot { width: 22px; height: 22px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 900; color: #fff; }
    .pm-items { margin-top: 16px; border-top: 1px dashed #E2E8F0; padding-top: 14px; }
    .pm-item { display: flex; justify-content: space-between; font-size: 13px; color: #475569; padding: 3px 0; }
    .pm-item span:first-child { max-width: 70%; }
    .pm-label { font-size: 12px; font-weight: 800; color: #64748B; text-transform: uppercase; letter-spacing: .5px; margin: 18px 0 8px; }
    .pm-input { width: 100%; padding: 12px; border: 1.5px solid #CBD5E1; border-radius: 10px; font-size: 15px; outline: none; }
    .pm-input:focus { border-color: #6366F1; box-shadow: 0 0 0 3px rgba(99,102,241,.15); }
    .pm-input-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px; }
    .pm-btn { width: 100%; border: 0; border-radius: 10px; padding: 14px; font-size: 15px; font-weight: 800; color: #fff; cursor: pointer; margin-top: 14px; }
    .pm-btn:active { transform: translateY(1px); }
    .pm-btn-approve { background: linear-gradient(135deg, #10B981, #059669); }
    .pm-btn-decline { background: #EF4444; }
    .pm-btn-fake { background: linear-gradient(135deg, #6366F1, #2563EB); }
    .pm-link { display: inline-block; margin-top: 12px; font-size: 13px; color: #94A3B8; text-decoration: underline; cursor: pointer; background: none; border: 0; }
    .pm-note { margin-top: 14px; background: #F0FDF4; border: 1px solid #BBF7D0; color: #166534; font-size: 12.5px; border-radius: 10px; padding: 10px 12px; line-height: 1.55; }
    .pm-note b { color: #14532D; }
    .pm-card-brand { height: 34px; background: linear-gradient(120deg, #1E293B, #334155); border-radius: 8px; margin-bottom: 10px; display: flex; align-items: center; padding: 0 10px; color: #E2E8F0; font-size: 12px; font-weight: 700; }
    .pm-result { text-align: center; padding: 34px 24px 38px; }
    .pm-result-icon { width: 64px; height: 64px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 30px; }
    .pm-result-ok { background: #D1FAE5; color: #059669; }
    .pm-result-bad { background: #FEE2E2; color: #DC2626; }
    .pm-result-x { background: #F1F5F9; color: #64748B; }
    .pm-result-title { font-size: 20px; font-weight: 800; margin-top: 16px; }
    .pm-result-sub { color: #64748B; font-size: 14px; margin-top: 8px; line-height: 1.55; }
    .pm-result-row { margin-top: 14px; font-size: 13px; color: #475569; }
    .pm-result-row b { color: #0F172A; }
    .pm-footer { max-width: 520px; margin: 0 auto; padding: 0 20px 34px; font-size: 12px; color: #94A3B8; text-align: center; line-height: 1.6; }
    .pm-testcards { margin-top: 12px; font-size: 12px; color: #475569; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 10px 12px; line-height: 1.6; }
    .pm-testcards code { background: #E2E8F0; padding: 1px 5px; border-radius: 4px; font-size: 11px; }
    .pm-error { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 12px 14px; border-radius: 10px; font-size: 13px; margin-top: 16px; line-height: 1.55; }
</style>
</head>
<body>

<div class="pm-topbar">
    <div class="pm-topbar-inner">
        <div class="pm-brand">
            <div class="pm-brand-logo">P</div>
            PayMongo
            <span class="pm-demo-pill">DEMO</span>
        </div>
        <div class="pm-secure">🔒 Secure checkout</div>
    </div>
</div>

<div class="pm-shell">

<?php if ($pageError && !$checkout): ?>
    <div class="pm-card">
        <div class="pm-body">
            <div class="pm-result" style="text-align:left;padding:24px;">
                <div class="pm-result-icon pm-result-bad" style="display:inline-flex;vertical-align:middle;margin-right:10px;">⚠️</div>
                <div class="pm-result-title" style="margin-top:0;">Checkout unavailable</div>
                <div class="pm-result-sub"><?php echo htmlspecialchars($pageError); ?></div>
                <div class="pm-result-sub" style="margin-top:10px;">Make sure you open the exact link/QR shown in the POS cart and that Demo mode is enabled.</div>
            </div>
        </div>
    </div>

<?php elseif (!$checkout): ?>
    <div class="pm-card">
        <div class="pm-body">
            <div class="pm-result" style="text-align:left;padding:24px;">
                <div class="pm-result-icon pm-result-x" style="display:inline-flex;vertical-align:middle;margin-right:10px;">🔍</div>
                <div class="pm-result-title" style="margin-top:0;">Payment session not found</div>
                <div class="pm-result-sub">This checkout session does not exist or has already been completed. Return to the POS and create a new payment.</div>
            </div>
        </div>
    </div>

<?php else: ?>

    <div class="pm-card">
        <div class="pm-card-head">
            <div class="pm-merchant"><?php echo htmlspecialchars($storeName); ?></div>
            <div class="pm-amount"><?php echo htmlspecialchars($currency); ?> <?php echo number_format($amount, 2); ?></div>
            <div class="pm-meta">
                <span>Reference: <b><?php echo htmlspecialchars($checkout['reference']); ?></b></span>
                <span>Status: <b><?php echo strtoupper(htmlspecialchars($checkout['status'])); ?></b></span>
            </div>
        </div>

        <div class="pm-body">
            <?php if ($checkout['status'] === 'paid'): ?>
                <div class="pm-result">
                    <div class="pm-result-icon pm-result-ok">✓</div>
                    <div class="pm-result-title">Payment completed</div>
                    <div class="pm-result-sub">The payment was approved. This window can be closed — the POS has recorded the sale automatically.</div>
                    <div class="pm-result-row">Reference: <b><?php echo htmlspecialchars($checkout['reference']); ?></b> · Payment: <b><?php echo htmlspecialchars($checkout['payment_id'] ?: 'pay_demo_' . $checkout['id']); ?></b></div>
                    <?php if ($saleId): ?><div class="pm-result-row">POS Sale: <b>#<?php echo (int)$saleId; ?></b> (<?php echo htmlspecialchars($saleRef ?: ''); ?>)</div><?php endif; ?>
                    <?php if (!empty($checkout['return_token'])): ?>
                    <a href="<?php echo htmlspecialchars(paymongoResultUrl($checkout['reference'], 'success', $checkout['return_token'])); ?>" class="pm-btn pm-btn-approve" style="display:block;text-decoration:none;text-align:center;margin-top:20px;">Return to POS — view confirmation</a>
                    <?php endif; ?>
                </div>

            <?php elseif (in_array($checkout['status'], ['failed', 'expired', 'cancelled', 'processing'], true)): ?>
                <div class="pm-result">
                    <div class="pm-result-icon pm-result-<?php echo $checkout['status'] === 'failed' ? 'bad' : 'x'; ?>"><?php echo $checkout['status'] === 'failed' ? '✕' : '⏸'; ?></div>
                    <div class="pm-result-title">Payment <?php echo htmlspecialchars($checkout['status']); ?></div>
                    <div class="pm-result-sub">
                        <?php
                        if ($checkout['status'] === 'failed') {
                            echo htmlspecialchars($checkout['error_message'] ?: 'The payment was declined by the bank or wallet provider.');
                        } elseif ($checkout['status'] === 'expired') {
                            echo 'This checkout session has expired. Ask the cashier to create a new payment.';
                        } elseif ($checkout['status'] === 'cancelled') {
                            echo 'This checkout session was cancelled.';
                        } else {
                            echo 'The payment is being processed.';
                        }
                        ?>
                    </div>
                    <?php if (!empty($checkout['return_token'])): ?>
                    <a href="<?php echo htmlspecialchars(paymongoResultUrl($checkout['reference'], 'cancel', $checkout['return_token'])); ?>" class="pm-btn pm-btn-fake" style="display:block;text-decoration:none;text-align:center;margin-top:20px;">Return to POS</a>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <!-- Still pending: show the simulated payment widget -->
                <div class="pm-method-chip">
                    <span class="pm-method-dot" style="background:<?php echo $meta['bg']; ?>;"><?php echo $meta['glyph']; ?></span>
                    <?php echo htmlspecialchars($meta['label']); ?>
                </div>

                <?php if (!empty($items)): ?>
                <div class="pm-items">
                    <?php foreach ($items as $it): ?>
                    <div class="pm-item">
                        <span><?php echo htmlspecialchars($it['name']); ?> × <?php echo (int)$it['qty']; ?></span>
                        <span><?php echo number_format((float)$it['price'] * (int)$it['qty'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if ((float)($snapshot['discount'] ?? 0) > 0): ?>
                    <div class="pm-item"><span>Discount</span><span>−<?php echo number_format((float)$snapshot['discount'], 2); ?></span></div>
                    <?php endif; ?>
                    <div class="pm-item" style="font-weight:800;color:#0F172A;margin-top:6px;border-top:1px solid #E2E8F0;padding-top:8px;"><span>Total</span><span><?php echo number_format($amount, 2); ?></span></div>
                </div>
                <?php endif; ?>

                <form method="post" id="simForm">
                    <input type="hidden" name="cs" value="<?php echo htmlspecialchars($cs); ?>">
                    <input type="hidden" name="outcome" id="simOutcome" value="">
                    <input type="hidden" name="reason" id="simReason" value="">

                    <?php if ($method === 'card'): ?>
                        <div class="pm-label">Card payment</div>
                        <div class="pm-card-brand">💳 Simulated Card • 3-D Secure enabled</div>
                        <input class="pm-input" id="simCardNo" value="4343 4343 4343 4345" maxlength="19" placeholder="Card number">
                        <div class="pm-input-row">
                            <input class="pm-input" value="12 / 28" placeholder="MM / YY">
                            <input class="pm-input" value="123" placeholder="CVC">
                        </div>
                        <div class="pm-label">PayMongo test card</div>
                        <select class="pm-input" id="simCard" style="cursor:pointer;">
                            <option value="4343434343434345">4343 4343 4343 4345 — use this to approve (success)</option>
                            <option value="4000000000000002">4000 0000 0000 0002 — generic decline</option>
                            <option value="4000000000009995">4000 0000 0000 9995 — insufficient funds</option>
                            <option value="4000000000009987">4000 0000 0000 9987 — lost card</option>
                            <option value="4000000000009979">4000 0000 0000 9979 — stolen card</option>
                            <option value="4000000000000069">4000 0000 0000 0069 — expired card</option>
                            <option value="4000000000000127">4000 0000 0000 0127 — invalid CVC</option>
                        </select>
                        <button type="button" class="pm-btn pm-btn-fake" onclick="simCardPay()">Pay <?php echo number_format($amount, 2); ?> PHP</button>
                        <div class="pm-testcards">These are the same test cards PayMongo documents. The output is chosen automatically from the card number — just like a real test-mode integration.</div>

                    <?php else: ?>
                        <?php $demoPhone = ($method === 'gcash') ? '0917 123 4567' : '0999 555 0123'; ?>
                        <div class="pm-label"><?php echo htmlspecialchars($meta['label']); ?> mobile number</div>
                        <input class="pm-input" value="<?php echo $demoPhone; ?>" disabled>
                        <div class="pm-note">
                            <b>This is a simulated payment.</b> No real transaction happens. Choose an outcome below to see how the POS reacts:
                            approve to record the sale, or decline / expire to show the failed flow.
                        </div>
                        <button type="button" class="pm-btn pm-btn-approve" onclick="simSubmit('paid')">✅ Approve payment — <?php echo number_format($amount, 2); ?> PHP</button>
                        <button type="button" class="pm-btn pm-btn-decline" onclick="simSubmit('failed')">✕ Decline payment</button>
                        <button type="button" class="pm-link" onclick="simSubmit('expired')">Mark session expired</button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($checkout['status'] === 'pending'): ?>
    <div class="pm-note" style="margin-top:16px;">
        <b>How to run this demo:</b> the cashier's POS is watching this checkout. Open the cart page and tap <b>Check status</b> — once you approve the payment, the sale is created, stock is deducted and the receipt prints.
    </div>
    <?php endif; ?>

<?php endif; ?>

</div>

<div class="pm-footer">
    <b>PayMongo</b> checkouts are PCI-DSS compliant. This page is a local <b>demo simulator</b> that mimics PayMongo's checkout flow — no card data is stored and no real payment is processed. Powered by the POS.
</div>

<script>
    var CARD_OUTCOMES = {
        '4343434343434345': ['paid', ''],
        '4000000000000002': ['failed', 'Your card was declined (generic decline).'],
        '4000000000009995': ['failed', 'Your card has insufficient funds.']
    };
    function simSubmit(outcome){
        var scaffold = { paid: '', failed: 'Payment declined by the card issuer.', expired: '' };
        var r = document.getElementById('simReason');
        if (r) r.value = scaffold[outcome] || '';
        document.getElementById('simOutcome').value = outcome;
        document.getElementById('simForm').submit();
    }
    function simCardPay(){
        var num = document.getElementById('simCard').value;
        var m = CARD_OUTCOMES[num] || ['failed', 'Your card was declined (generic decline).'];
        if (num === '4000000000009987') m = ['failed', 'Your card was reported lost.'];
        if (num === '4000000000009979') m = ['failed', 'Your card was reported stolen.'];
        if (num === '4000000000000069') m = ['failed', 'Your card has expired.'];
        if (num === '4000000000000127') m = ['failed', 'Invalid CVC.'];
        document.getElementById('simOutcome').value = m[0];
        var r = document.getElementById('simReason');
        if (r) r.value = m[1];
        document.getElementById('simForm').submit();
    }
</script>

</body>
</html>