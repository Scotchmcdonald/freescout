#!/bin/bash
# FreeScout Web Server Setup Script
# Configures Nginx to serve FreeScout at freescout-modern.local

set -e

echo "=========================================="
echo "FreeScout Web Server Setup"
echo "=========================================="
echo ""

# Check if running as root or with sudo
if [ "$EUID" -ne 0 ]; then 
    echo "❌ This script must be run with sudo"
    echo "Usage: sudo ./setup-webserver.sh"
    exit 1
fi

DOMAIN="${1:-freescout-modern.local}"
APP_ROOT="/var/www/html"
PUBLIC_ROOT="$APP_ROOT/public"

echo "Configuration:"
echo "  Domain: $DOMAIN"
echo "  Application Root: $APP_ROOT"
echo "  Public Root: $PUBLIC_ROOT"
echo ""

# Check if Nginx is installed
if ! command -v nginx &> /dev/null; then
    echo "📦 Installing Nginx..."
    apt-get update -qq
    apt-get install -y nginx
    echo "✅ Nginx installed"
else
    echo "✅ Nginx already installed ($(nginx -v 2>&1))"
fi

echo ""
echo "📝 Creating Nginx configuration..."

# Create Nginx site configuration
tee /etc/nginx/sites-available/freescout > /dev/null <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN;
    root $PUBLIC_ROOT;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Increase timeouts for long-running operations
    fastcgi_read_timeout 300;
    client_max_body_size 20M;
}
EOF

echo "✅ Nginx configuration created at /etc/nginx/sites-available/freescout"

# Enable the site
echo ""
echo "🔗 Enabling site..."
if [ -f /etc/nginx/sites-enabled/freescout ]; then
    echo "   Site already enabled"
else
    ln -s /etc/nginx/sites-available/freescout /etc/nginx/sites-enabled/
    echo "✅ Site enabled"
fi

# Disable default site if it exists
if [ -f /etc/nginx/sites-enabled/default ]; then
    echo ""
    echo "🔕 Disabling default Nginx site..."
    rm -f /etc/nginx/sites-enabled/default
    echo "✅ Default site disabled"
fi

# Test Nginx configuration
echo ""
echo "🔍 Testing Nginx configuration..."
nginx -t

# Add to /etc/hosts if not already there
echo ""
echo "📝 Updating /etc/hosts..."
if grep -q "$DOMAIN" /etc/hosts; then
    echo "   $DOMAIN already in /etc/hosts"
else
    echo "127.0.0.1    $DOMAIN" >> /etc/hosts
    echo "✅ Added $DOMAIN to /etc/hosts"
fi

# Set proper permissions
echo ""
echo "🔐 Setting permissions..."
chown -R www-data:www-data $APP_ROOT/storage
chown -R www-data:www-data $APP_ROOT/bootstrap/cache
chmod -R 775 $APP_ROOT/storage
chmod -R 775 $APP_ROOT/bootstrap/cache
echo "✅ Permissions set"

# Detect PHP-FPM socket location
echo ""
echo "🔍 Detecting PHP-FPM configuration..."
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
PHP_FPM_SOCK="/var/run/php/php${PHP_VERSION}-fpm.sock"

if [ ! -S "$PHP_FPM_SOCK" ]; then
    echo "⚠️  PHP-FPM socket not found at $PHP_FPM_SOCK"
    echo "   Checking alternative locations..."
    
    # Try to find PHP-FPM socket
    FOUND_SOCK=$(find /var/run/php/ -name "php*-fpm.sock" 2>/dev/null | head -1)
    
    if [ -n "$FOUND_SOCK" ]; then
        echo "   Found PHP-FPM at: $FOUND_SOCK"
        echo "   Updating Nginx configuration..."
        sed -i "s|/var/run/php/php-fpm.sock|$FOUND_SOCK|g" /etc/nginx/sites-available/freescout
        PHP_FPM_SOCK="$FOUND_SOCK"
    else
        echo "   ❌ Could not find PHP-FPM socket"
        echo "   Please install PHP-FPM: sudo apt-get install php${PHP_VERSION}-fpm"
        exit 1
    fi
else
    echo "✅ PHP-FPM found at $PHP_FPM_SOCK"
fi

# Check if PHP-FPM is installed
if ! systemctl list-units --type=service --all | grep -q "php.*-fpm"; then
    echo ""
    echo "📦 Installing PHP-FPM..."
    apt-get install -y php${PHP_VERSION}-fpm
    echo "✅ PHP-FPM installed"
fi

# Enable and start PHP-FPM
echo ""
echo "🚀 Starting PHP-FPM..."
PHP_FPM_SERVICE=$(systemctl list-units --type=service --all | grep "php.*-fpm" | awk '{print $1}' | head -1)
if [ -n "$PHP_FPM_SERVICE" ]; then
    systemctl enable $PHP_FPM_SERVICE
    systemctl start $PHP_FPM_SERVICE || systemctl restart $PHP_FPM_SERVICE
    echo "✅ PHP-FPM running"
else
    echo "⚠️  Could not find PHP-FPM service"
fi

# Reload Nginx
echo ""
echo "🔄 Reloading Nginx..."
systemctl enable nginx
systemctl restart nginx
echo "✅ Nginx restarted"

# Run Laravel optimizations
echo ""
echo "⚡ Running Laravel optimizations..."
cd $APP_ROOT
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
echo "✅ Laravel optimized"

echo ""
echo "=========================================="
echo "✅ Web Server Setup Complete!"
echo "=========================================="
echo ""
echo "FreeScout is now accessible at:"
echo "  http://$DOMAIN"
echo ""
echo "Services running:"
echo "  • Nginx: $(systemctl is-active nginx)"
echo "  • PHP-FPM: $(systemctl is-active $PHP_FPM_SERVICE 2>/dev/null || echo 'unknown')"
echo ""
echo "Useful commands:"
echo "  sudo systemctl status nginx          - Check Nginx status"
echo "  sudo systemctl restart nginx         - Restart Nginx"
echo "  sudo nginx -t                        - Test Nginx config"
echo "  sudo tail -f /var/log/nginx/error.log - View Nginx errors"
echo "  sudo tail -f $APP_ROOT/storage/logs/laravel.log - View Laravel logs"
echo ""
echo "Next steps:"
echo "  1. Visit http://$DOMAIN in your browser"
echo "  2. If DNS doesn't resolve, add to your local hosts file:"
echo "     127.0.0.1    $DOMAIN"
echo ""
