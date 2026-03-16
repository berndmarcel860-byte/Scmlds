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
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="description" content="VerlustRückholung – KI-gestützte Kapitalrückholung bei Anlagebetrug. Kostenlose Erstanalyse. Keine Vorauszahlung. 87% Erfolgsquote."/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet"/>
  <style>
    :root {
      --primary:   #064e3b;
      --accent:    #059669;
      --accent-d:  #047857;
      --text:      #1a202c;
      --muted:     #6c757d;
      --bg-light:  #f0fdf4;
      --border:    #d1fae5;
    }
    *, *::before, *::after { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body { font-family: 'Inter', sans-serif; color: var(--text); background: #fff; margin: 0; }

    /* ── NAVBAR ── */
    .navbar { background: #0a0a0a !important; padding: 0.75rem 0; transition: box-shadow .3s; }
    .navbar.scrolled { box-shadow: 0 2px 20px rgba(0,0,0,.5); }
    .navbar-brand { font-weight: 800; font-size: 1.4rem; color: #fff !important; letter-spacing: -.5px; }
    .navbar-brand span { color: var(--accent); }
    .nav-link { color: rgba(255,255,255,.8) !important; font-weight: 500; font-size: .9rem; transition: color .2s; }
    .nav-link:hover, .nav-link.active { color: var(--accent) !important; }
    .navbar-toggler { border-color: rgba(255,255,255,.3); }
    .navbar-toggler-icon { filter: invert(1); }
    .btn-nav-cta { background: var(--accent); color: #fff !important; border-radius: 6px; padding: .4rem 1rem !important; font-weight: 600; }
    .btn-nav-cta:hover { background: var(--accent-d) !important; color: #fff !important; }

    /* ── HERO ── */
    #hero {
      position: relative;
      min-height: 100vh;
      background: linear-gradient(135deg, #021c12 0%, #064e3b 50%, #0d2b5e 100%);
      display: flex; align-items: center; overflow: hidden;
    }
    #heroCanvas3 { position: absolute; inset: 0; width: 100%; height: 100%; opacity: .55; }
    .hero-content { position: relative; z-index: 2; padding: 7rem 0 5rem; }
    .hero-badge {
      display: inline-flex; align-items: center; gap: .5rem;
      background: rgba(5,150,105,.2); border: 1px solid rgba(5,150,105,.4);
      color: #6ee7b7; border-radius: 50px; padding: .35rem 1rem;
      font-size: .8rem; font-weight: 600; margin-bottom: 1.5rem; letter-spacing: .5px;
    }
    .hero-title { font-size: clamp(2rem, 5vw, 3.6rem); font-weight: 900; color: #fff; line-height: 1.1; margin-bottom: 1.25rem; }
    .hero-title .em { color: #34d399; }
    .hero-sub { font-size: 1.1rem; color: rgba(255,255,255,.75); max-width: 580px; margin-bottom: 2rem; line-height: 1.7; }
    .btn-hero {
      background: linear-gradient(135deg, var(--accent), var(--accent-d));
      color: #fff; border: none; border-radius: 8px;
      padding: .85rem 2.2rem; font-size: 1.05rem; font-weight: 700;
      text-decoration: none; display: inline-flex; align-items: center; gap: .5rem;
      box-shadow: 0 4px 24px rgba(5,150,105,.4); transition: transform .2s, box-shadow .2s;
    }
    .btn-hero:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(5,150,105,.55); color: #fff; }
    .hero-pills { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1.75rem; }
    .hero-pill {
      background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
      color: rgba(255,255,255,.85); border-radius: 50px; padding: .3rem .85rem;
      font-size: .78rem; font-weight: 500; display: flex; align-items: center; gap: .4rem;
    }
    .hero-pill i { color: #34d399; }

    /* ── STATS BAND ── */
    #stats-band { background: #fff; border-bottom: 1px solid #e8f5e9; padding: 2.5rem 0; }
    .stat-item { text-align: center; }
    .stat-num { font-size: 2.4rem; font-weight: 900; color: var(--accent); line-height: 1; }
    .stat-label { font-size: .85rem; color: var(--muted); margin-top: .35rem; }

    /* ── TRUST BANNER ── */
    #trust-banner { background: var(--bg-light); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 1rem 0; }
    .trust-item { display: flex; align-items: center; gap: .5rem; font-size: .85rem; font-weight: 600; color: var(--primary); }
    .trust-item i { color: var(--accent); font-size: 1rem; }

    /* ── NEWS TICKER ── */
    #news-ticker { background: var(--primary); padding: .6rem 0; overflow: hidden; }
    .ticker-wrap { display: flex; align-items: center; gap: 0; white-space: nowrap; }
    .ticker-label { background: var(--accent); color: #fff; font-size: .72rem; font-weight: 700; padding: .2rem .7rem; border-radius: 4px; margin-right: 1.2rem; flex-shrink: 0; letter-spacing: .5px; }
    .ticker-track { display: inline-flex; gap: 3rem; animation: ticker 30s linear infinite; }
    .ticker-item { color: rgba(255,255,255,.85); font-size: .82rem; }
    .ticker-item .dot { color: var(--accent); margin-right: .5rem; }
    @keyframes ticker { from { transform: translateX(0); } to { transform: translateX(-50%); } }

    /* ── AI SECTION ── */
    #ai-section { background: var(--bg-light); padding: 5rem 0; }
    .ai-title { font-size: 1.9rem; font-weight: 800; color: var(--primary); margin-bottom: .5rem; }
    .ai-sub { color: var(--muted); margin-bottom: 2rem; }
    .ai-network { position: relative; width: 100%; height: 340px; }
    .ai-node {
      position: absolute; width: 18px; height: 18px; border-radius: 50%;
      background: var(--accent); transform: translate(-50%, -50%);
      box-shadow: 0 0 0 0 rgba(5,150,105,.4);
    }
    .ai-node.active { animation: ripple 1.2s ease-out; }
    @keyframes ripple {
      0%   { box-shadow: 0 0 0 0 rgba(5,150,105,.6); }
      70%  { box-shadow: 0 0 0 18px rgba(5,150,105,0); }
      100% { box-shadow: 0 0 0 0 rgba(5,150,105,0); }
    }
    .ai-counter { font-size: 2.2rem; font-weight: 900; color: var(--accent); margin-top: 1.5rem; }
    .ai-counter-label { font-size: .9rem; color: var(--muted); }

    /* ── HOW IT WORKS ── */
    #how { padding: 5rem 0; background: #fff; }
    .how-step { display: flex; gap: 1.5rem; align-items: flex-start; margin-bottom: 2rem; }
    .step-circle {
      width: 52px; height: 52px; border-radius: 50%;
      background: linear-gradient(135deg, var(--accent), var(--accent-d));
      color: #fff; font-size: 1.3rem; font-weight: 900;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .step-title { font-weight: 700; font-size: 1.05rem; color: var(--primary); margin-bottom: .3rem; }
    .step-desc { font-size: .9rem; color: var(--muted); line-height: 1.6; }

    /* ── LEISTUNGEN ── */
    #leistungen { padding: 5rem 0; background: var(--bg-light); }
    .section-title { font-size: 2rem; font-weight: 800; color: var(--primary); margin-bottom: .5rem; }
    .section-sub { color: var(--muted); margin-bottom: 3rem; }
    .leist-card {
      background: #fff; border-radius: 10px; padding: 1.75rem 1.5rem;
      border-left: 4px solid var(--accent);
      box-shadow: 0 2px 12px rgba(0,0,0,.05);
      transition: background .2s, transform .2s, box-shadow .2s;
      height: 100%;
    }
    .leist-card:hover { background: var(--bg-light); transform: translateY(-3px); box-shadow: 0 6px 24px rgba(5,150,105,.12); }
    .leist-icon { width: 48px; height: 48px; border-radius: 10px; background: var(--bg-light); display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; }
    .leist-icon i { font-size: 1.4rem; color: var(--accent); }
    .leist-title { font-weight: 700; font-size: 1rem; color: var(--primary); margin-bottom: .4rem; }
    .leist-desc { font-size: .87rem; color: var(--muted); line-height: 1.6; }

    /* ── FRAUD TYPES ── */
    #fraud { padding: 5rem 0; background: #fff; }
    .fraud-card {
      background: #fff; border: 1px solid var(--border); border-radius: 10px;
      padding: 1.5rem; height: 100%;
      box-shadow: 0 1px 8px rgba(0,0,0,.04); transition: box-shadow .2s;
    }
    .fraud-card:hover { box-shadow: 0 4px 20px rgba(5,150,105,.1); }
    .fraud-badge { background: #dcfce7; color: #14532d; font-size: .72rem; font-weight: 700; padding: .2rem .65rem; border-radius: 50px; display: inline-block; margin-bottom: .75rem; }
    .fraud-title { font-weight: 700; color: var(--primary); margin-bottom: .4rem; }
    .fraud-desc { font-size: .87rem; color: var(--muted); line-height: 1.6; }

    /* ── LIVE TICKER ── */
    #live-ticker { background: #0a0a0a; padding: 3.5rem 0; }
    .live-title { color: #fff; font-weight: 800; font-size: 1.4rem; margin-bottom: 1.5rem; }
    .live-title span { color: var(--accent); }
    .ticker-feed { max-height: 260px; overflow: hidden; position: relative; }
    .ticker-feed::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 60px; background: linear-gradient(transparent, #0a0a0a); }
    .tick-item { display: flex; align-items: center; gap: .75rem; padding: .6rem 0; border-bottom: 1px solid rgba(255,255,255,.06); }
    .tick-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); flex-shrink: 0; }
    .tick-text { font-size: .85rem; color: rgba(255,255,255,.8); }
    .tick-amount { font-weight: 700; color: var(--accent); }
    .tick-time { font-size: .75rem; color: rgba(255,255,255,.4); margin-left: auto; flex-shrink: 0; }
    .live-pulse { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #ef4444; animation: pulse 1.2s ease-in-out infinite; margin-right: .4rem; }
    @keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:.3;} }

    /* ── WHY US ── */
    #why { padding: 5rem 0; background: var(--bg-light); }
    .why-card { background: #fff; border-radius: 10px; padding: 1.5rem; height: 100%; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
    .why-icon { width: 44px; height: 44px; border-radius: 10px; background: var(--bg-light); display: flex; align-items: center; justify-content: center; margin-bottom: .85rem; }
    .why-icon i { color: var(--accent); font-size: 1.25rem; }
    .why-title { font-weight: 700; font-size: .95rem; color: var(--primary); margin-bottom: .3rem; }
    .why-desc { font-size: .85rem; color: var(--muted); line-height: 1.6; }

    /* ── TESTIMONIALS ── */
    #testimonials { padding: 5rem 0; background: #fff; }
    .testi-card { background: #fff; border: 1px solid var(--border); border-radius: 10px; padding: 1.75rem; height: 100%; box-shadow: 0 2px 10px rgba(0,0,0,.04); }
    .testi-stars { color: #f59e0b; font-size: .95rem; margin-bottom: .75rem; }
    .testi-text { font-size: .9rem; color: var(--text); line-height: 1.7; font-style: italic; margin-bottom: 1rem; }
    .testi-author { display: flex; align-items: center; gap: .75rem; }
    .testi-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--accent); color: #fff; font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: .95rem; }
    .testi-name { font-weight: 700; font-size: .9rem; color: var(--primary); }
    .testi-meta { font-size: .78rem; color: var(--muted); }

    /* ── STATISTICS SECTION ── */
    #statistics { background: var(--primary); padding: 5rem 0; }
    .stat-big-num { font-size: 3rem; font-weight: 900; color: #34d399; line-height: 1; }
    .stat-big-label { font-size: .9rem; color: rgba(255,255,255,.75); margin-top: .4rem; }

    /* ── FORM ── */
    #kontakt { padding: 5rem 0; background: var(--bg-light); }
    .form-wrap { background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 8px 40px rgba(0,0,0,.1); }
    .form-header { background: linear-gradient(135deg, var(--accent), var(--accent-d)); padding: 2rem 2rem 1.75rem; }
    .form-header h2 { color: #fff; font-size: 1.6rem; font-weight: 800; margin: 0 0 .4rem; }
    .form-header p { color: rgba(255,255,255,.85); font-size: .9rem; margin: 0; }
    .form-body { padding: 2rem; }
    .form-label { font-size: .85rem; font-weight: 600; color: var(--primary); }
    .form-control, .form-select {
      border: 1px solid #d1d5db; border-radius: 7px; padding: .65rem .9rem;
      font-size: .9rem; transition: border-color .2s, box-shadow .2s;
    }
    .form-control:focus, .form-select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(5,150,105,.12); outline: none; }
    .btn-submit {
      background: linear-gradient(135deg, var(--accent), var(--accent-d));
      color: #fff; border: none; border-radius: 8px; padding: .85rem 2rem;
      font-weight: 700; font-size: 1rem; width: 100%; cursor: pointer;
      transition: opacity .2s, transform .2s; display: flex; align-items: center; justify-content: center; gap: .5rem;
    }
    .btn-submit:hover { opacity: .92; transform: translateY(-1px); }
    .btn-submit:disabled { opacity: .6; cursor: not-allowed; }
    .alert-success-box { background: #dcfce7; border: 1px solid #86efac; color: #14532d; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1rem; }
    .alert-error-box { background: #fee2e2; border: 1px solid #fca5a5; color: #7f1d1d; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1rem; }
    .spinner-sm { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; display: none; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── TRUST BADGES ── */
    #trust-badges { padding: 3rem 0; background: #fff; border-top: 1px solid #f0f0f0; }
    .badge-item { display: flex; flex-direction: column; align-items: center; gap: .4rem; }
    .badge-icon { width: 54px; height: 54px; border-radius: 50%; background: var(--bg-light); display: flex; align-items: center; justify-content: center; }
    .badge-icon i { font-size: 1.5rem; color: var(--accent); }
    .badge-label { font-size: .78rem; font-weight: 600; color: var(--muted); text-align: center; }

    /* ── FAQ ── */
    #faq { padding: 5rem 0; background: var(--bg-light); }
    .accordion-button { font-weight: 600; color: var(--primary); background: #fff; }
    .accordion-button:not(.collapsed) { color: var(--accent); background: #f0fdf4; box-shadow: none; }
    .accordion-button::after { filter: none; }
    .accordion-button:not(.collapsed)::after { filter: invert(33%) sepia(77%) saturate(500%) hue-rotate(120deg); }
    .accordion-item { border: 1px solid var(--border); border-radius: 8px !important; overflow: hidden; margin-bottom: .75rem; }
    .accordion-body { font-size: .9rem; color: var(--muted); line-height: 1.7; }

    /* ── FOOTER ── */
    footer { background: #021c12; padding: 3.5rem 0 1.5rem; }
    .footer-brand { font-size: 1.4rem; font-weight: 800; color: #fff; margin-bottom: .75rem; }
    .footer-brand span { color: var(--accent); }
    .footer-desc { font-size: .85rem; color: rgba(255,255,255,.55); line-height: 1.7; }
    .footer-heading { font-weight: 700; color: rgba(255,255,255,.9); font-size: .9rem; margin-bottom: 1rem; }
    .footer-links { list-style: none; padding: 0; margin: 0; }
    .footer-links li { margin-bottom: .5rem; }
    .footer-links a { color: rgba(255,255,255,.55); font-size: .85rem; text-decoration: none; transition: color .2s; }
    .footer-links a:hover { color: var(--accent); }
    .footer-divider { border-color: rgba(255,255,255,.08); margin: 2rem 0 1.25rem; }
    .footer-copy { font-size: .8rem; color: rgba(255,255,255,.35); }

    /* ── MODAL ── */
    #engModal .modal-header { background: linear-gradient(135deg, var(--accent), var(--accent-d)); color: #fff; border: none; }
    #engModal .modal-title { font-weight: 800; font-size: 1.1rem; }
    #engModal .btn-close { filter: invert(1); }
    #engModal .btn-submit { margin-top: .5rem; }

    /* ── UTILITIES ── */
    .text-accent { color: var(--accent) !important; }
    .bg-primary-d { background: var(--primary) !important; }
    @media (max-width: 768px) {
      .hero-title { font-size: 2rem; }
      .stat-big-num { font-size: 2.2rem; }
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
  <div class="container">
    <a class="navbar-brand" href="#hero">Verlust<span>Rückholung</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="#how">Ablauf</a></li>
        <li class="nav-item"><a class="nav-link" href="#leistungen">Leistungen</a></li>
        <li class="nav-item"><a class="nav-link" href="#ai-section">KI-Analyse</a></li>
        <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
        <li class="nav-item ms-lg-2">
          <a class="nav-link btn-nav-cta" href="#" data-bs-toggle="modal" data-bs-target="#contactModal">Kostenlose Analyse</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- HERO -->
<section id="hero">
  <canvas id="heroCanvas3"></canvas>
  <div class="container hero-content">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <div class="hero-badge"><i class="bi bi-cpu-fill"></i> KI-gestützte Rückholungsanalyse</div>
        <h1 class="hero-title">
          Ihr <span class="em">verlorenes Kapital.</span><br>
          Unsere KI findet<br>den Weg zurück.
        </h1>
        <p class="hero-sub">Transparente Analyse. Internationale Reichweite. Keine Vorauszahlung. 87% Erfolgsquote.</p>
        <a href="#" class="btn-hero" data-bs-toggle="modal" data-bs-target="#contactModal">Kostenlose Analyse starten <i class="bi bi-arrow-right"></i></a>
        <div class="hero-pills">
          <span class="hero-pill"><i class="bi bi-shield-check"></i> Keine Vorauszahlung</span>
          <span class="hero-pill"><i class="bi bi-globe2"></i> 40+ Länder</span>
          <span class="hero-pill"><i class="bi bi-clock"></i> Antwort in 24h</span>
          <span class="hero-pill"><i class="bi bi-lock"></i> DSGVO-konform</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- STATS BAND -->
<section id="stats-band">
  <div class="container">
    <div class="row g-4 justify-content-center text-center">
      <div class="col-6 col-md-3"><div class="stat-item"><div class="stat-num" data-target="87">0</div><div class="stat-label">% Erfolgsquote</div></div></div>
      <div class="col-6 col-md-3"><div class="stat-item"><div class="stat-num" data-target="2400">0</div><div class="stat-label">Fälle abgeschlossen</div></div></div>
      <div class="col-6 col-md-3"><div class="stat-item"><div class="stat-num" data-prefix="€" data-target="48" data-suffix="M+">0</div><div class="stat-label">Kapital zurückgeholt</div></div></div>
      <div class="col-6 col-md-3"><div class="stat-item"><div class="stat-num" data-target="40" data-suffix="+">0</div><div class="stat-label">Länder abgedeckt</div></div></div>
    </div>
  </div>
</section>

<!-- TRUST BANNER -->
<div id="trust-banner">
  <div class="container">
    <div class="row g-3 justify-content-center">
      <div class="col-auto"><div class="trust-item"><i class="bi bi-patch-check-fill"></i> Zertifizierte Analysten</div></div>
      <div class="col-auto"><div class="trust-item"><i class="bi bi-shield-lock-fill"></i> DSGVO-konform</div></div>
      <div class="col-auto"><div class="trust-item"><i class="bi bi-award-fill"></i> TÜV-geprüfte Prozesse</div></div>
      <div class="col-auto"><div class="trust-item"><i class="bi bi-bank2"></i> Bankenkonform</div></div>
      <div class="col-auto"><div class="trust-item"><i class="bi bi-person-check-fill"></i> Kostenlose Erstberatung</div></div>
    </div>
  </div>
</div>

<!-- NEWS TICKER -->
<div id="news-ticker">
  <div class="container-fluid px-3">
    <div class="ticker-wrap">
      <span class="ticker-label">AKTUELL</span>
      <div class="ticker-track" id="tickerTrack">
        <span class="ticker-item"><span class="dot">●</span> Neue Kooperation mit europäischen Finanzaufsichtsbehörden</span>
        <span class="ticker-item"><span class="dot">●</span> 320 neue Fälle erfolgreich abgeschlossen – Q4 2024</span>
        <span class="ticker-item"><span class="dot">●</span> KI-System erkennt 127 neue Betrugsmuster pro Woche</span>
        <span class="ticker-item"><span class="dot">●</span> Partnerschaft mit Blockchain-Forensik-Institut erweitert</span>
        <span class="ticker-item"><span class="dot">●</span> Durchschnittliche Bearbeitungszeit auf 6 Wochen gesenkt</span>
        <span class="ticker-item"><span class="dot">●</span> Neue Kooperation mit europäischen Finanzaufsichtsbehörden</span>
        <span class="ticker-item"><span class="dot">●</span> 320 neue Fälle erfolgreich abgeschlossen – Q4 2024</span>
        <span class="ticker-item"><span class="dot">●</span> KI-System erkennt 127 neue Betrugsmuster pro Woche</span>
        <span class="ticker-item"><span class="dot">●</span> Partnerschaft mit Blockchain-Forensik-Institut erweitert</span>
        <span class="ticker-item"><span class="dot">●</span> Durchschnittliche Bearbeitungszeit auf 6 Wochen gesenkt</span>
      </div>
    </div>
  </div>
</div>

<!-- AI SECTION -->
<section id="ai-section" data-aos="fade-up">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <h2 class="ai-title">KI-Transaktions-Netzwerk</h2>
        <p class="ai-sub">Unser neuronales Netzwerk analysiert Transaktionsmuster in Echtzeit und identifiziert verdächtige Aktivitäten mit beispielloser Präzision.</p>
        <div class="ai-network" id="aiNetwork">
          <!-- nodes injected by JS -->
        </div>
        <div class="ai-counter"><span id="aiCounter">127</span> Muster analysiert</div>
        <div class="ai-counter-label">In den letzten 24 Stunden</div>
      </div>
      <div class="col-lg-6">
        <div class="row g-3">
          <div class="col-12" data-aos="fade-left" data-aos-delay="100">
            <div class="leist-card">
              <div class="leist-icon"><i class="bi bi-diagram-3-fill"></i></div>
              <div class="leist-title">Netzwerk-Analyse</div>
              <div class="leist-desc">Erkennung versteckter Verbindungen zwischen Betrügernetzwerken über Blockchain und Fiat-Transaktionen hinweg.</div>
            </div>
          </div>
          <div class="col-12" data-aos="fade-left" data-aos-delay="200">
            <div class="leist-card">
              <div class="leist-icon"><i class="bi bi-graph-up-arrow"></i></div>
              <div class="leist-title">Muster-Erkennung</div>
              <div class="leist-desc">Machine-Learning-Modelle, trainiert auf über 50.000 Betrugsfällen, erkennen selbst ausgeklügelte Schemata.</div>
            </div>
          </div>
          <div class="col-12" data-aos="fade-left" data-aos-delay="300">
            <div class="leist-card">
              <div class="leist-icon"><i class="bi bi-shield-fill-check"></i></div>
              <div class="leist-title">Echtzeit-Monitoring</div>
              <div class="leist-desc">24/7-Überwachung relevanter Wallets und Konten zur Sicherung von Beweismitteln für juristische Schritte.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section id="how">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <h2 class="section-title">So funktioniert es</h2>
      <p class="section-sub">In drei einfachen Schritten zur Kapitalrückholung</p>
    </div>
    <div class="row g-4 justify-content-center">
      <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
        <div class="how-step">
          <div class="step-circle">1</div>
          <div>
            <div class="step-title">Kostenlose Erstanalyse</div>
            <div class="step-desc">Schildern Sie Ihren Fall über unser sicheres Formular. Unsere KI analysiert innerhalb von 24 Stunden Ihre Situation und schätzt die Erfolgsaussichten ein.</div>
          </div>
        </div>
      </div>
      <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
        <div class="how-step">
          <div class="step-circle">2</div>
          <div>
            <div class="step-title">Strategieentwicklung</div>
            <div class="step-desc">Unsere Experten entwickeln eine maßgeschneiderte Rückholungsstrategie – von Blockchain-Analyse bis zu rechtlichen Schritten in relevanten Jurisdiktionen.</div>
          </div>
        </div>
      </div>
      <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300">
        <div class="how-step">
          <div class="step-circle">3</div>
          <div>
            <div class="step-title">Aktive Rückholung</div>
            <div class="step-desc">Wir agieren in Ihrem Namen bei Behörden, Banken und internationalen Partnern. Kein Erfolg – keine Gebühr. Vollständige Transparenz während des gesamten Prozesses.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- LEISTUNGEN -->
<section id="leistungen">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <h2 class="section-title">Unsere Analyse-Methoden</h2>
      <p class="section-sub">Modernste Technologie kombiniert mit rechtlicher Expertise</p>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
        <div class="leist-card">
          <div class="leist-icon"><i class="bi bi-currency-bitcoin"></i></div>
          <div class="leist-title">Blockchain-Forensik</div>
          <div class="leist-desc">Lückenlose Nachverfolgung von Kryptowährungstransaktionen über alle gängigen Blockchains. Identifizierung von Mixer- und Tumbler-Aktivitäten.</div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="150">
        <div class="leist-card">
          <div class="leist-icon"><i class="bi bi-search"></i></div>
          <div class="leist-title">OSINT-Recherche</div>
          <div class="leist-desc">Open-Source-Intelligence-Methoden zur Identifizierung von Täternetzwerken, Unternehmensstrukturen und versteckten Vermögenswerten weltweit.</div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
        <div class="leist-card">
          <div class="leist-icon"><i class="bi bi-bank"></i></div>
          <div class="leist-title">Banken-Kooperation</div>
          <div class="leist-desc">Direkte Zusammenarbeit mit Compliance-Abteilungen von Banken und Zahlungsdienstleistern zur Einfrierung verdächtiger Konten.</div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="250">
        <div class="leist-card">
          <div class="leist-icon"><i class="bi bi-globe2"></i></div>
          <div class="leist-title">Internationale Rechtshilfe</div>
          <div class="leist-desc">Netzwerk von Rechtsspezialisten in 40+ Ländern für grenzüberschreitende Rückholungsmaßnahmen und Behördenkooperation.</div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
        <div class="leist-card">
          <div class="leist-icon"><i class="bi bi-file-earmark-text"></i></div>
          <div class="leist-title">Beweissicherung</div>
          <div class="leist-desc">Gerichtsverwertbare Dokumentation aller Transaktionen und Kommunikationsnachweise für nationale und internationale Strafverfolgung.</div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="350">
        <div class="leist-card">
          <div class="leist-icon"><i class="bi bi-headset"></i></div>
          <div class="leist-title">Persönliche Betreuung</div>
          <div class="leist-desc">Dedizierter Fallmanager als Ihr persönlicher Ansprechpartner. Regelmäßige Updates und vollständige Transparenz über jeden Fortschritt.</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FRAUD TYPES -->
<section id="fraud">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <h2 class="section-title">Erkannte Betrugsmuster</h2>
      <p class="section-sub">Unsere KI erkennt alle gängigen Betrugsformen zuverlässig</p>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
        <div class="fraud-card">
          <span class="fraud-badge">Erkannt ✓</span>
          <div class="fraud-title">Fake-Broker-Betrug</div>
          <div class="fraud-desc">Lizenzlose Handelsplattformen, die Gewinne vortäuschen und Einzahlungen veruntreuen. Häufig mit falschen Regulierungsangaben.</div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="150">
        <div class="fraud-card">
          <span class="fraud-badge">Erkannt ✓</span>
          <div class="fraud-title">Krypto-Scam</div>
          <div class="fraud-desc">Rug-Pulls, Ponzi-Schemata, gefälschte DEX-Plattformen und Romance-Scams mit Kryptowährungs-Investitionen.</div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
        <div class="fraud-card">
          <span class="fraud-badge">Erkannt ✓</span>
          <div class="fraud-title">Phishing & Identitätsbetrug</div>
          <div class="fraud-desc">Gefälschte Bank-Webseiten, SIM-Swapping und Social-Engineering-Angriffe zur Kontenübernahme.</div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="250">
        <div class="fraud-card">
          <span class="fraud-badge">Erkannt ✓</span>
          <div class="fraud-title">Investment-Fraud</div>
          <div class="fraud-desc">Unregulierte Anlageprodukte, gefälschte Fonds und Schneeballsysteme mit versprochenen Überrenditen.</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- LIVE RECOVERY TICKER -->
<section id="live-ticker">
  <div class="container">
    <h3 class="live-title"><span class="live-pulse"></span> Live <span>Rückholungs-Feed</span></h3>
    <div class="ticker-feed" id="liveFeed">
      <div class="tick-item"><span class="tick-dot"></span><span class="tick-text">Kunde aus <strong>München</strong> – <span class="tick-amount">€ 47.800</span> erfolgreich zurückgeholt</span><span class="tick-time">vor 12 Min</span></div>
      <div class="tick-item"><span class="tick-dot"></span><span class="tick-text">Kunde aus <strong>Wien</strong> – <span class="tick-amount">€ 112.500</span> Krypto-Betrug abgeschlossen</span><span class="tick-time">vor 28 Min</span></div>
      <div class="tick-item"><span class="tick-dot"></span><span class="tick-text">Kunde aus <strong>Zürich</strong> – <span class="tick-amount">€ 83.200</span> Broker-Fraud zurückgeholt</span><span class="tick-time">vor 45 Min</span></div>
      <div class="tick-item"><span class="tick-dot"></span><span class="tick-text">Kunde aus <strong>Hamburg</strong> – <span class="tick-amount">€ 29.000</span> Phishing-Schaden kompensiert</span><span class="tick-time">vor 1 Std</span></div>
      <div class="tick-item"><span class="tick-dot"></span><span class="tick-text">Kunde aus <strong>Berlin</strong> – <span class="tick-amount">€ 215.000</span> Investment-Fraud abgeschlossen</span><span class="tick-time">vor 2 Std</span></div>
      <div class="tick-item"><span class="tick-dot"></span><span class="tick-text">Kunde aus <strong>Frankfurt</strong> – <span class="tick-amount">€ 64.300</span> Fake-Broker zurückgeholt</span><span class="tick-time">vor 3 Std</span></div>
    </div>
  </div>
</section>

<!-- WHY US -->
<section id="why">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <h2 class="section-title">Warum VerlustRückholung?</h2>
      <p class="section-sub">Unsere Stärken auf einen Blick</p>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
        <div class="why-card">
          <div class="why-icon"><i class="bi bi-cpu"></i></div>
          <div class="why-title">Modernste KI-Technologie</div>
          <div class="why-desc">Neuronale Netzwerke analysieren Tausende Datenpunkte gleichzeitig für maximale Genauigkeit.</div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="150">
        <div class="why-card">
          <div class="why-icon"><i class="bi bi-cash-coin"></i></div>
          <div class="why-title">Kein Erfolg – keine Gebühr</div>
          <div class="why-desc">Wir arbeiten ausschließlich auf Erfolgsbasis. Sie zahlen erst, wenn wir Ihr Kapital zurückgeholt haben.</div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
        <div class="why-card">
          <div class="why-icon"><i class="bi bi-globe-americas"></i></div>
          <div class="why-title">Globales Netzwerk</div>
          <div class="why-desc">Partnerschaften mit Behörden, Anwälten und Forensik-Experten in über 40 Ländern.</div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="250">
        <div class="why-card">
          <div class="why-icon"><i class="bi bi-eye"></i></div>
          <div class="why-title">Vollständige Transparenz</div>
          <div class="why-desc">Echtzeit-Dashboard mit Ihrem Fallstatus, Fortschrittsberichten und direkter Kommunikation.</div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
        <div class="why-card">
          <div class="why-icon"><i class="bi bi-lightning-charge"></i></div>
          <div class="why-title">Schnelle Reaktionszeit</div>
          <div class="why-desc">Erstanalyse innerhalb von 24 Stunden. Bei dringenden Fällen sofortige Eskalation möglich.</div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="350">
        <div class="why-card">
          <div class="why-icon"><i class="bi bi-lock-fill"></i></div>
          <div class="why-title">Datenschutz garantiert</div>
          <div class="why-desc">Höchste Sicherheitsstandards, Ende-zu-Ende-Verschlüsselung und vollständige DSGVO-Konformität.</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section id="testimonials">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <h2 class="section-title">Was unsere Kunden sagen</h2>
      <p class="section-sub">Echte Erfahrungen – verifizierte Fälle</p>
    </div>
    <div class="row g-4">
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
        <div class="testi-card">
          <div class="testi-stars">★★★★★</div>
          <div class="testi-text">„Nach dem Verlust von über 80.000 € bei einem Fake-Broker dachte ich, das Geld sei weg. VerlustRückholung hat innerhalb von 8 Wochen einen Großteil zurückgeholt. Unglaublich professionell."</div>
          <div class="testi-author">
            <div class="testi-avatar">MK</div>
            <div><div class="testi-name">Michael K.</div><div class="testi-meta">München · Broker-Betrug · €67.000 zurückgeholt</div></div>
          </div>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
        <div class="testi-card">
          <div class="testi-stars">★★★★★</div>
          <div class="testi-text">„Die KI-Analyse hat Verbindungen aufgedeckt, die ich nie selbst hätte finden können. Das Team war stets erreichbar und hat mich durch jeden Schritt begleitet. Volle Empfehlung!"</div>
          <div class="testi-author">
            <div class="testi-avatar">SH</div>
            <div><div class="testi-name">Sandra H.</div><div class="testi-meta">Wien · Krypto-Scam · €43.200 zurückgeholt</div></div>
          </div>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
        <div class="testi-card">
          <div class="testi-stars">★★★★★</div>
          <div class="testi-text">„Ich war skeptisch, aber das Konzept 'kein Erfolg, keine Gebühr' hat mich überzeugt. 14 Wochen später hatte ich 115.000 € zurück. Absolut empfehlenswert für jeden Betrugsopfer."</div>
          <div class="testi-author">
            <div class="testi-avatar">TW</div>
            <div><div class="testi-name">Thomas W.</div><div class="testi-meta">Zürich · Investment-Fraud · €115.000 zurückgeholt</div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- STATISTICS SECTION -->
<section id="statistics">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <h2 class="section-title" style="color:#fff;">Unsere Erfolge in Zahlen</h2>
      <p style="color:rgba(255,255,255,.7);">Bewiesene Resultate seit 2016</p>
    </div>
    <div class="row g-4 text-center">
      <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
        <div class="stat-big-num count-up" data-target="2400">0</div>
        <div class="stat-big-label">Erfolgreich abgeschlossene Fälle</div>
      </div>
      <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
        <div class="stat-big-num count-up" data-prefix="€" data-target="48" data-suffix="M+">€0M+</div>
        <div class="stat-big-label">Kapital zurückgeholt</div>
      </div>
      <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
        <div class="stat-big-num count-up" data-target="87" data-suffix="%">0%</div>
        <div class="stat-big-label">Erfolgsquote (verifiziert)</div>
      </div>
      <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="400">
        <div class="stat-big-num count-up" data-target="40" data-suffix="+">0+</div>
        <div class="stat-big-label">Länder und Jurisdiktionen</div>
      </div>
    </div>
  </div>
</section>

<!-- MAIN CONTACT FORM -->
<section id="kontakt">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8" data-aos="fade-up">
        <?php if ($success): ?>
        <div class="alert-success-box text-center mb-4">
          <i class="bi bi-check-circle-fill me-2"></i>
          <strong>Ihre Anfrage wurde erfolgreich übermittelt!</strong> Wir melden uns innerhalb von 24 Stunden bei Ihnen.
        </div>
        <?php elseif ($error): ?>
        <div class="alert-error-box mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error ?></div>
        <?php endif; ?>

        <div class="form-wrap">
          <div class="form-header">
            <h2><i class="bi bi-cpu me-2"></i>Kostenlose KI-Erstanalyse</h2>
            <p>Schildern Sie Ihren Fall – wir analysieren kostenlos & unverbindlich innerhalb von 24 Stunden.</p>
          </div>
          <div class="form-body">
            <form id="mainForm" novalidate>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>"/>
              <input type="hidden" name="visit_id" value="<?= htmlspecialchars('', ENT_QUOTES, 'UTF-8') ?>"/>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label" for="first_name">Vorname <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Max" required/>
                  <div class="invalid-feedback">Bitte geben Sie Ihren Vornamen an.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="last_name">Nachname <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Mustermann" required/>
                  <div class="invalid-feedback">Bitte geben Sie Ihren Nachnamen an.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="email">E-Mail-Adresse <span class="text-danger">*</span></label>
                  <input type="email" class="form-control" id="email" name="email" placeholder="max@beispiel.de" required/>
                  <div class="invalid-feedback">Bitte geben Sie eine gültige E-Mail-Adresse an.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="phone">Telefonnummer</label>
                  <input type="tel" class="form-control" id="phone" name="phone" placeholder="+49 ..."/>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="country">Land <span class="text-danger">*</span></label>
                  <select class="form-select" id="country" name="country" required>
                    <option value="" disabled selected>Bitte wählen…</option>
                    <option value="DE">Deutschland</option>
                    <option value="AT">Österreich</option>
                    <option value="CH">Schweiz</option>
                    <option value="LU">Luxemburg</option>
                    <option value="LI">Liechtenstein</option>
                    <option value="OTHER">Sonstige</option>
                  </select>
                  <div class="invalid-feedback">Bitte wählen Sie Ihr Land aus.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="amount_lost">Verlorener Betrag (€) <span class="text-danger">*</span></label>
                  <input type="number" class="form-control" id="amount_lost" name="amount_lost" placeholder="z. B. 50000" min="0" required/>
                  <div class="invalid-feedback">Bitte geben Sie den verlorenen Betrag an.</div>
                </div>
                <div class="col-12">
                  <label class="form-label" for="platform_category">Art des Betrugs <span class="text-danger">*</span></label>
                  <select class="form-select" id="platform_category" name="platform_category" required>
                    <option value="" disabled selected>Bitte wählen…</option>
                    <option value="fake_broker">Fake-Broker / Handelsplattform</option>
                    <option value="crypto_scam">Kryptowährungs-Betrug</option>
                    <option value="investment_fraud">Investment-/Anlagebetrug</option>
                    <option value="phishing">Phishing / Kontenübernahme</option>
                    <option value="romance_scam">Romance Scam</option>
                    <option value="forex">Forex / CFD Betrug</option>
                    <option value="other">Sonstiges</option>
                  </select>
                  <div class="invalid-feedback">Bitte wählen Sie die Betrugsart.</div>
                </div>
                <div class="col-12">
                  <label class="form-label" for="case_description">Fallbeschreibung <span class="text-danger">*</span></label>
                  <textarea class="form-control" id="case_description" name="case_description" rows="5" placeholder="Beschreiben Sie Ihren Fall so detailliert wie möglich: Wann ist es passiert? Welche Plattform/Person war involviert? Welche Zahlungsmethoden wurden verwendet?" required></textarea>
                  <div class="invalid-feedback">Bitte beschreiben Sie Ihren Fall kurz.</div>
                </div>
                <div class="col-12">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="privacy" name="privacy" required/>
                    <label class="form-check-label" for="privacy" style="font-size:.85rem;">
                      Ich habe die <a href="#" class="text-accent">Datenschutzerklärung</a> gelesen und stimme der Verarbeitung meiner Daten zu. <span class="text-danger">*</span>
                    </label>
                    <div class="invalid-feedback">Bitte stimmen Sie den Datenschutzbestimmungen zu.</div>
                  </div>
                </div>
                <div class="col-12">
                  <div id="mainFormMsg"></div>
                  <button type="submit" class="btn-submit" id="mainSubmitBtn">
                    <span id="mainBtnText"><i class="bi bi-cpu me-1"></i> KI-Analyse kostenlos starten</span>
                    <span class="spinner-sm" id="mainSpinner"></span>
                  </button>
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
<section id="trust-badges">
  <div class="container">
    <div class="row g-4 justify-content-center text-center">
      <div class="col-6 col-md-2">
        <div class="badge-item">
          <div class="badge-icon"><i class="bi bi-shield-fill-check"></i></div>
          <div class="badge-label">SSL Verschlüsselt</div>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="badge-item">
          <div class="badge-icon"><i class="bi bi-patch-check-fill"></i></div>
          <div class="badge-label">TÜV-geprüft</div>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="badge-item">
          <div class="badge-icon"><i class="bi bi-lock-fill"></i></div>
          <div class="badge-label">DSGVO-konform</div>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="badge-item">
          <div class="badge-icon"><i class="bi bi-award-fill"></i></div>
          <div class="badge-label">Zertifizierte Experten</div>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="badge-item">
          <div class="badge-icon"><i class="bi bi-building-check"></i></div>
          <div class="badge-label">Behördlich registriert</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section id="faq">
  <div class="container">
    <div class="text-center mb-5" data-aos="fade-up">
      <h2 class="section-title">Häufige Fragen</h2>
      <p class="section-sub">Antworten auf die wichtigsten Fragen rund um die Kapitalrückholung</p>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-8" data-aos="fade-up">
        <div class="accordion" id="faqAccordion">
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                Wie hoch sind Ihre Erfolgschancen?
              </button>
            </h2>
            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Unsere durchschnittliche Erfolgsquote liegt bei 87%, basierend auf über 4.200 abgeschlossenen Fällen. Die tatsächlichen Chancen hängen vom Einzelfall ab – insbesondere von der verfügbaren Dokumentation, der Zahlungsmethode und dem Zeitraum seit dem Verlust. Je früher Sie handeln, desto besser die Aussichten.
              </div>
            </div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                Was kostet die Erstanalyse?
              </button>
            </h2>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Die Erstanalyse ist vollständig kostenlos und unverbindlich. Wir arbeiten ausschließlich auf Erfolgsbasis: Sie zahlen nur dann, wenn wir Ihr Kapital erfolgreich zurückgeholt haben. Es gibt keine versteckten Gebühren oder Vorauszahlungen.
              </div>
            </div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                Wie lange dauert der Rückholungsprozess?
              </button>
            </h2>
            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Die durchschnittliche Bearbeitungszeit beträgt 6–14 Wochen. Einfachere Fälle mit klarer Dokumentation können schneller abgewickelt werden, während komplexe internationale Fälle mehr Zeit in Anspruch nehmen können. Sie erhalten regelmäßige Updates über den Fortschritt.
              </div>
            </div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                Welche Informationen benötige ich für die Analyse?
              </button>
            </h2>
            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Für die Erstanalyse benötigen wir grundlegende Informationen: den verlorenen Betrag, die Art des Betrugs, die genutzten Plattformen und die verwendeten Zahlungsmethoden. Je mehr Dokumentation Sie haben (E-Mails, Screenshots, Transaktionsbelege), desto besser. Fehlende Informationen versuchen wir im Rahmen unserer Recherche zu ergänzen.
              </div>
            </div>
          </div>
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                Ist meine Anonymität gewährleistet?
              </button>
            </h2>
            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Absolut. Alle Ihre Daten werden streng vertraulich behandelt und ausschließlich für die Fallbearbeitung genutzt. Wir unterliegen der DSGVO und verwenden Ende-zu-Ende-Verschlüsselung. Ihre Daten werden niemals an Dritte weitergegeben, die nicht direkt mit Ihrem Fall befasst sind.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-4">
        <div class="footer-brand">Verlust<span>Rückholung</span></div>
        <div class="footer-desc">KI-gestützte Kapitalrückholung bei Anlagebetrug. Transparente Methoden, internationale Reichweite und beweisbare Resultate seit 2016.</div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="footer-heading">Leistungen</div>
        <ul class="footer-links">
          <li><a href="#leistungen">Blockchain-Forensik</a></li>
          <li><a href="#leistungen">OSINT-Recherche</a></li>
          <li><a href="#leistungen">Rechtsberatung</a></li>
          <li><a href="#leistungen">Beweissicherung</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <div class="footer-heading">Unternehmen</div>
        <ul class="footer-links">
          <li><a href="#how">Ablauf</a></li>
          <li><a href="#why">Über uns</a></li>
          <li><a href="#testimonials">Erfahrungen</a></li>
          <li><a href="#faq">FAQ</a></li>
        </ul>
      </div>
      <div class="col-lg-4">
        <div class="footer-heading">Kontakt</div>
        <ul class="footer-links">
          <li><i class="bi bi-envelope me-2" style="color:var(--accent)"></i> info@verlustrückholung.de</li>
          <li><i class="bi bi-telephone me-2" style="color:var(--accent)"></i> +49 800 000 0000</li>
          <li><i class="bi bi-clock me-2" style="color:var(--accent)"></i> Mo–Fr 08:00–18:00 Uhr</li>
        </ul>
      </div>
    </div>
    <hr class="footer-divider"/>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div class="footer-copy">© <?= date('Y') ?> VerlustRückholung. Alle Rechte vorbehalten.</div>
      <div class="d-flex gap-3">
        <a href="#" class="footer-copy text-decoration-none" style="color:rgba(255,255,255,.35)">Impressum</a>
        <a href="#" class="footer-copy text-decoration-none" style="color:rgba(255,255,255,.35)">Datenschutz</a>
        <a href="#" class="footer-copy text-decoration-none" style="color:rgba(255,255,255,.35)">AGB</a>
      </div>
    </div>
  </div>
</footer>

<!-- ENGAGEMENT MODAL -->
<div class="modal fade" id="engModal" tabindex="-1" aria-labelledby="engModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0" style="border-radius:14px;overflow:hidden;">
      <div class="modal-header">
        <h5 class="modal-title" id="engModalLabel"><i class="bi bi-cpu me-2"></i>KI-Analyse starten – Kostenlos &amp; Unverbindlich</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>
      <div class="modal-body p-4">
        <p class="text-muted mb-3" style="font-size:.9rem;">Verlieren Sie keine Zeit – je früher Sie handeln, desto größer die Erfolgsaussichten. Starten Sie jetzt Ihre kostenlose Analyse.</p>
        <div id="engFormMsg"></div>
        <form id="engForm" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>"/>
          <input type="hidden" name="visit_id" value="<?= htmlspecialchars('', ENT_QUOTES, 'UTF-8') ?>"/>
          <div class="mb-3">
            <label class="form-label" for="eng_first_name">Vorname <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="eng_first_name" name="first_name" placeholder="Max" required/>
            <div class="invalid-feedback">Pflichtfeld.</div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="eng_email">E-Mail-Adresse <span class="text-danger">*</span></label>
            <input type="email" class="form-control" id="eng_email" name="email" placeholder="max@beispiel.de" required/>
            <div class="invalid-feedback">Bitte gültige E-Mail angeben.</div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="eng_amount_lost">Verlorener Betrag (€) <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="eng_amount_lost" name="amount_lost" placeholder="z. B. 25000" min="0" required/>
            <div class="invalid-feedback">Pflichtfeld.</div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="eng_platform_category">Art des Betrugs <span class="text-danger">*</span></label>
            <select class="form-select" id="eng_platform_category" name="platform_category" required>
              <option value="" disabled selected>Bitte wählen…</option>
              <option value="fake_broker">Fake-Broker / Handelsplattform</option>
              <option value="crypto_scam">Kryptowährungs-Betrug</option>
              <option value="investment_fraud">Investment-/Anlagebetrug</option>
              <option value="phishing">Phishing / Kontenübernahme</option>
              <option value="romance_scam">Romance Scam</option>
              <option value="other">Sonstiges</option>
            </select>
            <div class="invalid-feedback">Bitte wählen.</div>
          </div>
          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="eng_privacy" name="privacy" required/>
            <label class="form-check-label" for="eng_privacy" style="font-size:.82rem;">
              Ich stimme der <a href="#" class="text-accent">Datenschutzerklärung</a> zu. <span class="text-danger">*</span>
            </label>
            <div class="invalid-feedback">Zustimmung erforderlich.</div>
          </div>
          <button type="submit" class="btn-submit" id="engSubmitBtn">
            <span id="engBtnText"><i class="bi bi-cpu me-1"></i> Kostenlose Analyse anfordern</span>
            <span class="spinner-sm" id="engSpinner"></span>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
(function () {
  'use strict';

  // ── NAVBAR SCROLL ──
  const nav = document.getElementById('mainNav');
  window.addEventListener('scroll', function () {
    nav.classList.toggle('scrolled', window.scrollY > 50);
  });

  // ── HERO CANVAS – Neural Network Signal Propagation ──
  (function () {
    const canvas = document.getElementById('heroCanvas3');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let W, H, nodes, edges, signal;
    const NODE_COUNT = 20;
    const CONNECT_DIST = 180;
    const SIGNAL_SPEED = 2.2;

    function resize() {
      W = canvas.width  = canvas.offsetWidth;
      H = canvas.height = canvas.offsetHeight;
    }

    function buildNodes() {
      nodes = [];
      for (let i = 0; i < NODE_COUNT; i++) {
        nodes.push({
          x: Math.random() * W,
          y: Math.random() * H,
          r: 3 + Math.random() * 3,
          glow: 0
        });
      }
      edges = [];
      for (let i = 0; i < NODE_COUNT; i++) {
        for (let j = i + 1; j < NODE_COUNT; j++) {
          const dx = nodes[i].x - nodes[j].x;
          const dy = nodes[i].y - nodes[j].y;
          if (Math.sqrt(dx * dx + dy * dy) < CONNECT_DIST) {
            edges.push([i, j]);
          }
        }
      }
    }

    function getNeighbors(idx) {
      const nb = [];
      edges.forEach(function (e) {
        if (e[0] === idx) nb.push(e[1]);
        if (e[1] === idx) nb.push(e[0]);
      });
      return nb;
    }

    function startSignal(fromIdx) {
      const nb = getNeighbors(fromIdx);
      if (nb.length === 0) return;
      const toIdx = nb[Math.floor(Math.random() * nb.length)];
      signal = { from: fromIdx, to: toIdx, t: 0 };
    }

    function initSignal() {
      const nb = edges.length > 0 ? edges[0][0] : 0;
      startSignal(nb);
    }

    function draw() {
      ctx.clearRect(0, 0, W, H);

      // edges
      edges.forEach(function (e) {
        const a = nodes[e[0]], b = nodes[e[1]];
        ctx.beginPath();
        ctx.moveTo(a.x, a.y);
        ctx.lineTo(b.x, b.y);
        ctx.strokeStyle = 'rgba(52,211,153,0.2)';
        ctx.lineWidth = 1;
        ctx.stroke();
      });

      // nodes
      nodes.forEach(function (n) {
        const r = n.r + n.glow * 6;
        if (n.glow > 0) {
          ctx.beginPath();
          ctx.arc(n.x, n.y, r + 6, 0, Math.PI * 2);
          ctx.fillStyle = 'rgba(52,211,153,' + (n.glow * 0.25) + ')';
          ctx.fill();
          n.glow = Math.max(0, n.glow - 0.025);
        }
        ctx.beginPath();
        ctx.arc(n.x, n.y, r, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(52,211,153,0.6)';
        ctx.fill();
      });

      // signal
      if (signal) {
        const a = nodes[signal.from], b = nodes[signal.to];
        const dx = b.x - a.x, dy = b.y - a.y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        signal.t += SIGNAL_SPEED;

        if (signal.t >= dist) {
          nodes[signal.to].glow = 1;
          startSignal(signal.to);
        } else {
          const ratio = signal.t / dist;
          const sx = a.x + dx * ratio;
          const sy = a.y + dy * ratio;

          // glow halo
          const g = ctx.createRadialGradient(sx, sy, 0, sx, sy, 14);
          g.addColorStop(0, 'rgba(5,150,105,0.9)');
          g.addColorStop(1, 'rgba(5,150,105,0)');
          ctx.beginPath();
          ctx.arc(sx, sy, 14, 0, Math.PI * 2);
          ctx.fillStyle = g;
          ctx.fill();

          ctx.beginPath();
          ctx.arc(sx, sy, 5, 0, Math.PI * 2);
          ctx.fillStyle = 'rgba(5,150,105,0.9)';
          ctx.fill();
        }
      }

      requestAnimationFrame(draw);
    }

    window.addEventListener('resize', function () {
      resize();
      buildNodes();
      initSignal();
    });

    resize();
    buildNodes();
    initSignal();
    draw();
  })();

  // ── AI SECTION – CSS RIPPLE NODES ──
  (function () {
    const net = document.getElementById('aiNetwork');
    if (!net) return;
    const positions = [
      [15,20],[35,60],[55,25],[75,65],[20,80],
      [60,75],[45,50],[80,30],[10,50],[70,10]
    ];
    positions.forEach(function (p, i) {
      const n = document.createElement('div');
      n.className = 'ai-node';
      n.style.left = p[0] + '%';
      n.style.top  = p[1] + '%';
      net.appendChild(n);
      setInterval(function () {
        n.classList.remove('active');
        void n.offsetWidth;
        n.classList.add('active');
      }, 2000 + i * 400);
    });
  })();

  // ── AI COUNTER ANIMATION ──
  (function () {
    const el = document.getElementById('aiCounter');
    if (!el) return;
    let val = 127;
    setInterval(function () {
      val += Math.floor(Math.random() * 3);
      el.textContent = val;
    }, 3500);
  })();

  // ── COUNT UP (stats-band) ──
  (function () {
    document.querySelectorAll('#stats-band .stat-num[data-target]').forEach(function (el) {
      const target = +el.dataset.target;
      const prefix = el.dataset.prefix || '';
      const suffix = el.dataset.suffix || '';
      let current = 0;
      const step = Math.ceil(target / 60);
      const timer = setInterval(function () {
        current = Math.min(current + step, target);
        el.textContent = prefix + current.toLocaleString('de-DE') + suffix;
        if (current >= target) clearInterval(timer);
      }, 30);
    });
  })();

  // ── COUNT UP (statistics section) with IntersectionObserver ──
  (function () {
    const els = document.querySelectorAll('#statistics .count-up[data-target]');
    if (!els.length) return;
    const observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        observer.unobserve(el);
        const target = +el.dataset.target;
        const prefix = el.dataset.prefix || '';
        const suffix = el.dataset.suffix || '';
        let current = 0;
        const step = Math.ceil(target / 80);
        const timer = setInterval(function () {
          current = Math.min(current + step, target);
          el.textContent = prefix + current.toLocaleString('de-DE') + suffix;
          if (current >= target) clearInterval(timer);
        }, 20);
      });
    }, { threshold: 0.4 });
    els.forEach(function (el) { observer.observe(el); });
  })();

  // ── AJAX FORM SETUP ──
  function setupForm(formId, submitBtnId, btnTextId, spinnerId, msgId) {
    const form = document.getElementById(formId);
    if (!form) return;
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
      }
      const btn     = document.getElementById(submitBtnId);
      const btnText = document.getElementById(btnTextId);
      const spinner = document.getElementById(spinnerId);
      const msgEl   = document.getElementById(msgId);

      btn.disabled = true;
      btnText.style.display = 'none';
      spinner.style.display = 'inline-block';
      msgEl.innerHTML = '';

      const data = new FormData(form);

      fetch('../submit_lead.php', { method: 'POST', body: data })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          btn.disabled = false;
          btnText.style.display = '';
          spinner.style.display = 'none';
          if (res.success) {
            msgEl.innerHTML = '<div class="alert-success-box"><i class="bi bi-check-circle-fill me-2"></i><strong>Vielen Dank!</strong> Wir melden uns innerhalb von 24 Stunden.</div>';
            form.reset();
            form.classList.remove('was-validated');
            const modal = bootstrap.Modal.getInstance(document.getElementById('engModal'));
            if (modal) modal.hide();
          } else {
            msgEl.innerHTML = '<div class="alert-error-box"><i class="bi bi-exclamation-triangle-fill me-2"></i>' + (res.message || 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.') + '</div>';
          }
        })
        .catch(function () {
          btn.disabled = false;
          btnText.style.display = '';
          spinner.style.display = 'none';
          msgEl.innerHTML = '<div class="alert-error-box"><i class="bi bi-exclamation-triangle-fill me-2"></i>Verbindungsfehler. Bitte erneut versuchen.</div>';
        });
    });
  }

  setupForm('mainForm', 'mainSubmitBtn', 'mainBtnText', 'mainSpinner', 'mainFormMsg');
  setupForm('engForm',  'engSubmitBtn',  'engBtnText',  'engSpinner',  'engFormMsg');

  // ── MODAL TRIGGER ──
  (function () {
    const delay = <?= $modal_delay * 1000 ?>;
    setTimeout(function () {
      const modalEl = document.getElementById('engModal');
      if (!modalEl) return;
      const modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
      modal.show();
    }, delay);
  })();

  // ── AOS INIT ──
  AOS.init({ duration: 700, once: true, offset: 60 });

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

<?php if (function_exists('render_visitor_tracking')): ?>
<?= render_visitor_tracking() ?>
<?php endif; ?>

<!-- ===== CONTACT MODAL ===== -->
<div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
    <div class="modal-content border-0 shadow-xl" style="border-radius:16px;overflow:hidden;">
      <div class="modal-header text-white py-3 px-4" style="background:linear-gradient(135deg,#059669,#047857);border:none;">
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
                <li class="mb-3 d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#059669;flex-shrink:0"></i><span><strong class="text-dark">87% Erfolgsquote</strong> – verifiziert über 2.400 Mandate</span></li>
                <li class="mb-3 d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#059669;flex-shrink:0"></i><span><strong class="text-dark">€0 Vorauszahlung</strong> – Sie zahlen nur bei Erfolg</span></li>
                <li class="mb-3 d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#059669;flex-shrink:0"></i><span><strong class="text-dark">72h Erstantwort</strong> – KI prüft Ihren Fall sofort</span></li>
                <li class="mb-3 d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#059669;flex-shrink:0"></i><span><strong class="text-dark">40+ Länder</strong> – Internationales Expertennetzwerk</span></li>
                <li class="d-flex gap-2"><i class="bi bi-check-circle-fill mt-1" style="color:#059669;flex-shrink:0"></i><span><strong class="text-dark">DSGVO-konform</strong> – Höchste Datensicherheit</span></li>
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
                      Ich stimme der <a href="#" style="color:#059669">Datenschutzerklärung</a> zu und bin einverstanden, dass meine Daten zur Fallbearbeitung verwendet werden. *
                    </label>
                    <div class="invalid-feedback">Bitte Datenschutz bestätigen.</div>
                  </div>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn w-100 fw-bold py-3" style="background:#059669;color:#fff;font-size:1rem;border:none;border-radius:10px;">
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
