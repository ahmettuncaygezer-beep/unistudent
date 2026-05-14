<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

$__title = 'Öğrenci İndirimleri Kataloğu';
$__desc  = 'Spotify, YouTube, JetBrains, Microsoft 365 ve daha fazlası — Türk öğrenciler için ücretsiz/indirimli abonelik rehberi. Yıllık binlerce lira tasarruf.';
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
$__allowDemoLogin = true; // Public catalog page — allow demo auto-login
require __DIR__ . '/includes/seo_tools_head.php';
?>
<h1>🎟️ Öğrenci İndirimleri Kataloğu</h1>
<p class="ub-lead">Spotify Premium %50, JetBrains ücretsiz, GitHub Student Pack 100+ araç... Öğrenci statünü kullan, yılda binlerce lira cebinde kalsın.</p>

<div class="ub-card" id="ubSavingsCard" style="display:none;">
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; text-align:center;">
        <div><div style="opacity:.6; font-size:.85rem;">Aktif Aboneliklerin</div><div style="font-size:1.8rem; font-weight:800; color:#39ff14;" id="ubMonthlySave">0₺/ay</div></div>
        <div><div style="opacity:.6; font-size:.85rem;">Yıllık Tasarruf</div><div style="font-size:1.8rem; font-weight:800; color:#0088ff;" id="ubAnnualSave">0₺</div></div>
    </div>
</div>

<div class="ub-card">
    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <input type="search" id="ubDisSearch" placeholder="Ara (Spotify, JetBrains...)" style="flex:1; min-width:200px; padding:10px; border-radius:8px; background:rgba(0,0,0,.4); color:#fff; border:1px solid rgba(255,255,255,.1);">
        <select id="ubDisCat" style="padding:10px; border-radius:8px; background:rgba(0,0,0,.4); color:#fff; border:1px solid rgba(255,255,255,.1);">
            <option value="">Tüm kategoriler</option>
        </select>
    </div>
</div>

<div id="ubDisList" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:14px; margin-top:20px;"></div>

<script>
const api = (u, opts = {}) => fetch(u, {
    credentials: 'same-origin',
    headers: { 'X-CSRF-Token': window.CSRF_TOKEN || '', ...(opts.headers || {}) }, ...opts
}).then(r => r.json());

const isLoggedIn = !!<?= !empty($_SESSION['user_id']) ? 'true' : 'false' ?>;

async function load() {
    const q   = document.getElementById('ubDisSearch').value;
    const cat = document.getElementById('ubDisCat').value;
    const r = await api(`api/discounts.php?q=${encodeURIComponent(q)}&category=${encodeURIComponent(cat)}`);
    if (!r.success) return;

    const catSel = document.getElementById('ubDisCat');
    if (catSel.options.length <= 1) {
        r.categories.forEach(c => { const o = document.createElement('option'); o.value=c; o.textContent=c; catSel.appendChild(o); });
    }

    if (isLoggedIn && r.user_monthly_savings > 0) {
        document.getElementById('ubSavingsCard').style.display = 'block';
        document.getElementById('ubMonthlySave').textContent  = r.user_monthly_savings.toLocaleString('tr-TR') + '₺/ay';
        document.getElementById('ubAnnualSave').textContent   = r.user_annual_savings.toLocaleString('tr-TR') + '₺';
    } else {
        document.getElementById('ubSavingsCard').style.display = 'none';
    }

    document.getElementById('ubDisList').innerHTML = r.discounts.map(d => {
        const claimed = +d.claimed > 0;
        return `<div class="ub-card" style="margin:0; display:flex; flex-direction:column; gap:10px;">
            <div style="display:flex; justify-content:space-between; align-items:start; gap:10px;">
                <div style="font-size:2rem;">${d.icon || '🎁'}</div>
                <span class="ub-pill ${claimed ? 'ub-pill-done' : 'ub-pill-planned'}">${claimed ? '✓ Aktif' : d.category}</span>
            </div>
            <div>
                <h3 style="margin:4px 0;">${d.brand}</h3>
                <div style="font-size:.9rem; opacity:.85;"><b>${d.discount_text}</b> · ${parseFloat(d.monthly_saving).toLocaleString('tr-TR')}₺/ay değerinde</div>
                <div style="font-size:.82rem; opacity:.65; margin-top:6px;">${d.how_to}</div>
            </div>
            <div style="display:flex; gap:8px; margin-top:auto;">
                <a href="${d.url}" target="_blank" rel="noopener" class="btn btn-primary" style="flex:1; text-align:center; padding:8px 12px; font-size:.88rem;">Siteye Git →</a>
                ${isLoggedIn ? `<button onclick="toggle(${d.id}, ${claimed})" style="padding:8px 14px; border-radius:8px; cursor:pointer; background:${claimed ? 'rgba(255,59,48,.15)' : 'rgba(57,255,20,.15)'}; color:${claimed ? '#ff3b30' : '#39ff14'}; border:1px solid ${claimed ? 'rgba(255,59,48,.3)' : 'rgba(57,255,20,.3)'};">${claimed ? '× Çıkar' : '+ Ekle'}</button>` : ''}
            </div>
        </div>`;
    }).join('') || '<p style="opacity:.6;">Sonuç yok.</p>';
}

async function toggle(id, isClaimed) {
    try {
        const fd = new FormData();
        fd.append('action', isClaimed ? 'unclaim' : 'claim');
        fd.append('discount_id', id);
        fd.append('csrf_token', window.CSRF_TOKEN || '');
        const r = await api('api/discounts.php', { method:'POST', body: fd });
        if (r.success) { window.UB?.haptic?.(10); load(); }
    } catch(e) { console.error('Toggle error:', e); }
}

let t; document.getElementById('ubDisSearch').addEventListener('input', () => { clearTimeout(t); t = setTimeout(load, 250); });
document.getElementById('ubDisCat').addEventListener('change', load);
load();
</script>

<?php require __DIR__ . '/includes/seo_tools_foot.php'; ?>
