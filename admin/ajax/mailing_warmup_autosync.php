<?php
/**
 * AJAX Endpoint: mailing_warmup_autosync.php
 *
 * Automatically synchronises today's IP-warmup log entries for every SMTP
 * account that sent at least one email in the given campaign today.
 *
 * Called fire-and-forget from send.php after a batch or campaign completion.
 *
 * POST params:
 *   campaign_id  int  (required)
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthenticated']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$campaign_id = (int) ($_POST['campaign_id'] ?? 0);
if (!$campaign_id) {
    echo json_encode(['error' => 'No campaign_id']);
    exit;
}

ensure_warmup_table();
$pdo      = db_connect();
$settings = get_all_mailing_settings();
$today    = date('Y-m-d');
$synced = 0;

/*
 * For each SMTP account that sent emails in this campaign today, compute:
 *   sent    = total sent by this account in campaign (today)
 *   bounced = total bounced/failed recipients assigned to this account
 *   opened  = total opened by recipients assigned to this account
 *
 * Then upsert a warmup log row for today.
 */
$stmt = $pdo->prepare("
    SELECT
        r.smtp_account_id,
        COUNT(CASE WHEN r.status = 'sent'   THEN 1 END)  AS sent_count,
        COUNT(CASE WHEN r.status IN ('failed','bounced') THEN 1 END) AS bounce_count,
        COUNT(CASE WHEN r.opened_at IS NOT NULL THEN 1 END) AS open_count
    FROM mailing_recipients r
    WHERE r.campaign_id = :cid
      AND r.smtp_account_id IS NOT NULL
      AND DATE(r.sent_at) = :today
    GROUP BY r.smtp_account_id
");
$stmt->execute([':cid' => $campaign_id, ':today' => $today]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    $aid = (int) $row['smtp_account_id'];
    if (!$aid) continue;

    // Determine day number for this account (count existing warmup days + 1 if no entry today)
    $existing = $pdo->prepare(
        'SELECT id, day_number FROM mailing_warmup_log WHERE smtp_account_id = :aid AND log_date = :d'
    );
    $existing->execute([':aid' => $aid, ':d' => $today]);
    $existing_row = $existing->fetch(PDO::FETCH_ASSOC);

    if ($existing_row) {
        $day_num = (int) $existing_row['day_number'];
    } else {
        // Count how many distinct days this account has already logged
        $cnt = $pdo->prepare(
            'SELECT COUNT(DISTINCT log_date) FROM mailing_warmup_log WHERE smtp_account_id = :aid'
        );
        $cnt->execute([':aid' => $aid]);
        $day_num = (int) $cnt->fetchColumn() + 1;
    }

    // Fetch the scheduled target for this day from warmup schedule
    // Parameters: warmup_start=20 emails/day, warmup_max=200 emails/day (30-day ramp)
    $warmup_start = (int)($settings['warmup_daily_start'] ?? 20);
    $warmup_max   = (int)($settings['warmup_daily_max']   ?? 200);
    $schedule = generate_warmup_schedule($warmup_start, $warmup_max);
    $target   = 0;
    foreach ($schedule as $s) {
        if ($s['day'] === $day_num) {
            $target = $s['target'];
            break;
        }
    }
    if (!$target) {
        // Fallback: use sent count as target if beyond schedule
        $target = max((int) $row['sent_count'], 10);
    }

    upsert_warmup_log($aid, $today, [
        'target'     => $target,
        'sent'       => (int) $row['sent_count'],
        'bounced'    => (int) $row['bounce_count'],
        'opened'     => (int) $row['open_count'],
        'day_number' => $day_num,
        'notes'      => 'Auto-synced from campaign #' . $campaign_id,
    ]);

    $synced++;
}

echo json_encode(['synced' => $synced, 'date' => $today]);
