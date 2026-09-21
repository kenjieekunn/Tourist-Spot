<?php

$host = '127.0.0.1';
$port = '3306';
$db = 'tourist_spot_db';
$user = 'root';
$pass = '';

$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
    $user,
    $pass,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$tableExists = false;
$stmt = $pdo->query("SHOW TABLES LIKE 'tourist_spot_favorites'");
if ($stmt && $stmt->fetch()) {
    $tableExists = true;
}

if (!$tableExists) {
    $pdo->exec(
        "CREATE TABLE tourist_spot_favorites (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            tourist_spot_id BIGINT UNSIGNED NOT NULL,
            created_at TIMESTAMP NULL DEFAULT NULL,
            updated_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_user_tourist_spot_favorite (user_id, tourist_spot_id),
            KEY idx_tourist_spot_favorites_user_id (user_id),
            KEY idx_tourist_spot_favorites_tourist_spot_id (tourist_spot_id),
            CONSTRAINT fk_tourist_spot_favorites_user
                FOREIGN KEY (user_id) REFERENCES users (id)
                ON DELETE CASCADE,
            CONSTRAINT fk_tourist_spot_favorites_spot
                FOREIGN KEY (tourist_spot_id) REFERENCES tourist_spots (id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

echo 'Tourist spot favorites schema repaired.' . PHP_EOL;
