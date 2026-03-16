<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$page_title   = get_setting('page_title', 'VerlustRückholung – KI-gestützte Kapitalrückholung bei Anlagebetrug');
$modal_delay  = max(5, (int) get_setting('modal_delay_seconds', '60'));
$success = isset($_GET['success']) && $_GET['success'] === '1';
$error   = isset($_GET['error'])   ? htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') : '';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <meta name="description" content="VerlustRückholung nutzt Künstliche Intelligenz zur Analyse verdächtiger Transaktionen und Prüfung möglicher Rückholungen bei Anlagebetrug – 87% Erfolgsquote, kostenlose Erstprüfung.">
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">
            <i class="bi bi-shield-check me-2 text-warning"></i>VerlustRückholung
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navContent">
            <ul class="navbar-nav ms-auto me-3">
                <li class="nav-item"><a class="nav-link" href="#leistungen">Leistungen</a></li>
                <li class="nav-item"><a class="nav-link" href="#vorteile">Vorteile</a></li>
                <li class="nav-item"><a class="nav-link" href="#statistiken">Statistiken</a></li>
                <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
                <li class="nav-item"><a class="nav-link" href="#kontakt">Kontakt</a></li>
            </ul>
            <a href="#fallform" class="btn btn-warning fw-semibold px-4">
                <i class="bi bi-send me-1"></i>Kostenlose Beratung
            </a>
        </div>
    </div>
</nav>

<!-- ===== CRYPTO TICKER STRIP ===== -->
<div class="ticker-strip" id="cryptoTickerStrip">
    <div class="ticker-label-box">
        <span class="live-dot-sm"></span>Live
    </div>
    <div class="ticker-track-wrap">
        <div class="ticker-track" id="tickerTrack">
            <!-- filled by JS -->
        </div>
    </div>
</div>

<!-- ===== HERO SECTION ===== -->
<section id="hero" class="hero-section d-flex align-items-center">
    <!-- Animated canvas background -->
    <canvas id="heroBg" class="hero-canvas"></canvas>
    <div class="hero-overlay"></div>

    <!-- Floating price tags -->
    <div class="hero-price-tags" id="heroPriceTags">
        <div class="price-tag" id="ptBTC" style="top:18%;left:2%">
            <i class="bi bi-currency-bitcoin text-warning"></i>
            <span class="pt-sym">BTC</span>
            <span class="pt-val" data-base="67241.50">67,241.50</span>
            <span class="pt-chg up">+2.4%</span>
        </div>
        <div class="price-tag d-none d-xl-flex" id="ptETH" style="top:55%;left:1%">
            <i class="bi bi-currency-exchange text-info"></i>
            <span class="pt-sym">ETH</span>
            <span class="pt-val" data-base="3541.20">3,541.20</span>
            <span class="pt-chg up">+1.8%</span>
        </div>
        <div class="price-tag d-none d-lg-flex" id="ptEURUSD" style="top:75%;right:2%">
            <span class="pt-sym">EUR/USD</span>
            <span class="pt-val" data-base="1.0842">1.0842</span>
            <span class="pt-chg down">-0.3%</span>
        </div>
        <div class="price-tag d-none d-xl-flex" id="ptXRP" style="top:30%;right:1%">
            <span class="pt-sym">XRP</span>
            <span class="pt-val" data-base="0.5312">0.5312</span>
            <span class="pt-chg up">+4.1%</span>
        </div>
    </div>

    <div class="container position-relative z-1">
        <div class="row align-items-center g-5">
            <!-- Left column -->
            <div class="col-lg-7" data-aos="fade-right">
                <div class="badge bg-warning text-dark mb-3 px-3 py-2 rounded-pill fs-6">
                    <i class="bi bi-cpu me-1"></i>KI-gestützte Transaktionsanalyse
                </div>
                <h1 class="display-4 fw-bold text-white mb-4 lh-sm">
                    Ihr verlorenes Kapital<br>
                    <span class="text-warning">zurückfordern</span> – mit<br>
                    modernster KI-Technologie
                </h1>
                <p class="lead text-white-75 mb-4">
                    Wurden Sie Opfer einer betrügerischen Investitionsplattform?
                    <strong class="text-white">Künstliche Intelligenz zur Analyse verdächtiger Transaktionen
                    und Prüfung möglicher Rückholungen</strong> – schnell, diskret und nachweislich erfolgreich.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <button class="btn btn-warning btn-lg fw-bold px-5 shadow-lg"
                            data-bs-toggle="modal" data-bs-target="#fallPruefenModal">
                        <i class="bi bi-search me-2"></i>Jetzt Fall überprüfen
                    </button>
                    <button class="btn btn-outline-light btn-lg px-4"
                            data-bs-toggle="modal" data-bs-target="#infoModal">
                        <i class="bi bi-play-circle me-2"></i>Wie es funktioniert
                    </button>
                </div>
                <div class="d-flex flex-wrap gap-4">
                    <div class="d-flex align-items-center text-white gap-2">
                        <i class="bi bi-check-circle-fill text-warning fs-5"></i>
                        <span>87% Erfolgsquote</span>
                    </div>
                    <div class="d-flex align-items-center text-white gap-2">
                        <i class="bi bi-check-circle-fill text-warning fs-5"></i>
                        <span>Kostenlose Erstberatung</span>
                    </div>
                    <div class="d-flex align-items-center text-white gap-2">
                        <i class="bi bi-check-circle-fill text-warning fs-5"></i>
                        <span>100% Diskret</span>
                    </div>
                </div>
            </div>

            <!-- Right column: Quick capture form -->
            <div class="col-lg-5" data-aos="fade-left">
                <div class="hero-form-card">
                    <div class="hero-form-header">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="live-dot"></span>
                            <span class="text-success small fw-semibold">KI-System aktiv</span>
                        </div>
                        <h4 class="fw-bold text-white mb-1">Kostenlose Fallprüfung</h4>
                        <p class="text-white-50 small mb-0">
                            In 48 Stunden erfahren Sie, ob wir helfen können.
                        </p>
                    </div>
                    <div class="hero-form-body">
                        <div class="mb-3">
                            <input type="text" class="form-control hero-input" id="heroName"
                                   placeholder="Ihr vollständiger Name" autocomplete="name">
                        </div>
                        <div class="mb-3">
                            <input type="email" class="form-control hero-input" id="heroEmail"
                                   placeholder="Ihre E-Mail-Adresse" autocomplete="email">
                        </div>
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text hero-input-prefix">€</span>
                                <input type="number" class="form-control hero-input" id="heroAmount"
                                       placeholder="Ca. verlorener Betrag" min="0">
                            </div>
                        </div>
                        <button class="btn btn-warning w-100 fw-bold py-3 btn-hero-submit"
                                data-bs-toggle="modal" data-bs-target="#fallPruefenModal">
                            <i class="bi bi-arrow-right-circle me-2"></i>Jetzt Fall überprüfen
                        </button>
                        <div class="text-center mt-2">
                            <span class="text-white-50 small">
                                <i class="bi bi-shield-lock me-1 text-success"></i>
                                100% kostenlos &amp; unverbindlich · DSGVO-konform
                            </span>
                        </div>
                    </div>
                    <div class="hero-form-footer">
                        <div class="row g-0 text-center">
                            <div class="col-4 border-end border-secondary">
                                <div class="text-warning fw-bold">2.400+</div>
                                <div class="text-white-50 small">Fälle geprüft</div>
                            </div>
                            <div class="col-4 border-end border-secondary">
                                <div class="text-warning fw-bold">€48M+</div>
                                <div class="text-white-50 small">Rückgefordert</div>
                            </div>
                            <div class="col-4">
                                <div class="text-warning fw-bold">87%</div>
                                <div class="text-white-50 small">Erfolgsquote</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

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
        <div class="news-ticker-inner" id="newsTicker">
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

<!-- ===== AI 3D VISUALIZATION SECTION ===== -->
<section id="ai-visual" class="ai-section py-6">
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
                    <canvas id="aiNetworkCanvas"></canvas>
                    <div class="ai-canvas-hud">
                        <div class="ai-hud-row">
                            <span class="ai-hud-label">Knoten analysiert</span>
                            <span class="ai-hud-val text-warning" id="aiNodes">0</span>
                        </div>
                        <div class="ai-hud-row">
                            <span class="ai-hud-label">Verbindungen geprüft</span>
                            <span class="ai-hud-val text-info" id="aiEdges">0</span>
                        </div>
                        <div class="ai-hud-row">
                            <span class="ai-hud-label">Betrug erkannt</span>
                            <span class="ai-hud-val text-danger" id="aiScams">0</span>
                        </div>
                        <div class="ai-hud-row">
                            <span class="ai-hud-label">Kapital gesichert</span>
                            <span class="ai-hud-val text-success" id="aiRecovered">€0</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 order-lg-1" data-aos="fade-right">
                <div class="ai-steps">
                    <div class="ai-step-item" id="aiStep1">
                        <div class="ai-step-icon bg-primary">
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
                    <div class="ai-step-item" id="aiStep2">
                        <div class="ai-step-icon bg-warning">
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
                    <div class="ai-step-item" id="aiStep3">
                        <div class="ai-step-icon bg-danger">
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
                    <div class="ai-step-item" id="aiStep4">
                        <div class="ai-step-icon bg-success">
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
                            data-bs-toggle="modal" data-bs-target="#fallPruefenModal">
                        <i class="bi bi-arrow-right-circle me-2"></i>Jetzt kostenlos starten
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>


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
                        <div class="step-item">
                            <div class="step-number">1</div>
                            <div>
                                <strong>Falleinreichung</strong>
                                <p class="small text-muted mb-0">Sie schildern Ihren Fall kostenlos über unser Formular</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">2</div>
                            <div>
                                <strong>KI-Analyse</strong>
                                <p class="small text-muted mb-0">Unser System analysiert Transaktionen und Plattformstrukturen</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">3</div>
                            <div>
                                <strong>Ergebnisbericht</strong>
                                <p class="small text-muted mb-0">Sie erhalten einen detaillierten Bericht mit Handlungsempfehlungen</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">4</div>
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

<!-- ===== WHY US SECTION ===== -->
<section id="vorteile" class="py-6 bg-light">
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
                    <h5 class="fw-bold mb-2">Diskrete & sichere Bearbeitung</h5>
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

<!-- ===== SCAM TYPES SECTION ===== -->
<section id="betrugsarten" class="py-6">
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
                     data-bs-toggle="modal" data-bs-target="#scamModal"
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
                     data-bs-toggle="modal" data-bs-target="#scamModal"
                     data-scam-type="Forex-Betrug"
                     data-scam-desc="Betrügerische Forex-Broker manipulieren Kurse, verweigern Auszahlungen und arbeiten ohne gültige Regulierung. Unser System verknüpft Zahlungsströme mit bekannten Betrugsstrukturen und identifiziert die Verantwortlichen hinter diesen Plattformen.">
                    <div class="d-flex align-items-center mb-3">
                        <div class="scam-icon bg-primary-subtle rounded-3 p-2 me-3">
                            <i class="bi bi-currency-exchange text-primary fs-4"></i>
                        </div>
                        <h6 class="fw-bold mb-0">Forex-Betrug</h6>
                    </div>
                    <p class="text-muted small mb-2">
                        Unregulierte Forex-Broker, Kurssmanipulation und verweigerte Auszahlungen.
                    </p>
                    <span class="badge bg-primary-subtle text-primary">Mehr erfahren →</span>
                </div>
            </div>
            <div class="col-md-6 col-lg-4" data-aos="zoom-in" data-aos-delay="300">
                <div class="scam-card p-4 rounded-4 border h-100 cursor-pointer"
                     data-bs-toggle="modal" data-bs-target="#scamModal"
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
                     data-bs-toggle="modal" data-bs-target="#scamModal"
                     data-scam-type="Romance-Scam"
                     data-scam-desc="Bei Romance-Scams werden emotionale Beziehungen aufgebaut, um Vertrauen zu gewinnen – anschließend werden Opfer zu Investitionen überredet. Diese Kombination aus emotionaler Manipulation und Investitionsbetrug ist besonders perfide. Wir unterstützen Opfer diskret bei der Aufarbeitung und Rückforderung.">
                    <div class="d-flex align-items-center mb-3">
                        <div class="scam-icon bg-pink-subtle rounded-3 p-2 me-3">
                            <i class="bi bi-heart-arrow text-danger fs-4"></i>
                        </div>
                        <h6 class="fw-bold mb-0">Romance-Scam & Investment</h6>
                    </div>
                    <p class="text-muted small mb-2">
                        Emotionale Manipulation kombiniert mit gefälschten Investmentangeboten.
                    </p>
                    <span class="badge bg-danger-subtle text-danger">Mehr erfahren →</span>
                </div>
            </div>
            <div class="col-md-6 col-lg-4" data-aos="zoom-in" data-aos-delay="500">
                <div class="scam-card p-4 rounded-4 border h-100 cursor-pointer"
                     data-bs-toggle="modal" data-bs-target="#scamModal"
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
                     data-bs-toggle="modal" data-bs-target="#scamModal"
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
                            <span><i class="bi bi-heart-arrow me-2 text-pink"></i>Romance-Scam</span>
                            <span>8%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-pink" style="width: 8%; background-color: #ff69b4 !important;"></div>
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

<!-- ===== LEAD FORM SECTION ===== -->
<section id="fallform" class="py-6">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="row g-0 rounded-4 overflow-hidden shadow-lg">
                    <div class="col-lg-5 form-sidebar d-flex flex-column justify-content-between">
                        <div>
                            <h3 class="fw-bold text-white mb-3">Kostenlose Erstberatung</h3>
                            <p class="text-white-75 mb-4">
                                Schildern Sie uns Ihren Fall. Wir prüfen kostenlos, ob und wie wir Ihnen
                                helfen können, Ihr verlorenes Kapital zurückzufordern.
                            </p>
                            <ul class="list-unstyled">
                                <li class="d-flex align-items-center mb-3 text-white">
                                    <i class="bi bi-check-circle-fill text-warning me-3 fs-5"></i>
                                    <span>100% kostenlose Erstprüfung</span>
                                </li>
                                <li class="d-flex align-items-center mb-3 text-white">
                                    <i class="bi bi-check-circle-fill text-warning me-3 fs-5"></i>
                                    <span>Antwort innerhalb von 48 Stunden</span>
                                </li>
                                <li class="d-flex align-items-center mb-3 text-white">
                                    <i class="bi bi-check-circle-fill text-warning me-3 fs-5"></i>
                                    <span>Streng vertrauliche Bearbeitung</span>
                                </li>
                                <li class="d-flex align-items-center mb-3 text-white">
                                    <i class="bi bi-check-circle-fill text-warning me-3 fs-5"></i>
                                    <span>Keine Vorauszahlung erforderlich</span>
                                </li>
                                <li class="d-flex align-items-center text-white">
                                    <i class="bi bi-check-circle-fill text-warning me-3 fs-5"></i>
                                    <span>DSGVO-konformer Datenschutz</span>
                                </li>
                            </ul>
                        </div>
                        <div class="mt-4 p-3 bg-white bg-opacity-10 rounded-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-telephone-fill text-warning me-2"></i>
                                <strong class="text-white">Telefonische Beratung</strong>
                            </div>
                            <div class="text-white-75 small">Mo–Fr: 09:00–18:00 Uhr</div>
                            <div class="text-warning fw-bold">+49 (0) 30 – 000 00 00</div>
                        </div>
                    </div>
                    <div class="col-lg-7 bg-white p-4 p-lg-5">
                        <?php if ($success): ?>
                            <div class="alert alert-success d-flex align-items-center" role="alert">
                                <i class="bi bi-check-circle-fill me-2 fs-4"></i>
                                <div>
                                    <strong>Vielen Dank!</strong> Ihr Fall wurde erfolgreich eingereicht.
                                    Wir melden uns innerhalb von 48 Stunden bei Ihnen.
                                </div>
                            </div>
                        <?php elseif ($error): ?>
                            <div class="alert alert-danger d-flex align-items-center" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
                                <div><strong>Fehler:</strong> <?= $error ?></div>
                            </div>
                        <?php endif; ?>
                        <h4 class="fw-bold mb-4">Fall einreichen</h4>
                        <form action="submit_lead.php" method="POST" id="leadForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="visit_id" id="visitIdLeadForm" value="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Vorname *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-person text-muted"></i></span>
                                        <input type="text" name="first_name" class="form-control border-start-0 ps-1" placeholder="Max" required>
                                    </div>
                                    <div class="invalid-feedback">Bitte Vorname eingeben.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Nachname *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-person-fill text-muted"></i></span>
                                        <input type="text" name="last_name" class="form-control border-start-0 ps-1" placeholder="Mustermann" required>
                                    </div>
                                    <div class="invalid-feedback">Bitte Nachname eingeben.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">E-Mail-Adresse *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                        <input type="email" name="email" class="form-control border-start-0 ps-1" placeholder="max@example.de" required>
                                    </div>
                                    <div class="invalid-feedback">Bitte gültige E-Mail eingeben.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Telefonnummer</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-telephone text-muted"></i></span>
                                        <input type="tel" name="phone" class="form-control border-start-0 ps-1" placeholder="+49 123 456789">
                                    </div>
                                </div>
                                <div class="col-md-6">
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
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Jahr des Verlusts</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar3 text-muted"></i></span>
                                        <select name="year_lost" class="form-select border-start-0 ps-1">
                                            <option value="">Jahr auswählen...</option>
                                            <?php for ($y = date('Y'); $y >= 2015; $y--): ?>
                                            <option value="<?= $y ?>"><?= $y ?></option>
                                            <?php endfor; ?>
                                            <option value="2014">Vor 2015</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Verlorener Betrag (ca.) *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">€</span>
                                        <input type="number" name="amount_lost" class="form-control" placeholder="10000" min="0" required>
                                    </div>
                                    <div class="invalid-feedback">Bitte Betrag eingeben.</div>
                                </div>
                                <div class="col-md-6">
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
                                    <label class="form-label fw-semibold small">Beschreibung des Falls *</label>
                                    <textarea name="case_description" class="form-control" rows="4"
                                              placeholder="Beschreiben Sie kurz, was passiert ist: Wie haben Sie die Plattform gefunden? Welchen Betrag haben Sie investiert? Seit wann haben Sie keinen Zugriff mehr auf Ihr Geld?" required></textarea>
                                    <div class="invalid-feedback">Bitte Fallbeschreibung eingeben.</div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="privacy" required>
                                        <label class="form-check-label small" for="privacy">
                                            Ich stimme der <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Datenschutzerklärung</a> zu
                                            und bin damit einverstanden, dass meine Daten zur Fallbearbeitung verwendet werden. *
                                        </label>
                                        <div class="invalid-feedback">Bitte Datenschutzerklärung akzeptieren.</div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold py-3">
                                        <i class="bi bi-send me-2"></i>Fall kostenlos einreichen
                                    </button>
                                    <p class="text-muted small text-center mt-2 mb-0">
                                        <i class="bi bi-lock me-1"></i>Ihre Daten werden SSL-verschlüsselt übertragen
                                    </p>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== FAQ SECTION ===== -->
<section id="faq" class="py-6 bg-light">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="badge bg-primary text-white rounded-pill px-3 py-2 mb-3">Häufige Fragen</span>
            <h2 class="display-6 fw-bold">Ihre Fragen – unsere Antworten</h2>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item border-0 mb-3 rounded-3 overflow-hidden shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Ist die Erstberatung wirklich kostenlos?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                Ja, die Erstprüfung Ihres Falles ist vollständig kostenlos und unverbindlich.
                                Wir analysieren Ihren Fall und teilen Ihnen mit, ob und wie wir Ihnen helfen können –
                                ohne jegliche Vorauszahlung oder versteckte Kosten.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item border-0 mb-3 rounded-3 overflow-hidden shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Wie lange dauert die Analyse meines Falls?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                Innerhalb von 48 Stunden nach Eingang Ihrer Anfrage erhalten Sie eine erste Einschätzung.
                                Die vollständige KI-gestützte Analyse dauert in der Regel 5–10 Werktage,
                                abhängig von der Komplexität des Falls.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item border-0 mb-3 rounded-3 overflow-hidden shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Welche Betrugsformen können überprüft werden?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                Wir spezialisieren uns auf alle Formen von Anlagebetrug: Krypto-Scams, Forex-Betrug,
                                Fake-Broker, Romance-Scam mit Investitionsbetrug, Binäre Optionen und weitere
                                Online-Investitionsbetrug. Wenn Sie sich unsicher sind, reichen Sie einfach
                                Ihren Fall ein – wir prüfen es für Sie.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item border-0 mb-3 rounded-3 overflow-hidden shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Welche Dokumente werden benötigt?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                Für die Erstprüfung benötigen wir nur Ihre Fallbeschreibung über das Formular.
                                Für die vollständige Analyse sind folgende Dokumente hilfreich:
                                Kontoauszüge, E-Mail-Korrespondenz mit der Plattform, Screenshots der Handelsplattform,
                                Zahlungsbelege und alle verfügbaren Kommunikationsnachweise.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item border-0 mb-3 rounded-3 overflow-hidden shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                Was passiert, wenn die Rückforderung erfolgreich ist?
                            </button>
                        </h2>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                Im Erfolgsfall wird eine erfolgsorientierte Gebühr vereinbart,
                                die Sie erst nach erfolgreicher Rückforderung zahlen. Scheitert die Rückforderung,
                                zahlen Sie nichts. Genaue Konditionen besprechen wir individuell nach der
                                kostenlosen Erstprüfung.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item border-0 rounded-3 overflow-hidden shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                Wie sicher sind meine persönlichen Daten?
                            </button>
                        </h2>
                        <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                Ihre Daten werden nach den strengsten Datenschutzstandards (DSGVO) verarbeitet,
                                SSL-verschlüsselt übertragen und ausschließlich zur Fallbearbeitung verwendet.
                                Wir geben keine Daten an Dritte weiter ohne Ihre ausdrückliche Zustimmung.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="footer-section pt-5 pb-3">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-lg-4">
                <a class="text-decoration-none" href="#">
                    <h4 class="fw-bold text-white"><i class="bi bi-shield-check me-2 text-warning"></i>VerlustRückholung</h4>
                </a>
                <p class="text-white-50 mt-2">
                    Künstliche Intelligenz zur Analyse verdächtiger Transaktionen und Prüfung
                    möglicher Rückholungen – professionelle Unterstützung bei Anlagebetrug.
                </p>
                <div class="d-flex gap-2 mt-3">
                    <a href="#" class="btn btn-sm btn-outline-light rounded-circle"><i class="bi bi-linkedin"></i></a>
                    <a href="#" class="btn btn-sm btn-outline-light rounded-circle"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="btn btn-sm btn-outline-light rounded-circle"><i class="bi bi-facebook"></i></a>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="text-white fw-bold mb-3">Navigation</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="#leistungen" class="text-white-50 text-decoration-none">Leistungen</a></li>
                    <li class="mb-2"><a href="#vorteile" class="text-white-50 text-decoration-none">Vorteile</a></li>
                    <li class="mb-2"><a href="#statistiken" class="text-white-50 text-decoration-none">Statistiken</a></li>
                    <li class="mb-2"><a href="#faq" class="text-white-50 text-decoration-none">FAQ</a></li>
                    <li><a href="#fallform" class="text-white-50 text-decoration-none">Kontakt</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="text-white fw-bold mb-3">Rechtliches</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="#" class="text-white-50 text-decoration-none" data-bs-toggle="modal" data-bs-target="#impressumModal">Impressum</a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="text-white-50 text-decoration-none" data-bs-toggle="modal" data-bs-target="#privacyModal">Datenschutz</a>
                    </li>
                    <li><a href="#" class="text-white-50 text-decoration-none">AGB</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h6 class="text-white fw-bold mb-3">Kontakt</h6>
                <ul class="list-unstyled">
                    <li class="d-flex align-items-center mb-2 text-white-50">
                        <i class="bi bi-geo-alt me-2 text-warning"></i>
                        Musterstraße 1, 10115 Berlin
                    </li>
                    <li class="d-flex align-items-center mb-2 text-white-50">
                        <i class="bi bi-telephone me-2 text-warning"></i>
                        +49 (0) 30 – 000 00 00
                    </li>
                    <li class="d-flex align-items-center text-white-50">
                        <i class="bi bi-envelope me-2 text-warning"></i>
                        info@verlustrueckholung.de
                    </li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary">
        <div class="d-flex flex-wrap justify-content-between align-items-center pt-2">
            <div class="text-white-50 small">
                &copy; <?= date('Y') ?> VerlustRückholung. Alle Rechte vorbehalten.
            </div>
            <div class="text-white-50 small">
                <i class="bi bi-shield-check me-1 text-success"></i>SSL-gesichert &nbsp;|&nbsp;
                <i class="bi bi-lock me-1 text-success"></i>DSGVO-konform
            </div>
        </div>
    </div>
</footer>

<!-- ===== MODALS ===== -->

<!-- Fall Prüfen Modal: Professional Lead Form -->
<div class="modal fade" id="fallPruefenModal" tabindex="-1" aria-labelledby="fallPruefenModalLabel">
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
                            <h4 class="modal-title fw-bold text-white mb-1" id="fallPruefenModalLabel">
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
                        <form action="submit_lead.php" method="POST" id="modalLeadForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="visit_id" id="visitIdModalForm" value="">

                            <!-- Section: Persönliche Angaben -->
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
                                            <input type="text" name="first_name" id="modalFirstName"
                                                   class="form-control border-start-0 ps-1" placeholder="Max" required>
                                        </div>
                                        <div class="invalid-feedback">Bitte Vorname eingeben.</div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold small">Nachname *</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-person-fill text-muted"></i></span>
                                            <input type="text" name="last_name" id="modalLastName"
                                                   class="form-control border-start-0 ps-1" placeholder="Mustermann" required>
                                        </div>
                                        <div class="invalid-feedback">Bitte Nachname eingeben.</div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold small">E-Mail-Adresse *</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                            <input type="email" name="email" id="modalEmail"
                                                   class="form-control border-start-0 ps-1" placeholder="max@example.de" required>
                                        </div>
                                        <div class="invalid-feedback">Bitte gültige E-Mail eingeben.</div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold small">Telefonnummer</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-telephone text-muted"></i></span>
                                            <input type="tel" name="phone"
                                                   class="form-control border-start-0 ps-1" placeholder="+49 123 456789">
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
                                                <?php for ($y = date('Y'); $y >= 2015; $y--): ?>
                                                <option value="<?= $y ?>"><?= $y ?></option>
                                                <?php endfor; ?>
                                                <option value="2014">Vor 2015</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Falldetails -->
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
                                            <input type="number" name="amount_lost" id="modalAmount"
                                                   class="form-control" placeholder="10.000" min="1" required>
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
                                    <input class="form-check-input" type="checkbox" id="modalPrivacy" required>
                                    <label class="form-check-label small text-muted" for="modalPrivacy">
                                        Ich stimme der
                                        <a href="#" class="text-primary" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#privacyModal">Datenschutzerklärung</a>
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

<!-- Exit Intent / Engagement Modal (shown after configurable delay) -->
<div class="modal fade" id="exitIntentModal" tabindex="-1"
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
                        <form action="submit_lead.php" method="POST" id="engagementForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="lead_source" value="engagement_modal">
                            <input type="hidden" name="visit_id" id="visitIdEngagementForm" value="">
                            <!-- Section 1: Personal details -->
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
                                        <?php for ($y = date('Y'); $y >= 2015; $y--): ?>
                                        <option value="<?= $y ?>"><?= $y ?></option>
                                        <?php endfor; ?>
                                        <option value="2014">Vor 2015</option>
                                    </select>
                                </div>
                            </div>
                            <!-- Section 2: Case details -->
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
                                        <input class="form-check-input" type="checkbox" id="engPrivacy" required>
                                        <label class="form-check-label small text-muted" for="engPrivacy">
                                            Ich stimme der <a href="#" class="text-primary" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#privacyModal">Datenschutzerklärung</a> zu. *
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


<div class="modal fade" id="infoModal" tabindex="-1">
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
                        data-bs-toggle="modal" data-bs-target="#fallPruefenModal">
                    <i class="bi bi-search me-1"></i>Fall einreichen
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scam Type Detail Modal -->
<div class="modal fade" id="scamModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="scamModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted" id="scamModalDesc"></p>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Betroffen?</strong> Reichen Sie Ihren Fall kostenlos ein –
                    wir prüfen, ob wir helfen können.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                <button class="btn btn-warning fw-bold" data-bs-dismiss="modal"
                        data-bs-toggle="modal" data-bs-target="#fallPruefenModal">
                    <i class="bi bi-search me-1"></i>Jetzt Fall prüfen lassen
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Impressum Modal -->
<div class="modal fade" id="impressumModal" tabindex="-1">
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

<!-- Privacy Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1">
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

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="assets/js/main.js"></script>
<!-- ===== Visitor Tracking ===== -->
<script>
(function () {
    'use strict';
    var visitId   = null;
    var startTime = Date.now();

    // Log the visit and get a visit_id back
    var body = new FormData();
    body.append('action', 'visit');
    body.append('referrer', document.referrer || '');
    fetch('track.php', { method: 'POST', body: body })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.visit_id) {
                visitId = data.visit_id;
                // Populate hidden fields in all lead forms
                ['visitIdLeadForm', 'visitIdModalForm', 'visitIdEngagementForm'].forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) el.value = visitId;
                });
            }
        })
        .catch(function () { /* non-critical – silently ignore */ });

    // Send time-on-site when the visitor leaves
    function sendTimeUpdate() {
        if (!visitId) return;
        var elapsed = Math.round((Date.now() - startTime) / 1000);
        var payload = new FormData();
        payload.append('action', 'update');
        payload.append('visit_id', visitId);
        payload.append('time_on_site', elapsed);
        if (navigator.sendBeacon) {
            // Beacon API: works even after page unload
            var blob = new Blob(
                ['action=update&visit_id=' + encodeURIComponent(visitId) +
                 '&time_on_site=' + encodeURIComponent(elapsed)],
                { type: 'application/x-www-form-urlencoded' }
            );
            navigator.sendBeacon('track.php', blob);
        } else {
            fetch('track.php', { method: 'POST', body: payload, keepalive: true }).catch(function () {});
        }
    }

    window.addEventListener('pagehide', sendTimeUpdate);
    window.addEventListener('beforeunload', sendTimeUpdate);
})();
</script>
</body>
</html>
