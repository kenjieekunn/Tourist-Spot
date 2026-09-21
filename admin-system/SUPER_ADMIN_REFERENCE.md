# Super Admin System - Quick Reference

## System Architecture

```
┌─────────────────────────────────────────────────────────┐
│                 Super Admin                             │
│  (superadmin@tourist-spots.com)                         │
├─────────────────────────────────────────────────────────┤
│ • Dashboard (all municipalities overview)               │
│ • Municipalities management                             │
│ • Tourist spot verification (approve/reject)            │
│ • Municipality admin management                         │
└──────────────┬──────────────────────────────────────────┘
               │
      ┌────────┴────────┬────────────┬───────┬──────┐
      │                 │            │       │      │
  ┌───▼────┐ ┌──────────▼──┐ ┌──────▼──┐ ... same for other municipalities
  │Lingayen │ │  Binmaley  │ │ Urbiztondo│
  │ Admin   │ │   Admin    │ │  Admin    │
  ├─────────┤ ├────────────┤ ├──────────┤
  │Dashboard│ │Dashboard   │ │Dashboard  │
  │Spots    │ │Spots       │ │Spots      │
  │Reviews  │ │Reviews     │ │Reviews    │
  └─────────┘ └────────────┘ └──────────┘
```

## User Roles

### Super Admin (`super-admin`)
- **Email**: superadmin@tourist-spots.com
- **Password**: SuperAdmin@123
- **Routes**: `/super-admin/*`
- **Permissions**:
  - View all municipalities
  - View all tourist spots
  - Approve/reject tourist spots
  - Manage municipality admins
  - View system statistics

### Municipality Admin (`municipality-admin`)
- **Email**: `[municipality]_admin@tourist-spots.com`
- **Password**: MuniAdmin@123
- **Routes**: `/municipality-admin/*`
- **Permissions**:
  - Manage their municipality's tourist spots
  - Moderate reviews for their municipality
  - View municipality statistics
  - Only see their own data

### Regular User (`user`)
- No admin dashboard access
- Uses the public-facing application

## Database Schema Changes

### Users Table
```sql
- role: ENUM('super-admin', 'municipality-admin', 'user')
- municipality_id: BIGINT UNSIGNED (FK to municipalities)
- Foreign Key: municipality_id → municipalities.id
```

### Relationships
```
User
  ├─ belongsTo → Municipality (via municipality_id)
  └─ Methods:
     ├─ isSuperAdmin()
     ├─ isMunicipalityAdmin()
     └─ isAdmin()

Municipality
  ├─ hasMany → TouristSpots
  └─ hasMany → Users (admins)
```

## File Structure

```
admin-system/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── SuperAdminDashboardController.php (NEW)
│   │   │   ├── MunicipalityAdminDashboardController.php (NEW)
│   │   │   └── AuthController.php (UPDATED)
│   │   └── Middleware/
│   │       ├── EnsureSuperAdmin.php (NEW)
│   │       └── EnsureMunicipalityAdmin.php (NEW)
│   └── Models/
│       ├── User.php (UPDATED)
│       └── Municipality.php (UPDATED)
├── routes/
│   └── web.php (UPDATED)
├── bootstrap/
│   └── app.php (UPDATED - middleware registration)
├── database/
│   ├── migrations/
│   │   └── 2024_01_01_000001_update_users_table_for_admin_system.php (NEW)
│   ├── seeders/
│   │   └── AdminSeeder.php (NEW)
│   └── admin_system_setup.sql (NEW)
├── resources/views/
│   ├── layouts/
│   │   └── app.blade.php (UPDATED - role-based nav)
│   └── dashboard/
│       ├── super-admin.blade.php (NEW)
│       ├── super-admin-municipalities.blade.php (NEW)
│       ├── super-admin-tourist-spots.blade.php (NEW)
│       ├── super-admin-admins.blade.php (NEW)
│       ├── municipality-admin.blade.php (NEW)
│       ├── municipality-admin-spots.blade.php (NEW)
│       ├── municipality-admin-reviews.blade.php (NEW)
│       └── municipality-admin-info.blade.php (NEW)
└── setup-admin-system.* (NEW - setup scripts)
```

## Routes

### Super Admin Routes
| Route | Purpose |
|-------|---------|
| `/super-admin/dashboard` | Main dashbaord |
| `/super-admin/municipalities` | List all municipalities |
| `/super-admin/tourist-spots` | Verify tourist spots |
| `/super-admin/admins` | Manage municipality admins |
| `/super-admin/spots/{id}/approve` | Approve a spot |
| `/super-admin/spots/{id}/reject` | Reject a spot |

### Municipality Admin Routes
| Route | Purpose |
|-------|---------|
| `/municipality-admin/dashboard` | Main dashboard |
| `/municipality-admin/tourist-spots` | Manage spots |
| `/municipality-admin/reviews` | Manage reviews |
| `/municipality-admin/municipality` | View municipality info |

## Tourist Spot Status Workflow

```
Municipality Admin Creates Spot
        ↓
  status = 'inactive' (PENDING)
        ↓
Appears in Super Admin Dashboard
        ↓
  ┌─────┴─────┐
  │           │
Approve    Reject
  │           │
  ↓           ↓
'active'   'inactive'
(LIVE)     (REJECTED)
```

## Authentication Flow

```
User submits login form
        ↓
Credentials validated
        ↓
    ┌───┴────────┬──────────┐
    │            │          │
Super Admin   Municipality  Regular User
    │          Admin        │
    ↓            ↓          ↓
/super-admin  /municipality /dashboard
/dashboard    /admin/dashboard
```

## Middleware

### EnsureSuperAdmin
- Checks: `auth()->user()->isSuperAdmin()`
- Returns: 403 error if not super admin

### EnsureMunicipalityAdmin
- Checks: `auth()->user()->isMunicipalityAdmin()`
- Checks: User has municipality assigned
- Returns: 403 error if checks fail

## Key Methods

### User Model
```php
$user->isSuperAdmin()        // Check if super admin
$user->isMunicipalityAdmin() // Check if municipality admin
$user->isAdmin()             // Check if any type of admin
$user->municipality()        // Get assigned municipality
```

### SuperAdminDashboardController
```php
index()             // Main dashboard
approveSpot($spot)  // Approve a spot
rejectSpot($spot)   // Reject a spot
municipalities()    // List municipalities
touristSpots()      // List all spots
admins()            // List admins
```

### MunicipalityAdminDashboardController
```php
index()             // Main dashboard
touristSpots()      // Manage spots
reviews()           // Manage reviews
municipality()      // View municipality
```

## Municipalities

The 8 municipalities in 2nd District of Pangasinan:
1. Lingayen (Capital)
2. Binmaley
3. Urbiztondo
4. Basista
5. Labrador
6. Bugallon
7. Mangatarem
8. Aguilar

## Setup Commands

```bash
# Run migration
php artisan migrate

# Run seeder
php artisan db:seed --class=AdminSeeder

# Or use setup scripts
# PowerShell
./setup-admin-system.ps1

# Bash/Linux
./setup-admin-system.sh
```

## Testing

### Super Admin Login
- Email: `superadmin@tourist-spots.com`
- Password: `SuperAdmin@123`

### Municipality Admin Login
- Email: `lingayen_admin@tourist-spots.com`
- Password: `MuniAdmin@123`

(Replace "lingayen" with any other municipality name)

## Important Notes

1. **Default Passwords**: Change in production environment
2. **Middleware Registration**: Located in `bootstrap/app.php`
3. **Route Protection**: All admin routes protected by middleware
4. **Session Handler**: File-based sessions (`storage/framework/sessions/`)
5. **Cache**: Dashboard data cached for 5 minutes per user
6. **Foreign Keys**: Cascade deletes from municipalities

## Performance

- Dashboard data cached: 5 minutes
- Index pagination: 15-20 items per page
- Tourist spot counts: Eager-loaded with `withCount()`
- Queries optimized with relationships

## Troubleshooting

### Incorrect role after login
- Clear cache: `php artisan cache:clear`
- Check user role in database

### Cannot see admin buttons
- Verify user role: `super-admin` or `municipality-admin`
- Check middleware registration in `bootstrap/app.php`

### Foreign key constraint error
- Ensure municipalities exist before creating admins
- Seeds should run in correct order

