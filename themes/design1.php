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
<meta name="description" content="VerlustRückholung – KI-gestützte Kapitalrückholung bei Anlagebetrug. 92% Rückholquote, kostenlose Erstprüfung.">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
<style>
:root{--cyan:#00d4ff;--gold:#f5a623;--bg:#080c14;--panel:rgba(255,255,255,0.04);}
*{box-sizing:border-box;}
body{background:var(--bg);color:#e0e0e0;font-family:'Inter',sans-serif;margin:0;}
/* Navbar */
.navbar{background:rgba(8,12,20,0.95)!important;border-bottom:1px solid rgba(0,212,255,0.15);backdrop-filter:blur(10px);}
.navbar-brand span{color:var(--cyan);}
.nav-link{color:#aaa!important;transition:color .2s;}
.nav-link:hover{color:var(--cyan)!important;}
/* Hero */
#hero{position:relative;min-height:100vh;display:flex;align-items:center;overflow:hidden;padding-top:80px;}
#matrixCanvas{position:absolute;inset:0;width:100%;height:100%;opacity:.35;}
.hero-overlay{position:absolute;inset:0;background:linear-gradient(135deg,rgba(8,12,20,.92) 55%,rgba(8,12,20,.7));}
.hero-content{position:relative;z-index:2;}
.neon-title{font-size:clamp(2rem,4vw,3.2rem);font-weight:800;line-height:1.15;}
.neon-title .accent{color:var(--cyan);text-shadow:0 0 20px rgba(0,212,255,.6);}
.stat-mono{font-family:'Courier New',monospace;font-size:.85rem;color:var(--cyan);border:1px solid rgba(0,212,255,.25);padding:6px 14px;border-radius:4px;display:inline-block;margin:4px;background:rgba(0,212,255,.05);}
/* Glass form */
.form-glass{background:rgba(255,255,255,.04);border:1px solid rgba(0,212,255,.2);border-radius:16px;padding:28px;backdrop-filter:blur(12px);}
.form-glass .form-control,.form-glass .form-select{background:rgba(255,255,255,.05);border:none;border-bottom:1px solid rgba(0,212,255,.3);border-radius:4px 4px 0 0;color:#fff;padding:10px 12px;}
.form-glass .form-control:focus,.form-glass .form-select:focus{background:rgba(0,212,255,.05);border-bottom-color:var(--cyan);box-shadow:none;color:#fff;}
.form-glass .form-select option{background:#0d1520;color:#fff;}
.form-glass label{color:#aaa;font-size:.8rem;text-transform:uppercase;letter-spacing:.05em;}
.btn-cyan{background:var(--cyan);color:#080c14;font-weight:700;border:none;}
.btn-cyan:hover{background:#00b8d9;color:#080c14;}
/* Stats strip */
.stats-strip{background:rgba(0,212,255,.05);border-top:1px solid rgba(0,212,255,.15);border-bottom:1px solid rgba(0,212,255,.15);padding:24px 0;}
/* Cards */
.glow-card{background:var(--panel);border:1px solid rgba(0,212,255,.15);border-radius:12px;padding:28px;box-shadow:0 0 20px rgba(0,212,255,.1);transition:transform .3s,box-shadow .3s;}
.glow-card:hover{transform:translateY(-4px);box-shadow:0 0 30px rgba(0,212,255,.25);}
.glow-card .icon{font-size:2rem;color:var(--cyan);}
/* Accordion */
.accordion-item{background:var(--panel)!important;border:1px solid rgba(0,212,255,.12)!important;margin-bottom:8px;border-radius:8px!important;}
.accordion-button{background:transparent!important;color:#e0e0e0!important;font-weight:600;}
.accordion-button:not(.collapsed){color:var(--cyan)!important;box-shadow:none!important;}
.accordion-body{color:#aaa;}
/* Footer */
footer{background:#040710;border-top:1px solid rgba(0,212,255,.12);padding:36px 0;color:#555;font-size:.85rem;}
footer a{color:#888;text-decoration:none;}
footer a:hover{color:var(--cyan);}
/* Modal */
.modal-content{background:#0d1520;border:1px solid rgba(0,212,255,.25);color:#e0e0e0;}
.modal-header{border-bottom:1px solid rgba(0,212,255,.2);}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top">
  <div class="container">
    <a class="navbar-brand fw-bold text-white" href="#">⚖️ Verlust<span>Rückholung</span></a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav1">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav1">
      <ul class="navbar-nav ms-auto me-3">
        <li class="nav-item"><a class="nav-link" href="#how-it-works">Ablauf</a></li>
        <li class="nav-item"><a class="nav-link" href="#fraud-types">Betrugsarten</a></li>
        <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
      </ul>
      <a href="#mainForm" class="btn btn-cyan px-4 rounded-pill">Kostenlos prüfen</a>
    </div>
  </div>
</nav>

<!-- Hero -->
<section id="hero">
  <canvas id="matrixCanvas"></canvas>
  <div class="hero-overlay"></div>
  <div class="container hero-content py-5">
    <div class="row align-items-center g-5">
      <div class="col-lg-6" data-aos="fade-right">
        <div class="mb-3">
          <span class="badge px-3 py-2 rounded-pill" style="background:rgba(0,212,255,.15);color:var(--cyan);border:1px solid rgba(0,212,255,.3);">
            <i class="bi bi-cpu me-1"></i>KI-gestützte Forensik
          </span>
        </div>
        <h1 class="neon-title mb-4">Wir holen Ihr Kapital <span class="accent">aus der Dunkelheit</span></h1>
        <p class="lead mb-4" style="color:#aaa;">Betrugsopfer wenden sich an uns, wenn andere gescheitert sind. Unsere KI durchleuchtet jede Transaktion – lückenlos und präzise.</p>
        <div class="mb-4">
          <span class="stat-mono">92% Rückholquote</span>
          <span class="stat-mono">€48M+ zurückgefordert</span>
          <span class="stat-mono">72h Erstanalyse</span>
          <span class="stat-mono">2.400+ Mandanten</span>
        </div>
        <a href="#how-it-works" class="btn btn-outline-secondary rounded-pill px-4 me-2" style="border-color:rgba(0,212,255,.4);color:var(--cyan);">
          <i class="bi bi-play-circle me-1"></i>Wie es funktioniert
        </a>
      </div>
      <div class="col-lg-6" data-aos="fade-left">
        <div class="form-glass">
          <h5 class="fw-bold mb-4" style="color:var(--cyan);"><i class="bi bi-shield-lock me-2"></i>Kostenlose Fallprüfung</h5>
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
                <label class="form-label">E-Mail</label>
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
                <div class="invalid-feedback">Bitte Land wählen</div>
              </div>
              <div class="col-12">
                <label class="form-label">Verlorener Betrag (€)</label>
                <input type="number" class="form-control" name="amount_lost" placeholder="z.B. 10000" min="1" required>
                <div class="invalid-feedback">Pflichtfeld</div>
              </div>
              <div class="col-12">
                <label class="form-label">Betrugsart</label>
                <select class="form-select" name="platform_category" required>
                  <option value="">-- Betrugsart wählen --</option>
                  <option>Krypto-Betrug</option><option>Forex-Betrug</option>
                  <option>Binäre Optionen</option><option>Investment-Betrug</option>
                  <option>Phishing</option><option>Sonstige</option>
                </select>
                <div class="invalid-feedback">Bitte Betrugsart wählen</div>
              </div>
              <div class="col-12">
                <label class="form-label">Fallbeschreibung</label>
                <textarea class="form-control" name="case_description" rows="3" placeholder="Kurze Beschreibung des Betrugs..." required></textarea>
                <div class="invalid-feedback">Bitte beschreiben Sie Ihren Fall</div>
              </div>
              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="privacy" id="privacyMain" required>
                  <label class="form-check-label" for="privacyMain" style="color:#888;font-size:.82rem;">
                    Ich stimme der <a href="#" style="color:var(--cyan);">Datenschutzerklärung</a> zu.*
                  </label>
                  <div class="invalid-feedback">Bitte zustimmen</div>
                </div>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-cyan w-100 py-3 fw-bold">
                  <i class="bi bi-search me-2"></i>Jetzt kostenlos prüfen →
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
<div class="stats-strip" id="stats-strip">
  <div class="container">
    <div class="row text-center g-4">
      <div class="col-6 col-md-3"><div class="fs-2 fw-800" style="color:var(--cyan);font-family:'Courier New',monospace;">92%</div><div class="text-muted small">Erfolgsquote</div></div>
      <div class="col-6 col-md-3"><div class="fs-2 fw-800" style="color:var(--gold);font-family:'Courier New',monospace;">€48M+</div><div class="text-muted small">Zurückgefordert</div></div>
      <div class="col-6 col-md-3"><div class="fs-2 fw-800" style="color:var(--cyan);font-family:'Courier New',monospace;">72h</div><div class="text-muted small">Erstanalyse</div></div>
      <div class="col-6 col-md-3"><div class="fs-2 fw-800" style="color:var(--gold);font-family:'Courier New',monospace;">2.400+</div><div class="text-muted small">Mandanten</div></div>
    </div>
  </div>
</div>

<!-- How It Works -->
<section id="how-it-works" class="py-5">
  <div class="container">
    <h2 class="text-center fw-bold mb-2" data-aos="fade-up">Unser <span style="color:var(--cyan);">Vorgehen</span></h2>
    <p class="text-center text-muted mb-5" data-aos="fade-up">Drei Schritte zur Kapitalrückholung</p>
    <div class="row g-4">
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
        <div class="glow-card text-center h-100">
          <div class="icon mb-3"><i class="bi bi-cpu-fill"></i></div>
          <div class="stat-mono mb-2">01</div>
          <h5 class="fw-bold mb-2">KI-Analyse</h5>
          <p class="text-muted small mb-0">Unsere KI durchsucht Blockchain-Daten, Transaktionshistorien und bekannte Betrugsmuster innerhalb von 72 Stunden.</p>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
        <div class="glow-card text-center h-100">
          <div class="icon mb-3"><i class="bi bi-shield-check"></i></div>
          <div class="stat-mono mb-2">02</div>
          <h5 class="fw-bold mb-2">Rechtliche Strategie</h5>
          <p class="text-muted small mb-0">Unsere Partner-Anwälte erarbeiten eine individuelle Rückholstrategie basierend auf den KI-Erkenntnissen.</p>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
        <div class="glow-card text-center h-100">
          <div class="icon mb-3"><i class="bi bi-cash-coin"></i></div>
          <div class="stat-mono mb-2">03</div>
          <h5 class="fw-bold mb-2">Rückholung</h5>
          <p class="text-muted small mb-0">Wir setzen die Rückforderung durch – ohne Vorauszahlung. Wir verdienen nur bei Erfolg.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Fraud Types -->
<section id="fraud-types" class="py-5" style="background:rgba(0,212,255,.03);">
  <div class="container">
    <h2 class="text-center fw-bold mb-5" data-aos="fade-up">Erkannte <span style="color:var(--cyan);">Betrugsarten</span></h2>
    <div class="row g-4">
      <?php $fraudTypes=[['bi-currency-bitcoin','Krypto-Betrug','Gefälschte Krypto-Exchanges, Rug Pulls und betrügerische Wallets.'],['bi-graph-up-arrow','Forex-Betrug','Unregulierte Broker und manipulierte Handelsergebnisse.'],['bi-bar-chart-line','Binäre Optionen','Plattformen, die Gewinne verweigern und Konten sperren.'],['bi-person-badge','Identitätsbetrug','Fake-Investmentberater mit gestohlenen Lizenzen.']]; foreach($fraudTypes as $ft): ?>
      <div class="col-md-6 col-lg-3" data-aos="fade-up">
        <div class="glow-card text-center h-100">
          <i class="bi <?= $ft[0] ?> fs-2 mb-3" style="color:var(--gold);"></i>
          <h6 class="fw-bold mb-2"><?= $ft[1] ?></h6>
          <p class="text-muted small mb-0"><?= $ft[2] ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- FAQ -->
<section id="faq" class="py-5">
  <div class="container" style="max-width:760px;">
    <h2 class="text-center fw-bold mb-5" data-aos="fade-up">Häufige <span style="color:var(--cyan);">Fragen</span></h2>
    <div class="accordion" id="faqAccordion">
      <?php $faqs=[['Kostet die Erstprüfung etwas?','Nein – die Erstprüfung Ihres Falls ist vollständig kostenlos. Wir arbeiten auf Erfolgsbasis.'],['Wie lange dauert der Prozess?','Die KI-Analyse dauert 72 Stunden. Der gesamte Rückholprozess dauert je nach Fall 3–12 Monate.'],['Welche Unterlagen brauche ich?','Kontoauszüge, Kommunikation mit der Plattform und Zahlungsbelege sind hilfreich – aber nicht alle sofort notwendig.'],['Arbeiten Sie weltweit?','Ja, wir haben Partner in über 18 Ländern und können international vorgehen.']]; foreach($faqs as $i=>$faq): ?>
      <div class="accordion-item" data-aos="fade-up">
        <h2 class="accordion-header">
          <button class="accordion-button <?= $i>0?'collapsed':'' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>">
            <?= htmlspecialchars($faq[0], ENT_QUOTES, 'UTF-8') ?>
          </button>
        </h2>
        <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i===0?'show':'' ?>" data-bs-parent="#faqAccordion">
          <div class="accordion-body"><?= htmlspecialchars($faq[1], ENT_QUOTES, 'UTF-8') ?></div>
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
      <div class="col-md-4">
        <div class="fw-bold text-white mb-2">⚖️ VerlustRückholung</div>
        <p class="small">KI-gestützte Kapitalrückholung bei Anlagebetrug. Keine Vorauszahlung.</p>
      </div>
      <div class="col-md-4">
        <div class="fw-bold text-white mb-2">Navigation</div>
        <div><a href="#how-it-works">Ablauf</a> · <a href="#fraud-types">Betrugsarten</a> · <a href="#faq">FAQ</a></div>
      </div>
      <div class="col-md-4">
        <div class="fw-bold text-white mb-2">Kontakt</div>
        <div>info@verlustrueckholung.de</div>
      </div>
    </div>
    <hr style="border-color:rgba(255,255,255,.08);">
    <div class="text-center small">© <?= date('Y') ?> VerlustRückholung · <a href="#">Impressum</a> · <a href="#">Datenschutz</a></div>
  </div>
</footer>

<!-- Engagement Modal -->
<div class="modal fade" id="engModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" style="color:var(--cyan);"><i class="bi bi-cpu me-2"></i>Kostenlose KI-Fallanalyse</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-4">Unsere KI hat bereits <strong style="color:var(--cyan);">2.400+ Fälle</strong> analysiert. Starten Sie jetzt Ihre kostenlose Prüfung.</p>
        <form id="modalForm" action="../submit_lead.php" method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="visit_id" data-visit-id value="">
          <div class="row g-3">
            <div class="col-6"><input type="text" class="form-control bg-dark text-white border-secondary" name="first_name" placeholder="Vorname" required><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-6"><input type="text" class="form-control bg-dark text-white border-secondary" name="last_name" placeholder="Nachname" required><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-12"><input type="email" class="form-control bg-dark text-white border-secondary" name="email" placeholder="E-Mail" required><div class="invalid-feedback">Gültige E-Mail erforderlich</div></div>
            <div class="col-12"><input type="tel" class="form-control bg-dark text-white border-secondary" name="phone" placeholder="Telefon (optional)"></div>
            <div class="col-6"><select class="form-select bg-dark text-white border-secondary" name="country" required><option value="">Land</option><option>Deutschland</option><option>Österreich</option><option>Schweiz</option><option>Sonstige</option></select><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-6"><input type="number" class="form-control bg-dark text-white border-secondary" name="amount_lost" placeholder="Betrag (€)" min="1" required><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-12"><select class="form-select bg-dark text-white border-secondary" name="platform_category" required><option value="">Betrugsart</option><option>Krypto-Betrug</option><option>Forex-Betrug</option><option>Binäre Optionen</option><option>Investment-Betrug</option><option>Sonstige</option></select><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-12"><textarea class="form-control bg-dark text-white border-secondary" name="case_description" rows="2" placeholder="Kurze Fallbeschreibung..." required></textarea><div class="invalid-feedback">Pflichtfeld</div></div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="privacy" id="privacyModal" required>
                <label class="form-check-label small text-muted" for="privacyModal">Ich stimme der <a href="#" style="color:var(--cyan);">Datenschutzerklärung</a> zu.*</label>
                <div class="invalid-feedback">Pflichtfeld</div>
              </div>
            </div>
            <div class="col-12"><button type="submit" class="btn btn-cyan w-100 py-3 fw-bold"><i class="bi bi-search me-2"></i>Jetzt kostenlos analysieren →</button></div>
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

// Matrix Rain Canvas
(function(){
  var c=document.getElementById('matrixCanvas');
  if(!c)return;
  var ctx=c.getContext('2d');
  var chars='0123456789ABCDEF アウエオカキクケコサシスセソ';
  var cols,drops;
  function resize(){
    c.width=c.offsetWidth;c.height=c.offsetHeight;
    cols=Math.floor(c.width/18);
    drops=Array(cols).fill(1);
  }
  resize();
  window.addEventListener('resize',resize);
  function draw(){
    ctx.fillStyle='rgba(8,12,20,0.07)';
    ctx.fillRect(0,0,c.width,c.height);
    ctx.font='14px "Courier New"';
    for(var i=0;i<drops.length;i++){
      var ch=chars[Math.floor(Math.random()*chars.length)];
      var g=Math.random()>.5?'#00d4ff':'#00ff88';
      ctx.fillStyle=g;
      ctx.fillText(ch,i*18,drops[i]*18);
      if(drops[i]*18>c.height&&Math.random()>.975)drops[i]=0;
      drops[i]++;
    }
    requestAnimationFrame(draw);
  }
  draw();
})();

// AJAX Form - Main
(function(){
  var form=document.getElementById('mainForm');
  if(!form)return;
  form.addEventListener('submit',function(e){
    e.preventDefault();
    if(!form.checkValidity()){form.classList.add('was-validated');return;}
    var btn=form.querySelector('[type="submit"]');
    var orig=btn?btn.innerHTML:'';
    if(btn){btn.disabled=true;btn.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Wird geprüft...';}
    var fd=new FormData(form);fd.append('_ajax','1');fd.append('_source','design1');
    fetch('../submit_lead.php',{method:'POST',body:fd})
      .then(function(r){return r.json();})
      .then(function(data){
        if(data.csrf_token){document.querySelectorAll('input[name="csrf_token"]').forEach(function(el){el.value=data.csrf_token;});}
        var al=document.createElement('div');
        al.className='alert '+(data.success?'alert-success':'alert-danger')+' mt-3';
        al.innerHTML=data.success?'<i class="bi bi-check-circle me-2"></i><strong>Vielen Dank!</strong> '+data.message:'<i class="bi bi-exclamation-triangle me-2"></i>'+(data.message||'Fehler. Bitte erneut versuchen.');
        form.parentNode.insertBefore(al,form);
        if(data.success)form.style.display='none';
        else if(btn){btn.disabled=false;btn.innerHTML=orig;}
      }).catch(function(){if(btn){btn.disabled=false;btn.innerHTML=orig;}});
  });
})();

// AJAX Form - Modal
(function(){
  var form=document.getElementById('modalForm');
  if(!form)return;
  form.addEventListener('submit',function(e){
    e.preventDefault();
    if(!form.checkValidity()){form.classList.add('was-validated');return;}
    var btn=form.querySelector('[type="submit"]');
    var orig=btn?btn.innerHTML:'';
    if(btn){btn.disabled=true;btn.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Wird geprüft...';}
    var fd=new FormData(form);fd.append('_ajax','1');fd.append('_source','design1-modal');
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
