-- MySQL schema for simple notes app (no users)
-- Run this in your TablePlus connection or CI init step

CREATE TABLE IF NOT EXISTS notes (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	title VARCHAR(255) NULL,
	content MEDIUMTEXT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	INDEX idx_updated_at (updated_at),
	INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


