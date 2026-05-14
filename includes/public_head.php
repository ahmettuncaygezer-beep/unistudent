<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/csrf.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$__csrf = csrf_token();
$__title = $__title ?? 'ÜniBütçe';
$__desc  = $__desc  ?? 'Türk üniversite öğrencileri için bütçe ve finans platformu.';
$loggedIn = !empty($_SESSION['user_id']);
?><!DOCTYPE html>
<html lang="tr" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="<?= htmlspecialchars($__desc, ENT_QUOTES) ?>">
    <meta name="csrf-token" content="<?= htmlspecialchars($__csrf, ENT_QUOTES) ?>">
    <title><?= htmlspecialchars($__title, ENT_QUOTES) ?> — ÜniBütçe</title>
    <link rel="icon" type="image/png" href="assets/icon-192x192.png">
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/ui-helpers.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/premium.css?v=<?= time() ?>">
    <script>window.CSRF_TOKEN = <?= json_encode($__csrf) ?>;</script>
    <script src="js/utils.js?v=<?= time() ?>" defer></script>
    <script src="js/premium-ui.js?v=<?= time() ?>" defer></script>
    <script src="js/command-palette.js?v=<?= time() ?>" defer></script>
    <script src="js/tab-sync.js?v=<?= time() ?>" defer></script>
    <style>
        .nav-brand-img { max-height: 32px; width: auto; }
        .navbar { position: fixed; top: 0; left: 0; right: 0; z-index: 100; backdrop-filter: blur(14px); background: rgba(8,10,20,.72); border-bottom: 1px solid rgba(255,255,255,.06); }
        .navbar .container { max-width: 1200px; margin: 0 auto; padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .nav-logo { color: #fff; text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .nav-links { list-style: none; display: flex; gap: 18px; margin: 0; padding: 0; align-items: center; flex-wrap: wrap; }
        .nav-links a { color: rgba(255,255,255,.8); text-decoration: none; font-size: .95rem; }
        .nav-links a:hover { color: #fff; }
        .nav-links .nav-cta { background: linear-gradient(135deg,#0088ff,#7d5cff); color: #fff; padding: 8px 16px; border-radius: 999px; font-weight: 600; }
        .ub-page { max-width: 920px; margin: 0 auto; padding: 120px 24px 80px; color: var(--text-primary, #fff); }
        .ub-page h1 { font-size: clamp(2rem, 5vw, 3rem); margin-bottom: 1rem; }
        .ub-page h2 { margin-top: 2.5rem; font-size: 1.5rem; color: var(--accent-cyan, #0088ff); }
        .ub-page .ub-lead { font-size: 1.1rem; opacity: .75; max-width: 640px; line-height: 1.6; }
        .ub-card { background: rgba(16,18,30,.6); border: 1px solid rgba(125,92,255,.15); border-radius: 16px; padding: 20px 22px; margin: 16px 0; backdrop-filter: blur(12px); }
        .ub-pill { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: .75rem; font-weight: 600; margin-right: 8px; }
        .ub-pill-done     { background: rgba(57,255,20,.15); color: #39ff14; border: 1px solid rgba(57,255,20,.3); }
        .ub-pill-progress { background: rgba(255,145,0,.15); color: #ff9100; border: 1px solid rgba(255,145,0,.3); }
        .ub-pill-planned  { background: rgba(125,92,255,.15); color: #7d5cff; border: 1px solid rgba(125,92,255,.3); }
        .ub-danger-btn { background: linear-gradient(135deg,#ff3b30,#ff5e7e); color: #fff; border: 0; padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .ub-status-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,.06); }
        .ub-status-row:last-child { border-bottom: 0; }
        .ub-status-ok { color: #39ff14; } .ub-status-bad { color: #ff3b30; }
    </style>
</head>
<body>
    <nav class="navbar scrolled" id="navbar">
        <div class="container">
            <a href="index.php" class="nav-logo" style="display:flex; align-items:center; gap:10px;">
                <svg class="pub-nav-logo-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:28px; height:28px;">
                    <defs>
                        <filter id="pub-logo-glow-head" x="-30%" y="-30%" width="160%" height="160%">
                            <feGaussianBlur stdDeviation="3.5" result="blur"/>
                            <feComponentTransfer in="blur" result="glow1"><feFuncA type="linear" slope="1.5"/></feComponentTransfer>
                            <feMerge><feMergeNode in="glow1"/><feMergeNode in="SourceGraphic"/></feMerge>
                        </filter>
                    </defs>
                    <path d="M50 25 L15 40 L50 55 L85 40 Z" fill="#FFFFFF" opacity="0.95"/>
                    <path d="M30 46 L30 65 Q50 80 70 65 L70 46" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round" fill="none" opacity="0.8"/>
                    <path d="M85 40 L85 65" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round" opacity="0.8"/>
                    <circle cx="85" cy="68" r="4" fill="#6FFF00" filter="url(#pub-logo-glow-head)"/>
                    <path d="M10 80 L35 55 L55 70 L95 25" stroke="#6FFF00" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" filter="url(#pub-logo-glow-head)"/>
                    <path d="M75 25 L95 25 L95 45" stroke="#6FFF00" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" filter="url(#pub-logo-glow-head)"/>
                </svg>
                <span class="nav-brand-text" style="font-weight:800; font-size:1.15rem;">ÜniBütçe</span>
            </a>
            <ul class="nav-links">
                <li><a href="index.php">Ana Sayfa</a></li>
                <li><a href="tools/index.php">Hesaplayıcılar</a></li>
                <li><a href="blog.php">Blog</a></li>
                <li><a href="roadmap.php">Yol Haritası</a></li>
                <li><a href="changelog.php">Changelog</a></li>
                <?php if ($loggedIn): ?>
                    <li><a href="user_dashboard.php" class="nav-cta">Panel →</a></li>
                <?php else: ?>
                    <li><a href="index.php#hero" class="nav-cta">Giriş / Kayıt</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    <main class="ub-page">
