<?php
if (!canAccess('procurement') && !isAdmin()) {
    echo '<div class="inv-card" style="margin:2rem;text-align:center;padding:40px;">
        <h2 style="color:#111827;">Access Denied</h2>
        <p style="color:#6b7280;">You do not have access to the procurement dashboard.</p>
        <a href="?page=dashboard" class="inv-btn-primary" style="margin-top:1rem;text-decoration:none;">Go to Dashboard</a>
    </div>';
    return;
}
$canManage = canAccess('procurement_manage') || isAdmin();
$canApprove = canAccess('procurement_approve') || isAdmin();
$canFinance = canAccess('procurement_finance') || isAdmin();
$unread = getUnreadProcurementNotificationCount($_SESSION['user_id'] ?? 0);
?>

<div class="inv-page-header">
    <div class="inv-page-header-left">
        <h2 class="inv-page-title">Procurement Dashboard</h2>
        <p class="inv-page-subtitle">Requisitions, approvals, purchase orders, deliveries and payables at a glance</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <?php if ($canApprove): ?>
        <a href="?page=procurement_approvals" class="inv-btn-secondary" style="text-decoration:none;">Approvals</a>
        <?php endif; ?>
        <?php if ($canManage): ?>
        <a href="?page=purchase_request_create" class="inv-btn-primary" style="text-decoration:none;">+ New Purchase Request</a>
        <?php endif; ?>
    </div>
</div>

<div class="inv-stats-grid" id="procStats">
    <div class="inv-stat-card"><div class="inv-stat-header"><div class="inv-stat-icon" style="background:linear-gradient(135deg,#6366F1,#4F46E5);">📋</div></div><div class="inv-stat-label">Total Requests</div><div class="inv-stat-value" id="sTotal">—</div></div>
    <div class="inv-stat-card"><div class="inv-stat-header"><div class="inv-stat-icon" style="background:linear-gradient(135deg,#94A3B8,#64748B);">📝</div></div><div class="inv-stat-label">Draft</div><div class="inv-stat-value" id="sDraft">—</div></div>
    <div class="inv-stat-card"><div class="inv-stat-header"><div class="inv-stat-icon" style="background:linear-gradient(135deg,#F59E0B,#D97706);">⏳</div></div><div class="inv-stat-label">Pending Approval</div><div class="inv-stat-value warning" id="sPending">—</div></div>
    <div class="inv-stat-card"><div class="inv-stat-header"><div class="inv-stat-icon" style="background:linear-gradient(135deg,#0EA5E9,#0284C7);">💰</div></div><div class="inv-stat-label">Budget Review</div><div class="inv-stat-value" id="sBudget">—</div></div>
    <div class="inv-stat-card"><div class="inv-stat-header"><div class="inv-stat-icon" style="background:linear-gradient(135deg,#22C55E,#16A34A);">✅</div></div><div class="inv-stat-label">Approved</div><div class="inv-stat-value success" id="sApproved">—</div></div>
    <div class="inv-stat-card"><div class="inv-stat-header"><div class="inv-stat-icon" style="background:linear-gradient(135deg,#0EA5E9,#0369A1);">🔎</div></div><div class="inv-stat-label">Sourcing / RFQ</div><div class="inv-stat-value" id="sSourcing">—</div></div>
    <div class="inv-stat-card"><div class="inv-stat-header"><div class="inv-stat-icon" style="background:linear-gradient(135deg,#EF4444,#B91C1C);">⛔</div></div><div class="inv-stat-label">Rejected</div><div class="inv-stat-value danger" id="sRejected">—</div></div>
    <div class="inv-stat-card"><div class="inv-stat-header"><div class="inv-stat-icon" style="background:linear-gradient(135deg,#F59E0B,#D97706);">🏛️</div></div><div class="inv-stat-label">Pending Finance</div><div class="inv-stat-value warning" id="sFinPO">—</div></div>
    <div class="inv-stat-card"><div class="inv-stat-header"><div class="inv-stat-icon" style="background:linear-gradient(135deg,#0EA5E9,#0284C7);">🚚</div></div><div class="inv-stat-label">Awaiting Delivery</div><div class="inv-stat-value" id="sOrdered">—</div></div>
    <div class="inv-stat-card"><div class="inv-stat-header"><div class="inv-stat-icon" style="background:linear-gradient(135deg,#F59E0B,#B45309);">📦</div></div><div class="inv-stat-label">Partially Received</div><div class="inv-stat-value warning" id="sPartial">—</div></div>
    <div class="inv-stat-card"><div class="inv-stat-header"><div class="inv-stat-icon" style="background:linear-gradient(135deg,#22C55E,#15803D);">🏁</div></div><div class="inv-stat-label">Received Orders</div><div class="inv-stat-value success" id="sReceived">—</div></div>
    <div class="inv-stat-card"><div class="inv-stat-header"><div class="inv-stat-icon" style="background:linear-gradient(135deg,#EF4444,#991B1B);">💸</div></div><div class="inv-stat-label">Unpaid Invoices</div><div class="inv-stat-value danger" id="sUnpaid">—</div></div>
    <div class="inv-stat-card"><div class="inv-stat-header"><div class="inv-stat-icon" style="background:linear-gradient(135deg,#F59E0B,#B45309);">🚨</div></div><div class="inv-stat-label">Match Hold</div><div class="inv-stat-value warning" id="sHeld">—</div></div>
    <div class="inv-stat-card"><div class="inv-stat-header"><div class="inv-stat-icon" style="background:linear-gradient(135deg,#8B5CF6,#6D28D9);">🔔</div></div><div class="inv-stat-label">My Notifications</div><div class="inv-stat-value" id="sUnread"><?php echo (int)$unread; ?></div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;" class="proc-dash-grid">
    <div class="inv-card">
        <div class="inv-card-header">
            <div class="inv-card-title-wrap"><div class="inv-card-title">Recently Completed Purchases</div></div>
        </div>
        <div class="inv-table-wrap">
            <table class="inv-table" style="width:100%;">
                <thead><tr><th>PO Number</th><th>Supplier</th><th>Value</th><th>Status</th></tr></thead>
                <tbody id="recentBody"><tr><td colspan="4" style="text-align:center;padding:20px;">Loading…</td></tr></tbody>
            </table>
        </div>
    </div>

    <div class="inv-card">
        <div class="inv-card-header">
            <div class="inv-card-title-wrap"><div class="inv-card-title">Low-Stock Products</div></div>
            <a href="?page=stock" class="inv-btn-secondary" style="text-decoration:none;padding:6px 12px;">Stock</a>
        </div>
        <div class="inv-table-wrap">
            <table class="inv-table" style="width:100%;">
                <thead><tr><th>Product</th><th>On Hand</th><th>Threshold</th></tr></thead>
                <tbody id="lowStockBody"><tr><td colspan="3" style="text-align:center;padding:20px;">Loading…</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<div class="inv-card" style="margin-top:1.25rem;">
    <div class="inv-card-header">
        <div class="inv-card-title-wrap"><div class="inv-card-title">My Procurement Notifications</div></div>
        <button class="inv-btn-secondary" style="padding:6px 12px;" onclick="markAllRead()">Mark all read</button>
    </div>
    <div id="notifList" style="padding:4px 0;"><div style="padding:20px;text-align:center;color:#6b7280;">Loading…</div></div>
</div>

<style>
@media (max-width: 900px) { .proc-dash-grid { grid-template-columns: 1fr !important; } }
</style>

<script src="assets/js/procurement.js"></script>
<script>
function loadDashboard() {
    procGet('?action=get_procurement_dashboard').then(function (res) {
        if (!res.success) return procToast('error', 'Unable to load dashboard', res.message || '');
        var c = res.counts || {};
        document.getElementById('sTotal').textContent = c.total_requests || 0;
        document.getElementById('sDraft').textContent = c.draft || 0;
        document.getElementById('sBudget').textContent = c.budget_review || 0;
        document.getElementById('sPending').textContent = c.pending_approval || 0;
        document.getElementById('sApproved').textContent = c.approved || 0;
        document.getElementById('sSourcing').textContent = c.sourcing || 0;
        document.getElementById('sRejected').textContent = c.rejected || 0;
        document.getElementById('sFinPO').textContent = c.pending_finance || 0;
        document.getElementById('sOrdered').textContent = c.awaiting_delivery || 0;
        document.getElementById('sPartial').textContent = c.partially_received || 0;
        document.getElementById('sReceived').textContent = c.received || 0;
        document.getElementById('sUnpaid').textContent = c.unpaid_invoices || 0;
        document.getElementById('sHeld').textContent = c.invoices_on_hold || 0;

        var recent = res.recent || [];
        document.getElementById('recentBody').innerHTML = recent.length ? recent.map(function (o) {
            return '<tr><td><strong>' + procEsc(o.po_number) + '</strong></td><td>' + procEsc(o.supplier_name || '—') + '</td><td>' + procMoney(o.total_amount) + '</td><td>' + procBadge(o.status) + '</td></tr>';
        }).join('') : '<tr><td colspan="4"><div class="inv-empty-state"><div class="inv-empty-icon">📭</div><div class="inv-empty-title">No completed purchases yet</div><div class="inv-empty-text">Received purchase orders will appear here.</div></div></td></tr>';

        var low = res.low_stock || [];
        document.getElementById('lowStockBody').innerHTML = low.length ? low.map(function (p) {
            return '<tr><td>' + procEsc(p.name) + '</td><td><span class="inv-stock-badge ' + (Number(p.stock_quantity) <= 0 ? 'out' : 'low') + '">' + procQty(p.stock_quantity) + '</span></td><td>' + procQty(p.low_stock_threshold) + '</td></tr>';
        }).join('') : '<tr><td colspan="3"><div class="inv-empty-state"><div class="inv-empty-icon">✅</div><div class="inv-empty-title">All products are above threshold</div></div></td></tr>';
    });
}

function loadNotifications() {
    procGet('?action=get_procurement_notifications').then(function (res) {
        if (!res.success) return;
        document.getElementById('sUnread').textContent = res.unread || 0;
        var rows = res.notifications || [];
        document.getElementById('notifList').innerHTML = rows.length ? rows.map(function (n) {
            var dot = n.is_read == 0 ? '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#6366F1;margin-right:8px;"></span>' : '<span style="display:inline-block;width:8px;height:8px;margin-right:8px;"></span>';
            return '<div style="padding:12px 18px;border-bottom:1px solid #F1F5F9;">' + dot + '<strong>' + procEsc(n.title) + '</strong><div style="color:#6b7280;font-size:0.85rem;margin-top:2px;">' + procEsc(n.message || '') + '</div><div style="color:#9CA3AF;font-size:0.75rem;margin-top:2px;">' + procDateTime(n.created_at) + '</div></div>';
        }).join('') : '<div class="inv-empty-state" style="padding:24px;"><div class="inv-empty-icon">🔔</div><div class="inv-empty-title">No notifications</div><div class="inv-empty-text">You will be notified about approvals, deliveries and payables here.</div></div>';
    });
}

function markAllRead() {
    procPost('mark_procurement_notifications_read', new FormData()).then(function (res) {
        if (res.success) { procToast('success', 'Notifications cleared', ''); loadNotifications(); }
    });
}

loadDashboard();
loadNotifications();
</script>
