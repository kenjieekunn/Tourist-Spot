<?php
/**
 * Quick Fix Script for Superadmin Login Issues
 * Run this script directly: php fix_superadmin.php
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "\n=== Superadmin Login Fix Script ===\n\n";

try {
    // Step 1: Check for duplicate usernames and remove them
    echo "Step 1: Checking for duplicate usernames...\n";
    $duplicates = DB::table('users')
        ->select('username')
        ->groupBy('username')
        ->havingRaw('COUNT(*) > 1')
        ->get();

    if ($duplicates->count() > 0) {
        echo "  Found " . $duplicates->count() . " duplicate username(s). Removing...\n";
        foreach ($duplicates as $dup) {
            $users = DB::table('users')
                ->where('username', $dup->username)
                ->orderBy('id', 'desc')
                ->get();
            
            // Keep the first one, delete others
            for ($i = 1; $i < count($users); $i++) {
                DB::table('users')->where('id', $users[$i]->id)->delete();
                echo "    ✓ Deleted duplicate user ID: {$users[$i]->id}\n";
            }
        }
    } else {
        echo "  ✓ No duplicate usernames found\n";
    }

    // Step 2: Check for duplicate emails and remove them
    echo "\nStep 2: Checking for duplicate emails...\n";
    $emailDuplicates = DB::table('users')
        ->select('email')
        ->groupBy('email')
        ->havingRaw('COUNT(*) > 1')
        ->get();

    if ($emailDuplicates->count() > 0) {
        echo "  Found " . $emailDuplicates->count() . " duplicate email(s). Removing...\n";
        foreach ($emailDuplicates as $dup) {
            $users = DB::table('users')
                ->where('email', $dup->email)
                ->orderBy('id', 'desc')
                ->get();
            
            // Keep the first one, delete others
            for ($i = 1; $i < count($users); $i++) {
                DB::table('users')->where('id', $users[$i]->id)->delete();
                echo "    ✓ Deleted duplicate user ID: {$users[$i]->id}\n";
            }
        }
    } else {
        echo "  ✓ No duplicate emails found\n";
    }

    // Step 3: Delete old superadmin if it exists
    echo "\nStep 3: Cleaning up old superadmin account...\n";
    $oldAdmin = User::where('email', 'superadmin@tourist-spots.com')->first();
    if ($oldAdmin) {
        $oldAdmin->delete();
        echo "  ✓ Deleted old superadmin account\n";
    } else {
        echo "  ✓ No old superadmin account found\n";
    }

    // Step 4: Create or update new superadmin
    echo "\nStep 4: Setting up new superadmin account...\n";
    $newAdmin = User::where('email', 'superadmin@gmail.com')->first();
    
    if ($newAdmin) {
        $newAdmin->update([
            'name' => 'Super Administrator',
            'username' => 'superadmin@gmail.com',
            'password' => Hash::make('superadmin@123'),
            'role' => 'super-admin',
            'municipality_id' => null,
            'is_active' => true,
        ]);
        echo "  ✓ Updated existing superadmin account\n";
    } else {
        User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@gmail.com',
            'username' => 'superadmin@gmail.com',
            'password' => Hash::make('superadmin@123'),
            'role' => 'super-admin',
            'municipality_id' => null,
            'is_active' => true,
        ]);
        echo "  ✓ Created new superadmin account\n";
    }

    // Step 5: Verify the account
    echo "\nStep 5: Verifying superadmin account...\n";
    $admin = User::where('email', 'superadmin@gmail.com')->first();
    
    if (!$admin) {
        echo "  ✗ ERROR: Superadmin account not found!\n";
        exit(1);
    }

    echo "  ✓ Account found\n";
    echo "    - Email: {$admin->email}\n";
    echo "    - Username: {$admin->username}\n";
    echo "    - Role: {$admin->role}\n";
    echo "    - Active: " . ($admin->is_active ? 'Yes' : 'No') . "\n";
    
    if (Hash::check('superadmin@123', $admin->password)) {
        echo "    - Password: ✓ CORRECT\n";
    } else {
        echo "    - Password: ✗ INCORRECT\n";
        exit(1);
    }

    // Step 6: Clear cache
    echo "\nStep 6: Clearing application cache...\n";
    \Artisan::call('cache:clear');
    \Artisan::call('config:clear');
    \Artisan::call('view:clear');
    echo "  ✓ Cache cleared\n";

    echo "\n=== Fix Complete ===\n";
    echo "\nYou can now login with:\n";
    echo "  Email/Username: superadmin@gmail.com\n";
    echo "  Password: superadmin@123\n\n";

} catch (\Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
