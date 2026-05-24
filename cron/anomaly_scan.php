<?php
declare(strict_types=1);
/**
 * Anomali Tarama Cron — Günlük çalıştır
 * Çalıştır: php cron/anomaly_scan.php
 * Windows Task Scheduler veya XAMPP cron ile otomatikleştir.
 */
if (php_sapi_name() !== 'cli' && !in_array(($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1','::1'], true)) {
    http_response_code(403); exit('CLI only');
}

require_once __DIR__ . '/../config.php';

$pdo = getDB();
$start = microtime(true);

// Son 30 günde aktif olan kullanıcıları tara
$users = $pdo->query(
    "SELECT DISTINCT user_id FROM transactions
     WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
)->fetchAll(PDO::FETCH_COLUMN);

$totalFlagged = 0;
$totalScanned = 0;

foreach ($users as $userId) {
    $userId = (int)$userId;

    // 97 günlük veri çek
    $rows = $pdo->prepare(
        "SELECT category, amount, transaction_date, id
         FROM transactions
         WHERE user_id = ? AND type = 'expense'
           AND transaction_date >= DATE_SUB(NOW(), INTERVAL 97 DAY)
         ORDER BY transaction_date DESC"
    );
    $rows->execute([$userId]);
    $all = $rows->fetchAll();
    $totalScanned += count($all);

    $byCat = [];
    foreach ($all as $r) {
        $byCat[$r['category']][] = $r;
    }

    $cutoff = strtotime('-7 days');
    $ins = $pdo->prepare(
        "INSERT IGNORE INTO user_anomalies (user_id, transaction_id, category, amount, zscore, reason)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    foreach ($byCat as $cat => $items) {
        $hist   = array_filter($items, fn($x) => strtotime($x['transaction_date']) < $cutoff);
        $recent = array_filter($items, fn($x) => strtotime($x['transaction_date']) >= $cutoff);
        if (count($hist) < 5) continue;

        $amounts = array_map(fn($x) => (float)$x['amount'], $hist);
        $mean    = array_sum($amounts) / count($amounts);
        $var     = array_sum(array_map(fn($a) => ($a - $mean) ** 2, $amounts)) / count($amounts);
        $std     = $var > 0 ? sqrt($var) : 1;

        foreach ($recent as $r) {
            $z = ($r['amount'] - $mean) / $std;
            if ($z > 2.2) {
                $reason = sprintf(
                    '"%s" kategorisinde ortalama %s₺ iken %s₺ harcandı (%.1f× normal).',
                    $cat, number_format($mean, 0), number_format($r['amount'], 0), $r['amount'] / max($mean, 1)
                );
                $ins->execute([$userId, $r['id'], $cat, $r['amount'], round($z, 2), $reason]);
                $totalFlagged++;

                // Kullanıcıya bildirim gönder
                try {
                    $notif = $pdo->prepare(
                        "INSERT IGNORE INTO notifications (user_id, type, title, message)
                         VALUES (?, 'warning', 'Harcama Anomalisi Tespit Edildi ⚠️', ?)"
                    );
                    $notif->execute([$userId, "\"$cat\" kategorisinde olağandışı harcama: " . number_format($r['amount'], 0) . "₺"]);
                } catch (\Throwable $e) { /* tablo yoksa sessizce geç */ }
            }
        }
    }
}

$elapsed = round(microtime(true) - $start, 2);
echo "Anomali taraması tamamlandı.\n";
echo "Taranan işlem: $totalScanned\n";
echo "Tespit edilen anomali: $totalFlagged\n";
echo "Süre: {$elapsed}s\n";
echo "Tarih: " . date('Y-m-d H:i:s') . "\n";
