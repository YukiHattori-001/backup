<?php
$canSource = canAccess('procurement_manage') || canAccess('procurement_po_manage') || isAdmin();
$canConfirmTerms = canAccess('procurement_finance') || isAdmin();
if (!$canSource && !$canConfirmTerms) {
    echo '<div class="inv-card" style="margin:2rem;text-align:center;padding:40px;"><h2 style="color:#111827;">Access Denied</h2><p style="color:#6b7280;">Only Inventory and Finance can manage sourcing.</p></div>';
    return;
}
$requestId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$canManage = canAccess('procurement_manage') || canAccess('procurement_po_manage') || isAdmin();
?>

<div class="inv-page-header">
    <div class="inv-page-header-left">
        <h2 class="inv-page-title">Sourcing / RFQ</h2>
        <p class="inv-page-subtitle">Request quotations from suppliers, evaluate them and select an award</p>
    </div>
    <a href="?page=purchase_requests" class="inv-btn-secondary" style="text-decoration:none;">← Back to requests</a>
</div>

<div id="sourceCard">
    <div class="inv-card"><div style="padding:40px;text-align:center;color:#6b7280;">Pick a purchase request from the Purchase Requests page to start sourcing.</div></div>
</div>

<!-- RFQ modal -->
<div class="modal" id="rfqModal">
    <div class="modal-content inv-modal" style="max-width:480px;">
        <div class="inv-modal-header">
            <div class="inv-modal-header-icon blue">📣</div>
            <div class="inv-modal-header-text"><h2>Issue an RFQ</h2><p id="rfqSub"></p></div>
            <button class="inv-modal-close" onclick="procCloseModal('rfqModal')">&times;</button>
        </div>
        <p style="color:#6b7280;margin-bottom:12px;">The request moves to <strong>Sourcing</strong>. Suppliers can then submit quotations that you record below.</p>
        <div class="inv-form-group"><label class="inv-form-label">RFQ deadline (optional)</label><input type="date" id="rfqDeadline" class="inv-form-input"></div>
        <div class="inv-modal-actions">
            <button type="button" class="inv-btn-cancel" onclick="procCloseModal('rfqModal')">Cancel</button>
            <button type="button" class="inv-btn-submit" onclick="confirmRfq()">Issue RFQ</button>
        </div>
    </div>
</div>

<!-- Quote modal -->
<div class="modal" id="quoteModal">
    <div class="modal-content inv-modal" style="max-width:540px;">
        <div class="inv-modal-header">
            <div class="inv-modal-header-icon green">📩</div>
            <div class="inv-modal-header-text"><h2>Record Supplier Quotation</h2><p id="quoteSub"></p></div>
            <button class="inv-modal-close" onclick="procCloseModal('quoteModal')">&times;</button>
        </div>
        <div class="inv-form-group"><label class="inv-form-label">Supplier *</label><select id="quoteSupplier" class="inv-form-select"></select></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="inv-form-group"><label class="inv-form-label">Quoted unit price (₱) *</label><input type="number" id="quoteUnitPrice" class="inv-form-input" min="0.01" step="0.01"><small id="quoteHint" style="color:#6b7280;"></small></div>
            <div class="inv-form-group"><label class="inv-form-label">Lead time (days)</label><input type="number" id="quoteLead" class="inv-form-input" min="0" step="1"></div>
        </div>
        <div class="inv-form-group"><label class="inv-form-label">Payment terms</label><input type="text" id="quoteTerms" class="inv-form-input" placeholder="e.g. 30 days net, COD…"></div>
        <div class="inv-form-group"><label class="inv-form-label">Notes</label><textarea id="quoteNotes" class="inv-form-textarea" rows="2"></textarea></div>
        <div class="inv-modal-actions">
            <button type="button" class="inv-btn-cancel" onclick="procCloseModal('quoteModal')">Cancel</button>
            <button type="button" class="inv-btn-submit green" onclick="saveQuote()">Save quotation</button>
        </div>
    </div>
</div>

<!-- Select quote modal -->
<div class="modal" id="selectModal">
    <div class="modal-content inv-modal" style="max-width:540px;">
        <div class="inv-modal-header">
            <div class="inv-modal-header-icon purple">🏆</div>
            <div class="inv-modal-header-text"><h2>Select Supplier</h2><p id="selectSub"></p></div>
            <button class="inv-modal-close" onclick="procCloseModal('selectModal')">&times;</button>
        </div>
        <div id="selectBody"></div>
        <div class="inv-form-group"><label class="inv-form-label">Negotiation notes</label><textarea id="selectNotes" class="inv-form-textarea" rows="2" placeholder="Price agreed, discounts, delivery commitments…"></textarea></div>
        <?php if (!$canConfirmTerms): ?><p style="color:#6b7280;font-size:13px;">⚠️ A Finance user should confirm the quoted payment terms when the award is submitted.</p><?php endif; ?>
        <div class="inv-modal-actions">
            <button type="button" class="inv-btn-cancel" onclick="procCloseModal('selectModal')">Cancel</button>
            <button type="button" class="inv-btn-submit green" onclick="confirmSelect()">Award supplier</button>
        </div>
    </div>
</div>

<script src="assets/js/procurement.js"></script>
<script>
var SRC_ID = <?php echo $requestId; ?>;
var SRC_CAN_MANAGE = <?php echo $canManage ? 'true' : 'false'; ?>;
var srcRequest = null;
var srcQuotes = [];
var srcSuppliers = [];
var selectedQuoteId = 0;

function loadRequest() {
    if (!SRC_ID) {
        document.getElementById('sourceCard').innerHTML = '<div class="inv-card" style="padding:40px;text-align:center;color:#6b7280;">Pick a purchase request from the Purchase Requests page to start sourcing.</div>';
        return;
    }
    procGet('?action=get_purchase_request&id=' + SRC_ID).then(function (res) {
        if (!res.success || !res.request) {
            document.getElementById('sourceCard').innerHTML = '<div class="inv-card" style="padding:40px;text-align:center;color:#EF4444;">' + procEsc(res.message || 'Unable to load request') + '</div>';
            return;
        }
        srcRequest = res.request;
        renderRequest(res);
        loadQuotes();
    });
}

function renderRequest(res) {
    var r = srcRequest;
    var ht = '<div class="inv-card" style="margin-bottom:1.25rem;">';
    ht += '<div class="inv-card-header"><div class="inv-card-title-wrap"><div class="inv-card-title">' + procEsc(r.request_number) + '</div></div>';
    var actions = '';
    if (SRC_CAN_MANAGE && (r.status === 'approved' || r.status === 'sourcing')) {
        actions += '<button class="inv-btn-secondary" style="padding:6px 12px;" onclick="openRfqModal()">' + (r.rfq_number ? 'Re-issue RFQ' : 'Issue RFQ') + '</button>';
    }
    if (SRC_CAN_MANAGE && r.status === 'sourcing') {
        actions += ' <button class="inv-btn-primary" style="padding:6px 12px;" onclick="openQuoteModal()">+ Record quotation</button>';
    }
    if (r.rfq_number) actions += ' <span class="badge badge-info">' + procEsc(r.rfq_number) + '</span>';
    ht += '<div>' + actions + '</div></div>';
    ht += '<div style="padding:16px;"><div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:10px;">';
    ht += '<div><strong>Requester:</strong> ' + procEsc(r.requester_name || '—') + '</div>';
    ht += '<div><strong>Status:</strong> ' + procBadge(r.status) + '</div>';
    ht += '<div><strong>Estimated total:</strong> ' + procMoney(r.estimated_total) + '</div>';
    ht += '<div><strong>Negotiated total:</strong> ' + (r.negotiated_total != null ? procMoney(r.negotiated_total) : '—') + '</div>';
    ht += '<div><strong>Supplier:</strong> ' + procEsc(r.supplier_name || '— (to be selected)') + '</div>';
    ht += '<div><strong>RFQ deadline:</strong> ' + procDate(r.rfq_deadline) + '</div>';
    if (r.payment_terms) ht += '<div><strong>Payment terms:</strong> ' + procEsc(r.payment_terms) + '</div>';
    ht += '</div>';
    ht += '<p><strong>Reason:</strong> ' + procEsc(r.reason || '—') + '</p>';
    ht += '<h4 style="margin:12px 0 8px;">Requested Products</h4><div class="inv-table-wrap"><table class="inv-table" style="width:100%;"><thead><tr><th>Product</th><th>Qty</th><th>UoM</th><th>Est. Unit Price</th><th>Line Total</th></tr></thead><tbody>';
    (res.items || []).forEach(function (it) { ht += '<tr><td>' + procEsc(it.product_name || '—') + '</td><td>' + procQty(it.quantity) + '</td><td>' + procEsc(it.unit_of_measure || '—') + '</td><td>' + procMoney(it.estimated_unit_price) + '</td><td>' + procMoney(it.line_total) + '</td></tr>'; });
    ht += '</tbody></table></div>';
    ht += '</div></div>';
    document.getElementById('sourceCard').innerHTML = ht;
}

function loadQuotes() {
    procGet('?action=get_rfq_quotes&id=' + SRC_ID).then(function (res) {
        srcQuotes = res.quotes || [];
        renderQuotes();
    });
}

function renderQuotes() {
    var r = srcRequest;
    var ht = '<div class="inv-card">';
    ht += '<div class="inv-card-header"><div class="inv-card-title-wrap"><div class="inv-card-title">Supplier Quotations</div></div></div>';
    ht += '<div class="inv-table-wrap"><table class="inv-table" style="width:100%;min-width:900px;"><thead><tr><th>Supplier</th><th>Unit Price</th><th>Total</th><th>Lead</th><th>Terms</th><th>Past Performance</th><th>Notes</th><th style="text-align:right;">Action</th></tr></thead><tbody>';
    if (!srcQuotes.length) {
        ht += '<tr><td colspan="8" style="text-align:center;padding:20px;color:#6b7280;">No quotations yet. Issue an RFQ then record the responses here.</td></tr>';
    } else {
        srcQuotes.forEach(function (q) {
            var chosen = Number(q.is_selected) === 1;
            var perf = [('OTIF ' + (q.otif_average != null ? q.otif_average : '—')), ('Quality ' + (q.quality_average != null ? q.quality_average : '—')), ('Resp ' + (q.responsiveness_average != null ? q.responsiveness_average : '—'))].join(' · ');
            var action = chosen
                ? '<span class="badge badge-success">Awarded</span>'
                : (r.status === 'sourcing' && SRC_CAN_MANAGE ? '<button class="inv-btn-primary" style="padding:5px 10px;" onclick="openSelectModal(' + q.id + ', \'' + procEsc(q.supplier_name || '') + '\', ' + Number(q.total_price) + ', \'' + procEsc(q.payment_terms || '') + '\')">Select</button>' : '—');
            ht += '<tr' + (chosen ? ' style="background:rgba(34,197,94,0.06);"' : '') + '><td><strong>' + procEsc(q.supplier_name || '—') + '</strong>' + (chosen ? ' <span class="badge badge-success">✓</span>' : '') + '</td>' +
                '<td>' + procMoney(q.unit_price) + '</td>' +
                '<td><strong>' + procMoney(q.total_price) + '</strong></td>' +
                '<td>' + (q.lead_time_days != null ? Number(q.lead_time_days) + ' d' : '—') + '</td>' +
                '<td>' + procEsc(q.payment_terms || '—') + '</td>' +
                '<td><small>' + procEsc(perf) + '</small>' + (q.lessons_learned ? '<br><small title="' + procEsc(q.lessons_learned) + '">' + procEsc(String(q.lessons_learned).slice(0, 40)) + '</small>' : '') + '</td>' +
                '<td>' + procEsc(q.notes || '—') + '</td>' +
                '<td style="text-align:right;">' + action + '</td></tr>';
        });
    }
    ht += '</tbody></table></div>';
    if (r.status === 'supplier_selected' && SRC_CAN_MANAGE) {
        ht += '<div style="padding:14px;border-top:1px solid #E5E7EB;"><a href="?page=purchase_orders" class="inv-btn-primary" style="text-decoration:none;">Generate the Purchase Order →</a></div>';
    }
    ht += '</div>';
    var holder = document.getElementById('quoteCard');
    if (!holder) { return; }
    holder.innerHTML = ht;
}

function openRfqModal() {
    document.getElementById('rfqSub').textContent = srcRequest.request_number;
    document.getElementById('rfqDeadline').value = '';
    procOpenModal('rfqModal');
}

function confirmRfq() {
    var fd = new FormData();
    fd.append('id', SRC_ID);
    fd.append('rfq_deadline', document.getElementById('rfqDeadline').value);
    procPost('send_rfq', fd).then(function (res) {
        if (res.success) { procToast('success', 'RFQ issued', res.rfq_number || ''); procCloseModal('rfqModal'); loadRequest(); }
        else procToast('error', 'Cannot issue RFQ', res.message || '');
    });
}

function openQuoteModal() {
    document.getElementById('quoteSub').textContent = srcRequest.request_number;
    document.getElementById('quoteUnitPrice').value = '';
    document.getElementById('quoteLead').value = '';
    document.getElementById('quoteTerms').value = '';
    document.getElementById('quoteNotes').value = '';
    var sel = document.getElementById('quoteSupplier'); sel.value = '';
    document.getElementById('quoteHint').textContent = 'Estimated total ' + procMoney(srcRequest.estimated_total) + ' — the unit price applies to the full requested quantity.';
    procOpenModal('quoteModal');
}

function saveQuote() {
    var supplier = document.getElementById('quoteSupplier').value;
    var price = Number(document.getElementById('quoteUnitPrice').value || 0);
    if (!supplier) { procToast('error', 'Supplier required', 'Choose a supplier.'); return; }
    if (!(price > 0)) { procToast('error', 'Invalid unit price', 'Enter a positive quoted unit price.'); return; }
    var fd = new FormData();
    fd.append('id', SRC_ID);
    fd.append('supplier_id', supplier);
    fd.append('unit_price', price);
    fd.append('lead_time_days', document.getElementById('quoteLead').value || 0);
    fd.append('payment_terms', document.getElementById('quoteTerms').value);
    fd.append('notes', document.getElementById('quoteNotes').value);
    procPost('save_rfq_quote', fd).then(function (res) {
        if (res.success) { procToast('success', 'Quotation recorded', ''); procCloseModal('quoteModal'); loadRequest(); }
        else procToast('error', 'Cannot record quotation', res.message || '');
    });
}

function openSelectModal(quoteId, supplier, total, terms) {
    selectedQuoteId = quoteId;
    document.getElementById('selectSub').textContent = supplier + ' · ' + procMoney(total) + (terms ? ' · ' + terms : '');
    document.getElementById('selectNotes').value = '';
    document.getElementById('selectBody').innerHTML = '<p>This awards <strong>' + procEsc(supplier) + '</strong> the total of <strong>' + procMoney(total) + '</strong>' + (terms ? ' (payment terms: ' + procEsc(terms) + ')' : '') + ' and moves the request to <strong>Supplier Selected</strong>, ready for the purchase order.</p>';
    procOpenModal('selectModal');
}

function confirmSelect() {
    var fd = new FormData();
    fd.append('id', SRC_ID);
    fd.append('quote_id', selectedQuoteId);
    fd.append('negotiation_notes', document.getElementById('selectNotes').value);
    procPost('select_rfq_quote', fd).then(function (res) {
        if (res.success) { procToast('success', 'Supplier awarded', 'Create the purchase order from Purchase Orders.'); procCloseModal('selectModal'); loadRequest(); }
        else procToast('error', 'Cannot select supplier', res.message || '');
    });
}

procGet('?action=get_suppliers').then(function (rows) {
    srcSuppliers = rows || [];
    var sel = document.getElementById('quoteSupplier');
    (srcSuppliers || []).forEach(function (s) { var o = document.createElement('option'); o.value = s.id; o.textContent = s.name; sel.appendChild(o); });
});

// Inject the quotations card once the request card is rendered
var quoteHolder = document.createElement('div');
quoteHolder.id = 'quoteCard';
document.getElementById('sourceCard').after(quoteHolder);

loadRequest();
</script>