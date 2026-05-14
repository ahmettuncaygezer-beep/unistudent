<?php
$__title    = 'Yüksek Lisans ROI Hesaplayıcı — Maaş Artışı Ne Zaman Geri Döner?';
$__desc     = 'Yüksek lisans yapmanın maaş artışı ROI hesabı. Yatırımın kaç yılda geri döneceğini, ALES hazırlık süresini ve kariyer etkisini hesapla.';
$__keywords = 'yüksek lisans maaş artışı, yüksek lisans ROI, ALES hazırlık, master yapmak mantıklı mı, yüksek lisans maliyet, akademik kariyer';
$__canonical = rtrim(getenv('APP_URL') ?: 'http://localhost/unistudent', '/') . '/tools/yuksek-lisans-roi.php';
$__schema = json_encode([
    "@context" => "https://schema.org",
    "@type" => "WebApplication",
    "name" => "Yüksek Lisans ROI Hesaplayıcı",
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
    <span class="current">Yüksek Lisans ROI</span>
</div>

<div class="tool-hero">
    <div class="tool-badge">🎓 Kariyer Aracı</div>
    <h1 class="tool-title">Yüksek Lisans <span class="tg">ROI Hesaplayıcı</span></h1>
    <p class="tool-subtitle">Yüksek lisans yapmanın maaş artışı karşılığında kaç yılda kendini amorti edeceğini hesapla.</p>
</div>

<div class="tool-card">
    <div class="tool-card-title"><span class="icon">📊</span> Bilgilerini Gir</div>
    <div class="tool-form-grid">
        <div class="tool-field">
            <label>Lisans Maaşı (Aylık Net ₺)</label>
            <input type="number" class="tool-input" id="lisansMaas" value="25000" min="0" step="500">
        </div>
        <div class="tool-field">
            <label>YL Sonrası Beklenen Maaş (₺)</label>
            <input type="number" class="tool-input" id="ylMaas" value="35000" min="0" step="500">
        </div>
        <div class="tool-field">
            <label>YL Süresi (Ay)</label>
            <input type="number" class="tool-input" id="ylSure" value="24" min="12" max="48">
        </div>
        <div class="tool-field">
            <label>Aylık YL Maliyeti (₺)</label>
            <input type="number" class="tool-input" id="ylMaliyet" value="3000" min="0" step="100">
        </div>
        <div class="tool-field">
            <label>YL Süresinde Çalışacak mısın?</label>
            <select class="tool-select" id="calisma">
                <option value="full">Evet, tam zamanlı</option>
                <option value="part" selected>Evet, yarı zamanlı</option>
                <option value="no">Hayır, sadece okuyacağım</option>
            </select>
        </div>
        <div class="tool-field">
            <label>ALES Hazırlık Süresi (Ay)</label>
            <input type="number" class="tool-input" id="alesAy" value="4" min="0" max="24">
        </div>
    </div>
    <button class="tool-btn tool-btn-primary tool-btn-block" onclick="hesapla()" style="margin-top:20px;">
        📈 ROI Hesapla
    </button>
</div>

<div id="results" style="display:none;">
    <div class="tool-result">
        <div class="tool-result-label">Yatırım Geri Dönüş Süresi</div>
        <div class="tool-result-value" id="roiDisplay">0 Yıl</div>
        <div class="tool-result-sub" id="roiSub"></div>
    </div>

    <div class="tool-stats-grid">
        <div class="tool-stat"><div class="tool-stat-value clr-red" id="toplamMaliyet">0 ₺</div><div class="tool-stat-label">Toplam Maliyet</div></div>
        <div class="tool-stat"><div class="tool-stat-value clr-green" id="aylikFark">0 ₺</div><div class="tool-stat-label">Aylık Maaş Farkı</div></div>
        <div class="tool-stat"><div class="tool-stat-value clr-cyan" id="yillikKazanc">0 ₺</div><div class="tool-stat-label">Yıllık Ek Kazanç</div></div>
        <div class="tool-stat"><div class="tool-stat-value clr-purple" id="onYilKazanc">0 ₺</div><div class="tool-stat-label">10 Yıllık Ek Kazanç</div></div>
    </div>

    <div class="tool-card">
        <div class="tool-card-title"><span class="icon">📋</span> Maliyet & Kazanç Analizi</div>
        <table class="tool-table" id="detayTable"><thead><tr><th>Kalem</th><th style="text-align:right;">Tutar</th></tr></thead><tbody></tbody></table>
    </div>

    <!-- Chart -->
    <div class="tool-card">
        <div class="tool-card-title"><span class="icon">📊</span> Kümülatif Kazanç Grafiği</div>
        <div class="tool-chart-wrap"><canvas id="roiChart"></canvas></div>
    </div>

    <div id="adviceBox" class="tool-info tool-info-green"></div>
</div>

<div class="tool-card" style="margin-top:32px;">
    <h2 style="margin-top:0; font-size:1.2rem;">❓ Sıkça Sorulan Sorular</h2>
    <div style="margin-top:20px;">
        <h3 style="font-size:1rem; margin:0 0 8px; color:#00d4ff;">Yüksek lisans yapmak maaşı ne kadar artırır?</h3>
        <p style="color:rgba(255,255,255,.6); line-height:1.7; margin:0 0 20px;">
            Türkiye'de YL mezunları lisans mezunlarına göre ortalama %25-40 daha yüksek maaş alır. 
            Özellikle mühendislik, finans, veri bilimi gibi alanlarda fark daha belirgindir.
        </p>
        <h3 style="font-size:1rem; margin:0 0 8px; color:#00d4ff;">ALES hazırlığı ne kadar sürer?</h3>
        <p style="color:rgba(255,255,255,.6); line-height:1.7; margin:0;">
            Ortalama 3-6 ay düzenli çalışma ile ALES'te 65+ puan alınabilir. Sayısal bölüm mezunları 
            genellikle daha kısa sürede hazırlanabilir.
        </p>
    </div>
</div>

<script>
const fmt = n => parseFloat(n).toLocaleString('tr-TR', {maximumFractionDigits: 0});
let chartInstance = null;

function hesapla() {
    const lisansM = parseFloat(document.getElementById('lisansMaas').value) || 0;
    const ylM = parseFloat(document.getElementById('ylMaas').value) || 0;
    const ylSure = parseInt(document.getElementById('ylSure').value) || 24;
    const ylAylikM = parseFloat(document.getElementById('ylMaliyet').value) || 0;
    const calisma = document.getElementById('calisma').value;
    const alesAy = parseInt(document.getElementById('alesAy').value) || 0;

    // Fırsat maliyeti
    const calismaCarpan = { full: 1.0, part: 0.5, no: 0.0 };
    const ylSirasindaGelir = lisansM * (calismaCarpan[calisma] || 0);
    const firsatMaliyeti = (lisansM - ylSirasindaGelir) * ylSure;
    const egitimMaliyeti = ylAylikM * ylSure;
    const alesMaliyeti = 2000; // kurs + kitap tahmini
    const toplamMaliyet = firsatMaliyeti + egitimMaliyeti + alesMaliyeti;

    const aylikFark = ylM - lisansM;
    const yillikFark = aylikFark * 12;
    const roiAy = aylikFark > 0 ? Math.ceil(toplamMaliyet / aylikFark) : 999;
    const roiYil = (roiAy / 12).toFixed(1);
    const onYilKazanc = yillikFark * 10 - toplamMaliyet;

    document.getElementById('results').style.display = 'block';
    document.getElementById('roiDisplay').textContent = roiYil + ' Yıl';
    document.getElementById('roiSub').textContent = aylikFark > 0 
        ? `Aylık ${fmt(aylikFark)}₺ fazla kazanarak, ${roiAy} ayda yatırımını geri alırsın`
        : 'Maaş artışı yok — YL finansal ROI sağlamıyor';
    document.getElementById('toplamMaliyet').textContent = fmt(toplamMaliyet) + ' ₺';
    document.getElementById('aylikFark').textContent = '+' + fmt(aylikFark) + ' ₺';
    document.getElementById('yillikKazanc').textContent = '+' + fmt(yillikFark) + ' ₺';
    document.getElementById('onYilKazanc').textContent = (onYilKazanc >= 0 ? '+' : '') + fmt(onYilKazanc) + ' ₺';

    // Tablo
    document.querySelector('#detayTable tbody').innerHTML = `
        <tr><td>📚 Eğitim Maliyeti (${ylSure} ay × ${fmt(ylAylikM)}₺)</td><td style="text-align:right;color:#ff3b30;">-${fmt(egitimMaliyeti)} ₺</td></tr>
        <tr><td>💼 Fırsat Maliyeti (kaçırılan gelir)</td><td style="text-align:right;color:#ff3b30;">-${fmt(firsatMaliyeti)} ₺</td></tr>
        <tr><td>📝 ALES Hazırlık (kurs + materyal)</td><td style="text-align:right;color:#ff3b30;">-${fmt(alesMaliyeti)} ₺</td></tr>
        <tr style="border-top:2px solid rgba(255,59,48,.3);"><td style="font-weight:700;">Toplam Yatırım</td><td style="text-align:right;font-weight:700;color:#ff3b30;">-${fmt(toplamMaliyet)} ₺</td></tr>
        <tr style="border-top:10px solid transparent;"><td>📈 Aylık Maaş Artışı</td><td style="text-align:right;color:#39ff14;">+${fmt(aylikFark)} ₺</td></tr>
        <tr><td>📊 Yıllık Ek Kazanç</td><td style="text-align:right;color:#39ff14;">+${fmt(yillikFark)} ₺</td></tr>
        <tr><td>🏆 10 Yıllık Net Kazanç</td><td style="text-align:right;font-weight:700;color:${onYilKazanc>=0?'#39ff14':'#ff3b30'};">${onYilKazanc>=0?'+':''}${fmt(onYilKazanc)} ₺</td></tr>
    `;

    // Chart
    if (chartInstance) chartInstance.destroy();
    const labels = [], withYL = [], withoutYL = [];
    let cumYL = -toplamMaliyet, cumNo = 0;
    for (let y = 0; y <= 10; y++) {
        labels.push(y === 0 ? 'Mezuniyet' : y + '. Yıl');
        withYL.push(Math.round(cumYL));
        withoutYL.push(Math.round(cumNo));
        cumYL += yillikFark;
    }
    const ctx = document.getElementById('roiChart').getContext('2d');
    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                { label: 'YL ile Kümülatif Fark', data: withYL, borderColor: '#0088ff', backgroundColor: 'rgba(0,136,255,.08)', fill: true, tension: 0.4, borderWidth: 3, pointRadius: 5 },
                { label: 'YL Yapmadan (Baz)', data: withoutYL, borderColor: 'rgba(255,255,255,.2)', borderDash: [5,5], borderWidth: 2, pointRadius: 3, fill: false }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: true, labels: { color: 'rgba(255,255,255,.6)' } },
                tooltip: { callbacks: { label: c => c.dataset.label + ': ' + fmt(c.parsed.y) + ' ₺' } }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,.4)' } },
                y: { grid: { color: 'rgba(255,255,255,.04)' }, ticks: { color: 'rgba(255,255,255,.4)', callback: v => fmt(v) + ' ₺' } }
            }
        }
    });

    const box = document.getElementById('adviceBox');
    if (roiAy < 36) {
        box.className = 'tool-info tool-info-green';
        box.innerHTML = `<span>✅</span><div><strong>Mantıklı yatırım!</strong> ${roiYil} yılda yatırımını geri alırsın. 10 yılda ${fmt(onYilKazanc)}₺ ek kazanç sağlarsın. YL yapmayı ciddi düşün.</div>`;
    } else if (roiAy < 72) {
        box.className = 'tool-info tool-info-blue';
        box.innerHTML = `<span>ℹ️</span><div><strong>Orta vadeli yatırım.</strong> Geri dönüş ${roiYil} yıl. Kariyer gelişimi, network ve uzmanlık gibi parasal olmayan faydaları da değerlendir.</div>`;
    } else {
        box.className = 'tool-info tool-info-orange';
        box.innerHTML = `<span>⚠️</span><div><strong>Uzun vadeli.</strong> Maaş artışı tek başına YL maliyetini kısa sürede karşılamıyor. Akademik kariyer veya kişisel gelişim motivasyonun varsa devam et, yoksa sektörel sertifikalar daha hızlı ROI sağlayabilir.</div>`;
    }

    document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

hesapla();
</script>

<?php require __DIR__ . '/../includes/seo_tools_foot.php'; ?>
