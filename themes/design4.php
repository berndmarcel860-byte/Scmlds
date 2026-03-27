<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title  = get_setting('page_title', 'VerlustRückholung – KI-gestützte Kapitalrückholung bei Anlagebetrug');
$modal_delay = max(5, (int) get_setting('modal_delay_seconds', '60'));
$success = isset($_GET['success']) && $_GET['success'] === '1';
$error   = isset($_GET['error']) ? htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') : '';
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
    <!-- Preconnect to CDN origins to reduce DNS/TLS latency -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Bootstrap 5.3 – critical layout CSS, loaded synchronously -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons – non-critical; deferred with print-media trick -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"></noscript>

    <!-- AOS – non-critical animation CSS; deferred -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css"
          as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css"></noscript>

    <!-- Google Fonts – non-critical; deferred + font-display:swap -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
          as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"></noscript>
<style>
:root{--primary:#1e1b4b;--accent:#4338ca;--accent-d:#3730a3;--text:#1a202c;--muted:#6c757d;--bg-light:#eef2ff;--border:#e0e7ff;}
*{box-sizing:border-box;}
body{font-family:'Inter',sans-serif;color:var(--text);background:#fff;margin:0;}
/* NAVBAR */
.navbar{background:rgba(10,22,40,0.95);backdrop-filter:blur(10px);padding:14px 0;transition:all .3s;}
.navbar.scrolled{padding:8px 0;box-shadow:0 2px 20px rgba(0,0,0,.4);}
.navbar-brand{font-weight:800;font-size:1.4rem;color:#fff!important;letter-spacing:-.5px;}
.navbar-brand span{color:#818cf8;}
.navbar-nav .nav-link{color:rgba(255,255,255,.85)!important;font-weight:500;font-size:.9rem;padding:6px 14px!important;transition:color .2s;}
.navbar-nav .nav-link:hover{color:#818cf8!important;}
.nav-cta{background:var(--accent);color:#fff!important;border-radius:6px;padding:7px 18px!important;}
.nav-cta:hover{background:var(--accent-d)!important;}
/* HERO */
.hero{min-height:100vh;background:linear-gradient(135deg,#0a0818 0%,#1e1b4b 50%,#0d2b5e 100%);display:flex;align-items:center;position:relative;overflow:hidden;padding:100px 0 60px;}
#heroCanvas4{position:absolute;top:0;left:0;width:100%;height:100%;opacity:.35;}
.hero-content{position:relative;z-index:2;}
.hero-badge{display:inline-block;background:rgba(99,102,241,.25);border:1px solid rgba(129,140,248,.4);color:#a5b4fc;border-radius:20px;padding:5px 16px;font-size:.8rem;font-weight:600;letter-spacing:.5px;margin-bottom:20px;}
.hero h1{font-size:clamp(2rem,4.5vw,3.2rem);font-weight:800;color:#fff;line-height:1.15;margin-bottom:20px;}
.hero h1 span{color:#818cf8;}
.hero .lead{color:rgba(255,255,255,.75);font-size:1.05rem;max-width:540px;margin-bottom:32px;}
.hero-btn{background:var(--accent);color:#fff;border:none;border-radius:8px;padding:14px 32px;font-size:1rem;font-weight:700;cursor:pointer;transition:all .3s;text-decoration:none;display:inline-block;}
.hero-btn:hover{background:var(--accent-d);transform:translateY(-2px);box-shadow:0 8px 25px rgba(67,56,202,.4);color:#fff;}
.stat-pills{display:flex;flex-wrap:wrap;gap:12px;margin-top:36px;}
.stat-pill{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:10px;padding:10px 18px;color:#fff;}
.stat-pill strong{display:block;font-size:1.2rem;font-weight:800;color:#a5b4fc;}
.stat-pill span{font-size:.75rem;color:rgba(255,255,255,.6);}
/* STATS BAND */
.stats-band{background:#fff;padding:40px 0;border-bottom:1px solid var(--border);}
.stat-item{text-align:center;}
.stat-item .num{font-size:2.2rem;font-weight:800;color:var(--accent);}
.stat-item .lbl{font-size:.85rem;color:var(--muted);font-weight:500;}
/* TRUST BANNER */
.trust-banner{background:var(--bg-light);padding:18px 0;border-top:1px solid var(--border);border-bottom:1px solid var(--border);}
.trust-item{display:flex;align-items:center;gap:8px;font-size:.85rem;font-weight:600;color:var(--primary);}
.trust-item i{color:var(--accent);font-size:1rem;}
/* NEWS TICKER */
.ticker-wrap{background:#1e1b4b;padding:10px 0;overflow:hidden;}
.ticker-label{background:var(--accent);color:#fff;padding:3px 14px;border-radius:4px;font-size:.78rem;font-weight:700;white-space:nowrap;margin-right:20px;}
.ticker-inner{display:flex;align-items:center;}
.ticker-track{display:flex;animation:ticker 40s linear infinite;white-space:nowrap;}
.ticker-track span{color:rgba(255,255,255,.8);font-size:.82rem;padding:0 30px;}
.ticker-track span::before{content:"●";color:#818cf8;margin-right:8px;}
@keyframes ticker{0%{transform:translateX(0);}100%{transform:translateX(-50%);}}
/* AI SECTION */
#ai-section{background:var(--bg-light);padding:80px 0;}
.gauge-wrap{display:flex;justify-content:center;margin-bottom:30px;}
.gauge-svg{width:220px;height:220px;}
.gauge-label{text-anchor:middle;font-family:'Inter',sans-serif;}
.ai-metric{margin-bottom:16px;}
.ai-metric-top{display:flex;justify-content:space-between;font-size:.85rem;font-weight:600;color:var(--primary);margin-bottom:5px;}
.ai-bar-bg{background:#dde4ff;border-radius:4px;height:8px;overflow:hidden;}
.ai-bar-fill{height:100%;background:linear-gradient(90deg,var(--accent),#818cf8);border-radius:4px;width:0;transition:width 1.2s ease;}
/* HOW IT WORKS */
.how-section{padding:80px 0;background:#fff;}
.step-num{width:56px;height:56px;background:var(--accent);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:800;margin:0 auto 16px;}
/* LEISTUNGEN */
.leistungen{padding:80px 0;background:#f8faff;}
.leistung-card{background:#fff;border-radius:12px;padding:28px;height:100%;border-right:3px solid var(--accent);box-shadow:0 2px 12px rgba(0,0,0,.06);transition:all .3s;}
.leistung-card:hover{background:var(--bg-light);transform:translateY(-4px);box-shadow:0 8px 24px rgba(67,56,202,.12);}
.leistung-icon{width:50px;height:50px;background:var(--bg-light);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;}
.leistung-icon i{color:var(--accent);font-size:1.4rem;}
/* FRAUD TYPES */
.fraud-section{padding:80px 0;background:#fff;}
.fraud-card{background:#fff;border-radius:12px;padding:24px;border:1px solid var(--border);transition:all .3s;height:100%;}
.fraud-card:hover{box-shadow:0 6px 20px rgba(67,56,202,.12);transform:translateY(-3px);}
.fraud-badge{display:inline-block;background:var(--bg-light);color:var(--accent);border:1px solid var(--border);border-radius:20px;font-size:.72rem;font-weight:700;padding:3px 10px;margin-bottom:12px;}
/* LIVE TICKER */
.live-ticker{background:linear-gradient(135deg,#0a0818,#1e1b4b);padding:60px 0;}
.live-entry{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:14px 20px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;color:#fff;}
.live-amount{color:#a5b4fc;font-weight:700;font-size:1rem;}
.live-tag{background:rgba(67,56,202,.3);color:#c7d2fe;font-size:.72rem;padding:3px 8px;border-radius:12px;}
/* WHY US */
.why-section{padding:80px 0;background:#f8faff;}
.why-card{background:#fff;border-radius:12px;padding:24px;height:100%;box-shadow:0 2px 10px rgba(0,0,0,.05);transition:all .3s;}
.why-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(67,56,202,.12);}
.why-icon{width:52px;height:52px;background:linear-gradient(135deg,var(--accent),#818cf8);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;}
.why-icon i{color:#fff;font-size:1.3rem;}
/* TESTIMONIALS */
.testimonials{padding:80px 0;background:#fff;}
.testi-card{background:#fff;border-radius:12px;padding:28px;border:1px solid var(--border);box-shadow:0 2px 12px rgba(0,0,0,.06);height:100%;}
.stars{color:var(--accent);font-size:1.1rem;margin-bottom:12px;}
/* STATS SECTION */
#stats-section{background:#1e1b4b;padding:80px 0;}
#stats-section .stat-num{font-size:2.8rem;font-weight:800;color:#a5b4fc;}
#stats-section .stat-lbl{color:rgba(255,255,255,.7);font-size:.9rem;}
/* FORM */
.form-section{padding:80px 0;background:var(--bg-light);}
.form-card{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 30px rgba(0,0,0,.1);}
.form-header{background:linear-gradient(135deg,#4338ca,#3730a3);padding:32px;color:#fff;}
.form-header h2{font-weight:800;margin-bottom:6px;}
.form-body{padding:32px;}
.btn-submit{background:var(--accent);color:#fff;border:none;border-radius:8px;padding:14px 32px;font-weight:700;font-size:1rem;width:100%;cursor:pointer;transition:all .3s;}
.btn-submit:hover{background:var(--accent-d);transform:translateY(-1px);}
/* TRUST BADGES */
.trust-badges{padding:40px 0;background:#fff;border-top:1px solid var(--border);}
.badge-item{text-align:center;padding:12px;}
.badge-item i{font-size:1.8rem;color:var(--accent);display:block;margin-bottom:6px;}
.badge-item span{font-size:.78rem;color:var(--muted);font-weight:600;}
/* FAQ */
.faq-section{padding:80px 0;background:var(--bg-light);}
.accordion-button:not(.collapsed){background:var(--bg-light);color:var(--accent);font-weight:600;}
.accordion-button:focus{box-shadow:0 0 0 .2rem rgba(67,56,202,.25);}
/* FOOTER */
footer{background:#0a0818;color:rgba(255,255,255,.7);padding:50px 0 24px;}
footer .brand{font-size:1.3rem;font-weight:800;color:#fff;}
footer .brand span{color:#818cf8;}
footer a{color:rgba(255,255,255,.6);text-decoration:none;font-size:.85rem;}
footer a:hover{color:#818cf8;}
footer hr{border-color:rgba(255,255,255,.1);}
/* MODAL */
.modal-header-indigo{background:linear-gradient(135deg,#4338ca,#3730a3);color:#fff;}
.modal-header-indigo .btn-close{filter:invert(1);}
/* ALERTS */
.alert-success-custom{background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;border-radius:8px;padding:14px 20px;margin-bottom:20px;}
.alert-error-custom{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;border-radius:8px;padding:14px 20px;margin-bottom:20px;}
@media(max-width:768px){.hero h1{font-size:1.8rem;}.stat-pills{flex-direction:column;}.stats-band .col-6{margin-bottom:20px;}}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
  <div class="container">
    <a class="navbar-brand" href="#">Verlust<span>Rückholung</span></a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
        <li class="nav-item"><a class="nav-link" href="#ai-section">KI-Analyse</a></li>
        <li class="nav-item"><a class="nav-link" href="#leistungen">Leistungen</a></li>
        <li class="nav-item"><a class="nav-link" href="#ablauf">Ablauf</a></li>
        <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
        <li class="nav-item ms-lg-2"><a class="nav-link nav-cta" href="#" data-bs-toggle="modal" data-bs-target="#contactModal">Kostenlos prüfen</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <canvas id="heroCanvas4"></canvas>
  <div class="container hero-content">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <div class="hero-badge"><i class="bi bi-cpu-fill me-1"></i> KI-gestützte Rückholung</div>
        <h1>Betrug erkannt.<br><span>Kapital gesichert.</span><br>Mit KI-Präzision.</h1>
        <p class="lead">Unsere Risikoanalyse-KI prüft Ihren Fall auf Basis von 150.000+ analysierten Transaktionen.</p>
        <a href="#" class="hero-btn" data-bs-toggle="modal" data-bs-target="#contactModal">Risikoanalyse starten →</a>
        <div class="stat-pills">
          <div class="stat-pill"><strong>87%</strong><span>Erfolgsquote</span></div>
          <div class="stat-pill"><strong>€48M+</strong><span>Rückgeholt</span></div>
          <div class="stat-pill"><strong>2.400+</strong><span>Fälle gelöst</span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- STATS BAND -->
<section class="stats-band">
  <div class="container">
    <div class="row text-center g-4">
      <div class="col-6 col-md-3"><div class="stat-item"><div class="num">87%</div><div class="lbl">Erfolgsquote</div></div></div>
      <div class="col-6 col-md-3"><div class="stat-item"><div class="num">€48M+</div><div class="lbl">Kapital rückgeholt</div></div></div>
      <div class="col-6 col-md-3"><div class="stat-item"><div class="num">2.400+</div><div class="lbl">Erfolgreiche Fälle</div></div></div>
      <div class="col-6 col-md-3"><div class="stat-item"><div class="num">18+</div><div class="lbl">Jahre Erfahrung</div></div></div>
    </div>
  </div>
</section>

<!-- TRUST BANNER -->
<div class="trust-banner">
  <div class="container">
    <div class="row g-3 justify-content-center text-center">
      <div class="col-6 col-md-2 trust-item justify-content-center"><i class="bi bi-shield-check-fill"></i> BaFin-konform</div>
      <div class="col-6 col-md-2 trust-item justify-content-center"><i class="bi bi-lock-fill"></i> SSL-verschlüsselt</div>
      <div class="col-6 col-md-2 trust-item justify-content-center"><i class="bi bi-award-fill"></i> Zertifiziert</div>
      <div class="col-6 col-md-2 trust-item justify-content-center"><i class="bi bi-eye-slash-fill"></i> DSGVO-konform</div>
      <div class="col-6 col-md-2 trust-item justify-content-center"><i class="bi bi-telephone-fill"></i> 24/7 Support</div>
    </div>
  </div>
</div>

<!-- NEWS TICKER -->
<div class="ticker-wrap">
  <div class="container-fluid px-0">
    <div class="ticker-inner d-flex align-items-center px-3">
      <div class="ticker-label flex-shrink-0">AKTUELL</div>
      <div class="overflow-hidden flex-grow-1">
        <div class="ticker-track">
          <span>München: €84.500 nach Krypto-Betrug zurückerhalten</span>
          <span>Hamburg: €127.000 Forex-Verlust erfolgreich rückgeholt</span>
          <span>Berlin: €55.200 Romance-Scam – Fall abgeschlossen</span>
          <span>Frankfurt: €210.000 CFD-Betrug – Rückholung erfolgreich</span>
          <span>Wien: €73.400 Binäre Optionen – Volle Rückerstattung</span>
          <span>Zürich: €98.600 bei Krypto-Plattform zurückgewonnen</span>
          <span>München: €84.500 nach Krypto-Betrug zurückerhalten</span>
          <span>Hamburg: €127.000 Forex-Verlust erfolgreich rückgeholt</span>
          <span>Berlin: €55.200 Romance-Scam – Fall abgeschlossen</span>
          <span>Frankfurt: €210.000 CFD-Betrug – Rückholung erfolgreich</span>
          <span>Wien: €73.400 Binäre Optionen – Volle Rückerstattung</span>
          <span>Zürich: €98.600 bei Krypto-Plattform zurückgewonnen</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- AI SECTION -->
<section id="ai-section" data-aos="fade-up">
  <div class="container">
    <div class="text-center mb-5">
      <span class="badge bg-primary bg-opacity-10 text-primary fw-600 mb-3 px-3 py-2" style="color:var(--accent)!important;background:var(--bg-light)!important;border:1px solid var(--border);border-radius:20px;">KI-Technologie</span>
      <h2 class="fw-800" style="color:var(--primary);">KI-Risikobewertung</h2>
      <p class="text-muted mx-auto" style="max-width:520px;">Unser Algorithmus analysiert Ihren Fall in Echtzeit und berechnet die Rückholwahrscheinlichkeit.</p>
    </div>
    <div class="row align-items-center g-5">
      <div class="col-lg-5 text-center">
        <div class="gauge-wrap">
          <svg class="gauge-svg" viewBox="0 0 220 220">
            <circle cx="110" cy="110" r="90" fill="none" stroke="#dde4ff" stroke-width="16"/>
            <circle id="gaugeArc" cx="110" cy="110" r="90" fill="none" stroke="url(#gaugeGrad)" stroke-width="16"
              stroke-linecap="round" stroke-dasharray="565.49" stroke-dashoffset="565.49"
              transform="rotate(-90 110 110)"/>
            <defs>
              <linearGradient id="gaugeGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" style="stop-color:#4338ca"/>
                <stop offset="100%" style="stop-color:#818cf8"/>
              </linearGradient>
            </defs>
            <text id="gaugeText" x="110" y="105" class="gauge-label" font-size="32" font-weight="800" fill="#1e1b4b">0%</text>
            <text x="110" y="135" class="gauge-label" font-size="13" fill="#6c757d">Rückholchance</text>
          </svg>
        </div>
        <p class="fw-700" style="color:var(--accent);">Rückholchance 87%</p>
      </div>
      <div class="col-lg-7">
        <div class="ai-metric" data-target="92">
          <div class="ai-metric-top"><span>Dokumentationsstärke</span><span>92%</span></div>
          <div class="ai-bar-bg"><div class="ai-bar-fill" data-width="92"></div></div>
        </div>
        <div class="ai-metric" data-target="85">
          <div class="ai-metric-top"><span>Plattform-Risikoindex</span><span>85%</span></div>
          <div class="ai-bar-bg"><div class="ai-bar-fill" data-width="85"></div></div>
        </div>
        <div class="ai-metric" data-target="78">
          <div class="ai-metric-top"><span>Transaktionsverfolgbarkeit</span><span>78%</span></div>
          <div class="ai-bar-bg"><div class="ai-bar-fill" data-width="78"></div></div>
        </div>
        <div class="ai-metric" data-target="88">
          <div class="ai-metric-top"><span>Rechtliche Erfolgswahrscheinlichkeit</span><span>88%</span></div>
          <div class="ai-bar-bg"><div class="ai-bar-fill" data-width="88"></div></div>
        </div>
        <div class="mt-4">
          <a href="#mainForm" class="hero-btn d-inline-block">Meinen Fall analysieren →</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-section" id="ablauf">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <h2 class="fw-800" style="color:var(--primary);">So funktioniert es</h2>
      <p class="text-muted">In 3 Schritten zu Ihrem Kapital</p>
    </div>
    <div class="row g-4 text-center">
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
        <div class="step-num">1</div>
        <h5 class="fw-700">Fall einreichen</h5>
        <p class="text-muted">Füllen Sie das Formular aus. Unsere KI analysiert Ihren Fall sofort und kostenlos.</p>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
        <div class="step-num">2</div>
        <h5 class="fw-700">KI-Analyse</h5>
        <p class="text-muted">Wir prüfen alle Transaktionen, Plattformstrukturen und rechtlichen Möglichkeiten.</p>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
        <div class="step-num">3</div>
        <h5 class="fw-700">Kapital zurückholen</h5>
        <p class="text-muted">Unser Expertenteam leitet alle notwendigen Maßnahmen ein und holt Ihr Geld zurück.</p>
      </div>
    </div>
  </div>
</section>

<!-- LEISTUNGEN -->
<section class="leistungen" id="leistungen">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <h2 class="fw-800" style="color:var(--primary);">Technologie & Expertise</h2>
      <p class="text-muted">Modernste Werkzeuge für Ihre Kapitalrückholung</p>
    </div>
    <div class="row g-4">
      <?php
      $leistungen = [
        ['bi-cpu','KI-Transaktionsanalyse','Automatische Auswertung aller Zahlungsströme mit Machine-Learning-Modellen.'],
        ['bi-shield-lock','Blockchain-Forensik','Nachverfolgung von Kryptowährungs-Transaktionen über alle Netzwerke.'],
        ['bi-graph-up-arrow','Fraud-Pattern-Erkennung','Identifikation bekannter Betrugsmuster aus unserer Datenbank.'],
        ['bi-briefcase-fill','Rechtliche Durchsetzung','Kooperation mit spezialisierten Anwaltskanzleien in 40+ Ländern.'],
        ['bi-bank','Bankrückbuchungen','Expertenwissen bei Chargeback-Verfahren und Bankdisputen.'],
        ['bi-headset','Persönliche Betreuung','Dedizierter Case-Manager für transparente Kommunikation.'],
      ];
      foreach ($leistungen as $i => [$icon, $title, $text]): ?>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= ($i % 3) * 100 ?>">
        <div class="leistung-card">
          <div class="leistung-icon"><i class="bi <?= $icon ?>"></i></div>
          <h5 class="fw-700 mb-2"><?= $title ?></h5>
          <p class="text-muted mb-0" style="font-size:.9rem;"><?= $text ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- FRAUD TYPES -->
<section class="fraud-section">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <h2 class="fw-800" style="color:var(--primary);">Betrugsarten die wir bekämpfen</h2>
      <p class="text-muted">Unsere KI ist spezialisiert auf alle gängigen Betrugsschemata</p>
    </div>
    <div class="row g-4">
      <?php
      $frauds = [
        ['bi-currency-bitcoin','Krypto-Betrug','Gefälschte Exchanges, Rug Pulls, Pump-and-Dump-Schemes und Wallet-Diebstahl.'],
        ['bi-graph-down-arrow','Forex & CFD','Unregulierte Broker, manipulierte Kurse, Auszahlungsverweigerung.'],
        ['bi-heart-fill','Romance Scam','Emotionale Manipulation mit dem Ziel, Investitionen zu erschleichen.'],
        ['bi-toggles','Binäre Optionen','Illegale Plattformen mit garantierten Verlusten durch Algorithmen.'],
      ];
      foreach ($frauds as $i => [$icon, $title, $text]): ?>
      <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>">
        <div class="fraud-card text-center">
          <div class="fraud-badge">Analysiert</div>
          <i class="bi <?= $icon ?>" style="font-size:2rem;color:var(--accent);display:block;margin-bottom:12px;"></i>
          <h6 class="fw-700"><?= $title ?></h6>
          <p class="text-muted mb-0" style="font-size:.85rem;"><?= $text ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- LIVE RECOVERY TICKER -->
<section class="live-ticker">
  <div class="container">
    <div class="text-center mb-4">
      <span style="color:#a5b4fc;font-size:.8rem;font-weight:700;letter-spacing:1px;">● LIVE</span>
      <h3 class="text-white fw-800 mt-2">Aktuelle Rückholungen</h3>
    </div>
    <div class="row g-3">
      <?php
      $recoveries = [
        ['München, DE','€84.500','Krypto-Betrug','vor 2 Std.'],
        ['Hamburg, DE','€127.000','Forex-Plattform','vor 4 Std.'],
        ['Wien, AT','€55.200','Romance Scam','vor 6 Std.'],
        ['Zürich, CH','€210.000','CFD-Broker','vor 8 Std.'],
        ['Berlin, DE','€73.400','Binäre Optionen','vor 11 Std.'],
        ['Frankfurt, DE','€98.600','Krypto-Exchange','vor 14 Std.'],
      ];
      foreach ($recoveries as [$city, $amount, $type, $time]): ?>
      <div class="col-md-6">
        <div class="live-entry">
          <div>
            <div style="color:rgba(255,255,255,.6);font-size:.78rem;"><?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="fw-600" style="font-size:.9rem;"><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?></div>
          </div>
          <div class="text-end">
            <div class="live-amount"><?= htmlspecialchars($amount, ENT_QUOTES, 'UTF-8') ?></div>
            <span class="live-tag">Rückgeholt</span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- WHY US -->
<section class="why-section">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <h2 class="fw-800" style="color:var(--primary);">Warum VerlustRückholung?</h2>
      <p class="text-muted">Die entscheidenden Vorteile auf einem Blick</p>
    </div>
    <div class="row g-4">
      <?php
      $whys = [
        ['bi-cpu-fill','KI-Präzision','150.000+ analysierte Fälle trainieren unsere Rückholalgorithmen täglich.'],
        ['bi-globe','Globales Netzwerk','Rechtsdurchsetzung in über 40 Ländern durch lokale Partner.'],
        ['bi-cash-coin','Erfolgsbasiert','Keine Vorabkosten. Wir verdienen nur, wenn Sie Ihr Geld zurückbekommen.'],
        ['bi-lightning-charge-fill','Schnelle Reaktion','Innerhalb von 24 Stunden erhalten Sie eine erste Fallbewertung.'],
        ['bi-incognito','Volle Diskretion','Absolute Vertraulichkeit und DSGVO-konforme Datenverarbeitung.'],
        ['bi-patch-check-fill','Zertifiziert','Zertifizierte Forensik-Experten und lizenzierte Rechtsberater.'],
      ];
      foreach ($whys as $i => [$icon, $title, $text]): ?>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= ($i % 3) * 100 ?>">
        <div class="why-card">
          <div class="why-icon"><i class="bi <?= $icon ?>"></i></div>
          <h6 class="fw-700 mb-2"><?= $title ?></h6>
          <p class="text-muted mb-0" style="font-size:.88rem;"><?= $text ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="testimonials">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <h2 class="fw-800" style="color:var(--primary);">Was unsere Mandanten sagen</h2>
    </div>
    <div class="row g-4">
      <?php
      $testis = [
        ['Thomas K.','München','€84.500 zurückerhalten','Krypto-Betrug','Ich hatte kaum noch Hoffnung – nach 4 Monaten hatte ich mein gesamtes Kapital zurück. Das Team war professionell und transparent.'],
        ['Sabine M.','Wien','€127.000 gesichert','Forex-Betrug','Die KI-Analyse hat sofort die Schwachstellen des Brokers identifiziert. Innerhalb von 6 Monaten war alles erledigt.'],
        ['René F.','Zürich','€210.000 rückgeholt','CFD-Plattform','Professionell, diskret und erfolgreich. Ich kann VerlustRückholung jedem empfehlen, der Opfer von Anlagebetrug wurde.'],
      ];
      foreach ($testis as $i => [$name, $city, $amount, $type, $quote]): ?>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>">
        <div class="testi-card">
          <div class="stars">★★★★★</div>
          <p class="text-muted mb-3" style="font-size:.9rem;font-style:italic;">"<?= htmlspecialchars($quote, ENT_QUOTES, 'UTF-8') ?>"</p>
          <div class="d-flex justify-content-between align-items-end">
            <div>
              <div class="fw-700"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></div>
              <div style="font-size:.78rem;color:var(--muted);"><?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div style="font-size:.9rem;font-weight:700;color:var(--accent);"><?= htmlspecialchars($amount, ENT_QUOTES, 'UTF-8') ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- STATISTICS SECTION -->
<section id="stats-section">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="fw-800 text-white">Unsere Erfolgszahlen</h2>
      <p style="color:rgba(255,255,255,.6);">Bewiesene Ergebnisse seit über 18 Jahren</p>
    </div>
    <div class="row g-4 text-center">
      <div class="col-6 col-md-3"><div class="stat-num" data-target="87" data-suffix="%">0%</div><div class="stat-lbl">Erfolgsquote</div></div>
      <div class="col-6 col-md-3"><div class="stat-num" data-target="48" data-prefix="€" data-suffix="M+">€0M+</div><div class="stat-lbl">Rückgeholt</div></div>
      <div class="col-6 col-md-3"><div class="stat-num" data-target="2400" data-suffix="+">0+</div><div class="stat-lbl">Fälle gelöst</div></div>
      <div class="col-6 col-md-3"><div class="stat-num" data-target="40" data-suffix="+">0+</div><div class="stat-lbl">Länder</div></div>
    </div>
  </div>
</section>

<!-- CONTACT FORM -->
<section class="form-section" id="mainForm">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <?php if ($success): ?>
        <div class="alert-success-custom"><i class="bi bi-check-circle-fill me-2"></i><strong>Erfolgreich übermittelt!</strong> Wir melden uns innerhalb von 24 Stunden bei Ihnen.</div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert-error-custom"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?></div>
        <?php endif; ?>
        <div class="form-card" data-aos="fade-up">
          <div class="form-header">
            <h2>Kostenlose Fallprüfung</h2>
            <p class="mb-0 opacity-75">Unverbindlich · Vertraulich · Innerhalb 24h Rückmeldung</p>
          </div>
          <div class="form-body">
            <form id="mainForm" action="../submit_lead.php" method="POST" novalidate class="needs-validation">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="visit_id" value="" data-visit-id>
              <input type="hidden" name="theme" value="design4">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-600">Vorname *</label>
                  <input type="text" class="form-control" name="first_name" required minlength="2" placeholder="Max">
                  <div class="invalid-feedback">Bitte geben Sie Ihren Vornamen ein.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-600">Nachname *</label>
                  <input type="text" class="form-control" name="last_name" required minlength="2" placeholder="Mustermann">
                  <div class="invalid-feedback">Bitte geben Sie Ihren Nachnamen ein.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-600">E-Mail-Adresse *</label>
                  <input type="email" class="form-control" name="email" required placeholder="max@beispiel.de">
                  <div class="invalid-feedback">Bitte geben Sie eine gültige E-Mail-Adresse ein.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-600">Telefon</label>
                  <input type="tel" class="form-control" name="phone" placeholder="+49 123 456789">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-600">Land *</label>
                  <select class="form-select" name="country" required>
                    <option value="">Land wählen …</option>
                    <option value="DE">Deutschland</option>
                    <option value="AT">Österreich</option>
                    <option value="CH">Schweiz</option>
                    <option value="LU">Luxemburg</option>
                    <option value="LI">Liechtenstein</option>
                    <option value="Sonstige">Sonstige</option>
                  </select>
                  <div class="invalid-feedback">Bitte wählen Sie Ihr Land.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-600">Geschätzter Verlust (€) *</label>
                  <input type="number" class="form-control" name="amount_lost" required min="500" placeholder="z.B. 15000">
                  <div class="invalid-feedback">Bitte geben Sie den ungefähren Verlustbetrag an.</div>
                </div>
                <div class="col-12">
                  <label class="form-label fw-600">Art des Betrugs *</label>
                  <select class="form-select" name="platform_category" required>
                    <option value="">Betrugsart wählen …</option>
                    <option value="Krypto">Kryptowährungen</option>
                    <option value="Forex">Forex</option>
                    <option value="Binaere_Optionen">Binäre Optionen</option>
                    <option value="Romance_Scam">Romance Scam</option>
                    <option value="CFD">CFD</option>
                    <option value="Sonstiges">Sonstiges</option>
                  </select>
                  <div class="invalid-feedback">Bitte wählen Sie die Betrugsart.</div>
                </div>
                <div class="col-12">
                  <label class="form-label fw-600">Fallbeschreibung *</label>
                  <textarea class="form-control" name="case_description" rows="4" required minlength="30" placeholder="Bitte beschreiben Sie kurz, was passiert ist …"></textarea>
                  <div class="invalid-feedback">Bitte beschreiben Sie Ihren Fall (min. 30 Zeichen).</div>
                </div>
                <div class="col-12">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="privacy" id="privacy" required>
                    <label class="form-check-label" for="privacy" style="font-size:.85rem;">
                      Ich habe die <a href="#" style="color:var(--accent);">Datenschutzerklärung</a> gelesen und akzeptiere die Verarbeitung meiner Daten zur Fallbearbeitung. *
                    </label>
                    <div class="invalid-feedback">Bitte stimmen Sie der Datenschutzerklärung zu.</div>
                  </div>
                </div>
                <div class="col-12">
                  <div id="mainFormMsg"></div>
                  <button type="submit" class="btn-submit">Kostenlose Risikoanalyse anfordern →</button>
                  <p class="text-center text-muted mt-2" style="font-size:.78rem;"><i class="bi bi-lock-fill"></i> SSL-verschlüsselt · DSGVO-konform · Kostenlos & unverbindlich</p>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- TRUST BADGES -->
<div class="trust-badges">
  <div class="container">
    <div class="row justify-content-center g-3">
      <div class="col-6 col-md-2 badge-item"><i class="bi bi-shield-fill-check"></i><span>BaFin-konform</span></div>
      <div class="col-6 col-md-2 badge-item"><i class="bi bi-award-fill"></i><span>ISO-zertifiziert</span></div>
      <div class="col-6 col-md-2 badge-item"><i class="bi bi-lock-fill"></i><span>SSL-Verschlüsselung</span></div>
      <div class="col-6 col-md-2 badge-item"><i class="bi bi-eye-slash-fill"></i><span>DSGVO-konform</span></div>
      <div class="col-6 col-md-2 badge-item"><i class="bi bi-patch-check-fill"></i><span>Geprüfte Experten</span></div>
    </div>
  </div>
</div>

<!-- FAQ -->
<section class="faq-section" id="faq">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <h2 class="fw-800" style="color:var(--primary);">Häufige Fragen</h2>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="accordion" id="faqAccordion">
          <?php
          $faqs = [
            ['Ist die Erstberatung wirklich kostenlos?','Ja, absolut. Die Prüfung Ihres Falls und die erste KI-Risikoanalyse sind vollständig kostenlos und unverbindlich.'],
            ['Wie lange dauert ein Rückholverfahren?','Je nach Komplexität des Falls dauert ein Verfahren zwischen 3 und 12 Monaten. Einfachere Fälle werden oft in 60–90 Tagen abgeschlossen.'],
            ['Was kostet der Service?','Wir arbeiten rein erfolgsbasiert. Sie zahlen nur eine Provision, wenn wir Ihr Geld erfolgreich zurückgeholt haben.'],
            ['Welche Informationen brauche ich für den Start?','Transaktionsnachweise, Kommunikation mit dem Broker/Betrüger, Kontodaten und mögliche Verträge sind hilfreich – aber kein Muss für die erste Analyse.'],
            ['Ist mein Fall noch rückholbar?','Das hängt von mehreren Faktoren ab. Unsere KI analysiert genau das in wenigen Minuten – kostenlos und unverbindlich.'],
          ];
          foreach ($faqs as $i => [$q, $a]): ?>
          <div class="accordion-item border-0 mb-2" data-aos="fade-up" data-aos-delay="<?= $i * 60 ?>">
            <h2 class="accordion-header">
              <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?> rounded fw-600" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>">
                <?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>
              </button>
            </h2>
            <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#faqAccordion">
              <div class="accordion-body text-muted"><?= htmlspecialchars($a, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <div class="brand mb-3">Verlust<span>Rückholung</span></div>
        <p style="font-size:.85rem;color:rgba(255,255,255,.6);">KI-gestützte Kapitalrückholung bei Anlagebetrug. Professionell, diskret, erfolgreich.</p>
      </div>
      <div class="col-md-2">
        <div class="fw-700 text-white mb-3" style="font-size:.9rem;">Service</div>
        <ul class="list-unstyled">
          <li class="mb-1"><a href="#ai-section">KI-Analyse</a></li>
          <li class="mb-1"><a href="#leistungen">Leistungen</a></li>
          <li class="mb-1"><a href="#ablauf">Ablauf</a></li>
        </ul>
      </div>
      <div class="col-md-2">
        <div class="fw-700 text-white mb-3" style="font-size:.9rem;">Betrugsarten</div>
        <ul class="list-unstyled">
          <li class="mb-1"><a href="#mainForm">Krypto-Betrug</a></li>
          <li class="mb-1"><a href="#mainForm">Forex & CFD</a></li>
          <li class="mb-1"><a href="#mainForm">Romance Scam</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <div class="fw-700 text-white mb-3" style="font-size:.9rem;">Kontakt</div>
        <p style="font-size:.85rem;color:rgba(255,255,255,.6);" class="mb-1"><i class="bi bi-envelope me-2"></i>info@verlustrueckholung.de</p>
        <p style="font-size:.85rem;color:rgba(255,255,255,.6);" class="mb-1"><i class="bi bi-telephone me-2"></i>+49 800 000 0000</p>
        <p style="font-size:.85rem;color:rgba(255,255,255,.6);"><i class="bi bi-clock me-2"></i>Mo–Fr 9–18 Uhr</p>
      </div>
    </div>
    <hr>
    <div class="d-flex flex-wrap justify-content-between align-items-center" style="font-size:.78rem;color:rgba(255,255,255,.4);">
      <span>© <?= date('Y') ?> VerlustRückholung. Alle Rechte vorbehalten.</span>
      <div class="d-flex gap-3">
        <a href="#">Impressum</a>
        <a href="#">Datenschutz</a>
        <a href="#">AGB</a>
      </div>
    </div>
  </div>
</footer>

<!-- ENGAGEMENT MODAL -->
<div class="modal fade" id="engModal" tabindex="-1" aria-labelledby="engModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header modal-header-indigo">
        <h5 class="modal-title fw-800" id="engModalLabel">KI-Risikoanalyse – Ihr Fall jetzt prüfen</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>
      <div class="modal-body p-4">
        <p class="text-muted mb-4" style="font-size:.9rem;">Kostenlose & unverbindliche Erstprüfung. Kein Risiko für Sie.</p>
        <form id="engForm" action="../submit_lead.php" method="POST" novalidate class="needs-validation">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="visit_id" value="" data-visit-id>
          <input type="hidden" name="theme" value="design4_modal">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-600">Vorname *</label>
              <input type="text" class="form-control" name="first_name" required minlength="2" placeholder="Max">
              <div class="invalid-feedback">Pflichtfeld</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Nachname *</label>
              <input type="text" class="form-control" name="last_name" required minlength="2" placeholder="Mustermann">
              <div class="invalid-feedback">Pflichtfeld</div>
            </div>
            <div class="col-12">
              <label class="form-label fw-600">E-Mail *</label>
              <input type="email" class="form-control" name="email" required placeholder="max@beispiel.de">
              <div class="invalid-feedback">Gültige E-Mail erforderlich</div>
            </div>
            <div class="col-12">
              <label class="form-label fw-600">Geschätzter Verlust (€) *</label>
              <input type="number" class="form-control" name="amount_lost" required min="500" placeholder="z.B. 15000">
              <div class="invalid-feedback">Pflichtfeld</div>
            </div>
            <div class="col-12">
              <label class="form-label fw-600">Art des Betrugs *</label>
              <select class="form-select" name="platform_category" required>
                <option value="">Betrugsart wählen …</option>
                <option value="Krypto">Kryptowährungen</option>
                <option value="Forex">Forex</option>
                <option value="Binaere_Optionen">Binäre Optionen</option>
                <option value="Romance_Scam">Romance Scam</option>
                <option value="CFD">CFD</option>
                <option value="Sonstiges">Sonstiges</option>
              </select>
              <div class="invalid-feedback">Pflichtfeld</div>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="privacy" id="privacyModal" required>
                <label class="form-check-label" for="privacyModal" style="font-size:.82rem;">
                  Ich akzeptiere die <a href="#" style="color:var(--accent);">Datenschutzerklärung</a> *
                </label>
                <div class="invalid-feedback">Zustimmung erforderlich</div>
              </div>
            </div>
            <div class="col-12">
              <div id="engFormMsg"></div>
              <button type="submit" class="btn-submit">Jetzt kostenlos prüfen →</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
(function () {
  'use strict';

  // NAVBAR SCROLL
  var nav = document.getElementById('mainNav');
  window.addEventListener('scroll', function () {
    nav.classList.toggle('scrolled', window.scrollY > 60);
  });

  // HERO CANVAS – Animated Data Flow Dashboard
  (function () {
    var canvas = document.getElementById('heroCanvas4');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');

    var bars = [
      { label: 'Eingang', target: 55, current: 10, color: '#6366f1' },
      { label: 'Analyse', target: 70, current: 10, color: '#818cf8' },
      { label: 'Prüfung', target: 60, current: 10, color: '#a5b4fc' },
      { label: 'Rückgeholt', target: 92, current: 10, color: '#4338ca', glow: true },
      { label: 'Abgeschl.', target: 75, current: 10, color: '#6366f1' },
      { label: 'Gesichert', target: 65, current: 10, color: '#818cf8' },
    ];

    var dirs = bars.map(function () { return 1; });
    var speeds = bars.map(function (b) { return 0.3 + Math.random() * 0.4; });

    function resize() {
      canvas.width = canvas.offsetWidth;
      canvas.height = canvas.offsetHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    function animate() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      var w = canvas.width;
      var h = canvas.height;
      var n = bars.length;
      var barW = Math.min(60, (w / n) * 0.5);
      var gap = w / n;

      for (var i = 0; i < n; i++) {
        bars[i].current += dirs[i] * speeds[i];
        var min = bars[i].target - 15;
        var max = bars[i].target + 8;
        if (bars[i].label === 'Rückgeholt') { min = 88; max = 97; }
        if (bars[i].current >= max) dirs[i] = -1;
        if (bars[i].current <= min) dirs[i] = 1;

        var pct = bars[i].current / 100;
        var barH = h * 0.65 * pct;
        var x = gap * i + gap / 2 - barW / 2;
        var y = h * 0.8 - barH;

        if (bars[i].glow) {
          ctx.save();
          ctx.shadowColor = '#4338ca';
          ctx.shadowBlur = 20;
        }
        var grad = ctx.createLinearGradient(x, y + barH, x, y);
        grad.addColorStop(0, bars[i].color + 'aa');
        grad.addColorStop(1, bars[i].color);
        ctx.fillStyle = grad;
        ctx.beginPath();
        ctx.roundRect ? ctx.roundRect(x, y, barW, barH, [4, 4, 0, 0]) : ctx.rect(x, y, barW, barH);
        ctx.fill();
        if (bars[i].glow) ctx.restore();

        ctx.fillStyle = 'rgba(255,255,255,0.5)';
        ctx.font = '11px Inter,sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(bars[i].label, x + barW / 2, h * 0.8 + 16);
        ctx.fillStyle = bars[i].glow ? '#a5b4fc' : 'rgba(255,255,255,0.6)';
        ctx.font = (bars[i].glow ? 'bold ' : '') + '12px Inter,sans-serif';
        ctx.fillText(Math.round(bars[i].current) + '%', x + barW / 2, y - 6);
      }
      requestAnimationFrame(animate);
    }
    animate();
  })();

  // AI SECTION – Gauge + Progress bars (IntersectionObserver)
  (function () {
    var arc = document.getElementById('gaugeArc');
    var txt = document.getElementById('gaugeText');
    var aiSection = document.getElementById('ai-section');
    var gaugeTriggered = false;

    var circumference = 2 * Math.PI * 90;

    function animateGauge() {
      var target = 87;
      var current = 0;
      var step = target / 80;
      function tick() {
        if (current >= target) { current = target; }
        var offset = circumference - (current / 100) * circumference;
        arc.style.strokeDashoffset = offset;
        txt.textContent = Math.round(current) + '%';
        if (current < target) { current += step; requestAnimationFrame(tick); }
      }
      tick();
    }

    function animateBars() {
      document.querySelectorAll('.ai-bar-fill').forEach(function (bar) {
        var w = bar.getAttribute('data-width');
        bar.style.width = w + '%';
      });
    }

    var obs = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting && !gaugeTriggered) {
          gaugeTriggered = true;
          animateGauge();
          animateBars();
        }
      });
    }, { threshold: 0.3 });
    if (aiSection) obs.observe(aiSection);
  })();

  // COUNTUP
  function countUp(el) {
    var target = parseInt(el.getAttribute('data-target'), 10);
    var prefix = el.getAttribute('data-prefix') || '';
    var suffix = el.getAttribute('data-suffix') || '';
    var duration = 1800;
    var step = target / (duration / 16);
    var current = 0;
    function tick() {
      current += step;
      if (current >= target) current = target;
      el.textContent = prefix + Math.round(current) + suffix;
      if (current < target) requestAnimationFrame(tick);
    }
    tick();
  }

  var statsSection = document.getElementById('stats-section');
  var statsTriggered = false;
  if (statsSection) {
    var statsObs = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting && !statsTriggered) {
          statsTriggered = true;
          statsSection.querySelectorAll('[data-target]').forEach(countUp);
        }
      });
    }, { threshold: 0.3 });
    statsObs.observe(statsSection);
  }

  // FORM SETUP
  function setupForm(formId, theme) {
    var form = document.getElementById(formId);
    if (!form) return;
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
      var msgEl = document.getElementById(formId + 'Msg');
      var btn = form.querySelector('[type=submit]');
      btn.disabled = true;
      btn.textContent = 'Wird gesendet …';
      if (msgEl) msgEl.innerHTML = '';
      var fd = new FormData(form);
      fd.set('theme', theme);
      fetch('../submit_lead.php', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d && d.success) {
            if (msgEl) msgEl.innerHTML = '<div class="alert-success-custom mt-3"><i class="bi bi-check-circle-fill me-2"></i>Vielen Dank! Wir melden uns innerhalb von 24 Stunden.</div>';
            form.reset(); form.classList.remove('was-validated');
          } else {
            if (msgEl) msgEl.innerHTML = '<div class="alert-error-custom mt-3"><i class="bi bi-exclamation-triangle-fill me-2"></i>' + (d && d.message ? d.message : 'Ein Fehler ist aufgetreten.') + '</div>';
          }
        })
        .catch(function () {
          if (msgEl) msgEl.innerHTML = '<div class="alert-error-custom mt-3">Netzwerkfehler. Bitte versuchen Sie es erneut.</div>';
        })
        .finally(function () { btn.disabled = false; btn.textContent = 'Kostenlose Risikoanalyse anfordern →'; });
    });
  }

  setupForm('mainForm', 'design4');
  setupForm('engForm', 'design4_modal');
  setupForm('contactModalForm', 'contact_modal');

  AOS.init({ duration: 700, once: true, offset: 60 });

  // MODAL TRIGGER
  setTimeout(function () {
    var m = document.getElementById('engModal');
    if (m && !sessionStorage.getItem('engShown')) {
      sessionStorage.setItem('engShown', '1');
      new bootstrap.Modal(m).show();
    }
  }, <?= $modal_delay * 1000 ?>);

})();
</script>

<script>
(function(){
'use strict';
var visitId=null,startTime=Date.now();
(function logVisit(){
  var sp=new URLSearchParams(window.location.search);
  var fd=new FormData();fd.append('action','visit');fd.append('referrer',document.referrer||'');fd.append('landing_page',window.location.href.substring(0,512));
  ['utm_source','utm_medium','utm_campaign','utm_content','utm_term','gclid'].forEach(function(k){var v=sp.get(k);if(v)fd.append(k,v.substring(0,200));});
  fetch('../track.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){if(d&&d.visit_id){visitId=d.visit_id;document.querySelectorAll('[data-visit-id]').forEach(function(el){el.value=visitId;});}})
    .catch(function(){});
})();
function sendTime(){if(!visitId)return;var e=Math.round((Date.now()-startTime)/1000);var b=new Blob(['action=update&visit_id='+encodeURIComponent(visitId)+'&time_on_site='+encodeURIComponent(e)],{type:'application/x-www-form-urlencoded'});if(navigator.sendBeacon)navigator.sendBeacon('../track.php',b);else fetch('../track.php',{method:'POST',body:b,keepalive:true}).catch(function(){});}
window.addEventListener('pagehide',sendTime);window.addEventListener('beforeunload',sendTime);
document.addEventListener('visibilitychange',function(){if(document.visibilityState==='hidden')sendTime();});
setInterval(sendTime,30000);
})();
</script>
<!-- ===== CONTACT MODAL ===== -->
<div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
    <div class="modal-content border-0 shadow-xl" style="border-radius:16px;overflow:hidden;">
      <div class="modal-header text-white py-3 px-4" style="background:linear-gradient(135deg,#4338ca,#3730a3);border:none;">
        <div>
          <div class="small fw-semibold opacity-75 mb-1"><i class="bi bi-robot me-1"></i>KI-gestützte Fallanalyse · Kostenlos &amp; Unverbindlich</div>
          <h5 class="modal-title fw-bold mb-0" id="contactModalLabel">Kostenlose Erstprüfung – Ihr Fall in 72h analysiert</h5>
        </div>
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div class="row g-0">
          <div class="col-lg-4 d-none d-lg-flex flex-column justify-content-between p-4" style="background:#f8faff;border-right:1px solid #e2e8f0;">
            <div>
              <div class="fw-bold text-dark mb-3">Warum VerlustRückholung?</div>
              <ul class="list-unstyled small text-muted">
                <li class="mb-3 d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#4338ca;flex-shrink:0"></i><span><strong class="text-dark">87% Erfolgsquote</strong> – verifiziert über 2.400 Mandate</span></li>
                <li class="mb-3 d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#4338ca;flex-shrink:0"></i><span><strong class="text-dark">€0 Vorauszahlung</strong> – Sie zahlen nur bei Erfolg</span></li>
                <li class="mb-3 d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#4338ca;flex-shrink:0"></i><span><strong class="text-dark">72h Erstantwort</strong> – KI prüft Ihren Fall sofort</span></li>
                <li class="mb-3 d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#4338ca;flex-shrink:0"></i><span><strong class="text-dark">40+ Länder</strong> – Internationales Expertennetzwerk</span></li>
                <li class="d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#4338ca;flex-shrink:0"></i><span><strong class="text-dark">DSGVO-konform</strong> – Höchste Datensicherheit</span></li>
              </ul>
            </div>
            <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:1rem;">
              <div class="small text-muted mb-2">Zuletzt erfolgreich zurückgeholt:</div>
              <div class="fw-bold text-dark">€ 127.400</div>
              <div class="small text-muted">Krypto-Betrug · München · vor 2h</div>
              <hr class="my-2">
              <div class="fw-bold text-dark">€ 84.900</div>
              <div class="small text-muted">Forex-Broker · Wien · vor 5h</div>
            </div>
          </div>
          <div class="col-lg-8 p-4">
            <?php if (isset($_GET['success']) && $_GET['success'] === '1'): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><strong>Vielen Dank!</strong> Wir melden uns innerhalb von 24 Stunden.</div>
            <?php endif; ?>
            <form action="../submit_lead.php" method="POST" id="contactModalForm" novalidate>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="lead_source" value="contact_modal">
              <input type="hidden" name="visit_id" data-visit-id value="">
              <div class="row g-3">
                <div class="col-sm-6">
                  <label class="form-label fw-semibold small">Vorname *</label>
                  <input type="text" name="first_name" class="form-control" placeholder="Max" required>
                  <div class="invalid-feedback">Bitte eingeben.</div>
                </div>
                <div class="col-sm-6">
                  <label class="form-label fw-semibold small">Nachname *</label>
                  <input type="text" name="last_name" class="form-control" placeholder="Mustermann" required>
                  <div class="invalid-feedback">Bitte eingeben.</div>
                </div>
                <div class="col-sm-6">
                  <label class="form-label fw-semibold small">E-Mail *</label>
                  <input type="email" name="email" class="form-control" placeholder="max@example.de" required>
                  <div class="invalid-feedback">Gültige E-Mail erforderlich.</div>
                </div>
                <div class="col-sm-6">
                  <label class="form-label fw-semibold small">Telefon (optional)</label>
                  <input type="tel" name="phone" class="form-control" placeholder="+49 123 456789">
                </div>
                <div class="col-sm-6">
                  <label class="form-label fw-semibold small">Land *</label>
                  <select name="country" class="form-select" required>
                    <option value="">Land auswählen...</option>
                    <option>🇩🇪 Deutschland</option><option>🇦🇹 Österreich</option><option>🇨🇭 Schweiz</option>
                    <option>🇺🇸 USA</option><option>🇬🇧 Vereinigtes Königreich</option><option>🌍 Anderes Land</option>
                  </select>
                  <div class="invalid-feedback">Bitte Land wählen.</div>
                </div>
                <div class="col-sm-6">
                  <label class="form-label fw-semibold small">Geschätzter Verlust (€) *</label>
                  <input type="number" name="amount_lost" class="form-control" placeholder="10000" min="1" required>
                  <div class="invalid-feedback">Bitte Betrag eingeben.</div>
                </div>
                <div class="col-12">
                  <label class="form-label fw-semibold small">Betrugsart *</label>
                  <select name="platform_category" class="form-select" required>
                    <option value="">Betrugsart wählen...</option>
                    <option>Krypto-Betrug</option><option>Forex-Betrug</option><option>Fake-Broker</option>
                    <option>Romance-Scam</option><option>Binäre Optionen</option><option>Andere</option>
                  </select>
                  <div class="invalid-feedback">Bitte Betrugsart wählen.</div>
                </div>
                <div class="col-12">
                  <label class="form-label fw-semibold small">Kurze Fallbeschreibung *</label>
                  <textarea name="case_description" class="form-control" rows="3" placeholder="Bitte beschreiben Sie kurz, was passiert ist..." required></textarea>
                  <div class="invalid-feedback">Bitte kurz beschreiben.</div>
                </div>
                <div class="col-12">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="privacy" id="privacyContactModal" required>
                    <label class="form-check-label small text-muted" for="privacyContactModal">
                      Ich stimme der <a href="#" style="color:#4338ca">Datenschutzerklärung</a> zu und bin einverstanden, dass meine Daten zur Fallbearbeitung verwendet werden. *
                    </label>
                    <div class="invalid-feedback">Bitte Datenschutz bestätigen.</div>
                  </div>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn w-100 fw-bold py-3" style="background:#4338ca;color:#fff;font-size:1rem;border:none;border-radius:10px;">
                    <i class="bi bi-send-fill me-2"></i>Kostenlose Analyse anfordern →
                  </button>
                  <p class="text-center text-muted mt-2 mb-0" style="font-size:.75rem;"><i class="bi bi-lock-fill me-1"></i>SSL-gesichert · DSGVO-konform · Keine Vorauszahlung</p>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
