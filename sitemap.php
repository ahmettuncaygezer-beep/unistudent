<?php
declare(strict_types=1);
/**
 * Dinamik sitemap. .htaccess ile /sitemap.xml -> /sitemap.php yönlendirilebilir
 * veya doğrudan /sitemap.php erişilebilir.
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/xml; charset=utf-8');

$base = rtrim(env('APP_URL', SITE_URL), '/');

$static = [
    ''                  => ['priority' => '1.0', 'changefreq' => 'weekly'],
    '/blog.php'         => ['priority' => '0.8', 'changefreq' => 'daily'],
    '/index.php#budget' => ['priority' => '0.9', 'changefreq' => 'weekly'],
    '/index.php#cities' => ['priority' => '0.7', 'changefreq' => 'monthly'],
];

$posts = [];
try {
    $pdo = getDB();
    $stmt = $pdo->query("
        SELECT slug, updated_at
        FROM blogs
        WHERE (status IS NULL OR status = 'published')
        ORDER BY updated_at DESC
    ");
    $posts = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('sitemap error: ' . $e->getMessage());
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($static as $path => $meta) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($base . $path, ENT_XML1) . "</loc>\n";
    echo "    <changefreq>{$meta['changefreq']}</changefreq>\n";
    echo "    <priority>{$meta['priority']}</priority>\n";
    echo "  </url>\n";
}

foreach ($posts as $p) {
    $loc = $base . '/blog/' . $p['slug'];
    $lastmod = date('c', strtotime($p['updated_at']));
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc>\n";
    echo "    <lastmod>$lastmod</lastmod>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.6</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>' . "\n";
