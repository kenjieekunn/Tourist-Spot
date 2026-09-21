# Superadmin Profile Editing Error - Complete Fix Guide

## Issues Fixed

### 1. **Missing Variable Error**
**Problem:** `$hasUsernameColumn` variable was not defined in the `update()` method
**Error:** `Undefined variable: hasUsernameColumn`
**Fix:** Added `$hasUsernameColumn = Schema::hasColumn('users', 'username');` on line 27

### 2. **Incorrect Exception Handling**
**Problem:** Used `ValidationException::withMessages()` which requires import
**Error:** `Class 'ValidationException' not found`
**Fix:** Changed to use `redirect()->withErrors()` instead

### 3. **Missing Import Statement**
**Problem:** `ValidationException` was not imported
**Fix:** Added proper import statement at the top of the file

## Files Fixed

### `app/Http/Controllers/AdminProfileController.php`
- Added missing `$hasUsernameColumn` variable definition
- Fixed error handling for profile image upload
- Added `ValidationException` import
- Improved error messages

## How to Apply the Fix

### Step 1: Verify the Fix
The controller file has already been updated. Verify it:

```bash
cd c:\xampp\htdocs\tourist-spot-system\admin-system
php check_profile_issues.php
```

Expected output:
```
Check 1: Database Columns
  - profile_image_path column: ✓ EXISTS
  - username column: ✓ EXISTS
  - password column: ✓ EXISTS

Check 2: Storage Directories
  - Storage path exists: ✓ YES
  - Public storage symlink: ✓ EXISTS
```

### Step 2: Ensure Storage Symlink Exists

If the symlink is missing, create it:

```bash
php artisan storage:link
```

### Step 3: Set Correct Permissions

Ensure the storage directory is writable:

```bash
# Windows (in PowerShell as Administrator)
icacls "storage\app\public" /grant:r "%USERNAME%:F" /t

# Or manually: Right-click storage folder > Properties > Security > Edit > Full Control
```

### Step 4: Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Step 5: Test Profile Editing

1. Login as superadmin: `superadmin@gmail.com` / `superadmin@123`
2. Click profile icon in top right
3. Try uploading a profile photo
4. Try changing password
5. Click "Save Changes"

## Troubleshooting

### Issue: "The database is missing the users.profile_image_path column"

**Solution:**
Run the migration that adds the profile image column:

```bash
php artisan migrate
```

If the migration doesn't exist, create it:

```bash
php artisan make:migration add_profile_image_path_to_users_table
```

Then add this to the migration:

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        if (!Schema::hasColumn('users', 'profile_image_path')) {
            $table->string('profile_image_path')->nullable()->after('password');
        }
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        if (Schema::hasColumn('users', 'profile_image_path')) {
            $table->dropColumn('profile_image_path');
        }
    });
}
```

### Issue: "The storage symlink does not exist"

**Solution:**
Create the symlink:

```bash
php artisan storage:link
```

Verify it was created:
```bash
# Windows
dir public\storage

# Should show: storage -> C:\xampp\htdocs\tourist-spot-system\admin-system\storage\app\public
```

### Issue: "File upload fails with permission error"

**Solution:**
1. Check directory permissions:
   ```bash
   php check_profile_issues.php
   ```

2. Make storage directory writable:
   - Right-click `storage` folder
   - Properties > Security > Edit
   - Select your user > Full Control > Apply

3. Or use PowerShell (as Administrator):
   ```powershell
   icacls "storage\app\public" /grant:r "$env:USERNAME`:F" /t
   ```

### Issue: "Password change fails"

**Solution:**
1. Verify password field is in the form (it is)
2. Check password confirmation matches
3. Ensure password is at least 8 characters
4. Check Laravel logs: `storage/logs/laravel.log`

### Issue: "Profile image not displaying after upload"

**Solution:**
1. Verify symlink exists: `php artisan storage:link`
2. Check file was uploaded: `storage/app/public/admin-profile-images/`
3. Verify database was updated: Check `users.profile_image_path` column
4. Clear browser cache (Ctrl+Shift+Delete)

## Testing Checklist

- [ ] Run diagnostic: `php check_profile_issues.php`
- [ ] All checks show ✓
- [ ] Storage symlink exists
- [ ] Storage directory is writable
- [ ] Login as superadmin
- [ ] Navigate to profile page
- [ ] Upload a profile photo
- [ ] Photo displays correctly
- [ ] Change password
- [ ] Logout and login with new password
- [ ] Password change works

## Database Schema

The `users` table should have these columns:

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(255) UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_image_path VARCHAR(255) NULL,
    role ENUM('super-admin', 'municipality-admin', 'user') DEFAULT 'user',
    municipality_id BIGINT UNSIGNED NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## File Structure

```
admin-system/
├── app/Http/Controllers/
│   └── AdminProfileController.php (FIXED)
├── resources/views/profile/
│   └── edit.blade.php
├── storage/app/public/
│   └── admin-profile-images/
├── public/
│   └── storage -> ../storage/app/public (SYMLINK)
└── check_profile_issues.php (NEW)
```

## Security Notes

- Profile images are stored in `storage/app/public/admin-profile-images/`
- Only authenticated admins can upload
- File types restricted to: jpg, jpeg, png, webp
- Max file size: 2MB
- Old images are automatically deleted when replaced

## Next Steps

1. Run diagnostic: `php check_profile_issues.php`
2. Fix any issues shown
3. Test profile editing
4. Verify photo upload works
5. Verify password change works
6. Test logout/login with new password

## Support

If issues persist:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Run diagnostic: `php check_profile_issues.php`
3. Verify database: `php artisan tinker`
   ```php
   $admin = App\Models\User::where('role', 'super-admin')->first();
   echo $admin->profile_image_path;
   exit;
   ```
4. Check file permissions on storage directory
