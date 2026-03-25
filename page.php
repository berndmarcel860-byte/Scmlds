<?php
/**
 * Static Page Renderer
 * Serves: /impressum  /datenschutz  /kontakt  /agb  /ueber-uns  etc.
 *
 * Nginx routes unknown paths to $uri.php — but we register known slugs via
 * a dedicated location block in nginx.conf (or via the rewrite rule in .htaccess).
 *
 * Usage: accessed as  /page.php?slug=impressum  OR via URL rewrite as  /impressum
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '', '/ ');
if ($slug === '') {
    http_response_code(302);
    header('Location: /');
    exit;
}

$page = get_static_page($slug);
if (!$page) {
    http_response_code(404);
    // Try to load the 404 page or fall back to home
    header('Location: /');
    exit;
}

$company_name    = get_setting('company_name', 'VerlustRückholung');
$site_url        = get_setting('site_url', 'https://verlustrueckholung.de');
$meta_title      = $page['meta_title']       ?: ($page['title'] . ' – ' . $company_name);
$meta_description = $page['meta_description'] ?: '';
$whatsapp        = get_setting('whatsapp_number', '');

?><!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($meta_description) ?>">
    <title><?= htmlspecialchars($meta_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0d2744;
            --accent:  #f0a500;
        }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }

        /* Navbar */
        .navbar-brand { font-weight: 700; color: var(--primary) !important; }
        .nav-accent    { color: var(--accent) !important; }

        /* Hero strip */
        .page-hero {
            background: linear-gradient(135deg, #0d2744 0%, #153566 100%);
            padding: 3rem 0 2rem;
            color: #fff;
        }
        .page-hero h1 { font-weight: 700; }

        /* Content */
        .page-body { padding: 3rem 0 4rem; }
        .page-body h1,
        .page-body h2 { color: var(--primary); }
        .page-body h2 { font-size: 1.25rem; font-weight: 700; margin-top: 2rem; }
        .page-body h3 { font-size: 1rem; font-weight: 600; margin-top: 1.5rem; }
        .page-body a  { color: var(--accent); }

        /* Footer */
        footer { background: #0d2744; color: rgba(255,255,255,.7); padding: 2rem 0; font-size: .87rem; }
        footer a { color: rgba(255,255,255,.6); text-decoration: none; }
        footer a:hover { color: #fff; }
    </style>
</head>
<body>

<!-- ── Navbar ─────────────────────────────────────────────────────── -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-2">
    <div class="container">
        <a class="navbar-brand" href="/">
            <i class="bi bi-shield-check me-1" style="color:var(--accent)"></i>
            <?= htmlspecialchars($company_name) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto gap-2">
                <li class="nav-item"><a class="nav-link" href="/">Startseite</a></li>
                <li class="nav-item"><a class="nav-link" href="/kontakt">Kontakt</a></li>
                <li class="nav-item">
                    <a class="btn btn-sm btn-warning fw-semibold px-3" href="/#kontakt">
                        Kostenlose Beratung
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- ── Page Hero ──────────────────────────────────────────────────── -->
<div class="page-hero">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2" style="--bs-breadcrumb-divider-color:rgba(255,255,255,.4)">
                <li class="breadcrumb-item"><a href="/" style="color:rgba(255,255,255,.7)">Home</a></li>
                <li class="breadcrumb-item active text-white"><?= htmlspecialchars($page['title']) ?></li>
            </ol>
        </nav>
        <h1><?= htmlspecialchars($page['title']) ?></h1>
    </div>
</div>

<!-- ── Page Content ───────────────────────────────────────────────── -->
<main class="page-body">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="bg-white rounded-3 shadow-sm p-4 p-md-5">
                    <?= $page['content'] /* Content is admin-managed HTML */ ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- ── Footer ─────────────────────────────────────────────────────── -->
<footer>
    <div class="container text-center">
        <p class="mb-1">
            &copy; <?= date('Y') ?> <?= htmlspecialchars($company_name) ?> &nbsp;|&nbsp;
            <a href="/impressum">Impressum</a> &nbsp;|&nbsp;
            <a href="/datenschutz">Datenschutz</a> &nbsp;|&nbsp;
            <a href="/agb">AGB</a> &nbsp;|&nbsp;
            <a href="/kontakt">Kontakt</a>
        </p>
    </div>
</footer>

<?php if ($whatsapp): ?>
<!-- WhatsApp floating button -->
<a href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>" target="_blank" rel="noopener"
   style="position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;background:#25d366;color:#fff;
          border-radius:50%;width:56px;height:56px;display:flex;align-items:center;
          justify-content:center;font-size:1.6rem;box-shadow:0 4px 12px rgba(0,0,0,.3);
          text-decoration:none;" aria-label="WhatsApp">
    <i class="bi bi-whatsapp"></i>
</a>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
