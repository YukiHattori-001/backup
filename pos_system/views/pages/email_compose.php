<?php
switch ('email_compose'):
case 'email_compose':

    if (!isAdmin()) {
        echo '<div class="alert alert-danger">⛔ Access Denied. Admin only.</div>';
        break;
    }

    $configured = emailjsConfigured();
    $storeName  = getSetting('store_name', 'Smart Market');

    $customers = [];
    $suppliers = [];
    $staff     = [];
    try {
        $customers = $pdo->query("SELECT id, name, email FROM customers WHERE email IS NOT NULL AND TRIM(email) <> '' ORDER BY name")->fetchAll();
    } catch (PDOException $e) { $customers = []; }
    try {
        $suppliers = $pdo->query("SELECT id, name, email FROM suppliers WHERE email IS NOT NULL AND TRIM(email) <> '' ORDER BY name")->fetchAll();
    } catch (PDOException $e) { $suppliers = []; }
    try {
        $staff = $pdo->query("SELECT id, full_name, email FROM users WHERE email IS NOT NULL AND TRIM(email) <> '' ORDER BY full_name")->fetchAll();
    } catch (PDOException $e) { $staff = []; }
    ?>

    <!-- ============================================ -->
    <!-- SEND EMAIL PAGE - EMAILJS -->
    <!-- ============================================ -->

    <!-- Page Header -->
    <div class="set-page-header">
        <div class="set-page-header-left">
            <h2 class="set-page-title">
                <span class="set-page-icon">📧</span>
                Send Email
            </h2>
            <p class="set-page-subtitle">Compose a message and send it from your connected Gmail via EmailJS</p>
        </div>
    </div>

    <?php if (!$configured): ?>
    <div class="emc-alert emc-alert-warning">
        <div class="emc-alert-icon">⚠️</div>
        <div>
            <strong>Email not configured yet.</strong>
            <span>Add your EmailJS Service ID, Template ID and Public Key in <a href="?page=settings" style="color:inherit;text-decoration:underline;font-weight:700;">System Settings → Email Sending (EmailJS)</a> before you can send messages.</span>
        </div>
    </div>
    <?php endif; ?>

    <div class="emc-layout">
        <!-- ===== COMPOSE CARD ===== -->
        <div class="emc-card">
            <div class="emc-card-header">
                <div class="emc-card-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                        <path d="m22 7-10 5L2 7"></path>
                    </svg>
                    New Message
                </div>
                <div class="emc-card-subtitle">Sends through the Gmail connected to your EmailJS service</div>
            </div>

            <form id="emcForm" onsubmit="sendEmail(event)" autocomplete="off">
                <div class="emc-form-group">
                    <label class="emc-form-label">Recipient</label>
                    <select id="emcRecipient" class="emc-input" onchange="emcRecipientChanged()">
                        <option value="">— Select a recipient —</option>
                        <?php if (!empty($staff)): ?>
                        <optgroup label="👥 Staff">
                            <?php foreach ($staff as $s): ?>
                            <option value="<?php echo htmlspecialchars($s['email'], ENT_QUOTES, 'UTF-8'); ?>" data-name="<?php echo htmlspecialchars($s['full_name'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($s['full_name'] . ' — ' . $s['email']); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                        <?php if (!empty($customers)): ?>
                        <optgroup label="🛒 Customers">
                            <?php foreach ($customers as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8'); ?>" data-name="<?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($c['name'] . ' — ' . $c['email']); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                        <?php if (!empty($suppliers)): ?>
                        <optgroup label="🚚 Suppliers">
                            <?php foreach ($suppliers as $sp): ?>
                            <option value="<?php echo htmlspecialchars($sp['email'], ENT_QUOTES, 'UTF-8'); ?>" data-name="<?php echo htmlspecialchars($sp['name'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($sp['name'] . ' — ' . $sp['email']); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                        <option value="custom">✏️ Custom email…</option>
                    </select>
                    <div id="emcCustomWrap" class="emc-custom-wrap" style="display:none;">
                        <input type="email" id="emcCustomEmail" class="emc-input" placeholder="Enter any email address here…">
                    </div>
                </div>

                <div class="emc-form-group">
                    <label class="emc-form-label">Subject</label>
                    <input type="text" id="emcSubject" class="emc-input" placeholder="e.g., Your purchase order PO-2026-001 has shipped" maxlength="200">
                </div>

                <div class="emc-form-group">
                    <label class="emc-form-label">Message</label>
                    <textarea id="emcMessage" rows="9" class="emc-input" placeholder="Type your message here…"></textarea>
                    <div class="emc-char-hint"><span id="emcCharCount">0</span> characters</div>
                </div>

                <div class="emc-form-group">
                    <label class="emc-form-label emc-inline-label">
                        <span style="flex:1;">Sends with the Gmail connected to EmailJS (appears in that account's Sent folder)</span>
                        <span class="emc-chip emc-chip-green" id="emcSourceChip">📬 <?php echo htmlspecialchars($configured ? 'Connected' : 'Not configured'); ?></span>
                    </label>
                </div>

                <div class="emc-actions">
                    <button type="submit" class="emc-btn-send" id="emcSendBtn" <?php echo $configured ? '' : 'disabled'; ?>>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                        Send Email
                    </button>
                    <button type="button" class="emc-btn-cancel" onclick="emcResetForm()">Clear</button>
                </div>
            </form>
        </div>

        <!-- ===== HELP CARD ===== -->
        <div class="emc-card">
            <div class="emc-card-header">
                <div class="emc-card-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    How it works
                </div>
                <div class="emc-card-subtitle">Why you can “see” your Gmail here</div>
            </div>
            <div class="emc-help-body">
                <ul class="emc-help-list">
                    <li>
                        <span class="emc-help-num">1</span>
                        <span><strong>Connect your Gmail</strong> to EmailJS once (emailjs.com → Email Services → Add New Service → Gmail). That's the Gmail address messages are sent from — and the one you'll see in the app.</span>
                    </li>
                    <li>
                        <span class="emc-help-num">2</span>
                        <span><strong>Set up a template</strong> that uses the placeholders <code>{{to_email}}</code>, <code>{{to_name}}</code>, <code>{{subject}}</code> and <code>{{message}}</code>.</span>
                    </li>
                    <li>
                        <span class="emc-help-num">3</span>
                        <span><strong>Pick a recipient</strong> from the list (staff, customers and suppliers show their Gmail addresses) or type any email, write your message, and hit <em>Send Email</em>.</span>
                    </li>
                    <li>
                        <span class="emc-help-num">4</span>
                        <span><strong>Done.</strong> EmailJS sends it from your Gmail without an SMTP server. Copies appear in that account's Sent folder.</span>
                    </li>
                </ul>
                <div class="emc-help-note">
                    📬 <strong>Tip:</strong> emails are sent from the browser with your public key only — no passwords are stored in this app.
                </div>
                <a href="?page=settings" class="emc-link-settings">Manage EmailJS settings →</a>
            </div>
        </div>
    </div>

    <script>
        window.emcConfig = {
            serviceId: <?php echo json_encode(getSetting('emailjs_service_id', '')); ?>,
            templateId: <?php echo json_encode(getSetting('emailjs_template_id', '')); ?>,
            publicKey: <?php echo json_encode(getSetting('emailjs_public_key', '')); ?>,
            fromName: <?php echo json_encode($storeName); ?>,
            configured: <?php echo $configured ? 'true' : 'false'; ?>
        };

        function emcRecipientChanged() {
            var sel = document.getElementById('emcRecipient');
            var customWrap = document.getElementById('emcCustomWrap');
            if (sel.value === 'custom') {
                customWrap.style.display = 'block';
                document.getElementById('emcCustomEmail').focus();
            } else {
                customWrap.style.display = 'none';
            }
        }

        function emcResetForm() {
            document.getElementById('emcForm').reset();
            document.getElementById('emcCustomWrap').style.display = 'none';
            document.getElementById('emcCharCount').textContent = '0';
        }

        document.getElementById('emcMessage').addEventListener('input', function () {
            document.getElementById('emcCharCount').textContent = this.value.length;
        });

        function sendEmail(e) {
            e.preventDefault();
            var cfg = window.emcConfig;
            if (!cfg.configured || !cfg.serviceId || !cfg.templateId || !cfg.publicKey) {
                showToast('warning', 'Email not configured', 'Add your EmailJS Service ID, Template ID and Public Key in System Settings first.', 6000);
                return;
            }

            var sel = document.getElementById('emcRecipient');
            var toEmail = sel.value === 'custom'
                ? document.getElementById('emcCustomEmail').value.trim()
                : sel.value;
            var toName = sel.value === 'custom'
                ? (document.getElementById('emcCustomEmail').value.trim() || '')
                : (sel.selectedOptions[0] ? sel.selectedOptions[0].dataset.name || '' : '');
            var subject = document.getElementById('emcSubject').value.trim();
            var message = document.getElementById('emcMessage').value.trim();

            if (!toEmail) { showToast('error', 'Missing recipient', 'Pick a recipient or type a custom email address.'); return; }
            if (!subject) { showToast('error', 'Missing subject', 'Please add a subject.'); return; }
            if (!message) { showToast('error', 'Missing message', 'Please write a message.'); return; }

            var btn = document.getElementById('emcSendBtn');
            var original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span style="display:inline-flex;gap:8px;align-items:center;">Sending…<span style="width:14px;height:14px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin 0.8s linear infinite;display:inline-block;"></span></span>';

            if (typeof emailjs === 'undefined') {
                btn.disabled = false;
                btn.innerHTML = original;
                showToast('error', 'EmailJS library not loaded', 'Check your internet connection and try again.');
                return;
            }

            emailjs.init(cfg.publicKey);
            emailjs.send(cfg.serviceId, cfg.templateId, {
                to_email: toEmail,
                to_name: toName,
                subject: subject,
                message: message,
                from_name: cfg.fromName
            }).then(function () {
                var data = new FormData();
                data.append('to_email', toEmail);
                data.append('subject', subject);
                fetch('?action=log_email_send', { method: 'POST', body: data }).catch(function () {});
                showToast('success', 'Email sent!', 'Your message was sent to ' + toEmail + ' from your connected Gmail.', 6000);
                btn.disabled = false;
                btn.innerHTML = original;
                emcResetForm();
            }, function (err) {
                btn.disabled = false;
                btn.innerHTML = original;
                var detail = (err && err.text) ? err.text : (err ? String(err) : 'Unknown error');
                showToast('error', 'Send failed', detail, 8000);
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>

    <?php
break;
endswitch;