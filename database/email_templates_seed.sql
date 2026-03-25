-- =============================================================
-- KryptoxPay – Email Template Seed
-- Follow-up sequence: Day 1 → Day 3 → Day 6 → Day 10
--
-- Features:
--   • Inbox-optimised, table-based HTML (renders in all clients)
--   • {A|B|C} spintax on subject lines AND body copy
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

  -- Subject spintax (one is picked randomly by spintax() at send time)
  '{Wichtige Information zu Ihren Krypto-Vermögenswerten|Haben Sie Kapital durch {{scam_platform}} verloren?|Kostenlose Erstberatung – KryptoxPay|Kurze Frage zu Ihrer digitalen Anlage}',

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
  body{margin:0;padding:0;background:#f2f4f7;font-family:''Helvetica Neue'',Helvetica,Arial,sans-serif}
  table{border-collapse:collapse}
  img{border:0;line-height:100%;outline:none;text-decoration:none}
  .wrapper{width:100%;background:#f2f4f7;padding:30px 0}
  .card{max-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)}
  .hd{background:#0d2744;padding:32px 40px;text-align:center}
  .hd-logo{font-size:24px;font-weight:700;color:#ffffff;text-decoration:none;display:block}
  .hd-logo span{color:#f0a500}
  .hd-tag{margin:6px 0 0;font-size:11px;color:#7fa8d4;letter-spacing:1px;text-transform:uppercase}
  .banner{background:#fff3cd;border-left:4px solid #f0a500;padding:14px 20px;margin:0}
  .banner p{margin:0;font-size:14px;color:#7a5c00}
  .bd{padding:36px 40px;color:#374151;font-size:15px;line-height:1.8}
  .bd h2{margin:0 0 16px;font-size:20px;color:#0d2744;font-weight:700}
  .bd p{margin:0 0 14px}
  .bd ul{padding-left:20px;margin:0 0 14px}
  .bd ul li{margin-bottom:6px}
  .divider{height:1px;background:#e8edf2;margin:20px 0}
  .cta{text-align:center;margin:26px 0}
  .btn{display:inline-block;background:#f0a500;color:#ffffff !important;padding:14px 36px;border-radius:6px;font-size:15px;font-weight:700;text-decoration:none}
  .ft{background:#f8f9fb;padding:20px 40px;text-align:center;font-size:12px;color:#6b7280}
  .ft a{color:#6b7280}
  @media only screen and (max-width:620px){
    .card{border-radius:0!important}
    .bd,.hd,.ft{padding:24px 20px!important}
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
    <p class="hd-tag">Asset Recovery &amp; Digital Finance</p>
  </td></tr>

  <!-- Alert banner (only when scam_platform is known) -->
  {{#if scam_platform}}
  <tr><td class="banner">
    <p>&#9888;&nbsp; {Wichtiger Hinweis|Achtung}: Wir haben Daten zu <strong>{{scam_platform}}</strong> erhalten.</p>
  </td></tr>
  {{/if}}

  <!-- Body -->
  <tr><td class="bd">
    <h2>Sehr geehrte/r {{name}},</h2>

    {{#if scam_platform}}
    <p>{wir wenden uns heute an Sie, da|unsere Analyse zeigt, dass} Anzeichen vorliegen,
    dass Sie über <strong>{{scam_platform}}</strong> einen finanziellen Schaden
    erlitten haben {könnten|möglicherweise haben}.</p>
    <p>Mit {modernster KI-Technologie|unserem spezialisierten KI-System}
    {unterstützen wir Betroffene dabei|helfen wir Ihnen},
    {verlorene Mittel zurückzuholen|Ihre Verluste zu analysieren und Rückforderungen einzuleiten}.</p>
    {{else}}
    <p>{wir wenden uns heute|wir melden uns heute} mit einer wichtigen Mitteilung
    an Sie, die {im Zusammenhang mit Ihren digitalen Vermögenswerten stehen könnte|für Ihre finanzielle Situation relevant sein könnte}.</p>
    <p>Bei KryptoxPay {begleiten|unterstützen} wir Anlegerinnen und Anleger dabei,
    ihre finanzielle Situation {transparent zu bewerten|genau zu analysieren}
    und {fundierte|informierte} Entscheidungen zu treffen.</p>
    {{/if}}

    <div class="divider"></div>

    <p><strong>Unsere {Leistungen|Services}:</strong></p>
    <ul>
      <li>{Unverbindliche und kostenlose|Kostenfreie} Erstberatung</li>
      <li>KI-gestützte Analyse Ihrer {individuellen Situation|persönlichen Lage}</li>
      <li>Transparente Kommunikation ohne versteckte Kosten</li>
      <li>Vertrauliche Bearbeitung {Ihres Anliegens|Ihrer Anfrage}</li>
    </ul>

    {{#if scam_platform}}
    <p>{Handeln Sie jetzt|Zögern Sie nicht} – je früher wir Ihren Fall prüfen können,
    desto besser {sind die Chancen auf eine Rückholung Ihrer Mittel|stehen die Aussichten für Sie}.</p>
    {{else}}
    <p>Wir {laden Sie herzlich ein|freuen uns darauf}, Sie auf Ihrem Weg zu begleiten.</p>
    {{/if}}

    <div class="cta">
      <a href="https://kryptoxpay.co.uk?utm_source=email&amp;utm_campaign=day1&amp;utm_medium=cta" class="btn">
        {Jetzt kostenlos beraten lassen|Kostenlose Beratung starten|Mehr erfahren}
      </a>
    </div>

    <div class="divider"></div>

    <p>Mit {freundlichen|herzlichen} Grüßen,<br>
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
{wir wenden uns heute an Sie, da|unsere Analyse zeigt, dass} Anzeichen vorliegen,
dass Sie über {{scam_platform}} einen finanziellen Schaden erlitten haben {könnten|möglicherweise haben}.

Mit {modernster KI-Technologie|unserem spezialisierten KI-System} {unterstützen wir
Betroffene dabei|helfen wir Ihnen}, {verlorene Mittel zurückzuholen|Ihre Verluste zu
analysieren und Rückforderungen einzuleiten}.
{{else}}
{wir wenden uns heute|wir melden uns heute} mit einer wichtigen Mitteilung an Sie,
die für Ihre finanzielle Situation relevant sein könnte.

Bei KryptoxPay {begleiten|unterstützen} wir Anlegerinnen und Anleger dabei,
ihre finanzielle Situation transparent zu bewerten und fundierte Entscheidungen zu treffen.
{{/if}}

Unsere {Leistungen|Services}:
- {Unverbindliche und kostenlose|Kostenfreie} Erstberatung
- KI-gestützte Analyse Ihrer {individuellen Situation|persönlichen Lage}
- Transparente Kommunikation ohne versteckte Kosten
- Vertrauliche Bearbeitung Ihrer Anfrage

{{#if scam_platform}}
{Handeln Sie jetzt|Zögern Sie nicht} – je früher wir Ihren Fall prüfen können,
desto besser {sind die Chancen auf eine Rückholung Ihrer Mittel|stehen die Aussichten für Sie}.
{{else}}
Wir {laden Sie herzlich ein|freuen uns darauf}, Sie auf Ihrem Weg zu begleiten.
{{/if}}

Mehr Informationen unter: https://kryptoxpay.co.uk

Mit {freundlichen|herzlichen} Grüßen,
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


-- -------------------------------------------------------------
-- DAY 3 – First follow-up
-- -------------------------------------------------------------
INSERT IGNORE INTO mailing_templates (name, subject, body_html, body_text)
SELECT
  'Follow-up Sequence – Day 3 (Follow-Up)',

  '{Kurze Nachfrage zu meiner letzten E-Mail|Haben Sie meine Nachricht erhalten?|Nur eine kurze Rückfrage, {{name}}|Noch eine Frage bezüglich Ihrer digitalen Anlage}',

  '<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>KryptoxPay</title>
<style>
  body,table,td,a{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}
  body{margin:0;padding:0;background:#f2f4f7;font-family:''Helvetica Neue'',Helvetica,Arial,sans-serif}
  table{border-collapse:collapse}
  .wrapper{width:100%;background:#f2f4f7;padding:30px 0}
  .card{max-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)}
  .hd{background:#0d2744;padding:32px 40px;text-align:center}
  .hd-logo{font-size:24px;font-weight:700;color:#ffffff;text-decoration:none;display:block}
  .hd-logo span{color:#f0a500}
  .hd-tag{margin:6px 0 0;font-size:11px;color:#7fa8d4;letter-spacing:1px;text-transform:uppercase}
  .bd{padding:36px 40px;color:#374151;font-size:15px;line-height:1.8}
  .bd h2{margin:0 0 16px;font-size:20px;color:#0d2744;font-weight:700}
  .bd p{margin:0 0 14px}
  .bd blockquote{margin:0 0 14px;padding:12px 20px;background:#f0f4f8;border-left:3px solid #0d2744;font-style:italic;color:#4b5563}
  .divider{height:1px;background:#e8edf2;margin:20px 0}
  .cta{text-align:center;margin:26px 0}
  .btn{display:inline-block;background:#f0a500;color:#ffffff !important;padding:14px 36px;border-radius:6px;font-size:15px;font-weight:700;text-decoration:none}
  .ft{background:#f8f9fb;padding:20px 40px;text-align:center;font-size:12px;color:#6b7280}
  .ft a{color:#6b7280}
  @media only screen and (max-width:620px){
    .card{border-radius:0!important}
    .bd,.hd,.ft{padding:24px 20px!important}
  }
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
    <h2>{Noch einmal, {{name}}|Kurze Nachfrage, {{name}}|Hallo {{name}}},</h2>

    <p>ich wollte {kurz nachfragen|mich kurz melden}, ob meine {letzte Nachricht|vorherige E-Mail}
    bei Ihnen angekommen ist.</p>

    {{#if scam_platform}}
    <p>Es geht um Ihre mögliche Erfahrung mit <strong>{{scam_platform}}</strong> –
    {wir möchten Ihnen helfen|wir stehen bereit}, Ihre Situation
    {kostenlos zu prüfen|unverbindlich zu analysieren}.</p>
    {{else}}
    <p>{Vielleicht|Möglicherweise} ist meine E-Mail in Ihrem Spam-Ordner gelandet –
    ich {wollte|möchte} sicherstellen, dass Sie die Informationen {erhalten haben|kennen}.</p>
    {{/if}}

    <div class="divider"></div>

    <blockquote>
      {„Unsere Erstberatung ist 100% kostenlos und unverbindlich. Viele unserer Klienten
      haben wertvolle Informationen erhalten, ohne auch nur einen Cent zu zahlen."|
      „KryptoxPay hat bereits zahlreichen Betroffenen geholfen, ihre Situation besser
      zu verstehen und konkrete Schritte einzuleiten."}
    </blockquote>

    <p>Falls Sie {Fragen haben|mehr erfahren möchten}, {antworten Sie einfach auf diese E-Mail|
    klicken Sie auf den Button unten} – ich {helfe gerne|stehe Ihnen zur Verfügung}.</p>

    <div class="cta">
      <a href="https://kryptoxpay.co.uk?utm_source=email&amp;utm_campaign=day3&amp;utm_medium=cta" class="btn">
        {Jetzt Beratung anfragen|Kostenlose Prüfung starten|Kontakt aufnehmen}
      </a>
    </div>

    <div class="divider"></div>

    <p>Mit {freundlichen|herzlichen} Grüßen,<br>
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

  '{Noch einmal, {{name}}|Kurze Nachfrage, {{name}}|Hallo {{name}}},

ich wollte {kurz nachfragen|mich kurz melden}, ob meine {letzte Nachricht|vorherige E-Mail}
bei Ihnen angekommen ist.

{{#if scam_platform}}
Es geht um Ihre mögliche Erfahrung mit {{scam_platform}} – {wir möchten Ihnen helfen|
wir stehen bereit}, Ihre Situation {kostenlos zu prüfen|unverbindlich zu analysieren}.
{{else}}
{Vielleicht|Möglicherweise} ist meine E-Mail in Ihrem Spam-Ordner gelandet –
ich {wollte|möchte} sicherstellen, dass Sie die Informationen {erhalten haben|kennen}.
{{/if}}

{„Unsere Erstberatung ist 100% kostenlos und unverbindlich. Viele unserer Klienten
haben wertvolle Informationen erhalten, ohne auch nur einen Cent zu zahlen."}

Falls Sie Fragen haben, {antworten Sie einfach auf diese E-Mail|besuchen Sie uns
unter https://kryptoxpay.co.uk}.

Mit {freundlichen|herzlichen} Grüßen,
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

  '{Erinnerung: Haben Sie bereits gehandelt?|Letzte Erinnerung von KryptoxPay|Noch immer offen: Ihr Fall bei {{scam_platform}}|Zeit läuft – Kostenlose Prüfung noch möglich}',

  '<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>KryptoxPay – Erinnerung</title>
<style>
  body,table,td,a{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}
  body{margin:0;padding:0;background:#f2f4f7;font-family:''Helvetica Neue'',Helvetica,Arial,sans-serif}
  table{border-collapse:collapse}
  .wrapper{width:100%;background:#f2f4f7;padding:30px 0}
  .card{max-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)}
  .hd{background:#7b1e1e;padding:32px 40px;text-align:center}
  .hd-logo{font-size:24px;font-weight:700;color:#ffffff;text-decoration:none;display:block}
  .hd-logo span{color:#f0a500}
  .hd-tag{margin:6px 0 0;font-size:11px;color:#e8b4b4;letter-spacing:1px;text-transform:uppercase}
  .urgency{background:#fde8e8;border-left:4px solid #c0392b;padding:16px 20px}
  .urgency p{margin:0;font-size:14px;color:#7b1e1e;font-weight:600}
  .bd{padding:36px 40px;color:#374151;font-size:15px;line-height:1.8}
  .bd h2{margin:0 0 16px;font-size:20px;color:#7b1e1e;font-weight:700}
  .bd p{margin:0 0 14px}
  .divider{height:1px;background:#e8edf2;margin:20px 0}
  .cta{text-align:center;margin:26px 0}
  .btn{display:inline-block;background:#c0392b;color:#ffffff !important;padding:14px 36px;border-radius:6px;font-size:15px;font-weight:700;text-decoration:none}
  .ft{background:#f8f9fb;padding:20px 40px;text-align:center;font-size:12px;color:#6b7280}
  .ft a{color:#6b7280}
  @media only screen and (max-width:620px){
    .card{border-radius:0!important}
    .bd,.hd,.ft{padding:24px 20px!important}
  }
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
    <p>&#8987;&nbsp; {Wichtige Erinnerung|Kurze Erinnerung}: Diese Möglichkeit besteht
    {nur noch begrenzte Zeit|nicht unbegrenzt}.</p>
  </td></tr>

  <tr><td class="bd">
    <h2>Sehr geehrte/r {{name}},</h2>

    <p>ich {melde mich|schreibe} ein {letztes|weiteres} Mal bezüglich
    {unserer Möglichkeit, Ihnen zu helfen|der Informationen, die ich Ihnen zugesandt habe}.</p>

    {{#if scam_platform}}
    <p>{Fälle wie Ihrer im Zusammenhang mit|Geschädigte durch} <strong>{{scam_platform}}</strong>
    {haben wir bereits erfolgreich begleitet|sind ein Schwerpunkt unserer Arbeit}.
    Die Erfolgswahrscheinlichkeit {sinkt jedoch|ist höher}, je {früher|zeitnah} gehandelt wird.</p>
    {{else}}
    <p>Die Möglichkeit einer kostenlosen und unverbindlichen Erstberatung
    {besteht weiterhin|steht Ihnen offen}. Viele Interessenten berichten,
    dass {ein erstes Gespräch|das erste Beratungsgespräch} bereits sehr {aufschlussreich|hilfreich} war.</p>
    {{/if}}

    <div class="divider"></div>

    <p>Was {passiert|geschieht}, wenn Sie {jetzt handeln|sich melden}:</p>
    <p>&#10003; {Kostenlose|Unverbindliche} Analyse Ihrer Situation<br>
       &#10003; Einschätzung der {Rückholchancen|Möglichkeiten}<br>
       &#10003; Klarer nächster Schritt ohne Verpflichtung</p>

    <div class="cta">
      <a href="https://kryptoxpay.co.uk?utm_source=email&amp;utm_campaign=day6&amp;utm_medium=cta" class="btn">
        {Jetzt handeln|Kostenlose Analyse anfordern|Fall prüfen lassen}
      </a>
    </div>

    <div class="divider"></div>

    <p>Mit {freundlichen|herzlichen} Grüßen,<br>
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

ich {melde mich|schreibe} ein {letztes|weiteres} Mal bezüglich
{unserer Möglichkeit, Ihnen zu helfen|der Informationen, die ich Ihnen zugesandt habe}.

{{#if scam_platform}}
{Fälle wie Ihrer im Zusammenhang mit|Geschädigte durch} {{scam_platform}} {haben wir
bereits erfolgreich begleitet|sind ein Schwerpunkt unserer Arbeit}.
Die Erfolgswahrscheinlichkeit {sinkt jedoch|ist höher}, je {früher|zeitnah} gehandelt wird.
{{else}}
Die Möglichkeit einer kostenlosen und unverbindlichen Erstberatung
{besteht weiterhin|steht Ihnen offen}.
{{/if}}

Was {passiert|geschieht}, wenn Sie {jetzt handeln|sich melden}:
✓ {Kostenlose|Unverbindliche} Analyse Ihrer Situation
✓ Einschätzung der {Rückholchancen|Möglichkeiten}
✓ Klarer nächster Schritt ohne Verpflichtung

Mehr Informationen unter: https://kryptoxpay.co.uk

Mit {freundlichen|herzlichen} Grüßen,
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

  '{Letzte Nachricht von KryptoxPay|Dies ist meine letzte E-Mail, {{name}}|Abschließende Information für Sie|Auf Wiedersehen – und eine letzte Chance}',

  '<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>KryptoxPay – Letzte Nachricht</title>
<style>
  body,table,td,a{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}
  body{margin:0;padding:0;background:#f2f4f7;font-family:''Helvetica Neue'',Helvetica,Arial,sans-serif}
  table{border-collapse:collapse}
  .wrapper{width:100%;background:#f2f4f7;padding:30px 0}
  .card{max-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)}
  .hd{background:#1a1a2e;padding:32px 40px;text-align:center}
  .hd-logo{font-size:24px;font-weight:700;color:#ffffff;text-decoration:none;display:block}
  .hd-logo span{color:#f0a500}
  .hd-tag{margin:6px 0 0;font-size:11px;color:#8888aa;letter-spacing:1px;text-transform:uppercase}
  .bd{padding:36px 40px;color:#374151;font-size:15px;line-height:1.8}
  .bd h2{margin:0 0 16px;font-size:20px;color:#1a1a2e;font-weight:700}
  .bd p{margin:0 0 14px}
  .divider{height:1px;background:#e8edf2;margin:20px 0}
  .cta{text-align:center;margin:26px 0}
  .btn{display:inline-block;background:#1a1a2e;color:#f0a500 !important;padding:14px 36px;border-radius:6px;font-size:15px;font-weight:700;text-decoration:none;border:2px solid #f0a500}
  .ps{background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:16px 20px;margin-top:20px}
  .ps p{margin:0;font-size:13px;color:#92400e}
  .ft{background:#f8f9fb;padding:20px 40px;text-align:center;font-size:12px;color:#6b7280}
  .ft a{color:#6b7280}
  @media only screen and (max-width:620px){
    .card{border-radius:0!important}
    .bd,.hd,.ft{padding:24px 20px!important}
  }
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

    <p>ich {werde mich nach dieser Nachricht|möchte nach dieser E-Mail} nicht mehr bei
    Ihnen {melden|in Verbindung setzen} – {das verspreche ich Ihnen|Sie haben mein Wort}.</p>

    <p>Aber {bevor ich das tue|bevor ich mich verabschiede}, {möchte ich|wollte ich}
    {noch einmal|ein letztes Mal} klar sein:</p>

    {{#if scam_platform}}
    <p>Wenn Sie {tatsächlich Kapital durch|wirklich Verluste über}
    <strong>{{scam_platform}}</strong> {erlitten haben|verloren haben},
    dann {haben Sie|stehen Ihnen} {möglicherweise rechtliche und praktische Möglichkeiten
    offen|Optionen zur Verfügung}, die Sie {noch nicht kennen|bisher nicht in Betracht
    gezogen haben}.</p>
    {{else}}
    <p>Wenn Sie sich {jemals fragen|eines Tages überlegen}, ob Ihre digitalen Anlagen
    {sicher sind|optimal verwaltet werden} – oder ob jemand Ihren Fall
    {kostenlos prüfen|unverbindlich analysieren} kann –
    dann {wissen Sie, wo Sie uns finden|sind wir für Sie da}.</p>
    {{/if}}

    <div class="cta">
      <a href="https://kryptoxpay.co.uk?utm_source=email&amp;utm_campaign=day10&amp;utm_medium=cta" class="btn">
        {Letzte Chance: Jetzt anfragen|Noch heute handeln|Kostenlose Prüfung – letzter Aufruf}
      </a>
    </div>

    <div class="divider"></div>

    <div class="ps">
      <p><strong>P.S.</strong> {Falls Sie sich doch noch entscheiden, uns zu kontaktieren –
      wir löschen Ihre Daten nach dieser letzten Nachricht nicht sofort. Sie können uns
      jederzeit unter info@kryptoxpay.co.uk erreichen.|
      Sie können uns jederzeit per E-Mail an info@kryptoxpay.co.uk kontaktieren,
      auch wenn Sie diese Serie nicht weiterverfolgen möchten.}</p>
    </div>

    <p style="margin-top:20px">Mit {freundlichen|herzlichen} Grüßen,<br>
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

ich {werde mich nach dieser Nachricht|möchte nach dieser E-Mail} nicht mehr bei
Ihnen {melden|in Verbindung setzen} – {das verspreche ich Ihnen|Sie haben mein Wort}.

Aber {bevor ich das tue|bevor ich mich verabschiede}, {möchte ich|wollte ich}
{noch einmal|ein letztes Mal} klar sein:

{{#if scam_platform}}
Wenn Sie {tatsächlich Kapital durch|wirklich Verluste über} {{scam_platform}}
{erlitten haben|verloren haben}, dann {haben Sie|stehen Ihnen}
{möglicherweise rechtliche und praktische Möglichkeiten offen|Optionen zur Verfügung},
die Sie {noch nicht kennen|bisher nicht in Betracht gezogen haben}.
{{else}}
Wenn Sie sich {jemals fragen|eines Tages überlegen}, ob Ihre digitalen Anlagen
{sicher sind|optimal verwaltet werden} – {wissen Sie, wo Sie uns finden|sind wir für Sie da}.
{{/if}}

P.S. Sie können uns jederzeit unter info@kryptoxpay.co.uk erreichen.

Mit {freundlichen|herzlichen} Grüßen,
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
-- 4. The PHP spintax() engine randomly resolves {A|B|C} at send time.
--    Every recipient gets a different variation → avoids pattern detection.
--
-- 5. Rotate SMTP accounts via mailing_smtp_accounts to spread load.
-- =============================================================
