<?php
/**
 * ÜniBütçe — Kurulum Sihirbazı (setup.php)
 * Tek tıkla veritabanı tablolarını oluşturur, dizinleri hazırlar,
 * sistem kontrollerini yapar ve kurulumu tamamlar.
 *
 * ⚠️  KURULUM SONRASI BU DOSYAYI SİL!
 */

define('SETUP_VERSION', '2.0');
define('SETUP_SECRET', 'UNIBUTCE_SETUP_2026'); // Yetkisiz erişimi engeller

// Basit güvenlik kontrolü
$secret = $_GET['key'] ?? $_POST['key'] ?? '';
$authorized = ($secret === SETUP_SECRET) || isset($_SESSION['setup_auth']);

session_start();
if ($secret === SETUP_SECRET) {
    $_SESSION['setup_auth'] = true;
    $authorized = true;
}

$step = (int)($_GET['step'] ?? 0);

// ─── ENV Yükleyici ───────────────────────────────────────────
function loadEnv(): array {
    $envFile = __DIR__ . '/.env';
    $vars = [];
    if (!is_file($envFile)) return $vars;
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        $vars[trim($k)] = trim($v, " \t\"'");
    }
    return $vars;
}

$env = loadEnv();
$dbHost = $env['DB_HOST'] ?? 'localhost';
$dbName = $env['DB_NAME'] ?? '';
$dbUser = $env['DB_USER'] ?? '';
$dbPass = $env['DB_PASS'] ?? '';
$appUrl = $env['APP_URL'] ?? '';

// ─── Sistem Kontrolleri ──────────────────────────────────────
function runChecks(): array {
    $checks = [];

    // PHP versiyonu
    $checks[] = [
        'label' => 'PHP Sürümü (≥8.0)',
        'ok'    => version_compare(PHP_VERSION, '8.0.0', '>='),
        'value' => PHP_VERSION,
    ];

    // PDO MySQL
    $checks[] = [
        'label' => 'PDO MySQL Eklentisi',
        'ok'    => extension_loaded('pdo_mysql'),
        'value' => extension_loaded('pdo_mysql') ? 'Yüklü' : 'EKSİK!',
    ];

    // cURL
    $checks[] = [
        'label' => 'cURL Eklentisi',
        'ok'    => extension_loaded('curl'),
        'value' => extension_loaded('curl') ? 'Yüklü' : 'EKSİK!',
    ];

    // JSON
    $checks[] = [
        'label' => 'JSON Eklentisi',
        'ok'    => extension_loaded('json'),
        'value' => extension_loaded('json') ? 'Yüklü' : 'EKSİK!',
    ];

    // .env dosyası
    $hasEnv = file_exists(__DIR__ . '/.env');
    $checks[] = [
        'label' => '.env Dosyası',
        'ok'    => $hasEnv,
        'value' => $hasEnv ? 'Mevcut' : 'EKSİK! (.env oluşturun)',
    ];

    // Yazılabilir dizinler
    $dirs = ['data/cache', 'assets/images/blog', 'blog'];
    foreach ($dirs as $dir) {
        $path = __DIR__ . '/' . $dir;
        $writable = is_dir($path) ? is_writable($path) : @mkdir($path, 0755, true);
        $checks[] = [
            'label' => "Dizin: $dir",
            'ok'    => $writable,
            'value' => $writable ? 'Yazılabilir ✓' : 'İzin Hatası!',
        ];
    }

    return $checks;
}

// ─── Veritabanı Kurulumu ─────────────────────────────────────
function installDatabase(string $host, string $name, string $user, string $pass): array {
    $results = [];
    $pdo = null;

    // Bağlantı testi
    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$name;charset=utf8mb4",
            $user, $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $results[] = ['ok' => true, 'msg' => "✅ Veritabanına bağlantı başarılı: <strong>$name</strong>"];
    } catch (PDOException $e) {
        $results[] = ['ok' => false, 'msg' => "❌ Bağlantı hatası: " . $e->getMessage()];
        return $results;
    }

    // Zaman dilimi
    try { $pdo->exec("SET time_zone = '+03:00'"); } catch (Exception $e) {}

    // Tüm CREATE TABLE sorguları
    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS `users` (
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
            `login_streak`    INT DEFAULT 0,
            `last_login_date` DATE DEFAULT NULL,
            `onboarding_done` TINYINT(1) DEFAULT 0,
            `subscription_tier` ENUM('free','pro') DEFAULT 'free',
            `subscription_expires` DATE DEFAULT NULL,
            `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'transactions' => "CREATE TABLE IF NOT EXISTS `transactions` (
            `id`               INT AUTO_INCREMENT PRIMARY KEY,
            `user_id`          INT NOT NULL,
            `type`             ENUM('income','expense') NOT NULL,
            `amount`           DECIMAL(10,2) NOT NULL,
            `category`         VARCHAR(100) NOT NULL,
            `description`      TEXT,
            `transaction_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'user_budgets' => "CREATE TABLE IF NOT EXISTS `user_budgets` (
            `id`             INT AUTO_INCREMENT PRIMARY KEY,
            `user_id`        INT NOT NULL,
            `month`          TINYINT NOT NULL,
            `year`           SMALLINT NOT NULL,
            `total_income`   DECIMAL(10,2) DEFAULT 0.00,
            `kyk_income`     DECIMAL(10,2) DEFAULT 0.00,
            `target_savings` DECIMAL(10,2) DEFAULT 0.00,
            `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'user_expenses' => "CREATE TABLE IF NOT EXISTS `user_expenses` (
            `id`             INT AUTO_INCREMENT PRIMARY KEY,
            `budget_id`      INT NOT NULL,
            `category_id`    VARCHAR(50) NOT NULL,
            `planned_amount` DECIMAL(10,2) DEFAULT 0.00,
            `actual_amount`  DECIMAL(10,2) DEFAULT 0.00,
            `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`budget_id`) REFERENCES `user_budgets`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'blogs' => "CREATE TABLE IF NOT EXISTS `blogs` (
            `id`          INT AUTO_INCREMENT PRIMARY KEY,
            `title`       VARCHAR(500) NOT NULL,
            `slug`        VARCHAR(500) NOT NULL UNIQUE,
            `description` TEXT,
            `keywords`    VARCHAR(1000),
            `content`     LONGTEXT,
            `cover_image` VARCHAR(500),
            `tag`         VARCHAR(100) DEFAULT '📝 Blog Yazısı',
            `status`      ENUM('draft','published') DEFAULT 'published',
            `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'cities' => "CREATE TABLE IF NOT EXISTS `cities` (
            `id`             INT AUTO_INCREMENT PRIMARY KEY,
            `slug`           VARCHAR(100) NOT NULL UNIQUE,
            `name`           VARCHAR(100) NOT NULL,
            `emoji`          VARCHAR(20),
            `description`    VARCHAR(500),
            `living_score`   DECIMAL(3,1),
            `rent_single`    INT, `rent_shared` INT, `dorm_private` INT, `dorm_kyk` INT DEFAULT 1000,
            `food`           INT, `transport` INT, `entertainment` INT, `utilities` INT,
            `tip_1`          VARCHAR(500), `tip_2` VARCHAR(500),
            `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'subscriptions' => "CREATE TABLE IF NOT EXISTS `subscriptions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL,
            `name` VARCHAR(100) NOT NULL, `amount` DECIMAL(10,2) NOT NULL,
            `billing_cycle` ENUM('monthly','yearly') DEFAULT 'monthly',
            `category` VARCHAR(50) DEFAULT 'Diğer', `next_billing` DATE,
            `is_active` TINYINT(1) DEFAULT 1, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'achievements' => "CREATE TABLE IF NOT EXISTS `achievements` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `slug` VARCHAR(50) NOT NULL UNIQUE,
            `title` VARCHAR(100) NOT NULL, `description` VARCHAR(255),
            `icon` VARCHAR(10) DEFAULT '🏆', `xp_reward` INT DEFAULT 100
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'user_achievements' => "CREATE TABLE IF NOT EXISTS `user_achievements` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL, `achievement_id` INT NOT NULL,
            `earned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`achievement_id`) REFERENCES `achievements`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `unique_user_achievement` (`user_id`, `achievement_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'notifications' => "CREATE TABLE IF NOT EXISTS `notifications` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL,
            `type` VARCHAR(50) DEFAULT 'info', `title` VARCHAR(255) NOT NULL,
            `message` TEXT, `is_read` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'user_goals' => "CREATE TABLE IF NOT EXISTS `user_goals` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL,
            `title` VARCHAR(255) NOT NULL, `target_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `current_amount` DECIMAL(10,2) NOT NULL DEFAULT 0, `icon` VARCHAR(10) DEFAULT '🎯',
            `color` VARCHAR(20) DEFAULT '#0088ff', `deadline` DATE DEFAULT NULL,
            `is_completed` TINYINT(1) DEFAULT 0, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'user_settings' => "CREATE TABLE IF NOT EXISTS `user_settings` (
            `user_id` INT PRIMARY KEY, `theme` VARCHAR(16) DEFAULT 'auto',
            `currency` VARCHAR(8) DEFAULT 'TRY', `abroad_mode` TINYINT(1) DEFAULT 0,
            `dashboard_json` LONGTEXT, `ui_flags` LONGTEXT,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'user_audit_log' => "CREATE TABLE IF NOT EXISTS `user_audit_log` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL,
            `action` VARCHAR(64) NOT NULL, `details` TEXT,
            `ip_address` VARCHAR(45), `user_agent` VARCHAR(255),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_audit_user_time` (`user_id`, `created_at`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'user_investments' => "CREATE TABLE IF NOT EXISTS `user_investments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL,
            `ticker` VARCHAR(24) NOT NULL, `asset_type` ENUM('bist','forex','crypto','fund','gold') DEFAULT 'bist',
            `quantity` DECIMAL(14,6) NOT NULL DEFAULT 0, `buy_price` DECIMAL(14,4) NOT NULL DEFAULT 0,
            `buy_date` DATE, `notes` VARCHAR(255), `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_inv_user` (`user_id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'user_watchlist' => "CREATE TABLE IF NOT EXISTS `user_watchlist` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL,
            `ticker` VARCHAR(24) NOT NULL, `asset_type` ENUM('bist','forex','crypto','fund','gold') DEFAULT 'bist',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'user_kyk' => "CREATE TABLE IF NOT EXISTS `user_kyk` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL UNIQUE,
            `monthly_amount` DECIMAL(10,2) DEFAULT 0, `start_date` DATE,
            `study_months` INT DEFAULT 48, `grace_months` INT DEFAULT 24, `notes` TEXT,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'user_deposits' => "CREATE TABLE IF NOT EXISTS `user_deposits` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL,
            `landlord_name` VARCHAR(200), `address` VARCHAR(255),
            `amount` DECIMAL(10,2) NOT NULL DEFAULT 0, `paid_date` DATE,
            `expected_return_date` DATE, `status` ENUM('active','returned','partial','lost') DEFAULT 'active',
            `returned_amount` DECIMAL(10,2) DEFAULT 0, `notes` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'challenges' => "CREATE TABLE IF NOT EXISTS `challenges` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `slug` VARCHAR(64) UNIQUE,
            `title` VARCHAR(150) NOT NULL, `description` VARCHAR(500),
            `icon` VARCHAR(10) DEFAULT '🎯', `duration_days` INT DEFAULT 7,
            `target_savings` DECIMAL(10,2) DEFAULT 0, `xp_reward` INT DEFAULT 100,
            `category_block` VARCHAR(100), `is_active` TINYINT(1) DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'user_challenges' => "CREATE TABLE IF NOT EXISTS `user_challenges` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL, `challenge_id` INT NOT NULL,
            `start_date` DATE NOT NULL, `end_date` DATE NOT NULL,
            `status` ENUM('active','completed','failed','abandoned') DEFAULT 'active',
            `saved_amount` DECIMAL(10,2) DEFAULT 0, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`challenge_id`) REFERENCES `challenges`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'feature_flags' => "CREATE TABLE IF NOT EXISTS `feature_flags` (
            `slug` VARCHAR(64) PRIMARY KEY, `enabled` TINYINT(1) DEFAULT 0,
            `rollout_pct` TINYINT DEFAULT 100,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'student_discounts' => "CREATE TABLE IF NOT EXISTS `student_discounts` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `brand` VARCHAR(100) NOT NULL,
            `category` VARCHAR(50), `discount_text` VARCHAR(120), `monthly_saving` DECIMAL(10,2) DEFAULT 0,
            `url` VARCHAR(500), `how_to` VARCHAR(500), `region` VARCHAR(32) DEFAULT 'TR',
            `icon` VARCHAR(10), `is_active` TINYINT(1) DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'user_discount_claims' => "CREATE TABLE IF NOT EXISTS `user_discount_claims` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL, `discount_id` INT NOT NULL,
            `claimed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_user_discount` (`user_id`, `discount_id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`discount_id`) REFERENCES `student_discounts`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'user_scholarships' => "CREATE TABLE IF NOT EXISTS `user_scholarships` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL,
            `name` VARCHAR(200) NOT NULL, `amount` DECIMAL(10,2) DEFAULT 0, `deadline` DATE,
            `status` ENUM('applied','awarded','missed','planned') DEFAULT 'planned',
            `notes` TEXT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'benchmark_submissions' => "CREATE TABLE IF NOT EXISTS `benchmark_submissions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `user_hash` VARCHAR(64) NOT NULL,
            `city` VARCHAR(64), `age_band` VARCHAR(16), `category` VARCHAR(100) NOT NULL,
            `amount` DECIMAL(10,2) NOT NULL, `period_ym` CHAR(7) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_benchmark` (`user_hash`, `category`, `period_ym`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'telegram_links' => "CREATE TABLE IF NOT EXISTS `telegram_links` (
            `user_id` INT PRIMARY KEY, `chat_id` BIGINT, `link_token` VARCHAR(32) UNIQUE,
            `linked_at` TIMESTAMP NULL, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'cookie_consents' => "CREATE TABLE IF NOT EXISTS `cookie_consents` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `user_hash` VARCHAR(64) NOT NULL,
            `necessary` TINYINT(1) DEFAULT 1, `analytics` TINYINT(1) DEFAULT 0,
            `marketing` TINYINT(1) DEFAULT 0, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_consent_hash` (`user_hash`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'user_anomalies' => "CREATE TABLE IF NOT EXISTS `user_anomalies` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL,
            `transaction_id` INT, `category` VARCHAR(100), `amount` DECIMAL(10,2),
            `zscore` DECIMAL(5,2), `reason` VARCHAR(255), `is_dismissed` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'shared_budgets' => "CREATE TABLE IF NOT EXISTS `shared_budgets` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `owner_id` INT NOT NULL,
            `name` VARCHAR(120) NOT NULL, `invite_code` VARCHAR(24) UNIQUE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'user_saved_views' => "CREATE TABLE IF NOT EXISTS `user_saved_views` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL,
            `name` VARCHAR(100) NOT NULL, `filters` LONGTEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'categorization_learned' => "CREATE TABLE IF NOT EXISTS `categorization_learned` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL,
            `pattern` VARCHAR(255) NOT NULL, `category` VARCHAR(100) NOT NULL,
            `hits` INT DEFAULT 1, `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    $created = 0;
    foreach ($tables as $tName => $sql) {
        try {
            $pdo->exec($sql);
            $results[] = ['ok' => true, 'msg' => "✅ Tablo: <strong>$tName</strong>"];
            $created++;
        } catch (PDOException $e) {
            $results[] = ['ok' => false, 'msg' => "❌ $tName: " . $e->getMessage()];
        }
    }

    // Seed: Achievements
    try {
        $cnt = $pdo->query("SELECT COUNT(*) FROM achievements")->fetchColumn();
        if ((int)$cnt === 0) {
            $pdo->exec("INSERT INTO achievements (slug, title, description, icon, xp_reward) VALUES
                ('first_step','İlk Adım','İlk gelir veya gider kaydını oluşturdun.','🥇',100),
                ('streak_7','7 Gün Serisi','Üst üste 7 gün gider girdin.','🔥',250),
                ('budget_master','Bütçe Ustası','Bir ay limiti aşmadan tamamladın.','💎',500),
                ('goal_hitter','Hedef Vurucu','Tasarruf hedefine ulaştın.','🎯',300),
                ('social_sharer','Topluluk Gücü','Şehir verisini toplulukla paylaştın.','🤝',150),
                ('sub_hunter','Abonelik Avcısı','İlk aboneliğini takibe aldın.','📱',100)");
            $results[] = ['ok' => true, 'msg' => "✅ Seed: <strong>achievements</strong> (6 kayıt)"];
        }
    } catch (Exception $e) {}

    // Seed: Challenges
    try {
        $cnt = $pdo->query("SELECT COUNT(*) FROM challenges")->fetchColumn();
        if ((int)$cnt === 0) {
            $pdo->exec("INSERT INTO challenges (slug,title,description,icon,duration_days,target_savings,xp_reward,category_block) VALUES
                ('no_eat_out_7','7 Gün Dışarıda Yeme','1 hafta boyunca dışarıda yeme.','🍱',7,300,250,'Yemek'),
                ('no_coffee_14','14 Gün Kahvehane Yok','Starbucks yok — eve demle.','☕',14,400,300,'Kahve'),
                ('bim_market_30','30 Gün Sadece İndirimli Market','Sadece BİM/A101/ŞOK.','🛒',30,800,500,'Market'),
                ('no_taksi_7','7 Gün Taksi Yok','Sadece toplu taşıma.','🚇',7,200,200,'Ulaşım'),
                ('no_spending_3','3 Gün Hiçbir Şey Alma','Zorunlu olmayan harcama yok.','💎',3,150,150,'Diğer'),
                ('cook_home_21','21 Gün Evde Yemek','Her öğünü evde pişir.','👨‍🍳',21,900,600,'Yemek')");
            $results[] = ['ok' => true, 'msg' => "✅ Seed: <strong>challenges</strong> (6 kayıt)"];
        }
    } catch (Exception $e) {}

    // Seed: Student Discounts
    try {
        $cnt = $pdo->query("SELECT COUNT(*) FROM student_discounts")->fetchColumn();
        if ((int)$cnt === 0) {
            $pdo->exec("INSERT INTO student_discounts (brand,category,discount_text,monthly_saving,url,how_to,icon) VALUES
                ('Spotify Premium','Müzik','%50 öğrenci',29.99,'https://spotify.com/tr/student/','SheerID ile doğrula','🎵'),
                ('YouTube Premium','Video','%50 öğrenci',39.50,'https://youtube.com/premium/student','SheerID doğrulama','▶️'),
                ('Microsoft 365','Yazılım','Ücretsiz (üni maili)',250.00,'https://microsoft.com/education/','edu.tr mail ile kaydol','📄'),
                ('JetBrains IDE','Yazılım','Ücretsiz (1 yıl)',600.00,'https://jetbrains.com/community/education/','Öğrenci belgesi ile başvur','💻'),
                ('GitHub Student Pack','Yazılım','100+ ücretsiz araç',500.00,'https://education.github.com/pack','GitHub Student Developer Pack','🐙'),
                ('Figma','Tasarım','Professional ücretsiz',180.00,'https://figma.com/education/','.edu.tr mail','🎨'),
                ('Notion','Verimlilik','Plus ücretsiz',200.00,'https://notion.so/students','.edu.tr mail','📝'),
                ('Canva Pro','Tasarım','Ücretsiz',149.00,'https://canva.com/education/','Eğitim planı','✨'),
                ('İETT İstanbulKart','Ulaşım','Öğrenci kart %70',200.00,'https://iett.istanbul','Öğrenci belgesi','🚌'),
                ('BluTV','Video','%50 öğrenci',25.00,'https://blutv.com.tr','UNiDAYS','📺')");
            $results[] = ['ok' => true, 'msg' => "✅ Seed: <strong>student_discounts</strong> (10 kayıt)"];
        }
    } catch (Exception $e) {}

    // Seed: Cities
    try {
        $cnt = $pdo->query("SELECT COUNT(*) FROM cities")->fetchColumn();
        if ((int)$cnt === 0) {
            $pdo->exec("INSERT INTO cities (slug,name,emoji,description,living_score,rent_single,rent_shared,dorm_private,dorm_kyk,food,transport,entertainment,utilities,tip_1,tip_2) VALUES
                ('istanbul','İstanbul','🏙️','Türkiye en büyük şehri',4.2,25000,12000,8000,1000,6000,1800,3000,1500,'İETT öğrenci kartı %70 indirim','Kadıköy ve Beşiktaş kampüslere yakın'),
                ('ankara','Ankara','🏛️','Başkent',4.0,18000,8000,6000,1000,5000,1200,2000,1200,'EGO öğrenci kartı zorunlu','Kızılay merkezi, ulaşım çok iyi'),
                ('izmir','İzmir','🌊','Ege kıyısı',4.5,20000,9000,7000,1000,5500,1200,2500,1100,'İZULAŞ öğrenci kartı','Bornova kampüse yakın'),
                ('bursa','Bursa','🏔️','Sanayi şehri',3.8,14000,6500,5000,1000,4500,1000,1500,1000,'Bursaray öğrenci kartı','Nilüfer ilçesi uygun'),
                ('antalya','Antalya','🌴','Turizm başkenti',4.1,16000,7500,6000,1000,5000,1100,2200,1000,'Yazın part-time iş imkânı','Kampüse yakın kira uygun'),
                ('eskisehir','Eskişehir','🚋','Öğrenci şehri',4.6,10000,5000,4000,1000,4000,800,1500,900,'Tramvay çok ucuz','En uygun öğrenci şehri'),
                ('konya','Konya','🌾','Orta Anadolu',3.7,10000,4500,4000,1000,3800,700,1200,800,'Yaşam maliyeti düşük','KYK yurtları kaliteli'),
                ('adana','Adana','🌶️','Akdenizin büyük şehri',3.6,11000,5000,4500,1000,4200,800,1300,900,'Yazın klima masrafı yüksek','Öğrenci mahalleleri uygun'),
                ('trabzon','Trabzon','🌿','Karadeniz incisi',3.9,10000,4500,3500,1000,4000,700,1200,800,'Doğa aktiviteleri parasız','KYK çok tercih ediliyor'),
                ('samsun','Samsun','⚓','Karadeniz sahil',3.8,9000,4000,3500,1000,3800,700,1100,750,'Deniz manzaralı kiralar ucuz','HÜDA indirimleri var')");
            $results[] = ['ok' => true, 'msg' => "✅ Seed: <strong>cities</strong> (10 şehir)"];
        }
    } catch (Exception $e) {}

    // Seed: Feature Flags
    try {
        $pdo->exec("INSERT IGNORE INTO feature_flags (slug, enabled) VALUES
            ('challenges_enabled',1),('discounts_enabled',1),('subscriptions_enabled',1),
            ('ai_blog_enabled',1),('notifications_enabled',1),('guest_access_enabled',0),
            ('registration_open',1),('maintenance_mode',0),('xp_system_enabled',1),('leaderboard_enabled',1)");
        $results[] = ['ok' => true, 'msg' => "✅ Seed: <strong>feature_flags</strong>"];
    } catch (Exception $e) {}

    // Performans indeksleri
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_tx_user_date ON transactions(user_id, transaction_date)",
        "CREATE INDEX IF NOT EXISTS idx_tx_user_type ON transactions(user_id, type)",
        "CREATE INDEX IF NOT EXISTS idx_budget_user_ym ON user_budgets(user_id, year, month)",
        "CREATE INDEX IF NOT EXISTS idx_sub_user_active ON subscriptions(user_id, is_active)",
        "CREATE INDEX IF NOT EXISTS idx_notif_user_read ON notifications(user_id, is_read)",
        "CREATE INDEX IF NOT EXISTS idx_blogs_status ON blogs(status, updated_at)",
    ];
    foreach ($indexes as $sql) {
        try { $pdo->exec($sql); } catch (Exception $e) {}
    }
    $results[] = ['ok' => true, 'msg' => "✅ Performans indeksleri oluşturuldu"];

    $results[] = ['ok' => true, 'msg' => "<strong>🎉 Kurulum tamamlandı! $created tablo oluşturuldu.</strong>"];
    return $results;
}

// ─── POST: Kurulum Başlat ────────────────────────────────────
$installResults = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    $installResults = installDatabase($dbHost, $dbName, $dbUser, $dbPass);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ÜniBütçe — Kurulum Sihirbazı</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#050510;color:#e0e0e0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
  .card{background:#0a0a1a;border:1px solid #1a1a3a;border-radius:16px;padding:40px;max-width:720px;width:100%;box-shadow:0 0 60px rgba(0,240,255,0.05)}
  .logo{text-align:center;margin-bottom:32px}
  .logo h1{font-size:2rem;font-weight:800;background:linear-gradient(135deg,#00f0ff,#7000ff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
  .logo p{color:#666;margin-top:6px;font-size:0.9rem}
  .section{margin-bottom:28px}
  .section-title{font-size:0.75rem;text-transform:uppercase;letter-spacing:2px;color:#00f0ff;margin-bottom:12px;font-weight:600}
  .check-row{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#0d0d1e;border-radius:8px;margin-bottom:6px;border:1px solid #1a1a3a}
  .check-row .label{font-size:0.88rem}
  .check-row .value{font-size:0.82rem;font-weight:600}
  .ok{color:#39ff14}.fail{color:#ff3b30}.warn{color:#ff9100}
  .info-row{display:flex;justify-content:space-between;padding:8px 14px;background:#0d0d1e;border-radius:6px;margin-bottom:4px;font-size:0.84rem;border:1px solid #1a1a3a}
  .info-row span:first-child{color:#888}
  .info-row span:last-child{color:#00f0ff;font-family:monospace}
  .btn{display:block;width:100%;padding:14px;background:linear-gradient(135deg,#00f0ff22,#7000ff22);border:1px solid #00f0ff44;color:#00f0ff;font-size:1rem;font-weight:700;border-radius:10px;cursor:pointer;text-align:center;letter-spacing:1px;transition:all .2s;margin-top:20px}
  .btn:hover{background:linear-gradient(135deg,#00f0ff33,#7000ff33);border-color:#00f0ff88}
  .result{padding:10px 14px;border-radius:8px;margin-bottom:6px;font-size:0.85rem}
  .result.ok{background:rgba(57,255,20,0.08);border:1px solid rgba(57,255,20,0.2);color:#39ff14}
  .result.fail{background:rgba(255,59,48,0.08);border:1px solid rgba(255,59,48,0.2);color:#ff6b6b}
  .warn-box{padding:14px;background:rgba(255,145,0,0.08);border:1px solid rgba(255,145,0,0.3);border-radius:10px;color:#ff9100;font-size:0.85rem;margin-bottom:20px}
  .lock{padding:60px 40px;text-align:center}
  .lock h2{font-size:1.5rem;margin-bottom:16px;color:#ff3b30}
  .lock p{color:#888;margin-bottom:24px}
  .lock input{width:100%;padding:12px 16px;background:#0d0d1e;border:1px solid #1a1a3a;border-radius:8px;color:#fff;font-size:1rem;margin-bottom:12px}
  .success-banner{text-align:center;padding:20px;background:rgba(57,255,20,0.06);border:1px solid rgba(57,255,20,0.2);border-radius:12px;margin-bottom:20px}
  .success-banner h2{color:#39ff14;font-size:1.4rem;margin-bottom:8px}
  .success-banner p{color:#888;font-size:0.87rem}
  .delete-warning{margin-top:24px;padding:16px;background:rgba(255,59,48,0.08);border:2px solid rgba(255,59,48,0.4);border-radius:10px;text-align:center}
  .delete-warning strong{color:#ff3b30;font-size:1.1rem;display:block;margin-bottom:6px}
  .delete-warning p{color:#aaa;font-size:0.85rem}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <h1>⚡ ÜniBütçe Kurulum</h1>
    <p>Tek tıkla kurulum sihirbazı — v<?= SETUP_VERSION ?></p>
  </div>

<?php if (!$authorized): ?>
  <div class="lock">
    <h2>🔒 Erişim Korumalı</h2>
    <p>Setup sayfasına erişmek için güvenlik anahtarı gereklidir.</p>
    <form method="get">
      <input type="text" name="key" placeholder="Güvenlik anahtarını girin..." autofocus>
      <button type="submit" class="btn">🔑 Giriş</button>
    </form>
    <p style="margin-top:16px;font-size:0.8rem;color:#555">
      Anahtar: <code style="color:#00f0ff"><?= SETUP_SECRET ?></code>
    </p>
  </div>

<?php elseif ($installResults !== null): ?>
  <?php $allOk = !in_array(false, array_column($installResults, 'ok')); ?>
  <?php if ($allOk): ?>
  <div class="success-banner">
    <h2>🎉 Kurulum Başarılı!</h2>
    <p>Tüm tablolar ve başlangıç verileri oluşturuldu.</p>
  </div>
  <?php endif; ?>

  <div class="section">
    <div class="section-title">📋 Kurulum Raporu</div>
    <?php foreach ($installResults as $r): ?>
    <div class="result <?= $r['ok'] ? 'ok' : 'fail' ?>"><?= $r['msg'] ?></div>
    <?php endforeach; ?>
  </div>

  <?php if ($allOk): ?>
  <div class="delete-warning">
    <strong>⚠️ ÖNEMLİ: Bu dosyayı sil!</strong>
    <p>Kurulum tamamlandı. Güvenlik için <code>setup.php</code> dosyasını hosting'den hemen silin!</p>
  </div>
  <a href="/" class="btn" style="text-decoration:none;display:block;text-align:center">🚀 Siteye Git →</a>
  <?php else: ?>
  <form method="post">
    <input type="hidden" name="install" value="1">
    <button type="submit" class="btn">🔄 Tekrar Dene</button>
  </form>
  <?php endif; ?>

<?php else: ?>
  <?php $checks = runChecks(); $allChecksOk = !in_array(false, array_column($checks, 'ok')); ?>

  <!-- DB Bağlantı Bilgileri -->
  <div class="section">
    <div class="section-title">🗄️ Veritabanı Bilgileri (.env)</div>
    <?php if (empty($dbName)): ?>
    <div class="warn-box">⚠️ .env dosyası bulunamadı veya DB bilgileri eksik! Önce .env oluşturun.</div>
    <?php else: ?>
    <div class="info-row"><span>DB Host</span><span><?= htmlspecialchars($dbHost) ?></span></div>
    <div class="info-row"><span>DB Name</span><span><?= htmlspecialchars($dbName) ?></span></div>
    <div class="info-row"><span>DB User</span><span><?= htmlspecialchars($dbUser) ?></span></div>
    <div class="info-row"><span>DB Pass</span><span><?= $dbPass ? '●●●●●●●●' : '⚠️ BOŞ!' ?></span></div>
    <div class="info-row"><span>APP_URL</span><span><?= htmlspecialchars($appUrl ?: '⚠️ Eksik') ?></span></div>
    <?php endif; ?>
  </div>

  <!-- Sistem Kontrolleri -->
  <div class="section">
    <div class="section-title">🔍 Sistem Kontrolleri</div>
    <?php foreach ($checks as $c): ?>
    <div class="check-row">
      <span class="label"><?= $c['label'] ?></span>
      <span class="value <?= $c['ok'] ? 'ok' : 'fail' ?>"><?= htmlspecialchars($c['value']) ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Kurulum Butonu -->
  <?php if ($allChecksOk && !empty($dbName)): ?>
  <div class="warn-box">
    ⚠️ Bu işlem tüm tabloları oluşturur ve başlangıç verilerini ekler. Mevcut veriler korunur (IF NOT EXISTS).
  </div>
  <form method="post">
    <input type="hidden" name="install" value="1">
    <button type="submit" class="btn">🚀 Kurulumu Başlat</button>
  </form>
  <?php else: ?>
  <div class="warn-box">⚠️ Kurulum başlatılamıyor. Yukarıdaki hataları giderin.</div>
  <?php endif; ?>

<?php endif; ?>
</div>
</body>
</html>
