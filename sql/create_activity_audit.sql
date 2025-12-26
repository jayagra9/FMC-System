-- Central activity audit table to track creates/updates/deletes across tables
CREATE TABLE IF NOT EXISTS `activity_audit` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `table_name` VARCHAR(128) NOT NULL,
  `record_id` BIGINT NOT NULL,
  `action` ENUM('create','update','delete') NOT NULL,
  `changed_by` INT DEFAULT NULL,
  `changed_at` DATETIME NOT NULL,
  `changes` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX (`table_name`),
  INDEX (`record_id`),
  INDEX (`changed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
