<?php
switch ('purchases'):
case 'purchases':

                            if (!canAccess('purchases')) {
                                echo '<div class="inv-card" style="margin:2rem;text-align:center;padding:40px;">
                                    <div style="font-size:4rem;margin-bottom:1rem;">⛔</div>
                                    <h2 style="color:#111827;">Access Denied</h2>
                                    <a href="?page=dashboard" class="inv-btn-primary" style="margin-top:1rem;text-decoration:none;">Go to Dashboard</a>
                                </div>';
                                break;
                            }
                            
                            $purchases = $pdo->query("
                                SELECT p.*, s.name as supplier_name, u.full_name as recorded_by_name,
                                    (SELECT COUNT(*) FROM purchase_items WHERE purchase_id = p.id) as item_count
                                FROM purchases p
                                LEFT JOIN suppliers s ON p.supplier_id = s.id
                                LEFT JOIN users u ON p.recorded_by = u.id
                                ORDER BY p.purchase_date DESC, p.id DESC
                                LIMIT 100
                            ")->fetchAll();
                            
                            $totalPurchases = count($purchases);
                            $totalSpent = array_sum(array_column($purchases, 'total_amount'));
                            $thisMonth = 0;
                            foreach ($purchases as $p) {
                                if (date('Y-m', strtotime($p['purchase_date'])) == date('Y-m')) $thisMonth++;
                            }
                            ?>
                            
                            <!-- Page Header -->
                            <div class="inv-page-header">
                                <div class="inv-page-header-left">
                                    <h2 class="inv-page-title">
                                        <span class="inv-page-icon">🛒</span>
                                        Purchases
                                    </h2>
                                    <p class="inv-page-subtitle">Record incoming stock from suppliers</p>
                                </div>
                                <button class="inv-btn-primary" onclick="showNewPurchase()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    Record Purchase
                                </button>
                            </div>
                            
                            <!-- Stats -->
                            <div class="inv-stats-grid">
                                <div class="inv-stat-card">
                                    <div class="inv-stat-header">
                                        <div class="inv-stat-icon" style="background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);">
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14 2 14 8 20 8"></polyline>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="inv-stat-label">Total POs</div>
                                    <div class="inv-stat-value primary"><?php echo number_format($totalPurchases); ?></div>
                                    <div class="inv-stat-footer">All-time records</div>
                                </div>
                                
                                <div class="inv-stat-card">
                                    <div class="inv-stat-header">
                                        <div class="inv-stat-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                                <line x1="3" y1="10" x2="21" y2="10"></line>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="inv-stat-label">This Month</div>
                                    <div class="inv-stat-value success"><?php echo $thisMonth; ?></div>
                                    <div class="inv-stat-footer"><?php echo date('F Y'); ?></div>
                                </div>
                                
                                <div class="inv-stat-card">
                                    <div class="inv-stat-header">
                                        <div class="inv-stat-icon" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="9" cy="21" r="1"></circle>
                                                <circle cx="20" cy="21" r="1"></circle>
                                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="inv-stat-label">Items Purchased</div>
                                    <div class="inv-stat-value warning"><?php echo number_format(array_sum(array_column($purchases, 'item_count'))); ?></div>
                                    <div class="inv-stat-footer">Line items</div>
                                </div>
                                
                                <div class="inv-stat-card">
                                    <div class="inv-stat-header">
                                        <div class="inv-stat-icon" style="background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);">
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="inv-stat-label">Total Spent</div>
                                    <div class="inv-stat-value primary">₱<?php echo number_format($totalSpent, 2); ?></div>
                                    <div class="inv-stat-footer">On inventory</div>
                                </div>
                            </div>
                            
                            <!-- Purchases Table -->
                            <div class="inv-card">
                                
                                <div class="inv-card-header">
                                    <div class="inv-card-title-wrap">
                                        <div class="inv-card-title">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14 2 14 8 20 8"></polyline>
                                            </svg>
                                            Purchase Orders
                                        </div>
                                        <div class="inv-card-subtitle">
                                            <strong><?php echo count($purchases); ?></strong> records
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="inv-filter-bar">
                                    <div class="inv-search-wrap">
                                        <span class="inv-search-icon">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="11" cy="11" r="8"></circle>
                                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                            </svg>
                                        </span>
                                        <input type="text" id="purchaseSearch" placeholder="Search by PO or supplier..." onkeyup="filterPurchases()" class="inv-search-input">
                                    </div>
                                    <select id="purchaseStatusFilter" onchange="filterPurchases()" class="inv-filter-select">
                                        <option value="all">All Payment Status</option>
                                        <option value="unpaid">Unpaid</option>
                                        <option value="partial">Partial</option>
                                        <option value="paid">Paid</option>
                                    </select>
                                </div>
                                
                                <?php if (!empty($purchases)): ?>
                                <div class="inv-table-wrap">
                                    <table class="inv-table">
                                        <thead>
                                            <tr>
                                                <th>PO Number</th>
                                                <th>Date</th>
                                                <th>Supplier</th>
                                                <th>Items</th>
                                                <th>Total</th>
                                                <th>Payment</th>
                                                <th>Recorded By</th>
                                                <th class="no-print" style="text-align:right;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="purchaseTableBody">
                                            <?php foreach ($purchases as $p): ?>
                                            <tr data-search="<?php echo strtolower(htmlspecialchars($p['po_number'] . ' ' . ($p['supplier_name'] ?? ''))); ?>"
                                                data-status="<?php echo $p['payment_status']; ?>">
                                                <td>
                                                    <div class="inv-product-cell">
                                                        <div class="inv-product-image" style="background:linear-gradient(135deg,#EEF2FF,#E0E7FF);color:#4F46E5;font-weight:800;font-size:14px;">
                                                            🧾
                                                        </div>
                                                        <div class="inv-product-info">
                                                            <div class="inv-product-name" style="font-size:13px;"><?php echo htmlspecialchars($p['po_number']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="inv-money" style="font-size:12.5px;font-weight:600;color:#6B7280;"><?php echo date('M d, Y', strtotime($p['purchase_date'])); ?></span></td>
                                                <td><?php echo htmlspecialchars($p['supplier_name'] ?? '—'); ?></td>
                                                <td><span class="inv-stock-number"><?php echo $p['item_count']; ?></span></td>
                                                <td><span class="inv-money">₱<?php echo number_format($p['total_amount'], 2); ?></span></td>
                                                <td>
                                                    <?php 
                                                    $statusClasses = ['unpaid' => 'out', 'partial' => 'low', 'paid' => 'ok'];
                                                    $sc = $statusClasses[$p['payment_status']] ?? 'ok';
                                                    ?>
                                                    <span class="inv-stock-badge <?php echo $sc; ?>"><?php echo ucfirst($p['payment_status']); ?></span>
                                                </td>
                                                <td><span style="font-size:12.5px;color:#6B7280;"><?php echo htmlspecialchars($p['recorded_by_name'] ?? '—'); ?></span></td>
                                                <td class="no-print" style="text-align:right;">
                                                    <div class="inv-actions">
                                                        <button class="inv-action-btn inv-action-view" onclick="viewPurchase(<?php echo $p['id']; ?>)" title="View Details">👁️</button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="inv-empty-state">
                                    <div class="inv-empty-icon">🛒</div>
                                    <div class="inv-empty-title">No purchases yet</div>
                                    <div class="inv-empty-text">Click "Record Purchase" to log incoming stock from a supplier</div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- ===== NEW PURCHASE MODAL ===== -->
                            <div class="modal" id="newPurchaseModal">
                                <div class="modal-content inv-modal" style="max-width:800px;">
                                    <div class="inv-modal-header">
                                        <div class="inv-modal-header-icon green">
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="9" cy="21" r="1"></circle>
                                                <circle cx="20" cy="21" r="1"></circle>
                                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                            </svg>
                                        </div>
                                        <div class="inv-modal-header-text">
                                            <h2>Record Purchase</h2>
                                            <p>Log incoming stock from a supplier</p>
                                        </div>
                                        <button class="inv-modal-close" onclick="closeModal('newPurchaseModal')">&times;</button>
                                    </div>
                                    
                                    <form onsubmit="savePurchase(event)">
                                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                            <div class="inv-form-group">
                                                <label class="inv-form-label">Supplier</label>
                                                <select id="purchaseSupplier" class="inv-form-select">
                                                    <option value="">No Supplier</option>
                                                </select>
                                            </div>
                                            <div class="inv-form-group">
                                                <label class="inv-form-label">Purchase Date *</label>
                                                <input type="date" id="purchaseDate" value="<?php echo date('Y-m-d'); ?>" required class="inv-form-input">
                                            </div>
                                        </div>
                                        
                                        <div class="inv-form-group">
                                            <label class="inv-form-label">Payment Status</label>
                                            <select id="purchasePaymentStatus" class="inv-form-select">
                                                <option value="unpaid">Unpaid</option>
                                                <option value="partial">Partial</option>
                                                <option value="paid">Paid</option>
                                            </select>
                                        </div>
                                        
                                        <div class="inv-form-group">
                                            <label class="inv-form-label">Items to Purchase</label>
                                            <div id="purchaseItemsList" style="border:1px solid #E5E7EB;border-radius:12px;padding:12px;min-height:80px;background:#F9FAFB;margin-bottom:12px;">
                                                <div style="text-align:center;color:#9CA3AF;font-size:13px;padding:20px;">No items added yet</div>
                                            </div>
                                            <button type="button" onclick="addPurchaseItem()" class="inv-btn-secondary" style="width:100%;justify-content:center;">
                                                ➕ Add Item
                                            </button>
                                        </div>
                                        
                                        <div class="inv-form-group">
                                            <label class="inv-form-label">Notes</label>
                                            <textarea id="purchaseNotes" rows="2" placeholder="Optional notes..." class="inv-form-textarea"></textarea>
                                        </div>
                                        
                                        <div style="padding:16px 20px;background:linear-gradient(135deg,#EEF2FF,#E0E7FF);border:1px solid #C7D2FE;border-radius:12px;display:flex;justify-content:space-between;align-items:center;margin-top:16px;">
                                            <span style="font-size:13px;font-weight:700;color:#3730A3;text-transform:uppercase;letter-spacing:0.05em;">Total</span>
                                            <span id="purchaseGrandTotal" style="font-size:24px;font-weight:800;color:#4F46E5;">₱0.00</span>
                                        </div>
                                        
                                        <div class="inv-modal-actions">
                                            <button type="button" class="inv-btn-cancel" onclick="closeModal('newPurchaseModal')">Cancel</button>
                                            <button type="submit" class="inv-btn-submit green">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                                Save Purchase
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- ===== VIEW PURCHASE MODAL ===== -->
                            <div class="modal" id="viewPurchaseModal">
                                <div class="modal-content inv-modal" style="max-width:700px;">
                                    <div class="inv-modal-header">
                                        <div class="inv-modal-header-icon purple">
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </div>
                                        <div class="inv-modal-header-text">
                                            <h2 id="viewPurchaseTitle">Purchase Details</h2>
                                            <p>Full purchase order information</p>
                                        </div>
                                        <button class="inv-modal-close" onclick="closeModal('viewPurchaseModal')">&times;</button>
                                    </div>
                                    
                                    <div id="viewPurchaseContent"></div>
                                </div>
                            </div>
                            
                            <script>
                            var purchaseItems = [];
                            var allProducts = [];
                            
                            // Load products & suppliers for purchase form
                            fetch('?action=get_products').then(function(r){ return r.json(); }).then(function(d){ allProducts = d; });
                            fetch('?action=get_suppliers').then(function(r){ return r.json(); }).then(function(d){
                                var sel = document.getElementById('purchaseSupplier');
                                d.forEach(function(s){
                                    var opt = document.createElement('option');
                                    opt.value = s.id;
                                    opt.textContent = s.name;
                                    sel.appendChild(opt);
                                });
                            });
                            
                            function filterPurchases() {
                                var search = document.getElementById('purchaseSearch').value.toLowerCase();
                                var filter = document.getElementById('purchaseStatusFilter').value;
                                var rows = document.querySelectorAll('#purchaseTableBody tr');
                                rows.forEach(function(row) {
                                    var text = row.getAttribute('data-search') || '';
                                    var status = row.getAttribute('data-status') || '';
                                    var show = (search === '' || text.indexOf(search) !== -1) && (filter === 'all' || filter === status);
                                    row.style.display = show ? '' : 'none';
                                });
                            }
                            
                            function showNewPurchase() {
                                purchaseItems = [];
                                renderPurchaseItems();
                                document.getElementById('purchaseDate').value = '<?php echo date('Y-m-d'); ?>';
                                document.getElementById('purchaseNotes').value = '';
                                document.getElementById('purchasePaymentStatus').value = 'unpaid';
                                document.getElementById('purchaseSupplier').value = '';
                                document.getElementById('newPurchaseModal').classList.add('show');
                            }
                            
                            function addPurchaseItem() {
                                // Show product picker as prompt for simplicity
                                var options = allProducts.map(function(p, i){ return (i+1) + '. ' + p.name + ' (Stock: ' + p.stock_quantity + ')'; }).join('\n');
                                var choice = prompt('Select product number:\n\n' + options);
                                if (!choice) return;
                                
                                var idx = parseInt(choice) - 1;
                                if (isNaN(idx) || !allProducts[idx]) { alert('Invalid selection'); return; }
                                
                                var qty = parseInt(prompt('Quantity:'));
                                if (!qty || qty <= 0) return;
                                
                                var cost = parseFloat(prompt('Unit cost (₱):') || '0');
                                if (cost < 0) cost = 0;
                                
                                purchaseItems.push({
                                    product_id: allProducts[idx].id,
                                    product_name: allProducts[idx].name,
                                    quantity: qty,
                                    unit_cost: cost
                                });
                                renderPurchaseItems();
                            }
                            
                            function renderPurchaseItems() {
                                var container = document.getElementById('purchaseItemsList');
                                if (purchaseItems.length === 0) {
                                    container.innerHTML = '<div style="text-align:center;color:#9CA3AF;font-size:13px;padding:20px;">No items added yet</div>';
                                    document.getElementById('purchaseGrandTotal').textContent = '₱0.00';
                                    return;
                                }
                                
                                var html = '';
                                var total = 0;
                                purchaseItems.forEach(function(item, i) {
                                    var lineTotal = item.quantity * item.unit_cost;
                                    total += lineTotal;
                                    html += '<div style="display:flex;justify-content:space-between;align-items:center;padding:10px 12px;background:white;border-radius:8px;margin-bottom:6px;border:1px solid #E5E7EB;">';
                                    html += '<div><strong style="font-size:13px;">' + item.product_name + '</strong>';
                                    html += '<div style="font-size:11.5px;color:#9CA3AF;">' + item.quantity + ' × ₱' + item.unit_cost.toFixed(2) + '</div></div>';
                                    html += '<div style="display:flex;align-items:center;gap:10px;">';
                                    html += '<strong style="font-size:14px;color:#4F46E5;">₱' + lineTotal.toFixed(2) + '</strong>';
                                    html += '<button type="button" onclick="removePurchaseItem(' + i + ')" style="background:#FEF2F2;color:#DC2626;border:none;width:26px;height:26px;border-radius:6px;cursor:pointer;font-weight:700;">✕</button>';
                                    html += '</div></div>';
                                });
                                container.innerHTML = html;
                                document.getElementById('purchaseGrandTotal').textContent = '₱' + total.toFixed(2);
                            }
                            
                            function removePurchaseItem(i) {
                                purchaseItems.splice(i, 1);
                                renderPurchaseItems();
                            }
                            
                            function savePurchase(e) {
                                e.preventDefault();
                                if (purchaseItems.length === 0) { alert('Add at least one item'); return; }
                                
                                var data = new FormData();
                                data.append('supplier_id', document.getElementById('purchaseSupplier').value);
                                data.append('purchase_date', document.getElementById('purchaseDate').value);
                                data.append('payment_status', document.getElementById('purchasePaymentStatus').value);
                                data.append('notes', document.getElementById('purchaseNotes').value);
                                data.append('items', JSON.stringify(purchaseItems));
                                
                                fetch('?action=save_purchase', { method: 'POST', body: data })
                                .then(function(res) { return res.json(); })
                                .then(function(result) {
                                    if (result.success) {
                                        if (window.showToast) showToast('success', 'Purchase Recorded', 'PO: ' + result.po_number, 4000);
                                        closeModal('newPurchaseModal');
                                        setTimeout(function(){ location.reload(); }, 1000);
                                    } else {
                                        alert('❌ ' + result.message);
                                    }
                                });
                            }
                            
                            function viewPurchase(id) {
                                document.getElementById('viewPurchaseContent').innerHTML = '<div style="text-align:center;padding:2rem;"><div style="display:inline-block;width:36px;height:36px;border:3px solid #E5E7EB;border-top-color:#6366F1;border-radius:50%;animation:spin 0.8s linear infinite;"></div></div>';
                                document.getElementById('viewPurchaseModal').classList.add('show');
                                
                                fetch('?action=get_purchase&id=' + id)
                                .then(function(res) { return res.json(); })
                                .then(function(p) {
                                    if (!p.id) { alert('Purchase not found'); return; }
                                    
                                    document.getElementById('viewPurchaseTitle').textContent = 'Purchase ' + p.po_number;
                                    
                                    var html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">';
                                    html += '<div style="padding:12px 14px;background:#F9FAFB;border-radius:10px;"><div style="font-size:10.5px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">PO Number</div><div style="font-weight:700;color:#111827;">' + p.po_number + '</div></div>';
                                    html += '<div style="padding:12px 14px;background:#F9FAFB;border-radius:10px;"><div style="font-size:10.5px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">Date</div><div style="font-weight:700;color:#111827;">' + new Date(p.purchase_date).toLocaleDateString() + '</div></div>';
                                    html += '<div style="padding:12px 14px;background:#F9FAFB;border-radius:10px;"><div style="font-size:10.5px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">Supplier</div><div style="font-weight:700;color:#111827;">' + (p.supplier_name || 'N/A') + '</div></div>';
                                    html += '<div style="padding:12px 14px;background:#F9FAFB;border-radius:10px;"><div style="font-size:10.5px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">Status</div><div style="font-weight:700;color:#4F46E5;">' + p.payment_status.charAt(0).toUpperCase() + p.payment_status.slice(1) + '</div></div>';
                                    html += '</div>';
                                    
                                    html += '<h3 style="font-size:13px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:10px;">Items</h3>';
                                    html += '<div class="inv-table-wrap" style="margin-bottom:16px;"><table class="inv-table"><thead><tr><th>Product</th><th>Qty</th><th>Unit Cost</th><th style="text-align:right;">Subtotal</th></tr></thead><tbody>';
                                    p.items.forEach(function(item) {
                                        html += '<tr>';
                                        html += '<td><strong>' + item.product_name + '</strong></td>';
                                        html += '<td>' + item.quantity + ' ' + (item.unit || 'pc') + '</td>';
                                        html += '<td>₱' + parseFloat(item.unit_cost).toFixed(2) + '</td>';
                                        html += '<td style="text-align:right;"><strong>₱' + parseFloat(item.total_cost).toFixed(2) + '</strong></td>';
                                        html += '</tr>';
                                    });
                                    html += '</tbody></table></div>';
                                    
                                    html += '<div style="padding:16px 20px;background:linear-gradient(135deg,#EEF2FF,#E0E7FF);border:1px solid #C7D2FE;border-radius:12px;display:flex;justify-content:space-between;align-items:center;">';
                                    html += '<span style="font-size:13px;font-weight:700;color:#3730A3;text-transform:uppercase;letter-spacing:0.05em;">Grand Total</span>';
                                    html += '<span style="font-size:24px;font-weight:800;color:#4F46E5;">₱' + parseFloat(p.total_amount).toFixed(2) + '</span>';
                                    html += '</div>';
                                    
                                    if (p.notes) {
                                        html += '<div style="margin-top:16px;padding:12px 14px;background:#FFFBEB;border:1px solid #FEF3C7;border-radius:10px;"><div style="font-size:10.5px;font-weight:700;color:#92400E;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">Notes</div><div style="font-size:13px;color:#78350F;">' + p.notes + '</div></div>';
                                    }
                                    
                                    document.getElementById('viewPurchaseContent').innerHTML = html;
                                });
                            }
                            </script>
                            
                            <?php
                            

                        
                        // ============================================
                        // INVENTORY REPORTS PAGE
                        // ============================================
break;
endswitch;
