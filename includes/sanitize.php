<?php
declare(strict_types=1);
/**
 * ÜniBütçe — Merkezi Input Sanitization Yardımcıları
 */

/**
 * @param mixed  $v
 * @param string $type int|float|string|email|username|slug|text|bool|date
 * @param array  $opts max_length, min, max
 * @return mixed
 */
function sanitize($v, string $type, array $opts = [])
{
    switch ($type) {
        case 'int':
            $n = (int)$v;
            if (isset($opts['min']) && $n < $opts['min']) $n = (int)$opts['min'];
            if (isset($opts['max']) && $n > $opts['max']) $n = (int)$opts['max'];
            return $n;

        case 'float':
            $n = (float)$v;
            if (isset($opts['min']) && $n < $opts['min']) $n = (float)$opts['min'];
            if (isset($opts['max']) && $n > $opts['max']) $n = (float)$opts['max'];
            return round($n, $opts['precision'] ?? 2);

        case 'email':
            $e = filter_var(trim((string)$v), FILTER_VALIDATE_EMAIL);
            return $e === false ? null : $e;

        case 'username':
            $u = trim((string)$v);
            return preg_match('/^[a-zA-Z0-9_]{3,32}$/', $u) ? $u : null;

        case 'slug':
            $s = strtolower(trim((string)$v));
            $s = preg_replace('/[^a-z0-9-]+/', '-', $s) ?? '';
            return trim($s, '-');

        case 'text':
            $t = trim((string)$v);
            $max = $opts['max_length'] ?? 1000;
            return mb_substr($t, 0, $max);

        case 'bool':
            return filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;

        case 'date':
            $d = trim((string)$v);
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : null;

        case 'string':
        default:
            $max = $opts['max_length'] ?? 255;
            return mb_substr(trim((string)$v), 0, $max);
    }
}

/** HTML çıktısı için escape; echo yerine her zaman bunu kullan. */
function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** JSON yanıt ve çıkış */
function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
