<?php
declare(strict_types=1);
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/rate_limit.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    json_response(['success' => false, 'message' => 'Oturum açmanız gerekiyor.'], 401);
}

csrf_require();
rate_limit('save_budget', 60, 3600);

$user_id = (int)$_SESSION['user_id'];
$raw     = file_get_contents('php://input');
$data    = json_decode($raw ?: '', true);

if (!is_array($data) || !isset($data['expenses']) || !is_array($data['expenses'])) {
    json_response(['success' => false, 'message' => 'Geçersiz veri.'], 422);
}

// Boyut koruması: saçma boyutlu JSON kabul etme
if (strlen($raw) > 200_000) {
    json_response(['success' => false, 'message' => 'Veri çok büyük.'], 413);
}

$data['lastSaved'] = date('Y-m-d H:i:s');
$budget_json = json_encode($data, JSON_UNESCAPED_UNICODE);

try {
    $stmt = $pdo->prepare("UPDATE users SET budget_json = ? WHERE id = ?");
    $stmt->execute([$budget_json, $user_id]);
    json_response(['success' => true, 'message' => 'Bütçe başarıyla kaydedildi!']);
} catch (PDOException $e) {
    error_log('save_budget error: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Veritabanı hatası.'], 500);
}
