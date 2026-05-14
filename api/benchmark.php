<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_system.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');
$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) { http_response_code(401); echo json_encode(['success'=>false]); exit; }
$uid = (int)$_SESSION['user_id'];

// Kullanıcıyı anonimleştir: user_id + sabit tuz
$SALT = env('APP_SECRET', 'unibutce-2026');
$userHash = hash('sha256', $uid . '|' . $SALT);

// User settings / profile - city + age band
$s = $pdo->prepare("SELECT u.university_name, us.dashboard_json FROM users u LEFT JOIN user_settings us ON us.user_id=u.id WHERE u.id=?");
$s->execute([$uid]);
$prof = $s->fetch() ?: [];
$city = 'Diğer';
if (!empty($prof['dashboard_json'])) {
    $dj = json_decode($prof['dashboard_json'], true);
    if (!empty($dj['city'])) $city = $dj['city'];
}
$ageBand = '18-24'; // default

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = $_POST['action'] ?? 'submit_month';
    if ($action === 'submit_month') {
        // Son ayın harcamalarını kategori bazlı topla ve benchmark_submissions'a yaz
        $ym = date('Y-m');
        $q = $pdo->prepare("SELECT category, SUM(amount) total FROM transactions
            WHERE user_id=? AND type='expense' AND DATE_FORMAT(transaction_date, '%Y-%m')=?
            GROUP BY category");
        $q->execute([$uid, $ym]);
        $ins = $pdo->prepare("INSERT INTO benchmark_submissions (user_hash, city, age_band, category, amount, period_ym)
            VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE amount=VALUES(amount)");
        $count = 0;
        foreach ($q as $row) {
            $ins->execute([$userHash, $city, $ageBand, $row['category'], (float)$row['total'], $ym]);
            $count++;
        }
        echo json_encode(['success'=>true, 'submitted'=>$count, 'period'=>$ym]); exit;
    }
    echo json_encode(['success'=>false,'message'=>'?']); exit;
}

// GET — benchmark karşılaştırma
$ym = date('Y-m');
$mine = [];
$q = $pdo->prepare("SELECT category, SUM(amount) t FROM transactions WHERE user_id=? AND type='expense' AND DATE_FORMAT(transaction_date,'%Y-%m')=? GROUP BY category");
$q->execute([$uid, $ym]);
foreach ($q as $r) $mine[$r['category']] = (float)$r['t'];

$results = [];
foreach ($mine as $cat => $myVal) {
    // Tüm şehir + yaş (min 3 kayıt şart — anonimlik)
    $all = $pdo->prepare("SELECT AVG(amount) avg, COUNT(*) n FROM benchmark_submissions WHERE category=? AND period_ym=?");
    $all->execute([$cat, $ym]);
    $allRow = $all->fetch();

    $same = $pdo->prepare("SELECT AVG(amount) avg, COUNT(*) n FROM benchmark_submissions WHERE category=? AND period_ym=? AND city=? AND age_band=?");
    $same->execute([$cat, $ym, $city, $ageBand]);
    $sameRow = $same->fetch();

    $results[] = [
        'category' => $cat,
        'mine' => $myVal,
        'peer_avg_same_city' => (int)$sameRow['n'] >= 3 ? round((float)$sameRow['avg'], 2) : null,
        'peer_count_same'    => (int)$sameRow['n'],
        'peer_avg_all'       => (int)$allRow['n'] >= 3 ? round((float)$allRow['avg'], 2) : null,
        'peer_count_all'     => (int)$allRow['n'],
    ];
}

echo json_encode([
    'success' => true,
    'your_city' => $city,
    'age_band' => $ageBand,
    'period' => $ym,
    'results' => $results,
]);
