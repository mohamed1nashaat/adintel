# 🚨 Fix Error 500 on https://adintel.redbananas.com/

Error 500 means there's a server configuration issue. Let's fix this step by step.

## 🔍 **Immediate Diagnosis Steps:**

### **Step 1: Check Error Logs**
**Via cPanel:**
1. **Go to cPanel** → **Error Logs**
2. **Check the latest entries** for specific error details
3. **Look for PHP errors** or permission issues

### **Step 2: Check File Permissions**
**Set correct permissions:**
- **Directories**: 755
- **Files**: 644
- **storage/ folder**: 755 (recursive)
- **bootstrap/cache/**: 755 (recursive)

## 🛠️ **Common 500 Error Fixes:**

### **Fix 1: Storage Permissions**
**Via cPanel File Manager:**
1. **Right-click on `storage/` folder**
2. **Change Permissions** → **755**
3. **Check "Recurse into subdirectories"**
4. **Apply**

**Repeat for:**
- `bootstrap/cache/` → 755 (recursive)
- `storage/logs/` → 755 (recursive)
- `storage/framework/` → 755 (recursive)

### **Fix 2: Create Missing Directories**
**Create these directories if missing:**
```
storage/logs/
storage/framework/cache/
storage/framework/sessions/
storage/framework/views/
bootstrap/cache/
```

### **Fix 3: Updated index.php (More Compatible)**
**Replace your current `index.php` with this version:**

```php
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if Laravel is properly installed
if (!file_exists(__DIR__.'/vendor/autoload.php')) {
    die('Error: vendor/autoload.php not found. Please ensure Composer dependencies are installed.');
}

// Check for maintenance mode
if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the auto loader
require __DIR__.'/vendor/autoload.php';

// Check if bootstrap file exists
if (!file_exists(__DIR__.'/bootstrap/app.php')) {
    die('Error: bootstrap/app.php not found. Please ensure all Laravel files are uploaded.');
}

try {
    // Run the application
    $app = require_once __DIR__.'/bootstrap/app.php';

    $kernel = $app->make(Kernel::class);

    $response = $kernel->handle(
        $request = Request::capture()
    );

    $response->send();

    $kernel->terminate($request, $response);
} catch (Exception $e) {
    // Show detailed error for debugging
    echo '<h1>Application Error</h1>';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
    echo '<p><strong>Line:</strong> ' . $e->getLine() . '</p>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}
```

### **Fix 4: Updated .htaccess (More Compatible)**
**Replace your current `.htaccess` with this version:**

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

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

# Disable directory browsing
Options -Indexes

# PHP Configuration
<IfModule mod_php.c>
    php_value memory_limit 256M
    php_value max_execution_time 300
    php_value upload_max_filesize 64M
    php_value post_max_size 64M
</IfModule>

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

<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>
```

## 🔧 **Alternative Quick Fix:**

### **Option A: Simple Debug Index**
**Create this as `index.php` to see what's wrong:**

```php
<?php
echo '<h1>Laravel Debug</h1>';

// Check PHP version
echo '<p><strong>PHP Version:</strong> ' . PHP_VERSION . '</p>';

// Check if files exist
$files = [
    'vendor/autoload.php',
    'bootstrap/app.php',
    '.env',
    'storage/',
    'bootstrap/cache/'
];

foreach ($files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    $status = $exists ? '✅ Found' : '❌ Missing';
    echo "<p><strong>$file:</strong> $status</p>";
}

// Check permissions
$dirs = ['storage', 'bootstrap/cache'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        echo "<p><strong>$dir permissions:</strong> $perms</p>";
    }
}

// Try to load Laravel
echo '<hr><h2>Loading Laravel...</h2>';
try {
    require __DIR__.'/vendor/autoload.php';
    echo '<p>✅ Autoloader loaded</p>';
    
    $app = require_once __DIR__.'/bootstrap/app.php';
    echo '<p>✅ Laravel app loaded</p>';
    
    echo '<p><strong>Success!</strong> Laravel is working. Remove this debug code.</p>';
} catch (Exception $e) {
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>
```

### **Option B: Check .env File**
**Ensure your `.env` file has:**
```env
APP_NAME="Adintel"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_URL=https://adintel.redbananas.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# Important: Set these for production
SESSION_DRIVER=database
CACHE_DRIVER=database
QUEUE_CONNECTION=database
```

## 🚨 **Emergency Steps:**

### **If Still Getting 500 Error:**

1. **Enable Debug Mode** temporarily:
   - Edit `.env` file
   - Change `APP_DEBUG=false` to `APP_DEBUG=true`
   - This will show detailed error messages

2. **Check PHP Error Log**:
   - Look in cPanel → Error Logs
   - Or check `/home/redbanan/adintel.redbananas.com/storage/logs/laravel.log`

3. **Contact Hosting Support**:
   - Show them the specific error from logs
   - Ask them to check PHP configuration

## 🎯 **Most Likely Causes:**

1. **Storage permissions** (most common)
2. **Missing directories** in storage/
3. **PHP memory limit** too low
4. **Database connection** issues
5. **Missing .env** configuration

## 📞 **Next Steps:**

1. **Try the updated index.php** with error reporting
2. **Set correct permissions** on storage/ and bootstrap/cache/
3. **Check error logs** for specific error details
4. **Share the specific error message** you see

**The error 500 is fixable - we just need to identify the specific cause!**
