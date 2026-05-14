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
$method  = $_SERVER['REQUEST_METHOD'];
$action  = $_GET['action'] ?? $_POST['action'] ?? 'list';

if ($method === 'GET' && $action === 'list') {
    $stmt = $pdo->prepare(
        "SELECT b.id, b.name, b.invite_code, b.owner_id, b.created_at,
                (SELECT COUNT(*) FROM shared_budget_members m WHERE m.budget_id = b.id) AS member_count
           FROM shared_budgets b
           LEFT JOIN shared_budget_members m ON m.budget_id = b.id
          WHERE b.owner_id = ? OR m.user_id = ?
          GROUP BY b.id"
    );
    $stmt->execute([$user_id, $user_id]);
    json_response(['success' => true, 'budgets' => $stmt->fetchAll()]);
}

if ($method === 'GET' && $action === 'detail') {
    $bid = (int)($_GET['id'] ?? 0);
    // Membership check
    $mem = $pdo->prepare(
        "SELECT 1 FROM shared_budgets b
         LEFT JOIN shared_budget_members m ON m.budget_id = b.id
         WHERE b.id = ? AND (b.owner_id = ? OR m.user_id = ?) LIMIT 1"
    );
    $mem->execute([$bid, $user_id, $user_id]);
    if (!$mem->fetch()) json_response(['success' => false, 'message' => 'Erişim yok.'], 403);

    // Expenses
    $exp = $pdo->prepare(
        "SELECT e.*, u.full_name AS payer_name
           FROM shared_expenses e
           JOIN users u ON e.payer_id = u.id
          WHERE e.budget_id = ?
          ORDER BY e.created_at DESC LIMIT 200"
    );
    $exp->execute([$bid]);
    $expenses = $exp->fetchAll();

    // Members
    $mems = $pdo->prepare(
        "SELECT u.id, u.full_name, m.role
           FROM shared_budget_members m JOIN users u ON u.id = m.user_id
          WHERE m.budget_id = ?"
    );
    $mems->execute([$bid]);
    $members = $mems->fetchAll();

    // Add owner
    $o = $pdo->prepare("SELECT u.id, u.full_name FROM shared_budgets b JOIN users u ON u.id = b.owner_id WHERE b.id = ?");
    $o->execute([$bid]);
    $owner = $o->fetch();

    $settlement = calc_settlement($expenses, $members, $owner);
    json_response([
        'success'    => true,
        'expenses'   => $expenses,
        'members'    => $members,
        'owner'      => $owner,
        'settlement' => $settlement,
    ]);
}

if ($method !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid method'], 405);
}

csrf_require();

if ($action === 'create') {
    $name = sanitize($_POST['name'] ?? 'Ev Bütçesi', 'text', ['max_length' => 120]);
    $code = bin2hex(random_bytes(5));
    $pdo->prepare("INSERT INTO shared_budgets (owner_id, name, invite_code) VALUES (?, ?, ?)")
        ->execute([$user_id, $name, $code]);
    $bid = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO shared_budget_members (budget_id, user_id, role) VALUES (?, ?, 'owner')")
        ->execute([$bid, $user_id]);
    json_response(['success' => true, 'id' => $bid, 'invite_code' => $code]);
}

if ($action === 'join') {
    rate_limit('shared_join', 10, 3600);
    $code = sanitize($_POST['invite_code'] ?? '', 'string', ['max_length' => 24]);
    $b = $pdo->prepare("SELECT id FROM shared_budgets WHERE invite_code = ?");
    $b->execute([$code]);
    $row = $b->fetch();
    if (!$row) json_response(['success' => false, 'message' => 'Geçersiz davet kodu.'], 404);
    $pdo->prepare("INSERT IGNORE INTO shared_budget_members (budget_id, user_id, role) VALUES (?, ?, 'editor')")
        ->execute([$row['id'], $user_id]);
    json_response(['success' => true, 'id' => (int)$row['id']]);
}

if ($action === 'add_expense') {
    $bid      = (int)($_POST['budget_id'] ?? 0);
    $amount   = sanitize($_POST['amount'] ?? 0, 'float', ['min' => 0.01]);
    $category = sanitize($_POST['category'] ?? 'Genel', 'text', ['max_length' => 100]);
    $desc     = sanitize($_POST['description'] ?? '', 'text', ['max_length' => 255]);
    $split    = $_POST['split_with'] ?? '[]';
    if (is_string($split)) $split = json_decode($split, true) ?: [];

    // Auth check
    $m = $pdo->prepare(
        "SELECT 1 FROM shared_budgets b LEFT JOIN shared_budget_members m ON m.budget_id=b.id
         WHERE b.id=? AND (b.owner_id=? OR m.user_id=?) LIMIT 1"
    );
    $m->execute([$bid, $user_id, $user_id]);
    if (!$m->fetch()) json_response(['success' => false, 'message' => 'Erişim yok.'], 403);

    $stmt = $pdo->prepare(
        "INSERT INTO shared_expenses (budget_id, payer_id, amount, category, description, split_with)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$bid, $user_id, $amount, $category, $desc, json_encode($split)]);
    json_response(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
}

json_response(['success' => false, 'message' => 'Unknown action'], 400);

/**
 * Splitwise-style greedy settlement algoritması.
 * Her kullanıcı için net bakiye hesapla → büyük borçlu & büyük alacaklı eşleştir.
 */
function calc_settlement(array $expenses, array $members, ?array $owner): array {
    $balance = [];
    $userMap = [];
    foreach ($members as $m) {
        $balance[$m['id']] = 0;
        $userMap[$m['id']] = $m['full_name'];
    }
    if ($owner) {
        $balance[$owner['id']] = $balance[$owner['id']] ?? 0;
        $userMap[$owner['id']] = $owner['full_name'];
    }

    foreach ($expenses as $e) {
        $split = json_decode($e['split_with'] ?: '[]', true) ?: [];
        if (!$split) $split = array_keys($userMap);
        $share = (float)$e['amount'] / max(count($split), 1);
        $balance[$e['payer_id']] = ($balance[$e['payer_id']] ?? 0) + (float)$e['amount'];
        foreach ($split as $uid) {
            $balance[$uid] = ($balance[$uid] ?? 0) - $share;
        }
    }

    // Greedy settlement
    $creditors = []; $debtors = [];
    foreach ($balance as $uid => $amt) {
        if ($amt > 0.01)      $creditors[] = ['user_id' => $uid, 'amount' => $amt];
        elseif ($amt < -0.01) $debtors[]   = ['user_id' => $uid, 'amount' => -$amt];
    }
    usort($creditors, fn($a, $b) => $b['amount'] <=> $a['amount']);
    usort($debtors,   fn($a, $b) => $b['amount'] <=> $a['amount']);

    $transfers = [];
    $i = $j = 0;
    while ($i < count($debtors) && $j < count($creditors)) {
        $pay = min($debtors[$i]['amount'], $creditors[$j]['amount']);
        $transfers[] = [
            'from'        => $debtors[$i]['user_id'],
            'from_name'   => $userMap[$debtors[$i]['user_id']] ?? '?',
            'to'          => $creditors[$j]['user_id'],
            'to_name'     => $userMap[$creditors[$j]['user_id']] ?? '?',
            'amount'      => round($pay, 2),
        ];
        $debtors[$i]['amount']   -= $pay;
        $creditors[$j]['amount'] -= $pay;
        if ($debtors[$i]['amount']   < 0.01) $i++;
        if ($creditors[$j]['amount'] < 0.01) $j++;
    }

    return [
        'balances'  => array_map(fn($uid, $amt) => [
            'user_id' => $uid, 'name' => $userMap[$uid] ?? '?', 'balance' => round($amt, 2)
        ], array_keys($balance), $balance),
        'transfers' => $transfers,
    ];
}
