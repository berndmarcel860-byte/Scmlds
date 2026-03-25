<?php
/**
 * Admin – Static Pages Management
 * Manage Impressum, Datenschutz, Kontakt, AGB and other static pages.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

admin_check();

$msg      = '';
$msg_type = 'success';
$edit     = null;

// Ensure table + seed defaults
ensure_static_pages_table();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $d  = [
            'title'            => trim($_POST['title']            ?? ''),
            'slug'             => trim(strtolower($_POST['slug']  ?? '')),
            'content'          => $_POST['content']               ?? '',
            'meta_title'       => trim($_POST['meta_title']       ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'sort_order'       => (int)($_POST['sort_order']      ?? 0),
        ];
        // Sanitise slug: only lowercase alphanumeric and hyphens
        $d['slug'] = preg_replace('/[^a-z0-9\-]/', '', $d['slug']);

        if (empty($d['title']) || empty($d['slug'])) {
            $msg_type = 'danger';
            $msg      = 'Titel und Slug sind Pflichtfelder.';
        } else {
            if (save_static_page($d, $id ?: null)) {
                log_activity('static_page_saved', 'Page saved: ' . $d['slug']);
                $msg = 'Seite gespeichert.';
                if (!$id) {
                    header('Location: pages.php?msg=saved');
                    exit;
                }
            } else {
                $msg_type = 'danger';
                $msg      = 'Fehler beim Speichern (Slug möglicherweise bereits vergeben).';
            }
        }
    }
}

if (isset($_GET['msg'])) { $msg = 'Seite gespeichert.'; }
if (isset($_GET['edit'])) {
    $edit = get_static_page($_GET['edit']);
}
if (!$edit && isset($_GET['new'])) {
    $edit = ['id' => 0, 'title' => '', 'slug' => '', 'content' => '',
             'meta_title' => '', 'meta_description' => '', 'sort_order' => 0];
}

$pages = get_static_pages();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seiten verwalten – Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        #contentEditor { font-size: 13px; min-height: 320px; }
    </style>
</head>
<body class="admin-body">

<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>
    <div class="admin-content p-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0"><i class="bi bi-file-text me-2 text-primary"></i>Seiten</h4>
                <p class="text-muted small mb-0">Impressum, Datenschutz, Kontakt, AGB und weitere statische Seiten</p>
            </div>
            <a href="pages.php?new=1" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Neue Seite
            </a>
        </div>

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show">
            <?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row g-4">

            <?php if ($edit !== null): ?>
            <!-- ── Editor ── -->
            <div class="col-xl-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-semibold mb-0">
                            <?= $edit['id'] ? 'Seite bearbeiten' : 'Neue Seite erstellen' ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Titel *</label>
                                    <input type="text" name="title" class="form-control" required
                                           value="<?= htmlspecialchars($edit['title'] ?? '') ?>"
                                           placeholder="z. B. Impressum">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Slug * <span class="text-muted fw-normal">(URL-Pfad)</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text text-muted">/</span>
                                        <input type="text" name="slug" id="slugInput" class="form-control" required
                                               value="<?= htmlspecialchars($edit['slug'] ?? '') ?>"
                                               placeholder="impressum">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">Reihenfolge</label>
                                    <input type="number" name="sort_order" class="form-control"
                                           min="0" max="99" value="<?= (int)($edit['sort_order'] ?? 0) ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Seiteninhalt (HTML)</label>
                                <textarea name="content" id="contentEditor"
                                          class="form-control font-monospace"
                                          rows="16"><?= htmlspecialchars($edit['content'] ?? '') ?></textarea>
                                <div class="form-text">Unterstützt vollständiges HTML. Bootstrap-Klassen stehen zur Verfügung.</div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">Meta-Titel</label>
                                    <input type="text" name="meta_title" class="form-control"
                                           value="<?= htmlspecialchars($edit['meta_title'] ?? '') ?>">
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label fw-semibold">Meta-Beschreibung</label>
                                    <input type="text" name="meta_description" class="form-control"
                                           value="<?= htmlspecialchars($edit['meta_description'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i>Speichern
                                </button>
                                <a href="pages.php" class="btn btn-outline-secondary">Abbrechen</a>
                                <?php if ($edit['id']): ?>
                                <a href="/<?= htmlspecialchars($edit['slug']) ?>"
                                   target="_blank" class="btn btn-outline-info ms-auto">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>Seite anzeigen
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
            <?php else: ?>
            <div class="col-12">
            <?php endif; ?>

                <!-- ── Page list ── -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-semibold mb-0">Vorhandene Seiten</h6>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php if (empty($pages)): ?>
                        <li class="list-group-item text-muted text-center py-3">Noch keine Seiten.</li>
                        <?php endif; ?>
                        <?php foreach ($pages as $p): ?>
                        <li class="list-group-item d-flex align-items-center justify-content-between py-2">
                            <div>
                                <div class="fw-semibold small"><?= htmlspecialchars($p['title']) ?></div>
                                <a href="/<?= htmlspecialchars($p['slug']) ?>" target="_blank"
                                   class="text-muted" style="font-size:.78em">
                                    /<?= htmlspecialchars($p['slug']) ?>
                                    <i class="bi bi-box-arrow-up-right ms-1" style="font-size:.7em"></i>
                                </a>
                            </div>
                            <a href="pages.php?edit=<?= htmlspecialchars($p['slug']) ?>"
                               class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

        </div><!-- row -->
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-generate slug from title for new pages
const titleInput = document.querySelector('[name="title"]');
const slugInput  = document.getElementById('slugInput');
if (titleInput && slugInput && !slugInput.value) {
    titleInput.addEventListener('input', function () {
        slugInput.value = this.value
            .toLowerCase()
            .replace(/ä/g,'ae').replace(/ö/g,'oe').replace(/ü/g,'ue').replace(/ß/g,'ss')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
    });
}
</script>
</body>
</html>
