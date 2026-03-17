<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

admin_check();

$vstats = get_visitor_stats();
$lstats = get_dashboard_stats();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiken – VerlustRückholung Admin</title>
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
        <div class="mb-4">
            <h4 class="fw-bold mb-0"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Statistiken</h4>
            <p class="text-muted small mb-0">Gesamtübersicht – Besucher &amp; Leads</p>
        </div>

        <!-- ===== VISITOR OVERVIEW CARDS ===== -->
        <h6 class="fw-bold text-uppercase text-muted small mb-3 mt-2">Besucherstatistiken</h6>
        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-2-4">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Gesamtbesuche</div>
                            <div class="fs-3 fw-bold"><?= number_format($vstats['total_visits']) ?></div>
                        </div>
                        <div class="bg-primary-subtle rounded-circle p-2">
                            <i class="bi bi-people-fill text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-2-4">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Über 30 Sekunden</div>
                            <div class="fs-3 fw-bold"><?= number_format($vstats['over_30s']) ?></div>
                            <?php if ($vstats['total_visits'] > 0): ?>
                            <div class="text-muted" style="font-size:0.7rem;">
                                <?= round(($vstats['over_30s'] / $vstats['total_visits']) * 100, 1) ?>% der Besuche
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="bg-success-subtle rounded-circle p-2">
                            <i class="bi bi-clock-history text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-2-4">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Ø Verweildauer</div>
                            <div class="fs-3 fw-bold"><?= format_duration((int) round($vstats['avg_time'])) ?></div>
                        </div>
                        <div class="bg-info-subtle rounded-circle p-2">
                            <i class="bi bi-stopwatch text-info fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-2-4">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Leads (aus Besuchen)</div>
                            <div class="fs-3 fw-bold"><?= number_format($vstats['total_leads_from_visitors']) ?></div>
                        </div>
                        <div class="bg-warning-subtle rounded-circle p-2">
                            <i class="bi bi-person-check-fill text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-2-4">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-secondary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Conversion-Rate</div>
                            <div class="fs-3 fw-bold"><?= $vstats['conversion_rate'] ?>%</div>
                            <div class="text-muted" style="font-size:0.7rem;">Besucher → Lead</div>
                        </div>
                        <div class="bg-secondary-subtle rounded-circle p-2">
                            <i class="bi bi-graph-up-arrow text-secondary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== LEAD OVERVIEW CARDS ===== -->
        <h6 class="fw-bold text-uppercase text-muted small mb-3 mt-4">Lead-Statistiken</h6>
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-primary">
                    <div class="text-muted small">Gesamt Leads</div>
                    <div class="fs-3 fw-bold"><?= $lstats['total'] ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-warning">
                    <div class="text-muted small">Neu</div>
                    <div class="fs-3 fw-bold"><?= $lstats['new'] ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-success">
                    <div class="text-muted small">Erfolgreich</div>
                    <div class="fs-3 fw-bold"><?= $lstats['successful'] ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-widget p-3 rounded-3 bg-white shadow-sm border-start border-4 border-secondary">
                    <div class="text-muted small">Gesamtbetrag</div>
                    <div class="fs-5 fw-bold"><?= format_currency($lstats['total_amount']) ?></div>
                </div>
            </div>
        </div>

        <!-- ===== CHARTS ROW ===== -->
        <div class="row g-4">
            <!-- Daily visitors chart -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 fw-bold">
                        <i class="bi bi-people me-2 text-primary"></i>Tägliche Besuche (letzte 30 Tage)
                    </div>
                    <div class="card-body">
                        <canvas id="visitorsChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            <!-- Top referrers -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 fw-bold">
                        <i class="bi bi-diagram-3 me-2 text-primary"></i>Top Quellen
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                        <?php foreach ($vstats['top_referrers'] as $ref): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                                <span class="small text-truncate" style="max-width:220px;">
                                    <?= htmlspecialchars($ref['source'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span class="badge bg-primary-subtle text-primary rounded-pill">
                                    <?= (int) $ref['cnt'] ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($vstats['top_referrers'])): ?>
                            <li class="list-group-item text-muted text-center py-4">Noch keine Daten.</li>
                        <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lead category chart + daily leads -->
        <div class="row g-4 mt-0">
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
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 fw-bold">
                        <i class="bi bi-bar-chart me-2 text-primary"></i>Lead-Eingänge (letzte 30 Tage)
                    </div>
                    <div class="card-body">
                        <canvas id="leadsChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== UTM Source Breakdown ===== -->
        <div class="row g-4 mt-0">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 fw-bold">
                        <i class="bi bi-tags me-2 text-success"></i>Besuche nach UTM-Quelle
                        <span class="text-muted fw-normal small ms-1">(nur wenn UTM-Parameter in der URL)</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-3">utm_source</th>
                                    <th class="text-end px-3">Besuche</th>
                                    <th class="text-end px-3">Leads</th>
                                    <th class="text-end px-3">CVR</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($vstats['utm_breakdown'] as $row): ?>
                                <?php $cvr = $row['visits'] > 0 ? round(($row['leads'] / $row['visits']) * 100, 1) : 0; ?>
                                <tr>
                                    <td class="px-3 small"><?= htmlspecialchars($row['utm_source'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end px-3"><?= (int) $row['visits'] ?></td>
                                    <td class="text-end px-3 fw-semibold text-success"><?= (int) $row['leads'] ?></td>
                                    <td class="text-end px-3"><?= $cvr ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($vstats['utm_breakdown'])): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3 small">Noch keine UTM-Daten. Fügen Sie <code>?utm_source=google</code> zu Ihren Links hinzu.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 fw-bold">
                        <i class="bi bi-funnel me-2 text-warning"></i>Leads nach Formular-Quelle
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-3">Formular</th>
                                    <th class="text-end px-3">Leads</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($vstats['lead_source_breakdown'] as $row): ?>
                                <tr>
                                    <td class="px-3 small"><?= htmlspecialchars($row['lead_source'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end px-3 fw-semibold"><?= (int) $row['cnt'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($vstats['lead_source_breakdown'])): ?>
                                <tr><td colspan="2" class="text-center text-muted py-3 small">Noch keine Leads vorhanden.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    // ─── Daily visitors chart ────────────────────────────────
    const visitorData   = <?= json_encode(array_values($vstats['chart_data'])) ?>;
    const visitorDays   = visitorData.map(d => d.day);
    const visitorCounts = visitorData.map(d => parseInt(d.cnt));

    new Chart(document.getElementById('visitorsChart'), {
        type: 'line',
        data: {
            labels: visitorDays,
            datasets: [{
                label: 'Besuche',
                data: visitorCounts,
                backgroundColor: 'rgba(13,110,253,0.1)',
                borderColor: '#0d6efd',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointRadius: 3,
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

    // ─── Lead category pie chart ─────────────────────────────
    const catData   = <?= json_encode(array_values($lstats['by_category'])) ?>;
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

    // ─── Daily leads bar chart ───────────────────────────────
    const leadsData   = <?= json_encode(array_values($lstats['chart_data'])) ?>;
    const leadsDays   = leadsData.map(d => d.day);
    const leadsCounts = leadsData.map(d => parseInt(d.cnt));

    new Chart(document.getElementById('leadsChart'), {
        type: 'bar',
        data: {
            labels: leadsDays,
            datasets: [{
                label: 'Leads',
                data: leadsCounts,
                backgroundColor: 'rgba(25,135,84,0.15)',
                borderColor: '#198754',
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
