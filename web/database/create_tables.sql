-- Create users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `email_verified_at` TIMESTAMP NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'user') DEFAULT 'admin',
  `is_active` BOOLEAN DEFAULT true,
  `remember_token` VARCHAR(100),
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create municipalities table
CREATE TABLE IF NOT EXISTS `municipalities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL UNIQUE,
  `description` LONGTEXT,
  `latitude` DECIMAL(10, 8) NOT NULL,
  `longitude` DECIMAL(11, 8) NOT NULL,
  `image_url` VARCHAR(255),
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create tourist_spots table
CREATE TABLE IF NOT EXISTS `tourist_spots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `municipality_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` LONGTEXT NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `latitude` DECIMAL(10, 8) NOT NULL,
  `longitude` DECIMAL(11, 8) NOT NULL,
  `phone` VARCHAR(255),
  `website` VARCHAR(255),
  `opening_hours` LONGTEXT,
  `entrance_fee` DECIMAL(10, 2),
  `image_url` VARCHAR(255),
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  CONSTRAINT `fk_tourist_spots_municipality` FOREIGN KEY (`municipality_id`) REFERENCES `municipalities` (`id`) ON DELETE CASCADE,
  INDEX `idx_municipality_id` (`municipality_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create reviews table
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tourist_spot_id` BIGINT UNSIGNED NOT NULL,
  `user_name` VARCHAR(255) NOT NULL,
  `rating` INT NOT NULL COMMENT '1-5 star rating',
  `comment` LONGTEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  CONSTRAINT `fk_reviews_tourist_spot` FOREIGN KEY (`tourist_spot_id`) REFERENCES `tourist_spots` (`id`) ON DELETE CASCADE,
  INDEX `idx_tourist_spot_id` (`tourist_spot_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert pre-defined municipalities
INSERT INTO municipalities (name, description, latitude, longitude, created_at, updated_at) VALUES
('Lingayen', 'Capital of Pangasinan', 16.0146, 120.2327, NOW(), NOW()),
('Basista', 'Municipality in Pangasinan', 16.0427, 120.2436, NOW(), NOW()),
('Urbiztondo', 'Municipality in Pangasinan', 16.0713, 120.2189, NOW(), NOW()),
('Aguilar', 'Municipality in Pangasinan', 15.9896, 120.2553, NOW(), NOW()),
('Bugallon', 'Municipality in Pangasinan', 16.0574, 120.1905, NOW(), NOW()),
('Binmaley', 'Municipality in Pangasinan', 15.9789, 120.1835, NOW(), NOW()),
('Labrador', 'Municipality in Pangasinan', 16.0045, 120.2087, NOW(), NOW()),
('Mangatarem', 'Municipality in Pangasinan', 15.9523, 120.2348, NOW(), NOW());
