<?php
/**
 * E-Mail-Marketing – Kampagnen-Statistiken
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

admin_check();

$cid = (int) ($_GET['id'] ?? 0);
if (!$cid) { header('Location: index.php'); exit; }

$campaign = get_mailing_campaign($cid);
if (!$campaign) { header('Location: index.php'); exit; }

$stats = get_campaign_stats($cid);

// Pagination for recipients
$page      = max(1, (int) ($_GET['page'] ?? 1));
$per_page  = 50;
$filter    = in_array($_GET['filter'] ?? '', ['','pending','sent','failed','bounced','unsubscribed']) ? ($_GET['filter'] ?? '') : '';
$total_r   = count_mailing_recipients($cid, $filter);
$pages     = max(1, (int) ceil($total_r / $per_page));
$recipients = get_mailing_recipients($cid, $filter, $per_page, ($page - 1) * $per_page);

// Per-account stats
$pdo = db_connect();
$acc_stats = $pdo->prepare(
    'SELECT a.id, a.label, a.from_email, COUNT(r.id) AS sent_count
     FROM mailing_recipients r
     JOIN mailing_smtp_accounts a ON a.id = r.smtp_account_id
     WHERE r.campaign_id = :cid AND r.status = "sent"
     GROUP BY a.id, a.label, a.from_email ORDER BY sent_count DESC'
);
$acc_stats->execute([':cid' => $cid]);
$account_breakdown = $acc_stats->fetchAll(PDO::FETCH_ASSOC);

// Open rate / click rate
$open_rate  = $stats['sent'] > 0 ? round($stats['opens']  / $stats['sent'] * 100, 1) : 0;
$click_rate = $stats['sent'] > 0 ? round($stats['clicks'] / $stats['sent'] * 100, 1) : 0;
$fail_rate  = $stats['total'] > 0 ? round($stats['failed'] / $stats['total'] * 100, 1) : 0;
$done_rate  = $stats['total'] > 0 ? round($stats['sent']   / $stats['total'] * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiken: <?= htmlspecialchars($campaign['name']) ?> – Marketing</title>
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
                <h4 class="fw-bold mb-0"><i class="bi bi-bar-chart me-2 text-primary"></i>Statistiken</h4>
                <p class="text-muted small mb-0"><?= htmlspecialchars($campaign['name']) ?> &nbsp;·&nbsp;
                    Status: <span class="badge bg-<?= ['draft'=>'secondary','running'=>'success','paused'=>'warning','completed'=>'primary','failed'=>'danger'][$campaign['status']] ?? 'secondary' ?>"><?= $campaign['status'] ?></span>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="campaign_edit.php?id=<?= $cid ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Kampagne</a>
                <a href="index.php" class="btn btn-outline-secondary btn-sm">Alle Kampagnen</a>
            </div>
        </div>

        <!-- KPI cards -->
        <div class="row g-3 mb-4">
            <?php
            $kpis = [
                ['primary',   'Gesamt (gültig)',  $stats['total'],   null],
                ['secondary', 'Ungültig',         $stats['invalid'], null],
                ['warning',   'Ausstehend',        $stats['pending'], null],
                ['success',   'Gesendet',          $stats['sent'],    $done_rate . '%'],
                ['danger',    'Fehler',             $stats['failed'],  $fail_rate . '%'],
                ['info',      'Geöffnet',           $stats['opens'],   $open_rate . '%'],
                ['primary',   'Link geklickt',      $stats['clicks'],  $click_rate . '%'],
            ];
            foreach ($kpis as [$color, $label, $val, $sub]):
            ?>
            <div class="col-6 col-md">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-3">
                        <div class="fw-bold fs-2 text-<?= $color ?>"><?= number_format($val) ?></div>
                        <div class="text-muted small"><?= $label ?></div>
                        <?php if ($sub): ?>
                        <div class="badge bg-<?= $color ?>-subtle text-<?= $color ?> mt-1"><?= $sub ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-4 mb-4">
            <!-- Progress bar card -->
            <div class="col-md-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3"><h6 class="fw-semibold mb-0">Versand-Fortschritt</h6></div>
                    <div class="card-body">
                        <?php
                        $total = max(1, $stats['total']);
                        $bar_data = [
                            ['success', 'Gesendet',   $stats['sent']],
                            ['danger',  'Fehler',     $stats['failed']],
                            ['warning', 'Ausstehend', $stats['pending']],
                        ];
                        ?>
                        <div class="progress mb-3" style="height:24px">
                            <?php foreach ($bar_data as [$c, $l, $v]): if (!$v) continue; ?>
                            <div class="progress-bar bg-<?= $c ?>" style="width:<?= round($v/$total*100) ?>%"
                                 title="<?= $l ?>: <?= $v ?>"><?= round($v/$total*100) ?>%</div>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-flex gap-3 small">
                            <?php foreach ($bar_data as [$c, $l, $v]): ?>
                            <span><span class="badge bg-<?= $c ?>">&nbsp;</span> <?= $l ?>: <strong><?= number_format($v) ?></strong></span>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($campaign['started_at']): ?>
                        <div class="text-muted small mt-3">
                            <i class="bi bi-calendar me-1"></i>Gestartet: <?= date('d.m.Y H:i', strtotime($campaign['started_at'])) ?>
                            <?php if ($campaign['finished_at']): ?>
                            &nbsp;·&nbsp; Beendet: <?= date('d.m.Y H:i', strtotime($campaign['finished_at'])) ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Per-account breakdown -->
            <div class="col-md-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3"><h6 class="fw-semibold mb-0">Versandt pro SMTP-Account</h6></div>
                    <?php if (empty($account_breakdown)): ?>
                    <div class="card-body text-muted text-center">Noch keine Daten.</div>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($account_breakdown as $ab): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <div>
                                <div class="small fw-semibold"><?= htmlspecialchars($ab['label'] ?: $ab['from_email']) ?></div>
                                <div class="text-muted" style="font-size:.75em"><?= htmlspecialchars($ab['from_email']) ?></div>
                            </div>
                            <span class="badge bg-primary rounded-pill"><?= number_format($ab['sent_count']) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recipients table -->
        <div class="card border-0 shadow-sm" id="recipients">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h6 class="fw-semibold mb-0">Empfänger-Liste</h6>
                <div class="d-flex gap-2 align-items-center">
                    <form method="get" class="d-flex gap-2">
                        <input type="hidden" name="id" value="<?= $cid ?>">
                        <select name="filter" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Alle (gültig)</option>
                            <?php foreach (['pending'=>'Ausstehend','sent'=>'Gesendet','failed'=>'Fehler','bounced'=>'Bounce'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= $filter === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <a href="leads.php?campaign_id=<?= $cid ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-people me-1"></i>Alle Leads
                    </a>
                    <a href="../ajax/export_recipients.php?campaign_id=<?= $cid ?>&filter=<?= $filter ?>"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-download me-1"></i>CSV
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>E-Mail</th>
                            <th>Name</th>
                            <th>Gültigkeit</th>
                            <th>Status</th>
                            <th>SMTP</th>
                            <th>Versendet am</th>
                            <th>Geöffnet</th>
                            <th>Link geklickt</th>
                            <th>Fehlermeldung</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($recipients)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">Keine Einträge.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recipients as $r): ?>
                    <?php
                        $smtpLabel = '—';
                        if ($r['smtp_account_id']) {
                            $a = get_mailing_smtp_account((int) $r['smtp_account_id']);
                            $smtpLabel = $a ? htmlspecialchars($a['label'] ?: $a['from_email']) : $r['smtp_account_id'];
                        }
                        $sc = ['pending'=>'secondary','sent'=>'success','failed'=>'danger','bounced'=>'warning','unsubscribed'=>'info'][$r['status']] ?? 'secondary';
                        $vc = ($r['email_validity'] ?? 'valid') === 'valid' ? 'success' : 'danger';
                        $vl = ($r['email_validity'] ?? 'valid') === 'valid' ? 'Gültig' : 'Ungültig';
                    ?>
                    <tr>
                        <td class="text-muted small"><?= $r['id'] ?></td>
                        <td class="small"><?= htmlspecialchars($r['email']) ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($r['name']) ?></td>
                        <td><span class="badge bg-<?= $vc ?>-subtle text-<?= $vc ?>" style="font-size:.72em"><?= $vl ?></span></td>
                        <td><span class="badge bg-<?= $sc ?>" style="font-size:.72em"><?= $r['status'] ?></span></td>
                        <td class="small text-muted"><?= $smtpLabel ?></td>
                        <td class="small text-muted"><?= $r['sent_at'] ? date('d.m.Y H:i', strtotime($r['sent_at'])) : '—' ?></td>
                        <td class="small">
                            <?php if ($r['opened_at']): ?>
                                <span class="text-success"><i class="bi bi-envelope-open-fill"></i> <?= date('d.m.Y H:i', strtotime($r['opened_at'])) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="small">
                            <?php if ($r['clicked_at'] ?? null): ?>
                                <span class="text-primary"><i class="bi bi-cursor-fill"></i> <?= date('d.m.Y H:i', strtotime($r['clicked_at'])) ?>
                                <?php if (($r['click_count'] ?? 0) > 1): ?>
                                    <span class="badge bg-primary-subtle text-primary ms-1"><?= (int)$r['click_count'] ?>×</span>
                                <?php endif; ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-danger"><?= htmlspecialchars(substr($r['error_msg'] ?? '', 0, 60)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pages > 1): ?>
            <div class="card-footer bg-white">
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($p = 1; $p <= $pages; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?id=<?= $cid ?>&page=<?= $p ?>&filter=<?= $filter ?>#recipients"><?= $p ?></a>
                        </li>
                        <?php endfor; ?>
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
