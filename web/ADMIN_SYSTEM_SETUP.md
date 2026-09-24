# Super Admin System Setup Guide

## Overview
The Laravel admin system has been restructured into a hierarchical system with:
- **Super Admin**: Manages all municipalities and verifies tourist spots added by municipality admins
- **Municipality Admins**: Each municipality has a dedicated admin who manages their local tourist spots

## Key Changes

### Database Changes
1. **Users Table Updated**:
   - Changed `role` enum from `('admin', 'user')` to `('super-admin', 'municipality-admin', 'user')`
   - Added `municipality_id` column (nullable, for municipality admins only)
   - Added foreign key constraint linking to municipalities

### File Structure

#### New Controllers
- `SuperAdminDashboardController` - Super admin dashboard and management features
- `MunicipalityAdminDashboardController` - Municipality admin dashboard

#### New Middleware
- `EnsureSuperAdmin` - Protects super-admin routes
- `EnsureMunicipalityAdmin` - Protects municipality-admin routes

#### New Views
**Super Admin Views**:
- `resources/views/dashboard/super-admin.blade.php` - Main dashboard
- `resources/views/dashboard/super-admin-municipalities.blade.php` - Municipalities management
- `resources/views/dashboard/super-admin-tourist-spots.blade.php` - Spot verification
- `resources/views/dashboard/super-admin-admins.blade.php` - Admin management

**Municipality Admin Views**:
- `resources/views/dashboard/municipality-admin.blade.php` - Main dashboard
- `resources/views/dashboard/municipality-admin-spots.blade.php` - Manage tourism spots
- `resources/views/dashboard/municipality-admin-reviews.blade.php` - Review management
- `resources/views/dashboard/municipality-admin-info.blade.php` - Municipality information

### Updated Files
- `app/Models/User.php` - Added municipality relationship and role methods
- `app/Models/Municipality.php` - Added admins relationship
- `app/Http/Controllers/AuthController.php` - Role-based login redirection
- `routes/web.php` - Added super-admin and municipality-admin route groups
- `bootstrap/app.php` - Registered middleware aliases
- `resources/views/layouts/app.blade.php` - Role-based navigation menu

## Setup Instructions

### Step 1: Run Database Migration
```bash
cd admin-system
php artisan migrate
```

This will:
- Update the `users` table with the new role enum and municipality_id column
- Create foreign key constraint to municipalities

### Step 2: Run Seeder
```bash
php artisan db:seed --class=AdminSeeder
```

This creates:
- **Super Admin**: `superadmin@gmail.com` / existing password
- **Municipality Admins** (one for each municipality):
  - `lingayen_admin@tourist-spots.com` / `MuniAdmin@123`
  - `binmaley_admin@tourist-spots.com` / `MuniAdmin@123`
  - `urbiztondo_admin@tourist-spots.com` / `MuniAdmin@123`
  - `basista_admin@tourist-spots.com` / `MuniAdmin@123`
  - `labrador_admin@tourist-spots.com` / `MuniAdmin@123`
  - `bugallon_admin@tourist-spots.com` / `MuniAdmin@123`
  - `mangatarem_admin@tourist-spots.com` / `MuniAdmin@123`
  - `aguilar_admin@tourist-spots.com` / `MuniAdmin@123`

## Admin Roles & Permissions

### Super Admin Features
1. **Dashboard View**:
   - Overview of all tourist spots
   - Statistics for all municipalities
   - List of all municipality admins

2. **Spot Verification**:
   - View all new tourist spots
   - Approve spots to make them active
   - Reject spots if they don't meet criteria

3. **Municipality Management**:
   - View all municipalities
   - Assign admins to municipalities
   - View statistics per municipality

4. **Admin Management**:
   - View all municipality admins
   - Monitor admin activity
   - Manage admin accounts

### Municipality Admin Features
1. **Dashboard View**:
   - Statistics for their municipality only
   - Recent tourist spots
   - Pending reviews to approve
   - Spots awaiting super admin approval

2. **Tourist Spot Management**:
   - Add new tourist spots
   - Edit tourist spot information
   - Delete tourist spots
   - View spot details

3. **Review Management**:
   - View reviews for their spots
   - Approve/reject reviews
   - Manage review moderation

4. **Municipality Info**:
   - View municipality details
   - View location coordinates
   - View municipality statistics

## Login Instructions

### For Super Admin
1. Go to: `/login`
2. Email: `superadmin@gmail.com`
3. Password: `SuperAdmin@123`
4. Will be redirected to: `/super-admin/dashboard`

### For Municipality Admin
1. Go to: `/login`
2. Email: `[municipality]_admin@tourist-spots.com` (e.g., `lingayen_admin@tourist-spots.com`)
3. Password: `MuniAdmin@123`
4. Will be redirected to: `/municipality-admin/dashboard`

## Route Structure

### Super Admin Routes
- `/super-admin/dashboard` - Main dashboard
- `/super-admin/municipalities` - All municipalities
- `/super-admin/tourist-spots` - All tourist spots for verification
- `/super-admin/admins` - All municipality admins
- `/super-admin/spots/{id}/approve` - Approve a spot
- `/super-admin/spots/{id}/reject` - Reject a spot

### Municipality Admin Routes
- `/municipality-admin/dashboard` - Main dashboard
- `/municipality-admin/tourist-spots` - Manage municipality spots
- `/municipality-admin/reviews` - Review management
- `/municipality-admin/municipality` - Municipality information

## Workflow

### Tourist Spot Addition Workflow:
1. **Municipality Admin** adds a new tourist spot
   - Spot is created with `status = 'inactive'` (pending approval)
   - Appears in their dashboard under "Awaiting Super Admin Approval"

2. **Super Admin** reviews the spot
   - Sees it in "Spots Pending Approval" section
   - Can approve (status = 'active') or reject (status = 'inactive')

3. **Upon Approval**
   - Spot becomes visible in the public application
   - Municipality admin can see it marked as "Active"

## Default Credentials

| User | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@gmail.com | existing password |
| Lingayen Admin | lingayen_admin@tourist-spots.com | MuniAdmin@123 |
| Binmaley Admin | binmaley_admin@tourist-spots.com | MuniAdmin@123 |
| Urbiztondo Admin | urbiztondo_admin@tourist-spots.com | MuniAdmin@123 |
| Basista Admin | basista_admin@tourist-spots.com | MuniAdmin@123 |
| Labrador Admin | labrador_admin@tourist-spots.com | MuniAdmin@123 |
| Bugallon Admin | bugallon_admin@tourist-spots.com | MuniAdmin@123 |
| Mangatarem Admin | mangatarem_admin@tourist-spots.com | MuniAdmin@123 |
| Aguilar Admin | aguilar_admin@tourist-spots.com | MuniAdmin@123 |

## Important Notes

1. **Password Security**: Change all default passwords in production
2. **Database Backup**: Back up your database before running migrations
3. **Cache Clearing**: If you encounter issues, try: `php artisan cache:clear` and `php artisan config:clear`
4. **Session**: Session driver is set to 'file' in config/session.php
5. **Permissions**: Ensure file permissions are correct on the storage directory

## Troubleshooting

### Migration Won't Run
```bash
php artisan migrate:refresh  # Use with caution - refreshes all migrations
php artisan migrate --step   # Run migrations step by step
```

### Users Not Being Created
```bash
php artisan db:seed --class=AdminSeeder  # Re-run the seeder
```

### Can't Log In
1. Check the `users` table has role and municipality_id columns
2. Verify the password hashes are correct
3. Check logs in `storage/logs/laravel.log`
4. Try clearing the session: `rm -rf storage/framework/sessions/*`

### Routes Not Working
```bash
php artisan route:cache      # Clear route cache
php artisan route:list       # List all available routes
```

## Support
For additional configuration or customization needs, refer to:
- Laravel Documentation: https://laravel.com/docs
- Database Schema: See `DATABASE_SCHEMA.md`
- Project Summary: See `PROJECT_SUMMARY.md`
