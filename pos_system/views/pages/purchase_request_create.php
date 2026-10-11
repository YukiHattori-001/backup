<?php
if (!canAccess('procurement_manage') && !isAdmin()) {
    echo '<div class="inv-card" style="margin:2rem;text-align:center;padding:40px;"><h2 style="color:#111827;">Access Denied</h2><p style="color:#6b7280;">Only Inventory staff can create purchase requests.</p></div>';
    return;
}
$editId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
?>

<div class="inv-page-header">
    <div class="inv-page-header-left">
        <h2 class="inv-page-title"><?php echo $editId ? 'Edit Purchase Request' : 'Create Purchase Request'; ?></h2>
        <p class="inv-page-subtitle">Add products, quantities and estimated prices, then save as draft or submit for approval</p>
    </div>
    <a href="?page=purchase_requests" class="inv-btn-secondary" style="text-decoration:none;">← Back to requests</a>
</div>

<div class="inv-card">
    <div class="inv-card-header"><div class="inv-card-title-wrap"><div class="inv-card-title">Request Details</div></div></div>
    <div style="padding:16px;">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;" class="pr-form-grid">
            <div class="inv-form-group">
                <label class="inv-form-label">Request Number</label>
                <input type="text" id="prNumber" class="inv-form-input" value="Auto-generated" disabled>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">Request Date *</label>
                <input type="date" id="prDate" class="inv-form-input" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">Requester</label>
                <input type="text" class="inv-form-input" value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>" disabled>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">Supplier (optional)</label>
                <select id="prSupplier" class="inv-form-select"><option value="">— Choose later during sourcing —</option></select>
                <small style="color:#6b7280;">Leave blank to keep the supplier open for RFQ sourcing.</small>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">Department / Team</label>
                <input type="text" id="prDepartment" class="inv-form-input" placeholder="e.g. Kitchen, Warehouse">
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">Cost Centre *</label>
                <input type="text" id="prCostCenter" class="inv-form-input" placeholder="e.g. OPS-KITCHEN">
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">Budget Code *</label>
                <input type="text" id="prBudgetCode" class="inv-form-input" placeholder="e.g. BUD-2026-014">
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">Budget Amount</label>
                <input type="number" id="prBudgetAmount" class="inv-form-input" min="0" step="0.01" placeholder="Available budget (Finance confirms)">
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">Supplier Quotation (PDF/JPG/PNG, max 5MB)</label>
                <input type="file" id="prQuotation" class="inv-form-input" accept=".pdf,.jpg,.jpeg,.png">
            </div>
        </div>

        <h4 style="margin:18px 0 8px;">Products</h4>
        <div class="inv-table-wrap">
            <table class="inv-table" style="width:100%;min-width:820px;">
                <thead><tr><th style="min-width:230px;">Product *</th><th>Quantity *</th><th>Unit of Measure</th><th>Estimated Unit Price *</th><th>Line Total</th><th style="text-align:right;">Remove</th></tr></thead>
                <tbody id="itemsBody"></tbody>
                <tfoot><tr><th colspan="4" style="text-align:right;">Estimated Grand Total</th><th id="grandTotal" style="font-weight:800;">₱0.00</th><th></th></tr></tfoot>
            </table>
        </div>
        <button type="button" class="inv-btn-secondary" style="margin-top:10px;" onclick="addItemRow()">+ Add product row</button>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:16px;" class="pr-form-grid">
            <div class="inv-form-group">
                <label class="inv-form-label">Reason for Purchase *</label>
                <textarea id="prReason" class="inv-form-textarea" rows="3" placeholder="e.g. Low stock — rice below reorder level"></textarea>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">Remarks (optional)</label>
                <textarea id="prRemarks" class="inv-form-textarea" rows="3" placeholder="Any additional notes for the approver"></textarea>
            </div>
        </div>

        <div class="inv-modal-actions" style="justify-content:flex-end;margin-top:18px;">
            <a href="?page=purchase_requests" class="inv-btn-cancel" style="text-decoration:none;text-align:center;">Cancel</a>
            <button type="button" class="inv-btn-submit amber" onclick="submitForm('draft')">Save as Draft</button>
            <button type="button" class="inv-btn-submit green" onclick="submitForm('submit')">Submit for Approval</button>
        </div>
    </div>
</div>

<style>
@media (max-width: 900px) { .pr-form-grid { grid-template-columns: 1fr !important; } }
</style>

<script src="assets/js/procurement.js"></script>
<script>
var PR_EDIT_ID = <?php echo $editId; ?>;
var prProducts = [];

function productOptions(selected) {
    var opts = '<option value="">— Select product —</option>';
    prProducts.forEach(function (p) {
        var sel = Number(selected) === Number(p.id) ? ' selected' : '';
        opts += '<option value="' + p.id + '"' + sel + '>' + procEsc(p.name) + ' (stock: ' + procQty(p.stock_quantity) + ')</option>';
    });
    return opts;
}

function addItemRow(item) {
    item = item || {};
    var tr = document.createElement('tr');
    tr.innerHTML =
        '<td><select class="inv-form-select item-product">' + productOptions(item.product_id) + '</select></td>' +
        '<td><input type="number" class="inv-form-input item-qty" min="0.01" step="0.01" value="' + (item.quantity || '') + '" oninput="recalc()"></td>' +
        '<td><input type="text" class="inv-form-input item-uom" value="' + (item.unit_of_measure ? String(item.unit_of_measure).replace(/"/g, '&quot;') : '') + '" placeholder="sacks, pcs…"></td>' +
        '<td><input type="number" class="inv-form-input item-price" min="0" step="0.01" value="' + (item.estimated_unit_price != null ? item.estimated_unit_price : '') + '" oninput="recalc()"></td>' +
        '<td class="line-total">₱0.00</td>' +
        '<td style="text-align:right;"><button type="button" class="inv-action-btn inv-action-delete" onclick="this.closest(\'tr\').remove(); recalc();">🗑️</button></td>';
    document.getElementById('itemsBody').appendChild(tr);
    recalc();
}

function recalc() {
    var total = 0;
    document.querySelectorAll('#itemsBody tr').forEach(function (tr) {
        var qty = Number(tr.querySelector('.item-qty').value || 0);
        var price = Number(tr.querySelector('.item-price').value || 0);
        var line = qty * price;
        tr.querySelector('.line-total').textContent = procMoney(line);
        total += line;
    });
    document.getElementById('grandTotal').textContent = procMoney(total);
}

function collectItems() {
    var items = [];
    document.querySelectorAll('#itemsBody tr').forEach(function (tr) {
        var productId = tr.querySelector('.item-product').value;
        var qty = tr.querySelector('.item-qty').value;
        var price = tr.querySelector('.item-price').value;
        if (!productId && !qty && !price) return;
        items.push({
            product_id: productId,
            quantity: qty,
            unit_of_measure: tr.querySelector('.item-uom').value,
            estimated_unit_price: price
        });
    });
    return items;
}

function validateForm(isSubmit) {
    if (isSubmit && !document.getElementById('prReason').value.trim()) return 'Reason for purchase is required.';
    if (isSubmit && !document.getElementById('prCostCenter').value.trim()) return 'Cost centre is required before submitting.';
    if (isSubmit && !document.getElementById('prBudgetCode').value.trim()) return 'Budget code is required before submitting.';
    var items = collectItems();
    if (!items.length) return 'Add at least one product row.';
    for (var i = 0; i < items.length; i++) {
        if (!items[i].product_id) return 'Row ' + (i + 1) + ': choose a product.';
        if (!(Number(items[i].quantity) > 0)) return 'Row ' + (i + 1) + ': enter a positive quantity.';
        if (isSubmit && !(Number(items[i].estimated_unit_price) > 0)) return 'Row ' + (i + 1) + ': enter a positive estimated unit price.';
        if (Number(items[i].estimated_unit_price) < 0) return 'Row ' + (i + 1) + ': unit price cannot be negative.';
    }
    return null;
}

function buildFormData(actionType) {
    var fd = new FormData();
    fd.append('id', PR_EDIT_ID);
    fd.append('action_type', actionType);
    fd.append('request_date', document.getElementById('prDate').value);
    fd.append('supplier_id', document.getElementById('prSupplier').value);
    fd.append('department', document.getElementById('prDepartment').value);
    fd.append('cost_center', document.getElementById('prCostCenter').value);
    fd.append('budget_code', document.getElementById('prBudgetCode').value);
    fd.append('budget_amount', document.getElementById('prBudgetAmount').value || 0);
    fd.append('reason', document.getElementById('prReason').value);
    fd.append('remarks', document.getElementById('prRemarks').value);
    fd.append('items', JSON.stringify(collectItems()));
    var file = document.getElementById('prQuotation').files[0];
    if (file) fd.append('quotation', file);
    return fd;
}

function submitForm(actionType) {
    var error = validateForm(actionType === 'submit');
    if (error) { procToast('error', 'Please complete the form', error); return; }
    var fd = buildFormData(actionType);
    procPost('save_purchase_request', fd).then(function (res) {
        if (res.success) {
            procToast('success', actionType === 'submit' ? 'Submitted for approval' : 'Saved as draft', res.request_number || '');
            setTimeout(function () { window.location.href = '?page=purchase_requests'; }, 700);
            return;
        }
        if (res.requires_ack) {
            procConfirmWarnings(res.warnings || [], fd, function (retryFd) {
                procPost('save_purchase_request', retryFd).then(function (r2) {
                    if (r2.success) { procToast('success', 'Submitted for approval', r2.request_number || ''); setTimeout(function () { window.location.href = '?page=purchase_requests'; }, 700); }
                    else procToast('error', 'Cannot submit', r2.message || '');
                });
            });
            return;
        }
        procToast('error', 'Cannot save request', res.message || '');
    });
}

// Load products + suppliers, then optionally an existing draft.
procGet('?action=get_products').then(function (rows) {
    prProducts = rows || [];
    if (!PR_EDIT_ID) addItemRow();
    loadEdit();
});

function loadEdit() {
    if (!PR_EDIT_ID) return;
    procGet('?action=get_purchase_request&id=' + PR_EDIT_ID).then(function (res) {
        if (!res.success) { procToast('error', 'Unable to load request', res.message || ''); return; }
        var r = res.request;
        if (r.status !== 'draft' && r.status !== 'revision_requested') {
            procToast('error', 'Not editable', 'Only draft or revision-requested purchase requests can be edited.');
            setTimeout(function () { window.location.href = '?page=purchase_requests'; }, 900);
            return;
        }
        document.getElementById('prNumber').value = r.request_number;
        document.getElementById('prDate').value = procDate(r.request_date);
        document.getElementById('prDepartment').value = r.department || '';
        document.getElementById('prCostCenter').value = r.cost_center || '';
        document.getElementById('prBudgetCode').value = r.budget_code || '';
        document.getElementById('prBudgetAmount').value = r.budget_amount > 0 ? r.budget_amount : '';
        document.getElementById('prReason').value = r.reason || '';
        document.getElementById('prRemarks').value = r.remarks || '';
        document.getElementById('prSupplier').value = r.supplier_id || '';
        document.getElementById('itemsBody').innerHTML = '';
        (res.items || []).forEach(function (it) {
            addItemRow({ product_id: it.product_id, quantity: it.quantity, unit_of_measure: it.unit_of_measure, estimated_unit_price: it.estimated_unit_price });
        });
        if (!(res.items || []).length) addItemRow();
    });
}

procGet('?action=get_suppliers').then(function (rows) {
    var select = document.getElementById('prSupplier');
    (rows || []).forEach(function (s) {
        var o = document.createElement('option'); o.value = s.id; o.textContent = s.name; select.appendChild(o);
    });
});
</script>
