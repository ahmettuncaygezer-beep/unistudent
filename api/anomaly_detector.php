<?php
declare(strict_types=1);
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_system.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/rate_limit.php';

header('Content-Type: application/json; charset=utf-8');

$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) {
    json_response(['success' => false, 'message' => 'Oturum yok.'], 401);
}

$user_id = (int)$_SESSION['user_id'];
$method  = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Return last 20 unacknowledged anomalies
    $stmt = $pdo->prepare(
        "SELECT id, transaction_id, category, amount, zscore, reason, created_at
         FROM user_anomalies
         WHERE user_id = ? AND is_dismissed = 0
         ORDER BY created_at DESC LIMIT 20"
    );
    $stmt->execute([$user_id]);
    $anomalies = $stmt->fetchAll();

    // Auto-scan: son taramadan 1 saat geçtiyse otomatik tara
    $lastScan = $pdo->prepare("SELECT MAX(created_at) FROM user_anomalies WHERE user_id = ?");
    $lastScan->execute([$user_id]);
    $lastScanTime = $lastScan->fetchColumn();
    $shouldScan = !$lastScanTime || (time() - strtotime($lastScanTime)) > 3600;

    json_response(['success' => true, 'anomalies' => $anomalies, 'auto_scan_pending' => $shouldScan]);
}


if ($method === 'POST') {
    rate_limit('anomaly_scan', 12, 3600); // saatte 12 tarama
    $action = $_POST['action'] ?? 'scan';

    if ($action === 'dismiss') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE user_anomalies SET is_dismissed = 1 WHERE id = ? AND user_id = ?")
            ->execute([$id, $user_id]);
        json_response(['success' => true]);
    }

    // Scan: compute z-score per category for last 7 days vs prior 90 days
    $rows = $pdo->prepare(
        "SELECT category, amount, transaction_date, id
         FROM transactions
         WHERE user_id = ? AND type = 'expense'
           AND transaction_date >= DATE_SUB(NOW(), INTERVAL 97 DAY)
         ORDER BY transaction_date DESC"
    );
    $rows->execute([$user_id]);
    $all = $rows->fetchAll();

    $byCat = [];
    foreach ($all as $r) {
        $byCat[$r['category']][] = $r;
    }

    $flagged = [];
    $cutoff = strtotime('-7 days');

    foreach ($byCat as $cat => $items) {
        $hist = array_filter($items, fn($x) => strtotime($x['transaction_date']) < $cutoff);
        $recent = array_filter($items, fn($x) => strtotime($x['transaction_date']) >= $cutoff);
        if (count($hist) < 5) continue; // yetersiz veri

        $amounts = array_map(fn($x) => (float)$x['amount'], $hist);
        $mean = array_sum($amounts) / count($amounts);
        $var  = array_sum(array_map(fn($a) => ($a - $mean) ** 2, $amounts)) / count($amounts);
        $std  = $var > 0 ? sqrt($var) : 1;

        foreach ($recent as $r) {
            $z = ($r['amount'] - $mean) / $std;
            if ($z > 2.2) {
                $flagged[] = [
                    'tx_id'    => (int)$r['id'],
                    'category' => $cat,
                    'amount'   => (float)$r['amount'],
                    'zscore'   => round($z, 2),
                    'reason'   => sprintf(
                        '"%s" kategorisinde ortalama %s₺ iken %s₺ harcandı (%.1f× normal).',
                        $cat, number_format($mean, 0), number_format($r['amount'], 0), $r['amount'] / max($mean, 1)
                    ),
                ];
            }
        }
    }

    // Persist (deduplicated by tx_id)
    $ins = $pdo->prepare(
        "INSERT IGNORE INTO user_anomalies (user_id, transaction_id, category, amount, zscore, reason)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    foreach ($flagged as $f) {
        $ins->execute([$user_id, $f['tx_id'], $f['category'], $f['amount'], $f['zscore'], $f['reason']]);
    }

    json_response([
        'success'  => true,
        'scanned'  => count($all),
        'flagged'  => count($flagged),
        'anomalies' => $flagged,
    ]);
}

json_response(['success' => false, 'message' => 'Invalid method'], 405);
