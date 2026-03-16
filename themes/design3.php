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
<meta name="description" content="VerlustRückholung – KI verfolgt kryptographische Transaktionen bis zur Quelle. 87% Erfolgsrate.">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
<style>
:root{--green:#00ff88;--mint:#7fffbe;--bg:#030d08;--panel:#0a1a10;--border:rgba(0,255,136,.2);}
body{background:var(--bg);color:#c8ffd4;font-family:'Inter',sans-serif;margin:0;}
code,pre,.mono{font-family:'JetBrains Mono',monospace;}
/* Navbar */
.navbar{background:rgba(3,13,8,.97)!important;border-bottom:1px solid var(--border);backdrop-filter:blur(8px);}
.navbar-brand span{color:var(--green);}
.navbar-brand{color:#c8ffd4!important;}
.nav-link{color:#6dbf85!important;}
.nav-link:hover{color:var(--green)!important;}
/* Hero */
#hero{position:relative;min-height:100vh;display:flex;align-items:center;overflow:hidden;padding-top:80px;}
#netCanvas{position:absolute;inset:0;width:100%;height:100%;}
.hero-overlay{position:absolute;inset:0;background:linear-gradient(135deg,rgba(3,13,8,.93) 50%,rgba(3,13,8,.75));}
.hero-content{position:relative;z-index:2;}
.term-badge{display:inline-block;background:rgba(0,255,136,.1);border:1px solid var(--border);border-radius:4px;padding:4px 12px;color:var(--green);font-family:'JetBrains Mono',monospace;font-size:.82rem;}
.hero-title{font-size:clamp(2rem,4vw,3.2rem);font-weight:800;line-height:1.2;}
.hero-title .accent{color:var(--green);text-shadow:0 0 15px rgba(0,255,136,.5);}
.stat-green{font-family:'JetBrains Mono',monospace;font-size:.8rem;color:var(--green);border:1px solid var(--border);padding:5px 12px;border-radius:3px;display:inline-block;margin:3px;background:rgba(0,255,136,.04);}
/* Ticker */
.ticker-wrap{overflow:hidden;background:rgba(0,255,136,.07);border-top:1px solid var(--border);border-bottom:1px solid var(--border);padding:10px 0;white-space:nowrap;}
.ticker-inner{display:inline-block;animation:tickscroll 30s linear infinite;}
@keyframes tickscroll{0%{transform:translateX(100vw);}100%{transform:translateX(-100%);}}
/* Form */
.form-green{background:var(--panel);border:1px solid var(--border);border-top:3px solid var(--green);border-radius:8px;padding:28px;}
.form-green .form-control,.form-green .form-select{background:rgba(0,255,136,.04);border:1px solid rgba(0,255,136,.15);color:#c8ffd4;}
.form-green .form-control:focus,.form-green .form-select:focus{background:rgba(0,255,136,.08);border-color:var(--green);box-shadow:0 0 0 2px rgba(0,255,136,.15);color:#c8ffd4;}
.form-green .form-select option{background:#0a1a10;}
.form-green label{color:#6dbf85;font-size:.8rem;font-family:'JetBrains Mono',monospace;}
.btn-green{background:var(--green);color:#030d08;font-weight:700;border:none;}
.btn-green:hover{background:var(--mint);color:#030d08;}
/* Feature Cards */
.feature-card{background:var(--panel);border:1px solid var(--border);border-radius:6px;padding:24px;transition:border-color .3s,box-shadow .3s;}
.feature-card:hover{border-color:var(--green);box-shadow:0 0 16px rgba(0,255,136,.15);}
.feature-card .icon-box{width:44px;height:44px;background:rgba(0,255,136,.1);border:1px solid var(--border);border-radius:6px;display:flex;align-items:center;justify-content:center;color:var(--green);font-size:1.2rem;margin-bottom:12px;}
/* Accordion */
.accordion-item{background:var(--panel)!important;border:1px solid var(--border)!important;margin-bottom:6px;border-radius:6px!important;}
.accordion-button{background:transparent!important;color:#c8ffd4!important;font-family:'JetBrains Mono',monospace;font-size:.9rem;}
.accordion-button:not(.collapsed){color:var(--green)!important;box-shadow:none!important;}
.accordion-body{color:#6dbf85;}
/* CTA */
.cta-section{background:linear-gradient(135deg,rgba(0,255,136,.08),rgba(0,255,136,.02));border-top:1px solid var(--border);border-bottom:1px solid var(--border);}
/* Footer */
footer{background:#020a05;border-top:1px solid var(--border);padding:32px 0;color:#3d7a50;font-size:.85rem;}
footer a{color:#4a9960;text-decoration:none;}
footer a:hover{color:var(--green);}
/* Modal */
.modal-content{background:#071410;border:1px solid var(--border);color:#c8ffd4;}
.modal-header{border-bottom:1px solid var(--border);}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#"><span class="mono" style="color:var(--green);">$</span> ⚖️ Verlust<span>Rückholung</span></a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav3">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav3">
      <ul class="navbar-nav ms-auto me-3">
        <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
        <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
        <li class="nav-item"><a class="nav-link" href="#cta">Start</a></li>
      </ul>
      <a href="#mainForm" class="btn btn-green px-4 mono">./analyse --start</a>
    </div>
  </div>
</nav>

<!-- Ticker -->
<div class="ticker-wrap" style="margin-top:70px;">
  <div class="ticker-inner mono small" style="color:var(--green);">
    ✓ 150.000+ analysierte Transaktionen &nbsp;·&nbsp; €48M+ gesichert &nbsp;·&nbsp; 87% Erfolgsrate &nbsp;·&nbsp; Weltweit aktiv &nbsp;·&nbsp; Kostenlose Erstanalyse &nbsp;·&nbsp; Keine Vorauszahlung &nbsp;·&nbsp; KI-Forensik seit 2019 &nbsp;·&nbsp;
  </div>
</div>

<!-- Hero -->
<section id="hero">
  <canvas id="netCanvas"></canvas>
  <div class="hero-overlay"></div>
  <div class="container hero-content py-5">
    <div class="row align-items-center g-5">
      <div class="col-lg-6" data-aos="fade-right">
        <div class="mb-3">
          <span class="term-badge">// blockchain_forensics v4.2.1 – aktiv</span>
        </div>
        <h1 class="hero-title mb-4">Jeder Cent zählt. <span class="accent">Wir finden ihn.</span></h1>
        <p class="lead mb-4" style="color:#6dbf85;">Unsere KI verfolgt kryptographische Transaktionen bis zur Quelle und macht das Unsichtbare sichtbar. Präzise. Schnell. Zuverlässig.</p>
        <div class="mb-4">
          <span class="stat-green">150k+ TX analysiert</span>
          <span class="stat-green">€48M+ gesichert</span>
          <span class="stat-green">87% Erfolgsrate</span>
          <span class="stat-green">Weltweit aktiv</span>
        </div>
        <div class="p-3 rounded" style="background:rgba(0,255,136,.05);border:1px solid var(--border);font-family:'JetBrains Mono',monospace;font-size:.82rem;color:#6dbf85;">
          <span style="color:var(--green);">$</span> scan --target fraudulent_platform --depth deep<br>
          <span style="color:#3d7a50;">&gt; Initialisiere KI-Analyse...</span><br>
          <span style="color:#3d7a50;">&gt; Transaktionsdaten geladen: 1.247 Einträge</span><br>
          <span style="color:var(--green);">&gt; Betrugsmuster identifiziert ✓</span>
        </div>
      </div>
      <div class="col-lg-6" data-aos="fade-left">
        <div class="form-green" id="mainFormWrapper">
          <div class="mono small mb-3" style="color:var(--green);">// fall_einreichen.php – kostenlose analyse</div>
          <form id="mainForm" action="../submit_lead.php" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="visit_id" data-visit-id value="">
            <div class="row g-3">
              <div class="col-6">
                <label class="form-label">vorname</label>
                <input type="text" class="form-control" name="first_name" placeholder="Max" required>
                <div class="invalid-feedback">Pflichtfeld</div>
              </div>
              <div class="col-6">
                <label class="form-label">nachname</label>
                <input type="text" class="form-control" name="last_name" placeholder="Mustermann" required>
                <div class="invalid-feedback">Pflichtfeld</div>
              </div>
              <div class="col-12">
                <label class="form-label">email_adresse</label>
                <input type="email" class="form-control" name="email" placeholder="max@beispiel.de" required>
                <div class="invalid-feedback">Gültige E-Mail erforderlich</div>
              </div>
              <div class="col-12">
                <label class="form-label">telefon (optional)</label>
                <input type="tel" class="form-control" name="phone" placeholder="+49 ...">
              </div>
              <div class="col-12">
                <label class="form-label">land</label>
                <select class="form-select" name="country" required>
                  <option value="">-- select country --</option>
                  <option>Deutschland</option><option>Österreich</option><option>Schweiz</option>
                  <option>Luxemburg</option><option>Liechtenstein</option><option>Sonstige</option>
                </select>
                <div class="invalid-feedback">Pflichtfeld</div>
              </div>
              <div class="col-12">
                <label class="form-label">betrag_verloren (€)</label>
                <input type="number" class="form-control" name="amount_lost" placeholder="10000" min="1" required>
                <div class="invalid-feedback">Pflichtfeld</div>
              </div>
              <div class="col-12">
                <label class="form-label">betrugsart</label>
                <select class="form-select" name="platform_category" required>
                  <option value="">-- select type --</option>
                  <option>Krypto-Betrug</option><option>Forex-Betrug</option>
                  <option>Binäre Optionen</option><option>Investment-Betrug</option><option>Sonstige</option>
                </select>
                <div class="invalid-feedback">Pflichtfeld</div>
              </div>
              <div class="col-12">
                <label class="form-label">fallbeschreibung</label>
                <textarea class="form-control" name="case_description" rows="3" placeholder="// Beschreiben Sie den Betrug..." required></textarea>
                <div class="invalid-feedback">Pflichtfeld</div>
              </div>
              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="privacy" id="privacyMain3" required>
                  <label class="form-check-label small" for="privacyMain3" style="color:#4a9960;font-family:'JetBrains Mono',monospace;font-size:.78rem;">
                    /* <a href="#" style="color:var(--green);">Datenschutzerklärung</a> akzeptiert */
                  </label>
                  <div class="invalid-feedback">Pflichtfeld</div>
                </div>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-green w-100 py-3 fw-bold mono">
                  $ analyse --submit --kostenlos →
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Features -->
<section id="features" class="py-5">
  <div class="container">
    <h2 class="text-center fw-bold mb-2" data-aos="fade-up" style="color:var(--green);">// Features</h2>
    <p class="text-center mb-5" style="color:#4a9960;" data-aos="fade-up">Was unsere KI-Plattform einzigartig macht</p>
    <div class="row g-4">
      <?php $features=[
        ['bi-cpu','Blockchain-Forensik','Verfolgt Transaktionen über mehrere Chains und Mixer hinweg'],
        ['bi-shield-lock','Datensicherheit','Ende-zu-Ende-Verschlüsselung aller Falldaten'],
        ['bi-graph-up','Pattern Recognition','KI erkennt bekannte Betrugsmuster in Millisekunden'],
        ['bi-globe','Globale Reichweite','Vollstreckung in 18+ Ländern mit lokalen Partnern'],
        ['bi-clock-history','72h Analyse','Erstes Ergebnis innerhalb von 72 Stunden'],
        ['bi-cash-coin','Erfolgsbasis','Keine Vorauszahlung – wir verdienen nur bei Erfolg'],
      ]; foreach($features as $f): ?>
      <div class="col-md-6 col-lg-4" data-aos="fade-up">
        <div class="feature-card h-100">
          <div class="icon-box"><i class="bi <?= $f[0] ?>"></i></div>
          <h6 class="fw-bold mb-2" style="color:var(--mint);"><?= $f[1] ?></h6>
          <p class="small mb-0" style="color:#4a9960;"><?= $f[2] ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- FAQ -->
<section id="faq" class="py-5" style="background:rgba(0,255,136,.02);">
  <div class="container" style="max-width:760px;">
    <h2 class="text-center fw-bold mb-5" data-aos="fade-up" style="color:var(--green);">// FAQ</h2>
    <div class="accordion" id="faq3">
      <?php $faqs=[['Ist die Analyse wirklich kostenlos?','Ja. Die vollständige KI-Erstanalyse Ihres Falls ist kostenlos und unverbindlich. Wir arbeiten ausschließlich auf Erfolgsbasis.'],['Welche Betrugsfälle bearbeiten Sie?','Krypto-Betrug, Forex, binäre Optionen, Investment-Scams, Phishing und ähnliche Finanzbetrugsformen.'],['Wie lange dauert der Prozess?','Die Erstanalyse dauert 72h. Der vollständige Rückholprozess beträgt 3–12 Monate je nach Fall.'],['Brauche ich Unterlagen?','Hilfreich sind: Zahlungsbelege, Kommunikation mit der Plattform, Kontoauszüge. Nicht alle sofort notwendig.']]; foreach($faqs as $i=>$f): ?>
      <div class="accordion-item" data-aos="fade-up">
        <h2 class="accordion-header">
          <button class="accordion-button <?= $i>0?'collapsed':'' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq3<?= $i ?>">
            <span class="mono" style="color:var(--green);margin-right:8px;">&gt;</span><?= htmlspecialchars($f[0], ENT_QUOTES, 'UTF-8') ?>
          </button>
        </h2>
        <div id="faq3<?= $i ?>" class="accordion-collapse collapse <?= $i===0?'show':'' ?>" data-bs-parent="#faq3">
          <div class="accordion-body"><?= htmlspecialchars($f[1], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section id="cta" class="cta-section py-5 text-center">
  <div class="container" data-aos="fade-up">
    <h2 class="fw-bold mb-3" style="color:var(--green);">Bereit für die Analyse?</h2>
    <p class="mb-4" style="color:#4a9960;">Starten Sie jetzt – kostenlos und unverbindlich. Unsere KI prüft Ihren Fall innerhalb von 72 Stunden.</p>
    <a href="#mainForm" class="btn btn-green px-5 py-3 fw-bold mono">$ start --kostenlose-analyse →</a>
  </div>
</section>

<!-- Footer -->
<footer>
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-md-4"><div class="fw-bold mb-2" style="color:var(--green);">// VerlustRückholung</div><p class="small">KI-Blockchain-Forensik für Betrugsopfer. Ohne Vorauszahlung.</p></div>
      <div class="col-md-4"><div class="fw-bold mb-2" style="color:#4a9960;">// links</div><div><a href="#features">Features</a> · <a href="#faq">FAQ</a> · <a href="#cta">Start</a></div></div>
      <div class="col-md-4"><div class="fw-bold mb-2" style="color:#4a9960;">// kontakt</div><div>info@verlustrueckholung.de</div></div>
    </div>
    <hr style="border-color:var(--border);">
    <div class="text-center small mono">/* © <?= date('Y') ?> VerlustRückholung · <a href="#">Impressum</a> · <a href="#">Datenschutz</a> */</div>
  </div>
</footer>

<!-- Engagement Modal -->
<div class="modal fade" id="engModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title mono" style="color:var(--green);">$ fall_analyse --start --kostenlos</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-4" style="color:#4a9960;">Unsere KI hat <strong style="color:var(--green);">150.000+</strong> Transaktionen analysiert. Starten Sie jetzt Ihre kostenlose Prüfung.</p>
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
            <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="privacy" id="privacyModal3" required><label class="form-check-label small" for="privacyModal3" style="color:#4a9960;">Datenschutzerklärung akzeptiert.*</label><div class="invalid-feedback">Pflichtfeld</div></div></div>
            <div class="col-12"><button type="submit" class="btn btn-green w-100 py-3 fw-bold mono">$ submit --analyse →</button></div>
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

// Blockchain Network Canvas
(function(){
  var c=document.getElementById('netCanvas');
  if(!c)return;
  var ctx=c.getContext('2d');
  var nodes=[];
  var pulses=[];
  function resize(){
    c.width=c.offsetWidth;c.height=c.offsetHeight;
    initNodes();
  }
  function initNodes(){
    nodes=[];
    var N=18;
    for(var i=0;i<N;i++){
      nodes.push({x:Math.random()*c.width,y:Math.random()*c.height,vx:(Math.random()-.5)*.4,vy:(Math.random()-.5)*.4});
    }
  }
  resize();
  window.addEventListener('resize',resize);
  var t=0;
  function draw(){
    ctx.clearRect(0,0,c.width,c.height);
    // Update nodes
    nodes.forEach(function(n){
      n.x+=n.vx;n.y+=n.vy;
      if(n.x<0||n.x>c.width)n.vx*=-1;
      if(n.y<0||n.y>c.height)n.vy*=-1;
    });
    // Edges
    for(var i=0;i<nodes.length;i++){
      for(var j=i+1;j<nodes.length;j++){
        var dx=nodes[i].x-nodes[j].x,dy=nodes[i].y-nodes[j].y;
        var d=Math.sqrt(dx*dx+dy*dy);
        if(d<180){
          ctx.beginPath();ctx.moveTo(nodes[i].x,nodes[i].y);ctx.lineTo(nodes[j].x,nodes[j].y);
          ctx.strokeStyle='rgba(0,255,136,'+(0.12*(1-d/180))+')';ctx.lineWidth=.8;ctx.stroke();
        }
      }
    }
    // Pulses
    if(t%120===0&&nodes.length>1){
      var src=Math.floor(Math.random()*nodes.length);
      var dst=Math.floor(Math.random()*nodes.length);
      if(src!==dst)pulses.push({sx:nodes[src].x,sy:nodes[src].y,ex:nodes[dst].x,ey:nodes[dst].y,p:0,speed:.015});
    }
    pulses=pulses.filter(function(p){return p.p<=1;});
    pulses.forEach(function(p){
      p.p+=p.speed;
      var px=p.sx+(p.ex-p.sx)*p.p;
      var py=p.sy+(p.ey-p.sy)*p.p;
      ctx.beginPath();ctx.arc(px,py,4,0,Math.PI*2);
      ctx.fillStyle='rgba(0,255,136,'+(1-p.p)+')';ctx.fill();
    });
    // Nodes
    nodes.forEach(function(n){
      ctx.beginPath();ctx.arc(n.x,n.y,3,0,Math.PI*2);
      ctx.fillStyle='rgba(0,255,136,0.5)';ctx.fill();
    });
    t++;
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
    var fd=new FormData(form);fd.append('_ajax','1');fd.append('_source','design3');
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
    var fd=new FormData(form);fd.append('_ajax','1');fd.append('_source','design3-modal');
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
