<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_check.php';
check_login();

require_once __DIR__ . '/database.php';

$db     = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$out    = ['success' => false, 'message' => 'Bilinmeyen aksiyon'];

try {
    switch ($action) {

        case 'list':
            $search  = trim($_GET['search'] ?? '');
            $role    = $_GET['role'] ?? '';
            $sort    = $_GET['sort'] ?? 'created_desc';
            $limit   = max(1, min(200, (int)($_GET['limit'] ?? 50)));
            $offset  = max(0, (int)($_GET['offset'] ?? 0));

            // Ensure is_banned column exists silently
            try { $db->query("ALTER TABLE users ADD COLUMN is_banned TINYINT(1) DEFAULT 0"); } catch(Exception $e2){}

            $where  = [];
            $params = [];
            if ($search !== '') {
                $where[]  = '(u.full_name LIKE ? OR u.email LIKE ? OR u.university_name LIKE ?)';
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($role === 'banned') {
                $where[] = 'u.is_banned = 1';
            } elseif ($role !== '' && $role !== 'all') {
                $where[]  = 'u.role = ?';
                $params[] = $role;
            }
            $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            // Sort
            $orderSQL = match($sort) {
                'created_asc'  => 'u.created_at ASC',
                'xp_desc'      => 'u.xp_points DESC',
                'tx_desc'      => 'tx_count DESC',
                default        => 'u.created_at DESC',
            };

            $stCount = $db->prepare("SELECT COUNT(*) FROM users u $whereSQL");
            $stCount->execute($params);
            $total = (int)$stCount->fetchColumn();

            $st = $db->prepare("
                SELECT u.id, u.full_name, u.email, u.role, u.university_name,
                       u.created_at, u.auth_provider,
                       COALESCE(u.xp_points,0) AS xp_points,
                       COALESCE(u.level,1) AS level,
                       COALESCE(u.is_banned,0) AS is_banned,
                       COUNT(t.id) AS tx_count,
                       COALESCE(SUM(t.amount),0) AS tx_volume,
                       MAX(t.transaction_date) AS last_tx
                FROM users u
                LEFT JOIN transactions t ON t.user_id = u.id
                $whereSQL
                GROUP BY u.id
                ORDER BY $orderSQL
                LIMIT $limit OFFSET $offset
            ");
            $st->execute($params);
            $users = $st->fetchAll(PDO::FETCH_ASSOC);

            $out = ['success' => true, 'total' => $total, 'users' => $users];
            break;

        case 'update_role':
            $userId = (int)($_POST['user_id'] ?? 0);
            $role   = $_POST['role'] ?? '';
            if (!$userId || !in_array($role, ['user','verified','admin'])) {
                throw new Exception('Geçersiz parametre');
            }
            $db->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role, $userId]);
            $out = ['success' => true, 'message' => 'Rol güncellendi'];
            break;

        case 'delete':
            $userId = (int)($_POST['user_id'] ?? 0);
            if (!$userId) throw new Exception('Geçersiz kullanıcı');
            $db->prepare("DELETE FROM users WHERE id=?")->execute([$userId]);
            $out = ['success' => true, 'message' => 'Kullanıcı silindi'];
            break;

        case 'get':
            $userId = (int)($_GET['user_id'] ?? 0);
            if (!$userId) throw new Exception('Geçersiz kullanıcı');
            $user = $db->prepare("SELECT id,full_name,email,role,university_name,created_at,auth_provider,xp_points,level FROM users WHERE id=?");
            $user->execute([$userId]);
            $u = $user->fetch(PDO::FETCH_ASSOC);
            if (!$u) throw new Exception('Kullanıcı bulunamadı');

            // Last 5 transactions
            $txs = $db->prepare("SELECT type,amount,category,description,transaction_date FROM transactions WHERE user_id=? ORDER BY transaction_date DESC LIMIT 5");
            $txs->execute([$userId]);

            $out = ['success' => true, 'user' => $u, 'transactions' => $txs->fetchAll(PDO::FETCH_ASSOC)];
            break;
    }
} catch (Exception $e) {
    $out = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($out);
