<?php
/**
 * Email open-tracking pixel endpoint.
 * Called when a recipient loads the 1×1 tracking image in an email.
 * URL: /track_open.php?t={open_token}
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$token = trim($_GET['t'] ?? '');
if ($token && preg_match('/^[a-f0-9]{32}$/', $token)) {
    record_mailing_open($token);
}

// Return 1×1 transparent GIF
header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
