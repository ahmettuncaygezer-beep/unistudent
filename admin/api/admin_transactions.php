<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_check.php';
check_login();
require_once __DIR__ . '/database.php';

$db     = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$out    = ['success' => false, 'message' => 'Bilinmeyen aksiyon'];

try {
    switch ($action) {
        case 'list':
            $limit  = max(1, min(200, (int)($_GET['limit'] ?? 50)));
            $offset = max(0, (int)($_GET['offset'] ?? 0));
            $type     = $_GET['type']     ?? '';
            $cat      = $_GET['category'] ?? $_GET['cat'] ?? '';
            $search   = trim($_GET['search'] ?? '');
            $dateFrom = $_GET['date_from'] ?? '';
            $dateTo   = $_GET['date_to']   ?? '';

            $where = []; $params = [];
            if ($type && $type !== 'all') { $where[] = 't.type=?'; $params[] = $type; }
            if ($cat  && $cat  !== 'all') { $where[] = 't.category=?'; $params[] = $cat; }
            if ($search) { $where[] = '(t.category LIKE ? OR t.description LIKE ? OR u.full_name LIKE ?)'; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
            if ($dateFrom) { $where[] = 'DATE(t.transaction_date)>=?'; $params[] = $dateFrom; }
            if ($dateTo)   { $where[] = 'DATE(t.transaction_date)<=?'; $params[] = $dateTo; }
            $ws = $where ? 'WHERE '.implode(' AND ',$where) : '';

            $stCount = $db->prepare("SELECT COUNT(*) FROM transactions t LEFT JOIN users u ON u.id=t.user_id $ws");
            $stCount->execute($params);
            $total = (int)$stCount->fetchColumn();

            $st = $db->prepare("
                SELECT t.id, t.type, t.amount, t.category, t.description, t.transaction_date,
                       u.full_name, u.email, u.id AS user_id
                FROM transactions t
                LEFT JOIN users u ON u.id=t.user_id
                $ws
                ORDER BY t.transaction_date DESC
                LIMIT $limit OFFSET $offset");
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            // Return both 'rows' and legacy 'transactions' key for compatibility
            $out = ['success'=>true, 'total'=>$total, 'rows'=>$rows, 'transactions'=>$rows];
            break;

        case 'summary':
            $income = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='income'")->fetchColumn();
            $expense= (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='expense'")->fetchColumn();
            $cats   = $db->query("SELECT category, COUNT(*) as cnt FROM transactions GROUP BY category ORDER BY cnt DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            $out = ['success'=>true,'income'=>$income,'expense'=>$expense,'net'=>$income-$expense,'top_cats'=>$cats];
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Geçersiz ID');
            $db->prepare("DELETE FROM transactions WHERE id=?")->execute([$id]);
            $out = ['success'=>true,'message'=>'İşlem silindi'];
            break;
    }
} catch(Exception $e) { $out=['success'=>false,'message'=>$e->getMessage()]; }
echo json_encode($out);
