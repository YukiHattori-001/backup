<?php
if (!canAccess('procurement')) {
    echo '<div class="inv-card" style="margin:2rem;text-align:center;padding:40px;">
        <h2 style="color:#111827;">Access Denied</h2>
        <a href="?page=dashboard" class="inv-btn-primary" style="margin-top:1rem;text-decoration:none;">Go to Dashboard</a>
    </div>';
    return;
}
$canManageProcurement = canAccess('procurement_manage') || isAdmin();
$canReviewFinance = hasRole('finance') || isAdmin();
?>

<div class="inv-page-header">
    <div class="inv-page-header-left">
        <h2 class="inv-page-title">Procurement</h2>
        <p class="inv-page-subtitle">From budget review and supplier sourcing to receipt, invoice match, and payment</p>
    </div>
    <button class="inv-btn-primary" onclick="showNewProcurement()">+ New Requisition</button>
</div>

<div class="inv-card">
    <div class="inv-card-header"><div class="inv-card-title">Procurement Queue</div></div>
    <div style="overflow-x:auto;">
        <table class="inv-table" style="width:100%;min-width:1000px;">
            <thead>
                <tr><th>Requisition</th><th>Item / Department</th><th>Qty</th><th>Estimated Total</th><th>Supplier / PO</th><th>Stage</th><th>Receipt / Invoice</th><th>Requested By</th><th>Next Action</th></tr>
            </thead>
            <tbody id="procurementBody"><tr><td colspan="9">Loading procurement requests...</td></tr></tbody>
        </table>
    </div>
</div>

<div id="procurementModal" class="inv-modal" style="display:none;">
    <div class="inv-modal-content" style="max-width:600px;max-height:90vh;overflow:auto;">
        <h3>New Purchase Requisition</h3>
        <form id="procurementForm">
            <label>Item or service *</label><input name="item_name" required style="width:100%;">
            <label>Linked product (stock increases on receipt)</label>
            <select name="product_id" id="procProduct" style="width:100%;"><option value="">-- None --</option></select>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div><label>Quantity *</label><input name="quantity" type="number" min="1" required style="width:100%;"></div>
                <div><label>Estimated unit cost *</label><input name="estimated_unit_cost" type="number" min="0" step="0.01" required value="0" style="width:100%;"></div>
                <div><label>Cost centre *</label><input name="cost_center" required style="width:100%;"></div>
                <div><label>Budget code *</label><input name="budget_code" required style="width:100%;"></div>
                <div><label>Available budget</label><input name="budget_amount" type="number" min="0" step="0.01" value="0" style="width:100%;"></div>
                <div><label>Department / team</label><input name="department" style="width:100%;"></div>
            </div>
            <label>Notes / business need</label><textarea name="notes" style="width:100%;"></textarea>
            <div style="display:flex;gap:8px;margin-top:1rem;">
                <button type="submit" class="inv-btn-primary">Submit for Budget Review</button>
                <button type="button" onclick="closeProcurementModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="workflowModal" class="inv-modal" style="display:none;">
    <div class="inv-modal-content" style="max-width:560px;max-height:90vh;overflow:auto;">
        <h3 id="workflowTitle"></h3>
        <p id="workflowContext" class="inv-page-subtitle"></p>
        <form id="workflowForm">
            <div id="workflowFields"></div>
            <div style="display:flex;gap:8px;margin-top:1rem;">
                <button type="submit" class="inv-btn-primary" id="workflowSubmit">Save</button>
                <button type="button" onclick="closeWorkflow()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="quotesModal" class="inv-modal" style="display:none;">
    <div class="inv-modal-content" style="max-width:760px;max-height:90vh;overflow:auto;">
        <h3>Supplier Quotations</h3>
        <div style="overflow-x:auto;"><table class="inv-table" style="width:100%;min-width:760px;">
            <thead><tr><th>Supplier</th><th>Unit Cost</th><th>Lead Time</th><th>Payment Terms</th><th>Performance / Lessons</th><th>Technical Notes</th><th></th></tr></thead>
            <tbody id="quotesBody"><tr><td colspan="6">Loading quotations...</td></tr></tbody>
        </table></div>
        <button type="button" style="margin-top:1rem;" onclick="document.getElementById('quotesModal').style.display='none'">Close</button>
    </div>
</div>

<script>
var canManageProcurement = <?php echo $canManageProcurement ? 'true' : 'false'; ?>;
var canReviewFinance = <?php echo $canReviewFinance ? 'true' : 'false'; ?>;
var procurementRows = {};
var procurementSuppliers = [];
var currentWorkflow = {};

function escapeProcurementText(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function(character) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[character];
    });
}

function formatProcurementMoney(value) {
    return Number(value || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
}

function statusLabel(status) {
    var labels = {
        budget_review:'Budget review', sourcing:'Sourcing / RFQ', supplier_selected:'Supplier selected',
        po_pending:'PO finance approval', ordered:'PO issued', acknowledged:'Supplier acknowledged',
        received:'Goods received', invoice_review:'Invoice held', invoiced:'Matched / payable',
        payment_scheduled:'Payment scheduled', paid:'Paid / reconciliation', closed:'Closed',
        rejected:'Rejected', pending:'Budget review', approved:'Sourcing'
    };
    return labels[status] || status;
}

function workflowButton(id, action, label) {
    return '<button type="button" class="inv-btn-secondary" style="padding:6px 9px;margin:2px;" onclick="openWorkflow(' + Number(id) + ',\'' + action + '\')">' + label + '</button>';
}

function actionsForRequest(request) {
    var actions = '';
    if (request.status === 'budget_review' || request.status === 'pending') {
        if (canReviewFinance) {
            actions += workflowButton(request.id, 'budget_approve', 'Check budget') + workflowButton(request.id, 'budget_reject', 'Reject');
        }
    } else if (request.status === 'sourcing') {
        if (canManageProcurement) actions += workflowButton(request.id, 'add_quote', 'Add quote') + '<button type="button" class="inv-btn-secondary" style="padding:6px 9px;margin:2px;" onclick="showQuotes(' + Number(request.id) + ')">Compare quotes</button>';
    } else if (request.status === 'supplier_selected') {
        if (canManageProcurement) actions += workflowButton(request.id, 'create_po', 'Create PO');
    } else if (request.status === 'po_pending') {
        if (canReviewFinance) actions += workflowButton(request.id, 'approve_po', 'Approve PO');
    } else if (request.status === 'ordered') {
        if (canManageProcurement) actions += workflowButton(request.id, 'acknowledge_po', 'Record acknowledgement') + workflowButton(request.id, 'receive_goods', 'Receive goods');
    } else if (request.status === 'acknowledged') {
        if (canManageProcurement) actions += workflowButton(request.id, 'receive_goods', 'Receive goods');
    } else if (request.status === 'received') {
        if (canManageProcurement && Number(request.received_quantity || 0) < Number(request.quantity)) actions += workflowButton(request.id, 'receive_goods', 'Receive remaining');
        if (canReviewFinance) actions += workflowButton(request.id, 'submit_invoice', 'Enter invoice');
    } else if (request.status === 'invoice_review') {
        if (canManageProcurement && Number(request.received_quantity || 0) < Number(request.quantity)) actions += workflowButton(request.id, 'receive_goods', 'Receive remaining');
        if (canReviewFinance) actions += workflowButton(request.id, 'submit_invoice', 'Recheck invoice');
    } else if (request.status === 'invoiced') {
        if (canReviewFinance) actions += workflowButton(request.id, 'schedule_payment', 'Schedule payment');
    } else if (request.status === 'payment_scheduled') {
        if (canReviewFinance) actions += workflowButton(request.id, 'execute_payment', 'Execute payment');
    } else if (request.status === 'paid') {
        if (canReviewFinance && !request.payment_advice_sent_at) actions += workflowButton(request.id, 'send_payment_advice', 'Record advice sent');
        else if (canReviewFinance && !request.supplier_reconciled_at) actions += workflowButton(request.id, 'reconcile_payment', 'Record supplier reconciliation');
        if (canManageProcurement && request.supplier_reconciled_at) actions += workflowButton(request.id, 'close_po', 'Close PO / rate supplier');
    }
    return actions || '<span style="color:#6b7280;">No action</span>';
}

function loadProcurement() {
    fetch('?action=get_procurement').then(function(response) { return response.json(); }).then(function(rows) {
        procurementRows = {};
        var html = '';
        rows.forEach(function(request) {
            procurementRows[request.id] = request;
            var estimatedTotal = Number(request.estimated_unit_cost || 0) * Number(request.quantity || 0);
            var receipt = Number(request.received_quantity || 0) + ' / ' + Number(request.quantity || 0);
            var invoice = request.invoice_status && request.invoice_status !== 'not_submitted' ?
                '<br><small>' + escapeProcurementText(request.invoice_status) + (request.invoice_number ? ': ' + escapeProcurementText(request.invoice_number) : '') + '</small>' : '';
            var payment = request.payment_terms ? '<br><small>' + escapeProcurementText(request.payment_terms) + '</small>' : '';
            if (request.payment_scheduled_for) payment += '<br><small>Run: ' + escapeProcurementText(request.payment_scheduled_for) + '</small>';
            if (request.payment_method) payment += '<br><small>' + escapeProcurementText(request.payment_method.replace('_', ' ')) + (request.payment_reference ? ' · ' + escapeProcurementText(request.payment_reference) : '') + '</small>';
            if (request.payment_advice_sent_at) payment += '<br><small>Advice sent</small>';
            if (request.supplier_reconciled_at) payment += '<br><small>Supplier reconciled</small>';
            html += '<tr>' +
                '<td><strong>' + escapeProcurementText(request.req_number) + '</strong><br><small>' + escapeProcurementText(request.cost_center || '') + (request.budget_code ? ' · ' + escapeProcurementText(request.budget_code) : '') + '</small></td>' +
                '<td>' + escapeProcurementText(request.item_name) + '<br><small>' + escapeProcurementText(request.department || '') + '</small></td>' +
                '<td>' + Number(request.quantity) + '</td>' +
                '<td>' + formatProcurementMoney(estimatedTotal) + (request.po_total ? '<br><small>PO ' + formatProcurementMoney(request.po_total) + '</small>' : '') + '</td>' +
                '<td>' + escapeProcurementText(request.supplier_name || 'Not selected') + (request.po_number ? '<br><small>' + escapeProcurementText(request.po_number) + '</small>' : '') + (request.on_time_in_full ? '<br><small>OTIF: ' + escapeProcurementText(request.on_time_in_full) + ' · Quality ' + escapeProcurementText(request.quality_rating) + '/5</small>' : '') + '</td>' +
                '<td><span class="badge">' + escapeProcurementText(statusLabel(request.status)) + '</span></td>' +
                '<td>' + receipt + invoice + (request.grn_number ? '<br><small>GRN ' + escapeProcurementText(request.grn_number) + '</small>' : '') + payment + '</td>' +
                '<td>' + escapeProcurementText(request.requested_by_name || '-') + '</td>' +
                '<td>' + actionsForRequest(request) + '</td></tr>';
        });
        if (!rows.length) html = '<tr><td colspan="9" style="text-align:center;padding:28px;">No requisitions yet</td></tr>';
        document.getElementById('procurementBody').innerHTML = html;
    }).catch(function() {
        document.getElementById('procurementBody').innerHTML = '<tr><td colspan="9">Unable to load procurement requests.</td></tr>';
    });
}

function showNewProcurement() {
    document.getElementById('procurementModal').style.display = 'flex';
}

function closeProcurementModal() {
    document.getElementById('procurementModal').style.display = 'none';
}

function closeWorkflow() {
    document.getElementById('workflowModal').style.display = 'none';
}

function workflowField(label, name, type, value, required, attributes) {
    var safeValue = escapeProcurementText(value == null ? '' : value);
    var reqAttr = required ? ' required' : '';
    var attrs = attributes || '';
    if (type === 'textarea') return '<label>' + label + '</label><textarea name="' + name + '" style="width:100%;"' + reqAttr + attrs + '>' + safeValue + '</textarea>';
    return '<label>' + label + '</label><input name="' + name + '" type="' + type + '" value="' + safeValue + '" style="width:100%;"' + reqAttr + attrs + '>';
}

function openWorkflow(id, action) {
    var request = procurementRows[id];
    if (!request) return;
    currentWorkflow = {id:id, action:action};
    var title = '';
    var fields = '';
    var labels = {
        budget_approve:'Budget availability check', budget_reject:'Reject requisition', add_quote:'Record supplier quotation',
        create_po:'Create purchase order', approve_po:'Finance PO approval', acknowledge_po:'Record supplier acknowledgement',
        receive_goods:'Goods receipt', submit_invoice:'Supplier invoice and three-way match',
        schedule_payment:'Schedule supplier payment', execute_payment:'Execute supplier payment',
        send_payment_advice:'Record payment advice', reconcile_payment:'Supplier payment reconciliation',
        close_po:'Close PO and review supplier'
    };
    title = labels[action] || 'Procurement action';
    if (action === 'budget_approve' || action === 'budget_reject') {
        fields = workflowField('Available budget', 'budget_amount', 'number', request.budget_amount || 0, true, ' min="0" step="0.01"');
        if (action === 'budget_reject') fields += workflowField('Reason / reallocation note', 'reason', 'textarea', '', false, '');
    } else if (action === 'add_quote') {
        fields = '<label>Supplier *</label><select name="supplier_id" required style="width:100%;"><option value="">Choose supplier</option>' + procurementSuppliers.map(function(supplier) { return '<option value="' + Number(supplier.id) + '">' + escapeProcurementText(supplier.name) + '</option>'; }).join('') + '</select>' +
            workflowField('Quoted unit cost', 'unit_cost', 'number', '', true, ' min="0" step="0.01"') +
            workflowField('Lead time (days)', 'lead_time_days', 'number', '0', false, ' min="0"') +
            workflowField('Payment terms', 'payment_terms', 'text', '', false, '') +
            workflowField('Technical / delivery notes', 'technical_notes', 'textarea', '', false, '');
    } else if (action === 'create_po') {
        fields = '<p>Selected supplier: <strong>' + escapeProcurementText(request.supplier_name || '') + '</strong><br>Estimated PO total: <strong>' + formatProcurementMoney(request.po_total) + '</strong></p><p>Finance approval is required above the configured PO threshold.</p>';
    } else if (action === 'approve_po') {
        fields = '<p>Approve PO <strong>' + escapeProcurementText(request.po_number || '') + '</strong> for ' + formatProcurementMoney(request.po_total) + '?</p>';
    } else if (action === 'acknowledge_po') {
        fields = '<p>Confirm that the supplier acknowledged PO <strong>' + escapeProcurementText(request.po_number || '') + '</strong>.</p>';
    } else if (action === 'receive_goods') {
        var remaining = Number(request.quantity) - Number(request.received_quantity || 0);
        fields = workflowField('Quantity received (remaining: ' + remaining + ')', 'received_quantity', 'number', remaining, true, ' min="1" max="' + remaining + '"') +
            workflowField('Delivery note / GRN number', 'grn_number', 'text', '', false, '') +
            workflowField('Discrepancy / receipt notes', 'receipt_notes', 'textarea', '', false, '');
    } else if (action === 'submit_invoice') {
        fields = workflowField('Supplier invoice number', 'invoice_number', 'text', request.invoice_number || '', true, '') +
            workflowField('Invoice total', 'invoice_amount', 'number', request.po_total || '', true, ' min="0" step="0.01"') +
            workflowField('Invoice quantity', 'invoice_quantity', 'number', request.quantity, true, ' min="1"') +
            workflowField('Invoice notes', 'invoice_notes', 'textarea', '', false, '') +
            '<p class="inv-page-subtitle">The invoice is held unless the PO total, ordered quantity, and received quantity all match.</p>';
    } else if (action === 'schedule_payment') {
        var today = new Date().toISOString().slice(0, 10);
        fields = '<p>Matched invoice <strong>' + escapeProcurementText(request.invoice_number || '') + '</strong> for ' + formatProcurementMoney(request.invoice_amount) + '.</p>' +
            '<p>Agreed terms: <strong>' + escapeProcurementText(request.payment_terms || 'Not specified') + '</strong>' + (request.payment_due_date ? ' · Due ' + escapeProcurementText(request.payment_due_date) : '') + '</p>' +
            workflowField('Scheduled payment run date', 'payment_scheduled_for', 'date', request.payment_due_date || today, true, '');
    } else if (action === 'execute_payment') {
        fields = '<p>Scheduled for <strong>' + escapeProcurementText(request.payment_scheduled_for || '') + '</strong>. Invoice ' + escapeProcurementText(request.invoice_number || '') + ' for ' + formatProcurementMoney(request.invoice_amount) + '.</p>' +
            '<label>Payment method *</label><select name="payment_method" required style="width:100%;"><option value="bank_transfer">Bank transfer</option><option value="cheque">Cheque</option><option value="virtual_card">Virtual card</option></select>' +
            workflowField('Execution date', 'payment_date', 'date', new Date().toISOString().slice(0, 10), true, '') +
            workflowField('Bank / cheque / transaction reference', 'payment_reference', 'text', '', true, '');
    } else if (action === 'send_payment_advice') {
        fields = '<p>Record that payment advice was sent to the supplier for reference <strong>' + escapeProcurementText(request.payment_reference || '') + '</strong>.</p>' +
            workflowField('Supplier email / recipient', 'payment_advice_recipient', 'email', request.supplier_email || '', !request.supplier_email, '');
    } else if (action === 'reconcile_payment') {
        fields = '<p>Confirm the supplier has reconciled payment ' + escapeProcurementText(request.payment_reference || '') + ' against invoice ' + escapeProcurementText(request.invoice_number || '') + '. This also releases the accrual.</p>';
    } else if (action === 'close_po') {
        fields = '<label>On-time in-full delivery *</label><select name="on_time_in_full" required style="width:100%;"><option value="yes">Yes</option><option value="partial">Partially</option><option value="no">No</option></select>' +
            '<label>Quality rating *</label><select name="quality_rating" required style="width:100%;"><option value="">Select 1 to 5</option><option value="1">1 - Poor</option><option value="2">2 - Below expectations</option><option value="3">3 - Meets expectations</option><option value="4">4 - Good</option><option value="5">5 - Excellent</option></select>' +
            '<label>Responsiveness rating *</label><select name="responsiveness_rating" required style="width:100%;"><option value="">Select 1 to 5</option><option value="1">1 - Poor</option><option value="2">2 - Below expectations</option><option value="3">3 - Meets expectations</option><option value="4">4 - Good</option><option value="5">5 - Excellent</option></select>' +
            workflowField('Issue resolution notes', 'issue_resolution', 'textarea', '', false, '') +
            workflowField('Lessons learned for future RFQs', 'lessons_learned', 'textarea', '', false, '') +
            '<label style="display:flex;align-items:center;gap:8px;margin-top:12px;"><input type="checkbox" name="issues_resolved" value="1" required> All open issues are resolved</label>';
    }
    document.getElementById('workflowTitle').textContent = title;
    document.getElementById('workflowContext').textContent = request.req_number + ' · ' + request.item_name;
    document.getElementById('workflowFields').innerHTML = fields;
    document.getElementById('workflowSubmit').textContent = action === 'budget_reject' ? 'Reject requisition' : action === 'close_po' ? 'Close purchase order' : 'Confirm';
    document.getElementById('workflowModal').style.display = 'flex';
}

function showQuotes(id) {
    var request = procurementRows[id];
    fetch('?action=get_procurement_quotes&id=' + Number(id)).then(function(response) { return response.json(); }).then(function(quotes) {
        var html = '';
        quotes.forEach(function(quote) {
            var history = quote.quality_average ? 'Quality ' + quote.quality_average + '/5 · Response ' + quote.responsiveness_average + '/5' : 'No past rating';
            if (quote.lessons_learned) history += '<br><small>' + escapeProcurementText(quote.lessons_learned) + '</small>';
            html += '<tr><td>' + escapeProcurementText(quote.supplier_name || '-') + '</td><td>' + formatProcurementMoney(quote.unit_cost) + '</td><td>' + escapeProcurementText(quote.lead_time_days || '-') + '</td><td>' + escapeProcurementText(quote.payment_terms || '-') + '</td><td>' + history + '</td><td>' + escapeProcurementText(quote.technical_notes || '-') + '</td><td>' + (canManageProcurement ? '<button type="button" class="inv-btn-secondary" style="padding:6px 9px;" onclick="selectQuote(' + Number(id) + ',' + Number(quote.id) + ')">Select</button>' : '') + '</td></tr>';
        });
        document.getElementById('quotesBody').innerHTML = html || '<tr><td colspan="7" style="text-align:center;padding:20px;">No quotations recorded</td></tr>';
        document.getElementById('quotesModal').style.display = 'flex';
    });
}

function selectQuote(requestId, quoteId) {
    if (!window.confirm('Select this supplier quotation and proceed to purchase order?')) return;
    var data = new FormData();
    data.append('id', requestId);
    data.append('workflow_action', 'select_quote');
    data.append('quote_id', quoteId);
    sendWorkflow(data);
    document.getElementById('quotesModal').style.display = 'none';
}

function sendWorkflow(data) {
    fetch('?action=procurement_workflow', {method:'POST', body:data}).then(function(response) { return response.json(); }).then(function(result) {
        if (!result.success) {
            window.alert(result.message || 'Unable to save procurement update');
            return;
        }
        closeWorkflow();
        loadProcurement();
    }).catch(function() { window.alert('Unable to save procurement update'); });
}

document.getElementById('procurementForm').addEventListener('submit', function(event) {
    event.preventDefault();
    fetch('?action=save_procurement', {method:'POST', body:new FormData(this)}).then(function(response) { return response.json(); }).then(function(result) {
        if (!result.success) return window.alert(result.message || 'Unable to submit requisition');
        closeProcurementModal();
        document.getElementById('procurementForm').reset();
        loadProcurement();
    }).catch(function() { window.alert('Unable to submit requisition'); });
});

document.getElementById('workflowForm').addEventListener('submit', function(event) {
    event.preventDefault();
    var data = new FormData(this);
    data.append('id', currentWorkflow.id);
    data.append('workflow_action', currentWorkflow.action);
    sendWorkflow(data);
});

fetch('?action=get_suppliers').then(function(response) { return response.json(); }).then(function(rows) {
    procurementSuppliers = rows || [];
});
fetch('?action=get_products').then(function(response) { return response.json(); }).then(function(rows) {
    var select = document.getElementById('procProduct');
    (rows || []).forEach(function(product) {
        var option = document.createElement('option');
        option.value = product.id;
        option.textContent = product.name + ' (stock: ' + (product.stock_quantity || product.stock || 0) + ')';
        select.appendChild(option);
    });
});
loadProcurement();
</script>