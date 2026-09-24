# Quick Start Guide

Get the Laravel Admin System up and running in 5 minutes!

## ⚡ Fast Setup

### Step 1: Install Dependencies (1 min)
```bash
cd admin-system
composer install
```

### Step 2: Setup Environment (1 min)
```bash
cp .env.example .env
php artisan key:generate
```

### Step 3: Database Setup (1 min)
```bash
# Create MySQL database
mysql -u root -p -e "CREATE DATABASE tourist_spot_db;"

# Update .env with your credentials:
# DB_DATABASE=tourist_spot_db
# DB_USERNAME=root
# DB_PASSWORD=your_password

# Run migrations
php artisan migrate
```

### Step 4: Start Server (1 min)
```bash
php artisan serve
```

Visit: `http://localhost:8000`

### Step 5: Create Admin Account
Option A - Via Web (Recommended):
1. Go to `http://localhost:8000/register`
2. Fill in your details
3. Click "Create Account"
4. Login with your credentials

Option B - Via Tinker:
```bash
php artisan tinker
User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true]);
exit
```

## 📊 First Steps After Login

1. **Add Municipality** → Go to "Municipalities" → Click "Add Municipality" → Fill coordinates
2. **Add Tourist Spot** → Go to "Tourist Spots" → Click "Add New Spot" → Select municipality & add details
3. **Test API** → Open your terminal:
   ```bash
   curl http://localhost:8000/api/v1/municipalities
   ```

## 🔧 Configuration

### Change API URL (for Flutter)
Edit `INTEGRATION_GUIDE.md` for Flutter setup instructions

### Enable HTTPS (Production)
```bash
# Use Let's Encrypt SSL
# Basic setup in .env:
APP_ENV=production
FORCE_HTTPS=true
```

## 🐛 Quick Fixes

| Problem | Solution |
|---------|----------|
| Database not found | Run `mysql -u root -p -e "CREATE DATABASE tourist_spot_db;"` |
| Port 8000 in use | Run `php artisan serve --port=8001` |
| Migrations failed | Run `php artisan migrate:fresh` |
| Cannot login | Check if admin account exists, run tinker to create one |
| CSS/Colors wrong | This is normal - backend only, add Bootstrap via CDN |

## 📁 Project Structure Overview

```
admin-system/
├── app/Http/Controllers/      ← All business logic
├── resources/views/           ← All templates (.blade.php)
├── routes/web.php             ← Admin routes
├── routes/api.php             ← Flutter API routes
├── database/migrations/        ← Database schema
└── .env                        ← Configuration
```

## 🚀 Next Steps

- [ ] Add sample municipalities
- [ ] Add sample tourist spots
- [ ] Test Flutter app connection
- [ ] Deploy to production

## 🔗 Useful Links

- [README.md](README.md) - Full documentation
- [INTEGRATION_GUIDE.md](INTEGRATION_GUIDE.md) - Flutter connection guide
- [Laravel Docs](https://laravel.com/docs)

## 💡 Tips

- **Want to reset everything?** Run `php artisan migrate:fresh`
- **Need test data?** Run `php artisan db:seed` (after creating seeders)
- **Check logs:** `tail -f storage/logs/laravel.log`
- **Stop server:** Press `Ctrl+C` in terminal

---

✅ **You're ready to go!** Start adding tourist spots and watch the Flutter app pull the data in real-time.

For detailed information, see [README.md](README.md)
