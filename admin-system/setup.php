<?php
// Manual setup script for tourist spot system

// Generate APP_KEY
$appKey = 'base64:' . base64_encode(random_bytes(32));

// Read .env file
$envFile = __DIR__ . '/.env';
$envContent = file_get_contents($envFile);

// Update APP_KEY in .env
$envContent = preg_replace('/APP_KEY=.*/', 'APP_KEY=' . $appKey, $envContent);

// Set database configuration
$envContent = preg_replace('/DB_CONNECTION=.*/', 'DB_CONNECTION=mysql', $envContent);
$envContent = preg_replace('/DB_HOST=.*/', 'DB_HOST=127.0.0.1', $envContent);
$envContent = preg_replace('/DB_PORT=.*/', 'DB_PORT=3306', $envContent);
$envContent = preg_replace('/DB_DATABASE=.*/', 'DB_DATABASE=tourist_spot_db', $envContent);
$envContent = preg_replace('/DB_USERNAME=.*/', 'DB_USERNAME=root', $envContent);
$envContent = preg_replace('/DB_PASSWORD=.*/', 'DB_PASSWORD=', $envContent);

// Write back to .env
file_put_contents($envFile, $envContent);

echo "✓ APP_KEY generated: " . $appKey . "\n";
echo "✓ Database configuration set\n";
echo "✓ .env file updated\n";
echo "\nNext steps:\n";
echo "1. Create MySQL database: CREATE DATABASE tourist_spot_db;\n";
echo "2. Run: php artisan migrate\n";
echo "3. Start server: php artisan serve\n";
?>
