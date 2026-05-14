<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_system.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/digest_generator.php';
$__csrf = csrf_token();

$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) { header('Location: index.php#hero'); exit; }
$currentUser = $auth->getCurrentUser();
$user_id = (int)$_SESSION['user_id'];

$digest = generate_weekly_digest($pdo, $user_id);
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İçgörüler — ÜniBütçe</title>
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
                <h1>🔍 Finansal İçgörüler</h1>
                <p>Harcamalarında olağandışı bir şey mi var? Hangi abonelikler sızıntı yapıyor?</p>
            </div>
            <div class="topbar-actions">
                <div class="date-display">
                    <i class="fa-regular fa-calendar"></i>
                    <?php echo date('d F Y'); ?>
                </div>
            </div>
        </header>
        <div class="profile-overhaul-container">

        <!-- Weekly Digest -->
        <div class="glass-card" style="padding:24px; margin-bottom:20px;">
            <div class="widget-header"><h3><i class="fa-solid fa-chart-bar"></i> Bu Hafta</h3></div>
            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:16px; text-align:center; margin-top:16px;">
                <div>
                    <div style="opacity:.6; font-size:.85rem;">Toplam Gider</div>
                    <div style="font-size:1.8rem; font-weight:800;"><?= number_format($digest['totals']['expense'], 0, ',', '.') ?>₺</div>
                </div>
                <div>
                    <div style="opacity:.6; font-size:.85rem;">Net</div>
                    <div style="font-size:1.8rem; font-weight:800; color:<?= $digest['totals']['net'] >= 0 ? '#39ff14' : '#ff3b30' ?>;">
                        <?= $digest['totals']['net'] >= 0 ? '+' : '' ?><?= number_format($digest['totals']['net'], 0, ',', '.') ?>₺
                    </div>
                </div>
                <div>
                    <div style="opacity:.6; font-size:.85rem;">Geçen Haftaya Göre</div>
                    <div style="font-size:1.8rem; font-weight:800; color:<?= $digest['totals']['delta_pct_vs_prior'] <= 0 ? '#39ff14' : '#ff9100' ?>;">
                        <?= $digest['totals']['delta_pct_vs_prior'] > 0 ? '+' : '' ?><?= $digest['totals']['delta_pct_vs_prior'] ?>%
                    </div>
                </div>
            </div>
            <?php if ($digest['ai_summary']): ?>
                <div style="margin-top:20px; padding:16px; background:rgba(125,92,255,.08); border-radius:10px; border-left:3px solid #7d5cff;">
                    <div style="font-size:.85rem; opacity:.7; margin-bottom:8px;">🤖 Claude'un Haftalık Özeti</div>
                    <div style="white-space:pre-wrap; line-height:1.6;"><?= htmlspecialchars($digest['ai_summary']) ?></div>
                </div>
            <?php else: ?>
                <ul style="margin-top:16px; padding-left:20px; line-height:1.9;">
                    <?php foreach ($digest['tips'] as $tip): ?>
                        <li><?= htmlspecialchars($tip) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Anomaly Detection -->
        <div class="glass-card" style="padding:24px; margin-bottom:20px;">
            <div class="widget-header"><h3><i class="fa-solid fa-triangle-exclamation"></i> Anomali Alarmları</h3></div>
            <p style="opacity:.8; margin-top:12px;">Harcamalarında ortalamanın çok üzerinde olan kayıtları buluyoruz (z-score > 2.2).</p>
            <button id="ubScanBtn" class="btn-premium-submit" style="margin-top:8px;">🔎 Şimdi Tara</button>
            <div id="ubAnomList" style="margin-top:20px;"></div>
        </div>

        <!-- Subscription Detection -->
        <div class="glass-card" style="padding:24px; margin-bottom:20px;">
            <div class="widget-header"><h3><i class="fa-solid fa-rotate"></i> Yakalanan Abonelikler</h3></div>
            <p style="opacity:.8; margin-top:12px;">İşlem açıklamalarından tekrar eden ödemeleri tespit ediyoruz.</p>
            <button id="ubSubScan" class="btn-premium-submit" style="margin-top:8px;">🔎 Taramayı Başlat</button>
            <div id="ubSubList" style="margin-top:20px;"></div>
        </div>

        <!-- Top Categories -->
        <?php if ($digest['top_categories']): ?>
        <div class="glass-card" style="padding:24px;">
            <div class="widget-header"><h3><i class="fa-solid fa-credit-card"></i> En Çok Harcadığın Kategoriler (7 gün)</h3></div>
            <div style="margin-top:16px;">
            <?php
            $maxAmt = max(array_column($digest['top_categories'], 'amount')) ?: 1;
            foreach ($digest['top_categories'] as $c):
                $pct = ($c['amount'] / $maxAmt) * 100;
            ?>
                <div style="margin-bottom:12px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                        <span><?= htmlspecialchars($c['category']) ?></span>
                        <span style="font-weight:700;"><?= number_format($c['amount'], 0, ',', '.') ?>₺</span>
                    </div>
                    <div style="height:8px; background:rgba(255,255,255,.06); border-radius:4px; overflow:hidden;">
                        <div style="height:100%; width:<?= $pct ?>%; background:linear-gradient(90deg,#0088ff,#7d5cff);"></div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        </div>
    </main>

<script>
const api = (u, opts = {}) => fetch(u, {
    credentials: 'same-origin',
    headers: { 'X-CSRF-Token': window.CSRF_TOKEN || '', ...(opts.headers || {}) },
    ...opts
}).then(r => r.json());

async function loadAnomalies() {
    const r = await api('api/anomaly_detector.php');
    const box = document.getElementById('ubAnomList');
    if (!r.success || !r.anomalies?.length) {
        box.innerHTML = '<p style="opacity:.6;">Henüz anomali tespit edilmedi. ✨</p>';
        return;
    }
    box.innerHTML = r.anomalies.map(a => `
        <div style="background:rgba(255,59,48,.08); border-left:3px solid #ff3b30; padding:12px 16px; border-radius:8px; margin-bottom:10px;">
            <div style="font-weight:600;">${a.category} · ${parseFloat(a.amount).toLocaleString('tr-TR')}₺ (z=${a.zscore})</div>
            <div style="opacity:.8; font-size:.9rem; margin-top:4px;">${a.reason}</div>
            <button onclick="dismiss(${a.id})" style="margin-top:8px; background:transparent; border:1px solid rgba(255,255,255,.2); color:#fff; padding:4px 10px; border-radius:6px; cursor:pointer; font-size:.8rem;">Gizle</button>
        </div>
    `).join('');
}

async function dismiss(id) {
    const fd = new FormData(); fd.append('action','dismiss'); fd.append('id', id);
    await api('api/anomaly_detector.php', { method:'POST', body: fd });
    loadAnomalies();
}

document.getElementById('ubScanBtn').addEventListener('click', async () => {
    const fd = new FormData(); fd.append('action', 'scan');
    const btn = document.getElementById('ubScanBtn');
    btn.disabled = true; btn.textContent = 'Taranıyor...';
    const r = await api('api/anomaly_detector.php', { method:'POST', body: fd });
    btn.disabled = false; btn.textContent = '🔎 Şimdi Tara';
    if (r.success) {
        alert(`${r.scanned} işlem tarandı, ${r.flagged} anomali bulundu.`);
        loadAnomalies();
    }
});

async function loadSubs() {
    const r = await api('api/subscription_detect.php');
    const box = document.getElementById('ubSubList');
    if (!r.success || !r.detected?.length) {
        box.innerHTML = '<p style="opacity:.6;">Henüz tekrar eden ödeme tespit edilmedi.</p>';
        return;
    }
    box.innerHTML = r.detected.map(s => `
        <div class="glass-card" style="padding:14px 18px; margin-bottom:10px;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
                <div>
                    <b>${s.name}</b> · ${parseFloat(s.amount).toLocaleString('tr-TR')}₺/ay
                    <div style="opacity:.6; font-size:.82rem;">${s.occurrences} defa tekrar · Son: ${new Date(s.last_seen).toLocaleDateString('tr-TR')}</div>
                </div>
                <button onclick="addSub(${JSON.stringify(s.name)}, ${s.amount}, ${JSON.stringify(s.category)})"
                        class="btn-premium-submit" style="padding:6px 14px; font-size:.85rem;">+ Aboneliklerime Ekle</button>
            </div>
        </div>
    `).join('');
}

async function addSub(name, amount, category) {
    const fd = new FormData();
    fd.append('name', name); fd.append('amount', amount);
    fd.append('category', category); fd.append('billing_cycle', 'monthly');
    const r = await api('api/subscription_detect.php', { method:'POST', body: fd });
    if (r.success) alert('Eklendi! Aboneliklerim sayfasından görebilirsin.');
    else alert(r.message);
}

document.getElementById('ubSubScan').addEventListener('click', loadSubs);
loadAnomalies();
</script>
</body>
</html>
