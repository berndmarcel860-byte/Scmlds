<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

admin_check();

$message  = '';
$msg_type = 'success';

// ── Handle form submission ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab = trim($_POST['tab'] ?? 'meta');

    if ($tab === 'meta') {
        $keys = [
            'page_title', 'meta_description', 'meta_keywords',
            'robots_meta', 'og_image',
            'twitter_handle', 'google_analytics_id',
        ];
        $data = [];
        foreach ($keys as $k) {
            $data[$k] = trim($_POST[$k] ?? '');
        }
        if (save_settings($data)) {
            log_activity('seo_updated', 'SEO meta settings updated');
            $message = 'SEO-Einstellungen wurden gespeichert.';
        } else {
            $msg_type = 'danger';
            $message  = 'Fehler beim Speichern.';
        }
    } elseif ($tab === 'ai') {
        $data = ['openai_api_key' => trim($_POST['openai_api_key'] ?? '')];
        if (save_settings($data)) {
            log_activity('seo_updated', 'OpenAI API key updated');
            $message = 'API-Schlüssel gespeichert.';
        } else {
            $msg_type = 'danger';
            $message  = 'Fehler beim Speichern des API-Schlüssels.';
        }
    }
}

// ── Load current values ───────────────────────────────────────────────────────
$s = [];
foreach (get_all_settings() as $row) {
    $s[$row['setting_key']] = $row['setting_value'];
}

$active_tab = $_POST['tab'] ?? ($_GET['tab'] ?? 'meta');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO – VerlustRückholung Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="admin-content p-4">
        <div class="mb-4">
            <h4 class="fw-bold mb-0"><i class="bi bi-search me-2 text-primary"></i>SEO-Verwaltung</h4>
            <p class="text-muted small mb-0">Suchmaschinenoptimierung, Open Graph, KI-Generierung</p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show">
            <i class="bi bi-<?= $msg_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Nav tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $active_tab === 'meta' ? 'active' : '' ?>"
                   href="?tab=meta"><i class="bi bi-tags me-1"></i>Meta-Tags &amp; OG</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $active_tab === 'ai' ? 'active' : '' ?>"
                   href="?tab=ai"><i class="bi bi-stars me-1"></i>KI-Generierung</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $active_tab === 'checklist' ? 'active' : '' ?>"
                   href="?tab=checklist"><i class="bi bi-list-check me-1"></i>SEO-Checkliste</a>
            </li>
        </ul>

        <!-- ===== TAB: META ===== -->
        <?php if ($active_tab === 'meta'): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="tab" value="meta">
                    <div class="row g-3">

                        <!-- Meta Title -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-type me-1 text-primary"></i>Meta-Titel (Title-Tag)
                                <span class="text-muted fw-normal small"> — empfohlen: 50–60 Zeichen</span>
                            </label>
                            <input type="text" name="page_title" id="metaTitle" class="form-control"
                                   maxlength="120"
                                   value="<?= htmlspecialchars($s['page_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-text">
                                <span id="titleCount">0</span>/120 Zeichen
                            </div>
                        </div>

                        <!-- Meta Description -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-text-paragraph me-1 text-primary"></i>Meta-Beschreibung
                                <span class="text-muted fw-normal small"> — empfohlen: 120–160 Zeichen</span>
                            </label>
                            <div class="input-group">
                                <textarea name="meta_description" id="metaDesc" class="form-control" rows="3"
                                          maxlength="320"><?= htmlspecialchars($s['meta_description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                <button type="button" class="btn btn-outline-secondary" id="btnGenDesc"
                                        title="Mit KI generieren">
                                    <i class="bi bi-stars"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                <span id="descCount">0</span>/320 Zeichen
                                &nbsp;·&nbsp; <a href="?tab=ai" class="small">API-Key konfigurieren</a>
                            </div>
                        </div>

                        <!-- Meta Keywords -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-tags me-1 text-primary"></i>Meta-Keywords
                                <span class="text-muted fw-normal small"> — kommagetrennt</span>
                            </label>
                            <div class="input-group">
                                <textarea name="meta_keywords" id="metaKw" class="form-control" rows="2"
                                          placeholder="Krypto-Betrug zurückfordern, Anlagebetrug Hilfe, ..."><?= htmlspecialchars($s['meta_keywords'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                <button type="button" class="btn btn-outline-secondary" id="btnGenKw"
                                        title="Mit KI generieren">
                                    <i class="bi bi-stars"></i>
                                </button>
                            </div>
                            <div class="form-text">Nicht mehr ranking-relevant, aber nützlich für interne Kategorisierung.</div>
                        </div>

                        <!-- Robots meta -->
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-robot me-1 text-primary"></i>Robots Meta-Tag
                            </label>
                            <input type="text" name="robots_meta" class="form-control"
                                   placeholder="index, follow, max-snippet:-1, max-image-preview:large"
                                   value="<?= htmlspecialchars($s['robots_meta'] ?? 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1', ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <!-- Twitter Handle -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-twitter-x me-1 text-primary"></i>Twitter/X Handle
                            </label>
                            <input type="text" name="twitter_handle" class="form-control"
                                   placeholder="@IhrHandle"
                                   value="<?= htmlspecialchars($s['twitter_handle'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <!-- OG Image -->
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-image me-1 text-primary"></i>Open Graph Bild-URL
                                <span class="text-muted fw-normal small"> — 1200 × 630 px empfohlen</span>
                            </label>
                            <input type="url" name="og_image" class="form-control"
                                   placeholder="https://example.de/assets/images/og-image.jpg"
                                   value="<?= htmlspecialchars($s['og_image'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <!-- Google Analytics -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-bar-chart me-1 text-primary"></i>Google Analytics ID
                            </label>
                            <input type="text" name="google_analytics_id" class="form-control"
                                   placeholder="G-XXXXXXXXXX"
                                   value="<?= htmlspecialchars($s['google_analytics_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-text">Wird automatisch in alle Seiten eingebettet.</div>
                        </div>

                        <!-- SERP Preview -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small">
                                <i class="bi bi-display me-1"></i>Google-Vorschau
                            </label>
                            <div class="border rounded p-3 bg-white" id="serpPreview">
                                <div class="text-success small" id="serpUrl"><?= htmlspecialchars(rtrim($s['site_url'] ?? '', '/') . '/', ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="fw-bold text-primary" id="serpTitle" style="font-size:1.05rem;"></div>
                                <div class="text-muted small" id="serpDesc" style="overflow:hidden;max-height:3em;"></div>
                            </div>
                        </div>

                    </div>
                    <hr class="my-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>SEO-Einstellungen speichern
                    </button>
                </form>
            </div>
        </div>

        <!-- ===== TAB: AI ===== -->
        <?php elseif ($active_tab === 'ai'): ?>
        <div class="row g-4">
            <!-- API Key card -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 fw-bold">
                        <i class="bi bi-key me-2 text-primary"></i>OpenAI API-Schlüssel
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info small">
                            <i class="bi bi-info-circle me-1"></i>
                            Erhalten Sie Ihren API-Key unter
                            <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a>.
                            Der Schlüssel wird verschlüsselt in der Datenbank gespeichert.
                            Es wird das Modell <strong>gpt-4o-mini</strong> verwendet.
                        </div>
                        <form method="POST">
                            <input type="hidden" name="tab" value="ai">
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">API-Key</label>
                                <div class="input-group">
                                    <input type="password" name="openai_api_key" id="apiKeyInput"
                                           class="form-control font-monospace"
                                           placeholder="sk-..."
                                           value="<?= htmlspecialchars($s['openai_api_key'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="button" class="btn btn-outline-secondary"
                                            onclick="var f=document.getElementById('apiKeyInput');f.type=f.type==='password'?'text':'password'">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    Status:
                                    <?php if (!empty($s['openai_api_key'])): ?>
                                        <span class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Konfiguriert</span>
                                    <?php else: ?>
                                        <span class="text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i>Nicht konfiguriert</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-save me-1"></i>API-Key speichern
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- AI Generator card -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 fw-bold">
                        <i class="bi bi-stars me-2 text-warning"></i>KI SEO-Generator
                    </div>
                    <div class="card-body">
                        <?php if (empty($s['openai_api_key'])): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Bitte zuerst einen OpenAI API-Key konfigurieren (links).
                        </div>
                        <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Kontext / Thema der Seite</label>
                            <textarea id="aiContext" class="form-control" rows="4"
                                      placeholder="Beschreiben Sie Ihre Website oder Seite, z. B.: Wir helfen Opfern von Krypto-Betrug, Forex-Betrug und Online-Investitionsbetrug dabei, ihr verlorenes Kapital zurückzufordern. Unser KI-gestützter Service analysiert verdächtige Transaktionen und begleitet die Rückholung durch internationale Experten."><?= htmlspecialchars($s['page_title'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Zielsprache</label>
                            <select id="aiLang" class="form-select form-select-sm" style="max-width:200px;">
                                <option value="Deutsch" selected>Deutsch</option>
                                <option value="Englisch">Englisch</option>
                                <option value="Français">Französisch</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 mb-3">
                            <button type="button" class="btn btn-primary" id="btnGenAll">
                                <i class="bi bi-stars me-1"></i>Alles generieren
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnGenDescOnly">
                                <i class="bi bi-text-paragraph me-1"></i>Nur Beschreibung
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnGenKwOnly">
                                <i class="bi bi-tags me-1"></i>Nur Keywords
                            </button>
                        </div>

                        <div id="aiSpinner" class="d-none text-center py-3">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="text-muted mt-2 small">KI generiert Vorschläge…</p>
                        </div>
                        <div id="aiResult" class="d-none">
                            <hr>
                            <h6 class="fw-bold">Generierte Meta-Beschreibung:</h6>
                            <div class="mb-3">
                                <textarea id="aiDescResult" class="form-control" rows="3" readonly></textarea>
                                <button type="button" class="btn btn-sm btn-outline-success mt-1" id="btnApplyDesc">
                                    <i class="bi bi-check me-1"></i>Übernehmen &amp; speichern
                                </button>
                            </div>
                            <h6 class="fw-bold">Generierte Keywords:</h6>
                            <div class="mb-2">
                                <textarea id="aiKwResult" class="form-control" rows="2" readonly></textarea>
                                <button type="button" class="btn btn-sm btn-outline-success mt-1" id="btnApplyKw">
                                    <i class="bi bi-check me-1"></i>Übernehmen &amp; speichern
                                </button>
                            </div>
                        </div>

                        <div id="aiError" class="alert alert-danger d-none mt-3"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== TAB: CHECKLIST ===== -->
        <?php elseif ($active_tab === 'checklist'): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 fw-bold">
                <i class="bi bi-list-check me-2 text-primary"></i>SEO-Checkliste
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i>Technisch</h6>
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item">✅ Canonical-Tag gesetzt</li>
                            <li class="list-group-item">✅ Open Graph + Twitter Card meta tags</li>
                            <li class="list-group-item">✅ JSON-LD strukturierte Daten (LocalBusiness + FAQPage)</li>
                            <li class="list-group-item">✅ sitemap.xml wird automatisch generiert (<a href="/sitemap.xml" target="_blank">/sitemap.xml</a>)</li>
                            <li class="list-group-item">✅ robots.txt vorhanden (<a href="/robots.txt" target="_blank">/robots.txt</a>)</li>
                            <li class="list-group-item">✅ HTTPS-Weiterleitung per .htaccess</li>
                            <li class="list-group-item">
                                <?= !empty($s['meta_description']) ? '✅' : '⚠️' ?>
                                Meta-Beschreibung konfiguriert
                                <?php if (empty($s['meta_description'])): ?>
                                    – <a href="?tab=meta">Jetzt konfigurieren</a>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item">
                                <?= !empty($s['google_analytics_id']) ? '✅' : '⚠️' ?>
                                Google Analytics eingebunden
                                <?php if (empty($s['google_analytics_id'])): ?>
                                    – <a href="?tab=meta">ID hinterlegen</a>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item">
                                <?= !empty($s['og_image']) ? '✅' : '⚠️' ?>
                                Open Graph Bild konfiguriert
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold text-primary"><i class="bi bi-pencil me-1"></i>Content &amp; Links</h6>
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item">
                                <?php
                                try {
                                    $pdo = db_connect();
                                    $cnt = (int) $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status='published'")->fetchColumn();
                                } catch (Exception $e) { $cnt = 0; }
                                echo $cnt > 0 ? '✅' : '⚠️';
                                ?>
                                Blog-Artikel veröffentlicht: <strong><?= $cnt ?></strong>
                                — <a href="blog.php">Blog verwalten</a>
                            </li>
                            <li class="list-group-item">⚠️ Google Search Console: Sitemap einreichen
                                (<a href="https://search.google.com/search-console" target="_blank">öffnen</a>)</li>
                            <li class="list-group-item">⚠️ Backlink-Aufbau (externe Links auf Ihre Domain)</li>
                            <li class="list-group-item">⚠️ Core Web Vitals prüfen (<a href="https://pagespeed.web.dev/" target="_blank">PageSpeed Insights</a>)</li>
                            <li class="list-group-item">⚠️ Lokale SEO: Google Business Profile anlegen</li>
                        </ul>

                        <h6 class="fw-bold text-warning mt-3"><i class="bi bi-link-45deg me-1"></i>Schnelllinks</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="/sitemap.xml" target="_blank" class="btn btn-sm btn-outline-secondary">sitemap.xml</a>
                            <a href="/robots.txt" target="_blank" class="btn btn-sm btn-outline-secondary">robots.txt</a>
                            <a href="https://search.google.com/search-console" target="_blank" class="btn btn-sm btn-outline-primary">Search Console</a>
                            <a href="https://pagespeed.web.dev/" target="_blank" class="btn btn-sm btn-outline-primary">PageSpeed</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /admin-content -->
</div><!-- /admin-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Character counters ────────────────────────────────────────────────────────
function updateCount(inputId, countId) {
    const el = document.getElementById(inputId);
    const ct = document.getElementById(countId);
    if (!el || !ct) return;
    ct.textContent = el.value.length;
    el.addEventListener('input', () => ct.textContent = el.value.length);
}
updateCount('metaTitle', 'titleCount');
updateCount('metaDesc',  'descCount');

// ── SERP Preview ──────────────────────────────────────────────────────────────
function updateSerp() {
    const titleEl = document.getElementById('metaTitle');
    const descEl  = document.getElementById('metaDesc');
    if (titleEl) document.getElementById('serpTitle').textContent = titleEl.value;
    if (descEl)  document.getElementById('serpDesc').textContent  = descEl.value;
}
['metaTitle', 'metaDesc'].forEach(id => {
    const el = document.getElementById(id);
    if (el) { el.addEventListener('input', updateSerp); }
});
updateSerp();

// ── AI generation helpers ─────────────────────────────────────────────────────
<?php if ($active_tab === 'meta'): ?>
function callAI(type) {
    const context = document.getElementById('metaTitle')?.value || '';
    const desc    = document.getElementById('metaDesc')?.value  || '';
    const kw      = document.getElementById('metaKw')?.value    || '';
    doAjaxGen(context + ' ' + desc, type, function(result) {
        if (result.description) document.getElementById('metaDesc').value = result.description;
        if (result.keywords)    document.getElementById('metaKw').value   = result.keywords;
        updateSerp();
        updateCount('metaDesc', 'descCount');
    });
}
document.getElementById('btnGenDesc')?.addEventListener('click', () => callAI('description'));
document.getElementById('btnGenKw')?.addEventListener('click',   () => callAI('keywords'));
<?php elseif ($active_tab === 'ai'): ?>
function doAjaxGen(context, type, cb) {
    const lang = document.getElementById('aiLang')?.value || 'Deutsch';
    document.getElementById('aiSpinner').classList.remove('d-none');
    document.getElementById('aiResult').classList.add('d-none');
    document.getElementById('aiError').classList.add('d-none');
    fetch('ajax/ai_generate.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'context=' + encodeURIComponent(context) + '&type=' + encodeURIComponent(type) + '&lang=' + encodeURIComponent(lang)
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('aiSpinner').classList.add('d-none');
        if (data.error) {
            document.getElementById('aiError').textContent = data.error;
            document.getElementById('aiError').classList.remove('d-none');
            return;
        }
        if (data.description) document.getElementById('aiDescResult').value = data.description;
        if (data.keywords)    document.getElementById('aiKwResult').value   = data.keywords;
        document.getElementById('aiResult').classList.remove('d-none');
        if (cb) cb(data);
    })
    .catch(err => {
        document.getElementById('aiSpinner').classList.add('d-none');
        document.getElementById('aiError').textContent = 'Verbindungsfehler: ' + err.message;
        document.getElementById('aiError').classList.remove('d-none');
    });
}

function genAll(type) {
    const ctx = document.getElementById('aiContext')?.value || '';
    doAjaxGen(ctx, type, null);
}
document.getElementById('btnGenAll')?.addEventListener('click',      () => genAll('all'));
document.getElementById('btnGenDescOnly')?.addEventListener('click', () => genAll('description'));
document.getElementById('btnGenKwOnly')?.addEventListener('click',   () => genAll('keywords'));

// Apply buttons save via AJAX to the settings
document.getElementById('btnApplyDesc')?.addEventListener('click', function() {
    const val = document.getElementById('aiDescResult').value;
    saveSetting('meta_description', val, this);
});
document.getElementById('btnApplyKw')?.addEventListener('click', function() {
    const val = document.getElementById('aiKwResult').value;
    saveSetting('meta_keywords', val, this);
});

function saveSetting(key, value, btn) {
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Speichern…';
    fetch('ajax/save_setting.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'key=' + encodeURIComponent(key) + '&value=' + encodeURIComponent(value)
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        if (data.ok) {
            btn.innerHTML = '<i class="bi bi-check me-1"></i>Gespeichert!';
            setTimeout(() => btn.innerHTML = orig, 2000);
        } else {
            btn.innerHTML = '<i class="bi bi-x me-1"></i>Fehler';
            setTimeout(() => btn.innerHTML = orig, 2000);
        }
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = orig; });
}
<?php endif; ?>

<?php if ($active_tab === 'meta'): ?>
function doAjaxGen(context, type, cb) {
    const lang = 'Deutsch';
    fetch('ajax/ai_generate.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'context=' + encodeURIComponent(context) + '&type=' + encodeURIComponent(type) + '&lang=' + encodeURIComponent(lang)
    })
    .then(r => r.json())
    .then(data => {
        if (!data.error && cb) cb(data);
    })
    .catch(() => {});
}
<?php endif; ?>
</script>
</body>
</html>
