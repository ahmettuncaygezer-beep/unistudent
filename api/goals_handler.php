<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_system.php';
require_once __DIR__ . '/../includes/csrf.php';

$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit;
}

// State değiştiren isteklerde CSRF doğrulaması
csrf_require();

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $title = trim($_POST['title'] ?? '');
            $target = floatval($_POST['target_amount'] ?? 0);
            $icon = $_POST['icon'] ?? '🎯';
            $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;

            if (empty($title) || $target <= 0) {
                echo json_encode(['success' => false, 'message' => 'Hedef adı ve tutar zorunludur']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO user_goals (user_id, title, target_amount, icon, deadline) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $target, $icon, $deadline]);

            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;

        case 'add_fund':
            $goal_id = (int)($_POST['goal_id'] ?? 0);
            $amount = floatval($_POST['amount'] ?? 0);

            if ($goal_id <= 0 || $amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Geçersiz veri']);
                exit;
            }

            // Verify ownership
            $stmt = $pdo->prepare("SELECT * FROM user_goals WHERE id = ? AND user_id = ?");
            $stmt->execute([$goal_id, $user_id]);
            $goal = $stmt->fetch();

            if (!$goal) {
                echo json_encode(['success' => false, 'message' => 'Hedef bulunamadı']);
                exit;
            }

            $newAmount = (float)$goal['current_amount'] + $amount;
            $completed = $newAmount >= (float)$goal['target_amount'] ? 1 : 0;

            $stmt = $pdo->prepare("UPDATE user_goals SET current_amount = ?, is_completed = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$newAmount, $completed, $goal_id, $user_id]);

            // Award XP if completed
            if ($completed && !(int)$goal['is_completed']) {
                try {
                    $pdo->prepare("UPDATE users SET xp_points = xp_points + 200 WHERE id = ?")->execute([$user_id]);
                } catch (Exception $e) {}
            }

            echo json_encode(['success' => true, 'completed' => (bool)$completed, 'new_amount' => $newAmount]);
            break;

        case 'delete':
            $goal_id = (int)($_POST['goal_id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM user_goals WHERE id = ? AND user_id = ?");
            $stmt->execute([$goal_id, $user_id]);
            echo json_encode(['success' => true]);
            break;

        case 'list':
            $stmt = $pdo->prepare("SELECT * FROM user_goals WHERE user_id = ? ORDER BY is_completed ASC, created_at DESC");
            $stmt->execute([$user_id]);
            echo json_encode($stmt->fetchAll());
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Geçersiz aksiyon']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
}
