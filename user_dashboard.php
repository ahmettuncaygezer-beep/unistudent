<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');
require_once 'includes/db.php';
require_once 'includes/auth_system.php';
require_once 'includes/csrf.php';
$__csrf = csrf_token();

$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$currentUser = $auth->getCurrentUser();
$user_id = $_SESSION['user_id'];

// Get user gamification stats
$stmtUser = $pdo->prepare("SELECT xp_points, level FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$gameStats = $stmtUser->fetch();
$userXp = (int)($gameStats['xp_points'] ?? 0);
$userLevel = (int)($gameStats['level'] ?? 1);
$xpToNextLevel = $userLevel * 1000;
$xpPercent = min(100, max(0, ($userXp / $xpToNextLevel) * 100));

// ── Toplam gelir / gider ──────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT 
    COALESCE(SUM(CASE WHEN type='income'  THEN amount ELSE 0 END), 0) AS total_in,
    COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END), 0) AS total_out
    FROM transactions WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats    = $stmt->fetch();
$income   = (float)($stats['total_in']  ?? 0);
$expense  = (float)($stats['total_out'] ?? 0);
$balance  = $income - $expense;

// ── Son 5 işlem ───────────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY transaction_date DESC LIMIT 5");
$stmt->execute([$user_id]);
$lastTransactions = $stmt->fetchAll();

// ── Son 7 günün günlük harcaması (grafik için) ───────────────────────────
$stmt = $pdo->prepare("
    SELECT DATE(transaction_date) AS day, SUM(amount) AS total
    FROM transactions
    WHERE user_id = ? AND type='expense' AND transaction_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(transaction_date)
    ORDER BY day ASC
");
$stmt->execute([$user_id]);
$dailyRows = $stmt->fetchAll();

// Günlük veriyi JS için hazırla (son 7 gün, eksik günler 0)
$trDays  = ['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'];
$weekMap = [];
for ($i = 6; $i >= 0; $i--) {
    $key = date('Y-m-d', strtotime("-{$i} days"));
    $weekMap[$key] = 0;
}
foreach ($dailyRows as $r) {
    if (isset($weekMap[$r['day']])) {
        $weekMap[$r['day']] = (float)$r['total'];
    }
}
$chartLabels = [];
$chartValues = [];
foreach ($weekMap as $date => $val) {
    $dow = (int)date('N', strtotime($date)); // 1=Mon..7=Sun
    $chartLabels[] = $trDays[$dow - 1];
    $chartValues[] = $val;
}

// ── Sağlık skoru hesapla ─────────────────────────────────────────────────
// Kural: gelir varsa (gider/gelir) oranına göre skor, yoksa 50
$healthScore = 50;
if ($income > 0) {
    $ratio       = $expense / $income;         // 0 = çok iyi, 1+ = kötü
    $healthScore = max(0, min(100, (int)round((1 - $ratio) * 100)));
}
if ($income === 0.0 && $expense === 0.0) $healthScore = 70; // yeni kullanıcı

$scoreLabel = match(true) {
    $healthScore >= 80 => 'Mükemmel',
    $healthScore >= 60 => 'İyi',
    $healthScore >= 40 => 'Orta',
    default            => 'Dikkat!',
};

// ── Günün ipucu ───────────────────────────────────────────────────────────
$savingTips = [
    "Okul yemekhanesindeki menüyü takip et; dışarıda bir öğün parasına okulda 4 gün sağlıklı yemek yiyebilirsin.",
    "Seyahat kartını (Akbil/Kentkart) aylık abonman yap. Tek basımlara kıyasla ulaşım masrafını %70'e kadar düşürür.",
    "Dışarıdan her gün kahve almak yerine iyi bir termos edinerek aylık en az 1.500 ₺ tasarruf sağlayabilirsin.",
    "KYK Bursu/Kredisi yattığı gün ilk iş olarak kiranı ve faturalarını ayır, kalan parayla harcama planı yap.",
    "Zincir marketlerin (BİM, A101, Şok vb.) kendi markalı ürünlerini tercih etmek mutfak bütçeni yarı yarıya rahatlatır.",
    "Dijital aboneliklerde (Spotify, Netflix vb.) mutlaka öğrenci indirimlerinden faydalan veya arkadaşlarınla ortak paket al.",
    "Vize/Final dönemlerinde geceleri dışarıdan yemek söylemek yerine, önceden evde pratik atıştırmalıklar hazırla.",
    "Kıyafetlerini Dolap, Gardrops gibi 2. el uygulamalarından alarak çok daha uygun fiyata etiketli ürünler bulabilirsin.",
    "Ders notlarını her hafta fotokopiciden ciltletmek yerine, PDF üzerinden veya kütüphanede çalışmayı alışkanlık edin.",
    "MüzeKart çıkartarak sosyalleşme bütçeni yormadan Türkiye'deki yüzlerce müzeyi yıl boyunca ücretsiz gezebilirsin."
];
$randomTip = $savingTips[array_rand($savingTips)];

// ── Baş harfleri ─────────────────────────────────────────────────────────
$initials = '';
foreach (explode(' ', $currentUser['name']) as $n) {
    if (!empty($n)) $initials .= strtoupper($n[0]);
}
$initials = substr($initials, 0, 2);

// ── Başarımlar (tek sorguda) ──────────────────────────────────────────────
$stmtAch = $pdo->prepare("
    SELECT a.slug, a.title, a.description, a.icon, a.xp_reward,
           CASE WHEN ua.id IS NOT NULL THEN 1 ELSE 0 END AS earned
    FROM achievements a
    LEFT JOIN user_achievements ua ON ua.achievement_id = a.id AND ua.user_id = ?
    ORDER BY earned DESC, a.id ASC
");
$stmtAch->execute([$user_id]);
$achievements = $stmtAch->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ÜniBütçe - Kişisel öğrenci bütçenizi, gelir ve giderlerinizi yönetin.">
    <meta name="theme-color" content="#050510">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icon-192x192.png">
    <link rel="canonical" href="https://example.com/unistudent/user_dashboard.php">
    <meta name="robots" content="noindex, nofollow"> <!-- Dashboard is private -->
    <title>Genel Bakış — ÜniBütçe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/auth.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/mobile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/ui-helpers.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="csrf-token" content="<?= htmlspecialchars($__csrf, ENT_QUOTES) ?>">
    <script>window.CSRF_TOKEN = <?= json_encode($__csrf) ?>;</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="js/utils.js?v=<?php echo time(); ?>" defer></script>
    <script>
        // PWA Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/unistudent/sw.js');
            });
        }
    </script>
    <link rel="stylesheet" href="css/animations.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/premium.css?v=<?php echo time(); ?>">
    <script src="js/animations.js?v=<?php echo time(); ?>" defer></script>
    <script src="js/premium-ui.js?v=<?php echo time(); ?>" defer></script>
    <script src="js/command-palette.js?v=<?php echo time(); ?>" defer></script>
    <script src="js/tab-sync.js?v=<?php echo time(); ?>" defer></script>
</head>
<body class="dashboard-body">

    <?php include 'includes/dashboard_navbar.php'; ?>
    <?php include 'includes/onboarding_wizard.php'; ?>

    <main class="main-content">
        <div class="profile-overhaul-container">
        
        <!-- Topbar -->
        <header class="topbar">
            <div class="welcome-text">
                <h1>Merhaba, <?php echo htmlspecialchars(explode(' ', $currentUser['name'])[0]); ?> 👋</h1>
                <p>İşte finansal dünyandaki son durum.</p>
            </div>
            
            <div class="gamification-badge">
                <div class="badge-info">
                    <span class="level-label">Level <?php echo $userLevel; ?></span>
                    <span class="xp-label"><?php echo $userXp; ?> / <?php echo $xpToNextLevel; ?> XP</span>
                </div>
                <div class="xp-bar-bg">
                    <div class="xp-bar-fill" style="width: <?php echo $xpPercent; ?>%"></div>
                </div>
            </div>

            <div class="topbar-actions">
                <div class="date-display">
                    <i class="fa-regular fa-calendar"></i>
                    <?php echo date('d F Y'); ?>
                </div>
                <div class="profile-mini"><?php echo $initials; ?></div>
            </div>
        </header>

        <style>
        .gamification-badge {
            background: linear-gradient(135deg, rgba(0, 136, 255, 0.15), rgba(0, 136, 255, 0.05));
            border: 1px solid rgba(0, 136, 255, 0.25);
            border-radius: 12px;
            padding: 10px 20px;
            min-width: 200px;
            box-shadow: inset 0 0 15px rgba(0, 136, 255, 0.1), 0 4px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            gap: 8px;
            backdrop-filter: blur(10px);
        }
        .badge-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: var(--font-primary);
            font-size: 0.85rem;
        }
        .level-label {
            color: #fff;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .xp-label {
            color: var(--accent-cyan);
            font-family: var(--font-mono);
            font-weight: 600;
        }
        .xp-bar-bg {
            width: 100%;
            height: 6px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            overflow: hidden;
        }
        .xp-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #0088ff, #00F0FF);
            box-shadow: 0 0 10px rgba(0, 240, 255, 0.5);
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }
        </style>

        <!-- ═══ Premium Widget Row ═══ -->
        <div class="premium-widgets-row">
            <!-- Safe-to-Spend -->
            <div class="glass-card premium-widget safe-spend-widget">
                <div class="safe-spend-label">Bugün Güvenle Harcayabileceğin</div>
                <?php
                    $daysLeft = max(1, (int)date('t') - (int)date('j') + 1);
                    $safeAmount = $balance > 0 ? round($balance / $daysLeft) : 0;
                    $safeClass = $safeAmount > 100 ? 'safe-green' : ($safeAmount > 40 ? 'safe-yellow' : 'safe-red');
                ?>
                <div class="safe-spend-amount <?php echo $safeClass; ?>">₺<?php echo number_format((float)($safeAmount), 0, ',', '.'); ?></div>
                <div class="safe-spend-sub">Ayın kalan <?php echo $daysLeft; ?> günü için</div>
            </div>

            <!-- Financial Health Score -->
            <div class="glass-card premium-widget health-score-widget">
                <div class="health-label">Finansal Sağlık Skoru</div>
                <?php
                    $healthScore = 50;
                    if ($income > 0) {
                        $savingsRate = ($balance / $income) * 100;
                        $healthScore = min(100, max(0, (int)(50 + $savingsRate)));
                    }
                    $healthColor = $healthScore >= 70 ? '#39FF14' : ($healthScore >= 40 ? '#ff9100' : '#ff2a2a');
                ?>
                <div class="health-gauge">
                    <svg viewBox="0 0 120 120" width="110" height="110">
                        <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="10"/>
                        <circle cx="60" cy="60" r="50" fill="none" stroke="<?php echo $healthColor; ?>"
                            stroke-width="10" stroke-linecap="round"
                            stroke-dasharray="<?php echo ($healthScore / 100) * 314; ?> 314"
                            transform="rotate(-90 60 60)"
                            style="filter: drop-shadow(0 0 6px <?php echo $healthColor; ?>); transition: stroke-dasharray 1.5s ease-out;" />
                        <text x="60" y="56" text-anchor="middle" fill="<?php echo $healthColor; ?>" font-size="28" font-weight="800" font-family="var(--font-mono)"><?php echo $healthScore; ?></text>
                        <text x="60" y="74" text-anchor="middle" fill="var(--text-muted)" font-size="10">/100</text>
                    </svg>
                </div>
                <div class="health-status" style="color:<?php echo $healthColor; ?>">
                    <?php echo $healthScore >= 70 ? 'Harika!' : ($healthScore >= 40 ? 'Dikkat' : 'Tehlike'); ?>
                </div>
            </div>
        </div>

        <!-- ═══ Achievement Showcase ═══ -->
        <?php
            $earnedBadges = [];
            try {
                $badgeStmt = $pdo->prepare("
                    SELECT a.icon, a.title, a.description, ua.earned_at
                    FROM user_achievements ua
                    JOIN achievements a ON a.id = ua.achievement_id
                    WHERE ua.user_id = ?
                    ORDER BY ua.earned_at DESC LIMIT 6
                ");
                $badgeStmt->execute([$user_id]);
                $earnedBadges = $badgeStmt->fetchAll();
            } catch (Exception $e) {}

            $allBadges = [];
            try {
                $allBStmt = $pdo->query("SELECT * FROM achievements ORDER BY id ASC");
                $allBadges = $allBStmt->fetchAll();
            } catch (Exception $e) {}

            $earnedSlugs = array_column($earnedBadges, 'title');
        ?>
        <div class="glass-card achievement-showcase">
            <div class="widget-header"><h3>🏆 Başarımlar</h3></div>
            <div class="badge-grid">
                <?php foreach ($allBadges as $badge): ?>
                    <?php $isEarned = in_array($badge['title'], $earnedSlugs); ?>
                    <div class="badge-item <?php echo $isEarned ? 'earned' : 'locked'; ?>" title="<?php echo htmlspecialchars($badge['description']); ?>">
                        <span class="badge-icon"><?php echo $badge['icon']; ?></span>
                        <span class="badge-title"><?php echo htmlspecialchars($badge['title']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ═══ Harcama Isı Haritası ═══ -->
        <div class="glass-card heatmap-widget">
            <div class="widget-header"><h3>🔥 Harcama Isı Haritası (Bu Ay)</h3></div>
            <div class="heatmap-grid" id="heatmapGrid">
                <?php
                    $heatStmt = $pdo->prepare("SELECT DAY(transaction_date) as d, SUM(amount) as total FROM transactions WHERE user_id = ? AND type='expense' AND MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE()) GROUP BY DAY(transaction_date)");
                    $heatStmt->execute([$user_id]);
                    $dailySpend = [];
                    foreach ($heatStmt->fetchAll() as $r) $dailySpend[(int)$r['d']] = (float)$r['total'];
                    $maxSpend = max(1, empty($dailySpend) ? 1 : max($dailySpend));
                    $daysInMonth = (int)date('t');
                    $today = (int)date('j');

                    for ($d = 1; $d <= $daysInMonth; $d++):
                        $amount = $dailySpend[$d] ?? 0;
                        $intensity = $amount > 0 ? max(0.15, min(1, $amount / $maxSpend)) : 0;
                        $bgColor = $amount > 0 ? "rgba(255, 42, 42, {$intensity})" : 'rgba(255,255,255,0.03)';
                        $border = $d === $today ? '2px solid var(--accent-cyan)' : '1px solid rgba(255,255,255,0.05)';
                ?>
                    <div class="heatmap-cell" style="background:<?php echo $bgColor; ?>; border:<?php echo $border; ?>;" title="<?php echo $d; ?>. gün: ₺<?php echo number_format((float)($amount), 0); ?>">
                        <span class="heatmap-day"><?php echo $d; ?></span>
                    </div>
                <?php endfor; ?>
            </div>
            <div class="heatmap-legend">
                <span>Az</span>
                <div class="legend-bar"></div>
                <span>Çok</span>
            </div>
        </div>

        <style>
            .premium-widgets-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
            .premium-widget { text-align: center; padding: 32px 24px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
            .safe-spend-label { font-size: 0.95rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px; }
            .safe-spend-amount { font-family: var(--font-display); font-size: 3.5rem; font-weight: 800; line-height: 1; }
            .safe-green { color: #39FF14; text-shadow: 0 0 25px rgba(57,255,20,0.4); }
            .safe-yellow { color: #ff9100; text-shadow: 0 0 25px rgba(255,145,0,0.4); }
            .safe-red { color: #ff2a2a; text-shadow: 0 0 25px rgba(255,42,42,0.4); }
            .safe-spend-sub { font-size: 0.85rem; color: var(--text-muted); margin-top: 12px; font-weight: 500;}
            .health-label { font-size: 0.95rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 16px; text-transform: uppercase; letter-spacing: 1px; }
            .health-gauge { display: flex; justify-content: center; position: relative; margin-bottom: 10px; }
            .health-status { font-family: var(--font-display); font-weight: 800; font-size: 1.1rem; margin-top: 8px; text-transform: uppercase; letter-spacing: 1px; }

            .achievement-showcase { margin-bottom: 24px; padding: 24px; }
            .badge-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 16px; margin-top: 16px; }
            .badge-item { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 18px 12px; border-radius: 16px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); text-align: center; }
            .badge-item.earned { border-color: rgba(0,240,255,0.3); background: linear-gradient(135deg, rgba(0,240,255,0.1), rgba(0,240,255,0.02)); box-shadow: 0 8px 20px rgba(0,240,255,0.15); }
            .badge-item.locked { opacity: 0.4; filter: grayscale(100%); }
            .badge-item.earned:hover { transform: translateY(-5px) scale(1.05); box-shadow: 0 12px 25px rgba(0,240,255,0.3); border-color: var(--accent-cyan); }
            .badge-icon { font-size: 2rem; margin-bottom: 4px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3)); }
            .badge-title { font-size: 0.75rem; font-weight: 700; color: var(--text-primary); }

            .heatmap-widget { margin-bottom: 24px; padding: 24px; }
            .heatmap-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; margin-top: 16px; }
            .heatmap-cell { aspect-ratio: 1; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); }
            .heatmap-cell:hover { transform: scale(1.2); z-index: 10; box-shadow: 0 10px 20px rgba(0,0,0,0.5); border-color: var(--accent-cyan); }
            .heatmap-day { font-size: 0.75rem; color: #fff; font-family: var(--font-primary); font-weight: 600; opacity: 0.9; }
            .heatmap-legend { display: flex; align-items: center; gap: 10px; justify-content: flex-end; margin-top: 16px; font-size: 0.8rem; color: var(--text-secondary); font-weight: 500; }
            .legend-bar { width: 100px; height: 10px; border-radius: 5px; background: linear-gradient(90deg, rgba(255,255,255,0.05), #ff2a2a); }

            @media (max-width: 768px) {
                .premium-widgets-row { grid-template-columns: 1fr; }
                .badge-grid { grid-template-columns: repeat(3, 1fr); }
            }
        </style>

        <!-- Bento Grid -->
        <div class="bento-grid">

            <!-- İstatistik kartları -->
            <div class="glass-card widget-stat stat-income">
                <div class="stat-icon-bg"><i class="fa-solid fa-arrow-up-long"></i></div>
                <div class="stat-content">
                    <span class="stat-label">Toplam Gelir</span>
                    <span class="stat-value">&#8378;<?php echo number_format((float)($income), 0, ',', '.'); ?></span>
                </div>
            </div>

            <div class="glass-card widget-stat stat-expense">
                <div class="stat-icon-bg"><i class="fa-solid fa-arrow-down-long"></i></div>
                <div class="stat-content">
                    <span class="stat-label">Toplam Gider</span>
                    <span class="stat-value">&#8378;<?php echo number_format((float)($expense), 0, ',', '.'); ?></span>
                </div>
            </div>

            <div class="glass-card widget-stat stat-balance">
                <div class="stat-icon-bg"><i class="fa-solid fa-wallet"></i></div>
                <div class="stat-content">
                    <span class="stat-label">Net Bakiye</span>
                    <span class="stat-value" style="color: <?php echo $balance >= 0 ? '#00e676' : '#ff5252'; ?>">
                        <?php echo ($balance >= 0 ? '+' : ''); ?>&#8378;<?php echo number_format((float)(abs($balance)), 0, ',', '.'); ?>
                    </span>
                </div>
            </div>

            <!-- Hızlı İşlemler -->
            <div class="glass-card widget-quick-actions">
                <div class="widget-header"><h3>Hızlı İşlemler</h3></div>
                <div class="quick-actions-container">
                    <button class="quick-btn btn-income" onclick="openModal('income')">
                        <i class="fa-solid fa-plus"></i>
                        <span>Gelir Ekle</span>
                    </button>
                    <button class="quick-btn btn-expense" onclick="openModal('expense')">
                        <i class="fa-solid fa-minus"></i>
                        <span>Gider Ekle</span>
                    </button>
                </div>
            </div>

            <!-- Harcama Trendi Grafiği (Gerçek Veri) -->
            <div class="glass-card widget-chart">
                <div class="widget-header">
                    <h3>Harcama Trendi (Son 7 Gün)</h3>
                </div>
                <div class="chart-wrapper">
                    <canvas id="expenseChart"></canvas>
                </div>
            </div>

            <!-- Sağlık Skoru -->
            <div class="glass-card widget-score">
                <div class="widget-header"><h3>Bütçe Sağlığı</h3></div>
                <div class="score-container">
                    <svg class="progress-ring" width="120" height="120">
                        <circle class="progress-ring__circle-bg" stroke="rgba(255,255,255,0.05)" stroke-width="8" fill="transparent" r="52" cx="60" cy="60"/>
                        <circle class="progress-ring__circle" stroke="<?php echo $healthScore >= 60 ? '#00e676' : ($healthScore >= 40 ? '#fdcb6e' : '#ff5252'); ?>" stroke-width="8" fill="transparent" r="52" cx="60" cy="60"/>
                    </svg>
                    <div class="score-value">
                        <span class="number"><?php echo $healthScore; ?></span>
                        <span class="label"><?php echo $scoreLabel; ?></span>
                    </div>
                </div>
                <p class="score-desc">
                    <?php if ($income > 0): ?>
                        Gelirinizin %<?php echo min(100, (int)round(($expense / $income) * 100)); ?>'ini harcadınız.
                    <?php else: ?>
                        Gelir ekleyerek bütçe sağlığını takip edin.
                    <?php endif; ?>
                </p>
            </div>

            <!-- Son Aktivite -->
            <div class="glass-card widget-table">
                <div class="widget-header">
                    <h3>Son Aktivite</h3>
                    <a href="transactions.php" style="color: var(--accent-blue); text-decoration:none; font-size: 0.8rem;">Tümünü Gör</a>
                </div>
                <table class="recent-table">
                    <tbody>
                        <?php if (empty($lastTransactions)): ?>
                            <tr>
                                <td colspan="3" style="text-align:center; color: var(--text-muted); padding: 2rem;">
                                    Henüz işlem yok. "Gelir/Gider Ekle" ile başla!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($lastTransactions as $tx): ?>
                            <tr>
                                <td>
                                    <div class="cat-badge <?php echo $tx['type']; ?>">
                                        <i class="fa-solid <?php echo $tx['type'] === 'income' ? 'fa-wallet' : 'fa-cart-shopping'; ?>"></i>
                                        <?php echo htmlspecialchars($tx['category']); ?>
                                    </div>
                                </td>
                                <td><?php echo date('d M', strtotime($tx['transaction_date'])); ?></td>
                                <td class="<?php echo $tx['type'] === 'income' ? 'amount-positive' : 'amount-negative'; ?>">
                                    <?php echo $tx['type'] === 'income' ? '+' : '-'; ?>&#8378;<?php echo number_format((float)($tx['amount']), 0, ',', '.'); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Günün İpucu -->
            <div class="glass-card widget-tip">
                <div class="tip-icon"><i class="fa-solid fa-lightbulb"></i></div>
                <div class="tip-content">
                    <h4>Günün Tasarruf İpucu</h4>
                    <p><?php echo htmlspecialchars($randomTip, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>

            <!-- Başarımlar -->
            <div class="glass-card" style="grid-column: span 2;">
                <div class="widget-header">
                    <h3><i class="fa-solid fa-trophy"></i> Başarımlar</h3>
                    <span class="widget-subtitle">
                        <?php echo array_sum(array_column($achievements, 'earned')); ?>
                        /
                        <?php echo count($achievements); ?> kazanıldı
                    </span>
                </div>
                <div class="ub-achievement-grid">
                    <?php foreach ($achievements as $ach): ?>
                        <div class="ub-achievement <?php echo $ach['earned'] ? 'earned' : ''; ?>"
                             title="<?php echo htmlspecialchars($ach['description'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="ub-icon"><?php echo htmlspecialchars($ach['icon'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <h4><?php echo htmlspecialchars($ach['title'], ENT_QUOTES, 'UTF-8'); ?></h4>
                            <small>+<?php echo (int)$ach['xp_reward']; ?> XP</small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div> <!-- End bento grid -->
        
        </div> <!-- End .profile-overhaul-container -->
    </main>

    <!-- Gelir/Gider Modal -->
    <div id="transactionModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle" class="modal-title">Gelir Ekle</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="transactionForm" onsubmit="handleTransaction(event)">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($__csrf, ENT_QUOTES) ?>">
                <input type="hidden" id="txType" name="type" value="income">
                <input type="hidden" name="action" value="add_transaction">

                <div class="form-group-premium" id="ocrScannerBox">
                    <label>
                        🤖 AI Fiş Tarayıcı (OCR)
                        <span style="font-size:0.75rem; color:var(--accent-purple); float:right;">Sahte Yükleme</span>
                    </label>
                    <input type="file" id="ocrInput" accept="image/*" class="input-glass" style="padding: 10px; cursor: pointer;" onchange="simulateOCR()">
                    <div id="ocrProgress" style="display:none; font-family: var(--font-mono); color: var(--accent-cyan); font-size: 0.8rem; margin-top: 5px;">Tarama Yapılıyor_ <i class="fa-solid fa-spinner fa-spin"></i></div>
                </div>

                <div class="form-group-premium">
                    <label>Tutar (&#8378;)</label>
                    <input type="number" step="0.01" name="amount" id="txAmount" class="input-glass" required placeholder="0.00" min="0.01">
                </div>

                <div class="form-group-premium">
                    <label>Kategori</label>
                    <input type="text" name="category" id="txCategory" class="input-glass" required placeholder="Örn: Market · Burs · Kredi">
                </div>

                <div class="form-group-premium">
                    <label>Açıklama (Opsiyonel)</label>
                    <input type="text" name="description" id="txDesc" class="input-glass" placeholder="Kısa bir not ekle...">
                </div>

                <button type="submit" class="btn-premium-submit">Onayla ve Kaydet</button>
            </form>
        </div>
    </div>

    <!-- ══ CYBER COACH — Floating Chat Widget (v3) ══ -->
    <div id="cyberCoachWidget" class="coach-widget">
        <button id="coachToggleBtn" class="coach-toggle-btn" onclick="toggleCoach()" aria-label="Cyber Coach Aç">
            <div class="coach-btn-inner">
                <span class="coach-btn-icon">🤖</span>
                <span class="coach-btn-label">Cyber Coach</span>
                <span class="coach-btn-pulse"></span>
            </div>
        </button>
        <div id="coachPanel" class="coach-panel" role="dialog">
            <div class="coach-header">
                <div class="coach-avatar">🤖</div>
                <div class="coach-header-info">
                    <span class="coach-name">Cyber Coach</span>
                    <span class="coach-status"><span class="status-dot"></span> Aktif</span>
                </div>
                <button class="coach-close-btn" onclick="toggleCoach()">✕</button>
            </div>
            <div id="coachMessages" class="coach-messages">
                <div class="coach-msg bot">
                    <div class="msg-bubble">&#128075; Merhaba! Ben <strong>Cyber Coach</strong>. KYK burs miktar&#305;, &#351;ehir maliyetleri, tasarruf stratejileri veya &#246;&#287;renci b&#252;t&#231;esi hakk&#305;nda her &#351;eyi sorabilirsin!</div>
                </div>
                <div class="coach-quick-asks">
                    <button onclick="sendQuickAsk('İstanbul\'da öğrenci olarak aylık ne kadar harcama yapılır?')">&#127961;&#65039; &#304;stanbul maliyeti</button>
                    <button onclick="sendQuickAsk('KYK burs miktarları 2025\'te ne kadar?')">&#127891; KYK burs miktar&#305;</button>
                    <button onclick="sendQuickAsk('Aylık 5000 TL ile nasıl tasarruf yapabilirim?')">&#128176; Tasarruf t&#252;yolar&#305;</button>
                    <button onclick="sendQuickAsk('Öğrenciler için en uygun part-time iş önerileri neler?')">&#128188; Part-time i&#351;</button>
                </div>
            </div>
            <div class="coach-input-area">
                <div class="coach-input-wrapper">
                    <input type="text" id="coachInput" class="coach-input" placeholder="Bir &#351;ey sor..." maxlength="500"
                        onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendCoachMessage();}" />
                    <span id="coachCounter" class="coach-counter" title="10 dakikada kalan mesaj hakkı">30/30</span>
                </div>
                <button class="coach-send-btn" onclick="sendCoachMessage()" id="coachSendBtn">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
    // ── Modal ───────────────────────────────────────────────────────────────
    function openModal(type) {
        document.getElementById('txType').value = type;
        document.getElementById('modalTitle').innerText = type === 'income' ? 'Gelir Ekle' : 'Gider Ekle';
        document.getElementById('txCategory').placeholder = type === 'income' ? 'Örn: Burs, Maaş, Havale' : 'Örn: Market, Yemek, Ulaşım';
        document.getElementById('transactionModal').classList.add('active');
    }
    function closeModal() {
        document.getElementById('transactionModal').classList.remove('active');
        document.getElementById('ocrInput').value = '';
        document.getElementById('ocrProgress').style.display = 'none';
    }
    document.getElementById('transactionModal').addEventListener('click', (e) => {
        if (e.target === e.currentTarget) closeModal();
    });

    // ── OCR Scanner Mock ──────────────────────────────────────────────────
    function simulateOCR() {
        const input = document.getElementById('ocrInput');
        const progress = document.getElementById('ocrProgress');
        
        if (input.files && input.files[0]) {
            progress.style.display = 'block';
            
            // Simülasyon gecikmesi 1.5 saniye
            setTimeout(() => {
                progress.style.display = 'none';
                
                // OCR Data Mock (Fake parsing from receipt)
                document.getElementById('txAmount').value = "120.50";
                document.getElementById('txCategory').value = "Gıda / Market";
                document.getElementById('txDesc').value = "AI Tarama: " + input.files[0].name;
                
                // Animasyon ile belirt
                const container = document.getElementById('ocrScannerBox');
                container.style.border = "1px solid var(--accent-green)";
                setTimeout(() => container.style.border = "none", 2000);
            }, 1500);
        }
    }

    // ── Cyber Coach Widget (v3) ───────────────────────────────────────────
    function toggleCoach() {
        const panel = document.getElementById('coachPanel');
        const btn   = document.getElementById('coachToggleBtn');
        const isOpen = panel.classList.toggle('open');
        btn.classList.toggle('active', isOpen);
        if (isOpen) setTimeout(() => document.getElementById('coachInput')?.focus(), 300);
    }
    function sendQuickAsk(text) {
        document.getElementById('coachInput').value = text;
        sendCoachMessage();
    }
    async function sendCoachMessage() {
        const input    = document.getElementById('coachInput');
        const sendBtn  = document.getElementById('coachSendBtn');
        const messages = document.getElementById('coachMessages');
        const counter  = document.getElementById('coachCounter');
        const text = input.value.trim();
        if (!text) return;
        document.querySelector('.coach-quick-asks')?.remove();
        appendCoachBubble(messages, 'user', text);
        input.value = '';
        sendBtn.disabled = true;
        const typingId = 'typing-' + Date.now();
        messages.insertAdjacentHTML('beforeend',
            `<div id="${typingId}" class="coach-msg bot typing-indicator"><div class="msg-bubble"><span></span><span></span><span></span></div></div>`);
        messages.scrollTop = messages.scrollHeight;
        try {
            const res  = await fetch('api/cyber_coach.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text })
            });
            const data = await res.json();
            document.getElementById(typingId)?.remove();
            if (data.rate_limit) {
                appendCoachBubble(messages, 'bot rate-limit-msg', data.reply);
                if (counter) { counter.textContent = '0/30'; counter.classList.add('exhausted'); }
                sendBtn.disabled = false; messages.scrollTop = messages.scrollHeight; return;
            }
            appendCoachBubble(messages, 'bot', data.reply || data.error || '⚠️ Bir hata oluştu.');
            if (counter && data.remaining !== undefined) {
                counter.textContent = `${data.remaining}/30`;
                counter.classList.toggle('low', data.remaining <= 5);
                counter.classList.remove('exhausted');
            }
        } catch (e) {
            document.getElementById(typingId)?.remove();
            appendCoachBubble(messages, 'bot', '🔌 Bağlantı hatası. Lütfen tekrar dene.');
        }
        sendBtn.disabled = false;
        messages.scrollTop = messages.scrollHeight;
        input.focus();
    }
    function appendCoachBubble(container, classes, text) {
        const div = document.createElement('div');
        div.className = `coach-msg ${classes}`;
        div.innerHTML = `<div class="msg-bubble">${text}</div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    async function handleTransaction(event) {
        event.preventDefault();
        const btn = event.target.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Kaydediliyor...';

        const formData = new FormData(event.target);
        try {
            const response = await fetch('api/transaction_handler.php', { method: 'POST', body: formData });
            const result   = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert(result.message || 'Bir hata oluştu.');
                btn.disabled = false;
                btn.textContent = 'Onayla ve Kaydet';
            }
        } catch (error) {
            console.error('Error:', error);
            btn.disabled = false;
            btn.textContent = 'Onayla ve Kaydet';
        }
    }

    // ── Grafik (Gerçek Veriler) ─────────────────────────────────────────────
    const ctx = document.getElementById('expenseChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [{
                label: 'Harcama (TL)',
                data: <?php echo json_encode($chartValues); ?>,
                borderColor: '#4f8cff',
                backgroundColor: 'rgba(79, 140, 255, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false },  ticks: { color: '#aaa' } },
                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#aaa', callback: v => v + ' TL' } }
            }
        }
    });

    // ── Achievement Auto-Check on Dashboard Load ──────────────────────────
    fetch('api/achievements.php?action=check')
        .then(r => r.json())
        .then(data => {
            if (data.awarded && data.awarded.length > 0) {
                data.awarded.forEach(title => {
                    if (title) console.log('🏆 New Achievement:', title);
                });
                // Reload to show new badges
                if (data.awarded.some(a => a !== null)) {
                    setTimeout(() => location.reload(), 1500);
                }
            }
        })
        .catch(e => console.log('Achievement check skipped'));

    // Theme init for dashboard
    (function() {
        const savedTheme = localStorage.getItem('ub-theme');
        if (savedTheme) document.documentElement.setAttribute('data-theme', savedTheme);
    })();
    </script>
</body>
</html>
