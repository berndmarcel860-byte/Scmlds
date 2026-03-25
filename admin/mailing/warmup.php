<?php
/**
 * E-Mail-Marketing – IP-Warmup System
 * Tracks daily sending volumes per SMTP account and generates warmup schedules.
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

admin_check();
ensure_warmup_table();

$msg      = '';
$msg_type = 'success';

$accounts = get_mailing_smtp_accounts();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'log') {
        $aid  = (int)($_POST['smtp_account_id'] ?? 0);
        $date = trim($_POST['log_date'] ?? date('Y-m-d'));
        $d    = [
            'target'     => (int)($_POST['target']     ?? 0),
            'sent'       => (int)($_POST['sent']       ?? 0),
            'bounced'    => (int)($_POST['bounced']    ?? 0),
            'opened'     => (int)($_POST['opened']     ?? 0),
            'day_number' => (int)($_POST['day_number'] ?? 1),
            'notes'      => trim($_POST['notes']       ?? ''),
        ];
        if ($aid && $date) {
            upsert_warmup_log($aid, $date, $d);
            $msg = 'Warmup-Eintrag gespeichert.';
        } else {
            $msg_type = 'danger';
            $msg = 'Account und Datum sind Pflichtfelder.';
        }
    }
}

// Load warmup log for selected account
$sel_account_id = (int)($_GET['account'] ?? ($accounts[0]['id'] ?? 0));
$warmup_log     = $sel_account_id ? get_warmup_schedule($sel_account_id) : [];
$warmup_sched   = generate_warmup_schedule(20, 200);

// Build indexed log by day number for display
$log_by_day = [];
foreach ($warmup_log as $row) {
    $log_by_day[$row['day_number']] = $row;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP-Warmup – E-Mail-Marketing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <style>
        .warmup-bar { height: 8px; border-radius: 4px; background: #e9ecef; }
        .warmup-fill { height: 8px; border-radius: 4px; background: var(--bs-success); transition: width .3s; }
        .day-complete { background: rgba(25,135,84,.06); }
    </style>
</head>
<body class="admin-body">

<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<div class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content p-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0"><i class="bi bi-fire me-2 text-warning"></i>IP-Warmup</h4>
                <p class="text-muted small mb-0">Tägliche Sendevolumina steigern und SMTP-Reputation aufbauen</p>
            </div>
            <a href="/admin/mailing/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Kampagnen
            </a>
        </div>

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show">
            <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (empty($accounts)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Keine SMTP-Accounts gefunden. <a href="/admin/mailing/smtp_accounts.php">Jetzt hinzufügen</a>.
        </div>
        <?php else: ?>

        <div class="row g-4">
            <!-- Left: account selector + log entry -->
            <div class="col-lg-4">

                <!-- Account selector -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-semibold mb-0"><i class="bi bi-server me-1"></i>SMTP-Account wählen</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($accounts as $a): ?>
                        <a href="?account=<?= $a['id'] ?>"
                           class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2
                                  <?= $a['id'] === $sel_account_id ? 'active' : '' ?>">
                            <div>
                                <div class="fw-semibold small"><?= htmlspecialchars($a['label'] ?: $a['from_email']) ?></div>
                                <div class="text-muted" style="font-size:.75em"><?= htmlspecialchars($a['from_email']) ?></div>
                            </div>
                            <span class="badge bg-<?= $a['active'] ? 'success' : 'secondary' ?>">
                                <?= $a['active'] ? 'Aktiv' : 'Inaktiv' ?>
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Log new warmup day -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-semibold mb-0"><i class="bi bi-journal-plus me-1"></i>Tag eintragen</h6>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="action" value="log">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">SMTP-Account</label>
                                <select name="smtp_account_id" class="form-select" required>
                                    <?php foreach ($accounts as $a): ?>
                                    <option value="<?= $a['id'] ?>"
                                        <?= $a['id'] === $sel_account_id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($a['label'] ?: $a['from_email']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-7">
                                    <label class="form-label fw-semibold">Datum</label>
                                    <input type="date" name="log_date" class="form-control"
                                           value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-5">
                                    <label class="form-label fw-semibold">Tag #</label>
                                    <input type="number" name="day_number" class="form-control"
                                           min="1" max="90" value="<?= count($warmup_log) + 1 ?>">
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Ziel</label>
                                    <input type="number" name="target" class="form-control" min="0" value="20">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Gesendet</label>
                                    <input type="number" name="sent" class="form-control" min="0" value="0">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Bounces</label>
                                    <input type="number" name="bounced" class="form-control" min="0" value="0">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Öffnungen</label>
                                    <input type="number" name="opened" class="form-control" min="0" value="0">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Notizen</label>
                                <input type="text" name="notes" class="form-control"
                                       placeholder="Optional: Probleme, Anmerkungen …">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-save me-1"></i>Eintragen
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right: warmup progress + schedule -->
            <div class="col-lg-8">

                <!-- Summary stats -->
                <?php
                $total_sent    = array_sum(array_column($warmup_log, 'sent'));
                $total_bounced = array_sum(array_column($warmup_log, 'bounced'));
                $days_done     = count($warmup_log);
                $bounce_rate   = $total_sent > 0 ? round($total_bounced / $total_sent * 100, 1) : 0;
                ?>
                <div class="row g-3 mb-4">
                    <div class="col-3 text-center">
                        <div class="card shadow-sm border-0 p-3">
                            <div class="fw-bold fs-4 text-primary"><?= $days_done ?></div>
                            <div class="text-muted small">Tage aktiv</div>
                        </div>
                    </div>
                    <div class="col-3 text-center">
                        <div class="card shadow-sm border-0 p-3">
                            <div class="fw-bold fs-4 text-success"><?= number_format($total_sent) ?></div>
                            <div class="text-muted small">Gesendet gesamt</div>
                        </div>
                    </div>
                    <div class="col-3 text-center">
                        <div class="card shadow-sm border-0 p-3">
                            <div class="fw-bold fs-4 <?= $bounce_rate > 5 ? 'text-danger' : 'text-success' ?>">
                                <?= $bounce_rate ?>%
                            </div>
                            <div class="text-muted small">Bounce-Rate</div>
                        </div>
                    </div>
                    <div class="col-3 text-center">
                        <div class="card shadow-sm border-0 p-3">
                            <div class="fw-bold fs-4 text-warning"><?= 30 - $days_done ?></div>
                            <div class="text-muted small">Tage verbleibend</div>
                        </div>
                    </div>
                </div>

                <!-- Warmup schedule table -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between">
                        <h6 class="fw-semibold mb-0">30-Tage Warmup-Plan</h6>
                        <span class="badge bg-primary"><?= $days_done ?>/30 Tage erledigt</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tag</th>
                                    <th>Ziel</th>
                                    <th>Gesendet</th>
                                    <th>Öffnungen</th>
                                    <th>Bounces</th>
                                    <th>Fortschritt</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($warmup_sched as $s):
                                $done_row = $log_by_day[$s['day']] ?? null;
                                $pct = $done_row && $s['target'] > 0
                                    ? min(100, round($done_row['sent'] / $s['target'] * 100))
                                    : 0;
                            ?>
                            <tr class="<?= $done_row ? 'day-complete' : '' ?>">
                                <td class="fw-semibold"><?= $s['day'] ?></td>
                                <td><?= number_format($s['target']) ?></td>
                                <td><?= $done_row ? number_format($done_row['sent']) : '<span class="text-muted">—</span>' ?></td>
                                <td><?= $done_row ? number_format($done_row['opened']) : '<span class="text-muted">—</span>' ?></td>
                                <td>
                                    <?php if ($done_row):
                                        $br = $done_row['sent'] > 0
                                            ? round($done_row['bounced'] / $done_row['sent'] * 100, 1) : 0;
                                    ?>
                                    <span class="<?= $br > 5 ? 'text-danger fw-bold' : '' ?>">
                                        <?= $done_row['bounced'] ?> (<?= $br ?>%)
                                    </span>
                                    <?php else: ?>
                                    <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="min-width:80px">
                                    <div class="warmup-bar">
                                        <div class="warmup-fill <?= $pct >= 100 ? '' : 'bg-warning' ?>"
                                             style="width:<?= $pct ?>%; <?= $pct >= 100 ? 'background:var(--bs-success)!important' : '' ?>"></div>
                                    </div>
                                    <span class="text-muted" style="font-size:.7em"><?= $pct ?>%</span>
                                </td>
                                <td>
                                    <?php if ($done_row): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-lg"></i> Erledigt</span>
                                    <?php elseif ($s['day'] === $days_done + 1): ?>
                                    <span class="badge bg-warning text-dark">Heute</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Ausstehend</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Warmup tips -->
                <div class="card border-0 bg-info-subtle mt-4">
                    <div class="card-body">
                        <h6 class="fw-semibold"><i class="bi bi-lightbulb me-1 text-info"></i>Warmup-Tipps</h6>
                        <ul class="small mb-0">
                            <li class="mb-1"><strong>Beginnen Sie langsam:</strong> Tag 1–3: 20–30 E-Mails/Tag. Steigern Sie alle 3 Tage um ca. 50%.</li>
                            <li class="mb-1"><strong>Bounce-Rate &lt; 5%:</strong> Liegt sie höher, stoppen Sie und bereinigen Sie die Liste.</li>
                            <li class="mb-1"><strong>Spam-Ordner prüfen:</strong> Senden Sie täglich Testmails und prüfen Sie manuell die Zustellung.</li>
                            <li class="mb-1"><strong>Öffnungsrate fördern:</strong> Bitten Sie Bekannte, die Warmup-Mails zu öffnen und zu beantworten.</li>
                            <li class="mb-1"><strong>DKIM/SPF/DMARC:</strong> Alle DNS-Einträge müssen korrekt konfiguriert sein bevor Sie beginnen.</li>
                            <li><strong>Pause bei Problemen:</strong> Bei hoher Bounce-Rate oder Spam-Beschwerden sofort pausieren und korrigieren.</li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>

        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
