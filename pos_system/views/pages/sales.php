<?php
switch ('sales'):
case 'sales':

                $sales = $saleManager->getSales();
                $customers = $customerManager->getAllCustomers();
                ?>
                
                <!-- ============================================ -->
                <!-- SALES HISTORY PAGE - MODERN INTERFACE -->
                <!-- ============================================ -->
                
                <!-- Page Header -->
                <div class="sales-page-header">
                    <div class="sales-page-header-left">
                        <h2 class="sales-page-title">
                            <span class="sales-page-icon">📋</span>
                            Sales History
                        </h2>
                        <p class="sales-page-subtitle">Track all transactions and revenue</p>
                    </div>
                </div>
                
                <!-- Filters Card -->
                <div class="sales-filter-card">
                    <div class="sales-filter-row">
                        <div class="sales-filter-group">
                            <label class="sales-filter-label">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                Date Range
                            </label>
                            <div class="sales-date-group">
                                <input type="date" id="salesStartDate" class="sales-date-input">
                                <span class="sales-date-separator">to</span>
                                <input type="date" id="salesEndDate" class="sales-date-input">
                            </div>
                        </div>
                        
                        <div class="sales-filter-group">
                            <label class="sales-filter-label">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                Customer
                            </label>
                            <select id="salesCustomerFilter" onchange="filterSalesByCustomer()" class="sales-customer-select">
                                <option value="">All Customers</option>
                                <?php foreach ($customers as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="sales-filter-actions">
                            <button class="sales-btn-filter" onclick="filterSalesByDate()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                                </svg>
                                Apply Filter
                            </button>
                            <button class="sales-btn-clear" onclick="clearSalesFilters()" title="Clear all filters">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                                Clear
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Sales Table Card -->
                <div class="sales-card">
                    
                    <div class="sales-card-header">
                        <div class="sales-card-title-wrap">
                            <div class="sales-card-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                                All Transactions
                            </div>
                            <div class="sales-card-subtitle">
                                <span id="salesCount"><?php echo count($sales); ?></span> transactions
                            </div>
                        </div>
                    </div>
                    
                    <div class="sales-table-wrap">
                        <table class="sales-table">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Cashier</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Paid</th>
                                    <th>Change</th>
                                    <th>Discount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th class="no-print" style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="salesTableBody">
                                <?php if (!empty($sales)): ?>
                                    <?php foreach ($sales as $sale): ?>
                                    <tr>
                                        <td>
                                            <span class="sales-invoice"><?php echo htmlspecialchars($sale['invoice_number']); ?></span>
                                        </td>
                                        <td>
                                            <div class="sales-user-cell">
                                                <div class="sales-user-avatar"><?php echo strtoupper(substr($sale['cashier'] ?? 'U', 0, 1)); ?></div>
                                                <span><?php echo htmlspecialchars($sale['cashier'] ?? 'N/A'); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($sale['customer'])): ?>
                                                <div class="sales-user-cell">
                                                    <div class="sales-user-avatar sales-customer-avatar"><?php echo strtoupper(substr($sale['customer'], 0, 1)); ?></div>
                                                    <span><?php echo htmlspecialchars($sale['customer']); ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="sales-walkin">🚶 Walk-in</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong class="sales-amount">₱<?php echo number_format($sale['total_amount'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <span class="sales-paid">₱<?php echo number_format($sale['amount_paid'] ?? $sale['total_amount'], 2); ?></span>
                                        </td>
                                        <td>
                                            <span class="sales-change">₱<?php echo number_format($sale['change_amount'] ?? 0, 2); ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($sale['discount_amount']) && $sale['discount_amount'] > 0): ?>
                                                <span class="sales-discount">-₱<?php echo number_format($sale['discount_amount'], 2); ?></span>
                                            <?php else: ?>
                                                <span class="sales-empty">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="sales-date"><?php echo date('M d, Y', strtotime($sale['sale_date'])); ?><br><small><?php echo date('h:i A', strtotime($sale['sale_date'])); ?></small></span>
                                        </td>
                                        <td>
                                            <span class="sales-status-badge">✅ Paid</span>
                                        </td>
                                        <td class="no-print" style="text-align:right;">
                                            <div class="sales-actions">
                                                <button class="sales-action-btn sales-action-view" onclick="viewSaleDetails(<?php echo $sale['id']; ?>)" title="View Details">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" style="text-align:center;padding:60px 20px;color:#9CA3AF;">
                                            <div style="font-size:48px;margin-bottom:12px;opacity:0.5;">🧾</div>
                                            <div style="font-size:16px;font-weight:700;color:#4B5563;margin-bottom:4px;">No sales yet</div>
                                            <div style="font-size:13px;">Start making sales to see them here</div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- ============================================ -->
                <!-- SALE DETAILS MODAL -->
                <!-- ============================================ -->
                <div class="modal" id="saleDetailsModal">
                    <div class="modal-content sale-details-modal">
                        <div class="sales-modal-header">
                            <div class="sales-modal-header-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1z"></path>
                                    <path d="M8 7h8"></path>
                                    <path d="M8 11h8"></path>
                                    <path d="M8 15h5"></path>
                                </svg>
                            </div>
                            <div class="sales-modal-header-text">
                                <h2 id="saleDetailsTitle">Sale Details</h2>
                                <p>Complete transaction information</p>
                            </div>
                            <button class="sales-modal-close" onclick="closeModal('saleDetailsModal')">&times;</button>
                        </div>
                        <div id="saleDetailsContent"></div>
                    </div>
                </div>
                
                <script>
                    // ===== SALES JAVASCRIPT (Functions preserved) =====
                    
                    function filterSalesByDate() {
                        var startDate = document.getElementById('salesStartDate').value;
                        var endDate = document.getElementById('salesEndDate').value;
                        if (!startDate || !endDate) { 
                            alert('Please select both start and end dates.'); 
                            return; 
                        }
                        fetch('?action=get_sales_by_date&start_date=' + startDate + '&end_date=' + endDate)
                        .then(function(res) { return res.json(); })
                        .then(function(data) {
                            updateSalesTable(data);
                        });
                    }
                    
                    function filterSalesByCustomer() {
                        var customerId = document.getElementById('salesCustomerFilter').value;
                        if (!customerId) { 
                            // No customer selected — show all sales
                            location.reload();
                            return; 
                        }
                        fetch('?action=get_sales_by_customer&customer_id=' + customerId)
                        .then(function(res) { return res.json(); })
                        .then(function(data) {
                            updateSalesTable(data);
                        });
                    }
                    
                    function updateSalesTable(sales) {
                        var tbody = document.getElementById('salesTableBody');
                        tbody.innerHTML = '';
                        
                        if (sales.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:60px 20px;color:#9CA3AF;"><div style="font-size:48px;margin-bottom:12px;opacity:0.5;">🔍</div><div style="font-size:16px;font-weight:700;color:#4B5563;margin-bottom:4px;">No sales found</div><div style="font-size:13px;">Try a different filter</div></td></tr>';
                            document.getElementById('salesCount').textContent = 0;
                            return;
                        }
                        
                        sales.forEach(function(s) {
                            var cashierInitial = (s.cashier || 'U').charAt(0).toUpperCase();
                            var customerHtml = s.customer 
                                ? '<div class="sales-user-cell"><div class="sales-user-avatar sales-customer-avatar">' + s.customer.charAt(0).toUpperCase() + '</div><span>' + s.customer + '</span></div>'
                                : '<span class="sales-walkin">🚶 Walk-in</span>';
                            var discountHtml = (s.discount_amount && parseFloat(s.discount_amount) > 0)
                                ? '<span class="sales-discount">-₱' + parseFloat(s.discount_amount).toFixed(2) + '</span>'
                                : '<span class="sales-empty">—</span>';
                            var saleDate = new Date(s.sale_date);
                            var dateStr = saleDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                            var timeStr = saleDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                            
                            var row = '<tr>' +
                                '<td><span class="sales-invoice">' + s.invoice_number + '</span></td>' +
                                '<td><div class="sales-user-cell"><div class="sales-user-avatar">' + cashierInitial + '</div><span>' + (s.cashier || 'N/A') + '</span></div></td>' +
                                '<td>' + customerHtml + '</td>' +
                                '<td><strong class="sales-amount">₱' + parseFloat(s.total_amount).toFixed(2) + '</strong></td>' +
                                '<td><span class="sales-paid">₱' + parseFloat(s.amount_paid || s.total_amount).toFixed(2) + '</span></td>' +
                                '<td><span class="sales-change">₱' + parseFloat(s.change_amount || 0).toFixed(2) + '</span></td>' +
                                '<td>' + discountHtml + '</td>' +
                                '<td><span class="sales-date">' + dateStr + '<br><small>' + timeStr + '</small></span></td>' +
                                '<td><span class="sales-status-badge">✅ Paid</span></td>' +
                                '<td class="no-print" style="text-align:right;"><div class="sales-actions"><button class="sales-action-btn sales-action-view" onclick="viewSaleDetails(' + s.id + ')" title="View Details"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></button></div></td>' +
                                '</tr>';
                            tbody.innerHTML += row;
                        });
                        
                        document.getElementById('salesCount').textContent = sales.length;
                    }
                    
                    function clearSalesFilters() {
                        document.getElementById('salesStartDate').value = '';
                        document.getElementById('salesEndDate').value = '';
                        document.getElementById('salesCustomerFilter').value = '';
                        location.reload();
                    }
                    
                    function viewSaleDetails(id) {
                        fetch('?action=get_sale&id=' + id)
                        .then(function(res) { return res.json(); })
                        .then(function(sale) {
                            var html = '';
                            
                            // Header info
                            html += '<div class="sale-detail-grid">';
                            html += '<div class="sale-detail-item"><div class="sale-detail-label">Invoice</div><div class="sale-detail-value"><span class="sale-invoice-badge">' + sale.invoice_number + '</span></div></div>';
                            html += '<div class="sale-detail-item"><div class="sale-detail-label">Date & Time</div><div class="sale-detail-value">' + new Date(sale.sale_date).toLocaleString() + '</div></div>';
                            html += '<div class="sale-detail-item"><div class="sale-detail-label">Cashier</div><div class="sale-detail-value">' + (sale.cashier || 'N/A') + '</div></div>';
                            html += '<div class="sale-detail-item"><div class="sale-detail-label">Customer</div><div class="sale-detail-value">' + (sale.customer || 'Walk-in') + '</div></div>';
                            html += '</div>';
                            
                            // Items table
                            html += '<div class="sale-items-title">Items</div>';
                            html += '<div class="sale-items-wrap"><table class="sale-items-table"><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead><tbody>';
                            if (sale.items && sale.items.length > 0) {
                                sale.items.forEach(function(item) {
                                    html += '<tr>';
                                    html += '<td><strong>' + item.product_name + '</strong></td>';
                                    html += '<td>' + item.quantity + ' ' + (item.unit || 'pc') + '</td>';
                                    html += '<td>₱' + parseFloat(item.unit_price).toFixed(2) + '</td>';
                                    html += '<td><strong>₱' + parseFloat(item.total_price).toFixed(2) + '</strong></td>';
                                    html += '</tr>';
                                });
                            }
                            html += '</tbody></table></div>';
                            
                            // Totals
                            var subtotal = parseFloat(sale.subtotal).toFixed(2);
                            var tax = parseFloat(sale.tax).toFixed(2);
                            var total = parseFloat(sale.total_amount).toFixed(2);
                            var discount = parseFloat(sale.discount_amount || 0).toFixed(2);
                            var paid = parseFloat(sale.amount_paid || sale.total_amount).toFixed(2);
                            var change = parseFloat(sale.change_amount || 0).toFixed(2);
                            
                            html += '<div class="sale-totals-box">';
                            if (parseFloat(discount) > 0) {
                                html += '<div class="sale-total-row"><span>Discount</span><span class="sale-total-discount">-₱' + discount + '</span></div>';
                            }
                            html += '<div class="sale-total-row"><span>Subtotal</span><span>₱' + subtotal + '</span></div>';
                            html += '<div class="sale-total-row"><span>VAT (12%)</span><span>₱' + tax + '</span></div>';
                            html += '<div class="sale-total-row sale-total-grand"><span>Total</span><span>₱' + total + '</span></div>';
                            html += '<div class="sale-total-row sale-total-paid"><span>Amount Paid</span><span>₱' + paid + '</span></div>';
                            html += '<div class="sale-total-row sale-total-change"><span>Change</span><span>₱' + change + '</span></div>';
                            html += '</div>';
                            
                            document.getElementById('saleDetailsTitle').textContent = 'Invoice ' + sale.invoice_number;
                            document.getElementById('saleDetailsContent').innerHTML = html;
                            document.getElementById('saleDetailsModal').classList.add('show');
                        });
                    }
                    
                    function closeModal(id){ document.getElementById(id).classList.remove('show'); }
                    
                    document.querySelectorAll('.modal').forEach(function(modal) {
                        modal.addEventListener('click', function(e) {
                            if (e.target === this) {
                                this.classList.remove('show');
                            }
                        });
                    });
                </script>
                
                <?php
break;
endswitch;
