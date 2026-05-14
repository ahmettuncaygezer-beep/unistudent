<?php
$__title    = 'KPSS Puan Tahmin Hesaplayıcı 2026';
$__desc     = 'KPSS Genel Yetenek ve Genel Kültür net sayılarından tahmini KPSS P3 puanını hesaplayın. Güncel katsayılar ile anlık hesaplama.';
$__keywords = 'KPSS puan hesaplama, KPSS puan tahmin, KPSS net hesaplama, KPSS 2026, P3 puan hesaplama, KPSS lisans puanı';
$__canonical = rtrim(getenv('APP_URL') ?: 'http://localhost/unistudent', '/') . '/tools/kpss-puan.php';
$__schema = json_encode([
    "@context" => "https://schema.org",
    "@graph" => [
        [
            "@type" => "WebApplication",
            "name" => "KPSS Puan Tahmin Hesaplayıcı",
            "description" => $__desc,
            "url" => $__canonical,
            "applicationCategory" => "EducationApplication",
            "operatingSystem" => "Web",
            "offers" => ["@type" => "Offer", "price" => "0", "priceCurrency" => "TRY"]
        ],
        [
            "@type" => "FAQPage",
            "mainEntity" => [
                ["@type" => "Question", "name" => "KPSS puanı nasıl hesaplanır?",
                 "acceptedAnswer" => ["@type" => "Answer", "text" => "KPSS puanı, Genel Yetenek ve Genel Kültür testlerindeki net sayılarınız, ortalama ve standart sapma değerleri kullanılarak standart puan formülü ile hesaplanır. P3 puanı için GY: %60, GK: %40 ağırlık uygulanır."]],
                ["@type" => "Question", "name" => "KPSS'de kaç net ile 70 puan alınır?",
                 "acceptedAnswer" => ["@type" => "Answer", "text" => "Ortalama zorlukta bir sınavda yaklaşık GY: 35-40, GK: 25-30 net ile 70 puan civarı alınabilir. Ancak bu, sınavın zorluk seviyesine göre değişir."]]
            ]
        ]
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$__allowDemoLogin = true; // Public tool page — allow demo auto-login
require __DIR__ . '/../includes/seo_tools_head.php';
?>

<div class="tool-breadcrumb">
    <a href="../index.php">Ana Sayfa</a><span class="separator">›</span>
    <a href="index.php">Araçlar</a><span class="separator">›</span>
    <span class="current">KPSS Puan Tahmin</span>
</div>

<div class="tool-hero">
    <div class="tool-badge">📝 Sınav Aracı</div>
    <h1 class="tool-title">KPSS Puan <span class="tg">Tahmin Hesaplayıcı</span></h1>
    <p class="tool-subtitle">Genel Yetenek ve Genel Kültür net sayılarından tahmini KPSS P3 puanını hesapla.</p>
</div>

<div class="tool-card">
    <div class="tool-card-title"><span class="icon">📊</span> Net Sayılarını Gir</div>

    <div class="tool-form-grid">
        <div class="tool-field">
            <label>Genel Yetenek — Doğru Sayısı</label>
            <input type="number" class="tool-input" id="gyDogru" min="0" max="60" value="35" placeholder="0-60">
        </div>
        <div class="tool-field">
            <label>Genel Yetenek — Yanlış Sayısı</label>
            <input type="number" class="tool-input" id="gyYanlis" min="0" max="60" value="5" placeholder="0-60">
        </div>
        <div class="tool-field">
            <label>Genel Kültür — Doğru Sayısı</label>
            <input type="number" class="tool-input" id="gkDogru" min="0" max="60" value="28" placeholder="0-60">
        </div>
        <div class="tool-field">
            <label>Genel Kültür — Yanlış Sayısı</label>
            <input type="number" class="tool-input" id="gkYanlis" min="0" max="60" value="7" placeholder="0-60">
        </div>
    </div>

    <div class="tool-info tool-info-blue" style="margin:16px 0 4px;">
        <span>ℹ️</span>
        <div>Her iki test de 60 sorudan oluşur. Net = Doğru − (Yanlış ÷ 4). Boş bırakılan sorular nete etki etmez.</div>
    </div>

    <div class="tool-form-grid" style="margin-top:16px;">
        <div class="tool-field">
            <label>Sınav Zorluk Seviyesi</label>
            <select class="tool-select" id="zorlukSeviye">
                <option value="easy">Kolay (Yüksek ortalama)</option>
                <option value="normal" selected>Normal</option>
                <option value="hard">Zor (Düşük ortalama)</option>
            </select>
        </div>
        <div class="tool-field">
            <label>Puan Türü</label>
            <select class="tool-select" id="puanTuru">
                <option value="p3" selected>P3 (GY: %60 + GK: %40)</option>
                <option value="p1">P1 (Sadece GY: %100)</option>
                <option value="p2">P2 (Sadece GK: %100)</option>
                <option value="p4">P4 (GY: %40 + GK: %60)</option>
            </select>
        </div>
    </div>

    <button class="tool-btn tool-btn-primary tool-btn-block" onclick="hesapla()" style="margin-top:20px;">
        🎯 Puanımı Hesapla
    </button>
</div>

<!-- Results -->
<div id="results" style="display:none;">
    <div class="tool-result" id="mainResult">
        <div class="tool-result-label">Tahmini KPSS Puanı</div>
        <div class="tool-result-value" id="puanDisplay">0</div>
        <div class="tool-result-sub" id="puanSub"></div>
    </div>

    <div class="tool-stats-grid">
        <div class="tool-stat">
            <div class="tool-stat-value clr-cyan" id="gyNet">0</div>
            <div class="tool-stat-label">GY Net</div>
        </div>
        <div class="tool-stat">
            <div class="tool-stat-value clr-purple" id="gkNet">0</div>
            <div class="tool-stat-label">GK Net</div>
        </div>
        <div class="tool-stat">
            <div class="tool-stat-value" id="toplamNet">0</div>
            <div class="tool-stat-label">Toplam Net</div>
        </div>
        <div class="tool-stat">
            <div class="tool-stat-value" id="puanSeviye">—</div>
            <div class="tool-stat-label">Seviye</div>
        </div>
    </div>

    <!-- Puan Karşılaştırma -->
    <div class="tool-card">
        <div class="tool-card-title"><span class="icon">📊</span> Puan Skalası</div>
        <div id="puanBar" style="position:relative; height:40px; background:linear-gradient(90deg, #ff3b30 0%, #ff9100 30%, #ffd700 50%, #39ff14 75%, #00d4ff 100%); border-radius:12px; margin:20px 0 12px; overflow:visible;">
            <div id="puanMarker" style="position:absolute; top:-10px; width:4px; height:60px; background:#fff; border-radius:2px; transition:left 0.5s ease; box-shadow:0 0 10px rgba(255,255,255,0.5);"></div>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:0.8rem; color:rgba(255,255,255,.5);">
            <span>40</span><span>50</span><span>60</span><span>70</span><span>80</span><span>90</span><span>100</span>
        </div>

        <div style="margin-top:24px;">
            <h4 style="margin:0 0 12px; font-size:0.95rem;">🎯 Hedef Puan Gereksinimleri</h4>
            <table class="tool-table">
                <thead><tr><th>Kadro Türü</th><th>Tahmini Taban</th><th>Durumun</th></tr></thead>
                <tbody id="kadroDB"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- FAQ -->
<div class="tool-card" style="margin-top:32px;">
    <h2 style="margin-top:0; font-size:1.2rem;">❓ Sıkça Sorulan Sorular</h2>
    <div style="margin-top:20px;">
        <h3 style="font-size:1rem; margin:0 0 8px; color:#00d4ff;">KPSS puanı nasıl hesaplanır?</h3>
        <p style="color:rgba(255,255,255,.6); line-height:1.7; margin:0 0 20px;">
            KPSS puanı, standart puan formülü ile hesaplanır. Her adayın netleri, sınav ortalaması ve standart sapma 
            kullanılarak standart skora çevrilir. P3 puanı için GY %60, GK %40 ağırlıkla birleştirilir.
        </p>
        <h3 style="font-size:1rem; margin:0 0 8px; color:#00d4ff;">KPSS'de kaç net ile 70 puan alınır?</h3>
        <p style="color:rgba(255,255,255,.6); line-height:1.7; margin:0;">
            Normal zorlukta bir sınavda yaklaşık GY: 35-40, GK: 25-30 net ile 70 puan civarı alınabilir. 
            Ancak bu, sınavın zorluk seviyesine ve aday dağılımına göre değişir.
        </p>
    </div>
</div>

<script>
const fmt = n => parseFloat(n).toFixed(2);

// Zorluk seviyesine göre ortalama ve SD
const PARAMS = {
    easy:   { gyMean: 32, gkMean: 28, gySD: 10, gkSD: 9 },
    normal: { gyMean: 27, gkMean: 22, gySD: 11, gkSD: 10 },
    hard:   { gyMean: 20, gkMean: 16, gySD: 10, gkSD: 9 }
};

// Puan türü ağırlıkları
const WEIGHTS = {
    p1: { gy: 1.0, gk: 0.0 },
    p2: { gy: 0.0, gk: 1.0 },
    p3: { gy: 0.6, gk: 0.4 },
    p4: { gy: 0.4, gk: 0.6 }
};

// Kadro taban puanları
const KADROLAR = [
    { name: 'Memur (Genel)', taban: 60 },
    { name: 'VHKİ', taban: 65 },
    { name: 'Zabıt Katibi', taban: 70 },
    { name: 'Uzman Çavuş', taban: 72 },
    { name: 'Öğretmen (Atama)', taban: 75 },
    { name: 'Müfettiş Yrd.', taban: 80 },
    { name: 'Kaymakamlık', taban: 85 },
];

function hesapla() {
    const gyD = parseInt(document.getElementById('gyDogru').value) || 0;
    const gyY = parseInt(document.getElementById('gyYanlis').value) || 0;
    const gkD = parseInt(document.getElementById('gkDogru').value) || 0;
    const gkY = parseInt(document.getElementById('gkYanlis').value) || 0;
    const zorluk = document.getElementById('zorlukSeviye').value;
    const puanTuru = document.getElementById('puanTuru').value;

    // Net hesaplama
    const gyNet = Math.max(0, gyD - (gyY / 4));
    const gkNet = Math.max(0, gkD - (gkY / 4));

    // Standart puan hesaplama
    const p = PARAMS[zorluk];
    const w = WEIGHTS[puanTuru];

    const gyStd = p.gySD > 0 ? ((gyNet - p.gyMean) / p.gySD) : 0;
    const gkStd = p.gkSD > 0 ? ((gkNet - p.gkMean) / p.gkSD) : 0;

    // Ağırlıklı standart puan → KPSS puanına dönüştürme
    const weightedStd = gyStd * w.gy + gkStd * w.gk;
    const puan = Math.min(100, Math.max(30, 50 + weightedStd * 10));

    // Seviye
    let seviye = '❌ Düşük';
    let seviyeColor = '#ff3b30';
    if (puan >= 85) { seviye = '🏆 Mükemmel'; seviyeColor = '#00d4ff'; }
    else if (puan >= 75) { seviye = '🌟 Çok İyi'; seviyeColor = '#39ff14'; }
    else if (puan >= 65) { seviye = '✅ İyi'; seviyeColor = '#ffd700'; }
    else if (puan >= 55) { seviye = '⚠️ Orta'; seviyeColor = '#ff9100'; }

    // Display
    document.getElementById('results').style.display = 'block';
    document.getElementById('puanDisplay').textContent = puan.toFixed(2);
    document.getElementById('puanSub').textContent = `${puanTuru.toUpperCase()} Puanı — GY: ${gyNet.toFixed(2)} net, GK: ${gkNet.toFixed(2)} net`;
    document.getElementById('gyNet').textContent = gyNet.toFixed(1);
    document.getElementById('gkNet').textContent = gkNet.toFixed(1);
    document.getElementById('toplamNet').textContent = (gyNet + gkNet).toFixed(1);
    document.getElementById('puanSeviye').textContent = seviye;
    document.getElementById('puanSeviye').style.color = seviyeColor;
    document.getElementById('puanSeviye').style.fontSize = '0.85rem';

    // Puan bar marker
    const barPct = Math.max(0, Math.min(100, ((puan - 40) / 60) * 100));
    document.getElementById('puanMarker').style.left = `calc(${barPct}% - 2px)`;

    // Kadro tablosu
    const kadroDB = document.getElementById('kadroDB');
    kadroDB.innerHTML = KADROLAR.map(k => {
        const ok = puan >= k.taban;
        return `<tr>
            <td style="font-weight:600;">${k.name}</td>
            <td>${k.taban}+</td>
            <td style="color:${ok ? '#39ff14' : '#ff3b30'}; font-weight:700;">${ok ? '✅ Yeterli' : '❌ Yetersiz'}</td>
        </tr>`;
    }).join('');

    document.getElementById('mainResult').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

hesapla();
</script>

<?php require __DIR__ . '/../includes/seo_tools_foot.php'; ?>
