<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title  = get_setting('page_title', 'VerlustRückholung – KI-gestützte Kapitalrückholung bei Anlagebetrug');
$modal_delay = max(5, (int) get_setting('modal_delay_seconds', '60'));
if (!defined('MIN_YEAR_LOST')) { define('MIN_YEAR_LOST', 2015); }
$years = [];
for ($y = date('Y'); $y >= MIN_YEAR_LOST; $y--) { $years[] = $y; }
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
<meta name="description" content="VerlustRückholung – Ihre Zeit läuft. Lassen Sie unsere KI sofort Ihren Fall analysieren. Kostenlos.">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
<style>
:root{--red:#dc2626;--red-dark:#991b1b;--gold:#f59e0b;--light-red:#fca5a5;--bg:#0d0505;--panel:rgba(220,38,38,0.06);}
body{background:var(--bg);color:#f5e0e0;font-family:'Inter',sans-serif;margin:0;}
/* Navbar */
.navbar{background:rgba(13,5,5,.97)!important;border-bottom:1px solid rgba(220,38,38,.25);backdrop-filter:blur(8px);}
.navbar-brand span{color:var(--red);}
.navbar-brand{color:#f5e0e0!important;font-weight:800!important;}
.nav-link{color:#c4a0a0!important;}
.nav-link:hover{color:var(--red)!important;}
/* Urgency Ticker */
.urgency-ticker{background:linear-gradient(135deg,var(--red-dark),#7f1d1d);padding:10px 0;overflow:hidden;white-space:nowrap;}
.urgency-inner{display:inline-block;animation:uscroll 20s linear infinite;color:#fca5a5;font-size:.85rem;font-weight:600;}
@keyframes uscroll{0%{transform:translateX(100vw);}100%{transform:translateX(-100%);}}
/* Hero */
#hero{position:relative;min-height:100vh;display:flex;align-items:center;overflow:hidden;padding-top:80px;}
#radarCanvas{position:absolute;inset:0;width:100%;height:100%;}
.hero-overlay{position:absolute;inset:0;background:linear-gradient(135deg,rgba(13,5,5,.94) 50%,rgba(13,5,5,.78));}
.hero-content{position:relative;z-index:2;}
/* Pulse Warning */
@keyframes pulse-warn{0%,100%{box-shadow:0 0 0 0 rgba(220,38,38,.5);}50%{box-shadow:0 0 0 12px rgba(220,38,38,0);}}
.warn-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(220,38,38,.15);border:1px solid rgba(220,38,38,.4);border-radius:4px;padding:6px 16px;color:var(--light-red);font-weight:700;font-size:.85rem;animation:pulse-warn 2s infinite;}
.hero-title{font-size:clamp(2rem,4vw,3.2rem);font-weight:800;line-height:1.15;}
.hero-title .accent{color:var(--red);text-shadow:0 0 20px rgba(220,38,38,.5);}
/* Stats Strip */
.stats-strip{background:rgba(220,38,38,.08);border-top:1px solid rgba(220,38,38,.2);border-bottom:1px solid rgba(220,38,38,.2);padding:20px 0;}
/* Form */
.form-red{background:rgba(220,38,38,.07);border:1px solid rgba(220,38,38,.25);border-radius:12px;padding:28px;backdrop-filter:blur(8px);}
.form-red .form-control,.form-red .form-select{background:rgba(255,255,255,.04);border:1px solid rgba(220,38,38,.2);color:#f5e0e0;}
.form-red .form-control:focus,.form-red .form-select:focus{background:rgba(220,38,38,.08);border-color:var(--red);box-shadow:0 0 0 2px rgba(220,38,38,.2);color:#f5e0e0;}
.form-red .form-select option{background:#1a0808;}
.form-red label{color:#c4a0a0;font-size:.8rem;text-transform:uppercase;letter-spacing:.05em;}
.btn-red{background:var(--red);color:#fff;font-weight:800;border:none;}
.btn-red:hover{background:var(--red-dark);color:#fff;}
.btn-gold{background:var(--gold);color:#1a0808;font-weight:800;border:none;}
.btn-gold:hover{background:#d97706;color:#1a0808;}
/* Warning Cards */
.warn-card{background:var(--panel);border:1px solid rgba(220,38,38,.2);border-left:3px solid var(--red);border-radius:8px;padding:20px;transition:border-color .3s,box-shadow .3s;}
.warn-card:hover{border-color:var(--red);box-shadow:0 0 16px rgba(220,38,38,.2);}
/* Process */
.process-num{width:44px;height:44px;border-radius:50%;background:var(--red);color:#fff;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
/* Testimonials */
.testi-card{background:rgba(220,38,38,.05);border:1px solid rgba(220,38,38,.15);border-radius:8px;padding:24px;}
/* CTA */
.cta-section{background:linear-gradient(135deg,rgba(220,38,38,.15),rgba(127,29,29,.1));border-top:1px solid rgba(220,38,38,.2);border-bottom:1px solid rgba(220,38,38,.2);}
/* Footer */
footer{background:#060202;border-top:1px solid rgba(220,38,38,.15);padding:36px 0;color:#7a4a4a;font-size:.85rem;}
footer a{color:#9a6a6a;text-decoration:none;}
footer a:hover{color:var(--red);}
/* Modal */
.modal-content{background:#1a0808;border:1px solid rgba(220,38,38,.3);color:#f5e0e0;}
.modal-header{border-bottom:1px solid rgba(220,38,38,.2);}
/* Accordion */
.accordion-item{background:var(--panel)!important;border:1px solid rgba(220,38,38,.15)!important;margin-bottom:6px;border-radius:6px!important;}
.accordion-button{background:transparent!important;color:#f5e0e0!important;font-weight:600;}
.accordion-button:not(.collapsed){color:var(--light-red)!important;box-shadow:none!important;}
.accordion-body{color:#c4a0a0;}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top">
  <div class="container">
    <a class="navbar-brand" href="#">⚖️ Verlust<span>Rückholung</span></a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav5">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav5">
      <ul class="navbar-nav ms-auto me-3">
        <li class="nav-item"><a class="nav-link" href="#fraud-warn">Betrugsarten</a></li>
        <li class="nav-item"><a class="nav-link" href="#process5">Ablauf</a></li>
        <li class="nav-item"><a class="nav-link" href="#testi5">Mandanten</a></li>
      </ul>
      <a href="#mainForm" class="btn btn-red px-4">⚡ Sofort prüfen</a>
    </div>
  </div>
</nav>

<!-- Urgency Ticker -->
<div class="urgency-ticker" style="margin-top:70px;">
  <div class="urgency-inner">
    ⚠️ 127 neue Betrugsopfer heute &nbsp;·&nbsp; ⏱ Jede Stunde zählt – Spuren verwischen sich &nbsp;·&nbsp; 🎯 Kostenlose Analyse starten &nbsp;·&nbsp; 🔴 Jetzt handeln – nicht warten &nbsp;·&nbsp; ⚡ 72h bis zur ersten Einschätzung &nbsp;·&nbsp;
  </div>
</div>

<!-- Hero -->
<section id="hero">
  <canvas id="radarCanvas"></canvas>
  <div class="hero-overlay"></div>
  <div class="container hero-content py-5">
    <div class="row align-items-center g-5">
      <div class="col-lg-6" data-aos="fade-right">
        <div class="mb-3">
          <span class="warn-badge"><i class="bi bi-exclamation-triangle-fill"></i>DRINGEND: Spuren verwischen sich täglich</span>
        </div>
        <h1 class="hero-title mb-4">Ihre Zeit läuft. <span class="accent">Wir handeln jetzt.</span></h1>
        <p class="lead mb-4" style="color:#c4a0a0;">Jeden Tag verlieren Betrugsopfer die Chance auf Rückforderung. Lassen Sie unsere KI sofort Ihren Fall analysieren – kostenlos und ohne Risiko.</p>
        <div class="d-flex flex-wrap gap-2 mb-4">
          <span style="background:rgba(220,38,38,.15);border:1px solid rgba(220,38,38,.3);color:var(--light-red);padding:5px 14px;border-radius:4px;font-size:.85rem;font-weight:600;">⚡ 72h Erstanalyse</span>
          <span style="background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.3);color:var(--gold);padding:5px 14px;border-radius:4px;font-size:.85rem;font-weight:600;">€48M+ zurückgeholt</span>
          <span style="background:rgba(220,38,38,.15);border:1px solid rgba(220,38,38,.3);color:var(--light-red);padding:5px 14px;border-radius:4px;font-size:.85rem;font-weight:600;">87% Erfolgsquote</span>
          <span style="background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.3);color:var(--gold);padding:5px 14px;border-radius:4px;font-size:.85rem;font-weight:600;">2.400+ Mandanten</span>
        </div>
        <a href="#mainForm" class="btn btn-red px-5 py-3 fw-bold me-2">
          <i class="bi bi-lightning-charge me-1"></i>Sofort analysieren lassen →
        </a>
      </div>
      <div class="col-lg-6" data-aos="fade-left">
        <div class="form-red" id="mainFormWrapper">
          <div class="d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-exclamation-triangle-fill" style="color:var(--red);font-size:1.2rem;"></i>
            <h5 class="fw-bold mb-0" style="color:var(--light-red);">Kostenlose Sofortanalyse</h5>
          </div>
          <form id="mainForm" action="../submit_lead.php" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="visit_id" data-visit-id value="">
            <div class="row g-3">
              <div class="col-6">
                <label class="form-label">Vorname</label>
                <input type="text" class="form-control" name="first_name" placeholder="Max" required>
                <div class="invalid-feedback">Pflichtfeld</div>
              </div>
              <div class="col-6">
                <label class="form-label">Nachname</label>
                <input type="text" class="form-control" name="last_name" placeholder="Mustermann" required>
                <div class="invalid-feedback">Pflichtfeld</div>
              </div>
              <div class="col-12">
                <label class="form-label">E-Mail-Adresse</label>
                <input type="email" class="form-control" name="email" placeholder="max@beispiel.de" required>
                <div class="invalid-feedback">Gültige E-Mail erforderlich</div>
              </div>
              <div class="col-12">
                <label class="form-label">Telefon (optional)</label>
                <input type="tel" class="form-control" name="phone" placeholder="+49 ...">
              </div>
              <div class="col-12">
                <label class="form-label">Land</label>
                <select class="form-select" name="country" required>
                  <option value="">-- Land wählen --</option>
                  <option>Deutschland</option><option>Österreich</option><option>Schweiz</option>
                  <option>Luxemburg</option><option>Liechtenstein</option><option>Sonstige</option>
                </select>
                <div class="invalid-feedback">Pflichtfeld</div>
              </div>
              <div class="col-12">
                <label class="form-label">Verlorener Betrag (€)</label>
                <input type="number" class="form-control" name="amount_lost" placeholder="z.B. 20000" min="1" required>
                <div class="invalid-feedback">Pflichtfeld</div>
              </div>
              <div class="col-12">
                <label class="form-label">Betrugsart</label>
                <select class="form-select" name="platform_category" required>
                  <option value="">-- Betrugsart wählen --</option>
                  <option>Krypto-Betrug</option><option>Forex-Betrug</option>
                  <option>Binäre Optionen</option><option>Investment-Betrug</option><option>Sonstige</option>
                </select>
                <div class="invalid-feedback">Pflichtfeld</div>
              </div>
              <div class="col-12">
                <label class="form-label">Fallbeschreibung</label>
                <textarea class="form-control" name="case_description" rows="3" placeholder="Was ist passiert? Je mehr Details, desto besser..." required></textarea>
                <div class="invalid-feedback">Pflichtfeld</div>
              </div>
              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="privacy" id="privacyMain5" required>
                  <label class="form-check-label" for="privacyMain5" style="color:#9a6a6a;font-size:.82rem;">
                    Ich stimme der <a href="#" style="color:var(--light-red);">Datenschutzerklärung</a> zu.*
                  </label>
                  <div class="invalid-feedback">Pflichtfeld</div>
                </div>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-red w-100 py-3 fw-bold" style="font-size:1rem;">
                  <i class="bi bi-lightning-charge me-2"></i>Jetzt Sofort Prüfen Lassen →
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Stats Strip -->
<div class="stats-strip">
  <div class="container">
    <div class="row text-center g-4">
      <div class="col-6 col-md-3"><div class="fs-2 fw-bold" style="color:var(--red);">127</div><div class="small" style="color:#c4a0a0;">Neue Fälle heute</div></div>
      <div class="col-6 col-md-3"><div class="fs-2 fw-bold" style="color:var(--gold);">€48M+</div><div class="small" style="color:#c4a0a0;">Zurückgeholt</div></div>
      <div class="col-6 col-md-3"><div class="fs-2 fw-bold" style="color:var(--red);">87%</div><div class="small" style="color:#c4a0a0;">Erfolgsquote</div></div>
      <div class="col-6 col-md-3"><div class="fs-2 fw-bold" style="color:var(--gold);">72h</div><div class="small" style="color:#c4a0a0;">Erstanalyse</div></div>
    </div>
  </div>
</div>

<!-- Fraud Types (Warning Cards) -->
<section id="fraud-warn" class="py-5">
  <div class="container">
    <h2 class="text-center fw-bold mb-2" data-aos="fade-up" style="color:var(--light-red);">⚠️ Erkannte Bedrohungen</h2>
    <p class="text-center mb-5" style="color:#c4a0a0;" data-aos="fade-up">Diese Betrugsarten bekämpfen wir täglich</p>
    <div class="row g-4">
      <?php $frauds=[['bi-currency-bitcoin','Krypto-Betrug','Fake-Exchanges, Rug Pulls, gefälschte Wallets und betrügerische DeFi-Projekte.','⚡ Hoch'],['bi-graph-up-arrow','Forex-Betrug','Unregulierte Broker mit manipulierten Handelsergebnissen und gesperrten Auszahlungen.','🔴 Kritisch'],['bi-bar-chart-line','Binäre Optionen','Plattformen, die trotz Gewinnen Auszahlungen verweigern und Konten einfrieren.','⚡ Hoch'],['bi-person-badge','Investment-Scams','Betrüger, die sich als seriöse Investmentberater tarnen – mit gestohlenen Lizenzen.','🔴 Kritisch']]; foreach($frauds as $f): ?>
      <div class="col-md-6 col-lg-3" data-aos="fade-up">
        <div class="warn-card h-100">
          <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi <?= $f[0] ?> fs-4" style="color:var(--red);"></i>
            <span class="small fw-bold" style="color:var(--gold);"><?= $f[3] ?></span>
          </div>
          <h6 class="fw-bold mb-2" style="color:var(--light-red);"><?= $f[1] ?></h6>
          <p class="small mb-0" style="color:#9a6a6a;"><?= $f[2] ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Process -->
<section id="process5" class="py-5" style="background:rgba(220,38,38,.04);">
  <div class="container" style="max-width:860px;">
    <h2 class="text-center fw-bold mb-5" data-aos="fade-up" style="color:var(--light-red);">Unser <span style="color:var(--gold);">Sofortplan</span></h2>
    <?php $steps=[['Sofortanalyse','Reichen Sie Ihren Fall ein. Unsere KI beginnt sofort mit der Analyse – keine Wartezeit.'],['Beweissicherung','Wir sichern digitale Beweise, bevor die Betrugsplattform Spuren verwischt.'],['Rechtliche Durchsetzung','Unsere Partneranwälte agieren schnell und entschlossen – national und international.'],['Kapitalrückholung','Wir treiben die Rückforderung bis zur erfolgreichen Auszahlung voran – ohne Vorauszahlung.']]; foreach($steps as $i=>$step): ?>
    <div class="d-flex gap-4 align-items-start mb-4" data-aos="fade-up">
      <div class="process-num"><?= $i+1 ?></div>
      <div class="warn-card flex-grow-1">
        <h6 class="fw-bold mb-1" style="color:var(--light-red);"><?= htmlspecialchars($step[0], ENT_QUOTES, 'UTF-8') ?></h6>
        <p class="small mb-0" style="color:#9a6a6a;"><?= htmlspecialchars($step[1], ENT_QUOTES, 'UTF-8') ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Testimonials -->
<section id="testi5" class="py-5">
  <div class="container">
    <h2 class="text-center fw-bold mb-5" data-aos="fade-up" style="color:var(--light-red);">Mandanten die <span style="color:var(--gold);">vertrauen</span></h2>
    <div class="row g-4">
      <?php $testi=[['R. Lange','Hamburg','Ich hatte die Hoffnung aufgegeben. Innerhalb von 3 Monaten hat VerlustRückholung €31.000 zurückgeholt. Unglaublich.'],['C. Weber','München','Die KI-Analyse war präzise und schnell. Das Team war jederzeit erreichbar. €19.500 zurück!'],['T. Klein','Frankfurt','Hätte ich früher gehandelt, wären noch mehr zurückgekommen. Aber €42.000 sind gerettet. Danke!']]; foreach($testi as $t): ?>
      <div class="col-md-4" data-aos="fade-up">
        <div class="testi-card h-100">
          <div class="mb-3" style="color:var(--gold);">★★★★★</div>
          <p class="mb-3 fst-italic" style="color:#c4a0a0;">"<?= htmlspecialchars($t[2], ENT_QUOTES, 'UTF-8') ?>"</p>
          <div class="fw-bold" style="color:var(--light-red);"><?= $t[0] ?></div>
          <div class="small" style="color:#7a4a4a;"><?= $t[1] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section py-5 text-center">
  <div class="container" data-aos="fade-up">
    <div class="warn-badge mb-3" style="display:inline-flex;"><i class="bi bi-clock-fill"></i>Jetzt handeln – nicht warten!</div>
    <h2 class="fw-bold mb-3" style="color:var(--light-red);">Jede Stunde zählt.</h2>
    <p class="mb-4" style="color:#c4a0a0;max-width:500px;margin:0 auto 1.5rem;">Betrugsplattformen löschen Spuren. Je früher Sie handeln, desto besser Ihre Chancen. Starten Sie jetzt – kostenlos.</p>
    <a href="#mainForm" class="btn btn-red px-5 py-3 fw-bold me-3">
      <i class="bi bi-lightning-charge me-1"></i>Sofort analysieren →
    </a>
    <a href="#mainForm" class="btn btn-gold px-5 py-3 fw-bold">Kostenlose Prüfung</a>
  </div>
</section>

<!-- Footer -->
<footer>
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-md-4"><div class="fw-bold mb-2" style="color:var(--light-red);">⚖️ VerlustRückholung</div><p class="small">KI-gestützte Kapitalrückholung. Sofort handeln. Keine Vorauszahlung.</p></div>
      <div class="col-md-4"><div class="fw-bold mb-2" style="color:#9a6a6a;">Navigation</div><div><a href="#fraud-warn">Betrugsarten</a> · <a href="#process5">Ablauf</a> · <a href="#testi5">Mandanten</a></div></div>
      <div class="col-md-4"><div class="fw-bold mb-2" style="color:#9a6a6a;">Kontakt</div><div>info@verlustrueckholung.de</div></div>
    </div>
    <hr style="border-color:rgba(220,38,38,.1);">
    <div class="text-center small">© <?= date('Y') ?> VerlustRückholung · <a href="#">Impressum</a> · <a href="#">Datenschutz</a></div>
  </div>
</footer>

<!-- Engagement Modal -->
<div class="modal fade" id="engModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" style="color:var(--light-red);"><i class="bi bi-exclamation-triangle-fill me-2"></i>Jetzt sofort Ihren Fall analysieren</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="warn-badge mb-3"><i class="bi bi-clock-fill"></i>Jede Stunde zählt – Spuren verwischen sich!</div>
        <form id="modalForm" action="../submit_lead.php" method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="visit_id" data-visit-id value="">
          <div class="row g-3">
            <div class="col-6"><input type="text" class="form-control bg-dark text-white border-secondary" name="first_name" placeholder="Vorname" required><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-6"><input type="text" class="form-control bg-dark text-white border-secondary" name="last_name" placeholder="Nachname" required><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-12"><input type="email" class="form-control bg-dark text-white border-secondary" name="email" placeholder="E-Mail" required><div class="invalid-feedback">Gültige E-Mail</div></div>
            <div class="col-12"><input type="tel" class="form-control bg-dark text-white border-secondary" name="phone" placeholder="Telefon (optional)"></div>
            <div class="col-6"><select class="form-select bg-dark text-white border-secondary" name="country" required><option value="">Land</option><option>Deutschland</option><option>Österreich</option><option>Schweiz</option><option>Sonstige</option></select><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-6"><input type="number" class="form-control bg-dark text-white border-secondary" name="amount_lost" placeholder="Betrag (€)" min="1" required><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-12"><select class="form-select bg-dark text-white border-secondary" name="platform_category" required><option value="">Betrugsart</option><option>Krypto-Betrug</option><option>Forex-Betrug</option><option>Binäre Optionen</option><option>Investment-Betrug</option><option>Sonstige</option></select><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-12"><textarea class="form-control bg-dark text-white border-secondary" name="case_description" rows="2" placeholder="Kurze Fallbeschreibung..." required></textarea><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="privacy" id="privacyModal5" required><label class="form-check-label small" for="privacyModal5" style="color:#9a6a6a;">Ich stimme der <a href="#" style="color:var(--light-red);">Datenschutzerklärung</a> zu.*</label><div class="invalid-feedback">Pflichtfeld</div></div></div>
            <div class="col-12"><button type="submit" class="btn btn-red w-100 py-3 fw-bold"><i class="bi bi-lightning-charge me-2"></i>Jetzt Sofort Prüfen Lassen →</button></div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({duration:700,once:true});

// Radar Canvas
(function(){
  var c=document.getElementById('radarCanvas');
  if(!c)return;
  var ctx=c.getContext('2d');
  var dots=[];
  var sweep=0;
  function resize(){c.width=c.offsetWidth;c.height=c.offsetHeight;}
  resize();
  window.addEventListener('resize',resize);
  function addDot(angle){
    var cx=c.width*.5,cy=c.height*.5;
    var maxR=Math.min(c.width,c.height)*.38;
    var r=(Math.random()*.85+.1)*maxR;
    dots.push({
      x:cx+Math.cos(angle+(Math.random()-.5)*.4)*r,
      y:cy+Math.sin(angle+(Math.random()-.5)*.4)*r,
      alpha:1,life:180+Math.floor(Math.random()*120)
    });
    if(dots.length>40)dots.shift();
  }
  function draw(){
    ctx.clearRect(0,0,c.width,c.height);
    var cx=c.width*.5,cy=c.height*.5;
    var maxR=Math.min(c.width,c.height)*.38;
    // Rings
    for(var r=1;r<=4;r++){
      ctx.beginPath();ctx.arc(cx,cy,(maxR/4)*r,0,Math.PI*2);
      ctx.strokeStyle='rgba(220,38,38,0.12)';ctx.lineWidth=1;ctx.stroke();
    }
    // Cross hairs
    ctx.strokeStyle='rgba(220,38,38,0.08)';ctx.lineWidth=.8;
    ctx.beginPath();ctx.moveTo(cx-maxR,cy);ctx.lineTo(cx+maxR,cy);ctx.stroke();
    ctx.beginPath();ctx.moveTo(cx,cy-maxR);ctx.lineTo(cx,cy+maxR);ctx.stroke();
    // Sweep gradient
    sweep+=0.018;
    var sweepGrad=ctx.createConicalGradient?null:null;
    ctx.save();
    ctx.translate(cx,cy);
    ctx.rotate(sweep);
    var grad=ctx.createLinearGradient(0,0,maxR,0);
    grad.addColorStop(0,'rgba(245,158,11,0.5)');
    grad.addColorStop(1,'rgba(245,158,11,0)');
    ctx.beginPath();ctx.moveTo(0,0);
    ctx.arc(0,0,maxR,-0.4,0.4);
    ctx.closePath();
    ctx.fillStyle=grad;ctx.fill();
    // Sweep arm
    ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(maxR,0);
    ctx.strokeStyle='rgba(245,158,11,0.8)';ctx.lineWidth=1.5;ctx.stroke();
    ctx.restore();
    // Maybe add dot
    if(Math.random()<.02)addDot(sweep);
    // Draw dots
    dots.forEach(function(d,i){
      d.life--;d.alpha=d.life/240;
      if(d.alpha<=0)return;
      ctx.beginPath();ctx.arc(d.x,d.y,4,0,Math.PI*2);
      ctx.fillStyle='rgba(220,38,38,'+d.alpha+')';ctx.fill();
      ctx.beginPath();ctx.arc(d.x,d.y,8,0,Math.PI*2);
      ctx.fillStyle='rgba(220,38,38,'+(d.alpha*.3)+')';ctx.fill();
    });
    dots=dots.filter(function(d){return d.life>0;});
    requestAnimationFrame(draw);
  }
  draw();
})();

// AJAX - Main Form
(function(){
  var form=document.getElementById('mainForm');
  if(!form)return;
  form.addEventListener('submit',function(e){
    e.preventDefault();
    if(!form.checkValidity()){form.classList.add('was-validated');return;}
    var btn=form.querySelector('[type="submit"]');var orig=btn?btn.innerHTML:'';
    if(btn){btn.disabled=true;btn.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Wird geprüft...';}
    var fd=new FormData(form);fd.append('_ajax','1');fd.append('_source','design5');
    fetch('../submit_lead.php',{method:'POST',body:fd})
      .then(function(r){return r.json();})
      .then(function(data){
        if(data.csrf_token){document.querySelectorAll('input[name="csrf_token"]').forEach(function(el){el.value=data.csrf_token;});}
        var al=document.createElement('div');
        al.className='alert '+(data.success?'alert-success':'alert-danger')+' mt-3';
        al.innerHTML=data.success?'<i class="bi bi-check-circle me-2"></i><strong>Vielen Dank!</strong> '+data.message:'<i class="bi bi-exclamation-triangle me-2"></i>'+(data.message||'Fehler.');
        form.parentNode.insertBefore(al,form);
        if(data.success)form.style.display='none';
        else if(btn){btn.disabled=false;btn.innerHTML=orig;}
      }).catch(function(){if(btn){btn.disabled=false;btn.innerHTML=orig;}});
  });
})();

// AJAX - Modal Form
(function(){
  var form=document.getElementById('modalForm');
  if(!form)return;
  form.addEventListener('submit',function(e){
    e.preventDefault();
    if(!form.checkValidity()){form.classList.add('was-validated');return;}
    var btn=form.querySelector('[type="submit"]');var orig=btn?btn.innerHTML:'';
    if(btn){btn.disabled=true;btn.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Wird geprüft...';}
    var fd=new FormData(form);fd.append('_ajax','1');fd.append('_source','design5-modal');
    fetch('../submit_lead.php',{method:'POST',body:fd})
      .then(function(r){return r.json();})
      .then(function(data){
        if(data.csrf_token){document.querySelectorAll('input[name="csrf_token"]').forEach(function(el){el.value=data.csrf_token;});}
        var al=document.createElement('div');
        al.className='alert '+(data.success?'alert-success':'alert-danger')+' mt-3';
        al.innerHTML=data.success?'<i class="bi bi-check-circle me-2"></i><strong>Vielen Dank!</strong> '+data.message:'<i class="bi bi-exclamation-triangle me-2"></i>'+(data.message||'Fehler.');
        form.parentNode.insertBefore(al,form);
        if(data.success)form.style.display='none';
        else if(btn){btn.disabled=false;btn.innerHTML=orig;}
      }).catch(function(){if(btn){btn.disabled=false;btn.innerHTML=orig;}});
  });
})();

// Engagement Modal
setTimeout(function(){
  var m=document.getElementById('engModal');
  if(m){new bootstrap.Modal(m).show();}
}, <?= (int)$modal_delay * 1000 ?>);
</script>

<!-- Visitor Tracking -->
<script>
(function() {
    'use strict';
    var visitId = null;
    var startTime = Date.now();

    (function logVisit() {
        var body = new FormData();
        body.append('action', 'visit');
        body.append('referrer', document.referrer || '');
        fetch('../track.php', { method: 'POST', body: body })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d && d.visit_id) {
                    visitId = d.visit_id;
                    document.querySelectorAll('[data-visit-id]').forEach(function(el) {
                        el.value = visitId;
                    });
                }
            }).catch(function() {});
    })();

    function sendTimeUpdate() {
        if (!visitId) return;
        var elapsed = Math.round((Date.now() - startTime) / 1000);
        var blob = new Blob(
            ['action=update&visit_id=' + encodeURIComponent(visitId) + '&time_on_site=' + encodeURIComponent(elapsed)],
            { type: 'application/x-www-form-urlencoded' }
        );
        if (navigator.sendBeacon) { navigator.sendBeacon('../track.php', blob); }
        else { fetch('../track.php', { method: 'POST', body: blob, keepalive: true }).catch(function() {}); }
    }
    window.addEventListener('pagehide', sendTimeUpdate);
    window.addEventListener('beforeunload', sendTimeUpdate);
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') { sendTimeUpdate(); }
    });
    setInterval(sendTimeUpdate, 30000);
})();
</script>
</body>
</html>
