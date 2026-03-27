<?php
/**
 * Save a single setting – AJAX endpoint
 * POST params: key, value
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Nicht autorisiert.']);
    exit;
}

// Whitelist allowed keys that can be saved via this endpoint
$allowed = ['meta_description', 'meta_keywords', 'page_title', 'meta_title'];

$key   = trim($_POST['key']   ?? '');
$value = trim($_POST['value'] ?? '');

if (!in_array($key, $allowed, true)) {
    echo json_encode(['ok' => false, 'error' => 'Unerlaubter Schlüssel.']);
    exit;
}

$ok = save_settings([$key => $value]);
log_activity('seo_ai_apply', "AI-generated $key saved");

echo json_encode(['ok' => $ok]);
