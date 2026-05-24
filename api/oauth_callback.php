<?php
/**
 * ÜniBütçe — OAuth Callback
 *
 * OAuth entegrasyonu henüz uygulanmamıştır.
 * Bu endpoint devre dışı bırakılmıştır.
 *
 * Gereksinimler (gerçek entegrasyon için):
 *   - Google API PHP Client (composer require google/apiclient)
 *   - Facebook Graph SDK (composer require facebook/graph-sdk)
 *   - PKCE + state parametresi ile CSRF koruması
 *   - .env dosyasında CLIENT_ID ve CLIENT_SECRET
 */
declare(strict_types=1);

http_response_code(501);

// Güvenli redirect — kullanıcıyı ana sayfaya gönder
$location = '../index.php';
if (!headers_sent()) {
    header('Location: ' . $location);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head><meta charset="UTF-8"><title>OAuth Kullanılamıyor</title></head>
<body style="font-family:sans-serif;text-align:center;padding:60px;background:#050510;color:#fff;">
    <h2>🚧 OAuth Henüz Aktif Değil</h2>
    <p style="opacity:.7">Sosyal giriş entegrasyonu geliştirme aşamasındadır.</p>
    <p><a href="../index.php" style="color:#0088ff;">Ana Sayfaya Dön</a></p>
</body>
</html>
