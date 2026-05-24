<?php
declare(strict_types=1);
/**
 * ÜniBütçe — Merkezi Konfigürasyon Dosyası
 * Hassas değerler .env dosyasından okunur (yoksa varsayılan).
 */

// ===========================================
// .ENV YÜKLEYİCİ (hafif, dependency-free)
// ===========================================
(function (): void {
    $envFile = __DIR__ . '/.env';
    if (!is_file($envFile)) return;
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        $k = trim($k);
        $v = trim($v, " \t\"'");
        if ($k !== '' && getenv($k) === false) {
            putenv("$k=$v");
            $_ENV[$k] = $v;
        }
    }
})();

function env(string $key, string $default = ''): string {
    $v = getenv($key);
    return $v === false ? $default : $v;
}

// ===========================================
// ZAMAN DİLİMİ
// ===========================================
date_default_timezone_set(env('APP_TIMEZONE', 'Europe/Istanbul'));

// ===========================================
// SESSION GÜVENLİĞİ (session_start'tan önce)
// ===========================================
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// ===========================================
// VERİTABANI AYARLARI
// ===========================================
define('DB_HOST',    env('DB_HOST', 'localhost'));
define('DB_NAME',    env('DB_NAME', 'unistudent'));
define('DB_USER',    env('DB_USER', 'root'));
define('DB_PASS',    env('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

// ===========================================
// ADMIN PANELİ GİRİŞ BİLGİLERİ
// ===========================================
define('ADMIN_USERNAME', env('ADMIN_USERNAME', 'admin'));
// Bcrypt hash; .env > ADMIN_PASSWORD_HASH üzerinden override edilebilir.
define('ADMIN_PASSWORD_HASH', env('ADMIN_PASSWORD_HASH', '$2y$10$iumTBOvINctSfMDrRs4kjuk1jaR5METT68.fKCYUeGbVtX4kt3Nbi'));

// Gizli Master PIN Doğrulaması
define('ADMIN_MASTER_PIN', env('ADMIN_MASTER_PIN', 'UNI-9999'));

// ===========================================
// API ANAHTARLARI
// ===========================================
define('FAL_API_KEY', env('FAL_API_KEY', ''));  // .env dosyasından al

// Gemini API anahtarını admin/api/ai_config.json'dan oku
function getGeminiApiKey(): string {
    $configFile = __DIR__ . '/admin/api/ai_config.json';
    if (file_exists($configFile)) {
        $conf = json_decode(file_get_contents($configFile), true);
        return $conf['gemini_api_key'] ?? '';
    }
    return '';
}

// ===========================================
// SITE AYARLARI
// ===========================================
define('SITE_NAME', 'ÜniBütçe');
define('SITE_URL', env('APP_URL', 'https://unibutce.com'));
define('BLOG_DIR', __DIR__ . '/blog/');

// ===========================================
// VERİTABANI BAĞLANTISI (Fonksiyon)
// ===========================================
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            // DB yok ise oluştur
            if (strpos($e->getMessage(), 'Unknown database') !== false) {
                $tmp = new PDO('mysql:host=' . DB_HOST, DB_USER, DB_PASS);
                $tmp->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                $tmp = null;
                $pdo = new PDO(
                    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } else {
                error_log('DB Bağlantı Hatası: ' . $e->getMessage());
                throw $e;
            }
        }
    }
    return $pdo;
}

// ===========================================
// TABLO OLUŞTURMA (İlk Kullanımda)
// ===========================================
function ensureAllTables(): void {
    $db = getDB();

    // Kullanıcılar tablosu
    $db->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id`              INT AUTO_INCREMENT PRIMARY KEY,
        `username`        VARCHAR(50) UNIQUE,
        `full_name`       VARCHAR(255) NOT NULL,
        `email`           VARCHAR(255) NOT NULL UNIQUE,
        `password_hash`   VARCHAR(255),
        `university_name` VARCHAR(255),
        `budget_json`     LONGTEXT,
        `auth_provider`   ENUM('local','google','facebook') DEFAULT 'local',
        `provider_id`     VARCHAR(255),
        `role`            ENUM('user','verified','admin') DEFAULT 'user',
        `xp_points`       INT DEFAULT 0,
        `level`           INT DEFAULT 1,
        `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // İşlemler tablosu (transactions)
    $db->exec("CREATE TABLE IF NOT EXISTS `transactions` (
        `id`               INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`          INT NOT NULL,
        `type`             ENUM('income','expense') NOT NULL,
        `amount`           DECIMAL(10,2) NOT NULL,
        `category`         VARCHAR(100) NOT NULL,
        `description`      TEXT,
        `transaction_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Bütçeler tablosu
    $db->exec("CREATE TABLE IF NOT EXISTS `user_budgets` (
        `id`             INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`        INT NOT NULL,
        `month`          TINYINT NOT NULL,
        `year`           SMALLINT NOT NULL,
        `total_income`   DECIMAL(10,2) DEFAULT 0.00,
        `kyk_income`     DECIMAL(10,2) DEFAULT 0.00,
        `target_savings` DECIMAL(10,2) DEFAULT 0.00,
        `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Harcama kalemleri tablosu
    $db->exec("CREATE TABLE IF NOT EXISTS `user_expenses` (
        `id`             INT AUTO_INCREMENT PRIMARY KEY,
        `budget_id`      INT NOT NULL,
        `category_id`    VARCHAR(50) NOT NULL,
        `planned_amount` DECIMAL(10,2) DEFAULT 0.00,
        `actual_amount`  DECIMAL(10,2) DEFAULT 0.00,
        `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`budget_id`) REFERENCES `user_budgets`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Eski users tablosuna budget_json yoksa ekle (migration)
    try {
        $db->exec("ALTER TABLE users ADD COLUMN budget_json LONGTEXT NULL");
    } catch (PDOException $migEx) {
        // Kolon zaten varsa sessizce geç
    }

    // Oyunlaştırma sütunları migrasonu
    try {
        $db->exec("ALTER TABLE users ADD COLUMN xp_points INT DEFAULT 0");
        $db->exec("ALTER TABLE users ADD COLUMN level INT DEFAULT 1");
    } catch (PDOException $migEx) {
        // Zaten varsa atla
    }

    // Blog tablosu
    $db->exec("CREATE TABLE IF NOT EXISTS `blogs` (
        `id`          INT AUTO_INCREMENT PRIMARY KEY,
        `title`       VARCHAR(500) NOT NULL,
        `slug`        VARCHAR(500) NOT NULL UNIQUE,
        `description` TEXT,
        `keywords`    VARCHAR(1000),
        `content`     LONGTEXT,
        `cover_image` VARCHAR(500),
        `tag`         VARCHAR(100) DEFAULT '📝 Blog Yazısı',
        `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Şehirler tablosu (data.js migrasyonu için)
    $db->exec("CREATE TABLE IF NOT EXISTS `cities` (
        `id`             INT AUTO_INCREMENT PRIMARY KEY,
        `slug`           VARCHAR(100) NOT NULL UNIQUE,
        `name`           VARCHAR(100) NOT NULL,
        `emoji`          VARCHAR(20),
        `description`    VARCHAR(500),
        `living_score`   DECIMAL(3,1),
        `rent_single`    INT,
        `rent_shared`    INT,
        `dorm_private`   INT,
        `dorm_kyk`       INT DEFAULT 1000,
        `food`           INT,
        `transport`      INT,
        `entertainment`  INT,
        `utilities`      INT,
        `tip_1`          VARCHAR(500),
        `tip_2`          VARCHAR(500),
        `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Abonelik Takipçisi tablosu
    $db->exec("CREATE TABLE IF NOT EXISTS `subscriptions` (
        `id`             INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`        INT NOT NULL,
        `name`           VARCHAR(100) NOT NULL,
        `amount`         DECIMAL(10,2) NOT NULL,
        `billing_cycle`  ENUM('monthly','yearly') DEFAULT 'monthly',
        `category`       VARCHAR(50) DEFAULT 'Diğer',
        `next_billing`   DATE,
        `is_active`      TINYINT(1) DEFAULT 1,
        `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Başarım tanımları tablosu
    $db->exec("CREATE TABLE IF NOT EXISTS `achievements` (
        `id`             INT AUTO_INCREMENT PRIMARY KEY,
        `slug`           VARCHAR(50) NOT NULL UNIQUE,
        `title`          VARCHAR(100) NOT NULL,
        `description`    VARCHAR(255),
        `icon`           VARCHAR(10) DEFAULT '🏆',
        `xp_reward`      INT DEFAULT 100
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Kullanıcı başarımları köprü tablosu
    $db->exec("CREATE TABLE IF NOT EXISTS `user_achievements` (
        `id`             INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`        INT NOT NULL,
        `achievement_id` INT NOT NULL,
        `earned_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`achievement_id`) REFERENCES `achievements`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `unique_user_achievement` (`user_id`, `achievement_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Bildirim tablosu
    $db->exec("CREATE TABLE IF NOT EXISTS `notifications` (
        `id`             INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`        INT NOT NULL,
        `type`           VARCHAR(50) DEFAULT 'info',
        `title`          VARCHAR(255) NOT NULL,
        `message`        TEXT,
        `is_read`        TINYINT(1) DEFAULT 0,
        `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Varsayılan başarımları ekle (yoksa)
    $checkAch = $db->query("SELECT COUNT(*) FROM achievements")->fetchColumn();
    if ((int)$checkAch === 0) {
        $db->exec("INSERT INTO achievements (slug, title, description, icon, xp_reward) VALUES
            ('first_step',     'İlk Adım',        'İlk gelir veya gider kaydını oluşturdun.',   '🥇', 100),
            ('streak_7',       '7 Gün Serisi',     'Üst üste 7 gün gider girdin.',              '🔥', 250),
            ('budget_master',  'Bütçe Ustası',     'Bir ay limiti aşmadan tamamladın.',          '💎', 500),
            ('goal_hitter',    'Hedef Vurucu',      'Tasarruf hedefine ulaştın.',                '🎯', 300),
            ('social_sharer',  'Topluluk Gücü',     'Şehir verisini toplulukla paylaştın.',      '🤝', 150),
            ('sub_hunter',     'Abonelik Avcısı',   'İlk aboneliğini takibe aldın.',             '📱', 100)
        ");
    }

    // Users tablosuna onboarding_done ekle (migration)
    try {
        $db->exec("ALTER TABLE users ADD COLUMN onboarding_done TINYINT(1) DEFAULT 0");
    } catch (PDOException $migEx) { /* Zaten varsa atla */ }

    // Blog status kolonu (draft/published)
    try {
        $db->exec("ALTER TABLE blogs ADD COLUMN status ENUM('draft','published') DEFAULT 'published'");
    } catch (PDOException $migEx) { /* Zaten varsa atla */ }

    // Login streak gamification columns (migration)
    try {
        $db->exec("ALTER TABLE users ADD COLUMN login_streak INT DEFAULT 0");
    } catch (PDOException $migEx) { /* Zaten varsa atla */ }
    try {
        $db->exec("ALTER TABLE users ADD COLUMN last_login_date DATE DEFAULT NULL");
    } catch (PDOException $migEx) { /* Zaten varsa atla */ }

    // User Goals (Tasarruf Hedefleri) tablosu
    $db->exec("CREATE TABLE IF NOT EXISTS `user_goals` (
        `id`              INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`         INT NOT NULL,
        `title`           VARCHAR(255) NOT NULL,
        `target_amount`   DECIMAL(10,2) NOT NULL DEFAULT 0,
        `current_amount`  DECIMAL(10,2) NOT NULL DEFAULT 0,
        `icon`            VARCHAR(10) DEFAULT '🎯',
        `color`           VARCHAR(20) DEFAULT '#0088ff',
        `deadline`        DATE DEFAULT NULL,
        `is_completed`    TINYINT(1) DEFAULT 0,
        `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ───────────────────────────────────────────────────
    // PERFORMANS İNDEKSLERİ (idempotent)
    // ───────────────────────────────────────────────────
    $indexes = [
        "idx_tx_user_date"    => "CREATE INDEX idx_tx_user_date ON transactions(user_id, transaction_date)",
        "idx_tx_user_type"    => "CREATE INDEX idx_tx_user_type ON transactions(user_id, type)",
        "idx_budget_user_ym"  => "CREATE INDEX idx_budget_user_ym ON user_budgets(user_id, year, month)",
        "idx_sub_user_active" => "CREATE INDEX idx_sub_user_active ON subscriptions(user_id, is_active)",
        "idx_sub_next_bill"   => "CREATE INDEX idx_sub_next_bill ON subscriptions(next_billing, is_active)",
        "idx_notif_user_read" => "CREATE INDEX idx_notif_user_read ON notifications(user_id, is_read)",
        "idx_blogs_status"    => "CREATE INDEX idx_blogs_status ON blogs(status, updated_at)",
    ];
    foreach ($indexes as $sql) {
        try { $db->exec($sql); } catch (PDOException $e) { /* zaten varsa atla */ }
    }

    // ───────────────────────────────────────────────────
    // ULTRA-PREMIUM KATMAN TABLOLARI
    // ───────────────────────────────────────────────────

    // Audit log (şeffaflık + güvenlik)
    $db->exec("CREATE TABLE IF NOT EXISTS `user_audit_log` (
        `id`          INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`     INT NOT NULL,
        `action`      VARCHAR(64) NOT NULL,
        `details`     TEXT,
        `ip_address`  VARCHAR(45),
        `user_agent`  VARCHAR(255),
        `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_audit_user_time` (`user_id`, `created_at`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Kullanıcı tercihleri (tema, para birimi, yurtdışı modu, widget düzeni)
    $db->exec("CREATE TABLE IF NOT EXISTS `user_settings` (
        `user_id`        INT PRIMARY KEY,
        `theme`          VARCHAR(16) DEFAULT 'auto',
        `currency`       VARCHAR(8)  DEFAULT 'TRY',
        `abroad_mode`    TINYINT(1)  DEFAULT 0,
        `dashboard_json` LONGTEXT,
        `ui_flags`       LONGTEXT,
        `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Saved views / filters
    $db->exec("CREATE TABLE IF NOT EXISTS `user_saved_views` (
        `id`         INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`    INT NOT NULL,
        `name`       VARCHAR(100) NOT NULL,
        `filters`    LONGTEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Anomaly alerts (z-score tespiti)
    $db->exec("CREATE TABLE IF NOT EXISTS `user_anomalies` (
        `id`             INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`        INT NOT NULL,
        `transaction_id` INT,
        `category`       VARCHAR(100),
        `amount`         DECIMAL(10,2),
        `zscore`         DECIMAL(5,2),
        `reason`         VARCHAR(255),
        `is_dismissed`   TINYINT(1) DEFAULT 0,
        `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_anom_user` (`user_id`, `is_dismissed`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Kategori öğrenimi
    $db->exec("CREATE TABLE IF NOT EXISTS `categorization_learned` (
        `id`          INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`     INT NOT NULL,
        `pattern`     VARCHAR(255) NOT NULL,
        `category`    VARCHAR(100) NOT NULL,
        `hits`        INT DEFAULT 1,
        `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_catlearn_user` (`user_id`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Paylaşımlı ev bütçeleri
    $db->exec("CREATE TABLE IF NOT EXISTS `shared_budgets` (
        `id`         INT AUTO_INCREMENT PRIMARY KEY,
        `owner_id`   INT NOT NULL,
        `name`       VARCHAR(120) NOT NULL,
        `invite_code` VARCHAR(24) UNIQUE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS `shared_budget_members` (
        `id`         INT AUTO_INCREMENT PRIMARY KEY,
        `budget_id`  INT NOT NULL,
        `user_id`    INT NOT NULL,
        `role`       ENUM('owner','editor','viewer') DEFAULT 'editor',
        `joined_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_budget_user` (`budget_id`, `user_id`),
        FOREIGN KEY (`budget_id`) REFERENCES `shared_budgets`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS `shared_expenses` (
        `id`         INT AUTO_INCREMENT PRIMARY KEY,
        `budget_id`  INT NOT NULL,
        `payer_id`   INT NOT NULL,
        `amount`     DECIMAL(10,2) NOT NULL,
        `category`   VARCHAR(100),
        `description` VARCHAR(255),
        `split_with` LONGTEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_shexp_budget` (`budget_id`),
        FOREIGN KEY (`budget_id`) REFERENCES `shared_budgets`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`payer_id`)  REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Burs / scholarship takibi
    $db->exec("CREATE TABLE IF NOT EXISTS `user_scholarships` (
        `id`         INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`    INT NOT NULL,
        `name`       VARCHAR(200) NOT NULL,
        `amount`     DECIMAL(10,2) DEFAULT 0,
        `deadline`   DATE,
        `status`     ENUM('applied','awarded','missed','planned') DEFAULT 'planned',
        `notes`      TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Yatırım portföyü (BIST/crypto/fon)
    $db->exec("CREATE TABLE IF NOT EXISTS `user_investments` (
        `id`          INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`     INT NOT NULL,
        `ticker`      VARCHAR(24) NOT NULL,
        `asset_type`  ENUM('bist','forex','crypto','fund','gold') DEFAULT 'bist',
        `quantity`    DECIMAL(14,6) NOT NULL DEFAULT 0,
        `buy_price`   DECIMAL(14,4) NOT NULL DEFAULT 0,
        `buy_date`    DATE,
        `notes`       VARCHAR(255),
        `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_inv_user` (`user_id`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Watchlist (yatırım takip listesi)
    $db->exec("CREATE TABLE IF NOT EXISTS `user_watchlist` (
        `id`          INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`     INT NOT NULL,
        `ticker`      VARCHAR(24) NOT NULL,
        `asset_type`  ENUM('bist','forex','crypto','fund','gold') DEFAULT 'bist',
        `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_watch_user` (`user_id`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Feature flags
    $db->exec("CREATE TABLE IF NOT EXISTS `feature_flags` (
        `slug`        VARCHAR(64) PRIMARY KEY,
        `enabled`     TINYINT(1) DEFAULT 0,
        `rollout_pct` TINYINT    DEFAULT 100,
        `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Abonelik tier
    try {
        $db->exec("ALTER TABLE users ADD COLUMN subscription_tier ENUM('free','pro') DEFAULT 'free'");
    } catch (PDOException $e) { /* ignore */ }
    try {
        $db->exec("ALTER TABLE users ADD COLUMN subscription_expires DATE DEFAULT NULL");
    } catch (PDOException $e) { /* ignore */ }

    // ───────────────────────────────────────────────────
    // ÖĞRENCI ODAKLI KATMAN (KYK, Depozito, Challenge, Benchmark, Telegram, Discount)
    // ───────────────────────────────────────────────────

    $db->exec("CREATE TABLE IF NOT EXISTS `user_kyk` (
        `id`             INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`        INT NOT NULL UNIQUE,
        `monthly_amount` DECIMAL(10,2) DEFAULT 0,
        `start_date`     DATE,
        `study_months`   INT DEFAULT 48,
        `grace_months`   INT DEFAULT 24,
        `notes`          TEXT,
        `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS `user_deposits` (
        `id`            INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`       INT NOT NULL,
        `landlord_name` VARCHAR(200),
        `address`       VARCHAR(255),
        `amount`        DECIMAL(10,2) NOT NULL DEFAULT 0,
        `paid_date`     DATE,
        `expected_return_date` DATE,
        `status`        ENUM('active','returned','partial','lost') DEFAULT 'active',
        `returned_amount` DECIMAL(10,2) DEFAULT 0,
        `notes`         TEXT,
        `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_dep_user` (`user_id`, `status`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS `challenges` (
        `id`          INT AUTO_INCREMENT PRIMARY KEY,
        `slug`        VARCHAR(64) UNIQUE,
        `title`       VARCHAR(150) NOT NULL,
        `description` VARCHAR(500),
        `icon`        VARCHAR(10) DEFAULT '🎯',
        `duration_days` INT DEFAULT 7,
        `target_savings` DECIMAL(10,2) DEFAULT 0,
        `xp_reward`   INT DEFAULT 100,
        `category_block` VARCHAR(100),
        `is_active`   TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS `user_challenges` (
        `id`           INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`      INT NOT NULL,
        `challenge_id` INT NOT NULL,
        `start_date`   DATE NOT NULL,
        `end_date`     DATE NOT NULL,
        `status`       ENUM('active','completed','failed','abandoned') DEFAULT 'active',
        `saved_amount` DECIMAL(10,2) DEFAULT 0,
        `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_ch_user` (`user_id`, `status`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`challenge_id`) REFERENCES `challenges`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS `benchmark_submissions` (
        `id`         INT AUTO_INCREMENT PRIMARY KEY,
        `user_hash`  VARCHAR(64) NOT NULL,
        `city`       VARCHAR(64),
        `age_band`   VARCHAR(16),
        `category`   VARCHAR(100) NOT NULL,
        `amount`     DECIMAL(10,2) NOT NULL,
        `period_ym`  CHAR(7) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_benchmark` (`user_hash`, `category`, `period_ym`),
        INDEX `idx_bm_filter` (`category`, `city`, `age_band`, `period_ym`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS `telegram_links` (
        `user_id`    INT PRIMARY KEY,
        `chat_id`    BIGINT,
        `link_token` VARCHAR(32) UNIQUE,
        `linked_at`  TIMESTAMP NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS `student_discounts` (
        `id`          INT AUTO_INCREMENT PRIMARY KEY,
        `brand`       VARCHAR(100) NOT NULL,
        `category`    VARCHAR(50),
        `discount_text` VARCHAR(120),
        `monthly_saving` DECIMAL(10,2) DEFAULT 0,
        `url`         VARCHAR(500),
        `how_to`      VARCHAR(500),
        `region`      VARCHAR(32) DEFAULT 'TR',
        `icon`        VARCHAR(10),
        `is_active`   TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS `user_discount_claims` (
        `id`          INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`     INT NOT NULL,
        `discount_id` INT NOT NULL,
        `claimed_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_user_discount` (`user_id`, `discount_id`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`discount_id`) REFERENCES `student_discounts`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS `cookie_consents` (
        `id`         INT AUTO_INCREMENT PRIMARY KEY,
        `user_hash`  VARCHAR(64) NOT NULL,
        `necessary`  TINYINT(1) DEFAULT 1,
        `analytics`  TINYINT(1) DEFAULT 0,
        `marketing`  TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_consent_hash` (`user_hash`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Challenge seed data
    $checkCh = $db->query("SELECT COUNT(*) FROM challenges")->fetchColumn();
    if ((int)$checkCh === 0) {
        $db->exec("INSERT INTO challenges (slug, title, description, icon, duration_days, target_savings, xp_reward, category_block) VALUES
            ('no_eat_out_7',    '7 Gün Dışarıda Yeme',      '1 hafta boyunca dışarıda yeme — 300₺ biriktir.', '🍱', 7,  300, 250, 'Yemek'),
            ('no_coffee_14',    '14 Gün Kahvehane Yok',     'Starbucks, Kahve Dünyası yok — eve demle.',       '☕', 14, 400, 300, 'Kahve'),
            ('bim_market_30',   '30 Gün Sadece İndirimli Market', 'Sadece BİM/A101/ŞOK — markete bütçen %30 düşecek.', '🛒', 30, 800, 500, 'Market'),
            ('no_taksi_7',      '7 Gün Taksi Yok',          'Sadece İETT/metro — 200₺ cebinde kalsın.',        '🚇', 7,  200, 200, 'Ulaşım'),
            ('no_spending_3',   '3 Gün Hiçbir Şey Alma',    'Zorunlu olmayan hiçbir harcama yok.',             '💎', 3,  150, 150, 'Diğer'),
            ('cook_home_21',    '21 Gün Evde Yemek',        'Her öğünü evde pişir. Kalorilerle birlikte bütçeni de koru.', '👨‍🍳', 21, 900, 600, 'Yemek')
        ");
    }

    // Discount seed data
    $checkDis = $db->query("SELECT COUNT(*) FROM student_discounts")->fetchColumn();
    if ((int)$checkDis === 0) {
        $db->exec("INSERT INTO student_discounts (brand, category, discount_text, monthly_saving, url, how_to, icon) VALUES
            ('Spotify Premium',   'Müzik',     '%50 öğrenci',           29.99, 'https://www.spotify.com/tr/student/',      'SheerID üzerinden öğrenci belgesi yükle.',              '🎵'),
            ('YouTube Premium',   'Video',     '%50 öğrenci',           39.50, 'https://www.youtube.com/premium/student',  'SheerID doğrulaması gerekli.',                          '▶️'),
            ('Apple Music',       'Müzik',     '%50 öğrenci',           39.99, 'https://www.apple.com/tr/apple-music/',    'UNiDAYS üzerinden aktive et.',                          '🎧'),
            ('Microsoft 365',     'Yazılım',   'Ücretsiz (üni maili)',  250.00, 'https://www.microsoft.com/tr-tr/education/', '.edu.tr uzantılı mail ile kaydol.',                      '📄'),
            ('JetBrains IDE',     'Yazılım',   'Ücretsiz (1 yıl)',      600.00, 'https://www.jetbrains.com/community/education/', 'Öğrenci belgesi ya da .edu.tr mail.',                   '💻'),
            ('GitHub Student Pack','Yazılım',  '100+ ücretsiz araç',    500.00, 'https://education.github.com/pack',         'GitHub Student Developer Pack başvuru.',                '🐙'),
            ('Figma',             'Tasarım',   'Professional ücretsiz', 180.00, 'https://www.figma.com/education/',         '.edu.tr mail doğrulaması.',                             '🎨'),
            ('Notion',            'Verimlilik', 'Plus ücretsiz',         200.00, 'https://www.notion.so/tr-tr/students',     '.edu.tr mail doğrulaması.',                             '📝'),
            ('Canva Pro',         'Tasarım',   'Ücretsiz',              149.00, 'https://www.canva.com/tr_tr/education/',   'Öğretmen/öğrenci kaydı (eğitim planı).',                '✨'),
            ('Amazon Prime',      'Alışveriş', '6 ay ücretsiz',         79.00,  'https://www.amazon.com.tr/primestudent',    'İlk 6 ay ücretsiz + sonrası %50.',                      '📦'),
            ('Trendyol Go',       'Yemek',     'Öğrenci indirimi',      50.00,  'https://www.trendyolgo.com',                'Uygulamada öğrenci doğrulaması.',                        '🍔'),
            ('İETT İstanbulKart', 'Ulaşım',    'Öğrenci kart (%70)',    200.00, 'https://www.iett.istanbul',                 'Öğrenci belgesi + başvuru formu.',                      '🚌'),
            ('EGO Başkent Kart',  'Ulaşım',    'Öğrenci tarifesi',      180.00, 'https://www.ego.gov.tr',                    'Öğrenci belgesi + başvuru.',                            '🚇'),
            ('BluTV',             'Video',     '%50 öğrenci',           25.00,  'https://www.blutv.com.tr',                  'UNiDAYS doğrulaması.',                                   '📺'),
            ('Exxen',             'Video',     'Öğrenci paketi',        20.00,  'https://www.exxen.com',                     'Üniversite mail doğrulaması.',                          '🎬'),
            ('Duolingo Super',    'Eğitim',    '2 ay ücretsiz',         60.00,  'https://www.duolingo.com',                  'Öğrenci e-posta doğrulaması.',                          '🦉'),
            ('LinkedIn Learning', 'Eğitim',    'Ücretsiz (bazı üni)',  150.00, 'https://www.linkedin.com/learning/',        'Üniversite kurumsal aboneliği üzerinden.',             '🎓'),
            ('Kitapyurdu',        'Kitap',     'Öğrenci kuponları',     40.00,  'https://www.kitapyurdu.com',                'Üye ol + öğrenci kampanyaları takip et.',               '📚'),
            ('Samsung Store Edu', 'Teknoloji', 'Öğrenci fiyatı',        300.00, 'https://shop.samsung.com/tr',               'UNiDAYS doğrulaması.',                                  '📱'),
            ('Apple Education',   'Teknoloji', 'Öğrenci fiyatı',        400.00, 'https://www.apple.com/tr/shop/education-pricing', 'Apple öğrenci mağazası.',                            '🍎'),
            ('THY Miles&Smiles',  'Seyahat',  'Öğrenci tarifesi',      100.00, 'https://www.turkishairlines.com/tr-tr/',    'Student program kaydı.',                                '✈️'),
            ('Bilyoner Cafe',     'Yemek',     'Kampüs öğle menüsü',    120.00, '#',                                         'Kampüs kartı yeterli.',                                 '🍽️')
        ");
    }

    // ───────────────────────────────────────────────────
    // CITIES SEED DATA (ilk kurulumda şehirleri doldur)
    // ───────────────────────────────────────────────────
    $checkCities = $db->query("SELECT COUNT(*) FROM cities")->fetchColumn();
    if ((int)$checkCities === 0) {
        $db->exec("INSERT INTO cities (slug, name, emoji, description, living_score, rent_single, rent_shared, dorm_private, dorm_kyk, food, transport, entertainment, utilities, tip_1, tip_2) VALUES
            ('istanbul', 'İstanbul', '🏙️', 'Türkiye\'nin en büyük şehri, iş ve kültür merkezi', 4.2, 25000, 12000, 8000, 1000, 6000, 1800, 3000, 1500, 'İETT öğrenci kartı ile %70 indirim', 'Kadıköy ve Beşiktaş kampüslere yakın, ulaşım kolay'),
            ('ankara', 'Ankara', '🏛️', 'Başkent, siyaset ve üniversite şehri', 4.0, 18000, 8000, 6000, 1000, 5000, 1200, 2000, 1200, 'EGO öğrenci kartı zorunlu', 'Kızılay merkezi, ulaşım çok iyi'),
            ('izmir', 'İzmir', '🌊', 'Ege kıyısında yaşam kalitesi yüksek şehir', 4.5, 20000, 9000, 7000, 1000, 5500, 1200, 2500, 1100, 'İZULAŞ öğrenci kartı büyük tasarruf', 'Alsancak ve Bornova kampüslere yakın'),
            ('bursa', 'Bursa', '🏔️', 'Sanayi şehri, uygun yaşam maliyeti', 3.8, 14000, 6500, 5000, 1000, 4500, 1000, 1500, 1000, 'Bursaray öğrenci kartı ucuz', 'Nilüfer ilçesi öğrencilere çok uygun'),
            ('antalya', 'Antalya', '🌴', 'Turizm başkenti, yıl boyu güneş', 4.1, 16000, 7500, 6000, 1000, 5000, 1100, 2200, 1000, 'Yazın part-time turizm iş imkânı bol', 'Kampüse yakın mahallelerde kira uygun'),
            ('eskisehir', 'Eskişehir', '🚋', 'Öğrenci şehri, ucuz ve yaşanabilir', 4.6, 10000, 5000, 4000, 1000, 4000, 800, 1500, 900, 'Tramvay öğrenci kartı çok ucuz', 'En uygun öğrenci şehirlerinden biri'),
            ('konya', 'Konya', '🌾', 'Orta Anadolu\'da uygun maliyetli şehir', 3.7, 10000, 4500, 4000, 1000, 3800, 700, 1200, 800, 'Yaşam maliyeti Türkiye ortalamasının altında', 'KYK yurtları kaliteli'),
            ('adana', 'Adana', '🌶️', 'Akdeniz\'in büyük şehri, uygun fiyatlar', 3.6, 11000, 5000, 4500, 1000, 4200, 800, 1300, 900, 'Yazın sıcaklık yüksek, klima masrafını hesapla', 'Öğrenci yoğun mahallelerde kira düşük'),
            ('trabzon', 'Trabzon', '🌿', 'Karadeniz\'in incisi, huzurlu yaşam', 3.9, 10000, 4500, 3500, 1000, 4000, 700, 1200, 800, 'Doğa aktiviteleri parasız', 'KYK yurtları çok tercih ediliyor'),
            ('samsun', 'Samsun', '⚓', 'Karadeniz sahil şehri, uygun yaşam', 3.8, 9000, 4000, 3500, 1000, 3800, 700, 1100, 750, 'Deniz manzaralı ucuz kiralar', 'HÜDA öğrenci indirimleri var'),
            ('gaziantep', 'Gaziantep', '🥙', 'Güneydoğu\'nun sanayi merkezi', 3.7, 9000, 4200, 3500, 1000, 4000, 650, 1100, 750, 'Yemek masrafı çok düşük, kebap ucuz', 'Üniversite yurtları kaliteli'),
            ('kayseri', 'Kayseri', '🏔️', 'İç Anadolu sanayi şehri', 3.6, 9000, 4000, 3500, 1000, 3700, 650, 1000, 750, 'KYK yurtları dolu, erken başvur', 'Şehir içi ulaşım ucuz'),
            ('diyarbakir', 'Diyarbakır', '🏺', 'Tarihi surlarıyla eşsiz şehir', 3.5, 8000, 3800, 3000, 1000, 3500, 600, 900, 700, 'Yaşam maliyeti çok düşük', 'KYK yurtları bölgedeki en iyileri'),
            ('sakarya', 'Sakarya', '🌲', 'Sanayi ve teknoloji şehri', 3.8, 9500, 4200, 3500, 1000, 3900, 700, 1100, 800, 'İstanbul\'a yakınlığı avantaj', 'Adapazarı merkezi uygun fiyatlı'),
            ('mersin', 'Mersin', '⛵', 'Akdeniz liman şehri', 3.9, 11000, 5000, 4500, 1000, 4200, 800, 1400, 900, 'Deniz kenarında yaşam kalitesi yüksek', 'Toroslar\'a yakın doğa aktiviteleri')
        ");
    }

    // MySQL oturum zaman dilimini ayarla (UTC+3)
    try { $db->exec("SET time_zone = '+03:00'"); } catch (PDOException $e) { /* ignore */ }
}
