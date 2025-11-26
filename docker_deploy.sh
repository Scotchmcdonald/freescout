#!/bin/bash

# ==========================================
# 1. DEFAULTS & INTERACTIVE SETUP
# ==========================================

# Define the defaults here
DEFAULT_REPO="https://github.com/Scotchmcdonald/freescout.git"
DEFAULT_BRANCH="laravel-11-foundation"
DEFAULT_INSTALL_DIR="/opt/freescout-docker"

# Colors for nicer output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

clear

echo -e "${CYAN}============================================================${NC}"
echo -e "${CYAN}   FreeScout Docker Deployer (Fork-Ready)   ${NC}"
echo -e "${CYAN}============================================================${NC}"

# Check if arguments are provided
if [ -n "$1" ]; then
    # --- NON-INTERACTIVE MODE ---
    echo "Arguments detected. Running in automatic mode."
    GIT_REPO_URL=$1
    GIT_BRANCH=${2:-$DEFAULT_BRANCH}
else
    # --- INTERACTIVE MODE ---
    echo "No arguments provided. Entering interactive setup."
    echo ""

    # 1. Confirm Repository
    echo -e "Default Repository: ${YELLOW}$DEFAULT_REPO${NC}"
    read -p "Press ENTER to confirm, or paste a new URL: " INPUT_REPO
    GIT_REPO_URL="${INPUT_REPO:-$DEFAULT_REPO}"
    echo ""

    # 2. Confirm Branch
    echo -e "Default Branch: ${YELLOW}$DEFAULT_BRANCH${NC}"
    read -p "Press ENTER to confirm, or type a new branch name: " INPUT_BRANCH
    GIT_BRANCH="${INPUT_BRANCH:-$DEFAULT_BRANCH}"
    echo ""

    # 3. Final Confirmation
    echo "------------------------------------------------------------"
    echo -e "CONFIGURATION SUMMARY:"
    echo -e "  Repo:   ${GREEN}$GIT_REPO_URL${NC}"
    echo -e "  Branch: ${GREEN}$GIT_BRANCH${NC}"
    echo "------------------------------------------------------------"
    
    read -p "Press ENTER to start deployment (or Ctrl+C to cancel)..."
fi

# ==========================================
# 2. CONFIGURATION PARAMETERS
# ==========================================

DOMAIN_NAME="freescout.local"

# Database Credentials (Auto-generated)
DB_ROOT_PASS=$(openssl rand -base64 12)
DB_USER="freescout"
DB_PASS=$(openssl rand -base64 12)
DB_NAME="freescout"

# Admin Credentials (Auto-generated)
ADMIN_EMAIL="admin@$DOMAIN_NAME"
ADMIN_PASS=$(openssl rand -base64 12)

# ==========================================
# 3. DEPLOYMENT LOGIC
# ==========================================

set -e # Exit on error

# 1. Check for Docker
if ! [ -x "$(command -v docker)" ]; then
  echo -e "${GREEN}Installing Docker...${NC}"
  sudo apt-get update
  sudo apt-get install -y ca-certificates curl gnupg lsb-release
  sudo mkdir -p /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
  echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
  sudo apt-get update
  sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin docker-compose
fi

# 2. Create Directory Structure
echo -e "${GREEN}Creating directory structure at $DEFAULT_INSTALL_DIR...${NC}"
sudo mkdir -p "$DEFAULT_INSTALL_DIR/nginx"
sudo chown -R $USER:$USER "$DEFAULT_INSTALL_DIR"
cd "$DEFAULT_INSTALL_DIR"

# 3. Generate Dockerfile
echo -e "${GREEN}Generating Dockerfile...${NC}"
# Using serversideup/php:8.2-fpm-nginx as recommended base image
cat <<EOF > Dockerfile
FROM serversideup/php:8.2-fpm-nginx

# Switch to root to install extensions
USER root

# Install dependencies for FreeScout (IMAP, GMP, SOAP, Intl)
RUN apt-get update && apt-get install -y \\
    libc-client-dev libkrb5-dev libgmp-dev libxml2-dev \\
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \\
    && docker-php-ext-install imap gmp soap intl bcmath \\
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Switch back to www-data
USER www-data
EOF

# 4. Generate Nginx Config
# We override the default config to ensure FreeScout specific rules are applied
echo -e "${GREEN}Generating Nginx Config...${NC}"
cat <<EOF > nginx/default.conf
server {
    listen 8080 default_server;
    server_name _;
    root /var/www/html/public;
    index index.php index.html;
    
    client_max_body_size 20M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass 127.0.0.1:9000; # In this image, PHP-FPM is on localhost
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;
    }

    # FreeScout specific security rules
    location ~* ^/storage/attachment/ {
        expires 1M;
        access_log off;
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~* ^/(?:css|js)/.*\.(?:css|js)$ {
        expires 2d;
        access_log off;
        add_header Cache-Control "public, must-revalidate";
    }

    location ~* ^/storage/.*\.((?!(jpg|jpeg|jfif|pjpeg|pjp|apng|bmp|gif|ico|cur|png|tif|tiff|webp|pdf|txt|diff|patch|json|mp3|wav|ogg|wma)).)*$ {
        add_header Content-disposition "attachment; filename=\$2";
        default_type application/octet-stream;
    }

    location ~ /\. {
        deny all;
    }
}
EOF

# 5. Generate .env file
echo -e "${GREEN}Generating .env file...${NC}"
cat <<EOF > .env
DB_ROOT_PASSWORD=$DB_ROOT_PASS
DB_DATABASE=$DB_NAME
DB_USER=$DB_USER
DB_PASSWORD=$DB_PASS
APP_URL=http://$DOMAIN_NAME
# Redis Configuration
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
EOF

# 6. Generate Docker Compose
echo -e "${GREEN}Generating Docker Compose file...${NC}"
cat <<EOF > docker-compose.yml
version: '3.8'

services:
  app:
    build: .
    image: freescout-app
    restart: unless-stopped
    ports:
      - "80:8080"
    environment:
      - PUID=33
      - PGID=33
      - PHP_MEMORY_LIMIT=512M
      - PHP_POST_MAX_SIZE=20M
      - PHP_UPLOAD_MAX_FILESIZE=20M
    volumes:
      - ./src:/var/www/html
      - ./nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - db
      - redis
    networks:
      - fs-net
    # Uncomment the following line if you need mDNS/Avahi discovery
    # network_mode: host

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
    command: php artisan queue:work --queue=emails,default --sleep=3 --tries=3 --max-time=3600
    environment:
      - PHP_MEMORY_LIMIT=512M
    volumes:
      - ./src:/var/www/html
    depends_on:
      - app
      - db
      - redis
    networks:
      - fs-net

  cron:
    image: freescout-app
    restart: unless-stopped
    command: /bin/sh -c "while true; do php artisan schedule:run; sleep 60; done"
    volumes:
      - ./src:/var/www/html
    depends_on:
      - app
      - db
      - redis
    networks:
      - fs-net

networks:
  fs-net:

volumes:
  db_data:
EOF

# 7. Generate Update Script Helper (Branch Aware)
echo -e "${GREEN}Generating 'update.sh' helper script...${NC}"
cat <<EOF > update.sh
#!/bin/bash
echo "Updating Freescout ($GIT_BRANCH)..."
cd src
git fetch origin
git checkout $GIT_BRANCH
git pull origin $GIT_BRANCH
cd ..
docker-compose build
docker-compose down
docker-compose up -d
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan optimize:clear
docker-compose exec app php artisan freescout:clear-cache
echo "Update Complete."
EOF
chmod +x update.sh

# 8. Clone Repository (Branch Aware)
if [ -d "$DEFAULT_INSTALL_DIR/src" ]; then
    echo "Source folder already exists. Checking branch settings..."
    cd "$DEFAULT_INSTALL_DIR/src"
    # Ensure origin is correct in case it changed
    git remote set-url origin "$GIT_REPO_URL"
    git fetch origin
    git checkout "$GIT_BRANCH"
    cd ..
else
    echo -e "${GREEN}Cloning branch '$GIT_BRANCH' from $GIT_REPO_URL...${NC}"
    # -b flag ensures we get the specific branch
    git clone -b "$GIT_BRANCH" "$GIT_REPO_URL" src
fi

# 9. Configure Application
echo -e "${GREEN}Configuring Application...${NC}"

# Create .env in src if it doesn't exist
if [ ! -f "$DEFAULT_INSTALL_DIR/src/.env" ]; then
    cp "$DEFAULT_INSTALL_DIR/src/.env.example" "$DEFAULT_INSTALL_DIR/src/.env"
    
    # Update .env with DB details
    # We use sed to replace the placeholder values
    sed -i "s/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/g" "$DEFAULT_INSTALL_DIR/src/.env"
    sed -i "s/# DB_HOST=127.0.0.1/DB_HOST=db/g" "$DEFAULT_INSTALL_DIR/src/.env"
    sed -i "s/# DB_PORT=3306/DB_PORT=3306/g" "$DEFAULT_INSTALL_DIR/src/.env"
    sed -i "s/# DB_DATABASE=laravel/DB_DATABASE=$DB_NAME/g" "$DEFAULT_INSTALL_DIR/src/.env"
    sed -i "s/# DB_USERNAME=root/DB_USERNAME=$DB_USER/g" "$DEFAULT_INSTALL_DIR/src/.env"
    sed -i "s/# DB_PASSWORD=/DB_PASSWORD=$DB_PASS/g" "$DEFAULT_INSTALL_DIR/src/.env"
    sed -i "s|APP_URL=http://localhost|APP_URL=http://$DOMAIN_NAME|g" "$DEFAULT_INSTALL_DIR/src/.env"

    # Configure Redis for Cache and Session
    sed -i "s/CACHE_STORE=database/CACHE_STORE=redis/g" "$DEFAULT_INSTALL_DIR/src/.env"
    sed -i "s/SESSION_DRIVER=database/SESSION_DRIVER=redis/g" "$DEFAULT_INSTALL_DIR/src/.env"
    sed -i "s/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/g" "$DEFAULT_INSTALL_DIR/src/.env"

    # Add Admin details to .env (for freescout:install)
    echo "" >> "$DEFAULT_INSTALL_DIR/src/.env"
    echo "ADMIN_EMAIL=$ADMIN_EMAIL" >> "$DEFAULT_INSTALL_DIR/src/.env"
    echo "ADMIN_PASSWORD=$ADMIN_PASS" >> "$DEFAULT_INSTALL_DIR/src/.env"
fi

# 10. Set Permissions
echo -e "${GREEN}Setting permissions...${NC}"
# serversideup image uses www-data (33)
sudo chown -R 33:33 "$DEFAULT_INSTALL_DIR/src"

# 11. Launch
echo -e "${GREEN}Building and Starting Containers...${NC}"
docker-compose up -d --build

# 12. Post-Launch Setup
echo -e "${GREEN}Running Post-Launch Setup...${NC}"
echo "Waiting for database to initialize (20s)..."
sleep 20

echo "Installing dependencies..."
docker-compose exec -T app composer install --no-dev --optimize-autoloader

echo "Generating Application Key..."
docker-compose exec -T app php artisan key:generate

echo "Running FreeScout Installation..."
docker-compose exec -T app php artisan freescout:install --force

echo ""
echo -e "${CYAN}============================================================${NC}"
echo -e "${CYAN}DEPLOYMENT FINISHED${NC}"
echo -e "${CYAN}============================================================${NC}"
echo "Repo:   $GIT_REPO_URL"
echo "Branch: $GIT_BRANCH"
echo ""
echo "Application URL: http://$DOMAIN_NAME"
echo ""
echo "Admin Credentials:"
echo "   - Email: $ADMIN_EMAIL"
echo "   - Pass:  $ADMIN_PASS"
echo ""
echo "Database Credentials:"
echo "   - Host: db"
echo "   - Name: $DB_NAME"
echo "   - User: $DB_USER"
echo "   - Pass: $DB_PASS"
echo ""
echo "To update this branch later:"
echo "   $DEFAULT_INSTALL_DIR/update.sh"
echo "============================================================"