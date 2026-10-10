-- Authentication security hardening (MariaDB/MySQL)
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `failed_login_attempts` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `last_login`,
  ADD COLUMN IF NOT EXISTS `locked_until` DATETIME NULL AFTER `failed_login_attempts`,
  ADD COLUMN IF NOT EXISTS `last_failed_login_at` DATETIME NULL AFTER `locked_until`,
  ADD COLUMN IF NOT EXISTS `auth_version` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `last_failed_login_at`,
  ADD COLUMN IF NOT EXISTS `mfa_email` VARCHAR(190) NULL AFTER `auth_version`,
  ADD COLUMN IF NOT EXISTS `mfa_mode` ENUM('email','off') NOT NULL DEFAULT 'email' AFTER `mfa_email`;

CREATE TABLE IF NOT EXISTS `mfa_login_challenges` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `otp_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `resend_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `last_sent_at` DATETIME NOT NULL,
  `consumed_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_mfa_user_active` (`user_id`, `consumed_at`, `expires_at`),
  CONSTRAINT `fk_mfa_login_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
