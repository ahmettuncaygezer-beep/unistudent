<?php
$__title = 'Sistem Durumu';
$__desc  = 'ÜniBütçe servislerinin anlık sağlık durumu.';
require_once __DIR__ . '/includes/db.php';

// Quick health probes
$checks = [];
try {
    $pdo = getDB();
    $pdo->query("SELECT 1");
    $checks['Veritabanı (MySQL)'] = ['ok' => true, 'latency' => 0];
} catch (\Throwable $e) {
    $checks['Veritabanı (MySQL)'] = ['ok' => false, 'err' => 'Bağlantı hatası'];
}

$authPath = __DIR__ . '/api/auth_handler.php';
$checks['Kimlik Doğrulama API'] = ['ok' => is_readable($authPath)];
$checks['Blog CMS']             = ['ok' => is_dir(__DIR__ . '/blog/')];
$checks['Yazma İzinleri']       = ['ok' => is_writable(__DIR__ . '/data') || !is_dir(__DIR__ . '/data')];
$checks['Service Worker']       = ['ok' => is_readable(__DIR__ . '/sw.js')];
$checks['Anomali API']          = ['ok' => is_readable(__DIR__ . '/api/anomaly_detector.php')];
$checks['Kur Servisi']          = ['ok' => is_readable(__DIR__ . '/api/currency.php')];

$allOk = !in_array(false, array_column($checks, 'ok'), true);
require __DIR__ . '/includes/public_head.php';
?>
<h1>💚 Sistem Durumu</h1>
<p class="ub-lead">Tüm sistemlerimizin anlık sağlık durumu. Bu sayfa her erişimde canlı olarak test edilir.</p>

<div class="ub-card" style="text-align:center; font-size:1.2rem; padding:30px;">
    <?php if ($allOk): ?>
        <div style="font-size:3rem;">✅</div>
        <div style="color:#39ff14; font-weight:700;">Tüm sistemler normal çalışıyor</div>
    <?php else: ?>
        <div style="font-size:3rem;">⚠️</div>
        <div style="color:#ff9100; font-weight:700;">Bazı servislerde sorun tespit edildi</div>
    <?php endif; ?>
    <div style="margin-top:10px; font-size:.85rem; opacity:.6;">Son kontrol: <?= date('d M Y H:i') ?> (Istanbul)</div>
</div>

<h2>Servisler</h2>
<div class="ub-card">
    <?php foreach ($checks as $name => $result): ?>
        <div class="ub-status-row">
            <span><?= htmlspecialchars($name) ?></span>
            <?php if ($result['ok']): ?>
                <span class="ub-status-ok">✓ Çalışıyor</span>
            <?php else: ?>
                <span class="ub-status-bad">✗ <?= htmlspecialchars($result['err'] ?? 'Hata') ?></span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<h2>Performans Metrikleri</h2>
<div class="ub-card">
    <div class="ub-status-row"><span>PHP Sürümü</span><span><?= PHP_VERSION ?></span></div>
    <div class="ub-status-row"><span>Zaman Dilimi</span><span><?= date_default_timezone_get() ?></span></div>
    <div class="ub-status-row"><span>Sunucu Yükü</span><span><?= function_exists('sys_getloadavg') ? implode(', ', array_map(fn($l) => number_format($l, 2), sys_getloadavg() ?: [0])) : 'n/a' ?></span></div>
    <div class="ub-status-row"><span>Bellek Kullanımı</span><span><?= round(memory_get_usage() / 1024 / 1024, 1) ?> MB</span></div>
</div>

<?php require __DIR__ . '/includes/public_foot.php'; ?>
