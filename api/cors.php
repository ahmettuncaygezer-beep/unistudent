<?php
declare(strict_types=1);
/**
 * CORS — whitelist tabanlı. Üretimde .env > CORS_ALLOWED_ORIGINS
 * (virgülle ayrılmış liste, ör: https://unibutce.com,https://www.unibutce.com).
 * Boşsa sadece same-origin / credentialed istekler reddedilir.
 */

require_once __DIR__ . '/../config.php';

$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', ''))));

// Local development için localhost origin'lerine izin ver
if (env('APP_ENV', 'local') === 'local') {
    $allowed = array_merge($allowed, [
        'http://localhost',
        'http://localhost:5173',
        'http://localhost:3000',
        'http://127.0.0.1',
    ]);
}

if ($origin && in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}
