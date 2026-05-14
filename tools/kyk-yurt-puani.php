<?php
$__title    = 'KYK Yurt Puanı Simülatörü 2026';
$__desc     = 'KYK yurt başvuru puanını tahmin edin. Aile geliri, kardeş sayısı, engel durumu ve diğer kriterlere göre simülasyon.';
$__keywords = 'KYK yurt puanı hesaplama, KYK yurt başvuru, KYK puan simülatörü, yurt puanı 2026, KYK yurt sıralama';
$__canonical = rtrim(getenv('APP_URL') ?: 'http://localhost/unistudent', '/') . '/tools/kyk-yurt-puani.php';
$__schema = json_encode([
    "@context" => "https://schema.org",
    "@type" => "WebApplication",
    "name" => "KYK Yurt Puanı Simülatörü",
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
    <span class="current">KYK Yurt Puanı</span>
</div>

<div class="tool-hero">
    <div class="tool-badge">🏠 KYK Aracı</div>
    <h1 class="tool-title">KYK Yurt Puanı <span class="tg">Simülatörü</span></h1>
    <p class="tool-subtitle">Aile bilgilerini gir, tahmini KYK yurt başvuru puanını ve yerleşme şansını öğren.</p>
</div>

<div class="tool-card">
    <div class="tool-card-title"><span class="icon">📋</span> Bilgilerini Gir</div>

    <div class="tool-form-grid">
        <div class="tool-field">
            <label>Yıllık Aile Geliri (₺)</label>
            <select class="tool-select" id="aileGelir">
                <option value="1">0 - 100.000 ₺</option>
                <option value="2" selected>100.001 - 200.000 ₺</option>
                <option value="3">200.001 - 350.000 ₺</option>
                <option value="4">350.001 - 500.000 ₺</option>
                <option value="5">500.001 - 750.000 ₺</option>
                <option value="6">750.001 ₺ ve üzeri</option>
            </select>
        </div>
        <div class="tool-field">
            <label>Öğrenim Türü</label>
            <select class="tool-select" id="ogrenimTuru">
                <option value="lisans" selected>Lisans</option>
                <option value="onlisans">Ön Lisans</option>
                <option value="ylisans">Yüksek Lisans</option>
                <option value="doktora">Doktora</option>
            </select>
        </div>
        <div class="tool-field">
            <label>Kardeş Sayısı (Senden hariç)</label>
            <input type="number" class="tool-input" id="kardesSayi" value="1" min="0" max="15">
        </div>
        <div class="tool-field">
            <label>Okuyan Kardeş Sayısı</label>
            <input type="number" class="tool-input" id="okuyanKardes" value="0" min="0" max="10">
        </div>
    </div>

    <div style="margin-top:20px;">
        <label style="font-size:0.85rem; font-weight:600; color:rgba(255,255,255,.7); display:block; margin-bottom:12px;">Ek Durumlar</label>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:10px;">
            <label style="display:flex; align-items:center; gap:10px; padding:12px 16px; border-radius:12px; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); cursor:pointer; font-size:0.9rem; color:rgba(255,255,255,.8);">
                <input type="checkbox" id="sehit" style="accent-color:#0088ff;"> 🎖️ Şehit/Gazi çocuğu
            </label>
            <label style="display:flex; align-items:center; gap:10px; padding:12px 16px; border-radius:12px; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); cursor:pointer; font-size:0.9rem; color:rgba(255,255,255,.8);">
                <input type="checkbox" id="engelli" style="accent-color:#0088ff;"> ♿ Engelli (rapor mevcut)
            </label>
            <label style="display:flex; align-items:center; gap:10px; padding:12px 16px; border-radius:12px; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); cursor:pointer; font-size:0.9rem; color:rgba(255,255,255,.8);">
                <input type="checkbox" id="yetim" style="accent-color:#0088ff;"> 💔 Anne veya baba vefat
            </label>
            <label style="display:flex; align-items:center; gap:10px; padding:12px 16px; border-radius:12px; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); cursor:pointer; font-size:0.9rem; color:rgba(255,255,255,.8);">
                <input type="checkbox" id="koruma" style="accent-color:#0088ff;"> 🏛️ Devlet koruması altında
            </label>
            <label style="display:flex; align-items:center; gap:10px; padding:12px 16px; border-radius:12px; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); cursor:pointer; font-size:0.9rem; color:rgba(255,255,255,.8);">
                <input type="checkbox" id="birincisınıf" checked style="accent-color:#0088ff;"> 🆕 İlk kez yurt başvurusu
            </label>
            <label style="display:flex; align-items:center; gap:10px; padding:12px 16px; border-radius:12px; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); cursor:pointer; font-size:0.9rem; color:rgba(255,255,255,.8);">
                <input type="checkbox" id="basariBelge" style="accent-color:#0088ff;"> 🏆 Başarı / Teşvik belgesi
            </label>
        </div>
    </div>

    <button class="tool-btn tool-btn-primary tool-btn-block" onclick="hesapla()" style="margin-top:24px;">
        🎯 Puanımı Hesapla
    </button>
</div>

<!-- Results -->
<div id="results" style="display:none;">
    <div class="tool-result">
        <div class="tool-result-label">Tahmini KYK Yurt Puanı</div>
        <div class="tool-result-value" id="puanDisplay">0</div>
        <div class="tool-result-sub" id="puanSub"></div>
    </div>

    <div class="tool-stats-grid">
        <div class="tool-stat">
            <div class="tool-stat-value" id="gelirPuan">0</div>
            <div class="tool-stat-label">Gelir Puanı</div>
        </div>
        <div class="tool-stat">
            <div class="tool-stat-value clr-cyan" id="kardesPuan">0</div>
            <div class="tool-stat-label">Kardeş Puanı</div>
        </div>
        <div class="tool-stat">
            <div class="tool-stat-value clr-green" id="ekPuan">0</div>
            <div class="tool-stat-label">Ek Durum Puanı</div>
        </div>
        <div class="tool-stat">
            <div class="tool-stat-value" id="sansYuzde">%0</div>
            <div class="tool-stat-label">Yerleşme Şansı</div>
        </div>
    </div>

    <!-- Puan Breakdown -->
    <div class="tool-card">
        <div class="tool-card-title"><span class="icon">📊</span> Puan Detayı</div>
        <table class="tool-table" id="puanTable">
            <thead><tr><th>Kriter</th><th>Durum</th><th style="text-align:right;">Puan</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>

    <div id="adviceBox" class="tool-info tool-info-blue"></div>
</div>

<!-- FAQ -->
<div class="tool-card" style="margin-top:32px;">
    <h2 style="margin-top:0; font-size:1.2rem;">❓ Sıkça Sorulan Sorular</h2>
    <div style="margin-top:20px;">
        <h3 style="font-size:1rem; margin:0 0 8px; color:#00d4ff;">KYK yurt puanı nasıl hesaplanır?</h3>
        <p style="color:rgba(255,255,255,.6); line-height:1.7; margin:0 0 20px;">
            KYK yurt puanı ağırlıklı olarak aile gelir durumu, kardeş sayısı, okuyan kardeş sayısı ve özel durumlardan (şehit/gazi çocuğu, engel, yetimlik) oluşur. 
            Düşük gelirli ve özel durumu olan öğrenciler öncelikli yerleştirilir.
        </p>
        <h3 style="font-size:1rem; margin:0 0 8px; color:#00d4ff;">Kaç puanla yurda yerleşilir?</h3>
        <p style="color:rgba(255,255,255,.6); line-height:1.7; margin:0;">
            Yurda yerleşme puanı şehir ve yıla göre değişir. Büyük şehirlerde (İstanbul, Ankara, İzmir) genellikle 
            60+ puan gerekirken, diğer şehirlerde 40-50 puan yeterli olabilir. Şehit/gazi çocukları ve devlet koruması 
            altındakiler kontenjan ayrılarak doğrudan yerleştirilir.
        </p>
    </div>
</div>

<script>
function hesapla() {
    const gelirKat = parseInt(document.getElementById('aileGelir').value);
    const ogTuru = document.getElementById('ogrenimTuru').value;
    const kardes = parseInt(document.getElementById('kardesSayi').value) || 0;
    const okuyan = parseInt(document.getElementById('okuyanKardes').value) || 0;

    const sehit = document.getElementById('sehit').checked;
    const engelli = document.getElementById('engelli').checked;
    const yetim = document.getElementById('yetim').checked;
    const koruma = document.getElementById('koruma').checked;
    const birinci = document.getElementById('birincisınıf').checked;
    const basari = document.getElementById('basariBelge').checked;

    // Gelir puanı (düşük gelir = yüksek puan)
    const gelirPuanlar = { 1: 40, 2: 32, 3: 24, 4: 16, 5: 8, 6: 4 };
    const gelirP = gelirPuanlar[gelirKat] || 20;

    // Kardeş puanı
    const kardesP = Math.min(15, kardes * 3);
    const okuyanP = Math.min(10, okuyan * 5);

    // Ek durum puanları
    let ekP = 0;
    const rows = [];

    rows.push({ kriter: '💰 Aile Geliri', durum: `Kategori ${gelirKat}`, puan: gelirP });
    rows.push({ kriter: '👨‍👩‍👧‍👦 Kardeş Sayısı', durum: `${kardes} kardeş`, puan: kardesP });
    rows.push({ kriter: '🎓 Okuyan Kardeş', durum: `${okuyan} kişi`, puan: okuyanP });

    if (sehit) { ekP += 20; rows.push({ kriter: '🎖️ Şehit/Gazi Çocuğu', durum: 'Evet', puan: 20 }); }
    if (engelli) { ekP += 15; rows.push({ kriter: '♿ Engelli', durum: 'Evet', puan: 15 }); }
    if (yetim) { ekP += 12; rows.push({ kriter: '💔 Anne/Baba Vefat', durum: 'Evet', puan: 12 }); }
    if (koruma) { ekP += 25; rows.push({ kriter: '🏛️ Devlet Koruması', durum: 'Evet', puan: 25 }); }
    if (birinci) { ekP += 5; rows.push({ kriter: '🆕 İlk Başvuru', durum: 'Evet', puan: 5 }); }
    if (basari) { ekP += 3; rows.push({ kriter: '🏆 Başarı Belgesi', durum: 'Evet', puan: 3 }); }

    // Öğrenim türü bonusu
    const ogBonus = { lisans: 0, onlisans: 2, ylisans: -5, doktora: -3 };
    const ogB = ogBonus[ogTuru] || 0;
    if (ogB !== 0) rows.push({ kriter: '📚 Öğrenim Türü', durum: ogTuru, puan: ogB });

    const toplamPuan = Math.min(100, gelirP + kardesP + okuyanP + ekP + ogB);

    // Şans tahmini
    let sans = 0;
    if (toplamPuan >= 80) sans = 95;
    else if (toplamPuan >= 65) sans = 80;
    else if (toplamPuan >= 50) sans = 55;
    else if (toplamPuan >= 35) sans = 30;
    else sans = 15;

    let seviye = '', seviyeColor = '';
    if (sans >= 80) { seviye = '🟢 Yüksek'; seviyeColor = '#39ff14'; }
    else if (sans >= 50) { seviye = '🟡 Orta'; seviyeColor = '#ffd700'; }
    else { seviye = '🔴 Düşük'; seviyeColor = '#ff3b30'; }

    // Display
    document.getElementById('results').style.display = 'block';
    document.getElementById('puanDisplay').textContent = toplamPuan;
    document.getElementById('puanSub').textContent = `${seviye} yerleşme şansı`;
    document.getElementById('puanDisplay').style.cssText = `-webkit-text-fill-color:${seviyeColor};`;
    document.getElementById('gelirPuan').textContent = gelirP;
    document.getElementById('kardesPuan').textContent = kardesP + okuyanP;
    document.getElementById('ekPuan').textContent = ekP;
    document.getElementById('sansYuzde').textContent = `%${sans}`;
    document.getElementById('sansYuzde').style.color = seviyeColor;

    // Tablo
    document.querySelector('#puanTable tbody').innerHTML = rows.map(r => `
        <tr>
            <td style="font-weight:600;">${r.kriter}</td>
            <td style="color:rgba(255,255,255,.6);">${r.durum}</td>
            <td style="text-align:right; font-weight:700; color:${r.puan > 0 ? '#39ff14' : '#ff3b30'};">${r.puan > 0 ? '+' : ''}${r.puan}</td>
        </tr>
    `).join('') + `
        <tr style="border-top:2px solid rgba(0,136,255,.3);">
            <td colspan="2" style="font-weight:800;">Toplam Puan</td>
            <td style="text-align:right; font-weight:800; font-size:1.1rem; color:#00d4ff;">${toplamPuan}</td>
        </tr>`;

    // Tavsiye
    const box = document.getElementById('adviceBox');
    if (sans >= 80) {
        box.className = 'tool-info tool-info-green';
        box.innerHTML = `<span>✅</span><div><strong>Yüksek şans!</strong> Puanın iyi durumda. Çoğu şehirde yurda yerleşebilirsin. Başvuru dönemini kaçırma!</div>`;
    } else if (sans >= 50) {
        box.className = 'tool-info tool-info-blue';
        box.innerHTML = `<span>ℹ️</span><div><strong>Orta şans.</strong> Büyük şehirlerde rekabet yüksek olabilir. Alternatif şehirleri de değerlendir. Ek belgelerini eksiksiz yükle.</div>`;
    } else {
        box.className = 'tool-info tool-info-orange';
        box.innerHTML = `<span>⚠️</span><div><strong>Düşük şans.</strong> KYK yurdu dışında özel yurt veya ev arkadaşı seçeneklerini de araştır. ÜniBütçe'nin şehir karşılaştırma aracı ile uygun fiyatlı bölgeleri keşfet.</div>`;
    }

    document.getElementById('results').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

hesapla();
</script>

<?php require __DIR__ . '/../includes/seo_tools_foot.php'; ?>
