<?php
switch ('categories'):
case 'categories':

                if (!canAccess('categories')) {
                    echo '<div class="alert alert-danger" style="margin:2rem;text-align:center;">
                        <div style="font-size:4rem;margin-bottom:1rem;">⛔</div>
                        <h2>Access Denied</h2>
                        <p>You do not have permission to access this page.</p>
                        <a href="?page=dashboard" class="btn btn-primary" style="margin-top:1rem;">Go to Dashboard</a>
                    </div>';
                    break;
                }
                $categories = $categoryManager->getAllCategories();
                ?>
                
                <!-- ============================================ -->
                <!-- CATEGORIES PAGE - MODERN INTERFACE -->
                <!-- ============================================ -->
                
                <!-- Page Header -->
                <div class="cat-page-header">
                    <div class="cat-page-header-left">
                        <h2 class="cat-page-title">
                            <span class="cat-page-icon">🏷️</span>
                            Category Management
                        </h2>
                        <p class="cat-page-subtitle">Organize your products into categories</p>
                    </div>
                    <?php if (isAdmin()): ?>
                    <button class="cat-btn-primary no-print" onclick="showAddCategory()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Add Category
                    </button>
                    <?php endif; ?>
                </div>
                
                <!-- Categories Card -->
                <div class="cat-card">
                    
                    <!-- Card Header -->
                    <!-- Card Header with Stats + Filters -->
                <div class="cat-card-header">
                    <!-- Stat Card -->
                    <div class="cat-stat-block">
                        <div class="cat-stat-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
                                <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
                                <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
                                <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
                            </svg>
                        </div>
                        <div class="cat-stat-text">
                            <div class="cat-stat-value"><?php echo count($categories); ?></div>
                            <div class="cat-stat-label">Total Categories</div>
                        </div>
                    </div>
                    
                    <!-- Search + Filter -->
                    <div class="cat-filters">
                        <div class="cat-search-wrap">
                            <span class="cat-search-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                            </span>
                            <input type="text" id="catSearchInput" placeholder="Search categories..." onkeyup="filterCategories()" class="cat-search-input">
                        </div>
                        <select id="catSortFilter" onchange="filterCategories()" class="cat-sort-select">
                            <option value="">All Categories</option>
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="az">A → Z</option>
                            <option value="za">Z → A</option>
                        </select>
                    </div>
                </div>
                    
                    <!-- Categories Table -->
                    <!-- Categories Table -->
                <?php if (!empty($categories)): ?>
                        <div class="cat-table-wrap">
                            <table class="cat-table">
                                <thead>
                                    <tr>
                                        <th>Category Name</th>
                                        <th>Description</th>
                                        <th>Created</th>
                                        <th class="no-print" style="text-align:right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Smart icon matcher - maps category name keywords to icon + color
                                    function getCategoryStyle($name) {
                                        $name_lower = strtolower($name);
                                        
                                        $styles = [
                                            // Books & Stationery
                                            ['keywords' => ['book', 'station', 'notebook', 'pen', 'paper'], 
                                            'icon' => '📚', 'bg' => '#EEF2FF', 'color' => '#4F46E5'],
                                            // Clothing
                                            ['keywords' => ['cloth', 'apparel', 'fashion', 'shirt', 'jeans'], 
                                            'icon' => '👕', 'bg' => '#EFF6FF', 'color' => '#3B82F6'],
                                            // Electronics
                                            ['keywords' => ['electron', 'device', 'gadget', 'phone', 'laptop', 'tech'], 
                                            'icon' => '📱', 'bg' => '#ECFDF5', 'color' => '#10B981'],
                                            // Food & Beverages
                                            ['keywords' => ['food', 'bever', 'drink', 'snack', 'meal'], 
                                            'icon' => '🍽️', 'bg' => '#FFF7ED', 'color' => '#EA580C'],
                                            // Health & Beauty
                                            ['keywords' => ['health', 'beauty', 'cosmet', 'care'], 
                                            'icon' => '💄', 'bg' => '#FDF2F8', 'color' => '#DB2777'],
                                            // Home & Living
                                            ['keywords' => ['home', 'living', 'furniture', 'appliance'], 
                                            'icon' => '🏠', 'bg' => '#EFF6FF', 'color' => '#2563EB'],
                                            // Sports & Outdoors
                                            ['keywords' => ['sport', 'outdoor', 'fitness', 'gym'], 
                                            'icon' => '⚽', 'bg' => '#F5F3FF', 'color' => '#7C3AED'],
                                            // Toys & Games
                                            ['keywords' => ['toy', 'game', 'kid', 'child', 'play'], 
                                            'icon' => '🎮', 'bg' => '#FFFBEB', 'color' => '#D97706'],
                                            // Uncategorized
                                            ['keywords' => ['uncategor'], 
                                            'icon' => '📦', 'bg' => '#F3F4F6', 'color' => '#6B7280'],
                                        ];
                                        
                                        foreach ($styles as $style) {
                                            foreach ($style['keywords'] as $keyword) {
                                                if (strpos($name_lower, $keyword) !== false) {
                                                    return $style;
                                                }
                                            }
                                        }
                                        
                                        // Default fallback
                                        return ['icon' => '🏷️', 'bg' => '#EEF2FF', 'color' => '#4F46E5'];
                                    }
                                    
                                    foreach ($categories as $cat): 
                                        $style = getCategoryStyle($cat['name']);
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="cat-name-cell">
                                                <div class="cat-icon-badge" style="background: <?php echo $style['bg']; ?>; color: <?php echo $style['color']; ?>; border-color: <?php echo $style['bg']; ?>;">
                                                    <span style="filter: saturate(1.2);"><?php echo $style['icon']; ?></span>
                                                </div>
                                                <div class="cat-name-info">
                                                    <div class="cat-name"><?php echo htmlspecialchars($cat['name']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="cat-description"><?php echo !empty($cat['description']) ? htmlspecialchars($cat['description']) : '<em style="color:#9CA3AF;">No description</em>'; ?></span>
                                        </td>
                                        <td>
                                            <span class="cat-date"><?php echo date('M d, Y', strtotime($cat['created_at'])); ?></span>
                                        </td>
                                        <td class="no-print" style="text-align:right;">
                                            <div class="cat-actions">
                                                <?php if (isAdmin()): ?>
                                                <button class="cat-action-btn cat-action-edit" onclick="editCategory(...)">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </button>
                                                <button class="cat-action-btn cat-action-delete" onclick="deleteCategory(...)">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    </svg>
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
                        <div class="cat-empty-state">
                            <div class="cat-empty-icon">🏷️</div>
                            <div class="cat-empty-title">No categories yet</div>
                            <div class="cat-empty-text">Click "Add Category" to create your first category</div>
                        </div>
                        <?php endif; ?>
                    
                </div>
                
                <!-- ============================================ -->
                <!-- ADD/EDIT CATEGORY MODAL -->
                <!-- ============================================ -->
                <div class="modal" id="categoryModal">
                    <div class="modal-content cat-modal">
                        
                        <div class="cat-modal-header">
                            <div class="cat-modal-header-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                                    <line x1="7" y1="7" x2="7.01" y2="7"></line>
                                </svg>
                            </div>
                            <div class="cat-modal-header-text">
                                <h2 id="categoryModalTitle">Add Category</h2>
                                <p>Fill in the category details below</p>
                            </div>
                            <button class="cat-modal-close" onclick="closeModal('categoryModal')">&times;</button>
                        </div>
                        
                        <form id="categoryForm" onsubmit="saveCategory(event)">
                            <input type="hidden" id="categoryId">
                            
                            <div class="cat-form-group">
                                <label class="cat-form-label">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                                    </svg>
                                    Category Name *
                                </label>
                                <input type="text" id="catName" placeholder="e.g., Electronics" required class="cat-form-input">
                            </div>
                            
                            <div class="cat-form-group">
                                <label class="cat-form-label">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                    </svg>
                                    Description
                                </label>
                                <textarea id="catDescription" placeholder="Describe this category..." rows="3" class="cat-form-textarea"></textarea>
                            </div>
                            
                            <div class="cat-modal-actions">
                                <button type="button" class="cat-btn-cancel" onclick="closeModal('categoryModal')">
                                    Cancel
                                </button>
                                <button type="submit" class="cat-btn-submit">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    Save Category
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <script>
                    // ===== CATEGORIES JAVASCRIPT (Functions unchanged) =====
                    
                    function showAddCategory() {
                        document.getElementById('categoryModalTitle').textContent = 'Add Category';
                        document.getElementById('categoryId').value = '';
                        document.getElementById('catName').value = '';
                        document.getElementById('catDescription').value = '';
                        document.getElementById('categoryModal').classList.add('show');
                    }

                                        // Filter categories by search + sort
                    function filterCategories() {
                        var searchTerm = (document.getElementById('catSearchInput').value || '').toLowerCase().trim();
                        var sortValue = document.getElementById('catSortFilter').value;
                        var tbody = document.querySelector('.cat-table tbody');
                        var rows = Array.from(tbody.querySelectorAll('tr'));
                        
                        // Filter
                        rows.forEach(function(row) {
                            var name = row.querySelector('.cat-name')?.textContent.toLowerCase() || '';
                            var desc = row.querySelector('.cat-description')?.textContent.toLowerCase() || '';
                            var show = searchTerm === '' || name.indexOf(searchTerm) !== -1 || desc.indexOf(searchTerm) !== -1;
                            row.style.display = show ? '' : 'none';
                        });
                        
                        // Sort
                        var visibleRows = rows.filter(function(r) { return r.style.display !== 'none'; });
                        if (sortValue) {
                            visibleRows.sort(function(a, b) {
                                var nameA = a.querySelector('.cat-name')?.textContent || '';
                                var nameB = b.querySelector('.cat-name')?.textContent || '';
                                var dateA = a.querySelector('.cat-date')?.textContent || '';
                                var dateB = b.querySelector('.cat-date')?.textContent || '';
                                
                                switch(sortValue) {
                                    case 'az': return nameA.localeCompare(nameB);
                                    case 'za': return nameB.localeCompare(nameA);
                                    case 'newest': return new Date(dateB) - new Date(dateA);
                                    case 'oldest': return new Date(dateA) - new Date(dateB);
                                    default: return 0;
                                }
                            });
                            visibleRows.forEach(function(row) { tbody.appendChild(row); });
                        }
                    }
                    
                    function editCategory(id, name, description) {
                        document.getElementById('categoryModalTitle').textContent = 'Edit Category';
                        document.getElementById('categoryId').value = id;
                        document.getElementById('catName').value = name;
                        document.getElementById('catDescription').value = description;
                        document.getElementById('categoryModal').classList.add('show');
                    }
                    
                    function saveCategory(e) {
                        e.preventDefault();
                        var id = document.getElementById('categoryId').value;
                        var name = document.getElementById('catName').value.trim();
                        var description = document.getElementById('catDescription').value.trim();
                        
                        if (!name) {
                            alert('Category name is required');
                            return;
                        }
                        
                        var data = new FormData();
                        data.append('id', id);
                        data.append('name', name);
                        data.append('description', description);
                        
                        fetch('?action=' + (id ? 'update_category' : 'add_category'), { method: 'POST', body: data })
                        .then(function(res) { return res.json(); })
                        .then(function(result) {
                            if (result.success) {
                                alert('✅ Category saved successfully!');
                                closeModal('categoryModal');
                                location.reload();
                            } else {
                                alert('❌ Error: ' + (result.message || 'Unknown error'));
                            }
                        })
                        ['catch'](function(error) {
                            alert('❌ Error: ' + error.message);
                        });
                    }
                    
                    function deleteCategory(id) {
                        customConfirm('Delete this category? Products using this category will remain uncategorized.', function() {
                            fetch('?action=delete_category&id=' + id)
                            .then(function(res) { return res.json(); })
                            .then(function(result) {
                                if (result.success) {
                                    alert('✅ Category deleted!');
                                    location.reload();
                                } else {
                                    alert('❌ Error: ' + result.message);
                                }
                            });
                        }, 'danger');
                    }
                    
                    function closeModal(id) {
                        document.getElementById(id).classList.remove('show');
                    }
                    
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
