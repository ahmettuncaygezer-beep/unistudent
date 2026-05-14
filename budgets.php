<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'includes/db.php';
require_once 'includes/auth_system.php';

$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}
$currentUser = $auth->getCurrentUser();
$user_id = $_SESSION['user_id'];

// Kullanıcının kayıtlı bütçe verisi (profile.php ile uyumlu)
$stmt = $pdo->prepare("SELECT budget_json FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$row = $stmt->fetch();
$budgetData = !empty($row['budget_json']) ? json_decode($row['budget_json'], true) : null;

// Toplam gelir/gider hesapla
$stmt = $pdo->prepare("SELECT 
    SUM(CASE WHEN type='income' THEN amount ELSE 0 END) as total_in,
    SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) as total_out
    FROM transactions WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();
$totalIncome = $stats['total_in'] ?? 0;
$totalExpense = $stats['total_out'] ?? 0;
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bütçem - ÜniBütçe</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/auth.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/mobile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/animations.css?v=<?php echo time(); ?>">
    <script src="js/animations.js?v=<?php echo time(); ?>" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="dashboard-body">

    <?php include 'includes/dashboard_navbar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text">
                <h1>Bütçem</h1>
                <p>Aktif bütçelerini ve harcama limitlerini yönet.</p>
            </div>
            <div class="topbar-actions">
                <div class="date-display">
                    <i class="fa-regular fa-calendar"></i>
                    <?php echo date('d F Y'); ?>
                </div>
            </div>
        </header>
        <div class="profile-overhaul-container">
        
        <div class="bento-grid">
            <!-- Özet Kartları -->
            <div class="glass-card widget-stat stat-income">
                <div class="stat-icon-bg"><i class="fa-solid fa-arrow-up-long"></i></div>
                <div class="stat-content">
                    <span class="stat-label">Toplam Gelir</span>
                    <span class="stat-value">₺<?php echo number_format((float)($totalIncome), 0, ',', '.'); ?></span>
                </div>
            </div>

            <div class="glass-card widget-stat stat-expense">
                <div class="stat-icon-bg"><i class="fa-solid fa-arrow-down-long"></i></div>
                <div class="stat-content">
                    <span class="stat-label">Toplam Gider</span>
                    <span class="stat-value">₺<?php echo number_format((float)($totalExpense), 0, ',', '.'); ?></span>
                </div>
            </div>

            <div class="glass-card widget-stat stat-balance">
                <div class="stat-icon-bg"><i class="fa-solid fa-wallet"></i></div>
                <div class="stat-content">
                    <span class="stat-label">Net Bakiye</span>
                    <span class="stat-value">₺<?php echo number_format((float)($totalIncome - $totalExpense), 0, ',', '.'); ?></span>
                </div>
            </div>

            <!-- Bütçe Planı (Hesaplayıcıdan kaydedilen) -->
            <div class="glass-card" style="grid-column: 1 / -1; padding: 2rem;">
                <div class="widget-header" style="margin-bottom: 1.5rem;">
                    <h3><i class="fa-solid fa-chart-pie" style="color: #6366f1;"></i> Kayıtlı Bütçe Planı</h3>
                    <a href="index.php#budget" style="color: var(--accent-blue); text-decoration:none; font-size: 0.85rem;">
                        <i class="fa-solid fa-calculator"></i> Hesaplayıcıya Git
                    </a>
                </div>

                <?php if ($budgetData && isset($budgetData['expenses']) && !empty($budgetData['expenses'])): ?>
                    <?php
                    $total = $budgetData['total'] ?? 0;
                    $catNames = [
                        'housing'   => ['Barınma', 'fa-house', '#6366f1'],
                        'food'      => ['Yemek', 'fa-utensils', '#00b894'],
                        'transport' => ['Ulaşım', 'fa-bus', '#0984e3'],
                        'social'    => ['Sosyal', 'fa-masks-theater', '#e17055'],
                        'education' => ['Eğitim', 'fa-book', '#a29bfe'],
                        'personal'  => ['Kişisel', 'fa-user', '#fdcb6e'],
                        'utilities' => ['Faturalar', 'fa-file-invoice-dollar', '#00cec9'],
                        'emergency' => ['Acil Durum', 'fa-kit-medical', '#ff7675'],
                    ];
                    ?>
                    <div style="margin-bottom: 1rem; padding: 1rem; background: rgba(255,255,255,0.03); border-radius: 0.8rem; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 1.5rem; font-weight: 800;">₺<?php echo number_format((float)($total), 0, ',', '.'); ?> / ay</div>
                            <?php if (!empty($budgetData['lastSaved'])): ?>
                                <div style="font-size: 0.8rem; color: var(--text-secondary);">Son kaydedilme: <?php echo date('d.m.Y H:i', strtotime($budgetData['lastSaved'])); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem;">
                        <?php foreach ($budgetData['expenses'] as $id => $amount):
                            if ($amount <= 0) continue;
                            $catInfo = $catNames[$id] ?? [ucfirst($id), 'fa-wallet', '#888'];
                            $pct = $total > 0 ? round(($amount / $total) * 100) : 0;
                        ?>
                        <div style="padding: 1.2rem; background: rgba(255,255,255,0.03); border-radius: 0.8rem; border: 1px solid rgba(255,255,255,0.06);">
                            <i class="fa-solid <?php echo $catInfo[1]; ?>" style="color: <?php echo $catInfo[2]; ?>; font-size: 1.3rem; margin-bottom: 0.5rem; display: block;"></i>
                            <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.3rem;"><?php echo $catInfo[0]; ?></div>
                            <div style="font-weight: 800; font-size: 1.1rem;">₺<?php echo number_format((float)($amount), 0, ',', '.'); ?></div>
                            <div style="font-size: 0.7rem; color: <?php echo $catInfo[2]; ?>; margin-top: 0.3rem;">%<?php echo $pct; ?></div>
                            <div style="height: 4px; background: rgba(255,255,255,0.05); border-radius: 2px; margin-top: 0.5rem; overflow: hidden;">
                                <div style="width: <?php echo $pct; ?>%; height: 100%; background: <?php echo $catInfo[2]; ?>;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; color: rgba(255,255,255,0.4);">
                        <i class="fa-solid fa-wallet" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.3;"></i>
                        <p style="margin-bottom: 1rem;">Henüz kaydedilmiş bir bütçe planın yok.</p>
                        <a href="index.php#budget" class="btn-premium-submit" style="display: inline-block; padding: 0.8rem 2rem; text-decoration: none;">
                            &#128202; Bütçemi Hesapla ve Kaydet
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        </div> <!-- End .profile-overhaul-container -->
    </main>

    <!-- ══ CYBER COACH — Floating Chat Widget ══ -->
    <div id="cyberCoachWidget" class="coach-widget">
        <button id="coachToggleBtn" class="coach-toggle-btn" onclick="toggleCoach()" aria-label="Cyber Coach Aç">
            <div class="coach-btn-inner">
                <span class="coach-btn-icon">🤖</span>
                <span class="coach-btn-label">Cyber Coach</span>
                <span class="coach-btn-pulse"></span>
            </div>
        </button>
        <div id="coachPanel" class="coach-panel" role="dialog" aria-label="Cyber Coach Sohbet Paneli">
            <div class="coach-header">
                <div class="coach-avatar">🤖</div>
                <div class="coach-header-info">
                    <span class="coach-name">Cyber Coach</span>
                    <span class="coach-status"><span class="status-dot"></span> Aktif</span>
                </div>
                <button class="coach-close-btn" onclick="toggleCoach()" aria-label="Kapat">✕</button>
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
</body>
</html>



