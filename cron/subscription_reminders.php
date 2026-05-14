<?php
declare(strict_types=1);
/**
 * ÜniBütçe — Abonelik Yenileme Hatırlatmaları
 *
 * Günde bir çalıştırın (örn: Windows Görev Zamanlayıcı / cron):
 *   php c:/xampp/htdocs/unistudent/cron/subscription_reminders.php
 *
 * 3 gün içinde yenilenecek aktif abonelikler için bildirim ekler
 * (aynı gün aynı abonelik için tekrar eklemez).
 */

// Sadece CLI veya yetkili localhost istekleri
if (PHP_SAPI !== 'cli' && ($_SERVER['REMOTE_ADDR'] ?? '') !== '127.0.0.1') {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/../config.php';

$pdo = getDB();
ensureAllTables();

$stmt = $pdo->prepare("
    SELECT s.id, s.user_id, s.name, s.amount, s.next_billing
    FROM subscriptions s
    WHERE s.is_active = 1
      AND s.next_billing IS NOT NULL
      AND s.next_billing BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
");
$stmt->execute();
$due = $stmt->fetchAll();

$inserted = 0;
$checkStmt = $pdo->prepare("
    SELECT 1 FROM notifications
    WHERE user_id = ? AND title = ? AND DATE(created_at) = CURDATE()
    LIMIT 1
");
$insertStmt = $pdo->prepare("
    INSERT INTO notifications (user_id, type, title, message)
    VALUES (?, 'warning', ?, ?)
");

foreach ($due as $sub) {
    $title = "⏰ {$sub['name']} yakında yenileniyor";
    $msg   = "Aboneliğin {$sub['next_billing']} tarihinde "
           . number_format((float)$sub['amount'], 2, ',', '.') . " ₺ olarak yenilenecek.";

    $checkStmt->execute([$sub['user_id'], $title]);
    if ($checkStmt->fetchColumn()) continue;

    $insertStmt->execute([$sub['user_id'], $title, $msg]);
    $inserted++;
}

// Geçmiş next_billing'leri ileri sar (monthly/yearly)
$pdo->exec("
    UPDATE subscriptions
    SET next_billing = CASE
        WHEN billing_cycle = 'yearly'
            THEN DATE_ADD(next_billing, INTERVAL 1 YEAR)
        ELSE DATE_ADD(next_billing, INTERVAL 1 MONTH)
    END
    WHERE is_active = 1 AND next_billing < CURDATE()
");

echo "Reminders created: $inserted" . PHP_EOL;
