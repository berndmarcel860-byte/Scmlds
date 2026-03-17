<?php
/**
 * sitemap.xml
 * Dynamically generated XML sitemap.
 * Served at /sitemap.xml via .htaccess rewrite.
 */
require_once __DIR__ . '/config/config.php';

$base = rtrim(SITE_URL, '/');

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
</urlset>
