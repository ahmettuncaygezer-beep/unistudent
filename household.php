<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_system.php';
require_once __DIR__ . '/includes/csrf.php';
$__csrf = csrf_token();

$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) { header('Location: index.php#hero'); exit; }
$currentUser = $auth->getCurrentUser();
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ev Bütçesi — ÜniBütçe</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/auth.css?v=<?php echo time(); ?>">
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
                <h1>👥 Paylaşımlı Ev Bütçesi</h1>
                <p>Ev arkadaşlarınla ortak giderleri ekle, kim kime ne borçlu otomatik hesaplansın.</p>
            </div>
            <div class="topbar-actions">
                <div class="date-display">
                    <i class="fa-regular fa-calendar"></i>
                    <?php echo date('d F Y'); ?>
                </div>
            </div>
        </header>
        <div class="profile-overhaul-container">

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:32px;">
            <div class="glass-card" style="padding:24px;">
                <h3 style="margin-top:0; color:var(--accent-cyan);"><i class="fa-solid fa-plus"></i> Yeni Bütçe Oluştur</h3>
                <form id="ubCreateForm">
                    <input type="text" name="name" placeholder="Ev/Bütçe adı (ör. Daire 3A)" required class="input-glass" style="width:100%; margin-bottom:12px;">
                    <button type="submit" class="btn-premium-submit" style="width:100%;">Oluştur</button>
                </form>
            </div>
            <div class="glass-card" style="padding:24px;">
                <h3 style="margin-top:0; color:var(--accent-purple);"><i class="fa-solid fa-link"></i> Davet Koduyla Katıl</h3>
                <form id="ubJoinForm">
                    <input type="text" name="invite_code" placeholder="Davet kodu" required class="input-glass" style="width:100%; margin-bottom:12px;">
                    <button type="submit" class="btn-premium-submit" style="width:100%; background:linear-gradient(135deg, #7d5cff, #a855f7);">Katıl</button>
                </form>
            </div>
        </div>

        <div class="glass-card" style="padding:24px;">
            <div class="widget-header"><h3><i class="fa-solid fa-folder-open"></i> Bütçelerim</h3></div>
            <div id="ubBudgetList"><p style="opacity:.6;">Henüz paylaşımlı bütçen yok.</p></div>
        </div>

        <div id="ubBudgetDetail" style="margin-top:24px;"></div>

        </div>
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

<script>
const api = (u, opts = {}) => fetch(u, {
    credentials: 'same-origin',
    headers: { 'X-CSRF-Token': window.CSRF_TOKEN || '', ...(opts.headers || {}) },
    ...opts
}).then(r => r.json());

async function loadBudgets() {
    const r = await api('api/shared_budget.php?action=list');
    const list = document.getElementById('ubBudgetList');
    if (!r.success || !r.budgets?.length) {
        list.innerHTML = '<p style="opacity:.6;">Henüz paylaşımlı bütçen yok.</p>';
        return;
    }
    list.innerHTML = r.budgets.map(b => `
        <div class="glass-card" style="cursor:pointer; padding:16px; margin-bottom:10px;" onclick="openBudget(${b.id}, '${b.invite_code}', ${JSON.stringify(b.name).replace(/"/g, '&quot;')})">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h3 style="margin:0;">${b.name}</h3>
                    <div style="opacity:.6; font-size:.85rem; margin-top:4px;">${b.member_count} üye · Davet: <code>${b.invite_code}</code></div>
                </div>
                <div style="font-size:1.5rem; color:var(--accent-cyan);">→</div>
            </div>
        </div>
    `).join('');
}

async function openBudget(id, code, name) {
    const r = await api('api/shared_budget.php?action=detail&id=' + id);
    if (!r.success) return alert(r.message || 'Hata');
    const d = document.getElementById('ubBudgetDetail');
    d.innerHTML = `
        <div class="glass-card" style="padding:24px;">
            <h3 style="margin-top:0;"><i class="fa-solid fa-plus"></i> Gider Ekle — ${name}</h3>
            <form id="ubExpForm" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <input type="number" step="0.01" name="amount" placeholder="Tutar" required class="input-glass">
                <input type="text" name="category" placeholder="Kategori (ör. Market)" class="input-glass">
                <input type="text" name="description" placeholder="Açıklama" style="grid-column: span 2;" class="input-glass">
                <button type="submit" class="btn-premium-submit" style="grid-column: span 2;">Ekle</button>
            </form>
        </div>

        <div class="glass-card" style="padding:24px; margin-top:16px;">
            <h3 style="margin-top:0;"><i class="fa-solid fa-scale-balanced"></i> Bakiyeler</h3>
            ${r.settlement.balances.map(b => `
                <div style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid rgba(255,255,255,.06);">
                    <span>${b.name}</span>
                    <span style="color:${b.balance >= 0 ? '#39ff14' : '#ff3b30'}; font-weight:700;">${b.balance >= 0 ? '+' : ''}${b.balance.toFixed(2)}₺</span>
                </div>`).join('')}
        </div>

        <div class="glass-card" style="padding:24px; margin-top:16px;">
            <h3 style="margin-top:0;"><i class="fa-solid fa-money-bill-transfer"></i> Ödemeler (Optimize)</h3>
            ${r.settlement.transfers.length ? r.settlement.transfers.map(t => `
                <div style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid rgba(255,255,255,.06);">
                    <span><b>${t.from_name}</b> → <b>${t.to_name}</b></span>
                    <span style="font-weight:700; color:#7d5cff;">${t.amount.toFixed(2)}₺</span>
                </div>`).join('') : '<p style="opacity:.6; margin:0;">Herkes eşit — ödeme gerekmez. 🎉</p>'}
        </div>

        <div class="glass-card" style="padding:24px; margin-top:16px;">
            <h3 style="margin-top:0;"><i class="fa-solid fa-receipt"></i> Son Giderler</h3>
            ${r.expenses.slice(0, 10).map(e => `
                <div style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid rgba(255,255,255,.06);">
                    <span><b>${e.payer_name}</b> — ${e.description || e.category}</span>
                    <span>${parseFloat(e.amount).toFixed(2)}₺</span>
                </div>`).join('') || '<p style="opacity:.6;">Henüz gider yok.</p>'}
        </div>
    `;

    document.getElementById('ubExpForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        fd.append('action', 'add_expense');
        fd.append('budget_id', id);
        const res = await api('api/shared_budget.php', { method: 'POST', body: fd });
        if (res.success) { openBudget(id, code, name); }
        else alert(res.message);
    });
    d.scrollIntoView({ behavior: 'smooth' });
}

document.getElementById('ubCreateForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target); fd.append('action', 'create');
    const r = await api('api/shared_budget.php', { method: 'POST', body: fd });
    if (r.success) loadBudgets(); else alert(r.message);
    e.target.reset();
});
document.getElementById('ubJoinForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target); fd.append('action', 'join');
    const r = await api('api/shared_budget.php', { method: 'POST', body: fd });
    if (r.success) loadBudgets(); else alert(r.message);
    e.target.reset();
});

loadBudgets();
</script>
</body>
</html>
