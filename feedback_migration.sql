-- Import once into existing lenscraft_db. Do not delete existing tables.
USE lenscraft_db;
CREATE TABLE IF NOT EXISTS photographer_reviews (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 booking_id BIGINT UNSIGNED NOT NULL UNIQUE,
 photographer_id INT UNSIGNED NOT NULL,
 user_id INT UNSIGNED NOT NULL,
 rating TINYINT UNSIGNED NOT NULL,
 comment TEXT NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_review_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
 CONSTRAINT fk_review_photographer FOREIGN KEY (photographer_id) REFERENCES photographers(id) ON DELETE CASCADE,
 CONSTRAINT fk_review_customer FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_reviews_photographer_created ON photographer_reviews(photographer_id, created_at);
