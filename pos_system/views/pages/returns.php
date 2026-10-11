<?php
switch ('returns'):
case 'returns':

    if (!canAccess('returns') && !canAccess('returns_create') && !canAccess('returns_view')) {
        echo '<div class="alert alert-danger" style="margin:2rem;text-align:center;">
            <div style="font-size:4rem;margin-bottom:1rem;">⛔</div>
            <h2>Access Denied</h2>
            <p>You do not have permission to access this page.</p>
            <a href="?page=dashboard" class="btn btn-primary" style="margin-top:1rem;">Go to Dashboard</a>
        </div>';
        break;
    }
    
    $returns = $returnManager->getAllReturns();
    $returnStats = $returnManager->getReturnStats();
    $canApprove = canAccess('returns_approve') || isAdmin();
    $canCreate = canAccess('returns_create') || isAdmin();
    $return_hours = getSetting('return_hours', 20);
    ?>
    
    <!-- ============================================ -->
    <!-- RETURNS PAGE - MODERN INTERFACE -->
    <!-- ============================================ -->
    
    <!-- Page Header -->
    <div class="returns-page-header">
        <div class="returns-page-header-left">
            <h2 class="returns-page-title">
                <span class="returns-page-icon">🔄</span>
                Returns & Refunds
            </h2>
            <p class="returns-page-subtitle">Manage product returns and refund requests</p>
        </div>
        <div class="returns-page-header-actions">
            <span class="returns-time-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Return window: <?php echo $return_hours; ?> hours
            </span>
            <?php if ($canCreate): ?>
            <button class="returns-btn-primary no-print" onclick="showCreateReturn()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                New Return
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Stats Grid -->
    <div class="returns-stats-grid">
        
        <div class="returns-stat-card">
            <div class="returns-stat-icon" style="background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
            </div>
            <div class="returns-stat-label">Total Returns</div>
            <div class="returns-stat-value"><?php echo $returnStats['total']; ?></div>
            <div class="returns-stat-footer">All-time returns</div>
        </div>
        
        <div class="returns-stat-card <?php echo $returnStats['pending'] > 0 ? 'returns-stat-warning' : ''; ?>">
            <div class="returns-stat-icon" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div class="returns-stat-label">Pending</div>
            <div class="returns-stat-value"><?php echo $returnStats['pending']; ?></div>
            <div class="returns-stat-footer">Needs approval</div>
        </div>
        
        <div class="returns-stat-card">
            <div class="returns-stat-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div class="returns-stat-label">Completed</div>
            <div class="returns-stat-value"><?php echo $returnStats['completed']; ?></div>
            <div class="returns-stat-footer">Successfully refunded</div>
        </div>
        
        <div class="returns-stat-card">
            <div class="returns-stat-icon" style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            </div>
            <div class="returns-stat-label">Rejected</div>
            <div class="returns-stat-value"><?php echo $returnStats['rejected']; ?></div>
            <div class="returns-stat-footer">Declined requests</div>
        </div>
        
        <div class="returns-stat-card">
            <div class="returns-stat-icon" style="background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
            <div class="returns-stat-label">Total Refunded</div>
            <div class="returns-stat-value">₱<?php echo number_format($returnStats['total_refunded'], 2); ?></div>
            <div class="returns-stat-footer">Avg: ₱<?php echo number_format($returnStats['avg_refund'], 2); ?></div>
        </div>
        
    </div>
    
    <!-- Returns Table Card -->
    <div class="returns-table-card">
        
        <!-- Table Header + Filters -->
        <div class="returns-table-header">
            <div>
                <div class="returns-table-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    Return Requests
                </div>
                <div class="returns-table-subtitle"><?php echo count($returns); ?> total records</div>
            </div>
            <div class="returns-filter-group">
                <button class="returns-filter-btn active" onclick="filterReturns('all', this)">All</button>
                <button class="returns-filter-btn" onclick="filterReturns('pending', this)">Pending</button>
                <button class="returns-filter-btn" onclick="filterReturns('completed', this)">Completed</button>
                <button class="returns-filter-btn" onclick="filterReturns('rejected', this)">Rejected</button>
            </div>
        </div>
        
        <!-- Returns Table -->
        <?php if (!empty($returns)): ?>
        <div class="returns-table-wrap">
            <table class="returns-table">
                <thead>
                    <tr>
                        <th>Return #</th>
                        <th>Original Sale</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Date</th>
                        <th class="no-print">Actions</th>
                    </tr>
                </thead>
                <tbody id="returnsTableBody">
                    <?php foreach ($returns as $r): ?>
                    <tr data-status="<?php echo $r['status']; ?>">
                        <td>
                            <span class="returns-invoice"><?php echo htmlspecialchars($r['return_number']); ?></span>
                        </td>
                        <td>
                            <span class="returns-sale"><?php echo htmlspecialchars($r['original_invoice'] ?? 'N/A'); ?></span>
                        </td>
                        <td>
                            <div class="returns-customer">
                                <div class="returns-customer-avatar"><?php echo strtoupper(substr($r['customer_name'] ?? 'W', 0, 1)); ?></div>
                                <span><?php echo htmlspecialchars($r['customer_name'] ?? 'Walk-in'); ?></span>
                            </div>
                        </td>
                        <td>
                            <strong class="returns-amount">₱<?php echo number_format($r['total_refund'], 2); ?></strong>
                        </td>
                        <td>
                            <span class="returns-reason"><?php echo htmlspecialchars($r['reason']); ?></span>
                        </td>
                        <td>
                            <?php 
                            $status = $r['status'];
                            $status_class = 'returns-badge-' . $status;
                            $status_icons = [
                                'pending' => '⏳',
                                'approved' => '✅',
                                'completed' => '✅',
                                'rejected' => '❌'
                            ];
                            $status_icon = $status_icons[$status] ?? '📋';
                            ?>
                            <span class="returns-badge <?php echo $status_class; ?>">
                                <?php echo $status_icon; ?> <?php echo ucfirst($status); ?>
                            </span>
                        </td>
                        <td>
                            <span class="returns-created-by"><?php echo htmlspecialchars($r['created_by_name'] ?? 'N/A'); ?></span>
                        </td>
                        <td>
                            <span class="returns-date"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></span>
                        </td>
                        <td class="no-print">
                            <div class="returns-actions">
                                <button class="returns-action-btn returns-action-view" onclick="viewReturn(<?php echo $r['id']; ?>)" title="View details">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                                <?php if ($r['status'] == 'pending' && $canApprove): ?>
                                <button class="returns-action-btn returns-action-approve" onclick="approveReturn(<?php echo $r['id']; ?>)" title="Approve">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </button>
                                <button class="returns-action-btn returns-action-reject" onclick="rejectReturn(<?php echo $r['id']; ?>)" title="Reject">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="returns-empty-state">
            <div class="returns-empty-icon">🔄</div>
            <div class="returns-empty-title">No returns yet</div>
            <div class="returns-empty-text">When customers return products, they'll appear here</div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- ============================================ -->
    <!-- CREATE RETURN MODAL -->
    <!-- ============================================ -->
    <div class="modal" id="createReturnModal">
        <div class="modal-content returns-modal">
            
            <div class="returns-modal-header">
                <div class="returns-modal-header-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                </div>
                <div class="returns-modal-header-text">
                    <h2>Create Return Request</h2>
                    <p>Process a customer return for an existing sale</p>
                </div>
                <button class="returns-modal-close" onclick="closeModal('createReturnModal')">&times;</button>
            </div>
            
            <!-- Time Window Info -->
            <div class="returns-info-banner">
                <div class="returns-info-icon">⏰</div>
                <div class="returns-info-content">
                    <strong>Return Time Limit: <?php echo $return_hours; ?> Hours</strong>
                    <p>Customers can return items within <?php echo $return_hours; ?> hours of purchase.</p>
                </div>
            </div>
            
            <form id="createReturnForm" onsubmit="submitReturn(event)">
                
                <!-- Select Sale -->
                <div class="returns-form-group">
                    <label class="returns-form-label">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        </svg>
                        Select Sale
                    </label>
                    <select id="returnSaleSelect" required onchange="loadSaleForReturn()" class="returns-form-select">
                        <option value="">Select a sale...</option>
                    </select>
                </div>
                
                <!-- Sale Details -->
                <div id="returnSaleDetails" style="display:none;">
                    
                    <div class="returns-form-group">
                        <label class="returns-form-label">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Customer
                        </label>
                        <div id="returnCustomerDisplay" class="returns-form-static"></div>
                    </div>
                    
                    <div class="returns-form-group">
                        <label class="returns-form-label">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                <line x1="12" y1="22.08" x2="12" y2="12"></line>
                            </svg>
                            Items to Return
                        </label>
                        <div id="returnItemsList" class="returns-items-list"></div>
                    </div>
                    
                    <div class="returns-form-group">
                        <label class="returns-form-label">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                            Reason for Return
                        </label>
                        <select id="returnReason" required class="returns-form-select">
                            <option value="">Select reason...</option>
                            <option value="defective">Defective Product</option>
                            <option value="wrong_item">Wrong Item Sent</option>
                            <option value="damaged">Damaged in Transit</option>
                            <option value="customer_request">Customer Changed Mind</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="returns-form-group">
                        <label class="returns-form-label">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            Notes (Optional)
                        </label>
                        <textarea id="returnNotes" rows="3" placeholder="Additional notes..." class="returns-form-textarea"></textarea>
                    </div>
                    
                    <!-- Total Refund Display -->
                    <div class="returns-refund-summary">
                        <div class="returns-refund-label">Total Refund</div>
                        <div class="returns-refund-value" id="returnTotalDisplay">₱0.00</div>
                    </div>
                </div>
                
                <div class="returns-modal-actions">
                    <button type="button" class="returns-btn-cancel" onclick="closeModal('createReturnModal')">
                        Cancel
                    </button>
                    <button type="submit" class="returns-btn-submit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Submit Return Request
                    </button>
                </div>
                
            </form>
        </div>
    </div>
    
    <!-- ============================================ -->
    <!-- VIEW RETURN MODAL -->
    <!-- ============================================ -->
    <div class="modal" id="viewReturnModal">
        <div class="modal-content returns-view-modal">
            
            <div class="returns-modal-header">
                <div class="returns-modal-header-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </div>
                <div class="returns-modal-header-text">
                    <h2 id="viewReturnTitle">Return Details</h2>
                    <p>View complete return information</p>
                </div>
                <button class="returns-modal-close" onclick="closeModal('viewReturnModal')">&times;</button>
            </div>
            
            <div id="viewReturnContent"></div>
            
        </div>
    </div>

    <!-- ============================================ -->
    <!-- REJECT RETURN MODAL - GLASS UI               -->
    <!-- ============================================ -->
    <div class="modal" id="rejectReturnModal">
        <div class="modal-content" style="max-width:480px;padding:28px;border-radius:24px;background:rgba(255,255,255,0.88);backdrop-filter:blur(40px) saturate(180%);-webkit-backdrop-filter:blur(40px) saturate(180%);border:1px solid rgba(255,255,255,0.6);box-shadow:0 30px 80px rgba(30,27,75,0.22),0 12px 32px rgba(30,27,75,0.12),inset 0 1px 0 rgba(255,255,255,0.9);">
            
            <!-- Header -->
            <div style="display:flex;align-items:center;gap:14px;padding-bottom:18px;margin-bottom:20px;border-bottom:1px solid #F3F4F6;">
                <div style="width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,#EF4444 0%,#DC2626 100%);color:white;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 6px 16px rgba(239,68,68,0.3);">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </div>
                <div style="flex:1;min-width:0;">
                    <h2 style="font-size:20px;font-weight:800;color:#111827;letter-spacing:-0.02em;margin:0 0 2px 0;">Reject Return</h2>
                    <p style="font-size:13px;color:#6B7280;margin:0;">Provide a reason for rejection</p>
                </div>
                <button onclick="closeModal('rejectReturnModal')" style="width:32px;height:32px;border-radius:8px;background:transparent;border:none;color:#9CA3AF;font-size:22px;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;transition:all 0.2s;" onmouseover="this.style.background='#FEE2E2';this.style.color='#EF4444';this.style.transform='rotate(90deg)';" onmouseout="this.style.background='transparent';this.style.color='#9CA3AF';this.style.transform='rotate(0deg)';">&times;</button>
            </div>
            
            <!-- Info banner -->
            <div style="display:flex;gap:10px;padding:12px 14px;background:linear-gradient(135deg,#FEF2F2 0%,#FEE2E2 100%);border:1px solid #FECACA;border-radius:12px;margin-bottom:20px;">
                <div style="font-size:18px;flex-shrink:0;line-height:1;">⚠️</div>
                <div style="flex:1;font-size:12.5px;color:#991B1B;line-height:1.5;font-weight:500;">
                    This action will reject the return. The inventory will <strong>not</strong> be restored and no refund will be issued.
                </div>
            </div>
            
            <!-- Input -->
            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:12.5px;font-weight:700;color:#374151;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.04em;">Reason for Rejection *</label>
                <textarea id="rejectReasonInput" rows="3" placeholder="e.g., Item was not defective, or return period expired..." style="width:100%;padding:13px 16px;border:2px solid #E5E7EB;border-radius:12px;font-size:14px;font-family:'Inter',sans-serif;font-weight:500;color:#111827;background:rgba(255,255,255,0.7);outline:none;transition:all 0.25s;resize:vertical;box-sizing:border-box;" onfocus="this.style.borderColor='#EF4444';this.style.boxShadow='0 0 0 4px rgba(239,68,68,0.1)';" onblur="this.style.borderColor='#E5E7EB';this.style.boxShadow='none';"></textarea>
                <div style="font-size:11.5px;color:#9CA3AF;margin-top:6px;font-weight:500;">This will be visible in the return record.</div>
            </div>
            
            <!-- Action buttons -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;padding-top:16px;border-top:1px solid #F3F4F6;">
                <button type="button" onclick="closeModal('rejectReturnModal')" style="padding:14px;background:#F3F4F6;color:#4B5563;border:1.5px solid #E5E7EB;border-radius:12px;font-weight:700;font-size:14px;font-family:'Inter',sans-serif;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.background='#E5E7EB';" onmouseout="this.style.background='#F3F4F6';">
                    Cancel
                </button>
                <button type="button" onclick="confirmRejectReturn()" style="padding:14px;background:linear-gradient(135deg,#EF4444 0%,#DC2626 100%);color:white;border:none;border-radius:12px;font-weight:700;font-size:14px;font-family:'Inter',sans-serif;cursor:pointer;box-shadow:0 8px 20px rgba(220,38,38,0.3);transition:all 0.2s;display:flex;align-items:center;justify-content:center;gap:8px;" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 12px 28px rgba(220,38,38,0.4)';" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 8px 20px rgba(220,38,38,0.3)';">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Reject Return
                </button>
            </div>
            
        </div>
    </div>
    
    <script>
        // ===== RETURNS JAVASCRIPT (Functions unchanged from your existing code) =====
        var returnItems = [];
        
        function loadSalesForReturn() {
            var select = document.getElementById('returnSaleSelect');
            select.innerHTML = '<option value="">Loading sales...</option>';
            
            fetch('?action=get_sales&limit=50')
            .then(function(res){ return res.json(); })
            .then(function(sales){
                select.innerHTML = '<option value="">Select a sale...</option>';
                
                if (sales.length === 0) {
                    select.innerHTML = '<option value="">No sales available</option>';
                    return;
                }
                
                var hasReturnableSales = false;
                var processedCount = 0;
                
                sales.forEach(function(s){
                    fetch('?action=check_sale_returned&sale_id=' + s.id)
                    .then(function(res){ return res.json(); })
                    .then(function(data){
                        processedCount++;
                        
                        if (!data.returned) {
                            hasReturnableSales = true;
                            var opt = document.createElement('option');
                            opt.value = s.id;
                            opt.textContent = s.invoice_number + ' - ₱' + parseFloat(s.total_amount).toFixed(2) + ' - ' + (s.customer || 'Walk-in');
                            select.appendChild(opt);
                        }
                        
                        if (processedCount === sales.length && !hasReturnableSales) {
                            select.innerHTML = '<option value="">No returnable sales available</option>';
                        }
                    });
                });
            })
            ['catch'](function(error){
                console.log('Error loading sales:', error);
                select.innerHTML = '<option value="">Error loading sales</option>';
            });
        }
        
        function loadSaleForReturn() {
            var saleId = document.getElementById('returnSaleSelect').value;
            var detailsDiv = document.getElementById('returnSaleDetails');
            var itemsList = document.getElementById('returnItemsList');
            
            if (!saleId) {
                detailsDiv.style.display = 'none';
                return;
            }
            
            fetch('?action=get_sale&id=' + saleId)
                .then(function(res) { return res.json(); })
                .then(function(sale) {
                    detailsDiv.style.display = 'block';
                    document.getElementById('returnCustomerDisplay').textContent = sale.customer || 'Walk-in Customer';
                    
                    var html = '<table class="returns-items-table"><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Return Qty</th><th>Refund</th></tr></thead><tbody>';
                    returnItems = [];
                    var totalRefund = 0;
                    
                    sale.items.forEach(function(item) {
                        var maxQty = item.quantity;
                        html += '<tr>';
                        html += '<td><strong>' + item.product_name + '</strong></td>';
                        html += '<td>' + item.quantity + '</td>';
                        html += '<td>₱' + parseFloat(item.unit_price).toFixed(2) + '</td>';
                        html += '<td><input type="number" class="return-qty" data-product="' + item.product_id + '" data-price="' + parseFloat(item.unit_price).toFixed(2) + '" value="' + maxQty + '" min="0" max="' + maxQty + '" onchange="updateReturnTotal()"></td>';
                        html += '<td><span class="refund-amount" id="refund_' + item.product_id + '">₱' + (maxQty * parseFloat(item.unit_price)).toFixed(2) + '</span></td>';
                        html += '</tr>';
                        
                        returnItems.push({
                            product_id: item.product_id,
                            quantity: maxQty,
                            refund_amount: maxQty * parseFloat(item.unit_price),
                            unit_price: parseFloat(item.unit_price)
                        });
                        totalRefund += maxQty * parseFloat(item.unit_price);
                    });
                    
                    html += '</tbody></table>';
                    itemsList.innerHTML = html;
                    document.getElementById('returnTotalDisplay').textContent = '₱' + totalRefund.toFixed(2);
                });
        }
        
        function updateReturnTotal() {
            var inputs = document.querySelectorAll('.return-qty');
            var total = 0;
            var index = 0;
            
            inputs.forEach(function(input) {
                var qty = parseInt(input.value) || 0;
                var price = parseFloat(input.dataset.price) || 0;
                var refund = qty * price;
                var productId = input.dataset.product;
                var refundDisplay = document.getElementById('refund_' + productId);
                if (refundDisplay) {
                    refundDisplay.textContent = '₱' + refund.toFixed(2);
                }
                total += refund;
                
                if (returnItems[index]) {
                    returnItems[index].quantity = qty;
                    returnItems[index].refund_amount = refund;
                }
                index++;
            });
            
            document.getElementById('returnTotalDisplay').textContent = '₱' + total.toFixed(2);
        }
        
        function submitReturn(e) {
            e.preventDefault();
            var saleId = document.getElementById('returnSaleSelect').value;
            var reason = document.getElementById('returnReason').value;
            var notes = document.getElementById('returnNotes').value;
            
            if (!saleId) {
                alert('Please select a sale.');
                return;
            }
            if (!reason) {
                alert('Please select a reason for return.');
                return;
            }
            
            var items = [];
            var inputs = document.querySelectorAll('.return-qty');
            var hasItems = false;
            
            inputs.forEach(function(input) {
                var qty = parseInt(input.value) || 0;
                if (qty > 0) {
                    hasItems = true;
                    items.push({
                        product_id: parseInt(input.dataset.product),
                        quantity: qty,
                        refund_amount: qty * parseFloat(input.dataset.price),
                        item_reason: reason
                    });
                }
            });
            
            if (!hasItems) {
                alert('Please select at least one item to return.');
                return;
            }
            
            var data = new FormData();
            data.append('sale_id', saleId);
            data.append('items', JSON.stringify(items));
            data.append('reason', reason);
            data.append('notes', notes);
            
            var btn = e.target.querySelector('button[type="submit"]');
            var originalText = btn.innerHTML;
            btn.innerHTML = 'Submitting...';
            btn.disabled = true;
            
            fetch('?action=create_return', {
                method: 'POST',
                body: data
            })
            .then(function(res) { return res.json(); })
            .then(function(result) {
                btn.innerHTML = originalText;
                btn.disabled = false;
                
                if (result.success) {
                    alert('✅ Return request submitted!\nReturn #: ' + result.return_number);
                    closeModal('createReturnModal');
                    location.reload();
                } else {
                    alert('❌ Error: ' + (result.message || 'Unknown error'));
                }
            })
            ['catch'](function(error) {
                btn.innerHTML = originalText;
                btn.disabled = false;
                alert('❌ Error: ' + error.message);
            });
        }
        
        function showCreateReturn() {
            document.getElementById('createReturnModal').classList.add('show');
            loadSalesForReturn();
            document.getElementById('returnSaleDetails').style.display = 'none';
            document.getElementById('returnReason').value = '';
            document.getElementById('returnNotes').value = '';
            document.getElementById('returnTotalDisplay').textContent = '₱0.00';
        }
        
        function viewReturn(id) {
            fetch('?action=get_return&id=' + id)
                .then(function(res) { return res.json(); })
                .then(function(returnData) {
                    var content = document.getElementById('viewReturnContent');
                    var title = document.getElementById('viewReturnTitle');
                    title.textContent = 'Return #' + returnData.return_number;
                    
                    var statusMap = {
                        'pending': { class: 'returns-badge-pending', icon: '⏳', label: 'Pending' },
                        'approved': { class: 'returns-badge-approved', icon: '✅', label: 'Approved' },
                        'completed': { class: 'returns-badge-completed', icon: '✅', label: 'Completed' },
                        'rejected': { class: 'returns-badge-rejected', icon: '❌', label: 'Rejected' }
                    };
                    var status = statusMap[returnData.status] || { class: 'returns-badge-pending', icon: '📋', label: returnData.status };
                    
                    var html = '<div class="returns-view-grid">';
                    html += '<div class="returns-view-item"><div class="returns-view-label">Return Number</div><div class="returns-view-value"><strong>' + returnData.return_number + '</strong></div></div>';
                    html += '<div class="returns-view-item"><div class="returns-view-label">Status</div><div class="returns-view-value"><span class="returns-badge ' + status.class + '">' + status.icon + ' ' + status.label + '</span></div></div>';
                    html += '<div class="returns-view-item"><div class="returns-view-label">Original Sale</div><div class="returns-view-value">' + (returnData.original_invoice || 'N/A') + '</div></div>';
                    html += '<div class="returns-view-item"><div class="returns-view-label">Created By</div><div class="returns-view-value">' + (returnData.created_by_name || 'N/A') + '</div></div>';
                    html += '<div class="returns-view-item"><div class="returns-view-label">Reason</div><div class="returns-view-value"><span class="returns-reason-badge">' + returnData.reason + '</span></div></div>';
                    html += '<div class="returns-view-item"><div class="returns-view-label">Total Refund</div><div class="returns-view-value returns-view-total">₱' + parseFloat(returnData.total_refund).toFixed(2) + '</div></div>';
                    if (returnData.notes) {
                        html += '<div class="returns-view-item" style="grid-column: 1 / -1;"><div class="returns-view-label">Notes</div><div class="returns-view-value">' + returnData.notes + '</div></div>';
                    }
                    html += '</div>';
                    
                    html += '<div class="returns-view-items-title">Returned Items</div>';
                    html += '<table class="returns-items-table"><thead><tr><th>Product</th><th>Quantity</th><th>Refund Amount</th></tr></thead><tbody>';
                    if (returnData.items) {
                        returnData.items.forEach(function(item) {
                            html += '<tr><td><strong>' + item.product_name + '</strong></td><td>' + item.quantity + '</td><td>₱' + parseFloat(item.refund_amount).toFixed(2) + '</td></tr>';
                        });
                    }
                    html += '</tbody></table>';
                    
                    content.innerHTML = html;
                    document.getElementById('viewReturnModal').classList.add('show');
                });
        }
        
        function approveReturn(id) {
            customConfirm(
                'Approve this return request? The refund will be processed and inventory will be restored.',
                function() {
                    var data = new FormData();
                    data.append('return_id', id);
                    
                    if (window.showToast) {
                        showToast('info', 'Processing', 'Approving return...', 2000);
                    }
                    
                    fetch('?action=approve_return', {
                        method: 'POST',
                        body: data
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(result) {
                        if (result.success) {
                            if (window.showToast) {
                                showToast('success', 'Return Approved', 'Inventory restored successfully.', 3000);
                            }
                            setTimeout(function() { location.reload(); }, 1000);
                        } else {
                            if (window.showToast) {
                                showToast('error', 'Failed', result.message || 'Could not approve return.', 4000);
                            } else {
                                alert('❌ Error: ' + (result.message || 'Failed to approve'));
                            }
                        }
                    })
                    .catch(function(err) {
                        if (window.showToast) {
                            showToast('error', 'Network Error', err.message, 4000);
                        } else {
                            alert('❌ Error: ' + err.message);
                        }
                    });
                },
                'success'  // Green icon for approval
            );
        }
        
        // Track which return is being rejected
var currentRejectReturnId = null;

    function rejectReturn(id) {
        currentRejectReturnId = id;
        document.getElementById('rejectReasonInput').value = '';
        document.getElementById('rejectReturnModal').classList.add('show');
        
        setTimeout(function() {
            document.getElementById('rejectReasonInput').focus();
        }, 200);
    }

    function confirmRejectReturn() {
        var reason = document.getElementById('rejectReasonInput').value.trim();
        if (!reason) {
            if (window.showToast) {
                showToast('warning', 'Missing Reason', 'Please provide a reason for rejection.', 3000);
            } else {
                alert('Please provide a reason for rejection.');
            }
            document.getElementById('rejectReasonInput').focus();
            return;
        }
        
        var id = currentRejectReturnId;
        if (!id) return;
        
        closeModal('rejectReturnModal');
        
        if (window.showToast) {
            showToast('info', 'Processing', 'Rejecting return...', 2000);
        }
        
        var data = new FormData();
        data.append('return_id', id);
        data.append('reason', reason);
        
        fetch('?action=reject_return', {
            method: 'POST',
            body: data
        })
        .then(function(res) { return res.json(); })
        .then(function(result) {
            if (result.success) {
                if (window.showToast) {
                    showToast('success', 'Return Rejected', 'The return has been rejected.', 3000);
                }
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                if (window.showToast) {
                    showToast('error', 'Failed', result.message || 'Could not reject return.', 4000);
                } else {
                    alert('❌ Error: ' + (result.message || 'Failed to reject'));
                }
            }
        })
        .catch(function(err) {
            if (window.showToast) {
                showToast('error', 'Network Error', err.message, 4000);
            } else {
                alert('❌ Error: ' + err.message);
            }
        });
    }
        
        // NEW: Filter returns by status (client-side, no reload)
        function filterReturns(status, btn) {
            document.querySelectorAll('.returns-filter-btn').forEach(function(b) { b.classList.remove('active'); });
            if (btn) btn.classList.add('active');
            
            var rows = document.querySelectorAll('#returnsTableBody tr');
            rows.forEach(function(row) {
                var rowStatus = row.getAttribute('data-status');
                if (status === 'all' || rowStatus === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
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
    

            // ============================================
            // ALL OTHER PAGES - FULLY PRESERVED
            // ============================================
break;
endswitch;
