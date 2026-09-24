#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$status = $kernel->handle(
    $input = new Symfony\Component\Console\Input\ArrayInput([
        'command' => 'tinker',
    ]),
    new Symfony\Component\Console\Output\BufferedOutput
);

// Get database connection
$db = $app->make('db');

// Check admins
echo "=== CHECKING ADMIN ACCOUNTS ===\n\n";

$result = $db->select("SELECT email, role, municipality_id FROM users ORDER BY role DESC");

foreach ($result as $row) {
    echo $row->email . " | Role: " . $row->role . " | Municipality ID: " . ($row->municipality_id ?? 'NULL') . "\n";
}

echo "\n=== CHECKING MUNICIPALITIES ===\n\n";

$municipalities = $db->select("SELECT id, name FROM municipalities ORDER BY name ASC");

foreach ($municipalities as $muni) {
    echo "ID: " . $muni->id . " | Name: " . $muni->name . "\n";
}
