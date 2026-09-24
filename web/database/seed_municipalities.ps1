<?php
// Insert pre-defined 2nd District Municipalities
$municipalities = [
    [
        'name' => 'Lingayen',
        'description' => 'Capital of Pangasinan',
        'latitude' => 16.0146,
        'longitude' => 120.2327,
        'image_url' => null
    ],
    [
        'name' => 'Basista',
        'description' => 'Municipality in Pangasinan',
        'latitude' => 16.0427,
        'longitude' => 120.2436,
        'image_url' => null
    ],
    [
        'name' => 'Urbiztondo',
        'description' => 'Municipality in Pangasinan',
        'latitude' => 16.0713,
        'longitude' => 120.2189,
        'image_url' => null
    ],
    [
        'name' => 'Aguilar',
        'description' => 'Municipality in Pangasinan',
        'latitude' => 15.9896,
        'longitude' => 120.2553,
        'image_url' => null
    ],
    [
        'name' => 'Bugallon',
        'description' => 'Municipality in Pangasinan',
        'latitude' => 16.0574,
        'longitude' => 120.1905,
        'image_url' => null
    ],
    [
        'name' => 'Binmaley',
        'description' => 'Municipality in Pangasinan',
        'latitude' => 15.9789,
        'longitude' => 120.1835,
        'image_url' => null
    ],
    [
        'name' => 'Labrador',
        'description' => 'Municipality in Pangasinan',
        'latitude' => 16.0045,
        'longitude' => 120.2087,
        'image_url' => null
    ],
    [
        'name' => 'Mangatarem',
        'description' => 'Municipality in Pangasinan',
        'latitude' => 15.9523,
        'longitude' => 120.2348,
        'image_url' => null
    ]
];

& "c:\xampp\mysql\bin\mysql" -u root tourist_spot_db -e "TRUNCATE TABLE municipalities;" 2>&1

$inserted = 0;
foreach ($municipalities as $municipality) {
    $sql = "INSERT INTO municipalities (name, description, latitude, longitude, image_url, created_at, updated_at) 
            VALUES ('{$municipality['name']}', '{$municipality['description']}', {$municipality['latitude']}, {$municipality['longitude']}, NULL, NOW(), NOW());";
    
    & "c:\xampp\mysql\bin\mysql" -u root tourist_spot_db -e "$sql" 2>&1
    $inserted++;
}

Write-Host "✓ Inserted $inserted municipalities"
& "c:\xampp\mysql\bin\mysql" -u root tourist_spot_db -e "SELECT id, name FROM municipalities;"
