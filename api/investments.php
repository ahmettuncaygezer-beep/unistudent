<?php
declare(strict_types=1);
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_system.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/market_data.php';

header('Content-Type: application/json; charset=utf-8');

$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) {
    json_response(['success' => false, 'message' => 'Oturum yok.'], 401);
}
$user_id = (int)$_SESSION['user_id'];
$method  = $_SERVER['REQUEST_METHOD'];

// Market data instance (cached — safe to construct on every request)
$market = new MarketData();

// ─── GET: Portfolio data ───
if ($method === 'GET') {
    $action = $_GET['action'] ?? 'portfolio';

    // Watchlist
    if ($action === 'watchlist') {
        $stmt = $pdo->prepare("SELECT * FROM user_watchlist WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        $items = $stmt->fetchAll();
        $tickers = array_map(fn($w) => strtoupper($w['ticker']), $items);
        $prices = $market->getPrices($tickers);
        foreach ($items as &$w) {
            $key = strtoupper($w['ticker']);
            $p = $prices[$key] ?? null;
            $w['current_price'] = $p ? $p['price'] : null;
            $w['change_pct'] = $p ? $p['change_pct'] : 0;
            $w['source'] = $p ? ($p['source'] ?? 'unknown') : 'none';
        }
        json_response(['success' => true, 'watchlist' => $items]);
    }

    // Market overview — top gainers/losers
    if ($action === 'market') {
        $allPrices = $market->getAllPrices();
        $top_gainers = $top_losers = [];
        foreach ($allPrices as $ticker => $data) {
            $item = [
                'ticker'     => $ticker,
                'price'      => $data['price'],
                'change_pct' => $data['change_pct'],
                'type'       => $data['type'] ?? 'unknown',
                'source'     => $data['source'] ?? 'unknown',
            ];
            if ($data['change_pct'] > 0) $top_gainers[] = $item;
            elseif ($data['change_pct'] < 0) $top_losers[] = $item;
        }
        usort($top_gainers, fn($a, $b) => $b['change_pct'] <=> $a['change_pct']);
        usort($top_losers, fn($a, $b) => $a['change_pct'] <=> $b['change_pct']);
        json_response([
            'success' => true,
            'gainers' => array_slice($top_gainers, 0, 10),
            'losers'  => array_slice($top_losers, 0, 10),
            'updated' => date('H:i:s'),
        ]);
    }

    // All prices — for frontend price lookup
    if ($action === 'prices') {
        $allPrices = $market->getAllPrices();
        json_response([
            'success' => true,
            'prices'  => $allPrices,
            'updated' => date('H:i:s'),
        ]);
    }

    // Portfolio (default)
    $stmt = $pdo->prepare("SELECT * FROM user_investments WHERE user_id = ? ORDER BY buy_date DESC");
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll();

    // Get all tickers user has
    $userTickers = array_unique(array_map(fn($r) => strtoupper($r['ticker']), $rows));
    $prices = $market->getPrices($userTickers);

    $total = 0; $cost = 0;
    $byType = [];

    foreach ($rows as &$r) {
        $key = strtoupper($r['ticker']);
        $priceData = $prices[$key] ?? null;
        $cur = $priceData ? $priceData['price'] : (float)$r['buy_price'];
        $value = (float)$r['quantity'] * $cur;
        $invested = (float)$r['quantity'] * (float)$r['buy_price'];

        $r['current_price'] = $cur;
        $r['current_value'] = round($value, 2);
        $r['invested']      = round($invested, 2);
        $r['pnl']           = round($value - $invested, 2);
        $r['pnl_pct']       = $invested > 0 ? round((($value - $invested) / $invested) * 100, 2) : 0;
        $r['daily_change']  = $priceData ? $priceData['change_pct'] : 0;
        $r['price_source']  = $priceData ? ($priceData['source'] ?? 'fallback') : 'buy_price';

        $total += $value;
        $cost  += $invested;

        // Allocation by type
        $type = $r['asset_type'];
        if (!isset($byType[$type])) $byType[$type] = 0;
        $byType[$type] += $value;
    }

    // Convert allocation to percentages
    $allocation = [];
    foreach ($byType as $type => $val) {
        $allocation[] = [
            'type'  => $type,
            'value' => round($val, 2),
            'pct'   => $total > 0 ? round(($val / $total) * 100, 1) : 0,
        ];
    }
    usort($allocation, fn($a, $b) => $b['pct'] <=> $a['pct']);

    // Best/Worst performers
    $performers = array_values($rows);
    usort($performers, fn($a, $b) => $b['pnl_pct'] <=> $a['pnl_pct']);
    $best_performer = $performers[0] ?? null;
    $worst_performer = end($performers) ?: null;

    json_response([
        'success'    => true,
        'holdings'   => $rows,
        'totals'     => [
            'market_value' => round($total, 2),
            'cost_basis'   => round($cost, 2),
            'pnl'          => round($total - $cost, 2),
            'pnl_pct'      => $cost > 0 ? round((($total - $cost) / $cost) * 100, 2) : 0,
            'count'        => count($rows),
        ],
        'allocation' => $allocation,
        'best'       => $best_performer ? ['ticker' => $best_performer['ticker'], 'pnl_pct' => $best_performer['pnl_pct']] : null,
        'worst'      => $worst_performer ? ['ticker' => $worst_performer['ticker'], 'pnl_pct' => $worst_performer['pnl_pct']] : null,
        'updated'    => date('H:i:s'),
    ]);
}

if ($method !== 'POST') json_response(['success' => false, 'message' => 'Invalid'], 405);
csrf_require();

// Pro gate
require_pro($user_id);

$action = $_POST['action'] ?? 'create';

if ($action === 'create') {
    $ticker    = strtoupper(sanitize($_POST['ticker'] ?? '', 'string', ['max_length' => 24]));
    $type      = in_array($_POST['asset_type'] ?? 'bist', ['bist','forex','crypto','fund','gold'], true)
        ? $_POST['asset_type'] : 'bist';
    $quantity  = sanitize($_POST['quantity'] ?? 0, 'float');
    $buyPrice  = sanitize($_POST['buy_price'] ?? 0, 'float');
    $buyDate   = sanitize($_POST['buy_date'] ?? date('Y-m-d'), 'date');
    $notes     = sanitize($_POST['notes'] ?? '', 'text', ['max_length' => 255]);

    if ($ticker === '' || $quantity <= 0) {
        json_response(['success' => false, 'message' => 'Ticker ve miktar gerekli.'], 422);
    }
    $pdo->prepare(
        "INSERT INTO user_investments (user_id, ticker, asset_type, quantity, buy_price, buy_date, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    )->execute([$user_id, $ticker, $type, $quantity, $buyPrice, $buyDate ?: null, $notes]);
    audit_log($user_id, 'investment.add', ['ticker' => $ticker, 'type' => $type]);
    json_response(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM user_investments WHERE id=? AND user_id=?")->execute([$id, $user_id]);
    json_response(['success' => true]);
}

// Watchlist add
if ($action === 'watch') {
    $ticker = strtoupper(sanitize($_POST['ticker'] ?? '', 'string', ['max_length' => 24]));
    if ($ticker === '') json_response(['success' => false, 'message' => 'Ticker gerekli.'], 422);

    $check = $pdo->prepare("SELECT id FROM user_watchlist WHERE user_id=? AND ticker=?");
    $check->execute([$user_id, $ticker]);
    if ($check->fetch()) {
        json_response(['success' => false, 'message' => 'Zaten izleme listende.']);
    }

    $pdo->prepare("INSERT INTO user_watchlist (user_id, ticker, asset_type) VALUES (?, ?, ?)")
        ->execute([$user_id, $ticker, $_POST['asset_type'] ?? 'bist']);
    json_response(['success' => true]);
}

if ($action === 'unwatch') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM user_watchlist WHERE id=? AND user_id=?")->execute([$id, $user_id]);
    json_response(['success' => true]);
}

json_response(['success' => false, 'message' => 'Unknown action'], 400);
