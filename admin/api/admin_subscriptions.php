<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_check.php';
check_login();
require_once __DIR__ . '/database.php';

$db = getDB(); $action = $_GET['action'] ?? '';
$out = ['success'=>false,'message'=>'Bilinmeyen aksiyon'];
try {
    switch($action) {
        case 'list':
            $limit  = max(1, min(200, (int)($_GET['limit'] ?? 30)));
            $offset = max(0, (int)($_GET['offset'] ?? 0));
            $status = $_GET['status'] ?? 'all';
            $search = trim($_GET['search'] ?? '');

            $where=[]; $params=[];
            if($status !== 'all'){ $where[]='s.is_active=?'; $params[]=(int)$status; }
            if($search){ $where[]='(s.name LIKE ? OR u.full_name LIKE ?)'; $params[]="%$search%"; $params[]="%$search%"; }
            $ws = $where ? 'WHERE '.implode(' AND ',$where) : '';

            $stCount = $db->prepare("SELECT COUNT(*) FROM subscriptions s LEFT JOIN users u ON u.id=s.user_id $ws");
            $stCount->execute($params);
            $total = (int)$stCount->fetchColumn();

            $st = $db->prepare("SELECT s.*,u.full_name,u.email FROM subscriptions s LEFT JOIN users u ON u.id=s.user_id $ws ORDER BY s.created_at DESC LIMIT $limit OFFSET $offset");
            $st->execute($params);
            $out = ['success'=>true,'total'=>$total,'subscriptions'=>$st->fetchAll(PDO::FETCH_ASSOC)];
            break;
    }
} catch(Exception $e){ $out=['success'=>false,'message'=>$e->getMessage()]; }
echo json_encode($out);
