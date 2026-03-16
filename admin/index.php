<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

admin_check();

$stats = get_dashboard_stats();
$recent_leads = get_leads([], 1, 5)['data'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Scmlds Admin</title>
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
                <h4 class="fw-bold mb-0">Dashboard</h4>
                <p class="text-muted small mb-0">Willkommen zurück, <?= htmlspecialchars($_SESSION['admin_user'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <a href="leads.php" class="btn btn-primary">
                <i class="bi bi-people me-1"></i>Alle Leads
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-2-4">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Gesamt Leads</div>
                            <div class="fs-3 fw-bold"><?= $stats['total'] ?></div>
                        </div>
                        <div class="bg-primary-subtle rounded-circle p-2">
                            <i class="bi bi-people-fill text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-2-4">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Neu</div>
                            <div class="fs-3 fw-bold"><?= $stats['new'] ?></div>
                        </div>
                        <div class="bg-warning-subtle rounded-circle p-2">
                            <i class="bi bi-inbox-fill text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-2-4">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">In Bearbeitung</div>
                            <div class="fs-3 fw-bold"><?= $stats['in_progress'] ?></div>
                        </div>
                        <div class="bg-info-subtle rounded-circle p-2">
                            <i class="bi bi-gear-fill text-info fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-2-4">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Erfolgreich</div>
                            <div class="fs-3 fw-bold"><?= $stats['successful'] ?></div>
                        </div>
                        <div class="bg-success-subtle rounded-circle p-2">
                            <i class="bi bi-check-circle-fill text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-2-4">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-secondary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Gesamtbetrag</div>
                            <div class="fs-5 fw-bold"><?= format_currency($stats['total_amount']) ?></div>
                        </div>
                        <div class="bg-secondary-subtle rounded-circle p-2">
                            <i class="bi bi-cash-coin text-secondary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Chart: Leads by Category -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 fw-bold">
                        <i class="bi bi-pie-chart me-2 text-primary"></i>Leads nach Betrugsart
                    </div>
                    <div class="card-body">
                        <canvas id="categoryChart" height="220"></canvas>
                    </div>
                </div>
            </div>
            <!-- Chart: Submissions last 30 days -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 fw-bold">
                        <i class="bi bi-bar-chart me-2 text-primary"></i>Eingänge (letzte 30 Tage)
                    </div>
                    <div class="card-body">
                        <canvas id="lineChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Leads -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Neueste Leads</span>
                <a href="leads.php" class="btn btn-sm btn-outline-primary">Alle anzeigen</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>E-Mail</th>
                                <th>Betrag</th>
                                <th>Kategorie</th>
                                <th>Status</th>
                                <th>Datum</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recent_leads as $lead): ?>
                            <tr>
                                <td class="fw-semibold">
                                    <?= htmlspecialchars($lead['first_name'] . ' ' . $lead['last_name'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="text-muted"><?= htmlspecialchars($lead['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= format_currency((float) $lead['amount_lost']) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($lead['platform_category'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= status_badge_class($lead['status']) ?>">
                                        <?= htmlspecialchars($lead['status'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    <?= date('d.m.Y', strtotime($lead['created_at'])) ?>
                                </td>
                                <td>
                                    <a href="lead_detail.php?id=<?= (int)$lead['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_leads)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Noch keine Leads vorhanden.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function() {
    // Category pie chart
    const catData = <?= json_encode(array_values($stats['by_category'])) ?>;
    const catLabels = catData.map(d => d.platform_category || 'Unbekannt');
    const catValues = catData.map(d => parseInt(d.cnt));
    const catColors = ['#0d6efd','#ffc107','#dc3545','#0dcaf0','#198754','#6c757d'];

    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: catLabels,
            datasets: [{ data: catValues, backgroundColor: catColors, borderWidth: 2 }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
        }
    });

    // Line chart
    const chartData = <?= json_encode(array_values($stats['chart_data'])) ?>;
    const days = chartData.map(d => d.day);
    const counts = chartData.map(d => parseInt(d.cnt));

    new Chart(document.getElementById('lineChart'), {
        type: 'bar',
        data: {
            labels: days,
            datasets: [{
                label: 'Leads',
                data: counts,
                backgroundColor: 'rgba(13,110,253,0.15)',
                borderColor: '#0d6efd',
                borderWidth: 2,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: { ticks: { maxTicksLimit: 10 } }
            },
            plugins: { legend: { display: false } }
        }
    });
})();
</script>
</body>
</html>
