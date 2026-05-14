-- ÜniBütçe — Tam Veritabanı Şeması
-- Bu dosya referans amaçlıdır. Tablolar config.php → ensureAllTables() ile otomatik oluşturulur.

CREATE DATABASE IF NOT EXISTS `unistudent` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `unistudent`;

-- ──────────────────────────────────────────────────────────────
-- Kullanıcılar
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `username`        VARCHAR(50) UNIQUE,
    `full_name`       VARCHAR(255) NOT NULL,
    `email`           VARCHAR(255) NOT NULL UNIQUE,
    `password_hash`   VARCHAR(255),              -- OAuth kullananlar için nullable
    `university_name` VARCHAR(255),
    `budget_json`     LONGTEXT,                  -- Bütçe hesaplayıcısının kaydedilen verisi
    `auth_provider`   ENUM('local','google','facebook') DEFAULT 'local',
    `provider_id`     VARCHAR(255),
    `role`            ENUM('user','verified','admin') DEFAULT 'user',
    `xp_points`       INT DEFAULT 0,
    `login_streak`    INT DEFAULT 0,
    `last_login_date` DATE DEFAULT NULL,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- Kullanıcı Hedefleri (Tasarruf Planı)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `user_goals` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- İşlemler (Gelir / Gider)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `transactions` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`          INT NOT NULL,
    `type`             ENUM('income','expense') NOT NULL,
    `amount`           DECIMAL(10,2) NOT NULL,
    `category`         VARCHAR(100) NOT NULL,
    `description`      TEXT,
    `transaction_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- Kullanıcı Bütçeleri (Aylık Plan)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `user_budgets` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`        INT NOT NULL,
    `month`          TINYINT NOT NULL,
    `year`           SMALLINT NOT NULL,
    `total_income`   DECIMAL(10,2) DEFAULT 0.00,
    `kyk_income`     DECIMAL(10,2) DEFAULT 0.00,
    `target_savings` DECIMAL(10,2) DEFAULT 0.00,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- Harcama Kalemleri (user_budgets'a bağlı)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `user_expenses` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `budget_id`      INT NOT NULL,
    `category_id`    VARCHAR(50) NOT NULL,       -- 'housing','food','transport' vb.
    `planned_amount` DECIMAL(10,2) DEFAULT 0.00,
    `actual_amount`  DECIMAL(10,2) DEFAULT 0.00,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`budget_id`) REFERENCES `user_budgets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- Blog Yazıları
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `blogs` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
