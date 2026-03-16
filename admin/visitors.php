<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

admin_check();

$page     = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 50;

$filters = [];
if (!empty($_GET['ip']))       $filters['ip']       = trim($_GET['ip']);
if (isset($_GET['with_lead']) && $_GET['with_lead'] !== '') {
    $filters['with_lead'] = (int) $_GET['with_lead'];
}
if (!empty($_GET['over_30s'])) $filters['over_30s'] = true;

$result  = get_visitor_logs($filters, $page, $per_page);
$logs    = $result['data'];
$total   = $result['total'];
$pages   = $result['pages'];

$stats = get_visitor_stats();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Besucher-Log – VerlustRückholung Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="admin-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0"><i class="bi bi-eye me-2 text-primary"></i>Besucher-Log</h4>
                <p class="text-muted small mb-0">Besuchte Seite (Bots werden automatisch gefiltert)</p>
            </div>
        </div>

        <!-- Quick stats row -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-primary">
                    <div class="text-muted small">Gesamtbesuche</div>
                    <div class="fs-3 fw-bold"><?= number_format($stats['total_visits']) ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-success">
                    <div class="text-muted small">Über 30 Sekunden</div>
                    <div class="fs-3 fw-bold"><?= number_format($stats['over_30s']) ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-info">
                    <div class="text-muted small">Ø Verweildauer</div>
                    <div class="fs-3 fw-bold"><?= format_duration((int) round($stats['avg_time'])) ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-warning">
                    <div class="text-muted small">Leads (aus Besuchern)</div>
                    <div class="fs-3 fw-bold"><?= number_format($stats['total_leads_from_visitors']) ?>
                        <small class="fs-6 text-muted">(<?= $stats['conversion_rate'] ?>%)</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">IP-Adresse</label>
                        <input type="text" name="ip" class="form-control form-control-sm"
                               placeholder="z.B. 192.168." value="<?= htmlspecialchars($filters['ip'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Lead eingereicht?</label>
                        <select name="with_lead" class="form-select form-select-sm">
                            <option value="">Alle</option>
                            <option value="1" <?= (isset($filters['with_lead']) && $filters['with_lead'] === 1) ? 'selected' : '' ?>>Ja</option>
                            <option value="0" <?= (isset($filters['with_lead']) && $filters['with_lead'] === 0) ? 'selected' : '' ?>>Nein</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="over_30s" id="over30s" value="1"
                                   <?= !empty($filters['over_30s']) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="over30s">Nur &gt; 30 Sekunden</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-funnel me-1"></i>Filtern
                            </button>
                            <a href="visitors.php" class="btn btn-outline-secondary btn-sm">Zurücksetzen</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Log table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-list-ul me-2 text-primary"></i>Besucherprotokoll</span>
                <span class="text-muted small"><?= number_format($total) ?> Einträge</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>IP-Adresse</th>
                                <th>Quelle / Referrer</th>
                                <th>Verweildauer</th>
                                <th>Lead</th>
                                <th>Datum &amp; Uhrzeit</th>
                                <th>User-Agent</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="text-muted small"><?= (int) $log['id'] ?></td>
                                <td class="font-monospace small"><?= htmlspecialchars($log['ip_address'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small text-truncate" style="max-width:200px;">
                                    <?php
                                    $ref = $log['referrer'] ?? '';
                                    if ($ref === '') {
                                        echo '<span class="text-muted">Direkt</span>';
                                    } else {
                                        $parsed = parse_url($ref);
                                        $display = $parsed['host'] ?? $ref;
                                        echo '<span title="' . htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') . '">'
                                           . htmlspecialchars($display, ENT_QUOTES, 'UTF-8') . '</span>';
                                    }
                                    ?>
                                </td>
                                <td class="small">
                                    <?php
                                    $t = (int) $log['time_on_site'];
                                    $cls = $t > 30 ? 'text-success fw-semibold' : 'text-muted';
                                    echo '<span class="' . $cls . '">' . format_duration($t) . '</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php if ($log['submitted_lead']): ?>
                                        <a href="lead_detail.php?id=<?= (int) $log['lead_id'] ?>"
                                           class="badge bg-success text-decoration-none">
                                            <i class="bi bi-person-check me-1"></i>
                                            <?= htmlspecialchars($log['first_name'] . ' ' . $log['last_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">–</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small text-nowrap">
                                    <?= date('d.m.Y H:i', strtotime($log['created_at'])) ?>
                                </td>
                                <td class="small text-truncate text-muted" style="max-width:180px;">
                                    <span title="<?= htmlspecialchars($log['user_agent'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars(substr($log['user_agent'] ?? '', 0, 60), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Keine Einträge gefunden.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if ($pages > 1): ?>
            <div class="card-footer bg-white border-0">
                <nav>
                    <ul class="pagination pagination-sm mb-0 justify-content-end">
                        <?php
                        $qs = http_build_query(array_merge($filters, ['page' => 1]));
                        $qp = http_build_query(array_merge($filters, ['page' => max(1, $page - 1)]));
                        $qn = http_build_query(array_merge($filters, ['page' => min($pages, $page + 1)]));
                        $ql = http_build_query(array_merge($filters, ['page' => $pages]));
                        ?>
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= $qp ?>">‹</a>
                        </li>
                        <li class="page-item disabled">
                            <span class="page-link"><?= $page ?> / <?= $pages ?></span>
                        </li>
                        <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= $qn ?>">›</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
