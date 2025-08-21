# 🚀 Adintel - Shared Hosting Deployment Guide

Deploy Adintel to any shared hosting (cPanel, DirectAdmin, etc.) in just 3 steps!

## 📋 What You Need

- **Shared hosting account** with PHP 8.2+ and MySQL
- **cPanel or similar control panel**
- **Domain name** pointed to your hosting

## 🎯 3-Step Deployment

### Step 1: Upload Files

1. **Download/Clone** the Adintel project
2. **Zip the entire project** (except `node_modules` if present)
3. **Upload to your hosting**:
   - Via cPanel File Manager → `public_html/`
   - Via FTP to your domain's root directory
4. **Extract the files** in your hosting control panel

### Step 2: Setup Database

1. **Create MySQL Database** via cPanel:
   - Database name: `your_cpanel_username_adintel`
   - Username: `your_cpanel_username_adintel`
   - Password: `your_secure_password`

2. **Import Database**:
   - Go to phpMyAdmin in cPanel
   - Select your database
   - Click "Import" tab
   - Upload `database_export.sql` file
   - Click "Go"

### Step 3: Configure Environment

1. **Rename** `.env.production` to `.env`

2. **Edit `.env` file** with your details:
```env
# Update these with your actual values
APP_URL=https://yourdomain.com
DB_HOST=localhost
DB_DATABASE=your_cpanel_username_adintel
DB_USERNAME=your_cpanel_username_adintel
DB_PASSWORD=your_secure_password

# Facebook Integration (Already Working!)
FB_REDIRECT_URI=https://yourdomain.com/facebook-callback

# Generate new key (see below)
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
```

3. **Generate APP_KEY**:
   - Visit: `https://yourdomain.com/generate-key.php`
   - Copy the generated key to your `.env` file
   - Delete `generate-key.php` after use

## ✅ That's It!

Your Adintel platform is now live at: `https://yourdomain.com`

### 🔑 Demo Login Credentials
- **Admin**: `admin@demo.com` / `password`
- **Viewer**: `viewer@demo.com` / `password`

### 🎯 Facebook Integration
- ✅ **Already configured** with working credentials
- Just click "Connect with Facebook" in the Integrations page
- OAuth flow will work immediately!

## 🔧 Optional: Add Other Platforms

To add Google, Snapchat, TikTok integrations, update your `.env`:

```env
# Google Ads
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=https://yourdomain.com/google-callback

# Snapchat Ads
SNAPCHAT_CLIENT_ID=your_snapchat_client_id
SNAPCHAT_CLIENT_SECRET=your_snapchat_client_secret
SNAPCHAT_REDIRECT_URI=https://yourdomain.com/snapchat-callback

# TikTok Ads
TIKTOK_CLIENT_ID=your_tiktok_client_id
TIKTOK_CLIENT_SECRET=your_tiktok_client_secret
TIKTOK_REDIRECT_URI=https://yourdomain.com/tiktok-callback
```

## 🐛 Troubleshooting

### Issue: "500 Internal Server Error"
**Solution**: Check file permissions
```bash
# Via cPanel File Manager or SSH
chmod 755 storage/ -R
chmod 755 bootstrap/cache/ -R
```

### Issue: "Database connection failed"
**Solution**: Double-check database credentials in `.env`

### Issue: "Facebook integration not working"
**Solution**: Update `FB_REDIRECT_URI` in `.env` with your actual domain

### Issue: "Assets not loading"
**Solution**: Ensure your domain points to the `public/` directory

## 📞 Support

- Check the main `README.md` for detailed documentation
- Review `DEPLOYMENT_GUIDE.md` for advanced configurations
- All integrations are pre-built and ready to use!

---

## 🎉 Success!

**Your multi-platform ad intelligence platform is now live and ready to connect Facebook, Google, Snapchat, and TikTok ads!**

**🚀 Total deployment time: Under 10 minutes!**
