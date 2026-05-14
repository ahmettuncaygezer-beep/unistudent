<?php
session_start();
require_once __DIR__ . '/admin/api/database.php';

// Blog yazılarını DB'den al
$posts = [];
try {
    ensureTables();
    $db    = getDB();
    $stmt  = $db->query("SELECT id, title, slug, description, cover_image, tag, created_at
                         FROM blogs
                         WHERE (status IS NULL OR status = 'published')
                         ORDER BY created_at DESC");
    $posts = $stmt->fetchAll();
} catch (Exception $e) {
    // DB yoksa HTML dosyalarından fallback
}

// DB boşsa HTML dosyalarından oku
if (empty($posts)) {
    $blogDir = __DIR__ . '/blog/';
    foreach (glob($blogDir . '*.html') as $file) {
        $html     = file_get_contents($file);
        $filename = basename($file);

        $title = '';
        if (preg_match('/<title>(.*?)<\/title>/s', $html, $m))
            $title = trim(str_replace([' — ÜniBütçe Blog', ' - ÜniBütçe Blog'], '', html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')));

        $desc = '';
        if (preg_match('/<meta name="description" content="(.*?)"/s', $html, $m))
            $desc = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));

        $cover = '';
        if (preg_match('/<img[^>]+class="blog-cover-image"[^>]*src="([^"]+)"/s', $html, $m))
            $cover = ltrim(str_replace('../', '', $m[1]), '/');
        elseif (preg_match('/<img[^>]*src="(assets\/images\/blog\/[^"]+)"/s', $html, $m))
            $cover = $m[1];

        $tag = 'Blog';
        if (preg_match('/<div[^>]+class="blog-article-tag"[^>]*>(.*?)<\/div>/s', $html, $m))
            $tag = trim(strip_tags($m[1])) ?: 'Blog';

        $posts[] = [
            'slug'        => $filename,
            'title'       => $title,
            'description' => $desc,
            'cover_image' => $cover,
            'tag'         => $tag,
            'created_at'  => date('Y-m-d H:i:s', filemtime($file)),
        ];
    }
    usort($posts, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
}

// Türkçe ay isimleri
$trMonths = [1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',
             7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık'];

function readTime(string $text): int {
    $words = str_word_count(strip_tags($text));
    return max(3, (int)round($words / 200));
}

// Kartlar için gradient ve emoji paleti
$gradients = [
    ['#6c5ce7','#a29bfe'], ['#0984e3','#74b9ff'], ['#00b894','#55efc4'],
    ['#e17055','#fab1a0'], ['#fdcb6e','#e17055'], ['#d63031','#ff7675'],
    ['#e84393','#fd79a8'], ['#636e72','#b2bec3']
];
$emojis = ['📊','💡','🏙️','🎓','📝','💰','🍽️','⚖️'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Üniversite öğrencileri için bütçe rehberleri, şehir maliyet analizleri, KYK karşılaştırmaları ve tasarruf ipuçları.">
    <meta name="theme-color" content="#050510">
    <link rel="canonical" href="https://example.com/unistudent/blog.php">

    <!-- Open Graph -->
    <meta property="og:type" content="blog">
    <meta property="og:url" content="https://example.com/unistudent/blog.php">
    <meta property="og:title" content="Blog — ÜniBütçe | Öğrenci Yaşam Rehberi 2026">
    <meta property="og:description" content="Üniversite öğrencileri için bütçe rehberleri, şehir maliyet analizleri, ve tasarruf ipuçları.">
    <meta property="og:image" content="https://example.com/unistudent/assets/og-image.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://example.com/unistudent/blog.php">
    <meta property="twitter:title" content="Blog — ÜniBütçe">
    <meta property="twitter:description" content="Üniversite öğrencileri için bütçe rehberleri ve tasarruf ipuçları.">
    <meta property="twitter:image" content="https://example.com/unistudent/assets/og-image.jpg">

    <title>Blog — ÜniBütçe | Öğrenci Yaşam Rehberi 2026</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── Blog-specific overrides ── */
        .blog-hero-section {
            min-height: 52vh;
            background: var(--gradient-hero);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 120px 0 70px;
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        .blog-hero-section::before {
            content: '';
            position: absolute;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(168,85,247,0.12), transparent 70%);
            top: -200px; right: -100px;
            pointer-events: none;
        }
        .blog-hero-section::after {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(79,140,255,0.1), transparent 70%);
            bottom: -150px; left: -100px;
            pointer-events: none;
        }
        .blog-hero-section .container { position: relative; z-index:1; }
        .blog-count-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            background: rgba(0,229,255,0.08);
            border: 1px solid rgba(0,229,255,0.2);
            border-radius: 999px;
            font-size: 0.85rem;
            color: var(--accent-cyan);
            margin-top: 20px;
            font-weight: 500;
        }

        /* ── Blog Grid & Cards ── */
        .blog-section {
            padding: 80px 0 100px;
            background: var(--bg-secondary);
        }
        .blog-posts-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 28px;
            margin-top: 48px;
        }
        @media (max-width: 1024px) { .blog-posts-grid { grid-template-columns: repeat(2,1fr); } }
        @media (max-width: 640px)  { .blog-posts-grid { grid-template-columns: 1fr; } }

        .blog-post-card {
            background: rgba(20,20,50,0.6);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
            text-decoration: none;
            color: inherit;
            backdrop-filter: blur(12px);
        }
        .blog-post-card:hover {
            transform: translateY(-6px);
            border-color: rgba(79,140,255,0.22);
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
            color: inherit;
            text-decoration: none;
        }
        .blog-post-card:visited { color: inherit; }

        .card-cover {
            width: 100%;
            height: 190px;
            overflow: hidden;
            flex-shrink: 0;
            position: relative;
        }
        .card-cover img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .blog-post-card:hover .card-cover img { transform: scale(1.06); }

        .card-cover-gradient {
            width: 100%; height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.2rem;
        }

        .card-body {
            padding: 1.4rem 1.5rem;
            display: flex;
            flex-direction: column;
            flex: 1;
            gap: 10px;
        }
        .card-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--accent-cyan);
            background: rgba(0,229,255,0.08);
            border: 1px solid rgba(0,229,255,0.15);
            border-radius: 999px;
            padding: 3px 10px;
            width: fit-content;
        }
        .card-title {
            font-family: var(--font-display);
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1.45;
            color: var(--text-primary);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .card-desc {
            font-size: 0.865rem;
            color: var(--text-secondary);
            line-height: 1.65;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.78rem;
            color: var(--text-muted);
            padding-top: 12px;
            border-top: 1px solid rgba(255,255,255,0.05);
            margin-top: auto;
        }
        .card-footer span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .card-read-more {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--accent-blue);
            display: flex;
            align-items: center;
            gap: 4px;
            transition: gap 0.2s ease;
        }
        .blog-post-card:hover .card-read-more { gap: 8px; }

        /* Empty state */
        .blog-empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 5rem 2rem;
            color: var(--text-muted);
        }
        .blog-empty i { font-size: 3rem; margin-bottom: 1rem; display: block; }

        /* Footer */
        .site-footer {
            background: var(--bg-primary);
            padding: 70px 0 36px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        .footer-inner {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 48px;
            margin-bottom: 48px;
        }
        @media (max-width: 768px) { .footer-inner { grid-template-columns: 1fr; } }
        .footer-brand-logo {
            display: flex; align-items: center; gap: 10px;
            font-family: var(--font-display);
            font-size: 1.15rem; font-weight: 700;
            color: var(--text-primary); margin-bottom: 14px;
        }
        .footer-brand-logo .logo-dot {
            width: 32px; height: 32px;
            background: var(--gradient-blue);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
        }
        .footer-brand-desc {
            font-size: 0.875rem;
            color: var(--text-secondary);
            line-height: 1.7;
            max-width: 320px;
        }
        .footer-col-title {
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            margin-bottom: 16px;
        }
        .footer-col-links { list-style: none; display: flex; flex-direction: column; gap: 10px; }
        .footer-col-links a {
            font-size: 0.875rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.2s ease;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .footer-col-links a:hover { color: var(--accent-blue); }
        .footer-divider { border: none; border-top: 1px solid rgba(255,255,255,0.05); margin: 0; }
        .footer-bottom-bar {
            padding-top: 28px;
            text-align: center;
            font-size: 0.82rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <!-- ═══ NAVIGATION ═══ -->
    <?php require_once __DIR__ . '/includes/public_mega_nav.php'; ?>

    <!-- ═══ BLOG HERO ═══ -->
    <section class="blog-hero-section">
        <div class="container">
            <div class="hero-badge" style="margin:0 auto 24px;">
                <i class="fa-solid fa-book-open"></i> ÜniBütçe Blog
            </div>
            <h1 class="hero-title">
                Öğrenci Yaşam<br>
                <span class="text-gradient">Rehberi 2026</span>
            </h1>
            <p class="hero-description" style="margin:20px auto 0;">
                Şehir maliyet analizleri, KYK karşılaştırmaları, tasarruf rehberleri ve
                öğrenci hayatına dair her şey — veriye dayalı, güncel içerikler.
            </p>
            <div class="blog-count-badge">
                <i class="fa-solid fa-newspaper"></i>
                <?php echo count($posts); ?> makale yayında
            </div>
        </div>
    </section>

    <!-- ═══ BLOG POSTS ═══ -->
    <section class="blog-section">
        <div class="container">
            <div class="section-badge">
                <i class="fa-solid fa-fire"></i> Öne Çıkan Yazılar
            </div>
            <h2 class="section-title">Güncel <span class="text-gradient">Rehberler</span></h2>

            <div class="blog-posts-grid">
<?php
foreach ($posts as $post):
    $slug  = htmlspecialchars($post['slug']);
    $title = htmlspecialchars($post['title'] ?: 'Blog Yazısı');
    $desc  = htmlspecialchars($post['description'] ?? '');
    $tag   = strip_tags($post['tag'] ?? 'Blog');
    $cover = $post['cover_image'] ?? '';

    if ($cover && !file_exists(__DIR__ . '/' . $cover)) $cover = '';

    $ts      = strtotime($post['created_at']);
    $dateStr = ($trMonths[(int)date('n', $ts)] ?? date('F', $ts)) . ' ' . date('Y', $ts);

    $idx   = abs(crc32($post['slug'] ?? '')) % count($gradients);
    $grad  = $gradients[$idx];
    $emoji = $emojis[$idx];
    $rt    = isset($post['content']) ? readTime($post['content']) : 5;
    $href  = 'blog/' . $slug;
?>
                <a href="<?php echo $href; ?>" class="blog-post-card">
                    <!-- Cover -->
                    <div class="card-cover">
                        <?php if ($cover): ?>
                        <img src="<?php echo htmlspecialchars($cover); ?>"
                             alt="<?php echo $title; ?>"
                             loading="lazy"
                             onerror="this.parentNode.innerHTML='<div class=\'card-cover-gradient\' style=\'background:linear-gradient(135deg,<?php echo $grad[0]; ?>,<?php echo $grad[1]; ?>)\'><?php echo $emoji; ?></div>'">
                        <?php else: ?>
                        <div class="card-cover-gradient" style="background:linear-gradient(135deg,<?php echo $grad[0]; ?>,<?php echo $grad[1]; ?>)">
                            <?php echo $emoji; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <!-- Body -->
                    <div class="card-body">
                        <span class="card-tag">
                            <i class="fa-solid fa-tag"></i>
                            <?php echo $tag; ?>
                        </span>
                        <h3 class="card-title"><?php echo $title; ?></h3>
                        <p class="card-desc"><?php echo $desc; ?></p>
                        <div class="card-footer">
                            <span>
                                <i class="fa-regular fa-calendar"></i>
                                <?php echo $dateStr; ?>
                            </span>
                            <span class="card-read-more">
                                <?php echo $rt; ?> dk okuma
                                <i class="fa-solid fa-arrow-right"></i>
                            </span>
                        </div>
                    </div>
                </a>
<?php endforeach; ?>

<?php if (empty($posts)): ?>
                <div class="blog-empty">
                    <i class="fa-regular fa-file-lines"></i>
                    <p>Henüz blog yazısı eklenmemiş.</p>
                </div>
<?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ═══ FOOTER ═══ -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-inner">
                <div>
                    <div class="footer-brand-logo" style="display:flex; align-items:center; gap:10px;">
                        <svg class="pub-nav-logo-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:28px; height:28px;">
                            <defs>
                                <filter id="pub-logo-glow-blog" x="-30%" y="-30%" width="160%" height="160%">
                                    <feGaussianBlur stdDeviation="3.5" result="blur"/>
                                    <feComponentTransfer in="blur" result="glow1"><feFuncA type="linear" slope="1.5"/></feComponentTransfer>
                                    <feMerge><feMergeNode in="glow1"/><feMergeNode in="SourceGraphic"/></feMerge>
                                </filter>
                            </defs>
                            <path d="M50 25 L15 40 L50 55 L85 40 Z" fill="#FFFFFF" opacity="0.95"/>
                            <path d="M30 46 L30 65 Q50 80 70 65 L70 46" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round" fill="none" opacity="0.8"/>
                            <path d="M85 40 L85 65" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round" opacity="0.8"/>
                            <circle cx="85" cy="68" r="4" fill="#6FFF00" filter="url(#pub-logo-glow-blog)"/>
                            <path d="M10 80 L35 55 L55 70 L95 25" stroke="#6FFF00" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" filter="url(#pub-logo-glow-blog)"/>
                            <path d="M75 25 L95 25 L95 45" stroke="#6FFF00" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" filter="url(#pub-logo-glow-blog)"/>
                        </svg>
                        ÜniBütçe
                    </div>
                    <p class="footer-brand-desc">
                        Üniversite öğrencilerinin finansal farkındalığını artırmak için
                        tasarlanmış yapay zeka destekli platform.
                    </p>
                </div>
                <div>
                    <div class="footer-col-title">Hızlı Erişim</div>
                    <ul class="footer-col-links">
                        <li><a href="index.php#budget">Bütçe Hesaplayıcı</a></li>
                        <li><a href="index.php#cities">Şehir Karşılaştırma</a></li>
                        <li><a href="index.php#kyk">KYK Simülatörü</a></li>
                        <li><a href="blog.php">Blog</a></li>
                    </ul>
                </div>
                <div>
                    <div class="footer-col-title">Popüler Yazılar</div>
                    <ul class="footer-col-links">
                        <?php foreach (array_slice($posts, 0, 4) as $fp): ?>
                        <li><a href="blog/<?php echo htmlspecialchars($fp['slug']); ?>"><?php echo htmlspecialchars($fp['title']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <hr class="footer-divider">
            <div class="footer-bottom-bar">
                <p>© 2026 ÜniBütçe — Öğrenciler için, öğrenciler tarafından ❤️ ile yapıldı.</p>
            </div>
        </div>
    </footer>

    <script>
        document.getElementById('hamburger')?.addEventListener('click', function () {
            this.classList.toggle('active');
            document.getElementById('navLinks').classList.toggle('open');
        });

        // Navbar scroll efekti
        window.addEventListener('scroll', () => {
            document.getElementById('navbar')?.classList.toggle('scrolled', window.scrollY > 20);
        });
    </script>
</body>
</html>
