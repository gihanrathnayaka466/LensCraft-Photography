CREATE DATABASE IF NOT EXISTS lenscraft_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lenscraft_db;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fullname VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  address VARCHAR(255) NOT NULL,
  role ENUM('customer','photographer','admin') NOT NULL DEFAULT 'customer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS photographers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  studio_name VARCHAR(150) NOT NULL,
  category VARCHAR(80) NOT NULL,
  location VARCHAR(100) NOT NULL,
  bio TEXT,
  avatar_url VARCHAR(500),
  cover_url VARCHAR(500),
  rating DECIMAL(2,1) DEFAULT 5.0,
  base_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS packages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  photographer_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  description TEXT,
  price DECIMAL(12,2) NOT NULL,
  duration_hours DECIMAL(5,2) DEFAULT 1,
  stock_qty INT UNSIGNED NOT NULL DEFAULT 99,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (photographer_id) REFERENCES photographers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS availability (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  photographer_id INT UNSIGNED NOT NULL,
  available_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  is_booked TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_slot (photographer_id, available_date, start_time, end_time),
  FOREIGN KEY (photographer_id) REFERENCES photographers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bookings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_code VARCHAR(40) NOT NULL UNIQUE,
  user_id INT UNSIGNED NOT NULL,
  photographer_id INT UNSIGNED NOT NULL,
  package_id INT UNSIGNED NOT NULL,
  booking_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  notes TEXT,
  amount DECIMAL(12,2) NOT NULL,
  status ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (photographer_id) REFERENCES photographers(id),
  FOREIGN KEY (package_id) REFERENCES packages(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id BIGINT UNSIGNED NOT NULL,
  order_id VARCHAR(60) NOT NULL UNIQUE,
  payment_id VARCHAR(100) NULL,
  amount DECIMAL(12,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'LKR',
  method VARCHAR(40) NULL,
  status_code VARCHAR(10) NULL,
  status ENUM('pending','paid','failed','cancelled','chargedback') NOT NULL DEFAULT 'pending',
  gateway_message VARCHAR(255) NULL,
  raw_reference VARCHAR(255) NULL,
  paid_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO photographers (studio_name,category,location,bio,avatar_url,cover_url,rating,base_price)
SELECT 'Vision Art Studio','Wedding & Portrait','Kandy','Wedding, portrait and lifestyle photography with a cinematic approach.',
'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=300&q=85',
'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=1400&q=85',4.9,25000
WHERE NOT EXISTS (SELECT 1 FROM photographers WHERE studio_name='Vision Art Studio');

INSERT INTO photographers (studio_name,category,location,bio,avatar_url,cover_url,rating,base_price)
SELECT 'Urban Lens Captures','Events & Street','Colombo','Modern event coverage, candid moments and editorial street work.',
'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=300&q=85',
'https://images.unsplash.com/photo-1511285560929-80b456fea0bc?auto=format&fit=crop&w=1400&q=85',4.8,18000
WHERE NOT EXISTS (SELECT 1 FROM photographers WHERE studio_name='Urban Lens Captures');

INSERT INTO photographers (studio_name,category,location,bio,avatar_url,cover_url,rating,base_price)
SELECT 'Aura Cinematic','Commercial & Film','Matara','Commercial, fashion and cinematic visual production.',
'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=300&q=85',
'https://images.unsplash.com/photo-1554048612-b6a482bc67e5?auto=format&fit=crop&w=1400&q=85',5.0,35000
WHERE NOT EXISTS (SELECT 1 FROM photographers WHERE studio_name='Aura Cinematic');

INSERT INTO packages (photographer_id,name,description,price,duration_hours)
SELECT p.id,'Standard Session','Professional photography session',p.base_price,2
FROM photographers p
WHERE NOT EXISTS (SELECT 1 FROM packages x WHERE x.photographer_id=p.id AND x.name='Standard Session');

-- Optional demo availability. Replace with photographer-managed real slots in production.
INSERT IGNORE INTO availability (photographer_id,available_date,start_time,end_time)
SELECT id, DATE_ADD(CURDATE(), INTERVAL 1 DAY),'09:00:00','12:00:00' FROM photographers;

CREATE TABLE IF NOT EXISTS portfolio_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  photographer_id INT UNSIGNED NOT NULL,
  image_path VARCHAR(500) NOT NULL,
  caption VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (photographer_id) REFERENCES photographers(id) ON DELETE CASCADE
) ENGINE=InnoDB;
