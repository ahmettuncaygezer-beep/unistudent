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
    if ($action === 'generate') {
        $token = bin2hex(random_bytes(12));
        $s = $pdo->prepare("INSERT INTO telegram_links (user_id, link_token) VALUES (?,?)
            ON DUPLICATE KEY UPDATE link_token=VALUES(link_token), chat_id=NULL, linked_at=NULL");
        $s->execute([$uid, $token]);
        $botUser = env('TELEGRAM_BOT_USERNAME', 'UniButceBot');
        echo json_encode([
            'success' => true,
            'token' => $token,
            'deep_link' => "https://t.me/{$botUser}?start={$token}",
        ]);
        exit;
    }
    if ($action === 'unlink') {
        $pdo->prepare("DELETE FROM telegram_links WHERE user_id=?")->execute([$uid]);
        echo json_encode(['success'=>true]); exit;
    }
}

$s = $pdo->prepare("SELECT chat_id, link_token, linked_at FROM telegram_links WHERE user_id=?");
$s->execute([$uid]);
$r = $s->fetch();
echo json_encode([
    'success' => true,
    'linked'  => $r && !empty($r['linked_at']),
    'token'   => $r['link_token'] ?? null,
    'linked_at' => $r['linked_at'] ?? null,
]);
