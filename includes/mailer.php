<?php
/**
 * VerlustRückholung – PHPMailer wrapper
 *
 * Provides two functions:
 *   send_confirmation_email(array $data) – confirmation to the user
 *   send_admin_notification(array $data) – new-lead alert to admin
 *
 * SMTP credentials are read from the `smtp_settings` DB table first;
 * config constants are used as a fallback.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Build a configured PHPMailer instance using DB SMTP settings (fallback to constants).
 */
function _build_mailer(): PHPMailer
{
    $mail = new PHPMailer(true);

    // Prefer DB settings; fall back to config.php constants.
    $smtp = get_smtp_settings();

    $mail->isSMTP();
    $mail->Host       = $smtp['host']      ?? SMTP_HOST;
    $mail->Port       = (int) ($smtp['port']  ?? SMTP_PORT);
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp['username']  ?? SMTP_USER;
    $mail->Password   = $smtp['password']  ?? SMTP_PASS;
    $secure           = $smtp['secure']    ?? SMTP_SECURE;
    $mail->SMTPSecure = ($secure !== 'none') ? $secure : '';
    $mail->SMTPDebug  = (int) ($smtp['debug'] ?? SMTP_DEBUG);

    $mail->CharSet  = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->setFrom(
        $smtp['from_email'] ?? FROM_EMAIL,
        $smtp['from_name']  ?? FROM_NAME
    );

    return $mail;
}

/**
 * Send a professional HTML confirmation email to the lead.
 *
 * @param array $data  Associative array with lead fields.
 * @return bool
 */
function send_confirmation_email(array $data): bool
{
    try {
        $mail = _build_mailer();
        $mail->addAddress($data['email'], $data['first_name'] . ' ' . $data['last_name']);
        $brandName = get_setting('company_name', BRAND_NAME);
        $mail->Subject = 'Ihre Fallprüfung bei ' . $brandName . ' – Eingangsbestätigung';

        $mail->isHTML(true);
        $mail->Body    = confirmation_email_html($data);
        $mail->AltBody = confirmation_email_text($data);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('[VerlustRückholung Mailer] Confirmation email failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send a new-lead notification to the admin and (optionally) Telegram.
 *
 * @param array $data  Associative array with lead fields.
 * @return bool
 */
function send_admin_notification(array $data): bool
{
    // Telegram notification (non-blocking; failures only logged)
    send_telegram_notification($data);

    try {
        $adminEmail = get_setting('admin_email', ADMIN_EMAIL);
        $mail = _build_mailer();
        $mail->addAddress($adminEmail, (get_setting('company_name', BRAND_NAME)) . ' Admin');
        $mail->Subject = '🔔 Neue Falleinreichung – ' . $data['first_name'] . ' ' . $data['last_name'];

        $mail->isHTML(true);
        $mail->Body    = admin_notification_html($data);
        $mail->AltBody = admin_notification_text($data);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('[VerlustRückholung Mailer] Admin notification failed: ' . $e->getMessage());
        return false;
    }
}

// ──────────────────────────────────────────────────────────────
// HTML Templates
// ──────────────────────────────────────────────────────────────

function confirmation_email_html(array $d): string
{
    $name        = htmlspecialchars($d['first_name'] . ' ' . $d['last_name'], ENT_QUOTES, 'UTF-8');
    $email       = htmlspecialchars($d['email'],               ENT_QUOTES, 'UTF-8');
    $amount      = number_format((float) ($d['amount_lost'] ?? 0), 2, ',', '.') . ' €';
    $category    = htmlspecialchars($d['platform_category']   ?? 'Nicht angegeben', ENT_QUOTES, 'UTF-8');
    $country     = htmlspecialchars($d['country']             ?? 'Nicht angegeben', ENT_QUOTES, 'UTF-8');
    $year        = htmlspecialchars((string) ($d['year_lost'] ?? 'Nicht angegeben'), ENT_QUOTES, 'UTF-8');
    $description = nl2br(htmlspecialchars($d['case_description'] ?? '', ENT_QUOTES, 'UTF-8'));
    $brand       = BRAND_NAME;
    $domain      = BRAND_DOMAIN;
    $siteUrl     = SITE_URL;
    $adminEmail  = ADMIN_EMAIL;
    $year_now    = date('Y');

    return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Ihre Fallprüfung bei {$brand}</title>
  <style>
    body{margin:0;padding:0;background:#f0f4f8;font-family:'Segoe UI',Arial,sans-serif;color:#2d3748;}
    .wrap{max-width:620px;margin:32px auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.10);}
    .header{background:linear-gradient(135deg,#0a1628 0%,#0d2b5e 60%,#1a4a9e 100%);padding:40px 32px;text-align:center;}
    .header-logo{font-size:26px;font-weight:800;color:#fff;letter-spacing:-0.5px;}
    .header-logo span{color:#f5a623;}
    .header h1{margin:16px 0 8px;font-size:22px;color:#fff;font-weight:700;}
    .header p{margin:0;color:rgba(255,255,255,0.7);font-size:14px;}
    .badge-row{display:flex;justify-content:center;gap:12px;padding:20px 32px;background:#f8faff;border-bottom:1px solid #e8edf5;}
    .badge{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:10px 16px;text-align:center;min-width:120px;flex:1;}
    .badge .val{font-size:18px;font-weight:800;color:#0d2b5e;}
    .badge .lbl{font-size:11px;color:#718096;margin-top:2px;}
    .body{padding:32px;}
    .greeting{font-size:17px;font-weight:600;margin-bottom:8px;color:#1a202c;}
    .intro{font-size:14px;color:#4a5568;line-height:1.7;margin-bottom:24px;}
    .section-title{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#f5a623;margin-bottom:12px;}
    .summary-box{background:#f8faff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:24px;}
    .row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #edf2f7;font-size:14px;}
    .row:last-child{border-bottom:none;}
    .row-label{color:#718096;font-weight:500;}
    .row-val{color:#1a202c;font-weight:600;text-align:right;max-width:55%;}
    .desc-box{background:#fffbf0;border:1px solid #f5a623;border-radius:10px;padding:16px;margin-bottom:24px;font-size:13px;color:#4a5568;line-height:1.7;}
    .steps{margin-bottom:28px;}
    .step{display:flex;gap:14px;margin-bottom:16px;}
    .step-num{width:32px;height:32px;min-width:32px;background:#f5a623;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;color:#000;}
    .step-text h4{margin:0 0 4px;font-size:14px;font-weight:700;color:#1a202c;}
    .step-text p{margin:0;font-size:13px;color:#718096;}
    .cta{text-align:center;margin-bottom:28px;}
    .cta a{display:inline-block;background:linear-gradient(135deg,#f5a623,#e69420);color:#000;font-weight:800;font-size:15px;padding:14px 36px;border-radius:10px;text-decoration:none;letter-spacing:0.02em;}
    .guarantee{background:#f0fff4;border:1px solid #9ae6b4;border-radius:10px;padding:16px;text-align:center;margin-bottom:24px;font-size:13px;color:#276749;}
    .footer{background:#f8faff;border-top:1px solid #e2e8f0;padding:24px 32px;text-align:center;font-size:12px;color:#a0aec0;}
    .footer a{color:#0d2b5e;text-decoration:none;}
  </style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div class="header-logo">⚖️ <span>{$brand}</span></div>
    <h1>Ihre Fallprüfung ist bei uns eingegangen</h1>
    <p>Eingangsbestätigung &amp; nächste Schritte</p>
  </div>

  <div class="badge-row">
    <div class="badge"><div class="val">87%</div><div class="lbl">Erfolgsquote</div></div>
    <div class="badge"><div class="val">€48M+</div><div class="lbl">Rückgefordert</div></div>
    <div class="badge"><div class="val">48h</div><div class="lbl">Erste Antwort</div></div>
    <div class="badge"><div class="val">€0</div><div class="lbl">Vorab-Kosten</div></div>
  </div>

  <div class="body">
    <p class="greeting">Sehr geehrte/r {$name},</p>
    <p class="intro">
      vielen Dank, dass Sie sich an <strong>{$brand}</strong> gewandt haben. Wir haben Ihre Fallprüfung
      erfolgreich entgegengenommen. Unser KI-gestütztes Analysesystem hat bereits begonnen, Ihren Fall
      zu prüfen. Sie erhalten innerhalb von <strong>48 Stunden</strong> eine detaillierte Rückmeldung
      von unserem Expertenteam.
    </p>

    <div class="section-title">📋 Ihre eingereichten Falldaten</div>
    <div class="summary-box">
      <div class="row"><span class="row-label">Name</span><span class="row-val">{$name}</span></div>
      <div class="row"><span class="row-label">E-Mail</span><span class="row-val">{$email}</span></div>
      <div class="row"><span class="row-label">Land</span><span class="row-val">{$country}</span></div>
      <div class="row"><span class="row-label">Jahr des Verlusts</span><span class="row-val">{$year}</span></div>
      <div class="row"><span class="row-label">Verlorener Betrag</span><span class="row-val">{$amount}</span></div>
      <div class="row"><span class="row-label">Betrugsart</span><span class="row-val">{$category}</span></div>
    </div>

    <div class="section-title">💬 Ihre Fallbeschreibung</div>
    <div class="desc-box">{$description}</div>

    <div class="section-title">🚀 Was passiert als nächstes?</div>
    <div class="steps">
      <div class="step">
        <div class="step-num">1</div>
        <div class="step-text">
          <h4>KI-Analyse (jetzt laufend)</h4>
          <p>Unser System analysiert Transaktionsmuster und bekannte Betrugsstrukturen rund um Ihren Fall.</p>
        </div>
      </div>
      <div class="step">
        <div class="step-num">2</div>
        <div class="step-text">
          <h4>Expertenprüfung (innerhalb 48h)</h4>
          <p>Unser Expertenteam bewertet den Analysebericht und kontaktiert Sie persönlich.</p>
        </div>
      </div>
      <div class="step">
        <div class="step-num">3</div>
        <div class="step-text">
          <h4>Ergebnisbericht &amp; Strategie</h4>
          <p>Sie erhalten einen detaillierten Bericht mit konkreten Handlungsempfehlungen und einer Rückforderungsstrategie.</p>
        </div>
      </div>
    </div>

    <div class="guarantee">
      ✅ <strong>Unsere Garantie:</strong> Die Erstprüfung ist vollständig kostenlos und unverbindlich.
      Erst wenn wir Ihnen erfolgreich helfen, entstehen Kosten – und das nur erfolgsbasiert.
    </div>

    <div class="cta">
      <a href="{$siteUrl}">Zu unserem Portal →</a>
    </div>
  </div>

  <div class="footer">
    <p style="margin:0 0 8px;">
      <strong>{$brand}</strong> · <a href="https://{$domain}">{$domain}</a>
    </p>
    <p style="margin:0 0 8px;">
      Bei Fragen antworten Sie einfach auf diese E-Mail oder schreiben Sie uns an
      <a href="mailto:{$adminEmail}">{$adminEmail}</a>
    </p>
    <p style="margin:0;">&copy; {$year_now} {$brand}. Alle Rechte vorbehalten. |
      <a href="https://{$domain}/datenschutz">Datenschutz</a> |
      <a href="https://{$domain}/impressum">Impressum</a>
    </p>
  </div>
</div>
</body>
</html>
HTML;
}

function confirmation_email_text(array $d): string
{
    $brand = BRAND_NAME;
    $name  = $d['first_name'] . ' ' . $d['last_name'];
    return "Sehr geehrte/r {$name},\n\n"
         . "vielen Dank für Ihre Falleinreichung bei {$brand}.\n\n"
         . "Ihre Daten:\n"
         . "- Name:            {$name}\n"
         . "- E-Mail:          {$d['email']}\n"
         . "- Land:            " . ($d['country'] ?? 'k.A.') . "\n"
         . "- Jahr des Verlusts: " . ($d['year_lost'] ?? 'k.A.') . "\n"
         . "- Verlorener Betrag: " . number_format((float)($d['amount_lost'] ?? 0), 2, ',', '.') . " €\n"
         . "- Betrugsart:      " . ($d['platform_category'] ?? 'k.A.') . "\n\n"
         . "Sie erhalten innerhalb von 48 Stunden Rückmeldung.\n\n"
         . "Mit freundlichen Grüßen,\nIhr {$brand}-Team\n" . SITE_URL;
}

function admin_notification_html(array $d): string
{
    $brand    = BRAND_NAME;
    $name     = htmlspecialchars($d['first_name'] . ' ' . $d['last_name'], ENT_QUOTES, 'UTF-8');
    $email    = htmlspecialchars($d['email'],                   ENT_QUOTES, 'UTF-8');
    $phone    = htmlspecialchars($d['phone']    ?? 'k.A.',      ENT_QUOTES, 'UTF-8');
    $amount   = number_format((float)($d['amount_lost'] ?? 0), 2, ',', '.') . ' €';
    $category = htmlspecialchars($d['platform_category'] ?? '', ENT_QUOTES, 'UTF-8');
    $country  = htmlspecialchars($d['country']  ?? 'k.A.',      ENT_QUOTES, 'UTF-8');
    $year     = htmlspecialchars((string)($d['year_lost'] ?? 'k.A.'), ENT_QUOTES, 'UTF-8');
    $desc     = nl2br(htmlspecialchars($d['case_description'] ?? '', ENT_QUOTES, 'UTF-8'));
    $ip       = htmlspecialchars($d['ip'] ?? '', ENT_QUOTES, 'UTF-8');
    $ts       = date('d.m.Y H:i:s');

    return <<<HTML
<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8">
<style>
  body{font-family:Arial,sans-serif;background:#f4f6fb;color:#2d3748;}
  .wrap{max-width:560px;margin:24px auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,0.1);}
  .h{background:#0d2b5e;color:#fff;padding:20px 24px;}
  .h h2{margin:0;font-size:18px;}
  .h .ts{font-size:12px;opacity:0.7;margin-top:4px;}
  .body{padding:24px;}
  .row{display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid #edf2f7;font-size:13px;}
  .row:last-child{border-bottom:none;}
  .lbl{color:#718096;font-weight:600;}
  .val{color:#1a202c;text-align:right;max-width:60%;}
  .desc{background:#fffbf0;border:1px solid #f5a623;border-radius:8px;padding:12px;font-size:13px;margin-top:16px;line-height:1.6;}
</style></head>
<body>
<div class="wrap">
  <div class="h"><h2>🔔 Neue Falleinreichung</h2><div class="ts">{$ts} · {$brand}</div></div>
  <div class="body">
    <div class="row"><span class="lbl">Name</span><span class="val">{$name}</span></div>
    <div class="row"><span class="lbl">E-Mail</span><span class="val">{$email}</span></div>
    <div class="row"><span class="lbl">Telefon</span><span class="val">{$phone}</span></div>
    <div class="row"><span class="lbl">Land</span><span class="val">{$country}</span></div>
    <div class="row"><span class="lbl">Jahr des Verlusts</span><span class="val">{$year}</span></div>
    <div class="row"><span class="lbl">Betrag</span><span class="val">{$amount}</span></div>
    <div class="row"><span class="lbl">Betrugsart</span><span class="val">{$category}</span></div>
    <div class="row"><span class="lbl">IP-Adresse</span><span class="val">{$ip}</span></div>
    <div class="desc"><strong>Fallbeschreibung:</strong><br>{$desc}</div>
  </div>
</div>
</body></html>
HTML;
}

function admin_notification_text(array $d): string
{
    $brand = BRAND_NAME;
    $name  = $d['first_name'] . ' ' . $d['last_name'];
    return "Neue Falleinreichung bei {$brand}\n"
         . str_repeat('-', 40) . "\n"
         . "Name:     {$name}\n"
         . "E-Mail:   {$d['email']}\n"
         . "Telefon:  " . ($d['phone'] ?? 'k.A.') . "\n"
         . "Land:     " . ($d['country'] ?? 'k.A.') . "\n"
         . "Jahr:     " . ($d['year_lost'] ?? 'k.A.') . "\n"
         . "Betrag:   " . number_format((float)($d['amount_lost'] ?? 0), 2, ',', '.') . " €\n"
         . "Art:      " . ($d['platform_category'] ?? 'k.A.') . "\n"
         . "IP:       " . ($d['ip'] ?? 'k.A.') . "\n\n"
         . "Beschreibung:\n" . ($d['case_description'] ?? '') . "\n";
}
