<?php
switch ('reports'):
case 'reports':

                if (!canAccess('reports')) {
                    echo '<div class="alert alert-danger" style="margin:2rem;text-align:center;">
                        <div style="font-size:4rem;margin-bottom:1rem;">⛔</div>
                        <h2>Access Denied</h2>
                        <p>You do not have permission to access this page.</p>
                        <a href="?page=dashboard" class="btn btn-primary" style="margin-top:1rem;">Go to Dashboard</a>
                    </div>';
                    break;
                }
                
                $stats = $reportManager->getStats();
                $topProducts = $pdo->query("
                    SELECT 
                        p.id, 
                        p.name, 
                        p.unit, 
                        c.name as category, 
                        p.selling_price as price, 
                        SUM(si.quantity) as sold, 
                        SUM(si.total_price) as revenue 
                    FROM sale_items si 
                    JOIN products p ON si.product_id = p.id 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    GROUP BY p.id 
                    ORDER BY sold DESC 
                    LIMIT 10
                ")->fetchAll();
                $totalUnitsSold = array_sum(array_column($topProducts, 'sold'));
                ?>
                
                <!-- ============================================ -->
                <!-- REPORTS PAGE - MODERN INTERFACE -->
                <!-- ============================================ -->
                
                <!-- Page Header -->
                <div class="rep-page-header">
                    <div class="rep-page-header-left">
                        <h2 class="rep-page-title">
                            <span class="rep-page-icon">📈</span>
                            Sales Reports
                        </h2>
                        <p class="rep-page-subtitle">Analyze your sales performance and top products</p>
                    </div>
                </div>
                
                <!-- Stats Grid -->
                <div class="rep-stats-grid">
                    
                    <div class="rep-stat-card">
                        <div class="rep-stat-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                        <div class="rep-stat-label">Today's Sales</div>
                        <div class="rep-stat-value">₱<?php echo number_format($stats['today'], 2); ?></div>
                        <div class="rep-stat-footer">Total revenue today</div>
                    </div>
                    
                    <div class="rep-stat-card">
                        <div class="rep-stat-icon" style="background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="20" x2="18" y2="10"></line>
                                <line x1="12" y1="20" x2="12" y2="4"></line>
                                <line x1="6" y1="20" x2="6" y2="14"></line>
                            </svg>
                        </div>
                        <div class="rep-stat-label">Monthly Revenue</div>
                        <div class="rep-stat-value">₱<?php echo number_format($stats['month'], 2); ?></div>
                        <div class="rep-stat-footer"><?php echo date('F Y'); ?></div>
                    </div>
                    
                    <div class="rep-stat-card">
                        <div class="rep-stat-icon" style="background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="17 1 21 5 17 9"></polyline>
                                <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                                <polyline points="7 23 3 19 7 15"></polyline>
                                <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                            </svg>
                        </div>
                        <div class="rep-stat-label">Total Transactions</div>
                        <div class="rep-stat-value"><?php echo number_format($stats['total_transactions']); ?></div>
                        <div class="rep-stat-footer">All-time transactions</div>
                    </div>
                    
                    <div class="rep-stat-card <?php echo $stats['low'] > 0 ? 'rep-stat-warning' : ''; ?>">
                        <div class="rep-stat-icon" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </div>
                        <div class="rep-stat-label">Low Stock Items</div>
                        <div class="rep-stat-value"><?php echo $stats['low']; ?></div>
                        <div class="rep-stat-footer"><?php echo $stats['low'] > 0 ? 'Action needed' : 'All good'; ?></div>
                    </div>
                    
                </div>
                
                <!-- Top Products Card -->
                <div class="rep-card">
                    
                    <div class="rep-card-header">
                        <div class="rep-card-title-wrap">
                            <div class="rep-card-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                </svg>
                                Top Selling Products
                            </div>
                            <div class="rep-card-subtitle">
                                Ranked by units sold · <strong><?php echo number_format($totalUnitsSold); ?></strong> total units across all products
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($topProducts)): ?>
                    <div class="rep-table-wrap">
                        <table class="rep-table">
                            <thead>
                                <tr>
                                    <th style="width:60px;">Rank</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Unit</th>
                                    <th>Units Sold</th>
                                    <th>Revenue</th>
                                    <th style="width:180px;">% of Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; ?>
                                <?php foreach ($topProducts as $p): ?>
                                <?php 
                                    $percentage = $totalUnitsSold > 0 ? ($p['sold'] / $totalUnitsSold) * 100 : 0;
                                    $rankClass = '';
                                    if ($rank == 1) $rankClass = 'gold';
                                    else if ($rank == 2) $rankClass = 'silver';
                                    else if ($rank == 3) $rankClass = 'bronze';
                                ?>
                                <tr>
                                    <td>
                                        <span class="rep-rank-badge <?php echo $rankClass; ?>"><?php echo $rank++; ?></span>
                                    </td>
                                    <td>
                                        <div class="rep-product-cell">
                                            <div class="rep-product-info">
                                                <div class="rep-product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="rep-category-badge"><?php echo htmlspecialchars($p['category'] ?? 'Uncategorized'); ?></span>
                                    </td>
                                    <td>
                                        <span class="rep-price">₱<?php echo number_format($p['price'] ?? 0, 2); ?></span>
                                    </td>
                                    <td>
                                        <span class="rep-unit-badge"><?php echo htmlspecialchars($p['unit'] ?? 'pc'); ?></span>
                                    </td>
                                    <td>
                                        <strong class="rep-sold-count"><?php echo number_format($p['sold']); ?></strong>
                                    </td>
                                    <td>
                                        <strong class="rep-revenue">₱<?php echo number_format($p['revenue'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <div class="rep-progress-wrap">
                                            <div class="rep-progress-bar">
                                                <div class="rep-progress-fill" style="width: <?php echo min($percentage, 100); ?>%;"></div>
                                            </div>
                                            <span class="rep-progress-text"><?php echo number_format($percentage, 1); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="rep-empty-state">
                        <div class="rep-empty-icon">📊</div>
                        <div class="rep-empty-title">No sales data yet</div>
                        <div class="rep-empty-text">Start making sales to see your top products here</div>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
                <?php
break;
endswitch;
