<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_check.php';
check_login();
require_once __DIR__ . '/database.php';

$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$out = ['success'=>false,'message'=>'Bilinmeyen aksiyon'];

try {
    switch($action) {

        // ── Dashboard full stats ──
        case 'dashboard_stats':
            $stats = [];
            $stats['total_users']       = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $stats['new_users_7d']      = (int)$db->query("SELECT COUNT(*) FROM users WHERE created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();
            $stats['new_users_30d']     = (int)$db->query("SELECT COUNT(*) FROM users WHERE created_at>=DATE_SUB(NOW(),INTERVAL 30 DAY)")->fetchColumn();
            $stats['active_users_7d']   = (int)$db->query("SELECT COUNT(DISTINCT user_id) FROM transactions WHERE transaction_date>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();
            $stats['active_users_30d']  = (int)$db->query("SELECT COUNT(DISTINCT user_id) FROM transactions WHERE transaction_date>=DATE_SUB(NOW(),INTERVAL 30 DAY)")->fetchColumn();
            $stats['total_tx']          = (int)$db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
            $stats['tx_today']          = (int)$db->query("SELECT COUNT(*) FROM transactions WHERE DATE(transaction_date)=CURDATE()")->fetchColumn();
            $stats['total_income']      = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='income'")->fetchColumn();
            $stats['total_expense']     = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='expense'")->fetchColumn();
            $stats['total_blogs']       = (int)$db->query("SELECT COUNT(*) FROM blogs")->fetchColumn();
            $stats['total_subs']        = (int)$db->query("SELECT COUNT(*) FROM subscriptions WHERE is_active=1")->fetchColumn();
            $stats['total_challenges']  = (int)$db->query("SELECT COUNT(*) FROM challenges")->fetchColumn();
            $stats['total_discounts']   = (int)$db->query("SELECT COUNT(*) FROM student_discounts WHERE is_active=1")->fetchColumn();
            $stats['total_achievements']= (int)$db->query("SELECT COUNT(*) FROM achievements")->fetchColumn();
            // Avg health score
            $stats['avg_xp']            = (float)$db->query("SELECT COALESCE(AVG(xp_points),0) FROM users")->fetchColumn();
            // Retention (users with >1 tx)
            $ret = $db->query("SELECT COUNT(*) FROM (SELECT user_id FROM transactions GROUP BY user_id HAVING COUNT(*)>1) t")->fetchColumn();
            $stats['retained_users'] = (int)$ret;
            $out = ['success'=>true,'stats'=>$stats];
            break;

        // ── Chart: daily signups last N days ──
        case 'chart_signups':
            $days = min(90,(int)($_GET['days']??30));
            $rows = $db->query("
                SELECT DATE(created_at) d, COUNT(*) cnt
                FROM users WHERE created_at>=DATE_SUB(NOW(),INTERVAL {$days} DAY)
                GROUP BY DATE(created_at) ORDER BY d ASC")->fetchAll(PDO::FETCH_ASSOC);
            $map=[]; foreach($rows as $r) $map[$r['d']]=$r['cnt'];
            $dates=[]; $data=[];
            for($i=$days-1;$i>=0;$i--){
                $d=date('Y-m-d',strtotime("-{$i} days"));
                $dates[]=date('d.m',$i<60?strtotime($d):strtotime($d));
                $data[]=(int)($map[$d]??0);
            }
            $out=['success'=>true,'labels'=>$dates,'data'=>$data];
            break;

        // ── Chart: hourly tx today ──
        case 'chart_hourly':
            $rows = $db->query("
                SELECT HOUR(transaction_date) h, COUNT(*) cnt, SUM(amount) total
                FROM transactions WHERE DATE(transaction_date)=CURDATE()
                GROUP BY HOUR(transaction_date) ORDER BY h")->fetchAll(PDO::FETCH_ASSOC);
            $map=[]; foreach($rows as $r) $map[$r['h']]=['cnt'=>$r['cnt'],'total'=>$r['total']];
            $labels=[]; $counts=[]; $totals=[];
            for($h=0;$h<24;$h++){ $labels[]=str_pad($h,2,'0',STR_PAD_LEFT).':00'; $counts[]=(int)($map[$h]['cnt']??0); $totals[]=(float)($map[$h]['total']??0); }
            $out=['success'=>true,'labels'=>$labels,'counts'=>$counts,'totals'=>$totals];
            break;

        // ── Chart: monthly income vs expense ──
        case 'chart_monthly':
            $months = min(24,(int)($_GET['months']??12));
            $rows = $db->query("
                SELECT DATE_FORMAT(transaction_date,'%Y-%m') m,
                       SUM(CASE WHEN type='income' THEN amount ELSE 0 END) income,
                       SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) expense
                FROM transactions WHERE transaction_date>=DATE_SUB(NOW(),INTERVAL {$months} MONTH)
                GROUP BY m ORDER BY m ASC")->fetchAll(PDO::FETCH_ASSOC);
            $out=['success'=>true,'rows'=>$rows];
            break;

        // ── Chart: category breakdown ──
        case 'chart_categories':
            $rows = $db->query("SELECT category, COUNT(*) cnt, SUM(amount) total FROM transactions WHERE type='expense' GROUP BY category ORDER BY total DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
            $out=['success'=>true,'rows'=>$rows];
            break;

        // ── Chart: user role distribution ──
        case 'chart_roles':
            $rows = $db->query("SELECT role, COUNT(*) cnt FROM users GROUP BY role")->fetchAll(PDO::FETCH_ASSOC);
            $out=['success'=>true,'rows'=>$rows];
            break;

        // ── Chart: subscription by category ──
        case 'chart_subs':
            $rows = $db->query("SELECT category, COUNT(*) cnt, SUM(CASE WHEN billing_cycle='yearly' THEN amount/12 ELSE amount END) monthly FROM subscriptions WHERE is_active=1 GROUP BY category ORDER BY monthly DESC")->fetchAll(PDO::FETCH_ASSOC);
            $out=['success'=>true,'rows'=>$rows];
            break;

        // ── Activity feed ──
        case 'activity_feed':
            $limit = min(50,(int)($_GET['limit']??20));
            $rows = $db->query("SELECT al.*, u.full_name FROM user_audit_log al LEFT JOIN users u ON u.id=al.user_id ORDER BY al.created_at DESC LIMIT $limit")->fetchAll(PDO::FETCH_ASSOC);
            $out=['success'=>true,'rows'=>$rows];
            break;

        // ── Platform health ──
        case 'health':
            $health=[];
            // DB ping
            try{ $db->query("SELECT 1"); $health['db']=['ok'=>true,'msg'=>'Bağlantı aktif']; }
            catch(Exception $e){ $health['db']=['ok'=>false,'msg'=>$e->getMessage()]; }
            // Disk space
            $free = disk_free_space(DIRECTORY_SEPARATOR);
            $total= disk_total_space(DIRECTORY_SEPARATOR);
            $health['disk']=['free_gb'=>round($free/1073741824,1),'total_gb'=>round($total/1073741824,1),'pct'=>round(($total-$free)/$total*100,1)];
            // PHP version
            $health['php']=['version'=>PHP_VERSION,'ok'=>version_compare(PHP_VERSION,'8.0','>=')];
            // MySQL version
            $health['mysql']=['version'=>$db->query("SELECT VERSION()")->fetchColumn()];
            $out=['success'=>true,'health'=>$health];
            break;

        // ── Recent admin log ──
        case 'admin_log':
            $rows = $db->query("SELECT id,action,target_type,target_id,detail,created_at FROM admin_audit_log ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
            $out=['success'=>true,'rows'=>$rows];
            break;

        // ── Log admin action ──
        case 'log_action':
            $action2 = $_POST['admin_action'] ?? '';
            $target  = $_POST['target_type'] ?? '';
            $tid     = (int)($_POST['target_id']??0);
            $detail  = $_POST['detail'] ?? '';
            // Ensure table exists
            $db->query("CREATE TABLE IF NOT EXISTS admin_audit_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                action VARCHAR(100), target_type VARCHAR(50),
                target_id INT, detail TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            $db->prepare("INSERT INTO admin_audit_log(action,target_type,target_id,detail) VALUES(?,?,?,?)")
               ->execute([$action2,$target,$tid,$detail]);
            $out=['success'=>true];
            break;

        // ── Notification: send to user ──
        case 'send_notification':
            $userId  = (int)($_POST['user_id']??0);
            $title   = trim($_POST['title']??'');
            $message = trim($_POST['message']??'');
            $type    = $_POST['type']??'info';
            if(!$title) throw new Exception('Başlık boş olamaz');
            if($userId) {
                $db->prepare("INSERT INTO notifications(user_id,title,message,type) VALUES(?,?,?,?)")
                   ->execute([$userId,$title,$message,$type]);
            } else {
                // Broadcast to all
                $users = $db->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
                $st = $db->prepare("INSERT INTO notifications(user_id,title,message,type) VALUES(?,?,?,?)");
                foreach($users as $uid) $st->execute([$uid,$title,$message,$type]);
            }
            $out=['success'=>true,'message'=>'Bildirim gönderildi'];
            break;

        // ── User: ban/unban ──
        case 'ban_user':
            $userId = (int)($_POST['user_id']??0);
            $ban    = (int)($_POST['ban']??1);
            if(!$userId) throw new Exception('Geçersiz kullanıcı');
            // Add is_banned col if not exists
            try{ $db->query("ALTER TABLE users ADD COLUMN is_banned TINYINT(1) DEFAULT 0"); }catch(Exception $e){}
            $db->prepare("UPDATE users SET is_banned=? WHERE id=?")->execute([$ban,$userId]);
            $out=['success'=>true,'message'=>$ban?'Kullanıcı banlandı':'Ban kaldırıldı'];
            break;

        // ── User: edit XP ──
        case 'edit_xp':
            $userId = (int)($_POST['user_id']??0);
            $xp     = (int)($_POST['xp']??0);
            if(!$userId) throw new Exception('Geçersiz kullanıcı');
            $db->prepare("UPDATE users SET xp_points=? WHERE id=?")->execute([$xp,$userId]);
            $out=['success'=>true,'message'=>'XP güncellendi'];
            break;

        // ── Feature flags ──
        case 'get_flags':
            $rows = $db->query("SELECT slug AS flag_name, enabled AS is_enabled, description FROM feature_flags ORDER BY slug")->fetchAll(PDO::FETCH_ASSOC);
            $out=['success'=>true,'flags'=>$rows];
            break;
        case 'set_flag':
            $flag    = trim($_POST['flag']??'');
            $enabled = (int)($_POST['enabled']??0);
            if(!$flag) throw new Exception('Bayrak adı gerekli');
            // config.php schema uses slug+enabled
            $db->prepare("INSERT INTO feature_flags(slug, enabled) VALUES(?,?) ON DUPLICATE KEY UPDATE enabled=?")->execute([$flag,$enabled,$enabled]);
            $out=['success'=>true,'message'=>'Bayrak güncellendi'];
            break;

        // ── Cohort analysis ──
        case 'cohort':
            // Monthly cohorts: signups per month vs 30-day active
            $rows = $db->query("
                SELECT DATE_FORMAT(u.created_at,'%Y-%m') cohort,
                       COUNT(*) signed_up,
                       COUNT(CASE WHEN t.tx_cnt>0 THEN 1 END) active
                FROM users u
                LEFT JOIN (SELECT user_id, COUNT(*) tx_cnt FROM transactions GROUP BY user_id) t ON t.user_id=u.id
                GROUP BY cohort ORDER BY cohort DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
            $out=['success'=>true,'rows'=>$rows];
            break;

        // ── Top spender users ──
        case 'top_users':
            $limit = min(20,(int)($_GET['limit']??10));
            $rows  = $db->query("
                SELECT u.full_name,u.email,u.university_name,
                       COUNT(t.id) tx_count,
                       COALESCE(SUM(CASE WHEN t.type='expense' THEN t.amount ELSE 0 END),0) expense,
                       COALESCE(SUM(CASE WHEN t.type='income'  THEN t.amount ELSE 0 END),0) income
                FROM users u
                LEFT JOIN transactions t ON t.user_id=u.id
                GROUP BY u.id ORDER BY expense DESC LIMIT $limit")->fetchAll(PDO::FETCH_ASSOC);
            $out=['success'=>true,'rows'=>$rows];
            break;
    }
} catch(Exception $e) { $out=['success'=>false,'message'=>$e->getMessage()]; }

echo json_encode($out);
