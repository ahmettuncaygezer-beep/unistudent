<?php
declare(strict_types=1);
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_system.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/rate_limit.php';

$auth = new AuthSystem($pdo);
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo 'Oturum yok.';
    exit;
}
$user_id = (int)$_SESSION['user_id'];

rate_limit('gdpr_export', 5, 86400); // 24 saatte 5

$format = $_GET['format'] ?? 'json';

// Collect everything tied to this user
$data = [];
$tables = [
    'users'              => "SELECT id, username, full_name, email, university_name, role, xp_points, level, created_at, subscription_tier FROM users WHERE id = ?",
    'transactions'       => "SELECT * FROM transactions WHERE user_id = ? ORDER BY transaction_date DESC",
    'user_budgets'       => "SELECT * FROM user_budgets WHERE user_id = ?",
    'subscriptions'      => "SELECT * FROM subscriptions WHERE user_id = ?",
    'user_goals'         => "SELECT * FROM user_goals WHERE user_id = ?",
    'user_achievements'  => "SELECT ua.*, a.slug, a.title FROM user_achievements ua JOIN achievements a ON ua.achievement_id=a.id WHERE ua.user_id = ?",
    'notifications'      => "SELECT * FROM notifications WHERE user_id = ?",
    'user_settings'      => "SELECT * FROM user_settings WHERE user_id = ?",
    'user_saved_views'   => "SELECT * FROM user_saved_views WHERE user_id = ?",
    'user_scholarships'  => "SELECT * FROM user_scholarships WHERE user_id = ?",
    'user_investments'   => "SELECT * FROM user_investments WHERE user_id = ?",
    'user_audit_log'     => "SELECT * FROM user_audit_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 500",
];

foreach ($tables as $key => $sql) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $data[$key] = $stmt->fetchAll();
    } catch (\Throwable $e) {
        $data[$key] = ['error' => 'not_available'];
    }
}

$data['_meta'] = [
    'export_id'   => bin2hex(random_bytes(6)),
    'user_id'     => $user_id,
    'generated_at'=> gmdate('c'),
    'format'      => $format,
    'gdpr_notice' => 'Kişisel verilerinize ait tam kopya. İstediğiniz zaman silme talebinde bulunabilirsiniz.',
];

audit_log($user_id, 'gdpr.export', ['format' => $format]);

$filename = 'unibutce-verilerim-' . date('Y-m-d') . '.' . ($format === 'csv' ? 'csv' : 'json');

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['table','field','value']);
    $flatten = function($prefix, $rows) use (&$flatten, $out) {
        foreach ($rows as $i => $row) {
            if (is_array($row)) {
                foreach ($row as $k => $v) {
                    fputcsv($out, [$prefix, "[{$i}].{$k}", is_scalar($v) ? (string)$v : json_encode($v, JSON_UNESCAPED_UNICODE)]);
                }
            } else {
                fputcsv($out, [$prefix, (string)$i, (string)$row]);
            }
        }
    };
    foreach ($data as $table => $rows) $flatten($table, (array)$rows);
    fclose($out);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
