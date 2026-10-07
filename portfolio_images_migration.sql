-- Run only if portfolio_images does not already exist.
USE lenscraft_db;
CREATE TABLE IF NOT EXISTS portfolio_images (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 photographer_id INT UNSIGNED NOT NULL,
 image_path VARCHAR(500) NOT NULL,
 caption VARCHAR(255) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (photographer_id) REFERENCES photographers(id) ON DELETE CASCADE
) ENGINE=InnoDB;
