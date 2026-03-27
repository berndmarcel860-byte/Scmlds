<?php
/**
 * E-Mail-Marketing – Kampagnen-Übersicht
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

admin_check();

// Ensure DB tables + seed default data on first visit
ensure_mailing_tables();

// ── Handle actions ────────────────────────────────────────────────────────────
$msg      = '';
$msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cid    = (int) ($_POST['campaign_id'] ?? 0);
    if ($action === 'delete' && $cid) {
        delete_mailing_campaign($cid);
        $msg = 'Kampagne gelöscht.';
    }
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $cid    = (int) $_GET['id'];
    if ($action === 'start') {
        if (start_mailing_campaign($cid)) {
            $msg = 'Kampagne gestartet.';
        } else {
            $msg_type = 'warning';
            $msg = 'Kampagne konnte nicht gestartet werden. Sind aktive SMTP-Accounts vorhanden?';
        }
    } elseif ($action === 'pause') {
        pause_mailing_campaign($cid);
        $msg = 'Kampagne pausiert.';
    }
}

$campaigns = get_mailing_campaigns();
$smtp_count = count(get_mailing_smtp_accounts(true));
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Mail-Marketing – Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../../assets/css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">

<?php include __DIR__ . '/../partials/sidebar.php'; ?>

<div class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>

    <div class="admin-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0"><i class="bi bi-envelope-paper me-2 text-primary"></i>E-Mail-Marketing</h4>
                <p class="text-muted small mb-0">Kampagnen verwalten und Massen-E-Mails versenden</p>
            </div>
            <a href="campaign_edit.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Neue Kampagne
            </a>
        </div>

        <?php if ($smtp_count === 0): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>Keine aktiven SMTP-Accounts gefunden. <a href="smtp_accounts.php" class="fw-semibold">Jetzt hinzufügen</a> bevor Sie eine Kampagne starten.</div>
        </div>
        <?php endif; ?>

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Summary cards -->
        <?php
        $total_campaigns  = count($campaigns);
        $running_campaigns = count(array_filter($campaigns, fn($c) => $c['status'] === 'running'));
        $total_sent = array_sum(array_column($campaigns, 'sent'));
        ?>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-primary">
                    <div class="text-muted small">Kampagnen gesamt</div>
                    <div class="fs-3 fw-bold"><?= $total_campaigns ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-success">
                    <div class="text-muted small">Laufend</div>
                    <div class="fs-3 fw-bold"><?= $running_campaigns ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-info">
                    <div class="text-muted small">E-Mails versendet</div>
                    <div class="fs-3 fw-bold"><?= number_format($total_sent) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-warning">
                    <div class="text-muted small">Aktive SMTP-Accounts</div>
                    <div class="fs-3 fw-bold"><?= $smtp_count ?></div>
                </div>
            </div>
        </div>

        <!-- Campaigns table -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                <h6 class="fw-semibold mb-0">Alle Kampagnen</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Template</th>
                            <th>Status</th>
                            <th>Fortschritt</th>
                            <th>Gesendet</th>
                            <th>Fehler</th>
                            <th>Erstellt</th>
                            <th class="text-end">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($campaigns)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">Noch keine Kampagnen vorhanden.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($campaigns as $c): ?>
                    <?php
                        $pct = $c['total'] > 0 ? round($c['sent'] / $c['total'] * 100) : 0;
                        $status_map = ['draft'=>['secondary','Entwurf'],'running'=>['success','Läuft'],'paused'=>['warning','Pausiert'],'completed'=>['primary','Abgeschlossen'],'failed'=>['danger','Fehler']];
                        [$sc, $sl] = $status_map[$c['status']] ?? ['secondary', $c['status']];
                    ?>
                    <tr>
                        <td class="text-muted small"><?= $c['id'] ?></td>
                        <td><a href="campaign_edit.php?id=<?= $c['id'] ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars($c['name']) ?></a></td>
                        <td class="text-muted small"><?= htmlspecialchars($c['template_name'] ?? '—') ?></td>
                        <td><span class="badge bg-<?= $sc ?>"><?= $sl ?></span></td>
                        <td style="min-width:120px">
                            <div class="progress" style="height:6px">
                                <div class="progress-bar bg-<?= $sc ?>" style="width:<?= $pct ?>%"></div>
                            </div>
                            <small class="text-muted"><?= $c['sent'] ?>/<?= $c['total'] ?></small>
                        </td>
                        <td><?= number_format($c['sent']) ?></td>
                        <td><?= number_format($c['failed']) ?></td>
                        <td class="text-muted small"><?= date('d.m.Y', strtotime($c['created_at'])) ?></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <?php if ($c['status'] === 'running'): ?>
                                <a href="send.php?id=<?= $c['id'] ?>" class="btn btn-success btn-sm"><i class="bi bi-play-fill"></i> Senden</a>
                                <a href="index.php?action=pause&id=<?= $c['id'] ?>" class="btn btn-warning btn-sm"><i class="bi bi-pause-fill"></i></a>
                                <?php elseif (in_array($c['status'], ['draft','paused'])): ?>
                                <a href="index.php?action=start&id=<?= $c['id'] ?>" class="btn btn-success btn-sm"><i class="bi bi-play-fill"></i> Starten</a>
                                <?php endif; ?>
                                <a href="stats.php?id=<?= $c['id'] ?>" class="btn btn-outline-info btn-sm"><i class="bi bi-bar-chart"></i></a>
                                <a href="campaign_edit.php?id=<?= $c['id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil"></i></a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Kampagne wirklich löschen?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="campaign_id" value="<?= $c['id'] ?>">
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div><!-- /admin-content -->
</div><!-- /admin-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
