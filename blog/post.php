<?php
/**
 * Public Blog Post – /blog/{slug}
 * Reads the slug from the URL (via .htaccess rewrite or ?slug=).
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

ensure_blog_table();

// Slug can come via rewrite or query string
$slug = '';
if (!empty($_GET['slug'])) {
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($_GET['slug'])));
} elseif (!empty($_SERVER['REQUEST_URI'])) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $base = dirname($_SERVER['SCRIPT_NAME']); // /blog
    $rel  = ltrim(str_replace($base, '', $path), '/');
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($rel));
}

$post = $slug ? get_blog_post_by_slug($slug) : null;

if (!$post) {
    http_response_code(404);
}

$site_url    = rtrim(get_setting('site_url', SITE_URL), '/');
$company     = get_setting('company_name', BRAND_NAME);
$og_image    = $post['featured_image'] ?: get_setting('og_image', $site_url . '/assets/images/og-image.jpg');
$ga_id       = get_setting('google_analytics_id', '');

$meta_title  = $post ? ($post['meta_title'] ?: $post['title']) : '404 – Seite nicht gefunden';
$meta_desc   = $post ? ($post['meta_description'] ?: mb_substr(strip_tags($post['excerpt'] ?: $post['content']), 0, 155)) : '';
$meta_kw     = $post ? ($post['meta_keywords'] ?: '') : '';
$canonical   = $post ? $site_url . '/blog/' . $post['slug'] : $site_url . '/blog/';

$published   = $post ? (date('Y-m-d', strtotime($post['published_at'] ?: $post['created_at']))) : '';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($meta_title . ' – ' . $company, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($meta_desc, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($meta_kw): ?>
    <meta name="keywords" content="<?= htmlspecialchars($meta_kw, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="index, follow">

    <meta property="og:type"            content="article">
    <meta property="og:url"             content="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:title"           content="<?= htmlspecialchars($meta_title, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description"     content="<?= htmlspecialchars($meta_desc, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image"           content="<?= htmlspecialchars($og_image, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:site_name"       content="<?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:locale"          content="de_DE">
    <?php if ($published): ?>
    <meta property="article:published_time" content="<?= $published ?>">
    <?php endif; ?>

    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= htmlspecialchars($meta_title, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($meta_desc, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image"       content="<?= htmlspecialchars($og_image, ENT_QUOTES, 'UTF-8') ?>">

    <?php if ($post): ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BlogPosting",
        "headline": <?= json_encode($meta_title) ?>,
        "description": <?= json_encode($meta_desc) ?>,
        "image": <?= json_encode($og_image) ?>,
        "datePublished": "<?= htmlspecialchars($published, ENT_QUOTES, 'UTF-8') ?>",
        "dateModified": "<?= htmlspecialchars(date('Y-m-d', strtotime($post['updated_at'])), ENT_QUOTES, 'UTF-8') ?>",
        "author": { "@type": "Organization", "name": <?= json_encode($company) ?> },
        "publisher": {
            "@type": "Organization",
            "name": <?= json_encode($company) ?>,
            "logo": { "@type": "ImageObject", "url": <?= json_encode($site_url . '/assets/images/logo.png') ?> }
        },
        "mainEntityOfPage": { "@type": "WebPage", "@id": <?= json_encode($canonical) ?> }
    }
    </script>
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8f9fa; }
        .navbar-back { background: rgba(255,255,255,.95); backdrop-filter: blur(8px); }
        .post-hero { background: linear-gradient(135deg, #1a237e 0%, #283593 100%); color: #fff; padding: 4rem 0 3rem; }
        .post-content { font-size: 1.05rem; line-height: 1.8; color: #333; }
        .post-content h2 { font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1rem; color: #1a237e; }
        .post-content h3 { font-size: 1.2rem; font-weight: 600; margin: 1.5rem 0 .75rem; color: #283593; }
        .post-content p { margin-bottom: 1.2rem; }
        .post-content ul, .post-content ol { margin-bottom: 1.2rem; padding-left: 1.5rem; }
        .post-content blockquote { border-left: 4px solid #1a237e; padding: .75rem 1rem; background: #f0f4ff; margin: 1.5rem 0; border-radius: 0 8px 8px 0; }
        .post-content a { color: #1a237e; }
        .share-btn { min-width: 120px; }
    </style>
    <?php if ($ga_id): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($ga_id, ENT_QUOTES, 'UTF-8') ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= htmlspecialchars($ga_id, ENT_QUOTES, 'UTF-8') ?>');</script>
    <?php endif; ?>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-back border-bottom sticky-top">
    <div class="container">
        <a href="../" class="navbar-brand fw-bold text-primary">
            <i class="bi bi-shield-check me-2"></i><?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?>
        </a>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Blog
        </a>
    </div>
</nav>

<?php if (!$post): ?>
<!-- 404 -->
<div class="container py-5 text-center">
    <i class="bi bi-exclamation-circle display-3 text-muted d-block mb-3"></i>
    <h1 class="h3 fw-bold">Artikel nicht gefunden</h1>
    <p class="text-muted">Dieser Artikel existiert nicht oder wurde gelöscht.</p>
    <a href="index.php" class="btn btn-primary">Zurück zur Blog-Übersicht</a>
</div>
<?php else: ?>

<!-- Hero -->
<section class="post-hero">
    <div class="container" style="max-width:760px;">
        <div class="text-white-50 small mb-3">
            <a href="index.php" class="text-white-50">Blog</a>
            <i class="bi bi-chevron-right mx-1" style="font-size:.7rem;"></i>
            <?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <h1 class="display-6 fw-bold mb-3">
            <?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>
        </h1>
        <?php if ($post['excerpt']): ?>
        <p class="lead opacity-75 mb-3">
            <?= htmlspecialchars($post['excerpt'], ENT_QUOTES, 'UTF-8') ?>
        </p>
        <?php endif; ?>
        <div class="text-white-50 small">
            <i class="bi bi-calendar3 me-1"></i>
            <?php
            $dateStr = $post['published_at'] ?: $post['created_at'];
            echo date('d. F Y', strtotime($dateStr));
            ?>
            &nbsp;·&nbsp; <?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
</section>

<!-- Featured Image -->
<?php if ($post['featured_image']): ?>
<div class="container" style="max-width:760px;">
    <div class="mt-n4">
        <img src="<?= htmlspecialchars($post['featured_image'], ENT_QUOTES, 'UTF-8') ?>"
             alt="<?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>"
             class="img-fluid rounded shadow-sm w-100" style="max-height:420px; object-fit:cover;">
    </div>
</div>
<?php endif; ?>

<!-- Article Content -->
<main class="container py-5" style="max-width:760px;">
    <div class="post-content">
        <?= $post['content'] /* Already stored as HTML, trust admin input */ ?>
    </div>

    <!-- Share buttons -->
    <hr class="my-5">
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="fw-semibold me-2">Teilen:</span>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($canonical) ?>"
           class="btn btn-outline-primary share-btn" target="_blank" rel="noopener">
            <i class="bi bi-facebook me-1"></i>Facebook
        </a>
        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($canonical) ?>&text=<?= urlencode($meta_title) ?>"
           class="btn btn-outline-dark share-btn" target="_blank" rel="noopener">
            <i class="bi bi-twitter-x me-1"></i>Twitter/X
        </a>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($canonical) ?>"
           class="btn btn-outline-primary share-btn" target="_blank" rel="noopener">
            <i class="bi bi-linkedin me-1"></i>LinkedIn
        </a>
        <a href="whatsapp://send?text=<?= urlencode($meta_title . ' ' . $canonical) ?>"
           class="btn btn-outline-success share-btn">
            <i class="bi bi-whatsapp me-1"></i>WhatsApp
        </a>
    </div>

    <!-- CTA -->
    <div class="card border-0 shadow-sm mt-5 text-center p-4 bg-primary text-white">
        <h4 class="fw-bold mb-2">Wurden Sie Opfer von Anlagebetrug?</h4>
        <p class="mb-3 opacity-90">Nutzen Sie unsere kostenlose Erstprüfung und erfahren Sie, ob wir Ihr Kapital zurückfordern können.</p>
        <a href="../" class="btn btn-warning btn-lg fw-bold">
            <i class="bi bi-shield-check me-2"></i>Kostenlose Erstprüfung starten
        </a>
    </div>
</main>
<?php endif; ?>

<!-- Footer -->
<footer class="bg-dark text-white py-4 mt-5">
    <div class="container text-center">
        <p class="mb-1 small">&copy; <?= date('Y') ?> <?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?> – Alle Rechte vorbehalten</p>
        <a href="../" class="text-white-50 small me-3">Startseite</a>
        <a href="index.php" class="text-white-50 small">Blog</a>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
