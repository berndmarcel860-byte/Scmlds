<?php
/**
 * Stop Background Runner
 *
 * Sets auto_send_active = 0 for the campaign so the background runner loop exits.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Nicht authentifiziert.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$campaign_id = (int) ($_POST['campaign_id'] ?? 0);
if (!$campaign_id) {
    echo json_encode(['error' => 'Keine Kampagnen-ID.']);
    exit;
}

$pdo = db_connect();
$pdo->prepare('UPDATE mailing_campaigns SET auto_send_active=0 WHERE id=:id')
    ->execute([':id' => $campaign_id]);

echo json_encode(['stopped' => true, 'campaign_id' => $campaign_id]);
