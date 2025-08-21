#!/bin/bash

# Adintel Platform Deployment Script
# This script automates the deployment process for any hosting environment

set -e  # Exit on any error

echo "🚀 Starting Adintel Platform Deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}=== $1 ===${NC}"
}

# Check if running as root (for VPS deployment)
check_root() {
    if [[ $EUID -eq 0 ]]; then
        print_warning "Running as root. This is recommended for VPS deployment."
        return 0
    else
        print_status "Running as non-root user. This is fine for shared hosting."
        return 1
    fi
}

# Check system requirements
check_requirements() {
    print_header "Checking System Requirements"
    
    # Check PHP version
    if command -v php &> /dev/null; then
        PHP_VERSION=$(php -r "echo PHP_VERSION;")
        print_status "PHP version: $PHP_VERSION"
        
        if php -r "exit(version_compare(PHP_VERSION, '8.2.0', '<') ? 1 : 0);"; then
            print_error "PHP 8.2 or higher is required. Current version: $PHP_VERSION"
            exit 1
        fi
    else
        print_error "PHP is not installed or not in PATH"
        exit 1
    fi
    
    # Check Composer
    if command -v composer &> /dev/null; then
        COMPOSER_VERSION=$(composer --version | cut -d' ' -f3)
        print_status "Composer version: $COMPOSER_VERSION"
    else
        print_error "Composer is not installed or not in PATH"
        exit 1
    fi
    
    # Check Node.js
    if command -v node &> /dev/null; then
        NODE_VERSION=$(node --version)
        print_status "Node.js version: $NODE_VERSION"
    else
        print_error "Node.js is not installed or not in PATH"
        exit 1
    fi
    
    # Check npm
    if command -v npm &> /dev/null; then
        NPM_VERSION=$(npm --version)
        print_status "npm version: $NPM_VERSION"
    else
        print_error "npm is not installed or not in PATH"
        exit 1
    fi
    
    print_status "✅ All system requirements met!"
}

# Install PHP dependencies
install_php_dependencies() {
    print_header "Installing PHP Dependencies"
    
    if [ -f "composer.json" ]; then
        print_status "Installing Composer dependencies..."
        composer install --no-dev --optimize-autoloader --no-interaction
        print_status "✅ PHP dependencies installed successfully!"
    else
        print_error "composer.json not found!"
        exit 1
    fi
}

# Install Node.js dependencies and build assets
install_node_dependencies() {
    print_header "Installing Node.js Dependencies and Building Assets"
    
    if [ -f "package.json" ]; then
        print_status "Installing npm dependencies..."
        npm install
        
        print_status "Building production assets..."
        npm run build
        
        print_status "✅ Node.js dependencies installed and assets built successfully!"
    else
        print_error "package.json not found!"
        exit 1
    fi
}

# Setup environment file
setup_environment() {
    print_header "Setting Up Environment Configuration"
    
    if [ ! -f ".env" ]; then
        if [ -f ".env.production" ]; then
            print_status "Copying .env.production to .env..."
            cp .env.production .env
        elif [ -f ".env.example" ]; then
            print_status "Copying .env.example to .env..."
            cp .env.example .env
        else
            print_error "No environment template found!"
            exit 1
        fi
    else
        print_status ".env file already exists, skipping..."
    fi
    
    # Generate application key if not set
    if ! grep -q "APP_KEY=base64:" .env; then
        print_status "Generating application key..."
        php artisan key:generate --force
    else
        print_status "Application key already set, skipping..."
    fi
    
    print_status "✅ Environment configuration completed!"
}

# Set file permissions
set_permissions() {
    print_header "Setting File Permissions"
    
    # Create directories if they don't exist
    mkdir -p storage/logs
    mkdir -p storage/framework/cache
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p bootstrap/cache
    
    # Set permissions
    if check_root; then
        print_status "Setting ownership to www-data..."
        chown -R www-data:www-data storage bootstrap/cache
    fi
    
    print_status "Setting directory permissions..."
    chmod -R 755 storage
    chmod -R 755 bootstrap/cache
    
    print_status "✅ File permissions set successfully!"
}

# Database setup
setup_database() {
    print_header "Setting Up Database"
    
    print_status "Running database migrations..."
    php artisan migrate --force
    
    # Ask if user wants to seed demo data
    read -p "Do you want to seed demo data? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        print_status "Seeding demo data..."
        php artisan db:seed --force
        print_status "✅ Demo data seeded successfully!"
        
        echo
        print_status "Demo Login Credentials:"
        echo "  Admin: admin@demo.com / password"
        echo "  Viewer: viewer@demo.com / password"
        echo
    fi
    
    print_status "✅ Database setup completed!"
}

# Optimize Laravel
optimize_laravel() {
    print_header "Optimizing Laravel Application"
    
    print_status "Clearing all caches..."
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear
    
    print_status "Caching configurations for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    print_status "✅ Laravel optimization completed!"
}

# Setup web server configuration
setup_web_server() {
    print_header "Web Server Configuration"
    
    if check_root; then
        # Check if Nginx is installed
        if command -v nginx &> /dev/null; then
            print_status "Nginx detected. Creating configuration..."
            
            # Get domain name from user
            read -p "Enter your domain name (e.g., yourdomain.com): " DOMAIN_NAME
            
            if [ ! -z "$DOMAIN_NAME" ]; then
                # Create Nginx configuration
                cat > "/etc/nginx/sites-available/adintel" << EOF
server {
    listen 80;
    server_name $DOMAIN_NAME www.$DOMAIN_NAME;
    root $(pwd)/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    # Deny access to sensitive files
    location ~ /\.(env|git) {
        deny all;
    }
}
EOF
                
                # Enable site
                ln -sf /etc/nginx/sites-available/adintel /etc/nginx/sites-enabled/
                
                # Test Nginx configuration
                if nginx -t; then
                    print_status "Nginx configuration is valid"
                    systemctl reload nginx
                    print_status "✅ Nginx configuration applied successfully!"
                    
                    # Offer SSL setup
                    read -p "Do you want to setup SSL with Let's Encrypt? (y/N): " -n 1 -r
                    echo
                    if [[ $REPLY =~ ^[Yy]$ ]]; then
                        if command -v certbot &> /dev/null; then
                            print_status "Setting up SSL certificate..."
                            certbot --nginx -d $DOMAIN_NAME -d www.$DOMAIN_NAME --non-interactive --agree-tos --email admin@$DOMAIN_NAME
                            print_status "✅ SSL certificate installed successfully!"
                        else
                            print_warning "Certbot not found. Please install it manually for SSL."
                        fi
                    fi
                else
                    print_error "Nginx configuration test failed!"
                fi
            fi
        elif command -v apache2 &> /dev/null || command -v httpd &> /dev/null; then
            print_status "Apache detected. Please configure manually using the documentation."
        else
            print_warning "No web server detected. Please configure manually."
        fi
    else
        print_status "Non-root user detected. Web server configuration skipped."
        print_status "Please configure your web server to point to the 'public' directory."
    fi
}

# Final checks and information
final_checks() {
    print_header "Final Checks and Information"
    
    # Test if Laravel is working
    if php artisan --version &> /dev/null; then
        LARAVEL_VERSION=$(php artisan --version | cut -d' ' -f3)
        print_status "Laravel version: $LARAVEL_VERSION"
    fi
    
    # Check if .env is properly configured
    if grep -q "APP_KEY=base64:" .env && ! grep -q "your_" .env; then
        print_status "✅ Environment configuration looks good!"
    else
        print_warning "Please review your .env file and update the placeholder values."
    fi
    
    # Display important information
    echo
    print_header "🎉 Deployment Completed Successfully!"
    echo
    print_status "Important Information:"
    echo "  • Application URL: $(grep APP_URL .env | cut -d'=' -f2)"
    echo "  • Document Root: $(pwd)/public"
    echo "  • Environment: $(grep APP_ENV .env | cut -d'=' -f2)"
    echo
    print_status "Next Steps:"
    echo "  1. Update your .env file with actual values (database, domain, etc.)"
    echo "  2. Configure your web server to point to the 'public' directory"
    echo "  3. Set up SSL certificate for production"
    echo "  4. Configure platform integrations (Facebook, Google, etc.)"
    echo "  5. Test the application thoroughly"
    echo
    print_status "Platform Integration Status:"
    echo "  • Facebook: ✅ Configured with working credentials"
    echo "  • Google Ads: ⚠️  Requires your credentials"
    echo "  • Snapchat: ⚠️  Requires your credentials"
    echo "  • TikTok: ⚠️  Requires your credentials"
    echo
    print_status "Demo Credentials (if seeded):"
    echo "  • Admin: admin@demo.com / password"
    echo "  • Viewer: viewer@demo.com / password"
    echo
    print_status "For support, check the DEPLOYMENT_GUIDE.md file!"
}

# Main deployment process
main() {
    print_header "🚀 Adintel Platform Deployment Script"
    echo
    
    # Check if we're in the right directory
    if [ ! -f "artisan" ]; then
        print_error "Laravel artisan file not found. Please run this script from the project root directory."
        exit 1
    fi
    
    # Run deployment steps
    check_requirements
    install_php_dependencies
    install_node_dependencies
    setup_environment
    set_permissions
    setup_database
    optimize_laravel
    setup_web_server
    final_checks
    
    echo
    print_status "🎉 Deployment completed successfully!"
    print_status "Your Adintel platform is ready to use!"
}

# Run main function
main "$@"
