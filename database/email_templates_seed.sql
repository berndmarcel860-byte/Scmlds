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
