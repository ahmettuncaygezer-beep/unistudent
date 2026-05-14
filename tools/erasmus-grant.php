<?php
$__title    = 'Erasmus Hibe Hesaplayıcı 2026';
$__desc     = 'Ülke bazlı Erasmus+ hibe tutarlarını karşılaştırın. Aylık hibe miktarı, yaşam maliyeti ve hibe yeterliliğini hesaplayın.';
$__keywords = 'Erasmus hibe miktarı 2026, Erasmus burs, Erasmus ülke bazlı hibe, Erasmus yaşam maliyeti, Erasmus hesaplama';
$__canonical = rtrim(getenv('APP_URL') ?: 'http://localhost/unistudent', '/') . '/tools/erasmus-grant.php';
$__schema = json_encode([
    "@context" => "https://schema.org",
    "@type" => "WebApplication",
    "name" => "Erasmus Hibe Hesaplayıcı",
    "description" => $__desc,
    "url" => $__canonical,
    "applicationCategory" => "EducationApplication",
    "operatingSystem" => "Web",
    "offers" => ["@type" => "Offer", "price" => "0", "priceCurrency" => "TRY"]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$__allowDemoLogin = true; // Public tool page — allow demo auto-login
require __DIR__ . '/../includes/seo_tools_head.php';
?>

<div class="tool-breadcrumb">
    <a href="../index.php">Ana Sayfa</a><span class="separator">›</span>
    <a href="index.php">Araçlar</a><span class="separator">›</span>
    <span class="current">Erasmus Hibe Hesaplayıcı</span>
</div>

<div class="tool-hero">
    <div class="tool-badge">🌍 Yurt Dışı</div>
    <h1 class="tool-title">Erasmus Hibe <span class="tg">Hesaplayıcı</span></h1>
    <p class="tool-subtitle">Gitmek istediğin ülkeyi seç, aylık hibeni ve yaşam maliyetine yetip yetmeyeceğini gör.</p>
</div>

<div class="tool-card">
    <div class="tool-card-title"><span class="icon">✈️</span> Ülke & Süre Seç</div>

    <div class="tool-form-grid">
        <div class="tool-field">
            <label>Hedef Ülke</label>
            <select class="tool-select" id="ulke" onchange="hesapla()">
                <option value="de" selected>🇩🇪 Almanya (2. Grup)</option>
                <option value="at">🇦🇹 Avusturya (2. Grup)</option>
                <option value="be">🇧🇪 Belçika (2. Grup)</option>
                <option value="bg">🇧🇬 Bulgaristan (3. Grup)</option>
                <option value="cz">🇨🇿 Çekya (2. Grup)</option>
                <option value="dk">🇩🇰 Danimarka (1. Grup)</option>
                <option value="ee">🇪🇪 Estonya (3. Grup)</option>
                <option value="fi">🇫🇮 Finlandiya (1. Grup)</option>
                <option value="fr">🇫🇷 Fransa (2. Grup)</option>
                <option value="hr">🇭🇷 Hırvatistan (3. Grup)</option>
                <option value="nl">🇳🇱 Hollanda (2. Grup)</option>
                <option value="ie">🇮🇪 İrlanda (1. Grup)</option>
                <option value="es">🇪🇸 İspanya (2. Grup)</option>
                <option value="se">🇸🇪 İsveç (1. Grup)</option>
                <option value="it">🇮🇹 İtalya (2. Grup)</option>
                <option value="is">🇮🇸 İzlanda (1. Grup)</option>
                <option value="cy">🇨🇾 Kıbrıs (2. Grup)</option>
                <option value="mk">🇲🇰 Kuzey Makedonya (3. Grup)</option>
                <option value="lv">🇱🇻 Letonya (3. Grup)</option>
                <option value="li">🇱🇮 Lihtenştayn (1. Grup)</option>
                <option value="lt">🇱🇹 Litvanya (3. Grup)</option>
                <option value="lu">🇱🇺 Lüksemburg (1. Grup)</option>
                <option value="hu">🇭🇺 Macaristan (3. Grup)</option>
                <option value="mt">🇲🇹 Malta (2. Grup)</option>
                <option value="no">🇳🇴 Norveç (1. Grup)</option>
                <option value="pl">🇵🇱 Polonya (3. Grup)</option>
                <option value="pt">🇵🇹 Portekiz (2. Grup)</option>
                <option value="ro">🇷🇴 Romanya (3. Grup)</option>
                <option value="rs">🇷🇸 Sırbistan (3. Grup)</option>
                <option value="sk">🇸🇰 Slovakya (3. Grup)</option>
                <option value="si">🇸🇮 Slovenya (3. Grup)</option>
                <option value="gr">🇬🇷 Yunanistan (2. Grup)</option>
            </select>
        </div>
        <div class="tool-field">
            <label>Kalış Süresi (Ay)</label>
            <input type="number" class="tool-input" id="kalisSuresi" value="5" min="3" max="12" placeholder="3-12 ay">
        </div>
        <div class="tool-field">
            <label>Ek Gelir / Aile Desteği (€/ay)</label>
            <input type="number" class="tool-input" id="ekGelir" value="0" min="0" placeholder="Opsiyonel">
        </div>
        <div class="tool-field">
            <label>EUR/TRY Kuru</label>
            <input type="number" class="tool-input" id="eurKur" value="38.50" min="1" step="0.1" placeholder="Güncel kur">
        </div>
    </div>

    <button class="tool-btn tool-btn-primary tool-btn-block" onclick="hesapla()" style="margin-top:20px;">
        🌍 Hesapla
    </button>
</div>

<!-- Results -->
<div id="results" style="display:none;">
    <div class="tool-result">
        <div class="tool-result-label">Aylık Erasmus Hibesi</div>
        <div class="tool-result-value" id="hibeDisplay">0 €</div>
        <div class="tool-result-sub" id="hibeSub"></div>
    </div>

    <div class="tool-stats-grid">
        <div class="tool-stat">
            <div class="tool-stat-value clr-green" id="toplamHibe">0 €</div>
            <div class="tool-stat-label">Toplam Hibe</div>
        </div>
        <div class="tool-stat">
            <div class="tool-stat-value clr-cyan" id="toplamTL">0 ₺</div>
            <div class="tool-stat-label">TL Karşılığı</div>
        </div>
        <div class="tool-stat">
            <div class="tool-stat-value" id="aylikMaliyet">0 €</div>
            <div class="tool-stat-label">Tahmini Maliyet</div>
        </div>
        <div class="tool-stat">
            <div class="tool-stat-value" id="fark">0 €</div>
            <div class="tool-stat-label">Aylık Fark</div>
        </div>
    </div>

    <div class="tool-card">
        <div class="tool-card-title"><span class="icon">📊</span> Maliyet Detayı</div>
        <table class="tool-table" id="detayTable">
            <thead><tr><th>Kalem</th><th>Aylık (€)</th><th>Toplam (€)</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>

    <div id="adviceBox" class="tool-info tool-info-green"></div>
</div>

<!-- FAQ -->
<div class="tool-card" style="margin-top:32px;">
    <h2 style="margin-top:0; font-size:1.2rem;">❓ Sıkça Sorulan Sorular</h2>
    <div style="margin-top:20px;">
        <h3 style="font-size:1rem; margin:0 0 8px; color:#00d4ff;">Erasmus hibesi ne kadar?</h3>
        <p style="color:rgba(255,255,255,.6); line-height:1.7; margin:0 0 20px;">
            Erasmus+ hibesi Türkiye'den giden öğrenciler için ülke grubuna göre aylık 600-750€ arasındadır (2025-2026).
            Ek olarak engelli, düşük gelirli öğrenciler için top-up hibeler mevcuttur.
        </p>
        <h3 style="font-size:1rem; margin:0 0 8px; color:#00d4ff;">Hibe yaşam maliyetini karşılıyor mu?</h3>
        <p style="color:rgba(255,255,255,.6); line-height:1.7; margin:0;">
            Genellikle hibe tek başına yetmez. Kuzey Avrupa ülkelerinde hibe maliyetin %40-50'sini karşılarken,
            Doğu Avrupa ülkelerinde %80-100'ünü karşılayabilir. Aile desteği veya part-time iş ile desteklemek gerekir.
        </p>
    </div>
</div>

<script>
const fmt = n => parseFloat(n).toLocaleString('tr-TR', {maximumFractionDigits: 0});
const fmtE = n => parseFloat(n).toLocaleString('tr-TR', {maximumFractionDigits: 0});

// Ülke verileri: hibe (€/ay), tahmini yaşam maliyeti (€/ay)
const COUNTRIES = {
    // Grup 1 — 750€
    dk: { name: 'Danimarka', hibe: 750, maliyet: 1400, kira: 650, yemek: 350, ulasim: 80, diger: 320 },
    fi: { name: 'Finlandiya', hibe: 750, maliyet: 1100, kira: 500, yemek: 300, ulasim: 60, diger: 240 },
    ie: { name: 'İrlanda', hibe: 750, maliyet: 1350, kira: 700, yemek: 300, ulasim: 70, diger: 280 },
    is: { name: 'İzlanda', hibe: 750, maliyet: 1500, kira: 650, yemek: 400, ulasim: 80, diger: 370 },
    li: { name: 'Lihtenştayn', hibe: 750, maliyet: 1600, kira: 700, yemek: 400, ulasim: 60, diger: 440 },
    lu: { name: 'Lüksemburg', hibe: 750, maliyet: 1300, kira: 600, yemek: 350, ulasim: 50, diger: 300 },
    no: { name: 'Norveç', hibe: 750, maliyet: 1500, kira: 650, yemek: 400, ulasim: 80, diger: 370 },
    se: { name: 'İsveç', hibe: 750, maliyet: 1200, kira: 500, yemek: 350, ulasim: 70, diger: 280 },
    // Grup 2 — 700€
    at: { name: 'Avusturya', hibe: 700, maliyet: 1000, kira: 450, yemek: 280, ulasim: 50, diger: 220 },
    be: { name: 'Belçika', hibe: 700, maliyet: 1000, kira: 450, yemek: 280, ulasim: 50, diger: 220 },
    de: { name: 'Almanya', hibe: 700, maliyet: 950, kira: 400, yemek: 250, ulasim: 40, diger: 260 },
    fr: { name: 'Fransa', hibe: 700, maliyet: 1100, kira: 500, yemek: 300, ulasim: 60, diger: 240 },
    it: { name: 'İtalya', hibe: 700, maliyet: 900, kira: 400, yemek: 250, ulasim: 40, diger: 210 },
    es: { name: 'İspanya', hibe: 700, maliyet: 850, kira: 380, yemek: 220, ulasim: 40, diger: 210 },
    nl: { name: 'Hollanda', hibe: 700, maliyet: 1100, kira: 500, yemek: 280, ulasim: 45, diger: 275 },
    pt: { name: 'Portekiz', hibe: 700, maliyet: 750, kira: 350, yemek: 200, ulasim: 35, diger: 165 },
    gr: { name: 'Yunanistan', hibe: 700, maliyet: 700, kira: 300, yemek: 200, ulasim: 30, diger: 170 },
    cy: { name: 'Kıbrıs', hibe: 700, maliyet: 800, kira: 350, yemek: 250, ulasim: 30, diger: 170 },
    cz: { name: 'Çekya', hibe: 700, maliyet: 650, kira: 300, yemek: 180, ulasim: 20, diger: 150 },
    mt: { name: 'Malta', hibe: 700, maliyet: 850, kira: 400, yemek: 250, ulasim: 40, diger: 160 },
    // Grup 3 — 600€
    bg: { name: 'Bulgaristan', hibe: 600, maliyet: 450, kira: 200, yemek: 120, ulasim: 20, diger: 110 },
    hr: { name: 'Hırvatistan', hibe: 600, maliyet: 550, kira: 250, yemek: 150, ulasim: 25, diger: 125 },
    hu: { name: 'Macaristan', hibe: 600, maliyet: 550, kira: 250, yemek: 150, ulasim: 20, diger: 130 },
    lt: { name: 'Litvanya', hibe: 600, maliyet: 500, kira: 220, yemek: 140, ulasim: 20, diger: 120 },
    lv: { name: 'Letonya', hibe: 600, maliyet: 500, kira: 220, yemek: 140, ulasim: 20, diger: 120 },
    pl: { name: 'Polonya', hibe: 600, maliyet: 550, kira: 250, yemek: 150, ulasim: 20, diger: 130 },
    ro: { name: 'Romanya', hibe: 600, maliyet: 450, kira: 200, yemek: 120, ulasim: 15, diger: 115 },
    sk: { name: 'Slovakya', hibe: 600, maliyet: 550, kira: 250, yemek: 150, ulasim: 20, diger: 130 },
    ee: { name: 'Estonya', hibe: 600, maliyet: 600, kira: 280, yemek: 160, ulasim: 25, diger: 135 },
    si: { name: 'Slovenya', hibe: 600, maliyet: 600, kira: 300, yemek: 180, ulasim: 20, diger: 100 },
    rs: { name: 'Sırbistan', hibe: 600, maliyet: 500, kira: 250, yemek: 150, ulasim: 20, diger: 80 },
    mk: { name: 'K. Makedonya', hibe: 600, maliyet: 450, kira: 200, yemek: 150, ulasim: 15, diger: 85 }
};

function hesapla() {
    const code = document.getElementById('ulke').value;
    const sure = parseInt(document.getElementById('kalisSuresi').value) || 5;
    const ekGelir = parseFloat(document.getElementById('ekGelir').value) || 0;
    const kur = parseFloat(document.getElementById('eurKur').value) || 38.5;
    const c = COUNTRIES[code];
    if (!c) return;

    const aylikHibe = c.hibe;
    const toplamHibe = aylikHibe * sure;
    const toplamTL = toplamHibe * kur;
    const toplamGelir = aylikHibe + ekGelir;
    const fark = toplamGelir - c.maliyet;
    const karsilama = ((toplamGelir / c.maliyet) * 100).toFixed(0);

    document.getElementById('results').style.display = 'block';
    document.getElementById('hibeDisplay').textContent = fmtE(aylikHibe) + ' €';
    document.getElementById('hibeSub').textContent = `${c.name} — ${sure} ay süre ile`;
    document.getElementById('toplamHibe').textContent = fmtE(toplamHibe) + ' €';
    document.getElementById('toplamTL').textContent = fmt(toplamTL) + ' ₺';
    document.getElementById('aylikMaliyet').textContent = fmtE(c.maliyet) + ' €';

    const farkEl = document.getElementById('fark');
    farkEl.textContent = (fark >= 0 ? '+' : '') + fmtE(fark) + ' €';
    farkEl.style.color = fark >= 0 ? '#39ff14' : '#ff3b30';

    // Tablo
    document.querySelector('#detayTable tbody').innerHTML = `
        <tr><td>🏠 Kira (paylaşımlı)</td><td>${fmtE(c.kira)} €</td><td>${fmtE(c.kira * sure)} €</td></tr>
        <tr><td>🍽️ Yemek</td><td>${fmtE(c.yemek)} €</td><td>${fmtE(c.yemek * sure)} €</td></tr>
        <tr><td>🚌 Ulaşım</td><td>${fmtE(c.ulasim)} €</td><td>${fmtE(c.ulasim * sure)} €</td></tr>
        <tr><td>🎭 Diğer</td><td>${fmtE(c.diger)} €</td><td>${fmtE(c.diger * sure)} €</td></tr>
        <tr style="border-top:2px solid rgba(0,136,255,.3);font-weight:700;">
            <td>Toplam Maliyet</td><td>${fmtE(c.maliyet)} €</td><td>${fmtE(c.maliyet * sure)} €</td>
        </tr>
        <tr style="color:#00d4ff; font-weight:700;">
            <td>Erasmus Hibesi</td><td>+${fmtE(aylikHibe)} €</td><td>+${fmtE(toplamHibe)} €</td>
        </tr>
    `;

    // Tavsiye
    const box = document.getElementById('adviceBox');
    if (fark >= 0) {
        box.className = 'tool-info tool-info-green';
        box.innerHTML = `<span>✅</span><div><strong>Harika!</strong> ${c.name}'daki Erasmus hiben${ekGelir > 0 ? ' ve ek gelirin' : ''} yaşam maliyetini karşılıyor (%${karsilama}). Rahat bir dönem geçirebilirsin!</div>`;
    } else {
        box.className = 'tool-info tool-info-orange';
        box.innerHTML = `<span>⚠️</span><div><strong>Dikkat:</strong> ${c.name}'da aylık ${fmtE(Math.abs(fark))}€ açığın olacak. Toplam ${sure} ayda ${fmtE(Math.abs(fark) * sure)}€ (≈${fmt(Math.abs(fark) * sure * kur)}₺) ek kaynağa ihtiyacın var. Part-time iş veya aile desteği düşün.</div>`;
    }

    document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

hesapla();
</script>

<?php require __DIR__ . '/../includes/seo_tools_foot.php'; ?>
