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

// Professional German email template for kryptoxpay.co.uk
// Supports {{#if scam_platform}}…{{else}}…{{/if}} conditional blocks
$default_html = <<<'HTML'
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>{{company_name}}</title>
<style>
  /* Reset */
  body,table,td,a{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}
  table,td{mso-table-lspace:0pt;mso-table-rspace:0pt}
  img{-ms-interpolation-mode:bicubic;border:0;outline:none;text-decoration:none}
  /* Layout */
  body{margin:0;padding:0;background-color:#f2f4f7;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif}
  .email-wrapper{width:100%;background:#f2f4f7;padding:30px 0}
  .email-content{max-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)}
  /* Header */
  .header{background:#0d2744;padding:32px 40px;text-align:center}
  .header-logo{font-size:24px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;text-decoration:none}
  .header-logo span{color:#f0a500}
  .header-tagline{margin:6px 0 0;font-size:12px;color:#7fa8d4;letter-spacing:1px;text-transform:uppercase}
  /* Alert banner */
  .alert-banner{background:#fff3cd;border-left:4px solid #f0a500;padding:14px 20px;margin:0 0 20px;border-radius:0 6px 6px 0}
  .alert-banner p{margin:0;font-size:14px;color:#7a5c00}
  /* Body */
  .body{padding:38px 40px;color:#374151;font-size:15px;line-height:1.8}
  .body h2{margin:0 0 18px;font-size:20px;color:#0d2744;font-weight:700}
  .body p{margin:0 0 16px}
  .body ul{padding-left:20px;margin:0 0 16px}
  .body ul li{margin-bottom:6px}
  /* Divider */
  .divider{height:1px;background:#e8edf2;margin:24px 0}
  /* CTA button */
  .cta-wrapper{text-align:center;margin:28px 0}
  .cta-btn{display:inline-block;background:#f0a500;color:#ffffff!important;padding:14px 36px;border-radius:6px;font-size:15px;font-weight:700;text-decoration:none;letter-spacing:0.3px}
  /* Signature */
  .signature{font-size:14px;color:#374151}
  .signature strong{color:#0d2744}
  /* Footer */
  .footer{background:#f8fafc;padding:22px 40px;border-top:1px solid #e8edf2}
  .footer p{margin:0 0 6px;font-size:12px;color:#9ca3af;text-align:center;line-height:1.6}
  .footer a{color:#9ca3af;text-decoration:underline}
  /* Responsive */
  @media only screen and (max-width:620px){
    .email-content,.header,.body,.footer{border-radius:0!important}
    .body,.header,.footer{padding:24px 20px!important}
  }
</style>
</head>
<body>
<div class="email-wrapper">
  <div class="email-content">

    <!-- Header -->
    <div class="header">
      <div class="header-logo">{{company_name}}</div>
      <p class="header-tagline">KI-gestützte Kapitalrückholung &amp; Beratung</p>
    </div>

    <!-- Body -->
    <div class="body">
      <h2>Sehr geehrte/r {{name}},</h2>

      {{#if scam_platform}}
      <div class="alert-banner">
        <p>&#9888;&nbsp; Wir haben Informationen erhalten, dass Sie Kapital auf der Plattform <strong>{{scam_platform}}</strong> verloren haben könnten. Unser KI-gestütztes System hat diese Plattform als bekannte Betrugsstätte identifiziert.</p>
      </div>
      <p>wir wenden uns heute gezielt an Sie, da Anzeichen vorliegen, dass Sie durch <strong>{{scam_platform}}</strong> einen finanziellen Schaden erlitten haben könnten.</p>
      <p>Mit modernster KI-Technologie und langjähriger Erfahrung im Bereich der Kapitalrückholung unterstützen wir Betroffene dabei, verlorene Mittel zurückzuholen. Unsere Analyse zeigt, dass eine Rückholung in vergleichbaren Fällen möglich sein kann.</p>
      {{else}}
      <p>wir wenden uns heute mit einer wichtigen Mitteilung an Sie, die im Zusammenhang mit Ihren digitalen Vermögenswerten stehen könnte.</p>
      <p>Unser Team bei <strong>{{company_name}}</strong> unterstützt Anleger dabei, ihre Situation zu bewerten und mögliche Handlungsoptionen zu prüfen. Mit modernster KI-Technologie und langjähriger Erfahrung stehen wir Ihnen als vertrauenswürdiger Ansprechpartner zur Verfügung.</p>
      {{/if}}

      <p>Was wir für Sie tun können:</p>
      <ul>
        <li>Kostenlose und unverbindliche Erstberatung</li>
        <li>KI-gestützte Analyse Ihrer individuellen Situation</li>
        <li>Professionelle Unterstützung durch erfahrene Fachleute</li>
        <li>Diskrete und vertrauliche Bearbeitung Ihres Anliegens</li>
      </ul>

      <div class="divider"></div>

      {{#if scam_platform}}
      <p>Handeln Sie jetzt – je früher wir Ihren Fall prüfen können, desto besser sind die Chancen auf eine Rückholung Ihrer Mittel. Kontaktieren Sie uns unverbindlich über unsere Website.</p>
      {{else}}
      <p>Wenn Sie mehr erfahren möchten oder Fragen zu Ihrer Situation haben, stehen wir Ihnen gerne zur Verfügung. Eine unverbindliche Kontaktaufnahme ist der erste Schritt.</p>
      {{/if}}

      <div class="cta-wrapper">
        <a href="{{site_url}}" class="cta-btn">Jetzt unverbindlich informieren</a>
      </div>

      <div class="divider"></div>

      <div class="signature">
        <p>Mit freundlichen Grüßen,<br>
        <strong>{{sender_name}}</strong><br>
        {{company_name}}<br>
        <a href="{{site_url}}" style="color:#0d2744">{{site_url}}</a></p>
      </div>
    </div>

    <!-- Footer -->
    <div class="footer">
      <p>Sie erhalten diese E-Mail, da Sie sich für Finanzthemen interessiert haben oder früher Kontakt mit uns aufgenommen haben.</p>
      <p>
        <a href="{{unsubscribe_url}}">Abmelden</a> &nbsp;|&nbsp;
        <a href="{{site_url}}/datenschutz">Datenschutz</a> &nbsp;|&nbsp;
        <a href="{{site_url}}/impressum">Impressum</a>
      </p>
      <p>{{company_name}} &nbsp;&middot;&nbsp; <a href="{{site_url}}">{{site_url}}</a></p>
      {{open_tracker}}
    </div>

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
                                    <label class="form-label fw-semibold">
                                        Betreff * <span class="text-muted fw-normal small">— Spintax erlaubt</span>
                                    </label>
                                    <input type="text" name="subject" class="form-control" required
                                           value="<?= htmlspecialchars($edit['subject'] ?? '') ?>"
                                           placeholder="{Betreff A|Betreff B|Betreff C}">
                                    <div class="form-text">Spintax: <code>{Option A|Option B|Option C}</code> – wird beim Senden zufällig aufgelöst.</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold d-flex justify-content-between">
                                    <span>HTML-Inhalt</span>
                                    <button type="button" class="btn btn-link btn-sm p-0" onclick="loadDefault()">Standard-Template laden</button>
                                </label>
                                <textarea name="body_html" id="bodyHtml" class="form-control font-monospace"
                                          rows="18" style="font-size:12px"><?= htmlspecialchars($edit['body_html'] ?: $default_html) ?></textarea>
                                <div class="form-text">Verfügbare Variablen: <span class="template-vars">
                                    <code>{{name}}</code> <code>{{email}}</code>
                                    <code>{{company_name}}</code> <code>{{site_url}}</code>
                                    <code>{{sender_name}}</code> <code>{{unsubscribe_url}}</code>
                                    <code>{{scam_platform}}</code> <code>{{open_tracker}}</code> (Tracking-Pixel)
                                </span><br>
                                <span class="text-primary small">Konditionale Blöcke: <code>{{#if scam_platform}}…{{else}}…{{/if}}</code><br>
                                <strong>Spintax:</strong> <code>{Text A|Text B|Text C}</code> – beim Versand wird automatisch eine zufällige Variante ausgewählt. Auch im Betreff nutzbar. Verschachtelung möglich.</span></div>
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
                <div class="card shadow-sm border-0 mb-4" id="previewCard" style="display:none">
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
    const card = document.getElementById('previewCard');
    if (card) card.style.display = 'none';
}
</script>
</body>
</html>
