<?php
if (!canAccess('payment_gateway')) {
    echo '<div class="inv-card" style="margin:2rem;text-align:center;padding:40px;">
        <h2 style="color:#111827;">Access Denied</h2>
        <p style="color:#6b7280;margin-top:0.5rem;">Only admins and finance can manage the payment gateway.</p>
        <a href="?page=dashboard" class="inv-btn-primary" style="margin-top:1rem;text-decoration:none;display:inline-block;">Go to Dashboard</a>
    </div>';
    return;
}
?>

<div class="inv-page-header">
    <div class="inv-page-header-left">
        <h2 class="inv-page-title">Payment Gateway</h2>
        <p class="inv-page-subtitle">Accept GCash, Maya, GrabPay, QR Ph and cards online through PayMongo</p>
    </div>
    <span id="pgStatusPill" style="padding:6px 14px;border-radius:999px;font-size:12px;font-weight:800;background:#F3F4F6;color:#6B7280;">Loading…</span>
</div>

<div class="inv-card" style="margin-bottom:1.25rem;">
    <div class="inv-card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <div class="inv-card-title">Connection</div>
        <div style="display:flex;gap:8px;">
            <button type="button" class="inv-btn-primary" id="pgTestBtn" style="background:#0EA5E9;">Test connection</button>
            <button type="button" class="inv-btn-primary" id="pgSaveBtn">Save settings</button>
        </div>
    </div>
    <div style="padding:1.25rem;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem;">
            <div>
                <label style="display:block;font-weight:700;font-size:13px;color:#374151;margin-bottom:6px;">Gateway status</label>
                <label style="display:flex;align-items:center;gap:8px;font-size:14px;color:#111827;">
                    <input type="checkbox" id="pgEnabled" style="width:18px;height:18px;"> Enable PayMongo at checkout
                </label>
            </div>
            <div>
                <label style="display:block;font-weight:700;font-size:13px;color:#374151;margin-bottom:6px;">Mode</label>
                <select id="pgMode" style="width:100%;padding:9px;border:1px solid #D1D5DB;border-radius:8px;">
                    <option value="demo">Demo / Simulator (fake, no keys needed)</option>
                    <option value="test">Test / Sandbox (real PayMongo keys)</option>
                    <option value="live">Live</option>
                </select>
            </div>
            <div id="pgDemoNote" style="display:none;grid-column:1 / -1;padding:12px 14px;background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;font-size:13px;color:#065F46;line-height:1.6;">
                <b>🛠 Demo simulator active.</b> No real money moves and no real PayMongo keys are needed.
                Checkouts get a fake <code>cs_demo_…</code> session, and the customer pays on a
                <b>simulated PayMongo checkout page</b> served by this app. Approve it and the payment is
                confirmed via the normal webhook path (signed), so the sale, stock and receipt behave exactly
                like a real GCash / Maya / card payment. Demo keys are auto-generated so this page looks fully configured.
            </div>
            <div>
                <label style="display:block;font-weight:700;font-size:13px;color:#374151;margin-bottom:6px;">Public key</label>
                <input type="text" id="pgPublic" placeholder="pk_test_..." style="width:100%;padding:9px;border:1px solid #D1D5DB;border-radius:8px;">
            </div>
            <div>
                <label style="display:block;font-weight:700;font-size:13px;color:#374151;margin-bottom:6px;">Secret key <span id="pgSecretState" style="font-weight:500;color:#9CA3AF;"></span></label>
                <input type="password" id="pgSecret" placeholder="Leave blank to keep current" autocomplete="new-password" style="width:100%;padding:9px;border:1px solid #D1D5DB;border-radius:8px;">
            </div>
            <div>
                <label style="display:block;font-weight:700;font-size:13px;color:#374151;margin-bottom:6px;">Webhook signing secret <span id="pgWebhookState" style="font-weight:500;color:#9CA3AF;"></span></label>
                <input type="password" id="pgWebhookSecret" placeholder="Leave blank to keep current" autocomplete="new-password" style="width:100%;padding:9px;border:1px solid #D1D5DB;border-radius:8px;">
            </div>
            <div>
                <label style="display:block;font-weight:700;font-size:13px;color:#374151;margin-bottom:6px;">Allowed methods</label>
                <input type="text" id="pgMethods" placeholder="gcash,maya,grab_pay,card,qrph" style="width:100%;padding:9px;border:1px solid #D1D5DB;border-radius:8px;">
                <div style="font-size:11px;color:#9CA3AF;margin-top:4px;">Comma-separated. Must be enabled in your PayMongo dashboard.</div>
            </div>
            <div>
                <label style="display:block;font-weight:700;font-size:13px;color:#374151;margin-bottom:6px;">Success URL (optional)</label>
                <input type="text" id="pgSuccess" placeholder="Defaults to the POS confirmation page" style="width:100%;padding:9px;border:1px solid #D1D5DB;border-radius:8px;">
            </div>
            <div>
                <label style="display:block;font-weight:700;font-size:13px;color:#374151;margin-bottom:6px;">Cancel URL (optional)</label>
                <input type="text" id="pgCancel" placeholder="Defaults to the POS confirmation page" style="width:100%;padding:9px;border:1px solid #D1D5DB;border-radius:8px;">
            </div>
        </div>

        <div style="margin-top:1.25rem;padding:14px;background:#F9FAFB;border:1px dashed #D1D5DB;border-radius:10px;">
            <div style="font-weight:700;font-size:13px;color:#374151;margin-bottom:6px;">Webhook endpoint (paste into PayMongo → Developers → Webhooks)</div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <code id="pgWebhookUrl" style="font-size:12px;background:#111827;color:#A5B4FC;padding:8px 10px;border-radius:8px;word-break:break-all;">…</code>
                <button type="button" id="pgCopyBtn" style="padding:7px 12px;border:1px solid #D1D5DB;background:#fff;border-radius:8px;cursor:pointer;font-size:12px;">Copy</button>
            </div>
            <div style="font-size:12px;color:#9CA3AF;margin-top:8px;">
                Subscribe to these events:
                <code>checkout_session.payment.paid</code>, <code>payment.paid</code>,
                <code>checkout_session.payment.failed</code>, <code>payment.failed</code>,
                <code>payment.refunded</code>.
                Local XAMPP installs are not reachable from the internet — use the POS <b>Check status</b> button for sandbox testing
                (or expose this URL with a tunnel such as ngrok).
                In <b>demo mode</b> this endpoint is called automatically by the simulator (signed), just like a real webhook delivery.
                <br>Signatures are verified with your webhook signing secret (<code>whsec_…</code>); unverified requests are rejected with HTTP 401.
            </div>
        </div>
    </div>
</div>

<div class="inv-card" style="margin-bottom:1.25rem;">
    <div class="inv-card-header"><div class="inv-card-title">How it works</div></div>
    <div style="padding:1.25rem;font-size:14px;color:#374151;line-height:1.7;">
        <ol style="margin:0;padding-left:1.2rem;">
            <li>The cashier builds a cart and chooses a <b>PayMongo</b> method (e.g. GCash) at checkout.</li>
            <li>The server verifies the cart and creates a hosted <b>Checkout Session</b> — a payment page and QR the customer can open.</li>
            <li>The customer pays with GCash, Maya, GrabPay, QR Ph or a card on PayMongo's secure page.</li>
            <li>PayMongo confirms via <b>webhook</b> (production). Locally, the cashier taps <b>Check status</b> to poll PayMongo.</li>
            <li>Once confirmed, the sale is recorded exactly once, stock is deducted, and the receipt is shown.</li>
        </ol>
        <div style="margin-top:10px;font-size:13px;color:#6B7280;">
            Amounts are converted to centavos automatically (₱10.00 = 1000). No card data ever touches your server — it stays on PayMongo.
        </div>
        <div style="margin-top:8px;font-size:13px;color:#6B7280;">
            <b>Demo mode:</b> the same flow runs against a local <b>simulated PayMongo</b> with a fake checkout page
            (approve / decline / expire buttons and PayMongo-style test cards). Perfect for demos and training — no keys, no money.
        </div>
    </div>
</div>

<div class="inv-card">
    <div class="inv-card-header">
        <div class="inv-card-title">Recent online payments</div>
        <button type="button" class="inv-btn-primary" id="pgRefreshBtn" style="background:#6B7280;">Refresh</button>
    </div>
    <table class="inv-table" style="width:100%;">
        <thead><tr><th>Reference</th><th>Created</th><th>Method</th><th>Amount</th><th>Fee</th><th>Net</th><th>Status</th><th>Sale</th><th>Cashier</th></tr></thead>
        <tbody id="pgRows"><tr><td colspan="9">Loading…</td></tr></tbody>
    </table>
</div>

<script>
(function(){
    function $(id){ return document.getElementById(id); }
    function toast(type, title, msg){ if (window.showToast) showToast(type, title, msg); else alert(msg); }
    function money(n){ return '₱' + parseFloat(n || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}); }
    var CSRF_TOKEN = <?php echo json_encode(csrfToken()); ?>;

    function statusColor(status){
        var map = { paid:'#10B981', pending:'#F59E0B', processing:'#6366F1', failed:'#EF4444', expired:'#6B7280', cancelled:'#6B7280', refunded:'#8B5CF6' };
        return map[status] || '#6B7280';
    }

    function loadSettings(){
        fetch('?action=get_paymongo_settings').then(function(r){ return r.json(); }).then(function(d){
            if (!d.success) { toast('error', 'Error', d.message || 'Unable to load settings'); return; }
            $('pgEnabled').checked = !!d.enabled;
            $('pgMode').value = d.mode || 'test';
            $('pgPublic').value = d.public_key || '';
            $('pgMethods').value = d.methods || '';
            $('pgSuccess').value = d.success_url || '';
            $('pgCancel').value = d.cancel_url || '';
            $('pgWebhookUrl').textContent = d.webhook_url || '';
            $('pgSecretState').textContent = d.env_secret ? '(from .env ✓)' : (d.has_secret_key ? '(saved ✓)' : '(none)');
            $('pgWebhookState').textContent = d.env_webhook_secret ? '(from .env ✓)' : (d.has_webhook_secret ? '(saved ✓)' : '(none)');

            var demo = d.mode === 'demo';
            $('pgDemoNote').style.display = demo ? 'block' : 'none';

            var pill = $('pgStatusPill');
            if (d.configured) {
                var modeLabel = demo ? 'Demo simulator' : (d.mode === 'live' ? 'Live' : 'Test');
                pill.textContent = '● Active (' + modeLabel + ')';
                pill.style.background = demo ? '#ECFDF5' : '#ECFDF5'; pill.style.color = demo ? '#0D9488' : '#059669';
            } else {
                pill.textContent = '● Not configured';
                pill.style.background = '#FEF2F2'; pill.style.color = '#DC2626';
            }
        }).catch(function(){ toast('error', 'Error', 'Network error'); });
    }

    function loadCheckouts(){
        fetch('?action=get_paymongo_checkouts').then(function(r){ return r.json(); }).then(function(d){
            var body = $('pgRows');
            if (!d.success || !d.checkouts || !d.checkouts.length) {
                body.innerHTML = '<tr><td colspan="9" style="text-align:center;color:#9CA3AF;padding:18px;">No online payments yet</td></tr>';
                return;
            }
            body.innerHTML = d.checkouts.map(function(c){
                var badge = '<span style="padding:2px 10px;border-radius:999px;font-size:11px;font-weight:800;background:' + statusColor(c.status) + '22;color:' + statusColor(c.status) + ';">' + c.status + '</span>';
                var method = (c.payment_type || c.payment_method_hint || '').replace('_',' ') || '—';
                var sale = c.sale_id ? ('#' + c.sale_id) : '—';
                var created = c.created_at ? new Date(c.created_at.replace(' ','T')).toLocaleString() : '—';
                // PayMongo's cut, and what settles to us. Only shown once PayMongo
                // has reported it, so Finance can reconcile against the settlement.
                var fee = (c.fee === null || c.fee === undefined) ? '—' : money(c.fee);
                var net = (c.net_amount === null || c.net_amount === undefined) ? '—' : money(c.net_amount);
                return '<tr><td>' + c.reference + '</td><td>' + created + '</td><td>' + method + '</td><td>' + money(c.amount) + '</td><td>' + fee + '</td><td>' + net + '</td><td>' + badge + '</td><td>' + sale + '</td><td>' + (c.cashier || '—') + '</td></tr>';
            }).join('');
        }).catch(function(){ $('pgRows').innerHTML = '<tr><td colspan="9">Error loading</td></tr>'; });
    }

    function collect(){
        var fd = new FormData();
        fd.append('enabled', $('pgEnabled').checked ? '1' : '');
        fd.append('mode', $('pgMode').value);
        fd.append('public_key', $('pgPublic').value.trim());
        fd.append('secret_key', $('pgSecret').value.trim());
        fd.append('webhook_secret', $('pgWebhookSecret').value.trim());
        fd.append('methods', $('pgMethods').value.trim());
        fd.append('success_url', $('pgSuccess').value.trim());
        fd.append('cancel_url', $('pgCancel').value.trim());
        fd.append('csrf_token', CSRF_TOKEN);
        return fd;
    }

    $('pgSaveBtn').addEventListener('click', function(){
        var btn = this; btn.disabled = true; btn.textContent = 'Saving…';
        fetch('?action=save_paymongo_settings', { method:'POST', body: collect() })
            .then(function(r){ return r.json(); })
            .then(function(d){
                btn.disabled = false; btn.textContent = 'Save settings';
                if (d.success) { toast('success', 'Saved', d.message || 'Settings saved'); $('pgSecret').value=''; $('pgWebhookSecret').value=''; loadSettings(); }
                else { toast('error', 'Error', d.message || 'Save failed'); }
            })
            .catch(function(){ btn.disabled = false; btn.textContent = 'Save settings'; toast('error', 'Error', 'Network error'); });
    });

    $('pgTestBtn').addEventListener('click', function(){
        var btn = this; btn.disabled = true; btn.textContent = 'Testing…';
        var fd = new FormData();
        fd.append('secret_key', $('pgSecret').value.trim());
        fd.append('csrf_token', CSRF_TOKEN);
        fetch('?action=test_paymongo_connection', { method:'POST', body: fd })
            .then(function(r){ return r.json(); })
            .then(function(d){
                btn.disabled = false; btn.textContent = 'Test connection';
                toast(d.success ? 'success' : 'error', d.success ? 'Connected' : 'Connection failed', d.message);
            })
            .catch(function(){ btn.disabled = false; btn.textContent = 'Test connection'; toast('error', 'Error', 'Network error'); });
    });

    $('pgCopyBtn').addEventListener('click', function(){
        var text = $('pgWebhookUrl').textContent;
        if (navigator.clipboard) { navigator.clipboard.writeText(text); }
        toast('info', 'Copied', 'Webhook URL copied to clipboard');
    });

    $('pgRefreshBtn').addEventListener('click', loadCheckouts);

    loadSettings();
    loadCheckouts();
})();
</script>
