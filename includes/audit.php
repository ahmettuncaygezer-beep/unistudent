<?php
declare(strict_types=1);

/**
 * Audit log helper — kullanıcının hassas eylemlerini kaydeder.
 */
function audit_log(int $userId, string $action, array $details = []): void {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare(
            "INSERT INTO user_audit_log (user_id, action, details, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $userId,
            substr($action, 0, 64),
            json_encode($details, JSON_UNESCAPED_UNICODE),
            substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45),
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    } catch (\Throwable $e) {
        error_log('audit_log failed: ' . $e->getMessage());
    }
}

/**
 * Feature flag — slug ile on/off + rollout%.
 */
function feature_enabled(string $slug, int $userId = 0): bool {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT enabled, rollout_pct FROM feature_flags WHERE slug = ?");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if (!$row) return false;
        if (!(int)$row['enabled']) return false;
        if ((int)$row['rollout_pct'] >= 100) return true;
        // Deterministic bucketing by user id
        $bucket = $userId > 0 ? ($userId % 100) : random_int(0, 99);
        return $bucket < (int)$row['rollout_pct'];
    } catch (\Throwable $e) {
        return false;
    }
}

/**
 * Pro tier gate — abonelik kontrolü.
 */
function is_pro(int $userId): bool {
    // Şimdilik her şey tüm üyelere ve misafirlere açık (Pro kısıtlaması kaldırıldı)
    return true;
}

function require_pro(int $userId): void {
    // Şimdilik her şey tüm üyelere açık, özelliği kilitlemeyi iptal et
}
