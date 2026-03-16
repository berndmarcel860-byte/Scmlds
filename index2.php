<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title  = get_setting('page_title', 'VerlustRückholung – KI-gestützte Kapitalrückholung bei Anlagebetrug');
$modal_delay = max(5, (int) get_setting('modal_delay_seconds', '60'));
$success = isset($_GET['success']) && $_GET['success'] === '1';
$error   = isset($_GET['error']) ? htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') : '';

// Year range helper — guard against config version mismatch
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
    <meta name="description" content="VerlustRückholung hilft Opfern von Anlagebetrug ihr Kapital zurückzufordern. KI-gestützte Analyse, internationale Experten, 87% Erfolgsquote. Kostenlose Erstprüfung.">

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- AOS (Animate on Scroll) -->
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
    /* ============================================================
       INDEX2.PHP – VerlustRückholung Professional Redesign
       ============================================================ */
    :root {
        --primary:   #0d2b5e;
        --accent:    #f5a623;
        --accent-d:  #e6941a;
        --success:   #198754;
        --text:      #1a202c;
        --muted:     #6c757d;
        --bg-light:  #f8faff;
        --border:    #e2e8f0;
    }
    *, *::before, *::after { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
        font-family: 'Inter', sans-serif;
        color: var(--text);
        background: #fff;
        overflow-x: hidden;
    }

    /* ── TYPOGRAPHY ─────────────────────────────────────────── */
    .section-eyebrow {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: .8rem; font-weight: 700; letter-spacing: .1em;
        text-transform: uppercase; color: var(--accent);
        margin-bottom: 1rem;
    }
    .section-eyebrow::before {
        content: ''; display: inline-block; width: 24px; height: 2px;
        background: var(--accent); border-radius: 2px;
    }

    /* ── NAVBAR ─────────────────────────────────────────────── */
    .navbar-v2 {
        background: rgba(10, 22, 40, 0.95);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid rgba(255,255,255,.08);
        padding: .75rem 0;
        position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
        transition: box-shadow .3s;
    }
    .navbar-v2.scrolled { box-shadow: 0 4px 32px rgba(0,0,0,.3); }
    .nav-brand { font-size: 1.25rem; font-weight: 800; color: #fff; text-decoration: none; }
    .nav-brand span { color: var(--accent); }
    .nav-link-v2 { color: rgba(255,255,255,.75); text-decoration: none; font-size: .9rem; font-weight: 500;
                   padding: .4rem .9rem; border-radius: 6px; transition: all .2s; }
    .nav-link-v2:hover { color: #fff; background: rgba(255,255,255,.08); }
    .btn-cta-nav { background: var(--accent); color: #000; font-weight: 700; padding: .5rem 1.25rem;
                   border-radius: 8px; border: none; font-size: .9rem; transition: all .2s; text-decoration: none; }
    .btn-cta-nav:hover { background: var(--accent-d); color: #000; transform: translateY(-1px);
                         box-shadow: 0 4px 16px rgba(245,166,35,.4); }

    /* ── HERO ───────────────────────────────────────────────── */
    .hero-v2 {
        min-height: 100vh;
        padding-top: 80px;
        background: linear-gradient(135deg, #06111e 0%, #0a1e36 40%, #0d2b5e 80%, #0a2050 100%);
        position: relative;
        display: flex; align-items: center;
        overflow: hidden;
    }

    /* Animated shield/fund-recovery SVG background */
    .hero-v2::before {
        content: '';
        position: absolute; inset: 0;
        background-image:
            radial-gradient(ellipse at 20% 50%, rgba(13,43,94,.6) 0%, transparent 60%),
            radial-gradient(ellipse at 80% 20%, rgba(245,166,35,.08) 0%, transparent 50%);
        pointer-events: none;
    }
    /* Animated canvas takes full hero background – see #heroCanvas */
    /* Floating shield graphic */
    .hero-shield-wrap {
        position: absolute; right: 5%; top: 50%;
        transform: translateY(-50%);
        width: min(480px, 45vw);
        opacity: .12;
        pointer-events: none;
    }
    .hero-shield-wrap svg { width: 100%; height: auto; }

    /* Floating stats pills */
    .stat-pill {
        position: absolute;
        background: rgba(255,255,255,.06);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 50px;
        padding: .5rem 1.1rem;
        color: #fff;
        font-size: .8rem;
        font-weight: 600;
        display: flex; align-items: center; gap: .5rem;
        animation: floatPill 4s ease-in-out infinite;
        white-space: nowrap;
    }
    .stat-pill .val { color: var(--accent); font-size: 1rem; font-weight: 800; }
    .stat-pill:nth-child(1) { top: 20%; right: 42%; animation-delay: 0s; }
    .stat-pill:nth-child(2) { top: 38%; right: 55%; animation-delay: 1.3s; }
    .stat-pill:nth-child(3) { top: 60%; right: 44%; animation-delay: 2.6s; }
    @keyframes floatPill { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }

    /* Live dot */
    .live-dot { width:8px;height:8px;border-radius:50%;background:#22c55e;
                box-shadow:0 0 0 2px rgba(34,197,94,.3);
                animation:livePulse 1.5s infinite; display:inline-block; }
    @keyframes livePulse { 0%,100%{box-shadow:0 0 0 2px rgba(34,197,94,.3)} 50%{box-shadow:0 0 0 6px rgba(34,197,94,.1)} }

    .hero-badge {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(245,166,35,.15); border: 1px solid rgba(245,166,35,.3);
        color: var(--accent); border-radius: 50px; padding: .35rem 1rem;
        font-size: .8rem; font-weight: 700; margin-bottom: 1.25rem;
    }
    .hero-headline { font-size: clamp(2rem, 4.5vw, 3.5rem); font-weight: 900;
                     color: #fff; line-height: 1.1; letter-spacing: -.02em; }
    .hero-headline .highlight { color: var(--accent); position: relative; }
    .hero-sub { font-size: 1.1rem; color: rgba(255,255,255,.7); line-height: 1.75; max-width: 520px; }

    /* Trust row */
    .trust-row { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; margin-top: 1.5rem; }
    .trust-item { display: flex; align-items: center; gap: .4rem;
                  color: rgba(255,255,255,.6); font-size: .82rem; font-weight: 500; }
    .trust-item i { color: var(--accent); }

    /* Hero form card */
    .hero-form-card {
        background: #fff;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 32px 80px rgba(0,0,0,.4);
    }
    .hero-form-card .form-card-header {
        background: linear-gradient(135deg, var(--primary), #1a4a9e);
        margin: -2rem -2rem 1.5rem;
        padding: 1.25rem 2rem;
        border-radius: 20px 20px 0 0;
        color: #fff;
    }

    /* ── STATS BAND ─────────────────────────────────────────── */
    .stats-band {
        background: var(--primary);
        padding: 2.5rem 0;
    }
    .stat-card-v2 { text-align: center; }
    .stat-card-v2 .num { font-size: 2.25rem; font-weight: 900; color: var(--accent); }
    .stat-card-v2 .lbl { color: rgba(255,255,255,.7); font-size: .88rem; font-weight: 500; margin-top: .25rem; }
    .stat-divider { width: 1px; background: rgba(255,255,255,.15); }

    /* ── HOW IT WORKS ───────────────────────────────────────── */
    .process-step {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 1.75rem;
        height: 100%;
        position: relative;
        transition: all .3s;
    }
    .process-step:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(13,43,94,.12); }
    .step-number {
        width: 48px; height: 48px;
        background: linear-gradient(135deg, var(--primary), #1a4a9e);
        color: #fff; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 1.1rem; margin-bottom: 1rem;
    }
    .step-connector {
        display: none;
    }
    @media (min-width: 992px) {
        .step-connector {
            display: block; position: absolute;
            top: 40px; right: -16px; width: 32px;
            color: var(--accent); font-size: 1.5rem; z-index: 1;
        }
    }

    /* ── FEATURES ───────────────────────────────────────────── */
    .feature-icon {
        width: 64px; height: 64px;
        background: linear-gradient(135deg, rgba(13,43,94,.08), rgba(26,74,158,.12));
        border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; color: var(--primary);
        margin-bottom: 1rem;
        transition: all .3s;
    }
    .feature-card:hover .feature-icon {
        background: linear-gradient(135deg, var(--primary), #1a4a9e);
        color: #fff; transform: scale(1.1);
    }

    /* ── CASE TYPES ─────────────────────────────────────────── */
    .case-card {
        border: 2px solid var(--border);
        border-radius: 16px;
        padding: 1.5rem;
        text-align: center;
        transition: all .3s;
        cursor: pointer;
        background: #fff;
        height: 100%;
    }
    .case-card:hover {
        border-color: var(--primary);
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(13,43,94,.12);
    }
    .case-icon { font-size: 2rem; margin-bottom: .75rem; }

    /* ── TESTIMONIALS ───────────────────────────────────────── */
    .testimonial-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 1.75rem;
        height: 100%;
        position: relative;
    }
    .testimonial-card::before {
        content: '"'; position: absolute;
        top: -8px; left: 20px;
        font-size: 5rem; line-height: 1;
        color: var(--accent); opacity: .3;
        font-family: Georgia, serif;
    }
    .testimonial-stars { color: #f5a623; font-size: .9rem; margin-bottom: .75rem; }
    .testimonial-avatar {
        width: 40px; height: 40px; border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), #1a4a9e);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 700; font-size: .9rem;
    }

    /* ── MAIN FORM SECTION ──────────────────────────────────── */
    .form-section-v2 {
        background: linear-gradient(135deg, var(--bg-light), #eef2ff);
        padding: 6rem 0;
    }
    .form-box-v2 {
        background: #fff;
        border-radius: 24px;
        box-shadow: 0 24px 80px rgba(13,43,94,.12);
        overflow: hidden;
    }
    .form-box-header {
        background: linear-gradient(135deg, var(--primary), #1a4a9e);
        padding: 2rem 2.5rem;
        color: #fff;
    }
    .form-box-body { padding: 2.5rem; }
    .form-step-label {
        display: flex; align-items: center; gap: .75rem;
        font-weight: 700; font-size: .95rem; color: var(--primary);
        margin-bottom: 1.25rem; padding-bottom: .75rem;
        border-bottom: 2px solid var(--border);
    }
    .form-step-label .num {
        width: 28px; height: 28px; border-radius: 50%;
        background: var(--primary); color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: .8rem; font-weight: 800; flex-shrink: 0;
    }
    .badge-row-form {
        display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1rem;
    }
    .badge-form { background: rgba(13,43,94,.06); border: 1px solid rgba(13,43,94,.12);
                  color: var(--primary); border-radius: 50px; padding: .3rem .85rem;
                  font-size: .75rem; font-weight: 600; }

    /* ── FAQ ─────────────────────────────────────────────────── */
    .accordion-v2 .accordion-item {
        border: 1px solid var(--border); border-radius: 12px !important;
        margin-bottom: .75rem; overflow: hidden;
    }
    .accordion-v2 .accordion-button {
        font-weight: 600; background: #fff; color: var(--text);
        border-radius: 12px !important;
    }
    .accordion-v2 .accordion-button:not(.collapsed) {
        background: var(--bg-light); color: var(--primary);
        box-shadow: none;
    }
    .accordion-v2 .accordion-button::after { filter: none; }

    /* ── FOOTER ─────────────────────────────────────────────── */
    .footer-v2 {
        background: #06111e;
        color: rgba(255,255,255,.6);
        padding: 4rem 0 2rem;
    }
    .footer-brand { font-size: 1.3rem; font-weight: 800; color: #fff; }
    .footer-brand span { color: var(--accent); }
    .footer-link { color: rgba(255,255,255,.5); text-decoration: none; font-size: .875rem;
                   transition: color .2s; display: block; margin-bottom: .5rem; }
    .footer-link:hover { color: var(--accent); }

    /* ── ENGAGEMENT MODAL ───────────────────────────────────── */
    .eng-modal-content {
        border: none; border-radius: 20px; overflow: hidden;
    }
    .eng-modal-left {
        background: linear-gradient(160deg, #0d2b5e, #1a4a9e);
        padding: 2.5rem 2rem;
        display: flex; flex-direction: column; justify-content: space-between;
    }
    .eng-stat { color: #fff; margin-bottom: 1.25rem; }
    .eng-stat .val { font-size: 1.75rem; font-weight: 900; color: var(--accent); display: block; }
    .eng-stat .lbl { font-size: .8rem; color: rgba(255,255,255,.6); }

    /* ── UTILITY ─────────────────────────────────────────────── */
    .py-6 { padding-top: 5rem; padding-bottom: 5rem; }
    .section-divider { height: 1px; background: var(--border); }
    @media (max-width: 767px) {
        .stat-pill { display: none; }
        .hero-shield-wrap { display: none; }
        .hero-v2 { min-height: auto; padding-top: 100px; padding-bottom: 2rem; }
    }

    /* ── HERO CANVAS (AI particle network) ───────────────────── */
    #heroCanvas {
        position: absolute; inset: 0;
        width: 100%; height: 100%;
        pointer-events: none; z-index: 0;
    }

    /* ── 3D CARD HOVER ───────────────────────────────────────── */
    .card-3d-wrap { perspective: 1200px; }
    .card-3d {
        transition: transform .6s cubic-bezier(.2,.8,.3,1),
                    box-shadow .6s ease;
        transform-style: preserve-3d;
    }
    .card-3d:hover {
        transform: rotateY(8deg) rotateX(-4deg) scale(1.03);
        box-shadow: -12px 20px 40px rgba(13,43,94,.2);
    }

    /* ── AI TERMINAL ─────────────────────────────────────────── */
    .ai-terminal {
        background: #060d1a;
        border: 1px solid rgba(245,166,35,.25);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 0 0 1px rgba(245,166,35,.06),
                    0 32px 64px rgba(0,0,0,.55),
                    inset 0 0 60px rgba(13,43,94,.25);
    }
    .ai-terminal-bar {
        background: #0d1b2e;
        padding: .65rem 1rem;
        display: flex; align-items: center; gap: .45rem;
        border-bottom: 1px solid rgba(255,255,255,.06);
    }
    .ai-dot { width: 10px; height: 10px; border-radius: 50%; }
    .ai-terminal-body {
        padding: 1.25rem 1.5rem;
        font-family: 'Courier New', monospace;
        font-size: .78rem; line-height: 2;
        color: rgba(255,255,255,.8);
        min-height: 280px;
    }
    .t-green  { color: #22c55e; }
    .t-gold   { color: #f5a623; }
    .t-blue   { color: #60a5fa; }
    .t-red    { color: #f87171; }
    .t-muted  { color: rgba(255,255,255,.35); }
    .ai-cursor {
        display: inline-block; width: 7px; height: 13px;
        background: #22c55e; vertical-align: middle; margin-left: 2px;
        animation: blink .75s step-end infinite;
        border-radius: 1px;
    }
    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }

    /* ── SCAN LINE ───────────────────────────────────────────── */
    .scan-wrap { position: relative; overflow: hidden; }
    .scan-line {
        position: absolute; left: 0; right: 0; height: 2px;
        background: linear-gradient(90deg, transparent, rgba(34,197,94,.5), transparent);
        animation: scanDown 3.5s linear infinite;
        pointer-events: none; z-index: 2;
    }
    @keyframes scanDown {
        0%   { top: -2px; opacity: 0; }
        5%   { opacity: 1; }
        95%  { opacity: 1; }
        100% { top: 100%; opacity: 0; }
    }

    /* ── AI DEMO SECTION ─────────────────────────────────────── */
    .ai-demo-section {
        background: linear-gradient(180deg, #060d1a 0%, #0a1e36 100%);
        padding: 6rem 0;
        position: relative; overflow: hidden;
    }
    .ai-demo-section::before {
        content: '';
        position: absolute; inset: 0;
        background: radial-gradient(ellipse at 70% 50%, rgba(245,166,35,.06) 0%, transparent 60%),
                    radial-gradient(ellipse at 20% 80%, rgba(13,43,94,.5) 0%, transparent 50%);
        pointer-events: none;
    }

    /* ── AI PROGRESS BARS ────────────────────────────────────── */
    .ai-progress-wrap { margin-bottom: 1.1rem; }
    .ai-progress-track {
        height: 6px; border-radius: 3px;
        background: rgba(255,255,255,.08); overflow: hidden; margin-top: .4rem;
    }
    .ai-progress-fill {
        height: 100%; border-radius: 3px;
        background: linear-gradient(90deg, var(--accent), #ff8c00);
        width: 0; transition: width 1.6s cubic-bezier(.4,0,.2,1) .2s;
    }
    .ai-progress-fill.animated { width: var(--pct, 0%); }

    /* ── FLOATING 3D MINI CARDS ──────────────────────────────── */
    .float-card {
        background: rgba(255,255,255,.05);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 14px; padding: 1.1rem;
        backdrop-filter: blur(10px);
        animation: floatCard 6s ease-in-out infinite;
    }
    .float-card:nth-child(2) { animation-delay: -2s; }
    .float-card:nth-child(3) { animation-delay: -4s; }
    @keyframes floatCard {
        0%,100% { transform: translateY(0) rotate(0deg); }
        33%     { transform: translateY(-7px) rotate(.4deg); }
        66%     { transform: translateY(-3px) rotate(-.4deg); }
    }

    /* ── GLOW ────────────────────────────────────────────────── */
    .glow-gold { text-shadow: 0 0 20px rgba(245,166,35,.6), 0 0 40px rgba(245,166,35,.3); }

    /* ── LIVE RECOVERY TICKER ────────────────────────────────── */
    .ticker-wrap {
        background: #06111e;
        border-top: 1px solid rgba(255,255,255,.06);
        border-bottom: 1px solid rgba(255,255,255,.06);
        padding: .65rem 0; overflow: hidden;
    }
    .ticker-track {
        display: flex; white-space: nowrap;
        animation: tickerScroll 50s linear infinite;
    }
    .ticker-track:hover { animation-play-state: paused; }
    .ticker-item {
        display: inline-flex; align-items: center; gap: .6rem;
        padding: 0 2.5rem; font-size: .82rem;
        color: rgba(255,255,255,.55); font-weight: 500;
    }
    .t-badge-ok {
        background: rgba(34,197,94,.15); color: #22c55e;
        font-size: .7rem; padding: .15rem .55rem;
        border-radius: 4px; font-weight: 700; white-space: nowrap;
    }
    .t-amount { color: var(--accent); font-weight: 700; }
    @keyframes tickerScroll { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }

    /* ── SVG SUCCESS RING ────────────────────────────────────── */
    .ring-wrap { position: relative; width: 130px; height: 130px; margin: 0 auto 1rem; }
    .ring-svg { transform: rotate(-90deg); }
    .ring-track { fill: none; stroke: rgba(255,255,255,.1); stroke-width: 9; }
    .ring-fill  {
        fill: none; stroke: var(--accent); stroke-width: 9; stroke-linecap: round;
        stroke-dasharray: 366;        /* 2π × r=58.3 ≈ 366 */
        stroke-dashoffset: 366;
        transition: stroke-dashoffset 1.8s cubic-bezier(.4,0,.2,1);
    }
    .ring-fill.animated { stroke-dashoffset: 47; } /* 366*(1-0.87)=47.6 */
    .ring-label {
        position: absolute; inset: 0;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        font-weight: 900; color: var(--accent); font-size: 1.6rem; line-height: 1;
    }
    .ring-label small { font-size: .68rem; color: rgba(255,255,255,.55); font-weight: 500; margin-top: 2px; }

    /* ── ENHANCED STATS BAND ─────────────────────────────────── */
    .stats-band-v3 {
        background: linear-gradient(135deg, var(--primary) 0%, #1a4a9e 100%);
        padding: 3.5rem 0;
        position: relative; overflow: hidden;
    }
    .stats-band-v3::before {
        content: '';
        position: absolute; inset: 0;
        background: radial-gradient(ellipse at 80% 50%, rgba(245,166,35,.08) 0%, transparent 60%);
        pointer-events: none;
    }
    .stat-v3 { text-align: center; }
    .stat-v3 .n { font-size: 2.5rem; font-weight: 900; color: var(--accent); line-height: 1; }
    .stat-v3 .l { color: rgba(255,255,255,.65); font-size: .85rem; font-weight: 500; margin-top: .35rem; }

    /* ── TRUST BADGES ────────────────────────────────────────── */
    .trust-badge-card {
        display: flex; align-items: flex-start; gap: .85rem;
        background: #fff; border: 1px solid var(--border);
        border-radius: 14px; padding: 1.25rem 1.5rem;
        height: 100%; transition: all .3s;
    }
    .trust-badge-card:hover {
        border-color: var(--primary);
        box-shadow: 0 6px 20px rgba(13,43,94,.1);
        transform: translateY(-2px);
    }
    .trust-badge-icon {
        font-size: 1.6rem; flex-shrink: 0;
        width: 48px; height: 48px;
        background: linear-gradient(135deg, rgba(13,43,94,.06), rgba(26,74,158,.1));
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
    }

    /* ── 3D HERO SHIELD ROTATE ───────────────────────────────── */
    @keyframes shieldFloat {
        0%,100% { transform: translateY(-50%) rotate(0deg) scale(1); }
        25%     { transform: translateY(-52%) rotate(1deg) scale(1.01); }
        75%     { transform: translateY(-48%) rotate(-1deg) scale(0.99); }
    }
    .hero-shield-wrap {
        position: absolute; right: 5%; top: 50%;
        transform: translateY(-50%);
        width: min(480px, 45vw);
        opacity: .15;
        pointer-events: none;
        animation: shieldFloat 8s ease-in-out infinite;
    }
    .hero-shield-wrap svg { width: 100%; height: auto; }

    /* ── PARTICLE GLOW PULSE ─────────────────────────────────── */
    @keyframes pulseGlow {
        0%,100% { box-shadow: 0 0 0 0 rgba(245,166,35,0); }
        50%     { box-shadow: 0 0 0 8px rgba(245,166,35,.15); }
    }
    .badge-pulse { animation: pulseGlow 3s ease-in-out infinite; }

    /* ── FALL PRÜFEN MODAL (synced from index.php) ───────────── */
    #fullFormModal .modal-xl { max-width: 900px; }
    .fall-modal-content {
        border: none; border-radius: 20px; overflow: hidden;
        box-shadow: 0 24px 80px rgba(0,0,0,0.22);
    }
    .fall-modal-header-inner {
        background: linear-gradient(135deg, #060e1f 0%, #0d2b5e 55%, #1a4a9e 100%);
    }
    .fall-badges-strip {
        display: flex; gap: 0;
        border-top: 1px solid rgba(255,255,255,0.1);
    }
    .fall-badge {
        flex: 1; display: flex; align-items: center; justify-content: center;
        gap: 6px; padding: 10px 8px; font-size: 0.78rem;
        color: rgba(255,255,255,0.85);
        border-right: 1px solid rgba(255,255,255,0.1);
        transition: background 0.2s ease;
    }
    .fall-badge:last-child { border-right: none; }
    .fall-badge i { font-size: 1rem; color: var(--accent); flex-shrink: 0; }
    .fall-badge strong { color: #fff; }
    .fall-modal-sidebar {
        background: linear-gradient(160deg, #0a1628 0%, #0f2248 100%);
        border-right: 1px solid rgba(255,255,255,0.07);
    }
    .fall-sidebar-list { margin: 0; padding: 0; }
    .fall-sidebar-list li {
        display: flex; align-items: flex-start; gap: 10px;
        color: rgba(255,255,255,0.82); font-size: 0.85rem;
        margin-bottom: 12px; line-height: 1.4;
    }
    .fall-sidebar-list li i { font-size: 0.9rem; margin-top: 1px; flex-shrink: 0; }
    .fall-stat-mini {
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 10px; padding: 10px 14px;
    }
    .fall-stat-val { font-size: 1.4rem; font-weight: 800; color: var(--accent); line-height: 1; }
    .fall-stat-lbl { font-size: 0.72rem; color: rgba(255,255,255,0.5); margin-top: 3px; }
    .fall-form-section {
        background: #f8faff; border: 1px solid #e8edf5;
        border-radius: 12px; padding: 20px;
    }
    .fall-form-section-title {
        display: flex; align-items: center; gap: 10px;
        font-weight: 700; font-size: 0.9rem; color: #1a202c; margin-bottom: 16px;
    }
    .fall-section-num {
        width: 26px; height: 26px; min-width: 26px;
        background: var(--primary); color: #fff; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.8rem; font-weight: 800;
    }
    .fall-form-section .input-group-text { border-color: #dee2e6; color: #6c757d; }
    .fall-form-section .form-control,
    .fall-form-section .form-select { border-color: #dee2e6; }
    .fall-form-section .form-control:focus,
    .fall-form-section .form-select:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 0.2rem rgba(245,166,35,0.18);
    }
    .fall-submit-btn {
        background: linear-gradient(135deg, #f5a623 0%, #e69420 100%) !important;
        border: none !important; border-radius: 12px !important;
        font-size: 1.05rem; letter-spacing: 0.02em;
        box-shadow: 0 6px 20px rgba(245,166,35,0.35);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .fall-submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(245,166,35,0.45);
    }
    .letter-spacing { letter-spacing: 0.08em; }

    /* ── ENGAGEMENT MODAL (synced from index.php) ────────────── */
    .engagement-modal-content { border: none; border-radius: 20px; overflow: hidden; }
    .engagement-modal-body { padding: 0; }
    .engagement-left {
        background: linear-gradient(160deg, var(--dark) 0%, var(--primary) 100%);
        min-height: 380px;
    }
    .engagement-right { background: #fff; }
    .engagement-icon {
        width: 80px; height: 80px;
        background: rgba(245,166,35,0.15);
        border: 2px solid rgba(245,166,35,0.4);
        border-radius: 50%; display: flex;
        align-items: center; justify-content: center;
        font-size: 2.2rem; color: var(--accent);
    }
    .engagement-close {
        position: absolute; top: 12px; right: 12px; z-index: 10;
        background: rgba(0,0,0,0.15); border-radius: 50%; opacity: 0.7;
    }
    .engagement-close:hover { opacity: 1; }

    /* ===== TRUST BANNER ===== */
    .trust-banner { background: #fff; border-bottom: 1px solid #e9ecef; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }

    /* ===== NEWS TICKER ===== */
    .news-ticker-bar { background: linear-gradient(90deg, #0d2b5e 0%, #1a4a9e 100%); padding: 0; display: flex; align-items: center; height: 40px; overflow: hidden; }
    .news-ticker-label { flex-shrink: 0; background: var(--accent); color: #000; font-weight: 700; font-size: 0.75rem; padding: 0 14px; height: 100%; display: flex; align-items: center; text-transform: uppercase; letter-spacing: 0.05em; }
    .news-ticker-wrap { overflow: hidden; flex: 1; }
    .news-ticker-inner { display: inline-block; white-space: nowrap; font-size: 0.82rem; color: rgba(255,255,255,0.88); padding-left: 100%; animation: newsScroll 60s linear infinite; }
    .news-ticker-inner span { margin: 0 8px; }
    @keyframes newsScroll { 0% { transform: translateX(0); } 100% { transform: translateX(-100%); } }

    /* ===== AI 3D CANVAS SECTION ===== */
    .ai-section-3d { background: linear-gradient(135deg, #060e1f 0%, #0d2b5e 60%, #0f1e3d 100%); position: relative; overflow: hidden; }
    .ai-section-3d::before { content: ''; position: absolute; inset: 0; background: radial-gradient(ellipse at 70% 50%, rgba(245,166,35,0.06) 0%, transparent 60%); pointer-events: none; }
    .ai-canvas-wrap { position: relative; border-radius: 20px; overflow: hidden; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); aspect-ratio: 4/3; }
    .ai-canvas-wrap canvas { width: 100%; height: 100%; display: block; }
    .ai-canvas-hud { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(6,14,31,0.85); backdrop-filter: blur(8px); padding: 12px 16px; border-top: 1px solid rgba(255,255,255,0.08); display: grid; grid-template-columns: 1fr 1fr; gap: 6px 16px; }
    .ai-hud-row { display: flex; justify-content: space-between; align-items: center; }
    .ai-hud-label { font-size: 0.72rem; color: rgba(255,255,255,0.5); }
    .ai-hud-val { font-size: 0.82rem; font-weight: 700; font-family: monospace; }
    .ai-steps-3d { display: flex; flex-direction: column; gap: 1.25rem; }
    .ai-step-item-3d { display: flex; align-items: flex-start; gap: 1rem; padding: 1rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.06); background: rgba(255,255,255,0.03); transition: all 0.4s ease; }
    .ai-step-item-3d.active, .ai-step-item-3d:hover { background: rgba(255,255,255,0.07); border-color: rgba(245,166,35,0.3); }
    .ai-step-icon-3d { width: 44px; height: 44px; min-width: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }

    /* ===== ADVANTAGE CARDS ===== */
    .advantage-card { transition: transform 0.3s ease, box-shadow 0.3s ease; border: 1px solid transparent; }
    .advantage-card:hover { transform: translateY(-6px); box-shadow: 0 12px 40px rgba(0,0,0,0.12) !important; border-color: var(--accent); }
    .advantage-icon { width: 70px; height: 70px; display: flex; align-items: center; justify-content: center; }

    /* ===== SCAM CARDS rich ===== */
    .scam-card { transition: all 0.3s ease; cursor: pointer; background: #fff; }
    .scam-card:hover { transform: translateY(-4px); box-shadow: 0 8px 30px rgba(0,0,0,0.12); border-color: #1a4a9e !important; }
    .scam-icon { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

    /* ===== STATISTICS SECTION ===== */
    .stats-section { background: linear-gradient(135deg, #0d2b5e 0%, #1a4a9e 100%); position: relative; overflow: hidden; }
    .stats-section::before { content: ''; position: absolute; inset: 0; background: radial-gradient(ellipse at center, rgba(245,166,35,0.08) 0%, transparent 70%); pointer-events: none; }
    .stat-card { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1); transition: transform 0.3s ease; }
    .stat-card:hover { transform: translateY(-4px); background: rgba(255,255,255,0.12); }
    .stat-number { line-height: 1; display: inline-block; }
    .stat-unit { display: inline-block; vertical-align: top; margin-top: 0.5rem; }
    .carousel-control-prev, .carousel-control-next { width: 2.5rem; background: rgba(255,255,255,0.1); border-radius: 50%; height: 2.5rem; top: 50%; transform: translateY(-50%); opacity: 0.8; }

    /* ===== FORM SIDEBAR ===== */
    .form-sidebar { background: linear-gradient(135deg, #0d2b5e 0%, #1a4a9e 100%); padding: 2.5rem 2rem; }

    /* ===== STEP TIMELINE ===== */
    .step-timeline { position: relative; padding-left: 0; }
    .step-item-timeline { display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1.25rem; position: relative; }
    .step-item-timeline:not(:last-child)::before { content: ''; position: absolute; left: 18px; top: 36px; bottom: -10px; width: 2px; background: linear-gradient(to bottom, #f5a623, transparent); }
    .step-num { width: 36px; height: 36px; min-width: 36px; background: #f5a623; color: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; }
    </style>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar-v2" id="mainNav2">
    <div class="container d-flex align-items-center justify-content-between gap-3">
        <a href="#" class="nav-brand">⚖️ Verlust<span>Rückholung</span></a>
        <div class="d-none d-lg-flex align-items-center gap-1">
            <a href="#wie-es-funktioniert" class="nav-link-v2">Wie es funktioniert</a>
            <a href="#betrugsarten"        class="nav-link-v2">Betrugsarten</a>
            <a href="#erfahrungsberichte"  class="nav-link-v2">Erfahrungen</a>
            <a href="#kontakt"             class="nav-link-v2">Kontakt</a>
        </div>
        <a href="#" class="btn-cta-nav" data-bs-toggle="modal" data-bs-target="#fullFormModal">
            <i class="bi bi-shield-check me-1"></i>Kostenlos prüfen
        </a>
    </div>
</nav>

<!-- ===== HERO ===== -->
<section class="hero-v2" id="hero">
    <!-- Animated AI neural-network canvas background -->
    <canvas id="heroCanvas" aria-hidden="true"></canvas>

    <!-- Decorative shield (fund recovery imagery) -->
    <div class="hero-shield-wrap" aria-hidden="true">
        <svg viewBox="0 0 400 480" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="sg" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#f5a623"/>
                    <stop offset="100%" stop-color="#0d6efd"/>
                </linearGradient>
            </defs>
            <!-- Shield outline -->
            <path d="M200 20 L360 80 L360 220 C360 330 280 420 200 460 C120 420 40 330 40 220 L40 80 Z"
                  fill="none" stroke="url(#sg)" stroke-width="6" stroke-linejoin="round"/>
            <!-- Inner shield -->
            <path d="M200 60 L320 110 L320 220 C320 305 260 380 200 420 C140 380 80 305 80 220 L80 110 Z"
                  fill="rgba(255,255,255,0.03)" stroke="rgba(245,166,35,0.4)" stroke-width="2"/>
            <!-- Scales of justice -->
            <line x1="200" y1="150" x2="200" y2="340" stroke="rgba(255,255,255,0.5)" stroke-width="3"/>
            <line x1="120" y1="200" x2="280" y2="200" stroke="rgba(255,255,255,0.5)" stroke-width="3"/>
            <!-- Left scale pan -->
            <path d="M120 200 L100 250 L140 250 Z" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="2"/>
            <ellipse cx="120" cy="253" rx="25" ry="8" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="2"/>
            <!-- Right scale pan -->
            <path d="M280 200 L260 240 L300 240 Z" fill="none" stroke="rgba(245,166,35,0.5)" stroke-width="2"/>
            <ellipse cx="280" cy="243" rx="25" ry="8" fill="none" stroke="rgba(245,166,35,0.5)" stroke-width="2"/>
            <!-- Euro symbol in center -->
            <text x="200" y="300" text-anchor="middle" fill="rgba(245,166,35,0.6)"
                  font-size="60" font-weight="900" font-family="Arial">€</text>
            <!-- Check mark at top of shield -->
            <polyline points="170,160 192,185 235,140" fill="none"
                      stroke="rgba(34,197,94,0.7)" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>

    <!-- Floating stats pills -->
    <div class="stat-pill" style="top:22%;right:38%">
        <i class="bi bi-check-circle-fill text-success"></i>
        <span class="val">87%</span> Erfolgsquote
    </div>
    <div class="stat-pill" style="top:42%;right:52%">
        <i class="bi bi-currency-euro text-warning"></i>
        <span class="val">€48M+</span> zurückgefordert
    </div>
    <div class="stat-pill" style="top:62%;right:40%">
        <span class="live-dot"></span>
        <span class="val">2.400+</span> Fälle geprüft
    </div>

    <div class="container position-relative z-1">
        <div class="row align-items-center g-5">

            <!-- Left: Copy -->
            <div class="col-lg-6" data-aos="fade-right" data-aos-duration="800">
                <div class="hero-badge">
                    <span class="live-dot"></span>
                    KI-System aktiv · Analyse bereit
                </div>
                <h1 class="hero-headline mb-4">
                    Ihr <span class="highlight">verlorenes Kapital</span><br>
                    kann zurückgeholt werden.
                </h1>
                <p class="hero-sub mb-4">
                    Unser KI-gestütztes System analysiert Betrugsstrukturen, verfolgt
                    Transaktionsketten und entwickelt individuelle Rückforderungsstrategien –
                    kostenlos, unverbindlich und erfolgsorientiert.
                </p>
                <div class="trust-row">
                    <div class="trust-item"><i class="bi bi-shield-lock-fill"></i>DSGVO-konform</div>
                    <div class="trust-item"><i class="bi bi-trophy-fill"></i>87% Erfolgsquote</div>
                    <div class="trust-item"><i class="bi bi-currency-euro"></i>Keine Vorauszahlung</div>
                    <div class="trust-item"><i class="bi bi-lightning-charge-fill"></i>Antwort in 48h</div>
                </div>
                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="#" class="btn btn-warning btn-lg fw-bold px-4 py-3"
                       style="border-radius:12px;box-shadow:0 8px 24px rgba(245,166,35,.4);"
                       data-bs-toggle="modal" data-bs-target="#fullFormModal">
                        <i class="bi bi-shield-check me-2"></i>Kostenlose Erstprüfung
                    </a>
                    <a href="#" class="btn btn-outline-light btn-lg px-4 py-3"
                       style="border-radius:12px;border-color:rgba(255,255,255,.3);"
                       data-bs-toggle="modal" data-bs-target="#infoModalV2">
                        <i class="bi bi-play-circle me-2"></i>Wie es funktioniert
                    </a>
                </div>
            </div>

            <!-- Right: Quick form card -->
            <div class="col-lg-6" data-aos="fade-left" data-aos-duration="800" data-aos-delay="150">
                <div class="hero-form-card">
                    <div class="form-card-header">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="live-dot"></span>
                            <span class="small fw-semibold" style="color:rgba(255,255,255,.8)">KI-System aktiv</span>
                        </div>
                        <h5 class="fw-bold mb-0">Kostenlose KI-Fallprüfung</h5>
                        <p class="small mb-0" style="color:rgba(255,255,255,.7)">Unverbindlich · 100% kostenlos · Antwort in 48h</p>
                    </div>

                    <?php if ($success): ?>
                    <div class="alert alert-success d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-check-circle-fill flex-shrink-0 mt-1"></i>
                        <div><strong>Vielen Dank!</strong> Ihr Fall wurde eingereicht. Wir melden uns innerhalb von 48 Stunden.</div>
                    </div>
                    <?php elseif ($error): ?>
                    <div class="alert alert-danger d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                        <div><strong>Fehler:</strong> <?= $error ?></div>
                    </div>
                    <?php endif; ?>

                    <form action="submit_lead.php" method="POST" id="heroForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="visit_id" id="visitIdHeroForm" value="">
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="text" name="first_name" class="form-control" placeholder="Vorname *" required>
                                <div class="invalid-feedback">Pflichtfeld</div>
                            </div>
                            <div class="col-6">
                                <input type="text" name="last_name" class="form-control" placeholder="Nachname *" required>
                                <div class="invalid-feedback">Pflichtfeld</div>
                            </div>
                            <div class="col-12">
                                <input type="email" name="email" class="form-control" placeholder="E-Mail-Adresse *" required>
                                <div class="invalid-feedback">Gültige E-Mail eingeben</div>
                            </div>
                            <div class="col-12">
                                <input type="tel" name="phone" class="form-control" placeholder="Telefonnummer (optional)">
                            </div>
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text">€</span>
                                    <input type="number" name="amount_lost" class="form-control" placeholder="Verlorener Betrag *" min="1" required>
                                </div>
                                <div class="invalid-feedback">Pflichtfeld</div>
                            </div>
                            <div class="col-md-6">
                                <select name="platform_category" class="form-select" required>
                                    <option value="">Betrugsart *</option>
                                    <option value="Krypto-Betrug">₿ Krypto-Betrug</option>
                                    <option value="Forex-Betrug">📈 Forex-Betrug</option>
                                    <option value="Fake-Broker">🏢 Fake-Broker</option>
                                    <option value="Romance-Scam mit Investitionsbetrug">💔 Romance-Scam</option>
                                    <option value="Binäre Optionen">📊 Binäre Optionen</option>
                                    <option value="Andere">❓ Andere</option>
                                </select>
                                <div class="invalid-feedback">Pflichtfeld</div>
                            </div>
                            <div class="col-12">
                                <select name="country" class="form-select" required>
                                    <option value="">Land auswählen *</option>
                                    <option value="Deutschland">🇩🇪 Deutschland</option>
                                    <option value="Österreich">🇦🇹 Österreich</option>
                                    <option value="Schweiz">🇨🇭 Schweiz</option>
                                    <option value="USA">🇺🇸 USA</option>
                                    <option value="Vereinigtes Königreich">🇬🇧 Vereinigtes Königreich</option>
                                    <option value="Andere">🌍 Anderes Land</option>
                                </select>
                                <div class="invalid-feedback">Pflichtfeld</div>
                            </div>
                            <div class="col-12">
                                <textarea name="case_description" class="form-control" rows="3" required
                                          placeholder="Kurze Beschreibung: Wie haben Sie die Plattform gefunden? Seit wann kein Zugriff?"></textarea>
                                <div class="invalid-feedback">Pflichtfeld</div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="heroPrivacy" required>
                                    <label class="form-check-label small text-muted" for="heroPrivacy">
                                        Ich stimme der <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModalV2">Datenschutzerklärung</a> zu *
                                    </label>
                                    <div class="invalid-feedback">Bitte akzeptieren.</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold"
                                        style="border-radius:10px;padding:.85rem;">
                                    <i class="bi bi-search me-2"></i>Fall jetzt kostenlos einreichen
                                </button>
                                <p class="text-center text-muted small mt-2 mb-0">
                                    <i class="bi bi-lock-fill text-success me-1"></i>
                                    SSL-verschlüsselt · Keine Kosten · Vertraulich
                                </p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== ENHANCED STATS BAND ===== -->
<div class="stats-band-v3 position-relative">
    <div class="container position-relative z-1">
        <div class="row align-items-center g-4 justify-content-center">
            <!-- Ring: 87% success -->
            <div class="col-6 col-md-3" data-aos="zoom-in">
                <div class="ring-wrap" id="ringWrap">
                    <svg class="ring-svg" viewBox="0 0 130 130" width="130" height="130">
                        <circle class="ring-track" cx="65" cy="65" r="58"/>
                        <circle class="ring-fill" id="ringFill" cx="65" cy="65" r="58"/>
                    </svg>
                    <div class="ring-label">
                        <span id="ctr87">87</span>%
                        <small>Erfolgsquote</small>
                    </div>
                </div>
            </div>
            <!-- Divider -->
            <div class="col-1 d-none d-md-block">
                <div class="stat-divider mx-auto" style="height:80px;background:rgba(255,255,255,.15)"></div>
            </div>
            <!-- €48M+ -->
            <div class="col-6 col-md-2" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-v3">
                    <div class="n">€48M+</div>
                    <div class="l">für Mandanten zurückgefordert</div>
                </div>
            </div>
            <div class="col-1 d-none d-md-block">
                <div class="stat-divider mx-auto" style="height:80px;background:rgba(255,255,255,.15)"></div>
            </div>
            <!-- 2400+ cases -->
            <div class="col-6 col-md-2" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-v3">
                    <div class="n"><span data-counter="2400">0</span>+</div>
                    <div class="l">erfolgreich geprüfte Fälle</div>
                </div>
            </div>
            <div class="col-1 d-none d-md-block">
                <div class="stat-divider mx-auto" style="height:80px;background:rgba(255,255,255,.15)"></div>
            </div>
            <!-- 18+ countries -->
            <div class="col-6 col-md-2" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-v3">
                    <div class="n">18+</div>
                    <div class="l">Länder · internationale Experten</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== TRUST BANNER ===== -->
<div class="trust-banner py-3">
    <div class="container">
        <div class="row g-3 text-center text-md-start align-items-center">
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2">
                    <i class="bi bi-award-fill text-warning fs-4"></i>
                    <div>
                        <div class="fw-semibold">87% Erfolgsquote</div>
                        <div class="small text-muted">Nachgewiesene Ergebnisse</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2">
                    <i class="bi bi-lock-fill text-warning fs-4"></i>
                    <div>
                        <div class="fw-semibold">Sichere Verarbeitung</div>
                        <div class="small text-muted">SSL-verschlüsselt</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2">
                    <i class="bi bi-chat-dots-fill text-warning fs-4"></i>
                    <div>
                        <div class="fw-semibold">Kostenlose Beratung</div>
                        <div class="small text-muted">Keine Vorauszahlung</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2">
                    <i class="bi bi-cpu-fill text-warning fs-4"></i>
                    <div>
                        <div class="fw-semibold">KI-Technologie</div>
                        <div class="small text-muted">Modernste Analyse</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== NEWS TICKER ===== -->
<div class="news-ticker-bar">
    <div class="news-ticker-label">
        <i class="bi bi-broadcast-pin me-1"></i>Aktuell
    </div>
    <div class="news-ticker-wrap">
        <div class="news-ticker-inner" id="newsTicker2">
            <span>🏆 VerlustRückholung-Erfolg: Familie aus Berlin erhält €42.000 nach Krypto-Betrug zurück &nbsp;|&nbsp;</span>
            <span>🔍 KI-Analyse identifiziert neue Betrugsplattform – 127 Mandanten erhalten Rückerstattung &nbsp;|&nbsp;</span>
            <span>⚡ Durchbruch im Forex-Fall: €89.000 nach nur 6 Wochen vollständig rückgefordert &nbsp;|&nbsp;</span>
            <span>🛡️ Neue Fake-Broker-Welle: VerlustRückholung warnt und schützt Anleger europaweit &nbsp;|&nbsp;</span>
            <span>✅ 34 Romance-Scam-Opfer: KI-Rückverfolgung deckt internationales Betrugsnetzwerk auf &nbsp;|&nbsp;</span>
            <span>📊 Quartalbericht: VerlustRückholung steigert Erfolgsquote auf branchenführende 87% &nbsp;|&nbsp;</span>
            <span>💼 Neuer Meilenstein: Über €48 Millionen für unsere Mandanten zurückgefordert &nbsp;|&nbsp;</span>
        </div>
    </div>
</div>

<!-- ===== AI ANALYSIS DEMO SECTION ===== -->
<section class="ai-demo-section" id="ki-analyse">
    <div class="container position-relative z-1">
        <div class="row align-items-center g-5">

            <!-- Left: Animated AI Terminal -->
            <div class="col-lg-6" data-aos="fade-right" data-aos-duration="900">
                <div class="ai-terminal scan-wrap">
                    <div class="ai-terminal-bar">
                        <div class="ai-dot" style="background:#ff5f56"></div>
                        <div class="ai-dot" style="background:#ffbd2e"></div>
                        <div class="ai-dot" style="background:#27c93f"></div>
                        <span class="ms-2 small font-monospace" style="color:rgba(255,255,255,.45);">verlust-ki-engine v2.4.1</span>
                        <span class="ms-auto"><span class="live-dot"></span></span>
                    </div>
                    <div class="ai-terminal-body" id="aiTerminal">
                        <div class="scan-line"></div>
                        <span class="t-muted"># Initialisiere KI-Analyse-Pipeline...<br></span>
                    </div>
                </div>
            </div>

            <!-- Right: Capabilities + 3D mini stats -->
            <div class="col-lg-6" data-aos="fade-left" data-aos-duration="900" data-aos-delay="100">
                <div class="section-eyebrow" style="color:var(--accent);">KI-Technologie</div>
                <h2 class="fw-bold display-6 text-white mb-3">
                    KI-gestützte<br>
                    <span class="glow-gold" style="color:var(--accent);">Transaktionsverfolgung</span>
                </h2>
                <p style="color:rgba(255,255,255,.65);line-height:1.8;" class="mb-4">
                    Unser proprietäres Modell wurde auf über 150.000 Betrugsfällen trainiert.
                    Es erkennt auch hochkomplexe, verschachtelte Betrugsstrukturen mit einer
                    Genauigkeit von mehr als 94%.
                </p>

                <?php $ai_skills = [
                    ['label' => 'Transaktionsanalyse',            'pct' => 94],
                    ['label' => 'Betrugsmusters-Erkennung',        'pct' => 91],
                    ['label' => 'Blockchain-Forensik',             'pct' => 88],
                    ['label' => 'Rückforderungs-Wahrscheinlichkeit','pct' => 87],
                ]; ?>
                <?php foreach ($ai_skills as $sk): ?>
                <div class="ai-progress-wrap">
                    <div class="d-flex justify-content-between">
                        <span class="small fw-semibold" style="color:rgba(255,255,255,.8)"><?= $sk['label'] ?></span>
                        <span class="small fw-bold" style="color:var(--accent)"><?= $sk['pct'] ?>%</span>
                    </div>
                    <div class="ai-progress-track">
                        <div class="ai-progress-fill" style="--pct:<?= $sk['pct'] ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- 3D floating mini-stat cards -->
                <div class="d-flex gap-3 mt-4">
                    <div class="float-card flex-fill text-center">
                        <div class="fw-bold fs-4" style="color:var(--accent)">94%</div>
                        <div class="small" style="color:rgba(255,255,255,.55)">Erkennungsrate</div>
                    </div>
                    <div class="float-card flex-fill text-center">
                        <div class="fw-bold fs-4" style="color:var(--accent)">48h</div>
                        <div class="small" style="color:rgba(255,255,255,.55)">Analyse-Zeit</div>
                    </div>
                    <div class="float-card flex-fill text-center">
                        <div class="fw-bold fs-4" style="color:var(--accent)">150k+</div>
                        <div class="small" style="color:rgba(255,255,255,.55)">Trainingsfälle</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== AI 3D VISUALIZATION SECTION ===== -->
<section id="ai-visual-3d" class="ai-section-3d py-6">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-warning text-dark rounded-pill px-3 py-2 mb-3">KI-Technologie</span>
            <h2 class="display-6 fw-bold text-white">
                Unser KI-Algorithmus <span class="text-warning">arbeitet für Sie</span>
            </h2>
            <p class="text-white-50 col-lg-7 mx-auto">
                In Echtzeit analysiert unser proprietärer Algorithmus Millionen von Transaktionsdaten,
                rekonstruiert Zahlungsströme und identifiziert die Verantwortlichen hinter Betrugsplattformen.
            </p>
        </div>
        <div class="row align-items-center g-5">
            <div class="col-lg-6 order-lg-2" data-aos="fade-left">
                <div class="ai-canvas-wrap">
                    <canvas id="aiNetworkCanvas2"></canvas>
                    <div class="ai-canvas-hud">
                        <div class="ai-hud-row">
                            <span class="ai-hud-label">Knoten analysiert</span>
                            <span class="ai-hud-val text-warning" id="aiNodes2">0</span>
                        </div>
                        <div class="ai-hud-row">
                            <span class="ai-hud-label">Verbindungen geprüft</span>
                            <span class="ai-hud-val text-info" id="aiEdges2">0</span>
                        </div>
                        <div class="ai-hud-row">
                            <span class="ai-hud-label">Betrug erkannt</span>
                            <span class="ai-hud-val text-danger" id="aiScams2">0</span>
                        </div>
                        <div class="ai-hud-row">
                            <span class="ai-hud-label">Kapital gesichert</span>
                            <span class="ai-hud-val text-success" id="aiRecovered2">€0</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 order-lg-1" data-aos="fade-right">
                <div class="ai-steps-3d">
                    <div class="ai-step-item-3d active" id="aiStep1b">
                        <div class="ai-step-icon-3d bg-primary">
                            <i class="bi bi-cloud-upload text-white"></i>
                        </div>
                        <div class="ai-step-content">
                            <h6 class="fw-bold text-white mb-1">01 · Dateneingabe &amp; Verarbeitung</h6>
                            <p class="text-white-50 small mb-0">
                                Ihre Falldaten werden sicher erfasst und in unser verschlüsseltes
                                KI-System eingespeist. Der Algorithmus beginnt sofort mit der Analyse.
                            </p>
                        </div>
                    </div>
                    <div class="ai-step-item-3d" id="aiStep2b">
                        <div class="ai-step-icon-3d bg-warning">
                            <i class="bi bi-diagram-3 text-dark"></i>
                        </div>
                        <div class="ai-step-content">
                            <h6 class="fw-bold text-white mb-1">02 · Transaktionsgraph-Analyse</h6>
                            <p class="text-white-50 small mb-0">
                                Unsere KI erstellt einen vollständigen Blockchain-Transaktionsgraphen
                                und verknüpft alle relevanten Wallet-Adressen und Zahlungsströme.
                            </p>
                        </div>
                    </div>
                    <div class="ai-step-item-3d" id="aiStep3b">
                        <div class="ai-step-icon-3d bg-danger">
                            <i class="bi bi-shield-exclamation text-white"></i>
                        </div>
                        <div class="ai-step-content">
                            <h6 class="fw-bold text-white mb-1">03 · Betrugsstruktur-Erkennung</h6>
                            <p class="text-white-50 small mb-0">
                                Durch maschinelles Lernen erkennt unser System bekannte Betrugsmuster
                                und identifiziert die verantwortlichen Akteure im Netzwerk.
                            </p>
                        </div>
                    </div>
                    <div class="ai-step-item-3d" id="aiStep4b">
                        <div class="ai-step-icon-3d bg-success">
                            <i class="bi bi-cash-coin text-white"></i>
                        </div>
                        <div class="ai-step-content">
                            <h6 class="fw-bold text-white mb-1">04 · Rückforderungsplan &amp; Umsetzung</h6>
                            <p class="text-white-50 small mb-0">
                                Sie erhalten einen individualisierten Aktionsplan. Unser Team begleitet
                                Sie Schritt für Schritt durch den gesamten Rückforderungsprozess.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="btn btn-warning btn-lg fw-bold px-5"
                            data-bs-toggle="modal" data-bs-target="#fullFormModal">
                        <i class="bi bi-arrow-right-circle me-2"></i>Jetzt kostenlos starten
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section id="wie-es-funktioniert" class="py-6 bg-white">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="section-eyebrow">Unser Prozess</div>
            <h2 class="fw-bold display-6">In 4 Schritten zur Rückforderung</h2>
            <p class="text-muted">Transparent, strukturiert und professionell begleitet.</p>
        </div>
        <div class="row g-4">
            <?php $steps = [
                ['num'=>'01','icon'=>'bi-file-earmark-text','title'=>'Fall einreichen',
                 'desc'=>'Schildern Sie Ihren Fall im sicheren Formular. Die Einreichung ist kostenlos, unverbindlich und dauert nur wenige Minuten.'],
                ['num'=>'02','icon'=>'bi-cpu','title'=>'KI-Analyse',
                 'desc'=>'Unser KI-System analysiert Transaktionsmuster, verknüpft Daten mit bekannten Betrugsstrukturen und erkennt Rückforderungspotenzial.'],
                ['num'=>'03','icon'=>'bi-people','title'=>'Expertenprüfung',
                 'desc'=>'Unsere Rechts- und Finanzexperten prüfen den Analysebericht und kontaktieren Sie persönlich innerhalb von 48 Stunden.'],
                ['num'=>'04','icon'=>'bi-currency-euro','title'=>'Rückforderung',
                 'desc'=>'Gemeinsam entwickeln wir eine individuelle Strategie – und erst wenn wir Ihnen erfolgreich helfen, entstehen Kosten.'],
            ]; ?>
            <?php foreach ($steps as $i => $step): ?>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>">
                <div class="process-step">
                    <div class="step-number"><?= $step['num'] ?></div>
                    <?php if ($i < 3): ?><div class="step-connector"><i class="bi bi-arrow-right"></i></div><?php endif; ?>
                    <i class="bi <?= $step['icon'] ?> fs-3 text-primary mb-2 d-block"></i>
                    <h5 class="fw-bold"><?= $step['title'] ?></h5>
                    <p class="text-muted small mb-0"><?= $step['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== LEISTUNGEN SECTION ===== -->
<section id="leistungen" class="py-6">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 mb-3">Unsere Leistungen</span>
                <h2 class="display-6 fw-bold mb-4">
                    Wie unsere <span class="text-primary">KI-Technologie</span> Ihnen hilft
                </h2>
                <p class="text-muted mb-4">
                    Unser fortschrittliches KI-System wurde speziell entwickelt, um betrügerische
                    Transaktionsnetzwerke zu identifizieren und aufzudecken. Durch die Analyse
                    komplexer Zahlungsströme können wir Verbindungen zu bekannten Betrugsplattformen
                    aufdecken und Ihnen dabei helfen, Ihr verlorenes Kapital zurückzufordern.
                </p>
                <div class="row g-3">
                    <div class="col-12">
                        <div class="d-flex gap-3 p-3 bg-light rounded-3">
                            <div class="bg-primary rounded-3 p-2 flex-shrink-0">
                                <i class="bi bi-graph-up-arrow text-white fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Transaktionsanalyse</h6>
                                <p class="text-muted small mb-0">
                                    Unsere KI analysiert verdächtige Transaktionsmuster und identifiziert
                                    Verbindungen zu bekannten Betrugsstrukturen.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-3 p-3 bg-light rounded-3">
                            <div class="bg-warning rounded-3 p-2 flex-shrink-0">
                                <i class="bi bi-search text-dark fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Plattformidentifikation</h6>
                                <p class="text-muted small mb-0">
                                    Wir identifizieren die verantwortlichen Betreiberstrukturen hinter
                                    den Betrugsplattformen durch forensische Blockchain-Analyse.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-3 p-3 bg-light rounded-3">
                            <div class="bg-success rounded-3 p-2 flex-shrink-0">
                                <i class="bi bi-cash-coin text-white fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Kapitalrückforderung</h6>
                                <p class="text-muted small mb-0">
                                    Auf Basis unserer Analyse unterstützen wir Sie bei der Geltendmachung
                                    Ihrer Rückforderungsansprüche gegenüber zuständigen Stellen.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <div class="position-relative">
                    <div class="bg-primary rounded-4 p-4 text-white mb-3">
                        <h5 class="fw-bold mb-3"><i class="bi bi-cpu me-2"></i>KI-Analysemodell</h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="bg-white bg-opacity-10 rounded-3 p-3 text-center">
                                    <i class="bi bi-diagram-3 fs-2 text-warning mb-2 d-block"></i>
                                    <div class="small">Netzwerkanalyse</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-white bg-opacity-10 rounded-3 p-3 text-center">
                                    <i class="bi bi-currency-bitcoin fs-2 text-warning mb-2 d-block"></i>
                                    <div class="small">Blockchain-Forensik</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-white bg-opacity-10 rounded-3 p-3 text-center">
                                    <i class="bi bi-fingerprint fs-2 text-warning mb-2 d-block"></i>
                                    <div class="small">Betrugsidentifikation</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-white bg-opacity-10 rounded-3 p-3 text-center">
                                    <i class="bi bi-file-earmark-text fs-2 text-warning mb-2 d-block"></i>
                                    <div class="small">Fallbericht</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="step-timeline">
                        <div class="step-item-timeline">
                            <div class="step-num">1</div>
                            <div>
                                <strong>Falleinreichung</strong>
                                <p class="small text-muted mb-0">Sie schildern Ihren Fall kostenlos über unser Formular</p>
                            </div>
                        </div>
                        <div class="step-item-timeline">
                            <div class="step-num">2</div>
                            <div>
                                <strong>KI-Analyse</strong>
                                <p class="small text-muted mb-0">Unser System analysiert Transaktionen und Plattformstrukturen</p>
                            </div>
                        </div>
                        <div class="step-item-timeline">
                            <div class="step-num">3</div>
                            <div>
                                <strong>Ergebnisbericht</strong>
                                <p class="small text-muted mb-0">Sie erhalten einen detaillierten Bericht mit Handlungsempfehlungen</p>
                            </div>
                        </div>
                        <div class="step-item-timeline">
                            <div class="step-num">4</div>
                            <div>
                                <strong>Rückforderung</strong>
                                <p class="small text-muted mb-0">Wir begleiten Sie durch den gesamten Rückforderungsprozess</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== FRAUD TYPES ===== -->
<section id="betrugsarten" class="py-6" style="background:var(--bg-light)">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="badge bg-danger text-white rounded-pill px-3 py-2 mb-3">Betrugsarten</span>
            <h2 class="display-6 fw-bold">Welche Betrugsformen wir aufdecken</h2>
            <p class="text-muted col-lg-6 mx-auto">
                Unser System ist spezialisiert auf die Identifikation verschiedener
                Betrugsplattformen und -strukturen.
            </p>
        </div>
        <div class="row g-3">
            <div class="col-md-6 col-lg-4" data-aos="zoom-in" data-aos-delay="100">
                <div class="scam-card p-4 rounded-4 border h-100 cursor-pointer"
                     data-bs-toggle="modal" data-bs-target="#scamModalV2"
                     data-scam-type="Krypto-Betrug"
                     data-scam-desc="Betrügerische Kryptowährungs-Investitionsplattformen versprechen überdurchschnittliche Renditen und locken Anleger mit gefälschten Trading-Ergebnissen. Oft werden auch bekannte Persönlichkeiten als angebliche Befürworter missbraucht. Unsere KI identifiziert die Wallet-Adressen und Transaktionsflüsse dieser Plattformen.">
                    <div class="d-flex align-items-center mb-3">
                        <div class="scam-icon bg-warning-subtle rounded-3 p-2 me-3">
                            <i class="bi bi-currency-bitcoin text-warning fs-4"></i>
                        </div>
                        <h6 class="fw-bold mb-0">Krypto-Betrug</h6>
                    </div>
                    <p class="text-muted small mb-2">
                        Fake-Krypto-Investitionsplattformen, gefälschte Exchanges und Rug-Pull-Projekte.
                    </p>
                    <span class="badge bg-warning-subtle text-warning">Mehr erfahren →</span>
                </div>
            </div>
            <div class="col-md-6 col-lg-4" data-aos="zoom-in" data-aos-delay="200">
                <div class="scam-card p-4 rounded-4 border h-100 cursor-pointer"
                     data-bs-toggle="modal" data-bs-target="#scamModalV2"
                     data-scam-type="Forex-Betrug"
                     data-scam-desc="Betrügerische Forex-Broker manipulieren Kurse, verweigern Auszahlungen und arbeiten ohne gültige Regulierung. Unser System verknüpft Zahlungsströme mit bekannten Betrugsstrukturen und identifiziert die Verantwortlichen hinter diesen Plattformen.">
                    <div class="d-flex align-items-center mb-3">
                        <div class="scam-icon bg-primary-subtle rounded-3 p-2 me-3">
                            <i class="bi bi-currency-exchange text-primary fs-4"></i>
                        </div>
                        <h6 class="fw-bold mb-0">Forex-Betrug</h6>
                    </div>
                    <p class="text-muted small mb-2">
                        Unregulierte Forex-Broker, Kursmanipulation und verweigerte Auszahlungen.
                    </p>
                    <span class="badge bg-primary-subtle text-primary">Mehr erfahren →</span>
                </div>
            </div>
            <div class="col-md-6 col-lg-4" data-aos="zoom-in" data-aos-delay="300">
                <div class="scam-card p-4 rounded-4 border h-100 cursor-pointer"
                     data-bs-toggle="modal" data-bs-target="#scamModalV2"
                     data-scam-type="Fake-Broker"
                     data-scam-desc="Betrügerische Investment-Broker täuschen mit professionellen Websites und gefälschten Regulierungsnachweisen. Sie kassieren Einlagen, zahlen aber nie Gewinne aus. Unsere KI analysiert die Registrierungsdaten, Zahlungsstrukturen und Verbindungen dieser Entitäten.">
                    <div class="d-flex align-items-center mb-3">
                        <div class="scam-icon bg-danger-subtle rounded-3 p-2 me-3">
                            <i class="bi bi-building-x text-danger fs-4"></i>
                        </div>
                        <h6 class="fw-bold mb-0">Fake Investment-Broker</h6>
                    </div>
                    <p class="text-muted small mb-2">
                        Gefälschte Lizenzen, manipulierte Handelsplattformen und betrügerische Berater.
                    </p>
                    <span class="badge bg-danger-subtle text-danger">Mehr erfahren →</span>
                </div>
            </div>
            <div class="col-md-6 col-lg-4" data-aos="zoom-in" data-aos-delay="400">
                <div class="scam-card p-4 rounded-4 border h-100 cursor-pointer"
                     data-bs-toggle="modal" data-bs-target="#scamModalV2"
                     data-scam-type="Romance-Scam"
                     data-scam-desc="Bei Romance-Scams werden emotionale Beziehungen aufgebaut, um Vertrauen zu gewinnen – anschließend werden Opfer zu Investitionen überredet. Diese Kombination aus emotionaler Manipulation und Investitionsbetrug ist besonders perfide. Wir unterstützen Opfer diskret bei der Aufarbeitung und Rückforderung.">
                    <div class="d-flex align-items-center mb-3">
                        <div class="scam-icon bg-pink-subtle rounded-3 p-2 me-3">
                            <i class="bi bi-heart-arrow text-danger fs-4"></i>
                        </div>
                        <h6 class="fw-bold mb-0">Romance-Scam &amp; Investment</h6>
                    </div>
                    <p class="text-muted small mb-2">
                        Emotionale Manipulation kombiniert mit gefälschten Investmentangeboten.
                    </p>
                    <span class="badge bg-danger-subtle text-danger">Mehr erfahren →</span>
                </div>
            </div>
            <div class="col-md-6 col-lg-4" data-aos="zoom-in" data-aos-delay="500">
                <div class="scam-card p-4 rounded-4 border h-100 cursor-pointer"
                     data-bs-toggle="modal" data-bs-target="#scamModalV2"
                     data-scam-type="Binäre Optionen"
                     data-scam-desc="Binäre Optionen und Online-Trading-Plattformen sind oft als Wettangebote mit manipulierten Kursen strukturiert. Verluste sind systembedingt einprogrammiert. Unsere Analyse deckt diese Strukturen auf und identifiziert verantwortliche Betreiber.">
                    <div class="d-flex align-items-center mb-3">
                        <div class="scam-icon bg-success-subtle rounded-3 p-2 me-3">
                            <i class="bi bi-bar-chart-line text-success fs-4"></i>
                        </div>
                        <h6 class="fw-bold mb-0">Binäre Optionen</h6>
                    </div>
                    <p class="text-muted small mb-2">
                        Manipulierte Online-Trading-Plattformen und Binäre-Optionen-Betrug.
                    </p>
                    <span class="badge bg-success-subtle text-success">Mehr erfahren →</span>
                </div>
            </div>
            <div class="col-md-6 col-lg-4" data-aos="zoom-in" data-aos-delay="600">
                <div class="scam-card p-4 rounded-4 border h-100 cursor-pointer"
                     data-bs-toggle="modal" data-bs-target="#scamModalV2"
                     data-scam-type="Sonstige Anlagebetrug"
                     data-scam-desc="Weitere Betrugsformen im Anlagebereich umfassen Ponzi-Systeme, Multi-Level-Marketing-Betrug, gefälschte ICOs und andere Kapitalanlage-Betrügereien. Sprechen Sie uns an – wir prüfen jeden Fall individuell.">
                    <div class="d-flex align-items-center mb-3">
                        <div class="scam-icon bg-secondary-subtle rounded-3 p-2 me-3">
                            <i class="bi bi-question-circle text-secondary fs-4"></i>
                        </div>
                        <h6 class="fw-bold mb-0">Weitere Betrugsarten</h6>
                    </div>
                    <p class="text-muted small mb-2">
                        Ponzi-Systeme, MLM-Betrug, gefälschte ICOs und andere Anlagebetrügereien.
                    </p>
                    <span class="badge bg-secondary-subtle text-secondary">Mehr erfahren →</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== LIVE RECOVERY TICKER ===== -->
<div class="ticker-wrap">
    <div class="ticker-track" id="tickerTrack">
        <?php
        $ticks = [
            ['name'=>'M.K., München',  'amount'=>'€47.200', 'type'=>'Krypto-Betrug'],
            ['name'=>'S.F., Wien',     'amount'=>'€28.900', 'type'=>'Forex-Betrug'],
            ['name'=>'T.L., Zürich',   'amount'=>'€91.500', 'type'=>'Fake-Broker'],
            ['name'=>'A.R., Berlin',   'amount'=>'€15.800', 'type'=>'Romance-Scam'],
            ['name'=>'K.M., Hamburg',  'amount'=>'€62.000', 'type'=>'Krypto-Betrug'],
            ['name'=>'P.S., Köln',     'amount'=>'€34.100', 'type'=>'Forex-Betrug'],
            ['name'=>'I.B., Frankfurt','amount'=>'€78.300', 'type'=>'Fake-Broker'],
            ['name'=>'J.W., Stuttgart','amount'=>'€22.600', 'type'=>'Binäre Optionen'],
        ];
        // Duplicate for seamless loop
        foreach (array_merge($ticks, $ticks) as $t):
        ?>
        <div class="ticker-item">
            <span class="t-badge-ok">✓ Zurückgeholt</span>
            <span><?= htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="t-amount"><?= htmlspecialchars($t['amount'], ENT_QUOTES, 'UTF-8') ?></span>
            <span style="color:rgba(255,255,255,.3);font-size:.7rem"><?= htmlspecialchars($t['type'], ENT_QUOTES, 'UTF-8') ?></span>
            <span style="color:rgba(255,255,255,.12);margin:0 .75rem">|</span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ===== FEATURES / WHY US ===== -->
<section class="py-6 bg-white">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5" data-aos="fade-right">
                <div class="section-eyebrow">Unsere Stärken</div>
                <h2 class="fw-bold display-6 mb-4">Warum VerlustRückholung?</h2>
                <p class="text-muted">
                    Wir kombinieren modernste KI-Technologie mit erfahrenen Rechts- und Finanzexperten,
                    um Ihnen die bestmögliche Chance auf Kapitalrückholung zu geben.
                </p>
                <div class="mt-4">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="flex-shrink-0 text-success fs-5"><i class="bi bi-check-circle-fill"></i></div>
                        <div><strong>Erfolgsbasierte Vergütung</strong> – Sie zahlen nur, wenn wir Ihnen erfolgreich helfen.</div>
                    </div>
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="flex-shrink-0 text-success fs-5"><i class="bi bi-check-circle-fill"></i></div>
                        <div><strong>Internationale Reichweite</strong> – Tätig in 18+ Ländern mit lokalen Partnerkanzleien.</div>
                    </div>
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="flex-shrink-0 text-success fs-5"><i class="bi bi-check-circle-fill"></i></div>
                        <div><strong>Volle Transparenz</strong> – Sie werden über jeden Schritt informiert.</div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-shrink-0 text-success fs-5"><i class="bi bi-check-circle-fill"></i></div>
                        <div><strong>DSGVO-konform</strong> – Ihre Daten sind bei uns sicher und geschützt.</div>
                    </div>
                </div>
                <a href="#" class="btn btn-primary btn-lg mt-4" style="border-radius:12px;"
                   data-bs-toggle="modal" data-bs-target="#fullFormModal">
                    <i class="bi bi-arrow-right me-1"></i>Jetzt Fall prüfen
                </a>
            </div>
            <div class="col-lg-7" data-aos="fade-left">
                <div class="row g-3">
                    <?php $features = [
                        ['icon'=>'bi-cpu-fill','title'=>'KI-Transaktionsanalyse','desc'=>'Unser Modell ist auf tausenden Betrugsmuster trainiert und erkennt komplexe Strukturen.'],
                        ['icon'=>'bi-diagram-3-fill','title'=>'Blockchain-Forensik','desc'=>'Verfolgung von Krypto-Transaktionen über mehrere Wallets und Exchanges hinweg.'],
                        ['icon'=>'bi-globe2','title'=>'Internationale Verfolgung','desc'=>'Netzwerk aus Anwälten, Behörden und Strafverfolgern in 18+ Ländern.'],
                        ['icon'=>'bi-shield-check','title'=>'Rechtliche Absicherung','desc'=>'Kooperation mit lizenzierten Anwaltskanzleien und Finanzaufsichtsbehörden.'],
                    ]; ?>
                    <?php foreach ($features as $i => $f): ?>
                    <div class="col-md-6" data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>">
                        <div class="p-4 rounded-3 border feature-card h-100" style="border-color:var(--border)!important">
                            <div class="feature-icon">
                                <i class="bi <?= $f['icon'] ?>"></i>
                            </div>
                            <h6 class="fw-bold"><?= $f['title'] ?></h6>
                            <p class="text-muted small mb-0"><?= $f['desc'] ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== WHY US 6-CARD GRID ===== -->
<section id="vorteile-v2" class="py-6 bg-light">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="badge bg-warning text-dark rounded-pill px-3 py-2 mb-3">Warum VerlustRückholung?</span>
            <h2 class="display-6 fw-bold">Ihre Vorteile auf einen Blick</h2>
            <p class="text-muted col-lg-7 mx-auto">
                Künstliche Intelligenz zur Analyse verdächtiger Transaktionen und Prüfung möglicher Rückholungen –
                professionelle Unterstützung bei Verlusten durch betrügerische Investmentplattformen.
            </p>
        </div>
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="advantage-card h-100 p-4 bg-white rounded-4 shadow-sm text-center">
                    <div class="advantage-icon mx-auto mb-3 bg-warning-subtle rounded-circle">
                        <i class="bi bi-trophy-fill text-warning fs-3"></i>
                    </div>
                    <h5 class="fw-bold mb-2">87% Erfolgsquote</h5>
                    <p class="text-muted small">
                        In 87% aller eingereichten Fälle konnten wir erfolgreich
                        Betrugsstrukturen identifizieren und die Rückforderung einleiten.
                    </p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="advantage-card h-100 p-4 bg-white rounded-4 shadow-sm text-center">
                    <div class="advantage-icon mx-auto mb-3 bg-primary-subtle rounded-circle">
                        <i class="bi bi-cpu-fill text-primary fs-3"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Modernste KI-Technologie</h5>
                    <p class="text-muted small">
                        Unser proprietäres KI-System analysiert in Echtzeit Millionen von
                        Transaktionsdaten und erkennt Betrugsstrukturen zuverlässig.
                    </p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="advantage-card h-100 p-4 bg-white rounded-4 shadow-sm text-center">
                    <div class="advantage-icon mx-auto mb-3 bg-success-subtle rounded-circle">
                        <i class="bi bi-chat-heart-fill text-success fs-3"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Kostenlose Erstberatung</h5>
                    <p class="text-muted small">
                        Wir prüfen Ihren Fall zunächst vollkommen kostenlos.
                        Erst wenn wir Ihnen helfen können, besprechen wir weitere Schritte.
                    </p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="400">
                <div class="advantage-card h-100 p-4 bg-white rounded-4 shadow-sm text-center">
                    <div class="advantage-icon mx-auto mb-3 bg-info-subtle rounded-circle">
                        <i class="bi bi-shield-lock-fill text-info fs-3"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Diskrete &amp; sichere Bearbeitung</h5>
                    <p class="text-muted small">
                        Alle Ihre Daten werden streng vertraulich und nach aktuellen
                        DSGVO-Richtlinien verarbeitet und gespeichert.
                    </p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="500">
                <div class="advantage-card h-100 p-4 bg-white rounded-4 shadow-sm text-center">
                    <div class="advantage-icon mx-auto mb-3 bg-danger-subtle rounded-circle">
                        <i class="bi bi-lightning-fill text-danger fs-3"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Schnelle Fallprüfung</h5>
                    <p class="text-muted small">
                        Innerhalb von 48 Stunden erhalten Sie eine erste Einschätzung
                        zu Ihrem Fall und den möglichen Handlungsoptionen.
                    </p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="600">
                <div class="advantage-card h-100 p-4 bg-white rounded-4 shadow-sm text-center">
                    <div class="advantage-icon mx-auto mb-3 bg-secondary-subtle rounded-circle">
                        <i class="bi bi-globe2 text-secondary fs-3"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Internationale Fälle</h5>
                    <p class="text-muted small">
                        Wir bearbeiten Fälle aus über 18 Ländern und verfügen über
                        ein weitreichendes Netzwerk an internationalen Partnern.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== TESTIMONIALS ===== -->
<section id="erfahrungsberichte" class="py-6" style="background:var(--bg-light)">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="section-eyebrow">Erfahrungsberichte</div>
            <h2 class="fw-bold display-6">Was unsere Mandanten sagen</h2>
        </div>
        <?php $testimonials = [
            ['initials'=>'MK','name'=>'Markus K.','location'=>'München, DE','amount'=>'€47.000',
             'text'=>'Ich hatte mein Erspartes in eine Krypto-Plattform investiert und dann plötzlich keinen Zugriff mehr. VerlustRückholung hat mir innerhalb von 3 Monaten über 80% zurückgeholt. Unglaublich professionell.'],
            ['initials'=>'SF','name'=>'Sandra F.','location'=>'Wien, AT','amount'=>'€28.500',
             'text'=>'Ein Romance-Scam hatte mich €28.500 gekostet. Ich hatte keine Hoffnung mehr. Das Team erklärte mir jeden Schritt und am Ende hatte ich mein Geld zurück. Tausend Dank!'],
            ['initials'=>'TL','name'=>'Thomas L.','location'=>'Zürich, CH','amount'=>'€92.000',
             'text'=>'Als erfahrener Investor war ich beschämt, auf einen Fake-Broker hereingefallen zu sein. Das Team war diskret, professionell und hat über €70.000 meines Kapitals zurückgeholt.'],
        ]; ?>
        <div class="row g-4">
            <?php foreach ($testimonials as $i => $t): ?>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>">
                <div class="testimonial-card">
                    <div class="testimonial-stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.9rem;line-height:1.7;"><?= $t['text'] ?></p>
                    <div class="d-flex align-items-center gap-3">
                        <div class="testimonial-avatar"><?= $t['initials'] ?></div>
                        <div>
                            <div class="fw-bold small"><?= $t['name'] ?></div>
                            <div class="text-muted" style="font-size:.75rem;"><?= $t['location'] ?></div>
                        </div>
                        <div class="ms-auto">
                            <span class="badge bg-success-subtle text-success"><?= $t['amount'] ?> ↩</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== STATISTICS SECTION ===== -->
<section id="statistiken" class="py-6 stats-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="badge bg-light text-dark rounded-pill px-3 py-2 mb-3">Unsere Ergebnisse</span>
            <h2 class="display-6 fw-bold text-white">Zahlen, die für sich sprechen</h2>
            <p class="text-white-50 col-lg-6 mx-auto">
                Unsere Erfolgsbilanz belegt die Wirksamkeit unserer KI-gestützten Analysemethoden.
            </p>
        </div>
        <div class="row g-4 text-center mb-5">
            <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="100">
                <div class="stat-card p-4 rounded-4">
                    <div class="stat-number display-4 fw-bold text-warning" data-counter="87">0</div>
                    <div class="stat-unit text-warning fw-bold fs-4">%</div>
                    <div class="text-white mt-2 fw-semibold">Erfolgsquote</div>
                    <div class="text-white-50 small">bei identifizierten Fällen</div>
                </div>
            </div>
            <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="200">
                <div class="stat-card p-4 rounded-4">
                    <div class="stat-number display-4 fw-bold text-warning" data-counter="2400">0</div>
                    <div class="stat-unit text-warning fw-bold fs-4">+</div>
                    <div class="text-white mt-2 fw-semibold">Fälle geprüft</div>
                    <div class="text-white-50 small">seit Gründung</div>
                </div>
            </div>
            <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="300">
                <div class="stat-card p-4 rounded-4">
                    <div class="stat-number display-4 fw-bold text-warning" data-counter="48">0</div>
                    <div class="stat-unit text-warning fw-bold fs-4">M €+</div>
                    <div class="text-white mt-2 fw-semibold">Kapital rückgefordert</div>
                    <div class="text-white-50 small">für unsere Mandanten</div>
                </div>
            </div>
            <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="400">
                <div class="stat-card p-4 rounded-4">
                    <div class="stat-number display-4 fw-bold text-warning" data-counter="150">0</div>
                    <div class="stat-unit text-warning fw-bold fs-4">K+</div>
                    <div class="text-white mt-2 fw-semibold">Transaktionen analysiert</div>
                    <div class="text-white-50 small">durch unser KI-System</div>
                </div>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="bg-white bg-opacity-10 rounded-4 p-4">
                    <h5 class="text-white fw-bold mb-4">Häufigste Betrugsarten</h5>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between text-white mb-1">
                            <span><i class="bi bi-currency-bitcoin me-2 text-warning"></i>Krypto-Betrug</span>
                            <span>42%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-warning" style="width: 42%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between text-white mb-1">
                            <span><i class="bi bi-currency-exchange me-2 text-info"></i>Forex-Betrug</span>
                            <span>28%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-info" style="width: 28%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between text-white mb-1">
                            <span><i class="bi bi-building-x me-2 text-danger"></i>Fake-Broker</span>
                            <span>18%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-danger" style="width: 18%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between text-white mb-1">
                            <span><i class="bi bi-heart-arrow me-2"></i>Romance-Scam</span>
                            <span>8%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar" style="width: 8%; background-color: #ff69b4 !important;"></div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <div class="d-flex justify-content-between text-white mb-1">
                            <span><i class="bi bi-three-dots me-2 text-secondary"></i>Sonstige</span>
                            <span>4%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-secondary" style="width: 4%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <div class="bg-white bg-opacity-10 rounded-4 p-4 h-100">
                    <h5 class="text-white fw-bold mb-4">Kundenfeedback</h5>
                    <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <div class="text-white">
                                    <div class="mb-3">
                                        <i class="bi bi-star-fill text-warning"></i>
                                        <i class="bi bi-star-fill text-warning"></i>
                                        <i class="bi bi-star-fill text-warning"></i>
                                        <i class="bi bi-star-fill text-warning"></i>
                                        <i class="bi bi-star-fill text-warning"></i>
                                    </div>
                                    <p class="fst-italic">
                                        "Ich hatte 28.000€ an eine gefälschte Krypto-Plattform verloren.
                                        Dank VerlustRückholung konnte ich 21.000€ zurückfordern. Professionell, diskret und effizient."
                                    </p>
                                    <div class="fw-bold">– Thomas K., München</div>
                                </div>
                            </div>
                            <div class="carousel-item">
                                <div class="text-white">
                                    <div class="mb-3">
                                        <i class="bi bi-star-fill text-warning"></i>
                                        <i class="bi bi-star-fill text-warning"></i>
                                        <i class="bi bi-star-fill text-warning"></i>
                                        <i class="bi bi-star-fill text-warning"></i>
                                        <i class="bi bi-star-fill text-warning"></i>
                                    </div>
                                    <p class="fst-italic">
                                        "Nach monatelangem Kampf mit einem Fake-Broker hat mir das Team von
                                        VerlustRückholung endlich geholfen. Die KI-Analyse war der entscheidende Durchbruch."
                                    </p>
                                    <div class="fw-bold">– Maria S., Berlin</div>
                                </div>
                            </div>
                            <div class="carousel-item">
                                <div class="text-white">
                                    <div class="mb-3">
                                        <i class="bi bi-star-fill text-warning"></i>
                                        <i class="bi bi-star-fill text-warning"></i>
                                        <i class="bi bi-star-fill text-warning"></i>
                                        <i class="bi bi-star-fill text-warning"></i>
                                        <i class="bi bi-star-half text-warning"></i>
                                    </div>
                                    <p class="fst-italic">
                                        "Die kostenlose Erstberatung hat mich überzeugt. Innerhalb von 48 Stunden
                                        hatte ich bereits eine Einschätzung meines Falles. Sehr empfehlenswert!"
                                    </p>
                                    <div class="fw-bold">– Andreas M., Hamburg</div>
                                </div>
                            </div>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
                            <i class="bi bi-chevron-left text-warning fs-4"></i>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
                            <i class="bi bi-chevron-right text-warning fs-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== MAIN CONTACT FORM ===== -->
<section id="fallformular" class="form-section-v2">
    <div class="container">
        <div class="row justify-content-center mb-5" data-aos="fade-up">
            <div class="col-lg-7 text-center">
                <div class="section-eyebrow">Kostenlose Erstprüfung</div>
                <h2 class="fw-bold display-6">Reichen Sie Ihren Fall ein</h2>
                <p class="text-muted">100% kostenlos, unverbindlich, vertraulich. Wir antworten innerhalb von 48 Stunden.</p>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-9 col-xl-8" data-aos="fade-up" data-aos-delay="100">
                <div class="form-box-v2">
                    <div class="form-box-header">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="live-dot"></span>
                            <span class="small fw-semibold" style="color:rgba(255,255,255,.75)">KI-System aktiv · Analyse bereit</span>
                        </div>
                        <h4 class="fw-bold mb-0">Kostenlose KI-Fallprüfung starten</h4>
                        <div class="d-flex flex-wrap gap-3 mt-2">
                            <span class="badge bg-warning text-dark"><i class="bi bi-trophy-fill me-1"></i>87% Erfolgsquote</span>
                            <span class="badge bg-light text-dark"><i class="bi bi-shield-lock-fill me-1"></i>DSGVO-konform</span>
                            <span class="badge bg-light text-dark"><i class="bi bi-currency-euro me-1"></i>Keine Vorauszahlung</span>
                        </div>
                    </div>
                    <div class="form-box-body">
                        <form action="submit_lead.php" method="POST" id="mainFormV2" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="visit_id" id="visitIdMainFormV2" value="">

                            <!-- Section 1 -->
                            <div class="form-step-label">
                                <span class="num">1</span>Ihre persönlichen Angaben
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold small">Vorname *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-person text-muted"></i></span>
                                        <input type="text" name="first_name" class="form-control border-start-0 ps-1" placeholder="Max" required>
                                    </div>
                                    <div class="invalid-feedback">Bitte Vorname eingeben.</div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold small">Nachname *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-person-fill text-muted"></i></span>
                                        <input type="text" name="last_name" class="form-control border-start-0 ps-1" placeholder="Mustermann" required>
                                    </div>
                                    <div class="invalid-feedback">Bitte Nachname eingeben.</div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold small">E-Mail-Adresse *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                        <input type="email" name="email" class="form-control border-start-0 ps-1" placeholder="max@example.de" required>
                                    </div>
                                    <div class="invalid-feedback">Bitte gültige E-Mail eingeben.</div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold small">Telefonnummer</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-telephone text-muted"></i></span>
                                        <input type="tel" name="phone" class="form-control border-start-0 ps-1" placeholder="+49 123 456789">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold small">Land / Wohnsitz *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-geo-alt text-muted"></i></span>
                                        <select name="country" class="form-select border-start-0 ps-1" required>
                                            <option value="">Land auswählen...</option>
                                            <option value="Deutschland">🇩🇪 Deutschland</option>
                                            <option value="Österreich">🇦🇹 Österreich</option>
                                            <option value="Schweiz">🇨🇭 Schweiz</option>
                                            <option value="USA">🇺🇸 USA</option>
                                            <option value="Vereinigtes Königreich">🇬🇧 Vereinigtes Königreich</option>
                                            <option value="Frankreich">🇫🇷 Frankreich</option>
                                            <option value="Spanien">🇪🇸 Spanien</option>
                                            <option value="Italien">🇮🇹 Italien</option>
                                            <option value="Niederlande">🇳🇱 Niederlande</option>
                                            <option value="Polen">🇵🇱 Polen</option>
                                            <option value="Türkei">🇹🇷 Türkei</option>
                                            <option value="Vereinigte Arabische Emirate">🇦🇪 VAE</option>
                                            <option value="Andere">🌍 Anderes Land</option>
                                        </select>
                                    </div>
                                    <div class="invalid-feedback">Bitte Land auswählen.</div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold small">Jahr des Verlusts</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar3 text-muted"></i></span>
                                        <select name="year_lost" class="form-select border-start-0 ps-1">
                                            <option value="">Jahr auswählen...</option>
                                            <?php foreach ($years as $y): ?>
                                            <option value="<?= $y ?>"><?= $y ?></option>
                                            <?php endforeach; ?>
                                            <option value="2014">Vor 2015</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 2 -->
                            <div class="form-step-label">
                                <span class="num">2</span>Angaben zu Ihrem Fall
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold small">Verlorener Betrag (ca.) *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">€</span>
                                        <input type="number" name="amount_lost" class="form-control" placeholder="10.000" min="1" required>
                                    </div>
                                    <div class="invalid-feedback">Bitte Betrag eingeben.</div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold small">Betrugsart *</label>
                                    <select name="platform_category" class="form-select" required>
                                        <option value="">Betrugsart wählen...</option>
                                        <option value="Krypto-Betrug">₿ Krypto-Betrug</option>
                                        <option value="Forex-Betrug">📈 Forex-Betrug</option>
                                        <option value="Fake-Broker">🏢 Fake Investment-Broker</option>
                                        <option value="Romance-Scam mit Investitionsbetrug">💔 Romance-Scam</option>
                                        <option value="Binäre Optionen">📊 Binäre Optionen / Trading</option>
                                        <option value="Andere">❓ Andere / Unbekannt</option>
                                    </select>
                                    <div class="invalid-feedback">Bitte Betrugsart auswählen.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold small">Fallbeschreibung *</label>
                                    <textarea name="case_description" class="form-control" rows="4" required
                                              placeholder="Wie haben Sie die Plattform gefunden? Welchen Betrag haben Sie investiert? Seit wann haben Sie keinen Zugriff? Jede Information hilft uns."></textarea>
                                    <div class="invalid-feedback">Bitte Fallbeschreibung eingeben.</div>
                                </div>
                            </div>

                            <!-- Privacy & Submit -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="mainPrivacy2" required>
                                    <label class="form-check-label small text-muted" for="mainPrivacy2">
                                        Ich stimme der
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModalV2">Datenschutzerklärung</a>
                                        zu und bin damit einverstanden, dass meine Daten zur Fallbearbeitung verwendet werden. *
                                    </label>
                                    <div class="invalid-feedback">Bitte Datenschutzerklärung akzeptieren.</div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold py-3"
                                    style="border-radius:12px;font-size:1.1rem;">
                                <i class="bi bi-search me-2"></i>Fall jetzt kostenlos einreichen
                                <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                            <p class="text-muted small text-center mt-3 mb-0">
                                <i class="bi bi-lock-fill text-success me-1"></i>
                                SSL-verschlüsselt · Keine Kosten · Vollständig vertraulich
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== TRUST BADGES ===== -->
<section class="py-6 bg-white">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="section-eyebrow">Vertrauen & Sicherheit</div>
            <h2 class="fw-bold display-6">Warum Sie uns vertrauen können</h2>
            <p class="text-muted">Zertifiziert, sicher, transparent – Ihr Schutz hat höchste Priorität.</p>
        </div>
        <div class="row g-3">
            <?php $trust_badges = [
                ['icon'=>'🔒','title'=>'256-Bit SSL','desc'=>'Alle Übertragungen militärisch verschlüsselt'],
                ['icon'=>'🇪🇺','title'=>'DSGVO-konform','desc'=>'Vollständige EU-Datenschutz-Compliance'],
                ['icon'=>'⚖️','title'=>'Lizenzierte Partner','desc'=>'Anwaltskanzleien in 18+ Ländern'],
                ['icon'=>'🏆','title'=>'87% Erfolgsquote','desc'=>'Geprüfte Rate aus über 2.400 Fällen'],
                ['icon'=>'💳','title'=>'Keine Vorauszahlung','desc'=>'Rein erfolgsbasiertes Vergütungsmodell'],
                ['icon'=>'📋','title'=>'Volle Transparenz','desc'=>'Laufende Updates zu Ihrem Fall-Status'],
            ]; ?>
            <?php foreach ($trust_badges as $i => $tb): ?>
            <div class="col-6 col-md-4 col-lg-4" data-aos="fade-up" data-aos-delay="<?= $i * 70 ?>">
                <div class="trust-badge-card">
                    <div class="trust-badge-icon"><?= $tb['icon'] ?></div>
                    <div>
                        <div class="fw-bold small mb-1"><?= $tb['title'] ?></div>
                        <div class="text-muted" style="font-size:.78rem;line-height:1.4"><?= $tb['desc'] ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== FAQ ===== -->
<section class="py-6 bg-white">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-6 text-center" data-aos="fade-up">
                <div class="section-eyebrow">Häufige Fragen</div>
                <h2 class="fw-bold display-6">FAQ</h2>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8" data-aos="fade-up">
                <div class="accordion accordion-v2" id="faqAccordionV2">
                    <?php $faqs = [
                        ['q'=>'Ist die Erstprüfung wirklich kostenlos?',
                         'a'=>'Ja, vollständig und unverbindlich. Wir analysieren Ihren Fall und teilen Ihnen mit, ob wir helfen können – ohne jegliche Vorauszahlung. Kosten entstehen erst bei Erfolg, und das nur erfolgsbasiert.'],
                        ['q'=>'Wie lange dauert die Analyse?',
                         'a'=>'Innerhalb von 48 Stunden erhalten Sie eine erste Einschätzung. Die vollständige KI-Analyse dauert in der Regel 5–10 Werktage, abhängig von der Komplexität des Falls.'],
                        ['q'=>'Welche Betrugsformen können geprüft werden?',
                         'a'=>'Wir prüfen alle Formen von Anlagebetrug: Krypto-Scams, Forex-Betrug, Fake-Broker, Romance-Scam mit Investitionsbetrug, Binäre Optionen und weitere Online-Investitionsbetrug.'],
                        ['q'=>'Was passiert, wenn keine Rückforderung möglich ist?',
                         'a'=>'In diesem Fall entstehen Ihnen keinerlei Kosten. Wir teilen Ihnen das Ergebnis der Prüfung ehrlich mit und geben ggf. Empfehlungen für weitere Schritte.'],
                        ['q'=>'Sind meine Daten sicher?',
                         'a'=>'Ja. Alle Daten werden SSL-verschlüsselt übertragen, ausschließlich zur Fallbearbeitung verwendet und nicht an Dritte weitergegeben. Wir sind vollständig DSGVO-konform.'],
                    ]; ?>
                    <?php foreach ($faqs as $i => $faq): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?> fw-semibold"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#faqV2-<?= $i ?>">
                                <?= $faq['q'] ?>
                            </button>
                        </h2>
                        <div id="faqV2-<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>"
                             data-bs-parent="#faqAccordionV2">
                            <div class="accordion-body text-muted"><?= $faq['a'] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="footer-v2" id="kontakt">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-lg-4">
                <div class="footer-brand mb-3">⚖️ Verlust<span>Rückholung</span></div>
                <p style="font-size:.875rem;line-height:1.7;color:rgba(255,255,255,.5);">
                    KI-gestützte Kapitalrückholung bei Anlagebetrug. Professionell, transparent und erfolgsorientiert.
                </p>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="text-white fw-bold mb-3" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;">Service</h6>
                <a href="#wie-es-funktioniert" class="footer-link">Wie es funktioniert</a>
                <a href="#betrugsarten" class="footer-link">Betrugsarten</a>
                <a href="#fallformular" class="footer-link">Fall einreichen</a>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="text-white fw-bold mb-3" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;">Rechtliches</h6>
                <a href="#" class="footer-link" data-bs-toggle="modal" data-bs-target="#impressumModalV2">Impressum</a>
                <a href="#" class="footer-link" data-bs-toggle="modal" data-bs-target="#privacyModalV2">Datenschutz</a>
            </div>
            <div class="col-lg-4">
                <h6 class="text-white fw-bold mb-3" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;">Kontakt</h6>
                <p style="font-size:.875rem;color:rgba(255,255,255,.5);">
                    <i class="bi bi-envelope me-2"></i>info@verlustrueckholung.de<br>
                    <i class="bi bi-globe me-2 mt-2 d-inline-block"></i>verlustrueckholung.de
                </p>
                <a href="#" class="btn btn-warning btn-sm fw-bold mt-2"
                   data-bs-toggle="modal" data-bs-target="#fullFormModal">
                    <i class="bi bi-shield-check me-1"></i>Kostenlos prüfen lassen
                </a>
            </div>
        </div>
        <div class="section-divider" style="background:rgba(255,255,255,.08);"></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center pt-3 gap-2">
            <div style="font-size:.8rem;">© <?= date('Y') ?> VerlustRückholung. Alle Rechte vorbehalten.</div>
            <div style="font-size:.8rem;color:rgba(255,255,255,.4);">
                <i class="bi bi-shield-lock me-1"></i>DSGVO-konform
                <i class="bi bi-lock ms-3 me-1"></i>SSL-gesichert
            </div>
        </div>
    </div>
</footer>

<!-- ===== ENGAGEMENT MODAL (configurable delay) ===== -->
<div class="modal fade" id="exitIntentModalV2" tabindex="-1"
     data-bs-backdrop="static" data-bs-keyboard="false"
     data-modal-delay="<?= $modal_delay ?>">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content engagement-modal-content">
            <button type="button" class="btn-close engagement-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            <div class="engagement-modal-body">
                <div class="row g-0">
                    <div class="col-lg-5 engagement-left d-none d-lg-flex flex-column justify-content-center align-items-center p-4">
                        <div class="engagement-icon mb-3">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="text-white text-center mb-4">
                            <div class="h3 fw-bold text-warning mb-1">87%</div>
                            <div class="small text-white-50">unserer Mandanten erhalten Kapital zurück</div>
                        </div>
                        <div class="text-white text-center mb-3">
                            <div class="h3 fw-bold text-warning mb-1">€48M+</div>
                            <div class="small text-white-50">bereits zurückgefordert</div>
                        </div>
                        <div class="text-white text-center">
                            <div class="h3 fw-bold text-warning mb-1">€0</div>
                            <div class="small text-white-50">Kosten für Sie vorab</div>
                        </div>
                    </div>
                    <div class="col-lg-7 engagement-right p-4 p-lg-5">
                        <div class="mb-1">
                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill small fw-semibold">
                                <i class="bi bi-gift me-1"></i>Exklusiv für Sie
                            </span>
                        </div>
                        <h3 class="fw-bold mt-3 mb-2" style="line-height:1.2;">
                            Warten Sie – Ihr Geld könnte noch rückforderbar sein.
                        </h3>
                        <p class="text-muted mb-3">
                            Täglich helfen wir Betrugsopfern, verloren geglaubtes Kapital zurückzufordern.
                            Viele unserer erfolgreichsten Fälle schienen zunächst hoffnungslos –
                            bis unsere KI die entscheidende Spur fand.
                        </p>
                        <div class="bg-light rounded-3 p-3 mb-4">
                            <p class="mb-2 fw-semibold text-dark">
                                <i class="bi bi-cpu text-primary me-2"></i>Probieren Sie es aus – die Beratung ist kostenlos.
                            </p>
                            <p class="text-muted small mb-0">
                                Sie haben nichts zu verlieren, aber möglicherweise alles zu gewinnen.
                                Unsere Erstprüfung ist vollständig kostenlos, unverbindlich und vertraulich.
                                Erst wenn wir Ihnen tatsächlich helfen können und Sie es wünschen, entstehen Kosten –
                                und auch dann nur erfolgsbasiert.
                            </p>
                        </div>
                        <form action="submit_lead.php" method="POST" id="engFormV2" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="lead_source" value="engagement_modal">
                            <input type="hidden" name="visit_id" id="visitIdEngV2" value="">
                            <div class="row g-2 mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold small">Vorname *</label>
                                    <input type="text" name="first_name" class="form-control form-control-sm" placeholder="Max" required>
                                    <div class="invalid-feedback">Bitte eingeben.</div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold small">Nachname *</label>
                                    <input type="text" name="last_name" class="form-control form-control-sm" placeholder="Mustermann" required>
                                    <div class="invalid-feedback">Bitte eingeben.</div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold small">E-Mail *</label>
                                    <input type="email" name="email" class="form-control form-control-sm" placeholder="max@example.de" required>
                                    <div class="invalid-feedback">Bitte gültige E-Mail eingeben.</div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold small">Telefon</label>
                                    <input type="tel" name="phone" class="form-control form-control-sm" placeholder="+49 123 456789">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold small">Land *</label>
                                    <select name="country" class="form-select form-select-sm" required>
                                        <option value="">Land auswählen...</option>
                                        <option value="Deutschland">🇩🇪 Deutschland</option>
                                        <option value="Österreich">🇦🇹 Österreich</option>
                                        <option value="Schweiz">🇨🇭 Schweiz</option>
                                        <option value="USA">🇺🇸 USA</option>
                                        <option value="Vereinigtes Königreich">🇬🇧 Vereinigtes Königreich</option>
                                        <option value="Frankreich">🇫🇷 Frankreich</option>
                                        <option value="Spanien">🇪🇸 Spanien</option>
                                        <option value="Italien">🇮🇹 Italien</option>
                                        <option value="Niederlande">🇳🇱 Niederlande</option>
                                        <option value="Polen">🇵🇱 Polen</option>
                                        <option value="Türkei">🇹🇷 Türkei</option>
                                        <option value="Vereinigte Arabische Emirate">🇦🇪 VAE</option>
                                        <option value="Andere">🌍 Anderes Land</option>
                                    </select>
                                    <div class="invalid-feedback">Bitte Land auswählen.</div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold small">Jahr des Verlusts</label>
                                    <select name="year_lost" class="form-select form-select-sm">
                                        <option value="">Jahr auswählen...</option>
                                        <?php foreach ($years as $y): ?><option value="<?= $y ?>"><?= $y ?></option><?php endforeach; ?>
                                        <option value="2014">Vor 2015</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold small">Betrag (ca.) *</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">€</span>
                                        <input type="number" name="amount_lost" class="form-control" placeholder="10000" min="1" required>
                                    </div>
                                    <div class="invalid-feedback">Bitte Betrag eingeben.</div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold small">Betrugsart *</label>
                                    <select name="platform_category" class="form-select form-select-sm" required>
                                        <option value="">Betrugsart wählen...</option>
                                        <option value="Krypto-Betrug">₿ Krypto-Betrug</option>
                                        <option value="Forex-Betrug">📈 Forex-Betrug</option>
                                        <option value="Fake-Broker">🏢 Fake-Broker</option>
                                        <option value="Romance-Scam mit Investitionsbetrug">💔 Romance-Scam</option>
                                        <option value="Binäre Optionen">📊 Binäre Optionen</option>
                                        <option value="Andere">❓ Andere</option>
                                    </select>
                                    <div class="invalid-feedback">Bitte Betrugsart auswählen.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold small">Kurze Fallbeschreibung *</label>
                                    <textarea name="case_description" class="form-control form-control-sm" rows="2" required
                                              placeholder="Was ist passiert? Welche Plattform? Seit wann kein Zugriff?"></textarea>
                                    <div class="invalid-feedback">Bitte Beschreibung eingeben.</div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="engPrivacyV2" required>
                                        <label class="form-check-label small text-muted" for="engPrivacyV2">
                                            Ich stimme der <a href="#" class="text-primary" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#privacyModalV2">Datenschutzerklärung</a> zu. *
                                        </label>
                                        <div class="invalid-feedback">Bitte akzeptieren.</div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold">
                                <i class="bi bi-search me-2"></i>Jetzt kostenlos prüfen lassen
                            </button>
                            <div class="text-center mt-2">
                                <button type="button" class="btn btn-link text-muted small p-0" data-bs-dismiss="modal">
                                    Nein danke, ich verzichte auf mein Geld.
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Full form modal (triggered from CTA buttons and fraud type cards) -->
<div class="modal fade" id="fullFormModal" tabindex="-1" aria-labelledby="fullFormModalLabel">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content fall-modal-content border-0">

            <!-- Modal Header -->
            <div class="modal-header fall-modal-header border-0 p-0">
                <div class="fall-modal-header-inner w-100">
                    <div class="d-flex justify-content-between align-items-start p-4">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="live-dot"></span>
                                <span class="text-success fw-semibold small">KI-System aktiv · Analyse bereit</span>
                            </div>
                            <h4 class="modal-title fw-bold text-white mb-1" id="fullFormModalLabel">
                                <i class="bi bi-shield-check text-warning me-2"></i>Kostenlose KI-Fallprüfung starten
                            </h4>
                            <p class="text-white-50 small mb-0">
                                Unverbindlich · 100% kostenlos · Antwort innerhalb 48 Stunden
                            </p>
                        </div>
                        <button type="button" class="btn-close btn-close-white mt-1" data-bs-dismiss="modal" aria-label="Schließen"></button>
                    </div>
                    <!-- Trust badges strip -->
                    <div class="fall-badges-strip">
                        <div class="fall-badge"><i class="bi bi-trophy-fill"></i><span><strong>87%</strong> Erfolgsquote</span></div>
                        <div class="fall-badge"><i class="bi bi-lightning-charge-fill"></i><span>Antwort in <strong>48h</strong></span></div>
                        <div class="fall-badge"><i class="bi bi-shield-lock-fill"></i><span><strong>DSGVO</strong>-konform</span></div>
                        <div class="fall-badge"><i class="bi bi-currency-euro"></i><span><strong>Keine</strong> Vorauszahlung</span></div>
                    </div>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-0">
                <div class="row g-0">

                    <!-- Left sidebar: visual / info -->
                    <div class="col-lg-4 fall-modal-sidebar d-none d-lg-flex flex-column justify-content-between p-4">
                        <div>
                            <h6 class="text-warning fw-bold text-uppercase small mb-3 letter-spacing">Warum VerlustRückholung?</h6>
                            <ul class="list-unstyled fall-sidebar-list">
                                <li><i class="bi bi-check-circle-fill text-warning"></i>KI-gestützte Transaktionsanalyse</li>
                                <li><i class="bi bi-check-circle-fill text-warning"></i>Internationale Betrugsrückverfolgung</li>
                                <li><i class="bi bi-check-circle-fill text-warning"></i>Erfahrene Rechts- &amp; Finanzexperten</li>
                                <li><i class="bi bi-check-circle-fill text-warning"></i>Erfolgsbasierte Vergütung</li>
                                <li><i class="bi bi-check-circle-fill text-warning"></i>18+ Länder · 2.400+ Fälle</li>
                            </ul>
                        </div>
                        <div>
                            <div class="fall-stat-mini mb-2">
                                <div class="fall-stat-val">€48M+</div>
                                <div class="fall-stat-lbl">für unsere Mandanten zurückgefordert</div>
                            </div>
                            <div class="fall-stat-mini mb-2">
                                <div class="fall-stat-val">2.400+</div>
                                <div class="fall-stat-lbl">erfolgreich geprüfte Fälle</div>
                            </div>
                            <div class="fall-stat-mini">
                                <div class="fall-stat-val">5 ★</div>
                                <div class="fall-stat-lbl">Kundenbewertung</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: form -->
                    <div class="col-lg-8 p-4 p-lg-5">
                        <form action="submit_lead.php" method="POST" id="modalFormV2" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="visit_id" id="visitIdModalV2" value="">

                            <!-- Section 1: Persönliche Angaben -->
                            <div class="fall-form-section mb-4">
                                <div class="fall-form-section-title">
                                    <span class="fall-section-num">1</span>
                                    Ihre persönlichen Angaben
                                </div>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold small">Vorname *</label>
                                        <div class="input-group input-group-sm-icon">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-person text-muted"></i></span>
                                            <input type="text" name="first_name" class="form-control border-start-0 ps-1" placeholder="Max" required>
                                        </div>
                                        <div class="invalid-feedback">Bitte Vorname eingeben.</div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold small">Nachname *</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-person-fill text-muted"></i></span>
                                            <input type="text" name="last_name" class="form-control border-start-0 ps-1" placeholder="Mustermann" required>
                                        </div>
                                        <div class="invalid-feedback">Bitte Nachname eingeben.</div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold small">E-Mail-Adresse *</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                            <input type="email" name="email" class="form-control border-start-0 ps-1" placeholder="max@example.de" required>
                                        </div>
                                        <div class="invalid-feedback">Bitte gültige E-Mail eingeben.</div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold small">Telefonnummer</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-telephone text-muted"></i></span>
                                            <input type="tel" name="phone" class="form-control border-start-0 ps-1" placeholder="+49 123 456789">
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold small">Land / Wohnsitz *</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-geo-alt text-muted"></i></span>
                                            <select name="country" class="form-select border-start-0 ps-1" required>
                                                <option value="">Land auswählen...</option>
                                                <option value="Deutschland">🇩🇪 Deutschland</option>
                                                <option value="Österreich">🇦🇹 Österreich</option>
                                                <option value="Schweiz">🇨🇭 Schweiz</option>
                                                <option value="USA">🇺🇸 USA</option>
                                                <option value="Vereinigtes Königreich">🇬🇧 Vereinigtes Königreich</option>
                                                <option value="Frankreich">🇫🇷 Frankreich</option>
                                                <option value="Spanien">🇪🇸 Spanien</option>
                                                <option value="Italien">🇮🇹 Italien</option>
                                                <option value="Niederlande">🇳🇱 Niederlande</option>
                                                <option value="Polen">🇵🇱 Polen</option>
                                                <option value="Türkei">🇹🇷 Türkei</option>
                                                <option value="Vereinigte Arabische Emirate">🇦🇪 VAE</option>
                                                <option value="Andere">🌍 Anderes Land</option>
                                            </select>
                                        </div>
                                        <div class="invalid-feedback">Bitte Land auswählen.</div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold small">Jahr des Verlusts</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar3 text-muted"></i></span>
                                            <select name="year_lost" class="form-select border-start-0 ps-1">
                                                <option value="">Jahr auswählen...</option>
                                                <?php foreach ($years as $y): ?><option value="<?= $y ?>"><?= $y ?></option><?php endforeach; ?>
                                                <option value="2014">Vor 2015</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 2: Falldetails -->
                            <div class="fall-form-section mb-4">
                                <div class="fall-form-section-title">
                                    <span class="fall-section-num">2</span>
                                    Angaben zu Ihrem Fall
                                </div>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold small">Ungefähr verlorener Betrag *</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white">€</span>
                                            <input type="number" name="amount_lost" class="form-control" placeholder="10.000" min="1" required>
                                        </div>
                                        <div class="invalid-feedback">Bitte Betrag eingeben.</div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold small">Betrugsart / Plattformtyp *</label>
                                        <select name="platform_category" class="form-select" required>
                                            <option value="">Betrugsart wählen...</option>
                                            <option value="Krypto-Betrug">₿ Krypto-Betrug</option>
                                            <option value="Forex-Betrug">📈 Forex-Betrug</option>
                                            <option value="Fake-Broker">🏢 Fake Investment-Broker</option>
                                            <option value="Romance-Scam mit Investitionsbetrug">💔 Romance-Scam</option>
                                            <option value="Binäre Optionen">📊 Binäre Optionen / Trading</option>
                                            <option value="Andere">❓ Andere / Unbekannt</option>
                                        </select>
                                        <div class="invalid-feedback">Bitte Betrugsart auswählen.</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold small">Kurze Fallbeschreibung *</label>
                                        <textarea name="case_description" class="form-control" rows="3" required
                                                  placeholder="Wie haben Sie die Plattform gefunden? Welchen Betrag haben Sie investiert? Seit wann haben Sie keinen Zugriff? Jede Information hilft uns."></textarea>
                                        <div class="invalid-feedback">Bitte Fallbeschreibung eingeben.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Datenschutz + Submit -->
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="modalPrivV2" required>
                                    <label class="form-check-label small text-muted" for="modalPrivV2">
                                        Ich stimme der
                                        <a href="#" class="text-primary" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#privacyModalV2">Datenschutzerklärung</a>
                                        zu und bin damit einverstanden, dass meine Daten zur Fallbearbeitung verwendet werden. *
                                    </label>
                                    <div class="invalid-feedback">Bitte Datenschutzerklärung akzeptieren.</div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold py-3 fall-submit-btn">
                                <i class="bi bi-search me-2"></i>Fall jetzt kostenlos einreichen
                                <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                            <p class="text-muted small text-center mt-3 mb-0">
                                <i class="bi bi-lock-fill text-success me-1"></i>
                                SSL-verschlüsselt · Keine Kosten · Vollständig vertraulich
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Info Modal: Wie KI-Technologie funktioniert -->
<div class="modal fade" id="infoModalV2" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 bg-primary text-white">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-cpu me-2"></i>Wie unsere KI-Technologie funktioniert
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex gap-3 mb-4">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;">1</div>
                            <div>
                                <h6 class="fw-bold">Datenerfassung</h6>
                                <p class="text-muted small mb-0">Sie reichen Ihren Fall mit allen relevanten Informationen über unser gesichertes Formular ein.</p>
                            </div>
                        </div>
                        <div class="d-flex gap-3 mb-4">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;">2</div>
                            <div>
                                <h6 class="fw-bold">KI-Transaktionsanalyse</h6>
                                <p class="text-muted small mb-0">Unser KI-Modell analysiert Transaktionsdaten, Zahlungsströme und verknüpft diese mit bekannten Betrugsstrukturen.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex gap-3 mb-4">
                            <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;">3</div>
                            <div>
                                <h6 class="fw-bold">Betrugsnetzwerk-Mapping</h6>
                                <p class="text-muted small mb-0">Wir identifizieren das Betrugsnetzwerk und seine Strukturen durch forensische Blockchain- und Zahlungsanalyse.</p>
                            </div>
                        </div>
                        <div class="d-flex gap-3">
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;">4</div>
                            <div>
                                <h6 class="fw-bold">Rückforderungsstrategie</h6>
                                <p class="text-muted small mb-0">Basierend auf der Analyse entwickeln wir eine individuelle Strategie zur Rückforderung Ihres Kapitals.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-primary mt-3 mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    Unsere KI wurde auf tausenden von Betrugsmustern trainiert und erkennt auch komplexe,
                    verschachtelte Betrugsstrukturen mit hoher Genauigkeit.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                <button class="btn btn-primary fw-bold" data-bs-dismiss="modal"
                        data-bs-toggle="modal" data-bs-target="#fullFormModal">
                    <i class="bi bi-search me-1"></i>Fall einreichen
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scam Type Detail Modal -->
<div class="modal fade" id="scamModalV2" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="scamModalV2Title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted" id="scamModalV2Desc"></p>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Betroffen?</strong> Reichen Sie Ihren Fall kostenlos ein –
                    wir prüfen, ob wir helfen können.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                <button class="btn btn-warning fw-bold" data-bs-dismiss="modal"
                        data-bs-toggle="modal" data-bs-target="#fullFormModal">
                    <i class="bi bi-search me-1"></i>Jetzt Fall prüfen lassen
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Privacy Modal -->
<div class="modal fade" id="privacyModalV2" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Datenschutzerklärung</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted"><em>Platzhalter – Bitte mit vollständiger DSGVO-konformer Datenschutzerklärung ersetzen.</em></p>
                <h6>1. Verantwortlicher</h6>
                <p>VerlustRückholung GmbH, Musterstraße 1, 10115 Berlin</p>
                <h6>2. Datenerhebung</h6>
                <p>
                    Wir erheben und verarbeiten Ihre personenbezogenen Daten (Vor- und Nachname, E-Mail-Adresse,
                    Telefonnummer sowie Fallbeschreibung) ausschließlich zum Zweck der Fallbearbeitung und Beratung
                    gemäß Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung).
                </p>
                <h6>3. Datenspeicherung</h6>
                <p>
                    Ihre Daten werden auf sicheren Servern in Deutschland gespeichert und nicht an
                    Dritte weitergegeben, sofern dies nicht zur Leistungserbringung erforderlich ist
                    oder Sie ausdrücklich zugestimmt haben.
                </p>
                <h6>4. Ihre Rechte</h6>
                <p>
                    Sie haben jederzeit das Recht auf Auskunft, Berichtigung, Löschung und Einschränkung
                    der Verarbeitung Ihrer Daten sowie das Recht auf Datenübertragbarkeit.
                </p>
                <h6>5. Kontakt</h6>
                <p>Bei Fragen zum Datenschutz: datenschutz@verlustrueckholung.de</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
            </div>
        </div>
    </div>
</div>

<!-- Impressum Modal -->
<div class="modal fade" id="impressumModalV2" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Impressum</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted"><em>Platzhalter – Bitte mit tatsächlichen Firmenangaben ersetzen.</em></p>
                <h6>Angaben gemäß § 5 TMG</h6>
                <p>VerlustRückholung GmbH<br>Musterstraße 1<br>10115 Berlin<br>Deutschland</p>
                <h6>Vertreten durch:</h6>
                <p>Max Mustermann (Geschäftsführer)</p>
                <h6>Kontakt:</h6>
                <p>Telefon: +49 (0) 30 – 000 00 00<br>E-Mail: info@verlustrueckholung.de</p>
                <h6>Registereintrag:</h6>
                <p>Eintragung im Handelsregister.<br>Registergericht: Amtsgericht Berlin-Charlottenburg<br>Registernummer: HRB 000000</p>
                <h6>Umsatzsteuer-ID:</h6>
                <p>Umsatzsteuer-Identifikationsnummer gemäß § 27 a Umsatzsteuergesetz: DE 000 000 000</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- AOS -->
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>

<script>
(function () {
    'use strict';

    // ── AOS init ────────────────────────────────────────────
    AOS.init({ duration: 750, once: true, easing: 'ease-out-cubic', offset: 60 });

    // ── Navbar scroll effect ────────────────────────────────
    var nav = document.getElementById('mainNav2');
    if (nav) window.addEventListener('scroll', function () {
        nav.classList.toggle('scrolled', window.scrollY > 40);
    });

    // ── AJAX Form submission ────────────────────────────────
    (function () {
        // IDs and their success-display strategies
        var formDefs = [
            { id: 'heroForm',    type: 'inline' },
            { id: 'mainFormV2', type: 'inline' },
            { id: 'modalFormV2', type: 'modal'  },
            { id: 'engFormV2',   type: 'modal'  },
        ];

        function showAlert(container, success, message) {
            var cls  = success ? 'alert-success' : 'alert-danger';
            var icon = success ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
            var div  = document.createElement('div');
            div.className = 'alert ' + cls + ' d-flex align-items-start gap-2 my-3';
            div.innerHTML = '<i class="bi ' + icon + ' flex-shrink-0 mt-1"></i><div>' + message + '</div>';
            // Remove any existing alert first
            var old = container.querySelector('.ajax-form-alert');
            if (old) old.remove();
            div.classList.add('ajax-form-alert');
            container.insertBefore(div, container.firstChild);
        }

        formDefs.forEach(function (def) {
            var form = document.getElementById(def.id);
            if (!form) return;

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (!form.checkValidity()) {
                    form.classList.add('was-validated');
                    return;
                }
                form.classList.add('was-validated');

                var btn = form.querySelector('[type="submit"]');
                var origHtml = btn ? btn.innerHTML : '';
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Wird geprüft...';
                }

                var fd = new FormData(form);
                fd.append('_ajax', '1');
                fd.append('_source', 'index2');

                fetch('submit_lead.php', { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        // Update CSRF tokens in all forms on the page
                        if (data.csrf_token) {
                            document.querySelectorAll('input[name="csrf_token"]').forEach(function (el) {
                                el.value = data.csrf_token;
                            });
                        }
                        if (data.success) {
                            if (def.type === 'inline') {
                                // Replace form with success message
                                var msg = document.createElement('div');
                                msg.className = 'alert alert-success d-flex align-items-start gap-2';
                                msg.innerHTML = '<i class="bi bi-check-circle-fill flex-shrink-0 mt-1 fs-5 text-success"></i>' +
                                    '<div><strong>Vielen Dank!</strong> ' + data.message + '</div>';
                                form.parentNode.insertBefore(msg, form);
                                form.style.display = 'none';
                            } else {
                                // Show success inside modal body, hide the form
                                showAlert(form.parentNode, true, '<strong>Vielen Dank!</strong> ' + data.message);
                                form.style.display = 'none';
                            }
                            // Mark as engaged so engagement modal won't re-appear
                            sessionStorage.setItem('vr2_engaged', '1');
                        } else {
                            showAlert(form, false, data.message || 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
                            if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
                        }
                    })
                    .catch(function () {
                        showAlert(form, false, 'Netzwerkfehler. Bitte versuchen Sie es erneut.');
                        if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
                    });
            });
        });
    })();

    // ────────────────────────────────────────────────────────
    //  HERO CANVAS – animated AI neural-network particle field
    // ────────────────────────────────────────────────────────
    (function () {
        var canvas = document.getElementById('heroCanvas');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var W, H, nodes, packets;

        var NODE_COUNT     = 70;
        var CONNECT_DIST   = 130;
        var PACKET_SPEED   = 1.4;
        var PACKET_SPAWN_MS = 400;

        function resize() {
            W = canvas.width  = canvas.offsetWidth;
            H = canvas.height = canvas.offsetHeight;
            initNodes();
        }

        function initNodes() {
            nodes = [];
            for (var i = 0; i < NODE_COUNT; i++) {
                var gold = Math.random() < 0.15;
                nodes.push({
                    x: Math.random() * W,
                    y: Math.random() * H,
                    vx: (Math.random() - .5) * .4,
                    vy: (Math.random() - .5) * .4,
                    r: gold ? 3.5 : (1.5 + Math.random() * 1.5),
                    gold: gold,
                    phase: Math.random() * Math.PI * 2,
                });
            }
            packets = [];
        }

        function spawnPacket() {
            // Pick a random edge between connected nodes
            var attempts = 0;
            while (attempts++ < 20) {
                var a = nodes[Math.floor(Math.random() * nodes.length)];
                var b = nodes[Math.floor(Math.random() * nodes.length)];
                if (a === b) continue;
                var dx = b.x - a.x, dy = b.y - a.y;
                if (Math.sqrt(dx*dx + dy*dy) < CONNECT_DIST) {
                    packets.push({ ax: a.x, ay: a.y, bx: b.x, by: b.y, t: 0 });
                    return;
                }
            }
        }

        var lastPacketTime = 0;
        function draw(ts) {
            ctx.clearRect(0, 0, W, H);

            // Update node positions
            var T = ts * 0.001;
            nodes.forEach(function (n) {
                n.x += n.vx;
                n.y += n.vy;
                if (n.x < 0 || n.x > W) n.vx *= -1;
                if (n.y < 0 || n.y > H) n.vy *= -1;
            });

            // Draw edges
            for (var i = 0; i < nodes.length; i++) {
                for (var j = i + 1; j < nodes.length; j++) {
                    var dx = nodes[j].x - nodes[i].x;
                    var dy = nodes[j].y - nodes[i].y;
                    var d  = Math.sqrt(dx*dx + dy*dy);
                    if (d < CONNECT_DIST) {
                        var alpha = (1 - d / CONNECT_DIST) * 0.18;
                        ctx.strokeStyle = 'rgba(100,160,255,' + alpha + ')';
                        ctx.lineWidth   = .6;
                        ctx.beginPath();
                        ctx.moveTo(nodes[i].x, nodes[i].y);
                        ctx.lineTo(nodes[j].x, nodes[j].y);
                        ctx.stroke();
                    }
                }
            }

            // Draw nodes
            nodes.forEach(function (n) {
                var pulse = Math.sin(T * 1.8 + n.phase) * .5 + .5; // 0..1
                if (n.gold) {
                    ctx.shadowBlur  = 10 + pulse * 8;
                    ctx.shadowColor = '#f5a623';
                    ctx.fillStyle   = 'rgba(245,166,35,' + (.7 + pulse * .3) + ')';
                } else {
                    ctx.shadowBlur  = 0;
                    ctx.shadowColor = 'transparent';
                    ctx.fillStyle   = 'rgba(180,210,255,' + (.25 + pulse * .2) + ')';
                }
                ctx.beginPath();
                ctx.arc(n.x, n.y, n.r + (n.gold ? pulse : 0), 0, Math.PI * 2);
                ctx.fill();
                ctx.shadowBlur = 0;
            });

            // Spawn packets
            if (ts - lastPacketTime > PACKET_SPAWN_MS) {
                spawnPacket();
                lastPacketTime = ts;
            }

            // Draw + move packets
            packets = packets.filter(function (p) {
                p.t += PACKET_SPEED / Math.sqrt(
                    (p.bx-p.ax)*(p.bx-p.ax) + (p.by-p.ay)*(p.by-p.ay)
                );
                if (p.t >= 1) return false;
                var x = p.ax + (p.bx - p.ax) * p.t;
                var y = p.ay + (p.by - p.ay) * p.t;
                ctx.shadowBlur  = 12;
                ctx.shadowColor = '#f5a623';
                ctx.fillStyle   = '#f5a623';
                ctx.beginPath();
                ctx.arc(x, y, 3, 0, Math.PI * 2);
                ctx.fill();
                ctx.shadowBlur = 0;
                return true;
            });

            requestAnimationFrame(draw);
        }

        resize();
        window.addEventListener('resize', resize);
        requestAnimationFrame(draw);
    })();

    // ────────────────────────────────────────────────────────
    //  AI TERMINAL TYPEWRITER
    // ────────────────────────────────────────────────────────
    (function () {
        var terminal = document.getElementById('aiTerminal');
        if (!terminal) return;

        var lines = [
            { delay: 600,  html: '<span class="t-green">▶</span> <span class="t-muted">Lade Falldaten...</span>' },
            { delay: 1200, html: '<span class="t-green">✓</span> <span class="t-gold">Betrugstyp erkannt:</span> Krypto-Betrug (Konfidenz: 97.3%)' },
            { delay: 2000, html: '<span class="t-green">▶</span> <span class="t-muted">Analysiere Blockchain-Transaktionen...</span>' },
            { delay: 3100, html: '<span class="t-green">✓</span> <span class="t-blue">23 Wallet-Adressen</span> verknüpft' },
            { delay: 3800, html: '<span class="t-green">✓</span> <span class="t-blue">6 Exchange-Konten</span> identifiziert' },
            { delay: 4500, html: '<span class="t-green">▶</span> <span class="t-muted">Prüfe Rückforderungsmöglichkeiten...</span>' },
            { delay: 5500, html: '<span class="t-green">✓</span> <span class="t-gold">Rückforderungswahrscheinlichkeit: 89%</span>' },
            { delay: 6300, html: '<span class="t-green">✓</span> Mögliche Rückholung: <span class="t-blue">€ 41.700 – €47.200</span>' },
            { delay: 7200, html: '<span class="t-green">✓</span> Strategie: Chargeback + Rechtliches Verfahren (EU)' },
            { delay: 8200, html: '<span class="t-green">■</span> <span class="t-gold">Analyse abgeschlossen</span> · Bericht wird erstellt...' },
        ];

        var cursor = document.createElement('span');
        cursor.className = 'ai-cursor';

        lines.forEach(function (l) {
            setTimeout(function () {
                var div = document.createElement('div');
                div.innerHTML = l.html;
                terminal.appendChild(div);
                terminal.appendChild(cursor);
                terminal.scrollTop = terminal.scrollHeight;
            }, l.delay);
        });

        // Re-run animation every 14s
        setInterval(function () {
            terminal.innerHTML = '<div class="scan-line"></div><span class="t-muted"># Neue Analyse gestartet...<br></span>';
            lines.forEach(function (l) {
                setTimeout(function () {
                    var div = document.createElement('div');
                    div.innerHTML = l.html;
                    terminal.appendChild(div);
                    terminal.appendChild(cursor);
                    terminal.scrollTop = terminal.scrollHeight;
                }, l.delay);
            });
        }, 14000);
    })();

    // ────────────────────────────────────────────────────────
    //  INTERSECTION OBSERVER: counters, ring, progress bars
    // ────────────────────────────────────────────────────────
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            var el = entry.target;

            // Animated counter
            if (el.hasAttribute('data-counter')) {
                var target = parseInt(el.getAttribute('data-counter'), 10);
                var duration = 1800;
                var start = performance.now();
                (function tick(now) {
                    var p = Math.min((now - start) / duration, 1);
                    var eased = 1 - Math.pow(1 - p, 3); // ease-out-cubic
                    el.textContent = Math.round(eased * target).toLocaleString('de-DE');
                    if (p < 1) requestAnimationFrame(tick);
                })(start);
            }

            // SVG ring fill (87% success)
            if (el.id === 'ringFill') {
                el.classList.add('animated');
            }

            // AI progress bars
            if (el.classList.contains('ai-progress-fill')) {
                el.classList.add('animated');
            }

            io.unobserve(el);
        });
    }, { threshold: 0.25 });

    document.querySelectorAll('[data-counter]').forEach(function (el) { io.observe(el); });
    var ring = document.getElementById('ringFill');
    if (ring) io.observe(ring);
    document.querySelectorAll('.ai-progress-fill').forEach(function (el) { io.observe(el); });

    // ── Visitor tracking ───────────────────────────────────
    var visitId = null;
    var startTime = Date.now();

    (function logVisit() {
        var body = new FormData();
        body.append('action', 'visit');
        body.append('referrer', document.referrer || '');
        fetch('track.php', { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d && d.visit_id) {
                    visitId = d.visit_id;
                    ['visitIdHeroForm','visitIdMainFormV2','visitIdModalV2','visitIdEngV2'].forEach(function (id) {
                        var el = document.getElementById(id);
                        if (el) el.value = visitId;
                    });
                }
            }).catch(function () {});
    })();

    function sendTimeUpdate() {
        if (!visitId) return;
        var elapsed = Math.round((Date.now() - startTime) / 1000);
        var blob = new Blob(
            ['action=update&visit_id=' + encodeURIComponent(visitId) + '&time_on_site=' + encodeURIComponent(elapsed)],
            { type: 'application/x-www-form-urlencoded' }
        );
        if (navigator.sendBeacon) { navigator.sendBeacon('track.php', blob); }
        else { fetch('track.php', { method: 'POST', body: blob, keepalive: true }).catch(function () {}); }
    }
    // Fire on tab close / browser close (pagehide is most reliable cross-browser)
    window.addEventListener('pagehide', sendTimeUpdate);
    // Fallback: beforeunload covers cases pagehide misses
    window.addEventListener('beforeunload', sendTimeUpdate);
    // visibilitychange fires reliably on tab close in Chrome; avoids 0s issue
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') { sendTimeUpdate(); }
    });
    // Periodic beacon every 30 s so the DB is updated even if unload events are missed
    setInterval(sendTimeUpdate, 30000);

    // ── Engagement modal (configurable delay) ──────────────
    var engModal = document.getElementById('exitIntentModalV2');
    if (engModal) {
        var shown = false;
        var delay = parseInt(engModal.getAttribute('data-modal-delay') || '60', 10) * 1000;

        function isEngaged() { return sessionStorage.getItem('vr2_engaged') === '1'; }
        function markEngaged() { sessionStorage.setItem('vr2_engaged', '1'); }
        function showEng() {
            if (shown || isEngaged()) return;
            shown = true;
            new bootstrap.Modal(engModal).show();
        }
        document.querySelectorAll('form').forEach(function (f) {
            f.addEventListener('submit', markEngaged);
        });
        setTimeout(showEng, delay);
        document.addEventListener('mouseleave', function (e) {
            if (e.clientY < 5 && !shown && !isEngaged()) showEng();
        });
    }

    // ── Scam type modal (populates title + description dynamically) ──
    var scamModalV2El = document.getElementById('scamModalV2');
    if (scamModalV2El) {
        scamModalV2El.addEventListener('show.bs.modal', function (e) {
            var trigger = e.relatedTarget;
            document.getElementById('scamModalV2Title').textContent = trigger.dataset.scamType || '';
            document.getElementById('scamModalV2Desc').textContent  = trigger.dataset.scamDesc || '';
        });
    }

    // ── Smooth scroll for hash links ───────────────────────
    document.querySelectorAll('a[href^="#"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            var href = this.getAttribute('href');
            if (href === '#' || !href.startsWith('#')) return;
            var target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                var top = target.getBoundingClientRect().top + window.scrollY - 80;
                window.scrollTo({ top: top, behavior: 'smooth' });
            }
        });
    });

    // ── AI Network Canvas Animation (aiNetworkCanvas2) ─────
    (function () {
        var canvas = document.getElementById('aiNetworkCanvas2');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var W, H, nodes, edges, animId;
        var hudNodes = document.getElementById('aiNodes2');
        var hudEdges = document.getElementById('aiEdges2');
        var hudScams = document.getElementById('aiScams2');
        var hudRec   = document.getElementById('aiRecovered2');
        var stepEls  = ['aiStep1b','aiStep2b','aiStep3b','aiStep4b'].map(function(id){ return document.getElementById(id); });
        var activeStep = 0;
        var nodeCount = 0, edgeCount = 0, scamCount = 0, recAmt = 0;

        function resize() {
            var wrap = canvas.parentElement;
            W = canvas.width  = wrap.offsetWidth;
            H = canvas.height = wrap.offsetHeight - 80;
            if (H < 100) H = canvas.height = 200;
        }

        function makeNodes(n) {
            var arr = [];
            for (var i = 0; i < n; i++) {
                var r = 4 + Math.random() * 5;
                arr.push({
                    x: r + Math.random() * (W - r * 2),
                    y: r + Math.random() * (H - r * 2),
                    vx: (Math.random() - 0.5) * 0.6,
                    vy: (Math.random() - 0.5) * 0.6,
                    r: r,
                    type: Math.random() < 0.15 ? 'scam' : (Math.random() < 0.3 ? 'hub' : 'normal'),
                    pulse: Math.random() * Math.PI * 2
                });
            }
            return arr;
        }

        function init() {
            resize();
            nodes = makeNodes(28);
            edges = [];
            for (var i = 0; i < nodes.length; i++) {
                for (var j = i + 1; j < nodes.length; j++) {
                    var dx = nodes[i].x - nodes[j].x;
                    var dy = nodes[i].y - nodes[j].y;
                    if (Math.sqrt(dx*dx + dy*dy) < W * 0.28) {
                        edges.push({a: i, b: j});
                    }
                }
            }
        }

        function draw(ts) {
            ctx.clearRect(0, 0, W, H);
            // edges
            edges.forEach(function(e) {
                var a = nodes[e.a], b = nodes[e.b];
                var alpha = (a.type === 'scam' || b.type === 'scam') ? 0.5 : 0.15;
                var color = (a.type === 'scam' || b.type === 'scam') ? '245,82,82' : '255,255,255';
                ctx.beginPath();
                ctx.moveTo(a.x, a.y);
                ctx.lineTo(b.x, b.y);
                ctx.strokeStyle = 'rgba(' + color + ',' + alpha + ')';
                ctx.lineWidth = (a.type === 'scam' || b.type === 'scam') ? 1.5 : 0.7;
                ctx.stroke();
            });
            // nodes
            nodes.forEach(function(n) {
                n.pulse += 0.04;
                var pulse = Math.sin(n.pulse) * 0.3 + 0.7;
                var r = n.r;
                var color = n.type === 'scam' ? '#f55252' : (n.type === 'hub' ? '#f5a623' : '#4d9fff');
                // glow
                var grd = ctx.createRadialGradient(n.x, n.y, 0, n.x, n.y, r * 2.5);
                grd.addColorStop(0, color.replace('#','rgba(').replace(/(..)(..)(..)/, function(m,r,g,b){
                    return parseInt(r,16)+','+parseInt(g,16)+','+parseInt(b,16)+',';
                }) + (0.25 * pulse) + ')');
                grd.addColorStop(1, 'rgba(0,0,0,0)');
                ctx.beginPath();
                ctx.arc(n.x, n.y, r * 2.5, 0, Math.PI * 2);
                ctx.fillStyle = grd;
                ctx.fill();
                // core
                ctx.beginPath();
                ctx.arc(n.x, n.y, r * pulse, 0, Math.PI * 2);
                ctx.fillStyle = color;
                ctx.fill();
                // move
                n.x += n.vx; n.y += n.vy;
                if (n.x < n.r || n.x > W - n.r) n.vx *= -1;
                if (n.y < n.r || n.y > H - n.r) n.vy *= -1;
            });
            animId = requestAnimationFrame(draw);
        }

        // HUD counters
        var hudInterval = setInterval(function() {
            nodeCount = Math.min(nodeCount + Math.floor(Math.random()*12+3), 847);
            edgeCount = Math.min(edgeCount + Math.floor(Math.random()*18+5), 2341);
            scamCount = Math.min(scamCount + (Math.random()<0.3?1:0), 34);
            recAmt    = Math.min(recAmt + Math.floor(Math.random()*4000+500), 1240000);
            if (hudNodes) hudNodes.textContent = nodeCount;
            if (hudEdges) hudEdges.textContent = edgeCount;
            if (hudScams) hudScams.textContent = scamCount;
            if (hudRec)   hudRec.textContent   = '€' + (recAmt >= 1000 ? (recAmt/1000).toFixed(1) + 'K' : recAmt);
            // step cycling
            if (stepEls[0]) {
                var newStep = Math.floor(Date.now() / 3000) % 4;
                if (newStep !== activeStep) {
                    if (stepEls[activeStep]) stepEls[activeStep].classList.remove('active');
                    activeStep = newStep;
                    if (stepEls[activeStep]) stepEls[activeStep].classList.add('active');
                }
            }
        }, 400);

        // start when visible
        var obs = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    init();
                    animId = requestAnimationFrame(draw);
                } else {
                    if (animId) cancelAnimationFrame(animId);
                }
            });
        }, { threshold: 0.1 });
        obs.observe(canvas.parentElement);

        window.addEventListener('resize', function() {
            if (animId) cancelAnimationFrame(animId);
            init();
            animId = requestAnimationFrame(draw);
        });
    })();

})();
</script>

<!-- Visitor Tracking beacon on page load is already above; no duplicate needed -->
</body>
</html>
