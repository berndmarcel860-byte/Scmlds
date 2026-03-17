<?php
/**
 * Public Blog Index – /blog/
 * Lists all published blog posts, paginated.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

ensure_blog_table();

$page     = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 9;
$result   = get_published_blog_posts($page, $per_page);
$posts    = $result['data'];
$total    = $result['total'];
$pages    = $result['pages'];

// SEO
$site_url    = rtrim(get_setting('site_url', SITE_URL), '/');
$company     = get_setting('company_name', BRAND_NAME);
$og_image    = get_setting('og_image', $site_url . '/assets/images/og-image.jpg');
$ga_id       = get_setting('google_analytics_id', '');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Blog – <?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="Aktuelle Artikel und Tipps zu Anlagebetrug, Krypto-Betrug, Forex-Betrug und Kapitalrückholung von den Experten bei <?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?>.">
    <link rel="canonical" href="<?= htmlspecialchars($site_url . '/blog/', ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="index, follow">

    <meta property="og:type"       content="website">
    <meta property="og:url"        content="<?= htmlspecialchars($site_url . '/blog/', ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:title"      content="Blog – <?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image"      content="<?= htmlspecialchars($og_image, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:site_name"  content="<?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:locale"     content="de_DE">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8f9fa; }
        .blog-hero { background: linear-gradient(135deg, #1a237e 0%, #283593 100%); color: #fff; padding: 4rem 0 3rem; }
        .blog-card { transition: transform .2s, box-shadow .2s; }
        .blog-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.12) !important; }
        .blog-card .card-img-top { height: 200px; object-fit: cover; background: #e9ecef; }
        .blog-card .card-img-placeholder { height: 200px; background: linear-gradient(135deg, #e3f2fd, #bbdefb); display:flex; align-items:center; justify-content:center; }
        .badge-category { background: rgba(255,255,255,.15); color:#fff; }
        .navbar-back { background: rgba(255,255,255,.95); backdrop-filter: blur(8px); }
    </style>
    <?php if ($ga_id): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($ga_id, ENT_QUOTES, 'UTF-8') ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?= htmlspecialchars($ga_id, ENT_QUOTES, 'UTF-8') ?>');
    </script>
    <?php endif; ?>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-back border-bottom sticky-top">
    <div class="container">
        <a href="../" class="navbar-brand fw-bold text-primary">
            <i class="bi bi-shield-check me-2"></i><?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?>
        </a>
        <a href="../" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Zur Startseite
        </a>
    </div>
</nav>

<!-- Hero -->
<section class="blog-hero">
    <div class="container text-center">
        <h1 class="display-5 fw-bold mb-3">
            <i class="bi bi-journal-richtext me-2"></i>Blog &amp; Ratgeber
        </h1>
        <p class="lead opacity-75 mb-0">
            Aktuelle Informationen zu Anlagebetrug, Kapitalrückholung und Ihrer rechtlichen Situation
        </p>
        <p class="small opacity-50 mt-2"><?= $total ?> Artikel</p>
    </div>
</section>

<!-- Blog Posts Grid -->
<main class="container py-5">

    <?php if (empty($posts)): ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-journal fs-1 d-block mb-3 opacity-25"></i>
        <h4>Noch keine Artikel vorhanden</h4>
        <p>Schauen Sie bald wieder vorbei – wir arbeiten an neuen Inhalten für Sie.</p>
        <a href="../" class="btn btn-primary mt-2">Zur Startseite</a>
    </div>
    <?php else: ?>

    <div class="row g-4">
        <?php foreach ($posts as $post): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm blog-card">
                <?php if ($post['featured_image']): ?>
                    <img src="<?= htmlspecialchars($post['featured_image'], ENT_QUOTES, 'UTF-8') ?>"
                         class="card-img-top" alt="<?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>"
                         loading="lazy">
                <?php else: ?>
                    <div class="card-img-placeholder">
                        <i class="bi bi-journal-text fs-1 text-primary opacity-25"></i>
                    </div>
                <?php endif; ?>

                <div class="card-body d-flex flex-column">
                    <div class="text-muted small mb-2">
                        <i class="bi bi-calendar3 me-1"></i>
                        <?php
                        $date = $post['published_at'] ?: $post['created_at'];
                        echo date('d. F Y', strtotime($date));
                        ?>
                    </div>
                    <h5 class="card-title fw-bold">
                        <a href="<?= htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8') ?>"
                           class="text-decoration-none text-dark stretched-link">
                            <?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </h5>
                    <?php if ($post['excerpt']): ?>
                    <p class="card-text text-muted small flex-grow-1">
                        <?= htmlspecialchars(mb_substr($post['excerpt'], 0, 120), ENT_QUOTES, 'UTF-8') ?>…
                    </p>
                    <?php endif; ?>
                    <div class="mt-3">
                        <span class="badge bg-primary bg-opacity-10 text-primary small">Weiterlesen →</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <nav class="mt-5 d-flex justify-content-center">
        <ul class="pagination">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>

    <?php endif; ?>

</main>

<!-- Footer -->
<footer class="bg-dark text-white py-4 mt-5">
    <div class="container text-center">
        <p class="mb-1 small">&copy; <?= date('Y') ?> <?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?> – Alle Rechte vorbehalten</p>
        <a href="../" class="text-white-50 small">Startseite</a>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
