<?php
/**
 * E-Mail-Marketing – Globale Statistiken (Übersicht)
 *
 * Shows:
 *  – Global KPI cards (sent / opened / clicked / not-opened / not-clicked / failed)
 *  – Hourly volume chart for the last 24 hours
 *  – Time-range breakdown table (1h · today · 7d · 30d · all-time)
 *  – Per-SMTP account breakdown
 *  – Template ranking by opens & clicks
 *  – Leads generated per campaign
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

admin_check();
ensure_mailing_tables();

$pdo = db_connect();

// ── 1. Global KPI totals (valid recipients only) ──────────────────────────────
$kpi = $pdo->query(
    "SELECT
        COUNT(*)                                              AS total_all,
        SUM(email_validity = 'valid')                        AS total_valid,
        SUM(email_validity = 'invalid')                      AS total_invalid,
        SUM(status = 'pending'  AND email_validity = 'valid') AS cnt_pending,
        SUM(status = 'sent'     AND email_validity = 'valid') AS cnt_sent,
        SUM(status = 'failed'   AND email_validity = 'valid') AS cnt_failed,
        SUM(status = 'bounced'  AND email_validity = 'valid') AS cnt_bounced,
        SUM(status = 'unsubscribed' AND email_validity = 'valid') AS cnt_unsub,
        SUM(opened_at IS NOT NULL AND email_validity = 'valid' AND status = 'sent') AS cnt_opened,
        SUM(clicked_at IS NOT NULL AND email_validity = 'valid' AND status = 'sent') AS cnt_clicked,
        SUM(opened_at IS NULL AND email_validity = 'valid' AND status = 'sent')     AS cnt_not_opened,
        SUM(clicked_at IS NULL AND email_validity = 'valid' AND status = 'sent')    AS cnt_not_clicked
     FROM mailing_recipients"
)->fetch(PDO::FETCH_ASSOC);

$sent      = (int)($kpi['cnt_sent'] ?? 0);
$opened    = (int)($kpi['cnt_opened'] ?? 0);
$clicked   = (int)($kpi['cnt_clicked'] ?? 0);
$notOpened = (int)($kpi['cnt_not_opened'] ?? 0);
$notClicked= (int)($kpi['cnt_not_clicked'] ?? 0);
$failed    = (int)($kpi['cnt_failed'] ?? 0);
$bounced   = (int)($kpi['cnt_bounced'] ?? 0);

$openRate    = $sent > 0 ? round($opened  / $sent * 100, 1) : 0;
$clickRate   = $sent > 0 ? round($clicked / $sent * 100, 1) : 0;
$failRate    = ($sent + $failed) > 0 ? round($failed / ($sent + $failed) * 100, 1) : 0;

// ── 2. Time-range breakdown table ─────────────────────────────────────────────
$ranges = [
    '1h'    => "sent_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)",
    'today' => "DATE(sent_at) = CURDATE()",
    '7d'    => "sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
    '30d'   => "sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
    'all'   => "1=1",
];

$range_stats = [];
foreach ($ranges as $label => $cond) {
    $s = $pdo->query(
        "SELECT
            COUNT(*) AS sent,
            SUM(opened_at IS NOT NULL) AS opened,
            SUM(clicked_at IS NOT NULL) AS clicked,
            SUM(opened_at IS NULL) AS not_opened,
            SUM(clicked_at IS NULL) AS not_clicked
         FROM mailing_recipients
         WHERE status = 'sent' AND email_validity = 'valid' AND $cond"
    )->fetch(PDO::FETCH_ASSOC);
    $range_stats[$label] = $s;
}

// ── 3. Hourly volume for chart (last 24h, sent + open + click per hour) ────────
$hourly_rows = $pdo->query(
    "SELECT
        DATE_FORMAT(sent_at, '%H:00') AS hour_label,
        HOUR(sent_at)                 AS hr,
        COUNT(*)                      AS sent,
        SUM(opened_at IS NOT NULL)    AS opened,
        SUM(clicked_at IS NOT NULL)   AS clicked
     FROM mailing_recipients
     WHERE status = 'sent'
       AND email_validity = 'valid'
       AND sent_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
     GROUP BY HOUR(sent_at), DATE_FORMAT(sent_at, '%H:00')
     ORDER BY hr ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// Fill all 24 slots
$hourly_map = [];
foreach ($hourly_rows as $row) {
    $hourly_map[(int)$row['hr']] = $row;
}
$chart_labels  = [];
$chart_sent    = [];
$chart_opened  = [];
$chart_clicked = [];
for ($h = 0; $h < 24; $h++) {
    $chart_labels[]  = sprintf('%02d:00', $h);
    $chart_sent[]    = isset($hourly_map[$h]) ? (int)$hourly_map[$h]['sent']    : 0;
    $chart_opened[]  = isset($hourly_map[$h]) ? (int)$hourly_map[$h]['opened']  : 0;
    $chart_clicked[] = isset($hourly_map[$h]) ? (int)$hourly_map[$h]['clicked'] : 0;
}

// ── 4. Daily volume for chart (last 30 days) ───────────────────────────────────
$daily_rows = $pdo->query(
    "SELECT
        DATE_FORMAT(sent_at, '%d.%m') AS day_label,
        DATE(sent_at)                 AS day_date,
        COUNT(*)                      AS sent,
        SUM(opened_at IS NOT NULL)    AS opened,
        SUM(clicked_at IS NOT NULL)   AS clicked
     FROM mailing_recipients
     WHERE status = 'sent'
       AND email_validity = 'valid'
       AND sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY DATE(sent_at), DATE_FORMAT(sent_at, '%d.%m')
     ORDER BY day_date ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$day_labels  = array_column($daily_rows, 'day_label');
$day_sent    = array_map('intval', array_column($daily_rows, 'sent'));
$day_opened  = array_map('intval', array_column($daily_rows, 'opened'));
$day_clicked = array_map('intval', array_column($daily_rows, 'clicked'));

// ── 5. Per-SMTP account stats ──────────────────────────────────────────────────
$smtp_stats = $pdo->query(
    "SELECT
        a.id,
        a.label,
        a.from_email,
        COUNT(r.id)                          AS total_sent,
        SUM(r.opened_at IS NOT NULL)         AS opened,
        SUM(r.clicked_at IS NOT NULL)        AS clicked,
        SUM(r.status = 'failed')             AS failed,
        SUM(r.status = 'bounced')            AS bounced,
        MIN(r.sent_at)                       AS first_sent,
        MAX(r.sent_at)                       AS last_sent,
        SUM(r.sent_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR))   AS sent_1h,
        SUM(r.sent_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR))  AS sent_24h
     FROM mailing_recipients r
     JOIN mailing_smtp_accounts a ON a.id = r.smtp_account_id
     WHERE r.email_validity = 'valid'
     GROUP BY a.id, a.label, a.from_email
     ORDER BY total_sent DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// ── 6. Template ranking ────────────────────────────────────────────────────────
$tpl_stats = $pdo->query(
    "SELECT
        t.id,
        t.name,
        COUNT(r.id)                   AS total_sent,
        SUM(r.opened_at IS NOT NULL)  AS opened,
        SUM(r.clicked_at IS NOT NULL) AS clicked
     FROM mailing_recipients r
     JOIN mailing_campaigns c  ON c.id = r.campaign_id
     JOIN mailing_templates t  ON t.id = c.template_id
     WHERE r.status = 'sent' AND r.email_validity = 'valid'
     GROUP BY t.id, t.name
     ORDER BY opened DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// ── 7. Leads generated per campaign ───────────────────────────────────────────
// "Generated" = a lead whose email matches a mailing_recipient in that campaign
$leads_per_camp = $pdo->query(
    "SELECT
        c.id   AS campaign_id,
        c.name AS campaign_name,
        c.status AS camp_status,
        COUNT(DISTINCT l.id)                     AS total_leads,
        SUM(l.status = 'Erfolgreich')            AS successful_leads,
        SUM(l.status = 'Neu')                    AS new_leads,
        SUM(l.status = 'In Bearbeitung')         AS inprogress_leads,
        SUM(l.status = 'Kontaktiert')            AS contacted_leads
     FROM mailing_campaigns c
     JOIN mailing_recipients r ON r.campaign_id = c.id
     JOIN leads l              ON l.email = r.email
     GROUP BY c.id, c.name, c.status
     ORDER BY total_leads DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// ── Helpers ────────────────────────────────────────────────────────────────────
function pct(int $part, int $total): string {
    return $total > 0 ? round($part / $total * 100, 1) . '%' : '0%';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Mail Statistiken – Marketing</title>
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

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0"><i class="bi bi-bar-chart-line-fill me-2 text-primary"></i>E-Mail Statistiken</h4>
                <p class="text-muted small mb-0">Globale Übersicht aller Kampagnen, SMTP-Accounts, Templates und Leads</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-grid me-1"></i>Kampagnen
            </a>
        </div>

        <!-- ── KPI Cards ─────────────────────────────────────────────────────── -->
        <div class="row g-3 mb-4">
            <?php
            $kpis = [
                ['primary',  'bi-send',            'Versendet',      $sent,       null],
                ['success',  'bi-envelope-open',   'Geöffnet',       $opened,     $openRate . '%'],
                ['info',     'bi-cursor',           'Geklickt',       $clicked,    $clickRate . '%'],
                ['warning',  'bi-envelope-x',       'Nicht geöffnet', $notOpened,  pct($notOpened,  $sent)],
                ['secondary','bi-hand-index-thumb', 'Nicht geklickt', $notClicked, pct($notClicked, $sent)],
                ['danger',   'bi-x-circle',         'Fehlgeschlagen', $failed,     $failRate . '%'],
                ['dark',     'bi-arrow-return-left','Bounced',        $bounced,    pct($bounced, $sent)],
                ['light text-dark', 'bi-inbox',     'Gesamt (valid)', (int)($kpi['total_valid'] ?? 0), null],
            ];
            foreach ($kpis as [$color, $icon, $label, $value, $sub]):
            ?>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-<?= $color ?> p-2"><i class="bi <?= $icon ?> fs-6"></i></span>
                            <span class="text-muted small"><?= $label ?></span>
                        </div>
                        <div class="fw-bold fs-4"><?= number_format($value, 0, ',', '.') ?></div>
                        <?php if ($sub !== null): ?>
                            <div class="text-muted small"><?= $sub ?> Rate</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ── Time-range breakdown table ──────────────────────────────────── -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h6 class="fw-semibold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Zeitraum-Auswertung</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Zeitraum</th>
                                <th>Versendet</th>
                                <th>Geöffnet</th>
                                <th>Öffnungsrate</th>
                                <th>Geklickt</th>
                                <th>Klickrate</th>
                                <th>Nicht geöffnet</th>
                                <th>Nicht geklickt</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $range_labels = [
                            '1h'    => 'Letzte 1 Stunde',
                            'today' => 'Heute',
                            '7d'    => 'Letzte 7 Tage',
                            '30d'   => 'Letzte 30 Tage',
                            'all'   => 'Gesamt (alle Zeiten)',
                        ];
                        foreach ($range_stats as $key => $r):
                            $rs = (int)($r['sent'] ?? 0);
                            $ro = (int)($r['opened'] ?? 0);
                            $rc = (int)($r['clicked'] ?? 0);
                            $rno = (int)($r['not_opened'] ?? 0);
                            $rnc = (int)($r['not_clicked'] ?? 0);
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= $range_labels[$key] ?></td>
                            <td><?= number_format($rs, 0, ',', '.') ?></td>
                            <td><?= number_format($ro, 0, ',', '.') ?></td>
                            <td><span class="badge bg-success-subtle text-success border"><?= pct($ro, $rs) ?></span></td>
                            <td><?= number_format($rc, 0, ',', '.') ?></td>
                            <td><span class="badge bg-info-subtle text-info border"><?= pct($rc, $rs) ?></span></td>
                            <td class="text-muted"><?= number_format($rno, 0, ',', '.') ?></td>
                            <td class="text-muted"><?= number_format($rnc, 0, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── Charts Row ───────────────────────────────────────────────────── -->
        <div class="row g-4 mb-4">
            <!-- Hourly chart (last 24h) -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3 pb-0">
                        <h6 class="fw-semibold mb-0">
                            <i class="bi bi-bar-chart me-2 text-primary"></i>Stündliches Volumen (letzte 24h)
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="hourlyChart" height="220"></canvas>
                    </div>
                </div>
            </div>
            <!-- Daily chart (last 30 days) -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3 pb-0">
                        <h6 class="fw-semibold mb-0">
                            <i class="bi bi-calendar3 me-2 text-primary"></i>Tägliches Volumen (letzte 30 Tage)
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="dailyChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Per-SMTP account stats ────────────────────────────────────────── -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h6 class="fw-semibold mb-0"><i class="bi bi-server me-2 text-primary"></i>SMTP-Account Statistiken</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Account</th>
                                <th>E-Mail</th>
                                <th>Gesendet gesamt</th>
                                <th>Letzte 1h</th>
                                <th>Letzte 24h</th>
                                <th>Geöffnet</th>
                                <th>Geklickt</th>
                                <th>Fehler</th>
                                <th>Bounced</th>
                                <th>Letzter Versand</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($smtp_stats)): ?>
                            <tr><td colspan="10" class="text-center text-muted py-4">Noch keine Versanddaten.</td></tr>
                        <?php else: ?>
                        <?php foreach ($smtp_stats as $acc):
                            $as = (int)$acc['total_sent'];
                            $ao = (int)$acc['opened'];
                            $ac = (int)$acc['clicked'];
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($acc['label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-muted small"><?= htmlspecialchars($acc['from_email'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format($as, 0, ',', '.') ?></td>
                            <td><?= number_format((int)$acc['sent_1h'], 0, ',', '.') ?></td>
                            <td><?= number_format((int)$acc['sent_24h'], 0, ',', '.') ?></td>
                            <td>
                                <?= number_format($ao, 0, ',', '.') ?>
                                <small class="text-muted">(<?= pct($ao, $as) ?>)</small>
                            </td>
                            <td>
                                <?= number_format($ac, 0, ',', '.') ?>
                                <small class="text-muted">(<?= pct($ac, $as) ?>)</small>
                            </td>
                            <td class="text-danger"><?= number_format((int)$acc['failed'], 0, ',', '.') ?></td>
                            <td class="text-warning"><?= number_format((int)$acc['bounced'], 0, ',', '.') ?></td>
                            <td class="text-muted small">
                                <?= $acc['last_sent'] ? date('d.m.Y H:i', strtotime($acc['last_sent'])) : '–' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── Template ranking ─────────────────────────────────────────────── -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h6 class="fw-semibold mb-0"><i class="bi bi-file-earmark-richtext me-2 text-primary"></i>Template Ranking</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Template</th>
                                <th>Versendet</th>
                                <th>Geöffnet</th>
                                <th>Öffnungsrate</th>
                                <th>Geklickt</th>
                                <th>Klickrate</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($tpl_stats)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Noch keine Template-Daten.</td></tr>
                        <?php else: ?>
                        <?php foreach ($tpl_stats as $i => $tpl):
                            $ts = (int)$tpl['total_sent'];
                            $to = (int)$tpl['opened'];
                            $tc = (int)$tpl['clicked'];
                        ?>
                        <tr>
                            <td class="text-muted small"><?= $i + 1 ?></td>
                            <td class="fw-semibold">
                                <a href="templates.php" class="text-decoration-none">
                                    <?= htmlspecialchars($tpl['name'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </td>
                            <td><?= number_format($ts, 0, ',', '.') ?></td>
                            <td><?= number_format($to, 0, ',', '.') ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:6px;min-width:60px;">
                                        <div class="progress-bar bg-success" style="width:<?= min(100, $ts > 0 ? round($to/$ts*100) : 0) ?>%"></div>
                                    </div>
                                    <span class="small"><?= pct($to, $ts) ?></span>
                                </div>
                            </td>
                            <td><?= number_format($tc, 0, ',', '.') ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:6px;min-width:60px;">
                                        <div class="progress-bar bg-info" style="width:<?= min(100, $ts > 0 ? round($tc/$ts*100) : 0) ?>%"></div>
                                    </div>
                                    <span class="small"><?= pct($tc, $ts) ?></span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── Leads per campaign ───────────────────────────────────────────── -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-people-fill me-2 text-primary"></i>Leads nach Kampagne
                    <span class="text-muted fw-normal small ms-1">(Leads deren E-Mail in einer Kampagne vorkommt)</span>
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kampagne</th>
                                <th>Status</th>
                                <th>Leads gesamt</th>
                                <th>Erfolgreich</th>
                                <th>Neu</th>
                                <th>In Bearbeitung</th>
                                <th>Kontaktiert</th>
                                <th>Erfolgsrate</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($leads_per_camp)): ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                Noch keine Leads aus Kampagnen.
                            </td></tr>
                        <?php else: ?>
                        <?php foreach ($leads_per_camp as $lc):
                            $lt = (int)$lc['total_leads'];
                            $ls = (int)$lc['successful_leads'];
                            $statusColors = [
                                'running'   => 'success',
                                'completed' => 'primary',
                                'paused'    => 'warning',
                                'draft'     => 'secondary',
                                'failed'    => 'danger',
                            ];
                            $sc = $statusColors[$lc['camp_status']] ?? 'secondary';
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($lc['campaign_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge bg-<?= $sc ?>"><?= htmlspecialchars($lc['camp_status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><?= number_format($lt, 0, ',', '.') ?></td>
                            <td class="text-success fw-semibold"><?= number_format($ls, 0, ',', '.') ?></td>
                            <td><?= number_format((int)$lc['new_leads'], 0, ',', '.') ?></td>
                            <td><?= number_format((int)$lc['inprogress_leads'], 0, ',', '.') ?></td>
                            <td><?= number_format((int)$lc['contacted_leads'], 0, ',', '.') ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:6px;min-width:60px;">
                                        <div class="progress-bar bg-success" style="width:<?= min(100, $lt > 0 ? round($ls/$lt*100) : 0) ?>%"></div>
                                    </div>
                                    <span class="small fw-semibold text-success"><?= pct($ls, $lt) ?></span>
                                </div>
                            </td>
                            <td>
                                <a href="stats.php?id=<?= (int)$lc['campaign_id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-bar-chart"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- /.admin-content -->
</div><!-- /.admin-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const chartDefaults = {
    borderWidth: 2,
    pointRadius: 3,
    tension: 0.35,
};

// Hourly chart
new Chart(document.getElementById('hourlyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [
            { label: 'Versendet',  data: <?= json_encode($chart_sent) ?>,    backgroundColor: 'rgba(13,110,253,0.7)' },
            { label: 'Geöffnet',   data: <?= json_encode($chart_opened) ?>,  backgroundColor: 'rgba(25,135,84,0.7)' },
            { label: 'Geklickt',   data: <?= json_encode($chart_clicked) ?>, backgroundColor: 'rgba(13,202,240,0.7)' },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

// Daily chart
<?php if (!empty($day_labels)): ?>
new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($day_labels) ?>,
        datasets: [
            { label: 'Versendet', data: <?= json_encode($day_sent) ?>,    borderColor: 'rgba(13,110,253,1)',   backgroundColor: 'rgba(13,110,253,0.1)', fill: true, ...<?= json_encode($chartDefaults) ?> },
            { label: 'Geöffnet',  data: <?= json_encode($day_opened) ?>,  borderColor: 'rgba(25,135,84,1)',    backgroundColor: 'rgba(25,135,84,0.1)',  fill: true, ...<?= json_encode($chartDefaults) ?> },
            { label: 'Geklickt',  data: <?= json_encode($day_clicked) ?>, borderColor: 'rgba(13,202,240,1)',   backgroundColor: 'rgba(13,202,240,0.1)', fill: true, ...<?= json_encode($chartDefaults) ?> },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
<?php else: ?>
document.getElementById('dailyChart').parentElement.innerHTML =
    '<p class="text-center text-muted py-5">Noch keine Versanddaten für die letzten 30 Tage.</p>';
<?php endif; ?>
</script>
</body>
</html>
