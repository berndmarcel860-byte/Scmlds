<?php
/**
 * sitemap.xml
 * Dynamically generated XML sitemap.
 * Served at /sitemap.xml via .htaccess rewrite.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$base = rtrim(get_setting('site_url', SITE_URL), '/');

// Fetch published blog posts
$blog_posts = [];
try {
    ensure_blog_table();
    $pdo  = db_connect();
    $stmt = $pdo->query(
        "SELECT slug, updated_at, published_at FROM blog_posts
         WHERE status = 'published' ORDER BY COALESCE(published_at, created_at) DESC"
    );
    $blog_posts = $stmt->fetchAll();
} catch (Exception $e) {
    // Table may not exist yet on first run
}

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Home / Landing Page – all sections are on this single page -->
    <url>
        <loc><?= htmlspecialchars($base . '/', ENT_XML1, 'UTF-8') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>
    <!-- Blog index -->
    <url>
        <loc><?= htmlspecialchars($base . '/blog/', ENT_XML1, 'UTF-8') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
<?php foreach ($blog_posts as $post): ?>
    <url>
        <loc><?= htmlspecialchars($base . '/blog/' . $post['slug'], ENT_XML1, 'UTF-8') ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($post['updated_at'] ?: $post['published_at'])) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
<?php endforeach; ?>
</urlset>
