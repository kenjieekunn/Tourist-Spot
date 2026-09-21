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

$columns = [];
foreach ($pdo->query('SHOW COLUMNS FROM tourist_spots') as $row) {
    $columns[$row['Field']] = $row['Type'];
}

$statements = [];

if (!isset($columns['category'])) {
    $statements[] = "ALTER TABLE tourist_spots ADD COLUMN category ENUM('beach','parks','falls','nature') NOT NULL DEFAULT 'nature' AFTER municipality_id";
}

if (!isset($columns['nearby_dining'])) {
    $statements[] = "ALTER TABLE tourist_spots ADD COLUMN nearby_dining LONGTEXT NULL AFTER image_url";
}

if (!isset($columns['nearby_gas_stations'])) {
    $statements[] = "ALTER TABLE tourist_spots ADD COLUMN nearby_gas_stations LONGTEXT NULL AFTER nearby_dining";
}

if (!isset($columns['nearby_facilities'])) {
    $statements[] = "ALTER TABLE tourist_spots ADD COLUMN nearby_facilities JSON NULL AFTER nearby_gas_stations";
}

if (!isset($columns['opening_days'])) {
    $statements[] = "ALTER TABLE tourist_spots ADD COLUMN opening_days LONGTEXT NULL AFTER opening_hours";
}

if (!isset($columns['opening_time'])) {
    $statements[] = "ALTER TABLE tourist_spots ADD COLUMN opening_time VARCHAR(5) NULL AFTER opening_days";
}

if (!isset($columns['closing_time'])) {
    $statements[] = "ALTER TABLE tourist_spots ADD COLUMN closing_time VARCHAR(5) NULL AFTER opening_time";
}

if (!isset($columns['verification_status'])) {
    $statements[] = "ALTER TABLE tourist_spots ADD COLUMN verification_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER status";
}

foreach ($statements as $statement) {
    $pdo->exec($statement);
}

if (isset($columns['status']) && str_contains($columns['status'], "active")) {
    $pdo->exec("UPDATE tourist_spots SET status = 'open' WHERE status = 'active'");
    $pdo->exec("UPDATE tourist_spots SET status = 'closed' WHERE status = 'inactive'");
    $pdo->exec("ALTER TABLE tourist_spots MODIFY COLUMN status ENUM('open','closed') NOT NULL DEFAULT 'open'");
}

if (isset($columns['verification_status'])) {
    $pdo->exec("UPDATE tourist_spots SET verification_status = 'approved'");
}

echo 'Tourist spots schema repaired.' . PHP_EOL;
