<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_system.php';
$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) { header('Location: index.php#hero'); exit; }
$__title = 'Tasarruf Meydan Okumaları';
$__desc  = '7 gün kahvesiz, 30 gün BİM-only... Tamamla, XP + para kazan.';
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
require __DIR__ . '/includes/seo_tools_head.php';
?>
<h1>🎯 Meydan Okumalar</h1>
<p class="ub-lead">Haftalık/aylık tasarruf hedefleri. Kategoriyi seç, süreyi bekle — hedef kategori sıfır harcama kalırsa ödül senin.</p>

<h2>🔥 Aktif Meydan Okumaların</h2>
<div id="ubMyChallenges"></div>

<h2>➕ Yeni Başlat</h2>
<div id="ubAllChallenges" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:14px;"></div>

<script>
const api = (u, opts={}) => fetch(u, {credentials:'same-origin', headers:{'X-CSRF-Token':window.CSRF_TOKEN||'', ...(opts.headers||{})}, ...opts}).then(r=>r.json());

async function load() {
    const r = await api('api/challenges.php');
    // Progress check first
    const fd = new FormData(); fd.append('action','check_progress');
    await api('api/challenges.php', {method:'POST', body:fd});

    const fresh = await api('api/challenges.php');
    renderMine(fresh.mine); renderAll(fresh.all, fresh.mine);
}

function renderMine(mine) {
    const active = mine.filter(m => m.status === 'active');
    const past = mine.filter(m => m.status !== 'active').slice(0, 5);
    const box = document.getElementById('ubMyChallenges');
    if (!active.length && !past.length) { box.innerHTML = '<p style="opacity:.6;">Henüz bir challenge başlatmadın.</p>'; return; }
    const html = [];
    active.forEach(m => {
        const pct = Math.min(100, (m.days_gone / m.days_total) * 100);
        const danger = +m.spent_during > 0;
        html.push(`<div class="ub-card" style="border-left:4px solid ${danger ? '#ff3b30' : '#39ff14'};">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
                <div style="flex:1;">
                    <h3 style="margin:0;">${m.icon} ${m.title}</h3>
                    <div style="opacity:.75; font-size:.9rem; margin-top:4px;">
                        ${m.days_left} gün kaldı · Kategoride harcama: <b style="color:${danger?'#ff3b30':'#39ff14'};">${parseFloat(m.spent_during).toLocaleString('tr-TR')}₺</b>
                    </div>
                    <div style="height:6px; background:rgba(255,255,255,.08); border-radius:3px; margin-top:8px; overflow:hidden;">
                        <div style="height:100%; width:${pct}%; background:linear-gradient(90deg, #0088ff, #7d5cff);"></div>
                    </div>
                    ${danger ? '<p style="margin-top:8px; color:#ff3b30; font-size:.85rem;">⚠️ Kategoride harcama var — challenge riskli!</p>' : ''}
                </div>
                <button onclick="abandon(${m.id})" style="background:transparent; border:1px solid rgba(255,255,255,.15); color:rgba(255,255,255,.6); padding:6px 12px; border-radius:8px; cursor:pointer;">Vazgeç</button>
            </div>
        </div>`);
    });
    past.forEach(m => {
        const c = m.status === 'completed' ? '#39ff14' : (m.status === 'failed' ? '#ff3b30' : '#888');
        const label = {completed:'✅ Tamamlandı', failed:'❌ Başarısız', abandoned:'↩️ Vazgeçildi'}[m.status];
        html.push(`<div class="ub-card" style="opacity:.75;">
            <h3 style="margin:0;">${m.icon} ${m.title}</h3>
            <div style="color:${c}; margin-top:6px;">${label} ${m.status==='completed' ? `· +${parseFloat(m.saved_amount).toLocaleString('tr-TR')}₺ tasarruf` : ''}</div>
        </div>`);
    });
    box.innerHTML = html.join('');
}

function renderAll(all, mine) {
    const activeIds = new Set(mine.filter(m => m.status==='active').map(m => +m.challenge_id));
    document.getElementById('ubAllChallenges').innerHTML = all.map(c => {
        const isActive = activeIds.has(+c.id);
        return `<div class="ub-card" style="margin:0;">
            <div style="font-size:2rem;">${c.icon}</div>
            <h3 style="margin:6px 0;">${c.title}</h3>
            <div style="opacity:.75; font-size:.88rem; margin-bottom:10px;">${c.description}</div>
            <div style="display:flex; gap:10px; font-size:.82rem; opacity:.85; margin-bottom:12px;">
                <span>⏱️ ${c.duration_days} gün</span>
                <span>💰 ${parseFloat(c.target_savings).toLocaleString('tr-TR')}₺</span>
                <span>⭐ +${c.xp_reward} XP</span>
            </div>
            ${isActive
                ? '<button disabled class="btn" style="width:100%; opacity:.5;">Aktif</button>'
                : `<button onclick="start(${c.id})" class="btn btn-primary" style="width:100%;" data-magnetic>🚀 Başlat</button>`}
        </div>`;
    }).join('');
}

async function start(id) {
    try {
        const fd = new FormData(); fd.append('action','start'); fd.append('challenge_id', id);
        const r = await api('api/challenges.php', {method:'POST', body:fd});
        if (r.success) { 
            window.UB?.haptic?.(20); 
            load(); 
            if(window.showToast) showToast('Meydan okuma başladı!', 'success');
            else alert('Meydan okuma başladı! Sayfayı yenileyerek görebilirsiniz.');
        } else {
            alert('Hata: ' + (r.message || 'Başlatılamadı'));
        }
    } catch(e) { console.error(e); alert('Bağlantı hatası.'); }
}
async function abandon(id) {
    if (!confirm('Vazgeçilsin mi?')) return;
    try {
        const fd = new FormData(); fd.append('action','abandon'); fd.append('id',id);
        const r = await api('api/challenges.php', {method:'POST', body:fd}); 
        if (r.success) {
            load();
        } else {
            alert('Hata: ' + (r.message || 'İptal edilemedi'));
        }
    } catch(e) { console.error(e); alert('Bağlantı hatası.'); }
}
load();
</script>

<?php require __DIR__ . '/includes/seo_tools_foot.php'; ?>
