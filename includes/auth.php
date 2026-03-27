<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';

function admin_login(string $username, string $password): bool {
    $pdo = db_connect();
    $stmt = $pdo->prepare('SELECT id, password_hash FROM admin_users WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    $row = $stmt->fetch();
    if ($row && password_verify($password, $row['password_hash'])) {
        $_SESSION['admin_id'] = $row['id'];
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $username;
        // Update last login
        $upd = $pdo->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = :id');
        $upd->execute([':id' => $row['id']]);
        return true;
    }
    return false;
}

function admin_check(): void {
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: login.php');
        exit;
    }
}

function admin_logout(): void {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

function log_activity(string $action, string $details = ''): void {
    try {
        $pdo = db_connect();
        $stmt = $pdo->prepare(
            'INSERT INTO activity_logs (admin_id, action, details, ip_address) VALUES (:aid, :act, :det, :ip)'
        );
        $stmt->execute([
            ':aid' => $_SESSION['admin_id'] ?? null,
            ':act' => $action,
            ':det' => $details,
            ':ip'  => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
    } catch (Exception $e) {
        // Log the error without breaking functionality
        error_log('Activity log failed: ' . $e->getMessage());
    }
}
