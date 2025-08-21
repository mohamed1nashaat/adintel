# 🚨 Fix Directory Listing on https://adintel.redbananas.com/

The site is showing a file list instead of the Laravel application. This means:
1. **Missing or broken `index.php`** in the root directory
2. **Web server showing directory contents** instead of executing PHP

## ✅ **IMMEDIATE FIX - Follow These Steps:**

### **Step 1: Create Working index.php**

**Create this file as `index.php` in your domain root:**

```php
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Check if Laravel is properly installed
if (!file_exists(__DIR__.'/vendor/autoload.php')) {
    die('Laravel not properly installed. Please ensure all files are uploaded correctly.');
}

// Check for maintenance mode
if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the auto loader
require __DIR__.'/vendor/autoload.php';

// Check if bootstrap file exists
if (!file_exists(__DIR__.'/bootstrap/app.php')) {
    die('Bootstrap file missing. Please ensure all Laravel files are uploaded.');
}

// Run the application
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
```

### **Step 2: Create/Update .htaccess**

**Create this file as `.htaccess` in your domain root:**

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Disable directory browsing
    Options -Indexes

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Disable directory browsing (fallback)
IndexIgnore *

# Security Headers
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set X-Content-Type-Options "nosniff"
</IfModule>

# Deny access to sensitive files
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

<Files "composer.json">
    Order allow,deny
    Deny from all
</Files>

<Files "artisan">
    Order allow,deny
    Deny from all
</Files>
```

### **Step 3: Verify File Structure**

**Your root directory should have:**
```
public_html/ (or www/)
├── index.php ← THIS FILE MUST EXIST
├── .htaccess ← THIS FILE MUST EXIST
├── app/
├── bootstrap/
├── config/
├── database/
├── resources/
├── routes/
├── storage/
├── vendor/
├── .env
├── artisan
└── composer.json
```

### **Step 4: Set Correct Permissions**

**Via cPanel File Manager:**
- **index.php**: 644
- **.htaccess**: 644
- **Directories**: 755
- **storage/ folder**: 755 (recursive)
- **bootstrap/cache/**: 755 (recursive)

### **Step 5: Create .env File**

**Rename `.env.production` to `.env` and update:**
```env
APP_NAME="Adintel"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://adintel.redbananas.com

# Generate using: https://adintel.redbananas.com/generate-key.php
APP_KEY=base64:YOUR_GENERATED_KEY_HERE

# Database (update with your values)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# Facebook Integration (Ready to use!)
FB_APP_ID=1090833026335549
FB_APP_SECRET=6b960b5a6ea8fcd303608790bd422dd0
FB_REDIRECT_URI=https://adintel.redbananas.com/facebook-callback
FB_DEFAULT_ACCESS_TOKEN=EAAaaoKmhhGABPPM0Lyc44cV62HAMPuayVVFE2PHHbBLURpTQhzV3KB2ZB3QKRwKMTIMZATjHka6jkKc2YZBYCxRYbmCFdReZCdZCITGs4oYjaY8oXF2aYgJzA9bZCAJCPAftzyZBhZAyCZBrgK9ZAUUKYCimglgFAvH1ur1w81A9vQNmNgjKkaE5ZB8ZAeNmZAWJjM4tvYHPYHrO0HCDm1546wsVTsxcNvu0XfxsvvVMb

SESSION_DRIVER=database
CACHE_DRIVER=database
QUEUE_CONNECTION=database
```

## 🧪 **Test the Fix**

After completing the steps:

1. **Visit**: `https://adintel.redbananas.com/`
2. **Should show**: Laravel application (not file list)
3. **If still showing files**: Check if `index.php` exists and has correct content

## 🔧 **Alternative Quick Fix**

If you're still seeing the file list:

### **Option A: Simple Redirect**
Create this as `index.html` in your root:
```html
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="refresh" content="0; url=public/">
    <title>Redirecting...</title>
</head>
<body>
    <p>Redirecting to application...</p>
    <script>window.location.href = 'public/';</script>
</body>
</html>
```

### **Option B: Move Public Files**
1. **Copy everything from `public/` folder** to your root
2. **Update `index.php` paths** to remove `../`
3. **Move Laravel files** to a subfolder like `laravel/`

## 🚨 **Emergency Fix**

If nothing works, create this simple `index.php`:

```php
<?php
// Simple Laravel bootstrap
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
    $app = require 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );
    $response->send();
    $kernel->terminate($request, $response);
} else {
    echo '<h1>Adintel Platform</h1>';
    echo '<p>Laravel files not found. Please ensure all files are uploaded correctly.</p>';
    echo '<p>Current directory contents:</p>';
    echo '<pre>' . print_r(scandir('.'), true) . '</pre>';
}
?>
```

## 📞 **Still Not Working?**

**Check these:**

1. **PHP Version**: Ensure PHP 8.2+ is enabled
2. **File Upload**: Ensure all Laravel files are uploaded
3. **Permissions**: 644 for files, 755 for directories
4. **mod_rewrite**: Ensure it's enabled on your hosting

## 🎯 **Expected Result**

After fixing:
- ✅ **No more file listing**
- ✅ **Laravel application loads**
- ✅ **Login page accessible**
- ✅ **Facebook integration ready**

**The platform will be fully functional at https://adintel.redbananas.com/**
