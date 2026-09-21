<?php
// Insert pre-defined 2nd District Municipalities

$municipalities = [
    ['name' => 'Lingayen', 'description' => 'Capital of Pangasinan', 'latitude' => 16.0146, 'longitude' => 120.2327],
    ['name' => 'Basista', 'description' => 'Municipality in Pangasinan', 'latitude' => 16.0427, 'longitude' => 120.2436],
    ['name' => 'Urbiztondo', 'description' => 'Municipality in Pangasinan', 'latitude' => 16.0713, 'longitude' => 120.2189],
    ['name' => 'Aguilar', 'description' => 'Municipality in Pangasinan', 'latitude' => 15.9896, 'longitude' => 120.2553],
    ['name' => 'Bugallon', 'description' => 'Municipality in Pangasinan', 'latitude' => 16.0574, 'longitude' => 120.1905],
    ['name' => 'Binmaley', 'description' => 'Municipality in Pangasinan', 'latitude' => 15.9789, 'longitude' => 120.1835],
    ['name' => 'Labrador', 'description' => 'Municipality in Pangasinan', 'latitude' => 16.0045, 'longitude' => 120.2087],
    ['name' => 'Mangatarem', 'description' => 'Municipality in Pangasinan', 'latitude' => 15.9523, 'longitude' => 120.2348]
];

// Connect to MySQL
$conn = new mysqli("127.0.0.1", "root", "", "tourist_spot_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Clear existing municipalities (disable foreign keys first)
$conn->query("SET FOREIGN_KEY_CHECKS=0");
$conn->query("TRUNCATE TABLE municipalities");
$conn->query("SET FOREIGN_KEY_CHECKS=1");

// Insert municipalities
foreach ($municipalities as $m) {
    $sql = "INSERT INTO municipalities (name, description, latitude, longitude, created_at, updated_at) 
            VALUES ('{$m['name']}', '{$m['description']}', {$m['latitude']}, {$m['longitude']}, NOW(), NOW())";
    
    if (!$conn->query($sql)) {
        echo "Error: " . $conn->error . "\n";
    }
}

echo "✓ Successfully inserted " . count($municipalities) . " municipalities\n\n";

// Display municipalities
$result = $conn->query("SELECT id, name, latitude, longitude FROM municipalities ORDER BY name");
echo "Municipalities in database:\n";
echo str_repeat("-", 60) . "\n";
echo sprintf("%-3s %-20s %-12s %-12s\n", "ID", "Name", "Latitude", "Longitude");
echo str_repeat("-", 60) . "\n";

while ($row = $result->fetch_assoc()) {
    echo sprintf("%-3s %-20s %-12s %-12s\n", $row['id'], $row['name'], $row['latitude'], $row['longitude']);
}

$conn->close();
?>
