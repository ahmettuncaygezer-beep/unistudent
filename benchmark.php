<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_system.php';
$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) { header('Location: index.php#hero'); exit; }
$__title = 'Arkadaş Ligi — Anonim Karşılaştırma';
$__desc  = 'Senin yaşındaki öğrenciler ayda ne kadar market harcıyor? Anonim benchmark.';
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
require __DIR__ . '/includes/seo_tools_head.php';
?>
<h1>👥 Arkadaş Ligi</h1>
<p class="ub-lead">Verin hash'lenerek anonimleştirilir — kimliğinle bağlantı kurulmaz. Senin şehir + yaşındaki öğrencilerle kategori bazlı karşılaştır. <b>Kim kime bakıyor görülmez</b> — sadece sayılar.</p>

<div class="ub-card">
    <button id="ubBmSubmit" class="btn btn-primary" data-magnetic>📤 Bu Ayın Verimi Havuza Ekle</button>
    <p style="margin-top:10px; opacity:.7; font-size:.85rem;">Verin hash'lenir, kimliğinle bağı koparılır — sadece kategori toplamları saklanır. İstediğin zaman Gizlilik sayfasından tüm verini silebilirsin.</p>
</div>

<h2>📊 Karşılaştırma</h2>
<div id="ubBmProfileWarn" style="display:none;"></div>
<div id="ubBmList"><p style="opacity:.6;">Yükleniyor...</p></div>

<script>
const api = (u, opts={}) => fetch(u, {credentials:'same-origin', headers:{'X-CSRF-Token':window.CSRF_TOKEN||'', ...(opts.headers||{})}, ...opts}).then(r=>r.json());

async function load() {
    const r = await api('api/benchmark.php');
    const list = document.getElementById('ubBmList');
    if (!r.success || !r.results.length) { list.innerHTML = '<p style="opacity:.6;">Bu ay işlem yok ya da karşılaştırma için yeterli veri yok.</p>'; return; }
    // Fix 10: Profile completion check
    const warnBox = document.getElementById('ubBmProfileWarn');
    if (r.your_city === 'Diğer') {
        warnBox.style.display = 'block';
        warnBox.innerHTML = `<div class="ub-card" style="border-left:3px solid #ff9100; background:rgba(255,145,0,.08);">
            <b>⚠️ Profil bilgilerin eksik.</b> Şehir = "Diğer" olduğu için akran grubu eşleştirmesi genel ortalamalar üzerinden yapılıyor.
            <a href="profile.php" style="color:#0088ff; font-weight:600;">Profilini güncelle →</a>
        </div>`;
    } else { warnBox.style.display = 'none'; }
    list.innerHTML = `<p style="opacity:.75;">Şehir: <b>${r.your_city}</b> · Yaş: <b>${r.age_band}</b> · Dönem: <b>${r.period}</b></p>` +
        r.results.map(x => {
            const peer = x.peer_avg_same_city || x.peer_avg_all;
            const peerType = x.peer_avg_same_city ? 'Şehirdeki öğrenciler' : (x.peer_avg_all ? 'Tüm öğrenciler' : null);
            const diff = peer ? ((x.mine - peer) / peer * 100) : null;
            const color = diff === null ? '#888' : (diff <= 0 ? '#39ff14' : (diff < 20 ? '#ff9100' : '#ff3b30'));
            return `<div class="ub-card">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                    <div>
                        <h3 style="margin:0;">${x.category}</h3>
                        <div style="opacity:.7; font-size:.88rem;">Senin: <b>${parseFloat(x.mine).toLocaleString('tr-TR')}₺</b></div>
                    </div>
                    <div style="text-align:right;">
                        ${peer ? `
                            <div style="opacity:.7; font-size:.82rem;">${peerType} ortalaması</div>
                            <div style="font-size:1.25rem; font-weight:800;">${peer.toLocaleString('tr-TR')}₺</div>
                            <div style="color:${color}; font-weight:700;">${diff >= 0 ? '+' : ''}${diff.toFixed(0)}% ${diff <= 0 ? '✓ altında' : 'üstünde'}</div>
                        ` : `<div style="opacity:.5;">Anonimlik için en az 3 kullanıcı gerek.</div>`}
                    </div>
                </div>
            </div>`;
        }).join('');
}

document.getElementById('ubBmSubmit').addEventListener('click', async () => {
    const btn = document.getElementById('ubBmSubmit');
    btn.disabled = true; btn.textContent = 'Ekleniyor...';
    const fd = new FormData(); fd.append('action','submit_month');
    const r = await api('api/benchmark.php', {method:'POST', body:fd});
    btn.disabled = false; btn.textContent = '📤 Bu Ayın Verimi Havuza Ekle';
    if (r.success) { window.UB?.haptic?.(15); alert(`✓ ${r.submitted} kategori havuza eklendi.`); load(); }
});
load();
</script>

<?php require __DIR__ . '/includes/seo_tools_foot.php'; ?>
