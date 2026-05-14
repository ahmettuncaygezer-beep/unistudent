<?php
/**
 * Database Installation & Migration Script
 * Migrates existing blog HTML files into MySQL database
 * Run once: http://localhost/unistudent/admin/api/install.php
 */

require_once 'database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>ÜniBütçe - Veritabanı Kurulumu</h2>";

// Step 1: Create tables
try {
    ensureTables();
    echo "<p>✅ Tablolar oluşturuldu.</p>";
}
catch (Exception $e) {
    echo "<p>❌ Tablo hatası: " . $e->getMessage() . "</p>";
    exit;
}

// Step 2: Migrate blog posts from HTML files
$blogDir = realpath(__DIR__ . '/../../blog/');
if (!$blogDir || !is_dir($blogDir)) {
    echo "<p>⚠️ Blog dizini bulunamadı: $blogDir</p>";
    exit;
}

$files = glob($blogDir . '/*.html');
$db = getDB();
$migrated = 0;
$skipped = 0;

foreach ($files as $file) {
    $html = file_get_contents($file);
    $filename = basename($file);
    $slug = $filename; // e.g. "kyk-mi-ev-mi-2026.html"

    // Check if already exists
    $stmt = $db->prepare("SELECT id FROM blogs WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetch()) {
        echo "<p>⏭️ Zaten var: $slug</p>";
        $skipped++;
        continue;
    }

    // Extract title
    $title = '';
    if (preg_match('/<title>(.*?)<\/title>/s', $html, $m)) {
        $title = trim(str_replace(' — ÜniBütçe Blog', '', $m[1]));
    }
    if (empty($title) && preg_match('/<h1>(.*?)<\/h1>/s', $html, $m)) {
        $title = strip_tags(trim($m[1]));
    }

    // Extract meta description
    $description = '';
    if (preg_match('/<meta name="description" content="(.*?)"/s', $html, $m)) {
        $description = trim($m[1]);
    }

    // Extract keywords
    $keywords = '';
    if (preg_match('/<meta name="keywords" content="(.*?)"/s', $html, $m)) {
        $keywords = trim($m[1]);
    }

    // Extract cover image
    $coverImage = '';
    if (preg_match('/src="(.*?)"[^>]*class="blog-cover-image"/s', $html, $m)) {
        $coverImage = $m[1];
    }
    elseif (preg_match('/class="blog-cover-image"[^>]*src="(.*?)"/s', $html, $m)) {
        $coverImage = $m[1];
    }
    // Normalize path (../assets -> assets)
    $coverImage = str_replace('../', '', $coverImage);

    // Extract tag
    $tag = '📝 Blog Yazısı';
    if (preg_match('/<div class="blog-article-tag">(.*?)<\/div>/s', $html, $m)) {
        $tag = strip_tags(trim($m[1]));
    }

    // Extract main content (inside <article> after header)
    $content = '';
    // Get everything between header closing and blog-cta div
    if (preg_match('/<\/header>(.*?)(<div class="blog-cta">|<\/article>)/s', $html, $m)) {
        $content = trim($m[1]);
        // Remove cover image from content (it's stored separately)
        $content = preg_replace('/<img[^>]*class="blog-cover-image"[^>]*>/s', '', $content);
        $content = trim($content);
    }

    // Extract date from meta
    $createdAt = date('Y-m-d H:i:s', filemtime($file));
    if (preg_match('/🗓️\s*(.*?)<\/span>/s', $html, $m)) {
        $dateStr = trim($m[1]);
        // Try to parse "Şubat 2026" or "February 2026"
        $months = [
            'Ocak' => '01', 'Şubat' => '02', 'Mart' => '03', 'Nisan' => '04',
            'Mayıs' => '05', 'Haziran' => '06', 'Temmuz' => '07', 'Ağustos' => '08',
            'Eylül' => '09', 'Ekim' => '10', 'Kasım' => '11', 'Aralık' => '12',
            'January' => '01', 'February' => '02', 'March' => '03', 'April' => '04',
            'May' => '05', 'June' => '06', 'July' => '07', 'August' => '08',
            'September' => '09', 'October' => '10', 'November' => '11', 'December' => '12'
        ];
        foreach ($months as $name => $num) {
            if (strpos($dateStr, $name) !== false) {
                preg_match('/(\d{4})/', $dateStr, $yearM);
                $year = $yearM[1] ?? date('Y');
                $createdAt = "$year-$num-15 12:00:00";
                break;
            }
        }
    }

    // Insert into database
    $stmt = $db->prepare("INSERT INTO blogs (title, slug, description, keywords, content, cover_image, tag, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $slug, $description, $keywords, $content, $coverImage, $tag, $createdAt]);

    $migrated++;
    echo "<p>✅ Aktarıldı: <strong>$title</strong> ($slug)</p>";
}

echo "<hr>";
echo "<p><strong>Sonuç:</strong> $migrated yazı aktarıldı, $skipped zaten mevcuttu.</p>";
echo "<p><a href='../blogs.php'>Admin Panel'e Dön →</a></p>";
