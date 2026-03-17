<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

admin_check();
ensure_blog_table();

$id      = (int) ($_GET['id'] ?? 0);
$post    = $id ? get_blog_post($id) : null;
$is_new  = ($post === null);

if ($id && !$post) {
    header('Location: blog.php');
    exit;
}

$success_msg = '';
$error_msg   = '';

// ── Handle save ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_post'])) {
    $title  = trim($_POST['title'] ?? '');
    $slug   = trim($_POST['slug']  ?? '') ?: slugify($title);
    $slug   = slugify($slug);

    $data = [
        'title'            => $title,
        'slug'             => $slug,
        'excerpt'          => trim($_POST['excerpt']          ?? ''),
        'content'          => trim($_POST['content']          ?? ''),
        'meta_title'       => trim($_POST['meta_title']       ?? ''),
        'meta_description' => trim($_POST['meta_description'] ?? ''),
        'meta_keywords'    => trim($_POST['meta_keywords']    ?? ''),
        'featured_image'   => trim($_POST['featured_image']   ?? ''),
        'status'           => in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'draft',
        'published_at'     => trim($_POST['published_at']     ?? ''),
    ];

    if (empty($title)) {
        $error_msg = 'Bitte geben Sie einen Titel ein.';
    } else {
        $result = save_blog_post($data, $is_new ? null : $id);
        if ($result !== false) {
            log_activity($is_new ? 'create_blog_post' : 'update_blog_post', "Blog post #$result");
            if ($is_new) {
                header('Location: blog_edit.php?id=' . $result . '&saved=1');
                exit;
            }
            $success_msg = 'Beitrag erfolgreich gespeichert.';
            $post = get_blog_post($id);
        } else {
            $error_msg = 'Fehler beim Speichern. Möglicherweise ist der Slug bereits vergeben.';
        }
    }
}

if (isset($_GET['saved'])) {
    $success_msg = 'Beitrag erfolgreich erstellt.';
    $post = get_blog_post($id);
}

$openai_configured = !empty(get_setting('openai_api_key', ''));
$site_url = rtrim(get_setting('site_url', SITE_URL), '/');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_new ? 'Neuer Beitrag' : 'Beitrag bearbeiten' ?> – VerlustRückholung Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        .content-editor { min-height: 350px; font-family: monospace; font-size: 0.9rem; }
        .char-badge { font-size: 0.7rem; }
    </style>
</head>
<body class="admin-body">

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="admin-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="blog.php" class="btn btn-sm btn-light mb-2">
                    <i class="bi bi-arrow-left me-1"></i>Zurück
                </a>
                <h4 class="fw-bold mb-0">
                    <?= $is_new ? '<i class="bi bi-plus-circle me-2 text-primary"></i>Neuer Blog-Beitrag' : '<i class="bi bi-pencil me-2 text-primary"></i>Beitrag bearbeiten' ?>
                </h4>
            </div>
            <?php if (!$is_new && $post['status'] === 'published'): ?>
            <a href="<?= htmlspecialchars($site_url . '/blog/' . $post['slug'], ENT_QUOTES, 'UTF-8') ?>"
               target="_blank" class="btn btn-outline-success btn-sm">
                <i class="bi bi-eye me-1"></i>Vorschau
            </a>
            <?php endif; ?>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($error_msg): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="row g-4">

                <!-- Left column: Content -->
                <div class="col-lg-8">

                    <!-- Title & Slug -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 fw-bold">
                            <i class="bi bi-type me-2 text-primary"></i>Titel &amp; URL
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Titel <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="postTitle" class="form-control form-control-lg"
                                       placeholder="z. B. Wie Sie Ihr Geld nach Krypto-Betrug zurückbekommen"
                                       value="<?= htmlspecialchars($post['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       required>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-semibold">Slug (URL)</label>
                                <div class="input-group">
                                    <span class="input-group-text text-muted small">/blog/</span>
                                    <input type="text" name="slug" id="postSlug" class="form-control font-monospace"
                                           placeholder="wie-sie-geld-nach-krypto-betrug-zurueckbekommen"
                                           value="<?= htmlspecialchars($post['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="form-text">Wird automatisch aus dem Titel generiert. Nur Kleinbuchstaben, Zahlen und Bindestriche.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Excerpt -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 fw-bold">
                            <i class="bi bi-chat-square-text me-2 text-primary"></i>Zusammenfassung (Excerpt)
                        </div>
                        <div class="card-body">
                            <textarea name="excerpt" class="form-control" rows="3"
                                      placeholder="Kurze Zusammenfassung des Beitrags (wird in der Blog-Übersicht und als Vorschau angezeigt)..."><?= htmlspecialchars($post['excerpt'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                            <span class="fw-bold"><i class="bi bi-file-text me-2 text-primary"></i>Inhalt</span>
                            <?php if ($openai_configured): ?>
                            <button type="button" class="btn btn-sm btn-outline-warning" id="btnGenContent">
                                <i class="bi bi-stars me-1"></i>KI-Inhalt generieren
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-0">
                            <div class="border-bottom p-2 bg-light d-flex gap-1 flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="wrapText('<strong>','</strong>')"><b>B</b></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="wrapText('<em>','</em>')"><i>I</i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="wrapText('<h2>','</h2>')">H2</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="wrapText('<h3>','</h3>')">H3</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="wrapText('<ul>\n  <li>','</li>\n</ul>')">UL</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="wrapText('<ol>\n  <li>','</li>\n</ol>')">OL</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="wrapText('<p>','</p>')">P</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="wrapText('<a href=\'\'>', '</a>')">Link</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="wrapText('<blockquote>','</blockquote>')">BQ</button>
                            </div>
                            <textarea name="content" id="postContent" class="form-control border-0 content-editor"
                                      placeholder="HTML-Inhalt des Blog-Beitrags..."><?= htmlspecialchars($post['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    </div>

                    <!-- SEO for this post -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 fw-bold">
                            <i class="bi bi-search me-2 text-primary"></i>SEO für diesen Beitrag
                            <?php if ($openai_configured): ?>
                            <button type="button" class="btn btn-sm btn-outline-warning ms-2" id="btnGenPostSeo">
                                <i class="bi bi-stars me-1"></i>KI generieren
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold small">
                                        SEO-Titel <span class="text-muted fw-normal">(leer = Beitragstitel)</span>
                                        <span class="badge bg-light text-dark char-badge ms-1" id="metaTitleCount">0/70</span>
                                    </label>
                                    <input type="text" name="meta_title" id="metaTitle" class="form-control"
                                           maxlength="70"
                                           placeholder="SEO-optimierter Titel..."
                                           value="<?= htmlspecialchars($post['meta_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold small">
                                        Meta-Beschreibung
                                        <span class="badge bg-light text-dark char-badge ms-1" id="metaDescCount">0/160</span>
                                    </label>
                                    <textarea name="meta_description" id="metaDesc" class="form-control" rows="2"
                                              maxlength="160"
                                              placeholder="Beschreibung des Beitrags für Suchmaschinen (max. 160 Zeichen)..."><?= htmlspecialchars($post['meta_description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold small">Keywords (kommagetrennt)</label>
                                    <input type="text" name="meta_keywords" id="metaKeywords" class="form-control"
                                           placeholder="keyword1, keyword2, ..."
                                           value="<?= htmlspecialchars($post['meta_keywords'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>

                            <!-- SERP Preview -->
                            <div class="mt-3">
                                <label class="form-label fw-semibold small"><i class="bi bi-display me-1"></i>Google-Vorschau</label>
                                <div class="border rounded p-3 bg-light">
                                    <div class="text-success small"><?= htmlspecialchars($site_url . '/blog/', ENT_QUOTES, 'UTF-8') ?><span id="serpSlug"></span></div>
                                    <div class="fw-bold text-primary" id="serpTitle" style="font-size:1rem;"></div>
                                    <div class="text-muted small" id="serpDesc" style="overflow:hidden;max-height:2.8em;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right column: Settings -->
                <div class="col-lg-4">

                    <!-- Status & Publish -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white border-0 fw-bold">
                            <i class="bi bi-toggles me-2 text-primary"></i>Veröffentlichung
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Status</label>
                                <select name="status" class="form-select">
                                    <option value="draft"     <?= ($post['status'] ?? 'draft') === 'draft'     ? 'selected' : '' ?>>Entwurf</option>
                                    <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Veröffentlicht</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Datum veröffentlichen</label>
                                <input type="datetime-local" name="published_at" class="form-control"
                                       value="<?= $post['published_at'] ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : '' ?>">
                                <div class="form-text">Leer lassen = jetzt veröffentlichen</div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" name="save_post" class="btn btn-primary fw-bold">
                                    <i class="bi bi-save me-2"></i>Speichern
                                </button>
                                <button type="submit" name="save_post" class="btn btn-success"
                                        onclick="document.querySelector('[name=status]').value='published'">
                                    <i class="bi bi-send me-2"></i>Veröffentlichen
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Featured Image -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white border-0 fw-bold">
                            <i class="bi bi-image me-2 text-primary"></i>Beitragsbild
                        </div>
                        <div class="card-body">
                            <input type="url" name="featured_image" id="featuredImage" class="form-control mb-2"
                                   placeholder="https://example.de/bild.jpg"
                                   value="<?= htmlspecialchars($post['featured_image'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div id="imgPreview" class="<?= empty($post['featured_image']) ? 'd-none' : '' ?>">
                                <img src="<?= htmlspecialchars($post['featured_image'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                     class="img-fluid rounded" alt="Vorschau" id="imgPreviewEl">
                            </div>
                            <div class="form-text">URL des Titelbilds (wird auf der Blog-Seite angezeigt).</div>
                        </div>
                    </div>

                    <!-- AI content generation (sidebar) -->
                    <?php if ($openai_configured): ?>
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white border-0 fw-bold">
                            <i class="bi bi-stars me-2 text-warning"></i>KI-Assistent
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-2">Generieren Sie automatisch Inhalt, SEO-Beschreibung und Keywords auf Basis des Titels.</p>
                            <button type="button" class="btn btn-warning btn-sm w-100 mb-2" id="btnAiAll">
                                <i class="bi bi-stars me-1"></i>Vollständig generieren
                            </button>
                            <div id="aiSpinner" class="d-none text-center py-2">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                <span class="ms-2 small">Generiere…</span>
                            </div>
                            <div id="aiError" class="alert alert-danger small d-none py-2"></div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

            </div><!-- /row -->
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Auto-slug from title ──────────────────────────────────────────────────────
const titleEl = document.getElementById('postTitle');
const slugEl  = document.getElementById('postSlug');
let slugEdited = <?= $is_new ? 'false' : 'true' ?>;

function toSlug(str) {
    const map = {ä:'ae',ö:'oe',ü:'ue',Ä:'ae',Ö:'oe',Ü:'ue',ß:'ss'};
    str = str.replace(/[äöüÄÖÜß]/g, c => map[c] || c);
    return str.toLowerCase().trim().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
}
if (titleEl) {
    titleEl.addEventListener('input', function() {
        if (!slugEdited) slugEl.value = toSlug(this.value);
        updateSerp();
    });
}
if (slugEl) {
    slugEl.addEventListener('input', function() {
        slugEdited = true;
        document.getElementById('serpSlug').textContent = this.value;
    });
}

// ── SERP Preview ──────────────────────────────────────────────────────────────
function updateSerp() {
    const t = document.getElementById('metaTitle')?.value || document.getElementById('postTitle')?.value || '';
    const d = document.getElementById('metaDesc')?.value  || '';
    const s = document.getElementById('postSlug')?.value  || '';
    document.getElementById('serpTitle').textContent = t;
    document.getElementById('serpDesc').textContent  = d;
    document.getElementById('serpSlug').textContent  = s;
}
['metaTitle','metaDesc','postSlug'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', updateSerp);
});
updateSerp();

// ── Character counters ────────────────────────────────────────────────────────
function charCounter(inputId, badgeId, max) {
    const el = document.getElementById(inputId);
    const bd = document.getElementById(badgeId);
    if (!el || !bd) return;
    const upd = () => {
        const len = el.value.length;
        bd.textContent = len + '/' + max;
        bd.className   = len > max * 0.9 ? 'badge bg-warning text-dark char-badge ms-1' : 'badge bg-light text-dark char-badge ms-1';
    };
    el.addEventListener('input', upd);
    upd();
}
charCounter('metaTitle', 'metaTitleCount', 70);
charCounter('metaDesc',  'metaDescCount',  160);

// ── Toolbar ───────────────────────────────────────────────────────────────────
function wrapText(before, after) {
    const ta = document.getElementById('postContent');
    if (!ta) return;
    const start = ta.selectionStart, end = ta.selectionEnd;
    const sel   = ta.value.substring(start, end);
    ta.value = ta.value.substring(0, start) + before + sel + after + ta.value.substring(end);
    ta.selectionStart = start + before.length;
    ta.selectionEnd   = end   + before.length;
    ta.focus();
}

// ── Featured image preview ────────────────────────────────────────────────────
const imgInput = document.getElementById('featuredImage');
const imgPrev  = document.getElementById('imgPreview');
const imgEl    = document.getElementById('imgPreviewEl');
if (imgInput) {
    imgInput.addEventListener('input', function() {
        if (this.value) {
            imgEl.src = this.value;
            imgPrev.classList.remove('d-none');
        } else {
            imgPrev.classList.add('d-none');
        }
    });
}

// ── AI Generation ─────────────────────────────────────────────────────────────
<?php if ($openai_configured): ?>
function showSpinner(show) {
    document.getElementById('aiSpinner')?.classList.toggle('d-none', !show);
    document.getElementById('aiError')?.classList.add('d-none');
}

function callAI(type, context) {
    showSpinner(true);
    fetch('../admin/ajax/ai_generate.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'context=' + encodeURIComponent(context) + '&type=' + encodeURIComponent(type) + '&lang=Deutsch'
    })
    .then(r => r.json())
    .then(data => {
        showSpinner(false);
        if (data.error) {
            const errEl = document.getElementById('aiError');
            errEl.textContent = data.error;
            errEl.classList.remove('d-none');
            return;
        }
        if (data.description) document.getElementById('metaDesc').value = data.description;
        if (data.keywords)    document.getElementById('metaKeywords').value = data.keywords;
        updateSerp();
        charCounter('metaDesc', 'metaDescCount', 160);
    })
    .catch(err => {
        showSpinner(false);
        const errEl = document.getElementById('aiError');
        errEl.textContent = 'Fehler: ' + err.message;
        errEl.classList.remove('d-none');
    });
}

function getContext() {
    const title   = document.getElementById('postTitle')?.value  || '';
    const excerpt = document.querySelector('[name=excerpt]')?.value || '';
    return title + ' ' + excerpt;
}

document.getElementById('btnAiAll')?.addEventListener('click', () => callAI('all', getContext()));

document.getElementById('btnGenContent')?.addEventListener('click', function() {
    const title = document.getElementById('postTitle')?.value || '';
    if (!title) { alert('Bitte zuerst einen Titel eingeben.'); return; }
    showSpinner(true);
    fetch('../admin/ajax/ai_generate.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'context=' + encodeURIComponent('Schreibe einen vollständigen, SEO-optimierten Blog-Artikel auf Deutsch zum Thema: ' + title + '. Der Artikel soll mindestens 600 Wörter haben, strukturierte Überschriften (H2/H3) enthalten und praktische Tipps für Betrugsopfer geben. Format: HTML ohne doctype/head/body Tags.') + '&type=content&lang=Deutsch'
    })
    .then(r => r.json())
    .then(data => {
        showSpinner(false);
        if (data.description || data.content) {
            document.getElementById('postContent').value = data.description || data.content || '';
        }
    })
    .catch(() => showSpinner(false));
});

document.getElementById('btnGenPostSeo')?.addEventListener('click', () => callAI('all', getContext()));
<?php endif; ?>
</script>
</body>
</html>
