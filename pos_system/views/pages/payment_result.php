<?php
// ============================================
// PAYMENT RESULT / CONFIRMATION PAGE
// ============================================
// Landing page after a PayMongo Hosted Checkout (success or cancel URL), and
// the same page used for pending / failed / expired outcomes. The payment
// status shown here is ALWAYS re-read from the database via get_paymongo_result
// — the `result=success` query flag is never trusted on its own.
if (!canAccess('payment_result')) {
    echo '<div class="inv-card" style="margin:2rem;text-align:center;padding:40px;">
        <h2 style="color:#111827;">Access Denied</h2>
        <p style="color:#6b7280;margin-top:0.5rem;">You do not have permission to view payment confirmations.</p>
        <a href="?page=dashboard" class="inv-btn-primary" style="margin-top:1rem;text-decoration:none;display:inline-block;">Go to Dashboard</a>
    </div>';
    return;
}

$prReference = isset($_GET['ref']) ? (string)$_GET['ref'] : '';
$prResultHint = isset($_GET['result']) ? strtolower((string)$_GET['result']) : '';
$prToken = isset($_GET['token']) ? (string)$_GET['token'] : '';
$prStoreName = $store_name ?? getSetting('store_name', 'Smart Market');
$prStoreAddress = getSetting('store_address', '');
$prStoreContact = getSetting('store_contact', '');
// Safe JSON for embedding inside <script> (prevents </script> breakout).
$prJson = function ($v) {
    return json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
};
?>

<style>
    #prWrap { max-width: 600px; margin: 0 auto; }
    .pr-card { background:#ffffff; border:1px solid #E5E7EB; border-radius:20px; box-shadow:0 12px 34px rgba(15,23,42,.08); overflow:hidden; }
    .pr-head { padding:34px 30px 22px; text-align:center; }
    .pr-icon { width:76px; height:76px; border-radius:50%; margin:0 auto 16px; display:flex; align-items:center; justify-content:center; }
    .pr-icon svg { width:40px; height:40px; }
    .pr-title { font-size:23px; font-weight:800; color:#0F172A; margin:0; letter-spacing:-.02em; }
    .pr-sub { margin-top:8px; font-size:14px; color:#64748B; }
    .pr-brand { display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:14px; font-weight:800; color:#1E293B; font-size:14px; letter-spacing:.02em; text-transform:uppercase; }
    .pr-brand .dot { width:9px; height:9px; border-radius:50%; background:#2563EB; display:inline-block; }
    .pr-body { padding:0 30px 8px; }
    .pr-rows { border-top:1px dashed #E2E8F0; margin-top:6px; padding-top:18px; }
    .pr-row { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:10px 0; font-size:14px; }
    .pr-row .k { color:#64748B; }
    .pr-row .v { color:#0F172A; font-weight:700; text-align:right; word-break:break-word; }
    .pr-amount .v { font-size:20px; color:#0F172A; }
    .pr-actions { padding:22px 30px 30px; display:flex; gap:12px; flex-wrap:wrap; }
    .pr-btn { flex:1 1 auto; min-width:150px; text-align:center; padding:13px 18px; border-radius:12px; font-weight:700; font-size:14px; text-decoration:none; cursor:pointer; border:1px solid transparent; transition:.15s; }
    .pr-btn-primary { background:#2563EB; color:#fff; }
    .pr-btn-primary:hover { background:#1D4ED8; }
    .pr-btn-ghost { background:#F1F5F9; color:#1E293B; border-color:#E2E8F0; }
    .pr-btn-ghost:hover { background:#E2E8F0; }
    .pr-note { margin:0 30px 22px; padding:12px 14px; border-radius:12px; font-size:13px; line-height:1.5; }
    .pr-spinner { width:34px; height:34px; border:3px solid #E2E8F0; border-top-color:#2563EB; border-radius:50%; animation:prspin 1s linear infinite; margin:60px auto; }
    @keyframes prspin { to { transform:rotate(360deg); } }
    .pr-receipt { display:none; }
    @media print {
        body * { visibility:hidden !important; }
        #prPrintArea, #prPrintArea * { visibility:visible !important; }
        #prPrintArea { position:absolute; left:0; top:0; width:100%; display:block !important; padding:0; }
        .no-print { display:none !important; }
    }
</style>

<div id="prWrap">
    <div class="pr-card" id="prCard">
        <div class="pr-spinner"></div>
    </div>
</div>

<!-- Hidden until a sale exists; becomes the printable receipt. -->
<div id="prPrintArea" class="pr-receipt"></div>

<script>
(function () {
    var PR_REF = <?php echo $prJson($prReference); ?>;
    var PR_RESULT = <?php echo $prJson($prResultHint); ?>;
    var PR_TOKEN = <?php echo $prJson($prToken); ?>;
    var PR_STORE = <?php echo $prJson($prStoreName); ?>;
    var STORE_ADDRESS = <?php echo $prJson($prStoreAddress); ?>;
    var STORE_CONTACT = <?php echo $prJson($prStoreContact); ?>;
    var prTimer = null;

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function peso(n) { return '₱' + parseFloat(n || 0).toFixed(2); }
    function fmtDate(d) {
        if (!d) return '—';
        var x = new Date(String(d).replace(' ', 'T'));
        if (isNaN(x.getTime())) return String(d);
        return x.toLocaleString('en-PH', { year:'numeric', month:'short', day:'2-digit', hour:'2-digit', minute:'2-digit' });
    }

    function iconBox(kind) {
        var map = {
            success:  { bg:'#DCFCE7', fg:'#16A34A', svg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>' },
            pending:  { bg:'#FEF3C7', fg:'#D97706', svg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><polyline points="12 7 12 12 15 14"></polyline></svg>' },
            failed:   { bg:'#FEE2E2', fg:'#DC2626', svg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' },
            cancel:   { bg:'#F3F4F6', fg:'#6B7280', svg:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><line x1="8" y1="12" x2="16" y2="12"></line></svg>' }
        };
        var m = map[kind] || map.cancel;
        return '<div class="pr-icon" style="background:' + m.bg + ';color:' + m.fg + '">' + m.svg + '</div>';
    }

    function brand() {
        return '<div class="pr-brand"><span class="dot"></span>' + esc(PR_STORE) + '</div>';
    }

    function rows(pairs) {
        var html = '';
        pairs.forEach(function (p) {
            if (p.value === null || p.value === undefined || p.value === '') return;
            html += '<div class="pr-row' + (p.big ? ' pr-amount' : '') + '"><span class="k">' + esc(p.label) + '</span><span class="v">' + esc(p.value) + '</span></div>';
        });
        return html;
    }

    function errorCard(title, msg) {
        document.getElementById('prCard').innerHTML =
            '<div class="pr-head">' + brand() + iconBox('failed') +
            '<h1 class="pr-title">' + esc(title) + '</h1>' +
            '<p class="pr-sub">' + esc(msg) + '</p></div>' +
            '<div class="pr-actions"><a class="pr-btn pr-btn-primary" href="?page=cart">Return to POS</a>' +
            '<a class="pr-btn pr-btn-ghost" href="?page=dashboard">Dashboard</a></div>';
    }

    function render(data) {
        if (!data || !data.success) {
            errorCard('Payment not found', (data && data.message) ? data.message : 'We could not find this payment.');
            return;
        }

        var status = data.status;
        // The DB is authoritative; the query `result` flag only distinguishes a
        // cancel redirect while the session is still technically pending.
        var state = data.paid ? 'success'
            : (status === 'pending' && PR_RESULT === 'cancel') ? 'cancel'
            : (status === 'failed' || status === 'expired' || status === 'cancelled' || status === 'cancel') ? status
            : (status === 'refunded') ? 'refunded'
            : 'pending';

        var titles = {
            success:   ['Payment Received!', 'Your online payment was confirmed.'],
            pending:   ['Payment Pending', 'We are waiting for PayMongo to confirm this payment.'],
            failed:    ['Payment Failed', 'The payment did not go through. No amount was charged.'],
            expired:   ['Payment Expired', 'This checkout session expired before payment was completed.'],
            cancelled: ['Payment Cancelled', 'This checkout was cancelled. No amount was charged.'],
            cancel:    ['Payment Cancelled', 'This checkout was cancelled. No amount was charged.'],
            refunded:  ['Payment Refunded', 'This payment has been refunded.']
        };
        var kindMap = { success:'success', pending:'pending', failed:'failed', expired:'cancel', cancelled:'cancel', cancel:'cancel', refunded:'cancel' };
        var meta = titles[state] || titles.pending;

        var detailRows = [
            { label:'Reference',        value:data.reference },
            { label:'Amount',           value:peso(data.amount), big:true },
            { label:'Payment method',   value:data.payment_label },
            { label:'Date & time',      value:fmtDate(data.paid_at) }
        ];
        if (state === 'success' && data.sale) {
            detailRows.splice(1, 0, { label:'Order number', value:data.sale.invoice_number });
            if (data.sale.paymongo_payment_id) {
                detailRows.push({ label:'Payment ID', value:data.sale.paymongo_payment_id });
            }
        }
        if (data.error && state !== 'success') {
            detailRows.push({ label:'Details', value:data.error });
        }

        var actions = '';
        if (state === 'success') {
            actions = '<a class="pr-btn pr-btn-primary" href="?page=cart">Return to POS</a>' +
                      '<button type="button" class="pr-btn pr-btn-ghost" id="prPrintBtn">Print Receipt</button>';
        } else if (state === 'pending') {
            actions = '<button type="button" class="pr-btn pr-btn-primary" id="prRefreshBtn">Check status again</button>' +
                      '<a class="pr-btn pr-btn-ghost" href="?page=cart">Return to POS</a>';
        } else {
            actions = '<a class="pr-btn pr-btn-primary" href="?page=cart">Try again / Return to POS</a>' +
                      '<a class="pr-btn pr-btn-ghost" href="?page=dashboard">Dashboard</a>';
        }

        document.getElementById('prCard').innerHTML =
            '<div class="pr-head">' + brand() + iconBox(kindMap[state]) +
                '<h1 class="pr-title">' + esc(meta[0]) + '</h1>' +
                '<p class="pr-sub">' + esc(meta[1]) + '</p>' +
            '</div>' +
            '<div class="pr-body"><div class="pr-rows">' + rows(detailRows) + '</div></div>' +
            (state === 'pending'
                ? '<div class="pr-note" style="background:#FFFBEB;color:#92400E;border:1px solid #FDE68A;">If you already paid, give it a few seconds and tap <strong>Check status again</strong>.</div>'
                : '') +
            '<div class="pr-actions">' + actions + '</div>';

        if (state === 'success' && data.sale) { buildReceipt(data.sale, data); }

        var printBtn = document.getElementById('prPrintBtn');
        if (printBtn) {
            printBtn.addEventListener('click', function () {
                if (prTimer) { clearTimeout(prTimer); prTimer = null; }
                window.print();
            });
        }
        var refreshBtn = document.getElementById('prRefreshBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () { fetchResult(); });
        }

        if (state === 'pending') {
            if (prTimer) clearTimeout(prTimer);
            prTimer = setTimeout(fetchResult, 5000);
        }
    }

    function buildReceipt(sale, data) {
        var itemsHtml = (sale.items || []).map(function (item) {
            return '<div style="margin-bottom:6px;">' +
                '<div>' + esc(item.product_name) + '</div>' +
                '<div style="display:flex;justify-content:space-between;"><span>' + esc(item.quantity) + ' \u00d7 ' + peso(item.unit_price) + '</span><span>' + peso(item.total_price) + '</span></div>' +
            '</div>';
        }).join('');

        document.getElementById('prPrintArea').innerHTML =
            '<div style="max-width:340px;margin:0 auto;font-family:\'Courier New\',monospace;font-size:12px;color:#000;">' +
                '<div style="text-align:center;font-weight:bold;font-size:15px;">' + esc(PR_STORE) + '</div>' +
                (STORE_ADDRESS ? '<div style="text-align:center;">' + esc(STORE_ADDRESS) + '</div>' : '') +
                (STORE_CONTACT ? '<div style="text-align:center;">' + esc(STORE_CONTACT) + '</div>' : '') +
                '<div style="text-align:center;margin:8px 0;">Invoice: ' + esc(sale.invoice_number) + '<br>' + esc(fmtDate(sale.sale_date)) + '</div>' +
                '<div style="border-top:1px dashed #000;margin:8px 0;"></div>' +
                itemsHtml +
                '<div style="border-top:1px dashed #000;margin:8px 0;"></div>' +
                '<div style="display:flex;justify-content:space-between;"><span>Subtotal</span><span>' + peso(sale.subtotal) + '</span></div>' +
                '<div style="display:flex;justify-content:space-between;"><span>VAT</span><span>' + peso(sale.tax) + '</span></div>' +
                (parseFloat(sale.discount_amount) > 0 ? '<div style="display:flex;justify-content:space-between;"><span>Discount</span><span>-' + peso(sale.discount_amount) + '</span></div>' : '') +
                '<div style="display:flex;justify-content:space-between;font-weight:bold;font-size:14px;margin-top:4px;"><span>TOTAL</span><span>' + peso(sale.total_amount) + '</span></div>' +
                '<div style="display:flex;justify-content:space-between;"><span>Paid</span><span>' + peso(sale.amount_paid) + '</span></div>' +
                '<div style="display:flex;justify-content:space-between;"><span>Method</span><span>' + esc(data.payment_label) + '</span></div>' +
                (sale.paymongo_payment_id ? '<div style="margin-top:4px;">Payment ID: ' + esc(sale.paymongo_payment_id) + '</div>' : '') +
                (sale.cashier ? '<div>Cashier: ' + esc(sale.cashier) + '</div>' : '') +
                '<div style="text-align:center;margin-top:10px;">Thank you!</div>' +
            '</div>';
    }

    function fetchResult() {
        if (prTimer) { clearTimeout(prTimer); prTimer = null; }
        var url = '?action=get_paymongo_result&ref=' + encodeURIComponent(PR_REF) + '&token=' + encodeURIComponent(PR_TOKEN);
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(render)
            .catch(function (err) { errorCard('Something went wrong', err.message || 'Please try again.'); });
    }

    fetchResult();
})();
</script>
