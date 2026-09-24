-- Super Admin System Database Setup
-- This script updates the database schema for the new admin system
-- Run this ONLY if you're not using Laravel migrations

-- Step 1: Update the users table
-- Update role enum to support super-admin and municipality-admin
ALTER TABLE `users` 
MODIFY COLUMN `role` ENUM('super-admin', 'municipality-admin', 'user') DEFAULT 'user';

-- Add municipality_id column if it doesn't exist
ALTER TABLE `users`
ADD COLUMN `municipality_id` BIGINT UNSIGNED NULL AFTER `role`,
ADD FOREIGN KEY (`municipality_id`) REFERENCES `municipalities` (`id`) ON DELETE SET NULL,
ADD INDEX `idx_role_municipality` (`role`, `municipality_id`);

-- Step 2: Create the Super Admin user
-- Password: SuperAdmin@123 (hashed with bcrypt)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `municipality_id`, `is_active`, `created_at`, `updated_at`)
VALUES (
    'Super Administrator',
    'superadmin@gmail.com',
    '$2y$12$YJrCYmtjLzHHvJ8Z1Y6H7.L5xKN8Q3X9vV7Z5Lw2m4XQ8Z9K6ZqAK',
    'super-admin',
    NULL,
    1,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    `password` = '$2y$12$YJrCYmtjLzHHvJ8Z1Y6H7.L5xKN8Q3X9vV7Z5Lw2m4XQ8Z9K6ZqAK',
    `role` = 'super-admin',
    `municipality_id` = NULL,
    `updated_at` = NOW();

-- Step 3: Create municipality admin users
-- Password for all: MuniAdmin@123 (same hash)
-- Hash: $2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8

-- Lingayen Admin
INSERT INTO `users` (`name`, `email`, `password`, `role`, `municipality_id`, `is_active`, `created_at`, `updated_at`)
SELECT 'Lingayen Admin', 'lingayen_admin@tourist-spots.com', '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8', 'municipality-admin', `id`, 1, NOW(), NOW()
FROM `municipalities` WHERE `name` = 'Lingayen'
ON DUPLICATE KEY UPDATE
    `password` = '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8',
    `role` = 'municipality-admin',
    `updated_at` = NOW();

-- Binmaley Admin
INSERT INTO `users` (`name`, `email`, `password`, `role`, `municipality_id`, `is_active`, `created_at`, `updated_at`)
SELECT 'Binmaley Admin', 'binmaley_admin@tourist-spots.com', '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8', 'municipality-admin', `id`, 1, NOW(), NOW()
FROM `municipalities` WHERE `name` = 'Binmaley'
ON DUPLICATE KEY UPDATE
    `password` = '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8',
    `role` = 'municipality-admin',
    `updated_at` = NOW();

-- Urbiztondo Admin
INSERT INTO `users` (`name`, `email`, `password`, `role`, `municipality_id`, `is_active`, `created_at`, `updated_at`)
SELECT 'Urbiztondo Admin', 'urbiztondo_admin@tourist-spots.com', '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8', 'municipality-admin', `id`, 1, NOW(), NOW()
FROM `municipalities` WHERE `name` = 'Urbiztondo'
ON DUPLICATE KEY UPDATE
    `password` = '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8',
    `role` = 'municipality-admin',
    `updated_at` = NOW();

-- Basista Admin
INSERT INTO `users` (`name`, `email`, `password`, `role`, `municipality_id`, `is_active`, `created_at`, `updated_at`)
SELECT 'Basista Admin', 'basista_admin@tourist-spots.com', '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8', 'municipality-admin', `id`, 1, NOW(), NOW()
FROM `municipalities` WHERE `name` = 'Basista'
ON DUPLICATE KEY UPDATE
    `password` = '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8',
    `role` = 'municipality-admin',
    `updated_at` = NOW();

-- Labrador Admin
INSERT INTO `users` (`name`, `email`, `password`, `role`, `municipality_id`, `is_active`, `created_at`, `updated_at`)
SELECT 'Labrador Admin', 'labrador_admin@tourist-spots.com', '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8', 'municipality-admin', `id`, 1, NOW(), NOW()
FROM `municipalities` WHERE `name` = 'Labrador'
ON DUPLICATE KEY UPDATE
    `password` = '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8',
    `role` = 'municipality-admin',
    `updated_at` = NOW();

-- Bugallon Admin
INSERT INTO `users` (`name`, `email`, `password`, `role`, `municipality_id`, `is_active`, `created_at`, `updated_at`)
SELECT 'Bugallon Admin', 'bugallon_admin@tourist-spots.com', '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8', 'municipality-admin', `id`, 1, NOW(), NOW()
FROM `municipalities` WHERE `name` = 'Bugallon'
ON DUPLICATE KEY UPDATE
    `password` = '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8',
    `role` = 'municipality-admin',
    `updated_at` = NOW();

-- Mangatarem Admin
INSERT INTO `users` (`name`, `email`, `password`, `role`, `municipality_id`, `is_active`, `created_at`, `updated_at`)
SELECT 'Mangatarem Admin', 'mangatarem_admin@tourist-spots.com', '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8', 'municipality-admin', `id`, 1, NOW(), NOW()
FROM `municipalities` WHERE `name` = 'Mangatarem'
ON DUPLICATE KEY UPDATE
    `password` = '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8',
    `role` = 'municipality-admin',
    `updated_at` = NOW();

-- Aguilar Admin
INSERT INTO `users` (`name`, `email`, `password`, `role`, `municipality_id`, `is_active`, `created_at`, `updated_at`)
SELECT 'Aguilar Admin', 'aguilar_admin@tourist-spots.com', '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8', 'municipality-admin', `id`, 1, NOW(), NOW()
FROM `municipalities` WHERE `name` = 'Aguilar'
ON DUPLICATE KEY UPDATE
    `password` = '$2y$12$Q8Z1Y6H7u9K6Q3X9vV7Z5Lw2m4q8Z9S7d5F3G6H8J2K7M9N4P6Q8',
    `role` = 'municipality-admin',
    `updated_at` = NOW();

-- Verify the setup
SELECT COUNT(*) as 'Super Admins' FROM users WHERE role = 'super-admin';
SELECT COUNT(*) as 'Municipality Admins' FROM users WHERE role = 'municipality-admin';
SELECT name, email, role, municipality_id FROM users WHERE role IN ('super-admin', 'municipality-admin') ORDER BY role, name;
