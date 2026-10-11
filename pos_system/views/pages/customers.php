<?php
switch ('customers'):
case 'customers':

                $customers = $customerManager->getAllCustomers();
                ?>
                
                <!-- ============================================ -->
                <!-- CUSTOMERS PAGE - MODERN INTERFACE -->
                <!-- ============================================ -->
                
                <!-- Page Header -->
                <div class="cust-page-header">
                    <div class="cust-page-header-left">
                        <h2 class="cust-page-title">
                            <span class="cust-page-icon">👤</span>
                            Customer Management
                        </h2>
                        <p class="cust-page-subtitle">Manage your customer database and loyalty program</p>
                    </div>
                    <button class="cust-btn-primary no-print" onclick="showAddCustomer()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Add Customer
                    </button>
                </div>
                
                <!-- Customers Card -->
                <div class="cust-card">
                    
                    <!-- Card Header with search -->
                    <div class="cust-card-header">
                        <div class="cust-card-title-wrap">
                            <div class="cust-card-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                All Customers
                            </div>
                            <div class="cust-card-subtitle">
                                <span id="custTotalCount"><?php echo count($customers); ?></span> <?php echo count($customers) === 1 ? 'customer' : 'customers'; ?> in database
                            </div>
                        </div>
                        
                        <!-- Search bar -->
                        <div class="cust-search-wrap">
                            <span class="cust-search-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                            </span>
                            <input type="text" id="custSearchInput" placeholder="Search customers..." onkeyup="filterCustomers()" class="cust-search-input">
                        </div>
                    </div>
                    
                    <!-- Customers Table -->
                    <?php if (!empty($customers)): ?>
                    <div class="cust-table-wrap">
                        <table class="cust-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Loyalty Points</th>
                                    <th>Joined</th>
                                    <th class="no-print" style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="custTableBody">
                                <?php foreach ($customers as $c): ?>
                                <tr data-name="<?php echo strtolower(htmlspecialchars($c['name'])); ?>" data-email="<?php echo strtolower(htmlspecialchars($c['email'] ?? '')); ?>" data-phone="<?php echo htmlspecialchars($c['phone'] ?? ''); ?>">
                                    <td>
                                        <div class="cust-name-cell">
                                            <div class="cust-avatar"><?php echo strtoupper(substr($c['name'], 0, 1)); ?></div>
                                            <div class="cust-name-info">
                                                <div class="cust-name"><?php echo htmlspecialchars($c['name']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($c['email'])): ?>
                                            <span class="cust-email"><?php echo htmlspecialchars($c['email']); ?></span>
                                        <?php else: ?>
                                            <span class="cust-empty">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($c['phone'])): ?>
                                            <span class="cust-phone"><?php echo htmlspecialchars($c['phone']); ?></span>
                                        <?php else: ?>
                                            <span class="cust-empty">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="cust-points-badge">
                                            ⭐ <?php echo number_format($c['loyalty_points'] ?? 0); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="cust-date"><?php echo isset($c['created_at']) ? date('M d, Y', strtotime($c['created_at'])) : 'N/A'; ?></span>
                                    </td>
                                    <td class="no-print" style="text-align:right;">
                                        <div class="cust-actions">
                                            <button class="cust-action-btn cust-action-view" onclick="viewCustomerProfile(<?php echo $c['id']; ?>)" title="View Profile">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </button>
                                            <button class="cust-action-btn cust-action-edit" onclick="editCustomer(<?php echo $c['id']; ?>)" title="Edit">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </button>
                                            <button class="cust-action-btn cust-action-delete" onclick="deleteCustomer(<?php echo $c['id']; ?>)" title="Delete">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="custNoResults" class="cust-empty-state" style="display:none;">
                        <div class="cust-empty-icon">🔍</div>
                        <div class="cust-empty-title">No customers found</div>
                        <div class="cust-empty-text">Try adjusting your search term</div>
                    </div>
                    <?php else: ?>
                    <div class="cust-empty-state">
                        <div class="cust-empty-icon">👤</div>
                        <div class="cust-empty-title">No customers yet</div>
                        <div class="cust-empty-text">Click "Add Customer" to create your first customer</div>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
                <!-- ============================================ -->
                <!-- CUSTOMER PROFILE MODAL -->
                <!-- ============================================ -->
                <div class="modal" id="customerProfileModal">
                    <div class="modal-content cust-profile-modal">
                        <div class="cust-modal-header">
                            <div class="cust-modal-header-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                            <div class="cust-modal-header-text">
                                <h2 id="profileName">Customer Profile</h2>
                                <p>View customer details and history</p>
                            </div>
                            <button class="cust-modal-close" onclick="closeModal('customerProfileModal')">&times;</button>
                        </div>
                        <div id="profileContent"></div>
                    </div>
                </div>
                
                <!-- ============================================ -->
                <!-- ADD/EDIT CUSTOMER MODAL -->
                <!-- ============================================ -->
                <div class="modal" id="addCustomerModal">
                    <div class="modal-content cust-modal">
                        <div class="cust-modal-header">
                            <div class="cust-modal-header-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="8.5" cy="7" r="4"></circle>
                                    <line x1="20" y1="8" x2="20" y2="14"></line>
                                    <line x1="23" y1="11" x2="17" y2="11"></line>
                                </svg>
                            </div>
                            <div class="cust-modal-header-text">
                                <h2 id="customerModalTitle">Add Customer</h2>
                                <p>Enter customer details below</p>
                            </div>
                            <button class="cust-modal-close" onclick="closeModal('addCustomerModal')">&times;</button>
                        </div>
                        
                        <form id="customerForm" onsubmit="saveCustomer(event)">
                            <input type="hidden" id="customerEditId">
                            
                            <div class="cust-form-group">
                                <label class="cust-form-label">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    Full Name *
                                </label>
                                <input type="text" id="custName" placeholder="e.g., Maria Santos" required class="cust-form-input">
                            </div>
                            
                            <div class="cust-form-group">
                                <label class="cust-form-label">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                        <polyline points="22,6 12,13 2,6"></polyline>
                                    </svg>
                                    Email Address
                                </label>
                                <input type="email" id="custEmail" placeholder="customer@email.com" class="cust-form-input">
                            </div>
                            
                            <div class="cust-form-group">
                                <label class="cust-form-label">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                    </svg>
                                    Phone Number (11 digits)
                                </label>
                                <input type="text" id="custPhone" placeholder="09123456789" maxlength="11" oninput="validateCustomerPhone(this)" class="cust-form-input">
                                <div id="custPhoneError" class="cust-form-hint"></div>
                            </div>
                            
                            <div class="cust-modal-actions">
                                <button type="button" class="cust-btn-cancel" onclick="closeModal('addCustomerModal')">
                                    Cancel
                                </button>
                                <button type="submit" class="cust-btn-submit">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    Save Customer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <script>
                    // ===== CUSTOMERS JAVASCRIPT (Functions preserved) =====
                    
                    function validateCustomerPhone(input) {
                        var phone = input.value.replace(/\D/g, '');
                        var errorDiv = document.getElementById('custPhoneError');
                        if (phone.length > 0 && phone.length !== 11) { 
                            errorDiv.textContent = '⚠️ Phone must be exactly 11 digits'; 
                            errorDiv.style.color = '#EF4444'; 
                            input.style.borderColor = '#EF4444'; 
                        } else if (phone.length === 11) { 
                            errorDiv.textContent = '✅ Valid phone number'; 
                            errorDiv.style.color = '#10B981'; 
                            input.style.borderColor = '#10B981'; 
                        } else { 
                            errorDiv.textContent = ''; 
                            input.style.borderColor = ''; 
                        }
                    }
                    
                    function filterCustomers() {
                        var searchTerm = (document.getElementById('custSearchInput').value || '').toLowerCase().trim();
                        var rows = document.querySelectorAll('#custTableBody tr');
                        var visibleCount = 0;
                        
                        rows.forEach(function(row) {
                            var name = row.getAttribute('data-name') || '';
                            var email = row.getAttribute('data-email') || '';
                            var phone = row.getAttribute('data-phone') || '';
                            var show = searchTerm === '' || name.indexOf(searchTerm) !== -1 || email.indexOf(searchTerm) !== -1 || phone.indexOf(searchTerm) !== -1;
                            row.style.display = show ? '' : 'none';
                            if (show) visibleCount++;
                        });
                        
                        var noResults = document.getElementById('custNoResults');
                        var totalCount = document.getElementById('custTotalCount');
                        if (noResults) noResults.style.display = visibleCount === 0 ? 'block' : 'none';
                        if (totalCount) totalCount.textContent = visibleCount;
                    }
                    
                    function viewCustomerProfile(id) {
                        fetch('?action=get_customer&id=' + id)
                        .then(function(res) { return res.json(); })
                        .then(function(data) {
                            var c = data.customer;
                            var stats = data.stats;
                            var history = data.history;
                            
                            var html = '';
                            
                            // Profile Header
                            html += '<div class="cust-profile-header">';
                            html += '<div class="cust-profile-avatar">' + c.name.charAt(0).toUpperCase() + '</div>';
                            html += '<div class="cust-profile-info">';
                            html += '<h3>' + c.name + '</h3>';
                            html += '<div class="cust-profile-meta">';
                            if (c.email) html += '<span>📧 ' + c.email + '</span>';
                            if (c.phone) html += '<span>📞 ' + c.phone + '</span>';
                            html += '</div>';
                            html += '<div class="cust-profile-badges">';
                            html += '<span class="cust-points-badge-large">⭐ ' + (c.loyalty_points || 0) + ' Points</span>';
                            html += '</div>';
                            html += '</div>';
                            html += '</div>';
                            
                            // Stats Grid
                            html += '<div class="cust-stats-grid">';
                            html += '<div class="cust-stat-item"><div class="cust-stat-value">' + stats.total_orders + '</div><div class="cust-stat-label">Total Orders</div></div>';
                            html += '<div class="cust-stat-item"><div class="cust-stat-value">₱' + parseFloat(stats.total_spent).toFixed(2) + '</div><div class="cust-stat-label">Total Spent</div></div>';
                            html += '<div class="cust-stat-item"><div class="cust-stat-value">₱' + parseFloat(stats.average_order).toFixed(2) + '</div><div class="cust-stat-label">Avg Order</div></div>';
                            html += '<div class="cust-stat-item"><div class="cust-stat-value">' + (stats.last_purchase ? new Date(stats.last_purchase).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : 'Never') + '</div><div class="cust-stat-label">Last Purchase</div></div>';
                            html += '</div>';
                            
                            // Purchase History
                            html += '<div class="cust-history-title">🕐 Purchase History</div>';
                            if (history.length > 0) {
                                html += '<div class="cust-history-wrap"><table class="cust-history-table"><thead><tr><th>Invoice</th><th>Total</th><th>Cashier</th><th>Date</th></tr></thead><tbody>';
                                history.forEach(function(s) {
                                    html += '<tr>';
                                    html += '<td><span class="cust-invoice-badge">' + s.invoice_number + '</span></td>';
                                    html += '<td><strong>₱' + parseFloat(s.total_amount).toFixed(2) + '</strong></td>';
                                    html += '<td>' + (s.cashier || 'N/A') + '</td>';
                                    html += '<td>' + new Date(s.sale_date).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) + '</td>';
                                    html += '</tr>';
                                });
                                html += '</tbody></table></div>';
                            } else {
                                html += '<div class="cust-no-history">No purchases yet</div>';
                            }
                            
                            document.getElementById('profileName').textContent = c.name;
                            document.getElementById('profileContent').innerHTML = html;
                            document.getElementById('customerProfileModal').classList.add('show');
                        });
                    }
                    
                    function showAddCustomer(){
                        document.getElementById('customerModalTitle').textContent = 'Add Customer';
                        document.getElementById('customerEditId').value = '';
                        document.getElementById('custName').value = '';
                        document.getElementById('custEmail').value = '';
                        document.getElementById('custPhone').value = '';
                        document.getElementById('custPhoneError').textContent = '';
                        document.getElementById('addCustomerModal').classList.add('show');
                    }
                    
                    function editCustomer(id) {
                        fetch('?action=get_customer&id=' + id)
                        .then(function(res) { return res.json(); })
                        .then(function(data) {
                            var c = data.customer;
                            document.getElementById('customerModalTitle').textContent = 'Edit Customer';
                            document.getElementById('customerEditId').value = c.id;
                            document.getElementById('custName').value = c.name;
                            document.getElementById('custEmail').value = c.email || '';
                            document.getElementById('custPhone').value = c.phone || '';
                            document.getElementById('custPhoneError').textContent = '';
                            document.getElementById('addCustomerModal').classList.add('show');
                        });
                    }
                    
                    function saveCustomer(e){
                        e.preventDefault();
                        var id = document.getElementById('customerEditId').value;
                        var name = document.getElementById('custName').value.trim();
                        var email = document.getElementById('custEmail').value;
                        var phone = document.getElementById('custPhone').value.replace(/\D/g, '');
                        
                        if(!name) return alert('Name is required');
                        if (phone.length > 0 && phone.length !== 11) { alert('⚠️ Phone number must be exactly 11 digits!'); return; }
                        
                        var data = new FormData();
                        data.append('id', id);
                        data.append('name', name);
                        data.append('email', email);
                        data.append('phone', phone);
                        
                        var action = id ? 'update_customer' : 'add_customer';
                        fetch('?action=' + action, { method: 'POST', body: data })
                        .then(function(res) { return res.json(); })
                        .then(function(result){
                            if(result.success){
                                alert('✅ Customer saved!');
                                closeModal('addCustomerModal');
                                location.reload();
                            } else {
                                alert('❌ Error: ' + result.message);
                            }
                        });
                    }
                    
                    function deleteCustomer(id) {
                        customConfirm('Delete this customer permanently?', function() {
                            fetch('?action=delete_customer&id=' + id)
                            .then(function(res) { return res.json(); })
                            .then(function(result){
                                if(result.success){
                                    alert('✅ Customer deleted!');
                                    location.reload();
                                } else {
                                    alert('❌ Error: ' + result.message);
                                }
                            });
                        }, 'danger');
                    }
                    
                    function closeModal(id){ document.getElementById(id).classList.remove('show'); }
                    
                    // Close modals on outside click
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
