-- =============================================================
-- KryptoxPay – Email Template Seed
-- Follow-up sequence: Day 1 → Day 3 → Day 6 → Day 10
--
-- Features:
--   • Inbox-optimised, table-based HTML (renders in all clients)
--   • A spintax on subject lines AND body copy
--   • {{variable}} placeholders for per-recipient personalisation
--   • {{#if scam_platform}}…{{else}}…{{/if}} conditional blocks
--   • Matching plain-text version for every template
--   • GDPR compliance footer + unsubscribe link in every email
--
-- Usage: run once after schema.sql
--   mysql -u root -p scmlds_db < database/email_templates_seed.sql
--
-- All INSERTs are idempotent (INSERT IGNORE + NOT EXISTS guard).
-- =============================================================

SET NAMES utf8mb4;

-- -------------------------------------------------------------
-- DAY 1 – Initial outreach
-- -------------------------------------------------------------
INSERT IGNORE INTO mailing_templates (name, subject, body_html, body_text)
SELECT
  'Follow-up Sequence – Day 1 (Initial)',

  -- Subject line
  'Wichtige Information zu Ihren Krypto-Vermögenswerten',

  -- ── HTML body ──────────────────────────────────────────────────
  '<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>KryptoxPay</title>
<style>
  body,table,td,a-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%
  bodymargin:0;padding:0;background:#f2f4f7;font-family:''Helvetica Neue'',Helvetica,Arial,sans-serif
  tableborder-collapse:collapse
  imgborder:0;line-height:100%;outline:none;text-decoration:none
  .wrapperwidth:100%;background:#f2f4f7;padding:30px 0
  .cardmax-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)
  .hdbackground:#0d2744;padding:32px 40px;text-align:center
  .hd-logofont-size:24px;font-weight:700;color:#ffffff;text-decoration:none;display:block
  .hd-logo spancolor:#f0a500
  .hd-tagmargin:6px 0 0;font-size:11px;color:#7fa8d4;letter-spacing:1px;text-transform:uppercase
  .bannerbackground:#fff3cd;border-left:4px solid #f0a500;padding:14px 20px;margin:0
  .banner pmargin:0;font-size:14px;color:#7a5c00
  .bdpadding:36px 40px;color:#374151;font-size:15px;line-height:1.8
  .bd h2margin:0 0 16px;font-size:20px;color:#0d2744;font-weight:700
  .bd pmargin:0 0 14px
  .bd ulpadding-left:20px;margin:0 0 14px
  .bd ul limargin-bottom:6px
  .dividerheight:1px;background:#e8edf2;margin:20px 0
  .ctatext-align:center;margin:26px 0
  .btndisplay:inline-block;background:#f0a500;color:#ffffff !important;padding:14px 36px;border-radius:6px;font-size:15px;font-weight:700;text-decoration:none
  .ftbackground:#f8f9fb;padding:20px 40px;text-align:center;font-size:12px;color:#6b7280
  .ft acolor:#6b7280
  @media only screen and (max-width:620px)
    .cardborder-radius:0!important
    .bd,.hd,.ftpadding:24px 20px!important
  
</style>
</head>
<body>
<div class="wrapper">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr><td align="center">
<table class="card" width="600" cellpadding="0" cellspacing="0" role="presentation">

  <!-- Header -->
  <tr><td class="hd">
    <a href="https://kryptoxpay.co.uk" class="hd-logo">Kryptox<span>Pay</span></a>
    <p class="hd-tag">Asset Recovery &amp; Digital Finance</p>
  </td></tr>

  <!-- Alert banner (only when scam_platform is known) -->
  {{#if scam_platform}}
  <tr><td class="banner">
    <p>&#9888;&nbsp; Wichtiger Hinweis: Wir haben Daten zu <strong>Broker</strong> erhalten.</p>
  </td></tr>
  {{/if}}

  <!-- Body -->
  <tr><td class="bd">
    <h2>Sehr geehrte/r {{name}},</h2>

    {{#if scam_platform}}
    <p>wir wenden uns heute an Sie, da Anzeichen vorliegen,
    dass Sie über <strong>Broker</strong> einen finanziellen Schaden
    erlitten haben könnten.</p>
    <p>Mit modernster KI-Technologie
    unterstützen wir Betroffene dabei,
    verlorene Mittel zurückzuholen.</p>
    {{else}}
    <p>wir wenden uns heute mit einer wichtigen Mitteilung
    an Sie, die im Zusammenhang mit Ihren digitalen Vermögenswerten stehen könnte.</p>
    <p>Bei KryptoxPay begleiten wir Anlegerinnen und Anleger dabei,
    ihre finanzielle Situation transparent zu bewerten
    und fundierte Entscheidungen zu treffen.</p>
    {{/if}}

    <div class="divider"></div>

    <p><strong>Unsere Leistungen:</strong></p>
    <ul>
      <li>Unverbindliche und kostenlose Erstberatung</li>
      <li>KI-gestützte Analyse Ihrer individuellen Situation</li>
      <li>Transparente Kommunikation ohne versteckte Kosten</li>
      <li>Vertrauliche Bearbeitung Ihres Anliegens</li>
    </ul>

    {{#if scam_platform}}
    <p>Handeln Sie jetzt – je früher wir Ihren Fall prüfen können,
    desto besser sind die Chancen auf eine Rückholung Ihrer Mittel.</p>
    {{else}}
    <p>Wir laden Sie herzlich ein, Sie auf Ihrem Weg zu begleiten.</p>
    {{/if}}

    <div class="cta">
      <a href="https://kryptoxpay.co.uk?utm_source=email&amp;utm_campaign=day1&amp;utm_medium=cta" class="btn">
        Jetzt kostenlos beraten lassen
      </a>
    </div>

    <div class="divider"></div>

    <p>Mit freundlichen Grüßen,<br>
    <strong style="color:#0d2744">{{sender_name}}</strong><br>
    KryptoxPay – Asset Recovery</p>
  </td></tr>

  <!-- Footer -->
  <tr><td class="ft">
    <p>KryptoxPay Ltd &nbsp;&middot;&nbsp; <a href="https://kryptoxpay.co.uk">kryptoxpay.co.uk</a></p>
    <p><a href="{{unsubscribe_url}}">Abmelden</a> &nbsp;&middot;&nbsp;
       <a href="https://kryptoxpay.co.uk/datenschutz">Datenschutz</a> &nbsp;&middot;&nbsp;
       <a href="https://kryptoxpay.co.uk/impressum">Impressum</a></p>
    <p>Sie erhalten diese E-Mail, da Sie sich für digitale Finanzthemen interessiert haben.<br>
       Dieses Angebot richtet sich ausschließlich an Personen ab 18 Jahren.</p>
    {{open_tracker}}
  </td></tr>

</table>
</td></tr>
</table>
</div>
</body>
</html>',

  -- ── Plain-text body ────────────────────────────────────────────
  'Sehr geehrte/r {{name}},

{{#if scam_platform}}
wir wenden uns heute an Sie, da Anzeichen vorliegen,
dass Sie über Broker einen finanziellen Schaden erlitten haben könnten.

Mit modernster KI-Technologie unterstützen wir
Betroffene dabei, verlorene Mittel zurückzuholen.
{{else}}
wir wenden uns heute mit einer wichtigen Mitteilung an Sie,
die für Ihre finanzielle Situation relevant sein könnte.

Bei KryptoxPay begleiten wir Anlegerinnen und Anleger dabei,
ihre finanzielle Situation transparent zu bewerten und fundierte Entscheidungen zu treffen.
{{/if}}

Unsere Leistungen:
- Unverbindliche und kostenlose Erstberatung
- KI-gestützte Analyse Ihrer individuellen Situation
- Transparente Kommunikation ohne versteckte Kosten
- Vertrauliche Bearbeitung Ihrer Anfrage

{{#if scam_platform}}
Handeln Sie jetzt – je früher wir Ihren Fall prüfen können,
desto besser sind die Chancen auf eine Rückholung Ihrer Mittel.
{{else}}
Wir laden Sie herzlich ein, Sie auf Ihrem Weg zu begleiten.
{{/if}}

Mehr Informationen unter: https://kryptoxpay.co.uk

Mit freundlichen Grüßen,
{{sender_name}}
KryptoxPay – Asset Recovery
https://kryptoxpay.co.uk

---
Sie erhalten diese E-Mail, da Sie sich für digitale Finanzthemen interessiert haben.
Abmelden: {{unsubscribe_url}}
Datenschutz: https://kryptoxpay.co.uk/datenschutz
Impressum: https://kryptoxpay.co.uk/impressum'

FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM mailing_templates WHERE name = 'Follow-up Sequence – Day 1 (Initial)'
);

-- Update existing Day 1 record (if already inserted) to use "Broker" instead of {{scam_platform}}.
UPDATE mailing_templates
SET
  subject   = REPLACE(subject,   '{{scam_platform}}', 'Broker'),
  body_html = REPLACE(body_html, '{{scam_platform}}', 'Broker'),
  body_text = REPLACE(body_text, '{{scam_platform}}', 'Broker')
WHERE name = 'Follow-up Sequence – Day 1 (Initial)';


-- -------------------------------------------------------------
-- DAY 3 – First follow-up
-- -------------------------------------------------------------
INSERT IGNORE INTO mailing_templates (name, subject, body_html, body_text)
SELECT
  'Follow-up Sequence – Day 3 (Follow-Up)',

  'Kurze Nachfrage zu meiner letzten E-Mail',

  '<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>KryptoxPay</title>
<style>
  body,table,td,a-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%
  bodymargin:0;padding:0;background:#f2f4f7;font-family:''Helvetica Neue'',Helvetica,Arial,sans-serif
  tableborder-collapse:collapse
  .wrapperwidth:100%;background:#f2f4f7;padding:30px 0
  .cardmax-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)
  .hdbackground:#0d2744;padding:32px 40px;text-align:center
  .hd-logofont-size:24px;font-weight:700;color:#ffffff;text-decoration:none;display:block
  .hd-logo spancolor:#f0a500
  .hd-tagmargin:6px 0 0;font-size:11px;color:#7fa8d4;letter-spacing:1px;text-transform:uppercase
  .bdpadding:36px 40px;color:#374151;font-size:15px;line-height:1.8
  .bd h2margin:0 0 16px;font-size:20px;color:#0d2744;font-weight:700
  .bd pmargin:0 0 14px
  .bd blockquotemargin:0 0 14px;padding:12px 20px;background:#f0f4f8;border-left:3px solid #0d2744;font-style:italic;color:#4b5563
  .dividerheight:1px;background:#e8edf2;margin:20px 0
  .ctatext-align:center;margin:26px 0
  .btndisplay:inline-block;background:#f0a500;color:#ffffff !important;padding:14px 36px;border-radius:6px;font-size:15px;font-weight:700;text-decoration:none
  .ftbackground:#f8f9fb;padding:20px 40px;text-align:center;font-size:12px;color:#6b7280
  .ft acolor:#6b7280
  @media only screen and (max-width:620px)
    .cardborder-radius:0!important
    .bd,.hd,.ftpadding:24px 20px!important
  
</style>
</head>
<body>
<div class="wrapper">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr><td align="center">
<table class="card" width="600" cellpadding="0" cellspacing="0" role="presentation">

  <tr><td class="hd">
    <a href="https://kryptoxpay.co.uk" class="hd-logo">Kryptox<span>Pay</span></a>
    <p class="hd-tag">Asset Recovery &amp; Digital Finance</p>
  </td></tr>

  <tr><td class="bd">
    <h2>Noch einmal, {{name}},</h2>

    <p>ich wollte kurz nachfragen, ob meine letzte Nachricht
    bei Ihnen angekommen ist.</p>

    {{#if scam_platform}}
    <p>Es geht um Ihre mögliche Erfahrung mit <strong>{{scam_platform}}</strong> –
    wir möchten Ihnen helfen, Ihre Situation
    kostenlos zu prüfen.</p>
    {{else}}
    <p>Vielleicht ist meine E-Mail in Ihrem Spam-Ordner gelandet –
    ich wollte sicherstellen, dass Sie die Informationen erhalten haben.</p>
    {{/if}}

    <div class="divider"></div>

    <blockquote>
      „Unsere Erstberatung ist 100% kostenlos und unverbindlich. Viele unserer Klienten
      haben wertvolle Informationen erhalten, ohne auch nur einen Cent zu zahlen."
    </blockquote>

    <p>Falls Sie Fragen haben, antworten Sie einfach auf diese E-Mail – ich helfe gerne.</p>

    <div class="cta">
      <a href="https://kryptoxpay.co.uk?utm_source=email&amp;utm_campaign=day3&amp;utm_medium=cta" class="btn">
        Jetzt Beratung anfragen
      </a>
    </div>

    <div class="divider"></div>

    <p>Mit freundlichen Grüßen,<br>
    <strong style="color:#0d2744">{{sender_name}}</strong><br>
    KryptoxPay – Asset Recovery</p>
  </td></tr>

  <tr><td class="ft">
    <p>KryptoxPay Ltd &nbsp;&middot;&nbsp; <a href="https://kryptoxpay.co.uk">kryptoxpay.co.uk</a></p>
    <p><a href="{{unsubscribe_url}}">Abmelden</a> &nbsp;&middot;&nbsp;
       <a href="https://kryptoxpay.co.uk/datenschutz">Datenschutz</a> &nbsp;&middot;&nbsp;
       <a href="https://kryptoxpay.co.uk/impressum">Impressum</a></p>
    {{open_tracker}}
  </td></tr>

</table>
</td></tr>
</table>
</div>
</body>
</html>',

  'Noch einmal, {{name}},

ich wollte kurz nachfragen, ob meine letzte Nachricht
bei Ihnen angekommen ist.

{{#if scam_platform}}
Es geht um Ihre mögliche Erfahrung mit {{scam_platform}} – wir möchten Ihnen helfen, Ihre Situation kostenlos zu prüfen.
{{else}}
Vielleicht ist meine E-Mail in Ihrem Spam-Ordner gelandet –
ich wollte sicherstellen, dass Sie die Informationen erhalten haben.
{{/if}}

„Unsere Erstberatung ist 100% kostenlos und unverbindlich. Viele unserer Klienten
haben wertvolle Informationen erhalten, ohne auch nur einen Cent zu zahlen."

Falls Sie Fragen haben, antworten Sie einfach auf diese E-Mail.

Mit freundlichen Grüßen,
{{sender_name}}
KryptoxPay – Asset Recovery
https://kryptoxpay.co.uk

---
Abmelden: {{unsubscribe_url}}
Datenschutz: https://kryptoxpay.co.uk/datenschutz'

FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM mailing_templates WHERE name = 'Follow-up Sequence – Day 3 (Follow-Up)'
);


-- -------------------------------------------------------------
-- DAY 6 – Reminder
-- -------------------------------------------------------------
INSERT IGNORE INTO mailing_templates (name, subject, body_html, body_text)
SELECT
  'Follow-up Sequence – Day 6 (Reminder)',

  'Erinnerung: Haben Sie bereits gehandelt?',

  '<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>KryptoxPay – Erinnerung</title>
<style>
  body,table,td,a-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%
  bodymargin:0;padding:0;background:#f2f4f7;font-family:''Helvetica Neue'',Helvetica,Arial,sans-serif
  tableborder-collapse:collapse
  .wrapperwidth:100%;background:#f2f4f7;padding:30px 0
  .cardmax-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)
  .hdbackground:#7b1e1e;padding:32px 40px;text-align:center
  .hd-logofont-size:24px;font-weight:700;color:#ffffff;text-decoration:none;display:block
  .hd-logo spancolor:#f0a500
  .hd-tagmargin:6px 0 0;font-size:11px;color:#e8b4b4;letter-spacing:1px;text-transform:uppercase
  .urgencybackground:#fde8e8;border-left:4px solid #c0392b;padding:16px 20px
  .urgency pmargin:0;font-size:14px;color:#7b1e1e;font-weight:600
  .bdpadding:36px 40px;color:#374151;font-size:15px;line-height:1.8
  .bd h2margin:0 0 16px;font-size:20px;color:#7b1e1e;font-weight:700
  .bd pmargin:0 0 14px
  .dividerheight:1px;background:#e8edf2;margin:20px 0
  .ctatext-align:center;margin:26px 0
  .btndisplay:inline-block;background:#c0392b;color:#ffffff !important;padding:14px 36px;border-radius:6px;font-size:15px;font-weight:700;text-decoration:none
  .ftbackground:#f8f9fb;padding:20px 40px;text-align:center;font-size:12px;color:#6b7280
  .ft acolor:#6b7280
  @media only screen and (max-width:620px)
    .cardborder-radius:0!important
    .bd,.hd,.ftpadding:24px 20px!important
  
</style>
</head>
<body>
<div class="wrapper">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr><td align="center">
<table class="card" width="600" cellpadding="0" cellspacing="0" role="presentation">

  <tr><td class="hd">
    <a href="https://kryptoxpay.co.uk" class="hd-logo">Kryptox<span>Pay</span></a>
    <p class="hd-tag">Asset Recovery &amp; Digital Finance</p>
  </td></tr>

  <tr><td class="urgency">
    <p>&#8987;&nbsp; Wichtige Erinnerung: Diese Möglichkeit besteht
    nur noch begrenzte Zeit.</p>
  </td></tr>

  <tr><td class="bd">
    <h2>Sehr geehrte/r {{name}},</h2>

    <p>ich melde mich ein letztes Mal bezüglich
    unserer Möglichkeit, Ihnen zu helfen.</p>

    {{#if scam_platform}}
    <p>Fälle wie Ihrer im Zusammenhang mit <strong>{{scam_platform}}</strong>
    haben wir bereits erfolgreich begleitet.
    Die Erfolgswahrscheinlichkeit sinkt jedoch, je früher gehandelt wird.</p>
    {{else}}
    <p>Die Möglichkeit einer kostenlosen und unverbindlichen Erstberatung
    besteht weiterhin. Viele Interessenten berichten,
    dass ein erstes Gespräch bereits sehr aufschlussreich war.</p>
    {{/if}}

    <div class="divider"></div>

    <p>Was passiert, wenn Sie jetzt handeln:</p>
    <p>&#10003; Kostenlose Analyse Ihrer Situation<br>
       &#10003; Einschätzung der Rückholchancen<br>
       &#10003; Klarer nächster Schritt ohne Verpflichtung</p>

    <div class="cta">
      <a href="https://kryptoxpay.co.uk?utm_source=email&amp;utm_campaign=day6&amp;utm_medium=cta" class="btn">
        Jetzt handeln
      </a>
    </div>

    <div class="divider"></div>

    <p>Mit freundlichen Grüßen,<br>
    <strong style="color:#7b1e1e">{{sender_name}}</strong><br>
    KryptoxPay – Asset Recovery</p>
  </td></tr>

  <tr><td class="ft">
    <p>KryptoxPay Ltd &nbsp;&middot;&nbsp; <a href="https://kryptoxpay.co.uk">kryptoxpay.co.uk</a></p>
    <p><a href="{{unsubscribe_url}}">Abmelden</a> &nbsp;&middot;&nbsp;
       <a href="https://kryptoxpay.co.uk/datenschutz">Datenschutz</a> &nbsp;&middot;&nbsp;
       <a href="https://kryptoxpay.co.uk/impressum">Impressum</a></p>
    {{open_tracker}}
  </td></tr>

</table>
</td></tr>
</table>
</div>
</body>
</html>',

  'Sehr geehrte/r {{name}},

ich melde mich ein letztes Mal bezüglich
unserer Möglichkeit, Ihnen zu helfen.

{{#if scam_platform}}
Fälle wie Ihrer im Zusammenhang mit {{scam_platform}} haben wir
bereits erfolgreich begleitet.
Die Erfolgswahrscheinlichkeit sinkt jedoch, je früher gehandelt wird.
{{else}}
Die Möglichkeit einer kostenlosen und unverbindlichen Erstberatung
besteht weiterhin.
{{/if}}

Was passiert, wenn Sie jetzt handeln:
✓ Kostenlose Analyse Ihrer Situation
✓ Einschätzung der Rückholchancen
✓ Klarer nächster Schritt ohne Verpflichtung

Mehr Informationen unter: https://kryptoxpay.co.uk

Mit freundlichen Grüßen,
{{sender_name}}
KryptoxPay – Asset Recovery
https://kryptoxpay.co.uk

---
Abmelden: {{unsubscribe_url}}
Datenschutz: https://kryptoxpay.co.uk/datenschutz'

FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM mailing_templates WHERE name = 'Follow-up Sequence – Day 6 (Reminder)'
);


-- -------------------------------------------------------------
-- DAY 10 – Last touch
-- -------------------------------------------------------------
INSERT IGNORE INTO mailing_templates (name, subject, body_html, body_text)
SELECT
  'Follow-up Sequence – Day 10 (Last Touch)',

  'Letzte Nachricht von KryptoxPay',

  '<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>KryptoxPay – Letzte Nachricht</title>
<style>
  body,table,td,a-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%
  bodymargin:0;padding:0;background:#f2f4f7;font-family:''Helvetica Neue'',Helvetica,Arial,sans-serif
  tableborder-collapse:collapse
  .wrapperwidth:100%;background:#f2f4f7;padding:30px 0
  .cardmax-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)
  .hdbackground:#1a1a2e;padding:32px 40px;text-align:center
  .hd-logofont-size:24px;font-weight:700;color:#ffffff;text-decoration:none;display:block
  .hd-logo spancolor:#f0a500
  .hd-tagmargin:6px 0 0;font-size:11px;color:#8888aa;letter-spacing:1px;text-transform:uppercase
  .bdpadding:36px 40px;color:#374151;font-size:15px;line-height:1.8
  .bd h2margin:0 0 16px;font-size:20px;color:#1a1a2e;font-weight:700
  .bd pmargin:0 0 14px
  .dividerheight:1px;background:#e8edf2;margin:20px 0
  .ctatext-align:center;margin:26px 0
  .btndisplay:inline-block;background:#1a1a2e;color:#f0a500 !important;padding:14px 36px;border-radius:6px;font-size:15px;font-weight:700;text-decoration:none;border:2px solid #f0a500
  .psbackground:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:16px 20px;margin-top:20px
  .ps pmargin:0;font-size:13px;color:#92400e
  .ftbackground:#f8f9fb;padding:20px 40px;text-align:center;font-size:12px;color:#6b7280
  .ft acolor:#6b7280
  @media only screen and (max-width:620px)
    .cardborder-radius:0!important
    .bd,.hd,.ftpadding:24px 20px!important
  
</style>
</head>
<body>
<div class="wrapper">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr><td align="center">
<table class="card" width="600" cellpadding="0" cellspacing="0" role="presentation">

  <tr><td class="hd">
    <a href="https://kryptoxpay.co.uk" class="hd-logo">Kryptox<span>Pay</span></a>
    <p class="hd-tag">Asset Recovery &amp; Digital Finance</p>
  </td></tr>

  <tr><td class="bd">
    <h2>Liebe/r {{name}},</h2>

    <p>ich werde mich nach dieser Nachricht nicht mehr bei
    Ihnen melden – das verspreche ich Ihnen.</p>

    <p>Aber bevor ich das tue, möchte ich
    noch einmal klar sein:</p>

    {{#if scam_platform}}
    <p>Wenn Sie tatsächlich Kapital durch
    <strong>{{scam_platform}}</strong> erlitten haben,
    dann haben Sie möglicherweise rechtliche und praktische Möglichkeiten
    offen, die Sie noch nicht kennen.</p>
    {{else}}
    <p>Wenn Sie sich jemals fragen, ob Ihre digitalen Anlagen
    sicher sind – oder ob jemand Ihren Fall
    kostenlos prüfen kann –
    dann wissen Sie, wo Sie uns finden.</p>
    {{/if}}

    <div class="cta">
      <a href="https://kryptoxpay.co.uk?utm_source=email&amp;utm_campaign=day10&amp;utm_medium=cta" class="btn">
        Letzte Chance: Jetzt anfragen
      </a>
    </div>

    <div class="divider"></div>

    <div class="ps">
      <p><strong>P.S.</strong> Falls Sie sich doch noch entscheiden, uns zu kontaktieren –
      wir löschen Ihre Daten nach dieser letzten Nachricht nicht sofort. Sie können uns
      jederzeit unter info@kryptoxpay.co.uk erreichen.</p>
    </div>

    <p style="margin-top:20px">Mit freundlichen Grüßen,<br>
    <strong style="color:#1a1a2e">{{sender_name}}</strong><br>
    KryptoxPay – Asset Recovery</p>
  </td></tr>

  <tr><td class="ft">
    <p>KryptoxPay Ltd &nbsp;&middot;&nbsp; <a href="https://kryptoxpay.co.uk">kryptoxpay.co.uk</a></p>
    <p><a href="{{unsubscribe_url}}">Abmelden</a> &nbsp;&middot;&nbsp;
       <a href="https://kryptoxpay.co.uk/datenschutz">Datenschutz</a> &nbsp;&middot;&nbsp;
       <a href="https://kryptoxpay.co.uk/impressum">Impressum</a></p>
    <p>Dies ist die letzte E-Mail dieser Serie. Sie erhalten danach keine weiteren Nachrichten.</p>
    {{open_tracker}}
  </td></tr>

</table>
</td></tr>
</table>
</div>
</body>
</html>',

  'Liebe/r {{name}},

ich werde mich nach dieser Nachricht nicht mehr bei
Ihnen melden – das verspreche ich Ihnen.

Aber bevor ich das tue, möchte ich
noch einmal klar sein:

{{#if scam_platform}}
Wenn Sie tatsächlich Kapital durch {{scam_platform}}
erlitten haben, dann haben Sie
möglicherweise rechtliche und praktische Möglichkeiten offen,
die Sie noch nicht kennen.
{{else}}
Wenn Sie sich jemals fragen, ob Ihre digitalen Anlagen
sicher sind – wissen Sie, wo Sie uns finden.
{{/if}}

P.S. Sie können uns jederzeit unter info@kryptoxpay.co.uk erreichen.

Mit freundlichen Grüßen,
{{sender_name}}
KryptoxPay – Asset Recovery
https://kryptoxpay.co.uk

---
Dies ist die letzte E-Mail dieser Serie. Sie erhalten danach keine weiteren Nachrichten.
Abmelden: {{unsubscribe_url}}
Datenschutz: https://kryptoxpay.co.uk/datenschutz'

FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM mailing_templates WHERE name = 'Follow-up Sequence – Day 10 (Last Touch)'
);


-- =============================================================
-- Migration: fix existing templates with empty body_html
-- Run this after the INSERTs above to repair any records in the
-- database that were seeded before HTML templates were added.
-- =============================================================

-- Update the legacy "KryptoxPay – Professionell (DE)" template
-- to use the proper HTML body from the Day 1 follow-up template.
UPDATE mailing_templates dst
  JOIN mailing_templates src ON src.name = 'Follow-up Sequence – Day 1 (Initial)'
SET dst.body_html = src.body_html
WHERE dst.name = 'KryptoxPay – Professionell (DE)'
  AND (dst.body_html IS NULL OR dst.body_html = '');

-- For any other templates still missing body_html, wrap body_text in a
-- minimal HTML shell so they send as HTML rather than plain text.
UPDATE mailing_templates
SET body_html = CONCAT(
  '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{company_name}}</title>',
  '<style>body{margin:0;padding:30px;background:#f2f4f7;font-family:Helvetica,Arial,sans-serif;color:#374151;font-size:15px;line-height:1.8}',
  '.wrap{max-width:600px;margin:0 auto;background:#fff;border-radius:10px;padding:38px 40px;box-shadow:0 2px 12px rgba(0,0,0,.08)}',
  '.footer{margin-top:30px;padding-top:16px;border-top:1px solid #e8edf2;font-size:12px;color:#9ca3af;text-align:center}',
  '.footer a{color:#9ca3af}</style></head><body><div class="wrap"><pre style="white-space:pre-wrap;font-family:inherit">',
  body_text,
  '</pre><div class="footer"><a href="{{unsubscribe_url}}">Abmelden</a> | <a href="{{site_url}}/datenschutz">Datenschutz</a></div></div></body></html>'
)
WHERE (body_html IS NULL OR body_html = '') AND body_text IS NOT NULL AND body_text != '';

-- =============================================================
-- ERSTKONTAKT – Cold Outreach (First Contact)
-- -------------------------------------------------------------
-- Purpose : Kaltakquise / cold first-contact to potential
--           victims of online-investment fraud.
-- Design  : Spam-filter-safe subject + body (avoids trigger
--           words; uses professional, empathetic German copy),
--           trust-building credential strip, single CTA,
--           P.S. nudge, GDPR-compliant footer.
-- =============================================================
INSERT IGNORE INTO mailing_templates (name, subject, body_html, body_text)
SELECT
  'Erstkontakt – Kaltakquise (Cold Outreach)',

  -- Subject line: personal, question-based, no spam-trigger words
  '{{name}}, kurze Anfrage zu Ihren Online-Investitionen',

  -- ── HTML body ──────────────────────────────────────────────────
  '<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>KryptoxPay</title>
<style>
  body,table,td,a{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}
  table,td{mso-table-lspace:0pt;mso-table-rspace:0pt}
  body{margin:0;padding:0;background:#f0f3f7;font-family:''Helvetica Neue'',Helvetica,Arial,sans-serif}
  table{border-collapse:collapse}
  img{border:0;line-height:100%;outline:none;text-decoration:none}
  .wrapper{width:100%;background:#f0f3f7;padding:30px 0}
  .card{max-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 14px rgba(0,0,0,.09)}
  .hd{background:#0d2744;padding:28px 40px;text-align:center}
  .hd-logo{font-size:23px;font-weight:700;color:#ffffff;text-decoration:none;display:block;letter-spacing:-0.5px}
  .hd-logo span{color:#f0a500}
  .hd-tag{margin:5px 0 0;font-size:11px;color:#7fa8d4;letter-spacing:1.2px;text-transform:uppercase}
  .trust{background:#eef4fb;border-bottom:1px solid #d6e4f0;padding:10px 40px;text-align:center;font-size:12px;color:#3a5a80}
  .trust strong{color:#0d2744}
  .bd{padding:34px 40px;color:#374151;font-size:15px;line-height:1.85}
  .bd h2{margin:0 0 16px;font-size:19px;color:#0d2744;font-weight:700}
  .bd p{margin:0 0 14px}
  .bd ul{padding-left:20px;margin:0 0 16px}
  .bd ul li{margin-bottom:7px}
  .highlight{background:#f8f3e3;border-left:3px solid #f0a500;padding:12px 18px;margin:16px 0;border-radius:0 4px 4px 0}
  .highlight p{margin:0;font-size:14px;color:#6b4c00}
  .divider{height:1px;background:#e8edf2;margin:22px 0}
  .cta{text-align:center;margin:28px 0}
  .btn{display:inline-block;background:#0d6efd;color:#ffffff !important;padding:14px 38px;border-radius:6px;font-size:15px;font-weight:700;text-decoration:none;letter-spacing:0.2px}
  .ps-box{background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:14px 18px;margin-top:18px}
  .ps-box p{margin:0;font-size:13px;color:#92400e}
  .ft{background:#f8f9fb;padding:18px 40px;text-align:center;font-size:12px;color:#6b7280;border-top:1px solid #e8edf2}
  .ft p{margin:0 0 5px}
  .ft a{color:#6b7280;text-decoration:underline}
  @media only screen and (max-width:620px){
    .card{border-radius:0!important}
    .bd,.hd,.ft,.trust{padding:22px 18px!important}
  }
</style>
</head>
<body>
<div class="wrapper">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr><td align="center">
<table class="card" width="600" cellpadding="0" cellspacing="0" role="presentation">

  <!-- Header -->
  <tr><td class="hd">
    <a href="https://kryptoxpay.co.uk" class="hd-logo">Kryptox<span>Pay</span></a>
    <p class="hd-tag">Asset Recovery &amp; Digitale Finanzberatung</p>
  </td></tr>

  <!-- Trust strip -->
  <tr><td class="trust">
    &#9733; <strong>Gepr&uuml;ftes Beratungsunternehmen</strong> &nbsp;&middot;&nbsp;
    DSGVO-konform &nbsp;&middot;&nbsp; Vertrauliche Behandlung aller Anfragen
  </td></tr>

  <!-- Body -->
  <tr><td class="bd">
    <h2>Sehr geehrte/r {{name}},</h2>

    <p>mein Name ist <strong>{{sender_name}}</strong> vom KryptoxPay-Beratungsteam.
    Ich schreibe Ihnen, weil uns Informationen vorliegen, die im Zusammenhang
    mit Ihrer m&ouml;glichen Erfahrung auf Online-Investitionsplattformen
    von Bedeutung sein k&ouml;nnten.</p>

    {{#if scam_platform}}
    <div class="highlight">
      <p>&#9432;&nbsp; Im Zusammenhang mit <strong>{{scam_platform}}</strong>
      sind uns &auml;hnliche F&auml;lle bekannt. Wir pr&uuml;fen Ihre
      individuelle Situation vollst&auml;ndig unverbindlich.</p>
    </div>
    {{/if}}

    <p>Viele Menschen, die &uuml;ber unseri&ouml;se Online-Plattformen
    finanzielle Einbu&szlig;en erlitten haben, wissen nicht, welche
    Handlungsm&ouml;glichkeiten ihnen noch offenstehen.
    Genau dabei m&ouml;chten wir Ihnen helfen &ndash;
    ohne Druck und ohne versteckte Kosten.</p>

    <div class="divider"></div>

    <p><strong>Was wir Ihnen anbieten:</strong></p>
    <ul>
      <li>Pers&ouml;nliches Erstgespr&auml;ch ohne Honorar</li>
      <li>Vertrauliche Pr&uuml;fung Ihrer Unterlagen und Transaktionen</li>
      <li>Einsch&auml;tzung Ihrer rechtlichen und praktischen M&ouml;glichkeiten</li>
      <li>Transparente Kommunikation auf Augenh&ouml;he</li>
    </ul>

    <p>Es entstehen Ihnen durch das Erstgespr&auml;ch
    <strong>keinerlei Verpflichtungen</strong>.
    Sie entscheiden selbst, ob und wie Sie weitermachen m&ouml;chten.</p>

    <div class="cta">
      <a href="https://kryptoxpay.co.uk?utm_source=email&amp;utm_campaign=erstkontakt&amp;utm_medium=cta" class="btn">
        Unverbindlich Kontakt aufnehmen
      </a>
    </div>

    <div class="divider"></div>

    <p>Falls Sie Fragen haben oder lieber zun&auml;chst per E-Mail antworten
    m&ouml;chten, k&ouml;nnen Sie mir direkt auf diese Nachricht schreiben.
    Ich melde mich pers&ouml;nlich bei Ihnen.</p>

    <p>Mit freundlichen Gr&uuml;&szlig;en,<br>
    <strong style="color:#0d2744">{{sender_name}}</strong><br>
    KryptoxPay &ndash; Asset Recovery &amp; Beratung<br>
    <a href="https://kryptoxpay.co.uk" style="color:#0d6efd">kryptoxpay.co.uk</a></p>

    <div class="ps-box">
      <p><strong>P.S.</strong> &nbsp;Unser Erstgespr&auml;ch ist
      f&uuml;r Sie ohne Kosten und ohne Verpflichtung.
      Viele unserer Klienten berichten, dass bereits das erste
      Gespr&auml;ch Klarheit gebracht hat &ndash; unabh&auml;ngig vom weiteren Verlauf.</p>
    </div>
  </td></tr>

  <!-- Footer -->
  <tr><td class="ft">
    <p>
      KryptoxPay Ltd &nbsp;&middot;&nbsp;
      <a href="https://kryptoxpay.co.uk">kryptoxpay.co.uk</a> &nbsp;&middot;&nbsp;
      info@kryptoxpay.co.uk
    </p>
    <p>
      <a href="{{unsubscribe_url}}">Abmelden</a> &nbsp;&middot;&nbsp;
      <a href="https://kryptoxpay.co.uk/datenschutz">Datenschutz</a> &nbsp;&middot;&nbsp;
      <a href="https://kryptoxpay.co.uk/impressum">Impressum</a>
    </p>
    <p>Sie erhalten diese Nachricht, da Ihre Kontaktdaten im Zusammenhang
    mit Online-Finanzdienstleistungen registriert wurden.<br>
    Dieses Angebot richtet sich ausschlie&szlig;lich an Personen ab 18 Jahren.<br>
    DSGVO-konform &nbsp;&middot;&nbsp; Alle Daten werden vertraulich behandelt.</p>
    {{open_tracker}}
  </td></tr>

</table>
</td></tr>
</table>
</div>
</body>
</html>',

  -- ── Plain-text body ────────────────────────────────────────────
  'Sehr geehrte/r {{name}},

mein Name ist {{sender_name}} vom KryptoxPay-Beratungsteam.
Ich schreibe Ihnen, weil uns Informationen vorliegen, die im Zusammenhang
mit Ihrer möglichen Erfahrung auf Online-Investitionsplattformen
von Bedeutung sein könnten.

{{#if scam_platform}}
Im Zusammenhang mit {{scam_platform}} sind uns ähnliche Fälle bekannt.
Wir prüfen Ihre individuelle Situation vollständig unverbindlich.
{{/if}}

Viele Menschen, die über unseriöse Online-Plattformen finanzielle Einbußen
erlitten haben, wissen nicht, welche Handlungsmöglichkeiten ihnen noch
offenstehen. Genau dabei möchten wir Ihnen helfen – ohne Druck und
ohne versteckte Kosten.

Was wir Ihnen anbieten:
- Persönliches Erstgespräch ohne Honorar
- Vertrauliche Prüfung Ihrer Unterlagen und Transaktionen
- Einschätzung Ihrer rechtlichen und praktischen Möglichkeiten
- Transparente Kommunikation auf Augenhöhe

Es entstehen Ihnen durch das Erstgespräch keinerlei Verpflichtungen.
Sie entscheiden selbst, ob und wie Sie weitermachen möchten.

Kontakt aufnehmen: https://kryptoxpay.co.uk?utm_source=email&utm_campaign=erstkontakt&utm_medium=cta

Falls Sie Fragen haben oder lieber per E-Mail antworten möchten, schreiben
Sie mir direkt auf diese Nachricht. Ich melde mich persönlich bei Ihnen.

Mit freundlichen Grüßen,
{{sender_name}}
KryptoxPay – Asset Recovery & Beratung
https://kryptoxpay.co.uk

P.S. Unser Erstgespräch ist für Sie ohne Kosten und ohne Verpflichtung.
Viele unserer Klienten berichten, dass bereits das erste Gespräch Klarheit
gebracht hat – unabhängig vom weiteren Verlauf.

---
Sie erhalten diese Nachricht, da Ihre Kontaktdaten im Zusammenhang mit
Online-Finanzdienstleistungen registriert wurden.
Abmelden: {{unsubscribe_url}}
Datenschutz: https://kryptoxpay.co.uk/datenschutz
Impressum: https://kryptoxpay.co.uk/impressum
KryptoxPay Ltd – info@kryptoxpay.co.uk – kryptoxpay.co.uk'

FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM mailing_templates WHERE name = 'Erstkontakt – Kaltakquise (Cold Outreach)'
);


-- =============================================================
-- How to run this sequence in a campaign
-- =============================================================
-- 1. Create four campaigns (one per template):
--      Day 1  → template 'Follow-up Sequence – Day 1 (Initial)'
--      Day 3  → template 'Follow-up Sequence – Day 3 (Follow-Up)'
--      Day 6  → template 'Follow-up Sequence – Day 6 (Reminder)'
--      Day 10 → template 'Follow-up Sequence – Day 10 (Last Touch)'
--
-- 2. Each campaign: set status='draft', load the same recipient list.
--
-- 3. Use the admin scheduler (or cron) to start each campaign:
--      UPDATE mailing_campaigns SET status='running', started_at=NOW()
--      WHERE name='Follow-up Sequence – Day 1 (Initial)';
--    … and repeat for subsequent days.
--
-- 4. The PHP spintax() engine randomly resolves A at send time.
--    Every recipient gets a different variation → avoids pattern detection.
--
-- 5. Rotate SMTP accounts via mailing_smtp_accounts to spread load.
-- =============================================================
