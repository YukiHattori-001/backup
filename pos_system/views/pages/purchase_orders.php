<?php
if (!canAccess('procurement') && !isAdmin()) {
    echo '<div class="inv-card" style="margin:2rem;text-align:center;padding:40px;"><h2 style="color:#111827;">Access Denied</h2></div>';
    return;
}
$canManagePO = canAccess('procurement_po_manage') || isAdmin();
$canReceive = canAccess('procurement_receive') || isAdmin();
$canFinance = canAccess('procurement_finance') || isAdmin();
?>

<div class="inv-page-header">
    <div class="inv-page-header-left">
        <h2 class="inv-page-title">Purchase Orders</h2>
        <p class="inv-page-subtitle">Generate orders from approved requests, then issue them to suppliers</p>
    </div>
</div>

<?php if ($canManagePO): ?>
<div class="inv-card">
    <div class="inv-card-header">
        <div class="inv-card-title-wrap"><div class="inv-card-title">Approved Requests Ready for a Purchase Order</div></div>
    </div>
    <div class="inv-table-wrap">
        <table class="inv-table" style="width:100%;min-width:820px;">
            <thead><tr><th>Request #</th><th>Requester</th><th>Supplier</th><th>Estimated Total</th><th>Approved</th><th style="text-align:right;">Action</th></tr></thead>
            <tbody id="readyBody"><tr><td colspan="6" style="text-align:center;padding:20px;">Loading…</td></tr></tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="inv-card" style="margin-top:1.25rem;">
    <div class="inv-card-header">
        <div class="inv-card-title-wrap"><div class="inv-card-title">All Purchase Orders</div></div>
        <div class="inv-filter-bar" style="margin:0;">
            <div class="inv-search-wrap"><span class="inv-search-icon">🔍</span><input type="text" id="poSearch" class="inv-search-input" placeholder="Search PO, supplier, request…" oninput="renderOrders()"></div>
            <select id="poStatus" class="inv-filter-select" onchange="renderOrders()">
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="pending_finance">Pending Finance</option>
                <option value="ordered">Ordered</option>
                <option value="acknowledged">Acknowledged</option>
                <option value="partially_received">Partially Received</option>
                <option value="received">Received</option>
                <option value="closed">Closed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <select id="poSupplier" class="inv-filter-select" onchange="renderOrders()"><option value="">All suppliers</option></select>
            <input type="date" id="poFrom" class="inv-filter-select" onchange="renderOrders()">
            <input type="date" id="poTo" class="inv-filter-select" onchange="renderOrders()">
        </div>
    </div>
    <div class="inv-table-wrap">
        <table class="inv-table" style="width:100%;min-width:1000px;">
            <thead>
                <tr><th>PO Number</th><th>Supplier</th><th>Request #</th><th>Order Date</th><th>Expected</th><th>Total</th><th>Received</th><th>Status</th><th style="text-align:right;">Actions</th></tr>
            </thead>
            <tbody id="poBody"><tr><td colspan="9" style="text-align:center;padding:24px;">Loading purchase orders…</td></tr></tbody>
        </table>
    </div>
</div>

<!-- Create PO modal -->
<div class="modal" id="poCreateModal">
    <div class="modal-content inv-modal" style="max-width:520px;">
        <div class="inv-modal-header">
            <div class="inv-modal-header-icon purple">🧾</div>
            <div class="inv-modal-header-text"><h2>Generate Purchase Order</h2><p id="poCreateSub"></p></div>
            <button class="inv-modal-close" onclick="procCloseModal('poCreateModal')">&times;</button>
        </div>
        <div class="inv-form-group"><label class="inv-form-label">Expected delivery date</label><input type="date" id="poExpected" class="inv-form-input"></div>
        <div class="inv-form-group"><label class="inv-form-label">Notes (optional)</label><textarea id="poNotes" class="inv-form-textarea" rows="2" placeholder="Delivery instructions, agreed terms…"></textarea></div>
        <div class="inv-modal-actions">
            <button type="button" class="inv-btn-cancel" onclick="procCloseModal('poCreateModal')">Cancel</button>
            <button type="button" class="inv-btn-submit" onclick="confirmCreatePO()">Create draft order</button>
        </div>
    </div>
</div>

<!-- Cancel PO modal -->
<div class="modal" id="poCancelModal">
    <div class="modal-content inv-modal" style="max-width:480px;">
        <div class="inv-modal-header">
            <div class="inv-modal-header-icon red">⛔</div>
            <div class="inv-modal-header-text"><h2>Cancel Purchase Order</h2><p id="poCancelSub"></p></div>
            <button class="inv-modal-close" onclick="procCloseModal('poCancelModal')">&times;</button>
        </div>
        <div class="inv-form-group"><label class="inv-form-label">Cancellation reason *</label><textarea id="poCancelReason" class="inv-form-textarea" rows="3"></textarea></div>
        <div class="inv-modal-actions">
            <button type="button" class="inv-btn-cancel" onclick="procCloseModal('poCancelModal')">Keep order</button>
            <button type="button" class="inv-btn-submit red" onclick="confirmCancelPO()">Cancel order</button>
        </div>
    </div>
</div>

<!-- Detail modal -->
<div class="modal" id="poDetailModal">
    <div class="modal-content inv-modal" style="max-width:920px;max-height:90vh;overflow:auto;">
        <div class="inv-modal-header">
            <div class="inv-modal-header-icon blue">🧾</div>
            <div class="inv-modal-header-text"><h2 id="poDetailTitle">Purchase Order</h2><p id="poDetailSub"></p></div>
            <button class="inv-modal-close" onclick="procCloseModal('poDetailModal')">&times;</button>
        </div>
        <div id="poDetailBody"></div>
    </div>
</div>

<!-- Finance approval modal -->
<div class="modal" id="poFinanceModal">
    <div class="modal-content inv-modal" style="max-width:520px;">
        <div class="inv-modal-header">
            <div class="inv-modal-header-icon blue">💰</div>
            <div class="inv-modal-header-text"><h2>Finance Approval</h2><p id="poFinanceSub"></p></div>
            <button class="inv-modal-close" onclick="procCloseModal('poFinanceModal')">&times;</button>
        </div>
        <div id="poFinanceBody" style="margin-bottom:10px;"></div>
        <div class="inv-form-group"><label class="inv-form-label">Comment (required to decline)</label><textarea id="poFinanceComments" class="inv-form-textarea" rows="2" placeholder="Approval note or reason for declining…"></textarea></div>
        <div class="inv-modal-actions">
            <button type="button" class="inv-btn-submit red" onclick="decideFinance('reject')">Decline</button>
            <button type="button" class="inv-btn-submit green" onclick="decideFinance('approve')">Approve &amp; release</button>
        </div>
    </div>
</div>

<!-- Acknowledge modal -->
<div class="modal" id="poAckModal">
    <div class="modal-content inv-modal" style="max-width:480px;">
        <div class="inv-modal-header">
            <div class="inv-modal-header-icon green">📨</div>
            <div class="inv-modal-header-text"><h2>Supplier Acknowledgement</h2><p id="poAckSub"></p></div>
            <button class="inv-modal-close" onclick="procCloseModal('poAckModal')">&times;</button>
        </div>
        <div class="inv-form-group"><label class="inv-form-label">Supplier acknowledgement reference (optional)</label><input type="text" id="poAckRef" class="inv-form-input" placeholder="e.g. email / PO acceptance ref"></div>
        <div class="inv-modal-actions">
            <button type="button" class="inv-btn-cancel" onclick="procCloseModal('poAckModal')">Cancel</button>
            <button type="button" class="inv-btn-submit green" onclick="confirmAckPO()">Confirm acknowledgement</button>
        </div>
    </div>
</div>

<!-- Close & rate modal -->
<div class="modal" id="poCloseModal">
    <div class="modal-content inv-modal" style="max-width:640px;max-height:90vh;overflow:auto;">
        <div class="inv-modal-header">
            <div class="inv-modal-header-icon purple">🏁</div>
            <div class="inv-modal-header-text"><h2>Close &amp; Review Purchase Order</h2><p id="poCloseSub"></p></div>
            <button class="inv-modal-close" onclick="procCloseModal('poCloseModal')">&times;</button>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
            <div class="inv-form-group"><label class="inv-form-label">On-time / in-full (1-5)</label><input type="number" id="poRateOtif" class="inv-form-input" min="0" max="5" value="5"></div>
            <div class="inv-form-group"><label class="inv-form-label">Quality (1-5)</label><input type="number" id="poRateQuality" class="inv-form-input" min="0" max="5" value="5"></div>
            <div class="inv-form-group"><label class="inv-form-label">Responsiveness (1-5)</label><input type="number" id="poRateResp" class="inv-form-input" min="0" max="5" value="5"></div>
        </div>
        <div class="inv-form-group"><label class="inv-form-label">Cost allocation / accrual release</label><input type="text" id="poRateCost" class="inv-form-input" placeholder="e.g. released to OPS-KITCHEN 2026-Q1"></div>
        <label style="display:flex;align-items:center;gap:8px;margin-bottom:10px;"><input type="checkbox" id="poRateAccrual" checked> Release the remaining accrual for this order</label>
        <div class="inv-form-group"><label class="inv-form-label">Comments</label><textarea id="poRateComments" class="inv-form-textarea" rows="2" placeholder="Overall supplier performance…"></textarea></div>
        <div class="inv-form-group"><label class="inv-form-label">Lessons learned</label><textarea id="poRateLessons" class="inv-form-textarea" rows="2" placeholder="What should improve next time?"></textarea></div>
        <div class="inv-modal-actions">
            <button type="button" class="inv-btn-cancel" onclick="procCloseModal('poCloseModal')">Cancel</button>
            <button type="button" class="inv-btn-submit green" onclick="confirmClosePO()">Close purchase order</button>
        </div>
    </div>
</div>

<script src="assets/js/procurement.js"></script>
<script>
var PO_CAN_MANAGE = <?php echo $canManagePO ? 'true' : 'false'; ?>;
var PO_CAN_RECEIVE = <?php echo $canReceive ? 'true' : 'false'; ?>;
var PO_CAN_FINANCE = <?php echo $canFinance ? 'true' : 'false'; ?>;
var poRows = [];
var createPORequestId = 0;
var cancelPOId = 0;
var financePOId = 0;
var ackPOId = 0;
var closePOId = 0;

function loadOrders() {
    procGet('?action=get_purchase_orders').then(function (res) {
        if (!res.success) { document.getElementById('poBody').innerHTML = '<tr><td colspan="9" style="text-align:center;padding:24px;color:#EF4444;">' + procEsc(res.message || 'Unable to load') + '</td></tr>'; return; }
        poRows = res.orders || [];
        renderOrders();
    });
}

function renderOrders() {
    var q = (document.getElementById('poSearch').value || '').toLowerCase();
    var status = document.getElementById('poStatus').value;
    var supplier = document.getElementById('poSupplier').value;
    var from = document.getElementById('poFrom').value;
    var to = document.getElementById('poTo').value;
    var rows = poRows.filter(function (o) {
        if (status && o.status !== status) return false;
        if (supplier && String(o.supplier_id) !== supplier) return false;
        if (from && procDate(o.order_date) < from) return false;
        if (to && procDate(o.order_date) > to) return false;
        if (q) {
            var hay = (o.po_number + ' ' + (o.supplier_name || '') + ' ' + (o.request_number || '')).toLowerCase();
            if (hay.indexOf(q) === -1) return false;
        }
        return true;
    });

    if (!rows.length) {
        document.getElementById('poBody').innerHTML = '<tr><td colspan="9"><div class="inv-empty-state"><div class="inv-empty-icon">📄</div><div class="inv-empty-title">No purchase orders found</div><div class="inv-empty-text">Approved requests can be converted into purchase orders above.</div></div></td></tr>';
        return;
    }

    document.getElementById('poBody').innerHTML = rows.map(function (o) {
        var received = Number(o.received_total || 0);
        var ordered = Number(o.ordered_total || 0);
        var pct = ordered > 0 ? Math.round((received / ordered) * 100) : 0;
        var progress = '<div style="min-width:110px;"><div style="height:6px;background:#E5E7EB;border-radius:99px;overflow:hidden;"><div style="height:100%;width:' + pct + '%;background:#6366F1;"></div></div><small>' + procQty(received) + ' / ' + procQty(ordered) + '</small></div>';
        var actions = '<button class="inv-action-btn inv-action-view" title="View" onclick="viewOrder(' + o.id + ')">👁️</button>';
        if (PO_CAN_MANAGE && o.status === 'draft') actions += '<button class="inv-action-btn inv-action-add" title="Issue to supplier" onclick="issueOrder(' + o.id + ')">📤</button>';
        if (PO_CAN_FINANCE && o.status === 'pending_finance') actions += '<button class="inv-action-btn inv-action-add" title="Finance approval" onclick="openFinancePO(' + o.id + ', \'' + procEsc(o.po_number) + '\')">💰</button>';
        if (PO_CAN_MANAGE && o.status === 'ordered') actions += '<button class="inv-action-btn inv-action-edit" title="Record supplier acknowledgement" onclick="openAckPO(' + o.id + ', \'' + procEsc(o.po_number) + '\')">📨</button>';
        if (PO_CAN_MANAGE && (o.status === 'draft' || o.status === 'pending_finance' || o.status === 'ordered' || o.status === 'acknowledged')) actions += '<button class="inv-action-btn inv-action-delete" title="Cancel order" onclick="openCancelPO(' + o.id + ', \'' + procEsc(o.po_number) + '\')">⛔</button>';
        if (PO_CAN_RECEIVE && (o.status === 'ordered' || o.status === 'acknowledged' || o.status === 'partially_received')) actions += '<a class="inv-action-btn inv-action-edit" title="Receive delivery" style="text-decoration:none;" href="?page=receive_deliveries&po_id=' + o.id + '">📦</a>';
        if (PO_CAN_MANAGE && o.status === 'received' && Number(o.open_invoices || 0) === 0) actions += '<button class="inv-action-btn inv-action-add" title="Close & rate supplier" onclick="openClosePO(' + o.id + ', \'' + procEsc(o.po_number) + '\')">🏁</button>';
        return '<tr>' +
            '<td><strong>' + procEsc(o.po_number) + '</strong></td>' +
            '<td>' + procEsc(o.supplier_name || '—') + '</td>' +
            '<td>' + procEsc(o.request_number || '—') + '</td>' +
            '<td>' + procDate(o.order_date) + '</td>' +
            '<td>' + (o.expected_delivery_date ? procDate(o.expected_delivery_date) : '—') + '</td>' +
            '<td>' + procMoney(o.total_amount) + '</td>' +
            '<td>' + progress + '</td>' +
            '<td>' + procBadge(o.status) + (Number(o.open_invoices || 0) > 0 ? ' <span class="badge badge-warning" title="Unsettled invoices">' + Number(o.open_invoices) + ' inv</span>' : '') + '</td>' +
            '<td><div class="inv-actions" style="justify-content:flex-end;">' + actions + '</div></td>' +
        '</tr>';
    }).join('');
}

function viewOrder(id) {
    procGet('?action=get_po_receipt&id=' + id).then(function (res) {
        if (!res.success) return procToast('error', 'Unable to load order', res.message || '');
        var o = res.order;
        document.getElementById('poDetailTitle').textContent = o.po_number;
        document.getElementById('poDetailSub').textContent = (o.supplier_name || '—') + ' · ' + procStatus(o.status).label;
        var html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">';
        html += '<div><strong>Request:</strong> ' + procEsc(o.request_number || '—') + '</div>';
        html += '<div><strong>Order date:</strong> ' + procDate(o.order_date) + '</div>';
        html += '<div><strong>Expected delivery:</strong> ' + (o.expected_delivery_date ? procDate(o.expected_delivery_date) : '—') + '</div>';
        html += '<div><strong>Status:</strong> ' + procBadge(o.status) + '</div>';
        html += '<div><strong>Finance:</strong> ' + procBadge(o.finance_status || 'not_required') + '</div>';
        html += '<div><strong>Acknowledged:</strong> ' + (Number(o.supplier_ack) ? procDateTime(o.acknowledged_at) : '—') + '</div>';
        if (o.closed_at) html += '<div><strong>Closed:</strong> ' + procDateTime(o.closed_at) + '</div>';
        html += '<div><strong>Subtotal:</strong> ' + procMoney(o.subtotal) + '</div>';
        html += '<div><strong>Total:</strong> ' + procMoney(o.total_amount) + '</div>';
        html += '</div>';
        if (o.notes) html += '<p><strong>Notes:</strong> ' + procEsc(o.notes) + '</p>';
        if (o.cancellation_reason) html += '<div class="alert alert-danger"><strong>Cancelled:</strong> ' + procEsc(o.cancellation_reason) + '</div>';

        html += '<h4 style="margin:16px 0 8px;">Order Items</h4><div class="inv-table-wrap"><table class="inv-table" style="width:100%;"><thead><tr><th>Product</th><th>Ordered</th><th>Received</th><th>Remaining</th><th>Unit Price</th><th>Line Total</th></tr></thead><tbody>';
        (res.items || []).forEach(function (it) {
            var remaining = Number(it.quantity) - Number(it.received_quantity);
            html += '<tr><td>' + procEsc(it.product_name || '—') + '</td><td>' + procQty(it.quantity) + '</td><td>' + procQty(it.received_quantity) + '</td><td>' + procQty(remaining) + '</td><td>' + procMoney(it.unit_price) + '</td><td>' + procMoney(it.line_total) + '</td></tr>';
        });
        html += '</tbody></table></div>';

        html += '<h4 style="margin:16px 0 8px;">Goods Receipts</h4><div class="inv-table-wrap"><table class="inv-table" style="width:100%;"><thead><tr><th>GRN #</th><th>Delivery Date</th><th>Reference</th><th>Received By</th></tr></thead><tbody>';
        if ((res.receipts || []).length) {
            res.receipts.forEach(function (g) { html += '<tr><td>' + procEsc(g.grn_number) + '</td><td>' + procDate(g.delivery_date) + '</td><td>' + procEsc(g.reference_no || '—') + '</td><td>' + procEsc(g.received_by_name || '—') + '</td></tr>'; });
        } else {
            html += '<tr><td colspan="4" style="text-align:center;padding:14px;color:#6b7280;">No deliveries recorded yet.</td></tr>';
        }
        html += '</tbody></table></div>';

        document.getElementById('poDetailBody').innerHTML = html;
        procOpenModal('poDetailModal');
    });
}

function issueOrder(id) {
    window.customConfirm('Issue this purchase order to the supplier? It will be marked as Ordered and appear in Finance.', function () {
        var fd = new FormData(); fd.append('id', id);
        procPost('issue_purchase_order', fd).then(function (res) {
            if (res.success) { procToast('success', 'Order issued', 'The purchase order is now marked as Ordered.'); loadOrders(); }
            else procToast('error', 'Cannot issue', res.message || '');
        });
    }, 'warning');
}

function openCancelPO(id, number) {
    cancelPOId = id;
    document.getElementById('poCancelSub').textContent = number;
    document.getElementById('poCancelReason').value = '';
    procOpenModal('poCancelModal');
}

function confirmCancelPO() {
    var reason = document.getElementById('poCancelReason').value.trim();
    if (!reason) { procToast('error', 'Reason required', 'Enter a cancellation reason.'); return; }
    var fd = new FormData(); fd.append('id', cancelPOId); fd.append('reason', reason);
    procPost('cancel_purchase_order', fd).then(function (res) {
        if (res.success) { procToast('success', 'Order cancelled', ''); procCloseModal('poCancelModal'); loadOrders(); loadReady(); }
        else procToast('error', 'Cannot cancel', res.message || '');
    });
}

function openFinancePO(id, number) {
    financePOId = id;
    document.getElementById('poFinanceSub').textContent = number;
    document.getElementById('poFinanceComments').value = '';
    document.getElementById('poFinanceBody').innerHTML = '<p style="color:#6b7280;">Approving releases the order to the supplier and books it in Finance. Declining returns it to draft for revision.</p>';
    procOpenModal('poFinanceModal');
}

function decideFinance(decision) {
    var comments = document.getElementById('poFinanceComments').value.trim();
    if (decision === 'reject' && !comments) { procToast('error', 'Comment required', 'Enter a reason for declining.'); return; }
    var fd = new FormData();
    fd.append('id', financePOId);
    fd.append('decision', decision);
    fd.append('comments', comments);
    procPost('finance_approve_po', fd).then(function (res) {
        if (res.success) { procToast('success', decision === 'approve' ? 'Order approved' : 'Order declined', ''); procCloseModal('poFinanceModal'); loadOrders(); }
        else procToast('error', 'Cannot record decision', res.message || '');
    });
}

function openAckPO(id, number) {
    ackPOId = id;
    document.getElementById('poAckSub').textContent = number;
    document.getElementById('poAckRef').value = '';
    procOpenModal('poAckModal');
}

function confirmAckPO() {
    var fd = new FormData();
    fd.append('id', ackPOId);
    fd.append('ack_reference', document.getElementById('poAckRef').value);
    procPost('acknowledge_po', fd).then(function (res) {
        if (res.success) { procToast('success', 'Supplier acknowledgement recorded', ''); procCloseModal('poAckModal'); loadOrders(); }
        else procToast('error', 'Cannot acknowledge', res.message || '');
    });
}

function openClosePO(id, number) {
    closePOId = id;
    document.getElementById('poCloseSub').textContent = number;
    document.getElementById('poRateOtif').value = 5;
    document.getElementById('poRateQuality').value = 5;
    document.getElementById('poRateResp').value = 5;
    document.getElementById('poRateCost').value = '';
    document.getElementById('poRateAccrual').checked = true;
    document.getElementById('poRateComments').value = '';
    document.getElementById('poRateLessons').value = '';
    procOpenModal('poCloseModal');
}

function confirmClosePO() {
    var fd = new FormData();
    fd.append('id', closePOId);
    fd.append('otif_score', document.getElementById('poRateOtif').value);
    fd.append('quality_score', document.getElementById('poRateQuality').value);
    fd.append('responsiveness_score', document.getElementById('poRateResp').value);
    fd.append('cost_allocation', document.getElementById('poRateCost').value);
    fd.append('accrual_released', document.getElementById('poRateAccrual').checked ? '1' : '0');
    fd.append('comments', document.getElementById('poRateComments').value);
    fd.append('lessons_learned', document.getElementById('poRateLessons').value);
    procPost('close_purchase_order', fd).then(function (res) {
        if (res.success) { procToast('success', 'Purchase order closed', 'Supplier rating saved.'); procCloseModal('poCloseModal'); loadOrders(); }
        else procToast('error', 'Cannot close order', res.message || '');
    });
}

function loadReady() {
    if (!PO_CAN_MANAGE) return;
    Promise.all([
        procGet('?action=get_purchase_requests&status=approved'),
        procGet('?action=get_purchase_requests&status=supplier_selected')
    ]).then(function (results) {
        var body = document.getElementById('readyBody');
        if (!body) return;
        var rows = [];
        results.forEach(function (res) { rows = rows.concat((res.requests || [])); });
        rows = rows.filter(function (r) { return !r.po_number; });
        if (!rows.length) { body.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:18px;color:#6b7280;">No approved requests are awaiting a purchase order.</td></tr>'; return; }
        body.innerHTML = rows.map(function (r) {
            var action = r.supplier_id
                ? '<button class="inv-btn-primary" style="padding:6px 12px;" onclick="openCreatePO(' + r.id + ', \'' + procEsc(r.request_number) + '\')">Generate PO</button>'
                : '<a class="inv-btn-secondary" style="padding:6px 12px;text-decoration:none;" href="?page=procurement_sourcing&id=' + r.id + '">Source suppliers</a>';
            return '<tr><td><strong>' + procEsc(r.request_number) + '</strong><br><small>' + procBadge(r.status) + '</small></td><td>' + procEsc(r.requester_name || '—') + '</td><td>' + procEsc(r.supplier_name || '—') + '</td><td>' + procMoney(r.negotiated_total != null ? r.negotiated_total : r.estimated_total) + '</td><td>' + procDateTime(r.approved_at || r.reviewed_at) + '</td>' +
                '<td style="text-align:right;">' + action + '</td></tr>';
        }).join('');
    });
}

function openCreatePO(requestId, number) {
    createPORequestId = requestId;
    document.getElementById('poCreateSub').textContent = 'From request ' + number;
    document.getElementById('poExpected').value = '';
    document.getElementById('poNotes').value = '';
    procOpenModal('poCreateModal');
}

function confirmCreatePO() {
    var fd = new FormData();
    fd.append('request_id', createPORequestId);
    fd.append('expected_delivery_date', document.getElementById('poExpected').value);
    fd.append('notes', document.getElementById('poNotes').value);
    procPost('create_purchase_order', fd).then(function (res) {
        if (res.success) { procToast('success', 'Draft purchase order created', res.po_number || ''); procCloseModal('poCreateModal'); loadOrders(); loadReady(); }
        else procToast('error', 'Cannot create order', res.message || '');
    });
}

procGet('?action=get_suppliers').then(function (rows) {
    var select = document.getElementById('poSupplier');
    (rows || []).forEach(function (s) { var o = document.createElement('option'); o.value = s.id; o.textContent = s.name; select.appendChild(o); });
});

loadOrders();
loadReady();
</script>
