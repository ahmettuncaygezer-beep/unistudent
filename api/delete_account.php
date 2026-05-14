<?php
declare(strict_types=1);
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_system.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/rate_limit.php';

header('Content-Type: application/json; charset=utf-8');

$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) {
    json_response(['success' => false, 'message' => 'Oturum yok.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'POST gerekli.'], 405);
}

csrf_require();
rate_limit('account_delete', 3, 86400);

$user_id = (int)$_SESSION['user_id'];
$confirm = sanitize($_POST['confirm'] ?? '', 'string', ['max_length' => 32]);

if ($confirm !== 'HESABIMI SIL') {
    json_response([
        'success' => false,
        'message' => 'Onay metni eşleşmiyor. "HESABIMI SIL" yazmalısın.'
    ], 422);
}

try {
    $pdo->beginTransaction();
    // Foreign keys ON DELETE CASCADE tüm veriyi götürür.
    audit_log($user_id, 'account.delete_requested', []);
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $pdo->commit();

    // Session'ı kapat
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();

    json_response(['success' => true, 'message' => 'Hesabın ve tüm verilerin silindi.']);
} catch (\Throwable $e) {
    $pdo->rollBack();
    error_log('delete_account: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Silme başarısız.'], 500);
}
