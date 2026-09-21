# Superadmin Profile Editing - Complete Fix Guide

## Quick Start

### Windows Command Prompt
```bash
cd c:\xampp\htdocs\tourist-spot-system\admin-system
fix_profile_issues.bat
```

### Windows PowerShell
```powershell
cd c:\xampp\htdocs\tourist-spot-system\admin-system
.\fix_profile_issues.ps1
```

### Manual (Any OS)
```bash
cd admin-system
php fix_profile_issues.php
```

---

## What Was Fixed

### Error 1: Undefined Variable
```
Error: Undefined variable: hasUsernameColumn
Location: AdminProfileController.php line 28
```
**Fixed:** Added variable definition

### Error 2: Missing Class Import
```
Error: Class 'ValidationException' not found
Location: AdminProfileController.php line 50
```
**Fixed:** Added proper import and changed error handling

### Error 3: Storage Issues
```
Error: Storage symlink not found
Error: Profile images directory missing
```
**Fixed:** Created symlink and directory

---

## Files Included

### 1. **fix_profile_issues.bat** (Windows CMD)
- Automated fix for Windows Command Prompt
- Runs diagnostic and fix scripts
- Shows results and next steps

### 2. **fix_profile_issues.ps1** (PowerShell)
- Automated fix for Windows PowerShell
- Color-coded output
- Shows results and next steps

### 3. **fix_profile_issues.php** (Manual Fix)
- PHP script that fixes all issues
- Creates storage symlink
- Creates profile images directory
- Runs migrations
- Clears cache
- Can be run from any OS

### 4. **check_profile_issues.php** (Diagnostic)
- Checks database columns
- Checks storage directories
- Checks file permissions
- Checks controller file
- Checks view file

### 5. **PROFILE_EDITING_FIX.md** (Detailed Guide)
- Step-by-step troubleshooting
- Common issues and solutions
- Manual verification procedures

### 6. **PROFILE_EDITING_FIX_SUMMARY.md** (Summary)
- Overview of all changes
- Files modified/created
- Security notes

---

## How to Use

### Option 1: Automated Fix (Recommended)

**Windows Command Prompt:**
```bash
fix_profile_issues.bat
```

**Windows PowerShell:**
```powershell
.\fix_profile_issues.ps1
```

**Result:** The script will:
- ✓ Create storage symlink
- ✓ Create profile images directory
- ✓ Run database migrations
- ✓ Verify controller file
- ✓ Clear application cache
- ✓ Show verification results

### Option 2: Manual Fix

```bash
php fix_profile_issues.php
```

### Option 3: Step by Step

```bash
# 1. Create storage symlink
php artisan storage:link

# 2. Run migrations
php artisan migrate

# 3. Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

---

## Verification

After running the fix, verify it worked:

### Check 1: Run Diagnostic
```bash
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

Check 3: File Permissions
  - Storage directory permissions: 0755
  - Writable: ✓ YES
```

### Check 2: Test Profile Editing
1. Go to: `http://localhost/admin-system/login`
2. Login with:
   - Email: `superadmin@gmail.com`
   - Password: `superadmin@123`
3. Click profile icon (top right)
4. Upload a profile photo
5. Change password
6. Click "Save Changes"
7. Should see success message

### Check 3: Verify Changes Saved
1. Refresh the page
2. Profile photo should display
3. Logout and login with new password
4. Should work correctly

---

## Troubleshooting

### Problem: "The database is missing the users.profile_image_path column"

**Solution:**
1. Run fix script: `php fix_profile_issues.php`
2. Or manually: `php artisan migrate`

### Problem: "Storage symlink does not exist"

**Solution:**
```bash
php artisan storage:link
```

Verify it was created:
```bash
# Windows
dir public\storage

# Should show: storage -> C:\xampp\htdocs\...\storage\app\public
```

### Problem: "File upload fails with permission error"

**Solution:**
1. Right-click `storage` folder
2. Properties > Security > Edit
3. Select your user > Full Control > Apply

Or in PowerShell (as Administrator):
```powershell
icacls "storage\app\public" /grant:r "$env:USERNAME`:F" /t
```

### Problem: "Profile image not displaying after upload"

**Solution:**
1. Verify symlink: `php artisan storage:link`
2. Check file exists: `storage/app/public/admin-profile-images/`
3. Clear browser cache: `Ctrl+Shift+Delete`
4. Refresh page

### Problem: "Password change fails"

**Solution:**
1. Ensure password is at least 8 characters
2. Ensure password confirmation matches
3. Check Laravel logs: `storage/logs/laravel.log`

### Problem: "Still getting errors"

**Solution:**
1. Run diagnostic: `php check_profile_issues.php`
2. Check Laravel logs: `storage/logs/laravel.log`
3. Clear all caches:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```
4. Try again

---

## Features Now Working

✅ **Profile Photo Upload**
- Upload JPG, PNG, or WEBP
- Max 2MB file size
- Old photos automatically deleted
- Photo displays in profile

✅ **Password Change**
- Change password securely
- Password confirmation required
- Minimum 8 characters
- Hashed with bcrypt

✅ **Profile Information**
- Edit name
- View email
- View municipality (for municipality admins)
- View role

---

## What the Fix Does

The `fix_profile_issues.php` script performs these steps:

1. **Creates Storage Symlink**
   - Links `public/storage` to `storage/app/public`
   - Allows public access to uploaded files

2. **Creates Profile Images Directory**
   - Creates `storage/app/public/admin-profile-images/`
   - Where profile photos are stored

3. **Runs Database Migrations**
   - Adds `profile_image_path` column if missing
   - Adds `username` column if missing

4. **Verifies Controller File**
   - Checks for required imports
   - Checks for required variables
   - Checks for required methods

5. **Clears Application Cache**
   - Clears config cache
   - Clears view cache
   - Clears application cache

---

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
├── check_profile_issues.php (NEW)
├── fix_profile_issues.php (NEW)
├── fix_profile_issues.bat (NEW)
├── fix_profile_issues.ps1 (NEW)
├── PROFILE_EDITING_FIX.md (NEW)
└── PROFILE_EDITING_FIX_SUMMARY.md (NEW)
```

---

## Security Recommendations

- Profile images are stored in `storage/app/public/admin-profile-images/`
- Only authenticated admins can upload
- File types restricted to: jpg, jpeg, png, webp
- Max file size: 2MB
- Old images are automatically deleted when replaced
- Passwords hashed with bcrypt
- CSRF protection on form

---

## Next Steps

1. Run the fix script
2. Verify with diagnostic
3. Test profile editing
4. Verify photo upload works
5. Verify password change works
6. Test logout/login with new password

---

## Support

If you encounter issues:

1. **Check the logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Run diagnostic:**
   ```bash
   php check_profile_issues.php
   ```

3. **Review detailed guide:**
   - Open `PROFILE_EDITING_FIX.md`

4. **Check database directly:**
   ```bash
   php artisan tinker
   $admin = App\Models\User::where('role', 'super-admin')->first();
   echo $admin->profile_image_path;
   exit;
   ```

---

## Summary

✅ **What's Fixed:**
- Missing variable error
- Exception handling error
- Missing import statement
- Storage symlink
- Profile images directory
- Database columns

✅ **What Works Now:**
- Upload profile photo
- Change password
- Edit profile information
- All changes saved correctly

✅ **Next Steps:**
1. Run the fix script
2. Test profile editing
3. Verify all features work
4. Update documentation

---

**Last Updated:** 2024
**Version:** 1.0
