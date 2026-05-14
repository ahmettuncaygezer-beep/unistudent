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
            $rows = $db->query("SELECT c.*, (SELECT COUNT(*) FROM user_challenges uc WHERE uc.challenge_id=c.id) as participant_count,
                (SELECT COUNT(*) FROM user_challenges uc WHERE uc.challenge_id=c.id AND uc.status='completed') as completed_count
                FROM challenges c ORDER BY c.id DESC")->fetchAll(PDO::FETCH_ASSOC);
            $out = ['success'=>true,'challenges'=>$rows];
            break;

        case 'create':
        case 'update':
            $fields = ['slug','title','description','icon','duration_days','target_savings','xp_reward','category_block'];
            $data = [];
            foreach($fields as $f) $data[$f] = $_POST[$f] ?? '';
            $data['duration_days']  = (int)$data['duration_days'];
            $data['target_savings'] = (float)$data['target_savings'];
            $data['xp_reward']      = (int)$data['xp_reward'];
            $data['is_active']      = isset($_POST['is_active']) ? 1 : 0;

            if($action === 'create') {
                $db->prepare("INSERT INTO challenges (slug,title,description,icon,duration_days,target_savings,xp_reward,category_block,is_active) VALUES (?,?,?,?,?,?,?,?,?)")
                   ->execute(array_values(array_merge(array_slice($data,0,8),[$data['is_active']])));
                $out = ['success'=>true,'message'=>'Challenge oluşturuldu'];
            } else {
                $id = (int)($_POST['id'] ?? 0);
                if(!$id) throw new Exception('Geçersiz ID');
                $db->prepare("UPDATE challenges SET slug=?,title=?,description=?,icon=?,duration_days=?,target_savings=?,xp_reward=?,category_block=?,is_active=? WHERE id=?")
                   ->execute([...array_values(array_slice($data,0,8)),$data['is_active'],$id]);
                $out = ['success'=>true,'message'=>'Challenge güncellendi'];
            }
            break;

        case 'delete':
            $id = (int)($_POST['id']??0);
            if(!$id) throw new Exception('Geçersiz ID');
            $db->prepare("DELETE FROM challenges WHERE id=?")->execute([$id]);
            $out = ['success'=>true,'message'=>'Challenge silindi'];
            break;

        case 'toggle':
            $id = (int)($_POST['id']??0);
            $db->prepare("UPDATE challenges SET is_active = 1-is_active WHERE id=?")->execute([$id]);
            $out = ['success'=>true,'message'=>'Durum güncellendi'];
            break;
    }
} catch(Exception $e){ $out=['success'=>false,'message'=>$e->getMessage()]; }
echo json_encode($out);
