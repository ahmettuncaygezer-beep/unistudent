<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');
require_once 'includes/db.php';
require_once 'includes/auth_system.php';
require_once 'includes/csrf.php';
$__csrf = csrf_token();

$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) { header('Location: index.php'); exit; }

$currentUser = $auth->getCurrentUser();
$user_id = $_SESSION['user_id'];
$initials = '';
$names = explode(' ', $currentUser['full_name'] ?? 'K');
foreach ($names as $n) $initials .= strtoupper(substr($n, 0, 1));
$initials = substr($initials, 0, 2) ?: 'KU';

// Abonelik verileri
$subs = [];
$monthlyTotal = 0;
try {
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY is_active DESC, name ASC");
    $stmt->execute([$user_id]);
    $subs = $stmt->fetchAll();
    foreach ($subs as $s) {
        if ($s['is_active']) {
            $monthlyTotal += $s['billing_cycle'] === 'monthly' ? (float)$s['amount'] : ((float)$s['amount'] / 12);
        }
    }
} catch (Exception $e) {}

$catIcons = [
    'Eğlence' => 'fa-gamepad', 'Müzik' => 'fa-music', 'Video' => 'fa-film',
    'Yazılım' => 'fa-code', 'Bulut' => 'fa-cloud', 'Diğer' => 'fa-rotate'
];
$catColors = [
    'Eğlence' => '#f42c92', 'Müzik' => '#39FF14', 'Video' => '#ff2a2a',
    'Yazılım' => '#00F0FF', 'Bulut' => '#7000FF', 'Diğer' => '#ff9100'
];
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Abonelikler — ÜniBütçe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/mobile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/animations.css?v=<?php echo time(); ?>">
    <script src="js/animations.js?v=<?php echo time(); ?>" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="csrf-token" content="<?= htmlspecialchars($__csrf, ENT_QUOTES) ?>">
    <script>window.CSRF_TOKEN = <?= json_encode($__csrf) ?>;</script>
    <script src="js/utils.js?v=<?php echo time(); ?>" defer></script>
</head>
<body class="dashboard-body">
    <?php include 'includes/dashboard_navbar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text">
                <h1>&#128241; Abonelik Yönetimi</h1>
                <p>Tekrarlayan harcamalarını takip et ve tasarruf fırsatlarını keşfet.</p>
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
        
        <!-- Abonelik Özeti -->
        <div class="premium-widgets-row" style="margin-bottom:20px;">
            <div class="glass-card premium-widget">
                <div class="safe-spend-label">Aylık Abonelik Maliyeti</div>
                <div class="safe-spend-amount" style="color:var(--accent-cyan); text-shadow:0 0 20px rgba(0,240,255,0.4);">
                    ₺<?php echo number_format((float)($monthlyTotal), 0, ',', '.'); ?>
                </div>
                <div class="safe-spend-sub">Yıllık: ₺<?php echo number_format((float)($monthlyTotal * 12), 0, ',', '.'); ?></div>
            </div>
            <div class="glass-card premium-widget">
                <div class="safe-spend-label">Aktif Abonelik</div>
                <div class="safe-spend-amount" style="color:var(--accent-purple); text-shadow:0 0 20px rgba(112,0,255,0.4);">
                    <?php echo count(array_filter($subs, fn($s) => $s['is_active'])); ?>
                </div>
                <div class="safe-spend-sub">Toplam <?php echo count($subs); ?> kayıt</div>
            </div>
        </div>

        <!-- Yeni Abonelik Ekleme -->
        <div class="glass-card" style="margin-bottom:20px;">
            <div class="widget-header"><h3>&#10133; Yeni Abonelik Ekle</h3></div>
            <form id="subForm" style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr auto; gap:12px; margin-top:14px; align-items:end;">
                <div>
                    <label style="font-size:0.8rem;color:var(--text-muted);">Ad</label>
                    <input type="text" name="name" required placeholder="Netflix, Spotify..." class="input-glass" style="width:100%;">
                </div>
                <div>
                    <label style="font-size:0.8rem;color:var(--text-muted);">Tutar (₺)</label>
                    <input type="number" name="amount" required step="0.01" min="0.01" class="input-glass" style="width:100%;" placeholder="99.99">
                </div>
                <div>
                    <label style="font-size:0.8rem;color:var(--text-muted);">Dönem</label>
                    <select name="billing_cycle" class="input-glass" style="width:100%;">
                        <option value="monthly">Aylık</option>
                        <option value="yearly">Yıllık</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:0.8rem;color:var(--text-muted);">Kategori</label>
                    <select name="category" class="input-glass" style="width:100%;">
                        <option>Eğlence</option><option>Müzik</option><option>Video</option>
                        <option>Yazılım</option><option>Bulut</option><option>Diğer</option>
                    </select>
                </div>
                <button type="submit" class="btn-premium-submit" style="padding:10px 20px;">Ekle</button>
            </form>
        </div>

        <!-- Abonelik Listesi -->
        <div class="glass-card">
            <div class="widget-header"><h3>&#128203; Aktif Abonelikler</h3></div>
            <div style="margin-top:14px;">
                <?php if (empty($subs)): ?>
                    <div style="text-align:center; padding:40px; color:var(--text-muted);">
                        Henüz abonelik eklemediniz. Yukarıdan ilk aboneliğinizi ekleyin!
                    </div>
                <?php else: ?>
                    <?php foreach ($subs as $sub):
                        $icon = $catIcons[$sub['category']] ?? 'fa-rotate';
                        $color = $catColors[$sub['category']] ?? '#ff9100';
                        $monthly = $sub['billing_cycle'] === 'monthly' ? (float)$sub['amount'] : ((float)$sub['amount'] / 12);
                    ?>
                    <div class="sub-row <?php echo $sub['is_active'] ? '' : 'inactive'; ?>" data-id="<?php echo $sub['id']; ?>">
                        <div class="sub-icon" style="color:<?php echo $color; ?>; border-color:<?php echo $color; ?>;">
                            <i class="fa-solid <?php echo $icon; ?>"></i>
                        </div>
                        <div class="sub-info">
                            <div class="sub-name"><?php echo htmlspecialchars($sub['name']); ?></div>
                            <div class="sub-meta"><?php echo $sub['category']; ?> &middot; <?php echo $sub['billing_cycle'] === 'monthly' ? 'Aylık' : 'Yıllık'; ?></div>
                        </div>
                        <div class="sub-amount" style="color:<?php echo $color; ?>;">
                            ₺<?php echo number_format((float)($sub['amount']), 2, ',', '.'); ?>
                        </div>
                        <div class="sub-actions">
                            <button class="sub-toggle-btn" onclick="toggleSub(<?php echo $sub['id']; ?>)" title="<?php echo $sub['is_active'] ? 'Durdur' : 'Aktifleştir'; ?>">
                                <i class="fa-solid <?php echo $sub['is_active'] ? 'fa-pause' : 'fa-play'; ?>"></i>
                            </button>
                            <button class="sub-delete-btn" onclick="deleteSub(<?php echo $sub['id']; ?>)" title="Sil">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- İptal Tasarrufu -->
        <?php
            $inactiveSavings = 0;
            foreach ($subs as $s) {
                if (!$s['is_active']) {
                    $inactiveSavings += $s['billing_cycle'] === 'monthly' ? (float)$s['amount'] : ((float)$s['amount'] / 12);
                }
            }
        ?>
        <?php if ($inactiveSavings > 0): ?>
        <div class="glass-card" style="margin-top:20px; border-color:var(--accent-green);">
            <div style="text-align:center; padding:16px;">
                <div style="font-size:1.5rem; margin-bottom:6px;">&#128161; İptal Edilen Aboneliklerle</div>
                <div style="font-family:var(--font-mono); font-size:2rem; font-weight:800; color:var(--accent-green); text-shadow:0 0 15px rgba(57,255,20,0.4);">
                    Aylık ₺<?php echo number_format((float)($inactiveSavings), 0, ',', '.'); ?> Tasarruf
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        </div> <!-- End .profile-overhaul-container -->
    </main>

    <style>
        .sub-row {
            display: flex; align-items: center; gap: 14px; padding: 14px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: all 0.2s;
        }
        .sub-row:hover { background: rgba(255,255,255,0.03); }
        .sub-row.inactive { opacity: 0.4; }
        .sub-icon {
            width: 42px; height: 42px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            border: 1px solid; font-size: 1.1rem;
            background: rgba(255,255,255,0.03); flex-shrink: 0;
        }
        .sub-info { flex: 1; }
        .sub-name { font-weight: 600; font-size: 0.95rem; }
        .sub-meta { font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; }
        .sub-amount { font-family: var(--font-mono); font-weight: 700; font-size: 1rem; white-space: nowrap; }
        .sub-actions { display: flex; gap: 8px; }
        .sub-toggle-btn, .sub-delete-btn {
            width: 32px; height: 32px; border-radius: 6px; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem; transition: all 0.2s;
        }
        .sub-toggle-btn { background: rgba(0,240,255,0.1); color: var(--accent-cyan); }
        .sub-toggle-btn:hover { background: rgba(0,240,255,0.2); }
        .sub-delete-btn { background: rgba(255,42,42,0.1); color: var(--accent-red); }
        .sub-delete-btn:hover { background: rgba(255,42,42,0.2); }
        .input-glass {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px; padding: 10px 12px;
            color: var(--text-primary); font-family: var(--font-primary);
            outline: none; transition: border 0.2s;
        }
        .input-glass:focus { border-color: var(--accent-cyan); }
        @media (max-width: 900px) {
            #subForm { grid-template-columns: 1fr 1fr; }
        }
    </style>

    <script>
    document.getElementById('subForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        fd.append('action', 'add');
        if (window.CSRF_TOKEN) fd.append('csrf_token', window.CSRF_TOKEN);
        await fetch('api/subscription_handler.php', { method: 'POST', body: fd });
        location.reload();
    });

    async function toggleSub(id) {
        const fd = new FormData();
        fd.append('action', 'toggle');
        fd.append('id', id);
        if (window.CSRF_TOKEN) fd.append('csrf_token', window.CSRF_TOKEN);
        await fetch('api/subscription_handler.php', { method: 'POST', body: fd });
        location.reload();
    }

    async function deleteSub(id) {
        if (!confirm('Bu aboneliği silmek istiyor musunuz?')) return;
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        if (window.CSRF_TOKEN) fd.append('csrf_token', window.CSRF_TOKEN);
        await fetch('api/subscription_handler.php', { method: 'POST', body: fd });
        location.reload();
    }
    </script>

    
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


