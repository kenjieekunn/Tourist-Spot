-- One-time fix for older databases missing the tourist_spots.category column
-- Safe to run in phpMyAdmin on the existing database.

ALTER TABLE `tourist_spots`
  ADD COLUMN `category` ENUM('beach', 'parks', 'falls', 'nature')
  NOT NULL DEFAULT 'nature'
  AFTER `municipality_id`;

UPDATE `tourist_spots`
SET `category` = 'nature'
WHERE `category` IS NULL;
