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
            $rows = $db->query("SELECT d.*, (SELECT COUNT(*) FROM user_discount_claims c WHERE c.discount_id=d.id) as claim_count FROM student_discounts d ORDER BY d.category, d.brand")->fetchAll(PDO::FETCH_ASSOC);
            $out = ['success'=>true,'discounts'=>$rows];
            break;
        case 'create':
        case 'update':
            $fields = ['brand','category','discount_text','monthly_saving','url','how_to','icon','region'];
            $data = [];
            foreach($fields as $f) $data[$f] = $_POST[$f] ?? '';
            $data['monthly_saving'] = (float)$data['monthly_saving'];
            $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;
            if($action==='create'){
                $db->prepare("INSERT INTO student_discounts (brand,category,discount_text,monthly_saving,url,how_to,icon,region,is_active) VALUES (?,?,?,?,?,?,?,?,?)")
                   ->execute([$data['brand'],$data['category'],$data['discount_text'],$data['monthly_saving'],$data['url'],$data['how_to'],$data['icon'],$data['region'],$data['is_active']]);
                $out=['success'=>true,'message'=>'İndirim eklendi'];
            } else {
                $id=(int)($_POST['id']??0); if(!$id) throw new Exception('Geçersiz ID');
                $db->prepare("UPDATE student_discounts SET brand=?,category=?,discount_text=?,monthly_saving=?,url=?,how_to=?,icon=?,region=?,is_active=? WHERE id=?")
                   ->execute([$data['brand'],$data['category'],$data['discount_text'],$data['monthly_saving'],$data['url'],$data['how_to'],$data['icon'],$data['region'],$data['is_active'],$id]);
                $out=['success'=>true,'message'=>'İndirim güncellendi'];
            }
            break;
        case 'delete':
            $id=(int)($_POST['id']??0); if(!$id) throw new Exception('Geçersiz ID');
            $db->prepare("DELETE FROM student_discounts WHERE id=?")->execute([$id]);
            $out=['success'=>true,'message'=>'İndirim silindi'];
            break;
        case 'toggle':
            $id=(int)($_POST['id']??0);
            $db->prepare("UPDATE student_discounts SET is_active=1-is_active WHERE id=?")->execute([$id]);
            $out=['success'=>true,'message'=>'Durum güncellendi'];
            break;
    }
} catch(Exception $e){ $out=['success'=>false,'message'=>$e->getMessage()]; }
echo json_encode($out);
