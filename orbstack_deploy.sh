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
# CHANGE: Install in User Home instead of /opt for macOS permission safety
DEFAULT_INSTALL_DIR="$HOME/freescout-docker"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
RED='\033[0;31m'
NC='\033[0m'

clear
echo -e "${CYAN}============================================================${NC}"
echo -e "${CYAN}   FreeScout OrbStack Deployer (macOS Edition)   ${NC}"
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
    echo "No configuration file found."
    read -p "Create configuration template? [y/N] " CREATE_CONFIG
    if [[ "$CREATE_CONFIG" =~ ^[Yy]$ ]]; then
        cat <<EOF > "$CONFIG_FILE"
GIT_REPO_URL="$DEFAULT_REPO"
GIT_BRANCH="$DEFAULT_BRANCH"
DEFAULT_INSTALL_DIR="$DEFAULT_INSTALL_DIR"
DOMAIN_NAME="devtickets.scotchmcdonald.dev" 
DOCKER_SUBNET="172.20.0.0/16"

# Database
DB_ROOT_PASS="change_me"
DB_USER="freescout"
DB_PASS="change_me"
DB_NAME="freescout"

# Admin
ADMIN_EMAIL="admin@scotchmcdonald.dev"
ADMIN_PASS="change_me"

# Porkbun DDNS API (For public access)
PORKBUN_API_KEY=""
PORKBUN_SECRET_KEY=""

# Google OAuth (Optional)
GOOGLE_CLIENT_ID=""
GOOGLE_CLIENT_SECRET=""
GOOGLE_ADMIN_EMAILS=""

# Seeding
SEED_SAMPLE_DATA=false
EOF
        echo -e "${GREEN}Template created at $CONFIG_FILE. Edit it and rerun.${NC}"
        exit 0
    fi
fi

# Interactive Setup
if [ "$INTERACTIVE" = true ]; then
    echo -e "${YELLOW}Network Configuration${NC}"
    read -p "Enter Domain Name [devtickets.scotchmcdonald.dev]: " INPUT_DOMAIN
    DOMAIN_NAME="${INPUT_DOMAIN:-devtickets.scotchmcdonald.dev}"
    
    echo -e "${YELLOW}Porkbun API (Optional - for Dynamic DNS)${NC}"
    read -p "Porkbun API Key (Enter to skip): " PORKBUN_API_KEY
    if [ -n "$PORKBUN_API_KEY" ]; then
        read -p "Porkbun Secret Key: " PORKBUN_SECRET_KEY
    fi
fi

# ==========================================
# 2. SYSTEM PREP (macOS Specific)
# ==========================================

# Check for Homebrew
if ! command -v brew >/dev/null 2>&1; then
    echo -e "${YELLOW}Homebrew not found. Please install it if dependencies are missing.${NC}"
fi

# Check Dependencies
REQUIRED_TOOLS="git curl openssl"
for tool in $REQUIRED_TOOLS; do
    if ! command -v $tool > /dev/null 2>&1; then 
        echo -e "${RED}Missing tool: $tool${NC}"
        echo "Please run: brew install $tool"
        exit 1
    fi
done

# Check Docker (OrbStack)
if ! command -v docker >/dev/null 2>&1; then
    echo -e "${RED}Docker not found! Please install and open OrbStack first.${NC}"
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

# Generate Nginx Config
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
APP_URL=http://$DOMAIN_NAME
REDIS_HOST=redis
REDIS_PORT=6379
EOF

# Generate docker-compose.yml
# CHANGE: Added porkbun-ddns service and mapped port 8080:8080
cat <<EOF > docker-compose.yml
services:
  app:
    build: .
    image: freescout-app
    restart: unless-stopped
    ports:
      - "8080:8080"
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

  # Dynamic DNS Updater (Only runs if keys are present)
  ddns:
    image: mietzen/porkbun-ddns
    restart: unless-stopped
    environment:
      - DOMAIN=scotchmcdonald.dev
      - SUBDOMAINS=devtickets
      - APIKEY=$PORKBUN_API_KEY
      - SECRETAPIKEY=$PORKBUN_SECRET_KEY
    profiles:
      - $(if [ -n "$PORKBUN_API_KEY" ]; then echo "always"; else echo "donotstart"; fi)
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
# (Standard sed replacements for DB/Redis go here - same as your original script but without sudo)
sed -i '' "s|APP_URL=http://localhost|APP_URL=http://$DOMAIN_NAME|g" "src/.env"
sed -i '' "s/DB_HOST=127.0.0.1/DB_HOST=db/g" "src/.env"
sed -i '' "s/DB_PASSWORD=/DB_PASSWORD=$DB_PASS/g" "src/.env"
sed -i '' "s/CACHE_STORE=database/CACHE_STORE=redis/g" "src/.env"
sed -i '' "s/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/g" "src/.env"

echo "" >> "src/.env"
echo "ADMIN_EMAIL=$ADMIN_EMAIL" >> "src/.env"

# Launch
echo -e "${GREEN}Starting Containers...${NC}"
# REMOVED SUDO
docker compose down --remove-orphans || true
docker compose build app
docker compose up -d

echo -e "${GREEN}Waiting for Database...${NC}"
sleep 20

# Install Dependencies
echo "Installing Composer Dependencies..."
docker compose exec -T app composer install --no-dev --optimize-autoloader
docker compose exec -T app npm install
docker compose exec -T app npm run build
docker compose exec -T app php artisan key:generate

# Install App
echo "Running Install..."
docker compose exec -T app php artisan freescout:install --force
docker compose exec -T app php artisan db:seed --class=ThemeSeeder --force

echo ""
echo -e "${CYAN}DEPLOYMENT COMPLETE${NC}"
echo "Local Access: http://localhost:8080"
echo "Public Access: http://$DOMAIN_NAME"