<?php
/**
 * Diagnostic script for profile editing issues
 * Run: php check_profile_issues.php
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

echo "\n=== Profile Editing Diagnostic ===\n\n";

// Check 1: Database columns
echo "Check 1: Database Columns\n";
echo "  - profile_image_path column: " . (Schema::hasColumn('users', 'profile_image_path') ? '✓ EXISTS' : '✗ MISSING') . "\n";
echo "  - username column: " . (Schema::hasColumn('users', 'username') ? '✓ EXISTS' : '✗ MISSING') . "\n";
echo "  - password column: " . (Schema::hasColumn('users', 'password') ? '✓ EXISTS' : '✗ MISSING') . "\n";
echo "\n";

// Check 2: Storage directories
echo "Check 2: Storage Directories\n";
$storagePath = storage_path('app/public/admin-profile-images');
$publicStoragePath = public_path('storage/admin-profile-images');

echo "  - Storage path exists: " . (is_dir($storagePath) ? '✓ YES' : '✗ NO') . "\n";
echo "    Path: {$storagePath}\n";
echo "  - Public storage symlink: " . (is_link(public_path('storage')) ? '✓ EXISTS' : '✗ MISSING') . "\n";
echo "    Path: " . public_path('storage') . "\n";

if (is_link(public_path('storage'))) {
    $target = readlink(public_path('storage'));
    echo "    Target: {$target}\n";
}
echo "\n";

// Check 3: File permissions
echo "Check 3: File Permissions\n";
$storagePath = storage_path('app/public');
if (is_dir($storagePath)) {
    $perms = substr(sprintf('%o', fileperms($storagePath)), -4);
    echo "  - Storage directory permissions: {$perms}\n";
    echo "  - Writable: " . (is_writable($storagePath) ? '✓ YES' : '✗ NO') . "\n";
} else {
    echo "  - Storage directory: ✗ DOES NOT EXIST\n";
}
echo "\n";

// Check 4: Superadmin account
echo "Check 4: Superadmin Account\n";
$superAdmin = \App\Models\User::where('role', 'super-admin')->first();
if ($superAdmin) {
    echo "  - Email: {$superAdmin->email}\n";
    echo "  - Name: {$superAdmin->name}\n";
    echo "  - Active: " . ($superAdmin->is_active ? '✓ YES' : '✗ NO') . "\n";
    echo "  - Profile image: " . ($superAdmin->profile_image_path ? '✓ SET' : '✗ NOT SET') . "\n";
    if ($superAdmin->profile_image_path) {
        echo "    Path: {$superAdmin->profile_image_path}\n";
        $fullPath = storage_path('app/public/' . $superAdmin->profile_image_path);
        echo "    File exists: " . (file_exists($fullPath) ? '✓ YES' : '✗ NO') . "\n";
    }
} else {
    echo "  - ✗ NO SUPERADMIN FOUND\n";
}
echo "\n";

// Check 5: Controller file
echo "Check 5: Controller File\n";
$controllerPath = app_path('Http/Controllers/AdminProfileController.php');
echo "  - File exists: " . (file_exists($controllerPath) ? '✓ YES' : '✗ NO') . "\n";
if (file_exists($controllerPath)) {
    $content = file_get_contents($controllerPath);
    echo "  - Has ValidationException import: " . (strpos($content, 'ValidationException') !== false ? '✓ YES' : '✗ NO') . "\n";
    echo "  - Has \$hasUsernameColumn: " . (strpos($content, '$hasUsernameColumn') !== false ? '✓ YES' : '✗ NO') . "\n";
    echo "  - Has replaceProfileImage method: " . (strpos($content, 'replaceProfileImage') !== false ? '✓ YES' : '✗ NO') . "\n";
}
echo "\n";

// Check 6: View file
echo "Check 6: Profile View File\n";
$viewPath = resource_path('views/profile/edit.blade.php');
echo "  - File exists: " . (file_exists($viewPath) ? '✓ YES' : '✗ NO') . "\n";
if (file_exists($viewPath)) {
    $content = file_get_contents($viewPath);
    echo "  - Has profile_image input: " . (strpos($content, 'profile_image') !== false ? '✓ YES' : '✗ NO') . "\n";
    echo "  - Has password input: " . (strpos($content, 'password') !== false ? '✓ YES' : '✗ NO') . "\n";
    echo "  - Has form enctype: " . (strpos($content, 'enctype') !== false ? '✓ YES' : '✗ NO') . "\n";
}
echo "\n";

echo "=== End Diagnostic ===\n\n";
