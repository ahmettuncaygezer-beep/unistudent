<?php
declare(strict_types=1);
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_system.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/rate_limit.php';

header('Content-Type: application/json; charset=utf-8');

$auth   = new AuthSystem($pdo);
$action = $_POST['action'] ?? '';

// logout hariç, genel POST action'lar için CSRF
// Login ve register session expiry kaynaklı hataları önlemek için CSRF'ten muaf tutuldu (Kritik değil)
if ($action !== '' && $action !== 'logout' && $action !== 'login' && $action !== 'register') {
    csrf_require();
}

switch ($action) {
    case 'register':
        rate_limit('register', 5, 3600); // saatte 5 kayıt denemesi
        $fullName       = sanitize($_POST['full_name'] ?? '', 'text', ['max_length' => 255]);
        $username       = sanitize($_POST['username'] ?? '', 'string', ['max_length' => 32]);
        $email          = sanitize($_POST['email'] ?? '', 'email') ?? '';
        $password       = (string)($_POST['password'] ?? '');
        $universityName = sanitize($_POST['university_name'] ?? '', 'text', ['max_length' => 255]);

        if (strlen($password) < 8) {
            json_response(['success' => false, 'message' => 'Şifre en az 8 karakter olmalı.'], 422);
        }

        $result = $auth->register($fullName, $username, $email, $password, $universityName);
        json_response($result, $result['success'] ? 200 : 400);
        break;

    case 'login':
        rate_limit('login', 10, 600); // 10 dk'da 10 deneme
        $identifier = sanitize($_POST['identifier'] ?? '', 'string', ['max_length' => 255]);
        $password   = (string)($_POST['password'] ?? '');
        $result = $auth->login($identifier, $password);
        json_response($result, $result['success'] ? 200 : 401);
        break;

    case 'logout':
        json_response($auth->logout());
        break;

    case 'complete_onboarding':
        if (!isset($_SESSION['user_id'])) {
            json_response(['success' => false, 'message' => 'Oturum yok.'], 401);
        }
        $stmt = $pdo->prepare("UPDATE users SET onboarding_done = 1 WHERE id = ?");
        $stmt->execute([(int)$_SESSION['user_id']]);
        json_response(['success' => true]);
        break;

    default:
        json_response(['success' => false, 'message' => 'Geçersiz işlem.'], 400);
}
