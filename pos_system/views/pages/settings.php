<?php
switch ('settings'):
case 'settings':

                if (!isAdmin()) {
                    echo '<div class="alert alert-danger">⛔ Access Denied. Admin only.</div>';
                    break;
                }
                
                // Handle settings save
                if (isset($_POST['save_settings'])) {
                    error_log('SETTINGS POST DATA: ' . print_r($_POST, true));
                    $success = true;
                    $message = 'Settings saved successfully!';
                    
                    try {
                                                // Save settings with safe fallbacks
                        if (isset($_POST['store_name']) && trim($_POST['store_name']) !== '') {
                            setSetting('store_name', trim($_POST['store_name']));
                        }
                        if (isset($_POST['tax_rate']) && trim($_POST['tax_rate']) !== '') {
                            setSetting('tax_rate', trim($_POST['tax_rate']));
                        }
                        setSetting('store_address', isset($_POST['store_address']) ? trim($_POST['store_address']) : '');
                        setSetting('store_contact', isset($_POST['store_contact']) ? trim($_POST['store_contact']) : '');
                        setSetting('vat_reg_number', isset($_POST['vat_reg_number']) ? trim($_POST['vat_reg_number']) : '');
                        setSetting('emailjs_service_id', isset($_POST['emailjs_service_id']) ? trim($_POST['emailjs_service_id']) : '');
                        setSetting('emailjs_template_id', isset($_POST['emailjs_template_id']) ? trim($_POST['emailjs_template_id']) : '');
                        setSetting('emailjs_public_key', isset($_POST['emailjs_public_key']) ? trim($_POST['emailjs_public_key']) : '');
                        
                        if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
                            $uploadDir = 'uploads/logo/';
                            
                            if (!is_dir($uploadDir)) {
                                if (!mkdir($uploadDir, 0777, true)) {
                                    throw new Exception('Failed to create upload directory');
                                }
                            }
                            
                            if ($_FILES['store_logo']['size'] > 5 * 1024 * 1024) {
                                throw new Exception('Logo file too large. Max 5MB allowed.');
                            }
                            
                            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mimeType = finfo_file($finfo, $_FILES['store_logo']['tmp_name']);
                            finfo_close($finfo);
                            
                            if (!in_array($mimeType, $allowedTypes)) {
                                throw new Exception('Invalid file type. Please upload JPEG, PNG, GIF, or WebP.');
                            }
                            
                            $oldLogo = getSetting('store_logo', '');
                            if (!empty($oldLogo) && file_exists($oldLogo)) {
                                @unlink($oldLogo);
                            }
                            
                            $fileExtension = pathinfo($_FILES['store_logo']['name'], PATHINFO_EXTENSION);
                            $fileName = 'logo_' . time() . '.' . $fileExtension;
                            $targetPath = $uploadDir . $fileName;
                            
                            if (move_uploaded_file($_FILES['store_logo']['tmp_name'], $targetPath)) {
                                setSetting('store_logo', $targetPath);
                                $message = 'Settings saved with new logo!';
                            } else {
                                throw new Exception('Failed to upload logo file.');
                            }
                        }
                        
                        logActivity('update_settings', "Updated settings (Tax: {$_POST['tax_rate']}%)");
                        $settings_saved = true;
                        
                    } catch (Exception $e) {
                        $settings_error = $e->getMessage();
                        $success = false;
                    }
                }
                
                $current_tax = getSetting('tax_rate', 12);
                $store_address = getSetting('store_address', '');
                $store_contact = getSetting('store_contact', '');
                $vat_reg_number = getSetting('vat_reg_number', '');
                $store_logo = getSetting('store_logo', '');
                $emailjs_service_id = getSetting('emailjs_service_id', '');
                $emailjs_template_id = getSetting('emailjs_template_id', '');
                $emailjs_public_key = getSetting('emailjs_public_key', '');
                ?>
                
                <!-- ============================================ -->
                <!-- SETTINGS PAGE - MODERN INTERFACE -->
                <!-- ============================================ -->
                
                <!-- Page Header -->
                <div class="set-page-header">
                    <div class="set-page-header-left">
                        <h2 class="set-page-title">
                            <span class="set-page-icon">⚙️</span>
                            System Settings
                        </h2>
                        <p class="set-page-subtitle">Configure your store information and system preferences</p>
                    </div>
                </div>
                
                <!-- Alerts -->
                <?php if (isset($settings_saved) && $success !== false): ?>
                    <div class="set-alert set-alert-success">
                        <div class="set-alert-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </div>
                        <div class="set-alert-content">
                            <strong>Success!</strong>
                            <span><?php echo $message; ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (isset($settings_error)): ?>
                    <div class="set-alert set-alert-error">
                        <div class="set-alert-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </div>
                        <div class="set-alert-content">
                            <strong>Error!</strong>
                            <span><?php echo $settings_error; ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Settings Form -->
                <form method="POST" enctype="multipart/form-data" id="settingsForm">
                    <input type="hidden" name="save_settings" value="1">
                    
                    <!-- ===== STORE IDENTITY SECTION ===== -->
                    <div class="set-section">
                        <div class="set-section-header">
                            <div class="set-section-icon" style="background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                </svg>
                            </div>
                            <div class="set-section-text">
                                <h3>Store Identity</h3>
                                <p>Basic information about your store</p>
                            </div>
                        </div>
                        
                        <div class="set-section-body">
                            
                            <!-- Logo + Name -->
                            <div class="set-logo-row">
                                <div class="set-logo-preview">
                                    <?php if (!empty($store_logo) && file_exists($store_logo)): ?>
                                        <img src="<?php echo $store_logo; ?>?t=<?php echo time(); ?>" alt="Store Logo" id="currentLogo">
                                    <?php else: ?>
                                        <div class="set-logo-placeholder" id="logoPlaceholder">🏪</div>
                                        <img src="" alt="" id="currentLogo" style="display:none;">
                                    <?php endif; ?>
                                </div>
                                <div class="set-logo-info">
                                    <div class="set-logo-label">Store Logo</div>
                                    <div class="set-logo-hint">Upload a square image for best results (JPEG, PNG, GIF, WebP · Max 5MB)</div>
                                    <input type="file" name="store_logo" accept="image/jpeg,image/png,image/gif,image/webp" id="logoInput" class="set-file-input">
                                    <label for="logoInput" class="set-file-label">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                            <polyline points="17 8 12 3 7 8"></polyline>
                                            <line x1="12" y1="3" x2="12" y2="15"></line>
                                        </svg>
                                        Choose New Logo
                                    </label>
                                    <div id="uploadStatus" class="set-upload-status"></div>
                                </div>
                            </div>
                            
                            <!-- Store Name -->
                            <div class="set-form-group">
                                <label class="set-form-label">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    Store Name *
                                </label>
                                <input type="text" name="store_name" value="<?php echo htmlspecialchars($store_name); ?>" required class="set-form-input" placeholder="e.g., Smart Market">
                                <div class="set-form-hint">This name appears throughout the system</div>
                            </div>
                            
                            <!-- Address + Contact (side by side) -->
                            <div class="set-form-row">
                                <div class="set-form-group">
                                    <label class="set-form-label">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        Store Address
                                    </label>
                                    <textarea name="store_address" rows="2" class="set-form-textarea" placeholder="e.g., 123 Main St, City"><?php echo htmlspecialchars($store_address); ?></textarea>
                                </div>
                                <div class="set-form-group">
                                    <label class="set-form-label">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                        </svg>
                                        Store Contact
                                    </label>
                                    <input type="text" name="store_contact" value="<?php echo htmlspecialchars($store_contact); ?>" class="set-form-input" placeholder="e.g., 0912-345-6789">
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    
                    <!-- ===== TAX & VAT SECTION ===== -->
                    <div class="set-section">
                        <div class="set-section-header">
                            <div class="set-section-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                            </div>
                            <div class="set-section-text">
                                <h3>Tax & VAT</h3>
                                <p>Configure your tax rates and VAT information</p>
                            </div>
                        </div>
                        
                        <div class="set-section-body">
                            
                            <div class="set-form-row">
                                <div class="set-form-group">
                                    <label class="set-form-label">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="18" y1="20" x2="18" y2="10"></line>
                                            <line x1="12" y1="20" x2="12" y2="4"></line>
                                            <line x1="6" y1="20" x2="6" y2="14"></line>
                                        </svg>
                                        VAT Rate (%)
                                    </label>
                                    <input type="number" step="0.01" name="tax_rate" value="<?php echo $current_tax; ?>" min="0" max="100" class="set-form-input" placeholder="12">
                                    <div class="set-form-hint">Philippines standard rate: 12%. Product prices are treated as VAT-inclusive; VAT is extracted from the final price.</div>
                                </div>
                                <div class="set-form-group">
                                    <label class="set-form-label">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                        VAT Registration Number
                                    </label>
                                    <input type="text" name="vat_reg_number" value="<?php echo htmlspecialchars($vat_reg_number); ?>" class="set-form-input" placeholder="Enter registered VAT number">
                                    <div class="set-form-hint">Printed on receipts</div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    
                    <!-- ===== EMAIL SENDING (EMAILJS) SECTION ===== -->
                    <div class="set-section">
                        <div class="set-section-header">
                            <div class="set-section-icon" style="background: linear-gradient(135deg, #EA4335 0%, #C5221F 100%);">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                    <path d="m22 7-10 5L2 7"></path>
                                </svg>
                            </div>
                            <div class="set-section-text">
                                <h3>Email Sending (EmailJS)</h3>
                                <p>Send messages from your Gmail through EmailJS — no SMTP server needed</p>
                            </div>
                        </div>
                        
                        <div class="set-section-body">
                            
                            <div class="set-alert set-alert-info" style="margin:0 0 18px;background:#EEF2FF;border:1px solid #C7D2FE;color:#4338CA;padding:14px 16px;border-radius:12px;font-size:13px;line-height:1.6;">
                                <strong>📬 How this works:</strong> messages are sent from the Gmail account you connect to EmailJS, then appear in that account's Sent folder. To set it up:
                                <ol style="margin:8px 0 0 18px;padding:0;">
                                    <li>Sign up free at <strong>emailjs.com</strong> → <em>Email Services</em> → <strong>Add New Service</strong> → choose <strong>Gmail</strong> and connect the Gmail account you want to send from.</li>
                                    <li><em>Email Templates</em> → <strong>Add New Template</strong> and use these placeholders in the To / Subject / Content boxes: <code>{{to_email}}</code>, <code>{{to_name}}</code>, <code>{{subject}}</code>, <code>{{message}}</code>.</li>
                                    <li>Copy your <strong>Service ID</strong>, <strong>Template ID</strong>, and <strong>Public Key</strong> (Account → API Keys) below, then press Save.</li>
                                </ol>
                            </div>
                            
                            <div class="set-form-row">
                                <div class="set-form-group">
                                    <label class="set-form-label">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                            <path d="m22 7-10 5L2 7"></path>
                                        </svg>
                                        Service ID
                                    </label>
                                    <input type="text" name="emailjs_service_id" value="<?php echo htmlspecialchars($emailjs_service_id); ?>" class="set-form-input" placeholder="service_xxxxxxxxxxx">
                                    <div class="set-form-hint">Found in EmailJS dashboard under “Email Services”</div>
                                </div>
                                <div class="set-form-group">
                                    <label class="set-form-label">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        Template ID
                                    </label>
                                    <input type="text" name="emailjs_template_id" value="<?php echo htmlspecialchars($emailjs_template_id); ?>" class="set-form-input" placeholder="template_xxxxxxxxxxx">
                                    <div class="set-form-hint">Found in EmailJS dashboard under “Email Templates”</div>
                                </div>
                            </div>
                            
                            <div class="set-form-row">
                                <div class="set-form-group">
                                    <label class="set-form-label">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                        </svg>
                                        Public Key (API Key)
                                    </label>
                                    <input type="text" name="emailjs_public_key" value="<?php echo htmlspecialchars($emailjs_public_key); ?>" class="set-form-input" placeholder="Your public key">
                                    <div class="set-form-hint">Found in EmailJS dashboard under “Account → API Keys”. Safe to keep here — it is public by design.</div>
                                </div>
                                <div class="set-form-group">
                                    <div class="set-alert <?php echo emailjsConfigured() ? 'set-alert-success' : 'set-alert-warning'; ?>" style="margin:0;min-height:74px;display:flex;align-items:center;">
                                        <?php if (emailjsConfigured()): ?>
                                            ✅ <strong>Configured.</strong> You can now send emails from <em><?php echo htmlspecialchars($store_name); ?></em> via the <strong>Send Email</strong> page.
                                        <?php else: ?>
                                            ⚠️ <strong>Not configured yet.</strong> Add the 3 IDs above to enable the Send Email page.
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    
                    <!-- ===== SAVE BAR ===== -->
                    <div class="set-save-bar">
                        <div class="set-save-info">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            Changes will apply system-wide
                        </div>
                        <button type="submit" class="set-btn-save" id="saveSettingsBtn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Save Settings
                        </button>
                    </div>
                    
                </form>
                
                <script>
                    // Logo preview
                    document.getElementById('logoInput').addEventListener('change', function(e) {
                        var file = this.files[0];
                        var status = document.getElementById('uploadStatus');
                        var currentLogo = document.getElementById('currentLogo');
                        var placeholder = document.getElementById('logoPlaceholder');
                        
                        if (!file) {
                            status.textContent = '';
                            return;
                        }
                        
                        if (file.size > 5 * 1024 * 1024) {
                            status.textContent = '❌ File too large! Max 5MB.';
                            status.className = 'set-upload-status set-upload-error';
                            this.value = '';
                            return;
                        }
                        
                        var validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        if (validTypes.indexOf(file.type) === -1) {
                            status.textContent = '❌ Invalid file type.';
                            status.className = 'set-upload-status set-upload-error';
                            this.value = '';
                            return;
                        }
                        
                        status.textContent = '✅ ' + file.name;
                        status.className = 'set-upload-status set-upload-success';
                        
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            if (currentLogo) {
                                currentLogo.src = e.target.result;
                                currentLogo.style.display = 'block';
                            }
                            if (placeholder) {
                                placeholder.style.display = 'none';
                            }
                        };
                        reader.readAsDataURL(file);
                    });
                    
                    // Save button loading state
                    document.getElementById('settingsForm').addEventListener('submit', function() {
                        var btn = document.getElementById('saveSettingsBtn');
                        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10" stroke-dasharray="30 100" stroke-linecap="round"></circle></svg> Saving...';
                        btn.disabled = true;
                        btn.style.opacity = '0.8';
                    });
                </script>
                
                <?php
                

                                // ============================================
                // STOCK MANAGEMENT PAGE
                // ============================================
                // ============================================
                // STOCK MANAGEMENT PAGE
                // ============================================
break;
endswitch;
