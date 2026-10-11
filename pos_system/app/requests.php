<?php
// INITIALIZE
// ============================================

function getPostLoginRedirect() {
    $roleEntryRequirements = [
        'admin.php' => 'admin',
        'hr.php' => 'hr',
        'inventory.php' => 'inventory',
        'finance.php' => 'finance'
    ];
    $pendingEntry = $_SESSION['pending_role_entry'] ?? '';
    if (isset($roleEntryRequirements[$pendingEntry]) && hasRole($roleEntryRequirements[$pendingEntry])) {
        unset($_SESSION['pending_role_entry']);
        return $pendingEntry;
    }
    unset($_SESSION['pending_role_entry']);
    foreach ($roleEntryRequirements as $entry => $role) {
        if (hasRole($role)) {
            return $entry;
        }
    }
    return 'index.php';
}

$categoryManager = new CategoryManager($pdo);
$productManager = new ProductManager($pdo);
$customerManager = new CustomerManager($pdo);
$saleManager = new SaleManager($pdo);
$reportManager = new ReportManager($pdo);
$userManager = new UserManager($pdo);
$activityLogManager = new ActivityLogManager($pdo);
$hrManager = new HRManager($pdo);
$financeManager = new FinanceManager($pdo);
$returnManager = new ReturnManager($pdo); 

// Check remember me token
if (!isset($_SESSION['logged_in']) && isset($_COOKIE['remember_token'])) {
    checkRememberToken();
}

// Handle login
if (isset($_POST['switch_saved_account'])) {
    $result = loginWithSavedAccount($_POST['switch_saved_account']);
    if ($result['success']) {
        if (!empty($result['requires_2fa'])) {
            $_SESSION['2fa_user_id'] = $_SESSION['user_id'];
            header('Location: login.php?2fa=1');
            exit();
        }
        header('Location: ' . getPostLoginRedirect());
        exit();
    }
    $error = $result['message'];
}

if (isset($_POST['login'])) {
    $result = login($_POST['username'], $_POST['password'], isset($_POST['remember']));
    if ($result['success']) {
        if (isset($result['requires_2fa']) && $result['requires_2fa']) {
            $_SESSION['2fa_required'] = true;
            $_SESSION['2fa_user_id'] = $result['user_id'];
            header('Location: login.php?2fa=1');
            exit();
        }
        header('Location: ' . getPostLoginRedirect());
        exit();
    } else {
        $error = $result['message'];
    }
}

// Handle 2FA verification
if (isset($_POST['verify_2fa'])) {
    if (verify2FA($_POST['2fa_code'])) {
        if (!empty($_SESSION['remember_after_2fa']) && isset($_SESSION['user_id'])) {
            saveLoginAccountForDevice($_SESSION['user_id']);
        }
        unset($_SESSION['remember_after_2fa']);
        header('Location: ' . getPostLoginRedirect());
        exit();
    } else {
        $error = 'Invalid 2FA code';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    logout();
}

// ============================================
// AJAX request handlers
// ============================================

if (isset($_GET['action'])) {
    // CSRF mitigation: for state-changing POST actions, the request must originate from our own pages
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $ok = false;
        foreach ([$origin, $referer] as $src) {
            if ($src && $host && stripos($src, $host) !== false) { $ok = true; break; }
        }
        if (!$ok) {
            echo json_encode(['success' => false, 'message' => 'Invalid request origin']);
            exit();
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    
    $adminActions = ['add_product', 'update_product', 'delete_product', 'archive_product', 'restore_product', 'add_user', 'delete_user', 'add_category', 'update_category', 'delete_category', 'save_settings', 'get_settings'];
    
    if (in_array($_GET['action'], $adminActions) && !isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Admin only']);
        exit();
    }
    
    $hrActions = ['save_employee', 'get_employee', 'delete_employee', 'save_attendance', 'save_leave', 'save_payroll', 'clock_in', 'clock_out', 'get_attendance'];
    if (in_array($_GET['action'], $hrActions) && !hasRole('hr') && !isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized: HR only']);
        exit();
    }
    
    // --- PRODUCT AJAX HANDLERS ---
    
    if ($_GET['action'] == 'add_product' || $_GET['action'] == 'update_product') {
        try {
            $id = isset($_POST['id']) && $_POST['id'] ? $_POST['id'] : null;
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $category_id = isset($_POST['category_id']) && $_POST['category_id'] ? $_POST['category_id'] : null;
            $price = isset($_POST['price']) ? $_POST['price'] : 0;
            $stock = isset($_POST['stock']) ? $_POST['stock'] : 0;
            $unit = isset($_POST['unit']) ? $_POST['unit'] : 'pc';
            
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Product name is required']);
                exit();
            }
            
            if (!$id) {
                $stmt = $pdo->prepare("SELECT id FROM products WHERE LOWER(name) = LOWER(?) AND archived = 0");
                $stmt->execute([$name]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    echo json_encode(['success' => false, 'message' => 'A product with this name already exists!']);
                    exit();
                }
            } else {
                $stmt = $pdo->prepare("SELECT id FROM products WHERE LOWER(name) = LOWER(?) AND id != ? AND archived = 0");
                $stmt->execute([$name, $id]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    echo json_encode(['success' => false, 'message' => 'Another product with this name already exists!']);
                    exit();
                }
            }
            
            $imagePath = null;
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/products/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                if ($_FILES['image']['size'] > 10 * 1024 * 1024) {
                    echo json_encode(['success' => false, 'message' => 'File is too large. Maximum 10MB allowed.']);
                    exit();
                }
                
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $_FILES['image']['tmp_name']);
                finfo_close($finfo);
                
                if (!in_array($mimeType, $allowedTypes)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload JPEG, PNG, GIF, or WebP.']);
                    exit();
                }
                
                $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $fileName = time() . '_' . uniqid() . '.' . $fileExtension;
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $imagePath = $targetPath;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
                    exit();
                }
            }
            
            if ($id && !$imagePath) {
                $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
                $stmt->execute([$id]);
                $existing = $stmt->fetch();
                if ($existing) {
                    $imagePath = $existing['image'];
                }
            }
            
            if ($id) {
                $result = $productManager->updateProduct($id, $name, $category_id, $price, $stock, $unit, $imagePath);
            } else {
                $result = $productManager->addProduct($name, $category_id, $price, $stock, $unit, null, $imagePath);
            }
            
            // 🔔 Notify inventory staff (only if changed by non-inventory user)
            if ($result) {
                $actionWord = $id ? 'edited' : 'added';
                notifyInventory(
                    $id ? 'product_edited' : 'product_added',
                    '📦 Product ' . ucfirst($actionWord),
                    "Product \"{$name}\" was {$actionWord} by " . ($_SESSION['full_name'] ?? 'Admin') . ".",
                    $id ?: $pdo->lastInsertId()
                );
            }

            echo json_encode(['success' => $result]);
            exit();
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit();
        }
    }
    
    if ($_GET['action'] == 'get_products') {
        echo json_encode($productManager->getAllProducts(false));
        exit();
    }
    
    if ($_GET['action'] == 'get_categories') {
        echo json_encode($categoryManager->getAllCategories());
        exit();
    }
    
    if ($_GET['action'] == 'add_category') {
        $result = $categoryManager->addCategory($_POST['name'], $_POST['description']);
        echo json_encode(['success' => $result]);
        exit();
    }
    
    if ($_GET['action'] == 'update_category') {
        $result = $categoryManager->updateCategory($_POST['id'], $_POST['name'], $_POST['description']);
        echo json_encode(['success' => $result]);
        exit();
    }
    
    if ($_GET['action'] == 'delete_category') {
        $result = $categoryManager->deleteCategory($_GET['id']);
        echo json_encode($result);
        exit();
    }
    
    if ($_GET['action'] == 'get_archived_products') {
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        echo json_encode($productManager->getArchivedProducts($search));
        exit();
    }
    
    if ($_GET['action'] == 'archive_product') {
        $productId = $_GET['id'];
        $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $archivedName = $stmt->fetch()['name'] ?? 'Unknown';
        
        $result = $productManager->archiveProduct($productId);
        
        if ($result) {
            notifyInventory(
                'product_archived',
                '📁 Product Archived',
                "Product \"{$archivedName}\" was archived by " . ($_SESSION['full_name'] ?? 'Admin') . ".",
                $productId
            );
        }
        
        echo json_encode(['success' => $result]);
        exit();
    }
    
    if ($_GET['action'] == 'restore_product') {
        $productId = $_GET['id'];
        $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $restoredName = $stmt->fetch()['name'] ?? 'Unknown';
        
        $result = $productManager->restoreProduct($productId);
        
        if ($result) {
            notifyInventory(
                'product_restored',
                '♻️ Product Restored',
                "Product \"{$restoredName}\" was restored by " . ($_SESSION['full_name'] ?? 'Admin') . ".",
                $productId
            );
        }
        
        echo json_encode(['success' => $result]);
        exit();
    }
    
    // --- CUSTOMER AJAX HANDLERS ---
    
    if ($_GET['action'] == 'get_customers') {
        echo json_encode($customerManager->getAllCustomers());
        exit();
    }
    
    if ($_GET['action'] == 'get_customer') {
        $customer = $customerManager->getCustomer($_GET['id']);
        $stats = $customerManager->getCustomerStats($_GET['id']);
        $history = $customerManager->getCustomerPurchaseHistory($_GET['id']);
        echo json_encode([
            'customer' => $customer,
            'stats' => $stats,
            'history' => $history
        ]);
        exit();
    }
    
    if ($_GET['action'] == 'add_customer') {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Name is required']);
            exit();
        }
        
        $result = $customerManager->addCustomer($name, $email, $phone);
        echo json_encode(['success' => $result]);
        exit();
    }
    
    if ($_GET['action'] == 'update_customer') {
        $id = isset($_POST['id']) ? $_POST['id'] : null;
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        
        if (!$id || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit();
        }
        
        $result = $customerManager->updateCustomer($id, $name, $email, $phone);
        echo json_encode($result);
        exit();
    }
    
    if ($_GET['action'] == 'delete_customer') {
        $result = $customerManager->deleteCustomer($_GET['id']);
        echo json_encode($result);
        exit();
    }
    
    // --- SALE AJAX HANDLERS ---
    
    if ($_GET['action'] == 'create_sale' && isset($_POST['items'])) {
        $items = json_decode($_POST['items'], true);
        $total = floatval($_POST['total']);
        $customer_id = isset($_POST['customer_id']) && $_POST['customer_id'] ? $_POST['customer_id'] : null;
        $amount_paid = isset($_POST['amount_paid']) ? floatval($_POST['amount_paid']) : $total;
        $change = isset($_POST['change']) ? floatval($_POST['change']) : 0;
        $discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;
        $loyalty_points_used = isset($_POST['loyalty_points_used']) ? intval($_POST['loyalty_points_used']) : 0;
        $payment_method = $_POST['payment_method'] ?? 'cash';
        $payment_token_id = !empty($_POST['payment_token_id']) ? intval($_POST['payment_token_id']) : null;

        $result = $saleManager->createSale($items, $total, $customer_id, $amount_paid, $change, $discount, $loyalty_points_used, $payment_method, $payment_token_id);
        echo json_encode($result);
        exit();
    }

                // ============================================
            // Sales list endpoint
            // ============================================

            if ($_GET['action'] == 'get_sales') {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        try {
            // FIX: Use a simple query without parameter binding for LIMIT
            $stmt = $pdo->query("
                SELECT s.*, u.full_name as cashier, c.name as customer 
                FROM sales s 
                LEFT JOIN users u ON s.user_id = u.id 
                LEFT JOIN customers c ON s.customer_id = c.id 
                ORDER BY s.sale_date DESC 
                LIMIT $limit
            ");
            $sales = $stmt->fetchAll();
            echo json_encode($sales);
        } catch(PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit();
    }

// ============================================

// ============================================
    
    if ($_GET['action'] == 'get_sale' && isset($_GET['id'])) {
        echo json_encode($saleManager->getSale($_GET['id']));
        exit();
    }
    
    if ($_GET['action'] == 'delete_product') {
        $productId = $_GET['id'];
        $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $deletedName = $stmt->fetch()['name'] ?? 'Unknown';

        $result = $productManager->deleteProduct($productId);

        if ($result) {
            notifyInventory(
                'product_deleted',
                '🗑️ Product Deleted',
                "Product \"{$deletedName}\" was deleted by " . ($_SESSION['full_name'] ?? 'Admin') . ".",
                $productId
            );
        }

        echo json_encode(['success' => $result]);
        exit();
    }
    
    if ($_GET['action'] == 'get_product') {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        echo json_encode($stmt->fetch());
        exit();
    }
    
    // --- USER AJAX HANDLERS ---

    if ($_GET['action'] == 'save_custom_role') {
        $roleKey = $_POST['role_key'] ?? '';
        if (!isAdmin() && !hasRole('hr')) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        if (!isAdmin() && $roleKey !== '') {
            echo json_encode(['success' => false, 'message' => 'HR can create custom roles but cannot edit existing roles.']);
            exit();
        }
        $result = $userManager->saveCustomRole(
            $_POST['role_name'] ?? '',
            $roleKey,
            $_POST['permissions'] ?? []
        );
        echo json_encode($result);
        exit();
    }

    if ($_GET['action'] == 'delete_custom_role') {
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        $result = $userManager->deleteCustomRole($_POST['role_key'] ?? '');
        echo json_encode($result);
        exit();
    }
    
    if ($_GET['action'] == 'add_user') {
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        $result = $userManager->addUser($_POST['password'] ?? '', $_POST['full_name'] ?? '', $_POST['role'] ?? [], $_POST['email'] ?? '');
        echo json_encode($result);
        exit();
    }

    if ($_GET['action'] == 'update_user_roles') {
        if (!isAdmin() && !hasRole('hr')) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        $targetId = (int)($_POST['id'] ?? 0);
        $requestedRoles = getUserRoles($_POST['roles'] ?? []);
        if (!isAdmin()) {
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$targetId]);
            $targetUser = $stmt->fetch();
            if (!$targetUser || in_array('admin', $requestedRoles, true) || in_array('admin', getUserRoles($targetUser['role']), true)) {
                echo json_encode(['success' => false, 'message' => 'HR cannot assign or change admin access.']);
                exit();
            }
        }
        $result = $userManager->updateRoles($targetId, $requestedRoles);
        if ($result && (int)($_POST['id'] ?? 0) === (int)($_SESSION['user_id'] ?? 0)) {
            $_SESSION['role'] = implode(',', array_values(array_intersect(getAssignableRoleKeys(), getUserRoles($_POST['roles'] ?? []))));
            $usernameStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $usernameStmt->execute([$targetId]);
            $_SESSION['username'] = $usernameStmt->fetchColumn();
        }
        echo json_encode(['success' => $result, 'message' => $result ? '' : 'Select at least one role.']);
        exit();
    }
    
    if ($_GET['action'] == 'delete_user') {
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        $result = $userManager->deleteUser($_GET['id']);
        echo json_encode(['success' => $result]);
        exit();
    }
    
    if ($_GET['action'] == 'get_archive_history') {
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        echo json_encode($productManager->getArchiveHistory($search));
        exit();
    }
    
    if ($_GET['action'] == 'get_chart_data') {
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
        echo json_encode($reportManager->getChartData($days));
        exit();
    }
    
    if ($_GET['action'] == 'get_customer_spending') {
        echo json_encode($customerManager->getCustomerSpendingReport());
        exit();
    }
    
    if ($_GET['action'] == 'check_user') {
        $username = isset($_GET['username']) ? $_GET['username'] : '';
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $exists = $stmt->fetch() ? true : false;
        echo json_encode(['exists' => $exists]);
        exit();
    }
    
    if ($_GET['action'] == 'reset_password') {
        $username = isset($_POST['username']) ? $_POST['username'] : '';
        $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        
        if (empty($username) || empty($new_password)) {
            echo json_encode(['success' => false, 'message' => 'Username and new password required']);
            exit();
        }
        
        $result = $userManager->updatePassword($username, $new_password);
        echo json_encode(['success' => $result]);
        exit();
    }
    
    if ($_GET['action'] == 'get_activity_log') {
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        echo json_encode($activityLogManager->getActivityLog($limit));
        exit();
    }
    
    if ($_GET['action'] == 'get_sales_by_date') {
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        echo json_encode($productManager->getSalesByDate($start_date, $end_date));
        exit();
    }
    
    if ($_GET['action'] == 'get_sales_by_customer') {
        $customer_id = isset($_GET['customer_id']) ? $_GET['customer_id'] : null;
        if ($customer_id) {
            echo json_encode($productManager->getSalesByCustomer($customer_id));
        } else {
            echo json_encode([]);
        }
        exit();
    }
    
    if ($_GET['action'] == 'save_settings') {
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        
        if (isset($_POST['store_name'])) setSetting('store_name', $_POST['store_name']);
        if (isset($_POST['tax_rate'])) setSetting('tax_rate', $_POST['tax_rate']);
        if (isset($_POST['store_address'])) setSetting('store_address', $_POST['store_address']);
        if (isset($_POST['store_contact'])) setSetting('store_contact', $_POST['store_contact']);
        if (isset($_POST['vat_reg_number'])) setSetting('vat_reg_number', $_POST['vat_reg_number']);
        
        if (isset($_POST['emailjs_service_id'])) setSetting('emailjs_service_id', trim($_POST['emailjs_service_id']));
        if (isset($_POST['emailjs_template_id'])) setSetting('emailjs_template_id', trim($_POST['emailjs_template_id']));
        if (isset($_POST['emailjs_public_key'])) setSetting('emailjs_public_key', trim($_POST['emailjs_public_key']));
        
        if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/logo/';
            
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
                    exit();
                }
            }
            
            if ($_FILES['store_logo']['size'] > 5 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'Logo file too large. Max 5MB.']);
                exit();
            }
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_FILES['store_logo']['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type for logo.']);
                exit();
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
                echo json_encode(['success' => true, 'message' => 'Settings saved with logo']);
                exit();
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload logo - check directory permissions']);
                exit();
            }
        }
        
        logActivity('update_settings', "Updated settings");
        echo json_encode(['success' => true, 'message' => 'Settings saved']);
        exit();
    }
    
    if ($_GET['action'] == 'get_settings') {
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        
        echo json_encode([
            'store_name' => getSetting('store_name', 'Smart Market'),
            'tax_rate' => getSetting('tax_rate', 12),
            'store_address' => getSetting('store_address', ''),
            'store_contact' => getSetting('store_contact', ''),
            'vat_reg_number' => getSetting('vat_reg_number', ''),
            'store_logo' => getSetting('store_logo', ''),
            'emailjs_service_id' => getSetting('emailjs_service_id', ''),
            'emailjs_template_id' => getSetting('emailjs_template_id', ''),
            'emailjs_public_key' => getSetting('emailjs_public_key', '')
        ]);
        exit();
    }
    
    if ($_GET['action'] == 'emailjs_config') {
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        echo json_encode([
            'success' => true,
            'configured' => emailjsConfigured(),
            'service_id' => getSetting('emailjs_service_id', ''),
            'template_id' => getSetting('emailjs_template_id', ''),
            'public_key' => getSetting('emailjs_public_key', ''),
            'current_user' => [
                'email' => $_SESSION['email'] ?? '',
                'name' => $_SESSION['full_name'] ?? ''
            ]
        ]);
        exit();
    }
    
    if ($_GET['action'] == 'log_email_send') {
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        $toEmail = trim((string)($_POST['to_email'] ?? ''));
        $subject = trim((string)($_POST['subject'] ?? ''));
        logActivity('email_send', 'Sent email to ' . ($toEmail !== '' ? $toEmail : '(unknown)') . ($subject !== '' ? ' — ' . $subject : ''));
        echo json_encode(['success' => true]);
        exit();
    }
    
    if ($_GET['action'] == 'get_customer_loyalty') {
        $customer_id = isset($_GET['id']) ? $_GET['id'] : null;
        if ($customer_id) {
            $stmt = $pdo->prepare("SELECT loyalty_points FROM customers WHERE id = ?");
            $stmt->execute([$customer_id]);
            $points = $stmt->fetch();
            echo json_encode(['points' => $points ? intval($points['loyalty_points']) : 0]);
        } else {
            echo json_encode(['points' => 0]);
        }
        exit();
    }
    
    // --- HR AJAX HANDLERS ---
    
    if ($_GET['action'] == 'save_employee') {
        try {
            $data = [
                'id' => (!empty($_POST['id'])) ? $_POST['id'] : null,
                'employee_id' => $_POST['employee_id'] ?? '',
                'first_name' => $_POST['first_name'] ?? '',
                'last_name' => $_POST['last_name'] ?? '',
                'email' => $_POST['email'] ?? null,
                'phone' => $_POST['phone'] ?? null,
                'address' => $_POST['address'] ?? null,
                'position' => $_POST['position'] ?? null,
                'department' => $_POST['department'] ?? null,
                'salary' => $_POST['salary'] ?? 0,
                'salary_type' => $_POST['salary_type'] ?? 'monthly',
                'status' => $_POST['status'] ?? 'active',
                'role' => $_POST['role'] ?? null,
                'emergency_name' => $_POST['emergency_name'] ?? null,
                'emergency_phone' => $_POST['emergency_phone'] ?? null,
                'daily_rate' => $_POST['daily_rate'] ?? 0,
                'hourly_rate' => $_POST['hourly_rate'] ?? 0,
                'start_date' => (!empty($_POST['start_date'])) ? $_POST['start_date'] : null,
                'contract_end_date' => (!empty($_POST['contract_end_date'])) ? $_POST['contract_end_date'] : null,
            ];

            // Validate required fields
            if (empty($data['employee_id'])) {
                echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
                exit();
            }
            if (empty($data['first_name']) || empty($data['last_name'])) {
                echo json_encode(['success' => false, 'message' => 'First and Last name are required']);
                exit();
            }

            $result = $hrManager->saveEmployee($data);
            
            if ($result) {
                logActivity('save_employee', "Saved employee: {$data['first_name']} {$data['last_name']} ({$data['employee_id']})");
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error - check error.log']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
    
    if ($_GET['action'] == 'get_employee') {
        echo json_encode($hrManager->getEmployee($_GET['id']));
        exit();
    }
    
    if ($_GET['action'] == 'delete_employee') {
        $result = $hrManager->deleteEmployee($_GET['id']);
        echo json_encode(['success' => $result]);
        exit();
    }
    
    if ($_GET['action'] == 'clock_in') {
        $employee_id = isset($_POST['employee_id']) ? $_POST['employee_id'] : null;
        if ($employee_id) {
            $result = $hrManager->clockIn($employee_id);
            if ($result['success']) {
                logActivity('clock_in', "Employee clocked in (ID: $employee_id)");
                echo json_encode(['success' => true, 'message' => $result['message']]);
            } else {
                echo json_encode(['success' => false, 'message' => $result['message']]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        }
        exit();
    }
    
    
    if ($_GET['action'] == 'clock_out') {
        $employee_id = isset($_POST['employee_id']) ? $_POST['employee_id'] : null;
        if ($employee_id) {
            $result = $hrManager->clockOut($employee_id);
            if ($result['success']) {
                logActivity('clock_out', "Employee clocked out (ID: $employee_id)");
                echo json_encode(['success' => true, 'message' => $result['message']]);
            } else {
                echo json_encode(['success' => false, 'message' => $result['message']]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        }
        exit();
    }
    
   if ($_GET['action'] == 'get_employee_attendance') {
    $employee_id = isset($_GET['id']) ? $_GET['id'] : null;
    if ($employee_id) {
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM attendance 
                WHERE employee_id = ? 
                ORDER BY date DESC, time_in DESC 
                LIMIT 50
            ");
            $stmt->execute([$employee_id]);
            $result = $stmt->fetchAll();
            echo json_encode($result);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    } else {
        echo json_encode([]);
    }
    exit();
}
    if ($_GET['action'] == 'get_employee_stats') {
        try {
            // Get all employees with their stats
            $stmt = $pdo->query("
                SELECT 
                    e.id,
                    e.first_name,
                    e.last_name,
                    e.employee_id,
                    e.start_date,
                    e.contract_end_date,
                    e.status,
                    COUNT(DISTINCT a.id) as shifts,
                    COUNT(DISTINCT a.date) as days_worked,
                    COALESCE(SUM(
                        CASE 
                            WHEN a.time_in IS NOT NULL AND a.time_out IS NOT NULL 
                            THEN TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out)
                            ELSE 0 
                        END
                    ), 0) as total_minutes
                FROM employees e
                LEFT JOIN attendance a ON e.id = a.employee_id
                GROUP BY e.id
                ORDER BY e.first_name
            ");
            $employees = $stmt->fetchAll();
            
            $totalEmployees = count($employees);
            $activeEmployees = 0;
            $totalShifts = 0;
            $totalMinutes = 0;
            $employeeData = [];
            
            foreach ($employees as $emp) {
                if ($emp['status'] == 'active') $activeEmployees++;
                $totalShifts += $emp['shifts'];
                $totalMinutes += $emp['total_minutes'];
                
                // Calculate days since start
                $daysSinceStart = 0;
                if ($emp['start_date']) {
                    $start = new DateTime($emp['start_date']);
                    $now = new DateTime();
                    $daysSinceStart = $start->diff($now)->days;
                }
                
                // Format hours
                $hours = floor($emp['total_minutes'] / 60) . 'h ' . ($emp['total_minutes'] % 60) . 'm';
                
                $employeeData[] = [
                    'name' => $emp['first_name'] . ' ' . $emp['last_name'],
                    'days_since_start' => $daysSinceStart,
                    'contract_end_date' => $emp['contract_end_date'] ? date('M d, Y', strtotime($emp['contract_end_date'])) : 'N/A',
                    'shifts' => $emp['shifts'],
                    'days_worked' => $emp['days_worked'],
                    'hours' => $hours
                ];
            }
            
            $totalHours = floor($totalMinutes / 60) . 'h ' . ($totalMinutes % 60) . 'm';
            
            echo json_encode([
                'total_employees' => $totalEmployees,
                'active_employees' => $activeEmployees,
                'total_shifts' => $totalShifts,
                'total_hours' => $totalHours,
                'employees' => $employeeData
            ]);
        } catch(PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit();
    }
    
    if ($_GET['action'] == 'save_leave') {
        $employee_id = isset($_POST['employee_id']) ? $_POST['employee_id'] : null;
        $type = isset($_POST['leave_type']) ? $_POST['leave_type'] : null;
        $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : null;
        $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
        
        if (empty($employee_id) || empty($type) || empty($start_date) || empty($end_date)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit();
        }
        
        if (strtotime($start_date) > strtotime($end_date)) {
            echo json_encode(['success' => false, 'message' => 'Start date cannot be after end date']);
            exit();
        }
        
        try {
            $result = $hrManager->createLeaveRequest($employee_id, $type, $start_date, $end_date, $reason);
            
            if ($result) {
                logActivity('create_leave', "Created leave request for employee ID: $employee_id");
                echo json_encode(['success' => true, 'message' => 'Leave request submitted successfully']);
            } else {
                $stmt = $pdo->prepare("SELECT id FROM employees WHERE id = ?");
                $stmt->execute([$employee_id]);
                if (!$stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Employee not found']);
                } else {
                    $stmt = $pdo->prepare("
                        SELECT id FROM leave_requests 
                        WHERE employee_id = ? 
                        AND status != 'rejected'
                        AND ((start_date <= ? AND end_date >= ?) OR (start_date <= ? AND end_date >= ?))
                    ");
                    $stmt->execute([$employee_id, $end_date, $start_date, $start_date, $end_date]);
                    if ($stmt->fetch()) {
                        echo json_encode(['success' => false, 'message' => 'Employee already has a leave request for this period']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to create leave request - database error']);
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("save_leave PDO Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        } catch (Exception $e) {
            error_log("save_leave Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit();
    }
    
    if ($_GET['action'] == 'get_leaves') {
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        echo json_encode($hrManager->getLeaveRequests($status));
        exit();
    }
    
    if ($_GET['action'] == 'update_leave_status') {
        $id = isset($_POST['id']) ? $_POST['id'] : null;
        $status = isset($_POST['status']) ? $_POST['status'] : null;
        
        if (empty($id) || empty($status)) {
            echo json_encode(['success' => false, 'message' => 'ID and status required']);
            exit();
        }
        
        $result = $hrManager->updateLeaveStatus($id, $status);
        if ($result) {
            logActivity('update_leave', "Updated leave request $id to $status");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update leave status']);
        }
        exit();
    }
    
    if ($_GET['action'] == 'save_payroll') {
        $employee_id = isset($_POST['employee_id']) ? $_POST['employee_id'] : null;
        $amount = isset($_POST['amount']) ? $_POST['amount'] : null;
        $period_start = isset($_POST['period_start']) ? $_POST['period_start'] : null;
        $period_end = isset($_POST['period_end']) ? $_POST['period_end'] : null;
        $type = isset($_POST['payroll_type']) ? $_POST['payroll_type'] : 'monthly';
        
        if (empty($employee_id) || empty($amount) || empty($period_start) || empty($period_end)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit();
        }
        
        $result = $hrManager->createPayroll($employee_id, $amount, $period_start, $period_end, $type);
        if ($result) {
            logActivity('create_payroll', "Created payroll for employee ID: $employee_id");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create payroll']);
        }
        exit();
    }
    
    if ($_GET['action'] == 'get_payroll') {
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        echo json_encode($hrManager->getPayroll($status));
        exit();
    }
    
    if ($_GET['action'] == 'update_payroll_status') {
        $id = isset($_POST['id']) ? $_POST['id'] : null;
        $status = isset($_POST['status']) ? $_POST['status'] : null;
        
        if (empty($id) || empty($status)) {
            echo json_encode(['success' => false, 'message' => 'ID and status required']);
            exit();
        }
        
        $result = $hrManager->updatePayrollStatus($id, $status);
        if ($result) {
            logActivity('update_payroll', "Updated payroll $id to $status");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update payroll status']);
        }
        exit();
    }
    
    if ($_GET['action'] == 'get_hr_stats') {
        echo json_encode($hrManager->getHRStats());
        exit();
    }
    if ($_GET['action'] == 'process_payroll') {
    if (!isAdmin() && !hasRole('hr')) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    try {
        // Check if there are any active employees with salary
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM employees WHERE status = 'active' AND salary > 0");
        $stmt->execute();
        $activeCount = $stmt->fetch()['count'];
        
        if ($activeCount == 0) {
            echo json_encode(['success' => false, 'message' => 'No active employees with salary found']);
            exit();
        }
        
        // Determine the current payroll period
        $currentPeriodStart = date('Y-m-01'); // First day of current month
        $currentPeriodEnd = date('Y-m-t');   // Last day of current month
        $currentPeriodType = 'monthly';
        
        // Check if payroll has already been processed for this period
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM payroll 
            WHERE period_start = ? 
            AND period_end = ? 
            AND status = 'paid'
        ");
        $stmt->execute([$currentPeriodStart, $currentPeriodEnd]);
        $alreadyPaidCount = $stmt->fetch()['count'];
        
        if ($alreadyPaidCount > 0) {
            // Get details of already paid payroll
            $stmt = $pdo->prepare("
                SELECT 
                    p.*,
                    e.first_name,
                    e.last_name,
                    e.employee_id
                FROM payroll p
                JOIN employees e ON p.employee_id = e.id
                WHERE p.period_start = ? 
                AND p.period_end = ? 
                AND p.status = 'paid'
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([$currentPeriodStart, $currentPeriodEnd]);
            $alreadyPaid = $stmt->fetchAll();
            
            $employeeNames = array();
            foreach ($alreadyPaid as $pay) {
                $employeeNames[] = $pay['first_name'] . ' ' . $pay['last_name'] . ' (₱' . number_format($pay['amount'], 2) . ')';
            }
            
            $message = "⚠️ Payroll for this period (" . date('F Y', strtotime($currentPeriodStart)) . ") has already been processed!\n\n";
            $message .= "Already paid employees:\n" . implode("\n", $employeeNames);
            $message .= "\n\nDo you want to process payroll for the next period?";
            
            echo json_encode([
                'success' => false, 
                'message' => $message,
                'already_paid' => true,
                'period' => date('F Y', strtotime($currentPeriodStart))
            ]);
            exit();
        }
        
        // Check if there are any pending payroll records for this period
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM payroll 
            WHERE period_start = ? 
            AND period_end = ? 
            AND status = 'pending'
        ");
        $stmt->execute([$currentPeriodStart, $currentPeriodEnd]);
        $pendingCount = $stmt->fetch()['count'];
        
        if ($pendingCount > 0) {
            // Mark pending payroll as paid
            $stmt = $pdo->prepare("
                UPDATE payroll 
                SET status = 'paid', updated_at = NOW() 
                WHERE period_start = ? 
                AND period_end = ? 
                AND status = 'pending'
            ");
            $stmt->execute([$currentPeriodStart, $currentPeriodEnd]);
            $paidCount = $stmt->rowCount();
            
            logActivity('process_payroll', "Processed payroll for $paidCount employees for period " . date('F Y', strtotime($currentPeriodStart)));
            echo json_encode([
                'success' => true, 
                'message' => "✅ Payroll processed for $paidCount employees for " . date('F Y', strtotime($currentPeriodStart))
            ]);
            exit();
        }
        
        // No payroll exists for this period, create new payroll
        $stmt = $pdo->prepare("
            INSERT INTO payroll (employee_id, amount, period_start, period_end, payroll_type, status, created_at)
            SELECT 
                id,
                salary,
                ?,
                ?,
                salary_type,
                'pending',
                NOW()
            FROM employees 
            WHERE status = 'active' AND salary > 0
        ");
        $stmt->execute([$currentPeriodStart, $currentPeriodEnd]);
        $createdCount = $stmt->rowCount();
        
        if ($createdCount == 0) {
            echo json_encode(['success' => false, 'message' => 'No active employees with salary found']);
            exit();
        }
        
        // Mark them as paid
        $stmt = $pdo->prepare("
            UPDATE payroll 
            SET status = 'paid', updated_at = NOW() 
            WHERE period_start = ? 
            AND period_end = ? 
            AND status = 'pending'
        ");
        $stmt->execute([$currentPeriodStart, $currentPeriodEnd]);
        $paidCount = $stmt->rowCount();
        
        logActivity('process_payroll', "Created and processed payroll for $paidCount employees for " . date('F Y', strtotime($currentPeriodStart)));
        echo json_encode([
            'success' => true, 
            'message' => "✅ Payroll created and processed for $paidCount employees for " . date('F Y', strtotime($currentPeriodStart))
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

        // ============================================
        // RECORD HOURS WORKED FOR HOURLY EMPLOYEES
        // ============================================

        if ($_GET['action'] == 'record_hours') {
            $employee_id = isset($_POST['employee_id']) ? $_POST['employee_id'] : null;
            $date = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
            $hours_worked = isset($_POST['hours_worked']) ? $_POST['hours_worked'] : 0;
            
            if (!$employee_id) {
                echo json_encode(['success' => false, 'message' => 'Employee ID required']);
                exit();
            }
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO attendance_hours 
                    (employee_id, date, hours_worked) 
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE hours_worked = hours_worked + ?
                ");
                $stmt->execute([$employee_id, $date, $hours_worked, $hours_worked]);
                
                echo json_encode(['success' => true, 'message' => 'Hours recorded']);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit();
        }
    
    // --- FINANCE AJAX HANDLERS ---
    
    if ($_GET['action'] == 'get_finance_stats') {
        echo json_encode($financeManager->getDashboardStats());
        exit();
    }
    
    if ($_GET['action'] == 'get_monthly_revenue') {
        $year = isset($_GET['year']) ? $_GET['year'] : date('Y');
        echo json_encode($financeManager->getMonthlyRevenue($year));
        exit();
    }
    
    if ($_GET['action'] == 'get_top_customers') {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        echo json_encode($financeManager->getTopCustomers($limit));
        exit();
    }
    
    if ($_GET['action'] == 'get_revenue_by_day') {
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        echo json_encode($financeManager->getRevenueByDay($days));
        exit();
    }


    // ============================================
    // INVENTORY AJAX HANDLERS
    // ============================================

    // ---- ADD STOCK ----
    if ($_GET['action'] == 'add_stock') {
        if (!canAccess('stock') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        
        try {
            $product_id = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity']);
            $reason = $_POST['reason'] ?? 'Stock added';
            $notes = $_POST['notes'] ?? '';
            
            if ($quantity <= 0) {
                echo json_encode(['success' => false, 'message' => 'Quantity must be positive']);
                exit();
            }
            
            // Get current stock
            $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $row = $stmt->fetch();
            
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                exit();
            }
            
            $before = intval($row['stock_quantity']);
            $after = $before + $quantity;
            
            // Update stock
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
            $stmt->execute([$after, $product_id]);
            
            // Log movement
            $stmt = $pdo->prepare("
                INSERT INTO stock_movements 
                (product_id, movement_type, quantity, quantity_before, quantity_after, reason, user_id) 
                VALUES (?, 'adjustment', ?, ?, ?, ?, ?)
            ");
            $fullReason = $reason . ($notes ? ' — ' . $notes : '');
            $stmt->execute([$product_id, $quantity, $before, $after, $fullReason, $_SESSION['user_id']]);
            
            logActivity('add_stock', "Added $quantity stock to product ID $product_id (new: $after)");
            
            // 🔔 Notify inventory (only if changed by non-inventory)
            $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product_name = $stmt->fetch()['name'] ?? 'Product';

            notifyInventory(
                'stock_added',
                '📊 Stock Added',
                "Stock for \"{$product_name}\" increased by {$quantity}. Reason: {$reason}. By " . ($_SESSION['full_name'] ?? 'Admin') . ".",
                $product_id,
                $after
            );

            echo json_encode(['success' => true, 'new_stock' => $after]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- ADJUST STOCK ----
    if ($_GET['action'] == 'adjust_stock') {
        if (!canAccess('stock') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        
        try {
            $product_id = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity']);
            $type = $_POST['type'] ?? 'adjustment';
            $notes = $_POST['notes'] ?? '';
            
            if ($quantity <= 0) {
                echo json_encode(['success' => false, 'message' => 'Quantity must be positive']);
                exit();
            }
            
            // Get current stock
            $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $row = $stmt->fetch();
            
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                exit();
            }
            
            $before = intval($row['stock_quantity']);
            $after = max(0, $before - $quantity); // Don't go below 0
            
            // Update stock
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
            $stmt->execute([$after, $product_id]);
            
            // Log movement (negative quantity)
            $movement_type = in_array($type, ['damage', 'lost', 'expired', 'correction']) ? $type : 'adjustment';
            $stmt = $pdo->prepare("
                INSERT INTO stock_movements 
                (product_id, movement_type, quantity, quantity_before, quantity_after, reason, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $fullReason = ucfirst($type) . ($notes ? ' — ' . $notes : '');
            $stmt->execute([$product_id, $movement_type, -$quantity, $before, $after, $fullReason, $_SESSION['user_id']]);
            
            logActivity('adjust_stock', "Adjusted stock for product ID $product_id: -$quantity ($type)");
            
            // 🔔 Notify inventory
            $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product_name = $stmt->fetch()['name'] ?? 'Product';

            notifyInventory(
                'stock_adjusted',
                '⚙️ Stock Adjusted',
                "Stock for \"{$product_name}\" decreased by {$quantity}. Reason: {$type}. By " . ($_SESSION['full_name'] ?? 'Admin') . ".",
                $product_id,
                $after
            );

            echo json_encode(['success' => true, 'new_stock' => $after]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- GET STOCK HISTORY ----
    if ($_GET['action'] == 'get_stock_history') {
        if (!canAccess('stock') && !isAdmin()) {
            echo json_encode([]);
            exit();
        }
        
        try {
            $product_id = intval($_GET['product_id'] ?? 0);
            $stmt = $pdo->prepare("
                SELECT sm.*, u.full_name as user_name
                FROM stock_movements sm
                LEFT JOIN users u ON sm.user_id = u.id
                WHERE sm.product_id = ?
                ORDER BY sm.created_at DESC
                LIMIT 100
            ");
            $stmt->execute([$product_id]);
            echo json_encode($stmt->fetchAll());
        } catch (Exception $e) {
            echo json_encode([]);
        }
        exit();
    }

    // ---- GET PROCUREMENT REQUESTS ----
    if ($_GET['action'] == 'get_procurement') {
        if (!canAccess('procurement') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        try {
            $stmt = $pdo->query("
                SELECT r.*, s.name AS supplier_name, u.full_name AS requested_by_name,
                    s.email AS supplier_email, a.full_name AS approved_by_name,
                    p.po_number AS purchase_po_number, perf.on_time_in_full,
                    perf.quality_rating, perf.responsiveness_rating
                FROM procurement_requests r
                LEFT JOIN suppliers s ON r.supplier_id = s.id
                LEFT JOIN users u ON r.requested_by = u.id
                LEFT JOIN users a ON r.approved_by = a.id
                LEFT JOIN purchases p ON r.purchase_id = p.id
                LEFT JOIN procurement_supplier_performance perf ON perf.request_id = r.id
                ORDER BY r.created_at DESC LIMIT 200
            ");
            echo json_encode($stmt->fetchAll());
        } catch (Exception $e) { echo json_encode([]); }
        exit();
    }

    if ($_GET['action'] == 'get_procurement_quotes') {
        if (!canAccess('procurement') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        $stmt = $pdo->prepare("SELECT q.*, s.name AS supplier_name,
            (SELECT ROUND(AVG(pf.quality_rating), 1) FROM procurement_supplier_performance pf WHERE pf.supplier_id = q.supplier_id) AS quality_average,
            (SELECT ROUND(AVG(pf.responsiveness_rating), 1) FROM procurement_supplier_performance pf WHERE pf.supplier_id = q.supplier_id) AS responsiveness_average,
            (SELECT GROUP_CONCAT(NULLIF(pf.lessons_learned, '') ORDER BY pf.rated_at DESC SEPARATOR ' | ') FROM procurement_supplier_performance pf WHERE pf.supplier_id = q.supplier_id AND pf.lessons_learned IS NOT NULL AND pf.lessons_learned <> '') AS lessons_learned
            FROM procurement_quotes q LEFT JOIN suppliers s ON q.supplier_id = s.id WHERE q.request_id = ? ORDER BY q.unit_cost, q.lead_time_days");
        $stmt->execute([intval($_GET['id'] ?? 0)]);
        echo json_encode($stmt->fetchAll());
        exit();
    }

    // ---- SAVE PROCUREMENT REQUEST ----
    if ($_GET['action'] == 'save_procurement') {
        if (!canAccess('procurement') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        try {
            $item_name = trim($_POST['item_name'] ?? '');
            $quantity = intval($_POST['quantity'] ?? 0);
            $estimatedCost = floatval($_POST['estimated_unit_cost'] ?? 0);
            if ($item_name === '' || $quantity <= 0 || $estimatedCost < 0) {
                echo json_encode(['success' => false, 'message' => 'Item name, positive quantity, and a valid estimated cost are required']);
                exit();
            }
            $req_number = 'PR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("
                INSERT INTO procurement_requests
                (req_number, item_name, product_id, quantity, estimated_unit_cost, supplier_id, department, notes, requested_by, cost_center, budget_code, budget_amount, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'budget_review')
            ");
            $stmt->execute([
                $req_number, $item_name,
                !empty($_POST['product_id']) ? intval($_POST['product_id']) : null,
                $quantity,
                $estimatedCost,
                !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null,
                $_POST['department'] ?? '',
                $_POST['notes'] ?? '',
                $_SESSION['user_id'],
                trim($_POST['cost_center'] ?? ''),
                trim($_POST['budget_code'] ?? ''),
                max(0, floatval($_POST['budget_amount'] ?? 0))
            ]);
            logActivity('procurement_request', "New request $req_number: $quantity x $item_name");
            echo json_encode(['success' => true, 'req_number' => $req_number]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- PROCUREMENT WORKFLOW ACTIONS ----
        if ($_GET['action'] == 'procurement_workflow') {
            $action = $_POST['workflow_action'] ?? '';
            $financeActions = ['budget_approve', 'budget_reject', 'approve_po', 'submit_invoice', 'schedule_payment', 'execute_payment', 'send_payment_advice', 'reconcile_payment'];
            if (in_array($action, $financeActions, true) && !hasRole('finance') && !isAdmin()) {
                echo json_encode(['success' => false, 'message' => 'Finance permission required']);
                exit();
            }
            if (!in_array($action, $financeActions, true) && !canAccess('procurement_manage') && !isAdmin()) {
                echo json_encode(['success' => false, 'message' => 'Procurement permission required']);
                exit();
            }
            try {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid requisition']);
                exit();
            }
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM procurement_requests WHERE id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $req = $stmt->fetch();
            if (!$req) throw new Exception('Requisition not found');

                if ($action === 'budget_approve' || $action === 'budget_reject') {
                    if ($req['status'] !== 'budget_review') throw new Exception('This requisition is not awaiting a budget check');
                    $available = max(0, floatval($_POST['budget_amount'] ?? $req['budget_amount']));
                    $estimate = floatval($req['estimated_unit_cost']) * intval($req['quantity']);
                    if ($action === 'budget_approve' && $available < $estimate) throw new Exception('Available budget is below the estimated request total. Reject or request a reallocation.');
                    $approved = $action === 'budget_approve';
                    $reason = trim($_POST['reason'] ?? '');
                    $notes = $req['notes'] ?? '';
                    if (!$approved && $reason !== '') $notes = trim($notes . "\nFinance rejection: " . $reason);
                    $pdo->prepare("UPDATE procurement_requests SET budget_amount = ?, budget_status = ?, budget_checked_by = ?, budget_checked_at = NOW(), status = ?, approved_by = ?, notes = ? WHERE id = ?")
                        ->execute([$available, $approved ? 'approved' : 'rejected', $_SESSION['user_id'], $approved ? 'sourcing' : 'rejected', $_SESSION['user_id'], $notes, $id]);
                } elseif ($action === 'add_quote') {
                    if ($req['status'] !== 'sourcing') throw new Exception('Quotations can only be added during sourcing');
                    $supplierId = intval($_POST['supplier_id'] ?? 0);
                    $unitCost = floatval($_POST['unit_cost'] ?? -1);
                    $leadTime = intval($_POST['lead_time_days'] ?? 0);
                    if ($supplierId <= 0 || $unitCost < 0 || $leadTime < 0) throw new Exception('Supplier, valid price, and a non-negative lead time are required');
                    $supplierStmt = $pdo->prepare("SELECT id FROM suppliers WHERE id = ? AND status = 'active'");
                    $supplierStmt->execute([$supplierId]);
                    if (!$supplierStmt->fetch()) throw new Exception('Choose an active supplier');
                    $pdo->prepare("INSERT INTO procurement_quotes (request_id, supplier_id, unit_cost, lead_time_days, payment_terms, technical_notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)")
                        ->execute([$id, $supplierId, $unitCost, $leadTime ?: null, trim($_POST['payment_terms'] ?? ''), trim($_POST['technical_notes'] ?? ''), $_SESSION['user_id']]);
                } elseif ($action === 'select_quote') {
                    if ($req['status'] !== 'sourcing') throw new Exception('This requisition is not in sourcing');
                    $quoteStmt = $pdo->prepare("SELECT * FROM procurement_quotes WHERE id = ? AND request_id = ?");
                    $quoteStmt->execute([intval($_POST['quote_id'] ?? 0), $id]);
                    $quote = $quoteStmt->fetch();
                    if (!$quote) throw new Exception('Quotation not found for this requisition');
                    $subtotal = floatval($quote['unit_cost']) * intval($req['quantity']);
                    $total = round($subtotal * (1 + floatval(getSetting('tax_rate', 12)) / 100), 2);
                    $pdo->prepare("UPDATE procurement_requests SET supplier_id = ?, estimated_unit_cost = ?, po_total = ?, payment_terms = ?, status = 'supplier_selected' WHERE id = ?")
                        ->execute([$quote['supplier_id'], $quote['unit_cost'], $total, $quote['payment_terms'], $id]);
                } elseif ($action === 'create_po') {
                    if ($req['status'] !== 'supplier_selected' || empty($req['supplier_id'])) throw new Exception('Select a supplier before creating a purchase order');
                    $subtotal = floatval($req['estimated_unit_cost']) * intval($req['quantity']);
                    $taxAmount = round(floatval($req['po_total']) - $subtotal, 2);
                    $poNumber = 'PO-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
                    $threshold = max(0, floatval(getSetting('procurement_po_threshold', 50000)));
                    $nextStatus = floatval($req['po_total']) > $threshold ? 'po_pending' : 'ordered';
                    $purchaseId = null;
                    if ($nextStatus === 'ordered') {
                        $pdo->prepare("INSERT INTO purchases (po_number, supplier_id, purchase_date, subtotal, tax_amount, total_amount, payment_status, notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?, 'unpaid', ?, ?)")
                            ->execute([$poNumber, $req['supplier_id'], date('Y-m-d'), $subtotal, $taxAmount, $req['po_total'], "Purchase order for {$req['req_number']}", $_SESSION['user_id']]);
                        $purchaseId = $pdo->lastInsertId();
                        if (!empty($req['product_id'])) {
                            $pdo->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_cost, total_cost) VALUES (?, ?, ?, ?, ?)")
                                ->execute([$purchaseId, $req['product_id'], $req['quantity'], $req['estimated_unit_cost'], $subtotal]);
                        }
                    }
                    $pdo->prepare("UPDATE procurement_requests SET po_number = ?, po_issued_at = NOW(), purchase_id = ?, status = ? WHERE id = ?")
                        ->execute([$poNumber, $purchaseId, $nextStatus, $id]);
                } elseif ($action === 'approve_po') {
                    if ($req['status'] !== 'po_pending') throw new Exception('This purchase order is not awaiting finance approval');
                    $subtotal = floatval($req['estimated_unit_cost']) * intval($req['quantity']);
                    $taxAmount = round(floatval($req['po_total']) - $subtotal, 2);
                    $pdo->prepare("INSERT INTO purchases (po_number, supplier_id, purchase_date, subtotal, tax_amount, total_amount, payment_status, notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?, 'unpaid', ?, ?)")
                        ->execute([$req['po_number'], $req['supplier_id'], date('Y-m-d'), $subtotal, $taxAmount, $req['po_total'], "Purchase order for {$req['req_number']}", $_SESSION['user_id']]);
                    $purchaseId = $pdo->lastInsertId();
                    if (!empty($req['product_id'])) {
                        $pdo->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_cost, total_cost) VALUES (?, ?, ?, ?, ?)")
                            ->execute([$purchaseId, $req['product_id'], $req['quantity'], $req['estimated_unit_cost'], $subtotal]);
                    }
                    $pdo->prepare("UPDATE procurement_requests SET status = 'ordered', approved_by = ?, purchase_id = ? WHERE id = ?")
                        ->execute([$_SESSION['user_id'], $purchaseId, $id]);
                } elseif ($action === 'acknowledge_po') {
                    if ($req['status'] !== 'ordered') throw new Exception('Only an issued purchase order can be acknowledged');
                    $pdo->prepare("UPDATE procurement_requests SET status = 'acknowledged', po_acknowledged_at = NOW() WHERE id = ?")->execute([$id]);
                } elseif ($action === 'receive_goods') {
                    if (!in_array($req['status'], ['ordered', 'acknowledged', 'received', 'invoice_review'], true)) throw new Exception('Goods cannot be received at this stage');
                    $quantity = intval($_POST['received_quantity'] ?? 0);
                    $remaining = intval($req['quantity']) - intval($req['received_quantity']);
                    if ($quantity <= 0 || $quantity > $remaining) throw new Exception('Received quantity must be positive and no greater than the remaining quantity (' . $remaining . ')');
                    $grn = trim($_POST['grn_number'] ?? '') ?: 'GRN-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
                    if (!empty($req['product_id'])) {
                        $productStmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ? FOR UPDATE");
                        $productStmt->execute([$req['product_id']]);
                        $product = $productStmt->fetch();
                        if (!$product) throw new Exception('Linked product no longer exists');
                        $before = intval($product['stock_quantity']);
                        $after = $before + $quantity;
                        $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?")->execute([$after, $req['product_id']]);
                        $pdo->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, quantity_before, quantity_after, reason, reference_id, reference_type, user_id) VALUES (?, 'purchase', ?, ?, ?, ?, ?, 'purchase', ?)")
                            ->execute([$req['product_id'], $quantity, $before, $after, "Goods receipt $grn for {$req['req_number']}", $id, $_SESSION['user_id']]);
                    }
                    $newReceived = intval($req['received_quantity']) + $quantity;
                    $pdo->prepare("UPDATE procurement_requests SET received_quantity = ?, grn_number = ?, received_at = NOW(), receipt_notes = ?, status = 'received' WHERE id = ?")
                        ->execute([$newReceived, $grn, trim($_POST['receipt_notes'] ?? ''), $id]);
                } elseif ($action === 'submit_invoice') {
                    if (!in_array($req['status'], ['received', 'invoice_review'], true)) throw new Exception('An invoice can only be matched after goods receipt');
                    $invoiceNumber = trim($_POST['invoice_number'] ?? '');
                    $invoiceAmount = floatval($_POST['invoice_amount'] ?? -1);
                    $invoiceQuantity = intval($_POST['invoice_quantity'] ?? 0);
                    if ($invoiceNumber === '' || $invoiceAmount < 0 || $invoiceQuantity <= 0) throw new Exception('Invoice number, amount, and quantity are required');
                    $paymentDueDate = null;
                    if (preg_match('/net\s*(\d+)/i', $req['payment_terms'] ?? '', $termsMatch)) {
                        $paymentDueDate = (new DateTimeImmutable())->modify('+' . intval($termsMatch[1]) . ' days')->format('Y-m-d');
                    }
                    $matched = intval($req['received_quantity']) === intval($req['quantity'])
                        && $invoiceQuantity === intval($req['quantity'])
                        && abs($invoiceAmount - floatval($req['po_total'])) <= 0.01;
                    $invoiceStatus = $matched ? 'matched' : 'held';
                    $pdo->prepare("UPDATE procurement_requests SET invoice_number = ?, invoice_amount = ?, invoice_quantity = ?, invoice_status = ?, invoice_notes = ?, invoice_received_at = NOW(), payment_due_date = ?, status = ? WHERE id = ?")
                        ->execute([$invoiceNumber, $invoiceAmount, $invoiceQuantity, $invoiceStatus, trim($_POST['invoice_notes'] ?? ''), $paymentDueDate, $matched ? 'invoiced' : 'invoice_review', $id]);
                } elseif ($action === 'schedule_payment') {
                    if ($req['status'] !== 'invoiced' || $req['invoice_status'] !== 'matched') throw new Exception('Only a fully matched invoice can be scheduled for payment');
                    $scheduledFor = trim($_POST['payment_scheduled_for'] ?? '');
                    $scheduledDate = DateTime::createFromFormat('!Y-m-d', $scheduledFor);
                    if (!$scheduledDate || $scheduledDate->format('Y-m-d') !== $scheduledFor) throw new Exception('Enter a valid scheduled payment date');
                    $pdo->prepare("UPDATE procurement_requests SET status = 'payment_scheduled', payment_scheduled_for = ?, payment_scheduled_at = NOW() WHERE id = ?")
                        ->execute([$scheduledFor, $id]);
                } elseif ($action === 'execute_payment') {
                    if ($req['status'] !== 'payment_scheduled') throw new Exception('Schedule payment before executing it');
                    $method = $_POST['payment_method'] ?? '';
                    $paymentMethods = ['bank_transfer', 'cheque', 'virtual_card'];
                    $reference = trim($_POST['payment_reference'] ?? '');
                    $paymentDate = trim($_POST['payment_date'] ?? '');
                    $validPaymentDate = DateTime::createFromFormat('!Y-m-d', $paymentDate);
                    if (!in_array($method, $paymentMethods, true) || $reference === '' || !$validPaymentDate || $validPaymentDate->format('Y-m-d') !== $paymentDate) {
                        throw new Exception('Choose a payment method and provide a valid payment date and confirmation reference');
                    }
                    $pdo->prepare("UPDATE procurement_requests SET status = 'paid', invoice_status = 'paid', paid_at = CONCAT(?, ' 00:00:00'), payment_method = ?, payment_reference = ? WHERE id = ?")
                        ->execute([$paymentDate, $method, $reference, $id]);
                    if (!empty($req['purchase_id'])) $pdo->prepare("UPDATE purchases SET payment_status = 'paid' WHERE id = ?")->execute([$req['purchase_id']]);
                } elseif ($action === 'send_payment_advice') {
                    if ($req['status'] !== 'paid' || $req['payment_advice_sent_at']) throw new Exception('Payment advice can only be recorded once after payment is executed');
                    $recipient = trim($_POST['payment_advice_recipient'] ?? '') ?: trim($req['supplier_email'] ?? '');
                    if ($recipient === '') throw new Exception('Enter the supplier email or contact who received the payment advice');
                    $pdo->prepare("UPDATE procurement_requests SET payment_advice_sent_at = NOW(), payment_advice_recipient = ? WHERE id = ?")
                        ->execute([$recipient, $id]);
                } elseif ($action === 'reconcile_payment') {
                    if ($req['status'] !== 'paid' || !$req['payment_advice_sent_at']) throw new Exception('Record supplier payment advice before reconciliation');
                    $pdo->prepare("UPDATE procurement_requests SET supplier_reconciled_at = NOW(), accrual_released_at = NOW() WHERE id = ?")
                        ->execute([$id]);
                } elseif ($action === 'close_po') {
                    if ($req['status'] !== 'paid' || !$req['supplier_reconciled_at']) throw new Exception('Reconcile the supplier payment before closing the PO');
                    if (($_POST['issues_resolved'] ?? '') !== '1') throw new Exception('Confirm that all open issues are resolved before closing');
                    $otif = $_POST['on_time_in_full'] ?? '';
                    $quality = intval($_POST['quality_rating'] ?? 0);
                    $responsiveness = intval($_POST['responsiveness_rating'] ?? 0);
                    if (!in_array($otif, ['yes', 'no', 'partial'], true) || $quality < 1 || $quality > 5 || $responsiveness < 1 || $responsiveness > 5) {
                        throw new Exception('Complete the delivery result and both supplier ratings (1 to 5)');
                    }
                    $pdo->prepare("INSERT INTO procurement_supplier_performance (request_id, supplier_id, on_time_in_full, quality_rating, responsiveness_rating, issue_resolution, lessons_learned, rated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                        ->execute([$id, $req['supplier_id'], $otif, $quality, $responsiveness, trim($_POST['issue_resolution'] ?? ''), trim($_POST['lessons_learned'] ?? ''), $_SESSION['user_id']]);
                    $pdo->prepare("UPDATE procurement_requests SET status = 'closed', closed_at = NOW() WHERE id = ?")->execute([$id]);
                } else {
                    throw new Exception('Unknown procurement action');
                }

            $pdo->commit();
            logActivity('procurement_workflow', "Procurement request {$req['req_number']} action: $action");
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ====================================================================
    // PURCHASE REQUESTS  (Inventory creates -> Admin approves)
    // ====================================================================

    // ---- LIST PURCHASE REQUESTS ----
    if ($_GET['action'] == 'get_purchase_requests') {
        if (!canAccess('procurement') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit();
        }
        try {
            $where = []; $params = [];
            if (!empty($_GET['status'])) { $where[] = 'r.status = ?'; $params[] = $_GET['status']; }
            if (!empty($_GET['supplier_id'])) { $where[] = 'r.supplier_id = ?'; $params[] = intval($_GET['supplier_id']); }
            if (!empty($_GET['requester_id'])) { $where[] = 'r.requester_id = ?'; $params[] = intval($_GET['requester_id']); }
            if (!empty($_GET['mine'])) { $where[] = 'r.requester_id = ?'; $params[] = intval($_SESSION['user_id']); }
            if (!empty($_GET['q'])) {
                $where[] = '(r.request_number LIKE ? OR r.reason LIKE ? OR s.name LIKE ?)';
                $like = '%' . trim($_GET['q']) . '%'; array_push($params, $like, $like, $like);
            }
            if (!empty($_GET['date_from'])) { $where[] = 'r.request_date >= ?'; $params[] = $_GET['date_from']; }
            if (!empty($_GET['date_to'])) { $where[] = 'r.request_date <= ?'; $params[] = $_GET['date_to']; }
            $sql = "SELECT r.*, s.name AS supplier_name, s.status AS supplier_status, u.full_name AS requester_name,
                    (SELECT COUNT(*) FROM purchase_request_items i WHERE i.request_id = r.id) AS item_count,
                    (SELECT COALESCE(SUM(i.quantity),0) FROM purchase_request_items i WHERE i.request_id = r.id) AS total_quantity,
                    (SELECT po.po_number FROM purchase_orders po WHERE po.request_id = r.id AND po.status <> 'cancelled' ORDER BY po.id DESC LIMIT 1) AS po_number,
                    (SELECT po.status FROM purchase_orders po WHERE po.request_id = r.id AND po.status <> 'cancelled' ORDER BY po.id DESC LIMIT 1) AS po_status
                FROM purchase_requests r
                LEFT JOIN suppliers s ON r.supplier_id = s.id
                LEFT JOIN users u ON r.requester_id = u.id";
            if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
            $sql .= ' ORDER BY r.created_at DESC LIMIT 500';
            $stmt = $pdo->prepare($sql); $stmt->execute($params);
            echo json_encode(['success' => true, 'requests' => $stmt->fetchAll()]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit();
    }

    // ---- PURCHASE REQUEST DETAIL (items + approval history + PO + receipts) ----
    if ($_GET['action'] == 'get_purchase_request') {
        if (!canAccess('procurement') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit();
        }
        try {
            $id = intval($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT r.*, s.name AS supplier_name, s.status AS supplier_status,
                    u.full_name AS requester_name, ab.full_name AS approved_by_name, rv.full_name AS reviewed_by_name
                FROM purchase_requests r
                LEFT JOIN suppliers s ON r.supplier_id = s.id
                LEFT JOIN users u ON r.requester_id = u.id
                LEFT JOIN users ab ON r.approved_by = ab.id
                LEFT JOIN users rv ON r.reviewed_by = rv.id
                WHERE r.id = ?");
            $stmt->execute([$id]);
            $request = $stmt->fetch();
            if (!$request) { echo json_encode(['success' => false, 'message' => 'Purchase request not found']); exit(); }
            $items = $pdo->prepare("SELECT i.*, p.name AS product_name, p.stock_quantity, p.sku, p.unit AS product_unit FROM purchase_request_items i LEFT JOIN products p ON i.product_id = p.id WHERE i.request_id = ? ORDER BY i.id");
            $items->execute([$id]);
            $logs = $pdo->prepare("SELECT l.*, u.full_name AS actor_name FROM procurement_approval_logs l LEFT JOIN users u ON l.actor_id = u.id WHERE l.request_id = ? ORDER BY l.created_at ASC, l.id ASC");
            $logs->execute([$id]);
            $orders = $pdo->prepare("SELECT po.*, s.name AS supplier_name FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.id WHERE po.request_id = ? ORDER BY po.id DESC");
            $orders->execute([$id]);
            $receipts = $pdo->prepare("SELECT gr.*, u.full_name AS received_by_name FROM goods_receipts gr LEFT JOIN users u ON gr.received_by = u.id WHERE gr.po_id IN (SELECT id FROM purchase_orders WHERE request_id = ?) ORDER BY gr.created_at DESC");
            $receipts->execute([$id]);
            echo json_encode(['success' => true, 'request' => $request, 'items' => $items->fetchAll(), 'logs' => $logs->fetchAll(), 'orders' => $orders->fetchAll(), 'receipts' => $receipts->fetchAll()]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit();
    }

    // ---- SAVE / SUBMIT PURCHASE REQUEST (create or edit a draft) ----
    if ($_GET['action'] == 'save_purchase_request') {
        if (!canAccess('procurement_manage') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to create purchase requests']); exit();
        }
        try {
            $id = intval($_POST['id'] ?? 0);
            $submit = ($_POST['action_type'] ?? 'draft') === 'submit';
            $ackWarnings = ($_POST['acknowledge_warnings'] ?? '') === '1';
            $supplierId = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;
            $requestDate = trim($_POST['request_date'] ?? '') ?: date('Y-m-d');
            $department = substr(trim($_POST['department'] ?? ''), 0, 100);
            $reason = trim($_POST['reason'] ?? '');
            $remarks = trim($_POST['remarks'] ?? '');
            $costCenter = substr(trim($_POST['cost_center'] ?? ''), 0, 100);
            $budgetCode = substr(trim($_POST['budget_code'] ?? ''), 0, 100);
            $budgetAmount = max(0, round(floatval($_POST['budget_amount'] ?? 0), 2));
            $items = json_decode($_POST['items'] ?? '[]', true);
            if (!is_array($items)) $items = [];

            if ($submit) {
                if ($supplierId) {
                    $s = $pdo->prepare("SELECT id FROM suppliers WHERE id = ? AND status = 'active'");
                    $s->execute([$supplierId]);
                    if (!$s->fetch()) { echo json_encode(['success' => false, 'message' => 'Choose an active registered supplier, or leave the supplier to be selected during sourcing.']); exit(); }
                }
                if ($reason === '') { echo json_encode(['success' => false, 'message' => 'Reason for purchase is required.']); exit(); }
                if ($costCenter === '') { echo json_encode(['success' => false, 'message' => 'Cost centre is required before submitting.']); exit(); }
                if ($budgetCode === '') { echo json_encode(['success' => false, 'message' => 'Budget code is required before submitting.']); exit(); }
            }
            $validation = procurementValidateItems($items, $submit);
            if (!$validation['valid']) { echo json_encode(['success' => false, 'message' => implode(' ', $validation['errors'])]); exit(); }

            $quotationPath = null;
            if (isset($_FILES['quotation']) && $_FILES['quotation']['error'] === UPLOAD_ERR_OK) {
                if ($_FILES['quotation']['size'] > 5 * 1024 * 1024) { echo json_encode(['success' => false, 'message' => 'Quotation file is too large (maximum 5MB).']); exit(); }
                $uploadDir = 'uploads/procurement/';
                if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES['quotation']['tmp_name']);
                finfo_close($finfo);
                $ext = strtolower(pathinfo($_FILES['quotation']['name'], PATHINFO_EXTENSION));
                if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true) || !in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid quotation file type. Upload a PDF, JPG, or PNG.']); exit();
                }
                $fileName = 'pr_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $targetPath = $uploadDir . $fileName;
                if (!move_uploaded_file($_FILES['quotation']['tmp_name'], $targetPath)) { echo json_encode(['success' => false, 'message' => 'Failed to store the quotation file.']); exit(); }
                $quotationPath = $targetPath;
            }

            if ($submit && !$ackWarnings) {
                $warnings = procurementDuplicateWarnings($validation['clean'], $id);
                if ($warnings) {
                    echo json_encode(['success' => false, 'requires_ack' => true, 'warnings' => $warnings, 'message' => 'Please review the warnings before submitting.']);
                    exit();
                }
            }

            $pdo->beginTransaction();

            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT * FROM purchase_requests WHERE id = ? FOR UPDATE");
                $stmt->execute([$id]);
                $existing = $stmt->fetch();
                if (!$existing) throw new Exception('Purchase request not found.');
                if ((int)$existing['requester_id'] !== (int)$_SESSION['user_id'] && !isAdmin()) throw new Exception('You can only edit your own purchase requests.');
                if (!in_array($existing['status'], ['draft', 'revision_requested'], true)) throw new Exception('This request can no longer be edited (status: ' . $existing['status'] . ').');
                $status = $submit ? 'budget_review' : 'draft';
                $budgetStatus = $submit ? 'pending' : $existing['budget_status'];
                $submittedAt = $submit ? date('Y-m-d H:i:s') : $existing['submitted_at'];
                $revisionInc = ($submit && $existing['status'] === 'revision_requested') ? 1 : 0;
                $pdo->prepare("UPDATE purchase_requests SET supplier_id=?, request_date=?, department=?, reason=?, remarks=?, estimated_total=?, cost_center=?, budget_code=?, budget_amount=?, budget_status=?, status=?, submitted_at=?, revision_count = revision_count + ? WHERE id=?")
                    ->execute([$supplierId, $requestDate, $department, $reason, $remarks, $validation['total'], $costCenter, $budgetCode, $budgetAmount, $budgetStatus, $status, $submittedAt, $revisionInc, $id]);
                if ($quotationPath) { $pdo->prepare("UPDATE purchase_requests SET quotation_path = ? WHERE id = ?")->execute([$quotationPath, $id]); }
                $requestId = $id;
                $number = $existing['request_number'];
                $pdo->prepare("DELETE FROM purchase_request_items WHERE request_id = ?")->execute([$id]);
            } else {
                $number = generateProcurementNumber('purchase_requests', 'request_number', 'PR');
                $status = $submit ? 'budget_review' : 'draft';
                $pdo->prepare("INSERT INTO purchase_requests (request_number, request_date, requester_id, supplier_id, department, reason, remarks, quotation_path, estimated_total, cost_center, budget_code, budget_amount, budget_status, status, submitted_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                    ->execute([$number, $requestDate, $_SESSION['user_id'], $supplierId, $department, $reason, $remarks, $quotationPath, $validation['total'], $costCenter, $budgetCode, $budgetAmount, 'pending', $status, $submit ? date('Y-m-d H:i:s') : null]);
                $requestId = (int)$pdo->lastInsertId();
            }

            $itemStmt = $pdo->prepare("INSERT INTO purchase_request_items (request_id, product_id, quantity, unit_of_measure, estimated_unit_price, line_total, notes) VALUES (?,?,?,?,?,?,?)");
            foreach ($validation['clean'] as $c) {
                $itemStmt->execute([$requestId, $c['product_id'], $c['quantity'], $c['unit_of_measure'], $c['estimated_unit_price'], $c['line_total'], $c['notes']]);
            }

            $logStmt = $pdo->prepare("INSERT INTO procurement_approval_logs (request_id, action, actor_id, actor_role, comments) VALUES (?,?,?,?,?)");
            $logStmt->execute([$requestId, $submit ? 'submitted' : ($id ? 'updated' : 'created'), $_SESSION['user_id'], $_SESSION['role'] ?? '', $submit ? 'Submitted for approval' : 'Saved as draft']);

            if ($submit) {
                notifyProcurementRole('finance', 'purchase_request', 'Budget review needed: ' . $number, ($_SESSION['full_name'] ?? 'Inventory') . ' submitted ' . $number . ' for a budget availability check.', $requestId);
            }

            $pdo->commit();
            logActivity('procurement_request', ($submit ? 'Submitted' : 'Saved') . " purchase request $number");
            echo json_encode(['success' => true, 'id' => $requestId, 'request_number' => $number, 'status' => $status]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- SUBMIT AN EXISTING DRAFT / REVISED REQUEST ----
    if ($_GET['action'] == 'submit_purchase_request') {
        if (!canAccess('procurement_manage') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit();
        }
        try {
            $id = intval($_POST['id'] ?? 0);
            $ackWarnings = ($_POST['acknowledge_warnings'] ?? '') === '1';
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM purchase_requests WHERE id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $req = $stmt->fetch();
            if (!$req) throw new Exception('Purchase request not found.');
            if ((int)$req['requester_id'] !== (int)$_SESSION['user_id'] && !isAdmin()) throw new Exception('You can only submit your own purchase requests.');
            if (!in_array($req['status'], ['draft', 'revision_requested'], true)) throw new Exception('Only draft or revision-requested items can be submitted.');
            if (!empty($req['supplier_id'])) {
                $s = $pdo->prepare("SELECT id FROM suppliers WHERE id = ? AND status = 'active'");
                $s->execute([$req['supplier_id']]);
                if (!$s->fetch()) throw new Exception('The selected supplier is not active.');
            }
            if (trim($req['reason'] ?? '') === '') throw new Exception('Reason for purchase is required.');
            if (trim($req['cost_center'] ?? '') === '') throw new Exception('A cost centre is required before submitting.');
            if (trim($req['budget_code'] ?? '') === '') throw new Exception('A budget code is required before submitting.');

            $items = $pdo->prepare("SELECT * FROM purchase_request_items WHERE request_id = ?");
            $items->execute([$id]);
            $rows = $items->fetchAll();
            if (!$rows) throw new Exception('At least one product line is required.');
            $clean = []; $total = 0;
            foreach ($rows as $r) {
                if (floatval($r['quantity']) <= 0) throw new Exception('Each line must have a positive quantity.');
                if (floatval($r['estimated_unit_price']) <= 0) throw new Exception('Each line must have a positive estimated unit price.');
                $clean[] = ['product_id' => (int)$r['product_id'], 'quantity' => floatval($r['quantity']), 'estimated_unit_price' => floatval($r['estimated_unit_price']), 'line_total' => floatval($r['line_total'])];
                $total += floatval($r['line_total']);
            }
            if (!$ackWarnings) {
                $warnings = procurementDuplicateWarnings($clean, $id);
                if ($warnings) {
                    $pdo->rollBack();
                    echo json_encode(['success' => false, 'requires_ack' => true, 'warnings' => $warnings, 'message' => 'Please review the warnings before submitting.']);
                    exit();
                }
            }
            $pdo->prepare("UPDATE purchase_requests SET status='budget_review', budget_status='pending', submitted_at=NOW(), estimated_total=?, revision_count = revision_count + ? WHERE id=?")
                ->execute([round($total, 2), $req['status'] === 'revision_requested' ? 1 : 0, $id]);
            $pdo->prepare("INSERT INTO procurement_approval_logs (request_id, action, actor_id, actor_role, comments) VALUES (?, 'submitted', ?, ?, 'Submitted for budget check')")
                ->execute([$id, $_SESSION['user_id'], $_SESSION['role'] ?? '']);
            notifyProcurementRole('finance', 'purchase_request', 'Budget review needed: ' . $req['request_number'], ($_SESSION['full_name'] ?? 'Inventory') . ' submitted ' . $req['request_number'] . ' for a budget availability check.', $id);
            $pdo->commit();
            logActivity('procurement_request', "Submitted purchase request {$req['request_number']}");
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- DELETE A DRAFT PURCHASE REQUEST ----
    if ($_GET['action'] == 'delete_purchase_request') {
        if (!canAccess('procurement_manage') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit();
        }
        try {
            $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM purchase_requests WHERE id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $req = $stmt->fetch();
            if (!$req) throw new Exception('Purchase request not found.');
            if ((int)$req['requester_id'] !== (int)$_SESSION['user_id'] && !isAdmin()) throw new Exception('You can only delete your own purchase requests.');
            if ($req['status'] !== 'draft') throw new Exception('Only draft requests can be deleted. Cancel submitted requests instead.');
            $pdo->prepare("DELETE FROM procurement_approval_logs WHERE request_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM purchase_requests WHERE id = ?")->execute([$id]);
            $pdo->commit();
            logActivity('procurement_request', "Deleted draft purchase request {$req['request_number']}");
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- CANCEL A PURCHASE REQUEST ----
    if ($_GET['action'] == 'cancel_purchase_request') {
        if (!canAccess('procurement_manage') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit();
        }
        try {
            $id = intval($_POST['id'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT r.*, (SELECT COUNT(*) FROM purchase_orders po WHERE po.request_id = r.id AND po.status <> 'cancelled') AS active_pos FROM purchase_requests r WHERE r.id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $req = $stmt->fetch();
            if (!$req) throw new Exception('Purchase request not found.');
            if ((int)$req['requester_id'] !== (int)$_SESSION['user_id'] && !isAdmin()) throw new Exception('You can only cancel your own purchase requests.');
            if (!in_array($req['status'], ['draft', 'budget_review', 'pending_approval', 'revision_requested', 'approved', 'sourcing', 'supplier_selected'], true)) throw new Exception('This request cannot be cancelled at its current stage.');
            if (intval($req['active_pos']) > 0) throw new Exception('Cancel the linked purchase order before cancelling this request.');
            $pdo->prepare("UPDATE purchase_requests SET status='cancelled', cancelled_at=NOW(), review_comments=? WHERE id=?")
                ->execute([$reason, $id]);
            $pdo->prepare("INSERT INTO procurement_approval_logs (request_id, action, actor_id, actor_role, comments) VALUES (?, 'cancelled', ?, ?, ?)")
                ->execute([$id, $_SESSION['user_id'], $_SESSION['role'] ?? '', $reason ?: 'Cancelled']);
            if ($req['status'] === 'pending_approval') {
                notifyProcurementAdmins('cancelled', 'Purchase request cancelled', $req['request_number'] . ' was cancelled by ' . ($_SESSION['full_name'] ?? 'Inventory') . '.', $id);
            }
            $pdo->commit();
            logActivity('procurement_request', "Cancelled purchase request {$req['request_number']}");
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- ADMIN DECISION: approve / reject / request revision ----
    if ($_GET['action'] == 'procurement_decision') {
        if (!canAccess('procurement_approve') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Only an authorized approver may decide on purchase requests']); exit();
        }
        try {
            $id = intval($_POST['id'] ?? 0);
            $decision = $_POST['decision'] ?? '';
            $comments = trim($_POST['comments'] ?? '');
            if (!in_array($decision, ['approve', 'reject', 'request_revision'], true)) throw new Exception('Unknown approval decision.');
            if (in_array($decision, ['reject', 'request_revision'], true) && $comments === '') throw new Exception('A comment is required to reject a request or ask for revisions.');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM purchase_requests WHERE id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $req = $stmt->fetch();
            if (!$req) throw new Exception('Purchase request not found.');
            if ($req['status'] !== 'pending_approval') throw new Exception('This request is not awaiting approval (status: ' . $req['status'] . ').');
            if ((int)$req['requester_id'] === (int)$_SESSION['user_id']) throw new Exception('You cannot approve or reject your own purchase request.');
            $map = [
                'approve' => ['approved', 'approved'],
                'reject' => ['rejected', 'rejected'],
                'request_revision' => ['revision_requested', 'revision_requested'],
            ];
            list($newStatus, $logAction) = $map[$decision];
            $approvedBy = $decision === 'approve' ? $_SESSION['user_id'] : $req['approved_by'];
            $approvedAt = $decision === 'approve' ? date('Y-m-d H:i:s') : $req['approved_at'];
            $pdo->prepare("UPDATE purchase_requests SET status=?, reviewed_by=?, reviewed_at=NOW(), review_comments=?, approved_by=?, approved_at=? WHERE id=?")
                ->execute([$newStatus, $_SESSION['user_id'], $comments, $approvedBy, $approvedAt, $id]);
            $pdo->prepare("INSERT INTO procurement_approval_logs (request_id, action, actor_id, actor_role, comments) VALUES (?,?,?,?,?)")
                ->execute([$id, $logAction, $_SESSION['user_id'], $_SESSION['role'] ?? '', $comments]);
            $note = ['approve' => 'was approved.', 'reject' => 'was rejected.', 'request_revision' => 'needs revisions.'];
            notifyProcurementUser((int)$req['requester_id'], 'decision', ucfirst(str_replace('_', ' ', $decision)) . ': ' . $req['request_number'], 'Your purchase request ' . $req['request_number'] . ' ' . $note[$decision] . ($comments ? ' Comment: ' . $comments : ''), $id);
            $pdo->commit();
            logActivity('procurement_decision', "Purchase request {$req['request_number']} $logAction");
            echo json_encode(['success' => true, 'status' => $newStatus]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ====================================================================
    // STEP 2 — FINANCE BUDGET AVAILABILITY CHECK
    // ====================================================================

    if ($_GET['action'] == 'budget_decision') {
        if (!canAccess('procurement_finance') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Only Finance can perform the budget availability check']); exit();
        }
        try {
            $id = intval($_POST['id'] ?? 0);
            $decision = $_POST['decision'] ?? '';
            $comments = trim($_POST['comments'] ?? '');
            $available = max(0, round(floatval($_POST['available_budget'] ?? 0), 2));
            if (!in_array($decision, ['approve', 'reject', 'reallocate'], true)) throw new Exception('Unknown budget decision.');
            if ($decision !== 'approve' && $comments === '') throw new Exception('A comment is required to reject a request or ask for a reallocation.');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM purchase_requests WHERE id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $req = $stmt->fetch();
            if (!$req) throw new Exception('Purchase request not found.');
            if ($req['status'] !== 'budget_review') throw new Exception('This request is not awaiting a budget check (status: ' . $req['status'] . ').');
            $estimate = floatval($req['estimated_total']);
            if ($decision === 'approve') {
                if ($available <= 0) $available = floatval($req['budget_amount']);
                if ($available < $estimate) throw new Exception('Available budget (₱' . number_format($available, 2) . ') is below the estimated request total (₱' . number_format($estimate, 2) . '). Reject or request a reallocation.');
                $pdo->prepare("UPDATE purchase_requests SET budget_amount=?, budget_status='approved', budget_checked_by=?, budget_checked_at=NOW(), budget_comments=?, status='pending_approval' WHERE id=?")
                    ->execute([$available, $_SESSION['user_id'], $comments, $id]);
                $pdo->prepare("INSERT INTO procurement_approval_logs (request_id, action, actor_id, actor_role, comments) VALUES (?, 'budget_approved', ?, ?, ?)")
                    ->execute([$id, $_SESSION['user_id'], $_SESSION['role'] ?? '', 'Budget available: ₱' . number_format($available, 2) . ($comments ? ' — ' . $comments : '')]);
                notifyProcurementAdmins('purchase_request', 'Approval needed: ' . $req['request_number'], 'Budget check passed for ' . $req['request_number'] . '. It now awaits management approval.', $id);
                notifyProcurementUser((int)$req['requester_id'], 'budget_approved', 'Budget approved: ' . $req['request_number'], 'Finance approved the budget for ' . $req['request_number'] . '. It is now awaiting management approval.', $id);
            } elseif ($decision === 'reject') {
                $pdo->prepare("UPDATE purchase_requests SET budget_status='rejected', budget_checked_by=?, budget_checked_at=NOW(), budget_comments=?, review_comments=?, status='rejected' WHERE id=?")
                    ->execute([$_SESSION['user_id'], $comments, $comments, $id]);
                $pdo->prepare("INSERT INTO procurement_approval_logs (request_id, action, actor_id, actor_role, comments) VALUES (?, 'budget_rejected', ?, ?, ?)")
                    ->execute([$id, $_SESSION['user_id'], $_SESSION['role'] ?? '', $comments]);
                notifyProcurementUser((int)$req['requester_id'], 'budget_rejected', 'Budget rejected: ' . $req['request_number'], 'Finance rejected the budget for ' . $req['request_number'] . '. ' . $comments, $id);
            } else {
                $pdo->prepare("UPDATE purchase_requests SET budget_status='revision', budget_checked_by=?, budget_checked_at=NOW(), budget_comments=?, review_comments=?, status='revision_requested' WHERE id=?")
                    ->execute([$_SESSION['user_id'], $comments, $comments, $id]);
                $pdo->prepare("INSERT INTO procurement_approval_logs (request_id, action, actor_id, actor_role, comments) VALUES (?, 'budget_reallocate', ?, ?, ?)")
                    ->execute([$id, $_SESSION['user_id'], $_SESSION['role'] ?? '', $comments]);
                notifyProcurementUser((int)$req['requester_id'], 'budget_reallocate', 'Reallocation needed: ' . $req['request_number'], 'Finance requested a budget reallocation for ' . $req['request_number'] . '. ' . $comments, $id);
            }
            $pdo->commit();
            logActivity('procurement_budget', "Budget $decision for {$req['request_number']}");
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ====================================================================
    // STEPS 3-4 — SOURCING / RFQ + SUPPLIER SELECTION & NEGOTIATION
    // ====================================================================

    // ---- ISSUE AN RFQ FOR AN APPROVED REQUEST (step 3) ----
    if ($_GET['action'] == 'send_rfq') {
        if (!canAccess('procurement_manage') && !canAccess('procurement_po_manage') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to issue RFQs']); exit();
        }
        try {
            $id = intval($_POST['id'] ?? 0);
            $deadline = trim($_POST['rfq_deadline'] ?? '');
            $validDeadline = null;
            if ($deadline !== '') {
                $validDeadline = DateTime::createFromFormat('!Y-m-d', $deadline);
                if (!$validDeadline || $validDeadline->format('Y-m-d') !== $deadline) throw new Exception('Enter a valid RFQ deadline.');
            }
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM purchase_requests WHERE id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $req = $stmt->fetch();
            if (!$req) throw new Exception('Purchase request not found.');
            if (!in_array($req['status'], ['approved', 'sourcing'], true)) throw new Exception('Only an approved request can be sourced (status: ' . $req['status'] . ').');
            $rfqNumber = $req['rfq_number'] ?: generateProcurementNumber('purchase_requests', 'rfq_number', 'RFQ');
            $pdo->prepare("UPDATE purchase_requests SET status='sourcing', rfq_number=?, rfq_status='sent', rfq_deadline=?, rfq_issued_at=NOW() WHERE id=?")
                ->execute([$rfqNumber, $validDeadline ? $validDeadline->format('Y-m-d') : null, $id]);
            $pdo->prepare("INSERT INTO procurement_approval_logs (request_id, action, actor_id, actor_role, comments) VALUES (?, 'rfq_issued', ?, ?, ?)")
                ->execute([$id, $_SESSION['user_id'], $_SESSION['role'] ?? '', "RFQ $rfqNumber issued to suppliers" . ($validDeadline ? ' (due ' . $validDeadline->format('Y-m-d') . ')' : '')]);
            $pdo->commit();
            logActivity('procurement_rfq', "Issued RFQ $rfqNumber for {$req['request_number']}");
            echo json_encode(['success' => true, 'rfq_number' => $rfqNumber]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- LIST QUOTATIONS RECEIVED FOR A REQUEST (step 3 evaluation) ----
    if ($_GET['action'] == 'get_rfq_quotes') {
        if (!canAccess('procurement') && !isAdmin()) { echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit(); }
        try {
            $id = intval($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT q.*, s.name AS supplier_name, s.email AS supplier_email,
                    (SELECT ROUND(AVG(r.quality_score), 1) FROM purchase_supplier_ratings r WHERE r.supplier_id = q.supplier_id) AS quality_average,
                    (SELECT ROUND(AVG(r.responsiveness_score), 1) FROM purchase_supplier_ratings r WHERE r.supplier_id = q.supplier_id) AS responsiveness_average,
                    (SELECT ROUND(AVG(r.otif_score), 1) FROM purchase_supplier_ratings r WHERE r.supplier_id = q.supplier_id) AS otif_average,
                    (SELECT GROUP_CONCAT(NULLIF(r.lessons_learned, '') ORDER BY r.created_at DESC SEPARATOR ' | ') FROM purchase_supplier_ratings r WHERE r.supplier_id = q.supplier_id AND r.lessons_learned IS NOT NULL AND r.lessons_learned <> '') AS lessons_learned
                FROM purchase_rfq_quotes q LEFT JOIN suppliers s ON q.supplier_id = s.id
                WHERE q.request_id = ? ORDER BY q.is_selected DESC, q.total_price ASC, q.lead_time_days ASC");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'quotes' => $stmt->fetchAll()]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit();
    }

    // ---- RECORD A SUPPLIER QUOTATION (step 3) ----
    if ($_GET['action'] == 'save_rfq_quote') {
        if (!canAccess('procurement_manage') && !canAccess('procurement_po_manage') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to record quotations']); exit();
        }
        try {
            $id = intval($_POST['id'] ?? 0);
            $supplierId = intval($_POST['supplier_id'] ?? 0);
            $unitPrice = round(floatval($_POST['unit_price'] ?? 0), 2);
            $leadTime = intval($_POST['lead_time_days'] ?? 0);
            $paymentTerms = substr(trim($_POST['payment_terms'] ?? ''), 0, 100);
            $notes = trim($_POST['notes'] ?? '');
            if ($supplierId <= 0 || $unitPrice <= 0) throw new Exception('Supplier and a positive quoted unit price are required.');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT r.*, (SELECT COALESCE(SUM(quantity),0) FROM purchase_request_items WHERE request_id = r.id) AS total_qty FROM purchase_requests r WHERE r.id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $req = $stmt->fetch();
            if (!$req) throw new Exception('Purchase request not found.');
            if ($req['status'] !== 'sourcing') throw new Exception('Quotations can only be recorded while the request is in sourcing (status: ' . $req['status'] . ').');
            $s = $pdo->prepare("SELECT id FROM suppliers WHERE id = ? AND status = 'active'");
            $s->execute([$supplierId]);
            if (!$s->fetch()) throw new Exception('Choose an active supplier.');
            $total = round($unitPrice * floatval($req['total_qty']), 2);
            $pdo->prepare("INSERT INTO purchase_rfq_quotes (request_id, supplier_id, unit_price, total_price, lead_time_days, payment_terms, notes, created_by) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$id, $supplierId, $unitPrice, $total, $leadTime ?: null, $paymentTerms, $notes, $_SESSION['user_id']]);
            $pdo->prepare("UPDATE purchase_requests SET rfq_status='evaluating' WHERE id=?")->execute([$id]);
            $pdo->prepare("INSERT INTO procurement_approval_logs (request_id, action, actor_id, actor_role, comments) VALUES (?, 'rfq_quote_recorded', ?, ?, ?)")
                ->execute([$id, $_SESSION['user_id'], $_SESSION['role'] ?? '', 'Quotation recorded: ₱' . number_format($unitPrice, 2) . '/unit, lead ' . $leadTime . ' day(s)']);
            $pdo->commit();
            logActivity('procurement_rfq', "Recorded quotation for {$req['request_number']}");
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- SELECT A WINNING QUOTATION / SUPPLIER (step 4) ----
    if ($_GET['action'] == 'select_rfq_quote') {
        if (!canAccess('procurement_manage') && !canAccess('procurement_po_manage') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to select a supplier']); exit();
        }
        try {
            $id = intval($_POST['id'] ?? 0);
            $quoteId = intval($_POST['quote_id'] ?? 0);
            $negotiationNotes = trim($_POST['negotiation_notes'] ?? '');
            // Finance (or admin) selecting the quote confirms the payment terms / total cost review.
            $financeTermsOk = (canAccess('procurement_finance') || isAdmin()) ? 1 : 0;
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM purchase_requests WHERE id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $req = $stmt->fetch();
            if (!$req) throw new Exception('Purchase request not found.');
            if ($req['status'] !== 'sourcing') throw new Exception('A supplier can only be selected while sourcing (status: ' . $req['status'] . ').');
            $qStmt = $pdo->prepare("SELECT * FROM purchase_rfq_quotes WHERE id = ? AND request_id = ?");
            $qStmt->execute([$quoteId, $id]);
            $quote = $qStmt->fetch();
            if (!$quote) throw new Exception('Quotation not found for this request.');
            $pdo->prepare("UPDATE purchase_rfq_quotes SET is_selected=0 WHERE request_id=?")->execute([$id]);
            $pdo->prepare("UPDATE purchase_rfq_quotes SET is_selected=1, finance_terms_ok=?, negotiation_notes=? WHERE id=?")
                ->execute([$financeTermsOk, $negotiationNotes, $quoteId]);
            $pdo->prepare("UPDATE purchase_requests SET supplier_id=?, payment_terms=?, negotiated_total=?, selected_quote_id=?, status='supplier_selected', rfq_status='awarded' WHERE id=?")
                ->execute([$quote['supplier_id'], $quote['payment_terms'], $quote['total_price'], $quoteId, $id]);
            $pdo->prepare("INSERT INTO procurement_approval_logs (request_id, action, actor_id, actor_role, comments) VALUES (?, 'supplier_selected', ?, ?, ?)")
                ->execute([$id, $_SESSION['user_id'], $_SESSION['role'] ?? '', 'Supplier selected with a negotiated total of ₱' . number_format($quote['total_price'], 2) . ($negotiationNotes ? ' — ' . $negotiationNotes : '')]);
            $pdo->commit();
            logActivity('procurement_rfq', "Selected supplier for {$req['request_number']} (quote $quoteId)");
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ====================================================================
    // PURCHASE ORDERS  (Admin generates from an approved request)
    // ====================================================================

    // ---- LIST PURCHASE ORDERS ----
    if ($_GET['action'] == 'get_purchase_orders') {
        if (!canAccess('procurement') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit();
        }
        try {
            $where = []; $params = [];
            if (!empty($_GET['status'])) { $where[] = 'po.status = ?'; $params[] = $_GET['status']; }
            if (!empty($_GET['supplier_id'])) { $where[] = 'po.supplier_id = ?'; $params[] = intval($_GET['supplier_id']); }
            if (!empty($_GET['q'])) {
                $where[] = '(po.po_number LIKE ? OR s.name LIKE ? OR r.request_number LIKE ?)';
                $like = '%' . trim($_GET['q']) . '%'; array_push($params, $like, $like, $like);
            }
            if (!empty($_GET['date_from'])) { $where[] = 'po.order_date >= ?'; $params[] = $_GET['date_from']; }
            if (!empty($_GET['date_to'])) { $where[] = 'po.order_date <= ?'; $params[] = $_GET['date_to']; }
            $sql = "SELECT po.*, s.name AS supplier_name, r.request_number, u.full_name AS created_by_name,
                    (SELECT COALESCE(SUM(quantity),0) FROM purchase_order_items WHERE po_id = po.id) AS ordered_total,
                    (SELECT COALESCE(SUM(received_quantity),0) FROM purchase_order_items WHERE po_id = po.id) AS received_total,
                    (SELECT COUNT(*) FROM supplier_invoices si WHERE si.po_id = po.id) AS invoice_count,
                    (SELECT COUNT(*) FROM supplier_invoices si WHERE si.po_id = po.id AND si.payment_status <> 'paid' AND si.voided = 0) AS open_invoices
                FROM purchase_orders po
                LEFT JOIN suppliers s ON po.supplier_id = s.id
                LEFT JOIN purchase_requests r ON po.request_id = r.id
                LEFT JOIN users u ON po.created_by = u.id";
            if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
            $sql .= ' ORDER BY po.created_at DESC LIMIT 500';
            $stmt = $pdo->prepare($sql); $stmt->execute($params);
            echo json_encode(['success' => true, 'orders' => $stmt->fetchAll()]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit();
    }

    // ---- CREATE A DRAFT PURCHASE ORDER FROM AN APPROVED REQUEST ----
    if ($_GET['action'] == 'create_purchase_order') {
        if (!canAccess('procurement_po_manage') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to create purchase orders']); exit();
        }
        try {
            $requestId = intval($_POST['request_id'] ?? 0);
            $expected = trim($_POST['expected_delivery_date'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM purchase_requests WHERE id = ? FOR UPDATE");
            $stmt->execute([$requestId]);
            $req = $stmt->fetch();
            if (!$req) throw new Exception('Purchase request not found.');
            if (!in_array($req['status'], ['approved', 'supplier_selected'], true)) throw new Exception('Only an approved request or one with a selected supplier can generate a purchase order.');
            if (empty($req['supplier_id'])) throw new Exception('Select a supplier during sourcing before creating a purchase order.');
            $chk = $pdo->prepare("SELECT COUNT(*) FROM purchase_orders WHERE request_id = ? AND status <> 'cancelled'");
            $chk->execute([$requestId]);
            if ($chk->fetchColumn() > 0) throw new Exception('A purchase order already exists for this request.');
            $items = $pdo->prepare("SELECT * FROM purchase_request_items WHERE request_id = ?");
            $items->execute([$requestId]);
            $rows = $items->fetchAll();
            if (!$rows) throw new Exception('The request has no product lines.');
            // Prefer the negotiated (RFQ) total when a supplier quotation was awarded.
            $estimatedSubtotal = 0;
            foreach ($rows as $r) $estimatedSubtotal += floatval($r['line_total']);
            $estimatedSubtotal = round($estimatedSubtotal, 2);
            $negotiated = $req['negotiated_total'] !== null ? round(floatval($req['negotiated_total']), 2) : null;
            $poSubtotal = ($negotiated !== null && $negotiated > 0) ? $negotiated : $estimatedSubtotal;
            // Spread the agreed total over the lines proportionally to the estimate.
            $lines = [];
            $allocated = 0;
            $count = count($rows);
            foreach ($rows as $i => $r) {
                $weight = $estimatedSubtotal > 0 ? floatval($r['line_total']) / $estimatedSubtotal : 1 / max(1, $count);
                $lineTotal = ($i === $count - 1) ? round($poSubtotal - $allocated, 2) : round($poSubtotal * $weight, 2);
                $allocated += $lineTotal;
                $qty = floatval($r['quantity']);
                $lines[] = [
                    'product_id' => $r['product_id'],
                    'quantity' => $r['quantity'],
                    'unit_of_measure' => $r['unit_of_measure'],
                    'unit_price' => $qty > 0 ? round($lineTotal / $qty, 4) : 0,
                    'line_total' => $lineTotal
                ];
            }
            $poNumber = generateProcurementNumber('purchase_orders', 'po_number', 'PO');
            $pdo->prepare("INSERT INTO purchase_orders (po_number, request_id, supplier_id, order_date, expected_delivery_date, status, subtotal, tax_amount, total_amount, notes, created_by) VALUES (?,?,?,?,?,'draft',?,0,?,?,?)")
                ->execute([$poNumber, $requestId, $req['supplier_id'], date('Y-m-d'), $expected ?: null, $poSubtotal, $poSubtotal, $notes, $_SESSION['user_id']]);
            $poId = (int)$pdo->lastInsertId();
            $itemStmt = $pdo->prepare("INSERT INTO purchase_order_items (po_id, product_id, quantity, unit_of_measure, unit_price, line_total) VALUES (?,?,?,?,?,?)");
            foreach ($lines as $l) {
                $itemStmt->execute([$poId, $l['product_id'], $l['quantity'], $l['unit_of_measure'], $l['unit_price'], $l['line_total']]);
            }
            $pdo->prepare("INSERT INTO procurement_approval_logs (request_id, po_id, action, actor_id, actor_role, comments) VALUES (?,?, 'po_created', ?, ?, ?)")
                ->execute([$requestId, $poId, $_SESSION['user_id'], $_SESSION['role'] ?? '', "Purchase order $poNumber created (draft)"]);
            $pdo->commit();
            logActivity('procurement_po', "Created purchase order $poNumber for {$req['request_number']}");
            echo json_encode(['success' => true, 'po_id' => $poId, 'po_number' => $poNumber]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- ISSUE (authorize) A DRAFT PURCHASE ORDER ----
    if ($_GET['action'] == 'issue_purchase_order') {
        if (!canAccess('procurement_po_manage') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to issue purchase orders']); exit();
        }
        try {
            $id = intval($_POST['id'] ?? 0);
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT po.*, r.request_number, r.requester_id FROM purchase_orders po LEFT JOIN purchase_requests r ON po.request_id = r.id WHERE po.id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $po = $stmt->fetch();
            if (!$po) throw new Exception('Purchase order not found.');
            if ($po['status'] !== 'draft') throw new Exception('Only draft purchase orders can be issued.');
            $threshold = max(0, floatval(getSetting('procurement_po_threshold', 50000)));
            if (floatval($po['total_amount']) > $threshold) {
                // High-value PO: hold for the final Finance approval before transmission (step 5).
                $pdo->prepare("UPDATE purchase_orders SET status='pending_finance', finance_status='pending', issued_by=?, issued_at=NOW() WHERE id=?")
                    ->execute([$_SESSION['user_id'], $id]);
                $pdo->prepare("INSERT INTO procurement_approval_logs (request_id, po_id, action, actor_id, actor_role, comments) VALUES (?,?, 'po_submitted_finance', ?, ?, ?)")
                    ->execute([$po['request_id'], $id, $_SESSION['user_id'], $_SESSION['role'] ?? '', 'Purchase order ' . $po['po_number'] . ' exceeds the ₱' . number_format($threshold, 2) . ' threshold and needs Finance approval']);
                notifyProcurementRole('finance', 'po_finance', 'Finance approval needed: ' . $po['po_number'], 'Purchase order ' . $po['po_number'] . ' (₱' . number_format(floatval($po['total_amount']), 2) . ') exceeds the approval threshold.', $po['request_id'], $id);
                $pdo->commit();
                logActivity('procurement_po', "Submitted purchase order {$po['po_number']} for finance approval");
                echo json_encode(['success' => true, 'status' => 'pending_finance']);
                exit();
            }
            $pdo->prepare("INSERT INTO purchases (po_number, supplier_id, purchase_date, subtotal, tax_amount, total_amount, payment_status, notes, recorded_by) VALUES (?,?,?,?,?,?,'unpaid',?,?)")
                ->execute([$po['po_number'], $po['supplier_id'], $po['order_date'], $po['subtotal'], $po['tax_amount'], $po['total_amount'], "Purchase order {$po['po_number']} (request {$po['request_number']})", $_SESSION['user_id']]);
            $purchaseId = (int)$pdo->lastInsertId();
            $items = $pdo->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
            $items->execute([$id]);
            $piStmt = $pdo->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_cost, total_cost) VALUES (?,?,?,?,?)");
            foreach ($items->fetchAll() as $it) {
                $piStmt->execute([$purchaseId, $it['product_id'], $it['quantity'], $it['unit_price'], $it['line_total']]);
            }
            $pdo->prepare("UPDATE purchase_orders SET status='ordered', finance_status='not_required', issued_by=?, issued_at=NOW(), approved_by=?, approved_at=NOW() WHERE id=?")
                ->execute([$_SESSION['user_id'], $_SESSION['user_id'], $id]);
            $pdo->prepare("INSERT INTO procurement_approval_logs (request_id, po_id, action, actor_id, actor_role, comments) VALUES (?,?, 'po_issued', ?, ?, ?)")
                ->execute([$po['request_id'], $id, $_SESSION['user_id'], $_SESSION['role'] ?? '', "Purchase order {$po['po_number']} issued and marked as ordered"]);
            if (!empty($po['requester_id'])) {
                notifyProcurementUser((int)$po['requester_id'], 'po_issued', 'Purchase order ' . $po['po_number'] . ' issued', 'Your approved request was converted to ' . $po['po_number'] . ' and marked as ordered.', $po['request_id'], $id);
            }
            $pdo->commit();
            logActivity('procurement_po', "Issued purchase order {$po['po_number']}");
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- FINANCE APPROVAL OF A HIGH-VALUE PURCHASE ORDER (step 5) ----
    if ($_GET['action'] == 'finance_approve_po') {
        if (!canAccess('procurement_finance') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Only Finance can approve high-value purchase orders']); exit();
        }
        try {
            $id = intval($_POST['id'] ?? 0);
            $decision = $_POST['decision'] ?? 'approve';
            $comments = trim($_POST['comments'] ?? '');
            if (!in_array($decision, ['approve', 'reject'], true)) throw new Exception('Unknown finance decision.');
            if ($decision === 'reject' && $comments === '') throw new Exception('A comment is required to decline a purchase order.');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT po.*, r.request_number, r.requester_id FROM purchase_orders po LEFT JOIN purchase_requests r ON po.request_id = r.id WHERE po.id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $po = $stmt->fetch();
            if (!$po) throw new Exception('Purchase order not found.');
            if ($po['status'] !== 'pending_finance') throw new Exception('This purchase order is not awaiting finance approval (status: ' . $po['status'] . ').');
            if ($decision === 'reject') {
                // Return the order to draft so inventory can revise and re-issue it.
                $pdo->prepare("UPDATE purchase_orders SET status='draft', finance_status='rejected', finance_approved_by=?, finance_approved_at=NOW() WHERE id=?")
                    ->execute([$_SESSION['user_id'], $id]);
                $pdo->prepare("INSERT INTO procurement_approval_logs (request_id, po_id, action, actor_id, actor_role, comments) VALUES (?,?, 'po_finance_rejected', ?, ?, ?)")
                    ->execute([$po['request_id'], $id, $_SESSION['user_id'], $_SESSION['role'] ?? '', $comments]);
                notifyProcurementRole('inventory', 'po_finance', 'Finance declined ' . $po['po_number'], 'Finance declined purchase order ' . $po['po_number'] . ': ' . $comments, $po['request_id'], $id);
                if (!empty($po['requester_id'])) {
                    notifyProcurementUser((int)$po['requester_id'], 'po_finance', 'Declined: ' . $po['po_number'], 'Finance declined purchase order ' . $po['po_number'] . '. ' . $comments, $po['request_id'], $id);
                }
                $pdo->commit();
                logActivity('procurement_po', "Finance declined purchase order {$po['po_number']}");
                echo json_encode(['success' => true, 'status' => 'draft']);
                exit();
            }
            // Approved: create the finance-side purchase record and release the order.
            $pdo->prepare("INSERT INTO purchases (po_number, supplier_id, purchase_date, subtotal, tax_amount, total_amount, payment_status, notes, recorded_by) VALUES (?,?,?,?,?,?,'unpaid',?,?)")
                ->execute([$po['po_number'], $po['supplier_id'], $po['order_date'], $po['subtotal'], $po['tax_amount'], $po['total_amount'], "Purchase order {$po['po_number']} (request {$po['request_number']})", $_SESSION['user_id']]);
            $purchaseId = (int)$pdo->lastInsertId();
            $items = $pdo->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
            $items->execute([$id]);
            $piStmt = $pdo->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_cost, total_cost) VALUES (?,?,?,?,?)");
            foreach ($items->fetchAll() as $it) {
                $piStmt->execute([$purchaseId, $it['product_id'], $it['quantity'], $it['unit_price'], $it['line_total']]);
            }
            $pdo->prepare("UPDATE purchase_orders SET status='ordered', finance_status='approved', finance_approved_by=?, finance_approved_at=NOW(), approved_by=?, approved_at=NOW() WHERE id=?")
                ->execute([$_SESSION['user_id'], $_SESSION['user_id'], $id]);
            $pdo->prepare("INSERT INTO procurement_approval_logs (request_id, po_id, action, actor_id, actor_role, comments) VALUES (?,?, 'po_finance_approved', ?, ?, ?)")
                ->execute([$po['request_id'], $id, $_SESSION['user_id'], $_SESSION['role'] ?? '', 'Finance approved purchase order ' . $po['po_number'] . ($comments ? ' — ' . $comments : '')]);
            if (!empty($po['requester_id'])) {
                notifyProcurementUser((int)$po['requester_id'], 'po_finance', 'Purchase order ' . $po['po_number'] . ' approved', 'Finance approved purchase order ' . $po['po_number'] . '. It can now be sent to the supplier.', $po['request_id'], $id);
            }
            $pdo->commit();
            logActivity('procurement_po', "Finance approved purchase order {$po['po_number']}");
            echo json_encode(['success' => true, 'status' => 'ordered']);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- SUPPLIER ACKNOWLEDGEMENT OF A PURCHASE ORDER (step 5) ----
    if ($_GET['action'] == 'acknowledge_po') {
        if (!canAccess('procurement_po_manage') && !canAccess('procurement_receive') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to acknowledge purchase orders']); exit();
        }
        try {
            $id = intval($_POST['id'] ?? 0);
            $ackRef = substr(trim($_POST['ack_reference'] ?? ''), 0, 100);
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT po.*, r.request_number, r.requester_id FROM purchase_orders po LEFT JOIN purchase_requests r ON po.request_id = r.id WHERE po.id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $po = $stmt->fetch();
            if (!$po) throw new Exception('Purchase order not found.');
            if ($po['status'] !== 'ordered') throw new Exception('Only an open (ordered) purchase order can be acknowledged (status: ' . $po['status'] . ').');
            $pdo->prepare("UPDATE purchase_orders SET status='acknowledged', supplier_ack=1, acknowledged_at=NOW() WHERE id=?")
                ->execute([$id]);
            $pdo->prepare("INSERT INTO procurement_approval_logs (request_id, po_id, action, actor_id, actor_role, comments) VALUES (?,?, 'po_acknowledged', ?, ?, ?)")
                ->execute([$po['request_id'], $id, $_SESSION['user_id'], $_SESSION['role'] ?? '', 'Supplier acknowledged ' . $po['po_number'] . ($ackRef ? " (ref: $ackRef)" : '')]);
            if (!empty($po['requester_id'])) {
                notifyProcurementUser((int)$po['requester_id'], 'po_acknowledged', 'Supplier acknowledged ' . $po['po_number'], 'The supplier confirmed purchase order ' . $po['po_number'] . '. Delivery is expected ' . ($po['expected_delivery_date'] ?: 'soon') . '.', $po['request_id'], $id);
            }
            $pdo->commit();
            logActivity('procurement_po', "Supplier acknowledged purchase order {$po['po_number']}");
            echo json_encode(['success' => true, 'status' => 'acknowledged']);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- CLOSE A PURCHASE ORDER + RATE THE SUPPLIER (step 9) ----
    if ($_GET['action'] == 'close_purchase_order') {
        if (!canAccess('procurement_po_manage') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to close purchase orders']); exit();
        }
        try {
            $id = intval($_POST['id'] ?? 0);
            $otif = max(0, min(5, intval($_POST['otif_score'] ?? 0)));
            $quality = max(0, min(5, intval($_POST['quality_score'] ?? 0)));
            $responsiveness = max(0, min(5, intval($_POST['responsiveness_score'] ?? 0)));
            $costAllocation = trim($_POST['cost_allocation'] ?? '');
            $comments = trim($_POST['comments'] ?? '');
            $lessons = trim($_POST['lessons_learned'] ?? '');
            $accrualReleased = ($_POST['accrual_released'] ?? '1') === '1' ? 1 : 0;
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT po.*, r.request_number, r.requester_id FROM purchase_orders po LEFT JOIN purchase_requests r ON po.request_id = r.id WHERE po.id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $po = $stmt->fetch();
            if (!$po) throw new Exception('Purchase order not found.');
            if (!in_array($po['status'], ['received', 'partially_received'], true)) throw new Exception('Only a received purchase order can be closed (status: ' . $po['status'] . ').');
            // All linked invoices must be settled (three-way match + payment) before close.
            $osStmt = $pdo->prepare("SELECT COUNT(*) FROM supplier_invoices WHERE po_id = ? AND payment_status <> 'paid' AND voided = 0");
            $osStmt->execute([$id]);
            if ((int)$osStmt->fetchColumn() > 0) throw new Exception('Settle all outstanding invoices for this purchase order before closing.');
            $reviewStmt = $pdo->prepare("SELECT COUNT(*) FROM supplier_invoices WHERE po_id = ? AND match_status <> 'matched' AND voided = 0");
            $reviewStmt->execute([$id]);
            if ((int)$reviewStmt->fetchColumn() > 0) throw new Exception('Resolve invoices that are still on three-way match hold before closing.');
            $pdo->prepare("UPDATE purchase_orders SET status='closed', closed_at=NOW() WHERE id=?")->execute([$id]);
            if (!empty($po['request_id'])) {
                $pdo->prepare("UPDATE purchase_requests SET status='closed', closed_at=NOW() WHERE id=?")->execute([$po['request_id']]);
            }
            $pdo->prepare("INSERT INTO purchase_supplier_ratings (po_id, request_id, supplier_id, otif_score, quality_score, responsiveness_score, cost_allocation, accrual_released, comments, lessons_learned, rated_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE otif_score=VALUES(otif_score), quality_score=VALUES(quality_score), responsiveness_score=VALUES(responsiveness_score), cost_allocation=VALUES(cost_allocation), accrual_released=VALUES(accrual_released), comments=VALUES(comments), lessons_learned=VALUES(lessons_learned), rated_by=VALUES(rated_by)")
                ->execute([$id, $po['request_id'], $po['supplier_id'], $otif, $quality, $responsiveness, $costAllocation, $accrualReleased, $comments, $lessons, $_SESSION['user_id']]);
            $pdo->prepare("INSERT INTO procurement_approval_logs (request_id, po_id, action, actor_id, actor_role, comments) VALUES (?,?, 'po_closed', ?, ?, ?)")
                ->execute([$po['request_id'], $id, $_SESSION['user_id'], $_SESSION['role'] ?? '', 'Purchase order closed. Supplier rated OTIF ' . $otif . '/5, quality ' . $quality . '/5, responsiveness ' . $responsiveness . '/5.' . ($lessons ? ' Lessons: ' . $lessons : '')]);
            notifyProcurementRole('finance', 'po_closed', 'PO closed: ' . $po['po_number'], 'Purchase order ' . $po['po_number'] . ' is fully settled and closed. Accrual ' . ($accrualReleased ? 'released' : 'still held') . '.', $po['request_id'], $id);
            if (!empty($po['requester_id'])) {
                notifyProcurementUser((int)$po['requester_id'], 'po_closed', 'Closed: ' . $po['po_number'], 'Purchase request ' . $po['request_number'] . ' is complete and the purchase order has been closed.', $po['request_id'], $id);
            }
            $pdo->commit();
            logActivity('procurement_po', "Closed purchase order {$po['po_number']}");
            echo json_encode(['success' => true, 'status' => 'closed']);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- CANCEL A PURCHASE ORDER ----
    if ($_GET['action'] == 'cancel_purchase_order') {
        if (!canAccess('procurement_po_manage') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit();
        }
        try {
            $id = intval($_POST['id'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');
            if ($reason === '') throw new Exception('A cancellation reason is required.');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM purchase_orders WHERE id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $po = $stmt->fetch();
            if (!$po) throw new Exception('Purchase order not found.');
            if (!in_array($po['status'], ['draft', 'pending_finance', 'ordered', 'acknowledged'], true)) throw new Exception('A received, partly received or closed order cannot be cancelled.');
            $pdo->prepare("UPDATE purchase_orders SET status='cancelled', cancellation_reason=?, cancelled_at=NOW() WHERE id=?")
                ->execute([$reason, $id]);
            $pdo->prepare("UPDATE purchases SET payment_status='unpaid' WHERE po_number = ?")->execute([$po['po_number']]);
            $pdo->prepare("INSERT INTO procurement_approval_logs (request_id, po_id, action, actor_id, actor_role, comments) VALUES (?,?, 'po_cancelled', ?, ?, ?)")
                ->execute([$po['request_id'], $id, $_SESSION['user_id'], $_SESSION['role'] ?? '', "Purchase order cancelled: $reason"]);
            $pdo->commit();
            logActivity('procurement_po', "Cancelled purchase order {$po['po_number']}");
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ====================================================================
    // RECEIVE DELIVERIES  (Inventory confirms; accepted qty moves stock)
    // ====================================================================

    // ---- DETAIL FOR THE RECEIVE-DELIVERY SCREEN ----
    if ($_GET['action'] == 'get_po_receipt') {
        if (!canAccess('procurement') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit();
        }
        try {
            $id = intval($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT po.*, s.name AS supplier_name, r.request_number FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.id LEFT JOIN purchase_requests r ON po.request_id = r.id WHERE po.id = ?");
            $stmt->execute([$id]);
            $po = $stmt->fetch();
            if (!$po) { echo json_encode(['success' => false, 'message' => 'Purchase order not found']); exit(); }
            $items = $pdo->prepare("SELECT oi.*, p.name AS product_name, p.sku, p.unit AS product_unit, p.stock_quantity FROM purchase_order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.po_id = ? ORDER BY oi.id");
            $items->execute([$id]);
            $receipts = $pdo->prepare("SELECT gr.*, u.full_name AS received_by_name FROM goods_receipts gr LEFT JOIN users u ON gr.received_by = u.id WHERE gr.po_id = ? ORDER BY gr.created_at DESC");
            $receipts->execute([$id]);
            echo json_encode(['success' => true, 'order' => $po, 'items' => $items->fetchAll(), 'receipts' => $receipts->fetchAll()]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit();
    }

    // ---- SAVE A GOODS RECEIPT (transactional: receipt + stock movement) ----
    if ($_GET['action'] == 'save_goods_receipt') {
        if (!canAccess('procurement_receive') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to receive deliveries']); exit();
        }
        try {
            $poId = intval($_POST['po_id'] ?? 0);
            $deliveryDate = trim($_POST['delivery_date'] ?? '') ?: date('Y-m-d');
            $reference = substr(trim($_POST['reference_no'] ?? ''), 0, 100);
            $remarks = trim($_POST['remarks'] ?? '');
            $clientToken = substr(trim($_POST['client_token'] ?? ''), 0, 64);
            $items = json_decode($_POST['items'] ?? '[]', true);
            if (!is_array($items) || !$items) throw new Exception('No delivery lines were provided.');

            if ($clientToken !== '') {
                $dup = $pdo->prepare("SELECT grn_number FROM goods_receipts WHERE client_token = ?");
                $dup->execute([$clientToken]);
                $found = $dup->fetchColumn();
                if ($found) { echo json_encode(['success' => true, 'grn_number' => $found, 'duplicate' => true]); exit(); }
            }

            $pdo->beginTransaction();
            $poStmt = $pdo->prepare("SELECT * FROM purchase_orders WHERE id = ? FOR UPDATE");
            $poStmt->execute([$poId]);
            $po = $poStmt->fetch();
            if (!$po) throw new Exception('Purchase order not found.');
            if (!in_array($po['status'], ['ordered', 'acknowledged', 'partially_received'], true)) throw new Exception('This purchase order is not awaiting delivery (status: ' . $po['status'] . ').');

            $grn = generateProcurementNumber('goods_receipts', 'grn_number', 'GRN');
            $pdo->prepare("INSERT INTO goods_receipts (grn_number, po_id, supplier_id, delivery_date, reference_no, remarks, received_by, status, stock_applied, client_token) VALUES (?,?,?,?,?,?,?, 'confirmed', 0, ?)")
                ->execute([$grn, $poId, $po['supplier_id'], $deliveryDate, $reference ?: null, $remarks, $_SESSION['user_id'], $clientToken ?: null]);
            $receiptId = (int)$pdo->lastInsertId();

            $poItemStmt = $pdo->prepare("SELECT * FROM purchase_order_items WHERE id = ? AND po_id = ? FOR UPDATE");
            $productStmt = $pdo->prepare("SELECT id, name, stock_quantity FROM products WHERE id = ? FOR UPDATE");
            $riStmt = $pdo->prepare("INSERT INTO goods_receipt_items (receipt_id, po_item_id, product_id, ordered_quantity, previously_received_quantity, delivered_quantity, accepted_quantity, damaged_quantity, rejected_quantity, batch_no, expiry_date, remarks) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $updPoItem = $pdo->prepare("UPDATE purchase_order_items SET received_quantity = received_quantity + ? WHERE id = ?");
            $stockStmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
            $moveStmt = $pdo->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, quantity_before, quantity_after, reason, reference_id, reference_type, user_id) VALUES (?, 'purchase', ?, ?, ?, ?, ?, 'goods_receipt', ?)");

            $linesApplied = 0;
            foreach ($items as $it) {
                $poItemId = intval($it['po_item_id'] ?? 0);
                $poItemStmt->execute([$poItemId, $poId]);
                $poItem = $poItemStmt->fetch();
                if (!$poItem) throw new Exception('Invalid delivery line.');
                $delivered = round(floatval($it['delivered_quantity'] ?? 0), 2);
                $accepted = round(floatval($it['accepted_quantity'] ?? 0), 2);
                $damaged = round(floatval($it['damaged_quantity'] ?? 0), 2);
                $rejected = round(floatval($it['rejected_quantity'] ?? 0), 2);
                if ($delivered <= 0 && $accepted <= 0 && $damaged <= 0 && $rejected <= 0) continue;
                if (min($delivered, $accepted, $damaged, $rejected) < 0) throw new Exception('Quantities cannot be negative.');
                if (abs(($accepted + $damaged + $rejected) - $delivered) > 0.001) throw new Exception('For each line, accepted + damaged + rejected must equal the delivered quantity.');
                $previous = floatval($poItem['received_quantity']);
                $remaining = floatval($poItem['quantity']) - $previous;
                if ($accepted > $remaining + 0.001) throw new Exception('Accepted quantity exceeds the remaining ordered quantity (' . $remaining . ').');

                $batch = substr(trim($it['batch_no'] ?? ''), 0, 60);
                $expiry = trim($it['expiry_date'] ?? '') ?: null;
                $riStmt->execute([$receiptId, $poItemId, $poItem['product_id'], $poItem['quantity'], $previous, $delivered, $accepted, $damaged, $rejected, $batch ?: null, $expiry, substr(trim($it['remarks'] ?? ''), 0, 255)]);

                if ($accepted > 0) {
                    $productStmt->execute([$poItem['product_id']]);
                    $product = $productStmt->fetch();
                    if (!$product) throw new Exception('Linked product no longer exists.');
                    $before = floatval($product['stock_quantity']);
                    $after = $before + $accepted;
                    $stockStmt->execute([$after, $poItem['product_id']]);
                    $moveStmt->execute([$poItem['product_id'], $accepted, $before, $after, "Goods receipt $grn for {$po['po_number']}", $receiptId, $_SESSION['user_id']]);
                    $updPoItem->execute([$accepted, $poItemId]);
                }
                $linesApplied++;
            }
            if ($linesApplied === 0) throw new Exception('Enter a delivered quantity for at least one line.');

            $pdo->prepare("UPDATE goods_receipts SET stock_applied = 1 WHERE id = ?")->execute([$receiptId]);

            $remStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity - received_quantity),0) FROM purchase_order_items WHERE po_id = ?");
            $remStmt->execute([$poId]);
            $newPoStatus = floatval($remStmt->fetchColumn()) <= 0.001 ? 'received' : 'partially_received';
            $pdo->prepare("UPDATE purchase_orders SET status = ? WHERE id = ?")->execute([$newPoStatus, $poId]);

            $pdo->prepare("INSERT INTO procurement_approval_logs (request_id, po_id, action, actor_id, actor_role, comments) VALUES (?,?, 'goods_received', ?, ?, ?)")
                ->execute([$po['request_id'], $poId, $_SESSION['user_id'], $_SESSION['role'] ?? '', "Goods receipt $grn recorded ($newPoStatus)"]);

            if ($newPoStatus === 'received') {
                $financeUsers = $pdo->query("SELECT id, role FROM users")->fetchAll();
                foreach ($financeUsers as $fu) {
                    if (in_array('finance', getUserRoles($fu['role']), true)) {
                        notifyProcurementUser((int)$fu['id'], 'goods_received', 'Goods received for ' . $po['po_number'], 'Purchase order ' . $po['po_number'] . ' is fully received and ready for invoice matching.', $po['request_id'], $poId);
                    }
                }
            }

            $pdo->commit();
            logActivity('procurement_receipt', "Recorded goods receipt $grn for {$po['po_number']}");
            echo json_encode(['success' => true, 'grn_number' => $grn, 'po_status' => $newPoStatus]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- LIST GOODS RECEIPTS ----
    if ($_GET['action'] == 'get_goods_receipts') {
        if (!canAccess('procurement') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit();
        }
        try {
            $where = []; $params = [];
            if (!empty($_GET['po_id'])) { $where[] = 'gr.po_id = ?'; $params[] = intval($_GET['po_id']); }
            $sql = "SELECT gr.*, s.name AS supplier_name, po.po_number, u.full_name AS received_by_name,
                    (SELECT COUNT(*) FROM goods_receipt_items gi WHERE gi.receipt_id = gr.id AND gi.accepted_quantity > 0) AS accepted_lines,
                    (SELECT COALESCE(SUM(gi.accepted_quantity),0) FROM goods_receipt_items gi WHERE gi.receipt_id = gr.id) AS accepted_total,
                    (SELECT COALESCE(SUM(gi.damaged_quantity),0) FROM goods_receipt_items gi WHERE gi.receipt_id = gr.id) AS damaged_total
                FROM goods_receipts gr
                LEFT JOIN suppliers s ON gr.supplier_id = s.id
                LEFT JOIN purchase_orders po ON gr.po_id = po.id
                LEFT JOIN users u ON gr.received_by = u.id";
            if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
            $sql .= ' ORDER BY gr.created_at DESC LIMIT 500';
            $stmt = $pdo->prepare($sql); $stmt->execute($params);
            echo json_encode(['success' => true, 'receipts' => $stmt->fetchAll()]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit();
    }

    // ====================================================================
    // FINANCE INTEGRATION  (invoices + payments)
    // ====================================================================

    // ---- LIST SUPPLIER INVOICES (+ POs eligible to invoice) ----
    if ($_GET['action'] == 'get_supplier_invoices') {
        if (!canAccess('procurement_finance') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit();
        }
        try {
            $where = ['si.voided = 0']; $params = [];
            if (!empty($_GET['status'])) { $where[] = 'si.payment_status = ?'; $params[] = $_GET['status']; }
            if (!empty($_GET['supplier_id'])) { $where[] = 'si.supplier_id = ?'; $params[] = intval($_GET['supplier_id']); }
            if (!empty($_GET['q'])) {
                $where[] = '(si.invoice_number LIKE ? OR s.name LIKE ? OR po.po_number LIKE ?)';
                $like = '%' . trim($_GET['q']) . '%'; array_push($params, $like, $like, $like);
            }
            $sql = "SELECT si.*, s.name AS supplier_name, po.po_number, po.status AS po_status, po.total_amount AS po_total,
                    ROUND(si.invoice_amount - si.amount_paid, 2) AS outstanding
                FROM supplier_invoices si
                LEFT JOIN suppliers s ON si.supplier_id = s.id
                LEFT JOIN purchase_orders po ON si.po_id = po.id";
            if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
            $sql .= ' ORDER BY si.created_at DESC LIMIT 500';
            $stmt = $pdo->prepare($sql); $stmt->execute($params);
            $invoices = $stmt->fetchAll();

            $pos = $pdo->query("SELECT po.id, po.po_number, po.status, po.total_amount, s.name AS supplier_name, r.request_number,
                    (SELECT COUNT(*) FROM supplier_invoices si WHERE si.po_id = po.id) AS invoice_count
                FROM purchase_orders po
                LEFT JOIN suppliers s ON po.supplier_id = s.id
                LEFT JOIN purchase_requests r ON po.request_id = r.id
                WHERE po.status IN ('ordered','acknowledged','partially_received','received')
                ORDER BY po.updated_at DESC LIMIT 300")->fetchAll();

            echo json_encode(['success' => true, 'invoices' => $invoices, 'orders' => $pos]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit();
    }

    // ---- RECORD A SUPPLIER INVOICE ----
    if ($_GET['action'] == 'save_supplier_invoice') {
        if (!canAccess('procurement_finance') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to record supplier invoices']); exit();
        }
        try {
            $poId = intval($_POST['po_id'] ?? 0);
            $invoiceNumber = substr(trim($_POST['invoice_number'] ?? ''), 0, 100);
            $invoiceDate = trim($_POST['invoice_date'] ?? '') ?: date('Y-m-d');
            $amount = round(floatval($_POST['invoice_amount'] ?? 0), 2);
            $notes = trim($_POST['notes'] ?? '');
            if ($invoiceNumber === '') throw new Exception('Invoice number is required.');
            if ($amount <= 0) throw new Exception('Invoice amount must be greater than zero.');
            $pdo->beginTransaction();
            $poStmt = $pdo->prepare("SELECT * FROM purchase_orders WHERE id = ? FOR UPDATE");
            $poStmt->execute([$poId]);
            $po = $poStmt->fetch();
            if (!$po) throw new Exception('Purchase order not found.');
            if (!in_array($po['status'], ['ordered', 'partially_received', 'received'], true)) throw new Exception('This purchase order cannot be invoiced yet.');
            $dup = $pdo->prepare("SELECT COUNT(*) FROM supplier_invoices WHERE supplier_id = ? AND invoice_number = ?");
            $dup->execute([$po['supplier_id'], $invoiceNumber]);
            if ($dup->fetchColumn() > 0) throw new Exception('An invoice with this number already exists for this supplier.');
            // ---- Three-way match: purchase order vs goods receipt vs invoice ----
            $ordStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) AS ordered, COALESCE(SUM(received_quantity),0) AS received FROM purchase_order_items WHERE po_id = ?");
            $ordStmt->execute([$poId]);
            $qty = $ordStmt->fetch();
            $orderedQty = round(floatval($qty['ordered']), 2);
            $receivedQty = round(floatval($qty['received']), 2);
            $poTotal = round(floatval($po['total_amount']), 2);
            $priceTolerance = max(0, floatval(getSetting('procurement_match_tolerance', 0.01)));
            $priceDiff = round(abs($amount - $poTotal), 2);
            $qtyOk = $receivedQty + 0.001 >= $orderedQty;
            $priceOk = $priceDiff <= $priceTolerance;
            $matchStatus = ($priceOk && $qtyOk) ? 'matched' : 'held';
            $grnStmt = $pdo->prepare("SELECT id FROM goods_receipts WHERE po_id = ? ORDER BY created_at DESC LIMIT 1");
            $grnStmt->execute([$poId]);
            $grnId = $grnStmt->fetchColumn() ?: null;
            $matchBits = ['PO total ₱' . number_format($poTotal, 2), 'goods received ' . $receivedQty . '/' . $orderedQty];
            if (!$priceOk) $matchBits[] = 'PRICE VARIANCE ₱' . number_format($priceDiff, 2);
            if (!$qtyOk) $matchBits[] = 'QUANTITY SHORTFALL ' . number_format(max(0, $orderedQty - $receivedQty), 2);
            $matchNotes = implode('; ', $matchBits);
            $pdo->prepare("INSERT INTO supplier_invoices (invoice_number, po_id, supplier_id, invoice_date, invoice_amount, amount_paid, payment_status, notes, grn_id, match_status, match_notes, ordered_quantity, received_quantity, recorded_by) VALUES (?,?,?,?,?,0,'unpaid',?,?,?,?,?,?,?)")
                ->execute([$invoiceNumber, $poId, $po['supplier_id'], $invoiceDate, $amount, $notes, $grnId, $matchStatus, $matchNotes, $orderedQty, $receivedQty, $_SESSION['user_id']]);
            $invoiceId = (int)$pdo->lastInsertId();
            $pdo->prepare("INSERT INTO procurement_approval_logs (request_id, po_id, action, actor_id, actor_role, comments) VALUES (?,?, 'invoice_recorded', ?, ?, ?)")
                ->execute([$po['request_id'], $poId, $_SESSION['user_id'], $_SESSION['role'] ?? '', "Supplier invoice $invoiceNumber recorded for {$po['po_number']} (₱" . number_format($amount, 2) . "). Three-way match: $matchStatus — $matchNotes"]);
            if ($matchStatus === 'held') {
                notifyProcurementRole('inventory', 'invoice_held', 'Invoice match exception: ' . $invoiceNumber, 'Invoice ' . $invoiceNumber . ' for ' . $po['po_number'] . ' did not pass the three-way match. ' . $matchNotes, $po['request_id'], $poId);
            }
            $pdo->commit();
            logActivity('procurement_invoice', "Recorded supplier invoice $invoiceNumber for {$po['po_number']} ($matchStatus)");
            echo json_encode(['success' => true, 'invoice_id' => $invoiceId, 'match_status' => $matchStatus, 'match_notes' => $matchNotes]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- LIST SUPPLIER PAYMENTS ----
    if ($_GET['action'] == 'get_supplier_payments') {
        if (!canAccess('procurement_finance') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit();
        }
        try {
            $where = []; $params = [];
            if (!empty($_GET['invoice_id'])) { $where[] = 'sp.invoice_id = ?'; $params[] = intval($_GET['invoice_id']); }
            $sql = "SELECT sp.*, s.name AS supplier_name, si.invoice_number, u.full_name AS recorded_by_name
                FROM supplier_payments sp
                LEFT JOIN suppliers s ON sp.supplier_id = s.id
                LEFT JOIN supplier_invoices si ON sp.invoice_id = si.id
                LEFT JOIN users u ON sp.recorded_by = u.id";
            if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
            $sql .= ' ORDER BY sp.created_at DESC LIMIT 500';
            $stmt = $pdo->prepare($sql); $stmt->execute($params);
            echo json_encode(['success' => true, 'payments' => $stmt->fetchAll()]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit();
    }

    // ---- RECORD A SUPPLIER PAYMENT (supports partial payments) ----
    if ($_GET['action'] == 'save_supplier_payment') {
        if (!canAccess('procurement_finance') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to record supplier payments']); exit();
        }
        try {
            $invoiceId = intval($_POST['invoice_id'] ?? 0);
            $amount = round(floatval($_POST['amount'] ?? 0), 2);
            $paymentDate = trim($_POST['payment_date'] ?? '') ?: date('Y-m-d');
            $method = substr(trim($_POST['payment_method'] ?? 'cash'), 0, 40);
            $reference = substr(trim($_POST['reference_no'] ?? ''), 0, 100);
            $notes = trim($_POST['notes'] ?? '');
            if ($amount <= 0) throw new Exception('Payment amount must be greater than zero.');
            $pdo->beginTransaction();
            $invStmt = $pdo->prepare("SELECT * FROM supplier_invoices WHERE id = ? FOR UPDATE");
            $invStmt->execute([$invoiceId]);
            $inv = $invStmt->fetch();
            if (!$inv) throw new Exception('Supplier invoice not found.');
            if ((int)$inv['voided'] === 1) throw new Exception('This invoice has been voided and cannot be paid.');
            if (($inv['match_status'] ?? 'pending') !== 'matched') {
                throw new Exception('This invoice has not passed the three-way match' . (($inv['match_status'] ?? '') === 'held' ? ' (it is on hold).' : '.') . ' Resolve the match exception before paying.');
            }
            if (empty($inv['scheduled_for'])) {
                throw new Exception('Schedule the payment before recording it.');
            }
            $outstanding = round(floatval($inv['invoice_amount']) - floatval($inv['amount_paid']), 2);
            if ($outstanding <= 0) throw new Exception('This invoice is already fully paid.');
            if ($amount > $outstanding + 0.001) throw new Exception('Payment exceeds the outstanding balance (₱' . number_format($outstanding, 2) . ').');
            if ($reference !== '') {
                $dup = $pdo->prepare("SELECT COUNT(*) FROM supplier_payments WHERE invoice_id = ? AND reference_no = ?");
                $dup->execute([$invoiceId, $reference]);
                if ($dup->fetchColumn() > 0) throw new Exception('A payment with this reference already exists for this invoice.');
            }
            $pdo->prepare("INSERT INTO supplier_payments (invoice_id, supplier_id, payment_date, amount, payment_method, reference_no, notes, recorded_by) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$invoiceId, $inv['supplier_id'], $paymentDate, $amount, $method, $reference ?: null, $notes, $_SESSION['user_id']]);
            $newPaid = round(floatval($inv['amount_paid']) + $amount, 2);
            $status = $newPaid >= floatval($inv['invoice_amount']) - 0.001 ? 'paid' : 'partial';
            $pdo->prepare("UPDATE supplier_invoices SET amount_paid = ?, payment_status = ? WHERE id = ?")->execute([$newPaid, $status, $invoiceId]);
            if (!empty($inv['po_id'])) {
                $chk = $pdo->prepare("SELECT COALESCE(SUM(invoice_amount - amount_paid),0) FROM supplier_invoices WHERE po_id = ?");
                $chk->execute([$inv['po_id']]);
                $poOutstanding = floatval($chk->fetchColumn());
                $pstatus = $poOutstanding <= 0.001 ? 'paid' : 'partial';
                $pdo->prepare("UPDATE purchases p JOIN purchase_orders po ON po.po_number = p.po_number SET p.payment_status = ? WHERE po.id = ?")->execute([$pstatus, $inv['po_id']]);
            }
            $pdo->prepare("INSERT INTO procurement_approval_logs (po_id, action, actor_id, actor_role, comments) VALUES (?, 'payment_recorded', ?, ?, ?)")
                ->execute([$inv['po_id'], $_SESSION['user_id'], $_SESSION['role'] ?? '', "Payment ₱" . number_format($amount, 2) . " for invoice {$inv['invoice_number']} ($method)"]);
            $pdo->commit();
            logActivity('procurement_payment', "Recorded ₱$amount payment for invoice {$inv['invoice_number']}");
            echo json_encode(['success' => true, 'payment_status' => $status, 'outstanding' => round(floatval($inv['invoice_amount']) - $newPaid, 2)]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- SCHEDULE A SUPPLIER PAYMENT (step 8) ----
    if ($_GET['action'] == 'schedule_supplier_payment') {
        if (!canAccess('procurement_finance') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to schedule supplier payments']); exit();
        }
        try {
            $invoiceId = intval($_POST['invoice_id'] ?? 0);
            $scheduledFor = trim($_POST['scheduled_for'] ?? '');
            $method = substr(trim($_POST['payment_method'] ?? ''), 0, 40);
            $dateCheck = $scheduledFor !== '' ? DateTime::createFromFormat('!Y-m-d', $scheduledFor) : null;
            if (!$dateCheck || $dateCheck->format('Y-m-d') !== $scheduledFor) throw new Exception('Enter a valid scheduled payment date.');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM supplier_invoices WHERE id = ? FOR UPDATE");
            $stmt->execute([$invoiceId]);
            $inv = $stmt->fetch();
            if (!$inv) throw new Exception('Supplier invoice not found.');
            if ((int)$inv['voided'] === 1) throw new Exception('This invoice has been voided and cannot be scheduled for payment.');
            if (($inv['match_status'] ?? 'pending') !== 'matched') throw new Exception('Only an invoice that passed the three-way match can be scheduled for payment.');
            if ($inv['payment_status'] === 'paid') throw new Exception('This invoice is already fully paid.');
            $pdo->prepare("UPDATE supplier_invoices SET scheduled_for=?, scheduled_at=NOW(), payment_method=? WHERE id=?")
                ->execute([$scheduledFor, $method ?: null, $invoiceId]);
            $pdo->prepare("INSERT INTO procurement_approval_logs (po_id, action, actor_id, actor_role, comments) VALUES (?, 'payment_scheduled', ?, ?, ?)")
                ->execute([$inv['po_id'], $_SESSION['user_id'], $_SESSION['role'] ?? '', 'Payment scheduled for ' . $scheduledFor . ($method ? " via $method" : '')]);
            $pdo->commit();
            logActivity('procurement_payment', "Scheduled payment for invoice {$inv['invoice_number']} on $scheduledFor");
            echo json_encode(['success' => true, 'scheduled_for' => $scheduledFor]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- RECORD A PAYMENT ADVICE SENT TO THE SUPPLIER (step 8) ----
    if ($_GET['action'] == 'record_payment_advice') {
        if (!canAccess('procurement_finance') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to issue payment advices']); exit();
        }
        try {
            $invoiceId = intval($_POST['invoice_id'] ?? 0);
            $recipient = substr(trim($_POST['advice_recipient'] ?? ''), 0, 150);
            $notes = trim($_POST['notes'] ?? '');
            if ($recipient === '') throw new Exception('Enter who the payment advice was sent to.');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM supplier_invoices WHERE id = ? FOR UPDATE");
            $stmt->execute([$invoiceId]);
            $inv = $stmt->fetch();
            if (!$inv) throw new Exception('Supplier invoice not found.');
            if (empty($inv['scheduled_for'])) throw new Exception('Schedule the payment before issuing a payment advice.');
            $pdo->prepare("UPDATE supplier_invoices SET advice_sent_at=NOW(), advice_recipient=? WHERE id=?")
                ->execute([$recipient, $invoiceId]);
            $pdo->prepare("INSERT INTO procurement_approval_logs (po_id, action, actor_id, actor_role, comments) VALUES (?, 'payment_advice_sent', ?, ?, ?)")
                ->execute([$inv['po_id'], $_SESSION['user_id'], $_SESSION['role'] ?? '', 'Payment advice sent to ' . $recipient . ($notes ? " — $notes" : '')]);
            $pdo->commit();
            logActivity('procurement_payment', "Sent payment advice for invoice {$inv['invoice_number']} to $recipient");
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- RECONCILE A SUPPLIER PAYMENT (step 8) ----
    if ($_GET['action'] == 'reconcile_supplier_payment') {
        if (!canAccess('procurement_finance') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to reconcile supplier payments']); exit();
        }
        try {
            $invoiceId = intval($_POST['invoice_id'] ?? 0);
            $reference = substr(trim($_POST['payment_reference'] ?? ''), 0, 100);
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM supplier_invoices WHERE id = ? FOR UPDATE");
            $stmt->execute([$invoiceId]);
            $inv = $stmt->fetch();
            if (!$inv) throw new Exception('Supplier invoice not found.');
            if ((int)$inv['voided'] === 1) throw new Exception('This invoice has been voided and cannot be reconciled.');
            if (floatval($inv['amount_paid']) <= 0) throw new Exception('Record the payment before reconciling it.');
            $pdo->prepare("UPDATE supplier_invoices SET reconciled_at=NOW(), payment_reference=? WHERE id=?")
                ->execute([$reference ?: null, $invoiceId]);
            $pdo->prepare("INSERT INTO procurement_approval_logs (po_id, action, actor_id, actor_role, comments) VALUES (?, 'payment_reconciled', ?, ?, ?)")
                ->execute([$inv['po_id'], $_SESSION['user_id'], $_SESSION['role'] ?? '', 'Payment reconciled against the bank statement' . ($reference ? " (ref: $reference)" : '')]);
            $pdo->commit();
            logActivity('procurement_payment', "Reconciled payment for invoice {$inv['invoice_number']}");
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- VOID A WRONGLY-RECORDED SUPPLIER INVOICE (match hold correction) ----
    if ($_GET['action'] == 'void_supplier_invoice') {
        if (!canAccess('procurement_finance') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to void supplier invoices']); exit();
        }
        try {
            $invoiceId = intval($_POST['invoice_id'] ?? 0);
            $reason = trim($_POST['void_reason'] ?? '');
            if ($reason === '') throw new Exception('A void reason is required.');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM supplier_invoices WHERE id = ? FOR UPDATE");
            $stmt->execute([$invoiceId]);
            $inv = $stmt->fetch();
            if (!$inv) throw new Exception('Supplier invoice not found.');
            if ((int)$inv['voided'] === 1) throw new Exception('This invoice is already voided.');
            if (floatval($inv['amount_paid']) > 0) throw new Exception('Payments have already been recorded against this invoice. Reverse them first, then void the invoice.');
            $pdo->prepare("UPDATE supplier_invoices SET voided=1, voided_at=NOW(), voided_by=?, void_reason=? WHERE id=?")
                ->execute([$_SESSION['user_id'], substr($reason, 0, 255), $invoiceId]);
            $pdo->prepare("INSERT INTO procurement_approval_logs (po_id, action, actor_id, actor_role, comments) VALUES (?, 'invoice_voided', ?, ?, ?)")
                ->execute([$inv['po_id'], $_SESSION['user_id'], $_SESSION['role'] ?? '', "Invoice {$inv['invoice_number']} voided: $reason"]);
            $pdo->commit();
            logActivity('procurement_invoice', "Voided supplier invoice {$inv['invoice_number']} ({$reason})");
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- PROCUREMENT DASHBOARD STATISTICS ----
    if ($_GET['action'] == 'get_procurement_dashboard') {
        if (!canAccess('procurement') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit();
        }
        try {
            $counts = [
                'total_requests' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_requests")->fetchColumn(),
                'draft' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_requests WHERE status='draft'")->fetchColumn(),
                'budget_review' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_requests WHERE status='budget_review'")->fetchColumn(),
                'pending_approval' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_requests WHERE status='pending_approval'")->fetchColumn(),
                'approved' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_requests WHERE status='approved'")->fetchColumn(),
                'sourcing' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_requests WHERE status='sourcing'")->fetchColumn(),
                'supplier_selected' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_requests WHERE status='supplier_selected'")->fetchColumn(),
                'closed_requests' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_requests WHERE status='closed'")->fetchColumn(),
                'rejected' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_requests WHERE status='rejected'")->fetchColumn(),
                'revision_requested' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_requests WHERE status='revision_requested'")->fetchColumn(),
                'pending_finance' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE status='pending_finance'")->fetchColumn(),
                'awaiting_delivery' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE status IN ('ordered','acknowledged')")->fetchColumn(),
                'partially_received' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE status='partially_received'")->fetchColumn(),
                'received' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE status='received'")->fetchColumn(),
                'closed_orders' => (int)$pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE status='closed'")->fetchColumn(),
                'unpaid_invoices' => (int)$pdo->query("SELECT COUNT(*) FROM supplier_invoices WHERE payment_status <> 'paid'")->fetchColumn(),
                'invoices_on_hold' => (int)$pdo->query("SELECT COUNT(*) FROM supplier_invoices WHERE match_status='held'")->fetchColumn(),
            ];
            $recent = $pdo->query("SELECT po.id, po.po_number, po.status, po.total_amount, po.updated_at, s.name AS supplier_name
                FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.id
                WHERE po.status IN ('received','partially_received') ORDER BY po.updated_at DESC LIMIT 6")->fetchAll();
            $lowStock = $pdo->query("SELECT id, name, stock_quantity, low_stock_threshold FROM products
                WHERE (archived = 0 OR archived IS NULL) AND active = 1 AND stock_quantity <= low_stock_threshold
                ORDER BY stock_quantity ASC LIMIT 8")->fetchAll();
            echo json_encode(['success' => true, 'counts' => $counts, 'recent' => $recent, 'low_stock' => $lowStock]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit();
    }

    // ---- PROCUREMENT HISTORY (approval / workflow audit trail) ----
    if ($_GET['action'] == 'get_procurement_history') {
        if (!canAccess('procurement') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit();
        }
        try {
            $where = []; $params = [];
            if (!empty($_GET['action_filter'])) { $where[] = 'l.action = ?'; $params[] = $_GET['action_filter']; }
            if (!empty($_GET['date_from'])) { $where[] = 'DATE(l.created_at) >= ?'; $params[] = $_GET['date_from']; }
            if (!empty($_GET['date_to'])) { $where[] = 'DATE(l.created_at) <= ?'; $params[] = $_GET['date_to']; }
            if (!empty($_GET['q'])) {
                $where[] = '(r.request_number LIKE ? OR po.po_number LIKE ? OR l.comments LIKE ?)';
                $like = '%' . trim($_GET['q']) . '%'; array_push($params, $like, $like, $like);
            }
            $sql = "SELECT l.*, r.request_number, r.status AS request_status, po.po_number, u.full_name AS actor_name
                FROM procurement_approval_logs l
                LEFT JOIN purchase_requests r ON l.request_id = r.id
                LEFT JOIN purchase_orders po ON l.po_id = po.id
                LEFT JOIN users u ON l.actor_id = u.id";
            if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
            $sql .= ' ORDER BY l.created_at DESC, l.id DESC LIMIT 500';
            $stmt = $pdo->prepare($sql); $stmt->execute($params);
            echo json_encode(['success' => true, 'logs' => $stmt->fetchAll()]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit();
    }

    // ---- MY PROCUREMENT NOTIFICATIONS ----
    if ($_GET['action'] == 'get_procurement_notifications') {
        if (!isLoggedIn()) { echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit(); }
        try {
            $stmt = $pdo->prepare("SELECT * FROM procurement_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
            $stmt->execute([$_SESSION['user_id']]);
            echo json_encode(['success' => true, 'notifications' => $stmt->fetchAll(), 'unread' => getUnreadProcurementNotificationCount($_SESSION['user_id'])]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit();
    }

    if ($_GET['action'] == 'mark_procurement_notifications_read') {
        if (!isLoggedIn()) { echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit(); }
        try {
            $pdo->prepare("UPDATE procurement_notifications SET is_read = 1 WHERE user_id = ?")->execute([$_SESSION['user_id']]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit();
    }

    // ---- TOKENIZE CARD (never stores the full card number) ----
    if ($_GET['action'] == 'tokenize_card') {
        if (!canAccess('tokenization') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        try {
            $number = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
            if (strlen($number) < 13 || strlen($number) > 19) {
                echo json_encode(['success' => false, 'message' => 'Invalid card number']);
                exit();
            }
            $brand = 'unknown';
            if (preg_match('/^4/', $number)) $brand = 'visa';
            elseif (preg_match('/^(5[1-5]|2[2-7])/', $number)) $brand = 'mastercard';
            elseif (preg_match('/^3[47]/', $number)) $brand = 'amex';
            $last4 = substr($number, -4);
            $token = bin2hex(random_bytes(24)); // the safe stand-in for the card number

            $stmt = $pdo->prepare("
                INSERT INTO payment_tokens (customer_id, token, card_brand, last4, expiry_month, expiry_year, cardholder_name, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null,
                $token, $brand, $last4,
                !empty($_POST['expiry_month']) ? intval($_POST['expiry_month']) : null,
                !empty($_POST['expiry_year']) ? intval($_POST['expiry_year']) : null,
                $_POST['cardholder_name'] ?? '',
                $_SESSION['user_id']
            ]);
            logActivity('tokenize_card', "Tokenized $brand card ending $last4");
            echo json_encode(['success' => true, 'token_id' => $pdo->lastInsertId(), 'last4' => $last4, 'brand' => $brand]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- GET PAYMENT TOKENS ----
    if ($_GET['action'] == 'get_payment_tokens') {
        try {
            $stmt = $pdo->query("
                SELECT t.id, t.card_brand, t.last4, t.expiry_month, t.expiry_year, t.cardholder_name,
                       t.created_at, t.active, c.name AS customer_name
                FROM payment_tokens t
                LEFT JOIN customers c ON t.customer_id = c.id
                ORDER BY t.created_at DESC
            ");
            echo json_encode($stmt->fetchAll());
        } catch (Exception $e) { echo json_encode([]); }
        exit();
    }

    // ---- DELETE (DEACTIVATE) TOKEN ----
    if ($_GET['action'] == 'delete_payment_token') {
        if (!canAccess('tokenization') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        try {
            $id = intval($_GET['id'] ?? 0);
            $pdo->prepare("UPDATE payment_tokens SET active = 0 WHERE id = ?")->execute([$id]);
            logActivity('delete_payment_token', "Deactivated payment token #$id");
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- SAVE SUPPLIER ----
    if ($_GET['action'] == 'save_supplier') {
        if (!canAccess('suppliers') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        
        try {
            $id = !empty($_POST['id']) ? intval($_POST['id']) : null;
            $data = [
                $_POST['name'] ?? '',
                $_POST['contact_person'] ?? '',
                $_POST['phone'] ?? '',
                $_POST['email'] ?? '',
                $_POST['address'] ?? '',
                $_POST['notes'] ?? '',
                $_POST['status'] ?? 'active'
            ];
            
            if (empty($data[0])) {
                echo json_encode(['success' => false, 'message' => 'Supplier name is required']);
                exit();
            }
            
            if ($id) {
                $stmt = $pdo->prepare("
                    UPDATE suppliers SET 
                        name = ?, contact_person = ?, phone = ?, email = ?, 
                        address = ?, notes = ?, status = ?
                    WHERE id = ?
                ");
                $data[] = $id;
                $stmt->execute($data);
                logActivity('update_supplier', "Updated supplier: {$data[0]}");
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO suppliers 
                    (name, contact_person, phone, email, address, notes, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute($data);
                logActivity('add_supplier', "Added supplier: {$data[0]}");
            }
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- DELETE SUPPLIER ----
    if ($_GET['action'] == 'delete_supplier') {
        if (!canAccess('suppliers') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        
        try {
            $id = intval($_GET['id'] ?? 0);
            
            // Check if supplier has purchases
            $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM purchases WHERE supplier_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetch()['c'] > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete — supplier has purchase history']);
                exit();
            }
            
            $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('delete_supplier', "Deleted supplier ID: $id");
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- GET SUPPLIERS (for dropdown) ----
    if ($_GET['action'] == 'get_suppliers') {
        try {
            $stmt = $pdo->query("SELECT id, name FROM suppliers WHERE status = 'active' ORDER BY name");
            echo json_encode($stmt->fetchAll());
        } catch (Exception $e) {
            echo json_encode([]);
        }
        exit();
    }

    // ---- SAVE PURCHASE ----
    if ($_GET['action'] == 'save_purchase') {
        if (!canAccess('purchases_create') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        
        try {
            $pdo->beginTransaction();
            
            $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;
            $purchase_date = $_POST['purchase_date'] ?? date('Y-m-d');
            $notes = $_POST['notes'] ?? '';
            $payment_status = $_POST['payment_status'] ?? 'unpaid';
            $items = json_decode($_POST['items'] ?? '[]', true);
            
            if (empty($items)) {
                echo json_encode(['success' => false, 'message' => 'At least one item required']);
                exit();
            }
            
            // Generate PO number
            $po_number = 'PO-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Calculate totals
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += floatval($item['quantity']) * floatval($item['unit_cost']);
            }
            $tax_rate = floatval(getSetting('tax_rate', 12)) / 100;
            $tax_amount = $subtotal * $tax_rate;
            $total_amount = $subtotal + $tax_amount;
            
            // Insert purchase
            $stmt = $pdo->prepare("
                INSERT INTO purchases 
                (po_number, supplier_id, purchase_date, subtotal, tax_amount, total_amount, payment_status, notes, recorded_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $po_number, $supplier_id, $purchase_date,
                $subtotal, $tax_amount, $total_amount,
                $payment_status, $notes, $_SESSION['user_id']
            ]);
            $purchase_id = $pdo->lastInsertId();
            
            // Insert items + update stock + log movements
            foreach ($items as $item) {
                $product_id = intval($item['product_id']);
                $quantity = intval($item['quantity']);
                $unit_cost = floatval($item['unit_cost']);
                $total_cost = $quantity * $unit_cost;
                
                // Get current stock
                $stmt = $pdo->prepare("SELECT stock_quantity, cost_price FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $prod = $stmt->fetch();
                $before = intval($prod['stock_quantity']);
                $after = $before + $quantity;
                
                // Insert purchase item
                $stmt = $pdo->prepare("
                    INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_cost, total_cost) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$purchase_id, $product_id, $quantity, $unit_cost, $total_cost]);
                
                // Update product stock + cost
                $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ?, cost_price = ? WHERE id = ?");
                $stmt->execute([$after, $unit_cost, $product_id]);
                
                // Log movement
                $stmt = $pdo->prepare("
                    INSERT INTO stock_movements 
                    (product_id, movement_type, quantity, quantity_before, quantity_after, reason, reference_id, reference_type, user_id) 
                    VALUES (?, 'purchase', ?, ?, ?, ?, ?, 'purchase', ?)
                ");
                $stmt->execute([
                    $product_id, $quantity, $before, $after,
                    "Purchase from supplier (PO: $po_number)",
                    $purchase_id, $_SESSION['user_id']
                ]);
            }
            
            $pdo->commit();
            
            logActivity('create_purchase', "Created purchase: $po_number (₱$total_amount)");
            
            echo json_encode(['success' => true, 'po_number' => $po_number, 'purchase_id' => $purchase_id]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- GET PURCHASE ----
    if ($_GET['action'] == 'get_purchase') {
        if (!canAccess('purchases') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        
        try {
            $id = intval($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("
                SELECT p.*, s.name as supplier_name, s.phone as supplier_phone, s.email as supplier_email,
                    u.full_name as recorded_by_name
                FROM purchases p
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                LEFT JOIN users u ON p.recorded_by = u.id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $purchase = $stmt->fetch();
            
            if (!$purchase) {
                echo json_encode(['success' => false, 'message' => 'Purchase not found']);
                exit();
            }
            
            $stmt = $pdo->prepare("
                SELECT pi.*, p.name as product_name, p.unit
                FROM purchase_items pi
                JOIN products p ON pi.product_id = p.id
                WHERE pi.purchase_id = ?
            ");
            $stmt->execute([$id]);
            $purchase['items'] = $stmt->fetchAll();
            
            echo json_encode($purchase);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ---- EXPORT INVENTORY REPORT ----
    if ($_GET['action'] == 'export_inventory_report') {
        if (!canAccess('inventory_reports') && !isAdmin()) {
            http_response_code(403);
            exit('Unauthorized');
        }
        
        $type = $_GET['type'] ?? 'current';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="inventory_' . $type . '_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        
        fputcsv($output, ['Inventory Report: ' . strtoupper($type)]);
        fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        
        if ($type == 'current' || $type == 'valuation') {
            fputcsv($output, ['Product', 'SKU', 'Category', 'Stock', 'Unit', 'Cost', 'Retail', 'Stock Value (Cost)', 'Stock Value (Retail)']);
            $stmt = $pdo->query("
                SELECT p.name, p.sku, c.name as category, p.stock_quantity, p.unit, 
                    p.cost_price, p.selling_price
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.archived = 0
                ORDER BY p.name
            ");
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $r['cost_value'] = $r['stock_quantity'] * ($r['cost_price'] ?? 0);
                $r['retail_value'] = $r['stock_quantity'] * $r['selling_price'];
                fputcsv($output, $r);
            }
        } elseif ($type == 'low') {
            fputcsv($output, ['Product', 'Category', 'Stock', 'Threshold', 'Unit']);
            $stmt = $pdo->query("
                SELECT p.name, c.name as category, p.stock_quantity, p.low_stock_threshold, p.unit
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.archived = 0 
                AND p.stock_quantity > 0 
                AND p.stock_quantity <= COALESCE(p.low_stock_threshold, 5)
                ORDER BY p.stock_quantity ASC
            ");
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, $r);
            }
        } elseif ($type == 'out') {
            fputcsv($output, ['Product', 'Category', 'Unit', 'Cost', 'Retail']);
            $stmt = $pdo->query("
                SELECT p.name, c.name as category, p.unit, p.cost_price, p.selling_price
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.archived = 0 AND p.stock_quantity = 0
                ORDER BY p.name
            ");
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, $r);
            }
        } elseif ($type == 'movement') {
            fputcsv($output, ['Date', 'Product', 'Type', 'Quantity', 'Before', 'After', 'Reason', 'User']);
            $stmt = $pdo->query("
                SELECT sm.created_at, p.name as product, sm.movement_type, sm.quantity, 
                    sm.quantity_before, sm.quantity_after, sm.reason, u.full_name as user
                FROM stock_movements sm
                LEFT JOIN products p ON sm.product_id = p.id
                LEFT JOIN users u ON sm.user_id = u.id
                ORDER BY sm.created_at DESC
                LIMIT 500
            ");
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, $r);
            }
        } elseif ($type == 'purchase') {
            fputcsv($output, ['PO Number', 'Date', 'Supplier', 'Status', 'Total']);
            $stmt = $pdo->query("
                SELECT p.po_number, p.purchase_date, s.name as supplier, p.payment_status, p.total_amount
                FROM purchases p
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                ORDER BY p.purchase_date DESC
            ");
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, $r);
            }
        }
        
        fclose($output);
        exit();
    }

            // ============================================
        // Returns endpoints
        // ============================================

        if ($_GET['action'] == 'create_return') {
            if (!canAccess('returns_create') && !isAdmin()) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit();
            }
            
            $sale_id = isset($_POST['sale_id']) ? $_POST['sale_id'] : null;
            $items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
            $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
            $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
            $user_id = $_SESSION['user_id'];
            
            if (!$sale_id || empty($items) || !$reason) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit();
            }
            
            $result = $returnManager->createReturn($sale_id, $items, $reason, $notes, $user_id);
            echo json_encode($result);
            exit();
        }

        if ($_GET['action'] == 'approve_return') {
            if (!canAccess('returns_approve') && !isAdmin()) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit();
            }
            
            $return_id = isset($_POST['return_id']) ? $_POST['return_id'] : null;
            $user_id = $_SESSION['user_id'];
            
            if (!$return_id) {
                echo json_encode(['success' => false, 'message' => 'Return ID required']);
                exit();
            }
            
            $result = $returnManager->approveReturn($return_id, $user_id);
            echo json_encode($result);
            exit();
        }

        if ($_GET['action'] == 'reject_return') {
            if (!canAccess('returns_approve') && !isAdmin()) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit();
            }
            
            $return_id = isset($_POST['return_id']) ? $_POST['return_id'] : null;
            $reason = isset($_POST['reason']) ? $_POST['reason'] : 'No reason provided';
            
            if (!$return_id) {
                echo json_encode(['success' => false, 'message' => 'Return ID required']);
                exit();
            }
            
            $result = $returnManager->rejectReturn($return_id, $reason);
            echo json_encode($result);
            exit();
        }

        if ($_GET['action'] == 'get_return') {
            if (!canAccess('returns') && !canAccess('returns_view') && !canAccess('returns_create') && !isAdmin()) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit();
            }
            
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Return ID required']);
                exit();
            }
            
            $result = $returnManager->getReturn($id);
            echo json_encode($result);
            exit();
        }

        if ($_GET['action'] == 'get_return_stats') {
            if (!canAccess('returns') && !canAccess('returns_view') && !isAdmin()) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit();
            }
            
            echo json_encode($returnManager->getReturnStats());
            exit();
        }

        // ============================================
        // CHECK IF SALE ALREADY HAS A RETURN
        // ============================================

        if ($_GET['action'] == 'check_sale_returned') {
            $sale_id = isset($_GET['sale_id']) ? $_GET['sale_id'] : null;
            
            if (!$sale_id) {
                echo json_encode(['returned' => false, 'error' => 'No sale ID provided']);
                exit();
            }
            
            try {
                $stmt = $pdo->prepare("
                SELECT COUNT(*) as return_count 
                FROM returns 
                WHERE original_sale_id = ?
            ");
                $stmt->execute([$sale_id]);
                $result = $stmt->fetch();
                
                echo json_encode([
                    'returned' => $result['return_count'] > 0,
                    'count' => $result['return_count']
                ]);
            } catch(PDOException $e) {
                echo json_encode(['returned' => false, 'error' => $e->getMessage()]);
            }
            exit();
        }

            // ============================================
            // SAVE EXPENSE
            // ============================================
            if ($_GET['action'] == 'save_expense') {
                if (!isAdmin() && !hasRole('finance')) {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                    exit();
                }
                
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO expenses (expense_date, category, description, amount, recorded_by)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['expense_date'] ?? date('Y-m-d'),
                        $_POST['category'] ?? 'Other',
                        $_POST['description'] ?? '',
                        floatval($_POST['amount'] ?? 0),
                        $_SESSION['user_id']
                    ]);
                    
                    logActivity('save_expense', "Recorded expense: {$_POST['category']} - ₱{$_POST['amount']}");
                    
                    echo json_encode(['success' => true]);
                } catch (PDOException $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                exit();
            }

            // ============================================
            // EXPORT REPORT TO CSV
            // ============================================
            if ($_GET['action'] == 'export_report') {
                if (!isAdmin() && !hasRole('finance')) {
                    http_response_code(403);
                    exit('Unauthorized');
                }
                
                $type = $_GET['type'] ?? 'revenue';
                $start = $_GET['start_date'] ?? date('Y-m-01');
                $end = $_GET['end_date'] ?? date('Y-m-t');
                
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="finance_' . $type . '_' . $start . '_to_' . $end . '.csv"');
                
                $output = fopen('php://output', 'w');
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel
                
                // Header info
                fputcsv($output, ['Finance Report: ' . strtoupper($type)]);
                fputcsv($output, ['Period: ' . $start . ' to ' . $end]);
                fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
                fputcsv($output, []);
                
                if ($type == 'revenue' || $type == 'profit') {
                    fputcsv($output, ['Date', 'Transactions', 'Discounts', 'VAT', 'Revenue']);
                    $stmt = $pdo->prepare("
                        SELECT DATE(sale_date) as date, COUNT(*) as txn, 
                            SUM(discount_amount) as discount, SUM(tax) as vat, SUM(total_amount) as revenue
                        FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?
                        GROUP BY DATE(sale_date) ORDER BY date
                    ");
                    $stmt->execute([$start, $end]);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        fputcsv($output, $row);
                    }
                } elseif ($type == 'expense') {
                    fputcsv($output, ['Date', 'Category', 'Description', 'Amount']);
                    $stmt = $pdo->prepare("SELECT expense_date, category, description, amount FROM expenses WHERE expense_date BETWEEN ? AND ? ORDER BY expense_date");
                    $stmt->execute([$start, $end]);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        fputcsv($output, $row);
                    }
                } elseif ($type == 'sales') {
                    fputcsv($output, ['Invoice', 'Date', 'Total', 'VAT', 'Discount']);
                    $stmt = $pdo->prepare("SELECT invoice_number, sale_date, total_amount, tax, discount_amount FROM sales WHERE DATE(sale_date) BETWEEN ? AND ? ORDER BY sale_date DESC");
                    $stmt->execute([$start, $end]);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        fputcsv($output, $row);
                    }
                } elseif ($type == 'refund') {
                    fputcsv($output, ['Return #', 'Date', 'Reason', 'Status', 'Amount']);
                    $stmt = $pdo->prepare("SELECT return_number, created_at, reason, status, total_refund FROM returns WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC");
                    $stmt->execute([$start, $end]);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        fputcsv($output, $row);
                    }
                } elseif ($type == 'payment') {
                    fputcsv($output, ['Method', 'Count', 'Total']);
                    $stmt = $pdo->prepare("SELECT payment_method, COUNT(*) as count, SUM(total_amount) as total FROM sales WHERE DATE(sale_date) BETWEEN ? AND ? GROUP BY payment_method");
                    $stmt->execute([$start, $end]);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        fputcsv($output, $row);
                    }
                }
                
                fclose($output);
                exit();
            }

    // ============================================
    // PAYMONGO PAYMENT GATEWAY HANDLERS
    // ============================================
    require_once __DIR__ . '/paymongo.php';

    if ($_GET['action'] == 'get_paymongo_settings') {
        if (!isAdmin() && !hasRole('finance')) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        echo json_encode([
            'success' => true,
            'enabled' => paymongoEnabled(),
            'mode' => paymongoMode(),
            'public_key' => paymongoPublicKey(),
            'has_secret_key' => paymongoSecretKey() !== '',
            'has_webhook_secret' => paymongoWebhookSecret() !== '',
            'methods' => getSetting('paymongo_methods', 'gcash,maya,grab_pay,card'),
            'success_url' => getSetting('paymongo_success_url', ''),
            'cancel_url' => getSetting('paymongo_cancel_url', ''),
            'webhook_url' => paymongoWebhookUrl(),
            'configured' => paymongoIsConfigured(),
            'env_secret' => paymongoEnv('PAYMONGO_SECRET_KEY') !== '',
            'env_webhook_secret' => paymongoEnv('PAYMONGO_WEBHOOK_SECRET') !== '',
        ]);
        exit();
    }

    if ($_GET['action'] == 'save_paymongo_settings') {
        if (!isAdmin() && !hasRole('finance')) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Your session expired. Please refresh the page and try again.']);
            exit();
        }
        try {
            $modeVal = $_POST['mode'] ?? 'test';
            $mode = in_array($modeVal, ['live', 'demo'], true) ? $modeVal : 'test';

            // Blank secret fields keep the stored value; a blank public key clears it.
            $publicKey  = isset($_POST['public_key'])      ? trim($_POST['public_key'])      : getSetting('paymongo_public_key', '');
            $secretKey  = !empty($_POST['secret_key'])     ? trim($_POST['secret_key'])      : getSetting('paymongo_secret_key', '');
            $webhookSec = !empty($_POST['webhook_secret']) ? trim($_POST['webhook_secret'])  : getSetting('paymongo_webhook_secret', '');

            // Demo credentials are only meaningful in demo mode, so discard them
            // rather than forwarding them to the real API. We report it rather
            // than wiping silently — the public key is visible in the form, and a
            // key that vanishes with no explanation reads as data loss.
            $clearedDemo = false;
            if ($mode !== 'demo') {
                if (strpos($publicKey,  'pk_demo_') === 0)    { $publicKey = '';  $clearedDemo = true; }
                if (strpos($secretKey,  'sk_demo_') === 0)    { $secretKey = '';  $clearedDemo = true; }
                if (strpos($webhookSec, 'whsec_demo_') === 0) { $webhookSec = ''; $clearedDemo = true; }
            }

            // Validate before writing anything, so a bad key leaves the previous
            // configuration exactly as it was.
            $formatError = paymongoKeyFormatError($publicKey, $secretKey, $webhookSec, $mode);
            if ($formatError !== null) {
                echo json_encode(['success' => false, 'message' => $formatError]);
                exit();
            }

            setSetting('paymongo_enabled', !empty($_POST['enabled']) ? '1' : '0');
            setSetting('paymongo_mode', $mode);
            setSetting('paymongo_public_key', $publicKey);
            setSetting('paymongo_secret_key', $secretKey);
            setSetting('paymongo_webhook_secret', $webhookSec);
            if (isset($_POST['methods'])) { setSetting('paymongo_methods', trim($_POST['methods'])); }
            if (isset($_POST['success_url'])) { setSetting('paymongo_success_url', trim($_POST['success_url'])); }
            if (isset($_POST['cancel_url'])) { setSetting('paymongo_cancel_url', trim($_POST['cancel_url'])); }
            // Demo mode: provision fake-but-real-looking credentials so the
            // gateway looks configured, the settings page matches the real one,
            // and the signed self-delivered webhook works offline.
            if ($mode === 'demo') {
                if (paymongoPublicKey() === '') { setSetting('paymongo_public_key', 'pk_demo_' . bin2hex(random_bytes(8))); }
                if (paymongoSecretKey() === '') { setSetting('paymongo_secret_key', 'sk_demo_' . bin2hex(random_bytes(8))); }
                if (paymongoWebhookSecret() === '') { setSetting('paymongo_webhook_secret', 'whsec_demo_' . bin2hex(random_bytes(8))); }
            }
            $modeLabel = $mode === 'demo' ? 'demo (simulator)' : $mode;
            logActivity('save_paymongo_settings', "Updated PayMongo gateway settings ($modeLabel mode)");
            $note = $clearedDemo
                ? ' Demo credentials were cleared — paste your real '
                  . ($mode === 'live' ? 'pk_live_/sk_live_' : 'pk_test_/sk_test_') . ' keys to connect.'
                : '';
            echo json_encode(['success' => true, 'message' => 'PayMongo settings saved.' . $note]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    if ($_GET['action'] == 'test_paymongo_connection') {
        if (!isAdmin() && !hasRole('finance')) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Your session expired. Please refresh the page and try again.']);
            exit();
        }
        try {
            if (paymongoIsDemo()) {
                echo json_encode(['success' => true, 'message' => 'Demo simulator ready — payments are simulated locally, no real PayMongo keys needed.']);
                exit();
            }
            $secret = !empty($_POST['secret_key']) ? trim($_POST['secret_key']) : paymongoSecretKey();
            if ($secret === '') {
                echo json_encode(['success' => false, 'message' => 'No secret key provided.']);
                exit();
            }
            // Reject a malformed key locally instead of sending it to PayMongo and
            // surfacing their raw "Invalid merchant key format" error.
            $formatError = paymongoKeyFormatError(paymongoPublicKey(), $secret, '', paymongoMode());
            if ($formatError !== null) {
                echo json_encode(['success' => false, 'message' => $formatError]);
                exit();
            }
            $res = PayMongo::testConnection($secret);
            echo json_encode([
                'success' => $res['ok'],
                'message' => $res['ok'] ? 'Connected to PayMongo successfully. Secret key is valid.' : ($res['error'] ?: 'Connection failed'),
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // --- Create a hosted PayMongo Checkout Session from the POS cart ---
    if ($_GET['action'] == 'create_paymongo_checkout') {
        if (!canAccess('cart') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Your session expired. Please refresh the page and try again.']);
            exit();
        }
        try {
            if (!paymongoIsConfigured()) {
                echo json_encode(['success' => false, 'message' => 'PayMongo is not configured yet. Ask an admin to set it up under Payment Gateway.']);
                exit();
            }

            $items = json_decode($_POST['items'] ?? '[]', true);
            $customerId = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
            $discount = $_POST['discount'] ?? 0;
            $loyalty = $_POST['loyalty_points_used'] ?? 0;
            $hint = $_POST['payment_method'] ?? 'paymongo';
            if (!in_array($hint, ['gcash', 'maya', 'grab_pay', 'card', 'qrph', 'paymongo'], true)) { $hint = 'paymongo'; }

            $valid = paymongoValidateCart($items, $customerId, $discount, $loyalty);
            if (empty($valid['ok'])) {
                echo json_encode(['success' => false, 'message' => $valid['message']]);
                exit();
            }
            $snapshot = $valid['snapshot'];

            $reference = 'PMP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            // Opaque token carried in the success/cancel URL so the confirmation
            // page can only be opened for a checkout the payer actually started.
            $returnToken = paymongoNewReturnToken();
            $stmt = $pdo->prepare("INSERT INTO paymongo_checkouts (reference, return_token, payment_method_hint, status, amount, currency, customer_id, cashier_id, cart_snapshot) VALUES (?, ?, ?, 'pending', ?, 'PHP', ?, ?, ?)");
            $stmt->execute([$reference, $returnToken, $hint, $snapshot['total'], $customerId, $_SESSION['user_id'] ?? null, json_encode($snapshot)]);
            $checkoutId = (int)$pdo->lastInsertId();

            $successUrl = getSetting('paymongo_success_url', '');
            $cancelUrl = getSetting('paymongo_cancel_url', '');
            if ($successUrl === '') { $successUrl = paymongoResultUrl($reference, 'success', $returnToken); }
            if ($cancelUrl === '') { $cancelUrl = paymongoResultUrl($reference, 'cancel', $returnToken); }

            $attributes = [
                'line_items' => paymongoLineItems($snapshot),
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'description' => mb_substr('Order ' . $reference . ' - ' . $store_name, 0, 250),
                'reference_number' => $reference,
                'send_email_receipt' => false,
                'metadata' => ['checkout_reference' => $reference],
            ];
            $methods = paymongoCheckoutMethodTypes($hint, explode(',', getSetting('paymongo_methods', '')));
            if (!empty($methods)) { $attributes['payment_method_types'] = $methods; }

            $res = PayMongo::createCheckoutSession($attributes);
            if (empty($res['ok'])) {
                $pdo->prepare("UPDATE paymongo_checkouts SET status = 'failed', error_message = ? WHERE id = ?")
                    ->execute([mb_substr((string)$res['error'], 0, 250), $checkoutId]);
                echo json_encode(['success' => false, 'message' => $res['error']]);
                exit();
            }

            $session = $res['data']['data'] ?? [];
            $sessionId = $session['id'] ?? null;
            $checkoutUrl = $session['attributes']['checkout_url'] ?? null;
            if (!$sessionId || !$checkoutUrl) {
                $pdo->prepare("UPDATE paymongo_checkouts SET status = 'failed', error_message = 'Malformed response from PayMongo' WHERE id = ?")
                    ->execute([$checkoutId]);
                echo json_encode(['success' => false, 'message' => 'PayMongo did not return a checkout URL.']);
                exit();
            }

            $pdo->prepare("UPDATE paymongo_checkouts SET checkout_session_id = ?, checkout_url = ?, updated_at = NOW() WHERE id = ?")
                ->execute([$sessionId, $checkoutUrl, $checkoutId]);

            logActivity('paymongo_checkout_created', "Created PayMongo checkout {$reference} (₱" . number_format((float)$snapshot['total'], 2) . ", {$hint})");

            echo json_encode([
                'success' => true,
                'reference' => $reference,
                'checkout_id' => $checkoutId,
                'checkout_session_id' => $sessionId,
                'checkout_url' => $checkoutUrl,
                'amount' => (float)$snapshot['total'],
                'status' => 'pending',
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // --- Poll PayMongo for a pending checkout and finalize the sale if paid ---
    if ($_GET['action'] == 'get_paymongo_checkout_status') {
        if (!canAccess('cart') && !isAdmin() && !hasRole('finance')) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        try {
            if (!empty($_GET['id'])) {
                $stmt = $pdo->prepare("SELECT * FROM paymongo_checkouts WHERE id = ?");
                $stmt->execute([(int)$_GET['id']]);
            } else {
                $stmt = $pdo->prepare("SELECT * FROM paymongo_checkouts WHERE reference = ?");
                $stmt->execute([$_GET['reference'] ?? '']);
            }
            $row = $stmt->fetch();
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Checkout not found']);
                exit();
            }

            if ($row['status'] === 'pending') {
                paymongoPollCheckout($pdo, $saleManager, $row);
                $stmt = $pdo->prepare("SELECT * FROM paymongo_checkouts WHERE id = ?");
                $stmt->execute([$row['id']]);
                $row = $stmt->fetch();
            }

            echo json_encode([
                'success' => true,
                'status' => $row['status'],
                'paid' => $row['status'] === 'paid',
                'sale_id' => $row['sale_id'] ? (int)$row['sale_id'] : null,
                'reference' => $row['reference'],
                'amount' => (float)$row['amount'],
                'checkout_url' => $row['checkout_url'],
                'error' => $row['error_message'],
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // --- Secure lookup for the payment confirmation (result) page ---
    // The page never trusts a `result=success` query flag: it re-reads the
    // authoritative checkout status here. The opaque return token prevents
    // reference enumeration across orders.
    if ($_GET['action'] == 'get_paymongo_result') {
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Please sign in again.']);
            exit();
        }
        try {
            $reference = (string)($_GET['ref'] ?? '');
            $token = (string)($_GET['token'] ?? '');
            if ($reference === '' || $token === '') {
                echo json_encode(['success' => false, 'message' => 'Missing payment reference.']);
                exit();
            }
            $stmt = $pdo->prepare("SELECT * FROM paymongo_checkouts WHERE reference = ?");
            $stmt->execute([$reference]);
            $row = $stmt->fetch();
            if (!$row || empty($row['return_token']) || !hash_equals((string)$row['return_token'], $token)) {
                echo json_encode(['success' => false, 'message' => 'Payment not found, or this confirmation link is no longer valid.']);
                exit();
            }

            // Refresh from PayMongo while still pending (same poller the cart uses).
            if ($row['status'] === 'pending' && paymongoIsConfigured()) {
                paymongoPollCheckout($pdo, $saleManager, $row);
                $stmt = $pdo->prepare("SELECT * FROM paymongo_checkouts WHERE id = ?");
                $stmt->execute([$row['id']]);
                $row = $stmt->fetch();
            }

            $methodCode = $row['payment_type'] ?: $row['payment_method_hint'];
            $payload = [
                'success' => true,
                'status' => $row['status'],
                'paid' => $row['status'] === 'paid',
                'reference' => $row['reference'],
                'amount' => (float)$row['amount'],
                'currency' => $row['currency'],
                'payment_type' => $methodCode,
                'payment_label' => paymongoMethodLabel($methodCode),
                'paid_at' => $row['paid_at'],
                'error' => $row['error_message'],
                'sale_id' => $row['sale_id'] ? (int)$row['sale_id'] : null,
            ];

            if ($row['status'] === 'paid' && !empty($row['sale_id'])) {
                $sale = $saleManager->getSale((int)$row['sale_id']);
                if ($sale) {
                    $payload['sale'] = [
                        'invoice_number' => $sale['invoice_number'],
                        'sale_date' => $sale['sale_date'],
                        'total_amount' => (float)$sale['total_amount'],
                        'subtotal' => (float)$sale['subtotal'],
                        'tax' => (float)$sale['tax'],
                        'discount_amount' => (float)$sale['discount_amount'],
                        'payment_method' => $sale['payment_method'],
                        'amount_paid' => (float)$sale['amount_paid'],
                        'change_amount' => (float)$sale['change_amount'],
                        'cashier' => $sale['cashier'],
                        'customer' => $sale['customer'],
                        'paymongo_payment_id' => $sale['paymongo_payment_id'] ?? null,
                        'items' => $sale['items'] ?? [],
                    ];
                }
            }
            echo json_encode($payload);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // --- Abandon a pending checkout (keep the stock, no sale created) ---
    if ($_GET['action'] == 'cancel_paymongo_checkout') {
        if (!canAccess('cart') && !isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Your session expired. Please refresh the page and try again.']);
            exit();
        }
        try {
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$id && !empty($_POST['reference'])) {
                $stmt = $pdo->prepare("SELECT id FROM paymongo_checkouts WHERE reference = ?");
                $stmt->execute([$_POST['reference']]);
                $id = (int)($stmt->fetchColumn() ?: 0);
            }
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Checkout not found']);
                exit();
            }
            $stmt = $pdo->prepare("SELECT * FROM paymongo_checkouts WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && $row['status'] === 'pending') {
                if (!empty($row['checkout_session_id'])) {
                    PayMongo::expireCheckoutSession($row['checkout_session_id']);
                }
                $pdo->prepare("UPDATE paymongo_checkouts SET status = 'cancelled', updated_at = NOW() WHERE id = ? AND status = 'pending'")
                    ->execute([$id]);
                logActivity('paymongo_checkout_cancelled', "Cancelled PayMongo checkout {$row['reference']}");
            }
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // --- Recent online payments (for the gateway page) ---
    if ($_GET['action'] == 'get_paymongo_checkouts') {
        if (!isAdmin() && !hasRole('finance')) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        try {
            $stmt = $pdo->query("
                SELECT pc.*, u.full_name AS cashier, c.name AS customer_name
                FROM paymongo_checkouts pc
                LEFT JOIN users u ON pc.cashier_id = u.id
                LEFT JOIN customers c ON pc.customer_id = c.id
                ORDER BY pc.created_at DESC
                LIMIT 50
            ");
            echo json_encode(['success' => true, 'checkouts' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    }
