<?php
$canApprove = canAccess('procurement_approve') || isAdmin();
$canBudget = canAccess('procurement_finance') || isAdmin();
if (!$canApprove && !$canBudget) {
    echo '<div class="inv-card" style="margin:2rem;text-align:center;padding:40px;"><h2 style="color:#111827;">Access Denied</h2><p style="color:#6b7280;">Only approvers and Finance can review purchase requests.</p></div>';
    return;
}
$canManagePO = canAccess('procurement_po_manage') || isAdmin();
?>

<div class="inv-page-header">
    <div class="inv-page-header-left">
        <h2 class="inv-page-title">Procurement Approvals</h2>
        <p class="inv-page-subtitle">Finance checks budget availability, then management approves the request</p>
    </div>
    <a href="?page=purchase_orders" class="inv-btn-secondary" style="text-decoration:none;">Purchase Orders →</a>
</div>

<?php if ($canBudget): ?>
<div class="inv-card" style="margin-bottom:18px;">
    <div class="inv-card-header">
        <div class="inv-card-title-wrap"><div class="inv-card-title">Budget Review Queue <span class="badge badge-info">Finance</span></div></div>
        <div class="inv-filter-bar" style="margin:0;">
            <div class="inv-search-wrap"><span class="inv-search-icon">🔍</span><input type="text" id="bqSearch" class="inv-search-input" placeholder="Search number, requester, cost centre…" oninput="renderBudgetQueue()"></div>
        </div>
    </div>
    <div class="inv-table-wrap">
        <table class="inv-table" style="width:100%;min-width:900px;">
            <thead><tr><th>Request #</th><th>Requester</th><th>Cost Centre</th><th>Budget Code</th><th>Estimated Total</th><th>Submitted</th><th style="text-align:right;">Action</th></tr></thead>
            <tbody id="bqBody"><tr><td colspan="7" style="text-align:center;padding:24px;">Loading budget queue…</td></tr></tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($canApprove): ?>
<div class="inv-card">
    <div class="inv-card-header">
        <div class="inv-card-title-wrap"><div class="inv-card-title">Approval Queue</div></div>
        <div class="inv-filter-bar" style="margin:0;">
            <div class="inv-search-wrap"><span class="inv-search-icon">🔍</span><input type="text" id="apSearch" class="inv-search-input" placeholder="Search number, requester, supplier…" oninput="renderQueue()"></div>
            <select id="apStatus" class="inv-filter-select" onchange="loadQueue()">
                <option value="pending_approval">Pending Approval</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="revision_requested">Revision Requested</option>
                <option value="">All requests</option>
            </select>
        </div>
    </div>
    <div class="inv-table-wrap">
        <table class="inv-table" style="width:100%;min-width:900px;">
            <thead><tr><th>Request #</th><th>Requester</th><th>Supplier</th><th>Items</th><th>Estimated Total</th><th>Submitted</th><th>Status</th><th style="text-align:right;">Action</th></tr></thead>
            <tbody id="apBody"><tr><td colspan="8" style="text-align:center;padding:24px;">Loading queue…</td></tr></tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Admin review modal -->
<div class="modal" id="apReviewModal">
    <div class="modal-content inv-modal" style="max-width:920px;max-height:90vh;overflow:auto;">
        <div class="inv-modal-header">
            <div class="inv-modal-header-icon purple">✅</div>
            <div class="inv-modal-header-text"><h2 id="apTitle">Review Request</h2><p id="apSub"></p></div>
            <button class="inv-modal-close" onclick="procCloseModal('apReviewModal')">&times;</button>
        </div>
        <div id="apBodyDetail"></div>
        <div id="apDecisionArea" style="display:none;border-top:1px solid #E5E7EB;padding-top:14px;margin-top:8px;">
            <div class="inv-form-group"><label class="inv-form-label">Comment <span id="apCommentReq" style="color:#EF4444;display:none;">(required to reject or request revision)</span></label><textarea id="apComments" class="inv-form-textarea" rows="2" placeholder="Feedback for the requester…"></textarea></div>
            <div class="inv-modal-actions" style="justify-content:flex-end;">
                <button type="button" class="inv-btn-submit amber" onclick="decide('request_revision')">Request Revision</button>
                <button type="button" class="inv-btn-submit red" onclick="decide('reject')">Reject</button>
                <button type="button" class="inv-btn-submit green" onclick="decide('approve')">Approve</button>
            </div>
        </div>
    </div>
</div>

<!-- Budget review modal -->
<div class="modal" id="bqReviewModal">
    <div class="modal-content inv-modal" style="max-width:760px;max-height:90vh;overflow:auto;">
        <div class="inv-modal-header">
            <div class="inv-modal-header-icon blue">💰</div>
            <div class="inv-modal-header-text"><h2 id="bqTitle">Budget Check</h2><p id="bqSub"></p></div>
            <button class="inv-modal-close" onclick="procCloseModal('bqReviewModal')">&times;</button>
        </div>
        <div id="bqBodyDetail"></div>
        <div id="bqDecisionArea" style="border-top:1px solid #E5E7EB;padding-top:14px;margin-top:8px;">
            <div class="inv-form-group"><label class="inv-form-label">Available budget (₱)</label><input type="number" id="bqAvailable" class="inv-form-input" min="0" step="0.01" placeholder="Amount available under this budget code"></div>
            <div class="inv-form-group"><label class="inv-form-label">Comment <span id="bqCommentReq" style="color:#EF4444;display:none;">(required to reject or request reallocation)</span></label><textarea id="bqComments" class="inv-form-textarea" rows="2" placeholder="Notes for the requester…"></textarea></div>
            <div class="inv-modal-actions" style="justify-content:flex-end;">
                <button type="button" class="inv-btn-submit amber" onclick="budgetDecide('reallocate')">Request Reallocation</button>
                <button type="button" class="inv-btn-submit red" onclick="budgetDecide('reject')">Reject</button>
                <button type="button" class="inv-btn-submit green" onclick="budgetDecide('approve')">Approve Budget</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/procurement.js"></script>
<script>
var AP_CAN_PO = <?php echo $canManagePO ? 'true' : 'false'; ?>;
var AP_CAN_APPROVE = <?php echo $canApprove ? 'true' : 'false'; ?>;
var AP_CAN_BUDGET = <?php echo $canBudget ? 'true' : 'false'; ?>;
var apRows = [];
var apCurrent = 0;
var bqRows = [];
var bqCurrent = 0;

function loadQueue() {
    if (!AP_CAN_APPROVE) return;
    var status = document.getElementById('apStatus').value;
    var url = '?action=get_purchase_requests' + (status ? '&status=' + encodeURIComponent(status) : '');
    procGet(url).then(function (res) {
        if (!res.success) { document.getElementById('apBody').innerHTML = '<tr><td colspan="8" style="text-align:center;padding:24px;color:#EF4444;">' + procEsc(res.message || 'Unable to load') + '</td></tr>'; return; }
        apRows = res.requests || [];
        renderQueue();
    });
}

function renderQueue() {
    if (!AP_CAN_APPROVE) return;
    var q = (document.getElementById('apSearch').value || '').toLowerCase();
    var rows = apRows.filter(function (r) {
        if (!q) return true;
        var hay = (r.request_number + ' ' + (r.requester_name || '') + ' ' + (r.supplier_name || '')).toLowerCase();
        return hay.indexOf(q) !== -1;
    });
    if (!rows.length) {
        document.getElementById('apBody').innerHTML = '<tr><td colspan="8"><div class="inv-empty-state"><div class="inv-empty-icon">🫡</div><div class="inv-empty-title">Nothing in this queue</div><div class="inv-empty-text">Submitted requests awaiting a decision will appear here.</div></div></td></tr>';
        return;
    }
    document.getElementById('apBody').innerHTML = rows.map(function (r) {
        return '<tr>' +
            '<td><strong>' + procEsc(r.request_number) + '</strong></td>' +
            '<td>' + procEsc(r.requester_name || '—') + '</td>' +
            '<td>' + procEsc(r.supplier_name || '—') + '</td>' +
            '<td>' + Number(r.item_count) + ' line(s)</td>' +
            '<td>' + procMoney(r.estimated_total) + '</td>' +
            '<td>' + procDateTime(r.submitted_at) + '</td>' +
            '<td>' + procBadge(r.status) + '</td>' +
            '<td style="text-align:right;"><button class="inv-btn-secondary" style="padding:6px 12px;" onclick="reviewRequest(' + r.id + ')">Review</button></td>' +
        '</tr>';
    }).join('');
}

function reviewRequest(id) {
    apCurrent = id;
    procGet('?action=get_purchase_request&id=' + id).then(function (res) {
        if (!res.success) return procToast('error', 'Unable to load request', res.message || '');
        var r = res.request;
        document.getElementById('apTitle').textContent = r.request_number;
        document.getElementById('apSub').textContent = (r.requester_name || '—') + ' · ' + (r.supplier_name || 'no supplier');
        var html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">';
        html += '<div><strong>Requester:</strong> ' + procEsc(r.requester_name || '—') + '</div>';
        html += '<div><strong>Request date:</strong> ' + procDate(r.request_date) + '</div>';
        html += '<div><strong>Supplier:</strong> ' + procEsc(r.supplier_name || '—') + '</div>';
        html += '<div><strong>Estimated total:</strong> ' + procMoney(r.estimated_total) + '</div>';
        html += '<div><strong>Cost centre:</strong> ' + procEsc(r.cost_center || '—') + '</div>';
        html += '<div><strong>Budget code:</strong> ' + procEsc(r.budget_code || '—') + '</div>';
        html += '<div><strong>Status:</strong> ' + procBadge(r.status) + '</div>';
        html += '<div><strong>Submitted:</strong> ' + procDateTime(r.submitted_at) + '</div>';
        html += '</div>';
        if (r.budget_comments) html += '<div class="alert alert-info"><strong>Budget check:</strong> ' + procEsc(r.budget_comments) + '</div>';
        html += '<p><strong>Reason:</strong> ' + procEsc(r.reason || '—') + '</p>';
        if (r.remarks) html += '<p><strong>Remarks:</strong> ' + procEsc(r.remarks) + '</p>';
        if (r.review_comments) html += '<div class="alert alert-info"><strong>Previous feedback:</strong> ' + procEsc(r.review_comments) + '</div>';
        if (r.quotation_path) html += '<p><a href="' + String(r.quotation_path).replace(/"/g, '&quot;') + '" target="_blank" class="inv-btn-secondary" style="text-decoration:none;padding:6px 10px;">📎 View quotation</a></p>';

        html += '<h4 style="margin:14px 0 8px;">Requested Products</h4><div class="inv-table-wrap"><table class="inv-table" style="width:100%;"><thead><tr><th>Product</th><th>Qty</th><th>UoM</th><th>Est. Unit Price</th><th>Line Total</th><th>Stock On Hand</th></tr></thead><tbody>';
        (res.items || []).forEach(function (it) {
            html += '<tr><td>' + procEsc(it.product_name || '—') + '</td><td>' + procQty(it.quantity) + '</td><td>' + procEsc(it.unit_of_measure || '—') + '</td><td>' + procMoney(it.estimated_unit_price) + '</td><td>' + procMoney(it.line_total) + '</td><td>' + procQty(it.stock_quantity) + '</td></tr>';
        });
        html += '</tbody></table></div>';

        html += '<h4 style="margin:14px 0 8px;">Approval History</h4><div class="inv-table-wrap"><table class="inv-table" style="width:100%;"><thead><tr><th>When</th><th>Action</th><th>By</th><th>Comments</th></tr></thead><tbody>';
        (res.logs || []).forEach(function (l) { html += '<tr><td>' + procDateTime(l.created_at) + '</td><td>' + procEsc(String(l.action).replace(/_/g, ' ')) + '</td><td>' + procEsc(l.actor_name || '—') + '</td><td>' + procEsc(l.comments || '—') + '</td></tr>'; });
        html += '</tbody></table></div>';

        if (r.status === 'approved' && AP_CAN_PO) {
            var hasPo = (res.orders || []).some(function (po) { return po.status !== 'cancelled'; });
            if (!hasPo) html += '<div style="margin-top:14px;"><a href="?page=procurement_sourcing&id=' + r.id + '" class="inv-btn-primary" style="text-decoration:none;">Source Suppliers / Create PO →</a></div>';
        }

        document.getElementById('apBodyDetail').innerHTML = html;
        document.getElementById('apComments').value = '';
        var pending = r.status === 'pending_approval';
        document.getElementById('apDecisionArea').style.display = pending ? 'block' : 'none';
        procOpenModal('apReviewModal');
    });
}

function decide(decision) {
    var comments = document.getElementById('apComments').value.trim();
    if ((decision === 'reject' || decision === 'request_revision') && !comments) {
        procToast('error', 'Comment required', 'Enter a comment to reject or request revisions.');
        return;
    }
    var labels = { approve: 'Approve', reject: 'Reject', request_revision: 'Request revision' };
    window.customConfirm(labels[decision] + ' this purchase request?', function () {
        var fd = new FormData();
        fd.append('id', apCurrent);
        fd.append('decision', decision);
        fd.append('comments', comments);
        procPost('procurement_decision', fd).then(function (res) {
            if (res.success) {
                procToast('success', 'Decision recorded', 'Status is now ' + procStatus(res.status).label + '.');
                procCloseModal('apReviewModal');
                loadQueue();
            } else {
                procToast('error', 'Cannot record decision', res.message || '');
            }
        });
    }, decision === 'approve' ? 'success' : 'danger');
}

// ---------------- Budget review (Finance) ----------------
function loadBudgetQueue() {
    if (!AP_CAN_BUDGET) return;
    procGet('?action=get_purchase_requests&status=budget_review').then(function (res) {
        if (!res.success) { document.getElementById('bqBody').innerHTML = '<tr><td colspan="7" style="text-align:center;padding:24px;color:#EF4444;">' + procEsc(res.message || 'Unable to load') + '</td></tr>'; return; }
        bqRows = res.requests || [];
        renderBudgetQueue();
    });
}

function renderBudgetQueue() {
    if (!AP_CAN_BUDGET) return;
    var q = (document.getElementById('bqSearch').value || '').toLowerCase();
    var rows = bqRows.filter(function (r) {
        if (!q) return true;
        var hay = (r.request_number + ' ' + (r.requester_name || '') + ' ' + (r.cost_center || '') + ' ' + (r.budget_code || '')).toLowerCase();
        return hay.indexOf(q) !== -1;
    });
    if (!rows.length) {
        document.getElementById('bqBody').innerHTML = '<tr><td colspan="7"><div class="inv-empty-state"><div class="inv-empty-icon">💰</div><div class="inv-empty-title">No budget checks pending</div><div class="inv-empty-text">Requisitions submitted for a budget check will appear here.</div></div></td></tr>';
        return;
    }
    document.getElementById('bqBody').innerHTML = rows.map(function (r) {
        return '<tr>' +
            '<td><strong>' + procEsc(r.request_number) + '</strong></td>' +
            '<td>' + procEsc(r.requester_name || '—') + '</td>' +
            '<td>' + procEsc(r.cost_center || '—') + '</td>' +
            '<td>' + procEsc(r.budget_code || '—') + '</td>' +
            '<td>' + procMoney(r.estimated_total) + '</td>' +
            '<td>' + procDateTime(r.submitted_at) + '</td>' +
            '<td style="text-align:right;"><button class="inv-btn-secondary" style="padding:6px 12px;" onclick="reviewBudget(' + r.id + ')">Check</button></td>' +
        '</tr>';
    }).join('');
}

function reviewBudget(id) {
    bqCurrent = id;
    procGet('?action=get_purchase_request&id=' + id).then(function (res) {
        if (!res.success) return procToast('error', 'Unable to load request', res.message || '');
        var r = res.request;
        document.getElementById('bqTitle').textContent = 'Budget Check · ' + r.request_number;
        document.getElementById('bqSub').textContent = (r.requester_name || '—') + ' · ' + (r.cost_center || 'no cost centre');
        var html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">';
        html += '<div><strong>Estimated total:</strong> ' + procMoney(r.estimated_total) + '</div>';
        html += '<div><strong>Cost centre:</strong> ' + procEsc(r.cost_center || '—') + '</div>';
        html += '<div><strong>Budget code:</strong> ' + procEsc(r.budget_code || '—') + '</div>';
        html += '<div><strong>Requested budget:</strong> ' + procMoney(r.budget_amount) + '</div>';
        html += '</div>';
        html += '<p><strong>Reason:</strong> ' + procEsc(r.reason || '—') + '</p>';
        html += '<h4 style="margin:14px 0 8px;">Products</h4><div class="inv-table-wrap"><table class="inv-table" style="width:100%;"><thead><tr><th>Product</th><th>Qty</th><th>Est. Unit Price</th><th>Line Total</th></tr></thead><tbody>';
        (res.items || []).forEach(function (it) {
            html += '<tr><td>' + procEsc(it.product_name || '—') + '</td><td>' + procQty(it.quantity) + '</td><td>' + procMoney(it.estimated_unit_price) + '</td><td>' + procMoney(it.line_total) + '</td></tr>';
        });
        html += '</tbody></table></div>';
        document.getElementById('bqBodyDetail').innerHTML = html;
        document.getElementById('bqAvailable').value = Number(r.estimated_total || 0).toFixed(2);
        document.getElementById('bqComments').value = '';
        procOpenModal('bqReviewModal');
    });
}

function budgetDecide(decision) {
    var comments = document.getElementById('bqComments').value.trim();
    var available = document.getElementById('bqAvailable').value;
    if ((decision === 'reject' || decision === 'reallocate') && !comments) {
        procToast('error', 'Comment required', 'Enter a comment to reject or request a reallocation.');
        return;
    }
    var labels = { approve: 'Approve the budget for', reject: 'Reject the budget for', reallocate: 'Request a budget reallocation for' };
    window.customConfirm(labels[decision] + ' this request?', function () {
        var fd = new FormData();
        fd.append('id', bqCurrent);
        fd.append('decision', decision);
        fd.append('comments', comments);
        fd.append('available_budget', available);
        procPost('budget_decision', fd).then(function (res) {
            if (res.success) {
                procToast('success', 'Budget decision recorded', '');
                procCloseModal('bqReviewModal');
                loadBudgetQueue();
            } else {
                procToast('error', 'Cannot record decision', res.message || '');
            }
        });
    }, decision === 'approve' ? 'success' : 'danger');
}

if (AP_CAN_APPROVE) loadQueue();
if (AP_CAN_BUDGET) loadBudgetQueue();
</script>
