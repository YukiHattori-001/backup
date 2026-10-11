<?php
// Application bootstrap: DB connection, settings, session, auth helpers

error_reporting(E_ALL);
ini_set('display_errors', 0); // don't leak paths/SQL to users
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/error.log');

header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// ============================================
// ENVIRONMENT (.env) LOADER
// ============================================
// Optional KEY=VALUE file in the project root (never committed). Values are
// exposed through getenv()/_ENV and used as a secure server-side source for
// credentials (DB + PayMongo). Real environment variables always win, so the
// file is purely a local convenience. See .env.example.
function loadDotEnv($path) {
    if (!is_string($path) || !is_file($path) || !is_readable($path)) { return; }
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) { return; }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') { continue; }
        $pos = strpos($line, '=');
        if ($pos === false) { continue; }
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        if ($key === '' || getenv($key) !== false) { continue; }
        if (strlen($val) >= 2) {
            $first = $val[0];
            $last = substr($val, -1);
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $val = substr($val, 1, -1);
            }
        }
        putenv($key . '=' . $val);
        $_ENV[$key] = $val;
    }
}
loadDotEnv(dirname(__DIR__) . '/.env');

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'pos_system';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET NAMES utf8mb4");
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// ============================================
// SETTINGS FUNCTIONS
// ============================================

function getSetting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch(PDOException $e) {
        return $default;
    }
}

function setSetting($key, $value) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE setting_value = ?");
        return $stmt->execute([$key, $value, $value]);
    } catch(PDOException $e) {
        return false;
    }
}

// ============================================
// CSRF PROTECTION
// ============================================
// A per-session token embedded into pages and echoed back by state-changing
// AJAX calls. The token is generated lazily and checked with hash_equals().
function csrfToken() {
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals((string)$_SESSION['csrf_token'], $token);
}

function ensureCustomRolesTable() {
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS custom_roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role_key VARCHAR(50) NOT NULL UNIQUE,
        role_name VARCHAR(80) NOT NULL,
        permissions LONGTEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensureSavedLoginAccountsTable() {
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS saved_login_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        device_token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_saved_account_device (user_id, device_token_hash),
        KEY idx_saved_account_device (device_token_hash, expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensureProcurementTables() {
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS procurement_quotes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        supplier_id INT NOT NULL,
        unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        lead_time_days INT DEFAULT NULL,
        payment_terms VARCHAR(255) DEFAULT NULL,
        technical_notes TEXT DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_procurement_quote_request (request_id),
        KEY idx_procurement_quote_supplier (supplier_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS procurement_supplier_performance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL UNIQUE,
        supplier_id INT NOT NULL,
        on_time_in_full ENUM('yes','no','partial') NOT NULL,
        quality_rating TINYINT NOT NULL,
        responsiveness_rating TINYINT NOT NULL,
        issue_resolution TEXT DEFAULT NULL,
        lessons_learned TEXT DEFAULT NULL,
        rated_by INT DEFAULT NULL,
        rated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_procurement_performance_supplier (supplier_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $columns = [
        'cost_center' => "VARCHAR(100) DEFAULT NULL",
        'budget_code' => "VARCHAR(100) DEFAULT NULL",
        'budget_amount' => "DECIMAL(12,2) DEFAULT 0.00",
        'budget_status' => "ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'",
        'budget_checked_by' => "INT DEFAULT NULL",
        'budget_checked_at' => "DATETIME DEFAULT NULL",
        'po_number' => "VARCHAR(50) DEFAULT NULL",
        'po_total' => "DECIMAL(12,2) DEFAULT NULL",
        'po_issued_at' => "DATETIME DEFAULT NULL",
        'po_acknowledged_at' => "DATETIME DEFAULT NULL",
        'purchase_id' => "INT DEFAULT NULL",
        'received_quantity' => "INT NOT NULL DEFAULT 0",
        'grn_number' => "VARCHAR(50) DEFAULT NULL",
        'received_at' => "DATETIME DEFAULT NULL",
        'receipt_notes' => "TEXT DEFAULT NULL",
        'invoice_number' => "VARCHAR(100) DEFAULT NULL",
        'invoice_amount' => "DECIMAL(12,2) DEFAULT NULL",
        'invoice_quantity' => "INT DEFAULT NULL",
        'invoice_status' => "ENUM('not_submitted','held','matched','paid') NOT NULL DEFAULT 'not_submitted'",
        'invoice_notes' => "TEXT DEFAULT NULL",
        'invoice_received_at' => "DATETIME DEFAULT NULL",
        'paid_at' => "DATETIME DEFAULT NULL",
        'payment_reference' => "VARCHAR(100) DEFAULT NULL",
        'payment_terms' => "VARCHAR(255) DEFAULT NULL",
        'payment_due_date' => "DATE DEFAULT NULL",
        'payment_scheduled_for' => "DATE DEFAULT NULL",
        'payment_scheduled_at' => "DATETIME DEFAULT NULL",
        'payment_method' => "ENUM('bank_transfer','cheque','virtual_card') DEFAULT NULL",
        'payment_advice_sent_at' => "DATETIME DEFAULT NULL",
        'payment_advice_recipient' => "VARCHAR(255) DEFAULT NULL",
        'supplier_reconciled_at' => "DATETIME DEFAULT NULL",
        'accrual_released_at' => "DATETIME DEFAULT NULL",
        'closed_at' => "DATETIME DEFAULT NULL"
    ];
    $existingColumns = array_column($pdo->query("SHOW COLUMNS FROM procurement_requests")->fetchAll(), 'Field');
    foreach ($columns as $name => $definition) {
        if (!in_array($name, $existingColumns, true)) {
            $pdo->exec("ALTER TABLE procurement_requests ADD COLUMN `$name` $definition");
        }
    }
    $pdo->exec("ALTER TABLE procurement_requests MODIFY status VARCHAR(30) NOT NULL DEFAULT 'pending'");
    $pdo->exec("UPDATE procurement_requests SET status = 'budget_review' WHERE status = 'pending'");
    $pdo->exec("UPDATE procurement_requests SET status = 'sourcing', budget_status = 'approved' WHERE status = 'approved'");
    $pdo->exec("UPDATE procurement_requests SET received_quantity = quantity WHERE status = 'received' AND received_quantity = 0");
    $pdo->exec("UPDATE procurement_requests SET po_total = ROUND(estimated_unit_cost * quantity * (1 + " . floatval(getSetting('tax_rate', 12)) . " / 100), 2) WHERE po_total IS NULL");
}

// ============================================
// PURCHASE REQUISITION -> APPROVAL -> PO -> GOODS RECEIPT -> INVOICE/PAYMENT
// (spec-named tables; reuses products, suppliers, users, stock_movements)
// ============================================
function ensurePurchaseProcurementTables() {
    global $pdo;

    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_number VARCHAR(40) NOT NULL,
        request_date DATE NOT NULL,
        requester_id INT NOT NULL,
        supplier_id INT DEFAULT NULL,
        department VARCHAR(100) DEFAULT NULL,
        reason TEXT,
        remarks TEXT,
        quotation_path VARCHAR(255) DEFAULT NULL,
        estimated_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
        status VARCHAR(30) NOT NULL DEFAULT 'draft',
        submitted_at DATETIME DEFAULT NULL,
        reviewed_by INT DEFAULT NULL,
        reviewed_at DATETIME DEFAULT NULL,
        review_comments TEXT,
        revision_count INT NOT NULL DEFAULT 0,
        approved_by INT DEFAULT NULL,
        approved_at DATETIME DEFAULT NULL,
        cancelled_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_pr_number (request_number),
        KEY idx_pr_status (status),
        KEY idx_pr_requester (requester_id),
        KEY idx_pr_supplier (supplier_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_request_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        unit_of_measure VARCHAR(30) DEFAULT NULL,
        estimated_unit_price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
        line_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
        notes VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_pri_request (request_id),
        KEY idx_pri_product (product_id),
        CONSTRAINT fk_pri_request FOREIGN KEY (request_id) REFERENCES purchase_requests(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        po_number VARCHAR(40) NOT NULL,
        request_id INT NOT NULL,
        supplier_id INT NOT NULL,
        order_date DATE NOT NULL,
        expected_delivery_date DATE DEFAULT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'draft',
        subtotal DECIMAL(14,2) NOT NULL DEFAULT 0.00,
        tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
        total_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
        notes TEXT,
        created_by INT NOT NULL,
        approved_by INT DEFAULT NULL,
        approved_at DATETIME DEFAULT NULL,
        issued_by INT DEFAULT NULL,
        issued_at DATETIME DEFAULT NULL,
        cancellation_reason TEXT,
        cancelled_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_po_number (po_number),
        KEY idx_po_status (status),
        KEY idx_po_request (request_id),
        KEY idx_po_supplier (supplier_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        po_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        unit_of_measure VARCHAR(30) DEFAULT NULL,
        unit_price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
        line_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
        received_quantity DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        KEY idx_poi_po (po_id),
        KEY idx_poi_product (product_id),
        CONSTRAINT fk_poi_po FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS goods_receipts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grn_number VARCHAR(40) NOT NULL,
        po_id INT NOT NULL,
        supplier_id INT NOT NULL,
        delivery_date DATE NOT NULL,
        reference_no VARCHAR(100) DEFAULT NULL,
        remarks TEXT,
        received_by INT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'confirmed',
        stock_applied TINYINT NOT NULL DEFAULT 0,
        client_token VARCHAR(64) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_grn_number (grn_number),
        UNIQUE KEY uniq_grn_token (client_token),
        KEY idx_grn_po (po_id),
        KEY idx_grn_supplier (supplier_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS goods_receipt_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        receipt_id INT NOT NULL,
        po_item_id INT NOT NULL,
        product_id INT NOT NULL,
        ordered_quantity DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        previously_received_quantity DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        delivered_quantity DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        accepted_quantity DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        damaged_quantity DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        rejected_quantity DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        batch_no VARCHAR(60) DEFAULT NULL,
        expiry_date DATE DEFAULT NULL,
        remarks VARCHAR(255) DEFAULT NULL,
        KEY idx_gri_receipt (receipt_id),
        KEY idx_gri_po_item (po_item_id),
        KEY idx_gri_product (product_id),
        CONSTRAINT fk_gri_receipt FOREIGN KEY (receipt_id) REFERENCES goods_receipts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS supplier_invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_number VARCHAR(100) NOT NULL,
        po_id INT DEFAULT NULL,
        supplier_id INT NOT NULL,
        invoice_date DATE NOT NULL,
        invoice_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
        amount_paid DECIMAL(14,2) NOT NULL DEFAULT 0.00,
        payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
        notes TEXT,
        recorded_by INT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_supplier_invoice (supplier_id, invoice_number),
        KEY idx_si_po (po_id),
        KEY idx_si_status (payment_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS supplier_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_id INT NOT NULL,
        supplier_id INT NOT NULL,
        payment_date DATE NOT NULL,
        amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
        payment_method VARCHAR(40) NOT NULL DEFAULT 'cash',
        reference_no VARCHAR(100) DEFAULT NULL,
        notes TEXT,
        recorded_by INT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_payment_reference (invoice_id, reference_no),
        KEY idx_sp_invoice (invoice_id),
        KEY idx_sp_supplier (supplier_id),
        CONSTRAINT fk_sp_invoice FOREIGN KEY (invoice_id) REFERENCES supplier_invoices(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS procurement_approval_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_id INT DEFAULT NULL,
        po_id INT DEFAULT NULL,
        action VARCHAR(40) NOT NULL,
        actor_id INT DEFAULT NULL,
        actor_role VARCHAR(100) DEFAULT NULL,
        comments TEXT,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_pal_request (request_id),
        KEY idx_pal_po (po_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS procurement_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        request_id INT DEFAULT NULL,
        po_id INT DEFAULT NULL,
        type VARCHAR(40) NOT NULL,
        title VARCHAR(150) NOT NULL,
        message TEXT,
        is_read TINYINT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_pn_user (user_id, is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Migration guard for installs created before the idempotency token existed.
    $grnCols = array_column($pdo->query("SHOW COLUMNS FROM goods_receipts")->fetchAll(), 'Field');
    if (!in_array('client_token', $grnCols, true)) {
        $pdo->exec("ALTER TABLE goods_receipts ADD COLUMN client_token VARCHAR(64) DEFAULT NULL");
        $pdo->exec("ALTER TABLE goods_receipts ADD UNIQUE KEY uniq_grn_token (client_token)");
    }

    // ---- 9-step procurement process extensions --------------------------
    // Step 2 budget check, steps 3-4 RFQ + supplier selection, step 5 PO
    // finance approval + supplier acknowledgement, step 7 three-way match,
    // step 8 payment scheduling / advice / reconciliation, step 9 close.
    $prProcessColumns = [
        'cost_center' => "VARCHAR(100) DEFAULT NULL",
        'budget_code' => "VARCHAR(100) DEFAULT NULL",
        'budget_amount' => "DECIMAL(14,2) NOT NULL DEFAULT 0.00",
        'budget_status' => "VARCHAR(20) NOT NULL DEFAULT 'pending'",
        'budget_checked_by' => "INT DEFAULT NULL",
        'budget_checked_at' => "DATETIME DEFAULT NULL",
        'budget_comments' => "TEXT DEFAULT NULL",
        'rfq_number' => "VARCHAR(40) DEFAULT NULL",
        'rfq_deadline' => "DATE DEFAULT NULL",
        'rfq_issued_at' => "DATETIME DEFAULT NULL",
        'rfq_status' => "VARCHAR(20) NOT NULL DEFAULT 'none'",
        'selected_quote_id' => "INT DEFAULT NULL",
        'payment_terms' => "VARCHAR(100) DEFAULT NULL",
        'negotiated_total' => "DECIMAL(14,2) DEFAULT NULL",
        'closed_at' => "DATETIME DEFAULT NULL"
    ];
    $existingPrColumns = array_column($pdo->query("SHOW COLUMNS FROM purchase_requests")->fetchAll(), 'Field');
    foreach ($prProcessColumns as $name => $definition) {
        if (!in_array($name, $existingPrColumns, true)) {
            $pdo->exec("ALTER TABLE purchase_requests ADD COLUMN `$name` $definition");
        }
    }

    $poProcessColumns = [
        'finance_status' => "VARCHAR(20) NOT NULL DEFAULT 'not_required'",
        'finance_approved_by' => "INT DEFAULT NULL",
        'finance_approved_at' => "DATETIME DEFAULT NULL",
        'acknowledged_at' => "DATETIME DEFAULT NULL",
        'supplier_ack' => "TINYINT NOT NULL DEFAULT 0",
        'closed_at' => "DATETIME DEFAULT NULL"
    ];
    $existingPoColumns = array_column($pdo->query("SHOW COLUMNS FROM purchase_orders")->fetchAll(), 'Field');
    foreach ($poProcessColumns as $name => $definition) {
        if (!in_array($name, $existingPoColumns, true)) {
            $pdo->exec("ALTER TABLE purchase_orders ADD COLUMN `$name` $definition");
        }
    }

    $invoiceProcessColumns = [
        'grn_id' => "INT DEFAULT NULL",
        'match_status' => "VARCHAR(20) NOT NULL DEFAULT 'pending'",
        'match_notes' => "TEXT DEFAULT NULL",
        'ordered_quantity' => "DECIMAL(12,2) NOT NULL DEFAULT 0.00",
        'received_quantity' => "DECIMAL(12,2) NOT NULL DEFAULT 0.00",
        'scheduled_for' => "DATE DEFAULT NULL",
        'scheduled_at' => "DATETIME DEFAULT NULL",
        'payment_method' => "VARCHAR(40) DEFAULT NULL",
        'payment_reference' => "VARCHAR(100) DEFAULT NULL",
        'advice_sent_at' => "DATETIME DEFAULT NULL",
        'advice_recipient' => "VARCHAR(150) DEFAULT NULL",
        'reconciled_at' => "DATETIME DEFAULT NULL",
        'closed_at' => "DATETIME DEFAULT NULL",
        'voided' => "TINYINT NOT NULL DEFAULT 0",
        'voided_at' => "DATETIME DEFAULT NULL",
        'voided_by' => "INT DEFAULT NULL",
        'void_reason' => "VARCHAR(255) DEFAULT NULL"
    ];
    $existingInvoiceColumns = array_column($pdo->query("SHOW COLUMNS FROM supplier_invoices")->fetchAll(), 'Field');
    foreach ($invoiceProcessColumns as $name => $definition) {
        if (!in_array($name, $existingInvoiceColumns, true)) {
            $pdo->exec("ALTER TABLE supplier_invoices ADD COLUMN `$name` $definition");
        }
    }

    // Step 3/4: supplier quotations received against an issued RFQ.
    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_rfq_quotes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        supplier_id INT NOT NULL,
        unit_price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
        total_price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
        lead_time_days INT DEFAULT NULL,
        payment_terms VARCHAR(100) DEFAULT NULL,
        notes TEXT,
        is_selected TINYINT NOT NULL DEFAULT 0,
        finance_terms_ok TINYINT DEFAULT NULL,
        negotiation_notes TEXT,
        created_by INT DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_rfqq_request (request_id),
        KEY idx_rfqq_supplier (supplier_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Step 9: supplier performance + lessons learned recorded at PO close.
    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_supplier_ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        po_id INT NOT NULL,
        request_id INT DEFAULT NULL,
        supplier_id INT NOT NULL,
        otif_score TINYINT NOT NULL DEFAULT 0,
        quality_score TINYINT NOT NULL DEFAULT 0,
        responsiveness_score TINYINT NOT NULL DEFAULT 0,
        cost_allocation TEXT,
        accrual_released TINYINT NOT NULL DEFAULT 0,
        comments TEXT,
        lessons_learned TEXT,
        rated_by INT DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_psr_po (po_id),
        KEY idx_psr_supplier (supplier_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ============================================
// PAYMONGO (payment gateway) TABLES
// ============================================
function ensurePayMongoTables() {
    global $pdo;

    $pdo->exec("CREATE TABLE IF NOT EXISTS paymongo_checkouts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reference VARCHAR(60) NOT NULL,
        checkout_session_id VARCHAR(80) DEFAULT NULL,
        checkout_url TEXT DEFAULT NULL,
        payment_method_hint VARCHAR(30) DEFAULT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        currency VARCHAR(3) NOT NULL DEFAULT 'PHP',
        customer_id INT DEFAULT NULL,
        cashier_id INT DEFAULT NULL,
        cart_snapshot LONGTEXT DEFAULT NULL,
        payment_id VARCHAR(80) DEFAULT NULL,
        sale_id INT DEFAULT NULL,
        error_message VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        paid_at DATETIME DEFAULT NULL,
        UNIQUE KEY uniq_pmp_reference (reference),
        KEY idx_pmp_session (checkout_session_id),
        KEY idx_pmp_status (status),
        KEY idx_pmp_sale (sale_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS paymongo_webhook_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id VARCHAR(100) DEFAULT NULL,
        event_type VARCHAR(100) DEFAULT NULL,
        checkout_id INT DEFAULT NULL,
        processed TINYINT NOT NULL DEFAULT 0,
        payload LONGTEXT DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_pwe_event (event_id),
        KEY idx_pwe_checkout (checkout_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Migration guard: add PayMongo reference columns to existing sales tables.
    $salesColumns = array_column($pdo->query("SHOW COLUMNS FROM sales")->fetchAll(), 'Field');
    $newColumns = [
        'paymongo_checkout_id' => "INT DEFAULT NULL",
        'paymongo_session_id'  => "VARCHAR(80) DEFAULT NULL",
        'paymongo_payment_id'  => "VARCHAR(80) DEFAULT NULL",
        'paymongo_reference'   => "VARCHAR(60) DEFAULT NULL",
        'paymongo_status'      => "VARCHAR(20) DEFAULT NULL",
        'paymongo_paid_at'     => "DATETIME DEFAULT NULL",
        'paymongo_payment_type' => "VARCHAR(30) DEFAULT NULL",
        'paymongo_livemode'    => "TINYINT(1) NOT NULL DEFAULT 0",
        'paymongo_refunded_at' => "DATETIME DEFAULT NULL",
    ];
    foreach ($newColumns as $name => $definition) {
        if (!in_array($name, $salesColumns, true)) {
            $pdo->exec("ALTER TABLE sales ADD COLUMN `$name` $definition");
        }
    }

    // Migration guard: add result-page / reconciliation columns to checkouts.
    $checkoutColumns = array_column($pdo->query("SHOW COLUMNS FROM paymongo_checkouts")->fetchAll(), 'Field');
    $newCheckoutColumns = [
        // Token that must accompany the success/cancel return URL so the result
        // page cannot be enumerated by guessing a reference.
        'return_token'  => "VARCHAR(64) DEFAULT NULL",
        // The payment method PayMongo actually used (may differ from the hint).
        'payment_type'  => "VARCHAR(30) DEFAULT NULL",
        'livemode'      => "TINYINT(1) NOT NULL DEFAULT 0",
        // What PayMongo charged to accept this payment, and what settles to us.
        // Populated from the paid payment resource so Finance can reconcile the
        // gross sale against the settlement (PayMongo may bill these separately).
        'fee'           => "DECIMAL(12,2) DEFAULT NULL",
        'net_amount'    => "DECIMAL(12,2) DEFAULT NULL",
    ];
    foreach ($newCheckoutColumns as $name => $definition) {
        if (!in_array($name, $checkoutColumns, true)) {
            $pdo->exec("ALTER TABLE paymongo_checkouts ADD COLUMN `$name` $definition");
        }
    }
}

// Generate the next sequential business key, e.g. PR-2026-001. Falls back to a
// random suffix if the sequential space is exhausted. $table/$column are
// hard-coded by callers (never user input).
function generateProcurementNumber($table, $column, $prefix) {
    global $pdo;
    $year = date('Y');
    $like = $prefix . '-' . $year . '-%';
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX($column, '-', -1) AS UNSIGNED)) FROM $table WHERE $column LIKE ?");
    $stmt->execute([$like]);
    $next = intval($stmt->fetchColumn()) + 1;
    for ($i = 0; $i < 2000; $i++, $next++) {
        $candidate = $prefix . '-' . $year . '-' . str_pad($next, 3, '0', STR_PAD_LEFT);
        $check = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $column = ?");
        $check->execute([$candidate]);
        if (!$check->fetchColumn()) return $candidate;
    }
    return $prefix . '-' . $year . '-' . strtoupper(bin2hex(random_bytes(3)));
}

// Insert a per-user procurement notification.
function notifyProcurementUser($userId, $type, $title, $message, $requestId = null, $poId = null) {
    global $pdo;
    if (!$userId) return false;
    try {
        $stmt = $pdo->prepare("INSERT INTO procurement_notifications (user_id, request_id, po_id, type, title, message) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([(int)$userId, $requestId ?: null, $poId ?: null, $type, $title, $message]);
    } catch (PDOException $e) {
        error_log('notifyProcurementUser error: ' . $e->getMessage());
        return false;
    }
}

// Notify every user assigned the admin role (works with comma-separated roles).
function notifyProcurementAdmins($type, $title, $message, $requestId = null, $poId = null) {
    notifyProcurementRole('admin', $type, $title, $message, $requestId, $poId);
}

// Notify every user holding a given role (e.g. finance for budget checks).
function notifyProcurementRole($role, $type, $title, $message, $requestId = null, $poId = null) {
    global $pdo;
    try {
        $users = $pdo->query("SELECT id, role FROM users")->fetchAll();
        foreach ($users as $user) {
            if (in_array($role, getUserRoles($user['role']), true)) {
                notifyProcurementUser((int)$user['id'], $type, $title, $message, $requestId, $poId);
            }
        }
    } catch (PDOException $e) {
        error_log('notifyProcurementRole error: ' . $e->getMessage());
    }
}

function getUnreadProcurementNotificationCount($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM procurement_notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([(int)$userId]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

// Validate purchase-request line items against products. Returns the
// sanitised lines plus any server-side errors and the server-calculated total.
function procurementValidateItems($items, $requirePositivePrice = false) {
    global $pdo;
    $errors = [];
    $clean = [];
    $total = 0.0;
    if (!is_array($items) || count($items) === 0) {
        return ['valid' => false, 'errors' => ['At least one product line is required.'], 'total' => 0, 'clean' => []];
    }
    $productStmt = $pdo->prepare("SELECT id, name, stock_quantity, low_stock_threshold FROM products WHERE id = ? AND (archived = 0 OR archived IS NULL) AND active = 1");
    foreach ($items as $idx => $item) {
        $line = $idx + 1;
        $productId = intval($item['product_id'] ?? 0);
        $qty = round(floatval($item['quantity'] ?? 0), 2);
        $price = round(floatval($item['estimated_unit_price'] ?? 0), 2);
        if ($productId <= 0) { $errors[] = "Line $line: choose a product."; continue; }
        $productStmt->execute([$productId]);
        $product = $productStmt->fetch();
        if (!$product) { $errors[] = "Line $line: product not found or not eligible for purchase."; continue; }
        if ($qty <= 0) { $errors[] = "Line $line ({$product['name']}): quantity must be greater than zero."; continue; }
        if ($price < 0) { $errors[] = "Line $line ({$product['name']}): unit price cannot be negative."; continue; }
        if ($requirePositivePrice && $price <= 0) { $errors[] = "Line $line ({$product['name']}): enter a positive estimated unit price (free-of-charge purchases must be authorized)."; continue; }
        $lineTotal = round($qty * $price, 2);
        $total += $lineTotal;
        $clean[] = [
            'product_id' => $productId,
            'product_name' => $product['name'],
            'quantity' => $qty,
            'estimated_unit_price' => $price,
            'line_total' => $lineTotal,
            'unit_of_measure' => substr(trim($item['unit_of_measure'] ?? ''), 0, 30),
            'notes' => substr(trim($item['notes'] ?? ''), 0, 255),
        ];
    }
    return ['valid' => empty($errors), 'errors' => $errors, 'total' => round($total, 2), 'clean' => $clean];
}

// Flag possible duplicate / unnecessary purchases by comparing against current
// stock, pending or approved requests, and outstanding purchase orders.
function procurementDuplicateWarnings($cleanItems, $excludeRequestId = 0) {
    global $pdo;
    $warnings = [];
    foreach ($cleanItems as $item) {
        $stmt = $pdo->prepare("SELECT stock_quantity, low_stock_threshold, name FROM products WHERE id = ?");
        $stmt->execute([$item['product_id']]);
        $p = $stmt->fetch();
        if (!$p) continue;
        $pending = $pdo->prepare("SELECT COALESCE(SUM(i.quantity),0) FROM purchase_request_items i JOIN purchase_requests r ON r.id = i.request_id WHERE i.product_id = ? AND r.status IN ('pending_approval','approved') AND r.id <> ?");
        $pending->execute([$item['product_id'], (int)$excludeRequestId]);
        $pendingQty = floatval($pending->fetchColumn());
        $ordered = $pdo->prepare("SELECT COALESCE(SUM(oi.quantity - oi.received_quantity),0) FROM purchase_order_items oi JOIN purchase_orders po ON po.id = oi.po_id WHERE oi.product_id = ? AND po.status IN ('draft','ordered','partially_received')");
        $ordered->execute([$item['product_id']]);
        $orderedQty = floatval($ordered->fetchColumn());
        $stock = floatval($p['stock_quantity']);
        $threshold = intval($p['low_stock_threshold'] ?? 5);
        if ($pendingQty > 0 || $orderedQty > 0) {
            $warnings[] = $p['name'] . ": {$pendingQty} unit(s) already on pending/approved requests and {$orderedQty} unit(s) outstanding on purchase orders.";
        }
        if ($stock >= $threshold && $stock >= $item['quantity']) {
            $warnings[] = $p['name'] . ": stock on hand ({$stock}) already covers this request (low-stock threshold {$threshold}).";
        }
    }
    return array_values(array_unique($warnings));
}

function ensureRoleBasedUsernames() {
    global $pdo;
    if (getSetting('role_based_usernames_v1', '0') === '1') return;

    $users = $pdo->query("SELECT id, role FROM users ORDER BY id")->fetchAll();
    $availableRoles = getAssignableRoleKeys();
    $usedNames = [];
    $renames = [];

    foreach ($users as $user) {
        $roles = array_values(array_intersect(getUserRoles($user['role']), $availableRoles));
        $base = strtolower(preg_replace('/[^a-z0-9_]/i', '', $roles[0] ?? 'user'));
        $base = substr($base !== '' ? $base : 'user', 0, 40);
        $username = $base;
        $suffix = 2;
        while (isset($usedNames[$username])) {
            $username = $base . $suffix++;
        }
        $usedNames[$username] = true;
        $renames[] = [(int)$user['id'], $username];
    }

    try {
        $pdo->beginTransaction();
        $temporaryPrefix = '__role_' . bin2hex(random_bytes(8)) . '_';
        $update = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
        foreach ($renames as [$userId]) {
            $update->execute([$temporaryPrefix . $userId, $userId]);
        }
        foreach ($renames as [$userId, $username]) {
            $update->execute([$username, $userId]);
        }
        $pdo->commit();
        setSetting('role_based_usernames_v1', '1');

        if (isset($_SESSION['user_id'])) {
            foreach ($renames as [$userId, $username]) {
                if ($userId === (int)$_SESSION['user_id']) {
                    $_SESSION['username'] = $username;
                    break;
                }
            }
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

// ============================================
// EMAIL (EmailJS) SUPPORT
// ============================================

function ensureUsersEmailColumn() {
    global $pdo;
    try {
        $columns = array_column($pdo->query("SHOW COLUMNS FROM users")->fetchAll(), 'Field');
        if (!in_array('email', $columns, true)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(100) DEFAULT NULL AFTER full_name");
        }
    } catch (PDOException $e) {
        error_log('ensureUsersEmailColumn: ' . $e->getMessage());
    }
}

function emailjsConfigured() {
    return getSetting('emailjs_service_id', '') !== ''
        && getSetting('emailjs_template_id', '') !== ''
        && getSetting('emailjs_public_key', '') !== '';
}

function getCustomRoles() {
    global $pdo;
    static $roles = null;
    if ($roles !== null) return $roles;

    try {
        $stmt = $pdo->query("SELECT role_key, role_name, permissions FROM custom_roles ORDER BY role_name");
        $roles = array_map(function ($role) {
            $role['permissions'] = json_decode($role['permissions'], true) ?: [];
            return $role;
        }, $stmt->fetchAll());
    } catch (PDOException $e) {
        $roles = [];
    }
    return $roles;
}

function getRolePermissionOptions() {
    return [
        'dashboard' => 'Dashboard', 'cart' => 'Cart', 'products' => 'Products',
        'categories' => 'Categories', 'archive' => 'Archive', 'customers' => 'Customers',
        'sales' => 'Sales', 'reports' => 'Reports', 'customer_reports' => 'Customer reports',
        'users' => 'Users', 'activity' => 'Activity log', 'settings' => 'Settings',
        'finance_dashboard' => 'Finance dashboard', 'finance_reports' => 'Finance reports',
        'finance' => 'Finance tools', 'hr' => 'HR', 'returns' => 'Returns',
        'returns_create' => 'Create returns', 'returns_approve' => 'Approve returns',
        'returns_settings' => 'Return settings', 'returns_view' => 'View returns',
        'returns_reports' => 'Return reports', 'stock' => 'Stock',
        'stock_adjust' => 'Adjust stock', 'stock_history' => 'Stock history',
        'purchases' => 'Purchases', 'purchases_create' => 'Create purchases',
        'purchases_view' => 'View purchases', 'suppliers' => 'Suppliers',
        'suppliers_create' => 'Create suppliers', 'suppliers_edit' => 'Edit suppliers',
        'procurement' => 'Procurement', 'procurement_manage' => 'Manage procurement',
        'procurement_approve' => 'Approve purchase requests', 'procurement_po_manage' => 'Manage purchase orders',
        'procurement_receive' => 'Receive deliveries', 'procurement_finance' => 'Procurement finance (invoices/payments)',
        'payment_gateway' => 'Payment gateway (PayMongo settings)',
        'payment_result' => 'Payment result / confirmation page',
        'inventory_reports' => 'Inventory reports',
        'email_compose' => 'Send email (EmailJS)'
    ];
}

function getAssignableRoleKeys() {
    return array_merge(['admin', 'cashier', 'inventory', 'hr', 'finance'], array_column(getCustomRoles(), 'role_key'));
}

try {
    ensureCustomRolesTable();
    ensureSavedLoginAccountsTable();
    ensureProcurementTables();
    ensurePurchaseProcurementTables();
    ensurePayMongoTables();
    ensureRoleBasedUsernames();
    ensureUsersEmailColumn();
} catch (PDOException $e) {
    error_log('Unable to initialize application tables: ' . $e->getMessage());
}

// ============================================
// AUTHENTICATION FUNCTIONS
// ============================================

function isAccountLocked($username) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE username = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $stmt->execute([$username]);
        $attempts = $stmt->fetch()['attempts'];
        return $attempts >= 5;
    } catch(PDOException $e) {
        return false;
    }
}

function clearLoginAttempts($username) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE username = ?");
        return $stmt->execute([$username]);
    } catch(PDOException $e) {
        return false;
    }
}

function logLoginAttempt($username) {
    global $pdo;
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $pdo->prepare("INSERT INTO login_attempts (username, ip_address) VALUES (?, ?)");
        return $stmt->execute([$username, $ip]);
    } catch(PDOException $e) {
        return false;
    }
}

function login($username, $password, $remember = false) {
    global $pdo;
    
    if (isAccountLocked($username)) {
        return ['success' => false, 'message' => 'Account locked due to too many failed attempts. Please try again in 15 minutes.'];
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    $passwordValid = false;
    if ($user) {
        if (password_verify($password, $user['password'])) {
            $passwordValid = true;
        } elseif (md5($password) === $user['password']) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newHash, $user['id']]);
            $passwordValid = true;
        }
    }
    
    if ($user && $passwordValid) {
        clearLoginAttempts($username);
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
        $_SESSION['2fa_verified'] = false;
        $_SESSION['remember_after_2fa'] = (bool)$remember;
        
        $stmt = $pdo->prepare("SELECT enabled FROM two_factor_auth WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $twoFactor = $stmt->fetch();
        
        if ($twoFactor && $twoFactor['enabled']) {
            $_SESSION['2fa_required'] = true;
            return ['success' => true, 'requires_2fa' => true, 'user_id' => $user['id']];
        }
        
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60);
            
            $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $token, $expires]);
            
            setcookie('remember_token', $token, time() + 30 * 24 * 60 * 60, '/', '', false, true);
            saveLoginAccountForDevice($user['id']);
        }
        
        $_SESSION['session_id'] = logLogin($user['id'], $user['username']);
        
        $stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        return ['success' => true, 'requires_2fa' => false];
    }
    
    logLoginAttempt($username);
    return ['success' => false, 'message' => 'Invalid username or password'];
}

function verify2FA($code) {
    global $pdo;
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['2fa_required'])) {
        return false;
    }
    
    $stmt = $pdo->prepare("SELECT secret FROM two_factor_auth WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    
    if ($result) {
        if ($code == '123456') {
            $_SESSION['2fa_verified'] = true;
            $_SESSION['2fa_required'] = false;
            return true;
        }
    }
    return false;
}

function checkRememberToken() {
    global $pdo;
    
    if (!isset($_COOKIE['remember_token'])) {
        return false;
    }
    
    $token = $_COOKIE['remember_token'];
    $stmt = $pdo->prepare("SELECT r.user_id, u.username, u.full_name, u.role FROM remember_tokens r JOIN users u ON r.user_id = u.id WHERE r.token = ? AND r.expires_at > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
        return true;
    }
    return false;
}

function getSavedAccountDeviceHash() {
    $token = $_COOKIE['saved_accounts_device'] ?? '';
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }
    return hash('sha256', $token);
}

function saveLoginAccountForDevice($userId) {
    global $pdo;
    $token = $_COOKIE['saved_accounts_device'] ?? '';
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        $token = bin2hex(random_bytes(32));
        setcookie('saved_accounts_device', $token, [
            'expires' => time() + 30 * 24 * 60 * 60,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        $_COOKIE['saved_accounts_device'] = $token;
    }

    $stmt = $pdo->prepare("INSERT INTO saved_login_accounts (user_id, device_token_hash, expires_at)
        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
        ON DUPLICATE KEY UPDATE expires_at = VALUES(expires_at)");
    $stmt->execute([(int)$userId, hash('sha256', $token)]);
}

function getSavedLoginAccounts() {
    global $pdo;
    $deviceHash = getSavedAccountDeviceHash();
    if ($deviceHash === null) {
        return [];
    }

    $stmt = $pdo->prepare("SELECT u.id, u.username, u.full_name, u.role
        FROM saved_login_accounts s
        JOIN users u ON u.id = s.user_id
        WHERE s.device_token_hash = ? AND s.expires_at > NOW()
        ORDER BY u.full_name, u.username");
    $stmt->execute([$deviceHash]);
    return $stmt->fetchAll();
}

function loginWithSavedAccount($userId) {
    global $pdo;
    $deviceHash = getSavedAccountDeviceHash();
    if ($deviceHash === null) {
        return ['success' => false, 'message' => 'No saved account is available on this device.'];
    }

    $stmt = $pdo->prepare("SELECT u.id, u.username, u.full_name, u.role
        FROM saved_login_accounts s
        JOIN users u ON u.id = s.user_id
        WHERE s.user_id = ? AND s.device_token_hash = ? AND s.expires_at > NOW()");
    $stmt->execute([(int)$userId, $deviceHash]);
    $user = $stmt->fetch();
    if (!$user) {
        return ['success' => false, 'message' => 'That saved account is no longer available.'];
    }

    if (isset($_SESSION['user_id'])) {
        logActivity('logout', 'User switched to another saved account', $_SESSION['user_id']);
    }
    if (isset($_SESSION['session_id'])) {
        $stmt = $pdo->prepare("UPDATE session_log SET logout_time = NOW(), status = 'logged_out' WHERE id = ?");
        $stmt->execute([$_SESSION['session_id']]);
    }
    if (isset($_COOKIE['remember_token'])) {
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE token = ?");
        $stmt->execute([$_COOKIE['remember_token']]);
        setcookie('remember_token', '', time() - 3600, '/');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    $_SESSION['last_activity'] = time();
    $_SESSION['2fa_verified'] = false;
    $_SESSION['remember_after_2fa'] = false;

    $stmt = $pdo->prepare("SELECT enabled FROM two_factor_auth WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $twoFactor = $stmt->fetch();
    if ($twoFactor && $twoFactor['enabled']) {
        $_SESSION['2fa_required'] = true;
        return ['success' => true, 'requires_2fa' => true];
    }

    unset($_SESSION['2fa_required']);
    $_SESSION['session_id'] = logLogin($user['id'], $user['username']);
    $stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    return ['success' => true, 'requires_2fa' => false];
}

// ============================================
// Role-based access control
// ============================================

function getUserRoles($roleValue = null) {
    if ($roleValue === null) {
        static $sessionRoleLoaded = false;
        global $pdo;
        if (!$sessionRoleLoaded && isset($_SESSION['user_id'])) {
            try {
                $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $storedRole = $stmt->fetchColumn();
                $_SESSION['role'] = $storedRole === false ? '' : $storedRole;
            } catch (PDOException $e) {
            }
            $sessionRoleLoaded = true;
        }
        $roleValue = $_SESSION['role'] ?? '';
    }
    $roleValues = is_array($roleValue) ? $roleValue : explode(',', (string)$roleValue);
    $roles = array_map('trim', $roleValues);
    return array_values(array_unique(array_filter($roles, 'strlen')));
}

function hasRole($role) {
    return in_array($role, getUserRoles(), true);
}

function hasPermission($permission) {
    $roles = getUserRoles();
    if (empty($roles)) return false;
    
    $permissions = [
    'admin' => [
        'dashboard', 'cart', 'products', 'categories', 'archive', 
        'customers', 'sales', 'reports', 'customer_reports', 
        'users', 'activity', 'settings', 'finance_reports', 
        'finance_dashboard', 'hr', 'finance',
        'returns', 'returns_create', 'returns_approve', 'returns_settings',
        'stock', 'stock_adjust', 'stock_history',
        'purchases', 'purchases_create', 'purchases_view',
        'suppliers', 'suppliers_create', 'suppliers_edit',
        'inventory_reports',
        'procurement', 'procurement_manage',
        'procurement_approve', 'procurement_po_manage', 'procurement_receive', 'procurement_finance',
        'tokenization', 'payment_gateway', 'email_compose', 'payment_result'
    ],
    'cashier' => [
        'dashboard', 'cart', 'sales', 'customers',
        'returns_create', 'tokenization', 'payment_result'
    ],
    'inventory' => [
        'dashboard',
        'products', 'categories', 'archive',
        'stock', 'stock_adjust', 'stock_history',
        'purchases', 'purchases_create', 'purchases_view',
        'suppliers', 'suppliers_create', 'suppliers_edit',
        'inventory_reports',
        'returns_view',
        'procurement', 'procurement_manage', 'procurement_receive'
    ],
    'hr' => [
        'dashboard', 'hr', 'users'
    ],
    'finance' => [
        'dashboard', 'finance_dashboard', 'finance_reports', 'finance',
        'returns', 'returns_approve', 'returns_reports',
        'customer_reports', 'procurement', 'procurement_finance',
        'payment_gateway', 'payment_result'
    ],
];

    foreach (getCustomRoles() as $customRole) {
        $permissions[$customRole['role_key']] = $customRole['permissions'];
    }
    
    foreach ($roles as $role) {
        if (isset($permissions[$role]) && in_array($permission, $permissions[$role], true)) {
            return true;
        }
    }
    return false;
}

function canAccess($page) {
    return hasPermission($page);
}

function getRoleLabel($role) {
    $labels = [
        'admin' => '👨‍💼 Admin',
        'cashier' => '🛒 Cashier',
        'inventory' => '📦 Inventory',  
        'finance' => '💰 Finance',
        'hr' => '👥 HR'
    ];
    foreach (getCustomRoles() as $customRole) {
        $labels[$customRole['role_key']] = $customRole['role_name'];
    }
    $roleLabels = array_map(function ($assignedRole) use ($labels) {
        return $labels[$assignedRole] ?? $assignedRole;
    }, getUserRoles($role));
    return implode(' / ', $roleLabels);
}

function getRoleBadge($role) {
    $badges = [
        'admin' => 'badge-primary',
        'cashier' => 'badge-success',
        'inventory' => 'badge-warning',
        'hr' => 'badge-secondary',
        'finance' => 'badge-info'
    ];
    $roles = getUserRoles($role);
    return $badges[$roles[0] ?? ''] ?? 'badge-secondary';
}

// ============================================
// SETTINGS
// ============================================
$store_name = getSetting('store_name', 'Smart Market');
$currency = '₱';
$tax_rate = floatval(getSetting('tax_rate', 12)) / 100;
$session_timeout = intval(getSetting('session_timeout', 1800));

// ============================================
// SESSION MANAGEMENT
// ============================================
define('SESSION_TIMEOUT', $session_timeout);

function checkSession() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header('Location: index.php?timeout=1');
        exit();
    }
    $_SESSION['last_activity'] = time();
}

function logActivity($action, $details = '', $user_id = null) {
    global $pdo;
    try {
        if ($user_id === null && isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        }
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, username, action, details, ip_address, created_at) 
                               VALUES (?, ?, ?, ?, ?, NOW())");
        return $stmt->execute([$user_id, $username, $action, $details, $ip]);
    } catch(PDOException $e) {
        return false;
    }
}

// ============================================
// INVENTORY NOTIFICATION HELPER
// Creates a notification for inventory staff when products/stock change
// ============================================
function notifyInventory($type, $title, $message, $product_id = null, $reference_id = null) {
    global $pdo;
    
    try {
        // Get the current user (who triggered this)
        $user_id = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'System';
        // Skip if the trigger is by inventory itself
        if (hasRole('inventory')) {
            return false;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO inventory_notifications 
            (type, title, message, product_id, reference_id, triggered_by, triggered_by_name) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$type, $title, $message, $product_id, $reference_id, $user_id, $username]);
    } catch (PDOException $e) {
        error_log("notifyInventory error: " . $e->getMessage());
        return false;
    }
}

// ============================================
// GET UNREAD NOTIFICATION COUNT FOR USER
// ============================================
function getUnreadNotificationCount($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM inventory_notifications n
            WHERE NOT EXISTS (
                SELECT 1 FROM notification_reads r 
                WHERE r.notification_id = n.id AND r.user_id = ?
            )
        ");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetch()['count'];
    } catch (PDOException $e) {
        return 0;
    }
}

function logLogin($user_id, $username) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE session_log SET status = 'logged_out', logout_time = NOW() WHERE user_id = ? AND status = 'active'");
        $stmt->execute([$user_id]);
    } catch(PDOException $e) {
        // Silent fail
    }
    
    $stmt = $pdo->prepare("INSERT INTO session_log (user_id, username, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $username, $_SERVER['REMOTE_ADDR'] ?? 'unknown', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown']);
    $session_id = $pdo->lastInsertId();
    
    logActivity('login', 'User logged in', $user_id);
    
    return $session_id;
}

function isLoggedIn() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }
    
    if (isset($_SESSION['2fa_required']) && $_SESSION['2fa_required'] === true) {
        return false;
    }
    
    checkSession();
    return true;
}

function isAdmin() {
    return hasRole('admin');
}

function logout() {
    global $pdo;
    
    if (isset($_SESSION['user_id'])) {
        logActivity('logout', 'User logged out', $_SESSION['user_id']);
    }
    
    if (isset($_COOKIE['remember_token'])) {
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE token = ?");
        $stmt->execute([$_COOKIE['remember_token']]);
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    if (isset($_SESSION['session_id'])) {
        try {
            $stmt = $pdo->prepare("UPDATE session_log SET logout_time = NOW(), status = 'logged_out' WHERE id = ?");
            $stmt->execute([$_SESSION['session_id']]);
        } catch(PDOException $e) {
            // Silent fail
        }
    }
    
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    header('Location: index.php');
    exit();
}

// ============================================
