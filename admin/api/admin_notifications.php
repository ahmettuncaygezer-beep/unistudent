<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_check.php';
check_login();
require_once __DIR__ . '/database.php';

$db = getDB(); $action = $_POST['action'] ?? $_GET['action'] ?? '';
$out = ['success'=>false,'message'=>'Bilinmeyen aksiyon'];
try {
    switch($action) {
        case 'list':
            $limit  = min(200,(int)($_GET['limit']??30));
            $offset = max(0,(int)($_GET['offset']??0));
            $search = trim($_GET['search']??'');
            $where=[]; $params=[];
            if($search){ $where[]='(n.title LIKE ? OR n.message LIKE ?)'; $params[]="%$search%"; $params[]="%$search%"; }
            $ws=$where?'WHERE '.implode(' AND ',$where):'';
            $stC=$db->prepare("SELECT COUNT(*) FROM notifications n $ws"); $stC->execute($params);
            $total=(int)$stC->fetchColumn();
            $st=$db->prepare("SELECT n.*, u.full_name FROM notifications n LEFT JOIN users u ON u.id=n.user_id $ws ORDER BY n.id DESC LIMIT $limit OFFSET $offset");
            $st->execute($params);
            $out=['success'=>true,'rows'=>$st->fetchAll(PDO::FETCH_ASSOC),'total'=>$total];
            break;

        case 'delete':
            $id=(int)($_POST['id']??0);
            $db->prepare("DELETE FROM notifications WHERE id=?")->execute([$id]);
            $out=['success'=>true,'message'=>'Bildirim silindi'];
            break;

        case 'mark_all_read':
            $db->query("UPDATE notifications SET is_read=1");
            $out=['success'=>true,'message'=>'Tümü okundu işaretlendi'];
            break;

        case 'clear_all':
            $db->query("DELETE FROM notifications");
            $out=['success'=>true,'message'=>'Tüm bildirimler silindi'];
            break;
    }
} catch(Exception $e){ $out=['success'=>false,'message'=>$e->getMessage()]; }
echo json_encode($out);
