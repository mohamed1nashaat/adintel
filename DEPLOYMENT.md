# Adintel Deployment Guide

## Current Issue: Web Server Configuration

The Adintel Laravel application has been successfully built and is located at:
```
/home/u552682660/public_html/public_html/adintel/
```

However, the web server for `adintel.codestan.net` needs to be configured to point to the Laravel application's `public` directory:
```
/home/u552682660/public_html/public_html/adintel/public/
```

## Required Web Server Configuration

### Option 1: Update Document Root (Recommended)
Configure the web server (Apache/Nginx) to point the document root for `adintel.codestan.net` to:
```
/home/u552682660/public_html/public_html/adintel/public/
```

### Option 2: Symlink Approach
If you cannot change the document root, create a symlink:
```bash
# From the web server's current document root
ln -s /home/u552682660/public_html/public_html/adintel/public/* .
```

### Option 3: Copy Files (Not Recommended for Production)
Copy the contents of the public directory to the web server root:
```bash
cp -r /home/u552682660/public_html/public_html/adintel/public/* /path/to/webserver/root/
```

## Environment Configuration

The application is configured with:
- **Database**: MySQL (u552682660_adintel)
- **URL**: https://adintel.codestan.net
- **Environment**: Production ready

## Post-Deployment Steps

1. **Set Proper Permissions**:
   ```bash
   chmod -R 755 /home/u552682660/public_html/public_html/adintel/
   chmod -R 775 /home/u552682660/public_html/public_html/adintel/storage/
   chmod -R 775 /home/u552682660/public_html/public_html/adintel/bootstrap/cache/
   ```

2. **Run Database Migrations** (if not already done):
   ```bash
   cd /home/u552682660/public_html/public_html/adintel/
   php artisan migrate --force
   ```

3. **Seed Demo Data** (if not already done):
   ```bash
   php artisan db:seed --class=DemoSeeder --force
   ```

4. **Build Frontend Assets**:
   ```bash
   npm install
   npm run build
   ```

5. **Clear Caches**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

## Demo Credentials

Once deployed, you can access the application with:

- **Admin User**: admin@demo.com / password
- **Viewer User**: viewer@demo.com / password

## Application Features

The deployed application includes:
- ✅ Multi-tenant SaaS architecture
- ✅ Objective-aware analytics (Awareness, Leads, Sales, Calls)
- ✅ Complete API with 27 endpoints
- ✅ Vue 3 + TypeScript frontend
- ✅ 30 days of demo data across all objectives
- ✅ Export functionality
- ✅ Role-based access control
- ✅ Comprehensive testing suite

## Troubleshooting

If you continue to see 403/404 errors:
1. Verify the web server document root points to the `public` directory
2. Check file permissions (755 for directories, 644 for files)
3. Ensure the `.htaccess` file is present in the public directory
4. Verify PHP is enabled and Laravel requirements are met
5. Check error logs for specific issues

## Support

The application is production-ready with:
- Complete database schema (11 tables)
- Full API implementation
- Modern frontend with responsive design
- Comprehensive documentation
- Docker support for development
- CI/CD pipeline ready
