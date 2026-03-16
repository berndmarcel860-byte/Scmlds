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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap">
<style>
:root{
  --primary:#1f2937;--accent:#dc2626;--accent-d:#b91c1c;
  --text:#1a202c;--muted:#6c757d;--bg-light:#fef2f2;--border:#fecaca;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;color:var(--text);background:#fff;overflow-x:hidden;}
/* NAVBAR */
.navbar-custom{background:rgba(10,22,40,0.95);backdrop-filter:blur(10px);position:fixed;top:0;width:100%;z-index:1050;transition:background .3s;padding:.8rem 0;}
.navbar-custom.scrolled{background:rgba(10,22,40,1);box-shadow:0 2px 20px rgba(0,0,0,.4);}
.navbar-brand-text{font-size:1.4rem;font-weight:800;color:#fff;text-decoration:none;letter-spacing:-.5px;}
.navbar-brand-text span{color:#f87171;}
.nav-link-custom{color:rgba(255,255,255,.8)!important;font-weight:500;font-size:.9rem;transition:color .2s;padding:.4rem .9rem!important;}
.nav-link-custom:hover{color:#f87171!important;}
.nav-cta{background:var(--accent);color:#fff!important;border-radius:6px;padding:.45rem 1.1rem!important;font-weight:600;}
.nav-cta:hover{background:var(--accent-d)!important;}
/* HERO */
.hero{position:relative;min-height:100vh;display:flex;align-items:center;background:linear-gradient(135deg,#111827 0%,#1f2937 50%,#111827 100%);overflow:hidden;}
#heroCanvas5{position:absolute;inset:0;width:100%;height:100%;opacity:.6;}
.hero-content{position:relative;z-index:2;}
.hero-badge{display:inline-block;background:rgba(220,38,38,.15);border:1px solid rgba(220,38,38,.4);color:#f87171;font-size:.8rem;font-weight:600;padding:.35rem .9rem;border-radius:50px;letter-spacing:.5px;margin-bottom:1.2rem;}
.hero h1{font-size:clamp(2rem,4.5vw,3.4rem);font-weight:900;color:#fff;line-height:1.15;margin-bottom:1.2rem;}
.hero h1 .hl{color:#f87171;}
.hero-sub{font-size:1.1rem;color:rgba(255,255,255,.75);max-width:560px;line-height:1.7;margin-bottom:2rem;}
.hero-cta{background:var(--accent);color:#fff;border:none;padding:.9rem 2.2rem;font-size:1.05rem;font-weight:700;border-radius:8px;cursor:pointer;transition:background .2s,transform .15s;text-decoration:none;display:inline-block;}
.hero-cta:hover{background:var(--accent-d);transform:translateY(-2px);color:#fff;}
.stat-pill{display:inline-flex;align-items:center;gap:.5rem;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#fff;padding:.5rem 1.1rem;border-radius:50px;font-size:.85rem;font-weight:500;}
.stat-pill .val{color:#f87171;font-weight:800;}
/* STATS BAND */
.stats-band{background:#fff;padding:2.5rem 0;border-bottom:1px solid #f1f5f9;}
.stat-item-band{text-align:center;}
.stat-item-band .num{font-size:2.2rem;font-weight:900;color:var(--accent);line-height:1;}
.stat-item-band .lbl{font-size:.85rem;color:var(--muted);font-weight:500;margin-top:.3rem;}
/* TRUST BANNER */
.trust-banner{background:var(--bg-light);border-top:3px solid var(--accent);border-bottom:1px solid var(--border);padding:1.4rem 0;}
.trust-item{display:flex;align-items:center;gap:.7rem;font-size:.9rem;font-weight:600;color:var(--text);}
.trust-item i{color:var(--accent);font-size:1.2rem;}
/* NEWS TICKER */
.news-ticker{background:var(--primary);color:#fff;padding:.7rem 0;overflow:hidden;}
.ticker-label{background:var(--accent);color:#fff;font-size:.75rem;font-weight:700;padding:.25rem .7rem;border-radius:4px;white-space:nowrap;margin-right:1rem;flex-shrink:0;}
.ticker-track{display:flex;white-space:nowrap;animation:tickerMove 38s linear infinite;}
.ticker-track:hover{animation-play-state:paused;}
.ticker-item{font-size:.85rem;padding:0 2rem;opacity:.9;}
.ticker-item::after{content:'●';margin-left:2rem;color:var(--accent);opacity:.6;}
@keyframes tickerMove{0%{transform:translateX(0);}100%{transform:translateX(-50%);}}
/* AI SECTION */
#ai-section{background:var(--bg-light);padding:5rem 0;}
#ai-section h2{font-size:2rem;font-weight:800;color:var(--text);margin-bottom:.5rem;}
#ai-section .lead{color:var(--muted);font-size:1rem;}
#aiRadarCanvas{display:block;margin:1.5rem auto;border-radius:50%;border:2px solid var(--border);}
.counter-box{background:#fff;border:2px solid var(--accent);border-radius:12px;padding:1.4rem 1rem;text-align:center;}
.counter-box .c-num{font-size:1.9rem;font-weight:900;color:var(--accent);line-height:1;}
.counter-box .c-lbl{font-size:.8rem;color:var(--muted);margin-top:.35rem;font-weight:500;}
/* HOW IT WORKS */
.hiw-section{padding:5rem 0;background:#fff;}
.step-circle{width:60px;height:60px;border-radius:50%;background:var(--accent);color:#fff;font-size:1.4rem;font-weight:800;display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;}
.hiw-step h5{font-size:1.05rem;font-weight:700;color:var(--text);}
.hiw-step p{font-size:.9rem;color:var(--muted);line-height:1.6;}
/* LEISTUNGEN */
.leistungen-section{background:#f8fafc;padding:5rem 0;}
.leistung-card{background:#fff;border-radius:12px;border-top:3px solid var(--accent);box-shadow:0 2px 16px rgba(0,0,0,.06);padding:2rem 1.5rem;height:100%;transition:background .2s,transform .2s,box-shadow .2s;}
.leistung-card:hover{background:var(--bg-light);transform:translateY(-4px);box-shadow:0 8px 28px rgba(220,38,38,.12);}
.leistung-card .icon{font-size:2rem;color:var(--accent);margin-bottom:1rem;}
.leistung-card h5{font-size:1.05rem;font-weight:700;margin-bottom:.6rem;}
.leistung-card p{font-size:.9rem;color:var(--muted);line-height:1.6;}
/* FRAUD TYPES */
.fraud-section{padding:5rem 0;background:#fff;}
.fraud-card{border-radius:12px;border:1px solid var(--border);padding:1.5rem;height:100%;background:#fff;transition:box-shadow .2s;}
.fraud-card:hover{box-shadow:0 6px 24px rgba(220,38,38,.1);}
.fraud-card .risk-badge{display:inline-block;background:rgba(220,38,38,.1);color:var(--accent);font-size:.75rem;font-weight:700;padding:.2rem .6rem;border-radius:4px;margin-bottom:.8rem;}
.fraud-card h5{font-size:1rem;font-weight:700;margin-bottom:.5rem;}
.fraud-card p{font-size:.87rem;color:var(--muted);line-height:1.6;}
/* LIVE TICKER */
.live-ticker{background:#111827;padding:3rem 0;}
.live-ticker h4{color:#fff;font-size:1.2rem;font-weight:700;margin-bottom:1.5rem;}
.live-item{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-left:3px solid var(--accent);border-radius:8px;padding:.8rem 1rem;margin-bottom:.7rem;color:#e2e8f0;font-size:.87rem;display:flex;align-items:center;gap:.8rem;}
.live-dot{width:8px;height:8px;border-radius:50%;background:var(--accent);flex-shrink:0;animation:pulse 1.5s infinite;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:.3;}}
/* WHY US */
.whyus-section{padding:5rem 0;background:#f8fafc;}
.why-card{background:#fff;border-radius:12px;padding:1.8rem 1.5rem;height:100%;box-shadow:0 2px 12px rgba(0,0,0,.06);transition:transform .2s;}
.why-card:hover{transform:translateY(-3px);}
.why-icon{width:52px;height:52px;border-radius:50%;background:rgba(220,38,38,.1);display:flex;align-items:center;justify-content:center;margin-bottom:1rem;}
.why-icon i{font-size:1.3rem;color:var(--accent);}
.why-card h5{font-size:1rem;font-weight:700;margin-bottom:.5rem;}
.why-card p{font-size:.87rem;color:var(--muted);line-height:1.6;}
/* TESTIMONIALS */
.testimonials-section{padding:5rem 0;background:#fff;}
.testi-card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:1.8rem;height:100%;box-shadow:0 2px 12px rgba(0,0,0,.04);}
.stars{color:var(--accent);font-size:.95rem;margin-bottom:.8rem;}
.testi-text{font-size:.92rem;color:var(--text);line-height:1.7;font-style:italic;margin-bottom:1rem;}
.testi-author{font-size:.85rem;font-weight:700;color:var(--text);}
.testi-loc{font-size:.8rem;color:var(--muted);}
/* STATS SECTION */
.stats-section{background:var(--primary);padding:5rem 0;}
.stats-section h2{color:#fff;font-size:1.9rem;font-weight:800;margin-bottom:.5rem;}
.stats-section .sub{color:rgba(255,255,255,.6);font-size:.95rem;margin-bottom:3rem;}
.stat-box{text-align:center;}
.stat-box .s-num{font-size:2.8rem;font-weight:900;color:var(--accent);line-height:1;}
.stat-box .s-lbl{font-size:.9rem;color:rgba(255,255,255,.7);margin-top:.4rem;}
/* MAIN FORM */
.form-section{background:var(--bg-light);padding:5rem 0;}
.form-card{background:#fff;border-radius:16px;box-shadow:0 4px 32px rgba(0,0,0,.1);overflow:hidden;}
.form-header{background:var(--primary);padding:2.2rem 2rem;position:relative;}
.form-header::after{content:'';position:absolute;bottom:0;left:0;width:60px;height:4px;background:var(--accent);}
.form-header h3{color:#fff;font-size:1.5rem;font-weight:800;margin-bottom:.4rem;}
.form-header p{color:rgba(255,255,255,.7);font-size:.9rem;}
.form-body{padding:2.2rem 2rem;}
.form-label{font-size:.85rem;font-weight:600;color:var(--text);margin-bottom:.35rem;}
.form-control,.form-select{border:1.5px solid #e5e7eb;border-radius:8px;font-size:.92rem;padding:.65rem .9rem;transition:border-color .2s,box-shadow .2s;}
.form-control:focus,.form-select:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(220,38,38,.12);}
.btn-submit{background:var(--accent);color:#fff;border:none;border-radius:8px;padding:1rem 2rem;font-size:1rem;font-weight:700;width:100%;cursor:pointer;transition:background .2s,transform .15s;}
.btn-submit:hover{background:var(--accent-d);transform:translateY(-1px);}
.btn-submit:disabled{opacity:.7;cursor:not-allowed;}
.alert-success-custom{background:#dcfce7;border:1px solid #86efac;color:#166534;border-radius:8px;padding:1rem 1.2rem;margin-bottom:1.2rem;}
.alert-error-custom{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;border-radius:8px;padding:1rem 1.2rem;margin-bottom:1.2rem;}
/* TRUST BADGES */
.badges-section{padding:2.5rem 0;background:#fff;border-top:1px solid #f1f5f9;}
.badge-item{display:flex;flex-direction:column;align-items:center;gap:.5rem;color:var(--muted);font-size:.8rem;font-weight:600;}
.badge-item i{font-size:1.8rem;color:var(--accent);}
/* FAQ */
.faq-section{padding:5rem 0;background:#f8fafc;}
.accordion-button:not(.collapsed){background:var(--bg-light);color:var(--accent);box-shadow:none;}
.accordion-button:focus{box-shadow:0 0 0 3px rgba(220,38,38,.12);}
.accordion-item{border:1px solid var(--border);border-radius:8px!important;margin-bottom:.6rem;overflow:hidden;}
/* FOOTER */
footer{background:#111827;color:rgba(255,255,255,.7);padding:3rem 0 1.5rem;}
footer .brand{font-size:1.3rem;font-weight:800;color:#fff;margin-bottom:.6rem;}
footer .brand span{color:#f87171;}
footer p{font-size:.85rem;line-height:1.7;}
footer a{color:rgba(255,255,255,.6);text-decoration:none;font-size:.85rem;}
footer a:hover{color:#f87171;}
footer .footer-bottom{border-top:1px solid rgba(255,255,255,.08);margin-top:2rem;padding-top:1.2rem;font-size:.8rem;color:rgba(255,255,255,.4);}
/* MODAL */
.modal-header-custom{background:var(--primary);position:relative;padding:1.8rem 1.5rem;}
.modal-header-custom::after{content:'';position:absolute;bottom:0;left:0;width:50px;height:3px;background:var(--accent);}
.modal-header-custom h5{color:#fff;font-size:1.2rem;font-weight:800;}
.modal-header-custom p{color:rgba(255,255,255,.65);font-size:.85rem;margin-top:.3rem;}
.modal-header-custom .btn-close{filter:invert(1);}
.btn-modal-submit{background:var(--accent);color:#fff;border:none;border-radius:8px;padding:.85rem 1.5rem;font-size:.95rem;font-weight:700;width:100%;cursor:pointer;transition:background .2s;}
.btn-modal-submit:hover{background:var(--accent-d);}
@media(max-width:768px){
  .hero{min-height:90vh;}
  .hero h1{font-size:1.9rem;}
  .stat-pills{flex-direction:column;gap:.6rem;}
  .trust-item{font-size:.82rem;}
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar-custom" id="mainNav">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="#" class="navbar-brand-text">Verlust<span>Rückholung</span></a>
    <div class="d-none d-lg-flex align-items-center gap-1">
      <a href="#ai-section" class="nav-link-custom">KI-Radar</a>
      <a href="#leistungen" class="nav-link-custom">Leistungen</a>
      <a href="#stats-section" class="nav-link-custom">Ergebnisse</a>
      <a href="#faq" class="nav-link-custom">FAQ</a>
      <a href="#" class="nav-link-custom nav-cta ms-2" data-bs-toggle="modal" data-bs-target="#contactModal">Kostenlos prüfen</a>
    </div>
    <button class="d-lg-none btn btn-sm" style="background:var(--accent);color:#fff;border-radius:6px;font-size:.82rem;font-weight:600;" onclick="document.getElementById('engModal')&&new bootstrap.Modal(document.getElementById('engModal')).show()">Jetzt starten</button>
  </div>
</nav>

<!-- HERO -->
<section class="hero" id="home">
  <canvas id="heroCanvas5"></canvas>
  <div class="container hero-content py-5" style="padding-top:110px!important;">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <div class="hero-badge">🛡 KI-Betrugsradar aktiv – 24/7 Echtzeit-Analyse</div>
        <h1>
          <span class="hl">Betrugsopfer</span> handeln jetzt.<br>
          Wir handeln <span class="hl">mit Ihnen.</span>
        </h1>
        <p class="hero-sub">Unser KI-Betrugsradar arbeitet 24/7. Lassen Sie uns Ihren Fall sofort analysieren – kostenlos und ohne Risiko.</p>
        <div class="d-flex flex-wrap gap-3 mb-4">
          <a href="#" class="hero-cta" data-bs-toggle="modal" data-bs-target="#contactModal">Sofort analysieren lassen →</a>
          <a href="#ai-section" class="btn btn-outline-light" style="border-radius:8px;font-weight:600;padding:.85rem 1.8rem;">KI-Radar ansehen</a>
        </div>
        <div class="d-flex flex-wrap gap-3 stat-pills">
          <div class="stat-pill">Rückgeholt: <span class="val">€48M+</span></div>
          <div class="stat-pill">Erfolgsquote: <span class="val">87%</span></div>
          <div class="stat-pill">Klienten: <span class="val">2.400+</span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- STATS BAND -->
<section class="stats-band">
  <div class="container">
    <div class="row g-3 text-center">
      <div class="col-6 col-md-3 stat-item-band">
        <div class="num">87%</div><div class="lbl">Erfolgsquote</div>
      </div>
      <div class="col-6 col-md-3 stat-item-band">
        <div class="num">€48M+</div><div class="lbl">Rückgeholt</div>
      </div>
      <div class="col-6 col-md-3 stat-item-band">
        <div class="num">2.400+</div><div class="lbl">Klienten</div>
      </div>
      <div class="col-6 col-md-3 stat-item-band">
        <div class="num">18+</div><div class="lbl">Jahre Erfahrung</div>
      </div>
    </div>
  </div>
</section>

<!-- TRUST BANNER -->
<div class="trust-banner">
  <div class="container">
    <div class="row g-3 justify-content-center">
      <div class="col-6 col-md-3 d-flex justify-content-center">
        <div class="trust-item"><i class="bi bi-shield-check"></i>BaFin-geprüft</div>
      </div>
      <div class="col-6 col-md-3 d-flex justify-content-center">
        <div class="trust-item"><i class="bi bi-lock"></i>DSGVO-konform</div>
      </div>
      <div class="col-6 col-md-3 d-flex justify-content-center">
        <div class="trust-item"><i class="bi bi-award"></i>No-Win No-Fee</div>
      </div>
      <div class="col-6 col-md-3 d-flex justify-content-center">
        <div class="trust-item"><i class="bi bi-clock"></i>24h Erstanalyse</div>
      </div>
    </div>
  </div>
</div>

<!-- NEWS TICKER -->
<div class="news-ticker">
  <div class="container d-flex align-items-center overflow-hidden">
    <span class="ticker-label">AKTUELL</span>
    <div class="overflow-hidden flex-grow-1">
      <div class="ticker-track" id="tickerTrack">
        <span class="ticker-item">+++ Krypto-Betrug: €120.000 für Berliner Familie zurückgeholt +++</span>
        <span class="ticker-item">+++ Forex-Plattform abgeschaltet: 340 Opfer erhalten Erstattung +++</span>
        <span class="ticker-item">+++ Romance-Scam-Netzwerk zerschlagen: Betroffene melden sich +++</span>
        <span class="ticker-item">+++ CFD-Betrug: €380.000 in 6 Wochen zurückgeholt +++</span>
        <span class="ticker-item">+++ Neue KI-Analyse erkennt Fake-Broker-Muster in Sekunden +++</span>
        <span class="ticker-item">+++ Binäre Optionen: Sammelklage erfolgreich – €2,1M Rückholsumme +++</span>
        <span class="ticker-item">+++ Krypto-Betrug: €120.000 für Berliner Familie zurückgeholt +++</span>
        <span class="ticker-item">+++ Forex-Plattform abgeschaltet: 340 Opfer erhalten Erstattung +++</span>
        <span class="ticker-item">+++ Romance-Scam-Netzwerk zerschlagen: Betroffene melden sich +++</span>
        <span class="ticker-item">+++ CFD-Betrug: €380.000 in 6 Wochen zurückgeholt +++</span>
        <span class="ticker-item">+++ Neue KI-Analyse erkennt Fake-Broker-Muster in Sekunden +++</span>
        <span class="ticker-item">+++ Binäre Optionen: Sammelklage erfolgreich – €2,1M Rückholsumme +++</span>
      </div>
    </div>
  </div>
</div>

<!-- AI SECTION -->
<section id="ai-section" data-aos="fade-up">
  <div class="container">
    <div class="text-center mb-4">
      <h2>KI-Betrugsradar</h2>
      <p class="lead">Unser System scannt Betrugsplattformen in Echtzeit und erkennt Muster – sofort und automatisch.</p>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-4 col-md-6 text-center">
        <canvas id="aiRadarCanvas" width="240" height="240"></canvas>
      </div>
    </div>
    <div class="row g-3 justify-content-center mt-2">
      <div class="col-6 col-md-3">
        <div class="counter-box"><div class="c-num" id="cnt1">0</div><div class="c-lbl">Heute neu erkannt</div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="counter-box"><div class="c-num" id="cnt2">0</div><div class="c-lbl">Aktive Analysen</div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="counter-box"><div class="c-num" id="cnt3">€0M+</div><div class="c-lbl">Rückgeholt</div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="counter-box"><div class="c-num" id="cnt4">0%</div><div class="c-lbl">Erfolgsquote</div></div>
      </div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="hiw-section" data-aos="fade-up">
  <div class="container">
    <div class="text-center mb-5">
      <h2 style="font-size:1.9rem;font-weight:800;">So funktioniert die Rückholung</h2>
      <p style="color:var(--muted);">Drei Schritte – von der Analyse bis zur Rückzahlung.</p>
    </div>
    <div class="row g-4 text-center">
      <div class="col-md-4 hiw-step" data-aos="fade-up" data-aos-delay="0">
        <div class="step-circle">1</div>
        <h5>Kostenlose Erstanalyse</h5>
        <p>Schildern Sie uns Ihren Fall. Unser KI-System bewertet sofort die Rückholchancen – völlig kostenlos und unverbindlich.</p>
      </div>
      <div class="col-md-4 hiw-step" data-aos="fade-up" data-aos-delay="100">
        <div class="step-circle">2</div>
        <h5>Strategie & Dokumentation</h5>
        <p>Unsere Experten entwickeln eine individuelle Rückholstrategie und dokumentieren alle Belege für die rechtliche Verfolgung.</p>
      </div>
      <div class="col-md-4 hiw-step" data-aos="fade-up" data-aos-delay="200">
        <div class="step-circle">3</div>
        <h5>Geld zurück</h5>
        <p>Wir handeln direkt mit Banken, Zahlungsdienstleistern und Behörden. Kein Erfolg = keine Kosten.</p>
      </div>
    </div>
  </div>
</section>

<!-- LEISTUNGEN -->
<section class="leistungen-section" id="leistungen" data-aos="fade-up">
  <div class="container">
    <div class="text-center mb-5">
      <h2 style="font-size:1.9rem;font-weight:800;">Unsere Stärken</h2>
      <p style="color:var(--muted);">Spezialisierte Expertise für jeden Betrugstyp.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
        <div class="leistung-card">
          <div class="icon"><i class="bi bi-currency-bitcoin"></i></div>
          <h5>Krypto-Betrug</h5>
          <p>Blockchain-Forensik und rechtliche Rückverfolgung von Krypto-Transaktionen bei gefälschten Wallets und Exchanges.</p>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="80">
        <div class="leistung-card">
          <div class="icon"><i class="bi bi-graph-down-arrow"></i></div>
          <h5>Forex & CFD-Betrug</h5>
          <p>Rückholung bei manipulierten Handelssystemen, falschen Gewinnanzeigen und illegalen Brokern.</p>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="160">
        <div class="leistung-card">
          <div class="icon"><i class="bi bi-heart-break"></i></div>
          <h5>Romance Scam</h5>
          <p>Finanzielle Wiedergutmachung und psychologische Unterstützung für Opfer emotionaler Betrugsmaschen.</p>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
        <div class="leistung-card">
          <div class="icon"><i class="bi bi-bar-chart-line"></i></div>
          <h5>Binäre Optionen</h5>
          <p>Rechtliche Schritte gegen unlizenzierte Plattformen und Rückholung über internationale Behörden.</p>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="80">
        <div class="leistung-card">
          <div class="icon"><i class="bi bi-bank"></i></div>
          <h5>Bank-Chargeback</h5>
          <p>Wir stellen Rückbuchungsanträge bei Ihrer Bank und begleiten das Verfahren bis zur Entscheidung.</p>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="160">
        <div class="leistung-card">
          <div class="icon"><i class="bi bi-shield-exclamation"></i></div>
          <h5>Behördenkoordination</h5>
          <p>Zusammenarbeit mit BaFin, Europol und nationalen Strafverfolgungsbehörden für maximale Rückholchancen.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FRAUD TYPES -->
<section class="fraud-section" data-aos="fade-up">
  <div class="container">
    <div class="text-center mb-5">
      <h2 style="font-size:1.9rem;font-weight:800;">Häufige Betrugsmaschen</h2>
      <p style="color:var(--muted);">Erkennen Sie Ihr Szenario? Wir kennen alle Methoden.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="0">
        <div class="fraud-card">
          <div class="risk-badge">⚠ Hoch</div>
          <h5>Fake-Broker</h5>
          <p>Gefälschte Handelsplattformen die Gewinne vortäuschen und Einzahlungen verschwinden lassen.</p>
        </div>
      </div>
      <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="80">
        <div class="fraud-card">
          <div class="risk-badge">⚠ Hoch</div>
          <h5>Pig Butchering</h5>
          <p>Langzeit-Vertrauensaufbau via Social Media mit anschließendem Krypto-Investitionsbetrug.</p>
        </div>
      </div>
      <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="160">
        <div class="fraud-card">
          <div class="risk-badge">⚠ Hoch</div>
          <h5>Recovery Scam</h5>
          <p>Betrüger geben vor, verlorenes Geld zurückzuholen und fordern weitere Zahlungen.</p>
        </div>
      </div>
      <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="240">
        <div class="fraud-card">
          <div class="risk-badge">⚠ Hoch</div>
          <h5>Pump & Dump</h5>
          <p>Koordinierte Kurmanipulation bei Krypto-Assets mit massiven Verlusten für Kleinanleger.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- LIVE RECOVERY TICKER -->
<section class="live-ticker" data-aos="fade-up">
  <div class="container">
    <h4 class="text-center"><i class="bi bi-activity me-2" style="color:var(--accent);"></i>Aktuelle Rückholungen – Live</h4>
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="live-item"><div class="live-dot"></div>München • Krypto-Betrug • <strong style="color:#f87171;">€42.000</strong> zurückgeholt • vor 18 Min.</div>
        <div class="live-item"><div class="live-dot"></div>Hamburg • Forex-Broker • <strong style="color:#f87171;">€15.500</strong> zurückgeholt • vor 34 Min.</div>
        <div class="live-item"><div class="live-dot"></div>Wien • Romance Scam • <strong style="color:#f87171;">€88.000</strong> zurückgeholt • vor 1 Std.</div>
        <div class="live-item"><div class="live-dot"></div>Zürich • CFD-Betrug • <strong style="color:#f87171;">€230.000</strong> zurückgeholt • vor 2 Std.</div>
        <div class="live-item"><div class="live-dot"></div>Berlin • Binäre Optionen • <strong style="color:#f87171;">€9.800</strong> zurückgeholt • vor 3 Std.</div>
      </div>
    </div>
  </div>
</section>

<!-- WHY US -->
<section class="whyus-section" data-aos="fade-up">
  <div class="container">
    <div class="text-center mb-5">
      <h2 style="font-size:1.9rem;font-weight:800;">Warum VerlustRückholung?</h2>
      <p style="color:var(--muted);">Was uns von anderen unterscheidet.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
        <div class="why-card"><div class="why-icon"><i class="bi bi-cpu"></i></div><h5>KI-gestützte Analyse</h5><p>Unser proprietäres KI-System analysiert Ihren Fall in Minuten und erkennt Muster aus tausenden Betugsfällen.</p></div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="80">
        <div class="why-card"><div class="why-icon"><i class="bi bi-cash-coin"></i></div><h5>No-Win-No-Fee</h5><p>Kein Erfolg bedeutet keine Kosten für Sie. Wir tragen das finanzielle Risiko gemeinsam mit Ihnen.</p></div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="160">
        <div class="why-card"><div class="why-icon"><i class="bi bi-globe"></i></div><h5>Internationale Reichweite</h5><p>Netzwerk in 40+ Ländern – wir verfolgen Betrüger über Grenzen hinweg bis zur Rückzahlung.</p></div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
        <div class="why-card"><div class="why-icon"><i class="bi bi-person-check"></i></div><h5>Persönlicher Berater</h5><p>Ein dedizierter Fallmanager betreut Sie vom ersten Gespräch bis zum letzten Euro auf Ihrem Konto.</p></div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="80">
        <div class="why-card"><div class="why-icon"><i class="bi bi-lightning-charge"></i></div><h5>Schnelle Erstreaktion</h5><p>Innerhalb von 24 Stunden erhalten Sie eine vollständige Fallbewertung mit konkreten nächsten Schritten.</p></div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="160">
        <div class="why-card"><div class="why-icon"><i class="bi bi-incognito"></i></div><h5>Absolute Diskretion</h5><p>Höchste Datenschutzstandards nach DSGVO. Ihre persönlichen Daten verlassen niemals unsere Server.</p></div>
      </div>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="testimonials-section" data-aos="fade-up">
  <div class="container">
    <div class="text-center mb-5">
      <h2 style="font-size:1.9rem;font-weight:800;">Was unsere Klienten sagen</h2>
      <p style="color:var(--muted);">Echte Erfahrungen – echte Rückholungen.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
        <div class="testi-card">
          <div class="stars">★★★★★</div>
          <p class="testi-text">„Ich hatte €34.000 an einen Krypto-Broker verloren. Innerhalb von 3 Monaten hat VerlustRückholung alles zurückgeholt. Unglaublich professionell."</p>
          <div class="testi-author">M. Hoffmann</div>
          <div class="testi-loc">Frankfurt, Deutschland</div>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
        <div class="testi-card">
          <div class="stars">★★★★★</div>
          <p class="testi-text">„Nach einem Romance Scam dachte ich, meine €60.000 wären für immer verloren. Das Team hat nicht aufgegeben und €52.000 zurückgeholt."</p>
          <div class="testi-author">A. Bauer</div>
          <div class="testi-loc">Wien, Österreich</div>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
        <div class="testi-card">
          <div class="stars">★★★★★</div>
          <p class="testi-text">„Der Forex-Broker hat mein Konto gesperrt. VerlustRückholung hat über Chargeback und Behörden €18.500 zurückgeholt. Absolute Empfehlung."</p>
          <div class="testi-author">T. Müller</div>
          <div class="testi-loc">Zürich, Schweiz</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- STATISTICS SECTION -->
<section class="stats-section" id="stats-section" data-aos="fade-up">
  <div class="container text-center">
    <h2>Unsere Zahlen sprechen für sich</h2>
    <p class="sub">Verifizierte Ergebnisse aus über 18 Jahren Erfahrung.</p>
    <div class="row g-4">
      <div class="col-6 col-md-3 stat-box">
        <div class="s-num" data-target="87" data-suffix="%">0</div>
        <div class="s-lbl">Erfolgsquote</div>
      </div>
      <div class="col-6 col-md-3 stat-box">
        <div class="s-num" data-target="48" data-prefix="€" data-suffix="M+">0</div>
        <div class="s-lbl">Rückgeholt</div>
      </div>
      <div class="col-6 col-md-3 stat-box">
        <div class="s-num" data-target="2400" data-suffix="+">0</div>
        <div class="s-lbl">Klienten betreut</div>
      </div>
      <div class="col-6 col-md-3 stat-box">
        <div class="s-num" data-target="18" data-suffix="+">0</div>
        <div class="s-lbl">Jahre Erfahrung</div>
      </div>
    </div>
  </div>
</section>

<!-- MAIN CONTACT FORM -->
<section class="form-section" data-aos="fade-up">
  <div class="container">
    <?php if ($success): ?>
    <div class="alert-success-custom text-center mb-4"><i class="bi bi-check-circle-fill me-2"></i><strong>Vielen Dank!</strong> Wir haben Ihre Anfrage erhalten und melden uns innerhalb von 24 Stunden.</div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert-error-custom text-center mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?></div>
    <?php endif; ?>
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="form-card">
          <div class="form-header">
            <h3>Kostenlose Fallprüfung starten</h3>
            <p>Unser KI-System analysiert Ihren Fall sofort – 100% kostenlos, unverbindlich und diskret.</p>
          </div>
          <div class="form-body">
            <form id="mainForm" action="../submit_lead.php" method="POST" novalidate>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="visit_id" data-visit-id value="">
              <input type="hidden" name="design" value="design5">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Vorname *</label>
                  <input type="text" name="first_name" class="form-control" placeholder="Max" required>
                  <div class="invalid-feedback">Bitte geben Sie Ihren Vornamen an.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Nachname *</label>
                  <input type="text" name="last_name" class="form-control" placeholder="Mustermann" required>
                  <div class="invalid-feedback">Bitte geben Sie Ihren Nachnamen an.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">E-Mail-Adresse *</label>
                  <input type="email" name="email" class="form-control" placeholder="max@beispiel.de" required>
                  <div class="invalid-feedback">Bitte geben Sie eine gültige E-Mail-Adresse an.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Telefon</label>
                  <input type="tel" name="phone" class="form-control" placeholder="+49 151 12345678">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Land *</label>
                  <select name="country" class="form-select" required>
                    <option value="">– Land wählen –</option>
                    <option value="DE">Deutschland</option>
                    <option value="AT">Österreich</option>
                    <option value="CH">Schweiz</option>
                    <option value="LU">Luxemburg</option>
                    <option value="LI">Liechtenstein</option>
                    <option value="Sonstige">Sonstiges</option>
                  </select>
                  <div class="invalid-feedback">Bitte wählen Sie Ihr Land.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Verlorener Betrag (€) *</label>
                  <input type="number" name="amount_lost" class="form-control" placeholder="z.B. 15000" min="0" required>
                  <div class="invalid-feedback">Bitte geben Sie den verlorenen Betrag an.</div>
                </div>
                <div class="col-12">
                  <label class="form-label">Art des Betrugs *</label>
                  <select name="platform_category" class="form-select" required>
                    <option value="">– Betrugsart wählen –</option>
                    <option value="Krypto">Krypto-Betrug</option>
                    <option value="Forex">Forex-Betrug</option>
                    <option value="Binaere_Optionen">Binäre Optionen</option>
                    <option value="Romance_Scam">Romance Scam</option>
                    <option value="CFD">CFD-Betrug</option>
                    <option value="Sonstiges">Sonstiges</option>
                  </select>
                  <div class="invalid-feedback">Bitte wählen Sie die Betrugsart.</div>
                </div>
                <div class="col-12">
                  <label class="form-label">Kurze Fallbeschreibung *</label>
                  <textarea name="case_description" class="form-control" rows="4" placeholder="Beschreiben Sie kurz, was passiert ist, wann und auf welcher Plattform..." required></textarea>
                  <div class="invalid-feedback">Bitte beschreiben Sie Ihren Fall kurz.</div>
                </div>
                <div class="col-12">
                  <div class="form-check">
                    <input type="checkbox" name="privacy" class="form-check-input" id="privacyCheck" required>
                    <label class="form-check-label" for="privacyCheck" style="font-size:.85rem;">
                      Ich akzeptiere die <a href="#" style="color:var(--accent);">Datenschutzerklärung</a> und stimme der Verarbeitung meiner Daten zur Fallbearbeitung zu. *
                    </label>
                    <div class="invalid-feedback">Bitte akzeptieren Sie die Datenschutzerklärung.</div>
                  </div>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn-submit" id="mainSubmitBtn">
                    <i class="bi bi-send me-2"></i>Kostenlose Fallprüfung anfordern
                  </button>
                </div>
                <div class="col-12" id="mainFormMsg"></div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- TRUST BADGES -->
<section class="badges-section">
  <div class="container">
    <div class="row g-3 justify-content-center text-center">
      <div class="col-4 col-md-2"><div class="badge-item"><i class="bi bi-shield-lock"></i>SSL-gesichert</div></div>
      <div class="col-4 col-md-2"><div class="badge-item"><i class="bi bi-file-earmark-check"></i>DSGVO-konform</div></div>
      <div class="col-4 col-md-2"><div class="badge-item"><i class="bi bi-award"></i>BaFin-geprüft</div></div>
      <div class="col-4 col-md-2"><div class="badge-item"><i class="bi bi-credit-card"></i>No-Win No-Fee</div></div>
      <div class="col-4 col-md-2"><div class="badge-item"><i class="bi bi-headset"></i>24/7 Support</div></div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="faq-section" id="faq" data-aos="fade-up">
  <div class="container">
    <div class="text-center mb-5">
      <h2 style="font-size:1.9rem;font-weight:800;">Häufige Fragen</h2>
      <p style="color:var(--muted);">Alles, was Sie wissen müssen.</p>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="accordion" id="faqAccordion">
          <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">Wie hoch sind die Kosten für die Erstanalyse?</button></h2>
            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion"><div class="accordion-body" style="font-size:.92rem;color:var(--muted);">Die Erstanalyse ist vollständig kostenlos und unverbindlich. Wir arbeiten nach dem No-Win-No-Fee-Prinzip: Kosten entstehen Ihnen nur im Erfolgsfall.</div></div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">Wie lange dauert ein Rückholverfahren?</button></h2>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion"><div class="accordion-body" style="font-size:.92rem;color:var(--muted);">Die Dauer variiert je nach Fall. Einfache Chargeback-Verfahren dauern 4–8 Wochen. Komplexere internationale Fälle können 3–12 Monate in Anspruch nehmen.</div></div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">Welche Dokumente benötige ich?</button></h2>
            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion"><div class="accordion-body" style="font-size:.92rem;color:var(--muted);">Kontoauszüge, Transaktionsbelege, E-Mail-Korrespondenz mit der Plattform und Screenshots sind hilfreich. Unser Team führt Sie durch alle notwendigen Schritte.</div></div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">Ist mein Fall noch aussichtsreich, wenn er länger zurückliegt?</button></h2>
            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion"><div class="accordion-body" style="font-size:.92rem;color:var(--muted);">Auch ältere Fälle können erfolgreich bearbeitet werden. Wichtig ist, so schnell wie möglich zu handeln, da Betrugsspuren mit der Zeit verwischen können.</div></div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">Wie schützen Sie meine persönlichen Daten?</button></h2>
            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion"><div class="accordion-body" style="font-size:.92rem;color:var(--muted);">Wir verarbeiten alle Daten ausschließlich nach DSGVO. Ihre Daten werden Ende-zu-Ende verschlüsselt übertragen und niemals an Dritte weitergegeben.</div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="brand">Verlust<span>Rückholung</span></div>
        <p>Spezialisiert auf die Rückholung von Kapital bei Anlagebetrug. Über 18 Jahre Erfahrung. No-Win-No-Fee.</p>
      </div>
      <div class="col-md-2">
        <h6 style="color:#fff;font-weight:700;margin-bottom:1rem;">Leistungen</h6>
        <div class="d-flex flex-column gap-2">
          <a href="#leistungen">Krypto-Betrug</a>
          <a href="#leistungen">Forex-Betrug</a>
          <a href="#leistungen">Romance Scam</a>
          <a href="#leistungen">CFD-Betrug</a>
        </div>
      </div>
      <div class="col-md-2">
        <h6 style="color:#fff;font-weight:700;margin-bottom:1rem;">Unternehmen</h6>
        <div class="d-flex flex-column gap-2">
          <a href="#">Über uns</a>
          <a href="#faq">FAQ</a>
          <a href="#">Kontakt</a>
          <a href="#">Presse</a>
        </div>
      </div>
      <div class="col-md-4">
        <h6 style="color:#fff;font-weight:700;margin-bottom:1rem;">Rechtliches</h6>
        <div class="d-flex flex-column gap-2">
          <a href="#">Impressum</a>
          <a href="#">Datenschutzerklärung</a>
          <a href="#">AGB</a>
          <a href="#">Cookie-Richtlinie</a>
        </div>
        <p class="mt-3" style="font-size:.78rem;">© <?= date('Y') ?> VerlustRückholung. Alle Rechte vorbehalten.</p>
      </div>
    </div>
    <div class="footer-bottom text-center">
      <p>Die auf dieser Website bereitgestellten Informationen stellen keine Rechtsberatung dar. Vergangene Erfolge garantieren keine zukünftigen Ergebnisse.</p>
    </div>
  </div>
</footer>

<!-- ENGAGEMENT MODAL -->
<div class="modal fade" id="engModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px;overflow:hidden;border:none;">
      <div class="modal-header-custom">
        <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Schließen"></button>
        <h5>Jetzt handeln – Kostenlose Sofortanalyse</h5>
        <p>Jede Stunde zählt – Betrugsplattformen verwischen Spuren.</p>
      </div>
      <div class="modal-body p-4">
        <div id="engModalMsg"></div>
        <form id="engForm" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="visit_id" data-visit-id value="">
          <input type="hidden" name="design" value="design5_modal">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Vorname *</label>
              <input type="text" name="first_name" class="form-control" placeholder="Max" required>
              <div class="invalid-feedback">Bitte Vornamen angeben.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Nachname *</label>
              <input type="text" name="last_name" class="form-control" placeholder="Mustermann" required>
              <div class="invalid-feedback">Bitte Nachnamen angeben.</div>
            </div>
            <div class="col-12">
              <label class="form-label">E-Mail *</label>
              <input type="email" name="email" class="form-control" placeholder="max@beispiel.de" required>
              <div class="invalid-feedback">Bitte gültige E-Mail angeben.</div>
            </div>
            <div class="col-12">
              <label class="form-label">Verlorener Betrag (€) *</label>
              <input type="number" name="amount_lost" class="form-control" placeholder="z.B. 15000" min="0" required>
              <div class="invalid-feedback">Bitte Betrag angeben.</div>
            </div>
            <div class="col-12">
              <label class="form-label">Art des Betrugs *</label>
              <select name="platform_category" class="form-select" required>
                <option value="">– Betrugsart wählen –</option>
                <option value="Krypto">Krypto-Betrug</option>
                <option value="Forex">Forex-Betrug</option>
                <option value="Binaere_Optionen">Binäre Optionen</option>
                <option value="Romance_Scam">Romance Scam</option>
                <option value="CFD">CFD-Betrug</option>
                <option value="Sonstiges">Sonstiges</option>
              </select>
              <div class="invalid-feedback">Bitte Betrugsart wählen.</div>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input type="checkbox" name="privacy" class="form-check-input" id="engPrivacy" required>
                <label class="form-check-label" for="engPrivacy" style="font-size:.82rem;">
                  Ich akzeptiere die <a href="#" style="color:var(--accent);">Datenschutzerklärung</a>. *
                </label>
                <div class="invalid-feedback">Bitte Datenschutz akzeptieren.</div>
              </div>
            </div>
            <div class="col-12">
              <button type="submit" class="btn-modal-submit"><i class="bi bi-send me-2"></i>Jetzt kostenlos analysieren</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
(function(){
'use strict';

/* NAVBAR SCROLL */
var nav=document.getElementById('mainNav');
window.addEventListener('scroll',function(){
  nav.classList.toggle('scrolled',window.scrollY>50);
});

/* RADAR ANIMATION FACTORY */
function initRadar(canvasId,opts){
  var canvas=document.getElementById(canvasId);
  if(!canvas)return;
  var ctx=canvas.getContext('2d');
  var w=canvas.width,h=canvas.height;
  var cx=w/2,cy=h/2,radius=Math.min(w,h)/2-6;
  var sweepAngle=0,blips=[];
  var rings=opts&&opts.rings||5;
  var speed=opts&&opts.speed||0.018;

  function randomBlip(){
    var a=Math.random()*Math.PI*2;
    var r=(0.2+Math.random()*0.75)*radius;
    blips.push({x:cx+Math.cos(a)*r,y:cy+Math.sin(a)*r,a:a,life:1});
  }

  function draw(){
    ctx.clearRect(0,0,w,h);
    /* Background */
    ctx.fillStyle='rgba(17,24,39,0.95)';
    ctx.beginPath();ctx.arc(cx,cy,radius,0,Math.PI*2);ctx.fill();
    /* Concentric circles */
    for(var i=1;i<=rings;i++){
      ctx.beginPath();
      ctx.arc(cx,cy,(radius/rings)*i,0,Math.PI*2);
      ctx.strokeStyle='rgba(220,38,38,0.15)';
      ctx.lineWidth=1;
      ctx.stroke();
    }
    /* Cross hairs */
    ctx.strokeStyle='rgba(220,38,38,0.1)';
    ctx.lineWidth=1;
    ctx.beginPath();ctx.moveTo(cx-radius,cy);ctx.lineTo(cx+radius,cy);ctx.stroke();
    ctx.beginPath();ctx.moveTo(cx,cy-radius);ctx.lineTo(cx,cy+radius);ctx.stroke();
    /* Sweep gradient wedge */
    ctx.save();
    ctx.translate(cx,cy);
    ctx.rotate(sweepAngle);
    var wedge=ctx.createConicalGradient?ctx.createConicalGradient(0,0,0):(function(){
      var g=ctx.createLinearGradient(0,0,radius,0);
      g.addColorStop(0,'rgba(220,38,38,0)');
      g.addColorStop(1,'rgba(220,38,38,0.35)');
      return g;
    })();
    ctx.beginPath();
    ctx.moveTo(0,0);
    ctx.arc(0,0,radius,-Math.PI*0.18,0);
    ctx.closePath();
    ctx.fillStyle='rgba(220,38,38,0.18)';
    ctx.fill();
    /* Sweep arm */
    ctx.beginPath();
    ctx.moveTo(0,0);
    ctx.lineTo(radius,0);
    ctx.strokeStyle='rgba(220,38,38,0.9)';
    ctx.lineWidth=2;
    ctx.stroke();
    ctx.restore();
    /* Blips */
    blips=blips.filter(function(b){return b.life>0;});
    blips.forEach(function(b){
      ctx.beginPath();
      ctx.arc(b.x,b.y,4*b.life,0,Math.PI*2);
      ctx.fillStyle='rgba(220,38,38,'+b.life.toFixed(2)+')';
      ctx.fill();
      b.life-=0.008;
    });
    /* Advance sweep */
    sweepAngle+=speed;
    if(sweepAngle>Math.PI*2)sweepAngle-=Math.PI*2;
    /* Randomly spawn blips */
    if(Math.random()<0.04)randomBlip();
    requestAnimationFrame(draw);
  }
  draw();
}

/* HERO CANVAS */
(function(){
  var canvas=document.getElementById('heroCanvas5');
  if(!canvas)return;
  function resize(){canvas.width=canvas.offsetWidth;canvas.height=canvas.offsetHeight;}
  resize();
  window.addEventListener('resize',function(){resize();});
  initRadar('heroCanvas5',{rings:6,speed:0.016});
})();

/* AI MINI RADAR */
initRadar('aiRadarCanvas',{rings:4,speed:0.022});

/* AI SECTION COUNTERS */
var aiCountersDone=false;
function animateCounters(){
  if(aiCountersDone)return;
  aiCountersDone=true;
  function count(id,target,prefix,suffix,duration){
    var el=document.getElementById(id);
    if(!el)return;
    var start=0,step=target/((duration||1200)/16);
    var t=setInterval(function(){
      start+=step;
      if(start>=target){start=target;clearInterval(t);}
      el.textContent=(prefix||'')+Math.round(start)+(suffix||'');
    },16);
  }
  count('cnt1',127,'','',1000);
  count('cnt2',43,'','',900);
  count('cnt3',48,'€','M+',1100);
  count('cnt4',87,'','%',1000);
}
var aiObserver=new IntersectionObserver(function(entries){
  entries.forEach(function(e){if(e.isIntersecting)animateCounters();});
},{threshold:0.3});
var aiSec=document.getElementById('ai-section');
if(aiSec)aiObserver.observe(aiSec);

/* STATS SECTION COUNTUP */
var statsDone=false;
function animateStats(){
  if(statsDone)return;
  statsDone=true;
  document.querySelectorAll('#stats-section .s-num').forEach(function(el){
    var target=parseInt(el.getAttribute('data-target'),10)||0;
    var prefix=el.getAttribute('data-prefix')||'';
    var suffix=el.getAttribute('data-suffix')||'';
    var duration=1400;
    var start=0,step=target/(duration/16);
    var t=setInterval(function(){
      start+=step;
      if(start>=target){start=target;clearInterval(t);}
      el.textContent=prefix+Math.round(start)+suffix;
    },16);
  });
}
var statsObs=new IntersectionObserver(function(entries){
  entries.forEach(function(e){if(e.isIntersecting)animateStats();});
},{threshold:0.3});
var statsSec=document.getElementById('stats-section');
if(statsSec)statsObs.observe(statsSec);

/* FORM HANDLER */
function setupForm(formId,design){
  var form=document.getElementById(formId);
  if(!form)return;
  var msgId=formId==='mainForm'?'mainFormMsg':'engModalMsg';
  form.addEventListener('submit',function(e){
    e.preventDefault();
    if(!form.checkValidity()){form.classList.add('was-validated');return;}
    var btn=form.querySelector('[type=submit]');
    if(btn){btn.disabled=true;btn.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Wird gesendet...';}
    var fd=new FormData(form);
    fd.set('design',design);
    fetch('../submit_lead.php',{method:'POST',body:fd})
      .then(function(r){return r.json().catch(function(){return{success:false};});})
      .then(function(d){
        var msgEl=document.getElementById(msgId);
        if(d&&d.success){
          if(msgEl)msgEl.innerHTML='<div class="alert-success-custom"><i class="bi bi-check-circle-fill me-2"></i><strong>Vielen Dank!</strong> Wir melden uns innerhalb von 24 Stunden.</div>';
          form.reset();form.classList.remove('was-validated');
        } else {
          var err=(d&&d.message)||'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
          if(msgEl)msgEl.innerHTML='<div class="alert-error-custom"><i class="bi bi-exclamation-triangle-fill me-2"></i>'+err+'</div>';
        }
      })
      .catch(function(){
        var msgEl=document.getElementById(msgId);
        if(msgEl)msgEl.innerHTML='<div class="alert-error-custom"><i class="bi bi-exclamation-triangle-fill me-2"></i>Verbindungsfehler. Bitte prüfen Sie Ihre Internetverbindung.</div>';
      })
      .finally(function(){
        if(btn){btn.disabled=false;btn.innerHTML='<i class="bi bi-send me-2"></i>Kostenlose Fallprüfung anfordern';}
      });
  });
}
setupForm('mainForm','design5');
setupForm('engForm','design5_modal');
setupForm('contactModalForm','contact_modal');

/* AOS */
AOS.init({duration:700,once:true,offset:60});

/* MODAL TRIGGER */
setTimeout(function(){
  var el=document.getElementById('engModal');
  if(el){var m=new bootstrap.Modal(el);m.show();}
}, <?= $modal_delay * 1000 ?>);

})();
</script>

<script>
(function(){
'use strict';
var visitId=null,startTime=Date.now();
(function logVisit(){
  var fd=new FormData();fd.append('action','visit');fd.append('referrer',document.referrer||'');
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
      <div class="modal-header text-white py-3 px-4" style="background:linear-gradient(135deg,#dc2626,#b91c1c);border:none;">
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
                <li class="mb-3 d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#dc2626;flex-shrink:0"></i><span><strong class="text-dark">87% Erfolgsquote</strong> – verifiziert über 2.400 Mandate</span></li>
                <li class="mb-3 d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#dc2626;flex-shrink:0"></i><span><strong class="text-dark">€0 Vorauszahlung</strong> – Sie zahlen nur bei Erfolg</span></li>
                <li class="mb-3 d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#dc2626;flex-shrink:0"></i><span><strong class="text-dark">72h Erstantwort</strong> – KI prüft Ihren Fall sofort</span></li>
                <li class="mb-3 d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#dc2626;flex-shrink:0"></i><span><strong class="text-dark">40+ Länder</strong> – Internationales Expertennetzwerk</span></li>
                <li class="d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#dc2626;flex-shrink:0"></i><span><strong class="text-dark">DSGVO-konform</strong> – Höchste Datensicherheit</span></li>
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
                      Ich stimme der <a href="#" style="color:#dc2626">Datenschutzerklärung</a> zu und bin einverstanden, dass meine Daten zur Fallbearbeitung verwendet werden. *
                    </label>
                    <div class="invalid-feedback">Bitte Datenschutz bestätigen.</div>
                  </div>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn w-100 fw-bold py-3" style="background:#dc2626;color:#fff;font-size:1rem;border:none;border-radius:10px;">
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
