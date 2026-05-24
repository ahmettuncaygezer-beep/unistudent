<?php
declare(strict_types=1);
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_system.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/digest_generator.php';

header('Content-Type: application/json; charset=utf-8');

$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) {
    json_response(['success' => false, 'message' => 'Oturum yok.'], 401);
}
$user_id = (int)$_SESSION['user_id'];

require_once __DIR__ . '/../includes/rate_limit.php';
rate_limit('digest', 5, 3600); // saatte 5 istek

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'GET gerekli.'], 405);
}

json_response(['success' => true, 'digest' => generate_weekly_digest($pdo, $user_id)]);
