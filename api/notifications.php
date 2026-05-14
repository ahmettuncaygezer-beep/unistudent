<?php
declare(strict_types=1);
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    json_response(['success' => false, 'message' => 'Oturum yok.'], 401);
}
$user_id = (int)$_SESSION['user_id'];
$action  = $_GET['action'] ?? $_POST['action'] ?? '';

// Mutating işlemler CSRF ister
if (in_array($action, ['mark_all_read', 'create'], true)) {
    csrf_require();
}

try {
    switch ($action) {
        case 'list':
            $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
            $stmt->execute([$user_id]);
            json_response($stmt->fetchAll());
            break;

        case 'mark_all_read':
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$user_id]);
            json_response(['success' => true]);
            break;

        case 'create':
            $title   = sanitize($_POST['title'] ?? '', 'text', ['max_length' => 255]);
            $message = sanitize($_POST['message'] ?? '', 'text', ['max_length' => 1000]);
            $type    = in_array($_POST['type'] ?? '', ['info', 'success', 'warning', 'error'], true)
                       ? $_POST['type'] : 'info';
            if ($title === '') {
                json_response(['success' => false, 'message' => 'Başlık gerekli.'], 422);
            }
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $type, $title, $message]);
            json_response(['success' => true]);
            break;

        default:
            json_response(['success' => false, 'message' => 'Bilinmeyen işlem.'], 400);
    }
} catch (PDOException $e) {
    error_log('notifications error: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Veritabanı hatası.'], 500);
}
