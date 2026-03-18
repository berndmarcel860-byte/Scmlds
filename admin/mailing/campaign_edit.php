<?php
/**
 * E-Mail-Marketing – Kampagne erstellen / bearbeiten + CSV-Import
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

admin_check();

$msg      = '';
$msg_type = 'success';
$campaign = null;
$cid      = (int) ($_GET['id'] ?? 0);

if ($cid) {
    $campaign = get_mailing_campaign($cid);
    if (!$campaign) { header('Location: index.php'); exit; }
}

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Save campaign meta
    if ($action === 'save_campaign') {
        $name = trim($_POST['name'] ?? '');
        $tid  = (int) ($_POST['template_id'] ?? 0);
        if (empty($name) || !$tid) {
            $msg_type = 'danger';
            $msg = 'Name und Template sind Pflichtfelder.';
        } elseif ($cid) {
            update_mailing_campaign($cid, ['name'=>$name,'template_id'=>$tid]);
            $msg = 'Kampagne gespeichert.';
            $campaign = get_mailing_campaign($cid); // refresh
        } else {
            $new_id = create_mailing_campaign($name, $tid);
            if ($new_id) {
                log_activity('mailing_campaign_created', "Campaign created: $name");
                header('Location: campaign_edit.php?id=' . $new_id . '&created=1');
                exit;
            }
        }
    }

    // CSV import
    if ($action === 'import_csv' && $cid) {
        $imported = 0;
        $errors   = [];

        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['csv_file']['tmp_name'];
            $rows = [];
            if (($fh = fopen($tmp, 'r')) !== false) {
                // Detect and skip header row
                $first = fgetcsv($fh, 1000, ',') ?: fgetcsv($fh, 1000, ';');
                $is_header = $first && preg_match('/^(e-?mail|email|mail|name|vorname|erste)/i', $first[0]);
                if (!$is_header && $first) {
                    $rows[] = $first; // first row is data
                }
                while (($row = fgetcsv($fh, 1000, ',')) !== false) {
                    $rows[] = $row;
                }
                // Also try semicolon delimiter if only 1 column found
                if (count($rows) < 2 || (count($rows[0] ?? []) < 2)) {
                    rewind($fh);
                    $rows = [];
                    while (($row = fgetcsv($fh, 1000, ';')) !== false) {
                        $rows[] = $row;
                    }
                    if (!$is_header && !empty($rows)) array_shift($rows);
                }
                fclose($fh);
            }
            $imported = import_mailing_recipients($cid, $rows);
            $msg = "$imported Empfänger importiert.";
            $campaign = get_mailing_campaign($cid);
        } elseif (!empty(trim($_POST['manual_emails'] ?? ''))) {
            // Manual paste
            $lines = preg_split('/[\r\n,;]+/', trim($_POST['manual_emails']));
            $rows  = [];
            foreach ($lines as $line) {
                $parts = preg_split('/[\t;,]/', trim($line), 2);
                $rows[] = [$parts[0], $parts[1] ?? ''];
            }
            $imported = import_mailing_recipients($cid, $rows);
            $msg = "$imported Empfänger importiert.";
            $campaign = get_mailing_campaign($cid);
        } else {
            $msg_type = 'danger';
            $msg = 'Bitte eine CSV-Datei hochladen oder E-Mail-Adressen einfügen.';
        }
    }

    // Clear recipients
    if ($action === 'clear_recipients' && $cid) {
        db_connect()->prepare('DELETE FROM mailing_recipients WHERE campaign_id=:cid AND status="pending"')->execute([':cid'=>$cid]);
        db_connect()->prepare('UPDATE mailing_campaigns SET total=(SELECT COUNT(*) FROM mailing_recipients r WHERE r.campaign_id=:cid) WHERE id=:cid2')->execute([':cid'=>$cid,':cid2'=>$cid]);
        $msg = 'Ausstehende Empfänger gelöscht.';
        $campaign = get_mailing_campaign($cid);
    }
}

if (isset($_GET['created'])) $msg = 'Kampagne erstellt. Jetzt Empfänger importieren.';

$templates = get_mailing_templates();
$stats = $cid ? get_campaign_stats($cid) : null;
$sample_recipients = $cid ? get_mailing_recipients($cid, '', 10, 0) : [];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $campaign ? 'Kampagne bearbeiten' : 'Neue Kampagne' ?> – Marketing</title>
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
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-megaphone me-2 text-primary"></i>
                    <?= $campaign ? 'Kampagne: ' . htmlspecialchars($campaign['name']) : 'Neue Kampagne' ?>
                </h4>
                <?php if ($campaign): ?>
                <nav aria-label="breadcrumb" class="mt-1">
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="index.php">Kampagnen</a></li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($campaign['name']) ?></li>
                    </ol>
                </nav>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <?php if ($campaign && $campaign['status'] === 'draft'): ?>
                <a href="index.php?action=start&id=<?= $cid ?>" class="btn btn-success">
                    <i class="bi bi-play-fill me-1"></i>Kampagne starten
                </a>
                <?php elseif ($campaign && $campaign['status'] === 'running'): ?>
                <a href="send.php?id=<?= $cid ?>" class="btn btn-success">
                    <i class="bi bi-play-fill me-1"></i>Versand-Panel öffnen
                </a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Übersicht</a>
            </div>
        </div>

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show">
            <?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Step 1: Campaign details -->
            <div class="col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-semibold mb-0"><span class="badge bg-primary me-2">1</span>Kampagnen-Details</h6>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="action" value="save_campaign">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Kampagnen-Name *</label>
                                <input type="text" name="name" class="form-control" required
                                       value="<?= htmlspecialchars($campaign['name'] ?? '') ?>"
                                       placeholder="z.B. Newsletter März 2026">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">E-Mail-Template *</label>
                                <select name="template_id" class="form-select" required>
                                    <option value="">– Template wählen –</option>
                                    <?php foreach ($templates as $t): ?>
                                    <option value="<?= $t['id'] ?>" <?= ($campaign['template_id'] ?? 0) == $t['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['name']) ?> — <?= htmlspecialchars(substr($t['subject'],0,50)) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($templates)): ?>
                                <div class="form-text text-warning"><i class="bi bi-exclamation-triangle me-1"></i>
                                    Noch keine Templates. <a href="templates.php">Jetzt erstellen</a>.</div>
                                <?php endif; ?>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i><?= $cid ? 'Speichern' : 'Kampagne erstellen' ?>
                            </button>
                        </form>
                    </div>
                </div>

                <?php if ($cid && $stats): ?>
                <!-- Stats summary -->
                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-semibold mb-0"><i class="bi bi-bar-chart me-1"></i>Statistiken</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 text-center">
                            <?php $items = [['primary','Gesamt',$stats['total']],['warning','Ausstehend',$stats['pending']],['success','Gesendet',$stats['sent']],['danger','Fehler',$stats['failed']],['info','Geöffnet',$stats['opens']]]; ?>
                            <?php foreach ($items as [$color,$label,$val]): ?>
                            <div class="col">
                                <div class="border rounded-3 p-2">
                                    <div class="fw-bold text-<?= $color ?> fs-5"><?= number_format($val) ?></div>
                                    <div class="text-muted" style="font-size:.75em"><?= $label ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($stats['total'] > 0): ?>
                        <div class="progress mt-3" style="height:8px">
                            <div class="progress-bar bg-success" style="width:<?= round($stats['sent']/$stats['total']*100) ?>%"></div>
                            <div class="progress-bar bg-danger" style="width:<?= round($stats['failed']/$stats['total']*100) ?>%"></div>
                        </div>
                        <div class="text-muted small mt-1"><?= round($stats['sent']/$stats['total']*100) ?>% versendet</div>
                        <?php endif; ?>
                        <a href="stats.php?id=<?= $cid ?>" class="btn btn-outline-info btn-sm mt-3 w-100">
                            <i class="bi bi-bar-chart me-1"></i>Detaillierte Statistiken
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Step 2: Recipient import -->
            <?php if ($cid): ?>
            <div class="col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-semibold mb-0"><span class="badge bg-primary me-2">2</span>Empfänger importieren</h6>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3" id="importTabs">
                            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabCsv">CSV hochladen</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabManual">Manuell eingeben</a></li>
                        </ul>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="import_csv">
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="tabCsv">
                                    <p class="text-muted small">CSV-Format: <code>email,name</code> (Komma oder Semikolon getrennt). Erste Zeile kann Header sein.</p>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">CSV-Datei</label>
                                        <input type="file" name="csv_file" class="form-control" accept=".csv,.txt">
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="tabManual">
                                    <p class="text-muted small">Eine Adresse pro Zeile: <code>email@example.com,Name</code> oder nur E-Mail.</p>
                                    <div class="mb-3">
                                        <textarea name="manual_emails" class="form-control font-monospace" rows="8"
                                                  placeholder="max@mustermann.de,Max Mustermann&#10;anna@beispiel.de"></textarea>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload me-1"></i>Importieren
                            </button>
                        </form>

                        <?php if ($stats && $stats['total'] > 0): ?>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small"><?= number_format($stats['total']) ?> Empfänger geladen (<?= number_format($stats['pending']) ?> ausstehend)</span>
                            <form method="post" class="d-inline" onsubmit="return confirm('Ausstehende Empfänger löschen?')">
                                <input type="hidden" name="action" value="clear_recipients">
                                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Pending löschen</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sample recipients -->
                <?php if (!empty($sample_recipients)): ?>
                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                        <h6 class="fw-semibold mb-0 small">Empfänger-Vorschau (10 von <?= number_format($stats['total'] ?? 0) ?>)</h6>
                        <a href="stats.php?id=<?= $cid ?>#recipients" class="btn btn-link btn-sm p-0">Alle anzeigen</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light"><tr><th>E-Mail</th><th>Name</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($sample_recipients as $r): ?>
                            <tr>
                                <td class="small"><?= htmlspecialchars($r['email']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($r['name']) ?></td>
                                <td><span class="badge bg-<?= ['pending'=>'secondary','sent'=>'success','failed'=>'danger'][$r['status']] ?? 'secondary' ?>" style="font-size:.7em"><?= $r['status'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
