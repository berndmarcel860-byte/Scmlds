<?php
/**
 * E-Mail-Marketing – E-Mail-Templates
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

admin_check();

$msg      = '';
$msg_type = 'success';
$edit     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $d  = [
            'name'      => trim($_POST['name']      ?? ''),
            'subject'   => trim($_POST['subject']   ?? ''),
            'body_html' => $_POST['body_html']      ?? '',
            'body_text' => trim($_POST['body_text'] ?? ''),
        ];
        if (empty($d['name']) || empty($d['subject'])) {
            $msg_type = 'danger';
            $msg = 'Name und Betreff sind Pflichtfelder.';
        } else {
            $result = save_mailing_template($d, $id ?: null);
            if ($result) {
                log_activity('mailing_template_saved', 'Template saved: ' . $d['name']);
                $msg = 'Template gespeichert.';
                if (!$id) { header('Location: templates.php?edit=' . $result . '&saved=1'); exit; }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) { delete_mailing_template($id); $msg = 'Template gelöscht.'; }
    }
}

if (isset($_GET['saved'])) { $msg = 'Template gespeichert.'; }
if (isset($_GET['edit'])) { $edit = get_mailing_template((int) $_GET['edit']); }

$templates = get_mailing_templates();

// Default spam-safe template HTML
$default_html = <<<'HTML'
<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  body{font-family:Arial,Helvetica,sans-serif;background:#f4f4f4;margin:0;padding:0}
  .wrap{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)}
  .header{background:#1a3c5e;color:#fff;padding:30px 40px;text-align:center}
  .header h1{margin:0;font-size:22px;font-weight:700}
  .body{padding:30px 40px;color:#333;line-height:1.7}
  .body h2{color:#1a3c5e;font-size:18px}
  .cta{display:inline-block;background:#e8a020;color:#fff!important;padding:12px 28px;border-radius:5px;text-decoration:none;font-weight:700;margin:20px 0}
  .footer{background:#f8f8f8;padding:20px 40px;font-size:12px;color:#888;text-align:center;border-top:1px solid #e5e5e5}
  .footer a{color:#888}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>{{company_name}}</h1>
  </div>
  <div class="body">
    <h2>Sehr geehrte/r {{name}},</h2>
    <p>Schreiben Sie hier Ihren Nachrichtentext. Achten Sie auf eine professionelle Sprache und vermeiden Sie typische Spam-Trigger-Wörter.</p>
    <p>Erklären Sie klar den Mehrwert Ihres Angebots für den Empfänger.</p>
    <p style="text-align:center">
      <a href="{{site_url}}" class="cta">Jetzt informieren</a>
    </p>
    <p>Mit freundlichen Grüßen,<br><strong>{{sender_name}}</strong><br>{{company_name}}</p>
  </div>
  <div class="footer">
    Sie erhalten diese E-Mail, weil Sie sich für unseren Dienst interessiert haben.<br>
    <a href="{{unsubscribe_url}}">Abmelden</a> | <a href="{{site_url}}">{{site_url}}</a>
    {{open_tracker}}
  </div>
</div>
</body>
</html>
HTML;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Mail-Templates – Marketing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <style>
        .template-vars code { background:#f0f4ff; padding:1px 5px; border-radius:3px; font-size:.8em; }
        #previewFrame { border:1px solid #dee2e6; border-radius:6px; background:#fff; }
    </style>
</head>
<body class="admin-body">

<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<div class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content p-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0"><i class="bi bi-file-earmark-richtext me-2 text-primary"></i>E-Mail-Templates</h4>
                <p class="text-muted small mb-0">Professionelle HTML-Templates für Kampagnen</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Übersicht</a>
        </div>

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show">
            <?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Editor -->
            <div class="col-xl-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-semibold mb-0"><?= $edit ? 'Template bearbeiten' : 'Neues Template erstellen' ?></h6>
                    </div>
                    <div class="card-body">
                        <form method="post" id="templateForm">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="id" value="<?= $edit ? (int)$edit['id'] : 0 ?>">

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Template-Name *</label>
                                    <input type="text" name="name" class="form-control" required
                                           value="<?= htmlspecialchars($edit['name'] ?? '') ?>"
                                           placeholder="z.B. Willkommens-E-Mail">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Betreff *</label>
                                    <input type="text" name="subject" class="form-control" required
                                           value="<?= htmlspecialchars($edit['subject'] ?? '') ?>"
                                           placeholder="Betreff der E-Mail">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold d-flex justify-content-between">
                                    <span>HTML-Inhalt</span>
                                    <button type="button" class="btn btn-link btn-sm p-0" onclick="loadDefault()">Standard-Template laden</button>
                                </label>
                                <textarea name="body_html" id="bodyHtml" class="form-control font-monospace"
                                          rows="18" style="font-size:12px"><?= htmlspecialchars($edit['body_html'] ?? $default_html) ?></textarea>
                                <div class="form-text">Verfügbare Variablen: <span class="template-vars">
                                    <code>{{name}}</code> <code>{{email}}</code>
                                    <code>{{company_name}}</code> <code>{{site_url}}</code>
                                    <code>{{sender_name}}</code> <code>{{unsubscribe_url}}</code>
                                    <code>{{open_tracker}}</code> (Tracking-Pixel)
                                </span></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nur-Text-Version <span class="text-muted fw-normal">(für E-Mail-Clients ohne HTML)</span></label>
                                <textarea name="body_text" class="form-control font-monospace" rows="5"
                                          style="font-size:12px"><?= htmlspecialchars($edit['body_text'] ?? '') ?></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i>Speichern
                                </button>
                                <button type="button" class="btn btn-outline-info" onclick="showPreview()">
                                    <i class="bi bi-eye me-1"></i>Vorschau
                                </button>
                                <?php if ($edit): ?>
                                <a href="templates.php" class="btn btn-outline-secondary ms-auto">+ Neu</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar: template list + tips -->
            <div class="col-xl-5">
                <!-- Preview -->
                <div class="card shadow-sm border-0 mb-4" id="previewCard" style="display:none!important">
                    <div class="card-header bg-white py-2 d-flex justify-content-between">
                        <span class="fw-semibold small">Vorschau</span>
                        <button type="button" class="btn-close btn-sm" onclick="hidePreview()"></button>
                    </div>
                    <div class="card-body p-0">
                        <iframe id="previewFrame" width="100%" height="400" frameborder="0"></iframe>
                    </div>
                </div>

                <!-- Spam word warning box -->
                <div class="card border-warning border-0 bg-warning-subtle mb-4">
                    <div class="card-body">
                        <h6 class="fw-semibold"><i class="bi bi-exclamation-triangle me-1 text-warning"></i>Spam-Trigger vermeiden</h6>
                        <p class="small mb-2">Folgende Wörter erhöhen den Spam-Score – vermeiden Sie diese:</p>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach (['Gratis','Kostenlos','Jetzt kaufen','Klicken Sie hier','Einmalig','Dringend','Gewinner','Lotto','Casino','Kredit','Schulden','Zinsen','Garantiert','100%','Exklusiv für Sie'] as $w): ?>
                            <span class="badge bg-warning text-dark"><?= $w ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Templates list -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-semibold mb-0">Vorhandene Templates</h6>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php if (empty($templates)): ?>
                        <li class="list-group-item text-muted text-center py-3">Noch keine Templates.</li>
                        <?php endif; ?>
                        <?php foreach ($templates as $t): ?>
                        <li class="list-group-item d-flex align-items-center justify-content-between py-2">
                            <div>
                                <div class="fw-semibold small"><?= htmlspecialchars($t['name']) ?></div>
                                <div class="text-muted" style="font-size:.78em"><?= htmlspecialchars($t['subject']) ?></div>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <a href="templates.php?edit=<?= $t['id'] ?>" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Template löschen?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const defaultHtml = <?= json_encode($default_html) ?>;

function loadDefault() {
    if (confirm('Standard-Template laden? Der aktuelle Inhalt wird überschrieben.')) {
        document.getElementById('bodyHtml').value = defaultHtml;
    }
}

function showPreview() {
    const html  = document.getElementById('bodyHtml').value;
    const frame = document.getElementById('previewFrame');
    const card  = document.getElementById('previewCard');
    card.style.display = '';
    const doc = frame.contentDocument || frame.contentWindow.document;
    doc.open(); doc.write(html); doc.close();
}

function hidePreview() {
    document.getElementById('previewCard').style.display = 'none!important';
    document.getElementById('previewCard').style.removeProperty('display');
    document.getElementById('previewCard').style.display = 'none';
}
</script>
</body>
</html>
