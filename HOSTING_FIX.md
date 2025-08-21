# 🔧 Fix 403 Error on https://adintel.codestan.net/

The 403 error occurs because the web server needs to point to the `public/` directory, not the root directory.

## 🎯 Quick Fixes (Choose One):

### **Option 1: Move Files to Correct Location**

If your hosting points to `public_html/` or `www/`:

1. **Create a subdirectory**: `public_html/adintel/`
2. **Move all Laravel files** to `public_html/adintel/`
3. **Move contents of `public/` folder** to `public_html/`
4. **Update `index.php`** in `public_html/`:

```php
<?php
// Change this line:
require __DIR__.'/../vendor/autoload.php';
// To this:
require __DIR__.'/adintel/vendor/autoload.php';

// Change this line:
$app = require_once __DIR__.'/../bootstrap/app.php';
// To this:
$app = require_once __DIR__.'/adintel/bootstrap/app.php';
```

### **Option 2: Create Root Index File**

Create this file as `index.php` in your domain root:

```php
<?php
// Redirect to public directory
header('Location: /public/');
exit;
```

### **Option 3: Use .htaccess Redirect**

Create this `.htaccess` in your domain root:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

## 🔧 **Recommended Solution for Your Hosting:**

Since you're using `adintel.codestan.net`, try **Option 1**:

1. **File Structure Should Be:**
```
public_html/ (or www/)
├── index.php (from public/ folder)
├── .htaccess (from public/ folder)
├── build/ (from public/build/)
├── adintel/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env
│   ├── artisan
│   └── composer.json
```

2. **Update index.php** in root with the paths above

## 🚨 **Alternative: Simple Fix**

If the above seems complex, try this simple approach:

1. **Copy everything from `public/` folder** to your domain root
2. **Update the paths** in `index.php` to point to the Laravel files

## 🔍 **Check Your Current Setup:**

Visit: `https://adintel.codestan.net/generate-key.php`

If this works, then the files are uploaded correctly, and you just need to fix the directory structure.

## 📞 **Need Help?**

If you're still getting 403 errors:

1. **Check file permissions**: Should be 755 for directories, 644 for files
2. **Check if there's an index file** in the root
3. **Verify the web server** is pointing to the right directory

Let me know which option works for your hosting setup!
