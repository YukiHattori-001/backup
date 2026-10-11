<?php
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/managers.php';
require_once __DIR__ . '/app/requests.php';

$roleEntryRoutes = [
	'admin.php' => ['role' => 'admin', 'page' => 'dashboard'],
	'hr.php' => ['role' => 'hr', 'page' => 'hr'],
	'inventory.php' => ['role' => 'inventory', 'page' => 'stock'],
	'finance.php' => ['role' => 'finance', 'page' => 'finance_dashboard']
];
$currentEntry = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
if (isset($roleEntryRoutes[$currentEntry])) {
	if (!isLoggedIn()) {
		$_SESSION['pending_role_entry'] = $currentEntry;
		header('Location: login.php');
		exit();
	}
	if (!hasRole($roleEntryRoutes[$currentEntry]['role'])) {
		unset($_SESSION['pending_role_entry']);
		header('Location: index.php');
		exit();
	}
	unset($_SESSION['pending_role_entry']);
	if (!isset($_GET['page'])) {
		$_GET['page'] = $roleEntryRoutes[$currentEntry]['page'];
	}
}

$isLoginEntry = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'login.php';
if ($isLoginEntry && isLoggedIn() && !isset($_GET['2fa'])) {
	header('Location: ' . getPostLoginRedirect());
	exit();
}
if (!$isLoginEntry && !isLoggedIn() && !isset($_GET['2fa'])) {
	$loginUrl = 'login.php' . (isset($_GET['timeout']) ? '?timeout=1' : '');
	header('Location: ' . $loginUrl);
	exit();
}

require __DIR__ . '/views/app.php';
