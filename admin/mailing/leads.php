<?php
/**
 * E-Mail-Marketing – Leads / Kontakte Übersicht
 *
 * Lists ALL mailing recipients across all campaigns (or filtered by one),
 * showing email validity, send status, opens, link clicks, and error details.
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

admin_check();
ensure_mailing_tables();

$pdo = db_connect();

// ── Filters ───────────────────────────────────────────────────────────────────
$filter_campaign  = (int)  ($_GET['campaign_id'] ?? 0);
$filter_validity  = in_array($_GET['validity']  ?? '', ['','valid','invalid'])   ? ($_GET['validity']  ?? '') : '';
$filter_status    = in_array($_GET['status']    ?? '', ['','pending','sent','failed','bounced','unsubscribed']) ? ($_GET['status'] ?? '') : '';
$filter_opened    = in_array($_GET['opened']    ?? '', ['','yes','no'])           ? ($_GET['opened']   ?? '') : '';
$filter_clicked   = in_array($_GET['clicked']   ?? '', ['','yes','no'])           ? ($_GET['clicked']  ?? '') : '';
$search           = trim($_GET['search'] ?? '');
$page             = max(1, (int) ($_GET['page'] ?? 1));
$per_page         = 50;

// ── Build WHERE clause ────────────────────────────────────────────────────────
$where   = ['1=1'];
$params  = [];

if ($filter_campaign) {
    $where[]  = 'r.campaign_id = :cid';
    $params[':cid'] = $filter_campaign;
}
if ($filter_validity) {
    $where[]  = 'r.email_validity = :ev';
    $params[':ev'] = $filter_validity;
}
if ($filter_status) {
    $where[]  = 'r.status = :st';
    $params[':st'] = $filter_status;
}
if ($filter_opened === 'yes') {
    $where[] = 'r.opened_at IS NOT NULL';
} elseif ($filter_opened === 'no') {
    $where[] = 'r.opened_at IS NULL';
}
if ($filter_clicked === 'yes') {
    $where[] = 'r.clicked_at IS NOT NULL';
} elseif ($filter_clicked === 'no') {
    $where[] = 'r.clicked_at IS NULL';
}
if ($search !== '') {
    $where[]  = '(r.email LIKE :sr OR r.name LIKE :sr2)';
    $params[':sr']  = '%' . $search . '%';
    $params[':sr2'] = '%' . $search . '%';
}

$where_sql = implode(' AND ', $where);

// ── CSV Export (must happen before any HTML output) ───────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $export_stmt = $pdo->prepare(
        "SELECT r.*, c.name AS campaign_name
         FROM mailing_recipients r
         LEFT JOIN mailing_campaigns c ON c.id = r.campaign_id
         WHERE $where_sql
         ORDER BY r.id DESC"
    );
    $export_stmt->execute($params);
    $all_export = $export_stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="leads_export_' . date('Ymd_His') . '.csv"');
    header('Pragma: no-cache');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Kampagne','E-Mail','Name','Plattform','Gültigkeit','Status','Versendet am','Geöffnet am','Link geklickt','Klick-Anzahl','Fehlermeldung']);
    foreach ($all_export as $row) {
        fputcsv($out, [
            $row['id'],
            $row['campaign_name'] ?? '',
            $row['email'],
            $row['name'],
            $row['scam_platform'] ?? '',
            $row['email_validity'] ?? 'valid',
            $row['status'],
            $row['sent_at']     ?? '',
            $row['opened_at']   ?? '',
            $row['clicked_at']  ?? '',
            $row['click_count'] ?? 0,
            $row['error_msg']   ?? '',
        ]);
    }
    fclose($out);
    exit;
}

// ── Total count ───────────────────────────────────────────────────────────────
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM mailing_recipients r WHERE $where_sql");
$count_stmt->execute($params);
$total_records = (int) $count_stmt->fetchColumn();
$total_pages   = max(1, (int) ceil($total_records / $per_page));
$offset        = ($page - 1) * $per_page;

// ── Records ───────────────────────────────────────────────────────────────────
$data_stmt = $pdo->prepare(
    "SELECT r.*, c.name AS campaign_name
     FROM mailing_recipients r
     LEFT JOIN mailing_campaigns c ON c.id = r.campaign_id
     WHERE $where_sql
     ORDER BY r.id DESC
     LIMIT $per_page OFFSET $offset"
);
$data_stmt->execute($params);
$recipients = $data_stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Summary stats (for current filter set) ────────────────────────────────────
$agg = $pdo->prepare(
    "SELECT
        COUNT(*) AS total_all,
        SUM(email_validity = 'valid')   AS cnt_valid,
        SUM(email_validity = 'invalid') AS cnt_invalid,
        SUM(status = 'sent')            AS cnt_sent,
        SUM(status = 'failed')          AS cnt_failed,
        SUM(status = 'pending' AND email_validity = 'valid') AS cnt_pending,
        SUM(opened_at IS NOT NULL AND email_validity = 'valid') AS cnt_opened,
        SUM(clicked_at IS NOT NULL AND email_validity = 'valid') AS cnt_clicked
     FROM mailing_recipients r WHERE $where_sql"
);
$agg->execute($params);
$summary = $agg->fetch(PDO::FETCH_ASSOC);

// ── Campaign list for filter dropdown ─────────────────────────────────────────
$campaigns = $pdo->query("SELECT id, name FROM mailing_campaigns ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// ── Query string helper ───────────────────────────────────────────────────────
function leads_qs(array $overrides = []): string {
    $base = [
        'campaign_id' => $_GET['campaign_id'] ?? '',
        'validity'    => $_GET['validity']    ?? '',
        'status'      => $_GET['status']      ?? '',
        'opened'      => $_GET['opened']      ?? '',
        'clicked'     => $_GET['clicked']     ?? '',
        'search'      => $_GET['search']      ?? '',
        'page'        => $_GET['page']        ?? '1',
    ];
    $merged = array_merge($base, $overrides);
    $parts  = [];
    foreach ($merged as $k => $v) {
        if ($v !== '' && $v !== '0') $parts[] = urlencode($k) . '=' . urlencode($v);
    }
    return $parts ? '?' . implode('&', $parts) : '?';
}

$sent_count = (int)($summary['cnt_sent'] ?? 0);
$open_rate  = $sent_count > 0 ? round($summary['cnt_opened']  / $sent_count * 100, 1) : 0;
$click_rate = $sent_count > 0 ? round($summary['cnt_clicked'] / $sent_count * 100, 1) : 0;
$valid_count = (int)($summary['cnt_valid'] ?? 0);
$fail_rate  = $valid_count > 0 ? round((int)($summary['cnt_failed'] ?? 0) / $valid_count * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads / Kontakte – E-Mail-Marketing</title>
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

        <!-- Page header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0"><i class="bi bi-people-fill me-2 text-primary"></i>Leads / Kontakte</h4>
                <p class="text-muted small mb-0">Alle importierten E-Mail-Empfänger inkl. Gültigkeit, Versandstatus, Öffnungen und Klicks</p>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-grid me-1"></i>Kampagnen
                </a>
                <a href="leads.php<?= leads_qs(['export'=>'csv','page'=>'']) ?>" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-download me-1"></i>CSV Export
                </a>
            </div>
        </div>

        <!-- KPI cards -->
        <div class="row g-3 mb-4">
            <?php
            $kpis = [
                ['dark',    'Gesamt',          $summary['total_all']    ?? 0, null],
                ['success', 'Gültig',          $summary['cnt_valid']    ?? 0, null],
                ['danger',  'Ungültig',         $summary['cnt_invalid']  ?? 0, null],
                ['primary', 'E-Mail gesendet',  $summary['cnt_sent']     ?? 0, null],
                ['warning', 'Fehler',           $summary['cnt_failed']   ?? 0, $fail_rate  > 0 ? $fail_rate  . '%' : null],
                ['info',    'Geöffnet',         $summary['cnt_opened']   ?? 0, $open_rate  > 0 ? $open_rate  . '%' : null],
                ['primary', 'Link geklickt',    $summary['cnt_clicked']  ?? 0, $click_rate > 0 ? $click_rate . '%' : null],
            ];
            foreach ($kpis as [$color, $label, $val, $sub]):
            ?>
            <div class="col-6 col-sm-4 col-md">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center py-3">
                        <div class="fw-bold fs-3 text-<?= $color ?>"><?= number_format((int)$val) ?></div>
                        <div class="text-muted small"><?= $label ?></div>
                        <?php if ($sub): ?>
                        <div class="badge bg-<?= $color ?>-subtle text-<?= $color ?> mt-1"><?= $sub ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Filter bar -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-2 px-3">
                <form method="get" class="row g-2 align-items-end">
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label small mb-1">Kampagne</label>
                        <select name="campaign_id" class="form-select form-select-sm">
                            <option value="">Alle Kampagnen</option>
                            <?php foreach ($campaigns as $camp): ?>
                            <option value="<?= $camp['id'] ?>" <?= $filter_campaign == $camp['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($camp['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <label class="form-label small mb-1">Gültigkeit</label>
                        <select name="validity" class="form-select form-select-sm">
                            <option value="">Alle</option>
                            <option value="valid"   <?= $filter_validity === 'valid'   ? 'selected' : '' ?>>✔ Gültig</option>
                            <option value="invalid" <?= $filter_validity === 'invalid' ? 'selected' : '' ?>>✘ Ungültig</option>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <label class="form-label small mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Alle</option>
                            <option value="pending"      <?= $filter_status === 'pending'      ? 'selected' : '' ?>>Ausstehend</option>
                            <option value="sent"         <?= $filter_status === 'sent'         ? 'selected' : '' ?>>E-Mail gesendet</option>
                            <option value="failed"       <?= $filter_status === 'failed'       ? 'selected' : '' ?>>E-Mail Fehler</option>
                            <option value="bounced"      <?= $filter_status === 'bounced'      ? 'selected' : '' ?>>Bounce</option>
                            <option value="unsubscribed" <?= $filter_status === 'unsubscribed' ? 'selected' : '' ?>>Abgemeldet</option>
                        </select>
                    </div>
                    <div class="col-4 col-sm-3 col-md-1">
                        <label class="form-label small mb-1">Geöffnet</label>
                        <select name="opened" class="form-select form-select-sm">
                            <option value="">—</option>
                            <option value="yes" <?= $filter_opened === 'yes' ? 'selected' : '' ?>>Ja</option>
                            <option value="no"  <?= $filter_opened === 'no'  ? 'selected' : '' ?>>Nein</option>
                        </select>
                    </div>
                    <div class="col-4 col-sm-3 col-md-1">
                        <label class="form-label small mb-1">Geklickt</label>
                        <select name="clicked" class="form-select form-select-sm">
                            <option value="">—</option>
                            <option value="yes" <?= $filter_clicked === 'yes' ? 'selected' : '' ?>>Ja</option>
                            <option value="no"  <?= $filter_clicked === 'no'  ? 'selected' : '' ?>>Nein</option>
                        </select>
                    </div>
                    <div class="col-12 col-md">
                        <label class="form-label small mb-1">Suche (E-Mail / Name)</label>
                        <input type="text" name="search" class="form-control form-control-sm"
                               placeholder="E-Mail oder Name…" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-auto d-flex gap-2 align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-funnel me-1"></i>Filtern
                        </button>
                        <a href="leads.php<?= $filter_campaign ? '?campaign_id=' . $filter_campaign : '' ?>"
                           class="btn btn-outline-secondary btn-sm">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recipients table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
                <span class="small text-muted">
                    <strong><?= number_format($total_records) ?></strong> Einträge gefunden
                    · Seite <strong><?= $page ?></strong> / <?= $total_pages ?>
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">#</th>
                            <th>E-Mail</th>
                            <th>Name</th>
                            <th>Plattform</th>
                            <th>Kampagne</th>
                            <th>Gültigkeit</th>
                            <th>Versandstatus</th>
                            <th>Geöffnet</th>
                            <th>Geklickt</th>
                            <th style="width:60px" class="text-center">Klicks</th>
                            <th>Versendet am</th>
                            <th>Fehlermeldung</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($recipients)): ?>
                    <tr>
                        <td colspan="12" class="text-center text-muted py-5">
                            <i class="bi bi-inbox display-5 d-block mb-2 opacity-25"></i>
                            Keine Einträge gefunden.
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($recipients as $r):
                        $validity  = $r['email_validity'] ?? 'valid';
                        $vc = $validity === 'valid' ? 'success' : 'danger';
                        $vl = $validity === 'valid' ? 'Gültig'  : 'Ungültig';
                        $statusMap = [
                            'pending'      => ['secondary', 'Ausstehend'],
                            'sent'         => ['success',   'E-Mail gesendet'],
                            'failed'       => ['danger',    'E-Mail Fehler'],
                            'bounced'      => ['warning',   'Bounce'],
                            'unsubscribed' => ['info',      'Abgemeldet'],
                        ];
                        [$sc, $sl] = $statusMap[$r['status']] ?? ['secondary', htmlspecialchars($r['status'])];
                    ?>
                    <tr class="<?= $validity === 'invalid' ? 'opacity-60' : '' ?>">
                        <td class="text-muted small"><?= $r['id'] ?></td>
                        <td class="small fw-semibold"><?= htmlspecialchars($r['email']) ?></td>
                        <td class="small"><?= htmlspecialchars($r['name'] ?: '—') ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($r['scam_platform'] ?: '—') ?></td>
                        <td class="small">
                            <?php if ($r['campaign_name']): ?>
                            <a href="stats.php?id=<?= (int)$r['campaign_id'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($r['campaign_name']) ?>
                            </a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $vc ?>-subtle text-<?= $vc ?>" style="font-size:.72em"><?= $vl ?></span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $sc ?>" style="font-size:.72em"><?= $sl ?></span>
                        </td>
                        <td class="small">
                            <?php if ($r['opened_at']): ?>
                                <span class="text-success" title="Geöffnet: <?= htmlspecialchars($r['opened_at']) ?>">
                                    <i class="bi bi-envelope-open-fill"></i>
                                    <?= date('d.m.y H:i', strtotime($r['opened_at'])) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="small">
                            <?php if (!empty($r['clicked_at'])): ?>
                                <span class="text-primary" title="Geklickt: <?= htmlspecialchars($r['clicked_at']) ?>">
                                    <i class="bi bi-cursor-fill"></i>
                                    <?= date('d.m.y H:i', strtotime($r['clicked_at'])) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ((int)($r['click_count'] ?? 0) > 0): ?>
                                <span class="badge bg-primary rounded-pill"><?= (int)$r['click_count'] ?></span>
                            <?php else: ?>
                                <span class="text-muted small">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted" style="white-space:nowrap">
                            <?= $r['sent_at'] ? date('d.m.y H:i', strtotime($r['sent_at'])) : '—' ?>
                        </td>
                        <td class="small text-danger"
                            style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                            title="<?= htmlspecialchars($r['error_msg'] ?? '') ?>">
                            <?= htmlspecialchars(substr($r['error_msg'] ?? '', 0, 50)) ?: '' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="card-footer bg-white">
                <nav>
                    <ul class="pagination pagination-sm mb-0 flex-wrap">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="leads.php<?= leads_qs(['page' => $page - 1]) ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php
                        $p_start = max(1, $page - 3);
                        $p_end   = min($total_pages, $page + 3);
                        if ($p_start > 1): ?>
                        <li class="page-item disabled"><span class="page-link">1</span></li>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php endif; ?>
                        <?php for ($p = $p_start; $p <= $p_end; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="leads.php<?= leads_qs(['page' => $p]) ?>"><?= $p ?></a>
                        </li>
                        <?php endfor; ?>
                        <?php if ($p_end < $total_pages): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                        <li class="page-item"><a class="page-link" href="leads.php<?= leads_qs(['page' => $total_pages]) ?>"><?= $total_pages ?></a></li>
                        <?php endif; ?>
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="leads.php<?= leads_qs(['page' => $page + 1]) ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
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
