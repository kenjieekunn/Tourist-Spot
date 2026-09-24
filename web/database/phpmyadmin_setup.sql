-- Tourist Spot System database schema for phpMyAdmin import
-- This file matches the current Laravel migrations and seed data.

CREATE DATABASE IF NOT EXISTS `tourist_spot_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `tourist_spot_db`;

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `cache` (
  `key` VARCHAR(255) NOT NULL,
  `value` MEDIUMTEXT NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`),
  KEY `idx_cache_expiration` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` VARCHAR(255) NOT NULL,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`),
  KEY `idx_cache_locks_expiration` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `municipalities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `latitude` DECIMAL(10,8) NOT NULL,
  `longitude` DECIMAL(11,8) NOT NULL,
  `image_url` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_municipalities_name` (`name`),
  KEY `idx_municipalities_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `username` VARCHAR(255) NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('super-admin', 'municipality-admin', 'user') NOT NULL DEFAULT 'user',
  `municipality_id` BIGINT UNSIGNED NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `remember_token` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_username` (`username`),
  KEY `idx_users_email` (`email`),
  KEY `idx_role_municipality` (`role`, `municipality_id`),
  KEY `idx_users_municipality_id` (`municipality_id`),
  CONSTRAINT `fk_users_municipality`
    FOREIGN KEY (`municipality_id`) REFERENCES `municipalities` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tourist_spots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `municipality_id` BIGINT UNSIGNED NOT NULL,
  `category` ENUM('beach', 'parks', 'falls', 'nature') NOT NULL DEFAULT 'nature',
  `name` VARCHAR(255) NOT NULL,
  `description` LONGTEXT NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `latitude` DECIMAL(10,8) NOT NULL,
  `longitude` DECIMAL(11,8) NOT NULL,
  `phone` VARCHAR(255) NULL,
  `website` VARCHAR(255) NULL,
  `opening_hours` LONGTEXT NULL,
  `opening_days` LONGTEXT NULL,
  `opening_time` VARCHAR(5) NULL,
  `closing_time` VARCHAR(5) NULL,
  `entrance_fee` DECIMAL(10,2) NULL,
  `image_url` VARCHAR(255) NULL,
  `nearby_dining` LONGTEXT NULL,
  `nearby_gas_stations` LONGTEXT NULL,
  `nearby_facilities` JSON NULL,
  `status` ENUM('open', 'closed') NOT NULL DEFAULT 'open',
  `verification_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tourist_spots_name` (`name`),
  KEY `idx_tourist_spots_municipality_id` (`municipality_id`),
  KEY `idx_tourist_spots_status` (`status`),
  KEY `idx_tourist_spots_name` (`name`),
  CONSTRAINT `fk_tourist_spots_municipality`
    FOREIGN KEY (`municipality_id`) REFERENCES `municipalities` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reviews` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tourist_spot_id` BIGINT UNSIGNED NOT NULL,
  `user_name` VARCHAR(255) NOT NULL,
  `rating` INT NOT NULL COMMENT '1-5 star rating',
  `comment` LONGTEXT NOT NULL,
  `images` JSON NULL COMMENT 'Array of image paths for review',
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reviews_tourist_spot_id` (`tourist_spot_id`),
  KEY `idx_reviews_status` (`status`),
  CONSTRAINT `fk_reviews_tourist_spot`
    FOREIGN KEY (`tourist_spot_id`) REFERENCES `tourist_spots` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tourist_spot_favorites` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `tourist_spot_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_tourist_spot_favorite` (`user_id`, `tourist_spot_id`),
  KEY `idx_tourist_spot_favorites_user_id` (`user_id`),
  KEY `idx_tourist_spot_favorites_tourist_spot_id` (`tourist_spot_id`),
  CONSTRAINT `fk_tourist_spot_favorites_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_tourist_spot_favorites_spot`
    FOREIGN KEY (`tourist_spot_id`) REFERENCES `tourist_spots` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `municipalities` (`id`, `name`, `description`, `latitude`, `longitude`, `image_url`, `created_at`, `updated_at`) VALUES
(1, 'Lingayen', 'The capital of Pangasinan', 16.01530000, 120.21830000, NULL, NOW(), NOW()),
(2, 'Binmaley', 'Municipality in Pangasinan', 16.06670000, 120.26670000, NULL, NOW(), NOW()),
(3, 'Urbiztondo', 'Municipality in Pangasinan', 16.13330000, 120.28330000, NULL, NOW(), NOW()),
(4, 'Basista', 'Municipality in Pangasinan', 16.08330000, 120.36670000, NULL, NOW(), NOW()),
(5, 'Labrador', 'Municipality in Pangasinan', 16.03330000, 120.31670000, NULL, NOW(), NOW()),
(6, 'Bugallon', 'Municipality in Pangasinan', 15.96670000, 120.33330000, NULL, NOW(), NOW()),
(7, 'Mangatarem', 'Municipality in Pangasinan', 15.93330000, 120.28330000, NULL, NOW(), NOW()),
(8, 'Aguilar', 'Municipality in Pangasinan', 15.91670000, 120.26670000, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `description` = VALUES(`description`),
  `latitude` = VALUES(`latitude`),
  `longitude` = VALUES(`longitude`),
  `image_url` = VALUES(`image_url`),
  `updated_at` = NOW();

INSERT INTO `users` (`id`, `name`, `email`, `username`, `email_verified_at`, `password`, `role`, `municipality_id`, `is_active`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Super Administrator', 'superadmin@gmail.com', 'superadmin@gmail.com', NULL, '$2y$12$YJrCYmtjLzHHvJ8Z1Y6H7.L5xKN8Q3X9vV7Z5Lw2m4XQ8Z9K6ZqAK', 'super-admin', NULL, 1, NULL, NOW(), NOW()),
(2, 'Lingayen Admin', 'lingayen_admin@tourist-spots.com', 'lingayen_admin', NULL, '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8', 'municipality-admin', 1, 1, NULL, NOW(), NOW()),
(3, 'Binmaley Admin', 'binmaley_admin@tourist-spots.com', 'binmaley_admin', NULL, '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8', 'municipality-admin', 2, 1, NULL, NOW(), NOW()),
(4, 'Urbiztondo Admin', 'urbiztondo_admin@tourist-spots.com', 'urbiztondo_admin', NULL, '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8', 'municipality-admin', 3, 1, NULL, NOW(), NOW()),
(5, 'Basista Admin', 'basista_admin@tourist-spots.com', 'basista_admin', NULL, '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8', 'municipality-admin', 4, 1, NULL, NOW(), NOW()),
(6, 'Labrador Admin', 'labrador_admin@tourist-spots.com', 'labrador_admin', NULL, '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8', 'municipality-admin', 5, 1, NULL, NOW(), NOW()),
(7, 'Bugallon Admin', 'bugallon_admin@tourist-spots.com', 'bugallon_admin', NULL, '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8', 'municipality-admin', 6, 1, NULL, NOW(), NOW()),
(8, 'Mangatarem Admin', 'mangatarem_admin@tourist-spots.com', 'mangatarem_admin', NULL, '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8', 'municipality-admin', 7, 1, NULL, NOW(), NOW()),
(9, 'Aguilar Admin', 'aguilar_admin@tourist-spots.com', 'aguilar_admin', NULL, '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8', 'municipality-admin', 8, 1, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `username` = VALUES(`username`),
  `password` = VALUES(`password`),
  `role` = VALUES(`role`),
  `municipality_id` = VALUES(`municipality_id`),
  `is_active` = VALUES(`is_active`),
  `updated_at` = NOW();

SET FOREIGN_KEY_CHECKS = 1;
