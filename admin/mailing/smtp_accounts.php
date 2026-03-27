<?php
/**
 * E-Mail-Marketing – SMTP-Accounts verwalten
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

admin_check();

$msg      = '';
$msg_type = 'success';
$edit     = null;

// ── Handle form submissions ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $secure = in_array($_POST['secure'] ?? '', ['tls','ssl','none']) ? $_POST['secure'] : 'tls';
        $d = [
            'label'      => trim($_POST['label']      ?? ''),
            'host'       => trim($_POST['host']        ?? ''),
            'port'       => (int) ($_POST['port']      ?? 587),
            'username'   => trim($_POST['username']    ?? ''),
            'password'   => trim($_POST['password']    ?? ''),
            'secure'     => $secure,
            'from_email' => trim($_POST['from_email']  ?? ''),
            'from_name'  => trim($_POST['from_name']   ?? ''),
            'active'     => isset($_POST['active']) ? 1 : 0,
        ];
        if (empty($d['host']) || empty($d['username'])) {
            $msg_type = 'danger';
            $msg = 'Host und Benutzername sind Pflichtfelder.';
        } else {
            save_mailing_smtp_account($d, $id ?: null);
            log_activity('mailing_smtp_saved', 'SMTP account saved: ' . $d['label']);
            $msg = $id ? 'Account aktualisiert.' : 'Account hinzugefügt.';
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            delete_mailing_smtp_account($id);
            log_activity('mailing_smtp_deleted', 'SMTP account deleted: ' . $id);
            $msg = 'Account gelöscht.';
        }
    }
}

if (isset($_GET['edit'])) {
    $edit = get_mailing_smtp_account((int) $_GET['edit']);
}

$accounts = get_mailing_smtp_accounts();

// Delivery health per account: open rate, bounce rate, last 30 days
$pdo = db_connect();
$health_stmt = $pdo->prepare("
    SELECT
        r.smtp_account_id,
        COUNT(CASE WHEN r.status = 'sent'                             THEN 1 END) AS sent,
        COUNT(CASE WHEN r.status IN ('failed','bounced')              THEN 1 END) AS bad,
        COUNT(CASE WHEN r.opened_at IS NOT NULL AND r.status='sent'   THEN 1 END) AS opened
    FROM mailing_recipients r
    WHERE r.sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY r.smtp_account_id
");
$health_stmt->execute();
$health_map = [];
foreach ($health_stmt->fetchAll(PDO::FETCH_ASSOC) as $h) {
    $health_map[(int)$h['smtp_account_id']] = $h;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMTP-Accounts – E-Mail-Marketing</title>
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
                <h4 class="fw-bold mb-0"><i class="bi bi-server me-2 text-primary"></i>SMTP-Accounts</h4>
                <p class="text-muted small mb-0">Mehrere SMTP-Accounts für Rotation beim Massenversand</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Übersicht</a>
        </div>

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show">
            <?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Form -->
            <div class="col-lg-5">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-semibold mb-0"><?= $edit ? 'Account bearbeiten' : 'Neuen Account hinzufügen' ?></h6>
                    </div>
                    <div class="card-body">
                        <form method="post" autocomplete="off">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="id" value="<?= $edit ? (int)$edit['id'] : 0 ?>">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Bezeichnung *</label>
                                <input type="text" name="label" class="form-control" required
                                       value="<?= htmlspecialchars($edit['label'] ?? '') ?>"
                                       placeholder="z.B. Account 1 – info@domain.de">
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-8">
                                    <label class="form-label fw-semibold">SMTP-Host *</label>
                                    <input type="text" name="host" class="form-control" required
                                           value="<?= htmlspecialchars($edit['host'] ?? '') ?>"
                                           placeholder="smtp.example.com">
                                </div>
                                <div class="col-4">
                                    <label class="form-label fw-semibold">Port</label>
                                    <input type="number" name="port" class="form-control"
                                           value="<?= (int)($edit['port'] ?? 587) ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Benutzername *</label>
                                <input type="text" name="username" class="form-control" autocomplete="new-password" required
                                       value="<?= htmlspecialchars($edit['username'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Passwort</label>
                                <input type="password" name="password" class="form-control" autocomplete="new-password"
                                       placeholder="<?= $edit ? '(leer lassen = unverändert)' : '' ?>"
                                       value="<?= htmlspecialchars($edit['password'] ?? '') ?>">
                                <div class="form-text">Wird verschlüsselt in der Datenbank gespeichert.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Verschlüsselung</label>
                                <select name="secure" class="form-select">
                                    <?php foreach (['tls'=>'STARTTLS (587)','ssl'=>'SSL/TLS (465)','none'=>'Keine (unsicher)'] as $v=>$l): ?>
                                    <option value="<?= $v ?>" <?= ($edit['secure'] ?? 'tls') === $v ? 'selected' : '' ?>><?= $l ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Absender-E-Mail</label>
                                    <input type="email" name="from_email" class="form-control"
                                           value="<?= htmlspecialchars($edit['from_email'] ?? '') ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Absender-Name</label>
                                    <input type="text" name="from_name" class="form-control"
                                           value="<?= htmlspecialchars($edit['from_name'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" name="active" class="form-check-input" id="chkActive"
                                       <?= ($edit['active'] ?? 1) ? 'checked' : '' ?> value="1">
                                <label class="form-check-label" for="chkActive">Account aktiv</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-save me-1"></i><?= $edit ? 'Aktualisieren' : 'Hinzufügen' ?>
                            </button>
                            <?php if ($edit): ?>
                            <a href="smtp_accounts.php" class="btn btn-outline-secondary w-100 mt-2">Abbrechen</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Info box: spam prevention tips -->
                <div class="card border-0 bg-primary-subtle mt-3">
                    <div class="card-body">
                        <h6 class="fw-semibold"><i class="bi bi-shield-check me-1"></i>Spam-Schutz-Tipps</h6>
                        <ul class="small mb-0 ps-3">
                            <li>SPF, DKIM und DMARC für jede Domain einrichten</li>
                            <li>Dedicated IPs verwenden (kein Shared Hosting)</li>
                            <li>Max. 200 E-Mails/Tag pro Account am Anfang (IP-Warming)</li>
                            <li>Immer Abmelde-Link im Footer einbauen</li>
                            <li>Keine gekauften E-Mail-Listen – nur opt-in Empfänger</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Account list -->
            <div class="col-lg-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-semibold mb-0">SMTP-Account-Pool (<?= count($accounts) ?> Accounts)</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Bezeichnung</th>
                                    <th>Host</th>
                                    <th>Absender</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Versendet</th>
                                    <th class="text-center">Delivery Health <small class="text-muted">(30d)</small></th>
                                    <th class="text-end">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($accounts)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Noch keine Accounts konfiguriert.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($accounts as $a): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($a['label']) ?></td>
                                <td class="text-muted small"><?= htmlspecialchars($a['host']) ?>:<?= $a['port'] ?></td>
                                <td class="text-muted small"><?= htmlspecialchars($a['from_email']) ?></td>
                                <td class="text-center">
                                    <?php if ($a['active']): ?>
                                        <span class="badge bg-success">Aktiv</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inaktiv</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= number_format($a['emails_sent']) ?></td>
                                <td class="text-center">
                                    <?php
                                    $h = $health_map[$a['id']] ?? null;
                                    if (!$h || (int)$h['sent'] === 0):
                                    ?>
                                    <span class="text-muted small">—</span>
                                    <?php else:
                                        $open_r   = round((int)$h['opened'] / (int)$h['sent'] * 100, 1);
                                        $bounce_r = round((int)$h['bad']    / (int)$h['sent'] * 100, 1);
                                        // Score: 0-100  (open rate boosts, bounces reduce)
                                        // Scoring: +2 per open-rate %, -5 per bounce/fail-rate % → 0–100 scale
                                        $score    = max(0, min(100, (int)round($open_r * 2 - $bounce_r * 5)));
                                        $badge    = $score >= 50 ? 'success' : ($score >= 20 ? 'warning' : 'danger');
                                        $label    = $score >= 50 ? 'Inbox' : ($score >= 20 ? 'Grauzone' : 'Spam-Risiko');
                                        $icon     = $score >= 50 ? 'envelope-check' : ($score >= 20 ? 'envelope-exclamation' : 'envelope-x');
                                    ?>
                                    <span class="badge bg-<?= $badge ?> d-flex align-items-center gap-1 justify-content-center"
                                          title="Öffnungsrate: <?= $open_r ?>% | Bounce/Fehler: <?= $bounce_r ?>% | Score: <?= $score ?>/100">
                                        <i class="bi bi-<?= $icon ?>"></i> <?= $label ?>
                                    </span>
                                    <div class="text-muted mt-1" style="font-size:.7rem">
                                        Open <?= $open_r ?>% &nbsp;|&nbsp; Bounce <?= $bounce_r ?>%
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="smtp_accounts.php?edit=<?= $a['id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil"></i></a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Account löschen?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="alert alert-info mt-3 small">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>SMTP-Rotation:</strong> Der Massenversand wechselt nach je N E-Mails (einstellbar) automatisch
                    zum nächsten Account und macht eine konfigurierbare Pause, um Spam-Filter zu umgehen.
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
