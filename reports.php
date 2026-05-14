<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'includes/db.php';
require_once 'includes/auth_system.php';

$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) { header('Location: index.php'); exit; }

$currentUser = $auth->getCurrentUser();
$user_id = $_SESSION['user_id'];
$initials = '';
$names = explode(' ', $currentUser['full_name'] ?? 'K');
foreach ($names as $n) $initials .= strtoupper(substr($n, 0, 1));
$initials = substr($initials, 0, 2) ?: 'KU';

// Son 6 aylık veri
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-{$i} months"));
    $monthlyData[$m] = ['income' => 0, 'expense' => 0, 'label' => ''];
}

try {
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(transaction_date, '%Y-%m') as m, type, SUM(amount) as total 
        FROM transactions WHERE user_id = ? AND transaction_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH) 
        GROUP BY m, type ORDER BY m ASC");
    $stmt->execute([$user_id]);
    foreach ($stmt->fetchAll() as $row) {
        if (isset($monthlyData[$row['m']])) {
            $monthlyData[$row['m']][$row['type']] = (float)$row['total'];
        }
    }
} catch (Exception $e) {}

$trMonths = ['01'=>'Ocak','02'=>'Şubat','03'=>'Mart','04'=>'Nisan','05'=>'Mayıs','06'=>'Haziran',
             '07'=>'Temmuz','08'=>'Ağustos','09'=>'Eylül','10'=>'Ekim','11'=>'Kasım','12'=>'Aralık'];
$chartLabels = [];
$chartIncome = [];
$chartExpense = [];
$chartSavings = [];
foreach ($monthlyData as $m => $d) {
    $month = substr($m, 5, 2);
    $chartLabels[] = $trMonths[$month] ?? $m;
    $chartIncome[] = $d['income'];
    $chartExpense[] = $d['expense'];
    $chartSavings[] = $d['income'] - $d['expense'];
}

// Kategori bazlı harcamalar (bu ay)
$catData = [];
try {
    $stmt = $pdo->prepare("SELECT category, SUM(amount) as total FROM transactions 
        WHERE user_id = ? AND type = 'expense' AND MONTH(transaction_date) = MONTH(CURRENT_DATE()) 
        AND YEAR(transaction_date) = YEAR(CURRENT_DATE()) GROUP BY category ORDER BY total DESC");
    $stmt->execute([$user_id]);
    $catData = $stmt->fetchAll();
} catch (Exception $e) {}

// Gün bazlı harcamalar (bu ay)
$dayData = array_fill(0, 7, 0); // Pzt-Paz
try {
    $stmt = $pdo->prepare("SELECT DAYOFWEEK(transaction_date) as dow, SUM(amount) as total FROM transactions 
        WHERE user_id = ? AND type = 'expense' AND MONTH(transaction_date) = MONTH(CURRENT_DATE()) 
        AND YEAR(transaction_date) = YEAR(CURRENT_DATE()) GROUP BY dow");
    $stmt->execute([$user_id]);
    foreach ($stmt->fetchAll() as $r) {
        $idx = ((int)$r['dow'] + 5) % 7; // MySQL: 1=Sun -> convert to 0=Mon
        $dayData[$idx] = (float)$r['total'];
    }
} catch (Exception $e) {}

// Bu ay vs geçen ay
$thisMonthExp = 0;
$lastMonthExp = 0;
try {
    $stmt = $pdo->prepare("SELECT SUM(amount) as t FROM transactions WHERE user_id = ? AND type = 'expense' 
        AND MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE())");
    $stmt->execute([$user_id]);
    $thisMonthExp = (float)($stmt->fetchColumn() ?: 0);

    $stmt = $pdo->prepare("SELECT SUM(amount) as t FROM transactions WHERE user_id = ? AND type = 'expense' 
        AND MONTH(transaction_date) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) 
        AND YEAR(transaction_date) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))");
    $stmt->execute([$user_id]);
    $lastMonthExp = (float)($stmt->fetchColumn() ?: 0);
} catch (Exception $e) {}

$changePercent = $lastMonthExp > 0 ? round((($thisMonthExp - $lastMonthExp) / $lastMonthExp) * 100) : 0;
$changeColor = $changePercent <= 0 ? '#39FF14' : '#ff2a2a';
$changeLabel = $changePercent <= 0 ? 'Azaldı' : 'Arttı';

// Tasarruf oranı
$thisMonthInc = 0;
try {
    $stmt = $pdo->prepare("SELECT SUM(amount) as t FROM transactions WHERE user_id = ? AND type = 'income' 
        AND MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE())");
    $stmt->execute([$user_id]);
    $thisMonthInc = (float)($stmt->fetchColumn() ?: 0);
} catch (Exception $e) {}

$savingsRate = $thisMonthInc > 0 ? round((($thisMonthInc - $thisMonthExp) / $thisMonthInc) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Raporlar &#8212; ÜniBütçe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/mobile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/animations.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="js/animations.js?v=<?php echo time(); ?>" defer></script>
</head>
<body class="dashboard-body">
    <?php include 'includes/dashboard_navbar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text">
                <h1>&#128200; Finansal Raporlar</h1>
                <p>Ayl&#305;k trendlerin, harcama al&#305;&#351;kanl&#305;klar&#305;n ve tasarruf performans&#305;n.</p>
            </div>
            <div class="topbar-actions">
                <div class="date-display">
                    <i class="fa-regular fa-calendar"></i>
                    <?php echo date('d F Y'); ?>
                </div>
                <div class="profile-mini"><?php echo $initials; ?></div>
            </div>
        </header>

        <div class="profile-overhaul-container">

        <!-- Özet Kartları -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;" class="stagger-children report-summary-grid">
            <div class="glass-card" style="padding: 20px; text-align: center;">
                <div style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Bu Ay Harcama</div>
                <div style="font-family: var(--font-mono); font-size: 1.6rem; font-weight: 800; color: #ff5252;">&#8378;<?php echo number_format((float)($thisMonthExp), 0, ',', '.'); ?></div>
            </div>
            <div class="glass-card" style="padding: 20px; text-align: center;">
                <div style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Ge&#231;en Aya G&#246;re</div>
                <div style="font-family: var(--font-mono); font-size: 1.6rem; font-weight: 800; color: <?php echo $changeColor; ?>;">
                    <?php echo $changePercent > 0 ? '↑' : '↓'; ?> %<?php echo abs($changePercent); ?>
                </div>
                <div style="font-size: 0.7rem; color: <?php echo $changeColor; ?>;"><?php echo $changeLabel; ?></div>
            </div>
            <div class="glass-card" style="padding: 20px; text-align: center;">
                <div style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Tasarruf Oran&#305;</div>
                <div style="font-family: var(--font-mono); font-size: 1.6rem; font-weight: 800; color: <?php echo $savingsRate >= 0 ? '#39FF14' : '#ff2a2a'; ?>;">
                    %<?php echo $savingsRate; ?>
                </div>
            </div>
            <div class="glass-card" style="padding: 20px; text-align: center;">
                <div style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Bu Ay Gelir</div>
                <div style="font-family: var(--font-mono); font-size: 1.6rem; font-weight: 800; color: #00e676;">&#8378;<?php echo number_format((float)($thisMonthInc), 0, ',', '.'); ?></div>
            </div>
        </div>

        <!-- Grafik Row 1 -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
            <!-- Aylık Trend -->
            <div class="glass-card cascade-in" style="padding: 24px;">
                <h3 style="margin-bottom: 16px; font-size: 0.95rem;"><i class="fa-solid fa-chart-line" style="color: var(--accent-cyan);"></i> Ayl&#305;k Gelir / Gider Trendi</h3>
                <div style="height: 280px;"><canvas id="monthlyTrendChart"></canvas></div>
            </div>
            <!-- Tasarruf Trendi -->
            <div class="glass-card cascade-in" style="padding: 24px;">
                <h3 style="margin-bottom: 16px; font-size: 0.95rem;"><i class="fa-solid fa-piggy-bank" style="color: var(--accent-green);"></i> Ayl&#305;k Tasarruf</h3>
                <div style="height: 280px;"><canvas id="savingsTrendChart"></canvas></div>
            </div>
        </div>

        <!-- Grafik Row 2 -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
            <!-- Kategori Dağılımı -->
            <div class="glass-card cascade-in" style="padding: 24px;">
                <h3 style="margin-bottom: 16px; font-size: 0.95rem;"><i class="fa-solid fa-chart-pie" style="color: var(--accent-purple);"></i> Kategori Da&#287;&#305;l&#305;m&#305; (Bu Ay)</h3>
                <div style="height: 280px; display: flex; align-items: center; justify-content: center;">
                    <?php if (empty($catData)): ?>
                        <p style="color: var(--text-muted);">Bu ay hen&#252;z harcama yok.</p>
                    <?php else: ?>
                        <canvas id="categoryChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Gün Bazlı Harcama -->
            <div class="glass-card cascade-in" style="padding: 24px;">
                <h3 style="margin-bottom: 16px; font-size: 0.95rem;"><i class="fa-solid fa-calendar-week" style="color: var(--accent-orange);"></i> G&#252;n Baz&#305;nda Harcama</h3>
                <div style="height: 280px;"><canvas id="dayChart"></canvas></div>
            </div>
        </div>

        <!-- Kategori Liste -->
        <?php if (!empty($catData)):
            $maxCat = (float)$catData[0]['total'];
        ?>
        <div class="glass-card cascade-in" style="padding: 24px; margin-bottom: 24px;">
            <h3 style="margin-bottom: 16px; font-size: 0.95rem;"><i class="fa-solid fa-ranking-star" style="color: var(--accent-pink);"></i> En &#199;ok Harcanan Kategoriler</h3>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php
                $catColors = ['#6366f1','#0088ff','#00e676','#ff9100','#f42c92','#00F0FF','#7000FF','#FFD700'];
                $ci = 0;
                foreach ($catData as $cat):
                    $pct = $maxCat > 0 ? round(($cat['total'] / $maxCat) * 100) : 0;
                    $color = $catColors[$ci % count($catColors)];
                    $ci++;
                ?>
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="width: 120px; font-size: 0.85rem; font-weight: 600; color: var(--text-primary); flex-shrink: 0;"><?php echo htmlspecialchars($cat['category']); ?></div>
                    <div style="flex: 1; height: 10px; background: rgba(255,255,255,0.05); border-radius: 5px; overflow: hidden;">
                        <div class="progress-animate" style="width: <?php echo $pct; ?>%; height: 100%; background: <?php echo $color; ?>; border-radius: 5px;"></div>
                    </div>
                    <div style="width: 100px; text-align: right; font-family: var(--font-mono); font-size: 0.85rem; font-weight: 700; color: <?php echo $color; ?>;">&#8378;<?php echo number_format((float)($cat['total']), 0, ',', '.'); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        </div> <!-- End .profile-overhaul-container -->
    </main>

    <style>
        @media (max-width: 768px) {
            .report-summary-grid { grid-template-columns: 1fr 1fr !important; }
            .report-summary-grid + div,
            .report-summary-grid + div + div { grid-template-columns: 1fr !important; }
        }
    </style>

    <script>
    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { labels: { padding: 14, font: { size: 11 } } },
            tooltip: {
                backgroundColor: 'rgba(10, 10, 26, 0.95)',
                borderColor: 'rgba(0,136,255,0.3)',
                borderWidth: 1,
                padding: 12,
                callbacks: { label: ctx => ` ₺${ctx.raw.toLocaleString('tr-TR')}` }
            }
        }
    };

    // Monthly Trend
    new Chart(document.getElementById('monthlyTrendChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [
                {
                    label: 'Gelir',
                    data: <?php echo json_encode($chartIncome); ?>,
                    borderColor: '#00e676',
                    backgroundColor: 'rgba(0,230,118,0.1)',
                    tension: 0.4, fill: true, pointRadius: 5, pointHoverRadius: 7
                },
                {
                    label: 'Gider',
                    data: <?php echo json_encode($chartExpense); ?>,
                    borderColor: '#ff5252',
                    backgroundColor: 'rgba(255,82,82,0.1)',
                    tension: 0.4, fill: true, pointRadius: 5, pointHoverRadius: 7
                }
            ]
        },
        options: {
            ...chartDefaults,
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#aaa' } },
                y: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#aaa', callback: v => '₺' + v.toLocaleString('tr-TR') } }
            }
        }
    });

    // Savings Trend
    new Chart(document.getElementById('savingsTrendChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [{
                label: 'Tasarruf',
                data: <?php echo json_encode($chartSavings); ?>,
                backgroundColor: <?php echo json_encode($chartSavings); ?>.map(v => v >= 0 ? 'rgba(57,255,20,0.6)' : 'rgba(255,82,82,0.6)'),
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            ...chartDefaults,
            plugins: { ...chartDefaults.plugins, legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#aaa' } },
                y: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#aaa', callback: v => '₺' + v.toLocaleString('tr-TR') } }
            }
        }
    });

    // Category Doughnut
    <?php if (!empty($catData)): ?>
    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($catData, 'category')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_map(fn($c) => (float)$c['total'], $catData)); ?>,
                backgroundColor: ['#6366f1','#0088ff','#00e676','#ff9100','#f42c92','#00F0FF','#7000FF','#FFD700'],
                borderWidth: 0, hoverOffset: 8
            }]
        },
        options: {
            ...chartDefaults,
            cutout: '60%',
            plugins: { ...chartDefaults.plugins, legend: { position: 'right' } }
        }
    });
    <?php endif; ?>

    // Day Chart
    new Chart(document.getElementById('dayChart'), {
        type: 'bar',
        data: {
            labels: ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'],
            datasets: [{
                label: 'Harcama',
                data: <?php echo json_encode(array_values($dayData)); ?>,
                backgroundColor: [
                    'rgba(0,136,255,0.6)', 'rgba(0,136,255,0.6)', 'rgba(0,136,255,0.6)',
                    'rgba(0,136,255,0.6)', 'rgba(0,136,255,0.6)',
                    'rgba(112,0,255,0.6)', 'rgba(112,0,255,0.6)'
                ],
                borderRadius: 8, borderSkipped: false
            }]
        },
        options: {
            ...chartDefaults,
            plugins: { ...chartDefaults.plugins, legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#aaa', font: { size: 10 } } },
                y: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#aaa', callback: v => '₺' + v.toLocaleString('tr-TR') } }
            }
        }
    });
    </script>
</body>
</html>
