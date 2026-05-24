<?php
require_once __DIR__ . '/database.php';

class BlogManager
{
    private $blogDir;

    public function __construct()
    {
        $this->blogDir = __DIR__ . '/../../blog/';
        // Ensure tables exist on first use
        ensureTables();
    }

    /**
     * Get all blogs (from database)
     */
    public function getBlogs()
    {
        $db   = getDB();
        $stmt = $db->query("SELECT id, title, slug, cover_image, tag, created_at, updated_at FROM blogs ORDER BY updated_at DESC");
        $rows = $stmt->fetchAll();

        // DB boşsa HTML dosyalardan oku
        if (empty($rows)) {
            $files = glob($this->blogDir . '*.html');
            $blogs = [];
            foreach ($files as $file) {
                $html  = file_get_contents($file);
                $title = '';
                if (preg_match('/<title>(.*?)<\/title>/s', $html, $m))
                    $title = trim(str_replace([' — ÜniBütçe Blog', ' - ÜniBütçe Blog'], '', html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')));
                $cover = '';
                if (preg_match('/<img[^>]+class="blog-cover-image"[^>]*src="([^"]+)"/s', $html, $m))
                    $cover = ltrim(str_replace('../', '', $m[1]), '/');
                $blogs[] = [
                    'id'       => 0,
                    'filename' => basename($file),
                    'title'    => $title ?: basename($file),
                    'cover'    => $cover,
                    'tag'      => '📝 Blog',
                    'modified' => filemtime($file),
                ];
            }
            usort($blogs, fn($a, $b) => $b['modified'] - $a['modified']);
            return $blogs;
        }

        $blogs = [];
        foreach ($rows as $row) {
            $blogs[] = [
                'id'       => $row['id'],
                'filename' => $row['slug'],
                'title'    => $row['title'],
                'cover'    => $row['cover_image'],
                'tag'      => $row['tag'],
                'modified' => strtotime($row['updated_at']),
            ];
        }
        return $blogs;
    }

    /**
     * Get blog content by slug/filename (from database)
     */
    public function getBlogContent($filename)
    {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM blogs WHERE slug = ?");
        $stmt->execute([$filename]);
        $row = $stmt->fetch();

        if (!$row)
            return null;

        return [
            'id' => $row['id'],
            'title' => $row['title'],
            'description' => $row['description'] ?? '',
            'keywords' => $row['keywords'] ?? '',
            'content' => $row['content'] ?? '',
            'cover_image' => $row['cover_image'] ?? '',
            'tag' => $row['tag'] ?? '📝 Blog Yazısı'
        ];
    }

    /**
     * Save (create or update) a blog post
     * Also generates the static HTML file for public viewing
     */
    public function saveBlog($data)
    {
        $db = getDB();

        $title = $data['title'];
        $filename = $data['filename'];
        $description = $data['description'] ?? '';
        $keywords = $data['keywords'] ?? '';
        $bodyContent = $data['content'] ?? '';
        $coverImage = $data['cover_image'] ?? '';
        $tag = $data['tag'] ?? '📝 Blog Yazısı';
        $status = in_array($data['status'] ?? 'published', ['draft', 'published'], true)
            ? $data['status'] : 'published';

        // Cleanup filename
        if (substr($filename, -5) !== '.html') {
            $filename .= '.html';
        }
        $slug = basename($filename);

        // Check if exists
        $stmt = $db->prepare("SELECT id FROM blogs WHERE slug = ?");
        $stmt->execute([$slug]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $db->prepare("UPDATE blogs SET title=?, description=?, keywords=?, content=?, cover_image=?, tag=?, status=?, updated_at=NOW() WHERE slug=?");
            $stmt->execute([$title, $description, $keywords, $bodyContent, $coverImage, $tag, $status, $slug]);
        }
        else {
            $stmt = $db->prepare("INSERT INTO blogs (title, slug, description, keywords, content, cover_image, tag, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $slug, $description, $keywords, $bodyContent, $coverImage, $tag, $status]);
        }

        // Yalnızca published ise statik HTML üret (draft'lar sızdırılmaz)
        if ($status === 'published') {
            $this->generateHtmlFile($slug, $title, $description, $keywords, $bodyContent, $coverImage);
        } else {
            $draftFile = $this->blogDir . $slug;
            if (file_exists($draftFile)) @unlink($draftFile);
        }

        return true;
    }

    /**
     * Delete a blog post
     */
    public function deleteBlog($filename)
    {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM blogs WHERE slug = ?");
        $stmt->execute([$filename]);

        // Also delete the HTML file
        $path = $this->blogDir . $filename;
        if (file_exists($path)) {
            unlink($path);
        }

        return true;
    }

    /**
     * Generate static HTML file for public viewing
     */
    private function generateHtmlFile($slug, $title, $description, $keywords, $bodyContent, $coverImage)
    {
        $coverHtml = '';
        if ($coverImage) {
            // Blog HTML files live in /blog/ subdirectory, so prepend ../ for root-relative paths
            $coverSrc = $coverImage;
            if (strncmp($coverSrc, '../', 3) !== 0 && strncmp($coverSrc, 'http', 4) !== 0) {
                $coverSrc = '../' . $coverSrc;
            }
            $coverHtml = '<img src="' . htmlspecialchars($coverSrc) . '" class="blog-cover-image" alt="' . htmlspecialchars($title) . '" style="width:100%; border-radius:1rem; margin-bottom:2rem; object-fit: cover; max-height: 500px;">';
        }

        $html = '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="' . htmlspecialchars($description) . '">
    <meta name="keywords" content="' . htmlspecialchars($keywords) . '">
    <title>' . htmlspecialchars($title) . ' — ÜniBütçe Blog</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <nav class="navbar scrolled">
        <div class="container">
            <a href="../index.php" class="nav-logo" style="display:flex; align-items:center; gap:10px;">
                <svg class="pub-nav-logo-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 28px; height: 28px;">
                    <defs>
                        <filter id="pub-logo-glow" x="-30%" y="-30%" width="160%" height="160%">
                            <feGaussianBlur stdDeviation="3.5" result="blur"/>
                            <feComponentTransfer in="blur" result="glow1"><feFuncA type="linear" slope="1.5"/></feComponentTransfer>
                            <feMerge><feMergeNode in="glow1"/><feMergeNode in="SourceGraphic"/></feMerge>
                        </filter>
                    </defs>
                    <path d="M50 25 L15 40 L50 55 L85 40 Z" fill="#FFFFFF" opacity="0.95"/>
                    <path d="M30 46 L30 65 Q50 80 70 65 L70 46" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round" fill="none" opacity="0.8"/>
                    <path d="M85 40 L85 65" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round" opacity="0.8"/>
                    <circle cx="85" cy="68" r="4" fill="#6FFF00" filter="url(#pub-logo-glow)"/>
                    <path d="M10 80 L35 55 L55 70 L95 25" stroke="#6FFF00" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" filter="url(#pub-logo-glow)"/>
                    <path d="M75 25 L95 25 L95 45" stroke="#6FFF00" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" filter="url(#pub-logo-glow)"/>
                </svg>
                <span>ÜniBütçe</span>
            </a>
            <ul class="nav-links" id="navLinks">
                <li><a href="../index.php#budget">Bütçe</a></li>
                <li><a href="../index.php#cities">Şehirler</a></li>
                <li><a href="../blog.php" class="active">Blog</a></li>
                <li><a href="../index.php#budget" class="nav-cta">Hesapla →</a></li>
            </ul>
            <button class="hamburger" id="hamburger" aria-label="Menü"><span></span><span></span><span></span></button>
        </div>
    </nav>

    <article class="blog-article container">
        <a href="../blog.php" class="blog-back">← Blog\'a Dön</a>

        <header class="blog-article-header">
            <div class="blog-article-tag">📝 Blog Yazısı</div>
            <h1>' . htmlspecialchars($title) . '</h1>
            <div class="blog-article-meta">
                <span>🗓️ ' . date("F Y") . '</span>
                <span>⏱️ Okuma süresi hesaplanıyor...</span>
            </div>
        </header>

        ' . $coverHtml . '
        
        ' . $bodyContent . '

        <div class="blog-cta">
            <h3>📊 Bütçeni Planla</h3>
            <p>Üniversite hayatında bütçeni doğru yönetmek için hemen hesaplama yap.</p>
            <a href="../index.php#budget" class="btn btn-primary">📊 Bütçemi Hesapla</a>
        </div>
    </article>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>© 2026 ÜniBütçe — Öğrenciler için, öğrenciler tarafından ❤️ ile yapıldı.</p>
            </div>
        </div>
    </footer>
    <script>document.getElementById(\'hamburger\')?.addEventListener(\'click\', function () { this.classList.toggle(\'active\'); document.getElementById(\'navLinks\').classList.toggle(\'open\'); });</script>
</body>
</html>';

        return file_put_contents($this->blogDir . $slug, $html) !== false;
    }

    /**
     * AI-based Article Generator Stub (Phase 3 readiness)
     * Calls a hypothetical AI NLP endpoint to generate SEO optimized articles
     */
    public function generateAiContent($topic, $keywords)
    {
        // 1. In the future this will curl a GPT or Claude API directly.
        // 2. Here we mock the behavior for the admin frontend testing.
        
        $prompt = "Sen bir öğrenci yaşam koçu ve SEO uzmanısın. Şu anahtar kelimeleri kullanarak: '{$keywords}', '{$topic}' konusu hakkında 3 paragraflık okunaklı bir makale yaz. HTML formatında (h2 ve p etiketleri dahil) döndür.";
        
        // Mock response (Cyber AI Placeholder):
        $mockedResp = "<h2>{$topic} Rehberi</h2><p>Üniversite hayatının getirdiği zorlukları aşmak için <strong>{$keywords}</strong> konusunu iyi analiz etmelisiniz.</p><p>Siber güvenlik kuralları kadar kişisel bütçenizi sıkı tutarsanız, gelecekte karlı çıkarsınız. AI asistanınız olarak tavsiyem; tasarruf oranınızı maksimumda tutmanız.</p>";
        
        return [
            "success" => true,
            "generated_content" => $mockedResp,
            "seo_title" => "{$topic} Konusunda Öğrenci Rehberi",
            "seo_desc" => "Üniversite öğrencileri için " . $topic . " stratejileri ve yapay zeka analizli bütçe planlaması."
        ];
    }
}
?>
