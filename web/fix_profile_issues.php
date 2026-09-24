<?php
/**
 * Quick Fix Script for Profile Editing Issues
 * Run: php fix_profile_issues.php
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

echo "\n=== Profile Editing Fix Script ===\n\n";

try {
    // Step 1: Check and create storage symlink
    echo "Step 1: Checking storage symlink...\n";
    if (!is_link(public_path('storage'))) {
        echo "  Creating storage symlink...\n";
        Artisan::call('storage:link');
        echo "  ✓ Storage symlink created\n";
    } else {
        echo "  ✓ Storage symlink already exists\n";
    }
    echo "\n";

    // Step 2: Create admin-profile-images directory
    echo "Step 2: Creating profile images directory...\n";
    $profileImagesDir = storage_path('app/public/admin-profile-images');
    if (!is_dir($profileImagesDir)) {
        mkdir($profileImagesDir, 0755, true);
        echo "  ✓ Directory created: {$profileImagesDir}\n";
    } else {
        echo "  ✓ Directory already exists\n";
    }
    echo "\n";

    // Step 3: Check database columns
    echo "Step 3: Checking database columns...\n";
    $missingColumns = [];

    if (!Schema::hasColumn('users', 'profile_image_path')) {
        $missingColumns[] = 'profile_image_path';
    }

    if (!Schema::hasColumn('users', 'username')) {
        $missingColumns[] = 'username';
    }

    if (count($missingColumns) > 0) {
        echo "  ⚠ Missing columns: " . implode(', ', $missingColumns) . "\n";
        echo "  Running migrations...\n";
        Artisan::call('migrate');
        echo "  ✓ Migrations completed\n";
    } else {
        echo "  ✓ All required columns exist\n";
    }
    echo "\n";

    // Step 4: Verify controller file
    echo "Step 4: Verifying controller file...\n";
    $controllerPath = app_path('Http/Controllers/AdminProfileController.php');
    if (file_exists($controllerPath)) {
        $content = file_get_contents($controllerPath);
        
        $checks = [
            'ValidationException import' => strpos($content, 'use Illuminate\\Validation\\ValidationException') !== false,
            '$hasUsernameColumn variable' => strpos($content, '$hasUsernameColumn = Schema::hasColumn') !== false,
            'replaceProfileImage method' => strpos($content, 'private function replaceProfileImage') !== false,
        ];

        $allGood = true;
        foreach ($checks as $check => $result) {
            echo "  - {$check}: " . ($result ? '✓' : '✗') . "\n";
            if (!$result) $allGood = false;
        }

        if ($allGood) {
            echo "  ✓ Controller file is correct\n";
        } else {
            echo "  ⚠ Controller file may need updates\n";
        }
    } else {
        echo "  ✗ Controller file not found\n";
    }
    echo "\n";

    // Step 5: Clear cache
    echo "Step 5: Clearing application cache...\n";
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    echo "  ✓ Cache cleared\n";
    echo "\n";

    // Step 6: Verify superadmin account
    echo "Step 6: Verifying superadmin account...\n";
    $superAdmin = \App\Models\User::where('role', 'super-admin')->first();
    if ($superAdmin) {
        echo "  ✓ Superadmin found: {$superAdmin->email}\n";
        echo "  ✓ Active: " . ($superAdmin->is_active ? 'Yes' : 'No') . "\n";
    } else {
        echo "  ✗ No superadmin found\n";
    }
    echo "\n";

    echo "=== Fix Complete ===\n\n";
    echo "Profile editing should now work correctly!\n\n";
    echo "You can now:\n";
    echo "  1. Login as superadmin\n";
    echo "  2. Go to Profile page\n";
    echo "  3. Upload a profile photo\n";
    echo "  4. Change your password\n";
    echo "  5. Click Save Changes\n\n";

} catch (\Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
