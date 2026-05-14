<?php
declare(strict_types=1);
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_system.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/rate_limit.php';

header('Content-Type: application/json; charset=utf-8');

$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) {
    json_response(['success' => false, 'message' => 'Oturum yok.'], 401);
}
$user_id = (int)$_SESSION['user_id'];

/**
 * Bilinen abonelik markaları (regex) → kategori
 */
const KNOWN_SUBS = [
    'netflix'          => ['name' => 'Netflix',          'cat' => 'Eğlence'],
    'spotify'          => ['name' => 'Spotify',          'cat' => 'Eğlence'],
    'youtube'          => ['name' => 'YouTube Premium',  'cat' => 'Eğlence'],
    'disney'           => ['name' => 'Disney+',          'cat' => 'Eğlence'],
    'blutv|blu tv'     => ['name' => 'BluTV',            'cat' => 'Eğlence'],
    'exxen'            => ['name' => 'Exxen',            'cat' => 'Eğlence'],
    'gain'             => ['name' => 'Gain',             'cat' => 'Eğlence'],
    'icloud'           => ['name' => 'iCloud',           'cat' => 'Teknoloji'],
    'google one|drive' => ['name' => 'Google One',       'cat' => 'Teknoloji'],
    'microsoft|office 365|m365' => ['name' => 'Microsoft 365', 'cat' => 'Teknoloji'],
    'adobe'            => ['name' => 'Adobe',            'cat' => 'Teknoloji'],
    'chatgpt|openai'   => ['name' => 'ChatGPT',          'cat' => 'Teknoloji'],
    'claude|anthropic' => ['name' => 'Claude',           'cat' => 'Teknoloji'],
    'notion'           => ['name' => 'Notion',           'cat' => 'Teknoloji'],
    'duolingo'         => ['name' => 'Duolingo',         'cat' => 'Eğitim'],
    'amazon prime|prime video' => ['name' => 'Amazon Prime', 'cat' => 'Eğlence'],
    'gittigidiyor|trendyol premium' => ['name' => 'Trendyol Premium', 'cat' => 'Alışveriş'],
    'yemeksepeti joker' => ['name' => 'Yemeksepeti Joker','cat' => 'Yemek'],
    'getir'            => ['name' => 'Getir',            'cat' => 'Yemek'],
    'gsm|turkcell|vodafone|türk telekom' => ['name' => 'GSM Faturası', 'cat' => 'Faturalar'],
];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Scan last 180 days of transactions for recurring patterns
    $stmt = $pdo->prepare(
        "SELECT id, description, amount, category, transaction_date
         FROM transactions
         WHERE user_id = ? AND type = 'expense'
           AND transaction_date >= DATE_SUB(NOW(), INTERVAL 180 DAY)
         ORDER BY transaction_date DESC"
    );
    $stmt->execute([$user_id]);
    $txs = $stmt->fetchAll();

    // Group by normalized description
    $groups = [];
    foreach ($txs as $t) {
        $desc = mb_strtolower($t['description'] ?? '', 'UTF-8');
        if ($desc === '') continue;
        $key = preg_replace('/[^a-z0-9ğıöşüç ]/u', '', $desc);
        $key = trim(preg_replace('/\s+/', ' ', $key));
        if ($key === '') continue;
        $groups[$key][] = $t;
    }

    // Existing subs (avoid duplicates)
    $existing = $pdo->prepare("SELECT LOWER(name) AS n FROM subscriptions WHERE user_id = ?");
    $existing->execute([$user_id]);
    $existingNames = array_column($existing->fetchAll(), 'n');

    $detected = [];
    foreach ($groups as $desc => $items) {
        if (count($items) < 2) continue;
        // Similar amounts
        $amounts = array_map(fn($x) => (float)$x['amount'], $items);
        $mean = array_sum($amounts) / count($amounts);
        $close = array_filter($amounts, fn($a) => abs($a - $mean) / max($mean, 1) < 0.15);
        if (count($close) < 2) continue;

        // Match known brand
        $matched = null;
        foreach (KNOWN_SUBS as $pattern => $meta) {
            if (preg_match('/\b(' . $pattern . ')\b/iu', $desc)) {
                $matched = $meta; break;
            }
        }
        $name = $matched ? $matched['name'] : ucwords(mb_substr($desc, 0, 40));
        $cat  = $matched ? $matched['cat']  : ($items[0]['category'] ?? 'Diğer');

        if (in_array(mb_strtolower($name, 'UTF-8'), $existingNames, true)) continue;

        $detected[] = [
            'name'         => $name,
            'category'     => $cat,
            'amount'       => round($mean, 2),
            'occurrences'  => count($items),
            'last_seen'    => $items[0]['transaction_date'],
            'sample_desc'  => $items[0]['description'],
        ];
    }

    usort($detected, fn($a, $b) => $b['occurrences'] <=> $a['occurrences']);
    json_response(['success' => true, 'detected' => array_slice($detected, 0, 20)]);
}

if ($method === 'POST') {
    csrf_require();
    rate_limit('sub_detect_add', 60, 3600);

    $name     = sanitize($_POST['name'] ?? '', 'text', ['max_length' => 100]);
    $amount   = sanitize($_POST['amount'] ?? 0, 'float', ['min' => 0.01]);
    $category = sanitize($_POST['category'] ?? 'Diğer', 'text', ['max_length' => 50]);
    $cycle    = in_array($_POST['billing_cycle'] ?? 'monthly', ['monthly','yearly'], true)
        ? $_POST['billing_cycle'] : 'monthly';

    if ($name === '' || $amount <= 0) {
        json_response(['success' => false, 'message' => 'Eksik bilgi.'], 422);
    }

    $next = $cycle === 'yearly'
        ? date('Y-m-d', strtotime('+1 year'))
        : date('Y-m-d', strtotime('+1 month'));

    $stmt = $pdo->prepare(
        "INSERT INTO subscriptions (user_id, name, amount, billing_cycle, category, next_billing, is_active)
         VALUES (?, ?, ?, ?, ?, ?, 1)"
    );
    $stmt->execute([$user_id, $name, $amount, $cycle, $category, $next]);
    json_response(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
}

json_response(['success' => false, 'message' => 'Invalid method'], 405);
