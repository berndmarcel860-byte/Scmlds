<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title  = get_setting('page_title', 'VerlustRückholung – KI-gestützte Kapitalrückholung bei Anlagebetrug');
$modal_delay = max(5, (int) get_setting('modal_delay_seconds', '60'));
$success = isset($_GET['success']) && $_GET['success'] === '1';
$error   = isset($_GET['error']) ? htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') : '';

// Year range helper
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
        <a href="#fallformular" class="btn-cta-nav">
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
                    <a href="#fallformular" class="btn btn-warning btn-lg fw-bold px-4 py-3"
                       style="border-radius:12px;box-shadow:0 8px 24px rgba(245,166,35,.4);">
                        <i class="bi bi-shield-check me-2"></i>Kostenlose Erstprüfung
                    </a>
                    <a href="#wie-es-funktioniert" class="btn btn-outline-light btn-lg px-4 py-3"
                       style="border-radius:12px;border-color:rgba(255,255,255,.3);">
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

<!-- ===== FRAUD TYPES ===== -->
<section id="betrugsarten" class="py-6" style="background:var(--bg-light)">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="section-eyebrow">Betrugsarten</div>
            <h2 class="fw-bold display-6">Bei diesen Betrugsformen helfen wir</h2>
            <p class="text-muted">Wir haben Erfahrung mit allen gängigen Formen von Anlagebetrug.</p>
        </div>
        <?php $cases = [
            ['icon'=>'₿','name'=>'Krypto-Betrug','desc'=>'Fake-Kryptobörsen, Rug-Pulls, gefälschte Investment-Pools'],
            ['icon'=>'📈','name'=>'Forex-Betrug','desc'=>'Unregulierte Handelssysteme, Kursmanipulation'],
            ['icon'=>'🏢','name'=>'Fake-Broker','desc'=>'Unlizenzierte Broker, gesperrte Auszahlungen'],
            ['icon'=>'💔','name'=>'Romance-Scam','desc'=>'Romantik-Betrug verbunden mit Investitionsaufforderungen'],
            ['icon'=>'📊','name'=>'Binäre Optionen','desc'=>'Betrügerische Binary-Options-Plattformen'],
            ['icon'=>'🔒','name'=>'Andere Betrugsformen','desc'=>'Ponzi-Schemes, ICO-Betrug, NFT-Fraud und mehr'],
        ]; ?>
        <div class="row g-3">
            <?php foreach ($cases as $i => $c): ?>
            <div class="col-6 col-md-4 col-lg-2" data-aos="zoom-in" data-aos-delay="<?= $i * 80 ?>">
                <div class="case-card" data-bs-toggle="modal" data-bs-target="#fullFormModal">
                    <div class="case-icon"><?= $c['icon'] ?></div>
                    <div class="fw-bold small"><?= $c['name'] ?></div>
                    <div class="text-muted" style="font-size:.75rem;margin-top:.35rem;"><?= $c['desc'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
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
                <a href="#fallformular" class="btn btn-primary btn-lg mt-4" style="border-radius:12px;">
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
                <a href="#fallformular" class="btn btn-warning btn-sm fw-bold mt-2">
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
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content eng-modal-content">
            <div class="row g-0">
                <!-- Left panel -->
                <div class="col-lg-4 eng-modal-left d-none d-lg-flex flex-column">
                    <div>
                        <div class="mb-4">
                            <span class="badge bg-warning text-dark rounded-pill px-3 py-2">
                                <i class="bi bi-gift me-1"></i>Für Sie reserviert
                            </span>
                        </div>
                        <h4 class="fw-bold text-white mb-1">Warten Sie –</h4>
                        <p class="text-white-50 small mb-4">Ihr Kapital könnte noch rückforderbar sein.</p>
                        <div class="eng-stat"><span class="val">87%</span><span class="lbl">Erfolgsquote bei unseren Fällen</span></div>
                        <div class="eng-stat"><span class="val">€48M+</span><span class="lbl">für Mandanten zurückgeholt</span></div>
                        <div class="eng-stat"><span class="val">€0</span><span class="lbl">Kosten vorab für Sie</span></div>
                    </div>
                    <div class="mt-auto">
                        <div class="d-flex align-items-center gap-2">
                            <span class="live-dot"></span>
                            <span class="text-white-50 small">KI-Analyse läuft</span>
                        </div>
                    </div>
                </div>
                <!-- Right: full form -->
                <div class="col-lg-8 p-4 p-lg-5 position-relative">
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                            data-bs-dismiss="modal"></button>
                    <h4 class="fw-bold mb-1">Kostenlose Fallprüfung</h4>
                    <p class="text-muted small mb-4">Alle Felder ausfüllen – dauert nur 2 Minuten.</p>
                    <form action="submit_lead.php" method="POST" id="engFormV2" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="lead_source" value="engagement_modal">
                        <input type="hidden" name="visit_id" id="visitIdEngV2" value="">
                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold">Vorname *</label>
                                <input type="text" name="first_name" class="form-control form-control-sm" placeholder="Max" required>
                                <div class="invalid-feedback">Bitte eingeben.</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold">Nachname *</label>
                                <input type="text" name="last_name" class="form-control form-control-sm" placeholder="Mustermann" required>
                                <div class="invalid-feedback">Bitte eingeben.</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold">E-Mail *</label>
                                <input type="email" name="email" class="form-control form-control-sm" placeholder="max@example.de" required>
                                <div class="invalid-feedback">Bitte gültige E-Mail eingeben.</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold">Telefon</label>
                                <input type="tel" name="phone" class="form-control form-control-sm" placeholder="+49 123 456789">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold">Land *</label>
                                <select name="country" class="form-select form-select-sm" required>
                                    <option value="">Bitte wählen...</option>
                                    <option value="Deutschland">🇩🇪 Deutschland</option>
                                    <option value="Österreich">🇦🇹 Österreich</option>
                                    <option value="Schweiz">🇨🇭 Schweiz</option>
                                    <option value="USA">🇺🇸 USA</option>
                                    <option value="Vereinigtes Königreich">🇬🇧 Vereinigtes Königreich</option>
                                    <option value="Andere">🌍 Anderes Land</option>
                                </select>
                                <div class="invalid-feedback">Bitte Land auswählen.</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold">Jahr des Verlusts</label>
                                <select name="year_lost" class="form-select form-select-sm">
                                    <option value="">Jahr (optional)</option>
                                    <?php foreach ($years as $y): ?>
                                    <option value="<?= $y ?>"><?= $y ?></option>
                                    <?php endforeach; ?>
                                    <option value="2014">Vor 2015</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold">Betrag (ca.) *</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">€</span>
                                    <input type="number" name="amount_lost" class="form-control" placeholder="10000" min="1" required>
                                </div>
                                <div class="invalid-feedback">Bitte Betrag eingeben.</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold">Betrugsart *</label>
                                <select name="platform_category" class="form-select form-select-sm" required>
                                    <option value="">Betrugsart wählen...</option>
                                    <option value="Krypto-Betrug">₿ Krypto-Betrug</option>
                                    <option value="Forex-Betrug">📈 Forex-Betrug</option>
                                    <option value="Fake-Broker">🏢 Fake-Broker</option>
                                    <option value="Romance-Scam mit Investitionsbetrug">💔 Romance-Scam</option>
                                    <option value="Binäre Optionen">📊 Binäre Optionen</option>
                                    <option value="Andere">❓ Andere</option>
                                </select>
                                <div class="invalid-feedback">Bitte auswählen.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Kurze Fallbeschreibung *</label>
                                <textarea name="case_description" class="form-control form-control-sm" rows="2" required
                                          placeholder="Was ist passiert? Welche Plattform? Seit wann kein Zugriff?"></textarea>
                                <div class="invalid-feedback">Bitte Beschreibung eingeben.</div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="engPrivacyV2" required>
                                    <label class="form-check-label small text-muted" for="engPrivacyV2">
                                        Ich stimme der <a href="#" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#privacyModalV2">Datenschutzerklärung</a> zu. *
                                    </label>
                                    <div class="invalid-feedback">Bitte akzeptieren.</div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold" style="border-radius:10px;">
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

<!-- Full form modal (triggered from fraud type cards) -->
<div class="modal fade" id="fullFormModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0" style="background:linear-gradient(135deg,#0d2b5e,#1a4a9e);">
                <h5 class="modal-title fw-bold text-white"><i class="bi bi-shield-check me-2 text-warning"></i>Kostenlose KI-Fallprüfung</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form action="submit_lead.php" method="POST" id="modalFormV2" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="visit_id" id="visitIdModalV2" value="">
                    <div class="row g-3">
                        <div class="col-sm-6"><label class="form-label fw-semibold small">Vorname *</label>
                            <input type="text" name="first_name" class="form-control" placeholder="Max" required>
                            <div class="invalid-feedback">Pflichtfeld</div></div>
                        <div class="col-sm-6"><label class="form-label fw-semibold small">Nachname *</label>
                            <input type="text" name="last_name" class="form-control" placeholder="Mustermann" required>
                            <div class="invalid-feedback">Pflichtfeld</div></div>
                        <div class="col-sm-6"><label class="form-label fw-semibold small">E-Mail *</label>
                            <input type="email" name="email" class="form-control" placeholder="max@example.de" required>
                            <div class="invalid-feedback">Gültige E-Mail eingeben</div></div>
                        <div class="col-sm-6"><label class="form-label fw-semibold small">Telefon</label>
                            <input type="tel" name="phone" class="form-control" placeholder="+49 123 456789"></div>
                        <div class="col-sm-6"><label class="form-label fw-semibold small">Land *</label>
                            <select name="country" class="form-select" required>
                                <option value="">Land auswählen...</option>
                                <option>🇩🇪 Deutschland</option><option>🇦🇹 Österreich</option>
                                <option>🇨🇭 Schweiz</option><option>🇺🇸 USA</option>
                                <option value="Vereinigtes Königreich">🇬🇧 Vereinigtes Königreich</option>
                                <option value="Andere">🌍 Anderes Land</option>
                            </select>
                            <div class="invalid-feedback">Pflichtfeld</div></div>
                        <div class="col-sm-6"><label class="form-label fw-semibold small">Jahr des Verlusts</label>
                            <select name="year_lost" class="form-select">
                                <option value="">Auswählen...</option>
                                <?php foreach ($years as $y): ?><option value="<?= $y ?>"><?= $y ?></option><?php endforeach; ?>
                                <option value="2014">Vor 2015</option>
                            </select></div>
                        <div class="col-sm-6"><label class="form-label fw-semibold small">Betrag (ca.) *</label>
                            <div class="input-group"><span class="input-group-text">€</span>
                                <input type="number" name="amount_lost" class="form-control" placeholder="10000" min="1" required>
                            </div><div class="invalid-feedback">Pflichtfeld</div></div>
                        <div class="col-sm-6"><label class="form-label fw-semibold small">Betrugsart *</label>
                            <select name="platform_category" class="form-select" required>
                                <option value="">Betrugsart...</option>
                                <option value="Krypto-Betrug">₿ Krypto-Betrug</option>
                                <option value="Forex-Betrug">📈 Forex-Betrug</option>
                                <option value="Fake-Broker">🏢 Fake-Broker</option>
                                <option value="Romance-Scam mit Investitionsbetrug">💔 Romance-Scam</option>
                                <option value="Binäre Optionen">📊 Binäre Optionen</option>
                                <option value="Andere">❓ Andere</option>
                            </select><div class="invalid-feedback">Pflichtfeld</div></div>
                        <div class="col-12"><label class="form-label fw-semibold small">Fallbeschreibung *</label>
                            <textarea name="case_description" class="form-control" rows="3" required
                                      placeholder="Was ist passiert? Welche Plattform? Seit wann kein Zugriff?"></textarea>
                            <div class="invalid-feedback">Pflichtfeld</div></div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="modalPrivV2" required>
                                <label class="form-check-label small text-muted" for="modalPrivV2">
                                    Ich stimme der <a href="#" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#privacyModalV2">Datenschutzerklärung</a> zu. *
                                </label>
                                <div class="invalid-feedback">Bitte akzeptieren.</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold">
                                <i class="bi bi-search me-2"></i>Kostenlos einreichen
                            </button>
                        </div>
                    </div>
                </form>
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
                <h6>1. Verantwortlicher</h6><p>VerlustRückholung GmbH, Musterstraße 1, 10115 Berlin</p>
                <h6>2. Datenerhebung</h6><p>Wir erheben Ihre personenbezogenen Daten ausschließlich zur Fallbearbeitung gemäß Art. 6 Abs. 1 lit. b DSGVO.</p>
                <h6>3. Ihre Rechte</h6><p>Sie haben das Recht auf Auskunft, Berichtigung, Löschung und Einschränkung der Verarbeitung Ihrer Daten.</p>
                <h6>4. Kontakt</h6><p>datenschutz@verlustrueckholung.de</p>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button></div>
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
                <h6>Kontakt</h6><p>E-Mail: info@verlustrueckholung.de</p>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button></div>
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

    // ── Form validation ────────────────────────────────────
    document.querySelectorAll('form[novalidate]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
            form.classList.add('was-validated');
        });
    });

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
    window.addEventListener('pagehide', sendTimeUpdate);
    window.addEventListener('beforeunload', sendTimeUpdate);

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

})();
</script>

<!-- Visitor Tracking beacon on page load is already above; no duplicate needed -->
</body>
</html>
