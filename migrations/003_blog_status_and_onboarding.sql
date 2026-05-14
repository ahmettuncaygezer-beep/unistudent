-- 003_blog_status_and_onboarding.sql
ALTER TABLE blogs ADD COLUMN status ENUM('draft','published') DEFAULT 'published';
ALTER TABLE users ADD COLUMN onboarding_done TINYINT(1) DEFAULT 0;
