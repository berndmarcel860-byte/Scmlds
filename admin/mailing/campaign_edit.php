<?php
/**
 * E-Mail-Marketing – Kampagne erstellen / bearbeiten + CSV-Import
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

admin_check();

$msg               = '';
$msg_type          = 'success';
$campaign          = null;
$cid               = (int) ($_GET['id'] ?? 0);
$show_column_mapper = false;
$csv_preview_info  = null;
$val_skipped       = [];   // filled after import validation

if ($cid) {
    $campaign = get_mailing_campaign($cid);
    if (!$campaign) { header('Location: index.php'); exit; }
}

/**
 * Build a human-readable import result message.
 *
 * @param int   $imported  Number of rows successfully inserted.
 * @param array $skipped   Array of ['email'=>…, 'reason'=>…] from filter_rows_by_email_validity().
 * @param int   $valid_count   Count of valid rows.
 * @param int   $invalid_count Count of invalid rows (still imported with flag).
 * @return string  HTML-safe summary string.
 */
function _import_summary_msg(int $imported, array $skipped, int $valid_count = 0, int $invalid_count = 0): string
{
    $parts = ["<strong>$imported</strong> Empfänger importiert"];
    if ($valid_count || $invalid_count) {
        $parts[] = "<span class='text-success'><strong>$valid_count</strong> gültig</span>";
        $parts[] = "<span class='text-danger'><strong>$invalid_count</strong> ungültig</span> (werden nicht versendet)";
    }
    return implode(' · ', $parts) . '.';
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

    // ── Phase 1: CSV uploaded → store in session, show column mapper ─────────────
    if ($action === 'csv_preview' && $cid) {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $tmp_src = $_FILES['csv_file']['tmp_name'];
            // Store raw CSV bytes in session (small files only; warn for large ones)
            $csv_bytes = file_get_contents($tmp_src);
            if ($csv_bytes !== false) {
                $_SESSION['csv_import_data']       = base64_encode($csv_bytes);
                $_SESSION['csv_import_cid']        = $cid;
                $_SESSION['csv_import_validate_mx'] = !empty($_POST['validate_mx']) ? '1' : '0';
                // Get preview for UI
                $fh_prev = fopen($tmp_src, 'r');
                $csv_preview_info = read_csv_preview($fh_prev, 5);
                fclose($fh_prev);
                $show_column_mapper = true;
            } else {
                $msg_type = 'danger';
                $msg = 'Datei konnte nicht gelesen werden.';
            }
        } else {
            $msg_type = 'danger';
            $msg = 'Bitte eine CSV-Datei hochladen.';
        }
    }

    // ── Phase 2: Column mapping submitted → validate + import ────────────────
    if ($action === 'import_csv' && $cid) {
        $imported    = 0;
        $val_skipped = [];   // collected by filter_rows_by_email_validity

        if (!empty($_SESSION['csv_import_data']) && (int)($_SESSION['csv_import_cid'] ?? 0) === $cid) {
            // Recover CSV from session
            $csv_bytes = base64_decode($_SESSION['csv_import_data']);
            $do_mx     = ($_SESSION['csv_import_validate_mx'] ?? '0') === '1';
            $fh = fopen('php://memory', 'r+');
            fwrite($fh, $csv_bytes);
            rewind($fh);

            // Build column map from POST: col_map[0]=email, col_map[1]=name, ...
            $raw_map    = $_POST['col_map']   ?? [];
            $has_header = !empty($_POST['has_header']);
            $delim      = $_POST['csv_delim'] ?? ',';
            $col_map    = [];
            foreach ($raw_map as $idx => $field) {
                $col_map[(int)$idx] = $field;
            }

            $rows = parse_csv_with_column_map($fh, $col_map, $has_header, $delim);
            fclose($fh);
            unset($_SESSION['csv_import_data'], $_SESSION['csv_import_cid'], $_SESSION['csv_import_validate_mx']);

            // ── E-Mail-Validierung ────────────────────────────────────────────
            $result      = filter_rows_by_email_validity($rows, $do_mx);
            $all_rows    = array_merge($result['valid'], $result['invalid']);
            $val_skipped = $result['skipped'];

            $imported = import_mailing_recipients($cid, $all_rows);
            $campaign = get_mailing_campaign($cid);
            $msg      = _import_summary_msg($imported, $val_skipped, count($result['valid']), count($result['invalid']));

        } elseif (!empty(trim($_POST['manual_emails'] ?? ''))) {
            $do_mx = !empty($_POST['validate_mx']);

            // Manual paste: one address per line, optional comma/tab-separated name
            $lines = preg_split('/[\r\n]+/', trim($_POST['manual_emails']));
            $rows  = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') continue;
                $parts    = preg_split('/[,;\t]+/', $line, 3);
                $email    = trim($parts[0]);
                $name     = trim($parts[1] ?? '');
                $platform = trim($parts[2] ?? '');
                if (preg_match('/^(.+?)\s*<([^>]+)>$/', $line, $m)) {
                    $name  = trim($m[1]);
                    $email = trim($m[2]);
                }
                $rows[] = ['email'=>$email,'name'=>$name,'scam_platform'=>$platform];
            }

            // ── E-Mail-Validierung ────────────────────────────────────────────
            $result      = filter_rows_by_email_validity($rows, $do_mx);
            $all_rows    = array_merge($result['valid'], $result['invalid']);
            $val_skipped = $result['skipped'];

            $imported = import_mailing_recipients($cid, $all_rows);
            $campaign = get_mailing_campaign($cid);
            $msg      = _import_summary_msg($imported, $val_skipped, count($result['valid']), count($result['invalid']));
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

    // Reset non-openers → pending (re-send to recipients who were sent to but never opened)
    if ($action === 'restart_non_openers' && $cid) {
        $pdo = db_connect();
        $stmt = $pdo->prepare(
            'UPDATE mailing_recipients
             SET status="pending", sent_at=NULL, error_msg=NULL
             WHERE campaign_id=:cid AND status="sent" AND opened_at IS NULL AND email_validity="valid"'
        );
        $stmt->execute([':cid' => $cid]);
        $n = $stmt->rowCount();
        $msg = "<strong>$n</strong> Nicht-Öffner zurückgesetzt – bereit zum erneuten Versand.";
        $campaign = get_mailing_campaign($cid);
    }

    // Reset failed recipients → pending
    if ($action === 'retry_failed' && $cid) {
        $pdo = db_connect();
        $stmt = $pdo->prepare(
            'UPDATE mailing_recipients
             SET status="pending", sent_at=NULL, error_msg=NULL
             WHERE campaign_id=:cid AND status="failed" AND email_validity="valid"'
        );
        $stmt->execute([':cid' => $cid]);
        $n = $stmt->rowCount();
        $msg = "<strong>$n</strong> fehlgeschlagene Empfänger zurückgesetzt – bereit zum erneuten Versand.";
        $campaign = get_mailing_campaign($cid);
    }
}

if (isset($_GET['created'])) $msg = 'Kampagne erstellt. Jetzt Empfänger importieren.';

$templates = get_mailing_templates();
$stats = $cid ? get_campaign_stats($cid) : null;
$sample_recipients = $cid ? get_mailing_recipients($cid, '', 10, 0, '') : [];
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
            <?= $msg /* trusted HTML from _import_summary_msg() */ ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php if (!empty($val_skipped)): ?>
        <div class="alert alert-warning py-2 px-3">
            <details>
                <summary class="small fw-semibold" style="cursor:pointer">
                    <i class="bi bi-exclamation-triangle me-1"></i><?= count($val_skipped) ?> abgelehnte Adressen anzeigen
                </summary>
                <div class="mt-2" style="max-height:200px;overflow-y:auto">
                    <table class="table table-sm table-borderless mb-0" style="font-size:.8em">
                        <thead class="table-light"><tr><th>E-Mail</th><th>Grund</th></tr></thead>
                        <tbody>
                        <?php
                        $reason_labels = ['invalid_syntax'=>'Ungültige Syntax', 'no_mx'=>'Kein MX-Eintrag'];
                        foreach ($val_skipped as $s):
                        ?>
                        <tr>
                            <td class="font-monospace"><?= htmlspecialchars($s['email']) ?></td>
                            <td class="text-danger"><?= htmlspecialchars($reason_labels[$s['reason']] ?? $s['reason']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </details>
        </div>
        <?php endif; ?>
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
                            <?php $items = [['primary','Gesamt',$stats['total']],['warning','Ausstehend',$stats['pending']],['success','Gesendet',$stats['sent']],['danger','SMTP-Fehler',$stats['failed']],['warning','Bounce',$stats['bounced']],['info','Geöffnet',$stats['opens']]]; ?>
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
                        <?php if (($stats['sent'] ?? 0) > 0): ?>
                        <div class="mt-2 d-flex gap-2">
                            <form method="post" class="flex-fill"
                                  onsubmit="return confirm('Alle gesendeten Nicht-Öffner zurück auf Ausstehend setzen?')">
                                <input type="hidden" name="action" value="restart_non_openers">
                                <button class="btn btn-outline-warning btn-sm w-100" title="Empfänger, die die E-Mail nicht geöffnet haben, erneut versenden">
                                    <i class="bi bi-arrow-repeat me-1"></i>Nicht-Öffner neu senden
                                </button>
                            </form>
                            <?php if (($stats['failed'] ?? 0) > 0): ?>
                            <form method="post" class="flex-fill"
                                  onsubmit="return confirm('Alle fehlgeschlagenen Empfänger zurück auf Ausstehend setzen?')">
                                <input type="hidden" name="action" value="retry_failed">
                                <button class="btn btn-outline-danger btn-sm w-100" title="Fehlgeschlagene Sendeversuche wiederholen">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Fehler wiederholen
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Step 2: Recipient import -->
            <?php if ($cid): ?>
            <div class="col-lg-6">

                <?php if ($show_column_mapper && $csv_preview_info): ?>
                <!-- ── Column Mapper (Phase 2) ─────────────────────────────────────── -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-semibold mb-0"><span class="badge bg-primary me-2">2</span>Spalten zuordnen</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Weise jeder CSV-Spalte das richtige Datenfeld zu. Spalten ohne Zuordnung werden ignoriert.
                        </p>
                        <form method="post">
                            <input type="hidden" name="action"     value="import_csv">
                            <input type="hidden" name="has_header" value="<?= $csv_preview_info['has_header'] ? '1' : '' ?>">
                            <input type="hidden" name="csv_delim"  value="<?= htmlspecialchars($csv_preview_info['delim']) ?>">

                            <?php
                            $col_field_opts = [
                                ''             => '— ignorieren —',
                                'email'        => 'E-Mail *  [email]',
                                'name'         => 'Name (kombiniert)  [name]',
                                'firstname'    => 'Vorname  [→ name]',
                                'lastname'     => 'Nachname  [→ name]',
                                'scam_platform'=> 'Betrugsplattform / Broker  [scam_platform]',
                            ];
                            // Auto-suggest based on header text
                            $auto = [];
                            foreach ($csv_preview_info['header'] as $i => $h) {
                                $hl = mb_strtolower(trim($h));
                                if      (str_contains($hl,'mail'))                                                                  $auto[$i] = 'email';
                                elseif  (str_contains($hl,'vorname')||str_contains($hl,'first'))                                    $auto[$i] = 'firstname';
                                elseif  (str_contains($hl,'nachname')||str_contains($hl,'last'))                                    $auto[$i] = 'lastname';
                                elseif  (str_contains($hl,'name'))                                                                  $auto[$i] = 'name';
                                elseif  (str_contains($hl,'platform')||str_contains($hl,'broker')||str_contains($hl,'scam')||str_contains($hl,'firma')) $auto[$i] = 'scam_platform';
                                else                                                                                                $auto[$i] = '';
                            }
                            ?>

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-3">
                                    <thead class="table-light">
                                        <tr>
                                            <?php foreach ($csv_preview_info['header'] as $i => $h): ?>
                                            <th class="text-center" style="min-width:130px">
                                                <div class="small text-muted mb-1"><?= htmlspecialchars($h) ?></div>
                                                <select name="col_map[<?= $i ?>]" class="form-select form-select-sm">
                                                    <?php foreach ($col_field_opts as $v => $label): ?>
                                                    <option value="<?= $v ?>" <?= ($auto[$i] ?? '') === $v ? 'selected' : '' ?>><?= $label ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($csv_preview_info['preview'] as $row): ?>
                                        <tr>
                                            <?php foreach ($csv_preview_info['header'] as $i => $_): ?>
                                            <td class="small text-truncate" style="max-width:160px" title="<?= htmlspecialchars($row[$i] ?? '') ?>"><?= htmlspecialchars($row[$i] ?? '') ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="alert alert-info py-2 px-3 small mb-3 d-flex align-items-start gap-2">
                                <i class="bi bi-shield-check fs-5 text-info"></i>
                                <div>
                                    <strong>E-Mail-Validierung</strong> läuft automatisch (Syntax-Check).
                                    <?php if (($_SESSION['csv_import_validate_mx'] ?? '0') === '1'): ?>
                                    DNS-MX-Check ist <strong>aktiv</strong> – Adressen ohne gültigen Mailserver werden herausgefiltert.
                                    <?php else: ?>
                                    DNS-MX-Check ist <strong>inaktiv</strong> – nur Syntax wird geprüft.
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload me-1"></i>Importieren
                                </button>
                                <a href="campaign_edit.php?id=<?= $cid ?>" class="btn btn-outline-secondary">Abbrechen</a>
                            </div>
                        </form>
                    </div>
                </div>

                <?php else: ?>
                <!-- ── Normal import form (Phase 1) ──────────────────────────────── -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-semibold mb-0"><span class="badge bg-primary me-2">2</span>Empfänger importieren</h6>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3" id="importTabs">
                            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabCsv">CSV hochladen</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabManual">Manuell eingeben</a></li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="tabCsv">
                                <p class="text-muted small mb-2">
                                    Nach dem Upload siehst du eine Vorschau und kannst jede Spalte dem richtigen Feld zuordnen
                                    (E-Mail, Name, Vorname/Nachname getrennt, <strong>Betrugsplattform</strong>).
                                </p>
                                <form method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="csv_preview">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">CSV-Datei</label>
                                        <input type="file" name="csv_file" class="form-control" accept=".csv,.txt,text/csv,text/plain,application/csv,application/vnd.ms-excel">
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="validate_mx" id="csvValidateMx" value="1">
                                        <label class="form-check-label small" for="csvValidateMx">
                                            <strong>DNS-Validierung (MX-Check)</strong> – Adressen ohne gültigen Mailserver herausfiltern
                                            <span class="text-muted">(reduziert Bounce-Rate)</span>
                                        </label>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-eye me-1"></i>Vorschau &amp; Spalten zuordnen
                                    </button>
                                </form>
                            </div>
                            <div class="tab-pane fade" id="tabManual">
                                <p class="text-muted small">Eine Adresse pro Zeile. Unterstützte Formate:</p>
                                <ul class="text-muted small mb-2">
                                    <li><code>email@example.com</code></li>
                                    <li><code>email@example.com, Max Mustermann</code></li>
                                    <li><code>email@example.com, Max Mustermann, BrokerXYZ</code> <span class="text-primary">(mit Plattform)</span></li>
                                    <li><code>Max Mustermann &lt;email@example.com&gt;</code></li>
                                </ul>
                                <form method="post">
                                    <input type="hidden" name="action" value="import_csv">
                                    <div class="mb-3">
                                        <textarea name="manual_emails" class="form-control font-monospace" rows="8"
                                                  placeholder="max@mustermann.de,Max Mustermann,CryptoScamBroker&#10;anna@beispiel.de,Anna Schmidt&#10;Hans Müller <hans@mail.de>"></textarea>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="validate_mx" id="manValidateMx" value="1">
                                        <label class="form-check-label small" for="manValidateMx">
                                            <strong>DNS-Validierung (MX-Check)</strong>
                                            <span class="text-muted">(reduziert Bounce-Rate)</span>
                                        </label>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-upload me-1"></i>Importieren
                                    </button>
                                </form>
                            </div>
                        </div>

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
                <?php endif; ?>

                <!-- Sample recipients -->
                <?php if (!empty($sample_recipients)): ?>
                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                        <h6 class="fw-semibold mb-0 small">Empfänger-Vorschau (10 von <?= number_format($stats['total'] ?? 0) ?>)</h6>
                        <div class="d-flex gap-2">
                            <a href="leads.php?campaign_id=<?= $cid ?>" class="btn btn-link btn-sm p-0 text-primary">Alle Leads</a>
                            <a href="stats.php?id=<?= $cid ?>#recipients" class="btn btn-link btn-sm p-0">Statistiken</a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light"><tr><th>E-Mail</th><th>Name</th><th>Plattform</th><th>Gültigkeit</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($sample_recipients as $r):
                                $validity = $r['email_validity'] ?? 'valid';
                                $vc = $validity === 'valid' ? 'success' : 'danger';
                                $vl = $validity === 'valid' ? 'Gültig' : 'Ungültig';
                            ?>
                            <tr class="<?= $validity === 'invalid' ? 'text-muted' : '' ?>">
                                <td class="small"><?= htmlspecialchars($r['email']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($r['name']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($r['scam_platform'] ?? '') ?></td>
                                <td><span class="badge bg-<?= $vc ?>-subtle text-<?= $vc ?>" style="font-size:.7em"><?= $vl ?></span></td>
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
