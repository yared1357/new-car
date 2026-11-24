<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/config.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, name, email, phone, role, force_password_change FROM users WHERE id = ? AND role IN ('admin', 'super_admin')");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

if ((int) ($admin['force_password_change'] ?? 0) === 1) {
    $currentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($currentPage !== 'change_password.php') {
        header('Location: change_password.php?forced=1');
        exit;
    }
}

$displayName = !empty($admin['name'])
    ? $admin['name']
    : ($admin['email'] ?: $admin['phone']);

require_once __DIR__ . '/../site_settings.php';
$siteSettings = get_site_settings($pdo);
$adminLogoPath = '../' . ltrim($siteSettings['logo_path'], '/');
