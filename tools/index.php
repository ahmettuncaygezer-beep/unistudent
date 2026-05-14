<?php
$__title    = 'Ücretsiz Hesaplama Araçları';
$__desc     = 'Üniversite öğrencileri için ücretsiz hesaplama araçları: bileşik faiz, asgari ücret, KPSS puan tahmin, Erasmus hibe, KYK yurt puanı ve daha fazlası.';
$__keywords = 'hesaplama araçları, bileşik faiz hesaplama, asgari ücret net, KPSS puan hesaplama, Erasmus hibe, KYK yurt puanı, üniversite, öğrenci';
$__canonical = rtrim(getenv('APP_URL') ?: 'http://localhost/unistudent', '/') . '/tools/index.php';
$__schema = json_encode([
    "@context" => "https://schema.org",
    "@type" => "CollectionPage",
    "name" => "ÜniBütçe Hesaplama Araçları",
    "description" => $__desc,
    "url" => $__canonical
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$__allowDemoLogin = true; // Public tool page — allow demo auto-login
require __DIR__ . '/../includes/seo_tools_head.php';
?>

<!-- Breadcrumb -->
<div class="tool-breadcrumb">
    <a href="../index.php">Ana Sayfa</a>
    <span class="separator">›</span>
    <span class="current">Hesaplama Araçları</span>
</div>

<!-- Hero -->
<div class="tool-hero">
    <div class="tool-badge">🧮 Ücretsiz Araçlar</div>
    <h1 class="tool-title">Öğrenci <span class="tg">Hesaplama Araçları</span></h1>
    <p class="tool-subtitle">Bütçe planlamasından KPSS puan tahminine, Erasmus hibe hesabından bileşik faize kadar — tüm hesaplamalar bir tıkla, ücretsiz.</p>
</div>

<!-- Tools Grid -->
<div class="tools-grid">

    <a href="bilesik-faiz.php" class="tool-grid-card">
        <span class="tool-grid-icon">📈</span>
        <div class="tool-grid-name">Bileşik Faiz Hesaplayıcı</div>
        <div class="tool-grid-desc">Aylık yatırımını gir, yıllar sonra ne kadar biriktireceğini gör. Grafik ile büyümeyi takip et.</div>
        <span class="tool-grid-seo">🔥 Popüler</span>
    </a>

    <a href="asgari-ucret.php" class="tool-grid-card">
        <span class="tool-grid-icon">💰</span>
        <div class="tool-grid-name">Asgari Ücret Net Hesaplayıcı</div>
        <div class="tool-grid-desc">2026 brüt asgari ücretten SGK, vergi kesintileri sonrası ele geçen net maaşı hesapla.</div>
        <span class="tool-grid-seo">🔥 En Çok Aranan</span>
    </a>

    <a href="kpss-puan.php" class="tool-grid-card">
        <span class="tool-grid-icon">📝</span>
        <div class="tool-grid-name">KPSS Puan Tahmin</div>
        <div class="tool-grid-desc">Genel yetenek ve genel kültür net sayılarından tahmini KPSS puanını hesapla.</div>
        <span class="tool-grid-seo">🎓 Akademik</span>
    </a>

    <a href="erasmus-grant.php" class="tool-grid-card">
        <span class="tool-grid-icon">🌍</span>
        <div class="tool-grid-name">Erasmus Hibe Hesaplayıcı</div>
        <div class="tool-grid-desc">Ülke bazlı Erasmus hibe tutarlarını karşılaştır, yaşam maliyetine yetip yetmeyeceğini gör.</div>
        <span class="tool-grid-seo">✈️ Yurt Dışı</span>
    </a>

    <a href="kyk-yurt-puani.php" class="tool-grid-card">
        <span class="tool-grid-icon">🏠</span>
        <div class="tool-grid-name">KYK Yurt Puanı Simülatörü</div>
        <div class="tool-grid-desc">Aile geliri, kardeş sayısı, engel durumu ile tahmini KYK yurt başvuru puanını hesapla.</div>
        <span class="tool-grid-seo">🏢 KYK</span>
    </a>

    <a href="finansal-iq.php" class="tool-grid-card">
        <span class="tool-grid-icon">🧠</span>
        <div class="tool-grid-name">Finansal IQ Testi</div>
        <div class="tool-grid-desc">20 soruluk test ile finansal okuryazarlık seviyeni ölç. Sonucu arkadaşlarınla paylaş!</div>
        <span class="tool-grid-seo">🎮 Viral</span>
    </a>

    <a href="yuksek-lisans-roi.php" class="tool-grid-card">
        <span class="tool-grid-icon">🎓</span>
        <div class="tool-grid-name">Yüksek Lisans ROI</div>
        <div class="tool-grid-desc">Yüksek lisans yapmanın maaş artışı karşılığında kaç yılda geri döneceğini hesapla.</div>
        <span class="tool-grid-seo">📊 Kariyer</span>
    </a>

</div>

<!-- SEO Content -->
<div class="tool-card" style="margin-top:32px;">
    <h2 style="margin-top:0; font-size:1.3rem;">❓ Neden Bu Araçları Kullanmalısın?</h2>
    <p style="color:rgba(255,255,255,.6); line-height:1.8;">
        ÜniBütçe'nin ücretsiz hesaplama araçları, Türkiye'deki üniversite öğrencilerinin en sık ihtiyaç duyduğu 
        finansal hesaplamaları tek bir yerde toplar. Kayıt olmadan, reklamsız ve tamamen ücretsiz kullanabilirsin.
    </p>
    <p style="color:rgba(255,255,255,.6); line-height:1.8;">
        Bileşik faiz hesaplayıcı ile küçük birikimlerin yıllar sonra ne kadar büyüyeceğini gör. 
        Asgari ücret hesaplayıcı ile part-time işten eline ne kadar geçeceğini öğren. 
        KPSS puan tahmini ile sınav hedefini belirle. Erasmus hibe hesaplayıcı ile yurt dışı planını yap.
    </p>
</div>

<?php require __DIR__ . '/../includes/seo_tools_foot.php'; ?>
