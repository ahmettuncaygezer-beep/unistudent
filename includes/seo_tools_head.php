<?php
/**
 * SEO Tools — Sidebar Layout Head Include
 * Tool sayfaları için dashboard-tarzı sidebar layout.
 * Kullanım: $__title, $__desc, $__keywords, $__canonical, $__schema set et, sonra require et.
 */
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_system.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$auth = new AuthSystem($pdo);
$loggedIn = $auth->isLoggedIn();

// Auto-login demo user if not logged in — ONLY for public tool pages
// Pages that write user data (kyk, deposits, challenges, benchmark, telegram, discounts)
// must NOT auto-login; they already have their own isLoggedIn() guards.
if (!$loggedIn && !empty($__allowDemoLogin)) {
    try {
        $demoStmt = $pdo->prepare("SELECT id FROM users WHERE email = 'demo@unistudent.app' LIMIT 1");
        $demoStmt->execute();
        $demoUser = $demoStmt->fetch();
        if ($demoUser) {
            $_SESSION['user_id'] = (int)$demoUser['id'];
            $loggedIn = true;
        }
    } catch (Exception $e) {}
}

$__csrf   = csrf_token();
$__title  = $__title  ?? 'ÜniBütçe Araçlar';
$__desc   = $__desc   ?? 'Üniversite öğrencileri için ücretsiz hesaplama araçları.';
$__keywords = $__keywords ?? 'üniversite, öğrenci, hesaplayıcı, bütçe, burs';
$__canonical = $__canonical ?? '';
$__schema = $__schema ?? '';
$__appUrl = rtrim(env('APP_URL', SITE_URL), '/');
?><!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="<?= htmlspecialchars($__desc, ENT_QUOTES) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($__keywords, ENT_QUOTES) ?>">
    <meta name="theme-color" content="#050510">
    <meta name="csrf-token" content="<?= htmlspecialchars($__csrf, ENT_QUOTES) ?>">
    <?php if ($__canonical): ?>
    <link rel="canonical" href="<?= htmlspecialchars($__canonical, ENT_QUOTES) ?>">
    <?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($__title, ENT_QUOTES) ?> — ÜniBütçe">
    <meta property="og:description" content="<?= htmlspecialchars($__desc, ENT_QUOTES) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($__canonical, ENT_QUOTES) ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="<?= htmlspecialchars($__title, ENT_QUOTES) ?> — ÜniBütçe">
    <meta property="twitter:description" content="<?= htmlspecialchars($__desc, ENT_QUOTES) ?>">

    <?php if ($__schema): ?>
    <script type="application/ld+json"><?= $__schema ?></script>
    <?php endif; ?>

    <title><?= htmlspecialchars($__title, ENT_QUOTES) ?> — ÜniBütçe</title>

    <link rel="icon" type="image/png" href="<?= $__appUrl ?>/assets/icon-192x192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $__appUrl ?>/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= $__appUrl ?>/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= $__appUrl ?>/css/mobile.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= $__appUrl ?>/css/animations.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= $__appUrl ?>/css/premium.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= $__appUrl ?>/css/ui-helpers.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= $__appUrl ?>/tools/css/tools.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <script>window.CSRF_TOKEN = <?= json_encode($__csrf) ?>;</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="<?= $__appUrl ?>/js/animations.js?v=<?= time() ?>" defer></script>
    <script src="<?= $__appUrl ?>/js/utils.js?v=<?= time() ?>" defer></script>
    <script src="<?= $__appUrl ?>/js/premium-ui.js?v=<?= time() ?>" defer></script>
    <script src="<?= $__appUrl ?>/js/command-palette.js?v=<?= time() ?>" defer></script>
    <script src="<?= $__appUrl ?>/js/tab-sync.js?v=<?= time() ?>" defer></script>

    <style>
        /* Tool page overrides for sidebar layout */
        .tool-page {
            padding: 30px 24px 60px;
            max-width: 100%;
        }
        .tool-card {
            background: var(--glass-bg, rgba(12, 18, 40, 0.7));
            border-color: var(--glass-border, rgba(0, 136, 255, 0.15));
        }
        .tool-result {
            background: var(--glass-bg, rgba(0,136,255,0.08));
            border-color: var(--glass-border, rgba(0,136,255,0.2));
        }
        .tool-stat {
            background: var(--glass-bg, rgba(255,255,255,0.03));
            border-color: var(--glass-border, rgba(255,255,255,0.06));
        }
        .tool-info-blue { background: rgba(0,136,255,0.06); }
        .tool-info-green { background: rgba(57,255,20,0.06); }
        .tool-info-orange { background: rgba(255,145,0,0.06); }
    </style>
</head>
<body class="dashboard-body">
    <?php include __DIR__ . '/dashboard_navbar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text">
                <h1 style="font-size:1.3rem;"><?= htmlspecialchars($__title, ENT_QUOTES) ?></h1>
                <p style="margin:0; opacity:.7; font-size:.85rem;"><?= htmlspecialchars($__desc, ENT_QUOTES) ?></p>
            </div>
            <div class="topbar-actions">
                <div class="date-display">
                    <i class="fa-regular fa-calendar"></i>
                    <?= date('d F Y') ?>
                </div>
            </div>
        </header>

        <div class="profile-overhaul-container">
            <div class="tool-page">
