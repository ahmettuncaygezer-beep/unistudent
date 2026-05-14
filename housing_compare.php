<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

$__title = 'Yurt vs Ev Karşılaştırıcı';
$__desc  = 'KYK yurdu mu, özel yurt mu, ev mi? Aylık maliyet, breakeven, fırsat maliyeti hesaplayıcı.';
$__allowDemoLogin = true; // Public page — allow demo auto-login
require __DIR__ . '/includes/seo_tools_head.php';
?>
<h1>🏠 Yurt vs Ev Karşılaştırıcı</h1>
<p class="ub-lead">KYK yurdu ucuz ama mesafe ne? Kira + fatura + yemek toplamı dışarıda yaşamak gerçekten daha mı pahalı? Rakamları yan yana koy, gerçek bedeli gör.</p>

<div class="ub-card">
    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
        <div>
            <h3 style="color:#7d5cff;">🏫 KYK Yurdu</h3>
            <label style="display:block; margin-bottom:8px;">Aylık yurt ücreti<br><input type="number" id="k_fee" value="1500" class="ub-inp"></label>
            <label style="display:block; margin-bottom:8px;">Yemekhane (ay)<br><input type="number" id="k_food" value="2500" class="ub-inp"></label>
            <label style="display:block; margin-bottom:8px;">Ulaşım (ay)<br><input type="number" id="k_transport" value="500" class="ub-inp"></label>
            <label style="display:block; margin-bottom:8px;">Günlük yol (dk)<br><input type="number" id="k_commute" value="60" class="ub-inp"></label>
        </div>
        <div>
            <h3 style="color:#ff9100;">🏢 Özel Yurt</h3>
            <label style="display:block; margin-bottom:8px;">Aylık ücret<br><input type="number" id="p_fee" value="7000" class="ub-inp"></label>
            <label style="display:block; margin-bottom:8px;">Ek yemek (ay)<br><input type="number" id="p_food" value="1500" class="ub-inp"></label>
            <label style="display:block; margin-bottom:8px;">Ulaşım (ay)<br><input type="number" id="p_transport" value="300" class="ub-inp"></label>
            <label style="display:block; margin-bottom:8px;">Günlük yol (dk)<br><input type="number" id="p_commute" value="20" class="ub-inp"></label>
        </div>
        <div>
            <h3 style="color:#39ff14;">🏡 Ev (Kiralık)</h3>
            <label style="display:block; margin-bottom:8px;">Kira (ay)<br><input type="number" id="h_rent" value="12000" class="ub-inp"></label>
            <label style="display:block; margin-bottom:8px;">Faturalar (ay)<br><input type="number" id="h_bills" value="2500" class="ub-inp"></label>
            <label style="display:block; margin-bottom:8px;">Market + yemek<br><input type="number" id="h_food" value="5000" class="ub-inp"></label>
            <label style="display:block; margin-bottom:8px;">Ulaşım<br><input type="number" id="h_transport" value="400" class="ub-inp"></label>
            <label style="display:block; margin-bottom:8px;">Oda arkadaşı sayısı<br><input type="number" id="h_roommates" value="1" class="ub-inp" min="0"></label>
            <label style="display:block; margin-bottom:8px;">Depozito (1 kez)<br><input type="number" id="h_deposit" value="24000" class="ub-inp"></label>
        </div>
    </div>
</div>

<h2>📊 Karşılaştırma</h2>
<div id="ubHousingResult"></div>

<style>
.ub-inp { width:100%; padding:8px; border-radius:6px; background:rgba(0,0,0,.4); color:#fff; border:1px solid rgba(255,255,255,.1); }
</style>

<script>
const $ = id => document.getElementById(id);
const HOURS_PER_MONTH = 22; // okul günü

function calc() {
    const kyk = {
        monthly: +$('k_fee').value + +$('k_food').value + +$('k_transport').value,
        commute: +$('k_commute').value * 2 * HOURS_PER_MONTH / 60, // aylık saat
        upfront: 0,
        label: 'KYK Yurdu', color: '#7d5cff'
    };
    const prv = {
        monthly: +$('p_fee').value + +$('p_food').value + +$('p_transport').value,
        commute: +$('p_commute').value * 2 * HOURS_PER_MONTH / 60,
        upfront: 0,
        label: 'Özel Yurt', color: '#ff9100'
    };
    const room = Math.max(1, 1 + +$('h_roommates').value);
    const hous = {
        monthly: ((+$('h_rent').value + +$('h_bills').value) / room) + +$('h_food').value + +$('h_transport').value,
        commute: 0,
        upfront: +$('h_deposit').value / room,
        label: 'Ev', color: '#39ff14'
    };

    const options = [kyk, prv, hous];
    const minMonth = Math.min(...options.map(o => o.monthly));
    // 1 saat öğrenci zamanı: min ücret saatlik (~50₺)
    const HOURLY = 50;

    options.forEach(o => {
        o.timeValue = o.commute * HOURLY;
        o.trueCost = o.monthly + o.timeValue;
    });

    // Breakeven: ev vs yurt — depozito ne kadar zamanda amorti olur?
    const kykSaveVsHouse = hous.monthly - kyk.monthly; // evden kyk'ya geçiş avantajı
    const breakeven_h_vs_k = kykSaveVsHouse > 0 ? (hous.upfront / kykSaveVsHouse) : null;

    const out = options.map(o => `
        <div class="ub-card" style="border-left:4px solid ${o.color};">
            <h3 style="margin:0 0 8px; color:${o.color};">${o.label}</h3>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:14px;">
                <div><div style="opacity:.6; font-size:.8rem;">Aylık</div><div style="font-size:1.4rem; font-weight:800;">${o.monthly.toLocaleString('tr-TR')}₺</div></div>
                <div><div style="opacity:.6; font-size:.8rem;">Yol saati değeri</div><div style="font-size:1.4rem; font-weight:800;">${o.timeValue.toLocaleString('tr-TR')}₺</div><div style="font-size:.75rem; opacity:.55;">${o.commute.toFixed(1)} sa/ay × ₺${HOURLY}</div></div>
                <div><div style="opacity:.6; font-size:.8rem;">Peşin</div><div style="font-size:1.4rem; font-weight:800;">${o.upfront.toLocaleString('tr-TR')}₺</div></div>
                <div><div style="opacity:.6; font-size:.8rem;">GERÇEK AYLIK</div><div style="font-size:1.6rem; font-weight:900; color:${o.color};">${o.trueCost.toLocaleString('tr-TR')}₺</div></div>
            </div>
        </div>`).join('');

    $('ubHousingResult').innerHTML = out + `
        <div class="ub-card" style="background:rgba(125,92,255,.08);">
            <h3 style="margin-top:0;">💡 Karar Özeti</h3>
            <ul style="line-height:1.8;">
                <li>En düşük <b>gerçek</b> aylık maliyet: <b style="color:${options.sort((a,b)=>a.trueCost-b.trueCost)[0].color};">${options.sort((a,b)=>a.trueCost-b.trueCost)[0].label}</b></li>
                ${breakeven_h_vs_k ? `<li>Evde yaşamak yerine KYK'ya geçersen <b>depozito ${breakeven_h_vs_k.toFixed(1)} ayda</b> amorti oluyor.</li>` : ''}
                <li style="opacity:.7;">Not: Yol süresi saat başı ₺${HOURLY}'den değerlenir — ders çalışma/part-time fırsat maliyeti.</li>
            </ul>
        </div>`;
}

document.querySelectorAll('.ub-inp').forEach(i => i.addEventListener('input', calc));
calc();
</script>

<?php require __DIR__ . '/includes/seo_tools_foot.php'; ?>
