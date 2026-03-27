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
    $amount      = number_format((float) ($d['amount_lost'] ?? 0), 2, ',', '.') . ' &euro;';
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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="de">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ihre Fallpr&uuml;fung bei {$brand}</title>
  <!--[if mso]>
  <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
  <![endif]-->
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
    body { margin: 0 !important; padding: 0 !important; background-color: #f0f4f8; }
    a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; }
    @media only screen and (max-width: 620px) {
      .wrapper { width: 100% !important; }
      .badge-cell { display: block !important; width: 50% !important; }
    }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#f0f4f8;">

<!-- Outer wrapper -->
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f0f4f8;">
<tr><td align="center" style="padding:32px 16px;">

  <!-- Card -->
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="wrapper" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.10);">

    <!-- ── HEADER ── -->
    <tr>
      <td bgcolor="#0d2b5e" style="background-color:#0d2b5e;padding:40px 32px;text-align:center;">
        <p style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:26px;font-weight:bold;color:#ffffff;">
          &#9878;&#65039; <span style="color:#f5a623;">{$brand}</span>
        </p>
        <h1 style="margin:0 0 6px;font-family:Arial,sans-serif;font-size:22px;font-weight:bold;color:#ffffff;line-height:1.3;">
          Ihre Fallpr&uuml;fung ist bei uns eingegangen
        </h1>
        <p style="margin:0;font-family:Arial,sans-serif;font-size:13px;color:rgba(255,255,255,0.75);">
          Eingangsbestätigung &amp; nächste Schritte
        </p>
      </td>
    </tr>

    <!-- ── TRUST BADGES ── -->
    <tr>
      <td bgcolor="#f8faff" style="background-color:#f8faff;padding:20px 24px;border-bottom:1px solid #e2e8f0;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <tr>
            <td align="center" class="badge-cell" style="padding:8px 6px;border-right:1px solid #e2e8f0;">
              <p style="margin:0;font-family:Arial,sans-serif;font-size:20px;font-weight:bold;color:#0d2b5e;">87%</p>
              <p style="margin:2px 0 0;font-family:Arial,sans-serif;font-size:11px;color:#718096;">Erfolgsquote</p>
            </td>
            <td align="center" class="badge-cell" style="padding:8px 6px;border-right:1px solid #e2e8f0;">
              <p style="margin:0;font-family:Arial,sans-serif;font-size:20px;font-weight:bold;color:#0d2b5e;">&euro;48M+</p>
              <p style="margin:2px 0 0;font-family:Arial,sans-serif;font-size:11px;color:#718096;">R&uuml;ckgefordert</p>
            </td>
            <td align="center" class="badge-cell" style="padding:8px 6px;border-right:1px solid #e2e8f0;">
              <p style="margin:0;font-family:Arial,sans-serif;font-size:20px;font-weight:bold;color:#0d2b5e;">48h</p>
              <p style="margin:2px 0 0;font-family:Arial,sans-serif;font-size:11px;color:#718096;">Erste Antwort</p>
            </td>
            <td align="center" class="badge-cell" style="padding:8px 6px;">
              <p style="margin:0;font-family:Arial,sans-serif;font-size:20px;font-weight:bold;color:#0d2b5e;">&euro;0</p>
              <p style="margin:2px 0 0;font-family:Arial,sans-serif;font-size:11px;color:#718096;">Vorab-Kosten</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- ── BODY ── -->
    <tr>
      <td style="padding:32px;">

        <!-- Greeting -->
        <p style="margin:0 0 6px;font-family:Arial,sans-serif;font-size:17px;font-weight:bold;color:#1a202c;">
          Sehr geehrte/r {$name},
        </p>
        <p style="margin:0 0 28px;font-family:Arial,sans-serif;font-size:14px;color:#4a5568;line-height:1.7;">
          vielen Dank, dass Sie sich an <strong>{$brand}</strong> gewandt haben. Wir haben Ihre Fallpr&uuml;fung
          erfolgreich entgegengenommen. Unser KI-gest&uuml;tztes Analysesystem hat bereits begonnen, Ihren Fall
          zu pr&uuml;fen. Sie erhalten innerhalb von <strong>48 Stunden</strong> eine detaillierte R&uuml;ckmeldung
          von unserem Expertenteam.
        </p>

        <!-- Case summary heading -->
        <p style="margin:0 0 12px;font-family:Arial,sans-serif;font-size:12px;font-weight:bold;text-transform:uppercase;letter-spacing:0.06em;color:#f5a623;">
          &#128203; Ihre eingereichten Falldaten
        </p>

        <!-- Case data table -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f8faff;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:24px;">
          <tr>
            <td style="padding:10px 16px;border-bottom:1px solid #edf2f7;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"><tr>
                <td style="font-family:Arial,sans-serif;font-size:13px;color:#718096;font-weight:500;">Name</td>
                <td align="right" style="font-family:Arial,sans-serif;font-size:13px;color:#1a202c;font-weight:bold;">{$name}</td>
              </tr></table>
            </td>
          </tr>
          <tr>
            <td style="padding:10px 16px;border-bottom:1px solid #edf2f7;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"><tr>
                <td style="font-family:Arial,sans-serif;font-size:13px;color:#718096;font-weight:500;">E-Mail</td>
                <td align="right" style="font-family:Arial,sans-serif;font-size:13px;color:#1a202c;font-weight:bold;">{$email}</td>
              </tr></table>
            </td>
          </tr>
          <tr>
            <td style="padding:10px 16px;border-bottom:1px solid #edf2f7;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"><tr>
                <td style="font-family:Arial,sans-serif;font-size:13px;color:#718096;font-weight:500;">Land</td>
                <td align="right" style="font-family:Arial,sans-serif;font-size:13px;color:#1a202c;font-weight:bold;">{$country}</td>
              </tr></table>
            </td>
          </tr>
          <tr>
            <td style="padding:10px 16px;border-bottom:1px solid #edf2f7;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"><tr>
                <td style="font-family:Arial,sans-serif;font-size:13px;color:#718096;font-weight:500;">Jahr des Verlusts</td>
                <td align="right" style="font-family:Arial,sans-serif;font-size:13px;color:#1a202c;font-weight:bold;">{$year}</td>
              </tr></table>
            </td>
          </tr>
          <tr>
            <td style="padding:10px 16px;border-bottom:1px solid #edf2f7;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"><tr>
                <td style="font-family:Arial,sans-serif;font-size:13px;color:#718096;font-weight:500;">Verlorener Betrag</td>
                <td align="right" style="font-family:Arial,sans-serif;font-size:13px;color:#1a202c;font-weight:bold;">{$amount}</td>
              </tr></table>
            </td>
          </tr>
          <tr>
            <td style="padding:10px 16px;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"><tr>
                <td style="font-family:Arial,sans-serif;font-size:13px;color:#718096;font-weight:500;">Betrugsart</td>
                <td align="right" style="font-family:Arial,sans-serif;font-size:13px;color:#1a202c;font-weight:bold;">{$category}</td>
              </tr></table>
            </td>
          </tr>
        </table>

        <!-- Case description -->
        <p style="margin:0 0 12px;font-family:Arial,sans-serif;font-size:12px;font-weight:bold;text-transform:uppercase;letter-spacing:0.06em;color:#f5a623;">
          &#128172; Ihre Fallbeschreibung
        </p>
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:28px;">
          <tr>
            <td style="background:#fffbf0;border:1px solid #f5a623;border-radius:10px;padding:16px;font-family:Arial,sans-serif;font-size:13px;color:#4a5568;line-height:1.7;">
              {$description}
            </td>
          </tr>
        </table>

        <!-- Next steps -->
        <p style="margin:0 0 16px;font-family:Arial,sans-serif;font-size:12px;font-weight:bold;text-transform:uppercase;letter-spacing:0.06em;color:#f5a623;">
          &#128640; Was passiert als n&auml;chstes?
        </p>

        <!-- Step 1 -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:14px;">
          <tr>
            <td width="40" valign="top" style="padding-right:14px;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr><td width="36" height="36" align="center" valign="middle" bgcolor="#f5a623" style="background-color:#f5a623;border-radius:50%;font-family:Arial,sans-serif;font-size:14px;font-weight:bold;color:#000000;">1</td></tr>
              </table>
            </td>
            <td valign="top">
              <p style="margin:0 0 3px;font-family:Arial,sans-serif;font-size:14px;font-weight:bold;color:#1a202c;">KI-Analyse <span style="color:#f5a623;">(jetzt laufend)</span></p>
              <p style="margin:0;font-family:Arial,sans-serif;font-size:13px;color:#718096;line-height:1.6;">Unser System analysiert Transaktionsmuster und bekannte Betrugsstrukturen rund um Ihren Fall.</p>
            </td>
          </tr>
        </table>

        <!-- Step 2 -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:14px;">
          <tr>
            <td width="40" valign="top" style="padding-right:14px;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr><td width="36" height="36" align="center" valign="middle" bgcolor="#f5a623" style="background-color:#f5a623;border-radius:50%;font-family:Arial,sans-serif;font-size:14px;font-weight:bold;color:#000000;">2</td></tr>
              </table>
            </td>
            <td valign="top">
              <p style="margin:0 0 3px;font-family:Arial,sans-serif;font-size:14px;font-weight:bold;color:#1a202c;">Expertenpr&uuml;fung <span style="color:#f5a623;">(innerhalb 48h)</span></p>
              <p style="margin:0;font-family:Arial,sans-serif;font-size:13px;color:#718096;line-height:1.6;">Unser Expertenteam bewertet den Analysebericht und kontaktiert Sie pers&ouml;nlich.</p>
            </td>
          </tr>
        </table>

        <!-- Step 3 -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:28px;">
          <tr>
            <td width="40" valign="top" style="padding-right:14px;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr><td width="36" height="36" align="center" valign="middle" bgcolor="#f5a623" style="background-color:#f5a623;border-radius:50%;font-family:Arial,sans-serif;font-size:14px;font-weight:bold;color:#000000;">3</td></tr>
              </table>
            </td>
            <td valign="top">
              <p style="margin:0 0 3px;font-family:Arial,sans-serif;font-size:14px;font-weight:bold;color:#1a202c;">Ergebnisbericht &amp; Strategie</p>
              <p style="margin:0;font-family:Arial,sans-serif;font-size:13px;color:#718096;line-height:1.6;">Sie erhalten einen detaillierten Bericht mit konkreten Handlungsempfehlungen und einer R&uuml;ckforderungsstrategie.</p>
            </td>
          </tr>
        </table>

        <!-- Guarantee box -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:28px;">
          <tr>
            <td style="background:#f0fff4;border:1px solid #9ae6b4;border-radius:10px;padding:16px;text-align:center;font-family:Arial,sans-serif;font-size:13px;color:#276749;line-height:1.6;">
              &#9989; <strong>Unsere Garantie:</strong> Die Erstpr&uuml;fung ist vollst&auml;ndig kostenlos und unverbindlich.
              Erst wenn wir Ihnen erfolgreich helfen, entstehen Kosten &ndash; und das nur erfolgsbasiert.
            </td>
          </tr>
        </table>

        <!-- CTA button -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:8px;">
          <tr>
            <td align="center">
              <!--[if mso]>
              <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="{$siteUrl}" style="height:48px;v-text-anchor:middle;width:220px;" arcsize="20%" fillcolor="#f5a623" stroke="f">
                <w:anchorlock/>
                <center style="color:#000000;font-family:Arial,sans-serif;font-size:15px;font-weight:bold;">Zu unserem Portal &rarr;</center>
              </v:roundrect>
              <![endif]-->
              <!--[if !mso]><!-->
              <a href="{$siteUrl}" style="display:inline-block;background-color:#f5a623;color:#000000;font-family:Arial,sans-serif;font-size:15px;font-weight:bold;padding:14px 36px;border-radius:10px;text-decoration:none;letter-spacing:0.02em;">
                Zu unserem Portal &rarr;
              </a>
              <!--<![endif]-->
            </td>
          </tr>
        </table>

      </td>
    </tr>

    <!-- ── FOOTER ── -->
    <tr>
      <td bgcolor="#f8faff" style="background-color:#f8faff;border-top:1px solid #e2e8f0;padding:24px 32px;text-align:center;">
        <p style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:13px;color:#4a5568;">
          <strong>{$brand}</strong> &middot; <a href="https://{$domain}" style="color:#0d2b5e;text-decoration:none;">{$domain}</a>
        </p>
        <p style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:12px;color:#718096;">
          Bei Fragen antworten Sie einfach auf diese E-Mail oder schreiben Sie uns an
          <a href="mailto:{$adminEmail}" style="color:#0d2b5e;text-decoration:none;">{$adminEmail}</a>
        </p>
        <p style="margin:0;font-family:Arial,sans-serif;font-size:11px;color:#a0aec0;">
          &copy; {$year_now} {$brand}. Alle Rechte vorbehalten. &nbsp;|&nbsp;
          <a href="https://{$domain}/datenschutz" style="color:#0d2b5e;text-decoration:none;">Datenschutz</a> &nbsp;|&nbsp;
          <a href="https://{$domain}/impressum" style="color:#0d2b5e;text-decoration:none;">Impressum</a>
        </p>
      </td>
    </tr>

  </table><!-- /Card -->

</td></tr>
</table><!-- /Outer wrapper -->

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
