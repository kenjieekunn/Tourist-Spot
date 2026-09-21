# Tourist Spot System - Deployment Guide

Complete guide for deploying the web-based admin system and Flutter mobile application.

---

## Table of Contents
1. [Web Backend Deployment](#web-backend-deployment)
2. [Flutter Mobile App Deployment](#flutter-mobile-app-deployment)
3. [Web Frontend Deployment](#web-frontend-deployment)
4. [Database Setup](#database-setup)
5. [Environment Configuration](#environment-configuration)
6. [Post-Deployment Checklist](#post-deployment-checklist)

---

## Web Backend Deployment

### Option 1: Shared Hosting (Recommended for Small-Medium Projects)

#### Providers:
- **Bluehost** - $2.95/month (WordPress-friendly, PHP support)
- **SiteGround** - $2.99/month (Great support, Laravel-optimized)
- **HostGator** - $2.75/month (Affordable, good uptime)
- **Kinsta** - $35/month (Premium, managed hosting)

#### Steps:

1. **Purchase Hosting Plan**
   - Choose plan with PHP 8.1+, MySQL 8.0+, Composer support
   - Ensure at least 2GB RAM, 50GB storage

2. **Upload Files via FTP/SFTP**
   ```bash
   # Using FileZilla or command line
   sftp user@your-domain.com
   cd public_html
   put -r admin-system/* .
   ```

3. **Install Dependencies**
   ```bash
   # SSH into server
   ssh user@your-domain.com
   cd public_html
   composer install --no-dev
   ```

4. **Configure Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Set Permissions**
   ```bash
   chmod -R 755 storage bootstrap/cache
   chmod -R 777 storage/app/spot-images
   ```

6. **Run Migrations**
   ```bash
   php artisan migrate --force
   php artisan db:seed
   ```

---

### Option 2: Cloud Hosting (Scalable)

#### Providers:
- **AWS** - Pay-as-you-go, highly scalable
- **DigitalOcean** - $5/month droplet, simple setup
- **Linode** - $5/month, good performance
- **Heroku** - $7/month, easy deployment
- **Railway** - $5/month, modern platform

#### DigitalOcean Deployment (Recommended):

1. **Create Droplet**
   - OS: Ubuntu 22.04 LTS
   - Size: $5/month (1GB RAM, 25GB SSD)
   - Region: Closest to your users

2. **Initial Setup**
   ```bash
   # SSH into droplet
   ssh root@your_droplet_ip
   
   # Update system
   apt update && apt upgrade -y
   
   # Install dependencies
   apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-mbstring \
   php8.1-xml php8.1-curl php8.1-zip nginx mysql-server composer
   ```

3. **Configure Nginx**
   ```bash
   # Create nginx config
   nano /etc/nginx/sites-available/tourist-spot
   ```
   
   ```nginx
   server {
       listen 80;
       server_name your-domain.com www.your-domain.com;
       root /var/www/tourist-spot/admin-system/public;
       
       index index.php index.html;
       
       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }
       
       location ~ \.php$ {
           include snippets/fastcgi-php.conf;
           fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
       }
       
       location ~ /\.ht {
           deny all;
       }
   }
   ```

4. **Enable Site & SSL**
   ```bash
   ln -s /etc/nginx/sites-available/tourist-spot /etc/nginx/sites-enabled/
   systemctl restart nginx
   
   # Install SSL (Let's Encrypt)
   apt install certbot python3-certbot-nginx -y
   certbot --nginx -d your-domain.com -d www.your-domain.com
   ```

5. **Setup Database**
   ```bash
   mysql -u root -p
   CREATE DATABASE tourist_spot_db;
   CREATE USER 'tourist_user'@'localhost' IDENTIFIED BY 'strong_password';
   GRANT ALL PRIVILEGES ON tourist_spot_db.* TO 'tourist_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```

6. **Deploy Application**
   ```bash
   cd /var/www
   git clone https://github.com/your-repo/tourist-spot-system.git
   cd tourist-spot-system/admin-system
   
   composer install --no-dev
   cp .env.example .env
   php artisan key:generate
   
   # Update .env with database credentials
   nano .env
   
   php artisan migrate --force
   php artisan db:seed
   
   # Set permissions
   chown -R www-data:www-data /var/www/tourist-spot
   chmod -R 755 storage bootstrap/cache
   chmod -R 777 storage/app/spot-images
   ```

---

### Option 3: Docker Deployment (Production-Ready)

#### Create Dockerfile
```dockerfile
FROM php:8.1-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    mysql-client \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql gd mbstring

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY admin-system/ .

RUN composer install --no-dev

EXPOSE 9000

CMD ["php-fpm"]
```

#### Docker Compose
```yaml
version: '3.8'

services:
  app:
    build: .
    container_name: tourist-spot-app
    working_dir: /app
    volumes:
      - ./admin-system:/app
    depends_on:
      - db
    networks:
      - tourist-network

  db:
    image: mysql:8.0
    container_name: tourist-spot-db
    environment:
      MYSQL_DATABASE: tourist_spot_db
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_PASSWORD: user_password
      MYSQL_USER: tourist_user
    volumes:
      - dbdata:/var/lib/mysql
    networks:
      - tourist-network

  nginx:
    image: nginx:alpine
    container_name: tourist-spot-nginx
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./admin-system:/app
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app
    networks:
      - tourist-network

volumes:
  dbdata:

networks:
  tourist-network:
    driver: bridge
```

#### Deploy with Docker
```bash
docker-compose up -d
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan db:seed
```

---

## Flutter Mobile App Deployment

### Android Deployment

#### Prerequisites:
- Android Studio installed
- Java Development Kit (JDK) 11+
- Google Play Developer Account ($25 one-time)

#### Steps:

1. **Generate Signing Key**
   ```bash
   keytool -genkey -v -keystore ~/key.jks -keyalg RSA -keysize 2048 -validity 10000 -alias upload
   ```

2. **Configure Signing in pubspec.yaml**
   ```yaml
   # android/app/build.gradle
   signingConfigs {
       release {
           keyAlias = 'upload'
           keyPassword = 'your_password'
           storeFile = file('/path/to/key.jks')
           storePassword = 'your_password'
       }
   }
   ```

3. **Build Release APK**
   ```bash
   flutter build apk --release
   # Output: build/app/outputs/flutter-app-release.apk
   ```

4. **Build App Bundle (Recommended)**
   ```bash
   flutter build appbundle --release
   # Output: build/app/outputs/bundle/release/app-release.aab
   ```

5. **Upload to Google Play**
   - Go to Google Play Console
   - Create new app
   - Fill app details (name, description, screenshots)
   - Upload app bundle
   - Set pricing and distribution
   - Submit for review (24-48 hours)

#### Google Play Store Requirements:
- Minimum 2 screenshots (1080x1920px)
- Feature graphic (1024x500px)
- App icon (512x512px)
- Privacy policy URL
- Content rating questionnaire

---

### iOS Deployment

#### Prerequisites:
- Mac with Xcode
- Apple Developer Account ($99/year)
- iOS 12.0+ support

#### Steps:

1. **Configure iOS Project**
   ```bash
   cd flutter-app/ios
   pod install
   cd ..
   ```

2. **Update App Info**
   ```bash
   # ios/Runner/Info.plist
   <key>CFBundleDisplayName</key>
   <string>Tourist Spot</string>
   <key>CFBundleVersion</key>
   <string>1</string>
   ```

3. **Build Release IPA**
   ```bash
   flutter build ios --release
   ```

4. **Archive and Upload**
   ```bash
   cd ios
   xcodebuild -workspace Runner.xcworkspace -scheme Runner -configuration Release -archivePath build/Runner.xcarchive archive
   xcodebuild -exportArchive -archivePath build/Runner.xcarchive -exportOptionsPlist ExportOptions.plist -exportPath build/ipa
   ```

5. **Upload to App Store**
   - Use Transporter app or Xcode
   - Fill app metadata
   - Submit for review (24-48 hours)

#### App Store Requirements:
- Minimum 2 screenshots per device type
- App preview video (optional)
- Privacy policy URL
- App icon (1024x1024px)
- Support URL

---

### Web Deployment (Flutter Web)

#### Build Web Version
```bash
flutter build web --release
# Output: build/web/
```

#### Deploy to Hosting

**Option 1: Firebase Hosting**
```bash
npm install -g firebase-tools
firebase login
firebase init hosting
firebase deploy
```

**Option 2: Netlify**
```bash
npm install -g netlify-cli
netlify deploy --prod --dir=build/web
```

**Option 3: Vercel**
```bash
npm install -g vercel
vercel --prod
```

---

## Web Frontend Deployment

### Create Flutter Web Admin Dashboard

1. **Build Web Version**
   ```bash
   flutter build web --release
   ```

2. **Deploy to Same Server**
   ```bash
   # Copy to web directory
   cp -r build/web/* /var/www/tourist-spot/public/web/
   ```

3. **Configure Nginx for Web App**
   ```nginx
   location /web {
       try_files $uri $uri/ /web/index.html;
   }
   ```

---

## Database Setup

### Production Database Configuration

1. **Create Database Backup**
   ```bash
   mysqldump -u tourist_user -p tourist_spot_db > backup.sql
   ```

2. **Optimize Database**
   ```sql
   -- Add indexes
   ALTER TABLE tourist_spots ADD INDEX idx_municipality (municipality_id);
   ALTER TABLE tourist_spots ADD INDEX idx_status (status);
   ALTER TABLE tourist_spots ADD INDEX idx_verification (verification_status);
   
   -- Enable query cache
   SET GLOBAL query_cache_size = 268435456;
   SET GLOBAL query_cache_type = 1;
   ```

3. **Setup Automated Backups**
   ```bash
   # Create backup script
   nano /usr/local/bin/backup-db.sh
   ```
   
   ```bash
   #!/bin/bash
   BACKUP_DIR="/backups/tourist-spot"
   DATE=$(date +%Y%m%d_%H%M%S)
   
   mysqldump -u tourist_user -p$DB_PASSWORD tourist_spot_db > $BACKUP_DIR/backup_$DATE.sql
   
   # Keep only last 30 days
   find $BACKUP_DIR -name "backup_*.sql" -mtime +30 -delete
   ```
   
   ```bash
   # Add to crontab
   crontab -e
   # Add: 0 2 * * * /usr/local/bin/backup-db.sh
   ```

---

## Environment Configuration

### Production .env File

```env
APP_NAME="Tourist Spot System"
APP_ENV=production
APP_KEY=base64:your_generated_key
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=tourist_spot_db
DB_USERNAME=tourist_user
DB_PASSWORD=strong_password

CACHE_DRIVER=redis
SESSION_DRIVER=cookie
QUEUE_DRIVER=sync

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@your-domain.com

GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret

FACEBOOK_CLIENT_ID=your_facebook_app_id
FACEBOOK_CLIENT_SECRET=your_facebook_app_secret

MAPS_API_KEY=your_google_maps_api_key
```

### Flutter App Configuration

Update `lib/config/constants/api_constants.dart`:

```dart
static const String localNetworkUrl =
    'https://your-domain.com/api/v1/';
```

---

## Post-Deployment Checklist

### Security
- [ ] Enable HTTPS/SSL certificate
- [ ] Configure firewall rules
- [ ] Set strong database passwords
- [ ] Enable two-factor authentication for admin accounts
- [ ] Configure CORS properly
- [ ] Set up rate limiting
- [ ] Enable CSRF protection
- [ ] Sanitize all user inputs

### Performance
- [ ] Enable caching (Redis/Memcached)
- [ ] Optimize database queries
- [ ] Enable gzip compression
- [ ] Minify CSS/JS
- [ ] Optimize images
- [ ] Setup CDN for static assets
- [ ] Enable browser caching

### Monitoring
- [ ] Setup error logging (Sentry)
- [ ] Configure uptime monitoring
- [ ] Setup performance monitoring
- [ ] Enable access logs
- [ ] Setup alerts for critical errors

### Backup & Recovery
- [ ] Test database backups
- [ ] Document recovery procedures
- [ ] Setup automated backups
- [ ] Store backups in multiple locations
- [ ] Test restore procedures

### Testing
- [ ] Test all API endpoints
- [ ] Test authentication flows
- [ ] Test file uploads
- [ ] Test on different devices/browsers
- [ ] Load testing
- [ ] Security testing

---

## Troubleshooting

### Common Issues

**1. Database Connection Error**
```bash
# Check MySQL service
systemctl status mysql

# Verify credentials
mysql -u tourist_user -p -h localhost
```

**2. Permission Denied on Storage**
```bash
chmod -R 777 storage/app/spot-images
chown -R www-data:www-data storage
```

**3. 404 on API Endpoints**
```bash
# Check .htaccess
cat admin-system/public/.htaccess

# Verify nginx rewrite rules
nginx -t
systemctl reload nginx
```

**4. Flutter App Can't Connect to API**
- Verify API URL in constants
- Check firewall rules
- Test with curl: `curl https://your-domain.com/api/v1/municipalities`

---

## Support & Resources

- **Laravel Documentation**: https://laravel.com/docs
- **Flutter Documentation**: https://flutter.dev/docs
- **DigitalOcean Tutorials**: https://www.digitalocean.com/community/tutorials
- **Google Play Console Help**: https://support.google.com/googleplay
- **App Store Connect Help**: https://help.apple.com/app-store-connect

---

## Cost Estimation

### Monthly Costs (Approximate)

| Component | Option | Cost |
|-----------|--------|------|
| Web Hosting | DigitalOcean | $5-20 |
| Database | Included | $0 |
| SSL Certificate | Let's Encrypt | $0 |
| Email Service | SendGrid | $0-20 |
| CDN | Cloudflare | $0-20 |
| Monitoring | Sentry | $0-29 |
| **Total** | | **$5-89** |

### One-Time Costs

| Item | Cost |
|------|------|
| Google Play Developer Account | $25 |
| Apple Developer Account | $99/year |
| Domain Name | $10-15/year |
| SSL Certificate (if not free) | $0-100 |

---

## Next Steps

1. Choose hosting provider
2. Setup domain and DNS
3. Deploy backend API
4. Configure database
5. Build and deploy mobile apps
6. Setup monitoring and backups
7. Test all functionality
8. Launch to production

