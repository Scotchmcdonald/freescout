#!/bin/bash

###############################################################################
# FreeScout Development Environment Setup
# This script automates the installation of required dependencies for
# development and testing.
###############################################################################

set -e  # Exit on error

echo "======================================"
echo "FreeScout Dev Environment Setup"
echo "======================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() { echo -e "${GREEN}✓ $1${NC}"; }
print_error() { echo -e "${RED}✗ $1${NC}"; }
print_info() { echo -e "${YELLOW}→ $1${NC}"; }

# Detect PHP version
print_info "Detecting PHP version..."
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
print_success "PHP $PHP_VERSION detected"

# Check if running on Ubuntu/Debian
if ! command -v apt-get &> /dev/null; then
    print_error "This script is designed for Ubuntu/Debian systems"
    exit 1
fi

# Check for sudo privileges
if [ "$EUID" -ne 0 ]; then 
    print_info "This script requires sudo privileges for package installation"
    SUDO="sudo"
else
    SUDO=""
fi

echo ""
echo "======================================"
echo "Installing PHP Extensions"
echo "======================================"
echo ""

# Update package list
print_info "Updating package list..."
$SUDO apt-get update -qq

# SQLite extension for fast testing
print_info "Checking SQLite extension..."
if php -m | grep -q "sqlite3"; then
    print_success "SQLite extension already installed"
else
    print_info "Installing php${PHP_VERSION}-sqlite3..."
    $SUDO apt-get install -y php${PHP_VERSION}-sqlite3
    print_success "SQLite extension installed"
fi

# IMAP extension for email fetching
print_info "Checking IMAP extension..."
if php -m | grep -q "imap"; then
    print_success "IMAP extension already installed"
else
    print_info "Installing php${PHP_VERSION}-imap..."
    $SUDO apt-get install -y php${PHP_VERSION}-imap
    print_success "IMAP extension installed"
fi

# Additional recommended extensions
EXTENSIONS=("bcmath" "curl" "gd" "intl" "mbstring" "mysql" "xml" "zip")

for EXT in "${EXTENSIONS[@]}"; do
    print_info "Checking $EXT extension..."
    if php -m | grep -qi "$EXT"; then
        print_success "$EXT extension already installed"
    else
        print_info "Installing php${PHP_VERSION}-${EXT}..."
        $SUDO apt-get install -y php${PHP_VERSION}-${EXT} 2>/dev/null || print_info "$EXT not available as separate package"
    fi
done

echo ""
echo "======================================"
echo "Installing Development Tools"
echo "======================================"
echo ""

# Composer
print_info "Checking Composer..."
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version | grep -oP '\d+\.\d+\.\d+' | head -1)
    print_success "Composer $COMPOSER_VERSION installed"
else
    print_info "Installing Composer..."
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --quiet
    $SUDO mv composer.phar /usr/local/bin/composer
    rm composer-setup.php
    print_success "Composer installed"
fi

# Node.js and npm
print_info "Checking Node.js..."
if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    print_success "Node.js $NODE_VERSION installed"
else
    print_info "Node.js not found. Please install Node.js 18.x or higher manually:"
    echo "  curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -"
    echo "  sudo apt-get install -y nodejs"
fi

echo ""
echo "======================================"
echo "Verifying Installation"
echo "======================================"
echo ""

# Verify critical extensions
CRITICAL_EXTENSIONS=("pdo_sqlite" "sqlite3" "imap" "pdo_mysql")
ALL_OK=true

for EXT in "${CRITICAL_EXTENSIONS[@]}"; do
    if php -m | grep -q "$EXT"; then
        print_success "$EXT: OK"
    else
        print_error "$EXT: MISSING"
        ALL_OK=false
    fi
done

echo ""
if [ "$ALL_OK" = true ]; then
    print_success "All critical extensions installed successfully!"
    echo ""
    echo "======================================"
    echo "Next Steps"
    echo "======================================"
    echo ""
    echo "1. Install PHP dependencies:"
    echo "   composer install"
    echo ""
    echo "2. Install frontend dependencies:"
    echo "   npm install"
    echo ""
    echo "3. Copy environment file:"
    echo "   cp .env.example .env"
    echo ""
    echo "4. Generate application key:"
    echo "   php artisan key:generate"
    echo ""
    echo "5. Run migrations:"
    echo "   php artisan migrate"
    echo ""
    echo "6. Run tests (with fast SQLite):"
    echo "   php artisan test"
    echo "   # Or exclude slow IMAP tests:"
    echo "   php artisan test --exclude-group=slow"
    echo ""
else
    print_error "Some extensions are missing. Please install them manually."
    exit 1
fi

echo "======================================"
echo "Setup Complete!"
echo "======================================"
