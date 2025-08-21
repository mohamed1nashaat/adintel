# 🚨 Fix 403 Error on https://adintel.codestan.net/

## 🔍 **Root Cause Analysis**

The 403 error occurs because:
1. **Missing index.php** in the web root
2. **Incorrect file permissions**
3. **Web server pointing to wrong directory**
4. **Missing .htaccess** configuration

## ✅ **IMMEDIATE FIX - Follow These Exact Steps:**

### **Step 1: Check Current File Structure**

Your current upload should look like this:
```
public_html/ (or www/ or httpdocs/)
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── vendor/
├── .env
├── artisan
├── composer.json
└── ... (other Laravel files)
```

### **Step 2: Move Files to Correct Locations**

**Copy these files from `public/` folder to your domain root:**

1. **Copy** `public/index.php` → **Root directory**
2. **Copy** `public/.htaccess` → **Root directory**  
3. **Copy** `public/build/` folder → **Root directory** (if exists)
4. **Copy** any other files from `public/` → **Root directory**

### **Step 3: Update index.php Paths**

**Edit the `index.php` file in your root directory:**

Find these lines:
```php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
```

**Change them to:**
```php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
```

### **Step 4: Set File Permissions**

**Via cPanel File Manager or FTP:**
- **Directories**: 755 permissions
- **Files**: 644 permissions
- **storage/ folder**: 755 (recursive)
- **bootstrap/cache/**: 755 (recursive)

### **Step 5: Create/Update .env File**

**Rename** `.env.production` to `.env` and update:
```env
APP_NAME="Adintel"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://adintel.codestan.net

# Generate key using: https://adintel.codestan.net/generate-key.php
APP_KEY=base64:YOUR_GENERATED_KEY_HERE

# Database settings (update with your actual values)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# Facebook Integration (Already configured!)
FB_APP_ID=1090833026335549
FB_APP_SECRET=6b960b5a6ea8fcd303608790bd422dd0
FB_REDIRECT_URI=https://adintel.codestan.net/facebook-callback
FB_DEFAULT_ACCESS_TOKEN=EAAaaoKmhhGABPPM0Lyc44cV62HAMPuayVVFE2PHHbBLURpTQhzV3KB2ZB3QKRwKMTIMZATjHka6jkKc2YZBYCxRYbmCFdReZCdZCITGs4oYjaY8oXF2aYgJzA9bZCAJCPAftzyZBhZAyCZBrgK9ZAUUKYCimglgFAvH1ur1w81A9vQNmNgjKkaE5ZB8ZAeNmZAWJjM4tvYHPYHrO0HCDm1546wsVTsxcNvu0XfxsvvVMb

SESSION_DRIVER=database
CACHE_DRIVER=database
QUEUE_CONNECTION=database
```

### **Step 6: Import Database**

1. **Create MySQL database** via cPanel
2. **Import** `database_export.sql` via phpMyAdmin
3. **Update database credentials** in `.env`

### **Step 7: Generate Application Key**

1. **Visit**: `https://adintel.codestan.net/generate-key.php`
2. **Copy the generated key**
3. **Update APP_KEY** in `.env` file
4. **Delete** `generate-key.php` after use

## 🧪 **Test the Fix**

After completing the steps above:

1. **Visit**: `https://adintel.codestan.net/`
2. **Should show**: Laravel application (not 403 error)
3. **Login with**: `admin@demo.com` / `password`

## 🔧 **Alternative Quick Fix**

If the above seems complex, try this **simple approach**:

1. **Delete everything** in your web root
2. **Upload only the contents** of the `public/` folder to your web root
3. **Create a new folder** called `laravel/` in your web root
4. **Upload all other Laravel files** to the `laravel/` folder
5. **Edit index.php** to point to `laravel/vendor/autoload.php` and `laravel/bootstrap/app.php`

## 🚨 **Still Getting 403?**

### **Check These:**

1. **File Permissions**:
   ```bash
   chmod 755 storage/ -R
   chmod 755 bootstrap/cache/ -R
   chmod 644 .env
   chmod 644 index.php
   ```

2. **Missing Files**:
   - Ensure `index.php` exists in root
   - Ensure `.htaccess` exists in root
   - Ensure `vendor/` folder exists

3. **Server Configuration**:
   - Check if mod_rewrite is enabled
   - Check if .htaccess files are allowed

4. **Contact Your Hosting Provider** if none of the above works

## 📞 **Need Immediate Help?**

**Send me:**
1. Screenshot of your file manager showing the directory structure
2. Contents of your current `index.php` file
3. Any error messages from error logs

## 🎯 **Expected Result**

After fixing, you should see:
- ✅ **Homepage loads** without 403 error
- ✅ **Login page accessible** at `/login`
- ✅ **Facebook integration ready** to use
- ✅ **Admin panel working** with demo credentials

**The platform will be fully functional with working Facebook integration!**
