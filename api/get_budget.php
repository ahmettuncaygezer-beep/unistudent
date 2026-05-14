<?php
require_once __DIR__ . '/cors.php';
header('Content-Type: application/json');

require_once '../includes/db.php';

$userId = isset($_GET['userId']) ? (int)$_GET['userId'] : 0;
if ($userId <= 0 && isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
}

// Oturum dışı bir userId istemek yasak: yalnızca oturum sahibi kendi verisine erişebilir
if (isset($_SESSION['user_id']) && $userId !== (int)$_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT budget_json FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if ($user && !empty($user['budget_json'])) {
        $budget = json_decode($user['budget_json'], true);
        echo json_encode([
            'success' => true,
            'budget' => $budget
        ]);
    } else {
        // Boş bütçe döndür
        echo json_encode([
            'success' => true,
            'budget' => [
                'expenses' => [],
                'total' => 0,
                'lastSaved' => null
            ]
        ]);
    }
} catch (Exception $e) {
    error_log('get_budget DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası.']);
}
