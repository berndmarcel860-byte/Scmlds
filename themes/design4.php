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
<meta name="description" content="VerlustRückholung – Professionelle Kapitalrückholung. Keine Vorauszahlung. Keine versteckten Kosten. Kostenlose Erstprüfung.">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
<style>
:root{--blue:#1e3a8a;--amber:#f59e0b;--text:#111827;--light:#f1f5f9;--white:#ffffff;}
body{background:var(--white);color:var(--text);font-family:'Inter',sans-serif;margin:0;}
/* Navbar */
.navbar{background:var(--white)!important;border-bottom:1px solid #e5e7eb;box-shadow:0 1px 8px rgba(0,0,0,.06);}
.navbar-brand span{color:var(--amber);}
.navbar-brand{color:var(--blue)!important;font-weight:800!important;}
.nav-link{color:#4b5563!important;font-weight:500;}
.nav-link:hover{color:var(--blue)!important;}
/* Hero */
#hero{background:linear-gradient(135deg,#f0f4ff 0%,var(--white) 60%);padding-top:80px;min-height:100vh;display:flex;align-items:center;}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:999px;padding:6px 16px;color:var(--blue);font-size:.85rem;font-weight:600;}
.hero-title{font-size:clamp(2rem,4vw,3.2rem);font-weight:800;color:var(--text);line-height:1.15;}
.hero-title .accent{color:var(--blue);}
/* Stat Boxes */
.stat-box{background:var(--white);border-radius:12px;padding:20px 24px;box-shadow:0 2px 12px rgba(0,0,0,.08);text-align:center;border-top:3px solid var(--blue);}
.stat-box.amber{border-top-color:var(--amber);}
.stat-num{font-size:1.8rem;font-weight:800;color:var(--blue);}
.stat-box.amber .stat-num{color:var(--amber);}
/* Chart Canvas */
#chartCanvas{border-radius:12px;background:linear-gradient(to bottom,#f8faff,#fff);box-shadow:0 4px 20px rgba(30,58,138,.1);}
/* Process Steps */
.process-step{display:flex;gap:20px;align-items:flex-start;padding:24px;background:var(--white);border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:16px;transition:box-shadow .3s;}
.process-step:hover{box-shadow:0 4px 20px rgba(30,58,138,.12);}
.step-num{width:48px;height:48px;border-radius:50%;background:var(--blue);color:#fff;font-weight:800;font-size:1.1rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.step-num.amber{background:var(--amber);}
/* Feature Cards */
.feature-card{background:var(--white);border:1px solid #e5e7eb;border-radius:12px;padding:28px;transition:box-shadow .3s,border-color .3s;}
.feature-card:hover{box-shadow:0 8px 24px rgba(30,58,138,.1);border-color:#bfdbfe;}
.feature-icon{width:52px;height:52px;background:#eff6ff;border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--blue);font-size:1.4rem;margin-bottom:16px;}
/* Trust Badges */
.trust-bar{background:var(--light);border-top:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb;padding:20px 0;}
.trust-badge{display:flex;align-items:center;gap:10px;color:#374151;font-size:.9rem;font-weight:500;}
.trust-badge i{color:var(--blue);font-size:1.2rem;}
/* Form */
.form-card{background:var(--white);border-radius:16px;box-shadow:0 8px 40px rgba(30,58,138,.12);overflow:hidden;}
.form-card-header{background:linear-gradient(135deg,var(--blue),#2d4db0);padding:24px 28px;color:#fff;}
.form-card-body{padding:28px;}
.form-card .form-control,.form-card .form-select{border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px;color:var(--text);}
.form-card .form-control:focus,.form-card .form-select:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(30,58,138,.1);}
.btn-blue{background:var(--blue);color:#fff;font-weight:700;border:none;border-radius:8px;}
.btn-blue:hover{background:#1e40af;color:#fff;}
.btn-amber{background:var(--amber);color:#fff;font-weight:700;border:none;border-radius:8px;}
.btn-amber:hover{background:#d97706;color:#fff;}
/* FAQ */
.accordion-item{border:1px solid #e5e7eb!important;border-radius:10px!important;margin-bottom:8px;overflow:hidden;}
.accordion-button{background:var(--white)!important;color:var(--text)!important;font-weight:600;}
.accordion-button:not(.collapsed){color:var(--blue)!important;background:#eff6ff!important;box-shadow:none!important;}
/* Footer */
footer{background:#0f172a;color:#94a3b8;padding:40px 0;font-size:.875rem;}
footer a{color:#64748b;text-decoration:none;}
footer a:hover{color:var(--amber);}
.footer-brand{color:#fff;font-weight:800;font-size:1.1rem;}
/* Modal */
.modal-content{background:#fff;color:var(--text);}
.modal-header{background:linear-gradient(135deg,var(--blue),#2d4db0);color:#fff;border:none;}
.modal-header .btn-close{filter:brightness(0) invert(1);}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top">
  <div class="container">
    <a class="navbar-brand" href="#">⚖️ Verlust<span>Rückholung</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav4">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav4">
      <ul class="navbar-nav ms-auto me-3">
        <li class="nav-item"><a class="nav-link" href="#process">Prozess</a></li>
        <li class="nav-item"><a class="nav-link" href="#features">Leistungen</a></li>
        <li class="nav-item"><a class="nav-link" href="#trust">Vertrauen</a></li>
        <li class="nav-item"><a class="nav-link" href="#faq4">FAQ</a></li>
      </ul>
      <a href="#form-section" class="btn btn-blue px-4">Kostenlos prüfen</a>
    </div>
  </div>
</nav>

<!-- Hero -->
<section id="hero">
  <div class="container py-5">
    <div class="row align-items-center g-5">
      <div class="col-lg-6" data-aos="fade-right">
        <div class="hero-badge mb-4">
          <i class="bi bi-check-circle-fill text-success"></i>
          Keine Vorauszahlung · Kostenlose Erstprüfung
        </div>
        <h1 class="hero-title mb-4">Professionelle <span class="accent">Kapitalrückholung.</span> Ohne Risiko.</h1>
        <p class="lead mb-4" style="color:#6b7280;">Keine Vorauszahlung. Keine versteckten Kosten. Nur Ergebnisse. Wir prüfen Ihren Fall kostenlos und arbeiten ausschließlich auf Erfolgsbasis.</p>
        <div class="row g-3 mb-4">
          <div class="col-6 col-md-3"><div class="stat-box"><div class="stat-num">87%</div><div class="small text-muted">Erfolgsquote</div></div></div>
          <div class="col-6 col-md-3"><div class="stat-box amber"><div class="stat-num">€48M+</div><div class="small text-muted">Zurückgeholt</div></div></div>
          <div class="col-6 col-md-3"><div class="stat-box"><div class="stat-num">48h</div><div class="small text-muted">Erstantwort</div></div></div>
          <div class="col-6 col-md-3"><div class="stat-box amber"><div class="stat-num">2.4k+</div><div class="small text-muted">Mandanten</div></div></div>
        </div>
        <a href="#form-section" class="btn btn-blue px-5 py-3 me-2">
          <i class="bi bi-arrow-right-circle me-2"></i>Jetzt kostenlos starten
        </a>
        <a href="#process" class="btn btn-outline-secondary px-4 py-3">Mehr erfahren</a>
      </div>
      <div class="col-lg-6" data-aos="fade-left">
        <canvas id="chartCanvas" width="480" height="300" style="width:100%;max-width:480px;"></canvas>
      </div>
    </div>
  </div>
</section>

<!-- Trust Bar -->
<div class="trust-bar" id="trust">
  <div class="container">
    <div class="row g-3 justify-content-center text-center">
      <?php $badges=[['bi-lock-fill','SSL-verschlüsselt'],['bi-shield-fill-check','DSGVO-konform'],['bi-award-fill','Lizenzierte Partner'],['bi-currency-euro','Erfolgsbasis'],['bi-globe','18+ Länder']]; foreach($badges as $b): ?>
      <div class="col-6 col-md-auto px-4">
        <div class="trust-badge justify-content-center">
          <i class="bi <?= $b[0] ?>"></i><span><?= $b[1] ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Process -->
<section id="process" class="py-5" style="background:var(--light);">
  <div class="container" style="max-width:860px;">
    <h2 class="text-center fw-bold mb-2" data-aos="fade-up">So funktioniert <span style="color:var(--blue);">unser Prozess</span></h2>
    <p class="text-center text-muted mb-5" data-aos="fade-up">Vier einfache Schritte zur Kapitalrückholung</p>
    <?php $steps=[['Fall einreichen','Schildern Sie uns Ihren Fall kostenlos und unverbindlich – per Formular in unter 5 Minuten.',false],['KI-Analyse','Unsere KI durchsucht Transaktionsdaten und Betrugsmuster. Ergebnis in 48 Stunden.',true],['Strategie entwickeln','Unsere Partneranwälte erarbeiten eine maßgeschneiderte Rückholstrategie.',false],['Rückholung','Wir setzen Ihre Forderung durch – national und international – ohne Vorauszahlung.',true]]; foreach($steps as $i=>$step): ?>
    <div class="process-step" data-aos="fade-up">
      <div class="step-num <?= $step[2]?'amber':'' ?>"><?= $i+1 ?></div>
      <div>
        <h6 class="fw-bold mb-1"><?= htmlspecialchars($step[0], ENT_QUOTES, 'UTF-8') ?></h6>
        <p class="text-muted small mb-0"><?= htmlspecialchars($step[1], ENT_QUOTES, 'UTF-8') ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Features -->
<section id="features" class="py-5">
  <div class="container">
    <h2 class="text-center fw-bold mb-5" data-aos="fade-up">Unsere <span style="color:var(--blue);">Leistungen</span></h2>
    <div class="row g-4">
      <?php $features=[['bi-cpu','KI-Forensik','Automatisierte Analyse von Blockchain-Transaktionen und Betrugsnetzwerken.'],['bi-shield-lock','Rechtliche Durchsetzung','Durchsetzung Ihrer Ansprüche mit lizenzierten Partneranwälten.'],['bi-graph-up-arrow','Transparentes Reporting','Sie erhalten regelmäßige Updates zum Status Ihres Falls.']]; foreach($features as $f): ?>
      <div class="col-md-4" data-aos="fade-up">
        <div class="feature-card h-100">
          <div class="feature-icon"><i class="bi <?= $f[0] ?>"></i></div>
          <h5 class="fw-bold mb-2"><?= $f[1] ?></h5>
          <p class="text-muted"><?= $f[2] ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Form Section -->
<section id="form-section" class="py-5" style="background:var(--light);">
  <div class="container" style="max-width:600px;">
    <div class="form-card" data-aos="fade-up">
      <div class="form-card-header">
        <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-check me-2"></i>Kostenlose Fallprüfung</h4>
        <p class="mb-0 opacity-75 small">Keine Vorauszahlung · Antwort in 48 Stunden</p>
      </div>
      <div class="form-card-body">
        <form id="mainForm" action="../submit_lead.php" method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="visit_id" data-visit-id value="">
          <div class="row g-3">
            <div class="col-6"><label class="form-label fw-medium">Vorname</label><input type="text" class="form-control" name="first_name" required><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-6"><label class="form-label fw-medium">Nachname</label><input type="text" class="form-control" name="last_name" required><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-12"><label class="form-label fw-medium">E-Mail-Adresse</label><input type="email" class="form-control" name="email" required><div class="invalid-feedback">Gültige E-Mail erforderlich</div></div>
            <div class="col-12"><label class="form-label fw-medium">Telefon <span class="text-muted">(optional)</span></label><input type="tel" class="form-control" name="phone"></div>
            <div class="col-12"><label class="form-label fw-medium">Land</label><select class="form-select" name="country" required><option value="">-- Land wählen --</option><option>Deutschland</option><option>Österreich</option><option>Schweiz</option><option>Luxemburg</option><option>Liechtenstein</option><option>Sonstige</option></select><div class="invalid-feedback">Bitte Land wählen</div></div>
            <div class="col-12"><label class="form-label fw-medium">Verlorener Betrag (€)</label><input type="number" class="form-control" name="amount_lost" min="1" placeholder="z.B. 15000" required><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-12"><label class="form-label fw-medium">Art des Betrugs</label><select class="form-select" name="platform_category" required><option value="">-- Betrugsart wählen --</option><option>Krypto-Betrug</option><option>Forex-Betrug</option><option>Binäre Optionen</option><option>Investment-Betrug</option><option>Phishing</option><option>Sonstige</option></select><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-12"><label class="form-label fw-medium">Fallbeschreibung</label><textarea class="form-control" name="case_description" rows="4" placeholder="Beschreiben Sie kurz, was passiert ist..." required></textarea><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="privacy" id="privacyMain4" required><label class="form-check-label small text-muted" for="privacyMain4">Ich stimme der <a href="#" style="color:var(--blue);">Datenschutzerklärung</a> zu.*</label><div class="invalid-feedback">Bitte zustimmen</div></div></div>
            <div class="col-12"><button type="submit" class="btn btn-blue w-100 py-3 fw-bold"><i class="bi bi-arrow-right-circle me-2"></i>Kostenlos prüfen lassen →</button></div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section id="faq4" class="py-5">
  <div class="container" style="max-width:760px;">
    <h2 class="text-center fw-bold mb-5" data-aos="fade-up">Häufige <span style="color:var(--blue);">Fragen</span></h2>
    <div class="accordion" id="faqAcc4">
      <?php $faqs=[['Gibt es wirklich keine Vorauszahlung?','Korrekt. Wir arbeiten auf reiner Erfolgsbasis. Kosten entstehen nur, wenn wir erfolgreich Kapital zurückholen.'],['Wie hoch sind Ihre Erfolgschancen?','Unsere Erfolgsquote liegt bei 87%. Ob Ihr Fall geeignet ist, stellen wir in der kostenlosen Erstprüfung fest.'],['In welchen Ländern arbeiten Sie?','Wir haben Partneranwälte in 18+ Ländern und können international vollstrecken.'],['Wie lange dauert die Bearbeitung?','48h für die erste Rückmeldung, 3–12 Monate für die vollständige Rückholung je nach Fall.']]; foreach($faqs as $i=>$f): ?>
      <div class="accordion-item" data-aos="fade-up">
        <h2 class="accordion-header">
          <button class="accordion-button <?= $i>0?'collapsed':'' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq4<?= $i ?>">
            <?= htmlspecialchars($f[0], ENT_QUOTES, 'UTF-8') ?>
          </button>
        </h2>
        <div id="faq4<?= $i ?>" class="accordion-collapse collapse <?= $i===0?'show':'' ?>" data-bs-parent="#faqAcc4">
          <div class="accordion-body text-muted"><?= htmlspecialchars($f[1], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Footer -->
<footer>
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-md-4"><div class="footer-brand mb-2">⚖️ VerlustRückholung</div><p class="small">Professionelle Kapitalrückholung. Keine Vorauszahlung. Nur Ergebnisse.</p></div>
      <div class="col-md-4"><div class="fw-bold text-white mb-2">Navigation</div><div><a href="#process">Prozess</a> · <a href="#features">Leistungen</a> · <a href="#faq4">FAQ</a></div></div>
      <div class="col-md-4"><div class="fw-bold text-white mb-2">Kontakt</div><div>info@verlustrueckholung.de</div></div>
    </div>
    <hr style="border-color:#1e293b;">
    <div class="text-center small">© <?= date('Y') ?> VerlustRückholung · <a href="#">Impressum</a> · <a href="#">Datenschutz</a></div>
  </div>
</footer>

<!-- Engagement Modal -->
<div class="modal fade" id="engModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-check me-2"></i>Kostenlose Fallprüfung starten</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <p class="text-muted mb-4">Keine Vorauszahlung · Antwort in 48h · 87% Erfolgsquote</p>
        <form id="modalForm" action="../submit_lead.php" method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="visit_id" data-visit-id value="">
          <div class="row g-3">
            <div class="col-6"><input type="text" class="form-control" name="first_name" placeholder="Vorname" required><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-6"><input type="text" class="form-control" name="last_name" placeholder="Nachname" required><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-12"><input type="email" class="form-control" name="email" placeholder="E-Mail" required><div class="invalid-feedback">Gültige E-Mail</div></div>
            <div class="col-12"><input type="tel" class="form-control" name="phone" placeholder="Telefon (optional)"></div>
            <div class="col-6"><select class="form-select" name="country" required><option value="">Land</option><option>Deutschland</option><option>Österreich</option><option>Schweiz</option><option>Sonstige</option></select><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-6"><input type="number" class="form-control" name="amount_lost" placeholder="Betrag (€)" min="1" required><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-12"><select class="form-select" name="platform_category" required><option value="">Betrugsart</option><option>Krypto-Betrug</option><option>Forex-Betrug</option><option>Binäre Optionen</option><option>Investment-Betrug</option><option>Sonstige</option></select><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-12"><textarea class="form-control" name="case_description" rows="3" placeholder="Kurze Fallbeschreibung..." required></textarea><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="privacy" id="privacyModal4" required><label class="form-check-label small text-muted" for="privacyModal4">Ich stimme der <a href="#" style="color:var(--blue);">Datenschutzerklärung</a> zu.*</label><div class="invalid-feedback">Pflichtfeld</div></div></div>
            <div class="col-12"><button type="submit" class="btn btn-blue w-100 py-3 fw-bold"><i class="bi bi-arrow-right-circle me-2"></i>Kostenlos prüfen →</button></div>
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

// Animated Bar Chart Canvas
(function(){
  var c=document.getElementById('chartCanvas');
  if(!c)return;
  var ctx=c.getContext('2d');
  var bars=[
    {label:'2020',val:.45,target:.45,color:'#1e3a8a'},
    {label:'2021',val:.62,target:.62,color:'#f59e0b'},
    {label:'2022',val:.74,target:.74,color:'#1e3a8a'},
    {label:'2023',val:.88,target:.88,color:'#f59e0b'},
    {label:'2024',val:.96,target:.96,color:'#1e3a8a'},
  ];
  var anim=bars.map(function(){return 0;});
  var t=0;
  function draw(){
    var W=c.width,H=c.height;
    ctx.clearRect(0,0,W,H);
    // Grid
    ctx.strokeStyle='#e5e7eb';ctx.lineWidth=1;
    for(var g=0;g<=4;g++){
      var y=40+((H-80)/4)*g;
      ctx.beginPath();ctx.moveTo(60,y);ctx.lineTo(W-20,y);ctx.stroke();
      ctx.fillStyle='#9ca3af';ctx.font='11px Inter';ctx.textAlign='right';
      ctx.fillText(((100-(g*25))+'%'),52,y+4);
    }
    // Bars
    var bW=((W-80)/bars.length)-16;
    bars.forEach(function(b,i){
      anim[i]=Math.min(b.target,anim[i]+b.target/40);
      var bH=(H-80)*anim[i];
      var x=70+i*((W-80)/bars.length);
      var y=H-40-bH;
      // Bar
      var grad=ctx.createLinearGradient(x,y,x,H-40);
      grad.addColorStop(0,b.color);
      grad.addColorStop(1,b.color+'44');
      ctx.fillStyle=grad;
      ctx.beginPath();
      ctx.roundRect?ctx.roundRect(x,y,bW,bH,4):ctx.rect(x,y,bW,bH);
      ctx.fill();
      // Label
      ctx.fillStyle='#374151';ctx.font='bold 11px Inter';ctx.textAlign='center';
      ctx.fillText(b.label,x+bW/2,H-22);
      // Value
      if(anim[i]>0.1){
        ctx.fillStyle=b.color;ctx.font='bold 12px Inter';
        ctx.fillText(Math.round(anim[i]*100)+'%',x+bW/2,y-6);
      }
    });
    // Title
    ctx.fillStyle='#1e3a8a';ctx.font='bold 13px Inter';ctx.textAlign='left';
    ctx.fillText('Rückholquote nach Jahr',64,24);
    t++;
    if(t<120)requestAnimationFrame(draw);
    else{
      // Idle: subtle pulse on last bar
      setInterval(function(){
        bars[bars.length-1].target=.9+Math.random()*.08;
        t=0;requestAnimationFrame(draw);
        var frames=0;
        function pulse(){if(frames++<40){requestAnimationFrame(pulse);draw();}}
        pulse();
      },3000);
    }
  }
  requestAnimationFrame(draw);
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
    var fd=new FormData(form);fd.append('_ajax','1');fd.append('_source','design4');
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
    var fd=new FormData(form);fd.append('_ajax','1');fd.append('_source','design4-modal');
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
