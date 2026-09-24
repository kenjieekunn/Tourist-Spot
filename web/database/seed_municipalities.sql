SET FOREIGN_KEY_CHECKS=0;
TRUNCATE TABLE municipalities;
SET FOREIGN_KEY_CHECKS=1;

INSERT INTO municipalities (name, description, latitude, longitude, created_at, updated_at) VALUES
('Lingayen', 'Capital of Pangasinan', 16.0146, 120.2327, NOW(), NOW()),
('Basista', 'Municipality in Pangasinan', 16.0427, 120.2436, NOW(), NOW()),
('Urbiztondo', 'Municipality in Pangasinan', 16.0713, 120.2189, NOW(), NOW()),
('Aguilar', 'Municipality in Pangasinan', 15.9896, 120.2553, NOW(), NOW()),
('Bugallon', 'Municipality in Pangasinan', 16.0574, 120.1905, NOW(), NOW()),
('Binmaley', 'Municipality in Pangasinan', 15.9789, 120.1835, NOW(), NOW()),
('Labrador', 'Municipality in Pangasinan', 16.0045, 120.2087, NOW(), NOW()),
('Mangatarem', 'Municipality in Pangasinan', 15.9523, 120.2348, NOW(), NOW());

SELECT id, name, latitude, longitude FROM municipalities ORDER BY name;
