<?php
switch ('customer_reports'):
case 'customer_reports':

                if (!canAccess('customer_reports')) {
                    echo '<div class="alert alert-danger" style="margin:2rem;text-align:center;">
                        <div style="font-size:4rem;margin-bottom:1rem;">⛔</div>
                        <h2>Access Denied</h2>
                        <p>You do not have permission to access this page.</p>
                        <a href="?page=dashboard" class="btn btn-primary" style="margin-top:1rem;">Go to Dashboard</a>
                    </div>';
                    break;
                }
                
                $customerData = $customerManager->getCustomerSpendingReport();
                $customerReport = $customerData['customers'];
                $totalRevenue = $customerData['total_revenue'];
                $totalCustomers = count($customerReport);
                $totalOrders = array_sum(array_column($customerReport, 'total_orders'));
                $topCustomer = !empty($customerReport) ? $customerReport[0]['name'] : 'N/A';
                $topCustomerSpent = !empty($customerReport) ? $customerReport[0]['total_spent'] : 0;
                ?>
                
                <!-- ============================================ -->
                <!-- CUSTOMER REPORTS PAGE - MODERN INTERFACE -->
                <!-- ============================================ -->
                
                <!-- Page Header -->
                <div class="crep-page-header">
                    <div class="crep-page-header-left">
                        <h2 class="crep-page-title">
                            <span class="crep-page-icon">📊</span>
                            Customer Spending Reports
                        </h2>
                        <p class="crep-page-subtitle">Analyze customer behavior and spending patterns</p>
                    </div>
                </div>
                
                <!-- Stats Grid -->
                <div class="crep-stats-grid">
                    
                    <div class="crep-stat-card">
                        <div class="crep-stat-icon" style="background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div class="crep-stat-label">Total Customers</div>
                        <div class="crep-stat-value"><?php echo $totalCustomers; ?></div>
                        <div class="crep-stat-footer">In your database</div>
                    </div>
                    
                    <div class="crep-stat-card">
                        <div class="crep-stat-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                        <div class="crep-stat-label">Total Revenue</div>
                        <div class="crep-stat-value">₱<?php echo number_format($totalRevenue, 2); ?></div>
                        <div class="crep-stat-footer">All-time revenue</div>
                    </div>
                    
                    <div class="crep-stat-card">
                        <div class="crep-stat-icon" style="background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                        </div>
                        <div class="crep-stat-label">Total Orders</div>
                        <div class="crep-stat-value"><?php echo number_format($totalOrders); ?></div>
                        <div class="crep-stat-footer">All customer orders</div>
                    </div>
                    
                    <div class="crep-stat-card crep-stat-highlight">
                        <div class="crep-stat-icon" style="background: linear-gradient(135deg, #FCD34D 0%, #F59E0B 100%);">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                            </svg>
                        </div>
                        <div class="crep-stat-label">Top Customer</div>
                        <div class="crep-stat-value crep-stat-value-name"><?php echo htmlspecialchars($topCustomer); ?></div>
                        <div class="crep-stat-footer">₱<?php echo number_format($topCustomerSpent, 2); ?> spent</div>
                    </div>
                    
                </div>
                
                <!-- Customer Spending Details Card -->
                <div class="crep-card">
                    
                    <div class="crep-card-header">
                        <div class="crep-card-title-wrap">
                            <div class="crep-card-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                Customer Spending Details
                            </div>
                            <div class="crep-card-subtitle">
                                <strong><?php echo $totalCustomers; ?></strong> customers analyzed
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($customerReport)): ?>
                    <div class="crep-table-wrap">
                        <table class="crep-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                    <th>Average Order</th>
                                    <th>Last Purchase</th>
                                    <th>First Purchase</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                foreach ($customerReport as $c): 
                                    $rankClass = '';
                                    if ($rank == 1) $rankClass = 'gold';
                                    else if ($rank == 2) $rankClass = 'silver';
                                    else if ($rank == 3) $rankClass = 'bronze';
                                ?>
                                <tr>
                                    <td>
                                        <div class="crep-customer-cell">
                                            <div class="crep-avatar <?php echo $rankClass; ?>">
                                                <?php echo strtoupper(substr($c['name'], 0, 1)); ?>
                                            </div>
                                            <div class="crep-customer-info">
                                                <div class="crep-customer-name"><?php echo htmlspecialchars($c['name']); ?></div>
                                                <?php if ($rank <= 3): ?>
                                                    <div class="crep-rank-label <?php echo $rankClass; ?>">🏆 Top <?php echo $rank; ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($c['email'])): ?>
                                            <span class="crep-email"><?php echo htmlspecialchars($c['email']); ?></span>
                                        <?php else: ?>
                                            <span class="crep-empty">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="crep-orders-badge"><?php echo $c['total_orders']; ?></span>
                                    </td>
                                    <td>
                                        <strong class="crep-spent">₱<?php echo number_format($c['total_spent'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <span class="crep-avg">₱<?php echo number_format($c['average_order'], 2); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($c['last_purchase']): ?>
                                            <span class="crep-date"><?php echo date('M d, Y', strtotime($c['last_purchase'])); ?></span>
                                        <?php else: ?>
                                            <span class="crep-empty">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($c['first_purchase']): ?>
                                            <span class="crep-date"><?php echo date('M d, Y', strtotime($c['first_purchase'])); ?></span>
                                        <?php else: ?>
                                            <span class="crep-empty">Never</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php 
                                $rank++;
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="crep-empty-state">
                        <div class="crep-empty-icon">📊</div>
                        <div class="crep-empty-title">No customer data yet</div>
                        <div class="crep-empty-text">Customer spending data will appear here once you have sales with customers</div>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
                <?php
break;
endswitch;
