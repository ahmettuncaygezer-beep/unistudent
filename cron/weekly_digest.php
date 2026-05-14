<?php
declare(strict_types=1);
/**
 * Haftalık Dijest Cron — Cuma 18:00 ideal
 * Çalıştır: php cron/weekly_digest.php
 */
if (php_sapi_name() !== 'cli' && !in_array(($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1','::1'], true)) {
    http_response_code(403); exit('CLI only');
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/digest_generator.php';

$pdo = getDB();
$stmt = $pdo->query(
    "SELECT DISTINCT u.id
       FROM users u
       JOIN transactions t ON t.user_id = u.id
      WHERE t.transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
);
$count = 0;
foreach ($stmt->fetchAll() as $row) {
    $uid    = (int)$row['id'];
    $digest = generate_weekly_digest($pdo, $uid);
    $summary = $digest['ai_summary'] ?: implode(' ', $digest['tips']);
    if (!$summary) continue;

    $ins = $pdo->prepare(
        "INSERT INTO notifications (user_id, type, title, message)
         VALUES (?, 'digest', 'Haftalık Finans Özeti 📊', ?)"
    );
    $ins->execute([$uid, substr($summary, 0, 2000)]);
    $count++;
}
echo "Dijest oluşturuldu: $count kullanıcı\n";
