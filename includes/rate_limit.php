<?php
declare(strict_types=1);
/**
 * Dosya-tabanlı atomik rate limiter (flock ile TOCTOU kapalı).
 * Kullanım: rate_limit('save_budget', 60, 3600); // saatte 60
 */
function rate_limit(string $bucket, int $limit, int $windowSec): void
{
    $ip  = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ip  = preg_replace('/[^a-zA-Z0-9._:-]/', '', $ip) ?? 'unknown';
    $uid = $_SESSION['user_id'] ?? 'guest';

    $dir = sys_get_temp_dir() . '/unibutce_rl';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);

    $file = $dir . '/' . md5($bucket . '|' . $uid . '|' . $ip) . '.json';
    $now  = time();

    $fp = fopen($file, 'c+');
    if (!$fp) return; // rate limiter çalışmazsa isteği boğma

    flock($fp, LOCK_EX);
    $raw   = stream_get_contents($fp);
    $state = $raw ? (json_decode($raw, true) ?: []) : [];
    if (!isset($state['window_start']) || ($now - $state['window_start']) >= $windowSec) {
        $state = ['count' => 0, 'window_start' => $now];
    }

    if ($state['count'] >= $limit) {
        $retry = $windowSec - ($now - $state['window_start']);
        flock($fp, LOCK_UN);
        fclose($fp);
        http_response_code(429);
        header('Content-Type: application/json; charset=utf-8');
        header('Retry-After: ' . $retry);
        echo json_encode([
            'success' => false,
            'message' => 'Çok fazla istek. Lütfen biraz bekleyin.',
            'retry_after' => $retry,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $state['count']++;
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($state));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}
