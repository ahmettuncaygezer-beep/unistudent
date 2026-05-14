<?php
declare(strict_types=1);
/**
 * ÜniBütçe — CSRF Koruma Yardımcıları
 *
 * Kullanım:
 *   Form: <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
 *   JS:   headers: { 'X-CSRF-Token': window.CSRF_TOKEN }
 *   API:  csrf_require(); // doğrulanmazsa 403 dönüp sonlanır
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_validate(?string $token): bool
{
    if (!$token || empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_require(): void
{
    // GET istekleri state değiştirmediği için CSRF'ten muaf
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') return;

    $token = $_POST['csrf_token']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN_']
        ?? null;

    if (!csrf_validate($token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'CSRF doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.',
        ]);
        exit;
    }
}
