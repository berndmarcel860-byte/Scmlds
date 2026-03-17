<?php
/**
 * send_email_otp.php
 * AJAX endpoint – generate and e-mail a 6-digit OTP for e-mail verification.
 *
 * POST fields:
 *   csrf_token  – current session CSRF token
 *   email       – e-mail address to verify
 *
 * Session keys written:
 *   $_SESSION['email_otp']  array {
 *       email        string   – address the code was sent to
 *       code         string   – 6-digit code
 *       expires      int      – Unix timestamp after which the code is invalid
 *       attempts     int      – failed verify attempts so far
 *       sends        int      – number of sends in the current send-window
 *       send_window  int      – Unix timestamp when the current window opened
 *   }
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');

function otp_json(bool $ok, string $msg, array $extra = []): never
{
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit;
}

// ── CSRF ──────────────────────────────────────────────────────────────────────
if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    otp_json(false, 'Sitzung abgelaufen. Bitte Seite neu laden.');
}

// ── Input validation ─────────────────────────────────────────────────────────
$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    otp_json(false, 'Bitte eine gültige E-Mail-Adresse eingeben.');
}

// ── Rate limiting (max 3 sends per 60 min per session) ───────────────────────
$otpData = $_SESSION['email_otp'] ?? [];

// Reset window if older than 60 minutes
$windowStart = $otpData['send_window'] ?? 0;
if (time() - $windowStart > 3600) {
    $otpData = []; // reset
}

$sends = $otpData['sends'] ?? 0;
if ($sends >= 3) {
    otp_json(false, 'Zu viele Versuche. Bitte warten Sie eine Stunde und versuchen Sie es erneut.');
}

// ── Generate OTP ─────────────────────────────────────────────────────────────
$code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

$_SESSION['email_otp'] = [
    'email'       => $email,
    'code'        => password_hash($code, PASSWORD_DEFAULT), // store hash, not plaintext
    'expires'     => time() + 600,                           // 10 minutes
    'attempts'    => 0,
    'sends'       => $sends + 1,
    'send_window' => $windowStart ?: time(),
];

// ── Send the code ─────────────────────────────────────────────────────────────
try {
    $mail = _build_mailer();
    $mail->addAddress($email);
    $brandName = get_setting('company_name', BRAND_NAME);
    $mail->Subject = 'Ihr Bestätigungscode – ' . $brandName;
    $mail->isHTML(true);
    $mail->Body    = otp_email_html($email, $code, $brandName);
    $mail->AltBody = "Ihr Bestätigungscode: $code\n\nDieser Code ist 10 Minuten gültig.\n\n$brandName";
    $mail->send();
} catch (\Exception $e) {
    error_log('[OTP] Mail send failed: ' . $e->getMessage());
    otp_json(false, 'Der Code konnte nicht gesendet werden. Bitte prüfen Sie die E-Mail-Adresse und versuchen Sie es erneut.');
}

otp_json(true, 'Code gesendet. Bitte prüfen Sie Ihren Posteingang (und den Spam-Ordner).');

// ── HTML email template ───────────────────────────────────────────────────────
function otp_email_html(string $email, string $code, string $brand): string
{
    $e = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $c = htmlspecialchars($code,  ENT_QUOTES, 'UTF-8');
    $b = htmlspecialchars($brand, ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"><title>Bestätigungscode</title></head>
<body style="margin:0;padding:0;background:#f4f7fb;font-family:Inter,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fb;padding:40px 0;">
  <tr><td align="center">
    <table width="540" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;max-width:540px;">
      <tr>
        <td style="background:#0d2b5e;padding:28px 36px;">
          <p style="margin:0;font-size:22px;font-weight:800;color:#fff;">⚖️ {$b}</p>
          <p style="margin:4px 0 0;font-size:13px;color:rgba(255,255,255,0.65);">E-Mail-Bestätigung</p>
        </td>
      </tr>
      <tr>
        <td style="padding:36px;">
          <p style="margin:0 0 16px;font-size:15px;color:#1a202c;">
            Um sicherzustellen, dass diese E-Mail-Adresse Ihnen gehört, geben Sie bitte den folgenden 6-stelligen Code auf der Website ein:
          </p>
          <div style="text-align:center;margin:24px 0;">
            <span style="display:inline-block;background:#f4f7fb;border:2px dashed #0d2b5e;border-radius:10px;padding:18px 36px;font-size:36px;font-weight:900;letter-spacing:10px;color:#0d2b5e;">{$c}</span>
          </div>
          <p style="margin:0 0 8px;font-size:13px;color:#6c757d;">
            Dieser Code ist <strong>10 Minuten</strong> gültig.<br>
            Falls Sie diesen Code nicht angefordert haben, ignorieren Sie diese E-Mail.
          </p>
          <hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0;">
          <p style="margin:0;font-size:12px;color:#adb5bd;">
            {$b} &nbsp;|&nbsp; Anfrage für: {$e}
          </p>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
}
