<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_system.php';
$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) { header('Location: index.php#hero'); exit; }
$__title = 'Depozito Takipçisi';
$__desc  = 'Ev sahibine verdiğin depozitoyu unutma — çıkışta geri alma hatırlatıcısı.';
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
require __DIR__ . '/includes/seo_tools_head.php';
?>
<h1>💰 Depozito Takipçisi</h1>
<p class="ub-lead">Ev sahibine verdiğin depozitoyu kaydet. Çıkış zamanı geldiğinde hatırlatalım, kaybetme.</p>

<div id="ubDepTotals"></div>

<h2>Yeni Depozito Ekle</h2>
<div class="ub-card">
    <form id="ubDepForm" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px;">
        <input type="text" name="landlord_name" placeholder="Ev sahibi adı" class="ub-inp2">
        <input type="text" name="address" placeholder="Adres (opsiyonel)" class="ub-inp2">
        <input type="number" step="0.01" name="amount" placeholder="Tutar ₺" required class="ub-inp2">
        <input type="date" name="paid_date" class="ub-inp2" title="Ödeme tarihi">
        <input type="date" name="expected_return_date" class="ub-inp2" title="Beklenen iade tarihi">
        <input type="text" name="notes" placeholder="Not" class="ub-inp2">
        <button type="submit" class="btn btn-primary" data-magnetic>Kaydet</button>
    </form>
</div>

<h2>Depozitoların</h2>
<div id="ubDepList"><p style="opacity:.6;">Yükleniyor...</p></div>

<style>.ub-inp2 { padding:10px; border-radius:8px; background:rgba(0,0,0,.4); color:#fff; border:1px solid rgba(255,255,255,.1); }</style>
<script>
const api = (u, opts = {}) => fetch(u, {
    credentials:'same-origin',
    headers: { 'X-CSRF-Token': window.CSRF_TOKEN || '', ...(opts.headers || {}) }, ...opts
}).then(r => r.json());

// HTML escape helper — XSS önlüyor
const esc = s => s ? String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#x27;') : '';

const STATUS = {
    active:   { label:'Aktif',      color:'#7d5cff' },
    returned: { label:'İade Alındı', color:'#39ff14' },
    partial:  { label:'Kısmi İade',  color:'#ff9100' },
    lost:     { label:'Kaybedildi',  color:'#ff3b30' },
};

async function load() {
    const r = await api('api/deposits.php');
    if (!r.success) return;
    document.getElementById('ubDepTotals').innerHTML = `
        <div class="ub-card">
            <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:14px; text-align:center;">
                <div><div style="opacity:.6; font-size:.85rem;">Aktif Depozito</div><div style="font-size:1.8rem; font-weight:800; color:#7d5cff;">${r.totals.active.toLocaleString('tr-TR')}₺</div></div>
                <div><div style="opacity:.6; font-size:.85rem;">Kaybedilen</div><div style="font-size:1.8rem; font-weight:800; color:#ff3b30;">${r.totals.lost.toLocaleString('tr-TR')}₺</div></div>
            </div>
        </div>`;
    const list = document.getElementById('ubDepList');
    if (!r.deposits.length) { list.innerHTML = '<p style="opacity:.6;">Henüz depozito kaydın yok.</p>'; return; }
    list.innerHTML = r.deposits.map(d => {
        const st = STATUS[d.status] || STATUS.active;
        const urgent = d.days_until_return !== null && d.days_until_return < 30 && d.status === 'active';
        return `<div class="ub-card" style="border-left:4px solid ${st.color};">
            <div style="display:flex; justify-content:space-between; align-items:start; gap:12px; flex-wrap:wrap;">
                <div style="flex:1;">
                    <h3 style="margin:0;">${esc(d.landlord_name) || 'Ev sahibi'} · ${parseFloat(d.amount).toLocaleString('tr-TR')}₺</h3>
                    <div style="opacity:.75; font-size:.9rem; margin-top:4px;">${esc(d.address)}</div>
                    <div style="opacity:.65; font-size:.85rem; margin-top:6px;">
                        Ödeme: ${d.paid_date ? new Date(d.paid_date).toLocaleDateString('tr-TR') : '-'} ·
                        İade beklentisi: ${d.expected_return_date ? new Date(d.expected_return_date).toLocaleDateString('tr-TR') : '-'}
                        ${urgent ? `<span class="ub-pill ub-pill-progress">⚠️ ${d.days_until_return} gün kaldı</span>` : ''}
                    </div>
                    ${d.notes ? `<div style="opacity:.8; font-size:.85rem; margin-top:6px;">${esc(d.notes)}</div>` : ''}
                    <div style="margin-top:10px;"><span style="color:${st.color}; font-weight:700;">${st.label}</span> ${d.status==='partial'||d.status==='returned' ? ` · ${parseFloat(d.returned_amount).toLocaleString('tr-TR')}₺ iade` : ''}</div>
                </div>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    ${d.status==='active' ? `
                        <button onclick="markReturned(${d.id}, ${d.amount})" style="padding:6px 12px; border-radius:6px; background:rgba(57,255,20,.15); color:#39ff14; border:1px solid rgba(57,255,20,.3); cursor:pointer; font-size:.85rem;">&#10003; İade Alındı</button>
                        <button onclick="markPartial(${d.id})" style="padding:6px 12px; border-radius:6px; background:rgba(255,145,0,.15); color:#ff9100; border:1px solid rgba(255,145,0,.3); cursor:pointer; font-size:.85rem;">Kısmi</button>
                        <button onclick="markLost(${d.id})" style="padding:6px 12px; border-radius:6px; background:rgba(255,59,48,.15); color:#ff3b30; border:1px solid rgba(255,59,48,.3); cursor:pointer; font-size:.85rem;">Kaybedildi</button>
                    ` : ''}
                    <button onclick="del(${d.id})" style="padding:6px 12px; border-radius:6px; background:transparent; color:rgba(255,255,255,.5); border:1px solid rgba(255,255,255,.15); cursor:pointer; font-size:.85rem;">Sil</button>
                </div>
            </div>
        </div>`;
    }).join('');
}

async function markReturned(id, amt) {
    const fd = new FormData(); fd.append('action','update_status'); fd.append('id',id); fd.append('status','returned'); fd.append('returned_amount', amt);
    await api('api/deposits.php', {method:'POST', body:fd}); load();
}
async function markPartial(id) {
    const amt = prompt('Ne kadar iade aldın? ₺');
    if (!amt) return;
    const fd = new FormData(); fd.append('action','update_status'); fd.append('id',id); fd.append('status','partial'); fd.append('returned_amount', amt);
    await api('api/deposits.php', {method:'POST', body:fd}); load();
}
async function markLost(id) {
    if (!confirm('Kayıp olarak işaretlensin mi?')) return;
    const fd = new FormData(); fd.append('action','update_status'); fd.append('id',id); fd.append('status','lost'); fd.append('returned_amount', 0);
    await api('api/deposits.php', {method:'POST', body:fd}); load();
}
async function del(id) {
    if (!confirm('Silinsin mi?')) return;
    const fd = new FormData(); fd.append('action','delete'); fd.append('id',id);
    await api('api/deposits.php', {method:'POST', body:fd}); load();
}

document.getElementById('ubDepForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target); fd.append('action','create');
    const r = await api('api/deposits.php', {method:'POST', body:fd});
    if (r.success) { window.UB?.haptic?.(15); e.target.reset(); load(); } else alert(r.message || 'Hata');
});
load();
</script>

<?php require __DIR__ . '/includes/seo_tools_foot.php'; ?>
