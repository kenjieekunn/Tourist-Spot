<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

// Get database connection and Hash service
$db = $app->make('db');
$hash = $app->make('hash');

// Update superadmin password
$updated = $db->table('users')
    ->where('role', 'super-admin')
    ->update([
        'password' => $hash->make('superadmin@123')
    ]);

if ($updated > 0) {
    echo "✓ Super Admin password has been updated to: superadmin@123\n";
    
    // Show the updated record
    $user = $db->table('users')
        ->where('role', 'super-admin')
        ->select('email', 'name', 'role', 'password')
        ->first();
    
    echo "\nUpdated Account Details:\n";
    echo "Email: {$user->email}\n";
    echo "Name: {$user->name}\n";
    echo "Role: {$user->role}\n";
    echo "New Password Hash: {$user->password}\n";
} else {
    echo "✗ No super admin found to update\n";
}
