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

$user_id     = $_SESSION['user_id'];
$currentUser = $auth->getCurrentUser();

// Silme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    // CSRF doğrulaması
    $token = $_POST['csrf_token'] ?? null;
    if (!csrf_validate($token)) {
        header('Location: transactions.php?error=csrf');
        exit;
    }
    $del_id = (int)$_POST['delete_id'];
    $stmt   = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->execute([$del_id, $user_id]);
    header('Location: transactions.php?deleted=1');
    exit;
}

// Filtre
$type_filter = $_GET['type'] ?? 'all';
$sql    = "SELECT * FROM transactions WHERE user_id = ?";
$params = [$user_id];
if (in_array($type_filter, ['income','expense'])) {
    $sql .= " AND type = ?";
    $params[] = $type_filter;
}
$sql .= " ORDER BY transaction_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Toplamlar
$stmt = $pdo->prepare("SELECT
    COALESCE(SUM(CASE WHEN type='income'  THEN amount ELSE 0 END),0) AS total_in,
    COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END),0) AS total_out
    FROM transactions WHERE user_id = ?");
$stmt->execute([$user_id]);
$totals  = $stmt->fetch();
$balance = $totals['total_in'] - $totals['total_out'];

// Kullanıcı adı initials
$initials = '';
$names = explode(' ', $currentUser['full_name'] ?? 'K');
foreach ($names as $n) $initials .= strtoupper(substr($n, 0, 1));
$initials = substr($initials, 0, 2) ?: 'KU';

// Kategori ikonları
$catIcons = [
    'Kira'         => 'fa-home',
    'Yemek'        => 'fa-utensils',
    'Ulaşım'       => 'fa-bus',
    'Eğitim'       => 'fa-book',
    'Kişisel'      => 'fa-user',
    'Sosyal'       => 'fa-music',
    'Faturalar'    => 'fa-bolt',
    'Acil Durum'   => 'fa-triangle-exclamation',
    'Maaş'         => 'fa-briefcase',
    'Burs'         => 'fa-graduation-cap',
    'KYK Kredisi'  => 'fa-landmark',
    'Ailevi Destek'=> 'fa-heart',
];
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harcamalarım — ÜniBütçe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/mobile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/animations.css?v=<?php echo time(); ?>">
    <script src="js/animations.js?v=<?php echo time(); ?>" defer></script>
    <meta name="csrf-token" content="<?= htmlspecialchars($__csrf, ENT_QUOTES) ?>">
    <script>window.CSRF_TOKEN = <?= json_encode($__csrf) ?>;</script>
    <script src="js/utils.js?v=<?php echo time(); ?>" defer></script>
    <style>
        /* ¦¦ Transactions-specific styles ¦¦ */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }
        @media (max-width: 768px) { .summary-grid { grid-template-columns: 1fr; } }

        .summary-card {
            background: rgba(18,18,42,0.7);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 20px;
            padding: 22px 24px;
            display: flex;
            align-items: center;
            gap: 18px;
            backdrop-filter: blur(12px);
            transition: border-color 0.3s ease;
        }
        .summary-card:hover { border-color: rgba(255,255,255,0.12); }
        .summary-icon {
            width: 52px; height: 52px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }
        .summary-icon.income  { background: rgba(0,230,118,0.12); color: #00e676; }
        .summary-icon.expense { background: rgba(255,82,82,0.12);  color: #ff5252; }
        .summary-icon.balance { background: rgba(79,140,255,0.12); color: #4f8cff; }
        .summary-label { font-size: 0.8rem; color: var(--text-secondary); font-weight: 500; margin-bottom: 4px; }
        .summary-value { font-size: 1.45rem; font-weight: 800; font-family: var(--font-display); }
        .summary-value.income  { color: #00e676; }
        .summary-value.expense { color: #ff5252; }

        /* Transactions table card */
        .tx-card {
            background: rgba(14,14,36,0.8);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 20px;
            overflow: hidden;
            backdrop-filter: blur(16px);
        }
        .tx-card-header {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
        }
        .tx-count {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .tx-count .count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(79,140,255,0.15);
            color: var(--accent-blue);
            border: 1px solid rgba(79,140,255,0.25);
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 2px 10px;
            min-width: 28px;
        }
        .filter-tabs {
            display: flex;
            gap: 6px;
        }
        .filter-tab {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            color: var(--text-secondary);
            background: rgba(255,255,255,0.04);
        }
        .filter-tab:hover { background: rgba(255,255,255,0.08); color: var(--text-primary); }
        .filter-tab.is-active-all     { background: rgba(99,102,241,0.18); color: #a5b4fc; border-color: rgba(99,102,241,0.35); }
        .filter-tab.is-active-income  { background: rgba(0,230,118,0.12); color: #00e676; border-color: rgba(0,230,118,0.3); }
        .filter-tab.is-active-expense { background: rgba(255,82,82,0.12);  color: #ff5252; border-color: rgba(255,82,82,0.3); }

        /* Table */
        .tx-table { width: 100%; border-collapse: collapse; }
        .tx-table th {
            padding: 14px 20px;
            text-align: left;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: var(--text-muted);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            white-space: nowrap;
        }
        .tx-table th:last-child { text-align: center; }
        .tx-table td {
            padding: 14px 20px;
            font-size: 0.9rem;
            color: var(--text-primary);
            border-bottom: 1px solid rgba(255,255,255,0.03);
            vertical-align: middle;
        }
        .tx-table tr:last-child td { border-bottom: none; }
        .tx-table tr:hover td { background: rgba(255,255,255,0.018); }

        /* Category badge */
        .cat-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .cat-pill.income  { background: rgba(0,230,118,0.1);  color: #00e676; border: 1px solid rgba(0,230,118,0.2); }
        .cat-pill.expense { background: rgba(255,82,82,0.1);  color: #ff5252; border: 1px solid rgba(255,82,82,0.2); }

        .tx-amount { font-weight: 700; font-size: 0.95rem; }
        .tx-amount.income  { color: #00e676; }
        .tx-amount.expense { color: #ff5252; }

        .tx-date { color: var(--text-muted); font-size: 0.83rem; white-space: nowrap; }
        .tx-desc { color: var(--text-secondary); font-size: 0.88rem; }

        .btn-delete {
            background: rgba(255,82,82,0.08);
            border: 1px solid rgba(255,82,82,0.2);
            color: #ff5252;
            width: 32px; height: 32px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.82rem;
            transition: all 0.2s ease;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto;
        }
        .btn-delete:hover { background: rgba(255,82,82,0.2); border-color: rgba(255,82,82,0.4); transform: scale(1.05); }

        /* Empty state */
        .tx-empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        .tx-empty i { font-size: 2.8rem; margin-bottom: 12px; display: block; opacity: 0.35; }
        .tx-empty p { font-size: 0.95rem; }

        /* Success toast */
        .success-toast {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(0,230,118,0.1);
            border: 1px solid rgba(0,230,118,0.25);
            color: #00e676;
            padding: 12px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
    </style>
</head>
<body class="dashboard-body">

    <?php include 'includes/dashboard_navbar.php'; ?>

    <main class="main-content">
        <!-- Topbar -->
        <header class="topbar">
            <div class="welcome-text">
                <h1>Tüm Hareketler</h1>
                <p>Geriye dönük tüm gelir ve giderlerini incele, yönet.</p>
            </div>
            <div class="topbar-actions">
                <div class="date-display">
                    <i class="fa-regular fa-calendar"></i>
                    <?php echo strftime('%d %B %Y') ?: date('d F Y'); ?>
                </div>
                <div class="profile-mini"><?php echo $initials; ?></div>
            </div>
        </header>

        <?php if (isset($_GET['deleted'])): ?>
        <div class="success-toast">
            <i class="fa-solid fa-circle-check"></i>
            İşlem başarıyla silindi.
        </div>
        <?php endif; ?>

        <div class="profile-overhaul-container">

        <!-- Hızlı İşlemler -->
        <div class="glass-card widget-quick-actions" style="margin-bottom: 25px;">
            <div class="widget-header"><h3>Hızlı İşlemler</h3></div>
            <div class="quick-actions-container" style="display: flex; gap: 15px; margin-top: 15px;">
                <button class="quick-btn btn-income" onclick="openModal('income')" style="flex: 1; padding: 15px; background: rgba(0,230,118,0.1); color: #00e676; border: 1px solid rgba(0,230,118,0.3); border-radius: 12px; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s; font-size: 1rem;">
                    <i class="fa-solid fa-plus"></i> Gelir Ekle
                </button>
                <button class="quick-btn btn-expense" onclick="openModal('expense')" style="flex: 1; padding: 15px; background: rgba(255,82,82,0.1); color: #ff5252; border: 1px solid rgba(255,82,82,0.3); border-radius: 12px; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s; font-size: 1rem;">
                    <i class="fa-solid fa-minus"></i> Gider Ekle
                </button>
            </div>
            <style>
                .quick-btn.btn-income:hover { background: rgba(0,230,118,0.2) !important; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,230,118,0.2); }
                .quick-btn.btn-expense:hover { background: rgba(255,82,82,0.2) !important; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255,82,82,0.2); }
            </style>
        </div>

        <!-- Özet Kartları -->
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-icon income"><i class="fa-solid fa-arrow-trend-up"></i></div>
                <div>
                    <div class="summary-label">Toplam Gelir</div>
                    <div class="summary-value income">&#8378;<?php echo number_format((float)($totals['total_in']), 0, ',', '.'); ?></div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon expense"><i class="fa-solid fa-arrow-trend-down"></i></div>
                <div>
                    <div class="summary-label">Toplam Gider</div>
                    <div class="summary-value expense">&#8378;<?php echo number_format((float)($totals['total_out']), 0, ',', '.'); ?></div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon balance"><i class="fa-solid fa-scale-balanced"></i></div>
                <div>
                    <div class="summary-label">Net Bakiye</div>
                    <div class="summary-value <?php echo $balance >= 0 ? 'income' : 'expense'; ?>" style="color:<?php echo $balance >= 0 ? '#00e676' : '#ff5252'; ?>">
                        <?php echo $balance >= 0 ? '+' : ''; ?>&#8378;<?php echo number_format((float)(abs($balance)), 0, ',', '.'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- İşlem Tablosu -->
        <div class="tx-card">
            <div class="tx-card-header">
                <div class="tx-count">
                    <span>İşlemler</span>
                    <span class="count-badge"><?php echo count($transactions); ?></span>
                </div>
                <div class="filter-tabs">
                    <a href="?type=all"
                       class="filter-tab <?php echo $type_filter === 'all' ? 'is-active-all' : ''; ?>">
                        <i class="fa-solid fa-list"></i> Tümü
                    </a>
                    <a href="?type=income"
                       class="filter-tab <?php echo $type_filter === 'income' ? 'is-active-income' : ''; ?>">
                        <i class="fa-solid fa-arrow-up"></i> Gelirler
                    </a>
                    <a href="?type=expense"
                       class="filter-tab <?php echo $type_filter === 'expense' ? 'is-active-expense' : ''; ?>">
                        <i class="fa-solid fa-arrow-down"></i> Giderler
                    </a>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="tx-table">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th>Açıklama</th>
                            <th>Tarih</th>
                            <th style="text-align:right;">Tutar</th>
                            <th>Sil</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="tx-empty">
                                    <i class="fa-regular fa-folder-open"></i>
                                    <p>Henüz bir işlem bulunmuyor. Dashboard'dan ekleyebilirsin.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($transactions as $tx): ?>
                        <?php
                            $iconClass = $catIcons[$tx['category']] ?? ($tx['type'] === 'income' ? 'fa-circle-plus' : 'fa-circle-minus');
                        ?>
                        <tr>
                            <td>
                                <span class="cat-pill <?php echo $tx['type']; ?>">
                                    <i class="fa-solid <?php echo $iconClass; ?>"></i>
                                    <?php echo htmlspecialchars($tx['category']); ?>
                                </span>
                            </td>
                            <td class="tx-desc">
                                <?php echo htmlspecialchars($tx['description'] ?: '—'); ?>
                            </td>
                            <td class="tx-date">
                                <?php echo date('d.m.Y', strtotime($tx['transaction_date'])); ?>
                                <br>
                                <span style="font-size:0.75rem"><?php echo date('H:i', strtotime($tx['transaction_date'])); ?></span>
                            </td>
                            <td style="text-align:right;">
                                <span class="tx-amount <?php echo $tx['type']; ?>">
                                    <?php echo $tx['type'] === 'income' ? '+' : '-'; ?>&#8378;<?php echo number_format((float)($tx['amount']), 2, ',', '.'); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Bu işlemi silmek istiyor musunuz?');" style="display:flex; justify-content:center;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($__csrf, ENT_QUOTES) ?>">
                                    <input type="hidden" name="delete_id" value="<?php echo $tx['id']; ?>">
                                    <button type="submit" class="btn-delete" title="Sil">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
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
        document.getElementById('transactionForm').reset();
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
            
            setTimeout(() => {
                progress.style.display = 'none';
                document.getElementById('txAmount').value = "120.50";
                document.getElementById('txCategory').value = "Gıda / Market";
                document.getElementById('txDesc').value = "AI Tarama: " + input.files[0].name;
                
                const container = document.getElementById('ocrScannerBox');
                container.style.border = "1px solid var(--accent-green)";
                setTimeout(() => container.style.border = "none", 2000);
            }, 1500);
        }
    }

    async function handleTransaction(e) {
        e.preventDefault();
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerText = "İşleniyor...";

        const formData = new FormData(form);
        if (window.CSRF_TOKEN) formData.append('csrf_token', window.CSRF_TOKEN);
        try {
            const res = await fetch('api/transaction_handler.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || "İşlem sırasında bir hata oluştu.");
                submitBtn.disabled = false;
                submitBtn.innerText = "Onayla ve Kaydet";
            }
        } catch (err) {
            console.error(err);
            alert("Sunucu ile bağlantı kurulamadı.");
            submitBtn.disabled = false;
            submitBtn.innerText = "Onayla ve Kaydet";
        }
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

