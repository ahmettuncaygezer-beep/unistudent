<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_system.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');
$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) { http_response_code(401); echo json_encode(['success'=>false]); exit; }
$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $stmt = $pdo->prepare("INSERT INTO user_deposits (user_id, landlord_name, address, amount, paid_date, expected_return_date, notes)
            VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([
            $uid,
            substr($_POST['landlord_name'] ?? '', 0, 200),
            substr($_POST['address'] ?? '', 0, 255),
            (float)($_POST['amount'] ?? 0),
            $_POST['paid_date'] ?: null,
            $_POST['expected_return_date'] ?: null,
            substr($_POST['notes'] ?? '', 0, 500),
        ]);
        echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]); exit;
    }
    if ($action === 'update_status') {
        $id = (int)$_POST['id'];
        $status = in_array($_POST['status'] ?? '', ['active','returned','partial','lost'], true) ? $_POST['status'] : 'active';
        $ret = (float)($_POST['returned_amount'] ?? 0);
        $stmt = $pdo->prepare("UPDATE user_deposits SET status=?, returned_amount=? WHERE id=? AND user_id=?");
        $stmt->execute([$status, $ret, $id, $uid]);
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM user_deposits WHERE id=? AND user_id=?");
        $stmt->execute([(int)$_POST['id'], $uid]);
        echo json_encode(['success'=>true]); exit;
    }
    echo json_encode(['success'=>false,'message'=>'?']); exit;
}

$st = $pdo->prepare("SELECT * FROM user_deposits WHERE user_id=? ORDER BY status='active' DESC, created_at DESC");
$st->execute([$uid]);
$rows = $st->fetchAll();

$today = new DateTime();
$totalActive = 0; $totalLost = 0;
foreach ($rows as &$r) {
    $r['days_since'] = $r['paid_date'] ? (int)$today->diff(new DateTime($r['paid_date']))->days : 0;
    if ($r['expected_return_date']) {
        $d = new DateTime($r['expected_return_date']);
        $r['days_until_return'] = (int)((($d->getTimestamp() - $today->getTimestamp())/86400));
    } else $r['days_until_return'] = null;
    if ($r['status']==='active') $totalActive += (float)$r['amount'];
    if ($r['status']==='lost')   $totalLost   += (float)$r['amount'];
}

echo json_encode([
    'success' => true,
    'deposits' => $rows,
    'totals' => ['active' => $totalActive, 'lost' => $totalLost]
]);
