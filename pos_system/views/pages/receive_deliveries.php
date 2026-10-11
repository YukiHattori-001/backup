<?php
if (!canAccess('procurement_receive') && !isAdmin()) {
    echo '<div class="inv-card" style="margin:2rem;text-align:center;padding:40px;"><h2 style="color:#111827;">Access Denied</h2><p style="color:#6b7280;">Only Inventory staff can receive deliveries.</p></div>';
    return;
}
$preselectPo = isset($_GET['po_id']) ? intval($_GET['po_id']) : 0;
?>

<div class="inv-page-header">
    <div class="inv-page-header-left">
        <h2 class="inv-page-title">Receive Deliveries</h2>
        <p class="inv-page-subtitle">Inspect delivered goods and confirm receipt — only accepted quantities increase stock</p>
    </div>
</div>

<div class="inv-card">
    <div class="inv-card-header">
        <div class="inv-card-title-wrap"><div class="inv-card-title">Purchase Orders Awaiting Delivery</div></div>
    </div>
    <div class="inv-table-wrap">
        <table class="inv-table" style="width:100%;min-width:900px;">
            <thead><tr><th>PO Number</th><th>Supplier</th><th>Order Date</th><th>Expected</th><th>Total</th><th>Progress</th><th>Status</th><th style="text-align:right;">Action</th></tr></thead>
            <tbody id="poListBody"><tr><td colspan="8" style="text-align:center;padding:20px;">Loading…</td></tr></tbody>
        </table>
    </div>
</div>

<div class="inv-card" id="receiveCard" style="margin-top:1.25rem;display:none;">
    <div class="inv-card-header">
        <div class="inv-card-title-wrap"><div class="inv-card-title" id="receiveTitle">Receive Delivery</div></div>
        <button class="inv-btn-secondary" style="padding:6px 12px;" onclick="closeReceive()">Close</button>
    </div>
    <div style="padding:16px;">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;" class="recv-grid">
            <div class="inv-form-group"><label class="inv-form-label">PO Number</label><input type="text" id="rcvPo" class="inv-form-input" disabled></div>
            <div class="inv-form-group"><label class="inv-form-label">Supplier</label><input type="text" id="rcvSupplier" class="inv-form-input" disabled></div>
            <div class="inv-form-group"><label class="inv-form-label">Delivery Date *</label><input type="date" id="rcvDate" class="inv-form-input" value="<?php echo date('Y-m-d'); ?>"></div>
            <div class="inv-form-group"><label class="inv-form-label">Delivery / Invoice Reference</label><input type="text" id="rcvReference" class="inv-form-input" placeholder="DR / supplier invoice no."></div>
            <div class="inv-form-group" style="grid-column:span 2;"><label class="inv-form-label">Remarks</label><input type="text" id="rcvRemarks" class="inv-form-input" placeholder="Notes about this delivery"></div>
        </div>

        <div class="inv-table-wrap" style="margin-top:10px;">
            <table class="inv-table" style="width:100%;min-width:1000px;">
                <thead><tr><th>Product</th><th>Ordered</th><th>Prev. Received</th><th>Remaining</th><th>Accepted*</th><th>Damaged</th><th>Rejected</th><th>Delivered</th><th>Batch / Expiry</th><th>Line Remarks</th></tr></thead>
                <tbody id="rcvItemsBody"></tbody>
            </table>
        </div>

        <div class="inv-modal-actions" style="justify-content:flex-end;margin-top:16px;">
            <button type="button" class="inv-btn-cancel" onclick="closeReceive()">Cancel</button>
            <button type="button" class="inv-btn-submit green" onclick="confirmReceipt()">Confirm Receipt &amp; Update Stock</button>
        </div>
    </div>
</div>

<div class="inv-card" style="margin-top:1.25rem;">
    <div class="inv-card-header"><div class="inv-card-title-wrap"><div class="inv-card-title">Recent Deliveries</div></div></div>
    <div class="inv-table-wrap">
        <table class="inv-table" style="width:100%;min-width:900px;">
            <thead><tr><th>GRN #</th><th>PO #</th><th>Supplier</th><th>Delivery Date</th><th>Reference</th><th>Accepted</th><th>Damaged</th><th>Received By</th></tr></thead>
            <tbody id="rcvHistoryBody"><tr><td colspan="8" style="text-align:center;padding:20px;">Loading…</td></tr></tbody>
        </table>
    </div>
</div>

<style>
@media (max-width: 900px) { .recv-grid { grid-template-columns: 1fr !important; } }
</style>

<script src="assets/js/procurement.js"></script>
<script>
var RCV_PRESELECT = <?php echo $preselectPo; ?>;
var currentPo = null;
var currentItems = [];
var receiptToken = '';

function newToken() {
    return 'grn-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);
}

function loadPos() {
    procGet('?action=get_purchase_orders').then(function (res) {
        var body = document.getElementById('poListBody');
        if (!res.success) { body.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px;color:#EF4444;">' + procEsc(res.message || 'Unable to load') + '</td></tr>'; return; }
        var rows = (res.orders || []).filter(function (o) { return o.status === 'ordered' || o.status === 'acknowledged' || o.status === 'partially_received'; });
        if (!rows.length) { body.innerHTML = '<tr><td colspan="8"><div class="inv-empty-state"><div class="inv-empty-icon">🚚</div><div class="inv-empty-title">No orders awaiting delivery</div><div class="inv-empty-text">Issued purchase orders will appear here.</div></div></td></tr>'; return; }
        body.innerHTML = rows.map(function (o) {
            var received = Number(o.received_total || 0);
            var ordered = Number(o.ordered_total || 0);
            var pct = ordered > 0 ? Math.round((received / ordered) * 100) : 0;
            return '<tr><td><strong>' + procEsc(o.po_number) + '</strong></td><td>' + procEsc(o.supplier_name || '—') + '</td><td>' + procDate(o.order_date) + '</td><td>' + (o.expected_delivery_date ? procDate(o.expected_delivery_date) : '—') + '</td><td>' + procMoney(o.total_amount) + '</td>' +
                '<td><div style="min-width:110px;"><div style="height:6px;background:#E5E7EB;border-radius:99px;overflow:hidden;"><div style="height:100%;width:' + pct + '%;background:#6366F1;"></div></div><small>' + procQty(received) + ' / ' + procQty(ordered) + '</small></div></td>' +
                '<td>' + procBadge(o.status) + '</td>' +
                '<td style="text-align:right;"><button class="inv-btn-primary" style="padding:6px 12px;" onclick="openReceive(' + o.id + ')">Receive</button></td></tr>';
        }).join('');

        if (RCV_PRESELECT) { openReceive(RCV_PRESELECT); RCV_PRESELECT = 0; }
    });
}

function openReceive(poId) {
    procGet('?action=get_po_receipt&id=' + poId).then(function (res) {
        if (!res.success) return procToast('error', 'Unable to load order', res.message || '');
        if (res.order.status !== 'acknowledged' && res.order.status !== 'ordered' && res.order.status !== 'partially_received') return procToast('error', 'Not receivable', 'This order is not awaiting delivery.');
        currentPo = res.order;
        currentItems = res.items || [];
        receiptToken = newToken();
        document.getElementById('rcvPo').value = currentPo.po_number;
        document.getElementById('rcvSupplier').value = currentPo.supplier_name || '';
        document.getElementById('receiveTitle').textContent = 'Receive Delivery — ' + currentPo.po_number;
        document.getElementById('rcvDate').value = new Date().toISOString().slice(0, 10);
        document.getElementById('rcvReference').value = '';
        document.getElementById('rcvRemarks').value = '';
        renderReceiveItems();
        document.getElementById('receiveCard').style.display = 'block';
        document.getElementById('receiveCard').scrollIntoView({ behavior: 'smooth' });
    });
}

function renderReceiveItems() {
    document.getElementById('rcvItemsBody').innerHTML = currentItems.map(function (it, idx) {
        var remaining = Number(it.quantity) - Number(it.received_quantity);
        return '<tr data-idx="' + idx + '">' +
            '<td>' + procEsc(it.product_name || '—') + '<br><small>Stock: ' + procQty(it.stock_quantity) + '</small></td>' +
            '<td>' + procQty(it.quantity) + '</td>' +
            '<td>' + procQty(it.received_quantity) + '</td>' +
            '<td>' + procQty(remaining) + '</td>' +
            '<td><input type="number" class="inv-form-input rcv-accepted" min="0" max="' + remaining + '" step="0.01" value="0" style="width:90px;" oninput="recalcLine(this)"></td>' +
            '<td><input type="number" class="inv-form-input rcv-damaged" min="0" step="0.01" value="0" style="width:80px;" oninput="recalcLine(this)"></td>' +
            '<td><input type="number" class="inv-form-input rcv-rejected" min="0" step="0.01" value="0" style="width:80px;" oninput="recalcLine(this)"></td>' +
            '<td class="rcv-delivered" style="font-weight:700;">0</td>' +
            '<td><input type="text" class="inv-form-input rcv-batch" placeholder="batch" style="width:90px;"><input type="date" class="inv-form-input rcv-expiry" style="width:130px;margin-top:4px;"></td>' +
            '<td><input type="text" class="inv-form-input rcv-remarks" placeholder="remarks" style="width:120px;"></td>' +
        '</tr>';
    }).join('');
}

function recalcLine(input) {
    var tr = input.closest('tr');
    var accepted = Number(tr.querySelector('.rcv-accepted').value || 0);
    var damaged = Number(tr.querySelector('.rcv-damaged').value || 0);
    var rejected = Number(tr.querySelector('.rcv-rejected').value || 0);
    tr.querySelector('.rcv-delivered').textContent = procQty(accepted + damaged + rejected);
}

function closeReceive() {
    document.getElementById('receiveCard').style.display = 'none';
    currentPo = null; currentItems = [];
}

function confirmReceipt() {
    if (!currentPo) return;
    var lines = [];
    var acceptedTotal = 0;
    document.querySelectorAll('#rcvItemsBody tr').forEach(function (tr) {
        var idx = Number(tr.getAttribute('data-idx'));
        var it = currentItems[idx];
        var accepted = Number(tr.querySelector('.rcv-accepted').value || 0);
        var damaged = Number(tr.querySelector('.rcv-damaged').value || 0);
        var rejected = Number(tr.querySelector('.rcv-rejected').value || 0);
        var delivered = accepted + damaged + rejected;
        if (delivered <= 0) return;
        var remaining = Number(it.quantity) - Number(it.received_quantity);
        if (accepted > remaining + 0.001) { lines = null; return; }
        acceptedTotal += accepted;
        lines.push({
            po_item_id: it.id,
            delivered_quantity: delivered,
            accepted_quantity: accepted,
            damaged_quantity: damaged,
            rejected_quantity: rejected,
            batch_no: tr.querySelector('.rcv-batch').value,
            expiry_date: tr.querySelector('.rcv-expiry').value,
            remarks: tr.querySelector('.rcv-remarks').value
        });
    });
    if (lines === null) { procToast('error', 'Invalid quantity', 'Accepted quantity cannot exceed the remaining ordered quantity.'); return; }
    if (!lines.length) { procToast('error', 'Nothing to receive', 'Enter an accepted, damaged or rejected quantity for at least one line.'); return; }

    var fd = new FormData();
    fd.append('po_id', currentPo.id);
    fd.append('delivery_date', document.getElementById('rcvDate').value);
    fd.append('reference_no', document.getElementById('rcvReference').value);
    fd.append('remarks', document.getElementById('rcvRemarks').value);
    fd.append('client_token', receiptToken);
    fd.append('items', JSON.stringify(lines));

    window.customConfirm('Confirm this delivery? Accepted quantities will be added to stock.', function () {
        procPost('save_goods_receipt', fd).then(function (res) {
            if (res.success) {
                procToast('success', res.duplicate ? 'Already recorded' : 'Delivery confirmed', 'GRN ' + res.grn_number + (res.po_status ? ' · order now ' + procStatus(res.po_status).label : ''));
                closeReceive();
                loadPos();
                loadHistory();
            } else {
                procToast('error', 'Cannot receive delivery', res.message || '');
            }
        });
    }, 'success');
}

function loadHistory() {
    procGet('?action=get_goods_receipts').then(function (res) {
        var body = document.getElementById('rcvHistoryBody');
        var rows = (res.receipts || []).slice(0, 15);
        if (!res.success) { body.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px;color:#EF4444;">' + procEsc(res.message || 'Unable to load') + '</td></tr>'; return; }
        if (!rows.length) { body.innerHTML = '<tr><td colspan="8"><div class="inv-empty-state"><div class="inv-empty-icon">📦</div><div class="inv-empty-title">No deliveries recorded yet</div></div></td></tr>'; return; }
        body.innerHTML = rows.map(function (g) {
            return '<tr><td><strong>' + procEsc(g.grn_number) + '</strong></td><td>' + procEsc(g.po_number || '—') + '</td><td>' + procEsc(g.supplier_name || '—') + '</td><td>' + procDate(g.delivery_date) + '</td><td>' + procEsc(g.reference_no || '—') + '</td><td>' + procQty(g.accepted_total) + '</td><td>' + procQty(g.damaged_total) + '</td><td>' + procEsc(g.received_by_name || '—') + '</td></tr>';
        }).join('');
    });
}

loadPos();
loadHistory();
</script>
