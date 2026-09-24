<?php
// Diagnostic script to check superadmin account
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== Superadmin Account Diagnostic ===\n\n";

// Check for old superadmin
$oldSuperAdmin = User::where('email', 'superadmin@tourist-spots.com')->first();
if ($oldSuperAdmin) {
    echo "✓ Old Superadmin Found:\n";
    echo "  - ID: {$oldSuperAdmin->id}\n";
    echo "  - Name: {$oldSuperAdmin->name}\n";
    echo "  - Email: {$oldSuperAdmin->email}\n";
    echo "  - Username: {$oldSuperAdmin->username}\n";
    echo "  - Role: {$oldSuperAdmin->role}\n";
    echo "  - Active: " . ($oldSuperAdmin->is_active ? 'Yes' : 'No') . "\n\n";
} else {
    echo "✗ Old Superadmin NOT found\n\n";
}

// Check for new superadmin
$newSuperAdmin = User::where('email', 'superadmin@gmail.com')->first();
if ($newSuperAdmin) {
    echo "✓ New Superadmin Found:\n";
    echo "  - ID: {$newSuperAdmin->id}\n";
    echo "  - Name: {$newSuperAdmin->name}\n";
    echo "  - Email: {$newSuperAdmin->email}\n";
    echo "  - Username: {$newSuperAdmin->username}\n";
    echo "  - Role: {$newSuperAdmin->role}\n";
    echo "  - Active: " . ($newSuperAdmin->is_active ? 'Yes' : 'No') . "\n\n";
    
    // Test password
    echo "Testing password 'superadmin@123':\n";
    if (Hash::check('superadmin@123', $newSuperAdmin->password)) {
        echo "  ✓ Password is CORRECT\n\n";
    } else {
        echo "  ✗ Password is INCORRECT\n\n";
    }
} else {
    echo "✗ New Superadmin NOT found\n\n";
}

// Check for duplicate usernames
$duplicateUsernames = User::select('username')
    ->groupBy('username')
    ->havingRaw('COUNT(*) > 1')
    ->get();

if ($duplicateUsernames->count() > 0) {
    echo "⚠ Duplicate Usernames Found:\n";
    foreach ($duplicateUsernames as $dup) {
        $users = User::where('username', $dup->username)->get();
        echo "  - '{$dup->username}': " . $users->count() . " users\n";
        foreach ($users as $user) {
            echo "    • {$user->email} (ID: {$user->id})\n";
        }
    }
    echo "\n";
} else {
    echo "✓ No duplicate usernames\n\n";
}

// Check for duplicate emails
$duplicateEmails = User::select('email')
    ->groupBy('email')
    ->havingRaw('COUNT(*) > 1')
    ->get();

if ($duplicateEmails->count() > 0) {
    echo "⚠ Duplicate Emails Found:\n";
    foreach ($duplicateEmails as $dup) {
        $users = User::where('email', $dup->email)->get();
        echo "  - '{$dup->email}': " . $users->count() . " users\n";
        foreach ($users as $user) {
            echo "    • {$user->username} (ID: {$user->id})\n";
        }
    }
    echo "\n";
} else {
    echo "✓ No duplicate emails\n\n";
}

// List all super admins
$superAdmins = User::where('role', 'super-admin')->get();
echo "All Super Admins (" . $superAdmins->count() . "):\n";
foreach ($superAdmins as $admin) {
    echo "  - {$admin->email} ({$admin->username}) - Active: " . ($admin->is_active ? 'Yes' : 'No') . "\n";
}
echo "\n";

echo "=== End Diagnostic ===\n";
