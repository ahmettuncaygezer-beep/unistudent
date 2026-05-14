<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_system.php';

$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) { header('Location: index.php#hero'); exit; }

$__title = 'KYK Takvimi & Geri Ödeme';
$__desc  = 'KYK burs yatış takvimi, kredi geri ödeme simülatörü, faiz endeksi tahmini.';
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
require __DIR__ . '/includes/seo_tools_head.php';
?>
<h1>🎓 KYK Takvimi & Geri Ödeme</h1>
<p class="ub-lead">Burs/kredi yatış tarihlerini kaçırma. Geri ödeme ne kadar olacak? Yİ-ÜFE endekslemesiyle tahmini hesapla — mezun olmadan önce gör.</p>

<h2>📅 2026 Yatış Takvimi</h2>
<div class="ub-card">
    <div id="ubKykCalendar" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(170px, 1fr)); gap:10px;"></div>
    <p style="margin-top:14px; opacity:.6; font-size:.85rem;">⚠️ Tarihler KYK'nın geçmiş yıl ortalamalarından tahmini çıkarılmıştır. Resmi yatış için GKB (gbs.kyk.gov.tr) uygulamasını kontrol et.</p>
</div>

<h2>💰 Senin Durumun</h2>
<div class="ub-card">
    <form id="ubKykForm" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px;">
        <label>Aylık KYK tutarı (₺)
            <input type="number" step="1" name="monthly_amount" required placeholder="3000"
                style="width:100%; padding:10px; border-radius:8px; background:rgba(0,0,0,.4); color:#fff; border:1px solid rgba(255,255,255,.1);">
        </label>
        <label>Kredi başlangıç tarihi
            <input type="date" name="start_date" required
                style="width:100%; padding:10px; border-radius:8px; background:rgba(0,0,0,.4); color:#fff; border:1px solid rgba(255,255,255,.1);">
        </label>
        <label>Öğretim süresi (ay)
            <input type="number" name="study_months" value="48"
                style="width:100%; padding:10px; border-radius:8px; background:rgba(0,0,0,.4); color:#fff; border:1px solid rgba(255,255,255,.1);">
        </label>
        <label>Ödemesiz dönem (ay)
            <input type="number" name="grace_months" value="24"
                style="width:100%; padding:10px; border-radius:8px; background:rgba(0,0,0,.4); color:#fff; border:1px solid rgba(255,255,255,.1);">
        </label>
        <label>Tahmini Yİ-ÜFE (yıllık %)
            <input type="number" name="yi_ufe_pct" value="25" min="0" max="200" step="1"
                style="width:100%; padding:10px; border-radius:8px; background:rgba(0,0,0,.4); color:#fff; border:1px solid rgba(255,255,255,.1);">
            <span style="font-size:.75rem; opacity:.5;">Varsayılan %25 — TÜİK Yİ-ÜFE verilerinden güncel oranı gir</span>
        </label>
        <div style="display:flex; align-items:end;"><button type="submit" class="btn btn-primary" data-magnetic>Kaydet & Hesapla</button></div>
    </form>
</div>

<div id="ubKykSim"></div>

<script>
const api = (u, opts = {}) => fetch(u, {
    credentials: 'same-origin',
    headers: { 'X-CSRF-Token': window.CSRF_TOKEN || '', ...(opts.headers || {}) }, ...opts
}).then(r => r.json());

async function loadKyk() {
    const ufeInput = document.querySelector('[name="yi_ufe_pct"]');
    const ufePct = ufeInput ? ufeInput.value : '25';
    const r = await api(`api/kyk.php?yi_ufe_pct=${encodeURIComponent(ufePct)}`);
    const cal = document.getElementById('ubKykCalendar');
    cal.innerHTML = (r.calendar || []).map(c => {
        const past = new Date(c.date) < new Date();
        return `<div style="padding:12px; border-radius:10px; background:${past ? 'rgba(255,255,255,.04)' : 'rgba(125,92,255,.12)'}; border:1px solid ${past ? 'rgba(255,255,255,.06)' : 'rgba(125,92,255,.3)'};">
            <div style="font-size:.75rem; opacity:.6;">${c.name}</div>
            <div style="font-weight:700; font-size:1.05rem;">${new Date(c.date).toLocaleDateString('tr-TR')}</div>
            <div style="font-size:.8rem; opacity:.75; margin-top:4px;">${c.note}</div>
        </div>`;
    }).join('');

    if (r.data) {
        const f = document.getElementById('ubKykForm');
        f.monthly_amount.value = r.data.monthly_amount;
        f.start_date.value = r.data.start_date;
        f.study_months.value = r.data.study_months;
        f.grace_months.value = r.data.grace_months;
    }

    if (r.simulation) {
        const s = r.simulation;
        document.getElementById('ubKykSim').innerHTML = `
            <h2>📊 Geri Ödeme Tahmini</h2>
            <div class="ub-card">
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:16px;">
                    <div><div style="opacity:.6; font-size:.82rem;">Toplam Alınan</div><div style="font-size:1.6rem; font-weight:800;">${s.total_received.toLocaleString('tr-TR')}₺</div></div>
                    <div><div style="opacity:.6; font-size:.82rem;">Yİ-ÜFE ile Endekslenmiş Borç</div><div style="font-size:1.6rem; font-weight:800; color:#ff9100;">${s.endexed_debt_est.toLocaleString('tr-TR')}₺</div></div>
                    <div><div style="opacity:.6; font-size:.82rem;">Tahmini Aylık Ödeme</div><div style="font-size:1.6rem; font-weight:800; color:#0088ff;">${s.monthly_payment_est.toLocaleString('tr-TR')}₺</div></div>
                    <div><div style="opacity:.6; font-size:.82rem;">Ödeme Süresi</div><div style="font-size:1.6rem; font-weight:800;">${s.repay_months} ay</div></div>
                </div>
                <div style="margin-top:20px; padding:14px; background:rgba(255,145,0,.08); border-left:3px solid #ff9100; border-radius:8px;">
                    <b>🎓 Mezuniyet:</b> ${new Date(s.graduation_date).toLocaleDateString('tr-TR')}<br>
                    <b>📅 Geri Ödeme Başlar:</b> ${new Date(s.repayment_start_date).toLocaleDateString('tr-TR')} (${s.repay_months} ay boyunca)<br>
                    <b>🏁 Son Ödeme:</b> ${new Date(s.repayment_end_date).toLocaleDateString('tr-TR')}
                </div>
                <p style="margin-top:12px; font-size:.85rem; opacity:.6;">⚠️ Bu hesaplama %${(s.yi_ufe_assumed*100).toFixed(0)} yıllık Yİ-ÜFE varsayımıyla yapılmıştır. Gerçek endeksleme TÜİK Yİ-ÜFE oranına bağlı olarak farklı olabilir. Güncel Yİ-ÜFE oranını <a href="https://data.tuik.gov.tr/" target="_blank" rel="noopener" style="color:#0088ff;">TÜİK</a>'ten kontrol edebilirsiniz.</p>
            </div>`;
    }
}

document.getElementById('ubKykForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target); fd.append('action', 'save');
    fd.append('yi_ufe_pct', e.target.yi_ufe_pct?.value || '25');
    const r = await api('api/kyk.php', { method:'POST', body: fd });
    if (r.success) { window.UB?.haptic?.(15); loadKyk(); }
    else alert(r.message || 'Hata');
});

loadKyk();
</script>

<?php require __DIR__ . '/includes/seo_tools_foot.php'; ?>
