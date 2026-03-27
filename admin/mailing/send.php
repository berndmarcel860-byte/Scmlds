<?php
/**
 * E-Mail-Marketing – Versand-Panel (AJAX-gesteuert)
 * Shows a live progress bar while the AJAX batcher processes the queue.
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

admin_check();

$cid = (int) ($_GET['id'] ?? 0);
if (!$cid) { header('Location: index.php'); exit; }

$campaign = get_mailing_campaign($cid);
if (!$campaign) { header('Location: index.php'); exit; }

$stats    = get_campaign_stats($cid);
$settings = get_all_mailing_settings();
$accounts = get_mailing_smtp_accounts(true);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Versand: <?= htmlspecialchars($campaign['name']) ?> – Marketing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <style>
        #sendLog { max-height: 320px; overflow-y: auto; font-size: .82rem; }
        .log-ok   { color: #198754; }
        .log-err  { color: #dc3545; }
        .log-info { color: #0d6efd; }
        .log-warn { color: #fd7e14; }
        #bigProgress { height: 28px; font-size: .9rem; transition: width .4s; }
    </style>
</head>
<body class="admin-body">

<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<div class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content p-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-send me-2 text-success"></i>
                    Versand: <?= htmlspecialchars($campaign['name']) ?>
                </h4>
                <p class="text-muted small mb-0">
                    Status: <strong><?= $campaign['status'] ?></strong> &nbsp;|&nbsp;
                    Template: <strong><?= htmlspecialchars($campaign['template_name'] ?? '—') ?></strong>
                </p>
            </div>
            <a href="campaign_edit.php?id=<?= $cid ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Kampagne
            </a>
        </div>

        <?php if (empty($accounts)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-x-circle me-1"></i>
            Keine aktiven SMTP-Accounts. <a href="smtp_accounts.php">Jetzt konfigurieren</a>.
        </div>
        <?php elseif ($stats['pending'] === 0 && $campaign['status'] !== 'running'): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Keine ausstehenden Empfänger. <a href="campaign_edit.php?id=<?= $cid ?>">Empfänger importieren</a>.
        </div>
        <?php else: ?>

        <!-- Main control panel -->
        <div class="row g-4">
            <div class="col-lg-7">
                <!-- Progress card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-semibold mb-0"><i class="bi bi-activity me-1"></i>Versand-Fortschritt</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-3 text-center">
                            <div class="col-3"><div class="border rounded-3 p-2"><div class="fw-bold fs-5" id="cntTotal"><?= $stats['total'] ?></div><div class="text-muted small">Gesamt</div></div></div>
                            <div class="col-3"><div class="border rounded-3 p-2"><div class="fw-bold fs-5 text-warning" id="cntPending"><?= $stats['pending'] ?></div><div class="text-muted small">Ausstehend</div></div></div>
                            <div class="col-3"><div class="border rounded-3 p-2"><div class="fw-bold fs-5 text-success" id="cntSent"><?= $stats['sent'] ?></div><div class="text-muted small">Gesendet</div></div></div>
                            <div class="col-3"><div class="border rounded-3 p-2"><div class="fw-bold fs-5 text-danger" id="cntFailed"><?= $stats['failed'] ?></div><div class="text-muted small">Fehler</div></div></div>
                        </div>

                        <div class="progress mb-2" style="height:28px">
                            <?php $pct = $stats['total'] > 0 ? round($stats['sent']/$stats['total']*100) : 0; ?>
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated fw-semibold"
                                 id="bigProgress" role="progressbar" style="width:<?= $pct ?>%">
                                <span id="pctLabel"><?= $pct ?>%</span>
                            </div>
                        </div>
                        <p class="text-muted small mb-3" id="statusLine">
                            <?= $campaign['status'] === 'running' ? 'Bereit zum Versand.' : ucfirst($campaign['status']) ?>
                        </p>

                        <div class="d-flex gap-2">
                            <button id="btnStart" class="btn btn-success" <?= $campaign['status'] !== 'running' ? 'disabled' : '' ?> onclick="startSending()">
                                <i class="bi bi-play-fill me-1"></i>Versand starten
                            </button>
                            <button id="btnStop" class="btn btn-warning" style="display:none" onclick="stopSending()">
                                <i class="bi bi-pause-fill me-1"></i>Pausieren
                            </button>
                            <a href="stats.php?id=<?= $cid ?>" class="btn btn-outline-info ms-auto">
                                <i class="bi bi-bar-chart me-1"></i>Statistiken
                            </a>
                        </div>

                        <?php if ($campaign['status'] !== 'running'): ?>
                        <div class="alert alert-info mt-3 small">
                            <i class="bi bi-info-circle me-1"></i>
                            Kampagne muss den Status "running" haben.
                            <a href="../index.php?action=start&id=<?= $cid ?>">Jetzt starten</a>.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Live log -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                        <h6 class="fw-semibold mb-0 small"><i class="bi bi-terminal me-1"></i>Versand-Log</h6>
                        <button class="btn btn-link btn-sm p-0" onclick="document.getElementById('sendLog').innerHTML=''">Leeren</button>
                    </div>
                    <div class="card-body p-0">
                        <pre id="sendLog" class="bg-dark text-light p-3 mb-0 rounded-bottom font-monospace">Bereit...<br></pre>
                    </div>
                </div>
            </div>

            <!-- Settings summary -->
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-semibold mb-0"><i class="bi bi-gear me-1"></i>Versand-Parameter</h6>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted small">E-Mails pro SMTP-Account</span>
                            <strong><?= htmlspecialchars($settings['emails_per_account'] ?? '5') ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted small">Pause zwischen E-Mails</span>
                            <strong><?= number_format((int)($settings['pause_between_emails_ms'] ?? 3000) / 1000, 1) ?> s</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted small">Pause nach Account-Wechsel</span>
                            <strong><?= number_format((int)($settings['pause_between_accounts_ms'] ?? 15000) / 1000, 0) ?> s</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted small">Aktive SMTP-Accounts</span>
                            <strong><?= count($accounts) ?></strong>
                        </li>
                    </ul>
                    <div class="card-footer bg-white">
                        <a href="settings.php" class="btn btn-link btn-sm p-0"><i class="bi bi-sliders me-1"></i>Parameter anpassen</a>
                    </div>
                </div>

                <!-- SMTP accounts status -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-semibold mb-0"><i class="bi bi-server me-1"></i>SMTP-Account-Pool</h6>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($accounts as $a): ?>
                        <li class="list-group-item d-flex align-items-center gap-2 py-2"
                            id="smtp-<?= $a['id'] ?>">
                            <div class="rounded-circle bg-secondary" style="width:8px;height:8px"
                                 id="smtpDot-<?= $a['id'] ?>"></div>
                            <div class="flex-grow-1">
                                <div class="small fw-semibold"><?= htmlspecialchars($a['label'] ?: $a['from_email']) ?></div>
                                <div class="text-muted" style="font-size:.75em"><?= htmlspecialchars($a['from_email']) ?></div>
                            </div>
                            <span class="badge bg-secondary small" id="smtpCount-<?= $a['id'] ?>">
                                <?= number_format($a['emails_sent']) ?> gesamt
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const CAMPAIGN_ID  = <?= $cid ?>;
const TOTAL        = <?= $stats['total'] ?>;
const PAUSE_EMAIL  = <?= (int)($settings['pause_between_emails_ms'] ?? 3000) ?>;
const PAUSE_ACCT   = <?= (int)($settings['pause_between_accounts_ms'] ?? 15000) ?>;

let running  = false;
let stopping = false;
let _cdSeq   = 0;   // monotonic counter for countdown line IDs

function log(msg, cls = '') {
    const el  = document.getElementById('sendLog');
    const now = new Date().toLocaleTimeString('de');
    el.innerHTML += `<span class="${cls}">[${now}] ${msg}</span>\n`;
    el.scrollTop = el.scrollHeight;
}

function updateStats(data) {
    document.getElementById('cntPending').textContent = data.pending;
    document.getElementById('cntSent').textContent    = data.sent;
    document.getElementById('cntFailed').textContent  = data.failed;
    const pct = TOTAL > 0 ? Math.round(data.sent / TOTAL * 100) : 0;
    const bar = document.getElementById('bigProgress');
    bar.style.width = pct + '%';
    document.getElementById('pctLabel').textContent = pct + '%';
    document.getElementById('statusLine').textContent = data.status_text || '';
    // Update SMTP dot
    if (data.active_smtp_id) {
        document.querySelectorAll('[id^="smtpDot-"]').forEach(d => { d.className = 'rounded-circle bg-secondary'; d.style.width='8px'; d.style.height='8px'; });
        const dot = document.getElementById('smtpDot-' + data.active_smtp_id);
        if (dot) { dot.className = 'rounded-circle bg-success'; dot.style.width='10px'; dot.style.height='10px'; }
    }
}

async function startSending() {
    if (running) return;
    running  = true;
    stopping = false;
    document.getElementById('btnStart').style.display = 'none';
    document.getElementById('btnStop').style.display  = '';
    log('Versand gestartet.', 'log-info');

    while (running && !stopping) {
        try {
            const res  = await fetch('../ajax/mailing_send_batch.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'campaign_id=' + CAMPAIGN_ID
            });
            const data = await res.json();

            if (data.error) {
                log('Fehler: ' + data.error, 'log-err');
                break;
            }

            updateStats(data);

            if (data.sent_now > 0) {
                log(`Batch: ${data.sent_now} gesendet via SMTP #${data.active_smtp_id} (${data.account_label})`, 'log-ok');
            }
            if (data.failed_now > 0) {
                log(`${data.failed_now} fehlgeschlagen.`, 'log-err');
            }
            if (data.account_rotated) {
                await countdown(PAUSE_ACCT, 'SMTP-Account gewechselt. Pause', 'log-warn');
                // Auto-sync warmup after account rotation
                syncWarmup();
                continue;
            }
            if (data.done) {
                log('✅ Alle E-Mails versendet! Kampagne abgeschlossen.', 'log-info');
                syncWarmup();
                break;
            }
            if (data.no_pending) {
                log('ℹ️ Keine ausstehenden Empfänger.', 'log-info');
                break;
            }

            // Countdown pause between emails
            await countdown(PAUSE_EMAIL, 'Nächste E-Mail in', 'log-info');
        } catch(e) {
            log('⚠️ Netzwerkfehler: ' + e.message, 'log-err');
            await countdown(5000, 'Wiederholung in', 'log-warn');
        }
    }

    running = false;
    document.getElementById('btnStart').style.display = '';
    document.getElementById('btnStop').style.display  = 'none';
    log('Versand beendet.', 'log-info');
}

function stopSending() {
    stopping = true;
    log('⏹️ Stopp angefordert …', 'log-warn');
}

/**
 * Countdown timer that writes ticking seconds into the last log line.
 * @param {number} ms   - total pause in milliseconds
 * @param {string} label - prefix label
 * @param {string} cls  - CSS class for the log line
 */
async function countdown(ms, label, cls = 'log-info') {
    const el       = document.getElementById('sendLog');
    const totalSec = Math.ceil(ms / 1000);
    const now      = new Date().toLocaleTimeString('de');
    // Append a new line we will overwrite in-place
    const lineId   = 'cdline-' + (++_cdSeq);
    el.innerHTML  += `<span id="${lineId}" class="${cls}">[${now}] ${label}: ${totalSec}s</span>\n`;
    el.scrollTop   = el.scrollHeight;

    for (let s = totalSec - 1; s >= 0 && running && !stopping; s--) {
        await sleep(1000);
        const line = document.getElementById(lineId);
        if (line) {
            line.textContent = `[${now}] ${label}: ${s}s`;
        }
    }
}

/**
 * Auto-sync today's warmup log entry for the active SMTP account.
 * Fire-and-forget — errors are non-fatal.
 */
function syncWarmup() {
    fetch('../ajax/mailing_warmup_autosync.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'campaign_id=' + CAMPAIGN_ID
    }).then(r => r.json()).then(d => {
        if (d && d.synced) {
            log(`🔥 IP-Warmup: ${d.synced} Account(s) für heute synchronisiert.`, 'log-info');
        }
    }).catch(() => {});
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}
</script>
</body>
</html>
