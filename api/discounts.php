<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_system.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');
$auth = new AuthSystem($pdo);
$uid = $auth->isLoggedIn() ? (int)$_SESSION['user_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    if (!$uid) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Giriş gerekli']); exit; }
    $action = $_POST['action'] ?? '';
    $did = (int)($_POST['discount_id'] ?? 0);
    if ($action === 'claim' && $did) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO user_discount_claims (user_id, discount_id) VALUES (?,?)");
        $stmt->execute([$uid, $did]);
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'unclaim' && $did) {
        $stmt = $pdo->prepare("DELETE FROM user_discount_claims WHERE user_id=? AND discount_id=?");
        $stmt->execute([$uid, $did]);
        echo json_encode(['success'=>true]); exit;
    }
    echo json_encode(['success'=>false,'message'=>'Bilinmeyen işlem']); exit;
}

$category = $_GET['category'] ?? null;
$q        = trim($_GET['q'] ?? '');

$sql = "SELECT d.*, " . ($uid ? "(SELECT COUNT(*) FROM user_discount_claims c WHERE c.user_id=? AND c.discount_id=d.id) AS claimed" : "0 AS claimed") . "
    FROM student_discounts d WHERE d.is_active=1";
$params = $uid ? [$uid] : [];
if ($category) { $sql .= " AND d.category=?"; $params[] = $category; }
if ($q !== '') { $sql .= " AND d.brand LIKE ?"; $params[] = '%' . $q . '%'; }
$sql .= " ORDER BY d.monthly_saving DESC";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

$categories = $pdo->query("SELECT DISTINCT category FROM student_discounts WHERE is_active=1 ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

$totalSavings = 0;
foreach ($rows as $r) if ((int)$r['claimed'] > 0) $totalSavings += (float)$r['monthly_saving'];

echo json_encode([
    'success'    => true,
    'discounts'  => $rows,
    'categories' => $categories,
    'user_monthly_savings' => round($totalSavings, 2),
    'user_annual_savings'  => round($totalSavings * 12, 2),
]);
