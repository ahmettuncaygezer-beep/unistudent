<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo '{}'; exit; }

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$hash = hash('sha256', $ip . '|' . $ua . '|' . env('APP_SECRET', 'unibutce-2026'));
$analytics = (int)($_POST['analytics'] ?? 0) === 1 ? 1 : 0;

try {
    $s = $pdo->prepare("INSERT INTO cookie_consents (user_hash, necessary, analytics, marketing) VALUES (?,1,?,0)");
    $s->execute([$hash, $analytics]);
} catch (Throwable $e) { /* best-effort */ }

echo json_encode(['success'=>true]);
