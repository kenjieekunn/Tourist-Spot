<?php

use App\Models\User;

// Get all municipality admins
$admins = User::where('role', 'municipality-admin')->with('municipality')->get();

echo "Total Municipality Admins: " . $admins->count() . "\n";
echo "=================================\n";

foreach ($admins as $admin) {
    $municipality = $admin->municipality ? $admin->municipality->name : 'NO MUNICIPALITY';
    echo $admin->email . " -> " . $municipality . "\n";
}

echo "\n================================\n";
$superAdmin = User::where('role', 'super-admin')->first();
if ($superAdmin) {
    echo "Super Admin: " . $superAdmin->email . " ✓\n";
} else {
    echo "Super Admin: NOT FOUND\n";
}
