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
cat <<EOF > Dockerfile
FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \\
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \\
    libgmp-dev libc-client-dev libkrb5-dev nginx \\
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \\
    && docker-php-ext-install imap \\
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd gmp soap intl

RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini \\
    && echo "upload_max_filesize=20M" > /usr/local/etc/php/conf.d/uploads.ini \\
    && echo "post_max_size=20M" > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
EOF

# 4. Generate Nginx Config
echo -e "${GREEN}Generating Nginx Config...${NC}"
cat <<EOF > nginx/default.conf
server {
    listen 80;
    server_name $DOMAIN_NAME;
    root /var/www/html/public;
    index index.php index.html;
    
    client_max_body_size 20M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;
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
    volumes:
      - ./src:/var/www/html
    networks:
      - fs-net

  web:
    image: nginx:alpine
    restart: unless-stopped
    ports:
      - "80:80"
    volumes:
      - ./src:/var/www/html
      - ./nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app
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

  queue:
    image: freescout-app
    restart: always
    command: php artisan queue:work --queue=emails,default --sleep=3 --tries=3 --max-time=3600
    volumes:
      - ./src:/var/www/html
    depends_on:
      - app
      - db
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

# 9. Set Permissions
echo -e "${GREEN}Setting permissions...${NC}"
sudo chown -R 33:33 "$DEFAULT_INSTALL_DIR/src"

# 10. Launch
echo -e "${GREEN}Building and Starting Containers...${NC}"
docker-compose up -d --build

echo ""
echo -e "${CYAN}============================================================${NC}"
echo -e "${CYAN}DEPLOYMENT FINISHED${NC}"
echo -e "${CYAN}============================================================${NC}"
echo "Repo:   $GIT_REPO_URL"
echo "Branch: $GIT_BRANCH"
echo ""
echo "1. Run the installer now with this command:"
echo "   cd $DEFAULT_INSTALL_DIR && docker-compose exec app php artisan freescout:install"
echo ""
echo "2. Use these database details when asked:"
echo "   - Host: db"
echo "   - Name: $DB_NAME"
echo "   - User: $DB_USER"
echo "   - Pass: $DB_PASS"
echo ""
echo "3. To update this branch later:"
echo "   $DEFAULT_INSTALL_DIR/update.sh"
echo "============================================================"