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
    $stmt = $pdo->prepare("SELECT * FROM user_scholarships WHERE user_id = ? ORDER BY deadline ASC");
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll();
    // Annotate days-left
    foreach ($rows as &$r) {
        if (!empty($r['deadline'])) {
            $r['days_left'] = max(0, (int)((strtotime($r['deadline']) - time()) / 86400));
            $r['is_urgent'] = $r['days_left'] <= 14 && $r['status'] === 'planned';
        }
    }
    json_response(['success' => true, 'scholarships' => $rows]);
}

if ($method !== 'POST') json_response(['success' => false, 'message' => 'Invalid'], 405);
csrf_require();

$action = $_POST['action'] ?? 'create';

if ($action === 'create' || $action === 'update') {
    $id       = (int)($_POST['id'] ?? 0);
    $name     = sanitize($_POST['name'] ?? '', 'text', ['max_length' => 200]);
    $amount   = sanitize($_POST['amount'] ?? 0, 'float');
    $deadline = sanitize($_POST['deadline'] ?? '', 'date');
    $status   = in_array($_POST['status'] ?? 'planned', ['applied','awarded','missed','planned'], true)
        ? $_POST['status'] : 'planned';
    $notes    = sanitize($_POST['notes'] ?? '', 'text', ['max_length' => 2000]);

    if ($name === '') json_response(['success' => false, 'message' => 'İsim zorunlu.'], 422);

    if ($action === 'update' && $id > 0) {
        $pdo->prepare("UPDATE user_scholarships SET name=?, amount=?, deadline=?, status=?, notes=? WHERE id=? AND user_id=?")
            ->execute([$name, $amount, $deadline ?: null, $status, $notes, $id, $user_id]);
        json_response(['success' => true]);
    }
    $pdo->prepare("INSERT INTO user_scholarships (user_id, name, amount, deadline, status, notes) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$user_id, $name, $amount, $deadline ?: null, $status, $notes]);
    json_response(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM user_scholarships WHERE id=? AND user_id=?")->execute([$id, $user_id]);
    json_response(['success' => true]);
}

json_response(['success' => false, 'message' => 'Unknown action'], 400);
