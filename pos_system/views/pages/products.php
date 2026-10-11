<?php
switch ('products'):
case 'products':

                $products = $productManager->getAllProducts(false);
                $categories = $categoryManager->getAllCategories();
                $isAdmin = isAdmin();
                ?>
                
                <!-- ============================================ -->
                <!-- PRODUCTS PAGE - MODERN INTERFACE -->
                <!-- ============================================ -->
                
                <!-- Page Header -->
                <div class="prod-page-header">
                    <div class="prod-page-header-left">
                        <h2 class="prod-page-title">
                            <span class="prod-page-icon">📦</span>
                            Product Management
                        </h2>
                        <p class="prod-page-subtitle">Manage your inventory and product catalog</p>
                    </div>
                    <?php if ($isAdmin): ?>
                    <div class="prod-page-header-actions">
                        <a href="?page=archive" class="prod-btn-secondary no-print">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 8v13H3V8"></path>
                                <path d="M1 3h22v5H1z"></path>
                                <line x1="10" y1="12" x2="14" y2="12"></line>
                            </svg>
                            View Archive
                        </a>
                        <button class="prod-btn-primary no-print" onclick="showAddProduct()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Add Product
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="prod-view-only">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        View Only Mode
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Products Table Card -->
                <div class="prod-card">
                    
                    <!-- Card Header -->
                    <div class="prod-card-header">
                        <div class="prod-card-title-wrap">
                            <div class="prod-card-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 7h-9"></path>
                                    <path d="M14 17H5"></path>
                                    <circle cx="17" cy="17" r="3"></circle>
                                    <circle cx="7" cy="7" r="3"></circle>
                                </svg>
                                All Products
                            </div>
                            <div class="prod-card-subtitle">
                                <span id="productCount"><?php echo count($products); ?></span> products in inventory
                            </div>
                        </div>
                    </div>
                    
                    <!-- Search & Filter Bar -->
                    <div class="prod-search-bar">
                        <div class="prod-search-wrap">
                            <span class="prod-search-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                            </span>
                            <input type="text" id="productSearch" placeholder="Search products by name..." onkeyup="filterProducts()" class="prod-search-input">
                            <button type="button" class="prod-search-clear" onclick="clearProductSearch()" title="Clear">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                        <select id="productCategoryFilter" onchange="filterProducts()" class="prod-category-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Search Results Info -->
                    <div id="searchResultsInfo" class="prod-search-info" style="display:none;">
                        Showing <strong id="visibleCount">0</strong> of <strong id="totalCount"><?php echo count($products); ?></strong> products
                    </div>
                    
                    <!-- Products Table -->
                    <?php if (!empty($products)): ?>
                    <div class="prod-table-wrap">
                        <table class="prod-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Unit</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <?php if ($isAdmin): ?>
                                    <th class="no-print" style="text-align:right;">Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="productTableBody">
                                <?php foreach ($products as $p): ?>
                                <tr data-name="<?php echo strtolower(htmlspecialchars($p['name'])); ?>" data-category="<?php echo $p['category_id']; ?>" data-id="<?php echo $p['id']; ?>">
                                    <td>
                                        <div class="prod-product-cell">
                                            <?php if (!empty($p['image'])): ?>
                                                <div class="prod-image-thumb">
                                                    <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                                                </div>
                                            <?php else: ?>
                                                <div class="prod-image-placeholder">📦</div>
                                            <?php endif; ?>
                                            <div class="prod-product-info">
                                                <div class="prod-product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="prod-category-badge"><?php echo htmlspecialchars($p['category_name'] ?? 'Uncategorized'); ?></span>
                                    </td>
                                    <td>
                                        <span class="prod-price">₱<?php echo number_format($p['selling_price'], 2); ?></span>
                                    </td>
                                    <td>
                                        <span class="prod-unit-badge"><?php echo htmlspecialchars($p['unit'] ?? 'pc'); ?></span>
                                    </td>
                                    <td>
                                        <span class="prod-stock <?php echo $p['stock_quantity'] <= 5 ? 'prod-stock-low' : ''; ?>">
                                            <?php echo $p['stock_quantity']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($p['stock_quantity'] <= 5): ?>
                                            <span class="prod-status-badge prod-status-low">⚠️ Low Stock</span>
                                        <?php else: ?>
                                            <span class="prod-status-badge prod-status-ok">✅ In Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($isAdmin): ?>
                                    <td class="no-print" style="text-align:right;">
                                        <div class="prod-actions">
                                            <button class="prod-action-btn prod-action-edit" onclick="editProduct(<?php echo $p['id']; ?>)" title="Edit">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </button>
                                            <button class="prod-action-btn prod-action-archive" onclick="archiveProduct(<?php echo $p['id']; ?>)" title="Archive">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M21 8v13H3V8"></path>
                                                    <path d="M1 3h22v5H1z"></path>
                                                    <line x1="10" y1="12" x2="14" y2="12"></line>
                                                </svg>
                                            </button>
                                            <button class="prod-action-btn prod-action-delete" onclick="deleteProduct(<?php echo $p['id']; ?>)" title="Delete">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="prod-empty-state">
                        <div class="prod-empty-icon">📦</div>
                        <div class="prod-empty-title">No products yet</div>
                        <div class="prod-empty-text">Click "Add Product" to create your first product</div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- No Results Message -->
                    <div id="noResultsMessage" class="prod-empty-state" style="display:none;">
                        <div class="prod-empty-icon">🔍</div>
                        <div class="prod-empty-title">No products found</div>
                        <div class="prod-empty-text">Try adjusting your search or filter</div>
                    </div>
                    
                </div>
                
                <!-- ============================================ -->
                <!-- ADD/EDIT PRODUCT MODAL -->
                <!-- ============================================ -->
                <?php if ($isAdmin): ?>
                <div class="modal" id="productModal">
                    <div class="modal-content prod-modal">
                        
                        <div class="prod-modal-header">
                            <div class="prod-modal-header-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                </svg>
                            </div>
                            <div class="prod-modal-header-text">
                                <h2 id="productModalTitle">Add Product</h2>
                                <p>Fill in the product details below</p>
                            </div>
                            <button class="prod-modal-close" onclick="closeModal('productModal')">&times;</button>
                        </div>
                        
                        <form id="productForm" onsubmit="saveProduct(event)" enctype="multipart/form-data">
                            <input type="hidden" id="productId">
                            
                            <div class="prod-form-row">
                                <div class="prod-form-group prod-form-full">
                                    <label class="prod-form-label">Product Name *</label>
                                    <input type="text" id="prodName" placeholder="e.g., Laptop Pro" required class="prod-form-input">
                                </div>
                            </div>
                            
                            <div class="prod-form-row">
                                <div class="prod-form-group">
                                    <label class="prod-form-label">Category</label>
                                    <select id="prodCategory" class="prod-form-select">
                                        <option value="">No Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="prod-form-group">
                                    <label class="prod-form-label">Unit Type</label>
                                    <select id="prodUnit" class="prod-form-select">
                                        <option value="pc">Piece (pc)</option>
                                        <option value="kg">Kilogram (kg)</option>
                                        <option value="g">Gram (g)</option>
                                        <option value="L">Liter (L)</option>
                                        <option value="mL">Milliliter (mL)</option>
                                        <option value="pack">Pack</option>
                                        <option value="box">Box</option>
                                        <option value="bottle">Bottle</option>
                                        <option value="can">Can</option>
                                        <option value="tray">Tray</option>
                                        <option value="bundle">Bundle</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="prod-form-row">
                                <div class="prod-form-group">
                                    <label class="prod-form-label">Selling Price (₱) *</label>
                                    <input type="number" step="0.01" id="prodPrice" placeholder="0.00" required class="prod-form-input">
                                </div>
                                <div class="prod-form-group">
                                    <label class="prod-form-label">Stock Quantity *</label>
                                    <input type="number" id="prodStock" placeholder="0" required class="prod-form-input">
                                </div>
                            </div>
                            
                            <div class="prod-form-group prod-form-full">
                                <label class="prod-form-label">Product Image</label>
                                <div class="prod-image-upload-wrap">
                                    <div class="prod-image-preview" id="imagePreview">
                                        <img id="previewImg" src="" alt="Preview">
                                        <div class="prod-image-preview-placeholder">📦</div>
                                    </div>
                                    <div class="prod-image-upload-info">
                                        <input type="file" id="prodImage" accept="image/jpeg,image/png,image/gif,image/webp" onchange="validateImage(event)" class="prod-file-input">
                                        <label for="prodImage" class="prod-file-label">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                <polyline points="17 8 12 3 7 8"></polyline>
                                                <line x1="12" y1="3" x2="12" y2="15"></line>
                                            </svg>
                                            Choose Image
                                        </label>
                                        <span class="prod-file-hint">JPEG, PNG, GIF, WebP · Max 10MB</span>
                                    </div>
                                    <div id="uploadStatus" class="prod-upload-status"></div>
                                </div>
                            </div>
                            
                            <div class="prod-modal-actions">
                                <button type="button" class="prod-btn-cancel" onclick="closeModal('productModal')">
                                    Cancel
                                </button>
                                <button type="submit" class="prod-btn-submit">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    Save Product
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
                
                <script>
                    // ===== PRODUCTS JAVASCRIPT (Functions unchanged) =====
                    
                    function filterProducts() {
                        var searchTerm = document.getElementById('productSearch').value.toLowerCase().trim();
                        var categoryFilter = document.getElementById('productCategoryFilter').value;
                        var rows = document.querySelectorAll('#productTableBody tr');
                        var visibleCount = 0;
                        var totalCount = rows.length;
                        
                        rows.forEach(function(row) {
                            var name = row.getAttribute('data-name') || '';
                            var category = row.getAttribute('data-category') || '';
                            var show = true;
                            if (searchTerm !== '' && name.indexOf(searchTerm) === -1) { show = false; }
                            if (categoryFilter !== '' && category !== categoryFilter) { show = false; }
                            if (show) { row.style.display = ''; visibleCount++; } else { row.style.display = 'none'; }
                        });
                        
                        var noResults = document.getElementById('noResultsMessage');
                        var searchInfo = document.getElementById('searchResultsInfo');
                        var totalSpan = document.getElementById('totalCount');
                        var visibleSpan = document.getElementById('visibleCount');
                        var productCount = document.getElementById('productCount');
                        
                        if (visibleCount === 0) { 
                            noResults.style.display = 'block'; 
                            searchInfo.style.display = 'none'; 
                        } else {
                            noResults.style.display = 'none';
                            if (searchTerm !== '' || categoryFilter !== '') { 
                                searchInfo.style.display = 'block'; 
                                visibleSpan.textContent = visibleCount; 
                                totalSpan.textContent = totalCount; 
                            } else { 
                                searchInfo.style.display = 'none'; 
                            }
                        }
                        
                        if (productCount) {
                            productCount.textContent = visibleCount + ' products' + (visibleCount !== totalCount ? ' (filtered)' : ' in inventory');
                        }
                    }
                    
                    function clearProductSearch() {
                        document.getElementById('productSearch').value = '';
                        document.getElementById('productCategoryFilter').value = '';
                        filterProducts();
                        document.getElementById('productSearch').focus();
                    }
                    
                    function validateImage(event) {
                        var file = event.target.files[0];
                        var status = document.getElementById('uploadStatus');
                        var preview = document.getElementById('imagePreview');
                        var img = document.getElementById('previewImg');
                        
                        if (!file) { 
                            status.innerHTML = ''; 
                            preview.classList.remove('has-image'); 
                            return; 
                        }
                        
                        if (file.size > 10 * 1024 * 1024) { 
                            status.innerHTML = '❌ File too large! Max 10MB.'; 
                            status.className = 'prod-upload-status prod-upload-error';
                            event.target.value = ''; 
                            preview.classList.remove('has-image'); 
                            return; 
                        }
                        
                        var validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        if (validTypes.indexOf(file.type) === -1) { 
                            status.innerHTML = '❌ Invalid file type! Use JPEG, PNG, GIF, or WebP.'; 
                            status.className = 'prod-upload-status prod-upload-error';
                            event.target.value = ''; 
                            preview.classList.remove('has-image'); 
                            return; 
                        }
                        
                        status.innerHTML = '✅ ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
                        status.className = 'prod-upload-status prod-upload-success';
                        
                        var reader = new FileReader();
                        reader.onload = function() { 
                            preview.classList.add('has-image'); 
                            img.src = reader.result; 
                        }
                        reader.readAsDataURL(file);
                    }
                    
                    function showAddProduct(){
                        document.getElementById('productModalTitle').textContent = 'Add Product';
                        document.getElementById('productId').value = '';
                        document.getElementById('prodName').value = '';
                        document.getElementById('prodCategory').value = '';
                        document.getElementById('prodPrice').value = '';
                        document.getElementById('prodUnit').value = 'pc';
                        document.getElementById('prodStock').value = '';
                        document.getElementById('prodImage').value = '';
                        document.getElementById('uploadStatus').innerHTML = '';
                        document.getElementById('imagePreview').classList.remove('has-image');
                        document.getElementById('previewImg').src = '';
                        document.getElementById('productModal').classList.add('show');
                    }
                    
                    function saveProduct(e){
                        e.preventDefault();
                        var id = document.getElementById('productId').value;
                        var formData = new FormData();
                        var imageFile = document.getElementById('prodImage').files[0];
                        formData.append('id', id);
                        formData.append('name', document.getElementById('prodName').value);
                        formData.append('category_id', document.getElementById('prodCategory').value);
                        formData.append('price', document.getElementById('prodPrice').value);
                        formData.append('unit', document.getElementById('prodUnit').value);
                        formData.append('stock', document.getElementById('prodStock').value);
                        
                        if (imageFile) {
                            if (imageFile.size > 10 * 1024 * 1024) { 
                                alert('Image is too large! Please use an image under 10MB.'); 
                                return; 
                            }
                            formData.append('image', imageFile);
                        }
                        
                        var btn = e.target.querySelector('button[type="submit"]');
                        var originalText = btn.innerHTML;
                        btn.innerHTML = 'Saving...';
                        btn.disabled = true;
                        
                        fetch('?action=' + (id ? 'update_product' : 'add_product'), { method: 'POST', body: formData })
                        .then(function(response) { 
                            if (!response.ok) { throw new Error('Server error: ' + response.status); } 
                            return response.json(); 
                        })
                        .then(function(result) {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                            if (result.success) { 
                                alert('✅ Product saved successfully!'); 
                                closeModal('productModal'); 
                                location.reload(); 
                            } else { 
                                alert('Error: ' + (result.message || 'Unknown error')); 
                            }
                        })
                        ['catch'](function(error) { 
                            btn.innerHTML = originalText; 
                            btn.disabled = false; 
                            alert('Error: ' + error.message); 
                        });
                    }
                    
                    function editProduct(id){
                        fetch('?action=get_product&id=' + id)
                        .then(function(res) { return res.json(); })
                        .then(function(product) {
                            document.getElementById('productModalTitle').textContent = 'Edit Product';
                            document.getElementById('productId').value = product.id;
                            document.getElementById('prodName').value = product.name;
                            document.getElementById('prodCategory').value = product.category_id || '';
                            document.getElementById('prodPrice').value = product.selling_price;
                            document.getElementById('prodUnit').value = product.unit || 'pc';
                            document.getElementById('prodStock').value = product.stock_quantity;
                            
                            if (product.image) { 
                                document.getElementById('imagePreview').classList.add('has-image'); 
                                document.getElementById('previewImg').src = product.image; 
                            } else { 
                                document.getElementById('imagePreview').classList.remove('has-image'); 
                            }
                            
                            document.getElementById('uploadStatus').innerHTML = '';
                            document.getElementById('prodImage').value = '';
                            document.getElementById('productModal').classList.add('show');
                        });
                    }
                    
                    function deleteProduct(id){ 
                        customConfirm('Delete this product permanently?', function() {
                            fetch('?action=delete_product&id=' + id)
                            .then(function(res) { return res.json(); })
                            .then(function(result){ 
                                if(result.success){ 
                                    alert('✅ Product deleted!'); 
                                    location.reload(); 
                                } 
                            }); 
                        }, 'danger'); 
                    }
                    
                    function archiveProduct(id){ 
                        customConfirm('Archive this product?', function() {
                            fetch('?action=archive_product&id=' + id)
                            .then(function(res) { return res.json(); })
                            .then(function(result){ 
                                if(result.success){ 
                                    alert('✅ Product archived!'); 
                                    location.reload(); 
                                } 
                            }); 
                        }, 'warning'); 
                    }
                    
                    function closeModal(id){ document.getElementById(id).classList.remove('show'); }
                    
                    // Prevent enter key from submitting in search
                    document.getElementById('productSearch').addEventListener('keydown', function(e) { 
                        if (e.key === 'Enter') { e.preventDefault(); } 
                    });
                </script>
                
                <?php
break;
endswitch;
