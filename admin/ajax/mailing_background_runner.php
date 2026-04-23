<?php
/**
 * Background Email Runner
 *
 * Called via AJAX when the admin clicks "Hintergrund-Modus starten".
 * The script:
 *   1. Responds to the browser immediately (HTTP 200, JSON)
 *   2. Sets ignore_user_abort(true) so PHP continues running after the tab closes
 *   3. Loops through pending recipients, sending one email per iteration
 *   4. Respects the auto_send_active flag in mailing_campaigns — set it to 0 to stop
 *   5. Applies the same pauses as the browser-based send (pause_between_emails_ms,
 *      pause_between_accounts_ms) using sleep() on the server side
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

$campaign_id = (int) ($_POST['campaign_id'] ?? 0);
if (!$campaign_id) {
    echo json_encode(['error' => 'Keine Kampagnen-ID.']);
    exit;
}

$pdo = db_connect();

// Mark background mode active
$pdo->prepare('UPDATE mailing_campaigns SET auto_send_active=1 WHERE id=:id AND status="running"')
    ->execute([':id' => $campaign_id]);

// Verify campaign is in running state
$row = $pdo->prepare('SELECT status, auto_send_active FROM mailing_campaigns WHERE id=:id');
$row->execute([':id' => $campaign_id]);
$campaign_row = $row->fetch(PDO::FETCH_ASSOC);

if (!$campaign_row || $campaign_row['status'] !== 'running') {
    $pdo->prepare('UPDATE mailing_campaigns SET auto_send_active=0 WHERE id=:id')->execute([':id' => $campaign_id]);
    echo json_encode(['error' => 'Kampagne ist nicht im Status "running".']);
    exit;
}

// ── Respond to browser immediately then continue in background ────────────────
$response_body = json_encode(['started' => true, 'campaign_id' => $campaign_id]);
header('Content-Type: application/json; charset=utf-8');
header('Connection: close');
header('Content-Length: ' . strlen($response_body));
echo $response_body;

// Flush all buffers so the HTTP response is sent to the browser
if (ob_get_level() > 0) {
    ob_end_flush();
}
flush();

// Close session so other requests aren't blocked while we run
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// ── Continue running after browser disconnects ────────────────────────────────
ignore_user_abort(true);
set_time_limit(0);

// ── Load vendor libs for PHPMailer ────────────────────────────────────────────
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

// ── Load settings once (they won't change during a run) ──────────────────────
$emails_per_account  = max(1, (int) get_mailing_setting('emails_per_account', '5'));
$pause_email_ms      = max(500, (int) get_mailing_setting('pause_between_emails_ms', '3000'));
$pause_account_ms    = max(1000, (int) get_mailing_setting('pause_between_accounts_ms', '15000'));
$track_opens         = (int) get_mailing_setting('track_opens', '0');
$site_url            = get_setting('site_url', 'https://verlustrueckholung.de');
$company_name        = get_setting('company_name', 'VerlustRückholung');
$unsubscribe_base    = rtrim(get_mailing_setting('unsubscribe_url', '') ?: $site_url, '/');

// Resolve {{#if scam_platform}}…{{else}}…{{/if}} conditional blocks
function _bg_resolve_platform_conditional(string $tpl, string $platform): string {
    $tpl = preg_replace_callback(
        '/\{\{#if\s+scam_platform\}\}(.*?)\{\{else\}\}(.*?)\{\{\/if\}\}/s',
        fn($m) => $platform !== '' ? $m[1] : $m[2],
        $tpl
    );
    $tpl = preg_replace_callback(
        '/\{\{#if\s+scam_platform\}\}(.*?)\{\{\/if\}\}/s',
        fn($m) => $platform !== '' ? $m[1] : '',
        $tpl
    );
    return $tpl;
}

// ── Main send loop ────────────────────────────────────────────────────────────
$consecutive_failures = 0;
$MAX_CONSECUTIVE_FAIL  = 5;

while (true) {
    // Re-fetch campaign to check stop flag and current SMTP state
    $pdo = db_connect(); // reconnect to avoid stale connections on long runs
    $stmt = $pdo->prepare(
        'SELECT c.*, t.subject, t.body_html, t.body_text
         FROM mailing_campaigns c
         LEFT JOIN mailing_templates t ON t.id = c.template_id
         WHERE c.id = :id FOR UPDATE'
    );
    $stmt->execute([':id' => $campaign_id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$campaign || !$campaign['auto_send_active'] || $campaign['status'] !== 'running') {
        // Stop requested or campaign no longer active
        break;
    }

    $accounts = get_mailing_smtp_accounts(true);
    if (empty($accounts)) {
        $pdo->prepare('UPDATE mailing_campaigns SET status="failed", auto_send_active=0 WHERE id=:id')
            ->execute([':id' => $campaign_id]);
        break;
    }

    // Find current account
    $current_smtp_id = (int) $campaign['current_smtp_account_id'];
    $batch_count     = (int) $campaign['current_smtp_batch_count'];

    $account = null;
    $account_idx = 0;
    foreach ($accounts as $idx => $a) {
        if ($a['id'] === $current_smtp_id) {
            $account = $a;
            $account_idx = $idx;
            break;
        }
    }
    if (!$account) {
        $account = $accounts[0];
        $account_idx = 0;
        $batch_count = 0;
        $current_smtp_id = $account['id'];
    }

    // Rotate account if batch limit reached
    if ($batch_count >= $emails_per_account) {
        $next_idx        = ($account_idx + 1) % count($accounts);
        $account         = $accounts[$next_idx];
        $account_idx     = $next_idx;
        $current_smtp_id = $account['id'];
        $pdo->prepare('UPDATE mailing_campaigns SET current_smtp_account_id=:aid, current_smtp_batch_count=0 WHERE id=:cid')
            ->execute([':aid' => $current_smtp_id, ':cid' => $campaign_id]);
        // Always apply the account-rotation pause (even with a single SMTP account)
        // so that configured cooldown between re-uses of the same account is respected.
        usleep($pause_account_ms * 1000);
        continue;
    }

    // Pick next pending recipient
    $stmt = $pdo->prepare(
        'SELECT * FROM mailing_recipients
         WHERE campaign_id = :cid AND status = "pending" AND email_validity = "valid"
         ORDER BY id ASC LIMIT 1'
    );
    $stmt->execute([':cid' => $campaign_id]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recipient) {
        // All done
        $pdo->prepare('UPDATE mailing_campaigns SET status="completed", finished_at=NOW(), auto_send_active=0 WHERE id=:id')
            ->execute([':id' => $campaign_id]);
        break;
    }

    // Pre-send MX check
    $_pre_domain = substr($recipient['email'], strrpos($recipient['email'], '@') + 1);
    if (!checkdnsrr($_pre_domain, 'MX') && !checkdnsrr($_pre_domain, 'A') && !checkdnsrr($_pre_domain, 'AAAA')) {
        $pdo->prepare('UPDATE mailing_recipients SET email_validity="invalid", error_msg=:err WHERE id=:id')
            ->execute([':err' => 'No MX: ' . $_pre_domain, ':id' => $recipient['id']]);
        usleep(200000); // 200ms
        continue;
    }

    // Build personalised email
    $unsub_url     = $unsubscribe_base . '/unsubscribe.php?token=' . urlencode($recipient['open_token'] ?? '');
    $scam_platform = trim($recipient['scam_platform'] ?? '');
    $track_pixel   = '';
    if ($track_opens) {
        $track_pixel = '<img src="' . htmlspecialchars($site_url . '/track_open.php?t=' . urlencode($recipient['open_token'] ?? ''), ENT_QUOTES) . '" width="1" height="1" border="0" alt="" style="width:1px;height:1px;border:0;overflow:hidden;padding:0;margin:0;" />';
    }

    $vars = [
        '{{name}}'            => htmlspecialchars($recipient['name'] ?: 'Interessent'),
        '{{email}}'           => htmlspecialchars($recipient['email']),
        '{{company_name}}'    => htmlspecialchars($company_name),
        '{{site_url}}'        => htmlspecialchars($site_url),
        '{{sender_name}}'     => htmlspecialchars($account['from_name'] ?: $company_name),
        '{{unsubscribe_url}}' => htmlspecialchars($unsub_url),
        '{{open_tracker}}'    => $track_pixel,
        '{{scam_platform}}'   => htmlspecialchars($scam_platform),
    ];

    $raw_html = _bg_resolve_platform_conditional($campaign['body_html'] ?? '', $scam_platform);
    $raw_subj = _bg_resolve_platform_conditional($campaign['subject']   ?? '', $scam_platform);
    $_cond_rx = '/\{\{#if\s+scam_platform\}\}|\{\{else\}\}|\{\{\/if\}\}/';
    $raw_html = preg_replace($_cond_rx, '', $raw_html);
    $raw_subj = preg_replace($_cond_rx, '', $raw_subj);

    $subject   = str_replace(array_keys($vars), array_values($vars), $raw_subj);
    $body_html = str_replace(array_keys($vars), array_values($vars), $raw_html);

    // Click tracking
    $click_token = $recipient['click_token'] ?? '';
    if ($click_token) {
        $body_html = preg_replace_callback(
            '/<a\s([^>]*?)href=["\']([^"\']+)["\']([^>]*?)>/i',
            function ($m) use ($site_url, $click_token) {
                $href = $m[2];
                if (strpos($href, 'unsubscribe') !== false || strpos($href, 'track_') !== false) {
                    return $m[0];
                }
                $tracked = $site_url . '/track_click.php?t=' . urlencode($click_token) . '&url=' . urlencode($href);
                return '<a ' . $m[1] . 'href="' . htmlspecialchars($tracked, ENT_QUOTES) . '"' . $m[3] . '>';
            },
            $body_html
        );
    }

    // Send email
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $account['host'];
        $mail->Port       = (int) $account['port'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $account['username'];
        $mail->Password   = $account['password'];
        $secure           = $account['secure'] ?? 'tls';
        $mail->SMTPSecure = ($secure !== 'none') ? $secure : '';
        $mail->SMTPDebug  = 0;
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64';
        $mail->setFrom($account['from_email'] ?: $account['username'], $account['from_name'] ?: $company_name);
        $mail->addAddress($recipient['email'], $recipient['name'] ?: '');
        $mail->Subject = $subject;
        if (!empty($body_html)) {
            $mail->isHTML(true);
            $mail->Body    = $body_html;
            $mail->AltBody = strip_tags($body_html);
        } else {
            $raw_text = $campaign['body_text'] ?? '';
            $mail->isHTML(false);
            $mail->Body = str_replace(array_keys($vars), array_values($vars), $raw_text) ?: strip_tags($subject);
        }
        $unsub_header = '<' . $unsubscribe_base . '/unsubscribe.php?token=' . urlencode($recipient['open_token'] ?? '') . '>';
        $mail->addCustomHeader('List-Unsubscribe', $unsub_header);
        $mail->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        $mail->addCustomHeader('Precedence', 'bulk');
        $mail->XMailer = ' ';
        $mail->send();

        $pdo->prepare('UPDATE mailing_recipients SET status="sent", sent_at=NOW(), smtp_account_id=:aid WHERE id=:id')
            ->execute([':aid' => $account['id'], ':id' => $recipient['id']]);
        $pdo->prepare('UPDATE mailing_smtp_accounts SET emails_sent=emails_sent+1, last_used_at=NOW() WHERE id=:id')
            ->execute([':id' => $account['id']]);
        $pdo->prepare('UPDATE mailing_campaigns SET sent=sent+1, current_smtp_batch_count=current_smtp_batch_count+1 WHERE id=:id')
            ->execute([':id' => $campaign_id]);

        $consecutive_failures = 0;

    } catch (MailException $e) {
        $error_msg = $e->getMessage();
        $pdo->prepare('UPDATE mailing_recipients SET status="failed", smtp_account_id=:aid, error_msg=:err WHERE id=:id')
            ->execute([':aid' => $account['id'], ':err' => substr($error_msg, 0, 512), ':id' => $recipient['id']]);
        $pdo->prepare('UPDATE mailing_campaigns SET failed=failed+1 WHERE id=:id')
            ->execute([':id' => $campaign_id]);

        $consecutive_failures++;

        // Force rotate on SMTP error
        if (count($accounts) > 1) {
            $next_idx = ($account_idx + 1) % count($accounts);
            $pdo->prepare('UPDATE mailing_campaigns SET current_smtp_account_id=:aid, current_smtp_batch_count=0 WHERE id=:cid')
                ->execute([':aid' => $accounts[$next_idx]['id'], ':cid' => $campaign_id]);
            usleep($pause_account_ms * 1000);
            continue;
        }

        // If too many consecutive failures, abort
        if ($consecutive_failures >= $MAX_CONSECUTIVE_FAIL) {
            $pdo->prepare('UPDATE mailing_campaigns SET auto_send_active=0 WHERE id=:id')
                ->execute([':id' => $campaign_id]);
            break;
        }
    }

    // Pause between emails
    usleep($pause_email_ms * 1000);
}

// Ensure flag is cleared on exit
$pdo->prepare('UPDATE mailing_campaigns SET auto_send_active=0 WHERE id=:id AND auto_send_active=1')
    ->execute([':id' => $campaign_id]);
