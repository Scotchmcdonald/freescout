#!/bin/bash

# ==========================================
# 1. DEFAULTS & INTERACTIVE SETUP
# ==========================================

DEFAULT_REPO="https://github.com/Scotchmcdonald/freescout.git"
DEFAULT_BRANCH="laravel-11-foundation"
DEFAULT_INSTALL_DIR="/opt/freescout-docker"

GOOGLE_CLIENT_ID=""
GOOGLE_CLIENT_SECRET=""
GOOGLE_ADMIN_EMAILS=""

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
RED='\033[0;31m'
NC='\033[0m'

clear
echo -e "${CYAN}============================================================${NC}"
echo -e "${CYAN}   FreeScout Docker Deployer   ${NC}"
echo -e "${CYAN}============================================================${NC}"

CONFIG_FILE="deploy.conf"
INTERACTIVE=true

if [ -f "$CONFIG_FILE" ]; then
    echo -e "${GREEN}Configuration file '$CONFIG_FILE' found.${NC}"
    read -p "Do you want to use this configuration? [Y/n] " USE_CONFIG
    USE_CONFIG=${USE_CONFIG:-Y}
    case "$USE_CONFIG" in
        [Yy])
            echo "Loading configuration..."
            source "$CONFIG_FILE"
            INTERACTIVE=false
            ;;
    esac
else
    echo "No configuration file found."
    read -p "Do you want to create a configuration template? [y/N] " CREATE_CONFIG
    case "$CREATE_CONFIG" in
        [Yy])
            cat <<EOF > "$CONFIG_FILE"
# ==========================================
# INSTALLATION SETTINGS
# ==========================================
GIT_REPO_URL="$DEFAULT_REPO"
GIT_BRANCH="$DEFAULT_BRANCH"
DEFAULT_INSTALL_DIR="$DEFAULT_INSTALL_DIR"

# ==========================================
# NETWORK SETTINGS
# ==========================================
DOMAIN_NAME=""
DOCKER_SUBNET=""

# ==========================================
# DATABASE SETTINGS
# ==========================================
DB_ROOT_PASS="change_me"
DB_USER="freescout"
DB_PASS="change_me"
DB_NAME="freescout"

# ==========================================
# ADMIN USER
# ==========================================
ADMIN_EMAIL="admin@freescout.local"
ADMIN_PASS="change_me"

# ==========================================
# INTEGRATIONS (OPTIONAL)
# ==========================================
# Google OAuth
GOOGLE_CLIENT_ID=""
GOOGLE_CLIENT_SECRET=""
GOOGLE_ADMIN_EMAILS="" # Comma separated

# ==========================================
# MAILBOX AUTO-PROVISIONING (OPTIONAL)
# ==========================================
MAILBOX_EMAIL=""
MAILBOX_NAME=""
MAILBOX_IMAP_HOST=""
MAILBOX_IMAP_PORT="993"
MAILBOX_IMAP_USER=""
MAILBOX_IMAP_PASS=""
MAILBOX_SMTP_HOST=""
MAILBOX_SMTP_PORT="587"
MAILBOX_SMTP_USER=""
MAILBOX_SMTP_PASS=""

# ==========================================
# SEEDING (OPTIONAL)
# ==========================================
SEED_SAMPLE_DATA=false
EOF
            echo -e "${GREEN}Configuration template created at $CONFIG_FILE.${NC}"
            echo "Please edit the file and run this script again."
            exit 0
            ;;
    esac
fi

# 0. Check for Existing Install & Reuse
REUSE_DB=false
EXISTING_COMPOSE_ENV="$DEFAULT_INSTALL_DIR/.env"

if [ -f "$EXISTING_COMPOSE_ENV" ]; then
    echo -e "${YELLOW}Existing installation found at $DEFAULT_INSTALL_DIR${NC}"
    if [ "$INTERACTIVE" = true ]; then
        read -p "Do you want to reuse the existing database (keep data)? [Y/n] " INPUT_REUSE
        INPUT_REUSE=${INPUT_REUSE:-Y}
    else
        INPUT_REUSE="Y" # Default to reuse in non-interactive if config exists? Or maybe N? Let's assume Y for safety.
    fi

    case "$INPUT_REUSE" in
        [Yy])
            REUSE_DB=true
            echo "Loading existing credentials..."
            # Load credentials from the docker .env file
            if [ -f "$EXISTING_COMPOSE_ENV" ]; then
                # We use grep/cut to avoid sourcing the whole file which might have other junk
                EXISTING_DB_PASS=$(grep "^DB_PASSWORD=" "$EXISTING_COMPOSE_ENV" | cut -d '=' -f2)
                EXISTING_DB_ROOT=$(grep "^DB_ROOT_PASSWORD=" "$EXISTING_COMPOSE_ENV" | cut -d '=' -f2)
                EXISTING_DB_USER=$(grep "^DB_USER=" "$EXISTING_COMPOSE_ENV" | cut -d '=' -f2)
                EXISTING_DB_NAME=$(grep "^DB_DATABASE=" "$EXISTING_COMPOSE_ENV" | cut -d '=' -f2)
                
                if [ -n "$EXISTING_DB_PASS" ]; then DB_PASS=$EXISTING_DB_PASS; fi
                if [ -n "$EXISTING_DB_ROOT" ]; then DB_ROOT_PASS=$EXISTING_DB_ROOT; fi
                if [ -n "$EXISTING_DB_USER" ]; then DB_USER=$EXISTING_DB_USER; fi
                if [ -n "$EXISTING_DB_NAME" ]; then DB_NAME=$EXISTING_DB_NAME; fi
            fi

            # Try to recover Admin credentials from src/.env
            if [ -f "$DEFAULT_INSTALL_DIR/src/.env" ]; then
                EXISTING_EMAIL=$(grep "^ADMIN_EMAIL=" "$DEFAULT_INSTALL_DIR/src/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'")
                EXISTING_PASS=$(grep "^ADMIN_PASSWORD=" "$DEFAULT_INSTALL_DIR/src/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'")
                
                if [ -n "$EXISTING_EMAIL" ]; then ADMIN_EMAIL=$EXISTING_EMAIL; fi
                if [ -n "$EXISTING_PASS" ]; then 
                    ADMIN_PASS=$EXISTING_PASS
                    ADMIN_PASS_PRESERVED=true
                fi
            fi
            ;;
    esac
fi

# Check arguments
if [ "$INTERACTIVE" = true ]; then
    if [ -n "$1" ]; then
        GIT_REPO_URL=$1
        GIT_BRANCH=${2:-$DEFAULT_BRANCH}
    else
        echo "Entering interactive setup..."
        echo ""
        echo -e "Default Repository: ${YELLOW}$DEFAULT_REPO${NC}"
        read -p "Press ENTER to confirm, or paste a new URL: " INPUT_REPO
        GIT_REPO_URL="${INPUT_REPO:-$DEFAULT_REPO}"
        echo ""
        echo -e "Default Branch: ${YELLOW}$DEFAULT_BRANCH${NC}"
        read -p "Press ENTER to confirm, or type a new branch name: " INPUT_BRANCH
        GIT_BRANCH="${INPUT_BRANCH:-$DEFAULT_BRANCH}"
        echo ""

        # 2. Network Configuration
        echo -e "${YELLOW}Network Configuration${NC}"
        while [ -z "$DOMAIN_NAME" ]; do
            read -p "Enter Domain Name: " DOMAIN_NAME
        done
        
        while [ -z "$DOCKER_SUBNET" ]; do
            read -p "Enter Docker Subnet (CIDR, e.g. 192.168.220.0/24): " DOCKER_SUBNET
        done
        echo ""

        # 3. Google OAuth (Optional)
        echo -e "${YELLOW}Google OAuth Configuration (Optional)${NC}"
        echo "Enter your credentials to enable 'Login with Google' immediately."
        read -p "Google Client ID (press Enter to skip): " GOOGLE_CLIENT_ID
        if [ -n "$GOOGLE_CLIENT_ID" ]; then
            read -p "Google Client Secret: " GOOGLE_CLIENT_SECRET
            echo "Enter comma-separated emails for auto-admin access (e.g. 'bob@ex.com,alice@ex.com')"
            read -p "Admin Emails (press Enter to skip): " GOOGLE_ADMIN_EMAILS
        fi
        echo ""

        # 4. Sample Data Seeding
        echo -e "${YELLOW}Sample Data Seeding${NC}"
        if [ "$REUSE_DB" = true ]; then
            echo -e "${RED}WARNING: You are reusing an existing database.${NC}"
            echo -e "${RED}Seeding sample data may cause conflicts or duplicate records.${NC}"
        fi
        read -p "Seed sample data (Mailboxes, Users, Conversations)? [y/N] " INPUT_SEED
        case "$INPUT_SEED" in
            [Yy]) SEED_SAMPLE_DATA=true ;;
            *) SEED_SAMPLE_DATA=false ;;
        esac
        echo ""

        echo "------------------------------------------------------------"
        echo -e "CONFIGURATION SUMMARY:"
        echo -e "  Repo:   ${GREEN}$GIT_REPO_URL${NC}"
        echo -e "  Branch: ${GREEN}$GIT_BRANCH${NC}"
        if [ -n "$GOOGLE_CLIENT_ID" ]; then
            echo -e "  Google: ${GREEN}Configured${NC}"
        else
            echo -e "  Google: ${YELLOW}Skipped${NC}"
        fi
        echo "------------------------------------------------------------"
        echo "Press ENTER to start deployment (or Ctrl+C to cancel)..."
        read CONFIRM
    fi
fi

# ==========================================
# 2. SYSTEM PREP & CONFIGURATION
# ==========================================

# 1. Credentials (Using HEX to avoid special char issues in .env)
# Only set defaults if not provided by config or existing install
if [ -z "$DOMAIN_NAME" ]; then echo -e "${RED}Error: DOMAIN_NAME not set.${NC}"; exit 1; fi
if [ -z "$DOCKER_SUBNET" ]; then echo -e "${RED}Error: DOCKER_SUBNET not set.${NC}"; exit 1; fi

DB_ROOT_PASS="${DB_ROOT_PASS:-$(openssl rand -hex 16)}"
DB_USER="${DB_USER:-freescout}"
DB_PASS="${DB_PASS:-$(openssl rand -hex 16)}"
DB_NAME="${DB_NAME:-freescout}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@freescout.local}"
ADMIN_PASS="${ADMIN_PASS:-$(openssl rand -hex 12)}"

# 2. Check Dependencies
export PATH=$PATH:/usr/bin:/usr/local/bin:/usr/sbin:/sbin
if [ "$(id -u)" -eq 0 ]; then
    if ! command -v sudo > /dev/null 2>&1; then sudo() { "$@"; }; fi
else
    if ! command -v sudo > /dev/null 2>&1; then echo -e "${RED}Error: Requires sudo.${NC}"; exit 1; fi
fi

REQUIRED_TOOLS="git curl openssl"
MISSING_TOOLS=""
for tool in $REQUIRED_TOOLS; do
    if ! command -v $tool > /dev/null 2>&1; then MISSING_TOOLS="$MISSING_TOOLS $tool"; fi
done

if [ -n "$MISSING_TOOLS" ]; then
    echo -e "${YELLOW}Installing tools: $MISSING_TOOLS${NC}"
    sudo apt-get update && sudo apt-get install -y $MISSING_TOOLS
fi

# 3. Check Docker
if ! [ -x "$(command -v docker)" ]; then
    echo -e "${GREEN}Installing Docker...${NC}"
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    rm get-docker.sh
fi

# ==========================================
# 3. DEPLOYMENT LOGIC
# ==========================================
set -e 

# 0. Decommission Existing Install
if [ -d "$DEFAULT_INSTALL_DIR" ] && [ -f "$DEFAULT_INSTALL_DIR/docker-compose.yml" ]; then
    echo -e "${YELLOW}Existing installation found. Stopping containers...${NC}"
    cd "$DEFAULT_INSTALL_DIR"
    
    if [ "$REUSE_DB" = true ]; then
        # Down without -v to keep volumes
        sudo docker compose down || true
    else
        # Down with -v to destroy volumes
        echo -e "${RED}Destroying existing database volume...${NC}"
        sudo docker compose down -v || true
    fi
    
    # Prune networks to prevent subnet exhaustion
    echo "Pruning unused networks..."
    sudo docker network prune -f || true
fi

# 1. Setup Directory
echo -e "${GREEN}Creating directory structure at $DEFAULT_INSTALL_DIR...${NC}"
sudo mkdir -p "$DEFAULT_INSTALL_DIR/nginx"
sudo chown -R $USER:$USER "$DEFAULT_INSTALL_DIR"
cd "$DEFAULT_INSTALL_DIR"

# 2. Generate Dockerfile (Using Extension Installer)
cat <<EOF > Dockerfile
FROM serversideup/php:8.2-fpm-nginx
USER root
RUN curl -sSLf \
        -o /usr/local/bin/install-php-extensions \
        https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions && \
    chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions imap gmp soap intl bcmath
USER www-data
EOF

# 3. Generate Nginx Config
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
    location ~* ^/storage/.*\.((?!(jpg|jpeg|jfif|pjpeg|pjp|apng|bmp|gif|ico|cur|png|tif|tiff|webp|pdf|txt|diff|patch|json|mp3|wav|ogg|wma)).)*$ { add_header Content-disposition "attachment; filename=\$2"; default_type application/octet-stream; }
    location ~ /\. { deny all; }
}
EOF

# 5. Generate .env
cat <<EOF > .env
DB_ROOT_PASSWORD=$DB_ROOT_PASS
DB_DATABASE=$DB_NAME
DB_USER=$DB_USER
DB_PASSWORD=$DB_PASS
APP_URL=http://$DOMAIN_NAME
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
GOOGLE_CLIENT_ID=$GOOGLE_CLIENT_ID
GOOGLE_CLIENT_SECRET=$GOOGLE_CLIENT_SECRET
GOOGLE_REDIRECT_URI=http://$DOMAIN_NAME/auth/google/callback
EOF

# 5. Generate Docker Compose
cat <<EOF > docker-compose.yml
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
    driver: bridge
    ipam:
      config:
        - subnet: $DOCKER_SUBNET
volumes:
  db_data:
EOF

# 6. Generate Update Script
cat <<EOF > update.sh
#!/bin/bash
echo "Updating Freescout ($GIT_BRANCH)..."
cd src
git fetch origin
git checkout $GIT_BRANCH
git pull origin $GIT_BRANCH
cd ..
sudo docker compose build app
sudo docker compose up -d
sudo docker compose exec app php artisan migrate --force
sudo docker compose exec app php artisan optimize:clear
sudo docker compose exec app php artisan freescout:clear-cache
echo "Update Complete."
EOF
chmod +x update.sh

# 7. Clone Repo
if [ -d "$DEFAULT_INSTALL_DIR/src" ]; then
    echo "Source folder already exists. Syncing..."
    cd "$DEFAULT_INSTALL_DIR/src"
    
    # Ensure git can operate in this directory
    git config --global --add safe.directory "$DEFAULT_INSTALL_DIR/src"

    git remote set-url origin "$GIT_REPO_URL"
    git fetch origin
    
    # Checkout branch (create if missing)
    if ! git checkout "$GIT_BRANCH" 2>/dev/null; then
        git checkout -b "$GIT_BRANCH" "origin/$GIT_BRANCH"
    fi

    # Pull latest changes
    if ! git pull origin "$GIT_BRANCH"; then
        echo -e "${RED}Git pull failed! You have local changes or conflicts.${NC}"
        
        if [ "$INTERACTIVE" = true ]; then
            echo ""
            echo -e "${YELLOW}Conflict detected. How do you want to proceed?${NC}"
            echo "1) Discard local changes (git reset --hard)"
            echo "2) Nuke & Re-clone (Delete src and download fresh)"
            echo "3) Exit and fix manually"
            read -p "Select [1-3]: " GIT_OPT
            
            case "$GIT_OPT" in
                1)
                    echo "Resetting to origin/$GIT_BRANCH..."
                    git reset --hard "origin/$GIT_BRANCH"
                    ;;
                2)
                    echo "Nuking source directory..."
                    cd ..
                    sudo rm -rf src
                    echo -e "${GREEN}Cloning branch '$GIT_BRANCH'...${NC}"
                    git clone -b "$GIT_BRANCH" "$GIT_REPO_URL" src
                    cd src
                    ;;
                *)
                    echo "Aborting. Please fix git conflicts in $DEFAULT_INSTALL_DIR/src"
                    exit 1
                    ;;
            esac
        else
            echo "Non-interactive mode: Git pull failed. Exiting."
            exit 1
        fi
    fi
    cd ..
else
    echo -e "${GREEN}Cloning branch '$GIT_BRANCH'...${NC}"
    git clone -b "$GIT_BRANCH" "$GIT_REPO_URL" src
fi

# 8. Configure Laravel
echo -e "${GREEN}Configuring Laravel Environment...${NC}"
cp "$DEFAULT_INSTALL_DIR/src/.env.example" "$DEFAULT_INSTALL_DIR/src/.env"

# Replace Variables
sed -i "s/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/g" "$DEFAULT_INSTALL_DIR/src/.env"
sed -i "s/# DB_HOST=127.0.0.1/DB_HOST=db/g" "$DEFAULT_INSTALL_DIR/src/.env"
sed -i "s/# DB_PORT=3306/DB_PORT=3306/g" "$DEFAULT_INSTALL_DIR/src/.env"
sed -i "s/# DB_DATABASE=laravel/DB_DATABASE=$DB_NAME/g" "$DEFAULT_INSTALL_DIR/src/.env"
sed -i "s/# DB_USERNAME=root/DB_USERNAME=$DB_USER/g" "$DEFAULT_INSTALL_DIR/src/.env"
sed -i "s/# DB_PASSWORD=/DB_PASSWORD=$DB_PASS/g" "$DEFAULT_INSTALL_DIR/src/.env"
sed -i "s|APP_URL=http://localhost|APP_URL=http://$DOMAIN_NAME|g" "$DEFAULT_INSTALL_DIR/src/.env"
sed -i "s/CACHE_STORE=database/CACHE_STORE=redis/g" "$DEFAULT_INSTALL_DIR/src/.env"
sed -i "s/SESSION_DRIVER=database/SESSION_DRIVER=redis/g" "$DEFAULT_INSTALL_DIR/src/.env"
sed -i "s/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/g" "$DEFAULT_INSTALL_DIR/src/.env"

# Append Admin details
echo "" >> "$DEFAULT_INSTALL_DIR/src/.env"
echo "ADMIN_EMAIL=$ADMIN_EMAIL" >> "$DEFAULT_INSTALL_DIR/src/.env"
echo "ADMIN_PASSWORD=$ADMIN_PASS" >> "$DEFAULT_INSTALL_DIR/src/.env"

if [ -n "$GOOGLE_CLIENT_ID" ]; then
    echo "" >> "$DEFAULT_INSTALL_DIR/src/.env"
    echo "GOOGLE_CLIENT_ID=$GOOGLE_CLIENT_ID" >> "$DEFAULT_INSTALL_DIR/src/.env"
    echo "GOOGLE_CLIENT_SECRET=$GOOGLE_CLIENT_SECRET" >> "$DEFAULT_INSTALL_DIR/src/.env"
    echo "GOOGLE_REDIRECT_URI=http://$DOMAIN_NAME/auth/google/callback" >> "$DEFAULT_INSTALL_DIR/src/.env"
    if [ -n "$GOOGLE_ADMIN_EMAILS" ]; then
        echo "GOOGLE_ADMIN_EMAILS=\"$GOOGLE_ADMIN_EMAILS\"" >> "$DEFAULT_INSTALL_DIR/src/.env"
    fi
fi

# 9. Create Storage Structure & Permissions
echo -e "${GREEN}Creating Storage Folders...${NC}"
mkdir -p src/storage/framework/{cache,sessions,views}
mkdir -p src/storage/logs
mkdir -p src/storage/app/public
mkdir -p src/bootstrap/cache
mkdir -p src/Modules
mkdir -p src/public/modules

echo -e "${GREEN}Setting Permissions (User 33)...${NC}"
sudo chown -R 33:33 src

# 10. Launch
echo -e "${GREEN}Starting Containers...${NC}"
sudo docker compose down --remove-orphans || true
sudo docker compose build app
sudo docker compose up -d

# 11. Finalize
echo -e "${GREEN}Waiting for DB (25s)...${NC}"
sleep 25

# Install dependencies (include dev if seeding is needed)
if [ "$SEED_SAMPLE_DATA" = true ]; then
    echo "Installing dependencies (including dev for seeding)..."
    sudo docker compose exec -T app composer install --optimize-autoloader
else
    echo "Installing dependencies..."
    sudo docker compose exec -T app composer install --no-dev --optimize-autoloader
fi

echo "Generating Key..."
sudo docker compose exec -T app php artisan key:generate

if [ "$REUSE_DB" = true ]; then
    echo "Existing database detected. Running migrations..."
    sudo docker compose exec -T app php artisan migrate --force
else
    echo "Installing FreeScout..."
    sudo docker compose exec -T app php artisan freescout:install --force
fi

echo "Seeding Themes..."
sudo docker compose exec -T app php artisan db:seed --class=ThemeSeeder --force

if [ "$SEED_SAMPLE_DATA" = true ]; then
    echo "Seeding Sample Data (Users, Mailboxes, Conversations)..."
    sudo docker compose exec -T app php artisan db:seed --class=DatabaseSeeder --force
    
    echo "Cleaning up dev dependencies..."
    sudo docker compose exec -T app composer install --no-dev --optimize-autoloader
fi

echo "Verifying Admin User..."
sudo docker compose exec -T app php artisan tinker --execute="App\Models\User::where('email', '$ADMIN_EMAIL')->update(['email_verified_at' => now()]);"

# Provision Mailbox if configured
if [ -n "$MAILBOX_EMAIL" ] && [ -n "$MAILBOX_NAME" ]; then
    echo "Provisioning Mailbox: $MAILBOX_EMAIL..."
    sudo docker compose exec -T app php artisan tinker --execute="
        \$mailbox = App\Models\Mailbox::firstOrCreate(
            ['email' => '$MAILBOX_EMAIL'],
            [
                'name' => '$MAILBOX_NAME',
                'is_default' => true,
                'status' => 1,
                'in_server' => '$MAILBOX_IMAP_HOST',
                'in_port' => '$MAILBOX_IMAP_PORT',
                'in_username' => '$MAILBOX_IMAP_USER',
                'in_password' => '$MAILBOX_IMAP_PASS',
                'in_protocol' => 1, // IMAP
                'in_encryption' => 2, // SSL/TLS
                'out_server' => '$MAILBOX_SMTP_HOST',
                'out_port' => '$MAILBOX_SMTP_PORT',
                'out_username' => '$MAILBOX_SMTP_USER',
                'out_password' => '$MAILBOX_SMTP_PASS',
                'out_method' => 1, // SMTP
                'out_encryption' => 2, // TLS
            ]
        );
        echo 'Mailbox created: ' . \$mailbox->name . PHP_EOL;
    "
fi

# Fix git safe directory for updates
cd "$DEFAULT_INSTALL_DIR"
sudo git config --global --add safe.directory "$DEFAULT_INSTALL_DIR/src"

echo ""
echo -e "${CYAN}DEPLOYMENT FINISHED${NC}"
echo "URL: http://$DOMAIN_NAME"
echo "Email: $ADMIN_EMAIL"

if [ "$REUSE_DB" = true ] && [ "$ADMIN_PASS_PRESERVED" != "true" ]; then
    echo "Pass:  (Existing password unchanged)"
else
    echo "Pass:  $ADMIN_PASS"
fi

echo "To update: cd $DEFAULT_INSTALL_DIR && sudo sh update.sh"