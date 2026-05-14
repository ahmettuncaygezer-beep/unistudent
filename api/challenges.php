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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = $_POST['action'] ?? '';
    if ($action === 'start') {
        $cid = (int)$_POST['challenge_id'];

        // Prevent duplicates
        $chk = $pdo->prepare("SELECT id FROM user_challenges WHERE user_id=? AND challenge_id=? AND status='active'");
        $chk->execute([$uid, $cid]);
        if ($chk->fetch()) { echo json_encode(['success'=>false, 'message'=>'Bu meydan okuma zaten aktif!']); exit; }

        $c = $pdo->prepare("SELECT * FROM challenges WHERE id=? AND is_active=1");
        $c->execute([$cid]); $ch = $c->fetch();
        if (!$ch) { echo json_encode(['success'=>false,'message'=>'Challenge bulunamadı']); exit; }
        $start = date('Y-m-d');
        $end   = date('Y-m-d', strtotime("+{$ch['duration_days']} days"));
        $s = $pdo->prepare("INSERT INTO user_challenges (user_id, challenge_id, start_date, end_date) VALUES (?,?,?,?)");
        $s->execute([$uid, $cid, $start, $end]);
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'abandon') {
        $stmt = $pdo->prepare("UPDATE user_challenges SET status='abandoned' WHERE id=? AND user_id=? AND status='active'");
        $stmt->execute([(int)$_POST['id'], $uid]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success'=>true]);
        } else {
            echo json_encode(['success'=>false, 'message'=>'İptal edilecek aktif kayıt bulunamadı.']);
        }
        exit;
    }
    if ($action === 'check_progress') {
        // Aktif challenge'ları değerlendir: kategori bloğu harcaması = 0 mı?
        $act = $pdo->prepare("SELECT uc.*, c.target_savings, c.xp_reward FROM user_challenges uc
            JOIN challenges c ON c.id=uc.challenge_id
            WHERE uc.user_id=? AND uc.status='active'");
        $act->execute([$uid]);
        $completed = 0; $failed = 0;
        foreach ($act as $a) {
            $cat = $a['category_block'];
            $q = $pdo->prepare("SELECT COALESCE(SUM(amount),0) t FROM transactions
                WHERE user_id=? AND type='expense' AND category=? AND transaction_date BETWEEN ? AND ?");
            $q->execute([$uid, $cat, $a['start_date'], $a['end_date'] . ' 23:59:59']);
            $spent = (float)$q->fetchColumn();
            if (date('Y-m-d') > $a['end_date']) {
                if ($spent == 0.0) {
                    $pdo->prepare("UPDATE user_challenges SET status='completed', saved_amount=? WHERE id=?")
                        ->execute([$a['target_savings'], $a['id']]);
                    $pdo->prepare("UPDATE users SET xp_points = xp_points + ? WHERE id=?")
                        ->execute([$a['xp_reward'], $uid]);
                    $completed++;
                } else {
                    $pdo->prepare("UPDATE user_challenges SET status='failed' WHERE id=?")->execute([$a['id']]);
                    $failed++;
                }
            }
        }
        echo json_encode(['success'=>true, 'completed'=>$completed, 'failed'=>$failed]); exit;
    }
}

// GET — hepsi + kullanıcının aktif durumu
$all = $pdo->query("SELECT * FROM challenges WHERE is_active=1 ORDER BY duration_days")->fetchAll();
$s = $pdo->prepare("SELECT uc.*, c.title, c.icon, c.category_block FROM user_challenges uc
    JOIN challenges c ON c.id=uc.challenge_id WHERE uc.user_id=? ORDER BY uc.start_date DESC");
$s->execute([$uid]);
$mine = $s->fetchAll();

// Aktif challenge'lar için anlık ilerleme
foreach ($mine as &$m) {
    if ($m['status'] === 'active') {
        $q = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM transactions
            WHERE user_id=? AND type='expense' AND category=? AND transaction_date BETWEEN ? AND ?");
        $q->execute([$uid, $m['category_block'], $m['start_date'], $m['end_date'] . ' 23:59:59']);
        $m['spent_during'] = (float)$q->fetchColumn();
        $daysTotal = (int)((strtotime($m['end_date']) - strtotime($m['start_date']))/86400) ?: 1;
        $daysGone  = max(0, (int)((time() - strtotime($m['start_date']))/86400));
        $m['days_total'] = $daysTotal;
        $m['days_gone']  = min($daysTotal, $daysGone);
        $m['days_left']  = max(0, $daysTotal - $daysGone);
    }
}
unset($m);

echo json_encode(['success'=>true, 'all'=>$all, 'mine'=>$mine]);
