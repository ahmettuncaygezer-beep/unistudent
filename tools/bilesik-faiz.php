<?php
$__title    = 'Bileşik Faiz Hesaplayıcı';
$__desc     = 'Aylık düzenli yatırım ile bileşik faiz hesaplama. Birikiminiz yıllar sonra ne kadar olacak? Grafik ile büyümeyi takip edin.';
$__keywords = 'bileşik faiz hesaplama, birikim hesaplayıcı, yatırım hesaplama, aylık birikim, faiz hesabı, öğrenci yatırım';
$__canonical = rtrim(getenv('APP_URL') ?: 'http://localhost/unistudent', '/') . '/tools/bilesik-faiz.php';
$__schema = json_encode([
    "@context" => "https://schema.org",
    "@graph" => [
        [
            "@type" => "WebApplication",
            "name" => "Bileşik Faiz Hesaplayıcı",
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
                    "name" => "Bileşik faiz nedir?",
                    "acceptedAnswer" => [
                        "@type" => "Answer",
                        "text" => "Bileşik faiz, anaparanın yanı sıra daha önce kazanılmış faiz üzerinden de faiz hesaplanmasıdır. Bu sayede birikim üstel olarak büyür."
                    ]
                ],
                [
                    "@type" => "Question",
                    "name" => "Aylık 500 TL biriktirirsem 10 yılda ne kadar olur?",
                    "acceptedAnswer" => [
                        "@type" => "Answer",
                        "text" => "Yıllık %15 getiri ile aylık 500 TL biriktirirseniz, 10 yıl sonunda yaklaşık 138.000 TL birikiminiz olur. Bunun 60.000 TL'si kendi yatırdığınız, 78.000 TL'si ise faiz getirisidir."
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
    <span class="current">Bileşik Faiz Hesaplayıcı</span>
</div>

<!-- Hero -->
<div class="tool-hero">
    <div class="tool-badge">📈 Yatırım Aracı</div>
    <h1 class="tool-title">Bileşik Faiz <span class="tg">Hesaplayıcı</span></h1>
    <p class="tool-subtitle">Aylık düzenli yatırımınla yıllar sonra ne kadar biriktireceğini gör. Küçük adımlar, büyük sonuçlar.</p>
</div>

<!-- Calculator Card -->
<div class="tool-card">
    <div class="tool-card-title"><span class="icon">⚙️</span> Parametreler</div>

    <!-- Başlangıç Tutarı -->
    <div class="tool-slider-group">
        <div class="tool-slider-header">
            <span class="tool-slider-label">Başlangıç Tutarı</span>
            <span class="tool-slider-value" id="initialDisplay">0 ₺</span>
        </div>
        <input type="range" class="tool-slider" id="initialAmount" min="0" max="100000" step="1000" value="0">
    </div>

    <!-- Aylık Yatırım -->
    <div class="tool-slider-group">
        <div class="tool-slider-header">
            <span class="tool-slider-label">Aylık Yatırım</span>
            <span class="tool-slider-value" id="monthlyDisplay">500 ₺</span>
        </div>
        <input type="range" class="tool-slider" id="monthlyAmount" min="100" max="10000" step="100" value="500">
    </div>

    <!-- Yıllık Getiri -->
    <div class="tool-slider-group">
        <div class="tool-slider-header">
            <span class="tool-slider-label">Yıllık Getiri Oranı</span>
            <span class="tool-slider-value" id="rateDisplay">%15</span>
        </div>
        <input type="range" class="tool-slider" id="annualRate" min="1" max="50" step="0.5" value="15">
    </div>

    <!-- Süre -->
    <div class="tool-slider-group">
        <div class="tool-slider-header">
            <span class="tool-slider-label">Yatırım Süresi</span>
            <span class="tool-slider-value" id="yearsDisplay">10 Yıl</span>
        </div>
        <input type="range" class="tool-slider" id="years" min="1" max="30" step="1" value="10">
    </div>

    <button class="tool-btn tool-btn-primary tool-btn-block" onclick="calculate()" style="margin-top:12px;">
        📊 Hesapla
    </button>
</div>

<!-- Results (hidden by default) -->
<div id="resultsSection" style="display:none;">

    <!-- Main Result -->
    <div class="tool-result" id="mainResult">
        <div class="tool-result-label">Toplam Birikim</div>
        <div class="tool-result-value" id="totalDisplay">0 ₺</div>
        <div class="tool-result-sub" id="totalSub"></div>
    </div>

    <!-- Stats Grid -->
    <div class="tool-stats-grid">
        <div class="tool-stat">
            <div class="tool-stat-value" id="statInvested">0 ₺</div>
            <div class="tool-stat-label">Yatırdığın</div>
        </div>
        <div class="tool-stat">
            <div class="tool-stat-value clr-green" id="statProfit">0 ₺</div>
            <div class="tool-stat-label">Faiz Getirisi</div>
        </div>
        <div class="tool-stat">
            <div class="tool-stat-value clr-cyan" id="statMultiplier">0x</div>
            <div class="tool-stat-label">Çarpan</div>
        </div>
        <div class="tool-stat">
            <div class="tool-stat-value clr-purple" id="statMonths">0</div>
            <div class="tool-stat-label">Toplam Ay</div>
        </div>
    </div>

    <!-- Chart -->
    <div class="tool-card">
        <div class="tool-card-title"><span class="icon">📊</span> Birikim Grafiği</div>
        <div class="tool-chart-wrap">
            <canvas id="compoundChart"></canvas>
        </div>
    </div>

    <!-- Year-by-Year Table -->
    <div class="tool-card">
        <div class="tool-card-title"><span class="icon">📋</span> Yıl Bazlı Detay</div>
        <div style="overflow-x:auto;">
            <table class="tool-table" id="yearTable">
                <thead>
                    <tr>
                        <th>Yıl</th>
                        <th>Yatırılan</th>
                        <th>Birikim</th>
                        <th>Faiz Getirisi</th>
                        <th>Getiri %</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- FAQ Section -->
<div class="tool-card" style="margin-top:32px;">
    <h2 style="margin-top:0; font-size:1.2rem;">❓ Sıkça Sorulan Sorular</h2>

    <div style="margin-top:20px;">
        <h3 style="font-size:1rem; margin:0 0 8px; color:#00d4ff;">Bileşik faiz nedir?</h3>
        <p style="color:rgba(255,255,255,.6); line-height:1.7; margin:0 0 20px;">
            Bileşik faiz, anaparanın yanı sıra daha önce kazanılmış faiz üzerinden de faiz hesaplanmasıdır. 
            Bu sayede birikim üstel olarak büyür. Albert Einstein'ın "dünyanın sekizinci harikası" dediği güç budur.
        </p>

        <h3 style="font-size:1rem; margin:0 0 8px; color:#00d4ff;">Aylık 500 TL biriktirirsem 10 yılda ne kadar olur?</h3>
        <p style="color:rgba(255,255,255,.6); line-height:1.7; margin:0 0 20px;">
            Yıllık %15 getiri ile aylık 500 TL biriktirirseniz, 10 yıl sonunda yaklaşık 138.000 TL birikiminiz olur. 
            Bunun 60.000 TL'si kendi yatırdığınız, 78.000 TL'si ise faiz getirisidir.
        </p>

        <h3 style="font-size:1rem; margin:0 0 8px; color:#00d4ff;">En iyi birikim yöntemi nedir?</h3>
        <p style="color:rgba(255,255,255,.6); line-height:1.7; margin:0;">
            Düzenli ve erken başlamak en önemli faktördür. Küçük miktarlarla bile olsa düzenli yatırım yapmak, 
            yıllar içinde bileşik faizin etkisiyle büyük birikimler oluşturur. Öğrenciyken bile aylık 200-500 TL 
            ayırmak, mezuniyette ciddi bir birikim sağlar.
        </p>
    </div>
</div>

<script>
const fmt = n => parseFloat(n).toLocaleString('tr-TR', {maximumFractionDigits: 0});
let chartInstance = null;

// Live slider updates
document.querySelectorAll('.tool-slider').forEach(s => {
    s.addEventListener('input', updateDisplays);
});

function updateDisplays() {
    document.getElementById('initialDisplay').textContent = fmt(document.getElementById('initialAmount').value) + ' ₺';
    document.getElementById('monthlyDisplay').textContent = fmt(document.getElementById('monthlyAmount').value) + ' ₺';
    document.getElementById('rateDisplay').textContent = '%' + document.getElementById('annualRate').value;
    document.getElementById('yearsDisplay').textContent = document.getElementById('years').value + ' Yıl';
}
updateDisplays();

function calculate() {
    const P = parseFloat(document.getElementById('initialAmount').value);
    const M = parseFloat(document.getElementById('monthlyAmount').value);
    const r = parseFloat(document.getElementById('annualRate').value) / 100;
    const n = parseInt(document.getElementById('years').value);
    const monthlyRate = r / 12;
    const totalMonths = n * 12;

    // Calculate year-by-year
    const yearlyData = [];
    let balance = P;

    for (let year = 1; year <= n; year++) {
        for (let m = 0; m < 12; m++) {
            balance = balance * (1 + monthlyRate) + M;
        }
        const invested = P + M * 12 * year;
        const profit = balance - invested;
        yearlyData.push({
            year,
            invested,
            balance: Math.round(balance),
            profit: Math.round(profit),
            pct: invested > 0 ? ((profit / invested) * 100).toFixed(1) : '0'
        });
    }

    const totalInvested = P + M * totalMonths;
    const totalBalance = Math.round(balance);
    const totalProfit = totalBalance - totalInvested;
    const multiplier = totalInvested > 0 ? (totalBalance / totalInvested).toFixed(1) : '0';

    // Show results
    document.getElementById('resultsSection').style.display = 'block';
    document.getElementById('totalDisplay').textContent = fmt(totalBalance) + ' ₺';
    document.getElementById('totalSub').textContent = `${n} yıl boyunca, aylık ${fmt(M)} ₺ yatırım ile`;
    document.getElementById('statInvested').textContent = fmt(totalInvested) + ' ₺';
    document.getElementById('statProfit').textContent = '+' + fmt(totalProfit) + ' ₺';
    document.getElementById('statMultiplier').textContent = multiplier + 'x';
    document.getElementById('statMonths').textContent = totalMonths;

    // Table
    const tbody = document.querySelector('#yearTable tbody');
    tbody.innerHTML = yearlyData.map(d => `
        <tr>
            <td style="font-weight:700;">${d.year}. Yıl</td>
            <td>${fmt(d.invested)} ₺</td>
            <td style="font-weight:700;">${fmt(d.balance)} ₺</td>
            <td class="clr-green">+${fmt(d.profit)} ₺</td>
            <td class="clr-cyan">%${d.pct}</td>
        </tr>
    `).join('');

    // Chart
    if (chartInstance) chartInstance.destroy();
    const ctx = document.getElementById('compoundChart').getContext('2d');
    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: yearlyData.map(d => d.year + '. Yıl'),
            datasets: [
                {
                    label: 'Toplam Birikim',
                    data: yearlyData.map(d => d.balance),
                    borderColor: '#0088ff',
                    backgroundColor: 'rgba(0, 136, 255, 0.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    borderWidth: 3
                },
                {
                    label: 'Yatırılan Tutar',
                    data: yearlyData.map(d => d.invested),
                    borderColor: 'rgba(255,255,255,0.25)',
                    backgroundColor: 'rgba(255,255,255,0.03)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    borderWidth: 2,
                    borderDash: [5, 5]
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: { color: 'rgba(255,255,255,0.6)', font: { size: 12 } }
                },
                tooltip: {
                    backgroundColor: 'rgba(12,18,40,0.95)',
                    borderColor: 'rgba(0,136,255,0.3)',
                    borderWidth: 1,
                    titleColor: '#fff',
                    bodyColor: 'rgba(255,255,255,0.8)',
                    callbacks: {
                        label: ctx => ctx.dataset.label + ': ' + fmt(ctx.parsed.y) + ' ₺'
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.4)' } },
                y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(255,255,255,0.4)', callback: v => fmt(v) + ' ₺' } }
            }
        }
    });

    // Scroll to results
    document.getElementById('mainResult').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Auto-calculate on load
calculate();
</script>

<?php require __DIR__ . '/../includes/seo_tools_foot.php'; ?>
