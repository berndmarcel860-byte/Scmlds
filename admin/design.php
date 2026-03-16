<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

admin_check();

$message  = '';
$msg_type = 'success';

// ── Handle activation ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['activate_design'])) {
    $valid = ['design_original', 'index2', 'design1', 'design2', 'design3', 'design4', 'design5'];
    $chosen = trim($_POST['activate_design']);
    if (in_array($chosen, $valid, true)) {
        if (save_settings(['active_design' => $chosen])) {
            log_activity('design_changed', 'Active design changed to: ' . $chosen);
            $message = 'Design erfolgreich aktiviert: <strong>' . htmlspecialchars($chosen, ENT_QUOTES, 'UTF-8') . '</strong>';
        } else {
            $msg_type = 'danger';
            $message  = 'Fehler beim Speichern. Stellen Sie sicher, dass der Datenbankeintrag existiert.';
        }
    } else {
        $msg_type = 'danger';
        $message  = 'Ungültiges Design ausgewählt.';
    }
}

$current = get_setting('active_design', 'index2');

// ── Design metadata ───────────────────────────────────────────────────────────
$designs = [
    [
        'key'         => 'design_original',
        'name'        => 'Classic (Original)',
        'description' => 'Das ursprüngliche Design: Dunkelblau + Gold, Canvas-Netzwerk, vollständige Sektionen aus dem klassischen Index.',
        'animation'   => 'Neural Network Canvas',
        'style'       => 'Dark Navy + Gold',
        'badge'       => 'Klassisch',
        'badge_class' => 'bg-secondary',
        'preview_url' => '../themes/design_original.php',
        'color_dots'  => ['#0d2b5e', '#f5a623', '#1a4a9e'],
    ],
    [
        'key'         => 'index2',
        'name'        => 'Professional (Index2)',
        'description' => 'Das umfassendste Design: KI-Terminal, Statistiken, Betrugsarten, Erfahrungsberichte, vollständige Sektionen.',
        'animation'   => 'AI Terminal + Neural Canvas',
        'style'       => 'Dark Navy + Amber, AOS Animationen',
        'badge'       => 'Empfohlen',
        'badge_class' => 'bg-success',
        'preview_url' => '../index2.php',
        'color_dots'  => ['#0a1628', '#f5a623', '#4a90d9'],
    ],
    [
        'key'         => 'design1',
        'name'        => 'Midnight Crypto',
        'description' => 'Sehr dunkles, kryptoaffines Design mit Matrix-Digitalregen-Animation. Ideal für technikaffine Zielgruppen.',
        'animation'   => 'Matrix Digital Rain',
        'style'       => 'Schwarz + Elektrisches Cyan + Gold',
        'badge'       => 'Neu',
        'badge_class' => 'bg-info text-dark',
        'preview_url' => '../themes/design1.php',
        'color_dots'  => ['#080c14', '#00d4ff', '#f5a623'],
    ],
    [
        'key'         => 'design2',
        'name'        => 'Legal Shield',
        'description' => 'Formelles, anwaltskanzlei-ähnliches Design mit rotierender Schild-Partikel-Animation. Sehr seriös.',
        'animation'   => 'Orbiting Shield Particles',
        'style'       => 'Tiefes Marineblau + Dunkelgold + Silber',
        'badge'       => 'Neu',
        'badge_class' => 'bg-info text-dark',
        'preview_url' => '../themes/design2.php',
        'color_dots'  => ['#0a1628', '#b8860b', '#4a90d9'],
    ],
    [
        'key'         => 'design3',
        'name'        => 'Green Tech',
        'description' => 'Hacker/Terminal-Ästhetik mit Blockchain-Netzwerk-Pulsanimation. Perfekt für Krypto-Betrug-fokussierte Kampagnen.',
        'animation'   => 'Blockchain Network Pulse',
        'style'       => 'Schwarz + Neon-Grün + Mint',
        'badge'       => 'Neu',
        'badge_class' => 'bg-info text-dark',
        'preview_url' => '../themes/design3.php',
        'color_dots'  => ['#030d08', '#00ff88', '#7fffbe'],
    ],
    [
        'key'         => 'design4',
        'name'        => 'Corporate Clean',
        'description' => 'Helles, weißes Corporate-Design mit animiertem Balkendiagramm. Sehr zugänglich, professionell, SaaS-Stil.',
        'animation'   => 'Animated Bar Chart Dashboard',
        'style'       => 'Weiß + Tiefblau + Bernstein',
        'badge'       => 'Neu',
        'badge_class' => 'bg-info text-dark',
        'preview_url' => '../themes/design4.php',
        'color_dots'  => ['#ffffff', '#1e3a8a', '#f59e0b'],
    ],
    [
        'key'         => 'design5',
        'name'        => 'Urgent Red',
        'description' => 'Dringlich, dramatisch: Radar-Sweep-Animation, rote Farben, urgenzfokussierte Texte. Maximale Konversionsrate durch Dringlichkeit.',
        'animation'   => 'Radar Sweep with Threat Dots',
        'style'       => 'Dunkel Karmesin + Gold',
        'badge'       => 'Neu',
        'badge_class' => 'bg-info text-dark',
        'preview_url' => '../themes/design5.php',
        'color_dots'  => ['#0d0505', '#dc2626', '#f59e0b'],
    ],
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design wählen – VerlustRückholung Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
    .design-card {
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
        transition: all .25s ease;
        background: #fff;
        height: 100%;
    }
    .design-card:hover {
        border-color: #0d6efd;
        box-shadow: 0 8px 32px rgba(13,110,253,.15);
        transform: translateY(-3px);
    }
    .design-card.active-design {
        border-color: #198754;
        box-shadow: 0 8px 32px rgba(25,135,84,.2);
    }
    .design-preview {
        height: 180px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
        font-size: .75rem;
    }
    .design-preview .preview-label {
        position: absolute;
        top: 10px; left: 10px;
        background: rgba(0,0,0,.6);
        color: #fff;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: .05em;
    }
    .color-dot {
        width: 16px; height: 16px;
        border-radius: 50%;
        display: inline-block;
        border: 2px solid rgba(255,255,255,.4);
        box-shadow: 0 1px 4px rgba(0,0,0,.3);
    }
    .active-badge {
        position: absolute;
        top: 10px; right: 10px;
        background: #198754;
        color: #fff;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: .7rem;
        font-weight: 700;
        display: flex; align-items: center; gap: 4px;
    }

    /* ── Mini-preview colours per design ── */
    .preview-design_original { background: linear-gradient(135deg, #0d2b5e 0%, #1a4a9e 60%, #f5a623 100%); }
    .preview-index2          { background: linear-gradient(135deg, #060e1f 0%, #0d2b5e 60%, #f5a623 100%); }
    .preview-design1         { background: linear-gradient(135deg, #080c14 0%, #001a24 60%, #00d4ff 100%); }
    .preview-design2         { background: linear-gradient(135deg, #0a1628 0%, #162440 60%, #b8860b 100%); }
    .preview-design3         { background: linear-gradient(135deg, #030d08 0%, #0a1a10 60%, #00ff88 100%); }
    .preview-design4         { background: linear-gradient(135deg, #f1f5f9 0%, #dbeafe 60%, #1e3a8a 100%); }
    .preview-design5         { background: linear-gradient(135deg, #0d0505 0%, #1a0808 60%, #dc2626 100%); }

    /* Animation previews */
    .anim-dots { display: flex; gap: 6px; align-items: center; }
    .anim-dot { width: 8px; height: 8px; border-radius: 50%; animation: ping 1.2s cubic-bezier(0,0,.2,1) infinite; }
    .anim-dot:nth-child(2) { animation-delay: .2s; }
    .anim-dot:nth-child(3) { animation-delay: .4s; }
    @keyframes ping { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(1.4)} }
    </style>
</head>
<body class="admin-body">

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="admin-content p-4">

        <div class="d-flex align-items-start justify-content-between mb-4">
            <div>
                <h4 class="fw-bold mb-0"><i class="bi bi-palette-fill me-2 text-primary"></i>Design wählen</h4>
                <p class="text-muted small mb-0">Wählen Sie das aktive Seitendesign aus. Besucher sehen sofort das aktivierte Design.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="../index.php" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye me-1"></i>Aktives Design ansehen
                </a>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-<?= $msg_type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?>"></i>
            <div><?= $message ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Currently active banner -->
        <div class="alert alert-success border-0 shadow-sm mb-4 d-flex align-items-center gap-3">
            <i class="bi bi-check-circle-fill fs-4 text-success"></i>
            <div>
                <div class="fw-bold">Aktives Design</div>
                <div class="small text-muted">
                    <?php
                    $active_meta = array_values(array_filter($designs, fn($d) => $d['key'] === $current));
                    echo htmlspecialchars($active_meta[0]['name'] ?? $current, ENT_QUOTES, 'UTF-8');
                    ?> –
                    <code><?= htmlspecialchars($current, ENT_QUOTES, 'UTF-8') ?></code>
                </div>
            </div>
        </div>

        <!-- Design gallery -->
        <div class="row g-4">
            <?php foreach ($designs as $d): ?>
            <?php $is_active = ($d['key'] === $current); ?>
            <div class="col-md-6 col-xl-4">
                <div class="design-card <?= $is_active ? 'active-design' : '' ?>">

                    <!-- Colour preview -->
                    <div class="design-preview preview-<?= $d['key'] ?>">
                        <div class="preview-label"><?= htmlspecialchars($d['animation'], ENT_QUOTES, 'UTF-8') ?></div>
                        <!-- Simulated animation dots -->
                        <div class="anim-dots">
                            <?php foreach ($d['color_dots'] as $dot): ?>
                            <div class="anim-dot" style="background:<?= htmlspecialchars($dot, ENT_QUOTES, 'UTF-8') ?>"></div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($is_active): ?>
                        <div class="active-badge"><i class="bi bi-check-lg"></i>Aktiv</div>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div class="p-4">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <h5 class="fw-bold mb-0"><?= htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8') ?></h5>
                            <span class="badge <?= $d['badge_class'] ?>"><?= htmlspecialchars($d['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <p class="text-muted small mb-3"><?= htmlspecialchars($d['description'], ENT_QUOTES, 'UTF-8') ?></p>

                        <div class="d-flex gap-1 mb-1 align-items-center">
                            <?php foreach ($d['color_dots'] as $dot): ?>
                            <span class="color-dot" style="background:<?= htmlspecialchars($dot, ENT_QUOTES, 'UTF-8') ?>;border-color:rgba(0,0,0,.1)"></span>
                            <?php endforeach; ?>
                            <span class="text-muted small ms-1"><?= htmlspecialchars($d['style'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <div class="small text-muted mb-3">
                            <i class="bi bi-film me-1"></i><?= htmlspecialchars($d['animation'], ENT_QUOTES, 'UTF-8') ?>
                        </div>

                        <div class="d-flex gap-2">
                            <?php if ($is_active): ?>
                            <button class="btn btn-success btn-sm flex-fill" disabled>
                                <i class="bi bi-check-circle me-1"></i>Aktiv
                            </button>
                            <?php else: ?>
                            <form method="POST" class="flex-fill">
                                <input type="hidden" name="activate_design" value="<?= htmlspecialchars($d['key'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-primary btn-sm w-100"
                                        onclick="return confirm('Design «<?= htmlspecialchars(addslashes($d['name']), ENT_QUOTES, 'UTF-8') ?>» aktivieren? Besucher sehen sofort dieses Design.')">
                                    <i class="bi bi-lightning-charge me-1"></i>Aktivieren
                                </button>
                            </form>
                            <?php endif; ?>
                            <a href="<?= htmlspecialchars($d['preview_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank"
                               class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Info box -->
        <div class="alert alert-info border-0 shadow-sm mt-4">
            <div class="d-flex gap-3">
                <i class="bi bi-info-circle-fill fs-5 flex-shrink-0 mt-1"></i>
                <div>
                    <strong>Wie funktioniert der Design-Wechsel?</strong>
                    <p class="small mb-0 mt-1">
                        Die Homepage (<code>index.php</code>) liest die <code>active_design</code>-Einstellung aus der Datenbank
                        und lädt das entsprechende Design aus dem <code>themes/</code>-Verzeichnis. Der Wechsel ist sofort aktiv –
                        kein Server-Neustart erforderlich. Alle Designs verwenden dasselbe Besucher-Tracking, CSRF-Schutz und
                        Lead-Formular-Backend.
                    </p>
                </div>
            </div>
        </div>

    </div><!-- /admin-content -->
</div><!-- /admin-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
