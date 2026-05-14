<?php
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

// Ensure goals table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `user_goals` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `target_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `current_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `icon` VARCHAR(10) DEFAULT '🎯',
        `color` VARCHAR(20) DEFAULT '#0088ff',
        `deadline` DATE DEFAULT NULL,
        `is_completed` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {}

// Fetch goals
$goals = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM user_goals WHERE user_id = ? ORDER BY is_completed ASC, created_at DESC");
    $stmt->execute([$user_id]);
    $goals = $stmt->fetchAll();
} catch (Exception $e) {}

$totalSaved = 0;
$totalTarget = 0;
$completedCount = 0;
foreach ($goals as $g) {
    $totalSaved += (float)$g['current_amount'];
    $totalTarget += (float)$g['target_amount'];
    if ($g['is_completed']) $completedCount++;
}
$activeCount = count($goals) - $completedCount;
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Hedeflerim &#8212; ÜniBütçe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/mobile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/animations.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="js/animations.js?v=<?php echo time(); ?>" defer></script>
    <meta name="csrf-token" content="<?= htmlspecialchars($__csrf, ENT_QUOTES) ?>">
    <script>window.CSRF_TOKEN = <?= json_encode($__csrf) ?>;</script>
    <script src="js/utils.js?v=<?php echo time(); ?>" defer></script>
</head>
<body class="dashboard-body">
    <?php include 'includes/dashboard_navbar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text">
                <h1>&#127919; Hedeflerim</h1>
                <p>Finansal hedeflerini belirle, ilerlemeni takip et ve başar!</p>
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
        <div class="premium-widgets-row stagger-children" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
            <div class="glass-card premium-widget">
                <div class="safe-spend-label">Toplam Biriktirilen</div>
                <div class="safe-spend-amount" style="color: var(--accent-green); text-shadow: 0 0 20px rgba(57,255,20,0.4); font-size: 2.5rem;">
                    &#8378;<?php echo number_format((float)($totalSaved), 0, ',', '.'); ?>
                </div>
                <div class="safe-spend-sub">Hedef: &#8378;<?php echo number_format((float)($totalTarget), 0, ',', '.'); ?></div>
            </div>
            <div class="glass-card premium-widget">
                <div class="safe-spend-label">Aktif Hedef</div>
                <div class="safe-spend-amount" style="color: var(--accent-cyan); text-shadow: 0 0 20px rgba(0,240,255,0.4); font-size: 2.5rem;">
                    <?php echo $activeCount; ?>
                </div>
                <div class="safe-spend-sub"><?php echo $completedCount; ?> tamamland&#305;</div>
            </div>
            <div class="glass-card premium-widget">
                <div class="safe-spend-label">Genel &#304;lerleme</div>
                <div style="margin: 12px 0;">
                    <svg viewBox="0 0 120 120" width="90" height="90">
                        <?php $overallPct = $totalTarget > 0 ? min(100, ($totalSaved / $totalTarget) * 100) : 0; ?>
                        <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="10"/>
                        <circle cx="60" cy="60" r="50" fill="none" stroke="var(--accent-purple)"
                            stroke-width="10" stroke-linecap="round"
                            stroke-dasharray="<?php echo ($overallPct / 100) * 314; ?> 314"
                            transform="rotate(-90 60 60)"
                            class="goal-ring"
                            style="filter: drop-shadow(0 0 6px var(--accent-purple));" />
                        <text x="60" y="56" text-anchor="middle" fill="var(--accent-purple)" font-size="22" font-weight="800" font-family="var(--font-mono)"><?php echo round($overallPct); ?>%</text>
                        <text x="60" y="74" text-anchor="middle" fill="var(--text-muted)" font-size="9">tamamland&#305;</text>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Yeni Hedef Ekleme -->
        <div class="glass-card cascade-in" style="margin-bottom: 24px;">
            <div class="widget-header"><h3>&#10133; Yeni Hedef Olu&#351;tur</h3></div>
            <form id="goalForm" style="display: grid; grid-template-columns: auto 1fr 1fr 1fr auto; gap: 12px; margin-top: 16px; align-items: end;">
                <div>
                    <label style="font-size:0.8rem; color:var(--text-muted); display:block; margin-bottom:4px;">&#304;kon</label>
                    <select name="icon" class="input-glass" style="width: 60px; text-align: center; font-size: 1.2rem; padding: 10px;">
                        <option>&#127919;</option>
                        <option>&#128187;</option>
                        <option>&#9992;&#65039;</option>
                        <option>&#128663;</option>
                        <option>&#127968;</option>
                        <option>&#128218;</option>
                        <option>&#128176;</option>
                        <option>&#127873;</option>
                        <option>&#129297;</option>
                        <option>&#128241;</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:0.8rem; color:var(--text-muted); display:block; margin-bottom:4px;">Hedef Ad&#305;</label>
                    <input type="text" name="title" required placeholder="Yeni Laptop, Yaz Tatili..." class="input-glass" style="width:100%;">
                </div>
                <div>
                    <label style="font-size:0.8rem; color:var(--text-muted); display:block; margin-bottom:4px;">Hedef Tutar (&#8378;)</label>
                    <input type="number" name="target_amount" required min="1" step="0.01" placeholder="10000" class="input-glass" style="width:100%;">
                </div>
                <div>
                    <label style="font-size:0.8rem; color:var(--text-muted); display:block; margin-bottom:4px;">Hedef Tarih</label>
                    <input type="date" name="deadline" class="input-glass" style="width:100%;">
                </div>
                <button type="submit" class="btn-premium-submit ripple-btn" style="padding: 10px 24px; margin: 0;">Ekle</button>
            </form>
        </div>

        <!-- Hedef Kartları -->
        <div id="goalsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
            <?php if (empty($goals)): ?>
                <div class="glass-card" style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
                    <div style="font-size: 3.5rem; margin-bottom: 16px; opacity: 0.3;">&#127919;</div>
                    <p style="color: var(--text-muted); font-size: 1rem;">Hen&#252;z hedef olu&#351;turmad&#305;n. Yukar&#305;dan ilk hedefini ekle!</p>
                </div>
            <?php else: ?>
                <?php foreach ($goals as $goal):
                    $pct = $goal['target_amount'] > 0 ? min(100, ($goal['current_amount'] / $goal['target_amount']) * 100) : 0;
                    $remaining = max(0, $goal['target_amount'] - $goal['current_amount']);
                    $daysLeft = $goal['deadline'] ? max(0, (int)((strtotime($goal['deadline']) - time()) / 86400)) : null;
                    $dailySave = ($daysLeft && $daysLeft > 0) ? round($remaining / $daysLeft) : null;
                    $isComplete = (bool)$goal['is_completed'];
                ?>
                <div class="glass-card cascade-in goal-card <?php echo $isComplete ? 'goal-completed' : ''; ?>" data-id="<?php echo $goal['id']; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="goal-icon-box <?php echo $isComplete ? 'kumbara-bounce' : ''; ?>"><?php echo $goal['icon']; ?></div>
                            <div>
                                <h4 style="font-size: 1rem; font-weight: 700; color: var(--text-primary);"><?php echo htmlspecialchars($goal['title']); ?></h4>
                                <?php if ($daysLeft !== null): ?>
                                    <span style="font-size: 0.75rem; color: <?php echo $daysLeft < 7 ? 'var(--accent-red)' : 'var(--text-muted)'; ?>;">
                                        <?php echo $daysLeft; ?> g&#252;n kald&#305;
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="display: flex; gap: 6px;">
                            <?php if (!$isComplete): ?>
                            <button class="goal-action-btn add-btn" onclick="openAddFund(<?php echo $goal['id']; ?>)" title="Para Ekle">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                            <?php endif; ?>
                            <button class="goal-action-btn del-btn" onclick="deleteGoal(<?php echo $goal['id']; ?>, event)" title="Sil">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Progress -->
                    <div style="margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 6px;">
                            <span style="color: var(--text-secondary);">&#8378;<?php echo number_format((float)($goal['current_amount']), 0, ',', '.'); ?></span>
                            <span style="color: var(--text-muted);">&#8378;<?php echo number_format((float)($goal['target_amount']), 0, ',', '.'); ?></span>
                        </div>
                        <div style="height: 8px; background: rgba(255,255,255,0.06); border-radius: 4px; overflow: hidden;">
                            <div class="progress-animate" style="width: <?php echo $pct; ?>%; height: 100%; background: <?php echo $isComplete ? 'var(--accent-green)' : 'var(--accent-cyan)'; ?>; border-radius: 4px; box-shadow: 0 0 10px <?php echo $isComplete ? 'rgba(57,255,20,0.4)' : 'rgba(0,240,255,0.4)'; ?>;"></div>
                        </div>
                        <div style="text-align: right; font-family: var(--font-mono); font-size: 0.75rem; color: <?php echo $isComplete ? 'var(--accent-green)' : 'var(--accent-cyan)'; ?>; margin-top: 4px; font-weight: 700;">
                            <?php echo $isComplete ? '&#10004; Tamamland&#305;!' : round($pct) . '%'; ?>
                        </div>
                    </div>

                    <?php if ($dailySave && !$isComplete): ?>
                    <div style="padding: 10px 14px; background: rgba(0,136,255,0.06); border-radius: 8px; border: 1px solid rgba(0,136,255,0.15); font-size: 0.8rem; color: var(--text-secondary);">
                        &#128161; G&#252;nl&#252;k <strong style="color: var(--accent-cyan);">&#8378;<?php echo number_format((float)($dailySave), 0, ',', '.'); ?></strong> biriktirirsen hedefe ula&#351;&#305;rs&#305;n!
                    </div>
                    <?php endif; ?>

                    <?php if ($isComplete): ?>
                    <div style="padding: 10px 14px; background: rgba(57,255,20,0.06); border-radius: 8px; border: 1px solid rgba(57,255,20,0.2); font-size: 0.85rem; color: var(--accent-green); text-align: center; font-weight: 600;">
                        &#127881; Tebrikler! Hedefe ula&#351;t&#305;n!
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        </div> <!-- End .profile-overhaul-container -->
    </main>

    <!-- Add Fund Modal -->
    <div id="addFundModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2 class="modal-title">&#128176; Para Ekle</h2>
                <button class="modal-close" onclick="closeAddFund()">&times;</button>
            </div>
            <form id="addFundForm">
                <input type="hidden" id="fundGoalId" name="goal_id">
                <div class="form-group-premium">
                    <label>Eklenecek Tutar (&#8378;)</label>
                    <input type="number" name="amount" id="fundAmount" class="input-glass" required min="0.01" step="0.01" placeholder="500">
                </div>
                <button type="submit" class="btn-premium-submit ripple-btn">Biriktir &#127919;</button>
            </form>
        </div>
    </div>

    <style>
        .premium-widgets-row { display: grid; gap: 20px; }
        .goal-icon-box {
            width: 48px; height: 48px; border-radius: 14px;
            background: rgba(0,136,255,0.1); border: 1px solid rgba(0,136,255,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; flex-shrink: 0;
        }
        .goal-completed .goal-icon-box {
            background: rgba(57,255,20,0.1); border-color: rgba(57,255,20,0.3);
        }
        .goal-completed { opacity: 0.7; }
        .goal-action-btn {
            width: 30px; height: 30px; border-radius: 8px; border: none;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; transition: all 0.2s;
        }
        .add-btn { background: rgba(0,240,255,0.1); color: var(--accent-cyan); }
        .add-btn:hover { background: rgba(0,240,255,0.2); transform: scale(1.1); }
        .del-btn { background: rgba(255,42,42,0.1); color: var(--accent-red); }
        .del-btn:hover { background: rgba(255,42,42,0.2); transform: scale(1.1); }
        .input-glass {
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px; padding: 10px 12px; color: var(--text-primary);
            font-family: var(--font-primary); outline: none; transition: border 0.2s;
        }
        .input-glass:focus { border-color: var(--accent-cyan); }
        .modal-overlay { display:none; position:fixed; inset:0; z-index:5000; background:rgba(0,0,0,0.7); backdrop-filter:blur(6px); align-items:center; justify-content:center; }
        .modal-overlay.active { display:flex; }
        .modal-content { background:var(--bg-card); border:var(--border-glass); border-radius:16px; padding:28px; width:90%; max-width:500px; }
        .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .modal-title { font-family:var(--font-display); font-size:1.2rem; font-weight:700; }
        .modal-close { background:none; border:none; color:var(--text-muted); font-size:1.5rem; cursor:pointer; }
        @media (max-width: 768px) {
            #goalForm { grid-template-columns: 1fr 1fr; }
            #goalsGrid { grid-template-columns: 1fr; }
            .premium-widgets-row { grid-template-columns: 1fr !important; }
        }
    </style>

    <script>
    // Goal Form Submit
    document.getElementById('goalForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        fd.append('action', 'create');
        if (window.CSRF_TOKEN) fd.append('csrf_token', window.CSRF_TOKEN);
        try {
            const res = await fetch('api/goals_handler.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                if (typeof showToast === 'function') showToast('Hedef oluşturuldu! 🎯', 'success');
                setTimeout(() => location.reload(), 600);
            } else {
                if (typeof showToast === 'function') showToast(data.message || 'Hata oluştu', 'error');
            }
        } catch(err) {
            if (typeof showToast === 'function') showToast('Bağlantı hatası', 'error');
        }
    });

    // Add Fund Modal
    function openAddFund(goalId) {
        document.getElementById('fundGoalId').value = goalId;
        document.getElementById('fundAmount').value = '';
        document.getElementById('addFundModal').classList.add('active');
    }
    function closeAddFund() {
        document.getElementById('addFundModal').classList.remove('active');
    }
    document.getElementById('addFundModal').addEventListener('click', (e) => {
        if (e.target === e.currentTarget) closeAddFund();
    });

    document.getElementById('addFundForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        fd.append('action', 'add_fund');
        if (window.CSRF_TOKEN) fd.append('csrf_token', window.CSRF_TOKEN);
        try {
            const res = await fetch('api/goals_handler.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                if (data.completed && typeof launchConfetti === 'function') {
                    launchConfetti(80);
                    if (typeof showToast === 'function') showToast('🎉 Tebrikler! Hedefe ulaştın!', 'success', 5000);
                } else {
                    if (typeof showToast === 'function') showToast('Para eklendi! 💰', 'success');
                }
                setTimeout(() => location.reload(), 1200);
            }
        } catch(err) {
            if (typeof showToast === 'function') showToast('Bağlantı hatası', 'error');
        }
    });

    // Delete Goal
    async function deleteGoal(id, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        if (!confirm('Bu hedefi silmek istiyor musunuz?')) return;
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('goal_id', id);
        if (window.CSRF_TOKEN) fd.append('csrf_token', window.CSRF_TOKEN);
        
        try {
            const res = await fetch('api/goals_handler.php', { method: 'POST', body: fd, credentials: 'same-origin' });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                if(typeof showToast === 'function') showToast(data.message || 'Silme işlemi başarısız', 'error');
                else alert('Hata: ' + (data.message || 'Silinemedi'));
            }
        } catch(err) {
            console.error(err);
            alert('Bağlantı hatası: Sunucu ile iletişim kurulamadı.');
        }
    }
    </script>
</body>
</html>
