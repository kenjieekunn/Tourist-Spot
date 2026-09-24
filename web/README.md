# Tourist Spot Admin System - Laravel Web Administration Panel

A complete Laravel-based web administration system for managing tourist spots, municipalities, and reviews with a connected Flutter mobile application.

## 📋 Features

### Admin Dashboard
- **Dashboard** - Overview of statistics and recent activity
- **Tourist Spots Management** - Create, Read, Update, Delete (CRUD) operations
- **Municipalities Management** - Manage different municipalities
- **Reviews Management** - Approve or reject user reviews
- **Admin Authentication** - Secure login system with role-based access

### API Endpoints (For Flutter App)
- Get all municipalities
- Get tourist spots by municipality
- Get detailed tourist spot information
- Submit reviews (moderation pending)

## 🏗️ MVC Architecture

```
admin-system/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php          (Authentication)
│   │   │   ├── DashboardController.php     (Dashboard stats)
│   │   │   ├── TouristSpotController.php   (CRUD for spots)
│   │   │   ├── MunicipalityController.php  (CRUD for municipalities)
│   │   │   ├── ReviewController.php        (Review management)
│   │   │   └── Api/
│   │   │       └── TouristSpotApiController.php (Flutter API)
│   ├── Models/
│   │   ├── User.php                        (Admin users)
│   │   ├── Municipality.php
│   │   ├── TouristSpot.php
│   │   └── Review.php
│
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php               (Main layout)
│       ├── auth/
│       │   ├── login.blade.php
│       │   └── register.blade.php
│       ├── dashboard/
│       │   └── index.blade.php
│       ├── tourist_spots/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   ├── edit.blade.php
│       │   └── show.blade.php
│       ├── municipalities/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   └── edit.blade.php
│       └── reviews/
│           └── index.blade.php
│
├── routes/
│   ├── web.php                             (Admin web routes)
│   └── api.php                             (Flutter app API)
│
├── database/
│   ├── migrations/
│   │   ├── create_users_table.php
│   │   ├── create_municipalities_table.php
│   │   ├── create_tourist_spots_table.php
│   │   └── create_reviews_table.php
│   └── seeders/
│
└── .env.example
```

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.1 or higher
- Composer
- MySQL or MariaDB
- Node.js & npm (for frontend assets)

### Steps

1. **Clone/Navigate to the project:**
   ```bash
   cd admin-system
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Copy environment file:**
   ```bash
   cp .env.example .env
   ```

4. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

5. **Configure database in `.env`:**
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=tourist_spot_db
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

6. **Create database:**
   ```bash
   mysql -u root -p -e "CREATE DATABASE tourist_spot_db;"
   ```

7. **Run migrations:**
   ```bash
   php artisan migrate
   ```

8. **Create first admin user (optional):**
   ```bash
   php artisan tinker
   # Type the following commands:
   User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true]);
   exit
   ```

9. **Start the development server:**
   ```bash
   php artisan serve
   ```

   Visit `http://localhost:8000` in your browser

## 📱 API Integration with Flutter App

### API Base URL
```
http://your-domain.com/api/v1
```

### Available Endpoints

#### 1. Get All Municipalities
```
GET /api/v1/municipalities
```
**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Dagupan",
      "description": "...",
      "latitude": 16.0419,
      "longitude": 120.3372,
      "image_url": "..."
    }
  ]
}
```

#### 2. Get Tourist Spots by Municipality
```
GET /api/v1/municipalities/{municipalityId}/spots
```
**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Dagupan Bangus Festival",
      "municipality_id": 1,
      "latitude": 16.0419,
      "longitude": 120.3372,
      "average_rating": 4.5,
      "reviews_count": 12
    }
  ]
}
```

#### 3. Get Tourist Spot Details
```
GET /api/v1/spots/{spotId}
```
**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Dagupan Bangus Festival",
    "description": "...",
    "address": "...",
    "latitude": 16.0419,
    "longitude": 120.3372,
    "phone": "...",
    "website": "...",
    "opening_hours": "...",
    "entrance_fee": 100,
    "image_url": "...",
    "average_rating": 4.5,
    "reviews": [
      {
        "id": 1,
        "user_name": "John Doe",
        "rating": 5,
        "comment": "Great place!",
        "status": "approved"
      }
    ]
  }
}
```

#### 4. Submit Review
```
POST /api/v1/spots/{spotId}/reviews

Body (JSON):
{
  "user_name": "John Doe",
  "rating": 5,
  "comment": "This is a great tourist spot!"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Review submitted successfully and is pending approval",
  "data": {
    "id": 1,
    "user_name": "John Doe",
    "rating": 5,
    "comment": "This is a great tourist spot!",
    "status": "pending"
  }
}
```

## 📊 Database Schema

### Users Table
- id (Primary Key)
- name
- email (Unique)
- password
- role (admin/user)
- is_active (boolean)
- timestamps

### Municipalities Table
- id (Primary Key)
- name (Unique)
- description
- latitude
- longitude
- image_url
- timestamps

### Tourist Spots Table
- id (Primary Key)
- municipality_id (Foreign Key)
- name (Unique)
- description
- address
- latitude
- longitude
- phone
- website
- opening_hours
- entrance_fee
- image_url
- status (active/inactive)
- timestamps

### Reviews Table
- id (Primary Key)
- tourist_spot_id (Foreign Key)
- user_name
- rating (1-5)
- comment
- status (pending/approved/rejected)
- timestamps

## 🔐 Security Features

- **CSRF Protection** - All forms protected with CSRF tokens
- **Password Hashing** - Passwords hashed using bcrypt
- **Authentication Middleware** - Protected routes require login
- **Validation** - Input validation on all forms
- **SQL Injection Protection** - Eloquent ORM prevents SQL injection
- **XSS Protection** - Blade escapes output by default

## 🔄 How Admin Updates Sync to Flutter App

1. **Admin adds/updates Tourist Spot** on the web dashboard
2. **Data stored in database** immediately
3. **Flutter app calls API endpoints** to fetch latest data
4. **Reviews submitted from Flutter** are stored in database
5. **Admin approves/rejects reviews** on the dashboard
6. **Flutter app fetches updated reviews** through API (only approved ones shown)

## 📝 Environment Variables

Key environmental variables to configure:

```
APP_NAME=Tourist Spot Admin System
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tourist_spot_db
DB_USERNAME=root
DB_PASSWORD=

API_BASE_URL=https://your-domain.com/api
```

For Hostinger, point the domain document root to `web/public`. If the domain
must point to the repository root, the root `.htaccess` forwards requests to
this Laravel public directory.

## 🛠️ Common Commands

```bash
# Generate new migration
php artisan make:migration create_table_name

# Run all migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh migration (drop and recreate)
php artisan migrate:fresh

# Tinker - Interactive shell
php artisan tinker

# Clear application cache
php artisan cache:clear

# View all routes
php artisan route:list
```

## 🐛 Troubleshooting

### Database connection errors
- Ensure MySQL is running
- Check credentials in `.env` file
- Verify database is created

### CSS/JS not loading
- Run `npm install` and `npm run dev`
- Clear browser cache

### Migration errors
- Check if database exists
- Run `php artisan migrate:fresh --seed` to reset

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.0)
- [Font Awesome Icons](https://fontawesome.com/icons)

## 📄 License

This project is provided as-is for educational purposes.

## 🤝 Support

For issues or questions, please contact the development team.

---

**Last Updated:** March 2026
