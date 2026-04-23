<?php
/**
 * Background Runner Status
 *
 * Returns the current campaign stats and auto_send_active flag
 * so the send.php UI can poll for live updates.
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

$campaign_id = (int) ($_GET['campaign_id'] ?? 0);
if (!$campaign_id) {
    echo json_encode(['error' => 'Keine Kampagnen-ID.']);
    exit;
}

$pdo = db_connect();
$stmt = $pdo->prepare('SELECT auto_send_active, status FROM mailing_campaigns WHERE id=:id');
$stmt->execute([':id' => $campaign_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['error' => 'Kampagne nicht gefunden.']);
    exit;
}

$stats = get_campaign_stats($campaign_id);
echo json_encode([
    'auto_send_active' => (int) $row['auto_send_active'],
    'campaign_status'  => $row['status'],
    'sent'             => $stats['sent'],
    'failed'           => $stats['failed'],
    'pending'          => $stats['pending'],
    'total'            => $stats['total'],
]);
