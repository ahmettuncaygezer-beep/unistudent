<?php
$__title    = 'Asgari Ücret Net Hesaplayıcı 2026';
$__desc     = '2026 asgari ücret net hesaplama. Brüt maaştan SGK, gelir vergisi, damga vergisi kesintileri sonrası ele geçen net maaşı hesaplayın.';
$__keywords = 'asgari ücret 2026, asgari ücret net, maaş hesaplama, net maaş hesaplama, SGK kesintisi, gelir vergisi, brüt net çevirici';
$__canonical = rtrim(getenv('APP_URL') ?: 'http://localhost/unistudent', '/') . '/tools/asgari-ucret.php';
$__schema = json_encode([
    "@context" => "https://schema.org",
    "@graph" => [
        [
            "@type" => "WebApplication",
            "name" => "Asgari Ücret Net Hesaplayıcı 2026",
            "description" => $__desc,
            "url" => $__canonical,
            "applicationCategory" => "FinanceApplication",
            "operatingSystem" => "Web",
            "offers" => ["@type" => "Offer", "price" => "0", "priceCurrency" => "TRY"]
        ],
        [
            "@type" => "FAQPage",
            "mainEntity" => [
                [
                    "@type" => "Question",
                    "name" => "2026 asgari ücret net ne kadar?",
                    "acceptedAnswer" => [
                        "@type" => "Answer",
                        "text" => "2026 yılı ilk yarısı için brüt asgari ücret 26.005,50 TL olup SGK ve vergi kesintileri sonrası net asgari ücret yaklaşık 22.104,67 TL'dir."
                    ]
                ],
                [
                    "@type" => "Question",
                    "name" => "Maaştan ne kadar SGK kesilir?",
                    "acceptedAnswer" => [
                        "@type" => "Answer",
                        "text" => "SGK işçi payı brüt maaşın %14'ü oranında kesilir. Ayrıca %1 işsizlik sigortası primi de işçiden kesilir."
                    ]
                ]
            ]
        ]
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$__allowDemoLogin = true; // Public tool page — allow demo auto-login
require __DIR__ . '/../includes/seo_tools_head.php';
?>

<!-- Breadcrumb -->
<div class="tool-breadcrumb">
    <a href="../index.php">Ana Sayfa</a>
    <span class="separator">›</span>
    <a href="index.php">Araçlar</a>
    <span class="separator">›</span>
    <span class="current">Asgari Ücret Net Hesaplayıcı</span>
</div>

<!-- Hero -->
<div class="tool-hero">
    <div class="tool-badge">💰 Maaş Hesaplayıcı</div>
    <h1 class="tool-title">Asgari Ücret <span class="tg">Net Hesaplayıcı</span> <small style="font-size:0.5em; opacity:0.5;">2026</small></h1>
    <p class="tool-subtitle">Brüt maaştan tüm yasal kesintiler çıktıktan sonra eline geçen net maaşı anında hesapla.</p>
</div>

<!-- Quick Result: 2026 Default -->
<div class="tool-result" id="quickResult">
    <div class="tool-result-label">2026 Net Asgari Ücret</div>
    <div class="tool-result-value" id="netDisplay">22.104,67 ₺</div>
    <div class="tool-result-sub">Brüt: 26.005,50 ₺ — Tüm kesintiler düşülmüş hali</div>
</div>

<!-- Calculator -->
<div class="tool-card">
    <div class="tool-card-title"><span class="icon">⚙️</span> Maaş Hesapla</div>

    <div class="tool-form-grid">
        <div class="tool-field">
            <label>Brüt Maaş (₺)</label>
            <input type="number" class="tool-input" id="brutMaas" value="26005.50" min="0" step="0.01" placeholder="Brüt maaşı girin">
        </div>
        <div class="tool-field">
            <label>Gelir Vergisi Matrah Dilimi</label>
            <select class="tool-select" id="vergiDilimi">
                <option value="1">1. Dilim (0 - 158.000 ₺) — %15</option>
                <option value="2">2. Dilim (158.000 - 330.000 ₺) — %20</option>
                <option value="3">3. Dilim (330.000 - 800.000 ₺) — %27</option>
                <option value="4">4. Dilim (800.000 - 4.300.000 ₺) — %35</option>
                <option value="5">5. Dilim (4.300.000 ₺ +) — %40</option>
            </select>
        </div>
        <div class="tool-field">
            <label>Engelli İndirimi</label>
            <select class="tool-select" id="engelliDerece">
                <option value="0">Yok</option>
                <option value="1">1. Derece — 6.900 ₺</option>
                <option value="2">2. Derece — 4.000 ₺</option>
                <option value="3">3. Derece — 2.000 ₺</option>
            </select>
        </div>
        <div class="tool-field">
            <label>AGİ (Asgari Geçim İndirimi)</label>
            <select class="tool-select" id="agiDurum">
                <option value="0">Uygulanmıyor (2022+ kaldırıldı)</option>
            </select>
        </div>
    </div>

    <button class="tool-btn tool-btn-primary tool-btn-block" onclick="hesapla()" style="margin-top:20px;">
        🧮 Hesapla
    </button>
</div>

<!-- Detailed Breakdown -->
<div id="breakdownSection" style="display:none;">
    <div class="tool-card">
        <div class="tool-card-title"><span class="icon">📋</span> Kesinti Detayı</div>
        <table class="tool-table" id="breakdownTable">
            <thead>
                <tr>
                    <th>Kalem</th>
                    <th>Oran</th>
                    <th style="text-align:right;">Tutar</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <!-- Stats -->
    <div class="tool-stats-grid" id="statsGrid"></div>

    <!-- Year-by-Month Table -->
    <div class="tool-card">
        <div class="tool-card-title"><span class="icon">📅</span> 12 Aylık Kümülatif Vergi Etkisi</div>
        <p style="color:rgba(255,255,255,.5); font-size:0.85rem; margin:0 0 16px;">Gelir vergisi, kümülatif matrah arttıkça dilim atlayabilir. İşte aylık net maaş değişimi:</p>
        <div style="overflow-x:auto;">
            <table class="tool-table" id="monthlyTable">
                <thead>
                    <tr>
                        <th>Ay</th>
                        <th>Küm. Matrah</th>
                        <th>Vergi Dilimi</th>
                        <th>Gelir Vergisi</th>
                        <th style="text-align:right;">Net Maaş</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Info -->
<div class="tool-info tool-info-blue">
    <span>ℹ️</span>
    <div>
        <strong>Not:</strong> Asgari ücretlilerde gelir vergisi ve damga vergisi istisnası uygulanmaktadır. 
        Asgari ücret üzerinden gelir vergisi ve damga vergisi kesilmez. Bu hesaplayıcı hem asgari ücret 
        hem de asgari ücret üstü maaşlar için çalışır.
    </div>
</div>

<!-- FAQ -->
<div class="tool-card" style="margin-top:32px;">
    <h2 style="margin-top:0; font-size:1.2rem;">❓ Sıkça Sorulan Sorular</h2>

    <div style="margin-top:20px;">
        <h3 style="font-size:1rem; margin:0 0 8px; color:#00d4ff;">2026 asgari ücret net ne kadar?</h3>
        <p style="color:rgba(255,255,255,.6); line-height:1.7; margin:0 0 20px;">
            2026 yılı ilk yarısı için brüt asgari ücret 26.005,50 TL olup SGK ve vergi kesintileri sonrası 
            net asgari ücret yaklaşık 22.104,67 TL'dir. Asgari ücrette gelir vergisi ve damga vergisi istisnası uygulanır.
        </p>

        <h3 style="font-size:1rem; margin:0 0 8px; color:#00d4ff;">Maaştan ne kadar SGK kesilir?</h3>
        <p style="color:rgba(255,255,255,.6); line-height:1.7; margin:0 0 20px;">
            SGK işçi payı brüt maaşın %14'ü oranında kesilir. Ayrıca %1 işsizlik sigortası primi de işçiden kesilir.
            Toplam işçi kesintisi %15'tir.
        </p>

        <h3 style="font-size:1rem; margin:0 0 8px; color:#00d4ff;">Gelir vergisi nasıl hesaplanır?</h3>
        <p style="color:rgba(255,255,255,.6); line-height:1.7; margin:0;">
            Gelir vergisi kümülatif matrah üzerinden artan oranlı olarak hesaplanır. 2026'da 5 dilim vardır: 
            %15, %20, %27, %35 ve %40. Yıl içinde matrah arttıkça üst dilimlere geçilir ve daha fazla vergi kesilir.
        </p>
    </div>
</div>

<script>
const fmt = n => parseFloat(n).toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
const fmtI = n => parseFloat(n).toLocaleString('tr-TR', {maximumFractionDigits: 0});

// 2026 Vergi Dilimleri (kümülatif)
const TAX_BRACKETS = [
    { limit: 158000,   rate: 0.15 },
    { limit: 330000,   rate: 0.20 },
    { limit: 800000,   rate: 0.27 },
    { limit: 4300000,  rate: 0.35 },
    { limit: Infinity, rate: 0.40 }
];

const ASGARI_BRUT = 26005.50;
const SGK_ISCI    = 0.14;  // %14
const ISSIZLIK    = 0.01;  // %1
const DAMGA       = 0.00759; // %0.759

// Engelli indirimi aylık tutarlar (2026 tahmini)
const ENGELLI_INDIRIM = { 0: 0, 1: 6900, 2: 4000, 3: 2000 };

function hesapla() {
    const brut = parseFloat(document.getElementById('brutMaas').value) || 0;
    const engelliDerece = parseInt(document.getElementById('engelliDerece').value);
    const isAsgari = brut <= ASGARI_BRUT;

    // SGK Kesintileri
    const sgkPrimi = brut * SGK_ISCI;
    const issizlikPrimi = brut * ISSIZLIK;
    const toplamSgk = sgkPrimi + issizlikPrimi;

    // Gelir Vergisi Matrahı
    let matrah = brut - toplamSgk;
    const engelliIndirim = ENGELLI_INDIRIM[engelliDerece] || 0;
    matrah = Math.max(0, matrah - engelliIndirim);

    // Gelir Vergisi (ilk ay için — dilim 1)
    const gelirVergisi = isAsgari ? 0 : matrah * 0.15;

    // Damga Vergisi
    const damgaVergisi = isAsgari ? 0 : brut * DAMGA;

    // Net Maaş
    const net = brut - toplamSgk - gelirVergisi - damgaVergisi;

    // Update quick result
    document.getElementById('netDisplay').textContent = fmt(net) + ' ₺';
    document.getElementById('quickResult').querySelector('.tool-result-sub').textContent = 
        `Brüt: ${fmt(brut)} ₺ — Tüm kesintiler düşülmüş hali`;

    // Show breakdown
    document.getElementById('breakdownSection').style.display = 'block';

    const tbody = document.querySelector('#breakdownTable tbody');
    tbody.innerHTML = `
        <tr><td style="font-weight:600;">Brüt Maaş</td><td>—</td><td style="text-align:right; font-weight:700;">${fmt(brut)} ₺</td></tr>
        <tr><td>SGK İşçi Primi</td><td>%14</td><td style="text-align:right; color:#ff3b30;">-${fmt(sgkPrimi)} ₺</td></tr>
        <tr><td>İşsizlik Sigortası</td><td>%1</td><td style="text-align:right; color:#ff3b30;">-${fmt(issizlikPrimi)} ₺</td></tr>
        <tr><td>Gelir Vergisi</td><td>${isAsgari ? 'İstisna' : '%15'}</td><td style="text-align:right; color:${gelirVergisi > 0 ? '#ff3b30' : '#39ff14'};">${gelirVergisi > 0 ? '-' + fmt(gelirVergisi) : '0,00'} ₺</td></tr>
        <tr><td>Damga Vergisi</td><td>${isAsgari ? 'İstisna' : '%0,759'}</td><td style="text-align:right; color:${damgaVergisi > 0 ? '#ff3b30' : '#39ff14'};">${damgaVergisi > 0 ? '-' + fmt(damgaVergisi) : '0,00'} ₺</td></tr>
        ${engelliIndirim > 0 ? `<tr><td>Engelli İndirimi</td><td>${engelliDerece}. Derece</td><td style="text-align:right; color:#39ff14;">+${fmt(engelliIndirim)} ₺</td></tr>` : ''}
        <tr style="border-top:2px solid rgba(0,136,255,.3);"><td style="font-weight:800; font-size:1.05rem;">Net Maaş</td><td></td><td style="text-align:right; font-weight:800; font-size:1.1rem; color:#39ff14;">${fmt(net)} ₺</td></tr>
    `;

    // Stats
    const toplamKesinti = toplamSgk + gelirVergisi + damgaVergisi;
    const eleGecenOran = brut > 0 ? ((net / brut) * 100).toFixed(1) : '0';
    const yillikNet = net * 12;

    document.getElementById('statsGrid').innerHTML = `
        <div class="tool-stat">
            <div class="tool-stat-value clr-red">${fmt(toplamKesinti)} ₺</div>
            <div class="tool-stat-label">Toplam Kesinti</div>
        </div>
        <div class="tool-stat">
            <div class="tool-stat-value clr-green">%${eleGecenOran}</div>
            <div class="tool-stat-label">Ele Geçen Oran</div>
        </div>
        <div class="tool-stat">
            <div class="tool-stat-value clr-cyan">${fmtI(yillikNet)} ₺</div>
            <div class="tool-stat-label">Yıllık Net</div>
        </div>
        <div class="tool-stat">
            <div class="tool-stat-value clr-purple">${fmtI(net / 30)} ₺</div>
            <div class="tool-stat-label">Günlük Net</div>
        </div>
    `;

    // 12-month cumulative table
    if (!isAsgari) {
        let cumMatrah = 0;
        const mtbody = document.querySelector('#monthlyTable tbody');
        let rows = '';
        for (let m = 1; m <= 12; m++) {
            const monthlyMatrah = matrah;
            cumMatrah += monthlyMatrah;

            // Find applicable bracket
            let bracketRate = 0.15;
            let bracketLabel = '1. Dilim (%15)';
            for (let i = 0; i < TAX_BRACKETS.length; i++) {
                if (cumMatrah <= TAX_BRACKETS[i].limit || i === TAX_BRACKETS.length - 1) {
                    bracketRate = TAX_BRACKETS[i].rate;
                    bracketLabel = `${i+1}. Dilim (%${(bracketRate*100).toFixed(0)})`;
                    break;
                }
            }

            const monthGelirVergisi = monthlyMatrah * bracketRate;
            const monthNet = brut - toplamSgk - monthGelirVergisi - damgaVergisi;
            const aylar = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];

            rows += `<tr>
                <td style="font-weight:600;">${aylar[m-1]}</td>
                <td>${fmtI(cumMatrah)} ₺</td>
                <td><span style="padding:3px 8px; border-radius:6px; font-size:.78rem; font-weight:600; background:rgba(0,136,255,.1); color:#0088ff;">${bracketLabel}</span></td>
                <td style="color:#ff3b30;">-${fmt(monthGelirVergisi)} ₺</td>
                <td style="text-align:right; font-weight:700; color:${monthNet < net ? '#ff9100' : '#39ff14'};">${fmt(monthNet)} ₺</td>
            </tr>`;
        }
        mtbody.innerHTML = rows;
        document.querySelector('#monthlyTable').closest('.tool-card').style.display = 'block';
    } else {
        document.querySelector('#monthlyTable').closest('.tool-card').style.display = 'none';
    }

    // Scroll
    document.getElementById('quickResult').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Auto-calculate on load
hesapla();
</script>

<?php require __DIR__ . '/../includes/seo_tools_foot.php'; ?>
