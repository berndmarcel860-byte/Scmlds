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
<meta name="description" content="VerlustRückholung hilft Opfern von Anlagebetrug ihr Kapital zurückzufordern. KI-gestützte Analyse, 87% Erfolgsquote. Kostenlose Erstprüfung.">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--primary:#0d2b5e;--accent:#2563eb;--accent-d:#1d4ed8;--text:#1a202c;--muted:#6c757d;--bg-light:#f0f4ff;--border:#e2e8f0;}
*,*::before,*::after{box-sizing:border-box;}
html{scroll-behavior:smooth;}
body{font-family:'Inter',sans-serif;color:var(--text);background:#fff;overflow-x:hidden;}
/* NAVBAR */
.navbar-d1{position:fixed;top:0;left:0;right:0;z-index:1050;background:rgba(10,22,40,0.95);backdrop-filter:blur(12px);border-bottom:1px solid rgba(255,255,255,.08);padding:.75rem 0;transition:box-shadow .3s;}
.navbar-d1.scrolled{box-shadow:0 4px 32px rgba(0,0,0,.3);}
.nav-brand{font-size:1.25rem;font-weight:800;color:#fff;text-decoration:none;}
.nav-brand span{color:var(--accent);}
.nav-lnk{color:rgba(255,255,255,.75);text-decoration:none;font-size:.88rem;font-weight:500;padding:.4rem .9rem;border-radius:6px;transition:all .2s;}
.nav-lnk:hover{color:#fff;background:rgba(255,255,255,.08);}
.btn-nav-cta{background:var(--accent);color:#fff;font-weight:700;padding:.45rem 1.2rem;border-radius:8px;border:none;font-size:.88rem;transition:all .2s;text-decoration:none;}
.btn-nav-cta:hover{background:var(--accent-d);color:#fff;transform:translateY(-1px);box-shadow:0 4px 16px rgba(37,99,235,.4);}
/* HERO */
.hero-d1{min-height:100vh;padding-top:80px;background:linear-gradient(135deg,#060e1f 0%,#0a1e36 40%,#0d2b5e 80%);position:relative;display:flex;align-items:center;overflow:hidden;}
#heroCanvas1{position:absolute;inset:0;width:100%;height:100%;}
.hero-overlay{position:absolute;inset:0;background:linear-gradient(135deg,rgba(6,14,31,.85) 50%,rgba(13,43,94,.4));}
.hero-content{position:relative;z-index:2;}
.hero-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(37,99,235,.15);border:1px solid rgba(37,99,235,.3);color:#60a5fa;border-radius:50px;padding:.35rem 1rem;font-size:.8rem;font-weight:700;margin-bottom:1.25rem;}
.hero-headline{font-size:clamp(2rem,4.5vw,3.4rem);font-weight:900;color:#fff;line-height:1.1;letter-spacing:-.02em;}
.hero-headline .hl{color:#60a5fa;}
.hero-sub{font-size:1.05rem;color:rgba(255,255,255,.7);line-height:1.75;max-width:520px;margin-top:1rem;}
.btn-hero-primary{background:var(--accent);color:#fff;font-weight:700;padding:.75rem 1.75rem;border-radius:10px;border:none;font-size:1rem;transition:all .25s;text-decoration:none;display:inline-block;}
.btn-hero-primary:hover{background:var(--accent-d);color:#fff;transform:translateY(-2px);box-shadow:0 8px 24px rgba(37,99,235,.5);}
.btn-hero-outline{background:transparent;color:#fff;font-weight:600;padding:.75rem 1.75rem;border-radius:10px;border:1px solid rgba(255,255,255,.3);font-size:1rem;transition:all .25s;text-decoration:none;display:inline-block;}
.btn-hero-outline:hover{background:rgba(255,255,255,.08);color:#fff;}
.stat-pill{position:absolute;background:rgba(255,255,255,.06);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.12);border-radius:50px;padding:.5rem 1.1rem;color:#fff;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:.5rem;animation:floatPill 4s ease-in-out infinite;white-space:nowrap;}
.stat-pill .val{color:#60a5fa;font-size:1rem;font-weight:800;}
.stat-pill:nth-child(1){top:22%;right:6%;animation-delay:0s;}
.stat-pill:nth-child(2){top:42%;right:3%;animation-delay:1.3s;}
.stat-pill:nth-child(3){top:62%;right:8%;animation-delay:2.6s;}
@keyframes floatPill{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
.live-dot{width:8px;height:8px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 2px rgba(34,197,94,.3);animation:livePulse 1.5s infinite;display:inline-block;}
@keyframes livePulse{0%,100%{box-shadow:0 0 0 2px rgba(34,197,94,.3)}50%{box-shadow:0 0 0 6px rgba(34,197,94,.1)}}
.trust-row{display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;margin-top:1.5rem;}
.trust-item{display:flex;align-items:center;gap:.4rem;color:rgba(255,255,255,.6);font-size:.82rem;font-weight:500;}
.trust-item i{color:#60a5fa;}
/* STATS BAND */
.stats-band{background:#fff;padding:3rem 0;border-bottom:1px solid var(--border);}
.stat-card{text-align:center;}
.stat-card .num{font-size:2.5rem;font-weight:900;color:var(--accent);}
.stat-card .lbl{color:var(--muted);font-size:.9rem;font-weight:500;margin-top:.25rem;}
/* TRUST BANNER */
.trust-banner{background:var(--bg-light);padding:2rem 0;border-top:1px solid var(--border);border-bottom:1px solid var(--border);}
.trust-item-b{display:flex;align-items:center;gap:.6rem;color:var(--text);font-size:.9rem;font-weight:600;}
.trust-item-b i{color:var(--accent);font-size:1.3rem;}
/* NEWS TICKER */
.ticker-wrap{background:var(--primary);padding:.6rem 0;overflow:hidden;white-space:nowrap;}
.ticker-inner{display:inline-block;animation:tickerScroll 40s linear infinite;}
.ticker-inner span{color:rgba(255,255,255,.85);font-size:.82rem;margin-right:3rem;}
.ticker-inner span strong{color:#60a5fa;}
@keyframes tickerScroll{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}
/* SECTION COMMON */
.section-eyebrow{display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--accent);margin-bottom:.75rem;}
.section-eyebrow::before{content:'';display:inline-block;width:20px;height:2px;background:var(--accent);border-radius:2px;}
.section-title{font-size:clamp(1.6rem,3vw,2.25rem);font-weight:800;color:var(--text);letter-spacing:-.02em;}
/* AI SECTION */
.ai-section{background:var(--bg-light);padding:5rem 0;}
.ai-terminal{background:#0f172a;border-radius:12px;padding:1.5rem;font-family:'Courier New',monospace;font-size:.82rem;color:#94a3b8;min-height:200px;position:relative;overflow:hidden;}
.ai-terminal .t-header{display:flex;gap:6px;margin-bottom:1rem;}
.ai-terminal .t-dot{width:12px;height:12px;border-radius:50%;}
.ai-terminal .t-line{color:#60a5fa;margin:3px 0;opacity:0;animation:typeLine .3s forwards;}
.ai-terminal .t-line.done{color:#4ade80;}
@keyframes typeLine{from{opacity:0;transform:translateX(-4px)}to{opacity:1;transform:none}}
.ai-progress-bar-wrap{margin-bottom:1rem;}
.ai-progress-bar-wrap .label{display:flex;justify-content:space-between;font-size:.82rem;font-weight:600;color:var(--text);margin-bottom:.3rem;}
.ai-bar{height:8px;border-radius:4px;background:#e2e8f0;overflow:hidden;}
.ai-bar-fill{height:100%;background:linear-gradient(90deg,var(--accent),var(--accent-d));border-radius:4px;width:0;transition:width 1.5s ease;}
/* HOW IT WORKS */
.hiw-section{background:#fff;padding:5rem 0;}
.hiw-step{background:#fff;border:1px solid var(--border);border-radius:16px;padding:2rem 1.5rem;text-align:center;height:100%;transition:all .3s;}
.hiw-step:hover{transform:translateY(-4px);box-shadow:0 16px 40px rgba(37,99,235,.1);}
.hiw-num{width:52px;height:52px;border-radius:50%;background:var(--accent);color:#fff;font-size:1.25rem;font-weight:800;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;}
.hiw-conn{position:absolute;top:26px;left:calc(50% + 2rem);right:calc(-50% + 2rem);height:2px;background:linear-gradient(90deg,var(--accent),transparent);display:none;}
@media(min-width:768px){.hiw-conn{display:block;}}
/* LEISTUNGEN CARDS */
.leist-section{background:var(--bg-light);padding:5rem 0;}
.leist-card{background:#fff;border-radius:12px;padding:1.75rem;height:100%;border-top:3px solid var(--accent);box-shadow:0 2px 12px rgba(37,99,235,.06);transition:all .3s;}
.leist-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(37,99,235,.12);}
.leist-icon{width:52px;height:52px;border-radius:12px;background:var(--bg-light);display:flex;align-items:center;justify-content:center;margin-bottom:1rem;font-size:1.4rem;color:var(--accent);}
/* FRAUD TYPES */
.fraud-section{background:#fff;padding:5rem 0;}
.fraud-card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:1.75rem;height:100%;transition:all .3s;}
.fraud-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(37,99,235,.1);}
.fraud-badge{background:var(--bg-light);color:var(--accent);font-size:.75rem;font-weight:700;padding:.25rem .65rem;border-radius:4px;display:inline-block;margin-bottom:.75rem;}
/* LIVE RECOVERY TICKER */
.recovery-ticker{background:var(--primary);padding:.65rem 0;overflow:hidden;white-space:nowrap;}
.recovery-ticker .inner{display:inline-block;animation:tickerScroll 35s linear infinite;}
.recovery-ticker .inner span{color:rgba(255,255,255,.85);font-size:.82rem;margin-right:3rem;}
.recovery-ticker .inner span strong{color:#4ade80;}
/* WHY US */
.why-section{background:var(--bg-light);padding:5rem 0;}
.why-card{background:#fff;border-radius:12px;padding:1.5rem;height:100%;border-top:3px solid var(--accent);transition:all .3s;}
.why-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(37,99,235,.1);}
.why-icon{font-size:1.75rem;color:var(--accent);margin-bottom:.75rem;}
/* TESTIMONIALS */
.testi-section{background:#fff;padding:5rem 0;}
.testi-card{background:#fff;border-radius:12px;padding:1.75rem;height:100%;border-left:4px solid var(--accent);box-shadow:0 2px 12px rgba(37,99,235,.06);}
.stars{color:#f59e0b;font-size:1rem;letter-spacing:2px;margin-bottom:.75rem;}
/* STATS SECTION (dark exception) */
.stats-section{background:var(--primary);padding:5rem 0;}
.stat-count{font-size:2.75rem;font-weight:900;color:var(--accent);}
.stat-count-lbl{color:rgba(255,255,255,.7);font-size:.9rem;margin-top:.25rem;}
/* CONTACT FORM */
.form-section{background:#fff;padding:5rem 0;}
.form-card{background:#fff;border-radius:20px;box-shadow:0 8px 40px rgba(37,99,235,.1);overflow:hidden;}
.form-card-hdr{background:linear-gradient(135deg,var(--accent),var(--accent-d));padding:2rem;color:#fff;}
.form-card-body{padding:2rem;}
.btn-submit{background:var(--accent);color:#fff;font-weight:700;padding:.8rem 2rem;border-radius:10px;border:none;font-size:1rem;width:100%;transition:all .25s;}
.btn-submit:hover{background:var(--accent-d);transform:translateY(-2px);box-shadow:0 6px 20px rgba(37,99,235,.4);}
/* TRUST BADGES */
.badges-section{background:var(--bg-light);padding:3rem 0;}
.trust-badge{background:#fff;border:1px solid var(--border);border-radius:10px;padding:.75rem 1.25rem;display:flex;align-items:center;gap:.6rem;font-weight:600;font-size:.88rem;color:var(--text);}
.trust-badge i{color:var(--accent);font-size:1.2rem;}
/* FAQ */
.faq-section{background:#fff;padding:5rem 0;}
.accordion-button:not(.collapsed){background:var(--bg-light);color:var(--accent);box-shadow:none;}
.accordion-button:focus{box-shadow:none;}
/* FOOTER */
.footer-d1{background:#0a1628;color:rgba(255,255,255,.7);padding:3rem 0 1.5rem;}
.footer-brand{font-size:1.3rem;font-weight:800;color:#fff;margin-bottom:.5rem;}
.footer-brand span{color:var(--accent);}
.footer-link{color:rgba(255,255,255,.6);text-decoration:none;font-size:.88rem;display:block;margin-bottom:.4rem;transition:color .2s;}
.footer-link:hover{color:var(--accent);}
/* MODAL */
.modal-hdr-blue{background:linear-gradient(135deg,var(--accent),var(--accent-d));color:#fff;}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar-d1">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="#hero" class="nav-brand">Verlust<span>Rückholung</span></a>
    <div class="d-none d-lg-flex gap-1">
      <a href="#leistungen" class="nav-lnk">Leistungen</a>
      <a href="#how-it-works" class="nav-lnk">Ablauf</a>
      <a href="#fraud-types" class="nav-lnk">Betrugsarten</a>
      <a href="#faq" class="nav-lnk">FAQ</a>
      <a href="#contact-form" class="nav-lnk">Kontakt</a>
    </div>
    <a href="#" class="btn-nav-cta" data-bs-toggle="modal" data-bs-target="#contactModal">Kostenlos prüfen →</a>
  </div>
</nav>

<!-- HERO -->
<section class="hero-d1" id="hero">
  <canvas id="heroCanvas1"></canvas>
  <div class="hero-overlay"></div>
  <!-- Floating pills -->
  <div class="stat-pill"><span class="live-dot"></span><span class="val">87%</span> Erfolgsquote</div>
  <div class="stat-pill"><span class="val">€48M+</span> Zurückgefordert</div>
  <div class="stat-pill"><span class="live-dot"></span><span class="val">2.400+</span> Mandanten</div>
  <div class="container hero-content">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <div class="hero-badge"><i class="bi bi-shield-check"></i> KI-gestützte Kapitalrückholung</div>
        <h1 class="hero-headline">Kapital verloren?<br><span class="hl">Unsere KI analysiert</span><br>Ihren Fall in 72 Stunden.</h1>
        <p class="hero-sub">Über 2.400 Betrugsopfer haben ihr Kapital mit uns zurückgefordert. Keine Vorauszahlung – nur Ergebnisse.</p>
        <div class="d-flex flex-wrap gap-3 mt-4">
          <a href="#" class="btn-hero-primary" data-bs-toggle="modal" data-bs-target="#contactModal">Jetzt kostenlos starten →</a>
          <a href="#how-it-works" class="btn-hero-outline">Wie es funktioniert</a>
        </div>
        <div class="trust-row">
          <span class="trust-item"><i class="bi bi-check-circle-fill"></i> Keine Vorauszahlung</span>
          <span class="trust-item"><i class="bi bi-lock-fill"></i> DSGVO-konform</span>
          <span class="trust-item"><i class="bi bi-award-fill"></i> Lizenzierte Experten</span>
          <span class="trust-item"><i class="bi bi-clock-fill"></i> 72h Erstantwort</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- STATS BAND -->
<section class="stats-band">
  <div class="container">
    <div class="row g-4 text-center">
      <div class="col-6 col-md-3"><div class="stat-card"><div class="num">87%</div><div class="lbl">Erfolgsquote</div></div></div>
      <div class="col-6 col-md-3"><div class="stat-card"><div class="num">€48M+</div><div class="lbl">Zurückgefordert</div></div></div>
      <div class="col-6 col-md-3"><div class="stat-card"><div class="num">2.400+</div><div class="lbl">Zufriedene Mandanten</div></div></div>
      <div class="col-6 col-md-3"><div class="stat-card"><div class="num">18+</div><div class="lbl">Länder aktiv</div></div></div>
    </div>
  </div>
</section>

<!-- TRUST BANNER -->
<section class="trust-banner">
  <div class="container">
    <div class="row g-3 justify-content-center">
      <div class="col-6 col-md-3 text-center"><div class="trust-item-b justify-content-center"><i class="bi bi-shield-check"></i> Kein Risiko</div></div>
      <div class="col-6 col-md-3 text-center"><div class="trust-item-b justify-content-center"><i class="bi bi-lock"></i> DSGVO-sicher</div></div>
      <div class="col-6 col-md-3 text-center"><div class="trust-item-b justify-content-center"><i class="bi bi-award"></i> Lizenzierte Experten</div></div>
      <div class="col-6 col-md-3 text-center"><div class="trust-item-b justify-content-center"><i class="bi bi-currency-euro"></i> Nur auf Erfolgsbasis</div></div>
    </div>
  </div>
</section>

<!-- NEWS TICKER -->
<div class="ticker-wrap">
  <div class="ticker-inner">
    <span>🔵 <strong>München:</strong> €34.000 nach Forex-Betrug zurückgeholt &bull;</span>
    <span>🔵 <strong>Berlin:</strong> Krypto-Betrug – €89.500 gesichert &bull;</span>
    <span>🔵 <strong>Hamburg:</strong> Romance-Scam – €21.000 zurückgefordert &bull;</span>
    <span>🔵 <strong>Wien:</strong> Binäre Optionen – €55.000 erfolgreich rückgeholt &bull;</span>
    <span>🔵 <strong>Zürich:</strong> CFD-Betrug – €120.000 zurückgefordert &bull;</span>
    <span>🔵 <strong>Frankfurt:</strong> KI-Analyse – Neuer Mandant in 48h bearbeitet &bull;</span>
    <span>🔵 <strong>München:</strong> €34.000 nach Forex-Betrug zurückgeholt &bull;</span>
    <span>🔵 <strong>Berlin:</strong> Krypto-Betrug – €89.500 gesichert &bull;</span>
    <span>🔵 <strong>Hamburg:</strong> Romance-Scam – €21.000 zurückgefordert &bull;</span>
    <span>🔵 <strong>Wien:</strong> Binäre Optionen – €55.000 erfolgreich rückgeholt &bull;</span>
  </div>
</div>

<!-- AI SECTION -->
<section class="ai-section" id="ai-section">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6" data-aos="fade-right">
        <div class="section-eyebrow">KI-ANALYSE LIVE</div>
        <h2 class="section-title mb-3">Modernste KI analysiert Ihren Fall</h2>
        <p class="text-muted mb-4">Unser System verarbeitet tausende Transaktionsdaten gleichzeitig und erkennt Betrugsmuster in Echtzeit.</p>
        <div class="ai-progress-bar-wrap">
          <div class="label"><span>Transaktionsprüfung</span><span>94%</span></div>
          <div class="ai-bar"><div class="ai-bar-fill" data-width="94"></div></div>
        </div>
        <div class="ai-progress-bar-wrap">
          <div class="label"><span>Betrugsmuster-Scan</span><span>88%</span></div>
          <div class="ai-bar"><div class="ai-bar-fill" data-width="88"></div></div>
        </div>
        <div class="ai-progress-bar-wrap">
          <div class="label"><span>Risikoanalyse</span><span>76%</span></div>
          <div class="ai-bar"><div class="ai-bar-fill" data-width="76"></div></div>
        </div>
        <div class="ai-progress-bar-wrap">
          <div class="label"><span>Bericht erstellt</span><span>100%</span></div>
          <div class="ai-bar"><div class="ai-bar-fill" data-width="100"></div></div>
        </div>
      </div>
      <div class="col-lg-6" data-aos="fade-left">
        <div class="ai-terminal">
          <div class="t-header">
            <div class="t-dot" style="background:#ff5f57"></div>
            <div class="t-dot" style="background:#fbbe2c"></div>
            <div class="t-dot" style="background:#28c940"></div>
          </div>
          <div class="t-line" id="tl1">&gt; System initialisiert...</div>
          <div class="t-line" id="tl2">&gt; Betrugsdatenbank geladen: 2.4M Einträge</div>
          <div class="t-line" id="tl3">&gt; Transaktionsanalyse gestartet...</div>
          <div class="t-line done" id="tl4">&#x2713; Betrugsmuster erkannt: CFD-Broker XYZ</div>
          <div class="t-line done" id="tl5">&#x2713; Rückforderungsgrundlage: §823 BGB, MiFID II</div>
          <div class="t-line done" id="tl6">&#x2713; Erfolgswahrscheinlichkeit: 91%</div>
          <div class="t-line" id="tl7">&gt; Erstbericht wird generiert...</div>
          <div class="t-line done" id="tl8">&#x2713; Bericht fertig – Versand an Mandant</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="hiw-section" id="how-it-works">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <div class="section-eyebrow">DER ABLAUF</div>
      <h2 class="section-title">So holen wir Ihr Kapital zurück</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-4 position-relative" data-aos="fade-up" data-aos-delay="0">
        <div class="hiw-step">
          <div class="hiw-num">1</div>
          <h5 class="fw-bold mb-2">Einreichung</h5>
          <p class="text-muted mb-0">Füllen Sie unser kostenloses Formular aus. Beschreiben Sie Ihren Fall – wir analysieren sofort.</p>
        </div>
      </div>
      <div class="col-md-4 position-relative" data-aos="fade-up" data-aos-delay="150">
        <div class="hiw-step">
          <div class="hiw-num">2</div>
          <h5 class="fw-bold mb-2">KI-Analyse</h5>
          <p class="text-muted mb-0">Unsere KI prüft Ihren Fall auf Basis von 150.000+ Transaktionen und erstellt einen detaillierten Bericht.</p>
        </div>
      </div>
      <div class="col-md-4 position-relative" data-aos="fade-up" data-aos-delay="300">
        <div class="hiw-step">
          <div class="hiw-num">3</div>
          <h5 class="fw-bold mb-2">Rückholung</h5>
          <p class="text-muted mb-0">Unser internationales Experten-Team fordert Ihr Kapital zurück – ohne Vorauszahlung.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- LEISTUNGEN -->
<section class="leist-section" id="leistungen">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <div class="section-eyebrow">LEISTUNGEN</div>
      <h2 class="section-title">Unsere Leistungen für Sie</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
        <div class="leist-card">
          <div class="leist-icon"><i class="bi bi-cpu"></i></div>
          <h5 class="fw-bold mb-2">KI-Fallanalyse</h5>
          <p class="text-muted mb-0">Automatische Analyse Ihres Betrugsfalles mit modernster KI-Technologie und Mustererkennung.</p>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
        <div class="leist-card">
          <div class="leist-icon"><i class="bi bi-globe2"></i></div>
          <h5 class="fw-bold mb-2">Internationale Verfolgung</h5>
          <p class="text-muted mb-0">Wir verfolgen Betrüger über Ländergrenzen hinweg – mit Partnern in 18+ Ländern.</p>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
        <div class="leist-card">
          <div class="leist-icon"><i class="bi bi-file-earmark-text"></i></div>
          <h5 class="fw-bold mb-2">Rechtliche Begleitung</h5>
          <p class="text-muted mb-0">Erfahrene Juristen begleiten Ihren Fall – von der Analyse bis zum erfolgreichen Abschluss.</p>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
        <div class="leist-card">
          <div class="leist-icon"><i class="bi bi-bank2"></i></div>
          <h5 class="fw-bold mb-2">Banken-Rückbuchung</h5>
          <p class="text-muted mb-0">Direkte Zusammenarbeit mit Banken und Zahlungsdienstleistern für schnelle Rückbuchungen.</p>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
        <div class="leist-card">
          <div class="leist-icon"><i class="bi bi-shield-lock"></i></div>
          <h5 class="fw-bold mb-2">Datenschutz &amp; Sicherheit</h5>
          <p class="text-muted mb-0">Alle Daten werden nach DSGVO verarbeitet – vollständige Vertraulichkeit garantiert.</p>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
        <div class="leist-card">
          <div class="leist-icon"><i class="bi bi-headset"></i></div>
          <h5 class="fw-bold mb-2">Persönliche Betreuung</h5>
          <p class="text-muted mb-0">Ihr persönlicher Fallberater begleitet Sie durch den gesamten Prozess und informiert Sie regelmäßig.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FRAUD TYPES -->
<section class="fraud-section" id="fraud-types">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <div class="section-eyebrow">BETRUGSARTEN</div>
      <h2 class="section-title">Welche Betrugsarten wir bekämpfen</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="0">
        <div class="fraud-card">
          <div class="fraud-badge">Erkannt</div>
          <div class="fs-2 mb-2">&#x20BF;</div>
          <h5 class="fw-bold mb-2">Krypto-Betrug</h5>
          <p class="text-muted mb-0">Fake-Exchanges, Rug-Pulls, NFT-Betrug und gefälschte Krypto-Investments.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
        <div class="fraud-card">
          <div class="fraud-badge">Erkannt</div>
          <div class="fs-2 mb-2">📈</div>
          <h5 class="fw-bold mb-2">Forex-Betrug</h5>
          <p class="text-muted mb-0">Unregulierte Broker, manipulierte Kurse, Auszahlungsverweigerung.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
        <div class="fraud-card">
          <div class="fraud-badge">Erkannt</div>
          <div class="fs-2 mb-2">⚡</div>
          <h5 class="fw-bold mb-2">Binäre Optionen</h5>
          <p class="text-muted mb-0">Illegale Plattformen mit manipulierten Ergebnissen und falschen Versprechen.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
        <div class="fraud-card">
          <div class="fraud-badge">Erkannt</div>
          <div class="fs-2 mb-2">💔</div>
          <h5 class="fw-bold mb-2">Romance-Scam</h5>
          <p class="text-muted mb-0">Beziehungsbetrug mit finanzieller Ausbeutung über soziale Netzwerke.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- LIVE RECOVERY TICKER -->
<div class="recovery-ticker">
  <div class="inner">
    <span>&#x2705; Soeben zurückgeholt: <strong>€27.000</strong> – München &bull;</span>
    <span>&#x2705; <strong>€143.500</strong> nach Krypto-Betrug gesichert – Hamburg &bull;</span>
    <span>&#x2705; <strong>€38.000</strong> – Forex-Betrug, Frankfurt &bull;</span>
    <span>&#x2705; <strong>€92.000</strong> Romance-Scam – Berlin &bull;</span>
    <span>&#x2705; <strong>€61.000</strong> – Wien, Binäre Optionen &bull;</span>
    <span>&#x2705; Soeben zurückgeholt: <strong>€27.000</strong> – München &bull;</span>
    <span>&#x2705; <strong>€143.500</strong> nach Krypto-Betrug gesichert – Hamburg &bull;</span>
    <span>&#x2705; <strong>€38.000</strong> – Forex-Betrug, Frankfurt &bull;</span>
    <span>&#x2705; <strong>€92.000</strong> Romance-Scam – Berlin &bull;</span>
  </div>
</div>

<!-- WHY US -->
<section class="why-section" id="why-us">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <div class="section-eyebrow">WARUM WIR</div>
      <h2 class="section-title">Ihr Vorteil mit VerlustRückholung</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
        <div class="why-card">
          <div class="why-icon"><i class="bi bi-cpu-fill"></i></div>
          <h5 class="fw-bold mb-2">KI-Forensik</h5>
          <p class="text-muted mb-0">Modernste KI analysiert Blockchain-Transaktionen und findet Beweismittel.</p>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
        <div class="why-card">
          <div class="why-icon"><i class="bi bi-people-fill"></i></div>
          <h5 class="fw-bold mb-2">Internationale Partner</h5>
          <p class="text-muted mb-0">Netzwerk aus Anwälten, Behörden und Finanzexperten in 18+ Ländern.</p>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
        <div class="why-card">
          <div class="why-icon"><i class="bi bi-currency-euro"></i></div>
          <h5 class="fw-bold mb-2">Nur auf Erfolgsbasis</h5>
          <p class="text-muted mb-0">Keine Vorauszahlung – wir verdienen nur, wenn Sie Ihr Geld zurückbekommen.</p>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
        <div class="why-card">
          <div class="why-icon"><i class="bi bi-shield-check"></i></div>
          <h5 class="fw-bold mb-2">DSGVO-konform</h5>
          <p class="text-muted mb-0">Vollständiger Datenschutz nach europäischem Standard – Ihre Daten sind sicher.</p>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
        <div class="why-card">
          <div class="why-icon"><i class="bi bi-graph-up-arrow"></i></div>
          <h5 class="fw-bold mb-2">87% Erfolgsquote</h5>
          <p class="text-muted mb-0">Über 87% unserer Fälle enden erfolgreich – belegt durch unabhängige Audits.</p>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
        <div class="why-card">
          <div class="why-icon"><i class="bi bi-lightning-charge-fill"></i></div>
          <h5 class="fw-bold mb-2">72h Erstantwort</h5>
          <p class="text-muted mb-0">Innerhalb von 72 Stunden erhalten Sie Ihren ersten Analysebericht.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="testi-section" id="testimonials">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <div class="section-eyebrow">ERFAHRUNGEN</div>
      <h2 class="section-title">Was unsere Mandanten sagen</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
        <div class="testi-card">
          <div class="stars">★★★★★</div>
          <p class="mb-3">"Nach dem Forex-Betrug hatte ich keine Hoffnung mehr. VerlustRückholung hat innerhalb von 4 Monaten €34.000 für mich zurückgeholt. Absolut empfehlenswert!"</p>
          <strong>Michael S.</strong><br><small class="text-muted">München · Forex-Betrug</small>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="150">
        <div class="testi-card">
          <div class="stars">★★★★★</div>
          <p class="mb-3">"Das Team hat mir professionell und einfühlsam geholfen. Nach dem Romance-Scam war ich am Boden – heute habe ich mein Geld zurück."</p>
          <strong>Sabine M.</strong><br><small class="text-muted">Hamburg · Romance-Scam</small>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
        <div class="testi-card">
          <div class="stars">★★★★★</div>
          <p class="mb-3">"Krypto-Betrug über eine Fake-Exchange. VerlustRückholung hat die Transaktionen verfolgt und €89.500 zurückgefordert. Unglaublich!"</p>
          <strong>Thomas K.</strong><br><small class="text-muted">Berlin · Krypto-Betrug</small>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- STATISTICS SECTION (dark exception) -->
<section class="stats-section" id="stats-section">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-eyebrow" style="color:#60a5fa;">UNSERE ZAHLEN</div>
      <h2 class="section-title" style="color:#fff;">Vertrauen in Zahlen</h2>
    </div>
    <div class="row g-4 text-center">
      <div class="col-6 col-md-3">
        <div class="stat-count" data-count="87" data-suffix="%">87%</div>
        <div class="stat-count-lbl">Erfolgsquote</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-count" data-count="48" data-suffix="M+">€48M+</div>
        <div class="stat-count-lbl">Zurückgefordert</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-count" data-count="2400" data-suffix="+">2.400+</div>
        <div class="stat-count-lbl">Mandanten</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-count" data-count="18" data-suffix="+">18+</div>
        <div class="stat-count-lbl">Länder</div>
      </div>
    </div>
  </div>
</section>

<!-- MAIN CONTACT FORM -->
<section class="form-section" id="contact-form">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <?php if ($success): ?>
          <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><strong>Vielen Dank!</strong> Wir melden uns innerhalb von 72 Stunden bei Ihnen.</div>
        <?php elseif ($error): ?>
          <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= $error ?></div>
        <?php endif; ?>
        <div class="form-card">
          <div class="form-card-hdr">
            <h3 class="mb-1 fw-bold"><i class="bi bi-shield-check me-2"></i>Kostenlose KI-Erstprüfung</h3>
            <p class="mb-0 opacity-75">Kein Risiko · Keine Vorauszahlung · Antwort in 72 Stunden</p>
          </div>
          <div class="form-card-body">
            <form action="../submit_lead.php" method="POST" id="mainForm" novalidate>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="visit_id" data-visit-id value="">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Vorname *</label>
                  <input type="text" class="form-control" name="first_name" required placeholder="Max">
                  <div class="invalid-feedback">Vorname erforderlich.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Nachname *</label>
                  <input type="text" class="form-control" name="last_name" required placeholder="Mustermann">
                  <div class="invalid-feedback">Nachname erforderlich.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">E-Mail *</label>
                  <input type="email" class="form-control" name="email" required placeholder="max@beispiel.de">
                  <div class="invalid-feedback">Gültige E-Mail erforderlich.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Telefon (optional)</label>
                  <input type="tel" class="form-control" name="phone" placeholder="+49 123 456789">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Land *</label>
                  <select class="form-select" name="country" required>
                    <option value="">Bitte wählen...</option>
                    <option value="DE">Deutschland</option>
                    <option value="AT">Österreich</option>
                    <option value="CH">Schweiz</option>
                    <option value="LU">Luxemburg</option>
                    <option value="LI">Liechtenstein</option>
                    <option value="Sonstige">Sonstiges</option>
                  </select>
                  <div class="invalid-feedback">Bitte Land wählen.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Verlorener Betrag (€) *</label>
                  <input type="number" class="form-control" name="amount_lost" required min="1" placeholder="z.B. 15000">
                  <div class="invalid-feedback">Bitte Betrag angeben.</div>
                </div>
                <div class="col-12">
                  <label class="form-label fw-semibold">Betrugsart *</label>
                  <select class="form-select" name="platform_category" required>
                    <option value="">Bitte wählen...</option>
                    <option value="Krypto">Krypto-Betrug</option>
                    <option value="Forex">Forex-Betrug</option>
                    <option value="Binaere_Optionen">Binäre Optionen</option>
                    <option value="Romance_Scam">Romance-Scam</option>
                    <option value="CFD">CFD-Betrug</option>
                    <option value="Sonstiges">Sonstiges</option>
                  </select>
                  <div class="invalid-feedback">Bitte Betrugsart wählen.</div>
                </div>
                <div class="col-12">
                  <label class="form-label fw-semibold">Fallbeschreibung *</label>
                  <textarea class="form-control" name="case_description" rows="4" required placeholder="Beschreiben Sie kurz Ihren Fall – Plattform, Zeitraum, was passiert ist..."></textarea>
                  <div class="invalid-feedback">Bitte Ihren Fall beschreiben.</div>
                </div>
                <div class="col-12">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="privacy1" name="privacy" required>
                    <label class="form-check-label" for="privacy1">Ich stimme der <a href="#" class="text-decoration-underline" style="color:var(--accent)">Datenschutzerklärung</a> zu und möchte kontaktiert werden. *</label>
                    <div class="invalid-feedback">Bitte Datenschutzerklärung akzeptieren.</div>
                  </div>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn-submit">Jetzt kostenlos starten →</button>
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
<section class="badges-section">
  <div class="container">
    <div class="row g-3 justify-content-center">
      <div class="col-6 col-md-2"><div class="trust-badge"><i class="bi bi-lock-fill"></i> SSL-gesichert</div></div>
      <div class="col-6 col-md-2"><div class="trust-badge"><i class="bi bi-bank"></i> BaFin-Partner</div></div>
      <div class="col-6 col-md-2"><div class="trust-badge"><i class="bi bi-shield-check"></i> DSGVO</div></div>
      <div class="col-6 col-md-2"><div class="trust-badge"><i class="bi bi-award"></i> ISO 27001</div></div>
      <div class="col-6 col-md-2"><div class="trust-badge"><i class="bi bi-star-fill"></i> TÜV-geprüft</div></div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="faq-section" id="faq">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="text-center mb-5" data-aos="fade-up">
          <div class="section-eyebrow">FAQ</div>
          <h2 class="section-title">Häufige Fragen</h2>
        </div>
        <div class="accordion" id="faqAccordion">
          <div class="accordion-item border-0 mb-3 rounded shadow-sm">
            <h2 class="accordion-header"><button class="accordion-button rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">Was kostet die Erstprüfung?</button></h2>
            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion"><div class="accordion-body text-muted">Die Erstprüfung ist vollkommen kostenlos und unverbindlich. Wir arbeiten ausschließlich auf Erfolgsbasis – Sie zahlen nur, wenn wir Ihr Kapital erfolgreich zurückgeholt haben.</div></div>
          </div>
          <div class="accordion-item border-0 mb-3 rounded shadow-sm">
            <h2 class="accordion-header"><button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">Wie lange dauert der Prozess?</button></h2>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion"><div class="accordion-body text-muted">Die KI-Erstanalyse erfolgt innerhalb von 72 Stunden. Je nach Komplexität des Falls dauert der gesamte Rückholungsprozess zwischen 3 und 18 Monaten.</div></div>
          </div>
          <div class="accordion-item border-0 mb-3 rounded shadow-sm">
            <h2 class="accordion-header"><button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">Welche Betrugsarten können rückgefordert werden?</button></h2>
            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion"><div class="accordion-body text-muted">Wir helfen bei Krypto-Betrug, Forex-Betrug, Binären Optionen, Romance-Scam, CFD-Betrug und anderen Formen von Anlagebetrug.</div></div>
          </div>
          <div class="accordion-item border-0 mb-3 rounded shadow-sm">
            <h2 class="accordion-header"><button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">Ist mein Fall noch rückforderbar?</button></h2>
            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion"><div class="accordion-body text-muted">In den meisten Fällen ja – auch ältere Fälle können erfolgreich bearbeitet werden. Lassen Sie Ihren Fall kostenlos prüfen, um eine klare Einschätzung zu erhalten.</div></div>
          </div>
          <div class="accordion-item border-0 mb-3 rounded shadow-sm">
            <h2 class="accordion-header"><button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">Wie sicher sind meine Daten?</button></h2>
            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion"><div class="accordion-body text-muted">Ihre Daten werden nach DSGVO verarbeitet und mit SSL verschlüsselt übertragen. Wir geben keine Daten an Dritte weiter und löschen Daten auf Anfrage.</div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer class="footer-d1">
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <div class="footer-brand">Verlust<span>Rückholung</span></div>
        <p class="small opacity-75">KI-gestützte Kapitalrückholung bei Anlagebetrug. Professionell, diskret, erfolgsorientiert.</p>
      </div>
      <div class="col-md-4">
        <h6 class="text-white fw-bold mb-3">Navigation</h6>
        <a href="#leistungen" class="footer-link">Leistungen</a>
        <a href="#how-it-works" class="footer-link">Ablauf</a>
        <a href="#fraud-types" class="footer-link">Betrugsarten</a>
        <a href="#faq" class="footer-link">FAQ</a>
        <a href="#contact-form" class="footer-link">Kontakt</a>
      </div>
      <div class="col-md-4">
        <h6 class="text-white fw-bold mb-3">Kontakt</h6>
        <p class="small opacity-75"><i class="bi bi-envelope me-2"></i>info@verlustrückholung.de</p>
        <p class="small opacity-75"><i class="bi bi-telephone me-2"></i>+49 800 123 4567</p>
        <p class="small opacity-75"><i class="bi bi-geo-alt me-2"></i>Frankfurt am Main, Deutschland</p>
      </div>
    </div>
    <div class="border-top border-secondary pt-3 text-center">
      <small class="opacity-50">&copy; <?= date('Y') ?> VerlustRückholung · <a href="#" class="footer-link d-inline">Datenschutz</a> · <a href="#" class="footer-link d-inline">Impressum</a></small>
    </div>
  </div>
</footer>

<!-- ENGAGEMENT MODAL -->
<div class="modal fade" id="engModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header modal-hdr-blue border-0">
        <h5 class="modal-title fw-bold"><i class="bi bi-shield-check me-2"></i>Kostenlose KI-Erstprüfung – Jetzt starten</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <p class="text-muted mb-4">Schildern Sie uns kurz Ihren Fall – unsere KI analysiert ihn kostenlos und unverbindlich.</p>
        <form id="engForm" action="../submit_lead.php" method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="visit_id" data-visit-id value="">
          <div class="row g-3">
            <div class="col-6">
              <input type="text" class="form-control" name="first_name" required placeholder="Vorname *">
              <div class="invalid-feedback">Erforderlich.</div>
            </div>
            <div class="col-6">
              <input type="text" class="form-control" name="last_name" required placeholder="Nachname *">
              <div class="invalid-feedback">Erforderlich.</div>
            </div>
            <div class="col-12">
              <input type="email" class="form-control" name="email" required placeholder="E-Mail *">
              <div class="invalid-feedback">Gültige E-Mail erforderlich.</div>
            </div>
            <div class="col-12">
              <input type="tel" class="form-control" name="phone" placeholder="Telefon (optional)">
            </div>
            <div class="col-12">
              <input type="number" class="form-control" name="amount_lost" required min="1" placeholder="Verlorener Betrag in € *">
              <div class="invalid-feedback">Betrag erforderlich.</div>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="engPrivacy" name="privacy" required>
                <label class="form-check-label small" for="engPrivacy">Ich stimme der Datenschutzerklärung zu. *</label>
                <div class="invalid-feedback">Bitte zustimmen.</div>
              </div>
            </div>
            <div class="col-12">
              <button type="submit" class="btn-submit">Jetzt kostenlos starten →</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
// Navbar scroll
window.addEventListener('scroll',function(){document.querySelector('.navbar-d1').classList.toggle('scrolled',window.scrollY>50);});

// HERO CANVAS – Globe Connection Lines
(function(){
  var canvas=document.getElementById('heroCanvas1');
  if(!canvas)return;
  var ctx=canvas.getContext('2d');
  function resize(){canvas.width=canvas.offsetWidth;canvas.height=canvas.offsetHeight;}
  resize();window.addEventListener('resize',resize);
  var nodes=[],NUM=35,rotation=0;
  function init(){
    nodes=[];
    for(var i=0;i<NUM;i++){
      var phi=Math.acos(1-2*(i+0.5)/NUM);
      var theta=Math.PI*(1+Math.sqrt(5))*i;
      nodes.push({phi:phi,theta:theta,
        traveler:i<3?{pos:0,speed:0.003+Math.random()*0.003,pathIdx:Math.floor(Math.random()*NUM)}:null});
    }
  }
  init();window.addEventListener('resize',init);
  function project(phi,theta,rot){
    var r=Math.min(canvas.width,canvas.height)*0.28;
    var x0=r*Math.sin(phi)*Math.cos(theta+rot);
    var z=r*Math.sin(phi)*Math.sin(theta+rot);
    var y0=r*Math.cos(phi);
    var scale=1+(z/r)*0.3;
    return{x:canvas.width/2+x0*scale,y:canvas.height/2-y0*scale,z:z,scale:scale,r:r};
  }
  function animate(){
    requestAnimationFrame(animate);
    ctx.clearRect(0,0,canvas.width,canvas.height);
    rotation+=0.003;
    var pts=nodes.map(function(n){return Object.assign({},project(n.phi,n.theta,rotation),{node:n});});
    for(var i=0;i<pts.length;i++){
      for(var j=i+1;j<pts.length;j++){
        var dx=pts[i].x-pts[j].x,dy=pts[i].y-pts[j].y;
        var dist=Math.sqrt(dx*dx+dy*dy);
        if(dist<canvas.width*0.2){
          var alpha=Math.max(0,(1-dist/(canvas.width*0.2))*0.35);
          ctx.strokeStyle='rgba(96,165,250,'+alpha+')';
          ctx.lineWidth=0.8;
          ctx.beginPath();ctx.moveTo(pts[i].x,pts[i].y);ctx.lineTo(pts[j].x,pts[j].y);ctx.stroke();
        }
      }
    }
    pts.forEach(function(p){
      var nodeR=Math.max(1.5,2.5*p.scale);
      var alpha=0.3+0.5*(p.z/p.r+1)/2;
      ctx.beginPath();ctx.arc(p.x,p.y,nodeR,0,Math.PI*2);
      ctx.fillStyle='rgba(96,165,250,'+Math.min(1,alpha)+')';ctx.fill();
    });
    nodes.forEach(function(n,i){
      if(!n.traveler)return;
      var t=n.traveler;
      t.pos+=t.speed;if(t.pos>1){t.pos=0;t.pathIdx=(t.pathIdx+1)%NUM;}
      var a=pts[i],b=pts[t.pathIdx];
      var x=a.x+(b.x-a.x)*t.pos,y=a.y+(b.y-a.y)*t.pos;
      ctx.beginPath();ctx.arc(x,y,4,0,Math.PI*2);
      ctx.fillStyle='rgba(147,197,253,0.9)';ctx.fill();
      ctx.beginPath();ctx.arc(x,y,8,0,Math.PI*2);
      ctx.fillStyle='rgba(147,197,253,0.2)';ctx.fill();
    });
  }
  animate();
})();

// AI Terminal animation
(function(){
  var lines=document.querySelectorAll('.t-line');
  var i=0;
  function showNext(){if(i<lines.length){lines[i].style.animationDelay=(i*0.6)+'s';i++;setTimeout(showNext,700);}}
  setTimeout(showNext,500);
})();

// AI progress bars on scroll
(function(){
  var obs=new IntersectionObserver(function(entries){
    entries.forEach(function(e){
      if(e.isIntersecting){
        e.target.querySelectorAll('.ai-bar-fill').forEach(function(bar){
          bar.style.width=bar.dataset.width+'%';
        });
        obs.unobserve(e.target);
      }
    });
  },{threshold:0.4});
  var sec=document.getElementById('ai-section');
  if(sec)obs.observe(sec);
})();

// CountUp
function countUp(el,target){
  var start=0,step=target/120;
  var timer=setInterval(function(){
    start=Math.min(start+step,target);
    el.textContent=Math.floor(start).toLocaleString('de-DE')+(el.dataset.suffix||'');
    if(start>=target)clearInterval(timer);
  },16);
}
var statsObs=new IntersectionObserver(function(entries){
  entries.forEach(function(e){
    if(e.isIntersecting){
      e.target.querySelectorAll('[data-count]').forEach(function(el){countUp(el,parseInt(el.dataset.count));});
      statsObs.unobserve(e.target);
    }
  });
},{threshold:0.5});
var sSec=document.getElementById('stats-section');if(sSec)statsObs.observe(sSec);

// AJAX Forms
function setupForm(formId,source){
  var form=document.getElementById(formId);if(!form)return;
  form.addEventListener('submit',function(e){
    e.preventDefault();
    if(!form.checkValidity()){form.classList.add('was-validated');return;}
    var btn=form.querySelector('[type="submit"]');var orig=btn?btn.innerHTML:'';
    if(btn){btn.disabled=true;btn.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Wird geprüft...';}
    var fd=new FormData(form);fd.append('_ajax','1');fd.append('_source',source);
    fetch('../submit_lead.php',{method:'POST',body:fd})
      .then(function(r){return r.json();})
      .then(function(data){
        if(data.csrf_token)document.querySelectorAll('input[name="csrf_token"]').forEach(function(el){el.value=data.csrf_token;});
        var alertEl=document.createElement('div');
        alertEl.className='alert '+(data.success?'alert-success':'alert-danger')+' mt-3';
        alertEl.innerHTML=data.success?'<i class="bi bi-check-circle me-2"></i><strong>Vielen Dank!</strong> '+data.message:'<i class="bi bi-exclamation-triangle me-2"></i>'+(data.message||'Fehler. Bitte erneut versuchen.');
        form.parentNode.insertBefore(alertEl,form);
        if(data.success)form.style.display='none';
        else if(btn){btn.disabled=false;btn.innerHTML=orig;}
      })
      .catch(function(){if(btn){btn.disabled=false;btn.innerHTML=orig;}});
  });
}
setupForm('mainForm','design1');setupForm('engForm','design1_modal');setupForm('contactModalForm','contact_modal');

// AOS
if(typeof AOS!=='undefined')AOS.init({duration:700,once:true,offset:80});

// Engagement modal
setTimeout(function(){var el=document.getElementById('engModal');if(el){var m=new bootstrap.Modal(el);m.show();}},<?= $modal_delay * 1000 ?>);
</script>
<!-- Visitor Tracking -->
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
      <div class="modal-header text-white py-3 px-4" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);border:none;">
        <div>
          <div class="small fw-semibold opacity-75 mb-1"><i class="bi bi-robot me-1"></i>KI-gestützte Fallanalyse · Kostenlos &amp; Unverbindlich</div>
          <h5 class="modal-title fw-bold mb-0" id="contactModalLabel">Kostenlose Erstprüfung – Ihr Fall in 72h analysiert</h5>
        </div>
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div class="row g-0">
          <!-- Left trust panel – hidden on mobile -->
          <div class="col-lg-4 d-none d-lg-flex flex-column justify-content-between p-4" style="background:#f8faff;border-right:1px solid #e2e8f0;">
            <div>
              <div class="fw-bold text-dark mb-3">Warum VerlustRückholung?</div>
              <ul class="list-unstyled small text-muted">
                <li class="mb-3 d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#2563eb;flex-shrink:0"></i><span><strong class="text-dark">87% Erfolgsquote</strong> – verifiziert über 2.400 Mandate</span></li>
                <li class="mb-3 d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#2563eb;flex-shrink:0"></i><span><strong class="text-dark">€0 Vorauszahlung</strong> – Sie zahlen nur bei Erfolg</span></li>
                <li class="mb-3 d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#2563eb;flex-shrink:0"></i><span><strong class="text-dark">72h Erstantwort</strong> – KI prüft Ihren Fall sofort</span></li>
                <li class="mb-3 d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#2563eb;flex-shrink:0"></i><span><strong class="text-dark">40+ Länder</strong> – Internationales Expertennetzwerk</span></li>
                <li class="d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#2563eb;flex-shrink:0"></i><span><strong class="text-dark">DSGVO-konform</strong> – Höchste Datensicherheit</span></li>
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
          <!-- Right form panel -->
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
                      Ich stimme der <a href="#" style="color:#2563eb">Datenschutzerklärung</a> zu und bin einverstanden, dass meine Daten zur Fallbearbeitung verwendet werden. *
                    </label>
                    <div class="invalid-feedback">Bitte Datenschutz bestätigen.</div>
                  </div>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn w-100 fw-bold py-3" style="background:#2563eb;color:#fff;font-size:1rem;border:none;border-radius:10px;">
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
