<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
$__title = 'KVKK Aydınlatma Metni';
$__desc  = 'KVKK 10. madde uyarınca ÜniBütçe aydınlatma metni — kişisel veri işleme şartları.';
require __DIR__ . '/includes/public_head.php';
?>
<h1>🛡️ KVKK Aydınlatma Metni</h1>
<p class="ub-lead">6698 sayılı Kişisel Verilerin Korunması Kanunu ("KVKK") kapsamında veri sorumlusu sıfatıyla aşağıdaki aydınlatma metnini kamuoyuna sunuyoruz.</p>

<div class="ub-card">
    <h2 style="margin-top:0;">1. Veri Sorumlusu</h2>
    <p>ÜniBütçe ("Platform") — iletişim: <a href="mailto:kvkk@unibutce.com" style="color:#0088ff;">kvkk@unibutce.com</a></p>

    <h2>2. İşlenen Kişisel Veri Kategorileri</h2>
    <ul style="line-height:1.9;">
        <li><b>Kimlik:</b> ad-soyad, üniversite adı</li>
        <li><b>İletişim:</b> e-posta adresi</li>
        <li><b>Finansal:</b> gelir, gider, tasarruf hedefleri (kullanıcı tarafından girilen)</li>
        <li><b>İşlem Güvenliği:</b> şifre özet (hash), IP adresi, tarayıcı bilgisi, oturum çerezleri</li>
        <li><b>Lokasyon (opsiyonel):</b> yaşanılan şehir (benchmark için)</li>
    </ul>

    <h2>3. İşleme Amaçları</h2>
    <ul style="line-height:1.9;">
        <li>Bütçe takibi ve finansal planlama hizmetinin sunulması</li>
        <li>Hesap oluşturma ve kimlik doğrulama</li>
        <li>Anonimleştirilmiş benchmark (arkadaş ligi) için istatistiki analiz</li>
        <li>Dolandırıcılık tespiti ve güvenlik önlemleri</li>
        <li>Yasal yükümlülüklerin yerine getirilmesi</li>
    </ul>

    <h2>4. Aktarım</h2>
    <p>Kişisel verilerin, açık rızan olmaksızın üçüncü kişilerle paylaşılmaz. Yasal talep veya yetkili kurumlardan resmi talep halinde mevzuat çerçevesinde aktarım yapılabilir. Verilerin saklanması için kullanılan altyapı sağlayıcısı (hosting) AB/Türkiye içindedir.</p>

    <h2>5. Saklama Süresi</h2>
    <p>Hesabın aktif olduğu sürece işlenir. Hesap silme talebinde tüm kişisel veriler en geç 30 gün içinde geri dönüşümsüz silinir. Yasal saklama zorunluluğu olan veriler (örn. loglar) ilgili mevzuat süresince tutulur.</p>

    <h2>6. KVKK 11. Madde Hakları</h2>
    <ul style="line-height:1.9;">
        <li>İşlenen verilerini öğrenme</li>
        <li>Yurt içi/dışı aktarıldığı üçüncü kişileri bilme</li>
        <li>Eksik/yanlış işlenmişse düzeltilmesini isteme</li>
        <li>Silinmesini / yok edilmesini isteme</li>
        <li>Otomatik sistemler aracılığıyla analiz edilmesine itiraz etme</li>
        <li>Zarara uğraman halinde tazminat talep etme</li>
    </ul>
    <p>Bu hakları <b>Gizlilik & Verilerim</b> sayfasından tek tıkla kullanabilir, hesabını ve tüm veriyi silebilir, tüm verini JSON/CSV olarak indirebilirsin.</p>

    <h2>7. Çerezler</h2>
    <p>Platform zorunlu (oturum), tercih (tema, dil) ve opsiyonel analitik çerezler kullanır. İlk ziyarette çerez tercihini kaydettiğin banner üzerinden pazarlama/analitik çerezleri reddedebilirsin.</p>

    <p style="opacity:.6; font-size:.85rem; margin-top:24px;">Son güncelleme: <?= date('d.m.Y') ?></p>
</div>

<?php require __DIR__ . '/includes/public_foot.php'; ?>
