# Superadmin Login/Logout Error Fix Guide

## Issue Summary
The superadmin account credentials have been changed from:
- **Old:** `superadmin` / `superadmin@tourist-spots.com` with password `SuperAdmin@123`
- **New:** `superadmin@gmail.com` / `superadmin@gmail.com` with password `superadmin@123`

However, there may be issues with:
1. Duplicate username entries in the database
2. Unique constraint violations on the `username` field
3. Session/authentication state issues

## Step 1: Run the Diagnostic Script

Execute the diagnostic script to check the current state:

```bash
cd c:\xampp\htdocs\tourist-spot-system\admin-system
php check_superadmin.php
```

This will show:
- Whether old and new superadmin accounts exist
- If passwords are correct
- Any duplicate usernames or emails
- All super admin accounts in the system

## Step 2: Apply the Migration

Run the migration to update the superadmin credentials:

```bash
php artisan migrate
```

This migration will:
- Check if the old superadmin exists
- Update it to the new credentials if the new one doesn't exist
- Create the new superadmin if neither exists
- Handle unique constraint issues properly

## Step 3: Verify the Changes

After running the migration, execute the diagnostic script again:

```bash
php artisan tinker
```

Then in the Tinker shell:

```php
$admin = App\Models\User::where('email', 'superadmin@gmail.com')->first();
echo "Email: " . $admin->email . "\n";
echo "Username: " . $admin->username . "\n";
echo "Role: " . $admin->role . "\n";
echo "Active: " . ($admin->is_active ? 'Yes' : 'No') . "\n";
echo "Password Check: " . (Hash::check('superadmin@123', $admin->password) ? 'PASS' : 'FAIL') . "\n";
exit;
```

## Step 4: Clear Cache and Sessions

If you still experience issues, clear the application cache:

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## Step 5: Test Login

1. Go to the login page: `http://localhost/admin-system/login`
2. Enter credentials:
   - **Username/Email:** `superadmin@gmail.com`
   - **Password:** `superadmin@123`
3. Click Login

## Step 6: Test Logout

1. After successful login, click the Logout button in the sidebar
2. You should be redirected to the login page

## Troubleshooting

### Issue: "The provided credentials do not match our records"

**Solution:**
1. Run the diagnostic script to verify the account exists
2. Check if the password hash is correct
3. Verify the account is active (`is_active = 1`)

### Issue: Login works but logout fails

**Solution:**
1. Check the browser console for JavaScript errors
2. Verify the logout route is accessible: `POST /logout`
3. Clear browser cookies and try again

### Issue: Duplicate username error

**Solution:**
1. Run the diagnostic script to identify duplicates
2. Execute this in Tinker:
```php
// Find and remove duplicate usernames
$duplicates = DB::table('users')
    ->select('username')
    ->groupBy('username')
    ->havingRaw('COUNT(*) > 1')
    ->get();

foreach ($duplicates as $dup) {
    $users = DB::table('users')
        ->where('username', $dup->username)
        ->orderBy('id')
        ->get();
    
    // Keep the first one, delete others
    for ($i = 1; $i < count($users); $i++) {
        DB::table('users')->where('id', $users[$i]->id)->delete();
    }
}
exit;
```

### Issue: Session not persisting

**Solution:**
1. Check `.env` file for `SESSION_DRIVER` (should be `file` or `database`)
2. Verify `storage/framework/sessions` directory exists and is writable
3. Run: `php artisan storage:link`

## Files Modified

1. **`database/seeders/AdminSeeder.php`** - Updated superadmin credentials
2. **`database/migrations/2024_01_20_update_superadmin_credentials.php`** - New migration to update database
3. **`check_superadmin.php`** - Diagnostic script

## Login Credentials After Fix

- **Email:** `superadmin@gmail.com`
- **Username:** `superadmin@gmail.com`
- **Password:** `superadmin@123`

## Next Steps

After successful login/logout:
1. Update any documentation with new credentials
2. Inform all super admins of the new credentials
3. Consider implementing password reset functionality
4. Review and strengthen password policies
