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
$user_id = (int)$_SESSION['user_id'];

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// State değiştiren action'lar için CSRF
$mutating = ['add', 'toggle', 'delete'];
if (in_array($action, $mutating, true) || $method !== 'GET') {
    csrf_require();
    rate_limit('sub_mutate', 60, 3600);
}

try {
    switch ($action) {
        case 'list':
            $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY is_active DESC, name ASC");
            $stmt->execute([$user_id]);
            json_response($stmt->fetchAll());
            break;

        case 'add':
            $name     = sanitize($_POST['name'] ?? '', 'text', ['max_length' => 100]);
            $amount   = sanitize($_POST['amount'] ?? 0, 'float', ['min' => 0, 'max' => 99999999.99]);
            $cycle    = in_array($_POST['billing_cycle'] ?? '', ['monthly', 'yearly'], true)
                        ? $_POST['billing_cycle'] : 'monthly';
            $category = sanitize($_POST['category'] ?? 'Diğer', 'string', ['max_length' => 50]);
            if ($name === '' || $amount <= 0) {
                json_response(['success' => false, 'message' => 'Ad ve tutar zorunlu.'], 422);
            }
            $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, name, amount, billing_cycle, category, next_billing)
                VALUES (?, ?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 1 MONTH))");
            $stmt->execute([$user_id, $name, $amount, $cycle, $category]);
            json_response(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
            break;

        case 'toggle':
            $id = sanitize($_POST['id'] ?? 0, 'int', ['min' => 1]);
            $stmt = $pdo->prepare("UPDATE subscriptions SET is_active = NOT is_active WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            json_response(['success' => $stmt->rowCount() > 0]);
            break;

        case 'delete':
            $id = sanitize($_POST['id'] ?? 0, 'int', ['min' => 1]);
            $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            json_response(['success' => $stmt->rowCount() > 0]);
            break;

        case 'summary':
            $stmt = $pdo->prepare("SELECT
                COALESCE(SUM(CASE WHEN billing_cycle='monthly' THEN amount ELSE amount/12 END), 0) AS monthly_total,
                COUNT(*) AS total_count
                FROM subscriptions WHERE user_id = ? AND is_active = 1");
            $stmt->execute([$user_id]);
            json_response($stmt->fetch() ?: []);
            break;

        default:
            json_response(['success' => false, 'message' => 'Bilinmeyen işlem.'], 400);
    }
} catch (PDOException $e) {
    error_log('subscription_handler error: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Veritabanı hatası.'], 500);
}
