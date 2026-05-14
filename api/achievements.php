<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'check';

switch ($action) {
    case 'check':
        // Check and auto-award achievements
        $awarded = [];

        // 1. İlk Adım — first transaction
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        if ((int)$stmt->fetchColumn() >= 1) {
            $awarded[] = awardAchievement($pdo, $user_id, 'first_step');
        }

        // 2. Abonelik Avcısı — first subscription
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE user_id = ?");
            $stmt->execute([$user_id]);
            if ((int)$stmt->fetchColumn() >= 1) {
                $awarded[] = awardAchievement($pdo, $user_id, 'sub_hunter');
            }
        } catch (Exception $e) {}

        // 3. 7 Gün Serisi — 7 distinct days with transactions in last 7 days
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT DATE(transaction_date)) FROM transactions WHERE user_id = ? AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        $stmt->execute([$user_id]);
        if ((int)$stmt->fetchColumn() >= 7) {
            $awarded[] = awardAchievement($pdo, $user_id, 'streak_7');
        }

        echo json_encode(['awarded' => array_filter($awarded)]);
        break;

    case 'list':
        $stmt = $pdo->prepare("
            SELECT a.*, ua.earned_at,
                CASE WHEN ua.id IS NOT NULL THEN 1 ELSE 0 END as is_earned
            FROM achievements a
            LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
            ORDER BY a.id ASC
        ");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll());
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}

function awardAchievement($pdo, $user_id, $slug) {
    try {
        // Get achievement ID
        $stmt = $pdo->prepare("SELECT id, xp_reward, title FROM achievements WHERE slug = ?");
        $stmt->execute([$slug]);
        $ach = $stmt->fetch();
        if (!$ach) return null;

        // Check if already awarded
        $chk = $pdo->prepare("SELECT id FROM user_achievements WHERE user_id = ? AND achievement_id = ?");
        $chk->execute([$user_id, $ach['id']]);
        if ($chk->fetch()) return null;

        // Award
        $ins = $pdo->prepare("INSERT INTO user_achievements (user_id, achievement_id) VALUES (?, ?)");
        $ins->execute([$user_id, $ach['id']]);

        // Add XP
        $xp = $pdo->prepare("UPDATE users SET xp_points = xp_points + ? WHERE id = ?");
        $xp->execute([$ach['xp_reward'], $user_id]);

        // Create notification
        $notif = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'success', ?, ?)");
        $notif->execute([$user_id, '🏆 Başarım Kazanıldı: ' . $ach['title'], '+' . $ach['xp_reward'] . ' XP kazandın!']);

        return $ach['title'];
    } catch (Exception $e) {
        return null;
    }
}
