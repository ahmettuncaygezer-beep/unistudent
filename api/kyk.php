<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_system.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');
$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Giriş gerekli']); exit; }
$uid = (int)$_SESSION['user_id'];

// KYK resmi takvim (public) - 2026 tahmini yatış tarihleri (her ayın 6'sı, dönem ücreti dahil)
$calendar = [];
for ($m = 1; $m <= 12; $m++) {
    $calendar[] = [
        'month' => $m,
        'name'  => ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'][$m-1],
        'date'  => sprintf('2026-%02d-06', $m),
        'note'  => in_array($m, [1, 7]) ? 'Dönem başı + ek destek' : 'Aylık yatış'
    ];
}

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    csrf_require();
    $action = $_POST['action'] ?? 'save';
    if ($action === 'save') {
        $amount = (float)($_POST['monthly_amount'] ?? 0);
        $start  = trim($_POST['start_date'] ?? '');
        $study  = (int)($_POST['study_months'] ?? 48);
        $grace  = (int)($_POST['grace_months'] ?? 24);
        $notes  = substr(trim($_POST['notes'] ?? ''), 0, 500);

        // Doğrulama
        if ($amount < 0 || $amount > 100000) {
            echo json_encode(['success'=>false,'message'=>'Geçersiz aylık tutar.']); exit;
        }
        if ($study < 1 || $study > 120) {
            echo json_encode(['success'=>false,'message'=>'Öğretim süresi 1-120 ay arasında olmalıdır.']); exit;
        }
        if ($grace < 0 || $grace > 48) {
            echo json_encode(['success'=>false,'message'=>'Geri ödeme muafiyet süresi 0-48 ay arasında olmalıdır.']); exit;
        }
        if ($start !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
            echo json_encode(['success'=>false,'message'=>'Geçersiz tarih formatı (YYYY-MM-DD bekleniyor).']); exit;
        }
        // Başlangıç tarihi çok uzak gelecek olamaz (10 yıldan fazla)
        if ($start !== '') {
            $startTs = strtotime($start);
            if ($startTs === false || $startTs > strtotime('+10 years')) {
                echo json_encode(['success'=>false,'message'=>'Başlangıç tarihi geçersiz.']); exit;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO user_kyk (user_id, monthly_amount, start_date, study_months, grace_months, notes)
            VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE monthly_amount=VALUES(monthly_amount), start_date=VALUES(start_date),
            study_months=VALUES(study_months), grace_months=VALUES(grace_months), notes=VALUES(notes)");
        $stmt->execute([$uid, $amount, $start ?: null, $study, $grace, $notes]);
        echo json_encode(['success'=>true]); exit;
    }
    echo json_encode(['success'=>false,'message'=>'Bilinmeyen işlem']); exit;
}


// GET - kullanıcı verisi + hesaplama
$row = $pdo->prepare("SELECT * FROM user_kyk WHERE user_id = ?");
$row->execute([$uid]);
$data = $row->fetch() ?: null;

$simulation = null;
if ($data && $data['monthly_amount'] > 0 && $data['start_date']) {
    $monthly   = (float)$data['monthly_amount'];
    $studyM    = (int)$data['study_months'];
    $graceM    = (int)$data['grace_months'];
    $totalDebt = $monthly * $studyM; // faizsiz ana para (KYK)
    // Yİ-ÜFE ile endekslenir; kullanıcı kendi tahmini girebilir, varsayılan %25
    $yiUfePct = isset($_GET['yi_ufe_pct']) ? (float)$_GET['yi_ufe_pct'] : 25;
    $yiUfePct = max(0, min(200, $yiUfePct)); // 0-200% arası sınırla
    $yiUfeAnnual = $yiUfePct / 100;
    $endexedDebt = $totalDebt * pow(1 + $yiUfeAnnual, $studyM / 12);
    $repayMonths = $studyM * 2; // genelde öğretim süresinin iki katı
    $monthlyPay  = round($endexedDebt / $repayMonths, 2);

    $startTs = strtotime($data['start_date']);
    $graduationTs = strtotime("+{$studyM} months", $startTs);
    $repayStartTs = strtotime("+{$graceM} months", $graduationTs);

    $simulation = [
        'total_received'       => $totalDebt,
        'endexed_debt_est'     => round($endexedDebt, 2),
        'repay_months'         => $repayMonths,
        'monthly_payment_est'  => $monthlyPay,
        'graduation_date'      => date('Y-m-d', $graduationTs),
        'repayment_start_date' => date('Y-m-d', $repayStartTs),
        'repayment_end_date'   => date('Y-m-d', strtotime("+{$repayMonths} months", $repayStartTs)),
        'yi_ufe_assumed'       => $yiUfeAnnual,
    ];
}

echo json_encode([
    'success'    => true,
    'data'       => $data,
    'simulation' => $simulation,
    'calendar'   => $calendar,
]);
