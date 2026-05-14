<?php
declare(strict_types=1);
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_system.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/rate_limit.php';

header('Content-Type: application/json; charset=utf-8');

$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) {
    json_response(['success' => false, 'message' => 'Oturum açmanız gerekiyor.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Geçersiz istek yöntemi.'], 405);
}

csrf_require();
rate_limit('tx_insert', 120, 3600); // saatte 120 işlem

$user_id     = (int)$_SESSION['user_id'];
$type        = sanitize($_POST['type'] ?? '', 'string', ['max_length' => 10]);
$amount      = sanitize($_POST['amount'] ?? 0, 'float', ['min' => 0, 'max' => 99999999.99]);
$category    = sanitize($_POST['category'] ?? '', 'text', ['max_length' => 100]);
$description = sanitize($_POST['description'] ?? '', 'text', ['max_length' => 1000]);

if (!in_array($type, ['income', 'expense'], true)) {
    json_response(['success' => false, 'message' => 'Geçersiz işlem tipi.'], 422);
}
if ($amount <= 0) {
    json_response(['success' => false, 'message' => 'Lütfen geçerli bir tutar girin.'], 422);
}
if ($category === '') {
    json_response(['success' => false, 'message' => 'Lütfen bir kategori seçin.'], 422);
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO transactions (user_id, type, amount, category, description) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$user_id, $type, $amount, $category, $description]);
    json_response(['success' => true, 'message' => 'İşlem başarıyla kaydedildi.', 'id' => (int)$pdo->lastInsertId()]);
} catch (PDOException $e) {
    error_log('transaction_handler DB error: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Veritabanı hatası oluştu.'], 500);
}
