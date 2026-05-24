<?php
declare(strict_types=1);
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_system.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) {
    json_response(['success' => false, 'error' => 'Unauthorized'], 401);
}
$user_id = (int)$_SESSION['user_id'];

// CSRF for state-mutating actions only
$action = $_GET['action'] ?? $_POST['action'] ?? '';
if ($action === 'check' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    csrf_require();
}

// Default action: list (safe GET)
if ($action === '' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = 'list';
}

switch ($action) {
    case 'check':
        // Sadece POST ile tetiklenebilir
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'error' => 'POST gerekli'], 405);
        }
        csrf_require();
        $awarded = [];

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        if ((int)$stmt->fetchColumn() >= 1) {
            $awarded[] = awardAchievement($pdo, $user_id, 'first_step');
        }

        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE user_id = ?");
            $stmt->execute([$user_id]);
            if ((int)$stmt->fetchColumn() >= 1) {
                $awarded[] = awardAchievement($pdo, $user_id, 'sub_hunter');
            }
        } catch (Exception $e) {}

        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT DATE(transaction_date)) FROM transactions WHERE user_id = ? AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        $stmt->execute([$user_id]);
        if ((int)$stmt->fetchColumn() >= 7) {
            $awarded[] = awardAchievement($pdo, $user_id, 'streak_7');
        }

        json_response(['success' => true, 'awarded' => array_values(array_filter($awarded))]);
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
        json_response($stmt->fetchAll());
        break;

    default:
        json_response(['success' => false, 'error' => 'Unknown action'], 400);
}

function awardAchievement(PDO $pdo, int $user_id, string $slug): ?string {
    try {
        $stmt = $pdo->prepare("SELECT id, xp_reward, title FROM achievements WHERE slug = ?");
        $stmt->execute([$slug]);
        $ach = $stmt->fetch();
        if (!$ach) return null;

        $chk = $pdo->prepare("SELECT id FROM user_achievements WHERE user_id = ? AND achievement_id = ?");
        $chk->execute([$user_id, $ach['id']]);
        if ($chk->fetch()) return null;

        $ins = $pdo->prepare("INSERT INTO user_achievements (user_id, achievement_id) VALUES (?, ?)");
        $ins->execute([$user_id, $ach['id']]);

        $xp = $pdo->prepare("UPDATE users SET xp_points = xp_points + ? WHERE id = ?");
        $xp->execute([$ach['xp_reward'], $user_id]);

        $notif = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'success', ?, ?)");
        $notif->execute([$user_id, '🏆 Başarım Kazanıldı: ' . $ach['title'], '+' . $ach['xp_reward'] . ' XP kazandın!']);

        return $ach['title'];
    } catch (Exception $e) {
        error_log('awardAchievement error: ' . $e->getMessage());
        return null;
    }
}
