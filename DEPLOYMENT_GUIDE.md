# Deployment Guide - Adintel Platform

This guide covers deploying the Adintel platform to various hosting environments with complete Facebook, Google, Snapchat, and TikTok integrations.

## 🚀 Quick Deployment Options

### Option 1: Shared Hosting (cPanel/DirectAdmin)
### Option 2: VPS/Cloud Server (Ubuntu/CentOS)
### Option 3: Docker Deployment
### Option 4: Platform-as-a-Service (Heroku, DigitalOcean App Platform)

---

## 📋 Prerequisites

### System Requirements
- **PHP**: 8.2 or higher
- **MySQL**: 8.0 or higher
- **Node.js**: 18.x or higher
- **Composer**: 2.x
- **Web Server**: Apache/Nginx

### Required PHP Extensions
```bash
php-mysql
php-mbstring
php-xml
php-curl
php-zip
php-gd
php-json
php-tokenizer
php-fileinfo
php-bcmath
php-ctype
php-openssl
```

---

## 🔧 Environment Configuration

### 1. Create Production Environment File

Create `.env` file with the following configuration:

```env
# Application Settings
APP_NAME="Adintel"
APP_ENV=production
APP_KEY=base64:YOUR_32_CHARACTER_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=adintel_production
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

# Session & Cache
SESSION_DRIVER=database
CACHE_DRIVER=database
QUEUE_CONNECTION=database

# Mail Configuration (Optional)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Adintel Platform"

# Facebook Integration
FB_APP_ID=1090833026335549
FB_APP_SECRET=6b960b5a6ea8fcd303608790bd422dd0
FB_REDIRECT_URI=https://yourdomain.com/facebook-callback
FB_DEFAULT_ACCESS_TOKEN=EAAaaoKmhhGABPPM0Lyc44cV62HAMPuayVVFE2PHHbBLURpTQhzV3KB2ZB3QKRwKMTIMZATjHka6jkKc2YZBYCxRYbmCFdReZCdZCITGs4oYjaY8oXF2aYgJzA9bZCAJCPAftzyZBhZAyCZBrgK9ZAUUKYCimglgFAvH1ur1w81A9vQNmNgjKkaE5ZB8ZAeNmZAWJjM4tvYHPYHrO0HCDm1546wsVTsxcNvu0XfxsvvVMb

# Google Ads Configuration
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=https://yourdomain.com/google-callback

# Snapchat Ads Configuration
SNAPCHAT_CLIENT_ID=your_snapchat_client_id
SNAPCHAT_CLIENT_SECRET=your_snapchat_client_secret
SNAPCHAT_REDIRECT_URI=https://yourdomain.com/snapchat-callback

# TikTok Ads Configuration
TIKTOK_CLIENT_ID=your_tiktok_client_id
TIKTOK_CLIENT_SECRET=your_tiktok_client_secret
TIKTOK_REDIRECT_URI=https://yourdomain.com/tiktok-callback

# Security
SANCTUM_STATEFUL_DOMAINS=yourdomain.com
SESSION_SECURE_COOKIE=true
```

---

## 🌐 Deployment Methods

### Method 1: Shared Hosting (cPanel)

#### Step 1: Upload Files
```bash
# Compress your project
zip -r adintel.zip . -x "node_modules/*" ".git/*" "storage/logs/*"

# Upload via cPanel File Manager to public_html/
# Extract the files
```

#### Step 2: Configure Database
```sql
-- Create database via cPanel MySQL Databases
CREATE DATABASE cpanel_adintel;
CREATE USER 'cpanel_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON cpanel_adintel.* TO 'cpanel_user'@'localhost';
FLUSH PRIVILEGES;
```

#### Step 3: Install Dependencies
```bash
# SSH into your hosting account
cd public_html
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

#### Step 4: Set Permissions
```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### Step 5: Run Migrations
```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Method 2: VPS/Cloud Server (Ubuntu)

#### Step 1: Server Setup
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2
sudo add-apt-repository ppa:ondrej/php
sudo apt install php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-json php8.2-tokenizer php8.2-fileinfo php8.2-bcmath php8.2-ctype php8.2-openssl

# Install MySQL
sudo apt install mysql-server

# Install Nginx
sudo apt install nginx

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install nodejs

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### Step 2: Clone and Setup Project
```bash
# Clone project
git clone https://github.com/your-repo/adintel.git /var/www/adintel
cd /var/www/adintel

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Set permissions
sudo chown -R www-data:www-data /var/www/adintel
sudo chmod -R 755 /var/www/adintel/storage
sudo chmod -R 755 /var/www/adintel/bootstrap/cache
```

#### Step 3: Configure Nginx
```nginx
# /etc/nginx/sites-available/adintel
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/adintel/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/adintel /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### Step 4: SSL Certificate (Let's Encrypt)
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

#### Step 5: Database Setup
```bash
sudo mysql
```
```sql
CREATE DATABASE adintel_production;
CREATE USER 'adintel_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON adintel_production.* TO 'adintel_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### Step 6: Laravel Setup
```bash
cd /var/www/adintel
cp .env.example .env
# Edit .env with your configuration
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Method 3: Docker Deployment

#### Step 1: Create Production Docker Compose
```yaml
# docker-compose.prod.yml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile.prod
    container_name: adintel-app-prod
    restart: unless-stopped
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    volumes:
      - ./storage:/var/www/storage
    networks:
      - adintel-prod

  nginx:
    image: nginx:alpine
    container_name: adintel-nginx-prod
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./docker/nginx/prod.conf:/etc/nginx/nginx.conf
      - ./docker/nginx/ssl:/etc/ssl/certs
    networks:
      - adintel-prod
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    container_name: adintel-mysql-prod
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: adintel_production
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_USER: ${DB_USERNAME}
    volumes:
      - mysql-data:/var/lib/mysql
    networks:
      - adintel-prod

networks:
  adintel-prod:
    driver: bridge

volumes:
  mysql-data:
    driver: local
```

#### Step 2: Production Dockerfile
```dockerfile
# Dockerfile.prod
FROM php:8.2-fpm

WORKDIR /var/www

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application
COPY . /var/www

# Install dependencies
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www/storage /var/www/bootstrap/cache

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
```

#### Step 3: Deploy with Docker
```bash
# Build and deploy
docker-compose -f docker-compose.prod.yml up -d --build

# Run migrations
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker-compose -f docker-compose.prod.yml exec app php artisan db:seed --force
```

---

## 🔐 Security Checklist

### 1. Environment Security
- [ ] Set `APP_DEBUG=false` in production
- [ ] Use strong `APP_KEY`
- [ ] Enable HTTPS with SSL certificate
- [ ] Set secure database credentials
- [ ] Configure firewall rules

### 2. File Permissions
```bash
# Secure file permissions
find /var/www/adintel -type f -exec chmod 644 {} \;
find /var/www/adintel -type d -exec chmod 755 {} \;
chmod -R 775 /var/www/adintel/storage
chmod -R 775 /var/www/adintel/bootstrap/cache
```

### 3. Web Server Security
```nginx
# Add security headers
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
```

---

## 🔄 Maintenance & Updates

### Regular Maintenance Tasks
```bash
# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Update dependencies
composer update --no-dev
npm update && npm run build

# Database maintenance
php artisan migrate
```

### Backup Strategy
```bash
# Database backup
mysqldump -u username -p adintel_production > backup_$(date +%Y%m%d).sql

# File backup
tar -czf adintel_backup_$(date +%Y%m%d).tar.gz /var/www/adintel
```

---

## 🐛 Troubleshooting

### Common Issues

#### 1. Facebook Integration "Unauthenticated"
```bash
# Clear config cache
php artisan config:clear

# Check environment variables
php artisan tinker
>>> config('services.facebook.app_id')
```

#### 2. Database Connection Issues
```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo()
```

#### 3. Permission Issues
```bash
# Fix storage permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### 4. Frontend Assets Not Loading
```bash
# Rebuild assets
npm run build
php artisan view:clear
```

---

## 📊 Performance Optimization

### 1. Enable OPcache
```ini
# php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### 2. Database Optimization
```sql
-- Add indexes for better performance
CREATE INDEX idx_tenant_date ON ad_metrics(tenant_id, date);
CREATE INDEX idx_platform_account ON ad_metrics(platform, ad_account_id);
```

### 3. Caching Strategy
```bash
# Enable all caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 🎯 Platform-Specific OAuth Setup

### Facebook App Configuration
1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create new app → Business → App Name: "Adintel"
3. Add Facebook Login product
4. Set Valid OAuth Redirect URIs: `https://yourdomain.com/facebook-callback`
5. Add required permissions: `ads_read`, `ads_management`, `business_management`

### Google Ads API Setup
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create new project → Enable Google Ads API
3. Create OAuth 2.0 credentials
4. Set authorized redirect URI: `https://yourdomain.com/google-callback`

### Snapchat Ads Setup
1. Go to [Snapchat Business](https://business.snapchat.com/)
2. Create developer account
3. Register new app with redirect URI: `https://yourdomain.com/snapchat-callback`

### TikTok for Business Setup
1. Go to [TikTok for Business](https://business.tiktok.com/)
2. Apply for developer access
3. Create app with redirect URI: `https://yourdomain.com/tiktok-callback`

---

## ✅ Post-Deployment Checklist

- [ ] SSL certificate installed and working
- [ ] All environment variables configured
- [ ] Database migrations completed
- [ ] Demo data seeded
- [ ] File permissions set correctly
- [ ] Caches optimized
- [ ] Facebook integration working
- [ ] Google Ads integration configured
- [ ] Snapchat integration configured
- [ ] TikTok integration configured
- [ ] Email notifications working (if configured)
- [ ] Backup strategy implemented
- [ ] Monitoring setup (optional)

---

## 🆘 Support

For deployment issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check web server logs: `/var/log/nginx/error.log`
3. Verify environment configuration
4. Test database connectivity
5. Ensure all required PHP extensions are installed

---

**🎉 Your Adintel platform is now ready for production with full multi-platform ad integration!**
