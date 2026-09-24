<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

// Get database connection
$db = $app->make('db');

// Query admins
$users = $db->table('users')
    ->where('role', 'super-admin')
    ->orWhere('role', 'municipality-admin')
    ->select('id', 'email', 'name', 'role', 'password')
    ->orderBy('role', 'desc')
    ->get();

echo "=== ADMIN ACCOUNTS IN DATABASE ===\n\n";

foreach ($users as $user) {
    echo "Email: {$user->email}\n";
    echo "Name: {$user->name}\n";
    echo "Role: {$user->role}\n";
    echo "Password Hash: {$user->password}\n";
    echo "---\n";
}
