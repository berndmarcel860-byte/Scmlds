<?php
// ============================================================
// Configuration
// ============================================================
// IMPORTANT: Update DB credentials, SMTP settings, SITE_URL,
// and ADMIN_EMAIL before deploying to production.
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // Set a strong password in production
define('DB_NAME', 'scmlds');

define('BRAND_NAME',  'VerlustRück');
define('BRAND_FULL',  'VerlustRück – Kapitalrückholung');
define('BRAND_DOMAIN','verlustrueckholung.de');

define('SITE_NAME', 'VerlustRück – Experten für Kapitalrückforderung');
define('SITE_URL',  'https://verlustrueckholung.de'); // Use https:// in production

define('ADMIN_EMAIL', 'info@verlustrueckholung.de');
define('FROM_EMAIL',  'noreply@verlustrueckholung.de');
define('FROM_NAME',   'VerlustRück – Kapitalrückholung');

// ── SMTP Configuration (update before going live) ─────────
define('SMTP_HOST',     'smtp.verlustrueckholung.de'); // Your SMTP server
define('SMTP_PORT',     587);
define('SMTP_USER',     'noreply@verlustrueckholung.de');
define('SMTP_PASS',     '');          // Set in production
define('SMTP_SECURE',   'tls');       // 'tls' or 'ssl'
define('SMTP_DEBUG',    0);           // 0 = off, 2 = verbose
// ─────────────────────────────────────────────────────────

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
