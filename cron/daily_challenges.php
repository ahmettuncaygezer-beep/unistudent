<?php
/**
 * Günlük Challenge İlerleme Kontrolü — Cron Job
 *
 * Süresi dolan aktif challenge'ları otomatik olarak completed/failed olarak işaretler
 * ve kazanılan XP'yi kullanıcıya yazar.
 *
 * Kullanım (CLI):
 *   php cron/daily_challenges.php
 *
 * Crontab (günde 1 kez, gece 00:05'te):
 *   5 0 * * * /usr/bin/php /path/to/unistudent/cron/daily_challenges.php >> /var/log/ub_challenges.log 2>&1
 *
 * Windows Task Scheduler:
 *   Program: C:\xampp\php\php.exe
 *   Arguments: C:\xampp\htdocs\unistudent\cron\daily_challenges.php
 *   Schedule: Daily at 00:05
 */
declare(strict_types=1);

// CLI-only guard
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo json_encode(['error' => 'CLI only']);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

$today = date('Y-m-d');
$completed = 0;
$failed = 0;
$errors = 0;

echo "[" . date('Y-m-d H:i:s') . "] Challenge cron başlatılıyor...\n";

try {
    // Süresi dolmuş aktif challenge'ları bul
    $stmt = $pdo->prepare("
        SELECT uc.id, uc.user_id, uc.start_date, uc.end_date,
               c.category_block, c.target_savings, c.xp_reward, c.title
        FROM user_challenges uc
        JOIN challenges c ON c.id = uc.challenge_id
        WHERE uc.status = 'active' AND uc.end_date < ?
    ");
    $stmt->execute([$today]);
    $expired = $stmt->fetchAll();

    echo "  Süresi dolmuş aktif challenge sayısı: " . count($expired) . "\n";

    foreach ($expired as $a) {
        try {
            // Kategori harcamasını kontrol et
            $q = $pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) AS total
                FROM transactions
                WHERE user_id = ? AND type = 'expense' AND category = ?
                  AND transaction_date BETWEEN ? AND ?
            ");
            $q->execute([$a['user_id'], $a['category_block'], $a['start_date'], $a['end_date'] . ' 23:59:59']);
            $spent = (float)$q->fetchColumn();

            if ($spent == 0.0) {
                // Başarılı — harcama yok
                $pdo->prepare("UPDATE user_challenges SET status = 'completed', saved_amount = ? WHERE id = ?")
                    ->execute([$a['target_savings'], $a['id']]);
                $pdo->prepare("UPDATE users SET xp_points = xp_points + ? WHERE id = ?")
                    ->execute([$a['xp_reward'], $a['user_id']]);

                // Bildirim
                $pdo->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'success', ?, ?)")
                    ->execute([
                        $a['user_id'],
                        '🎉 Meydan okuma tamamlandı!',
                        '"' . $a['title'] . '" başarıyla tamamlandı. +' . $a['xp_reward'] . ' XP kazandın!'
                    ]);

                $completed++;
                echo "  ✅ #{$a['id']} (user #{$a['user_id']}) — {$a['title']} → COMPLETED (+{$a['xp_reward']} XP)\n";
            } else {
                // Başarısız — harcama var
                $pdo->prepare("UPDATE user_challenges SET status = 'failed' WHERE id = ?")
                    ->execute([$a['id']]);

                // Bildirim
                $pdo->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'warning', ?, ?)")
                    ->execute([
                        $a['user_id'],
                        '❌ Meydan okuma başarısız',
                        '"' . $a['title'] . '" — ' . number_format($spent, 0, ',', '.') . '₺ harcama tespit edildi.'
                    ]);

                $failed++;
                echo "  ❌ #{$a['id']} (user #{$a['user_id']}) — {$a['title']} → FAILED ({$spent}₺ harcandı)\n";
            }
        } catch (Throwable $e) {
            $errors++;
            echo "  ⚠️ #{$a['id']} hata: {$e->getMessage()}\n";
        }
    }
} catch (Throwable $e) {
    echo "  💥 Kritik hata: {$e->getMessage()}\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] Tamamlandı. ✅ {$completed} başarılı, ❌ {$failed} başarısız, ⚠️ {$errors} hata.\n";
