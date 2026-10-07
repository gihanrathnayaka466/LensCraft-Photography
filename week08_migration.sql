USE lenscraft_db;

ALTER TABLE packages ADD COLUMN IF NOT EXISTS stock_qty INT UNSIGNED NOT NULL DEFAULT 99 AFTER duration_hours;
ALTER TABLE packages ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER stock_qty;

-- Make one existing account the administrator. Change the email before running this line.
-- UPDATE users SET role='admin' WHERE email='your-email@example.com';

-- Existing packages receive a practical/demo capacity.
UPDATE packages SET stock_qty=99 WHERE stock_qty IS NULL;
