<?php
if (!canAccess('procurement_finance') && !isAdmin()) {
    echo '<div class="inv-card" style="margin:2rem;text-align:center;padding:40px;"><h2 style="color:#111827;">Access Denied</h2><p style="color:#6b7280;">Only Finance can record supplier invoices and payments.</p></div>';
    return;
}
?>

<div class="inv-page-header">
    <div class="inv-page-header-left">
        <h2 class="inv-page-title">Procurement Payments</h2>
        <p class="inv-page-subtitle">Match invoices (three-way), schedule payments, issue advice and reconcile</p>
    </div>
    <button class="inv-btn-primary" onclick="openInvoiceModal()">+ Record Supplier Invoice</button>
</div>

<div class="inv-card">
    <div class="inv-card-header">
        <div class="inv-card-title-wrap"><div class="inv-card-title">Supplier Invoices</div></div>
        <div class="inv-filter-bar" style="margin:0;">
            <div class="inv-search-wrap"><span class="inv-search-icon">🔍</span><input type="text" id="invSearch" class="inv-search-input" placeholder="Search invoice, supplier, PO…" oninput="renderInvoices()"></div>
            <select id="invStatus" class="inv-filter-select" onchange="renderInvoices()">
                <option value="">All payment statuses</option>
                <option value="unpaid">Unpaid</option>
                <option value="partial">Partially Paid</option>
                <option value="paid">Paid</option>
            </select>
            <select id="invMatch" class="inv-filter-select" onchange="renderInvoices()">
                <option value="">All match states</option>
                <option value="matched">Matched</option>
                <option value="held">On Hold</option>
                <option value="pending">Pending</option>
            </select>
        </div>
    </div>
    <div class="inv-table-wrap">
        <table class="inv-table" style="width:100%;min-width:1100px;">
            <thead><tr><th>Invoice #</th><th>Supplier</th><th>PO #</th><th>Invoice Date</th><th>Amount</th><th>Match</th><th>Paid</th><th>Outstanding</th><th>Status</th><th style="text-align:right;">Actions</th></tr></thead>
            <tbody id="invBody"><tr><td colspan="10" style="text-align:center;padding:24px;">Loading invoices…</td></tr></tbody>
        </table>
    </div>
</div>

<div class="inv-card" style="margin-top:1.25rem;">
    <div class="inv-card-header"><div class="inv-card-title-wrap"><div class="inv-card-title">Payment Records</div></div></div>
    <div class="inv-table-wrap">
        <table class="inv-table" style="width:100%;min-width:900px;">
            <thead><tr><th>Date</th><th>Invoice #</th><th>Supplier</th><th>Amount</th><th>Method</th><th>Reference</th><th>Recorded By</th></tr></thead>
            <tbody id="payBody"><tr><td colspan="7" style="text-align:center;padding:20px;">Loading payments…</td></tr></tbody>
        </table>
    </div>
</div>

<!-- Record Invoice modal -->
<div class="modal" id="invoiceModal">
    <div class="modal-content inv-modal" style="max-width:560px;">
        <div class="inv-modal-header">
            <div class="inv-modal-header-icon green">🧾</div>
            <div class="inv-modal-header-text"><h2>Record Supplier Invoice</h2><p>Link the invoice to a purchase order — it is auto-matched against the PO and goods received</p></div>
            <button class="inv-modal-close" onclick="procCloseModal('invoiceModal')">&times;</button>
        </div>
        <div class="inv-form-group"><label class="inv-form-label">Purchase Order *</label><select id="invPo" class="inv-form-select"></select></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="inv-form-group"><label class="inv-form-label">Invoice Number *</label><input type="text" id="invNumber" class="inv-form-input" placeholder="e.g. SI-2026-0451"></div>
            <div class="inv-form-group"><label class="inv-form-label">Invoice Date *</label><input type="date" id="invDate" class="inv-form-input" value="<?php echo date('Y-m-d'); ?>"></div>
        </div>
        <div class="inv-form-group"><label class="inv-form-label">Invoice Amount (₱) *</label><input type="number" id="invAmount" class="inv-form-input" min="0.01" step="0.01" placeholder="0.00"><small id="invPoHint" style="color:#6b7280;"></small></div>
        <div class="inv-form-group"><label class="inv-form-label">Notes</label><textarea id="invNotes" class="inv-form-textarea" rows="2"></textarea></div>
        <div class="inv-modal-actions">
            <button type="button" class="inv-btn-cancel" onclick="procCloseModal('invoiceModal')">Cancel</button>
            <button type="button" class="inv-btn-submit green" onclick="saveInvoice()">Save Invoice</button>
        </div>
    </div>
</div>

<!-- Schedule Payment modal -->
<div class="modal" id="scheduleModal">
    <div class="modal-content inv-modal" style="max-width:520px;">
        <div class="inv-modal-header">
            <div class="inv-modal-header-icon blue">🗓️</div>
            <div class="inv-modal-header-text"><h2>Schedule Payment</h2><p id="schedSub"></p></div>
            <button class="inv-modal-close" onclick="procCloseModal('scheduleModal')">&times;</button>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="inv-form-group"><label class="inv-form-label">Scheduled date *</label><input type="date" id="schedDate" class="inv-form-input" value="<?php echo date('Y-m-d'); ?>"></div>
            <div class="inv-form-group"><label class="inv-form-label">Payment method</label>
                <select id="schedMethod" class="inv-form-select">
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cash">Cash</option>
                    <option value="cheque">Cheque</option>
                    <option value="online">Online / e-wallet</option>
                </select>
            </div>
        </div>
        <div class="inv-form-group"><label class="inv-form-label">Notes</label><textarea id="schedNotes" class="inv-form-textarea" rows="2"></textarea></div>
        <div class="inv-modal-actions">
            <button type="button" class="inv-btn-cancel" onclick="procCloseModal('scheduleModal')">Cancel</button>
            <button type="button" class="inv-btn-submit" onclick="saveSchedule()">Schedule payment</button>
        </div>
    </div>
</div>

<!-- Record Payment modal -->
<div class="modal" id="paymentModal">
    <div class="modal-content inv-modal" style="max-width:540px;">
        <div class="inv-modal-header">
            <div class="inv-modal-header-icon blue">💵</div>
            <div class="inv-modal-header-text"><h2>Record Payment</h2><p id="paySub"></p></div>
            <button class="inv-modal-close" onclick="procCloseModal('paymentModal')">&times;</button>
        </div>
        <div class="inv-form-group"><label class="inv-form-label">Amount (₱) *</label><input type="number" id="payAmount" class="inv-form-input" min="0.01" step="0.01" placeholder="0.00"><small id="payOutstanding" style="color:#6b7280;"></small></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="inv-form-group"><label class="inv-form-label">Payment Date *</label><input type="date" id="payDate" class="inv-form-input" value="<?php echo date('Y-m-d'); ?>"></div>
            <div class="inv-form-group"><label class="inv-form-label">Payment Method</label>
                <select id="payMethod" class="inv-form-select">
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cheque">Cheque</option>
                    <option value="online">Online / e-wallet</option>
                </select>
            </div>
        </div>
        <div class="inv-form-group"><label class="inv-form-label">Reference Number</label><input type="text" id="payReference" class="inv-form-input" placeholder="Cheque / transaction reference"></div>
        <div class="inv-form-group"><label class="inv-form-label">Notes</label><textarea id="payNotes" class="inv-form-textarea" rows="2"></textarea></div>
        <div class="inv-modal-actions">
            <button type="button" class="inv-btn-cancel" onclick="procCloseModal('paymentModal')">Cancel</button>
            <button type="button" class="inv-btn-submit" onclick="savePayment()">Record Payment</button>
        </div>
    </div>
</div>

<!-- Payment advice modal -->
<div class="modal" id="adviceModal">
    <div class="modal-content inv-modal" style="max-width:520px;">
        <div class="inv-modal-header">
            <div class="inv-modal-header-icon green">✉️</div>
            <div class="inv-modal-header-text"><h2>Payment Advice</h2><p id="adviceSub"></p></div>
            <button class="inv-modal-close" onclick="procCloseModal('adviceModal')">&times;</button>
        </div>
        <div class="inv-form-group"><label class="inv-form-label">Sent to *</label><input type="text" id="adviceRecipient" class="inv-form-input" placeholder="Supplier contact / email the advice was sent to"></div>
        <div class="inv-form-group"><label class="inv-form-label">Notes</label><textarea id="adviceNotes" class="inv-form-textarea" rows="2"></textarea></div>
        <div class="inv-modal-actions">
            <button type="button" class="inv-btn-cancel" onclick="procCloseModal('adviceModal')">Cancel</button>
            <button type="button" class="inv-btn-submit green" onclick="saveAdvice()">Mark advice sent</button>
        </div>
    </div>
</div>

<!-- Reconcile modal -->
<div class="modal" id="reconcileModal">
    <div class="modal-content inv-modal" style="max-width:520px;">
        <div class="inv-modal-header">
            <div class="inv-modal-header-icon blue">🔁</div>
            <div class="inv-modal-header-text"><h2>Reconcile Payment</h2><p id="reconSub"></p></div>
            <button class="inv-modal-close" onclick="procCloseModal('reconcileModal')">&times;</button>
        </div>
        <div class="inv-form-group"><label class="inv-form-label">Bank / statement reference</label><input type="text" id="reconReference" class="inv-form-input" placeholder="e.g. bank statement line ref"></div>
        <div class="inv-modal-actions">
            <button type="button" class="inv-btn-cancel" onclick="procCloseModal('reconcileModal')">Cancel</button>
            <button type="button" class="inv-btn-submit" onclick="saveReconcile()">Mark reconciled</button>
        </div>
    </div>
</div>

<!-- Void invoice modal -->
<div class="modal" id="voidModal">
    <div class="modal-content inv-modal" style="max-width:520px;">
        <div class="inv-modal-header">
            <div class="inv-modal-header-icon red">🗑️</div>
            <div class="inv-modal-header-text"><h2>Void Invoice</h2><p id="voidSub"></p></div>
            <button class="inv-modal-close" onclick="procCloseModal('voidModal')">&times;</button>
        </div>
        <p style="margin:0 0 14px;color:#6B7280;font-size:13.5px;line-height:1.5;">Voiding removes a wrongly-recorded invoice (for example one on match hold) from the payment queue so the correct invoice can be entered. This is permanent and recorded in the audit trail.</p>
        <div class="inv-form-group"><label class="inv-form-label">Void reason *</label><input type="text" id="voidReason" class="inv-form-input" placeholder="e.g. amount did not match the approved PO total"></div>
        <div class="inv-modal-actions">
            <button type="button" class="inv-btn-cancel" onclick="procCloseModal('voidModal')">Cancel</button>
            <button type="button" class="inv-btn-submit red" style="background:#DC2626;" onclick="saveVoid()">Void invoice</button>
        </div>
    </div>
</div>

<script src="assets/js/procurement.js"></script>
<script>
var invRows = [];
var eligiblePos = [];
var payInvoiceId = 0;
var payOutstanding = 0;
var schedInvoiceId = 0;
var adviceInvoiceId = 0;
var reconInvoiceId = 0;
var voidInvoiceId = 0;

function loadInvoices() {
    procGet('?action=get_supplier_invoices').then(function (res) {
        if (!res.success) { document.getElementById('invBody').innerHTML = '<tr><td colspan="10" style="text-align:center;padding:24px;color:#EF4444;">' + procEsc(res.message || 'Unable to load') + '</td></tr>'; return; }
        invRows = res.invoices || [];
        eligiblePos = res.orders || [];
        renderInvoices();
        renderPos();
    });
}

function renderInvoices() {
    var q = (document.getElementById('invSearch').value || '').toLowerCase();
    var status = document.getElementById('invStatus').value;
    var match = document.getElementById('invMatch').value;
    var rows = invRows.filter(function (i) {
        if (status && i.payment_status !== status) return false;
        if (match && (i.match_status || 'pending') !== match) return false;
        if (q) {
            var hay = ((i.invoice_number || '') + ' ' + (i.supplier_name || '') + ' ' + (i.po_number || '')).toLowerCase();
            if (hay.indexOf(q) === -1) return false;
        }
        return true;
    });
    if (!rows.length) {
        document.getElementById('invBody').innerHTML = '<tr><td colspan="10"><div class="inv-empty-state"><div class="inv-empty-icon">🧾</div><div class="inv-empty-title">No supplier invoices found</div><div class="inv-empty-text">Record an invoice against a received purchase order.</div></div></td></tr>';
        return;
    }
    document.getElementById('invBody').innerHTML = rows.map(function (i) {
        var num = procEsc(i.invoice_number), sup = procEsc(i.supplier_name || '');
        var btns = [];
        if (i.match_status === 'held') {
            btns.push('<span class="badge badge-danger" title="' + procEsc(i.match_notes || '') + '">Match hold</span>');
            if (Number(i.amount_paid) === 0) btns.push('<button class="inv-btn-secondary" style="padding:5px 10px;border-color:#DC2626;color:#DC2626;" onclick="openVoidModal(' + i.id + ', \'' + num + '\')">Void</button>');
        } else if (i.match_status !== 'matched') {
            btns.push('<span class="badge badge-secondary">Not matched</span>');
            if (Number(i.amount_paid) === 0) btns.push('<button class="inv-btn-secondary" style="padding:5px 10px;border-color:#DC2626;color:#DC2626;" onclick="openVoidModal(' + i.id + ', \'' + num + '\')">Void</button>');
        } else {
            if (i.payment_status !== 'paid') {
                if (!i.scheduled_for) {
                    btns.push('<button class="inv-btn-secondary" style="padding:5px 10px;" onclick="openScheduleModal(' + i.id + ', \'' + num + '\')">Schedule</button>');
                } else {
                    btns.push('<button class="inv-btn-primary" style="padding:5px 10px;" onclick="openPaymentModal(' + i.id + ', ' + Number(i.outstanding) + ', \'' + num + '\', \'' + sup + '\')">Pay</button>');
                }
            } else {
                btns.push('<span style="color:#16A34A;font-weight:600;">✔ Paid</span>');
            }
            if (i.scheduled_for && !i.advice_sent_at) btns.push('<button class="inv-btn-secondary" style="padding:5px 10px;" onclick="openAdviceModal(' + i.id + ', \'' + num + '\')">Advice</button>');
            if (Number(i.amount_paid) > 0 && !i.reconciled_at) btns.push('<button class="inv-btn-secondary" style="padding:5px 10px;" onclick="openReconcileModal(' + i.id + ', \'' + num + '\')">Reconcile</button>');
        }
        var matchCell = procBadge(i.match_status || 'pending') + (i.match_notes ? '<br><small title="' + procEsc(i.match_notes) + '">' + procEsc(String(i.match_notes).slice(0, 34)) + '</small>' : '');
        var schedCell = '';
        if (i.scheduled_for) schedCell = '<br><small>🗓 ' + procDate(i.scheduled_for) + (i.advice_sent_at ? ' ✉' : '') + (i.reconciled_at ? ' 🔁' : '') + '</small>';
        return '<tr>' +
            '<td><strong>' + num + '</strong></td>' +
            '<td>' + sup + '</td>' +
            '<td>' + procEsc(i.po_number || '—') + '</td>' +
            '<td>' + procDate(i.invoice_date) + '</td>' +
            '<td>' + procMoney(i.invoice_amount) + '</td>' +
            '<td>' + matchCell + '</td>' +
            '<td>' + procMoney(i.amount_paid) + '</td>' +
            '<td><strong>' + procMoney(i.outstanding) + '</strong></td>' +
            '<td>' + procBadge(i.payment_status) + schedCell + '</td>' +
            '<td style="text-align:right;"><div class="inv-actions" style="justify-content:flex-end;">' + btns.join(' ') + '</div></td>' +
        '</tr>';
    }).join('');
}

function renderPos() {
    var select = document.getElementById('invPo');
    select.innerHTML = '<option value="">— Select a purchase order —</option>';
    eligiblePos.forEach(function (po) {
        var o = document.createElement('option');
        o.value = po.id;
        o.textContent = po.po_number + ' · ' + (po.supplier_name || '') + ' · ' + procMoney(po.total_amount) + (Number(po.invoice_count) > 0 ? ' (has invoice)' : '');
        o.setAttribute('data-total', po.total_amount);
        select.appendChild(o);
    });
}

function updatePoHint() {
    var select = document.getElementById('invPo');
    var opt = select.options[select.selectedIndex];
    var hint = document.getElementById('invPoHint');
    if (opt && opt.getAttribute('data-total')) hint.textContent = 'PO total: ' + procMoney(opt.getAttribute('data-total'));
    else hint.textContent = '';
}

function openInvoiceModal() {
    if (!eligiblePos.length) { procToast('error', 'No eligible purchase orders', 'Issue a purchase order and receive goods before recording an invoice.'); return; }
    document.getElementById('invNumber').value = '';
    document.getElementById('invAmount').value = '';
    document.getElementById('invNotes').value = '';
    document.getElementById('invDate').value = new Date().toISOString().slice(0, 10);
    document.getElementById('invPo').value = '';
    updatePoHint();
    procOpenModal('invoiceModal');
}

function saveInvoice() {
    var poId = document.getElementById('invPo').value;
    var number = document.getElementById('invNumber').value.trim();
    var amount = Number(document.getElementById('invAmount').value || 0);
    if (!poId) { procToast('error', 'PO required', 'Select a purchase order.'); return; }
    if (!number) { procToast('error', 'Invoice number required', ''); return; }
    if (!(amount > 0)) { procToast('error', 'Invalid amount', 'Invoice amount must be greater than zero.'); return; }
    var fd = new FormData();
    fd.append('po_id', poId);
    fd.append('invoice_number', number);
    fd.append('invoice_date', document.getElementById('invDate').value);
    fd.append('invoice_amount', amount);
    fd.append('notes', document.getElementById('invNotes').value);
    procPost('save_supplier_invoice', fd).then(function (res) {
        if (res.success) {
            var msg = res.match_status === 'matched' ? 'Three-way match passed.' : 'Placed on match hold: ' + (res.match_notes || '');
            procToast(res.match_status === 'matched' ? 'success' : 'warning', 'Invoice recorded', msg);
            procCloseModal('invoiceModal');
            loadInvoices();
        } else procToast('error', 'Cannot record invoice', res.message || '');
    });
}

function openScheduleModal(invoiceId, number) {
    schedInvoiceId = invoiceId;
    document.getElementById('schedSub').textContent = number;
    document.getElementById('schedNotes').value = '';
    document.getElementById('schedDate').value = new Date().toISOString().slice(0, 10);
    procOpenModal('scheduleModal');
}

function saveSchedule() {
    var date = document.getElementById('schedDate').value;
    if (!date) { procToast('error', 'Date required', 'Choose a scheduled payment date.'); return; }
    var fd = new FormData();
    fd.append('invoice_id', schedInvoiceId);
    fd.append('scheduled_for', date);
    fd.append('payment_method', document.getElementById('schedMethod').value);
    fd.append('notes', document.getElementById('schedNotes').value);
    procPost('schedule_supplier_payment', fd).then(function (res) {
        if (res.success) { procToast('success', 'Payment scheduled', date); procCloseModal('scheduleModal'); loadInvoices(); }
        else procToast('error', 'Cannot schedule', res.message || '');
    });
}

function openPaymentModal(invoiceId, outstanding, number, supplier) {
    payInvoiceId = invoiceId;
    payOutstanding = outstanding;
    document.getElementById('paySub').textContent = number + ' · ' + supplier;
    document.getElementById('payOutstanding').textContent = 'Outstanding balance: ' + procMoney(outstanding);
    document.getElementById('payAmount').value = outstanding;
    document.getElementById('payReference').value = '';
    document.getElementById('payNotes').value = '';
    document.getElementById('payDate').value = new Date().toISOString().slice(0, 10);
    procOpenModal('paymentModal');
}

function savePayment() {
    var amount = Number(document.getElementById('payAmount').value || 0);
    if (!(amount > 0)) { procToast('error', 'Invalid amount', 'Payment amount must be greater than zero.'); return; }
    if (amount > payOutstanding + 0.001) { procToast('error', 'Amount too high', 'Payment cannot exceed the outstanding balance of ' + procMoney(payOutstanding) + '.'); return; }
    var fd = new FormData();
    fd.append('invoice_id', payInvoiceId);
    fd.append('amount', amount);
    fd.append('payment_date', document.getElementById('payDate').value);
    fd.append('payment_method', document.getElementById('payMethod').value);
    fd.append('reference_no', document.getElementById('payReference').value);
    fd.append('notes', document.getElementById('payNotes').value);
    procPost('save_supplier_payment', fd).then(function (res) {
        if (res.success) { procToast('success', 'Payment recorded', 'Status: ' + procStatus(res.payment_status).label + ' · Outstanding ' + procMoney(res.outstanding)); procCloseModal('paymentModal'); loadInvoices(); loadPayments(); }
        else procToast('error', 'Cannot record payment', res.message || '');
    });
}

function openAdviceModal(invoiceId, number) {
    adviceInvoiceId = invoiceId;
    document.getElementById('adviceSub').textContent = number;
    document.getElementById('adviceRecipient').value = '';
    document.getElementById('adviceNotes').value = '';
    procOpenModal('adviceModal');
}

function saveAdvice() {
    var recipient = document.getElementById('adviceRecipient').value.trim();
    if (!recipient) { procToast('error', 'Recipient required', 'Enter who the advice was sent to.'); return; }
    var fd = new FormData();
    fd.append('invoice_id', adviceInvoiceId);
    fd.append('advice_recipient', recipient);
    fd.append('notes', document.getElementById('adviceNotes').value);
    procPost('record_payment_advice', fd).then(function (res) {
        if (res.success) { procToast('success', 'Payment advice recorded', recipient); procCloseModal('adviceModal'); loadInvoices(); }
        else procToast('error', 'Cannot record advice', res.message || '');
    });
}

function openReconcileModal(invoiceId, number) {
    reconInvoiceId = invoiceId;
    document.getElementById('reconSub').textContent = number;
    document.getElementById('reconReference').value = '';
    procOpenModal('reconcileModal');
}

function saveReconcile() {
    var fd = new FormData();
    fd.append('invoice_id', reconInvoiceId);
    fd.append('payment_reference', document.getElementById('reconReference').value);
    procPost('reconcile_supplier_payment', fd).then(function (res) {
        if (res.success) { procToast('success', 'Payment reconciled', ''); procCloseModal('reconcileModal'); loadInvoices(); }
        else procToast('error', 'Cannot reconcile', res.message || '');
    });
}

function openVoidModal(invoiceId, number) {
    voidInvoiceId = invoiceId;
    document.getElementById('voidSub').textContent = number;
    document.getElementById('voidReason').value = '';
    procOpenModal('voidModal');
}

function saveVoid() {
    var reason = document.getElementById('voidReason').value.trim();
    if (!reason) { procToast('error', 'Reason required', 'Explain why this invoice is being voided.'); return; }
    var fd = new FormData();
    fd.append('invoice_id', voidInvoiceId);
    fd.append('void_reason', reason);
    procPost('void_supplier_invoice', fd).then(function (res) {
        if (res.success) { procToast('success', 'Invoice voided', reason); procCloseModal('voidModal'); loadInvoices(); }
        else procToast('error', 'Cannot void invoice', res.message || '');
    });
}

function loadPayments() {
    procGet('?action=get_supplier_payments').then(function (res) {
        var body = document.getElementById('payBody');
        var rows = res.payments || [];
        if (!res.success) { body.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px;color:#EF4444;">' + procEsc(res.message || 'Unable to load') + '</td></tr>'; return; }
        if (!rows.length) { body.innerHTML = '<tr><td colspan="7"><div class="inv-empty-state"><div class="inv-empty-icon">💵</div><div class="inv-empty-title">No payments recorded yet</div></div></td></tr>'; return; }
        body.innerHTML = rows.map(function (p) {
            return '<tr><td>' + procDate(p.payment_date) + '</td><td>' + procEsc(p.invoice_number || '—') + '</td><td>' + procEsc(p.supplier_name || '—') + '</td><td>' + procMoney(p.amount) + '</td><td>' + procEsc(String(p.payment_method || '').replace(/_/g, ' ')) + '</td><td>' + procEsc(p.reference_no || '—') + '</td><td>' + procEsc(p.recorded_by_name || '—') + '</td></tr>';
        }).join('');
    });
}

document.getElementById('invPo').addEventListener('change', updatePoHint);

loadInvoices();
loadPayments();
</script>
