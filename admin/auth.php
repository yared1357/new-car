<?php
// /admin/auth.php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = preg_replace('/\D+/', '', trim($_POST['phone'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->query("SELECT id, password, phone, force_password_change FROM users WHERE role IN ('admin', 'super_admin')");
    $users = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $candidate) {
        $candidatePhone = preg_replace('/\D+/', '', $candidate['phone'] ?? '');
        if ($phone !== '' && $candidatePhone === $phone) {
            $users[] = $candidate;
        }
    }

    if (count($users) === 1 && password_verify($password, $users[0]['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $users[0]['id'];
        header("Location: " . ((int) ($users[0]['force_password_change'] ?? 0) === 1 ? 'change_password.php?forced=1' : 'dashboard.php'));
        exit;
    } else {
        header("Location: login.php?error=1");
        exit;
    }
}

header('Location: login.php');
exit;
?>