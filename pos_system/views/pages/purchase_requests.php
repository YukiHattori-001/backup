<?php
if (!canAccess('procurement') && !isAdmin()) {
    echo '<div class="inv-card" style="margin:2rem;text-align:center;padding:40px;"><h2 style="color:#111827;">Access Denied</h2></div>';
    return;
}
$canManage = canAccess('procurement_manage') || isAdmin();
$canApprove = canAccess('procurement_approve') || isAdmin();
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
?>

<div class="inv-page-header">
    <div class="inv-page-header-left">
        <h2 class="inv-page-title">Purchase Requests</h2>
        <p class="inv-page-subtitle">Create, submit and track purchase requisitions through approval</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <?php if ($canManage): ?>
        <a href="?page=purchase_request_create" class="inv-btn-primary" style="text-decoration:none;">+ New Purchase Request</a>
        <?php endif; ?>
    </div>
</div>

<div class="inv-card">
    <div class="inv-card-header">
        <div class="inv-card-title-wrap"><div class="inv-card-title">All Purchase Requests</div></div>
        <div class="inv-filter-bar" style="margin:0;">
            <div class="inv-search-wrap"><span class="inv-search-icon">🔍</span><input type="text" id="prSearch" class="inv-search-input" placeholder="Search number, reason, supplier…" oninput="renderRequests()"></div>
            <select id="prStatus" class="inv-filter-select" onchange="renderRequests()">
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="budget_review">Budget Review</option>
                <option value="pending_approval">Pending Approval</option>
                <option value="revision_requested">Revision Requested</option>
                <option value="approved">Approved</option>
                <option value="sourcing">Sourcing / RFQ</option>
                <option value="supplier_selected">Supplier Selected</option>
                <option value="closed">Closed</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <select id="prSupplier" class="inv-filter-select" onchange="renderRequests()"><option value="">All suppliers</option></select>
            <input type="date" id="prFrom" class="inv-filter-select" onchange="renderRequests()">
            <input type="date" id="prTo" class="inv-filter-select" onchange="renderRequests()">
        </div>
    </div>
    <div class="inv-table-wrap">
        <table class="inv-table" style="width:100%;min-width:960px;">
            <thead>
                <tr><th>Request #</th><th>Date</th><th>Requester</th><th>Supplier</th><th>Items</th><th>Estimated Total</th><th>Status</th><th>PO</th><th style="text-align:right;">Actions</th></tr>
            </thead>
            <tbody id="prBody"><tr><td colspan="9" style="text-align:center;padding:24px;">Loading purchase requests…</td></tr></tbody>
        </table>
    </div>
</div>

<!-- Detail modal -->
<div class="modal" id="prDetailModal">
    <div class="modal-content inv-modal" style="max-width:900px;max-height:90vh;overflow:auto;">
        <div class="inv-modal-header">
            <div class="inv-modal-header-icon purple">📋</div>
            <div class="inv-modal-header-text"><h2 id="prDetailTitle">Purchase Request</h2><p id="prDetailSub"></p></div>
            <button class="inv-modal-close" onclick="procCloseModal('prDetailModal')">&times;</button>
        </div>
        <div id="prDetailBody"></div>
    </div>
</div>

<!-- Cancel modal -->
<div class="modal" id="prCancelModal">
    <div class="modal-content inv-modal" style="max-width:480px;">
        <div class="inv-modal-header">
            <div class="inv-modal-header-icon red">⛔</div>
            <div class="inv-modal-header-text"><h2>Cancel Purchase Request</h2><p id="prCancelSub"></p></div>
            <button class="inv-modal-close" onclick="procCloseModal('prCancelModal')">&times;</button>
        </div>
        <div class="inv-form-group"><label class="inv-form-label">Reason for cancellation</label><textarea id="prCancelReason" class="inv-form-textarea" rows="3" placeholder="Explain why this request is being cancelled"></textarea></div>
        <div class="inv-modal-actions">
            <button type="button" class="inv-btn-cancel" onclick="procCloseModal('prCancelModal')">Keep request</button>
            <button type="button" class="inv-btn-submit red" onclick="confirmCancelRequest()">Cancel request</button>
        </div>
    </div>
</div>

<script src="assets/js/procurement.js"></script>
<script>
var PR_CAN_MANAGE = <?php echo $canManage ? 'true' : 'false'; ?>;
var PR_CAN_APPROVE = <?php echo $canApprove ? 'true' : 'false'; ?>;
var PR_ME = <?php echo $currentUserId; ?>;
var prRows = [];
var prSuppliers = [];
var cancelTargetId = 0;

function escAttr(v) { return String(v == null ? '' : v).replace(/"/g, '&quot;'); }

function loadRequests() {
    procGet('?action=get_purchase_requests').then(function (res) {
        if (!res.success) { document.getElementById('prBody').innerHTML = '<tr><td colspan="9" style="text-align:center;padding:24px;color:#EF4444;">' + procEsc(res.message || 'Unable to load') + '</td></tr>'; return; }
        prRows = res.requests || [];
        renderRequests();
    });
}

function renderRequests() {
    var q = (document.getElementById('prSearch').value || '').toLowerCase();
    var status = document.getElementById('prStatus').value;
    var supplier = document.getElementById('prSupplier').value;
    var from = document.getElementById('prFrom').value;
    var to = document.getElementById('prTo').value;
    var rows = prRows.filter(function (r) {
        if (status && r.status !== status) return false;
        if (supplier && String(r.supplier_id) !== supplier) return false;
        if (from && procDate(r.request_date) < from) return false;
        if (to && procDate(r.request_date) > to) return false;
        if (q) {
            var hay = (r.request_number + ' ' + (r.reason || '') + ' ' + (r.supplier_name || '')).toLowerCase();
            if (hay.indexOf(q) === -1) return false;
        }
        return true;
    });

    if (!rows.length) {
        document.getElementById('prBody').innerHTML = '<tr><td colspan="9"><div class="inv-empty-state"><div class="inv-empty-icon">🗂️</div><div class="inv-empty-title">No purchase requests found</div><div class="inv-empty-text">Adjust the filters or create a new request.</div></div></td></tr>';
        return;
    }

    document.getElementById('prBody').innerHTML = rows.map(function (r) {
        var mine = Number(r.requester_id) === PR_ME;
        var canEdit = PR_CAN_MANAGE && mine && (r.status === 'draft' || r.status === 'revision_requested');
        var canSubmit = canEdit;
        var canDelete = PR_CAN_MANAGE && mine && r.status === 'draft';
        var canSource = PR_CAN_MANAGE && ['approved', 'sourcing', 'supplier_selected'].indexOf(r.status) !== -1;
        var canCancel = PR_CAN_MANAGE && mine && ['draft', 'budget_review', 'pending_approval', 'revision_requested', 'approved', 'sourcing', 'supplier_selected'].indexOf(r.status) !== -1 && !(r.po_number);
        var actions = '<button class="inv-action-btn inv-action-view" title="View" onclick="viewRequest(' + r.id + ')">👁️</button>';
        if (canEdit) actions += '<a class="inv-action-btn inv-action-edit" title="Edit" href="?page=purchase_request_create&id=' + r.id + '" style="text-decoration:none;">✏️</a>';
        if (canSubmit) actions += '<button class="inv-action-btn inv-action-add" title="Submit for approval" onclick="submitRequest(' + r.id + ')">📤</button>';
        if (canSource) actions += '<a class="inv-action-btn inv-action-add" title="Source suppliers / RFQ" href="?page=procurement_sourcing&id=' + r.id + '" style="text-decoration:none;">🔎</a>';
        if (canCancel) actions += '<button class="inv-action-btn inv-action-delete" title="Cancel" onclick="openCancelRequest(' + r.id + ')">⛔</button>';
        if (canDelete) actions += '<button class="inv-action-btn inv-action-delete" title="Delete draft" onclick="deleteRequest(' + r.id + ')">🗑️</button>';
        var poCell = r.po_number ? procEsc(r.po_number) + '<br>' + procBadge(r.po_status) : '—';
        return '<tr>' +
            '<td><strong>' + procEsc(r.request_number) + '</strong><br><small>' + procEsc(r.department || '') + '</small></td>' +
            '<td>' + procDate(r.request_date) + '</td>' +
            '<td>' + procEsc(r.requester_name || '—') + '</td>' +
            '<td>' + procEsc(r.supplier_name || '—') + '</td>' +
            '<td>' + Number(r.item_count) + ' line(s)<br><small>' + procQty(r.total_quantity) + ' units</small></td>' +
            '<td>' + procMoney(r.estimated_total) + '</td>' +
            '<td>' + procBadge(r.status) + '</td>' +
            '<td>' + poCell + '</td>' +
            '<td><div class="inv-actions" style="justify-content:flex-end;">' + actions + '</div></td>' +
        '</tr>';
    }).join('');
}

function viewRequest(id) {
    procGet('?action=get_purchase_request&id=' + id).then(function (res) {
        if (!res.success) return procToast('error', 'Unable to load request', res.message || '');
        var r = res.request;
        document.getElementById('prDetailTitle').textContent = r.request_number;
        document.getElementById('prDetailSub').textContent = (r.supplier_name || 'No supplier') + ' · ' + procStatus(r.status).label;
        var html = '';
        html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">';
        html += '<div><strong>Requester:</strong> ' + procEsc(r.requester_name || '—') + '</div>';
        html += '<div><strong>Request date:</strong> ' + procDate(r.request_date) + '</div>';
        html += '<div><strong>Department:</strong> ' + procEsc(r.department || '—') + '</div>';
        html += '<div><strong>Supplier:</strong> ' + procEsc(r.supplier_name || '—') + ' ' + (r.supplier_status === 'active' ? '' : '<span class="badge badge-danger">inactive</span>') + '</div>';
        html += '<div><strong>Status:</strong> ' + procBadge(r.status) + '</div>';
        html += '<div><strong>Estimated total:</strong> ' + procMoney(r.estimated_total) + '</div>';
        html += '<div><strong>Cost centre:</strong> ' + procEsc(r.cost_center || '—') + '</div>';
        html += '<div><strong>Budget code:</strong> ' + procEsc(r.budget_code || '—') + '</div>';
        html += '<div><strong>Budget status:</strong> ' + procBadge(r.budget_status || 'pending') + '</div>';
        html += '<div><strong>Budget available:</strong> ' + procMoney(r.budget_amount) + '</div>';
        html += '<div><strong>Submitted:</strong> ' + procDateTime(r.submitted_at) + '</div>';
        html += '<div><strong>Reviews:</strong> ' + Number(r.revision_count || 0) + '</div>';
        html += '</div>';
        if (r.rfq_number) {
            html += '<p><strong>RFQ:</strong> ' + procEsc(r.rfq_number) + ' <span class="badge badge-info">' + procEsc(r.rfq_status || '') + '</span>'
                + ' · deadline ' + procDate(r.rfq_deadline) + '</p>';
        }
        if (r.negotiated_total != null) {
            html += '<p><strong>Negotiated total:</strong> ' + procMoney(r.negotiated_total)
                + ' <span class="badge badge-primary">' + procEsc(r.payment_terms || 'terms n/a') + '</span></p>';
        }
        if (r.budget_comments) html += '<div class="alert alert-info"><strong>Budget check:</strong> ' + procEsc(r.budget_comments) + '</div>';
        html += '<p><strong>Reason:</strong> ' + procEsc(r.reason || '—') + '</p>';
        if (r.remarks) html += '<p><strong>Remarks:</strong> ' + procEsc(r.remarks) + '</p>';
        if (r.review_comments) html += '<div class="alert alert-info"><strong>Approver feedback:</strong> ' + procEsc(r.review_comments) + '</div>';
        if (r.quotation_path) html += '<p><a href="' + escAttr(r.quotation_path) + '" target="_blank" class="inv-btn-secondary" style="text-decoration:none;padding:6px 10px;">📎 View quotation</a></p>';

        html += '<h4 style="margin:16px 0 8px;">Items</h4><div class="inv-table-wrap"><table class="inv-table" style="width:100%;"><thead><tr><th>Product</th><th>Qty</th><th>UoM</th><th>Unit Price</th><th>Line Total</th></tr></thead><tbody>';
        (res.items || []).forEach(function (it) {
            html += '<tr><td>' + procEsc(it.product_name || '—') + '<br><small>Stock: ' + procQty(it.stock_quantity) + '</small></td><td>' + procQty(it.quantity) + '</td><td>' + procEsc(it.unit_of_measure || '—') + '</td><td>' + procMoney(it.estimated_unit_price) + '</td><td>' + procMoney(it.line_total) + '</td></tr>';
        });
        html += '</tbody></table></div>';

        if ((res.orders || []).length) {
            html += '<h4 style="margin:16px 0 8px;">Purchase Orders</h4><div class="inv-table-wrap"><table class="inv-table" style="width:100%;"><thead><tr><th>PO #</th><th>Order Date</th><th>Status</th><th>Total</th></tr></thead><tbody>';
            res.orders.forEach(function (po) { html += '<tr><td>' + procEsc(po.po_number) + '</td><td>' + procDate(po.order_date) + '</td><td>' + procBadge(po.status) + '</td><td>' + procMoney(po.total_amount) + '</td></tr>'; });
            html += '</tbody></table></div>';
        }

        html += '<h4 style="margin:16px 0 8px;">Approval History</h4><div class="inv-table-wrap"><table class="inv-table" style="width:100%;"><thead><tr><th>When</th><th>Action</th><th>By</th><th>Comments</th></tr></thead><tbody>';
        (res.logs || []).forEach(function (l) {
            html += '<tr><td>' + procDateTime(l.created_at) + '</td><td>' + procEsc(String(l.action).replace(/_/g, ' ')) + '</td><td>' + procEsc(l.actor_name || '—') + '</td><td>' + procEsc(l.comments || '—') + '</td></tr>';
        });
        html += '</tbody></table></div>';

        document.getElementById('prDetailBody').innerHTML = html;
        procOpenModal('prDetailModal');
    });
}

function submitRequest(id) {
    var data = new FormData();
    data.append('id', id);
    procPost('submit_purchase_request', data).then(function (res) {
        if (res.success) { procToast('success', 'Submitted for approval', 'The request is now pending admin approval.'); loadRequests(); return; }
        if (res.requires_ack) {
            var fd = new FormData(); fd.append('id', id);
            procConfirmWarnings(res.warnings || [], fd, function (f) {
                procPost('submit_purchase_request', f).then(function (r2) {
                    if (r2.success) { procToast('success', 'Submitted for approval', ''); loadRequests(); }
                    else { procToast('error', 'Cannot submit', r2.message || ''); }
                });
            });
            return;
        }
        procToast('error', 'Cannot submit', res.message || '');
    });
}

function deleteRequest(id) {
    window.customConfirm('Delete this draft purchase request? This cannot be undone.', function () {
        procGet('?action=delete_purchase_request&id=' + id).then(function (res) {
            if (res.success) { procToast('success', 'Draft deleted', ''); loadRequests(); }
            else procToast('error', 'Cannot delete', res.message || '');
        });
    }, 'danger');
}

function openCancelRequest(id) {
    cancelTargetId = id;
    document.getElementById('prCancelReason').value = '';
    procOpenModal('prCancelModal');
}

function confirmCancelRequest() {
    var data = new FormData();
    data.append('id', cancelTargetId);
    data.append('reason', document.getElementById('prCancelReason').value);
    procPost('cancel_purchase_request', data).then(function (res) {
        if (res.success) { procToast('success', 'Request cancelled', ''); procCloseModal('prCancelModal'); loadRequests(); }
        else procToast('error', 'Cannot cancel', res.message || '');
    });
}

procGet('?action=get_suppliers').then(function (rows) {
    prSuppliers = rows || [];
    var select = document.getElementById('prSupplier');
    prSuppliers.forEach(function (s) {
        var o = document.createElement('option'); o.value = s.id; o.textContent = s.name; select.appendChild(o);
    });
});

loadRequests();
</script>
