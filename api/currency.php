<?php
declare(strict_types=1);
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/sanitize.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * Cached currency rates (TRY base). 6-hour cache.
 * Fallback to static recent values if network is unreachable.
 */
const CACHE_FILE = __DIR__ . '/../data/currency_cache.json';
const CACHE_TTL  = 21600;

$fallback = [
    'base'  => 'TRY',
    'rates' => [
        'TRY' => 1.0,
        'USD' => 0.031,
        'EUR' => 0.028,
        'GBP' => 0.024,
        'CHF' => 0.027,
        'JPY' => 4.65,
        'RUB' => 2.85,
    ],
    'updated_at' => '2026-01-01T00:00:00Z',
    'source' => 'fallback',
];

function load_cache(): ?array {
    if (!is_file(CACHE_FILE)) return null;
    $raw = @file_get_contents(CACHE_FILE);
    $data = json_decode($raw ?: '', true);
    if (!$data || !isset($data['updated_at'])) return null;
    if (time() - strtotime($data['updated_at']) > CACHE_TTL) return null;
    return $data;
}

function save_cache(array $data): void {
    $dir = dirname(CACHE_FILE);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    @file_put_contents(CACHE_FILE, json_encode($data, JSON_UNESCAPED_UNICODE));
}

function fetch_live(): ?array {
    $ctx = stream_context_create(['http' => ['timeout' => 4, 'user_agent' => 'UniBudget/1.0']]);
    // open.er-api.com is free, no API key
    $raw = @file_get_contents('https://open.er-api.com/v6/latest/TRY', false, $ctx);
    if (!$raw) return null;
    $j = json_decode($raw, true);
    if (!$j || empty($j['rates'])) return null;
    return [
        'base'  => 'TRY',
        'rates' => $j['rates'],
        'updated_at' => gmdate('c'),
        'source' => 'open.er-api.com',
    ];
}

$data = load_cache();
if (!$data) {
    $data = fetch_live();
    if ($data) save_cache($data);
    else $data = $fallback;
}

$to = strtoupper(sanitize($_GET['to'] ?? '', 'string', ['max_length' => 3]));
$amount = sanitize($_GET['amount'] ?? 0, 'float');

if ($to && isset($data['rates'][$to]) && $amount > 0) {
    $converted = $amount * (float)$data['rates'][$to];
    json_response([
        'success' => true,
        'from' => 'TRY', 'to' => $to,
        'amount' => $amount, 'converted' => round($converted, 4),
        'rate' => $data['rates'][$to],
        'updated_at' => $data['updated_at'],
    ]);
}

json_response(['success' => true] + $data);
