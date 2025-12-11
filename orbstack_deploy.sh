#!/bin/bash

# Ensure the script is running in Bash
if [ -z "$BASH_VERSION" ]; then
    exec bash "$0" "$@"
fi

# ==========================================
# 1. DEFAULTS & CONFIGURATION
# ==========================================

DEFAULT_REPO="https://github.com/Scotchmcdonald/freescout.git"
DEFAULT_BRANCH="laravel-11-foundation"
DEFAULT_INSTALL_DIR="$HOME/freescout-tunnel"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
RED='\033[0;31m'
NC='\033[0m'

clear
echo -e "${CYAN}============================================================${NC}"
echo -e "${CYAN}   FreeScout Cloudflare Tunnel Deployer   ${NC}"
echo -e "${CYAN}============================================================${NC}"

CONFIG_FILE="deploy.conf"
INTERACTIVE=true

# Check for Config File
if [ -f "$CONFIG_FILE" ]; then
    echo -e "${GREEN}Configuration file '$CONFIG_FILE' found.${NC}"
    read -p "Do you want to use this configuration? [Y/n] " USE_CONFIG
    USE_CONFIG=${USE_CONFIG:-Y}
    if [[ "$USE_CONFIG" =~ ^[Yy]$ ]]; then
        echo "Loading configuration..."
        . "$CONFIG_FILE"
        INTERACTIVE=false
    fi
else
    # Create Config Template
    echo "No configuration file found."
    read -p "Create configuration template? [y/N] " CREATE_CONFIG
    if [[ "$CREATE_CONFIG" =~ ^[Yy]$ ]]; then
        cat <<EOF > "$CONFIG_FILE"
GIT_REPO_URL="$DEFAULT_REPO"
GIT_BRANCH="$DEFAULT_BRANCH"
DEFAULT_INSTALL_DIR="$DEFAULT_INSTALL_DIR"
DOMAIN_NAME="devtickets.scotchmcdonald.dev" 

# Cloudflare Tunnel Token (From Zero Trust Dashboard)
CF_TUNNEL_TOKEN=""

# Database
DB_ROOT_PASS="change_me"
DB_USER="freescout"
DB_PASS="change_me"
DB_NAME="freescout"

# Admin
ADMIN_EMAIL="admin@borealtek.ca"
ADMIN_PASS="change_me"

# Google OAuth (Optional)
GOOGLE_CLIENT_ID=""
GOOGLE_CLIENT_SECRET=""
GOOGLE_ADMIN_EMAILS=""
EOF
        echo -e "${GREEN}Template created at $CONFIG_FILE. Please edit it and paste your Tunnel Token.${NC}"
        exit 0
    fi
fi

# Interactive Setup
if [ "$INTERACTIVE" = true ]; then
    echo -e "${YELLOW}Cloudflare Configuration${NC}"
    read -p "Enter Domain Name [devtickets.scotchmcdonald.dev]: " INPUT_DOMAIN
    DOMAIN_NAME="${INPUT_DOMAIN:-devtickets.scotchmcdonald.dev}"
    
    while [ -z "$CF_TUNNEL_TOKEN" ]; do
        echo -e "${YELLOW}Paste your Cloudflare Tunnel Token (starts with ey...):${NC}"
        read -r CF_TUNNEL_TOKEN
    done

    echo -e "${YELLOW}Admin User${NC}"
    read -p "Admin Email [admin@scotchmcdonald.dev]: " INPUT_EMAIL
    ADMIN_EMAIL="${INPUT_EMAIL:-admin@scotchmcdonald.dev}"
    read -p "Admin Password [change_me]: " INPUT_PASS
    ADMIN_PASS="${INPUT_PASS:-change_me}"

    echo -e "${YELLOW}Google OAuth (Optional)${NC}"
    read -p "Google Client ID (Enter to skip): " GOOGLE_CLIENT_ID
    if [ -n "$GOOGLE_CLIENT_ID" ]; then
        read -p "Google Client Secret: " GOOGLE_CLIENT_SECRET
        read -p "Google Admin Emails (comma separated): " GOOGLE_ADMIN_EMAILS
        read -p "Allowed Domains (comma separated, e.g. example.com,gmail.com): " GOOGLE_ALLOWED_DOMAINS
    fi
fi

# ==========================================
# 2. SYSTEM PREP
# ==========================================

if ! command -v brew >/dev/null 2>&1; then
    echo -e "${YELLOW}Homebrew not found. Assuming dependencies are met.${NC}"
fi

REQUIRED_TOOLS="git curl openssl"
for tool in $REQUIRED_TOOLS; do
    if ! command -v $tool > /dev/null 2>&1; then 
        echo -e "${RED}Missing tool: $tool${NC}. Run: brew install $tool"
        exit 1
    fi
done

if ! command -v docker >/dev/null 2>&1; then
    echo -e "${RED}Docker not found! Install OrbStack first.${NC}"
    exit 1
fi

# ==========================================
# 3. DEPLOYMENT
# ==========================================

mkdir -p "$DEFAULT_INSTALL_DIR/nginx"
cd "$DEFAULT_INSTALL_DIR"

# Generate Dockerfile
cat <<EOF > Dockerfile
FROM serversideup/php:8.2-fpm-nginx
USER root
RUN apt-get update && apt-get install -y gnupg && \
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs && \
    curl -sSLf \
        -o /usr/local/bin/install-php-extensions \
        https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions && \
    chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions imap gmp soap intl bcmath gd
USER www-data
EOF

# Generate Nginx Config (Standard Port 8080)
cat <<EOF > nginx/default.conf
server {
    listen 8080 default_server;
    server_name _;
    root /var/www/html/public;
    index index.php index.html;
    client_max_body_size 20M;
    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;
    }
    location ~* ^/storage/attachment/ { expires 1M; access_log off; try_files \$uri \$uri/ /index.php?\$query_string; }
    location ~* ^/(?:css|js)/.*\.(?:css|js)$ { expires 2d; access_log off; add_header Cache-Control "public, must-revalidate"; }
    location ~ /\. { deny all; }
}
EOF

# Generate .env (Docker)
cat <<EOF > .env
DB_ROOT_PASSWORD=${DB_ROOT_PASS:-root}
DB_DATABASE=${DB_NAME:-freescout}
DB_USER=${DB_USER:-freescout}
DB_PASSWORD=${DB_PASS:-freescout}
APP_URL=https://$DOMAIN_NAME
REDIS_HOST=redis
REDIS_PORT=6379
TUNNEL_TOKEN=$CF_TUNNEL_TOKEN
EOF

# Generate docker-compose.yml
cat <<EOF > docker-compose.yml
services:
  app:
    build: .
    image: freescout-app
    restart: unless-stopped
    # We only expose to localhost for debugging. 
    # Public traffic comes via the 'tunnel' service below.
    ports:
      - "127.0.0.1:8080:8080"
    environment:
      - PUID=$(id -u)
      - PGID=$(id -g)
      - PHP_MEMORY_LIMIT=512M
    volumes:
      - ./src:/var/www/html
      - ./nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - db
      - redis
    networks:
      - fs-net

  db:
    image: mysql:8.0
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: \${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: \${DB_DATABASE}
      MYSQL_USER: \${DB_USER}
      MYSQL_PASSWORD: \${DB_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql
    networks:
      - fs-net

  redis:
    image: redis:alpine
    restart: unless-stopped
    networks:
      - fs-net

  queue:
    image: freescout-app
    restart: always
    command: php artisan queue:work --queue=emails,default --sleep=3 --tries=3
    volumes:
      - ./src:/var/www/html
    depends_on:
      - app
    networks:
      - fs-net

  # CLOUDFLARE TUNNEL
  tunnel:
    image: cloudflare/cloudflared:latest
    restart: unless-stopped
    command: tunnel run
    environment:
      - TUNNEL_TOKEN=\${TUNNEL_TOKEN}
    networks:
      - fs-net

networks:
  fs-net:
    driver: bridge

volumes:
  db_data:
EOF

# Generate Update Script
cat <<EOF > update.sh
#!/bin/bash
echo "Updating Freescout..."
cd src
git pull origin $DEFAULT_BRANCH
cd ..
docker compose build app
docker compose up -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan freescout:clear-cache
echo "Done."
EOF
chmod +x update.sh

# Clone Repo
if [ ! -d "src" ]; then
    echo -e "${GREEN}Cloning source...${NC}"
    git clone -b "$DEFAULT_BRANCH" "$DEFAULT_REPO" src
else
    echo "Source folder exists, skipping clone."
fi

# Configure Laravel .env
cp "src/.env.example" "src/.env"

# Set URL to HTTPS (Cloudflare handles SSL)
sed -i '' "s|APP_URL=http://localhost|APP_URL=https://$DOMAIN_NAME|g" "src/.env"
sed -i '' "s/DB_HOST=127.0.0.1/DB_HOST=db/g" "src/.env"
sed -i '' "s/DB_PASSWORD=/DB_PASSWORD=$DB_PASS/g" "src/.env"
sed -i '' "s/CACHE_STORE=database/CACHE_STORE=redis/g" "src/.env"
sed -i '' "s/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/g" "src/.env"
sed -i '' "s/APP_FORCE_HTTPS=false/APP_FORCE_HTTPS=true/g" "src/.env"

echo "" >> "src/.env"
echo "ADMIN_EMAIL=$ADMIN_EMAIL" >> "src/.env"
echo "ADMIN_PASSWORD=\"$ADMIN_PASS\"" >> "src/.env"

if [ -n "$GOOGLE_CLIENT_ID" ]; then
    echo "" >> "src/.env"
    echo "GOOGLE_CLIENT_ID=$GOOGLE_CLIENT_ID" >> "src/.env"
    echo "GOOGLE_CLIENT_SECRET=$GOOGLE_CLIENT_SECRET" >> "src/.env"
    echo "GOOGLE_ADMIN_EMAILS=\"$GOOGLE_ADMIN_EMAILS\"" >> "src/.env"
    echo "GOOGLE_ALLOWED_DOMAINS=\"$GOOGLE_ALLOWED_DOMAINS\"" >> "src/.env"
    echo "GOOGLE_REDIRECT_URI=https://$DOMAIN_NAME/auth/google/callback" >> "src/.env"
fi

# ==========================================
# NEW: FIX PERMISSIONS FOR DOCKER CACHE
# ==========================================
echo "Ensuring storage directories exist..."
# Clear potential cache files from repo
rm -f src/bootstrap/cache/*.php
rm -rf src/storage/framework/cache/*
rm -rf src/storage/framework/views/*
rm -rf src/storage/framework/sessions/*

mkdir -p src/storage/framework/{cache,sessions,views,testing}
mkdir -p src/storage/logs
mkdir -p src/bootstrap/cache
# Set permissions (777 to avoid permission issues in Docker volume mounts)
chmod -R 777 src/storage src/bootstrap/cache

# Trust Cloudflare Proxy Headers
echo "TRUSTED_PROXIES=*" >> "src/.env"

# Launch
echo -e "${GREEN}Starting Containers...${NC}"
docker compose down --remove-orphans || true
docker compose build app
docker compose up -d

echo -e "${GREEN}Waiting for Database...${NC}"
sleep 20

# Install Dependencies
echo "Installing Composer Dependencies..."
# Run as root to avoid permission issues with bind mounts
docker compose exec -T -u root app composer install --no-dev --optimize-autoloader || { echo -e "${RED}Composer install failed!${NC}"; exit 1; }
# Fix permissions for vendor directory
docker compose exec -T -u root app chown -R www-data:www-data /var/www/html/vendor /var/www/html/composer.lock

echo "Installing NPM Dependencies..."
docker compose exec -T -u root app npm install || { echo -e "${RED}NPM install failed!${NC}"; exit 1; }
docker compose exec -T -u root app npm run build

docker compose exec -T app php artisan key:generate

# Install App
echo "Running Install..."
docker compose exec -T app php artisan freescout:install --force --email="$ADMIN_EMAIL" --password="$ADMIN_PASS" --first_name="Admin" --last_name="User"
docker compose exec -T app php artisan db:seed --class=ThemeSeeder --force

echo "Verifying Admin User..."
docker compose exec -T app php artisan tinker --execute="App\Models\User::where('email', '$ADMIN_EMAIL')->update(['email_verified_at' => now()]);"

echo ""
echo -e "${CYAN}DEPLOYMENT COMPLETE${NC}"
echo "1. Go to Cloudflare Zero Trust Dashboard -> Networks -> Tunnels"
echo "2. Click your tunnel -> Configure -> Public Hostname"
echo "3. Add Public Hostname:"
echo "   - Subdomain: devtickets"
echo "   - Domain: scotchmcdonald.dev"
echo "   - Service: http://app:8080"
echo ""
if [ -n "$GOOGLE_CLIENT_ID" ]; then
    echo "4. Google OAuth Configuration:"
    echo "   - Add this Redirect URI to your Google Cloud Console credentials:"
    echo "   - https://$DOMAIN_NAME/auth/google/callback"
    echo ""
fi
echo "Local Access (Emergency): http://localhost:8080"