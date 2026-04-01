<?php
/**
 * AJAX Endpoint: mailing_send_batch.php
 *
 * Processes ONE batch of emails for a campaign:
 *   - Picks the current SMTP account
 *   - Sends up to N emails (emails_per_account setting)
 *   - After N emails OR on SMTP error, rotates to the next active account
 *   - Updates mailing_campaigns (sent, failed, status)
 *   - Returns JSON with progress info
 *
 * Called repeatedly from send.php via JS polling.
 * The pause between batches/emails is handled client-side to avoid PHP timeouts.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Must be authenticated admin
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Nicht authentifiziert.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// ── Load required vendor libs ─────────────────────────────────────────────────
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

// ── Input ─────────────────────────────────────────────────────────────────────
$campaign_id = (int) ($_POST['campaign_id'] ?? 0);
if (!$campaign_id) {
    echo json_encode(['error' => 'Keine Kampagnen-ID.']);
    exit;
}

$pdo = db_connect();

// Load campaign
$stmt = $pdo->prepare('SELECT c.*,t.subject,t.body_html,t.body_text FROM mailing_campaigns c LEFT JOIN mailing_templates t ON t.id=c.template_id WHERE c.id=:id FOR UPDATE');
$stmt->execute([':id' => $campaign_id]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$campaign) {
    echo json_encode(['error' => 'Kampagne nicht gefunden.']);
    exit;
}

if ($campaign['status'] !== 'running') {
    echo json_encode(['error' => 'Kampagne ist nicht im Status "running".', 'status' => $campaign['status']]);
    exit;
}

// ── Load settings ─────────────────────────────────────────────────────────────
$emails_per_account  = max(1, (int) get_mailing_setting('emails_per_account', '5'));
$track_opens         = (int) get_mailing_setting('track_opens', '0');
$site_url            = get_setting('site_url', 'https://verlustrueckholung.de');
$company_name        = get_setting('company_name', 'VerlustRückholung');
$unsubscribe_base    = rtrim(get_mailing_setting('unsubscribe_url', '') ?: $site_url, '/');
$max_daily           = max(10, (int) get_mailing_setting('max_daily_per_account', '200'));

// ── Pick current SMTP account ─────────────────────────────────────────────────
$accounts = get_mailing_smtp_accounts(true);
if (empty($accounts)) {
    // Mark failed
    $pdo->prepare('UPDATE mailing_campaigns SET status="failed" WHERE id=:id')->execute([':id'=>$campaign_id]);
    echo json_encode(['error' => 'Keine aktiven SMTP-Accounts verfügbar.']);
    exit;
}

// Find current account index
$current_smtp_id = (int) $campaign['current_smtp_account_id'];
$batch_count     = (int) $campaign['current_smtp_batch_count'];

// Find account in pool
$account      = null;
$account_idx  = 0;
foreach ($accounts as $idx => $a) {
    if ($a['id'] === $current_smtp_id) {
        $account     = $a;
        $account_idx = $idx;
        break;
    }
}
// If account not found (deleted/deactivated), start from first
if (!$account) {
    $account     = $accounts[0];
    $account_idx = 0;
    $batch_count = 0;
    $current_smtp_id = $account['id'];
}

// Check if we must rotate (batch limit reached)
$rotated = false;
if ($batch_count >= $emails_per_account) {
    // Rotate to next account
    $next_idx    = ($account_idx + 1) % count($accounts);
    $account     = $accounts[$next_idx];
    $account_idx = $next_idx;
    $batch_count = 0;
    $current_smtp_id = $account['id'];
    $rotated = true;

    // Update campaign with new account and reset batch count
    $pdo->prepare('UPDATE mailing_campaigns SET current_smtp_account_id=:aid, current_smtp_batch_count=0 WHERE id=:cid')
        ->execute([':aid' => $current_smtp_id, ':cid' => $campaign_id]);

    // Return rotation signal so client can apply pause
    $stats = get_campaign_stats($campaign_id);
    echo json_encode([
        'account_rotated' => true,
        'active_smtp_id'  => $current_smtp_id,
        'account_label'   => $account['label'] ?: $account['from_email'],
        'sent'            => $stats['sent'],
        'failed'          => $stats['failed'],
        'pending'         => $stats['pending'],
        'sent_now'        => 0,
        'failed_now'      => 0,
        'status_text'     => 'SMTP-Account gewechselt zu: ' . ($account['label'] ?: $account['from_email']),
    ]);
    exit;
}

// ── Get next pending recipients (1 at a time to keep response fast) ───────────
$stmt = $pdo->prepare(
    'SELECT * FROM mailing_recipients
     WHERE campaign_id = :cid AND status = "pending" AND email_validity = "valid"
     ORDER BY id ASC LIMIT 1'
);
$stmt->execute([':cid' => $campaign_id]);
$recipient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recipient) {
    // All done
    $pdo->prepare('UPDATE mailing_campaigns SET status="completed", finished_at=NOW() WHERE id=:id')
        ->execute([':id' => $campaign_id]);
    $stats = get_campaign_stats($campaign_id);
    echo json_encode([
        'done'           => true,
        'sent'           => $stats['sent'],
        'failed'         => $stats['failed'],
        'pending'        => 0,
        'sent_now'       => 0,
        'failed_now'     => 0,
        'active_smtp_id' => $current_smtp_id,
        'account_label'  => $account['label'] ?: $account['from_email'],
        'status_text'    => 'Versand abgeschlossen.',
    ]);
    exit;
}

// ── Pre-send MX validity check ────────────────────────────────────────────────
// Re-check the recipient's domain has a live MX/A record before we attempt SMTP.
// If the domain resolves to nothing, mark it invalid so it won't be retried and
// doesn't count as an SMTP failure (which inflates the bounce rate).
$_pre_domain = substr($recipient['email'], strrpos($recipient['email'], '@') + 1);
$_pre_valid  = checkdnsrr($_pre_domain, 'MX') || checkdnsrr($_pre_domain, 'A') || checkdnsrr($_pre_domain, 'AAAA');
if (!$_pre_valid) {
    $pdo->prepare('UPDATE mailing_recipients SET email_validity="invalid", error_msg=:err WHERE id=:id')
        ->execute([':err' => 'Pre-send MX check failed: no mail server for ' . $_pre_domain, ':id' => $recipient['id']]);
    $stats = get_campaign_stats($campaign_id);
    echo json_encode([
        'sent_now'        => 0,
        'failed_now'      => 0,
        'error_detail'    => 'Adresse übersprungen (kein MX): ' . $recipient['email'],
        'active_smtp_id'  => $current_smtp_id,
        'account_label'   => $account['label'] ?: $account['from_email'],
        'account_rotated' => false,
        'sent'            => $stats['sent'],
        'failed'          => $stats['failed'],
        'pending'         => $stats['pending'],
        'done'            => false,
        'no_pending'      => false,
        'status_text'     => 'Ungültige Adresse übersprungen: ' . $recipient['email'],
    ]);
    exit;
}

// ── Build personalised email ──────────────────────────────────────────────────
$unsub_url     = $unsubscribe_base . '/unsubscribe.php?token=' . urlencode($recipient['open_token'] ?? '');
$scam_platform = trim($recipient['scam_platform'] ?? '');
$track_pixel   = '';
if ($track_opens) {
    // Use a visible-but-invisible pixel (no display:none) so email clients actually load it.
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

// Resolve {{#if scam_platform}}…{{else}}…{{/if}} conditional blocks
function resolve_platform_conditional(string $tpl, string $platform): string {
    // Step 1: handle {{#if scam_platform}}…{{else}}…{{/if}} (must run first — more specific)
    $tpl = preg_replace_callback(
        '/\{\{#if\s+scam_platform\}\}(.*?)\{\{else\}\}(.*?)\{\{\/if\}\}/s',
        fn($m) => $platform !== '' ? $m[1] : $m[2],
        $tpl
    );
    // Step 2: handle {{#if scam_platform}}…{{/if}} (no else — runs after)
    $tpl = preg_replace_callback(
        '/\{\{#if\s+scam_platform\}\}(.*?)\{\{\/if\}\}/s',
        fn($m) => $platform !== '' ? $m[1] : '',
        $tpl
    );
    return $tpl;
}

$raw_html = $campaign['body_html'] ?? '';
$raw_subj = $campaign['subject']   ?? '';

$raw_html = resolve_platform_conditional($raw_html, $scam_platform);
$raw_subj = resolve_platform_conditional($raw_subj, $scam_platform);

// Strip any remaining unmatched conditional tags (e.g. stray {{/if}} or unclosed {{#if scam_platform}})
$_cond_rx  = '/\{\{#if\s+scam_platform\}\}|\{\{else\}\}|\{\{\/if\}\}/';
$raw_html  = preg_replace($_cond_rx, '', $raw_html);
$raw_subj  = preg_replace($_cond_rx, '', $raw_subj);

$subject   = str_replace(array_keys($vars), array_values($vars), $raw_subj);
$body_html = str_replace(array_keys($vars), array_values($vars), $raw_html);

// ── Inject click-tracking wrapper around links ────────────────────────────────
$click_token = $recipient['click_token'] ?? '';
if ($click_token) {
    $body_html = preg_replace_callback(
        '/<a\s([^>]*?)href=["\']([^"\']+)["\']([^>]*?)>/i',
        function ($m) use ($site_url, $click_token) {
            $href = $m[2];
            // Skip unsubscribe links and tracking links
            if (strpos($href, 'unsubscribe') !== false || strpos($href, 'track_') !== false) {
                return $m[0];
            }
            $tracked = $site_url . '/track_click.php?t=' . urlencode($click_token) . '&url=' . urlencode($href);
            return '<a ' . $m[1] . 'href="' . htmlspecialchars($tracked, ENT_QUOTES) . '"' . $m[3] . '>';
        },
        $body_html
    );
}

// ── Send email ────────────────────────────────────────────────────────────────
$sent_now   = 0;
$failed_now = 0;
$error_msg  = '';

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
        // No HTML body – fall back to plain-text body_text if available
        $raw_text = $campaign['body_text'] ?? '';
        $body_text_fallback = str_replace(array_keys($vars), array_values($vars), $raw_text);
        $mail->isHTML(false);
        $mail->Body = !empty($body_text_fallback) ? $body_text_fallback : strip_tags($subject);
    }

    // ── Anti-spam headers ─────────────────────────────────────────────────────
    // List-Unsubscribe: reduces spam score and gives providers a machine-readable opt-out
    $unsub_header = '<' . $unsubscribe_base . '/unsubscribe.php?token=' . urlencode($recipient['open_token'] ?? '') . '>';
    $mail->addCustomHeader('List-Unsubscribe', $unsub_header);
    $mail->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
    // Precedence: bulk tells receiving MTAs this is bulk mail (not spam)
    $mail->addCustomHeader('Precedence', 'bulk');
    // Clear the default PHPMailer X-Mailer fingerprint to reduce spam scoring
    $mail->XMailer = ' ';

    $mail->send();

    // Mark sent
    $pdo->prepare('UPDATE mailing_recipients SET status="sent", sent_at=NOW(), smtp_account_id=:aid WHERE id=:id')
        ->execute([':aid' => $account['id'], ':id' => $recipient['id']]);

    // Increment account email_sent counter
    $pdo->prepare('UPDATE mailing_smtp_accounts SET emails_sent=emails_sent+1, last_used_at=NOW() WHERE id=:id')
        ->execute([':id' => $account['id']]);

    // Increment campaign sent + batch count
    $pdo->prepare('UPDATE mailing_campaigns SET sent=sent+1, current_smtp_batch_count=current_smtp_batch_count+1 WHERE id=:id')
        ->execute([':id' => $campaign_id]);

    $sent_now = 1;

} catch (MailException $e) {
    $error_msg = $e->getMessage();
    // Mark failed
    $pdo->prepare('UPDATE mailing_recipients SET status="failed", smtp_account_id=:aid, error_msg=:err WHERE id=:id')
        ->execute([':aid' => $account['id'], ':err' => substr($error_msg, 0, 512), ':id' => $recipient['id']]);
    $pdo->prepare('UPDATE mailing_campaigns SET failed=failed+1 WHERE id=:id')
        ->execute([':id' => $campaign_id]);
    $failed_now = 1;
}

// ── Return response ───────────────────────────────────────────────────────────
$stats = get_campaign_stats($campaign_id);
echo json_encode([
    'sent_now'        => $sent_now,
    'failed_now'      => $failed_now,
    'error_detail'    => $error_msg,
    'active_smtp_id'  => $current_smtp_id,
    'account_label'   => $account['label'] ?: $account['from_email'],
    'account_rotated' => false,
    'sent'            => $stats['sent'],
    'failed'          => $stats['failed'],
    'pending'         => $stats['pending'],
    'done'            => false,
    'no_pending'      => false,
    'status_text'     => sprintf('Gesendet: %d / %d | Fehler: %d | Account: %s',
        $stats['sent'], $stats['total'], $stats['failed'],
        $account['label'] ?: $account['from_email']),
]);
