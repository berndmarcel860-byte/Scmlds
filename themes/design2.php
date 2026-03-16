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
<meta name="description" content="VerlustRückholung – Rechtliche Präzision und KI-Forensik bei Kapitalverlust durch Anlagebetrug.">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
<style>
:root{--navy:#0a1628;--gold:#b8860b;--gold-lt:#d4a017;--silver:#e8e8e8;--steel:#4a90d9;--panel:rgba(255,255,255,0.04);}
body{background:var(--navy);color:var(--silver);font-family:'Inter',sans-serif;margin:0;font-weight:300;}
h1,h2,h3,h4,h5,h6,.fw-bold{font-weight:800!important;}
/* Navbar */
.navbar{background:rgba(5,10,20,0.97)!important;border-bottom:2px solid var(--gold);backdrop-filter:blur(8px);}
.navbar-brand{color:var(--gold)!important;font-weight:800!important;}
.navbar-brand span{color:var(--silver);}
.nav-link{color:#aaa!important;font-weight:500;letter-spacing:.02em;}
.nav-link:hover{color:var(--gold)!important;}
/* Hero */
#hero{position:relative;min-height:100vh;display:flex;align-items:center;overflow:hidden;padding-top:80px;}
#shieldCanvas{position:absolute;inset:0;width:100%;height:100%;opacity:.5;}
.hero-overlay{position:absolute;inset:0;background:linear-gradient(120deg,rgba(10,22,40,.95) 50%,rgba(10,22,40,.75));}
.hero-content{position:relative;z-index:2;}
.gold-rule{width:60px;height:3px;background:var(--gold);margin:16px 0;}
.hero-title{font-size:clamp(2rem,3.5vw,3rem);font-weight:800!important;line-height:1.2;}
.hero-title .accent{color:var(--gold);}
/* Trust Grid */
.trust-grid{background:rgba(0,0,0,.3);border-top:1px solid rgba(184,134,11,.2);border-bottom:1px solid rgba(184,134,11,.2);padding:28px 0;}
.trust-item{text-align:center;border-right:1px solid rgba(184,134,11,.15);padding:12px 24px;}
.trust-item:last-child{border-right:none;}
.trust-num{font-size:2rem;font-weight:800!important;color:var(--gold);}
/* Steps */
.step-line{width:2px;height:60px;background:linear-gradient(to bottom,var(--gold),transparent);margin:0 auto;}
.step-circle{width:48px;height:48px;border-radius:50%;border:2px solid var(--gold);display:flex;align-items:center;justify-content:center;color:var(--gold);font-weight:800!important;font-size:1.1rem;margin:0 auto;}
/* Cards */
.formal-card{background:var(--panel);border:1px solid rgba(184,134,11,.2);border-radius:4px;padding:28px;transition:border-color .3s;}
.formal-card:hover{border-color:var(--gold);}
/* Quote Cards */
.quote-card{background:rgba(0,0,0,.25);border-left:3px solid var(--gold);padding:24px;border-radius:0 8px 8px 0;}
/* Form */
.form-formal{background:rgba(0,0,0,.3);border:1px solid rgba(184,134,11,.3);border-radius:8px;padding:32px;}
.form-formal .form-control,.form-formal .form-select{background:rgba(255,255,255,.04);border:1px solid rgba(184,134,11,.25);color:var(--silver);}
.form-formal .form-control:focus,.form-formal .form-select:focus{background:rgba(184,134,11,.05);border-color:var(--gold);box-shadow:none;color:var(--silver);}
.form-formal .form-select option{background:#0d1a30;}
.form-formal label{color:#aaa;font-size:.82rem;text-transform:uppercase;letter-spacing:.08em;font-weight:600!important;}
.btn-gold{background:var(--gold);color:#fff;font-weight:700;border:none;}
.btn-gold:hover{background:var(--gold-lt);color:#fff;}
/* Footer */
footer{background:rgba(0,0,0,.4);border-top:1px solid rgba(184,134,11,.15);padding:36px 0;color:#555;font-size:.85rem;}
footer a{color:#888;text-decoration:none;}
footer a:hover{color:var(--gold);}
/* Modal */
.modal-content{background:#0d1628;border:1px solid rgba(184,134,11,.3);color:var(--silver);}
.modal-header{border-bottom:2px solid var(--gold);}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top">
  <div class="container">
    <a class="navbar-brand" href="#"><i class="bi bi-shield-fill-check me-2"></i>⚖️ Verlust<span>Rückholung</span></a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav2">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav2">
      <ul class="navbar-nav ms-auto me-3">
        <li class="nav-item"><a class="nav-link" href="#trust">Vertrauen</a></li>
        <li class="nav-item"><a class="nav-link" href="#steps">Ablauf</a></li>
        <li class="nav-item"><a class="nav-link" href="#testimonials">Mandanten</a></li>
        <li class="nav-item"><a class="nav-link" href="#contact-form">Kontakt</a></li>
      </ul>
      <a href="#contact-form" class="btn btn-gold px-4 rounded-1">Kostenlose Beratung</a>
    </div>
  </div>
</nav>

<!-- Hero -->
<section id="hero">
  <canvas id="shieldCanvas"></canvas>
  <div class="hero-overlay"></div>
  <div class="container hero-content py-5">
    <div class="row align-items-center g-5">
      <div class="col-lg-6" data-aos="fade-right">
        <div class="text-uppercase small fw-bold mb-2" style="color:var(--gold);letter-spacing:.15em;">
          <i class="bi bi-shield-fill-check me-1"></i>Lizenzierte Partneranwälte · 18+ Länder
        </div>
        <div class="gold-rule"></div>
        <h1 class="hero-title mb-4">Rechtliche Präzision. <span class="accent">Technologische Überlegenheit.</span></h1>
        <p class="lead mb-4" style="color:#aaa;font-weight:300;">Wenn Ihr Kapital durch betrügerische Plattformen verloren ging, vereinen wir Rechtsexpertise mit KI-Forensik für maximale Rückholchancen.</p>
        <div class="d-flex flex-wrap gap-3 mb-4">
          <div class="text-center px-4 py-2" style="border:1px solid rgba(184,134,11,.3);border-radius:4px;">
            <div style="color:var(--gold);font-size:1.4rem;font-weight:800!important;">18+</div><div class="small text-muted">Länder</div>
          </div>
          <div class="text-center px-4 py-2" style="border:1px solid rgba(184,134,11,.3);border-radius:4px;">
            <div style="color:var(--gold);font-size:1.4rem;font-weight:800!important;">€48M+</div><div class="small text-muted">Gesichert</div>
          </div>
          <div class="text-center px-4 py-2" style="border:1px solid rgba(184,134,11,.3);border-radius:4px;">
            <div style="color:var(--gold);font-size:1.4rem;font-weight:800!important;">87%</div><div class="small text-muted">Erfolg</div>
          </div>
          <div class="text-center px-4 py-2" style="border:1px solid rgba(184,134,11,.3);border-radius:4px;">
            <div style="color:var(--gold);font-size:1.4rem;font-weight:800!important;">2.400+</div><div class="small text-muted">Mandanten</div>
          </div>
        </div>
        <a href="#contact-form" class="btn btn-gold px-5 py-3">
          <i class="bi bi-send me-2"></i>Jetzt Mandat anfragen
        </a>
      </div>
      <div class="col-lg-6" data-aos="fade-left">
        <div class="formal-card">
          <h5 class="fw-bold mb-1" style="color:var(--gold);">Kostenlose Erstprüfung</h5>
          <div class="gold-rule mb-4" style="height:2px;"></div>
          <form id="mainForm" action="../submit_lead.php" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="visit_id" data-visit-id value="">
            <div class="row g-3">
              <div class="col-6">
                <label class="form-label">Ihr Vorname:</label>
                <input type="text" class="form-control" name="first_name" required>
                <div class="invalid-feedback">Pflichtfeld</div>
              </div>
              <div class="col-6">
                <label class="form-label">Ihr Nachname:</label>
                <input type="text" class="form-control" name="last_name" required>
                <div class="invalid-feedback">Pflichtfeld</div>
              </div>
              <div class="col-12">
                <label class="form-label">Ihre E-Mail:</label>
                <input type="email" class="form-control" name="email" required>
                <div class="invalid-feedback">Gültige E-Mail erforderlich</div>
              </div>
              <div class="col-12">
                <label class="form-label">Ihr Telefon (optional):</label>
                <input type="tel" class="form-control" name="phone">
              </div>
              <div class="col-12">
                <label class="form-label">Ihr Land:</label>
                <select class="form-select" name="country" required>
                  <option value="">-- Land wählen --</option>
                  <option>Deutschland</option><option>Österreich</option><option>Schweiz</option>
                  <option>Luxemburg</option><option>Liechtenstein</option><option>Sonstige</option>
                </select>
                <div class="invalid-feedback">Bitte Land wählen</div>
              </div>
              <div class="col-12">
                <label class="form-label">Verlorener Betrag (€):</label>
                <input type="number" class="form-control" name="amount_lost" min="1" required>
                <div class="invalid-feedback">Pflichtfeld</div>
              </div>
              <div class="col-12">
                <label class="form-label">Art des Betrugs:</label>
                <select class="form-select" name="platform_category" required>
                  <option value="">-- Betrugsart wählen --</option>
                  <option>Krypto-Betrug</option><option>Forex-Betrug</option>
                  <option>Binäre Optionen</option><option>Investment-Betrug</option><option>Sonstige</option>
                </select>
                <div class="invalid-feedback">Pflichtfeld</div>
              </div>
              <div class="col-12">
                <label class="form-label">Ihre Fallbeschreibung:</label>
                <textarea class="form-control" name="case_description" rows="3" required></textarea>
                <div class="invalid-feedback">Pflichtfeld</div>
              </div>
              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="privacy" id="privacyMain2" required>
                  <label class="form-check-label small" for="privacyMain2" style="color:#888;">
                    Ich stimme der <a href="#" style="color:var(--gold);">Datenschutzerklärung</a> zu.*
                  </label>
                  <div class="invalid-feedback">Bitte zustimmen</div>
                </div>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-gold w-100 py-3 fw-bold">
                  <i class="bi bi-shield-check me-2"></i>Mandat anfragen →
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Trust Grid -->
<div class="trust-grid" id="trust">
  <div class="container">
    <div class="row">
      <?php $trust=[['bi-award','Lizenzierte Partner','Zusammenarbeit mit zugelassenen Anwälten'],['bi-globe','18+ Länder','Internationale Reichweite & Vollstreckung'],['bi-shield-lock','DSGVO-konform','Höchste Datenschutzstandards'],['bi-patch-check','Ohne Vorauszahlung','Erfolgsbasierte Vergütung']];foreach($trust as $t): ?>
      <div class="col-6 col-md-3 trust-item" data-aos="fade-up">
        <i class="bi <?= $t[0] ?> fs-2 mb-2" style="color:var(--gold);display:block;"></i>
        <div class="fw-bold"><?= $t[1] ?></div>
        <div class="small text-muted"><?= $t[2] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Steps -->
<section id="steps" class="py-5">
  <div class="container" style="max-width:900px;">
    <h2 class="text-center fw-bold mb-2" data-aos="fade-up">Unser <span style="color:var(--gold);">Mandatsprozess</span></h2>
    <div class="gold-rule mx-auto mb-5" style="width:80px;"></div>
    <?php $steps=[['Fallaufnahme & KI-Analyse','Sie schildern uns Ihren Fall. Unsere KI analysiert Transaktionsdaten und identifiziert Betrugsmuster innerhalb von 72 Stunden.'],['Rechtliche Bewertung','Unsere Partneranwälte prüfen die Rechtsgrundlagen und erarbeiten eine individuelle Rückholstrategie.'],['Durchsetzung','Wir verfolgen die Rückforderung auf rechtlichem Weg – national und international – bis zur erfolgreichen Rückholung.']]; foreach($steps as $i=>$step): ?>
    <div class="row align-items-center mb-4" data-aos="fade-up">
      <div class="col-auto text-center">
        <div class="step-circle"><?= $i+1 ?></div>
        <?php if($i<count($steps)-1): ?><div class="step-line mt-2"></div><?php endif; ?>
      </div>
      <div class="col">
        <div class="formal-card ms-3">
          <h6 class="fw-bold mb-1" style="color:var(--gold);"><?= htmlspecialchars($step[0], ENT_QUOTES, 'UTF-8') ?></h6>
          <p class="text-muted small mb-0"><?= htmlspecialchars($step[1], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Testimonials -->
<section id="testimonials" class="py-5" style="background:rgba(0,0,0,.2);">
  <div class="container">
    <h2 class="text-center fw-bold mb-5" data-aos="fade-up">Stimmen unserer <span style="color:var(--gold);">Mandanten</span></h2>
    <div class="row g-4">
      <?php $testi=[['K. Hoffmann','Berlin','€27.000 zurückgeholt – ich hatte die Hoffnung bereits aufgegeben. Das Team war jederzeit professionell und transparent.'],['M. Bauer','Wien','Nach 14 Monaten Kampf mit einer Fake-Krypto-Plattform hat VerlustRückholung den Fall in 4 Monaten gelöst.'],['S. Fischer','Zürich','Seriös, kompetent und erfolgreich. Meine €45.000 sind zurück. Absolute Empfehlung.']]; foreach($testi as $t): ?>
      <div class="col-md-4" data-aos="fade-up">
        <div class="quote-card h-100">
          <p class="mb-3 fst-italic text-muted">"<?= htmlspecialchars($t[2], ENT_QUOTES, 'UTF-8') ?>"</p>
          <div class="fw-bold" style="color:var(--gold);"><?= $t[0] ?></div>
          <div class="small text-muted"><?= $t[1] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Contact Form Section -->
<section id="contact-form" class="py-5">
  <div class="container" style="max-width:640px;">
    <h2 class="text-center fw-bold mb-2" data-aos="fade-up">Mandat <span style="color:var(--gold);">anfragen</span></h2>
    <div class="gold-rule mx-auto mb-5" style="width:80px;"></div>
    <div class="form-formal" data-aos="fade-up">
      <form id="contactForm2" action="../submit_lead.php" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="visit_id" data-visit-id value="">
        <div class="row g-3">
          <div class="col-6"><label class="form-label">Ihr Vorname:</label><input type="text" class="form-control" name="first_name" required><div class="invalid-feedback">Pflichtfeld</div></div>
          <div class="col-6"><label class="form-label">Ihr Nachname:</label><input type="text" class="form-control" name="last_name" required><div class="invalid-feedback">Pflichtfeld</div></div>
          <div class="col-12"><label class="form-label">Ihre E-Mail:</label><input type="email" class="form-control" name="email" required><div class="invalid-feedback">Gültige E-Mail</div></div>
          <div class="col-12"><label class="form-label">Ihr Telefon (optional):</label><input type="tel" class="form-control" name="phone"></div>
          <div class="col-6"><label class="form-label">Ihr Land:</label><select class="form-select" name="country" required><option value="">-- Land --</option><option>Deutschland</option><option>Österreich</option><option>Schweiz</option><option>Sonstige</option></select><div class="invalid-feedback">Pflichtfeld</div></div>
          <div class="col-6"><label class="form-label">Betrag (€):</label><input type="number" class="form-control" name="amount_lost" min="1" required><div class="invalid-feedback">Pflichtfeld</div></div>
          <div class="col-12"><label class="form-label">Art des Betrugs:</label><select class="form-select" name="platform_category" required><option value="">-- Betrugsart --</option><option>Krypto-Betrug</option><option>Forex-Betrug</option><option>Binäre Optionen</option><option>Investment-Betrug</option><option>Sonstige</option></select><div class="invalid-feedback">Pflichtfeld</div></div>
          <div class="col-12"><label class="form-label">Ihre Fallbeschreibung:</label><textarea class="form-control" name="case_description" rows="4" required></textarea><div class="invalid-feedback">Pflichtfeld</div></div>
          <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="privacy" id="privacyCF2" required><label class="form-check-label small" for="privacyCF2" style="color:#888;">Ich stimme der <a href="#" style="color:var(--gold);">Datenschutzerklärung</a> zu.*</label><div class="invalid-feedback">Pflichtfeld</div></div></div>
          <div class="col-12"><button type="submit" class="btn btn-gold w-100 py-3 fw-bold"><i class="bi bi-send me-2"></i>Mandat einreichen →</button></div>
        </div>
      </form>
    </div>
  </div>
</section>

<!-- Footer -->
<footer>
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-md-4"><div class="fw-bold" style="color:var(--gold);">⚖️ VerlustRückholung</div><p class="small mt-2">Rechtliche Präzision. Technologische Überlegenheit. Ohne Vorauszahlung.</p></div>
      <div class="col-md-4"><div class="fw-bold text-white mb-2">Navigation</div><div><a href="#trust">Vertrauen</a> · <a href="#steps">Ablauf</a> · <a href="#testimonials">Mandanten</a></div></div>
      <div class="col-md-4"><div class="fw-bold text-white mb-2">Kontakt</div><div>info@verlustrueckholung.de</div></div>
    </div>
    <hr style="border-color:rgba(184,134,11,.1);">
    <div class="text-center small">© <?= date('Y') ?> VerlustRückholung · <a href="#">Impressum</a> · <a href="#">Datenschutz</a></div>
  </div>
</footer>

<!-- Engagement Modal -->
<div class="modal fade" id="engModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" style="color:var(--gold);"><i class="bi bi-shield-fill-check me-2"></i>Kostenlose Erstberatung anfragen</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-4">Unsere lizenzierten Partner und KI-Forensiker prüfen Ihren Fall kostenlos und unverbindlich.</p>
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
            <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="privacy" id="privacyModal2" required><label class="form-check-label small text-muted" for="privacyModal2">Ich stimme der <a href="#" style="color:var(--gold);">Datenschutzerklärung</a> zu.*</label><div class="invalid-feedback">Pflichtfeld</div></div></div>
            <div class="col-12"><button type="submit" class="btn btn-gold w-100 py-3 fw-bold"><i class="bi bi-shield-check me-2"></i>Jetzt Mandat anfragen →</button></div>
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

// Shield Particle Canvas
(function(){
  var c=document.getElementById('shieldCanvas');
  if(!c)return;
  var ctx=c.getContext('2d');
  var dots=[];
  function resize(){c.width=c.offsetWidth;c.height=c.offsetHeight;initDots();}
  function initDots(){
    dots=[];
    var N=50;
    for(var i=0;i<N;i++){
      dots.push({
        angle:Math.random()*Math.PI*2,
        speed:(Math.random()*.4+.1)*(Math.random()>.5?1:-1),
        rx:80+Math.random()*200,
        ry:50+Math.random()*120,
        cx:c.width*.5+((Math.random()-.5)*80),
        cy:c.height*.5+((Math.random()-.5)*40),
        size:Math.random()*3+1,
        alpha:Math.random()*.7+.3
      });
    }
  }
  resize();
  window.addEventListener('resize',resize);
  function draw(){
    ctx.clearRect(0,0,c.width,c.height);
    // Shield outline
    var sx=c.width*.5,sy=c.height*.45,sw=120,sh=160;
    ctx.beginPath();
    ctx.moveTo(sx,sy-sh*.5);
    ctx.bezierCurveTo(sx+sw*.6,sy-sh*.5,sx+sw*.6,sy+sh*.1,sx,sy+sh*.5);
    ctx.bezierCurveTo(sx-sw*.6,sy+sh*.1,sx-sw*.6,sy-sh*.5,sx,sy-sh*.5);
    ctx.strokeStyle='rgba(184,134,11,0.25)';ctx.lineWidth=1.5;ctx.stroke();
    // Dots
    dots.forEach(function(d){
      d.angle+=d.speed*.01;
      var x=d.cx+Math.cos(d.angle)*d.rx;
      var y=d.cy+Math.sin(d.angle)*d.ry;
      ctx.beginPath();ctx.arc(x,y,d.size,0,Math.PI*2);
      ctx.fillStyle='rgba(184,134,11,'+d.alpha+')';ctx.fill();
      // Trail
      ctx.beginPath();
      ctx.arc(d.cx+Math.cos(d.angle-.15)*d.rx,d.cy+Math.sin(d.angle-.15)*d.ry,d.size*.5,0,Math.PI*2);
      ctx.fillStyle='rgba(74,144,217,0.3)';ctx.fill();
    });
    requestAnimationFrame(draw);
  }
  draw();
})();

// AJAX - Main Form
(function(){
  ['mainForm','contactForm2'].forEach(function(id){
    var form=document.getElementById(id);
    if(!form)return;
    form.addEventListener('submit',function(e){
      e.preventDefault();
      if(!form.checkValidity()){form.classList.add('was-validated');return;}
      var btn=form.querySelector('[type="submit"]');var orig=btn?btn.innerHTML:'';
      if(btn){btn.disabled=true;btn.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Wird geprüft...';}
      var fd=new FormData(form);fd.append('_ajax','1');fd.append('_source','design2');
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
    var fd=new FormData(form);fd.append('_ajax','1');fd.append('_source','design2-modal');
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
