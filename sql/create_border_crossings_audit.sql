-- Create audit table for border crossings edits
CREATE TABLE IF NOT EXISTS `border_crossings_audit` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `border_crossing_id` INT NOT NULL,
  `changed_by` INT DEFAULT NULL,
  `changed_at` DATETIME NOT NULL,
  `action` VARCHAR(32) NOT NULL,
  `changes` TEXT,
  PRIMARY KEY (`id`),
  INDEX `idx_border_crossing` (`border_crossing_id`),
  INDEX `idx_changed_by` (`changed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
