<?php
declare(strict_types=1);
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_system.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/audit.php';

header('Content-Type: application/json; charset=utf-8');

$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) {
    json_response(['success' => false, 'message' => 'Oturum yok.'], 401);
}
$user_id = (int)$_SESSION['user_id'];

// Ensure row exists
$pdo->prepare("INSERT IGNORE INTO user_settings (user_id) VALUES (?)")->execute([$user_id]);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT theme, currency, abroad_mode, dashboard_json, ui_flags FROM user_settings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch() ?: [];
    json_response(['success' => true, 'settings' => $row]);
}

if ($method !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid method'], 405);
}

csrf_require();

// JSON body destek
$raw = file_get_contents('php://input');
$body = [];
if ($raw && str_starts_with(trim($raw), '{')) {
    $body = json_decode($raw, true) ?: [];
}
$get = fn($k, $def = null) => $_POST[$k] ?? $body[$k] ?? $def;

$updates = [];
$params  = [];

if (($v = $get('theme')) !== null) {
    $v = sanitize($v, 'string', ['max_length' => 16]);
    if (in_array($v, ['auto','light','dark','oled','sepia'], true)) {
        $updates[] = 'theme = ?'; $params[] = $v;
    }
}
if (($v = $get('currency')) !== null) {
    $v = strtoupper(sanitize($v, 'string', ['max_length' => 8]));
    if (preg_match('/^[A-Z]{3}$/', $v)) {
        $updates[] = 'currency = ?'; $params[] = $v;
    }
}
if (($v = $get('abroad_mode')) !== null) {
    $updates[] = 'abroad_mode = ?'; $params[] = (int)!!$v;
}
if (($v = $get('dashboard_json')) !== null) {
    if (is_array($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE);
    $updates[] = 'dashboard_json = ?'; $params[] = substr((string)$v, 0, 20000);
}
if (($v = $get('ui_flags')) !== null) {
    if (is_array($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE);
    $updates[] = 'ui_flags = ?'; $params[] = substr((string)$v, 0, 10000);
}

if (!$updates) {
    json_response(['success' => false, 'message' => 'Güncellenecek alan yok.'], 422);
}

$params[] = $user_id;
$sql = "UPDATE user_settings SET " . implode(', ', $updates) . " WHERE user_id = ?";
$pdo->prepare($sql)->execute($params);

audit_log($user_id, 'settings.update', ['fields' => array_map(fn($u) => explode(' ', $u)[0], $updates)]);
json_response(['success' => true]);
