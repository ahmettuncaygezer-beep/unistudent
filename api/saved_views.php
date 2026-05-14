<?php
declare(strict_types=1);
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_system.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

header('Content-Type: application/json; charset=utf-8');

$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) {
    json_response(['success' => false, 'message' => 'Oturum yok.'], 401);
}
$user_id = (int)$_SESSION['user_id'];
$method  = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT id, name, filters, created_at FROM user_saved_views WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $rows = array_map(function ($r) {
        $r['filters'] = json_decode($r['filters'] ?: '{}', true) ?: [];
        return $r;
    }, $stmt->fetchAll());
    json_response(['success' => true, 'views' => $rows]);
}

if ($method !== 'POST') json_response(['success' => false, 'message' => 'Invalid'], 405);
csrf_require();

$action = $_POST['action'] ?? 'create';
if ($action === 'create') {
    $name    = sanitize($_POST['name'] ?? '', 'text', ['max_length' => 100]);
    $filters = $_POST['filters'] ?? '{}';
    if (is_array($filters)) $filters = json_encode($filters, JSON_UNESCAPED_UNICODE);
    if ($name === '') json_response(['success' => false, 'message' => 'İsim gerekli.'], 422);
    $pdo->prepare("INSERT INTO user_saved_views (user_id, name, filters) VALUES (?, ?, ?)")
        ->execute([$user_id, $name, substr((string)$filters, 0, 5000)]);
    json_response(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
}
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM user_saved_views WHERE id=? AND user_id=?")->execute([$id, $user_id]);
    json_response(['success' => true]);
}

json_response(['success' => false, 'message' => 'Unknown action'], 400);
