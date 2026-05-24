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
    <title>Burs Takipçisi — ÜniBütçe</title>
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
                <h1>🎓 Burs Takipçisi</h1>
                <p>Kaçırdığın burs — kaçırdığın para. Başvurularını takip et.</p>
            </div>
            <div class="topbar-actions">
                <div class="date-display">
                    <i class="fa-regular fa-calendar"></i>
                    <?php echo date('d F Y'); ?>
                </div>
            </div>
        </header>
        <div class="profile-overhaul-container">

        <div class="glass-card" style="padding:24px; margin-bottom:20px;">
            <div class="widget-header"><h3><i class="fa-solid fa-plus"></i> Yeni Burs Ekle</h3></div>
            <form id="ubScholForm" style="display:grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap:10px; margin-top:16px;">
                <input type="text" name="name" placeholder="Burs adı" required class="input-glass">
                <input type="number" step="0.01" name="amount" placeholder="Tutar ₺" class="input-glass">
                <input type="date" name="deadline" class="input-glass">
                <select name="status" class="input-glass">
                    <option value="planned">Planlanan</option>
                    <option value="applied">Başvuruldu</option>
                    <option value="awarded">Kazanıldı</option>
                    <option value="missed">Kaçırıldı</option>
                </select>
                <input type="text" name="notes" placeholder="Notlar (isteğe bağlı)" style="grid-column: span 3;" class="input-glass">
                <button type="submit" class="btn-premium-submit">Ekle</button>
            </form>
        </div>

        <div class="glass-card" style="padding:24px;">
            <div class="widget-header"><h3><i class="fa-solid fa-list"></i> Bursların</h3></div>
            <div id="ubScholList" style="margin-top:16px;"><p style="opacity:.6;">Yükleniyor...</p></div>
        </div>

        </div>
    </main>

<script>
const api = (u, opts = {}) => fetch(u, {
    credentials: 'same-origin',
    headers: { 'X-CSRF-Token': window.CSRF_TOKEN || '', ...(opts.headers || {}) },
    ...opts
}).then(r => r.json());

// HTML escape helper — XSS önlüyor
const esc = s => s ? String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#x27;') : '';

const STATUS_COLORS = {
    planned:  { label: 'Planlanan',   color: '#7d5cff' },
    applied:  { label: 'Başvuruldu',  color: '#ff9100' },
    awarded:  { label: 'Kazanıldı 🏆', color: '#39ff14' },
    missed:   { label: 'Kaçırıldı',    color: '#ff3b30' },
};

async function load() {
    const r = await api('api/scholarships.php');
    const list = document.getElementById('ubScholList');
    if (!r.success || !r.scholarships?.length) {
        list.innerHTML = '<p style="opacity:.6;">Henüz burs kaydın yok.</p>';
        return;
    }
    list.innerHTML = r.scholarships.map(s => {
        const st = STATUS_COLORS[s.status] || STATUS_COLORS.planned;
        const urgent = s.is_urgent ? `<span style="display:inline-block; padding:4px 10px; border-radius:999px; font-size:.75rem; font-weight:600; background:rgba(255,145,0,.15); color:#ff9100; border:1px solid rgba(255,145,0,.3); margin-top:8px;">⚠️ ${s.days_left} gün kaldı</span>` : '';
        return `<div class="glass-card" style="padding:16px; margin-bottom:10px;">
            <div style="display:flex; justify-content:space-between; align-items:start; gap:12px; flex-wrap:wrap;">
                <div style="flex:1;">
                    <h3 style="margin:0 0 6px;">${esc(s.name)}</h3>
                    <div style="opacity:.7; font-size:.9rem;">
                        ${s.amount > 0 ? `<b>${parseFloat(s.amount).toLocaleString('tr-TR')}₺</b> · ` : ''}
                        ${s.deadline ? 'Son tarih: ' + new Date(s.deadline).toLocaleDateString('tr-TR') : 'Tarihsiz'}
                        · <span style="color:${st.color};">${st.label}</span>
                    </div>
                    ${s.notes ? `<div style="margin-top:8px; opacity:.8; font-size:.88rem;">${esc(s.notes)}</div>` : ''}
                    ${urgent}
                </div>
                <button onclick="delSchol(${s.id})" style="background:transparent; border:1px solid rgba(255,59,48,.3); color:#ff3b30; padding:6px 12px; border-radius:8px; cursor:pointer;">Sil</button>
            </div>
        </div>`;
    }).join('');
}

async function delSchol(id) {
    if (!confirm('Silinsin mi?')) return;
    const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', id);
    await api('api/scholarships.php', { method: 'POST', body: fd });
    load();
}

document.getElementById('ubScholForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append('action', 'create');
    const r = await api('api/scholarships.php', { method: 'POST', body: fd });
    if (r.success) { e.target.reset(); load(); }
    else alert(r.message);
});

load();
</script>
</body>
</html>
