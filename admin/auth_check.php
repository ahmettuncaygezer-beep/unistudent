<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function check_login(): void {
    $isLoggedIn  = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    $loginTime   = $_SESSION['admin_login_time'] ?? 0;
    $sessionAge  = time() - $loginTime;
    $maxAge      = 4 * 3600; // 4 saat

    if (!$isLoggedIn || $sessionAge > $maxAge) {
        // Oturum temizle
        unset($_SESSION['logged_in'], $_SESSION['admin_login_time']);

        // JSON API isteği ise 401 döndür
        $isApi = (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')
               || str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/api/'));
        $wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
                  || str_contains($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'XMLHttpRequest');

        if ($isApi || $wantsJson) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Oturumunuz sona erdi. Yeniden giriş yapın.']);
            exit;
        }

        header('Location: ../admin/login.php?expired=1');
        exit;
    }

    // Her başarılı istekte süreyi yenile
    $_SESSION['admin_login_time'] = time();
}
