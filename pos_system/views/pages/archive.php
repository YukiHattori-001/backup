<?php
switch ('archive'):
case 'archive':

                if (!canAccess('archive')) {
                    echo '<div class="alert alert-danger" style="margin:2rem;text-align:center;">
                        <div style="font-size:4rem;margin-bottom:1rem;">⛔</div>
                        <h2>Access Denied</h2>
                        <p>You do not have permission to access this page.</p>
                        <a href="?page=dashboard" class="btn btn-primary" style="margin-top:1rem;">Go to Dashboard</a>
                    </div>';
                    break;
                }
                $search = isset($_GET['search']) ? $_GET['search'] : '';
                $archived = $productManager->getArchivedProducts($search);
                $history = $productManager->getArchiveHistory($search);
                ?>
                
                <!-- ============================================ -->
                <!-- ARCHIVE PAGE - MODERN INTERFACE -->
                <!-- ============================================ -->
                
                <!-- Page Header -->
                <div class="arch-page-header">
                    <div class="arch-page-header-left">
                        <h2 class="arch-page-title">
                            <span class="arch-page-icon">📁</span>
                            Archived Products
                        </h2>
                        <p class="arch-page-subtitle">View and restore archived products</p>
                    </div>
                    <div class="arch-page-header-actions">
                        <a href="?page=products" class="arch-btn-secondary no-print">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="19" y1="12" x2="5" y2="12"></line>
                                <polyline points="12 19 5 12 12 5"></polyline>
                            </svg>
                            Back to Products
                        </a>
                    </div>
                </div>
                
                <!-- Search Bar -->
                <div class="arch-search-bar-wrap">
                    <div class="arch-search-wrap">
                        <span class="arch-search-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </span>
                        <input type="text" id="archiveSearch" placeholder="Search archived products..." value="<?php echo htmlspecialchars($search); ?>" onkeyup="searchArchive(event)" class="arch-search-input">
                        <button type="button" class="arch-search-clear" onclick="clearArchiveSearch()" title="Clear">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- ===== ARCHIVED PRODUCTS CARD ===== -->
                <div class="arch-card">
                    
                    <div class="arch-card-header">
                        <div class="arch-card-title-wrap">
                            <div class="arch-card-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 8v13H3V8"></path>
                                    <path d="M1 3h22v5H1z"></path>
                                    <line x1="10" y1="12" x2="14" y2="12"></line>
                                </svg>
                                Archived Products
                            </div>
                            <div class="arch-card-subtitle">
                                <?php echo count($archived); ?> <?php echo count($archived) === 1 ? 'product' : 'products'; ?> in archive
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($archived)): ?>
                    <div class="arch-table-wrap">
                        <table class="arch-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Unit</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th class="no-print" style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($archived as $p): ?>
                                <tr>
                                    <td>
                                        <div class="arch-product-cell">
                                            <?php if (!empty($p['image'])): ?>
                                                <div class="arch-image-thumb">
                                                    <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                                                </div>
                                            <?php else: ?>
                                                <div class="arch-image-placeholder">📦</div>
                                            <?php endif; ?>
                                            <div class="arch-product-info">
                                                <div class="arch-product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="arch-category-badge"><?php echo htmlspecialchars($p['category_name'] ?? 'Uncategorized'); ?></span>
                                    </td>
                                    <td>
                                        <span class="arch-price">₱<?php echo number_format($p['selling_price'], 2); ?></span>
                                    </td>
                                    <td>
                                        <span class="arch-unit-badge"><?php echo htmlspecialchars($p['unit'] ?? 'pc'); ?></span>
                                    </td>
                                    <td>
                                        <span class="arch-stock"><?php echo $p['stock_quantity']; ?></span>
                                    </td>
                                    <td>
                                        <span class="arch-status-badge arch-status-archived">
                                            📁 Archived
                                        </span>
                                    </td>
                                    <td class="no-print" style="text-align:right;">
                                        <div class="arch-actions">
                                            <?php if (isAdmin()): ?>
                                                <button class="arch-action-btn arch-action-restore" onclick="restoreProduct(<?php echo $p['id']; ?>)" title="Restore Product">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="1 4 1 10 7 10"></polyline>
                                                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                                                    </svg>
                                                    Restore
                                                </button>
                                                <?php else: ?>
                                                <span style="color:#9CA3AF;font-size:11px;font-weight:600;">View Only</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="arch-empty-state">
                        <div class="arch-empty-icon">📁</div>
                        <div class="arch-empty-title"><?php echo $search ? 'No archived products match your search' : 'No archived products'; ?></div>
                        <div class="arch-empty-text"><?php echo $search ? 'Try a different search term' : 'Archived products will appear here'; ?></div>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
                <!-- ===== ARCHIVE HISTORY CARD ===== -->
                <div class="arch-card">
                    
                    <div class="arch-card-header">
                        <div class="arch-card-title-wrap">
                            <div class="arch-card-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                Archive History
                            </div>
                            <div class="arch-card-subtitle">
                                <?php echo count($history); ?> total records
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($history)): ?>
                    <div class="arch-table-wrap">
                        <table class="arch-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Action</th>
                                    <th>By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $h): ?>
                                <tr>
                                    <td>
                                        <div class="arch-history-product">
                                            <div class="arch-history-icon">
                                                <?php echo $h['action'] == 'archived' ? '📁' : '♻️'; ?>
                                            </div>
                                            <span class="arch-history-name"><?php echo htmlspecialchars($h['product_name']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="arch-action-badge <?php echo $h['action'] == 'archived' ? 'arch-badge-archived' : 'arch-badge-restored'; ?>">
                                            <?php echo $h['action'] == 'archived' ? '📁 Archived' : '♻️ Restored'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="arch-user-cell">
                                            <div class="arch-user-avatar">
                                                <?php echo strtoupper(substr($h['archived_by_name'] ?? $h['restored_by_name'] ?? 'S', 0, 1)); ?>
                                            </div>
                                            <span><?php echo htmlspecialchars($h['archived_by_name'] ?? $h['restored_by_name'] ?? 'System'); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="arch-date"><?php echo date('M d, Y H:i', strtotime($h['archived_date'])); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="arch-empty-state">
                        <div class="arch-empty-icon">📜</div>
                        <div class="arch-empty-title">No archive history</div>
                        <div class="arch-empty-text">Archive and restore actions will appear here</div>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
                <script>
                    // ===== ARCHIVE JAVASCRIPT (Functions unchanged) =====
                    
                    function restoreProduct(id){ 
                        customConfirm('Restore this product?', function() {
                            fetch('?action=restore_product&id=' + id)
                            .then(function(res) { return res.json(); })
                            .then(function(result){ 
                                if(result.success){ 
                                    alert('✅ Product restored!'); 
                                    location.reload(); 
                                } else { 
                                    alert('❌ Error restoring product'); 
                                } 
                            }); 
                        }, 'success'); 
                    }
                    
                    function searchArchive(e) {
                        if (e.key === 'Enter') {
                            var search = document.getElementById('archiveSearch').value;
                            window.location.href = '?page=archive&search=' + encodeURIComponent(search);
                        }
                    }
                    
                    function clearArchiveSearch() {
                        document.getElementById('archiveSearch').value = '';
                        window.location.href = '?page=archive';
                    }
                </script>
                
                <?php
break;
endswitch;
