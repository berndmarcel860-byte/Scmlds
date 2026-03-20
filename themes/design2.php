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
<meta name="description" content="VerlustRückholung – KI-gestützte juristische Analyse zur Rückforderung verlorener Kapitalanlagen bei Anlagebetrug.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
<style>
:root {
  --accent:   #b45309;
  --accent-d: #92400e;
  --navy:     #1e293b;
  --navy-d:   #0f172a;
  --text:     #1a202c;
  --muted:    #64748b;
  --light-bg: #f8fafc;
  --gold-bg:  #fffbeb;
  --border:   #e2e8f0;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body { font-family: 'Inter', sans-serif; color: var(--text); background: #fff; overflow-x: hidden; }

/* ── NAVBAR ── */
.navbar { background: var(--navy-d) !important; padding: 0.75rem 0; transition: box-shadow .3s; }
.navbar.scrolled { box-shadow: 0 4px 24px rgba(0,0,0,.5); }
.navbar-brand { font-size: 1.5rem; font-weight: 800; color: #fff !important; letter-spacing: -.5px; }
.navbar-brand span { color: var(--accent); }
.navbar-nav .nav-link { color: rgba(255,255,255,.8) !important; font-weight: 500; font-size: .9rem; transition: color .2s; }
.navbar-nav .nav-link:hover { color: var(--accent) !important; }
.btn-nav-cta { background: var(--accent); color: #fff !important; border-radius: 6px; padding: .45rem 1.1rem !important; font-weight: 600; transition: background .2s; }
.btn-nav-cta:hover { background: var(--accent-d) !important; }

/* ── HERO ── */
#hero {
  position: relative;
  min-height: 100vh;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
  display: flex; align-items: center; overflow: hidden;
}
#heroCanvas2 { position: absolute; inset: 0; width: 100%; height: 100%; opacity: .55; }
.hero-content { position: relative; z-index: 2; }
.hero-eyebrow { display: inline-block; background: rgba(180,83,9,.18); border: 1px solid rgba(180,83,9,.45); color: var(--accent); border-radius: 50px; padding: .35rem 1rem; font-size: .8rem; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 1.5rem; }
.hero-title { font-size: clamp(2.2rem, 5vw, 3.8rem); font-weight: 900; color: #fff; line-height: 1.1; margin-bottom: 1rem; }
.hero-title .accent-word { color: var(--accent); }
.hero-sub { font-size: 1.1rem; color: rgba(255,255,255,.75); max-width: 560px; line-height: 1.75; margin-bottom: 2rem; }
.btn-hero { background: var(--accent); color: #fff; border: none; border-radius: 8px; padding: .85rem 2.2rem; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background .2s, transform .15s; display: inline-block; text-decoration: none; }
.btn-hero:hover { background: var(--accent-d); transform: translateY(-2px); color: #fff; }
.btn-hero-outline { background: transparent; border: 2px solid rgba(255,255,255,.35); color: #fff; border-radius: 8px; padding: .85rem 2.2rem; font-size: 1rem; font-weight: 600; cursor: pointer; transition: border-color .2s, background .2s; display: inline-block; text-decoration: none; margin-left: .75rem; }
.btn-hero-outline:hover { border-color: var(--accent); background: rgba(180,83,9,.12); color: #fff; }
.hero-pills { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 2.5rem; }
.hero-pill { background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.15); color: rgba(255,255,255,.8); border-radius: 50px; padding: .3rem .9rem; font-size: .8rem; display: flex; align-items: center; gap: .4rem; }
.hero-pill i { color: var(--accent); }

/* ── STATS BAND ── */
.stats-band { background: #fff; border-bottom: 1px solid var(--border); padding: 2.5rem 0; }
.stat-item { text-align: center; }
.stat-num { font-size: 2.5rem; font-weight: 900; color: var(--accent); line-height: 1; }
.stat-label { font-size: .85rem; color: var(--muted); margin-top: .25rem; font-weight: 500; }

/* ── TRUST BANNER ── */
.trust-banner { background: var(--gold-bg); border-top: 1px solid #fde68a; border-bottom: 1px solid #fde68a; padding: 1.5rem 0; }
.trust-item { display: flex; align-items: center; gap: .6rem; font-size: .88rem; font-weight: 600; color: var(--navy); }
.trust-item i { color: var(--accent); font-size: 1.2rem; }

/* ── TICKER ── */
.news-ticker { background: var(--navy); padding: .7rem 0; overflow: hidden; white-space: nowrap; }
.ticker-label { background: var(--accent); color: #fff; font-size: .75rem; font-weight: 800; padding: .2rem .7rem; border-radius: 4px; margin-right: 1rem; letter-spacing: 1px; }
.ticker-inner { display: inline-block; animation: tickerScroll 40s linear infinite; color: rgba(255,255,255,.85); font-size: .88rem; }
@keyframes tickerScroll { from { transform: translateX(0); } to { transform: translateX(-50%); } }

/* ── AI SECTION ── */
#ai-section { background: var(--gold-bg); padding: 5rem 0; }
.ai-badge { display: inline-flex; align-items: center; gap: .5rem; background: rgba(180,83,9,.12); border: 1px solid rgba(180,83,9,.3); color: var(--accent); border-radius: 50px; padding: .3rem 1rem; font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1rem; }
.scanner-wrap { background: #fff; border-radius: 16px; padding: 2rem; box-shadow: 0 8px 40px rgba(0,0,0,.08); border: 1px solid #fde68a; }
.scanner-doc { position: relative; background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; overflow: hidden; }
.scanner-line { position: absolute; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, transparent, var(--accent), transparent); top: 0; animation: scanLine 2.4s ease-in-out infinite; }
@keyframes scanLine { 0%,100%{top:0;opacity:1} 90%{top:calc(100% - 3px);opacity:1} }
.doc-line { height: 10px; background: #e2e8f0; border-radius: 4px; margin-bottom: .6rem; }
.doc-line.short { width: 55%; }
.doc-line.med { width: 75%; }
.check-item { display: flex; align-items: center; gap: .6rem; padding: .5rem 0; border-bottom: 1px solid var(--border); font-size: .88rem; color: var(--navy); }
.check-item:last-child { border-bottom: none; }
.check-icon { width: 22px; height: 22px; border-radius: 50%; background: #dcfce7; display: flex; align-items: center; justify-content: center; font-size: .7rem; color: #16a34a; opacity: 0; transform: scale(.5); transition: opacity .4s, transform .4s; }
.check-icon.revealed { opacity: 1; transform: scale(1); }
.ai-feature { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
.ai-icon-box { width: 48px; height: 48px; background: rgba(180,83,9,.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.3rem; flex-shrink: 0; }

/* ── HOW IT WORKS ── */
.how-section { background: var(--light-bg); padding: 5rem 0; }
.step-card { text-align: center; padding: 2rem 1.5rem; }
.step-num { width: 64px; height: 64px; border-radius: 50%; background: var(--accent); color: #fff; font-size: 1.6rem; font-weight: 900; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; }
.step-connector { position: absolute; top: 32px; left: calc(50% + 32px); right: calc(-50% + 32px); height: 2px; background: #fde68a; z-index: 0; }

/* ── SECTION HEADER ── */
.sec-header { text-align: center; margin-bottom: 3.5rem; }
.sec-tag { display: inline-block; background: rgba(180,83,9,.1); color: var(--accent); border-radius: 50px; padding: .25rem .85rem; font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: .75rem; }
.sec-title { font-size: clamp(1.7rem, 3.5vw, 2.5rem); font-weight: 800; color: var(--navy); }
.sec-sub { color: var(--muted); max-width: 560px; margin: .75rem auto 0; line-height: 1.7; }

/* ── LEISTUNGEN CARDS ── */
.leistungen-section { padding: 5rem 0; background: #fff; }
.leistung-card { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 1.75rem; height: 100%; border-bottom: 3px solid var(--accent); transition: box-shadow .25s, transform .25s; }
.leistung-card:hover { box-shadow: 0 8px 32px rgba(0,0,0,.1); transform: translateY(-4px); }
.leistung-icon { width: 52px; height: 52px; background: rgba(180,83,9,.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.4rem; margin-bottom: 1rem; }
.leistung-card h5 { font-size: 1rem; font-weight: 700; color: var(--navy); margin-bottom: .5rem; }
.leistung-card p { font-size: .87rem; color: var(--muted); line-height: 1.65; }

/* ── FRAUD TYPES ── */
.fraud-section { background: var(--light-bg); padding: 5rem 0; }
.fraud-card { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 1.75rem; height: 100%; position: relative; transition: box-shadow .25s; }
.fraud-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.08); }
.fraud-badge { position: absolute; top: 1rem; right: 1rem; background: var(--accent); color: #fff; border-radius: 50px; padding: .2rem .65rem; font-size: .7rem; font-weight: 700; }
.fraud-card h5 { font-size: 1rem; font-weight: 700; color: var(--navy); margin: 1rem 0 .5rem; }
.fraud-card p { font-size: .87rem; color: var(--muted); line-height: 1.65; }

/* ── LIVE TICKER ── */
.live-ticker { background: var(--navy); padding: 3rem 0; }
.live-ticker .sec-title { color: #fff; }
.live-ticker .sec-sub { color: rgba(255,255,255,.6); }
.recovery-item { background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); border-radius: 10px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; margin-bottom: .75rem; animation: fadeInUp .5s ease forwards; }
.recovery-dot { width: 10px; height: 10px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,.25); flex-shrink: 0; }
.recovery-text { font-size: .88rem; color: rgba(255,255,255,.85); flex: 1; }
.recovery-amount { font-weight: 800; color: var(--accent); font-size: 1rem; white-space: nowrap; }
@keyframes fadeInUp { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }

/* ── WHY US ── */
.whyus-section { background: #fff; padding: 5rem 0; }
.whyus-card { background: var(--light-bg); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; height: 100%; }
.whyus-card i { font-size: 2rem; color: var(--accent); margin-bottom: .75rem; }
.whyus-card h6 { font-size: .95rem; font-weight: 700; color: var(--navy); margin-bottom: .4rem; }
.whyus-card p { font-size: .85rem; color: var(--muted); line-height: 1.6; }

/* ── TESTIMONIALS ── */
.testimonial-section { background: var(--gold-bg); padding: 5rem 0; }
.testi-card { background: #fff; border-radius: 14px; padding: 2rem 1.75rem; height: 100%; border-bottom: 3px solid var(--accent); box-shadow: 0 2px 16px rgba(0,0,0,.06); }
.testi-quote { font-size: 3.5rem; color: var(--accent); line-height: .8; font-family: Georgia, serif; font-weight: 900; margin-bottom: .5rem; }
.testi-text { font-size: .92rem; color: var(--text); line-height: 1.7; font-style: italic; margin-bottom: 1.25rem; }
.testi-stars { color: var(--accent); font-size: 1rem; margin-bottom: .75rem; }
.testi-author { font-weight: 700; color: var(--navy); font-size: .9rem; }
.testi-meta { font-size: .8rem; color: var(--muted); }

/* ── STATS SECTION (DARK) ── */
.stats-section { background: var(--navy); padding: 5rem 0; }
.stats-section .sec-title { color: #fff; }
.stats-section .sec-sub { color: rgba(255,255,255,.6); }
.stat-box { text-align: center; padding: 2rem; }
.stat-box .big-num { font-size: clamp(2.5rem, 5vw, 3.8rem); font-weight: 900; color: var(--accent); line-height: 1; }
.stat-box .big-suffix { font-size: 1.8rem; color: var(--accent); font-weight: 900; }
.stat-box .stat-desc { font-size: .9rem; color: rgba(255,255,255,.7); margin-top: .5rem; }

/* ── CONTACT FORM ── */
.form-section { background: var(--light-bg); padding: 5rem 0; }
.form-card { background: #fff; border-radius: 16px; box-shadow: 0 8px 48px rgba(0,0,0,.1); overflow: hidden; }
.form-header { background: var(--navy); padding: 2.5rem; color: #fff; }
.form-header h2 { font-size: 1.7rem; font-weight: 800; margin-bottom: .5rem; }
.form-header p { color: rgba(255,255,255,.7); font-size: .95rem; }
.form-body { padding: 2.5rem; }
.form-label { font-size: .85rem; font-weight: 600; color: var(--navy); margin-bottom: .35rem; }
.form-control, .form-select { border: 1.5px solid var(--border); border-radius: 8px; font-size: .9rem; padding: .65rem 1rem; color: var(--text); transition: border-color .2s; }
.form-control:focus, .form-select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(180,83,9,.12); }
.btn-submit-gold { background: var(--accent); color: #fff; border: none; border-radius: 8px; padding: .85rem 2.5rem; font-size: 1rem; font-weight: 700; width: 100%; transition: background .2s; cursor: pointer; }
.btn-submit-gold:hover { background: var(--accent-d); }
.alert-success-msg { background: #dcfce7; border: 1px solid #86efac; color: #166534; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1rem; display: flex; align-items: center; gap: .6rem; }
.alert-error-msg { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1rem; display: flex; align-items: center; gap: .6rem; }
.spinner-sm { display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; margin-right: .4rem; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── TRUST BADGES ── */
.trust-badges { background: #fff; padding: 2.5rem 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
.badge-item { display: flex; flex-direction: column; align-items: center; gap: .4rem; }
.badge-icon { width: 56px; height: 56px; border-radius: 50%; background: rgba(180,83,9,.08); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--accent); }
.badge-label { font-size: .75rem; font-weight: 600; color: var(--muted); text-align: center; }

/* ── FAQ ── */
.faq-section { background: var(--light-bg); padding: 5rem 0; }
.accordion-item { border: 1px solid var(--border) !important; border-radius: 10px !important; margin-bottom: .75rem; overflow: hidden; }
.accordion-button { font-weight: 600; font-size: .95rem; color: var(--navy) !important; background: #fff !important; }
.accordion-button:not(.collapsed) { color: var(--accent) !important; box-shadow: none !important; }
.accordion-button::after { filter: hue-rotate(200deg); }

/* ── FOOTER ── */
.site-footer { background: var(--navy-d); color: rgba(255,255,255,.7); padding: 4rem 0 2rem; }
.footer-brand { font-size: 1.4rem; font-weight: 800; color: #fff; margin-bottom: .75rem; }
.footer-brand span { color: var(--accent); }
.footer-links { list-style: none; padding: 0; }
.footer-links li { margin-bottom: .4rem; }
.footer-links a { color: rgba(255,255,255,.6); text-decoration: none; font-size: .9rem; transition: color .2s; }
.footer-links a:hover { color: var(--accent); }
.footer-heading { color: #fff; font-weight: 700; font-size: .95rem; margin-bottom: 1rem; }
.footer-divider { border-color: rgba(255,255,255,.1); margin: 2rem 0 1.5rem; }
.footer-copy { font-size: .82rem; color: rgba(255,255,255,.4); }

/* ── MODAL ── */
.modal-header-dark { background: var(--navy); color: #fff; }
.modal-header-dark .btn-close { filter: invert(1); }
.modal-header-dark .modal-title { font-weight: 800; }

/* ── RESPONSIVE ── */
@media (max-width: 768px) {
  .hero-title { font-size: 2rem; }
  .btn-hero-outline { margin-left: 0; margin-top: .5rem; }
  .stat-num { font-size: 1.8rem; }
  .big-num { font-size: 2.2rem !important; }
}
</style>
</head>
<body>

<!-- ═══════════════════════════════════ NAVBAR ═══════════════════════════════════ -->
<nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
  <div class="container">
    <a class="navbar-brand" href="#">Verlust<span>Rückholung</span></a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 me-3">
        <li class="nav-item"><a class="nav-link" href="#ai-section">KI-Analyse</a></li>
        <li class="nav-item"><a class="nav-link" href="#how-it-works">Ablauf</a></li>
        <li class="nav-item"><a class="nav-link" href="#leistungen">Leistungen</a></li>
        <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
      </ul>
      <a href="#" class="btn-nav-cta nav-link" data-bs-toggle="modal" data-bs-target="#contactModal">Kostenlose Erstprüfung →</a>
    </div>
  </div>
</nav>

<!-- ═══════════════════════════════════ HERO ═══════════════════════════════════ -->
<section id="hero">
  <canvas id="heroCanvas2"></canvas>
  <div class="container hero-content py-5" style="margin-top:56px;">
    <div class="row align-items-center min-vh-100 py-5">
      <div class="col-lg-7">
        <div class="hero-eyebrow">⚖ Gold Standard Rückholung</div>
        <h1 class="hero-title">
          Präzision.<br>Erfahrung.<br><span class="accent-word">Ergebnisse.</span>
        </h1>
        <p class="hero-sub">
          Wir verbinden juridische Expertise mit moderner KI-Analyse, um Ihr verlorenes Kapital zurückzufordern.
        </p>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="#" class="btn-hero" data-bs-toggle="modal" data-bs-target="#contactModal">Kostenlose Erstprüfung starten →</a>
          <a href="#how-it-works" class="btn-hero-outline">Ablauf ansehen</a>
        </div>
        <div class="hero-pills">
          <span class="hero-pill"><i class="bi bi-patch-check-fill"></i> Über 2.400 Fälle gewonnen</span>
          <span class="hero-pill"><i class="bi bi-currency-euro"></i> €48M+ zurückgeholt</span>
          <span class="hero-pill"><i class="bi bi-shield-lock-fill"></i> DSGVO-konform</span>
          <span class="hero-pill"><i class="bi bi-robot"></i> KI-gestützte Analyse</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════ STATS BAND ═══════════════════════════════════ -->
<div class="stats-band">
  <div class="container">
    <div class="row g-4">
      <div class="col-6 col-md-3">
        <div class="stat-item">
          <div class="stat-num">87%</div>
          <div class="stat-label">Erfolgsquote</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-item">
          <div class="stat-num">€48M+</div>
          <div class="stat-label">Zurückgeholt</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-item">
          <div class="stat-num">2.400+</div>
          <div class="stat-label">Erfolgreiche Mandate</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-item">
          <div class="stat-num">18+</div>
          <div class="stat-label">Jahre Erfahrung</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════ TRUST BANNER ═══════════════════════════════════ -->
<div class="trust-banner">
  <div class="container">
    <div class="row g-3 justify-content-center text-center text-md-start">
      <div class="col-6 col-md-3">
        <div class="trust-item justify-content-center justify-content-md-start">
          <i class="bi bi-award-fill"></i> TÜV-zertifizierte Kanzlei
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="trust-item justify-content-center justify-content-md-start">
          <i class="bi bi-lock-fill"></i> Ende-zu-Ende verschlüsselt
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="trust-item justify-content-center justify-content-md-start">
          <i class="bi bi-person-badge-fill"></i> Lizenzierte Rechtsanwälte
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="trust-item justify-content-center justify-content-md-start">
          <i class="bi bi-clock-fill"></i> Antwort in 24 Stunden
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════ NEWS TICKER ═══════════════════════════════════ -->
<div class="news-ticker">
  <div class="d-flex align-items-center px-3">
    <span class="ticker-label">AKTUELL</span>
    <div class="ticker-inner">
      &nbsp;&nbsp;✦ Neue Warnung: Kryptobörse „TradePrime" als Betrugsplattform eingestuft &nbsp;&nbsp;✦ Erfolgreiche Rückholung: Mandant aus Wien erhält €210.000 zurück &nbsp;&nbsp;✦ BaFin: Warnung vor gefälschten Investmentportalen &nbsp;&nbsp;✦ Neue Warnung: Kryptobörse „TradePrime" als Betrugsplattform eingestuft &nbsp;&nbsp;✦ Erfolgreiche Rückholung: Mandant aus Wien erhält €210.000 zurück &nbsp;&nbsp;✦ BaFin: Warnung vor gefälschten Investmentportalen
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════ AI SECTION ═══════════════════════════════════ -->
<section id="ai-section" data-aos="fade-up">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="ai-badge"><i class="bi bi-cpu-fill"></i> KI-gestützte Fallanalyse</div>
        <h2 class="sec-title text-start mb-3">Juristische KI-Analyse</h2>
        <p class="sec-sub text-start mb-4">Unser proprietäres KI-System analysiert Ihren Fall innerhalb von Sekunden, identifiziert Betrugsmuster und ermittelt die optimale juristische Strategie.</p>
        <div class="ai-feature">
          <div class="ai-icon-box"><i class="bi bi-search"></i></div>
          <div>
            <h6 style="font-weight:700;color:var(--navy);margin-bottom:.25rem;">Mustererkennung</h6>
            <p style="font-size:.87rem;color:var(--muted);line-height:1.6;">Vergleich mit über 50.000 Betrugsfällen in unserer Datenbank.</p>
          </div>
        </div>
        <div class="ai-feature">
          <div class="ai-icon-box"><i class="bi bi-graph-up-arrow"></i></div>
          <div>
            <h6 style="font-weight:700;color:var(--navy);margin-bottom:.25rem;">Erfolgswahrscheinlichkeit</h6>
            <p style="font-size:.87rem;color:var(--muted);line-height:1.6;">Präzise Prognose basierend auf vergleichbaren abgeschlossenen Fällen.</p>
          </div>
        </div>
        <div class="ai-feature">
          <div class="ai-icon-box"><i class="bi bi-file-earmark-text"></i></div>
          <div>
            <h6 style="font-weight:700;color:var(--navy);margin-bottom:.25rem;">Strategieempfehlung</h6>
            <p style="font-size:.87rem;color:var(--muted);line-height:1.6;">Individuelle Handlungsempfehlung durch erfahrene Juristen.</p>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="scanner-wrap">
          <div style="font-size:.78rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:1rem;">Fallanalyse läuft...</div>
          <div class="scanner-doc">
            <div class="scanner-line"></div>
            <div class="doc-line"></div>
            <div class="doc-line short"></div>
            <div class="doc-line med"></div>
            <div class="doc-line short"></div>
            <div class="doc-line"></div>
          </div>
          <div id="checkList">
            <div class="check-item">
              <div class="check-icon" id="chk1"><i class="bi bi-check"></i></div>
              <span>Betrugsmuster identifiziert</span>
            </div>
            <div class="check-item">
              <div class="check-icon" id="chk2"><i class="bi bi-check"></i></div>
              <span>Rechtliche Grundlage geprüft</span>
            </div>
            <div class="check-item">
              <div class="check-icon" id="chk3"><i class="bi bi-check"></i></div>
              <span>Vergleichsfälle analysiert</span>
            </div>
            <div class="check-item">
              <div class="check-icon" id="chk4"><i class="bi bi-check"></i></div>
              <span>Strategie generiert ✓</span>
            </div>
          </div>
          <div style="margin-top:1.25rem;padding:1rem;background:rgba(180,83,9,.07);border-radius:8px;border:1px solid rgba(180,83,9,.2);">
            <div style="font-size:.8rem;font-weight:700;color:var(--accent);margin-bottom:.25rem;">ANALYSEERGEBNIS</div>
            <div style="font-size:.9rem;font-weight:600;color:var(--navy);">Hohe Erfolgswahrscheinlichkeit – Mandat empfohlen</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════ HOW IT WORKS ═══════════════════════════════════ -->
<section class="how-section" id="how-it-works" data-aos="fade-up">
  <div class="container">
    <div class="sec-header">
      <div class="sec-tag">Ablauf</div>
      <h2 class="sec-title">Wie wir vorgehen</h2>
      <p class="sec-sub">Transparent, effizient und vollständig auf Ihren Fall zugeschnitten.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="step-card" data-aos="fade-up" data-aos-delay="0">
          <div class="step-num">1</div>
          <h5 style="font-weight:700;color:var(--navy);margin-bottom:.6rem;">Erstprüfung</h5>
          <p style="color:var(--muted);font-size:.9rem;line-height:1.65;">Schildern Sie uns Ihren Fall. Unsere KI und unser Rechtsteam prüfen Ihre Situation kostenlos und unverbindlich.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="step-card" data-aos="fade-up" data-aos-delay="100">
          <div class="step-num">2</div>
          <h5 style="font-weight:700;color:var(--navy);margin-bottom:.6rem;">Strategieentwicklung</h5>
          <p style="color:var(--muted);font-size:.9rem;line-height:1.65;">Wir entwickeln eine maßgeschneiderte juristische Strategie und leiten alle notwendigen Schritte ein.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="step-card" data-aos="fade-up" data-aos-delay="200">
          <div class="step-num">3</div>
          <h5 style="font-weight:700;color:var(--navy);margin-bottom:.6rem;">Kapitalrückholung</h5>
          <p style="color:var(--muted);font-size:.9rem;line-height:1.65;">Wir setzen Ihre Ansprüche durch – außergerichtlich oder vor Gericht – bis Ihr Kapital zurückerstattet ist.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════ LEISTUNGEN ═══════════════════════════════════ -->
<section class="leistungen-section" id="leistungen" data-aos="fade-up">
  <div class="container">
    <div class="sec-header">
      <div class="sec-tag">Unsere Expertise</div>
      <h2 class="sec-title">Unsere Leistungen</h2>
      <p class="sec-sub">Vollumfängliche Unterstützung bei der Rückforderung Ihrer verlorenen Kapitalanlagen.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="0">
        <div class="leistung-card">
          <div class="leistung-icon"><i class="bi bi-search-heart"></i></div>
          <h5>Fallanalyse & Bewertung</h5>
          <p>Kostenlose Erstprüfung Ihres Falles durch KI-gestützte Analyse und erfahrene Juristen mit fundierter Erfolgsprognose.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="80">
        <div class="leistung-card">
          <div class="leistung-icon"><i class="bi bi-shield-exclamation"></i></div>
          <h5>Betrugsdokumentation</h5>
          <p>Professionelle Sicherung und Aufbereitung aller Beweismittel für außergerichtliche und gerichtliche Verfahren.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="160">
        <div class="leistung-card">
          <div class="leistung-icon"><i class="bi bi-bank2"></i></div>
          <h5>Chargeback-Verfahren</h5>
          <p>Einleitung von Rückbuchungsverfahren bei Banken und Kreditkartenunternehmen zur schnellen Kapitalrückholung.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="0">
        <div class="leistung-card">
          <div class="leistung-icon"><i class="bi bi-globe-europe-africa"></i></div>
          <h5>Internationale Rechtsverfolgung</h5>
          <p>Grenzüberschreitende Strafverfolgung in Zusammenarbeit mit internationalen Behörden und Partnerkanzleien.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="80">
        <div class="leistung-card">
          <div class="leistung-icon"><i class="bi bi-currency-bitcoin"></i></div>
          <h5>Krypto-Asset-Rückverfolgung</h5>
          <p>Blockchain-Forensik und On-Chain-Analyse zur Rückverfolgung und Sicherung transferierter Kryptowerte.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="160">
        <div class="leistung-card">
          <div class="leistung-icon"><i class="bi bi-chat-square-quote-fill"></i></div>
          <h5>Psychologische Betreuung</h5>
          <p>Einfühlsame Begleitung durch den gesamten Prozess mit persönlichem Ansprechpartner und regelmäßigen Updates.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════ FRAUD TYPES ═══════════════════════════════════ -->
<section class="fraud-section" data-aos="fade-up">
  <div class="container">
    <div class="sec-header">
      <div class="sec-tag">Betrugsmuster</div>
      <h2 class="sec-title">Bekannte Betrugsmaschen</h2>
      <p class="sec-sub">Wir kennen alle gängigen Methoden und helfen Ihnen unabhängig davon, welcher Betrugsmasche Sie zum Opfer gefallen sind.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="0">
        <div class="fraud-card">
          <div class="fraud-badge">⚠ Häufig</div>
          <i class="bi bi-coin" style="font-size:2rem;color:var(--accent)"></i>
          <h5>Krypto-Investment-Betrug</h5>
          <p>Gefälschte Kryptoplattformen versprechen hohe Renditen und verschwinden mit Ihrem Kapital.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="80">
        <div class="fraud-card">
          <div class="fraud-badge">⚠ Verbreitet</div>
          <i class="bi bi-graph-up" style="font-size:2rem;color:var(--accent)"></i>
          <h5>Forex-Betrug</h5>
          <p>Unlizenzierte Broker manipulieren Kurse und verweigern Auszahlungen an ihre Kunden.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="160">
        <div class="fraud-card">
          <div class="fraud-badge">⚠ Neu</div>
          <i class="bi bi-heart-pulse" style="font-size:2rem;color:var(--accent)"></i>
          <h5>Romance Scam</h5>
          <p>Betrüger erschleichen sich durch gefälschte Beziehungen Vertrauen und Geldtransfers.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="240">
        <div class="fraud-card">
          <div class="fraud-badge">⚠ Kritisch</div>
          <i class="bi bi-building-exclamation" style="font-size:2rem;color:var(--accent)"></i>
          <h5>Fake-Fonds & Zertifikate</h5>
          <p>Gefälschte Investmentfonds mit professionell gestalteten Prospekten und Abschlussberichten.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════ LIVE RECOVERY TICKER ═══════════════════════════════════ -->
<section class="live-ticker" data-aos="fade-up">
  <div class="container">
    <div class="sec-header">
      <div class="sec-tag" style="background:rgba(255,255,255,.1);color:rgba(255,255,255,.8);">Live</div>
      <h2 class="sec-title">Aktuelle Rückholungen</h2>
      <p class="sec-sub">Verfolgen Sie anonymisiert, was wir aktuell für unsere Mandanten zurückholen.</p>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-8" id="recoveryFeed">
        <div class="recovery-item">
          <div class="recovery-dot"></div>
          <div class="recovery-text">Mandant aus <strong>München</strong> – Krypto-Investment-Betrug</div>
          <div class="recovery-amount">€87.500</div>
        </div>
        <div class="recovery-item" style="animation-delay:.2s">
          <div class="recovery-dot"></div>
          <div class="recovery-text">Mandant aus <strong>Wien</strong> – Forex-Broker-Betrug</div>
          <div class="recovery-amount">€142.000</div>
        </div>
        <div class="recovery-item" style="animation-delay:.4s">
          <div class="recovery-dot"></div>
          <div class="recovery-text">Mandant aus <strong>Zürich</strong> – Fake-Fonds-Betrug</div>
          <div class="recovery-amount">€63.200</div>
        </div>
        <div class="recovery-item" style="animation-delay:.6s">
          <div class="recovery-dot"></div>
          <div class="recovery-text">Mandant aus <strong>Hamburg</strong> – Binary-Options-Betrug</div>
          <div class="recovery-amount">€29.800</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════ WHY US ═══════════════════════════════════ -->
<section class="whyus-section" data-aos="fade-up">
  <div class="container">
    <div class="sec-header">
      <div class="sec-tag">Warum wir?</div>
      <h2 class="sec-title">Ihr Vorteil mit VerlustRückholung</h2>
      <p class="sec-sub">Wir unterscheiden uns durch Technologie, Erfahrung und konsequenten Einsatz für unsere Mandanten.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="0">
        <div class="whyus-card">
          <i class="bi bi-robot"></i>
          <h6>KI-gestützte Analyse</h6>
          <p>Proprietäre Algorithmen analysieren Ihren Fall in Sekunden und finden optimale juristische Hebel.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="80">
        <div class="whyus-card">
          <i class="bi bi-cash-coin"></i>
          <h6>Erfolgsbasiertes Honorar</h6>
          <p>Sie zahlen nur im Erfolgsfall. Keine versteckten Kosten, keine Vorauszahlungen.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="160">
        <div class="whyus-card">
          <i class="bi bi-globe2"></i>
          <h6>Internationales Netzwerk</h6>
          <p>Partnerkanzleien in 28 Ländern ermöglichen die grenzüberschreitende Rechtsverfolgung.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="0">
        <div class="whyus-card">
          <i class="bi bi-shield-check"></i>
          <h6>Zertifizierte Sicherheit</h6>
          <p>Alle Daten werden nach ISO 27001 verarbeitet. Ihre Informationen sind bei uns sicher.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="80">
        <div class="whyus-card">
          <i class="bi bi-person-lines-fill"></i>
          <h6>Persönliche Betreuung</h6>
          <p>Fester Ansprechpartner vom ersten Gespräch bis zur Rückzahlung Ihres Kapitals.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="160">
        <div class="whyus-card">
          <i class="bi bi-lightning-charge-fill"></i>
          <h6>Schnelle Reaktion</h6>
          <p>Innerhalb von 24 Stunden erhalten Sie eine qualifizierte Rückmeldung zu Ihrem Fall.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════ TESTIMONIALS ═══════════════════════════════════ -->
<section class="testimonial-section" data-aos="fade-up">
  <div class="container">
    <div class="sec-header">
      <div class="sec-tag">Stimmen unserer Mandanten</div>
      <h2 class="sec-title">Was unsere Mandanten sagen</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
        <div class="testi-card">
          <div class="testi-quote">&ldquo;</div>
          <div class="testi-stars">★★★★★</div>
          <p class="testi-text">Nach einem Verlust von €95.000 durch einen gefälschten Kryptobroker hatte ich jede Hoffnung aufgegeben. VerlustRückholung hat mir in weniger als 8 Monaten €78.000 zurückgeholt.</p>
          <div class="testi-author">Thomas K.</div>
          <div class="testi-meta">Frankfurt am Main – Krypto-Betrug</div>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="120">
        <div class="testi-card">
          <div class="testi-quote">&ldquo;</div>
          <div class="testi-stars">★★★★★</div>
          <p class="testi-text">Das Team ist absolut professionell und hat mich in jeder Phase des Verfahrens transparent informiert. Ich habe €120.000 von einem Fake-Forex-Broker zurückerhalten.</p>
          <div class="testi-author">Sabine M.</div>
          <div class="testi-meta">Wien – Forex-Betrug</div>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="240">
        <div class="testi-card">
          <div class="testi-quote">&ldquo;</div>
          <div class="testi-stars">★★★★★</div>
          <p class="testi-text">Die KI-Analyse hat meinen Fall sofort als hocherfolgswahrscheinlich eingestuft – zu Recht. Innerhalb von 6 Monaten erhielt ich €54.500 zurück. Unglaublich kompetent.</p>
          <div class="testi-author">Markus R.</div>
          <div class="testi-meta">Zürich – Investment-Betrug</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════ STATISTICS SECTION (DARK) ═══════════════════════════════════ -->
<section class="stats-section" id="stats-section" data-aos="fade-up">
  <div class="container">
    <div class="sec-header">
      <div class="sec-tag" style="background:rgba(255,255,255,.1);color:rgba(255,255,255,.85);">Zahlen</div>
      <h2 class="sec-title">Unsere Bilanz spricht für sich</h2>
      <p class="sec-sub">Belastbare Daten statt leerer Versprechen.</p>
    </div>
    <div class="row g-4">
      <div class="col-6 col-md-3">
        <div class="stat-box">
          <div><span class="big-num" data-target="87">0</span><span class="big-suffix">%</span></div>
          <div class="stat-desc">Erfolgsquote aller Mandate</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-box">
          <div><span class="big-num" data-target="48" data-prefix="€" data-suffix="M+">0</span></div>
          <div class="stat-desc">Zurückgeholtes Kapital</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-box">
          <div><span class="big-num" data-target="2400" data-suffix="+">0</span></div>
          <div class="stat-desc">Erfolgreiche Mandate</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-box">
          <div><span class="big-num" data-target="18" data-suffix="+">0</span></div>
          <div class="stat-desc">Jahre Erfahrung</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════ MAIN CONTACT FORM ═══════════════════════════════════ -->
<section class="form-section" id="mainForm" data-aos="fade-up">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-9">
        <div class="form-card">
          <div class="form-header">
            <h2>Mandat anfragen</h2>
            <p>Schildern Sie uns Ihren Fall – kostenlos, vertraulich und unverbindlich. Wir melden uns innerhalb von 24 Stunden.</p>
            <div class="d-flex gap-3 mt-3 flex-wrap">
              <span style="font-size:.8rem;color:rgba(255,255,255,.6);display:flex;align-items:center;gap:.4rem;"><i class="bi bi-lock-fill" style="color:var(--accent)"></i>SSL-verschlüsselt</span>
              <span style="font-size:.8rem;color:rgba(255,255,255,.6);display:flex;align-items:center;gap:.4rem;"><i class="bi bi-shield-check" style="color:var(--accent)"></i>DSGVO-konform</span>
              <span style="font-size:.8rem;color:rgba(255,255,255,.6);display:flex;align-items:center;gap:.4rem;"><i class="bi bi-award" style="color:var(--accent)"></i>Kostenlose Erstprüfung</span>
            </div>
          </div>
          <div class="form-body">
            <?php if ($success): ?>
            <div class="alert-success-msg"><i class="bi bi-check-circle-fill"></i><div><strong>Vielen Dank!</strong> Wir haben Ihre Anfrage erhalten und melden uns innerhalb von 24 Stunden.</div></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert-error-msg"><i class="bi bi-exclamation-triangle-fill"></i><div><?= $error ?></div></div>
            <?php endif; ?>

            <form action="../submit_lead.php" method="POST" id="mainContactForm" novalidate>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="visit_id" id="mainVisitId" value="">
              <input type="hidden" name="form_id" value="mainForm">

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Vorname *</label>
                  <input type="text" name="first_name" class="form-control" placeholder="Max" required>
                  <div class="invalid-feedback">Bitte geben Sie Ihren Vornamen ein.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Nachname *</label>
                  <input type="text" name="last_name" class="form-control" placeholder="Mustermann" required>
                  <div class="invalid-feedback">Bitte geben Sie Ihren Nachnamen ein.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">E-Mail-Adresse *</label>
                  <input type="email" name="email" class="form-control" placeholder="max@beispiel.de" required>
                  <div class="invalid-feedback">Bitte geben Sie eine gültige E-Mail-Adresse ein.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Telefonnummer</label>
                  <input type="tel" name="phone" class="form-control" placeholder="+49 123 456789">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Land *</label>
                  <select name="country" class="form-select" required>
                    <option value="" disabled selected>Bitte wählen</option>
                    <option value="DE">Deutschland</option>
                    <option value="AT">Österreich</option>
                    <option value="CH">Schweiz</option>
                    <option value="LU">Luxemburg</option>
                    <option value="LI">Liechtenstein</option>
                    <option value="other">Sonstige</option>
                  </select>
                  <div class="invalid-feedback">Bitte wählen Sie Ihr Land.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Verlorener Betrag (ca.) *</label>
                  <select name="amount_lost" class="form-select" required>
                    <option value="" disabled selected>Bitte wählen</option>
                    <option value="under_5000">Unter €5.000</option>
                    <option value="5000_15000">€5.000 – €15.000</option>
                    <option value="15000_50000">€15.000 – €50.000</option>
                    <option value="50000_150000">€50.000 – €150.000</option>
                    <option value="over_150000">Über €150.000</option>
                  </select>
                  <div class="invalid-feedback">Bitte wählen Sie einen Betragsbereich.</div>
                </div>
                <div class="col-12">
                  <label class="form-label">Art der Investition / Plattform *</label>
                  <select name="platform_category" class="form-select" required>
                    <option value="" disabled selected>Bitte wählen</option>
                    <option value="crypto">Kryptowährungen / Kryptobörse</option>
                    <option value="forex">Forex / CFD / Devisen</option>
                    <option value="binary">Binäre Optionen</option>
                    <option value="stocks">Aktien / Fonds / ETFs</option>
                    <option value="real_estate">Immobilien-Investment</option>
                    <option value="romance">Romance Scam</option>
                    <option value="other">Sonstiges</option>
                  </select>
                  <div class="invalid-feedback">Bitte wählen Sie eine Kategorie.</div>
                </div>
                <div class="col-12">
                  <label class="form-label">Fallbeschreibung *</label>
                  <textarea name="case_description" class="form-control" rows="4" placeholder="Bitte schildern Sie kurz, was passiert ist. Wann haben Sie investiert? Welche Plattform? Wie haben Sie den Betrug bemerkt?" required></textarea>
                  <div class="invalid-feedback">Bitte beschreiben Sie Ihren Fall kurz.</div>
                </div>
                <div class="col-12">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="privacy" id="privacyMain" required>
                    <label class="form-check-label" for="privacyMain" style="font-size:.85rem;color:var(--muted);">
                      Ich stimme der <a href="#" style="color:var(--accent);">Datenschutzerklärung</a> zu und bin damit einverstanden, dass meine Daten zur Fallbearbeitung verarbeitet werden. *
                    </label>
                    <div class="invalid-feedback">Bitte stimmen Sie der Datenschutzerklärung zu.</div>
                  </div>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn-submit-gold" id="mainSubmitBtn">
                    Mandat anfragen →
                  </button>
                  <p style="font-size:.78rem;color:var(--muted);margin-top:.75rem;text-align:center;">Kostenlos & unverbindlich · Kein Spam · Datenschutz garantiert</p>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════ TRUST BADGES ═══════════════════════════════════ -->
<div class="trust-badges">
  <div class="container">
    <div class="row g-4 justify-content-center">
      <div class="col-6 col-md-2 d-flex justify-content-center">
        <div class="badge-item">
          <div class="badge-icon"><i class="bi bi-patch-check-fill"></i></div>
          <div class="badge-label">TÜV-zertifiziert</div>
        </div>
      </div>
      <div class="col-6 col-md-2 d-flex justify-content-center">
        <div class="badge-item">
          <div class="badge-icon"><i class="bi bi-lock-fill"></i></div>
          <div class="badge-label">SSL / TLS</div>
        </div>
      </div>
      <div class="col-6 col-md-2 d-flex justify-content-center">
        <div class="badge-item">
          <div class="badge-icon"><i class="bi bi-building"></i></div>
          <div class="badge-label">BaFin-konform</div>
        </div>
      </div>
      <div class="col-6 col-md-2 d-flex justify-content-center">
        <div class="badge-item">
          <div class="badge-icon"><i class="bi bi-shield-fill-check"></i></div>
          <div class="badge-label">DSGVO-konform</div>
        </div>
      </div>
      <div class="col-6 col-md-2 d-flex justify-content-center">
        <div class="badge-item">
          <div class="badge-icon"><i class="bi bi-star-fill"></i></div>
          <div class="badge-label">4.9 / 5 Bewertung</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════ FAQ ═══════════════════════════════════ -->
<section class="faq-section" id="faq" data-aos="fade-up">
  <div class="container">
    <div class="sec-header">
      <div class="sec-tag">FAQ</div>
      <h2 class="sec-title">Häufig gestellte Fragen</h2>
      <p class="sec-sub">Antworten auf die wichtigsten Fragen rund um die Kapitalrückholung.</p>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="accordion" id="faqAccordion">
          <div class="accordion-item mb-3">
            <h2 class="accordion-header">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                Wie lange dauert ein Rückholungsverfahren?
              </button>
            </h2>
            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
              <div class="accordion-body" style="color:var(--muted);font-size:.92rem;line-height:1.7;">
                Die Dauer hängt von der Komplexität Ihres Falles und den beteiligten Behörden ab. Einfache Chargeback-Fälle können in 4–8 Wochen abgeschlossen werden. Komplexe internationale Fälle dauern 6–18 Monate. Wir geben Ihnen nach der Erstanalyse eine realistische Einschätzung.
              </div>
            </div>
          </div>
          <div class="accordion-item mb-3">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                Was kostet die Erstprüfung meines Falles?
              </button>
            </h2>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body" style="color:var(--muted);font-size:.92rem;line-height:1.7;">
                Die Erstprüfung ist vollständig kostenlos und unverbindlich. Unser KI-System und unsere Juristen analysieren Ihren Fall und geben Ihnen eine ehrliche Einschätzung der Erfolgsaussichten. Wir arbeiten ausschließlich auf Erfolgsbasis.
              </div>
            </div>
          </div>
          <div class="accordion-item mb-3">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                Können Sie auch bei länger zurückliegenden Betrugsfällen helfen?
              </button>
            </h2>
            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body" style="color:var(--muted);font-size:.92rem;line-height:1.7;">
                Ja, wir bearbeiten Fälle bis zu 10 Jahre zurückliegend – abhängig von der jeweiligen Verjährungsregelung im betreffenden Land. Die reguläre zivilrechtliche Verjährungsfrist in Deutschland beträgt 3 Jahre ab Kenntnis des Schadens. Kontaktieren Sie uns umgehend, damit wir prüfen können, ob Ihr Fall noch verfolgt werden kann.
              </div>
            </div>
          </div>
          <div class="accordion-item mb-3">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                Ist mein Fall wirklich vertraulich behandelt?
              </button>
            </h2>
            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body" style="color:var(--muted);font-size:.92rem;line-height:1.7;">
                Absolut. Alle Mitarbeiter unterliegen der anwaltlichen Verschwiegenheitspflicht. Ihre Daten werden nach ISO 27001 gespeichert, Ende-zu-Ende verschlüsselt übertragen und niemals an Dritte weitergegeben. Wir halten alle Anforderungen der DSGVO strikt ein.
              </div>
            </div>
          </div>
          <div class="accordion-item mb-3">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                Was passiert, wenn das Verfahren erfolglos bleibt?
              </button>
            </h2>
            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body" style="color:var(--muted);font-size:.92rem;line-height:1.7;">
                Da wir auf Erfolgsbasis arbeiten, entstehen Ihnen bei einem erfolglosen Verfahren keine Anwaltskosten. Wir tragen das finanzielle Risiko gemeinsam mit Ihnen. Nur wenn wir erfolgreich Kapital zurückgeholt haben, erhalten wir eine vorab vereinbarte Provision.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════ FOOTER ═══════════════════════════════════ -->
<footer class="site-footer">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-4">
        <div class="footer-brand">Verlust<span>Rückholung</span></div>
        <p style="font-size:.9rem;line-height:1.7;margin-bottom:1.25rem;">Juristische Expertise und KI-Technologie für die Rückforderung verlorener Kapitalanlagen. Ihr Vertrauen – unser Auftrag.</p>
        <div style="display:flex;gap:.75rem;">
          <a href="#" style="color:rgba(255,255,255,.5);font-size:1.3rem;"><i class="bi bi-linkedin"></i></a>
          <a href="#" style="color:rgba(255,255,255,.5);font-size:1.3rem;"><i class="bi bi-twitter-x"></i></a>
          <a href="#" style="color:rgba(255,255,255,.5);font-size:1.3rem;"><i class="bi bi-facebook"></i></a>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="footer-heading">Leistungen</div>
        <ul class="footer-links">
          <li><a href="#">Fallanalyse</a></li>
          <li><a href="#">Chargeback</a></li>
          <li><a href="#">Krypto-Rückverfolgung</a></li>
          <li><a href="#">Forex-Betrug</a></li>
          <li><a href="#">Romance Scam</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <div class="footer-heading">Unternehmen</div>
        <ul class="footer-links">
          <li><a href="#">Über uns</a></li>
          <li><a href="#">Team</a></li>
          <li><a href="#">Karriere</a></li>
          <li><a href="#">Presse</a></li>
          <li><a href="#faq">FAQ</a></li>
        </ul>
      </div>
      <div class="col-lg-4">
        <div class="footer-heading">Kontakt</div>
        <div style="font-size:.9rem;line-height:2;">
          <div><i class="bi bi-envelope" style="color:var(--accent);margin-right:.5rem;"></i>info@verlustrueckholung.de</div>
          <div><i class="bi bi-telephone" style="color:var(--accent);margin-right:.5rem;"></i>+49 800 123 456 789</div>
          <div><i class="bi bi-clock" style="color:var(--accent);margin-right:.5rem;"></i>Mo–Fr 8:00–20:00 Uhr</div>
          <div><i class="bi bi-geo-alt" style="color:var(--accent);margin-right:.5rem;"></i>Frankfurt am Main, Deutschland</div>
        </div>
      </div>
    </div>
    <hr class="footer-divider">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
      <div class="footer-copy">© <?= date('Y') ?> VerlustRückholung GmbH. Alle Rechte vorbehalten.</div>
      <div style="display:flex;gap:1.25rem;flex-wrap:wrap;">
        <a href="#" style="color:rgba(255,255,255,.4);font-size:.82rem;text-decoration:none;">Impressum</a>
        <a href="#" style="color:rgba(255,255,255,.4);font-size:.82rem;text-decoration:none;">Datenschutz</a>
        <a href="#" style="color:rgba(255,255,255,.4);font-size:.82rem;text-decoration:none;">AGB</a>
        <a href="#" style="color:rgba(255,255,255,.4);font-size:.82rem;text-decoration:none;">Cookie-Richtlinie</a>
      </div>
    </div>
  </div>
</footer>

<!-- ═══════════════════════════════════ ENGAGEMENT MODAL ═══════════════════════════════════ -->
<div class="modal fade" id="engModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0">
      <div class="modal-header modal-header-dark">
        <h5 class="modal-title">Juristische Erstprüfung anfordern</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <p class="text-muted mb-4" style="font-size:.92rem;">Schildern Sie uns kurz Ihren Fall – unsere KI analysiert ihn sofort und wir melden uns innerhalb von 24 Stunden kostenlos bei Ihnen.</p>

        <div id="engSuccessMsg" style="display:none;" class="alert-success-msg mb-3">
          <i class="bi bi-check-circle-fill"></i>
          <div><strong>Vielen Dank!</strong> Wir haben Ihre Anfrage erhalten und melden uns bald.</div>
        </div>
        <div id="engErrorMsg" style="display:none;" class="alert-error-msg mb-3">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span id="engErrorText"></span>
        </div>

        <form id="engForm" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="visit_id" id="engVisitId" value="">
          <input type="hidden" name="form_id" value="engModal">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Vorname *</label>
              <input type="text" name="first_name" class="form-control" placeholder="Max" required>
              <div class="invalid-feedback">Pflichtfeld</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Nachname *</label>
              <input type="text" name="last_name" class="form-control" placeholder="Mustermann" required>
              <div class="invalid-feedback">Pflichtfeld</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">E-Mail *</label>
              <input type="email" name="email" class="form-control" placeholder="max@beispiel.de" required>
              <div class="invalid-feedback">Gültige E-Mail erforderlich</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefon</label>
              <input type="tel" name="phone" class="form-control" placeholder="+49 123 456789">
            </div>
            <div class="col-12">
              <label class="form-label">Verlorener Betrag (ca.) *</label>
              <select name="amount_lost" class="form-select" required>
                <option value="" disabled selected>Bitte wählen</option>
                <option value="under_5000">Unter €5.000</option>
                <option value="5000_15000">€5.000 – €15.000</option>
                <option value="15000_50000">€15.000 – €50.000</option>
                <option value="50000_150000">€50.000 – €150.000</option>
                <option value="over_150000">Über €150.000</option>
              </select>
              <div class="invalid-feedback">Bitte wählen Sie einen Betragsbereich.</div>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="privacy" id="privacyEng" required>
                <label class="form-check-label" for="privacyEng" style="font-size:.83rem;color:var(--muted);">
                  Ich stimme der <a href="#" style="color:var(--accent);">Datenschutzerklärung</a> zu. *
                </label>
                <div class="invalid-feedback">Bitte zustimmen.</div>
              </div>
            </div>
            <div class="col-12">
              <button type="submit" class="btn-submit-gold" id="engSubmitBtn">
                Jetzt kostenlos prüfen →
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════ SCRIPTS ═══════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({ duration: 700, once: true, offset: 60 });

/* ── Navbar scroll ── */
(function() {
  var nav = document.getElementById('mainNav');
  window.addEventListener('scroll', function() {
    if (window.scrollY > 50) { nav.classList.add('scrolled'); }
    else { nav.classList.remove('scrolled'); }
  });
})();

/* ── heroCanvas2: Scales of Justice with Particles ── */
(function() {
  var canvas = document.getElementById('heroCanvas2');
  if (!canvas) return;
  var ctx = canvas.getContext('2d');
  var W, H, particles = [], animFrame;
  var tilt = 0;

  function resize() {
    W = canvas.width  = canvas.offsetWidth;
    H = canvas.height = canvas.offsetHeight;
  }
  resize();
  window.addEventListener('resize', resize);

  function Particle(x, y) {
    this.x  = x + (Math.random() - .5) * 30;
    this.y  = y;
    this.vy = -(Math.random() * 1.2 + 0.4);
    this.vx = (Math.random() - .5) * 0.5;
    this.life = 1;
    this.r    = Math.random() * 2.5 + 1;
  }

  function drawScales(cx, cy, tiltAngle) {
    var beamLen  = Math.min(W * 0.22, 160);
    var poleH    = Math.min(H * 0.22, 140);
    var panR     = beamLen * 0.22;
    var stringL  = poleH * 0.45;

    ctx.save();
    ctx.translate(cx, cy);

    /* vertical pole */
    ctx.strokeStyle = 'rgba(180,83,9,0.7)';
    ctx.lineWidth   = 3;
    ctx.beginPath();
    ctx.moveTo(0, -poleH);
    ctx.lineTo(0,  poleH * 0.1);
    ctx.stroke();

    /* base */
    ctx.strokeStyle = 'rgba(180,83,9,0.5)';
    ctx.lineWidth   = 6;
    ctx.beginPath();
    ctx.moveTo(-beamLen * 0.22, poleH * 0.1);
    ctx.lineTo( beamLen * 0.22, poleH * 0.1);
    ctx.stroke();

    /* rotating beam pivot */
    ctx.save();
    ctx.rotate(tiltAngle);

    /* beam */
    ctx.strokeStyle = 'rgba(212,160,50,0.85)';
    ctx.lineWidth   = 3;
    ctx.beginPath();
    ctx.moveTo(-beamLen, -poleH);
    ctx.lineTo( beamLen, -poleH);
    ctx.stroke();

    /* centre fulcrum circle */
    ctx.fillStyle = 'rgba(212,160,50,0.9)';
    ctx.beginPath();
    ctx.arc(0, -poleH, 7, 0, Math.PI * 2);
    ctx.fill();

    /* left pan strings */
    ctx.strokeStyle = 'rgba(212,160,50,0.6)';
    ctx.lineWidth   = 1.5;
    ctx.beginPath();
    ctx.moveTo(-beamLen, -poleH);
    ctx.lineTo(-beamLen - panR * 0.6, -poleH + stringL);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(-beamLen, -poleH);
    ctx.lineTo(-beamLen + panR * 0.6, -poleH + stringL);
    ctx.stroke();

    /* right pan strings */
    ctx.beginPath();
    ctx.moveTo(beamLen, -poleH);
    ctx.lineTo(beamLen - panR * 0.6, -poleH + stringL);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(beamLen, -poleH);
    ctx.lineTo(beamLen + panR * 0.6, -poleH + stringL);
    ctx.stroke();

    /* left pan */
    ctx.strokeStyle = 'rgba(212,160,50,0.85)';
    ctx.lineWidth   = 2.5;
    ctx.beginPath();
    ctx.arc(-beamLen, -poleH + stringL, panR, 0, Math.PI);
    ctx.stroke();

    /* right pan */
    ctx.beginPath();
    ctx.arc(beamLen, -poleH + stringL, panR, 0, Math.PI);
    ctx.stroke();

    ctx.restore(); /* end beam rotation */
    ctx.restore();

    /* Return pan positions for particle spawn */
    var cosT = Math.cos(tiltAngle), sinT = Math.sin(tiltAngle);
    var lx = cx + (-beamLen) * cosT - (-poleH) * sinT;
    var ly = cy + (-beamLen) * sinT + (-poleH) * cosT + stringL;
    var rx = cx + ( beamLen) * cosT - (-poleH) * sinT;
    var ry = cy + ( beamLen) * sinT + (-poleH) * cosT + stringL;
    return { lx: lx, ly: ly, rx: rx, ry: ry, tilt: tiltAngle };
  }

  var t = 0, frameCount = 0;
  /* Emit one particle every ~5 frames (~12 particles/s at 60fps) */
  var PARTICLE_INTERVAL = 5;
  function loop() {
    ctx.clearRect(0, 0, W, H);
    t += 0.012;
    frameCount++;
    tilt = Math.sin(t) * 0.28;

    var cx = W / 2, cy = H * 0.55;
    var pans = drawScales(cx, cy, tilt);

    /* emit particles from the lower pan at a fixed interval */
    var lowerPan = pans.tilt >= 0 ? { x: pans.lx, y: pans.ly } : { x: pans.rx, y: pans.ry };
    if (frameCount % PARTICLE_INTERVAL === 0) {
      particles.push(new Particle(lowerPan.x, lowerPan.y));
    }

    /* update & draw particles */
    for (var i = particles.length - 1; i >= 0; i--) {
      var p = particles[i];
      p.x   += p.vx;
      p.y   += p.vy;
      p.life -= 0.012;
      if (p.life <= 0) { particles.splice(i, 1); continue; }
      ctx.globalAlpha = p.life * 0.7;
      ctx.fillStyle   = '#d4a032';
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fill();
      ctx.globalAlpha = 1;
    }

    animFrame = requestAnimationFrame(loop);
  }
  loop();
})();

/* ── AI Section: reveal checkmarks sequentially ── */
(function() {
  var checks = ['chk1','chk2','chk3','chk4'];
  var delays = [800, 1600, 2400, 3200];
  var section = document.getElementById('ai-section');
  if (!section) return;
  var triggered = false;

  function revealChecks() {
    checks.forEach(function(id, i) {
      setTimeout(function() {
        var el = document.getElementById(id);
        if (el) el.classList.add('revealed');
      }, delays[i]);
    });
  }

  var obs = new IntersectionObserver(function(entries) {
    if (entries[0].isIntersecting && !triggered) {
      triggered = true;
      revealChecks();
    }
  }, { threshold: 0.3 });
  obs.observe(section);
})();

/* ── CountUp on IntersectionObserver ── */
(function() {
  var statsSection = document.getElementById('stats-section');
  if (!statsSection) return;
  var animated = false;

  function countUp(el, target, duration) {
    var prefix = el.dataset.prefix || '';
    var suffix = el.dataset.suffix || '';
    var start  = 0;
    var step   = target / (duration / 16);
    var timer  = setInterval(function() {
      start += step;
      if (start >= target) { start = target; clearInterval(timer); }
      el.textContent = prefix + Math.floor(start).toLocaleString('de-DE') + suffix;
    }, 16);
  }

  var obs = new IntersectionObserver(function(entries) {
    if (entries[0].isIntersecting && !animated) {
      animated = true;
      statsSection.querySelectorAll('.big-num[data-target]').forEach(function(el) {
        countUp(el, parseInt(el.dataset.target, 10), 1800);
      });
    }
  }, { threshold: 0.3 });
  obs.observe(statsSection);
})();

/* ── AJAX Form Handler ── */
function setupForm(formEl, submitBtnId, successElId, errorElId, errorTextId, redirectUrl) {
  if (!formEl) return;
  formEl.addEventListener('submit', function(e) {
    e.preventDefault();
    formEl.classList.add('was-validated');
    if (!formEl.checkValidity()) return;

    var btn = document.getElementById(submitBtnId);
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-sm"></span>Wird gesendet…'; }

    var data = new FormData(formEl);
    var visitInput = formEl.querySelector('[name="visit_id"]');
    if (visitInput && window._visitId) { visitInput.value = window._visitId; }

    fetch('../submit_lead.php', { method: 'POST', body: data })
      .then(function(res) { return res.json(); })
      .then(function(json) {
        if (json && json.success) {
          if (successElId) {
            var el = document.getElementById(successElId);
            if (el) { el.style.display = 'flex'; }
          } else if (redirectUrl) {
            window.location.href = redirectUrl;
          }
          formEl.reset();
          formEl.classList.remove('was-validated');
        } else {
          var msg = (json && json.error) ? json.error : 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
          if (errorElId) {
            var errEl = document.getElementById(errorElId);
            if (errEl) { errEl.style.display = 'flex'; }
            if (errorTextId) {
              var txtEl = document.getElementById(errorTextId);
              if (txtEl) { txtEl.textContent = msg; }
            }
          }
        }
      })
      .catch(function() {
        if (errorElId) {
          var errEl = document.getElementById(errorElId);
          if (errEl) { errEl.style.display = 'flex'; }
        }
      })
      .finally(function() {
        if (btn) { btn.disabled = false; btn.innerHTML = 'Mandat anfragen →'; }
      });
  });
}

/* Wire up main form */
setupForm(
  document.getElementById('mainContactForm'),
  'mainSubmitBtn',
  null, null, null,
  '?success=1'
);

/* Wire up engagement modal form */
setupForm(
  document.getElementById('engForm'),
  'engSubmitBtn',
  'engSuccessMsg',
  'engErrorMsg',
  'engErrorText',
  null
);

/* ── Modal trigger ── */
(function() {
  var delay = <?= (int)($modal_delay * 1000) ?>;
  setTimeout(function() {
    if (typeof bootstrap !== 'undefined') {
      var modalEl = document.getElementById('engModal');
      if (modalEl) {
        var m = bootstrap.Modal.getOrCreateInstance(modalEl);
        m.show();
      }
    }
  }, delay);
})();
(function(){
  var f = document.getElementById('contactModalForm');
  if (!f) return;
  f.addEventListener('submit', function(e){
    e.preventDefault();
    if (!f.checkValidity()){ f.classList.add('was-validated'); return; }
    var btn = f.querySelector('[type=submit]');
    var orig = btn ? btn.innerHTML : '';
    if (btn){ btn.disabled=true; btn.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Wird geprüft...'; }
    var fd = new FormData(f); fd.append('_ajax','1'); fd.append('_source','contact_modal');
    fetch('../submit_lead.php',{method:'POST',body:fd})
      .then(function(r){return r.json();})
      .then(function(d){
        if(d.csrf_token) document.querySelectorAll('input[name="csrf_token"]').forEach(function(el){el.value=d.csrf_token;});
        var al=document.createElement('div');
        al.className='alert '+(d.success?'alert-success':'alert-danger')+' mt-3';
        al.innerHTML=d.success?'<i class="bi bi-check-circle me-2"></i><strong>Vielen Dank!</strong> Wir melden uns in 24h bei Ihnen.':'<i class="bi bi-exclamation-triangle me-2"></i>'+(d.message||'Fehler. Bitte erneut versuchen.');
        f.parentNode.insertBefore(al,f);
        if(d.success) f.style.display='none';
        else if(btn){btn.disabled=false;btn.innerHTML=orig;}
      })
      .catch(function(){if(btn){btn.disabled=false;btn.innerHTML=orig;}});
  });
})();
</script>

<!-- Visitor tracking -->
<script>
(function () {
  'use strict';
  var visitId = null, startTime = Date.now();
  var sp = new URLSearchParams(window.location.search);
  var fd = new FormData();
  fd.append('action', 'visit');
  fd.append('referrer', document.referrer || '');
  fd.append('landing_page', window.location.href.substring(0, 512));
  ['utm_source','utm_medium','utm_campaign','utm_content','utm_term','gclid'].forEach(function (k) {
    var v = sp.get(k);
    if (v) fd.append(k, v.substring(0, 200));
  });
  fetch('../track.php', { method: 'POST', body: fd })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      if (d && d.visit_id) {
        visitId = d.visit_id;
        window._visitId = visitId;
        document.querySelectorAll('[data-visit-id],[id$="VisitId"]').forEach(function (el) { el.value = visitId; });
      }
    })
    .catch(function () {});
  function sendTime() {
    if (!visitId) return;
    var e = Math.round((Date.now() - startTime) / 1000);
    var b = new Blob(['action=update&visit_id=' + encodeURIComponent(visitId) + '&time_on_site=' + encodeURIComponent(e)], { type: 'application/x-www-form-urlencoded' });
    if (navigator.sendBeacon) { navigator.sendBeacon('../track.php', b); }
    else { fetch('../track.php', { method: 'POST', body: b, keepalive: true }).catch(function () {}); }
  }
  window.addEventListener('pagehide', sendTime);
  window.addEventListener('beforeunload', sendTime);
  document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'hidden') { sendTime(); } });
  setInterval(sendTime, 30000);
})();
</script>
<!-- ===== CONTACT MODAL ===== -->
<div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
    <div class="modal-content border-0 shadow-xl" style="border-radius:16px;overflow:hidden;">
      <div class="modal-header text-white py-3 px-4" style="background:linear-gradient(135deg,#b45309,#92400e);border:none;">
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
                <li class="mb-3 d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#b45309;flex-shrink:0"></i><span><strong class="text-dark">87% Erfolgsquote</strong> – verifiziert über 2.400 Mandate</span></li>
                <li class="mb-3 d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#b45309;flex-shrink:0"></i><span><strong class="text-dark">€0 Vorauszahlung</strong> – Sie zahlen nur bei Erfolg</span></li>
                <li class="mb-3 d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#b45309;flex-shrink:0"></i><span><strong class="text-dark">72h Erstantwort</strong> – KI prüft Ihren Fall sofort</span></li>
                <li class="mb-3 d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#b45309;flex-shrink:0"></i><span><strong class="text-dark">40+ Länder</strong> – Internationales Expertennetzwerk</span></li>
                <li class="d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#b45309;flex-shrink:0"></i><span><strong class="text-dark">DSGVO-konform</strong> – Höchste Datensicherheit</span></li>
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
                      Ich stimme der <a href="#" style="color:#b45309">Datenschutzerklärung</a> zu und bin einverstanden, dass meine Daten zur Fallbearbeitung verwendet werden. *
                    </label>
                    <div class="invalid-feedback">Bitte Datenschutz bestätigen.</div>
                  </div>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn w-100 fw-bold py-3" style="background:#b45309;color:#fff;font-size:1rem;border:none;border-radius:10px;">
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
