<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

$__title = 'Akademik-Finansal Araçlar';
$__desc  = 'Ders başı maliyet, staj net maaş hesaplayıcı, Erasmus bütçe planlayıcı — tek sayfada.';
$__allowDemoLogin = true; // Public page — allow demo auto-login
require __DIR__ . '/includes/seo_tools_head.php';
?>
<h1>🎓 Akademik-Finansal Araçlar</h1>
<p class="ub-lead">Okulu kırmanın gerçek maliyeti ne? Stajdan eline ne geçer? Erasmus'ta kaç Euro yeter? Hesapla, gör, planla.</p>

<!-- 1. Ders Maliyeti -->
<h2>📚 Ders Başı Maliyet</h2>
<div class="ub-card">
    <p style="opacity:.75; margin-top:0;">Dönem ücretini aldığın ders sayısına böl — devamsızlık yaptığında ne kadar para yaktığını gör.</p>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
        <label>Dönem ücreti (₺)<input type="number" id="l_fee" value="25000" class="ub-inp3"></label>
        <label>Ders sayısı (dönem)<input type="number" id="l_count" value="5" class="ub-inp3"></label>
        <label>Haftalık saat/ders<input type="number" id="l_hours" value="3" class="ub-inp3"></label>
        <label>Dönemdeki hafta<input type="number" id="l_weeks" value="14" class="ub-inp3"></label>
        <label>Bu dersi kaç kere kırdın?<input type="number" id="l_skipped" value="0" class="ub-inp3"></label>
    </div>
    <div id="lectureResult" style="margin-top:16px;"></div>
</div>

<!-- 2. Staj Maaşı -->
<h2>💼 Staj Net Maaş Hesaplayıcı</h2>
<div class="ub-card">
    <p style="opacity:.75; margin-top:0;">Brüt değil, eline geçen önemli. SGK kesintisi, İŞKUR desteği dahil hesap.</p>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
        <label>Brüt maaş (₺)<input type="number" id="s_gross" value="25000" class="ub-inp3"></label>
        <label>İŞKUR destekli mi?
            <select id="s_iskur" class="ub-inp3"><option value="0">Hayır</option><option value="1" selected>Evet</option></select>
        </label>
        <label>Zorunlu staj mı?
            <select id="s_mandatory" class="ub-inp3"><option value="1" selected>Zorunlu</option><option value="0">Gönüllü</option></select>
        </label>
        <label>Staj süresi (ay)<input type="number" id="s_months" value="3" class="ub-inp3"></label>
    </div>
    <div id="internResult" style="margin-top:16px;"></div>
</div>

<!-- 3. Erasmus -->
<h2>🌍 Erasmus Bütçe Planlayıcı</h2>
<div class="ub-card">
    <p style="opacity:.75; margin-top:0;">Erasmus grant'i yetmez — önceden görüp biriktir.</p>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
        <label>Hedef ülke
            <select id="e_country" class="ub-inp3">
                <option value="de">🇩🇪 Almanya</option>
                <option value="nl">🇳🇱 Hollanda</option>
                <option value="es">🇪🇸 İspanya</option>
                <option value="it">🇮🇹 İtalya</option>
                <option value="pt">🇵🇹 Portekiz</option>
                <option value="pl">🇵🇱 Polonya</option>
                <option value="cz">🇨🇿 Çekya</option>
                <option value="fr">🇫🇷 Fransa</option>
            </select>
        </label>
        <label>Süre (ay)<input type="number" id="e_months" value="5" class="ub-inp3"></label>
        <label>Erasmus grant (€/ay)<input type="number" id="e_grant" value="600" class="ub-inp3"></label>
        <label>Uçak + ulaşım (€)<input type="number" id="e_flight" value="400" class="ub-inp3"></label>
        <label>1€ kuru (₺)<input type="number" step="0.01" id="e_rate" value="38" class="ub-inp3"></label>
    </div>
    <div id="erasmusResult" style="margin-top:16px;"></div>
</div>

<style>.ub-inp3 { display:block; width:100%; padding:8px 10px; border-radius:6px; background:rgba(0,0,0,.4); color:#fff; border:1px solid rgba(255,255,255,.1); margin-top:4px; }</style>

<script>
const $$ = id => document.getElementById(id);
const fmt = n => Math.round(n).toLocaleString('tr-TR');

// 1) Lecture
function calcLecture() {
    const fee = +$$('l_fee').value, count = +$$('l_count').value || 1, hours = +$$('l_hours').value, weeks = +$$('l_weeks').value || 14, skipped = +$$('l_skipped').value;
    const perCourse = fee / count;
    const perHour = perCourse / (hours * weeks);
    const skippedCost = perHour * hours * skipped;
    $$('lectureResult').innerHTML = `
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:14px;">
            <div><div style="opacity:.6; font-size:.8rem;">Ders başı maliyet</div><div style="font-size:1.4rem; font-weight:800;">${fmt(perCourse)}₺</div></div>
            <div><div style="opacity:.6; font-size:.8rem;">Saat başı</div><div style="font-size:1.4rem; font-weight:800;">${fmt(perHour)}₺</div></div>
            <div><div style="opacity:.6; font-size:.8rem;">Şimdiye kadar yaktığın</div><div style="font-size:1.6rem; font-weight:900; color:#ff3b30;">${fmt(skippedCost)}₺</div></div>
        </div>
        ${skipped >= 3 ? '<p style="margin-top:10px; padding:10px; background:rgba(255,59,48,.08); border-left:3px solid #ff3b30; border-radius:6px;">⚠️ Bir sonraki kırışta devamsızlıktan kalma riskin var — tüm dersi tekrar alırsan maliyet 2× olur.</p>' : ''}`;
}

// 2) Intern — basit Türkiye 2026 hesabı
function calcIntern() {
    const gross = +$$('s_gross').value;
    const mandatory = +$$('s_mandatory').value === 1;
    const iskur = +$$('s_iskur').value === 1;
    const months = +$$('s_months').value;
    // Zorunlu staj: sadece %1 iş kazası SGK, gelir vergisi yok (2026 tahmini)
    // Gönüllü staj: %15 gelir vergisi + %14 SGK + %1 damga
    const sgkRate = mandatory ? 0.01 : 0.14;
    const taxRate = mandatory ? 0 : 0.15;
    const stampRate = 0.00759;
    const sgk = gross * sgkRate;
    const tax = Math.max(0, (gross - sgk)) * taxRate;
    const stamp = gross * stampRate;
    const net = gross - sgk - tax - stamp;
    // İŞKUR desteği (2026 tahmini ~3350₺ asgari ücretin yarısı kadar)
    const iskurSupport = iskur ? 3350 : 0;
    const effectiveNet = net + iskurSupport;
    $$('internResult').innerHTML = `
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:14px;">
            <div><div style="opacity:.6; font-size:.8rem;">Brüt</div><div style="font-size:1.3rem; font-weight:800;">${fmt(gross)}₺</div></div>
            <div><div style="opacity:.6; font-size:.8rem;">SGK</div><div style="font-size:1.3rem; font-weight:800; color:#ff9100;">-${fmt(sgk)}₺</div></div>
            ${!mandatory ? `<div><div style="opacity:.6; font-size:.8rem;">Gelir Vergisi</div><div style="font-size:1.3rem; font-weight:800; color:#ff9100;">-${fmt(tax)}₺</div></div>` : ''}
            <div><div style="opacity:.6; font-size:.8rem;">Damga</div><div style="font-size:1.3rem; font-weight:800; color:#ff9100;">-${fmt(stamp)}₺</div></div>
            ${iskurSupport > 0 ? `<div><div style="opacity:.6; font-size:.8rem;">İŞKUR Desteği</div><div style="font-size:1.3rem; font-weight:800; color:#39ff14;">+${fmt(iskurSupport)}₺</div></div>` : ''}
            <div><div style="opacity:.6; font-size:.8rem;">AYLIK NET</div><div style="font-size:1.7rem; font-weight:900; color:#39ff14;">${fmt(effectiveNet)}₺</div></div>
            <div><div style="opacity:.6; font-size:.8rem;">${months} ayda toplam</div><div style="font-size:1.5rem; font-weight:800;">${fmt(effectiveNet * months)}₺</div></div>
        </div>
        <p style="margin-top:10px; opacity:.6; font-size:.8rem;">⚠️ Tahmini. Gerçek kesintiler için şirkete sor.</p>`;
}

// 3) Erasmus
const ERASMUS_COSTS = {
    de: { name:'Almanya', rent:450, food:250, transport:60, other:150, tip:'WG (paylaşımlı ev) kirayı 2× düşürür.' },
    nl: { name:'Hollanda', rent:650, food:300, transport:80, other:200, tip:'Utrecht/Groningen Amsterdam\'dan %40 ucuz.' },
    es: { name:'İspanya', rent:350, food:200, transport:40, other:150, tip:'Sevilla/Granada Barcelona\'nın yarısı.' },
    it: { name:'İtalya', rent:400, food:220, transport:50, other:180, tip:'Mensa (öğrenci yemekhanesi) günde 5€.' },
    pt: { name:'Portekiz', rent:350, food:180, transport:40, other:120, tip:'Porto, Lizbon\'dan %30 ucuz.' },
    pl: { name:'Polonya', rent:250, food:150, transport:30, other:100, tip:'En ekonomik Erasmus seçeneği.' },
    cz: { name:'Çekya', rent:300, food:180, transport:25, other:120, tip:'Prag dışı (Brno) %40 daha ucuz.' },
    fr: { name:'Fransa', rent:500, food:280, transport:60, other:180, tip:'CAF konut yardımı başvur, 100-200€/ay geri al.' },
};

function calcErasmus() {
    const c = ERASMUS_COSTS[$$('e_country').value];
    const months = +$$('e_months').value;
    const grant = +$$('e_grant').value;
    const flight = +$$('e_flight').value;
    const rate = +$$('e_rate').value;
    const monthly = c.rent + c.food + c.transport + c.other;
    const total = (monthly * months) + flight;
    const totalGrant = grant * months;
    const gap = total - totalGrant;
    $$('erasmusResult').innerHTML = `
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:14px;">
            <div><div style="opacity:.6; font-size:.8rem;">Aylık yaşam</div><div style="font-size:1.4rem; font-weight:800;">€${fmt(monthly)}</div><div style="font-size:.75rem; opacity:.55;">${fmt(monthly*rate)}₺</div></div>
            <div><div style="opacity:.6; font-size:.8rem;">Toplam maliyet</div><div style="font-size:1.4rem; font-weight:800; color:#ff9100;">€${fmt(total)}</div><div style="font-size:.75rem; opacity:.55;">${fmt(total*rate)}₺</div></div>
            <div><div style="opacity:.6; font-size:.8rem;">Grant toplam</div><div style="font-size:1.4rem; font-weight:800; color:#39ff14;">€${fmt(totalGrant)}</div></div>
            <div><div style="opacity:.6; font-size:.8rem;">AÇIĞIN</div><div style="font-size:1.7rem; font-weight:900; color:${gap>0?'#ff3b30':'#39ff14'};">${gap>=0?'€':'+€'}${fmt(Math.abs(gap))}</div><div style="font-size:.75rem; opacity:.55;">${fmt(Math.abs(gap)*rate)}₺</div></div>
        </div>
        <p style="margin-top:12px; padding:12px; background:rgba(125,92,255,.08); border-left:3px solid #7d5cff; border-radius:6px;"><b>💡 ${c.name} ipucu:</b> ${c.tip}</p>
        ${gap > 0 ? `<p style="margin-top:10px; opacity:.85;">🎯 Gitmeden önce <b>${fmt(gap*rate)}₺</b> biriktirmen gerek. ${months} ay önce başlarsan aylık <b>${fmt((gap*rate)/months)}₺</b> yeterli.</p>` : ''}`;
}

['l_fee','l_count','l_hours','l_weeks','l_skipped'].forEach(id => $$(id).addEventListener('input', calcLecture));
['s_gross','s_iskur','s_mandatory','s_months'].forEach(id => $$(id).addEventListener('input', calcIntern));
['e_country','e_months','e_grant','e_flight','e_rate'].forEach(id => $$(id).addEventListener('input', calcErasmus));
calcLecture(); calcIntern(); calcErasmus();
</script>

<?php require __DIR__ . '/includes/seo_tools_foot.php'; ?>
