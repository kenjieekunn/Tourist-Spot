<?php
// Check and update municipality image URLs

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n========================================\n";
echo "Municipality Images - Database Check\n";
echo "========================================\n\n";

// Get all municipalities
$municipalities = DB::table('municipalities')->get();

echo "Found " . count($municipalities) . " municipalities\n\n";

// Check each municipality
foreach ($municipalities as $municipality) {
    echo "ID: {$municipality->id} | Name: {$municipality->name}\n";
    echo "  Current image_url: " . ($municipality->image_url ?? 'NULL') . "\n";
    
    // Check if image exists in storage
    $imageDir = 'municipality-images';
    $files = Storage::disk('public')->files($imageDir);
    
    // Try to find an image for this municipality (simple matching)
    $found = false;
    foreach ($files as $file) {
        // Just check if we have images
        if (!$found && strpos($file, $imageDir) !== false) {
            $imageUrl = '/storage/' . $file;
            echo "  Found image: $imageUrl\n";
            $found = true;
        }
    }
    
    if (!$found) {
        echo "  ⚠️  No image found\n";
    }
    echo "\n";
}

// Show storage directory contents
echo "========================================\n";
echo "Storage Directory Contents\n";
echo "========================================\n\n";

$files = Storage::disk('public')->files('municipality-images');
echo "Total municipality images: " . count($files) . "\n\n";

if (count($files) > 0) {
    echo "Images:\n";
    foreach (array_slice($files, 0, 5) as $file) {
        echo "  - /storage/$file\n";
    }
    if (count($files) > 5) {
        echo "  ... and " . (count($files) - 5) . " more\n";
    }
}

echo "\n========================================\n";
echo "✅ Check Complete\n";
echo "========================================\n\n";
?>
