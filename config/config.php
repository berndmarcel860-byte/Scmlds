<?php
// ============================================================
// Configuration
// ============================================================
// IMPORTANT: Update DB_PASS, SITE_URL, and ADMIN_EMAIL before deploying to production.
// Use HTTPS for SITE_URL in production environments.
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // Set a strong password in production
define('DB_NAME', 'scmlds');

define('SITE_NAME', 'Scmlds - Kapital-Rückforderungsexperten');
define('SITE_URL', 'http://localhost'); // Use https:// in production

define('ADMIN_EMAIL', 'admin@scmlds.de');

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
