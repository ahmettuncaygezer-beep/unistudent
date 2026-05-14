<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_check.php';
check_login();
require_once __DIR__ . '/database.php';

$db = getDB(); $action = $_POST['action'] ?? $_GET['action'] ?? '';
$out = ['success'=>false,'message'=>'Bilinmeyen aksiyon'];
try {
    $db->query("CREATE TABLE IF NOT EXISTS admin_audit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        action VARCHAR(100), target_type VARCHAR(50),
        target_id INT, detail TEXT,
        ip_address VARCHAR(45),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    switch($action) {
        case 'list_admin_log':
            $limit  = min(200,(int)($_GET['limit']??30));
            $offset = max(0,(int)($_GET['offset']??0));
            $search = trim($_GET['search']??'');
            $df = $_GET['date_from']??'';
            $dt = $_GET['date_to']??'';
            $where=[]; $params=[];
            if($search){ $where[]='(action LIKE ? OR detail LIKE ?)'; $params[]="%$search%"; $params[]="%$search%"; }
            if($df){ $where[]='DATE(created_at)>=?'; $params[]=$df; }
            if($dt){ $where[]='DATE(created_at)<=?'; $params[]=$dt; }
            $ws=$where?'WHERE '.implode(' AND ',$where):'';
            $stC=$db->prepare("SELECT COUNT(*) FROM admin_audit_log $ws"); $stC->execute($params);
            $total=(int)$stC->fetchColumn();
            $st=$db->prepare("SELECT * FROM admin_audit_log $ws ORDER BY id DESC LIMIT $limit OFFSET $offset");
            $st->execute($params);
            $out=['success'=>true,'rows'=>$st->fetchAll(PDO::FETCH_ASSOC),'total'=>$total];
            break;

        case 'add':
            $a  = $_POST['action2']  ?? $_POST['admin_action'] ?? '';
            $tt = $_POST['target_type']??'';
            $ti = (int)($_POST['target_id']??0);
            $d  = $_POST['detail']??'';
            $ip = $_SERVER['REMOTE_ADDR']??'';
            $db->prepare("INSERT INTO admin_audit_log(action,target_type,target_id,detail,ip_address) VALUES(?,?,?,?,?)")->execute([$a,$tt,$ti,$d,$ip]);
            $out=['success'=>true];
            break;

        case 'clear':
            $db->query("DELETE FROM admin_audit_log");
            $out=['success'=>true,'message'=>'Log temizlendi'];
            break;
    }
} catch(Exception $e){ $out=['success'=>false,'message'=>$e->getMessage()]; }
echo json_encode($out);
