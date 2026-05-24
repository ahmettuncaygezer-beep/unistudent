<?php
declare(strict_types=1);
/**
 * Rate Limiter Temizleme Cron — Günlük veya haftalık çalıştır
 * Çalıştır: php cron/cleanup_rate_limits.php
 *
 * sys_get_temp_dir()/unibutce_rl/ dizinindeki süresi dolmuş dosyaları siler.
 */
if (php_sapi_name() !== 'cli' && !in_array(($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1','::1'], true)) {
    http_response_code(403); exit('CLI only');
}

$dir = sys_get_temp_dir() . '/unibutce_rl';
if (!is_dir($dir)) {
    echo "Rate limiter dizini bulunamadı: $dir\n";
    exit(0);
}

$maxAge = 7200; // 2 saat — en uzun rate limit window
$deleted = 0;
$total   = 0;

foreach (new DirectoryIterator($dir) as $file) {
    if ($file->isDot() || $file->getExtension() !== 'json') continue;
    $total++;
    if ((time() - $file->getMTime()) > $maxAge) {
        @unlink($file->getPathname());
        $deleted++;
    }
}

echo "Rate limiter temizliği tamamlandı.\n";
echo "Toplam dosya: $total | Silinen: $deleted\n";
echo "Tarih: " . date('Y-m-d H:i:s') . "\n";
