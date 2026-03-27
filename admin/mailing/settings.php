<?php
/**
 * E-Mail-Marketing – Versand-Einstellungen
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

admin_check();

$msg      = '';
$msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['emails_per_account','pause_between_emails_ms','pause_between_accounts_ms','max_daily_per_account','unsubscribe_url','track_opens'];
    $data = [];
    foreach ($keys as $k) {
        $data[$k] = trim($_POST[$k] ?? '');
    }
    $data['track_opens'] = isset($_POST['track_opens']) ? '1' : '0';

    // Validate numeric fields
    foreach (['emails_per_account','pause_between_emails_ms','pause_between_accounts_ms','max_daily_per_account'] as $k) {
        $data[$k] = (string) max(0, (int) $data[$k]);
    }

    if (save_mailing_settings($data)) {
        log_activity('mailing_settings_saved', 'Mailing settings updated');
        $msg = 'Einstellungen gespeichert.';
    } else {
        $msg_type = 'danger';
        $msg = 'Fehler beim Speichern.';
    }
}

$s = get_all_mailing_settings();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mailing-Einstellungen – Admin</title>
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
                <h4 class="fw-bold mb-0"><i class="bi bi-sliders me-2 text-primary"></i>Mailing-Einstellungen</h4>
                <p class="text-muted small mb-0">Versand-Parameter und Anti-Spam-Throttling</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Übersicht</a>
        </div>

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show">
            <?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-6">
                <form method="post">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-semibold mb-0"><i class="bi bi-speedometer me-1"></i>Throttling & Rotation</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <label class="form-label fw-semibold">E-Mails pro SMTP-Account (dann rotieren)</label>
                                <input type="number" name="emails_per_account" class="form-control"
                                       min="1" max="500" value="<?= htmlspecialchars($s['emails_per_account'] ?? '5') ?>">
                                <div class="form-text">Nach dieser Anzahl wird automatisch zum nächsten SMTP-Account gewechselt. Empfohlen: 3–10.</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Pause zwischen E-Mails (Millisekunden)</label>
                                <div class="input-group">
                                    <input type="number" name="pause_between_emails_ms" class="form-control"
                                           min="500" max="60000" value="<?= htmlspecialchars($s['pause_between_emails_ms'] ?? '3000') ?>">
                                    <span class="input-group-text" id="emailPauseSec">= <?= number_format((int)($s['pause_between_emails_ms'] ?? 3000)/1000,1) ?>s</span>
                                </div>
                                <div class="form-text">Pause zwischen je zwei gesendeten E-Mails. Empfohlen: 2000–5000 ms.</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Pause beim SMTP-Account-Wechsel (Millisekunden)</label>
                                <div class="input-group">
                                    <input type="number" name="pause_between_accounts_ms" class="form-control"
                                           min="1000" max="300000" value="<?= htmlspecialchars($s['pause_between_accounts_ms'] ?? '15000') ?>">
                                    <span class="input-group-text" id="acctPauseSec">= <?= number_format((int)($s['pause_between_accounts_ms'] ?? 15000)/1000,0) ?>s</span>
                                </div>
                                <div class="form-text">Pause nachdem ein Account sein Batch-Limit erreicht hat. Empfohlen: 10–30 s.</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Max. E-Mails pro Account pro Tag</label>
                                <input type="number" name="max_daily_per_account" class="form-control"
                                       min="1" max="10000" value="<?= htmlspecialchars($s['max_daily_per_account'] ?? '200') ?>">
                                <div class="form-text">Tages-Limit pro SMTP-Account. Beim IP-Warming: 50–200/Tag empfohlen.</div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 mt-4">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-semibold mb-0"><i class="bi bi-link-45deg me-1"></i>Tracking & Abmeldung</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Abmelde-URL (leer = automatisch)</label>
                                <input type="url" name="unsubscribe_url" class="form-control"
                                       value="<?= htmlspecialchars($s['unsubscribe_url'] ?? '') ?>"
                                       placeholder="https://ihre-domain.de/unsubscribe.php">
                                <div class="form-text">Wird als <code>{{unsubscribe_url}}</code> im Template eingefügt.</div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" name="track_opens" class="form-check-input" id="chkTrack"
                                       value="1" <?= ($s['track_opens'] ?? '0') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="chkTrack">Öffnungs-Tracking (1×1 Pixel) aktivieren</label>
                                <div class="form-text">Fügt ein unsichtbares Tracking-Pixel ein um Öffnungen zu messen.</div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-4 w-100">
                        <i class="bi bi-save me-1"></i>Einstellungen speichern
                    </button>
                </form>
            </div>

            <!-- Best practices sidebar -->
            <div class="col-lg-6">
                <div class="card border-0 bg-success-subtle">
                    <div class="card-body">
                        <h6 class="fw-semibold"><i class="bi bi-check-circle me-1 text-success"></i>Deliverability Best Practices</h6>
                        <ul class="small mb-0">
                            <li class="mb-2"><strong>IP-Warming:</strong> Beginnen Sie mit 50 E-Mails/Tag und steigern täglich um 50%, bis Sie das Ziel-Volumen erreichen.</li>
                            <li class="mb-2"><strong>SPF/DKIM/DMARC:</strong> Konfigurieren Sie alle drei DNS-Einträge für jede Absender-Domain.</li>
                            <li class="mb-2"><strong>Authentizität:</strong> Verwenden Sie Ihren echten Firmennamen als Absender – keine generischen Adressen.</li>
                            <li class="mb-2"><strong>Inhalt:</strong> Kein ALL CAPS, keine übermäßigen Ausrufezeichen, kein "Klicken Sie jetzt".</li>
                            <li class="mb-2"><strong>Liste:</strong> Nur Double-Opt-In-Empfänger verwenden. Regelmäßig bereinigen.</li>
                            <li class="mb-2"><strong>Abmeldung:</strong> Immer einen klaren Abmelde-Link einfügen (gesetzliche Pflicht in DE/EU).</li>
                            <li class="mb-2"><strong>Textversion:</strong> Immer eine Nur-Text-Version mitschicken (verbessert Spam-Score).</li>
                            <li><strong>Bounce-Management:</strong> Hard Bounces sofort, Soft Bounces nach 3 Versuchen entfernen.</li>
                        </ul>
                    </div>
                </div>

                <div class="card border-0 mt-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-semibold mb-0"><i class="bi bi-calculator me-1"></i>Durchsatz-Kalkulator</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Basierend auf aktuellen Einstellungen:</p>
                        <?php
                        $epa  = max(1, (int)($s['emails_per_account'] ?? 5));
                        $pe   = (int)($s['pause_between_emails_ms'] ?? 3000);
                        $pa   = (int)($s['pause_between_accounts_ms'] ?? 15000);
                        $acct = count(get_mailing_smtp_accounts(true));
                        $max_d = (int)($s['max_daily_per_account'] ?? 200);

                        // Time per full rotation (ms)
                        $rotation_time_ms = ($epa * $pe) + $pa;
                        $emails_per_hour  = $acct > 0 ? round(3600000 / $rotation_time_ms * $epa * $acct) : 0;
                        $emails_per_day   = min($emails_per_hour * 8, $max_d * $acct); // 8h working day
                        ?>
                        <table class="table table-sm table-bordered mb-0">
                            <tr><td class="text-muted small">Aktive SMTP-Accounts</td><td class="fw-semibold"><?= $acct ?></td></tr>
                            <tr><td class="text-muted small">E-Mails pro Account</td><td class="fw-semibold"><?= $epa ?></td></tr>
                            <tr><td class="text-muted small">Schätzzahl/Stunde</td><td class="fw-semibold text-success"><?= number_format($emails_per_hour) ?></td></tr>
                            <tr><td class="text-muted small">Schätzzahl/Tag (8h, max. Limit)</td><td class="fw-semibold text-primary"><?= number_format($emails_per_day) ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Live update pause display
document.querySelector('[name="pause_between_emails_ms"]').addEventListener('input', function() {
    document.getElementById('emailPauseSec').textContent = '= ' + (parseInt(this.value)||0)/1000 + 's';
});
document.querySelector('[name="pause_between_accounts_ms"]').addEventListener('input', function() {
    document.getElementById('acctPauseSec').textContent = '= ' + Math.round((parseInt(this.value)||0)/1000) + 's';
});
</script>
</body>
</html>
