<?php
if (!canAccess('procurement') && !isAdmin()) {
    echo '<div class="inv-card" style="margin:2rem;text-align:center;padding:40px;"><h2 style="color:#111827;">Access Denied</h2></div>';
    return;
}
?>

<div class="inv-page-header">
    <div class="inv-page-header-left">
        <h2 class="inv-page-title">Procurement History</h2>
        <p class="inv-page-subtitle">Complete audit trail of submissions, approvals, orders, receipts and payments</p>
    </div>
</div>

<div class="inv-card">
    <div class="inv-card-header">
        <div class="inv-card-title-wrap"><div class="inv-card-title">Workflow Audit Trail</div></div>
        <div class="inv-filter-bar" style="margin:0;">
            <div class="inv-search-wrap"><span class="inv-search-icon">🔍</span><input type="text" id="hSearch" class="inv-search-input" placeholder="Search request, PO, comment…" oninput="renderHistory()"></div>
            <select id="hAction" class="inv-filter-select" onchange="renderHistory()">
                <option value="">All actions</option>
                <option value="created">Created</option>
                <option value="updated">Updated</option>
                <option value="submitted">Submitted</option>
                <option value="budget_approved">Budget Approved</option>
                <option value="budget_rejected">Budget Rejected</option>
                <option value="budget_reallocate">Budget Reallocation</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="revision_requested">Revision Requested</option>
                <option value="cancelled">Cancelled</option>
                <option value="rfq_issued">RFQ Issued</option>
                <option value="rfq_quote_recorded">Quotation Recorded</option>
                <option value="supplier_selected">Supplier Selected</option>
                <option value="po_created">PO Created</option>
                <option value="po_issued">PO Issued</option>
                <option value="po_submitted_finance">Submitted to Finance</option>
                <option value="po_finance_approved">Finance Approved</option>
                <option value="po_finance_rejected">Finance Declined</option>
                <option value="po_acknowledged">PO Acknowledged</option>
                <option value="po_cancelled">PO Cancelled</option>
                <option value="goods_received">Goods Received</option>
                <option value="invoice_recorded">Invoice Recorded</option>
                <option value="payment_scheduled">Payment Scheduled</option>
                <option value="payment_recorded">Payment Recorded</option>
                <option value="payment_advice_sent">Payment Advice</option>
                <option value="payment_reconciled">Payment Reconciled</option>
                <option value="po_closed">PO Closed</option>
            </select>
            <input type="date" id="hFrom" class="inv-filter-select" onchange="renderHistory()">
            <input type="date" id="hTo" class="inv-filter-select" onchange="renderHistory()">
        </div>
    </div>
    <div class="inv-table-wrap">
        <table class="inv-table" style="width:100%;min-width:900px;">
            <thead><tr><th>When</th><th>Request #</th><th>PO #</th><th>Action</th><th>By</th><th>Role</th><th>Comments</th></tr></thead>
            <tbody id="hBody"><tr><td colspan="7" style="text-align:center;padding:24px;">Loading history…</td></tr></tbody>
        </table>
    </div>
</div>

<script src="assets/js/procurement.js"></script>
<script>
var hRows = [];

function loadHistory() {
    procGet('?action=get_procurement_history').then(function (res) {
        if (!res.success) { document.getElementById('hBody').innerHTML = '<tr><td colspan="7" style="text-align:center;padding:24px;color:#EF4444;">' + procEsc(res.message || 'Unable to load') + '</td></tr>'; return; }
        hRows = res.logs || [];
        renderHistory();
    });
}

function renderHistory() {
    var q = (document.getElementById('hSearch').value || '').toLowerCase();
    var action = document.getElementById('hAction').value;
    var from = document.getElementById('hFrom').value;
    var to = document.getElementById('hTo').value;
    var rows = hRows.filter(function (l) {
        if (action && l.action !== action) return false;
        var d = procDate(l.created_at);
        if (from && d < from) return false;
        if (to && d > to) return false;
        if (q) {
            var hay = ((l.request_number || '') + ' ' + (l.po_number || '') + ' ' + (l.comments || '') + ' ' + (l.actor_name || '')).toLowerCase();
            if (hay.indexOf(q) === -1) return false;
        }
        return true;
    });
    if (!rows.length) {
        document.getElementById('hBody').innerHTML = '<tr><td colspan="7"><div class="inv-empty-state"><div class="inv-empty-icon">🕓</div><div class="inv-empty-title">No matching history</div><div class="inv-empty-text">Procurement activity will be recorded here.</div></div></td></tr>';
        return;
    }
    document.getElementById('hBody').innerHTML = rows.map(function (l) {
        return '<tr><td>' + procDateTime(l.created_at) + '</td><td>' + procEsc(l.request_number || '—') + '</td><td>' + procEsc(l.po_number || '—') + '</td><td><span class="badge badge-info">' + procEsc(String(l.action).replace(/_/g, ' ')) + '</span></td><td>' + procEsc(l.actor_name || '—') + '</td><td>' + procEsc(l.actor_role || '—') + '</td><td>' + procEsc(l.comments || '—') + '</td></tr>';
    }).join('');
}

loadHistory();
</script>
