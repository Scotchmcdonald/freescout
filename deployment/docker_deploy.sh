#!/usr/bin/env bash

#===============================================================================
# FreeScout Docker Deployer (Ubuntu/Linux)
# 
# Enterprise-grade deployment script with:
# - Bash strict mode (set -euo pipefail)
# - Trap handlers for cleanup
# - Progress indicators
# - Pre-flight validation
# - Docker BuildKit optimization
# - Comprehensive error handling
#===============================================================================

set -euo pipefail
IFS=$'\n\t'

#===============================================================================
# GLOBALS & CONFIGURATION
#===============================================================================

readonly SCRIPT_VERSION="2.0.0"
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly DEFAULT_REPO="https://github.com/Scotchmcdonald/freescout.git"
readonly DEFAULT_BRANCH="laravel-11-foundation"
readonly DEFAULT_INSTALL_DIR="/opt/freescout-docker"
readonly CONFIG_FILE="${SCRIPT_DIR}/deploy.conf"

# Color codes
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly CYAN='\033[0;36m'
readonly BLUE='\033[0;34m'
readonly MAGENTA='\033[0;35m'
readonly NC='\033[0m' # No Color

# State variables
INTERACTIVE=true
REUSE_DB=true  # Optimistic default - decommission_existing will handle gracefully if no DB exists
ADMIN_PASS_PRESERVED=false
CLEANUP_NEEDED=false

#===============================================================================
# UTILITY FUNCTIONS
#===============================================================================

log_info() {
    echo -e "${CYAN}ℹ ${NC} $*"
}

log_success() {
    echo -e "${GREEN}✓${NC} $*"
}

log_warning() {
    echo -e "${YELLOW}⚠${NC} $*"
}

log_error() {
    echo -e "${RED}✗${NC} $*" >&2
}

log_step() {
    echo ""
    echo -e "${MAGENTA}▶${NC} ${BLUE}$*${NC}"
}

progress_bar() {
    local duration=$1
    local message=$2
    local progress=0
    local bar_length=50
    
    while [ $progress -le $duration ]; do
        local filled=$((progress * bar_length / duration))
        local empty=$((bar_length - filled))
        printf "\r${CYAN}%s${NC} [" "$message"
        printf "%${filled}s" | tr ' ' '='
        printf "%${empty}s" | tr ' ' ' '
        printf "] %3d%%" $((progress * 100 / duration))
        sleep 1
        ((progress++))
    done
    printf "\n"
}

cleanup() {
    local exit_code=$?
    if [ "$CLEANUP_NEEDED" = true ]; then
        log_warning "Cleaning up after error..."
        # Add cleanup logic if needed
    fi
    
    if [ $exit_code -ne 0 ]; then
        log_error "Script failed with exit code $exit_code"
    fi
    
    exit $exit_code
}

trap cleanup EXIT INT TERM

command_exists() {
    command -v "$1" >/dev/null 2>&1
}

validate_required_var() {
    local var_name=$1
    local var_value=${2:-}
    
    if [ -z "$var_value" ]; then
        log_error "Required variable '$var_name' is not set"
        exit 1
    fi
}

#===============================================================================
# PRE-FLIGHT CHECKS
#===============================================================================

preflight_checks() {
    log_step "Running Pre-Flight Checks"
    
    # Check if running as root or with sudo access
    if [ "$(id -u)" -eq 0 ]; then
        if ! command_exists sudo; then
            sudo() { "$@"; }
        fi
    else
        if ! command_exists sudo; then
            log_error "This script requires sudo access"
            exit 1
        fi
    fi
    
    # Check for required tools
    local required_tools=("git" "curl" "openssl")
    local missing_tools=()
    
    for tool in "${required_tools[@]}"; do
        if ! command_exists "$tool"; then
            missing_tools+=("$tool")
        fi
    done
    
    if [ ${#missing_tools[@]} -gt 0 ]; then
        log_warning "Installing missing tools: ${missing_tools[*]}"
        sudo apt-get update -qq
        sudo apt-get install -y -qq "${missing_tools[@]}"
    fi
    
    # Check Docker
    if ! command_exists docker; then
        log_warning "Docker not found. Installing..."
        curl -fsSL https://get.docker.com -o /tmp/get-docker.sh
        sudo sh /tmp/get-docker.sh
        rm /tmp/get-docker.sh
    fi
    
    # Verify Docker is running
    if ! sudo docker info >/dev/null 2>&1; then
        log_error "Docker is installed but not running"
        exit 1
    fi
    
    # Enable BuildKit for faster builds
    export DOCKER_BUILDKIT=1
    export COMPOSE_DOCKER_CLI_BUILD=1
    
    log_success "Pre-flight checks passed"
}

#===============================================================================
# CONFIGURATION MANAGEMENT
#===============================================================================

show_banner() {
    clear
    echo -e "${CYAN}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║                                                            ║${NC}"
    echo -e "${CYAN}║          FreeScout Docker Deployer v${SCRIPT_VERSION}              ║${NC}"
    echo -e "${CYAN}║                                                            ║${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
}

load_or_create_config() {
    if [ -f "$CONFIG_FILE" ]; then
        # Fix ownership if needed
        if [ -n "${SUDO_USER:-}" ] && [ "$(stat -c '%U' "$CONFIG_FILE" 2>/dev/null)" = "root" ]; then
            chown "$SUDO_USER:$(id -g "$SUDO_USER")" "$CONFIG_FILE"
        fi
        
        log_success "Configuration file found: $CONFIG_FILE"
        
        if [ -t 0 ]; then  # Check if stdin is a terminal
            read -rp "Use this configuration? [Y/n] " use_config
            use_config=${use_config:-Y}
            
            if [[ "$use_config" =~ ^[Yy]$ ]]; then
                log_info "Loading configuration..."
                # shellcheck disable=SC1090
                source "$CONFIG_FILE"
                INTERACTIVE=false
                return
            fi
        fi
    else
        log_info "No configuration file found"
        
        if [ -t 0 ]; then
            read -rp "Create configuration template? [y/N] " create_config
            
            if [[ "$create_config" =~ ^[Yy]$ ]]; then
                create_config_template
                log_success "Configuration template created at $CONFIG_FILE"
                log_info "Please edit the file and run this script again"
                exit 0
            fi
        fi
    fi
}

create_config_template() {
    cat > "$CONFIG_FILE" <<EOF
#===============================================================================
# FreeScout Deployment Configuration
#===============================================================================

# Installation Settings
GIT_REPO_URL="$DEFAULT_REPO"
GIT_BRANCH="$DEFAULT_BRANCH"
DEFAULT_INSTALL_DIR="$DEFAULT_INSTALL_DIR"

# Network Settings
DOMAIN_NAME="tickets.example.com"
DOCKER_SUBNET="192.168.220.0/24"

# Database Settings
DB_ROOT_PASS="$(openssl rand -hex 16)"
DB_USER="freescout"
DB_PASS="$(openssl rand -hex 16)"
DB_NAME="freescout"

# Admin User
ADMIN_EMAIL="admin@example.com"
ADMIN_PASS="$(openssl rand -hex 12)"

# Google OAuth (Optional)
GOOGLE_CLIENT_ID=""
GOOGLE_CLIENT_SECRET=""
GOOGLE_ADMIN_EMAILS=""
GOOGLE_ALLOWED_DOMAINS=""

# Mailbox Auto-Provisioning (Optional)
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

# Sample Data Seeding (Optional)
SEED_SAMPLE_DATA=false
EOF
    
    if [ -n "${SUDO_USER:-}" ]; then
        chown "$SUDO_USER:$(id -g "$SUDO_USER")" "$CONFIG_FILE"
    fi
}

interactive_setup() {
    log_step "Interactive Setup"
    
    # Repository configuration
    echo -e "Default Repository: ${YELLOW}$DEFAULT_REPO${NC}"
    read -rp "Press ENTER to confirm, or paste a new URL: " input_repo
    GIT_REPO_URL="${input_repo:-$DEFAULT_REPO}"
    
    echo -e "Default Branch: ${YELLOW}$DEFAULT_BRANCH${NC}"
    read -rp "Press ENTER to confirm, or type a new branch: " input_branch
    GIT_BRANCH="${input_branch:-$DEFAULT_BRANCH}"
    echo ""
    
    # Network configuration
    log_info "Network Configuration"
    while [ -z "${DOMAIN_NAME:-}" ]; do
        read -rp "Domain Name: " DOMAIN_NAME
    done
    
    while [ -z "${DOCKER_SUBNET:-}" ]; do
        read -rp "Docker Subnet (CIDR, e.g. 192.168.220.0/24): " DOCKER_SUBNET
    done
    echo ""
    
    # Google OAuth (optional)
    log_info "Google OAuth (Optional)"
    read -rp "Google Client ID (Enter to skip): " GOOGLE_CLIENT_ID
    if [ -n "$GOOGLE_CLIENT_ID" ]; then
        read -rp "Google Client Secret: " GOOGLE_CLIENT_SECRET
        read -rp "Google Admin Emails (comma separated): " GOOGLE_ADMIN_EMAILS
        read -rp "Allowed Domains (comma separated): " GOOGLE_ALLOWED_DOMAINS
    fi
    echo ""
    
    # Sample data seeding
    log_info "Sample Data Seeding"
    if [ "$REUSE_DB" = true ]; then
        log_warning "WARNING: Reusing existing database"
        log_warning "Seeding may cause conflicts or duplicates"
    fi
    read -rp "Seed sample data (Mailboxes, Users, Conversations)? [y/N] " input_seed
    if [[ "$input_seed" =~ ^[Yy]$ ]]; then
        SEED_SAMPLE_DATA=true
    else
        SEED_SAMPLE_DATA=false
    fi
    echo ""
    
    # Configuration summary
    echo "────────────────────────────────────────────────────────────"
    echo -e "CONFIGURATION SUMMARY:"
    echo -e "  Repository: ${GREEN}$GIT_REPO_URL${NC}"
    echo -e "  Branch:     ${GREEN}$GIT_BRANCH${NC}"
    echo -e "  Domain:     ${GREEN}$DOMAIN_NAME${NC}"
    if [ -n "$GOOGLE_CLIENT_ID" ]; then
        echo -e "  Google:     ${GREEN}Configured${NC}"
    else
        echo -e "  Google:     ${YELLOW}Skipped${NC}"
    fi
    echo "────────────────────────────────────────────────────────────"
    echo ""
    read -rp "Press ENTER to start deployment (or Ctrl+C to cancel)..."
}

check_existing_installation() {
    local existing_env="$DEFAULT_INSTALL_DIR/.env"
    
    if [ -f "$existing_env" ]; then
        log_warning "Existing installation found at $DEFAULT_INSTALL_DIR"
        
        if [ -t 0 ]; then
            echo ""
            echo "1) Reuse existing database (Keep data)"
            echo "2) Overwrite database (DESTROY ALL DATA)"
            read -rp "Select [1-2]: " reuse_opt
            
            case "$reuse_opt" in
                2)
                    REUSE_DB=false
                    log_error "WARNING: Existing database will be destroyed!"
                    ;;
                *)
                    REUSE_DB=true
                    ;;
            esac
        else
            # Non-interactive: default to safe option
            REUSE_DB=true
        fi
        
        if [ "$REUSE_DB" = true ]; then
            log_info "Loading existing credentials..."
            load_existing_credentials "$existing_env"
        fi
    else
        # Fresh installation - ensure REUSE_DB is false
        REUSE_DB=false
    fi
}

load_existing_credentials() {
    local env_file=$1
    
    # Load Docker .env credentials
    if [ -f "$env_file" ]; then
        DB_PASS=$(grep "^DB_PASSWORD=" "$env_file" | cut -d '=' -f2 || echo "")
        DB_ROOT_PASS=$(grep "^DB_ROOT_PASSWORD=" "$env_file" | cut -d '=' -f2 || echo "")
        DB_USER=$(grep "^DB_USER=" "$env_file" | cut -d '=' -f2 || echo "")
        DB_NAME=$(grep "^DB_DATABASE=" "$env_file" | cut -d '=' -f2 || echo "")
    fi
    
    # Load Laravel .env credentials
    local laravel_env="$DEFAULT_INSTALL_DIR/src/.env"
    if [ -f "$laravel_env" ]; then
        local existing_email existing_pass
        existing_email=$(grep "^ADMIN_EMAIL=" "$laravel_env" | cut -d '=' -f2 | tr -d '"' | tr -d "'" || echo "")
        existing_pass=$(grep "^ADMIN_PASSWORD=" "$laravel_env" | cut -d '=' -f2 | tr -d '"' | tr -d "'" || echo "")
        
        if [ -n "$existing_email" ]; then ADMIN_EMAIL=$existing_email; fi
        if [ -n "$existing_pass" ]; then
            ADMIN_PASS=$existing_pass
            ADMIN_PASS_PRESERVED=true
        fi
    fi
}

#===============================================================================
# DEPLOYMENT FUNCTIONS
#===============================================================================

decommission_existing() {
    if [ -d "$DEFAULT_INSTALL_DIR" ] && [ -f "$DEFAULT_INSTALL_DIR/docker-compose.yml" ]; then
        log_step "Decommissioning Existing Installation"
        
        cd "$DEFAULT_INSTALL_DIR"
        
        # Always prompt for what to do with existing deployment
        log_warning "Existing deployment detected!"
        echo ""
        echo -e "${YELLOW}What would you like to do?${NC}"
        echo "  1) Reuse existing data (keep database and volumes)"
        echo "  2) Nuke everything (fresh install, all data lost)"
        echo "  3) Cancel deployment"
        echo ""
        read -p "Enter choice [1-3]: " choice
        
        case $choice in
            1)
                REUSE_DB=true
                log_info "Reusing existing data - stopping containers only..."
                sudo docker compose down 2>/dev/null || true
                ;;
            2)
                log_warning "Nuking everything - all data will be lost!"
                read -p "Type 'yes' to confirm: " confirm
                if [ "$confirm" = "yes" ]; then
                    REUSE_DB=false
                    log_info "Stopping and removing containers and volumes..."
                    sudo docker compose down -v 2>/dev/null || true
                    log_info "Removing source code directory..."
                    sudo rm -rf src
                    log_success "Everything nuked"
                else
                    log_error "Nuke cancelled"
                    exit 1
                fi
                ;;
            3)
                log_info "Deployment cancelled by user"
                exit 0
                ;;
            *)
                log_error "Invalid choice"
                exit 1
                ;;
        esac
        
        log_info "Pruning unused networks..."
        sudo docker network prune -f >/dev/null 2>&1 || true
        
        log_success "Decommissioning complete"
    else
        # No existing installation - treat as fresh install regardless of REUSE_DB
        if [ "$REUSE_DB" = true ]; then
            log_info "No existing installation found - proceeding with fresh install"
            REUSE_DB=false
        fi
    fi
}

setup_directories() {
    log_step "Setting Up Directory Structure"
    
    sudo mkdir -p "$DEFAULT_INSTALL_DIR/nginx"
    sudo chown -R "$USER:$USER" "$DEFAULT_INSTALL_DIR"
    cd "$DEFAULT_INSTALL_DIR"
    
    log_success "Directories created"
}

generate_dockerfile() {
    log_step "Generating Dockerfile"
    
    cat > Dockerfile <<'EOF'
FROM serversideup/php:8.2-fpm-nginx

USER root

# Install system dependencies and Node.js 24.x LTS
RUN apt-get update && apt-get install -y gnupg docker.io docker-compose-plugin && \
    curl -fsSL https://deb.nodesource.com/setup_24.x | bash - && \
    apt-get install -y nodejs && \
    curl -sSLf \
        -o /usr/local/bin/install-php-extensions \
        https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions && \
    chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions imap gmp soap intl bcmath gd && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Configure Docker socket access for www-data user
# This enables the sibling container architecture for EmailMigration lab testing
RUN groupadd -f docker || true && \
    usermod -aG docker www-data || true

USER www-data
EOF
    
    log_success "Dockerfile generated"
}

generate_nginx_config() {
    log_step "Generating Nginx Configuration (HTTPS + WebSocket)"
    
    cat > nginx/default.conf <<'EOF'
upstream reverb_backend {
    server reverb:8080;
}

server {
    listen 8080 ssl http2 default_server;
    server_name _;
    root /var/www/html/public;
    index index.php index.html;
    client_max_body_size 20M;
    
    # SSL Configuration
    ssl_certificate /etc/nginx/ssl/cert.pem;
    ssl_certificate_key /etc/nginx/ssl/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    
    # Proxy WebSocket requests to Reverb container
    location /app/ {
        proxy_pass http://reverb_backend;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 86400;
    }
    
    # PHP Application
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param HTTPS on;
    }
    
    # Static assets
    location ~* ^/storage/attachment/ {
        expires 1M;
        access_log off;
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~* ^/(?:css|js)/.*\.(?:css|js)$ {
        expires 2d;
        access_log off;
        add_header Cache-Control "public, must-revalidate";
    }
    
    # Security
    location ~ /\. {
        deny all;
    }
}
EOF
    
    log_success "Nginx config generated"
}

generate_ssl_certificates() {
    log_step "Generating Self-Signed SSL Certificates"
    
    mkdir -p nginx/ssl
    
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout nginx/ssl/key.pem \
        -out nginx/ssl/cert.pem \
        -subj "/C=US/ST=State/L=City/O=FreeScout/CN=${DOMAIN_NAME}" \
        2>&1 | grep -v "writing new private key" || true
    
    # Verify certificates
    if [ ! -f "nginx/ssl/cert.pem" ] || [ ! -f "nginx/ssl/key.pem" ]; then
        log_error "Failed to generate SSL certificates"
        exit 1
    fi
    
    log_success "SSL certificates generated"
}

generate_docker_env() {
    log_step "Generating Docker Environment File"
    
    cat > .env <<EOF
DB_ROOT_PASSWORD=${DB_ROOT_PASS}
DB_DATABASE=${DB_NAME}
DB_USER=${DB_USER}
DB_PASSWORD=${DB_PASS}
APP_URL=https://${DOMAIN_NAME}
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
GOOGLE_CLIENT_ID=${GOOGLE_CLIENT_ID:-}
GOOGLE_CLIENT_SECRET=${GOOGLE_CLIENT_SECRET:-}
GOOGLE_REDIRECT_URI=https://${DOMAIN_NAME}/auth/google/callback
EOF
    
    log_success "Docker .env generated"
}

generate_docker_compose() {
    log_step "Generating Docker Compose Configuration"
    
    # Detect Docker socket GID for permission handling
    # This allows the app container to communicate with Docker daemon
    local DOCKER_GID
    if [ -S "/var/run/docker.sock" ]; then
        DOCKER_GID=$(stat -c '%g' /var/run/docker.sock 2>/dev/null || echo "999")
        log_info "Docker socket GID detected: $DOCKER_GID"
    else
        DOCKER_GID="999"
        log_warning "Docker socket not found, using default GID: $DOCKER_GID"
    fi
    
    cat > docker-compose.yml <<EOF
services:
  app:
    build: .
    image: freescout-app
    restart: unless-stopped
    ports:
      - "443:8080"  # HTTPS on standard port
    environment:
      - PUID=33
      - PGID=33
      # Docker GID for socket access (enables sibling container spawning)
      - DOCKER_GID=${DOCKER_GID}
      - PHP_MEMORY_LIMIT=512M
      - PHP_OPCACHE_ENABLE=1
      - PHP_POST_MAX_SIZE=20M
      - PHP_UPLOAD_MAX_FILESIZE=20M
    volumes:
      - ./src:/var/www/html
      - ./nginx/default.conf:/etc/nginx/conf.d/default.conf
      - ./nginx/ssl:/etc/nginx/ssl
      # DOCKER-OUTSIDE-OF-DOCKER (Sibling Container Architecture)
      # Mount Docker socket to allow app container to spawn sibling containers
      # Used by EmailMigration module for spinning up temporary test mail servers
      # This enables "docker run" commands from within the app container
      - /var/run/docker.sock:/var/run/docker.sock
    depends_on:
      db:
        condition: service_healthy
      redis:
        condition: service_started
    networks:
      - fs-net
    healthcheck:
      test: ["CMD", "curl", "-fk", "https://localhost:8080"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

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
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

  redis:
    image: redis:alpine
    restart: unless-stopped
    networks:
      - fs-net
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5

  queue:
    image: freescout-app
    restart: always
    command: php artisan queue:work --queue=emails,default --sleep=3 --tries=3 --max-time=3600
    environment:
      - PHP_MEMORY_LIMIT=512M
      - PHP_OPCACHE_ENABLE=1
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
    command: >
      /bin/sh -c '
      echo "Installing cron..." &&
      apk add --no-cache dcron &&
      echo "* * * * * cd /var/www/html && php artisan schedule:run >> /var/log/cron.log 2>&1" | crontab - &&
      echo "Cron installed. Starting crond..." &&
      crond -f -l 2
      '
    volumes:
      - ./src:/var/www/html
    depends_on:
      - app
      - db
      - redis
    networks:
      - fs-net

  reverb:
    image: freescout-app
    restart: unless-stopped
    command: >
      sh -c '
      while [ ! -f /var/www/html/vendor/autoload.php ]; do
        echo "Waiting for composer dependencies to be installed..."
        sleep 5
      done
      echo "Dependencies ready, starting Reverb..."
      php artisan reverb:start --host="0.0.0.0" --port=8080
      '
    ports:
      - "6001:8080"
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
        - subnet: ${DOCKER_SUBNET}

volumes:
  db_data:
EOF
    
    log_success "Docker Compose config generated"
}

generate_update_script() {
    log_step "Generating Update Script"
    
    cat > update.sh <<EOF
#!/usr/bin/env bash
set -euo pipefail

echo "🔄 Updating FreeScout (${GIT_BRANCH})..."

cd src
git fetch origin
git checkout ${GIT_BRANCH}
git pull origin ${GIT_BRANCH}
cd ..

echo "🐳 Rebuilding containers..."
sudo docker compose build app
sudo docker compose up -d

echo "🗄️  Running migrations..."
sudo docker compose exec -T app php artisan migrate --force

echo "🧹 Clearing caches..."
sudo docker compose exec -T app php artisan optimize:clear
sudo docker compose exec -T app php artisan freescout:clear-cache

echo "✅ Update complete!"
EOF
    
    chmod +x update.sh
    log_success "Update script generated"
}

clone_or_update_repo() {
    log_step "Cloning/Updating Repository"
    
    if [ -d "$DEFAULT_INSTALL_DIR/src" ]; then
        log_info "Source folder exists. Syncing..."
        
        cd "$DEFAULT_INSTALL_DIR/src"
        git config --global --add safe.directory "$DEFAULT_INSTALL_DIR/src"
        git remote set-url origin "$GIT_REPO_URL"
        git fetch origin
        
        if ! git checkout "$GIT_BRANCH" 2>/dev/null; then
            git checkout -b "$GIT_BRANCH" "origin/$GIT_BRANCH"
        fi
        
        if ! git pull origin "$GIT_BRANCH"; then
            log_error "Git pull failed! Local changes detected."
            
            if [ -t 0 ]; then
                echo ""
                echo "1) Discard local changes (git reset --hard)"
                echo "2) Nuke & Re-clone (Delete src and download fresh)"
                echo "3) Exit and fix manually"
                read -rp "Select [1-3]: " git_opt
                
                case "$git_opt" in
                    1)
                        log_info "Resetting to origin/$GIT_BRANCH..."
                        git reset --hard "origin/$GIT_BRANCH"
                        ;;
                    2)
                        log_warning "Nuking source directory..."
                        cd ..
                        sudo rm -rf src
                        git clone -b "$GIT_BRANCH" "$GIT_REPO_URL" src
                        cd src
                        ;;
                    *)
                        log_error "Aborting. Please fix git conflicts manually."
                        exit 1
                        ;;
                esac
            else
                log_error "Cannot handle git conflict in non-interactive mode"
                exit 1
            fi
        fi
        
        cd ..
    else
        log_info "Cloning branch '$GIT_BRANCH'..."
        git clone -b "$GIT_BRANCH" "$GIT_REPO_URL" src
    fi
    
    log_success "Repository ready"
}

configure_laravel() {
    log_step "Configuring Laravel Environment"
    
    cp "$DEFAULT_INSTALL_DIR/src/.env.example" "$DEFAULT_INSTALL_DIR/src/.env"
    
    local env_file="$DEFAULT_INSTALL_DIR/src/.env"
    
    # Database configuration
    sed -i "s/APP_NAME=Laravel/APP_NAME=\"Borealtek Ticketing\"/g" "$env_file"
    sed -i "s/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/g" "$env_file"
    sed -i "s/# DB_HOST=127.0.0.1/DB_HOST=db/g" "$env_file"
    sed -i "s/# DB_PORT=3306/DB_PORT=3306/g" "$env_file"
    sed -i "s/# DB_DATABASE=laravel/DB_DATABASE=$DB_NAME/g" "$env_file"
    sed -i "s/# DB_USERNAME=root/DB_USERNAME=$DB_USER/g" "$env_file"
    sed -i "s/# DB_PASSWORD=/DB_PASSWORD=$DB_PASS/g" "$env_file"
    
    # App URL and caching  
    sed -i "s|APP_URL=http://localhost|APP_URL=https://$DOMAIN_NAME|g" "$env_file"
    sed -i "s/CACHE_STORE=database/CACHE_STORE=redis/g" "$env_file"
    sed -i "s/SESSION_DRIVER=database/SESSION_DRIVER=redis/g" "$env_file"
    sed -i "s/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/g" "$env_file"
    
    # Admin credentials
    cat >> "$env_file" <<EOF

# Admin Credentials
ADMIN_EMAIL=${ADMIN_EMAIL}
ADMIN_PASSWORD="${ADMIN_PASS}"
EOF
    
    # Reverb/Broadcasting
    local reverb_app_id reverb_app_key reverb_app_secret
    reverb_app_id=$(openssl rand -hex 8)
    reverb_app_key=$(openssl rand -hex 16)
    reverb_app_secret=$(openssl rand -hex 16)
    
    cat >> "$env_file" <<EOF

# Broadcasting (Reverb)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=${reverb_app_id}
REVERB_APP_KEY=${reverb_app_key}
REVERB_APP_SECRET=${reverb_app_secret}
REVERB_HOST="reverb"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${reverb_app_key}"
VITE_REVERB_HOST="${DOMAIN_NAME}"
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
EOF
    
    # Google OAuth (if configured)
    if [ -n "${GOOGLE_CLIENT_ID:-}" ]; then
        cat >> "$env_file" <<EOF

# Google OAuth
GOOGLE_CLIENT_ID=${GOOGLE_CLIENT_ID}
GOOGLE_CLIENT_SECRET=${GOOGLE_CLIENT_SECRET}
GOOGLE_REDIRECT_URI=https://${DOMAIN_NAME}/auth/google/callback
GOOGLE_ADMIN_EMAILS="${GOOGLE_ADMIN_EMAILS:-}"
GOOGLE_ALLOWED_DOMAINS="${GOOGLE_ALLOWED_DOMAINS:-}"
EOF
    fi
    
    log_success "Laravel environment configured"
}

setup_storage_permissions() {
    log_step "Setting Up Storage & Permissions"
    
    mkdir -p src/storage/framework/{cache,sessions,views}
    mkdir -p src/storage/logs
    mkdir -p src/storage/app/public
    mkdir -p src/bootstrap/cache
    mkdir -p src/Modules
    mkdir -p src/public/modules
    
    sudo chown -R 33:33 src
    
    log_success "Storage directories ready"
}

build_and_launch_containers() {
    log_step "Building & Launching Docker Containers"
    
    log_info "Stopping any existing containers..."
    sudo docker compose down --remove-orphans 2>/dev/null || true
    
    log_info "Building application image (with BuildKit)..."
    sudo docker compose build app
    
    log_info "Starting all services..."
    sudo docker compose up -d
    
    log_success "Containers launched"
}

wait_for_database() {
    log_step "Waiting for Database"
    
    local max_attempts=30
    local attempt=0
    
    while [ $attempt -lt $max_attempts ]; do
        if sudo docker compose exec -T db mysqladmin ping -h localhost -u root -p"${DB_ROOT_PASS}" >/dev/null 2>&1; then
            log_success "Database is ready"
            return 0
        fi
        
        ((attempt++))
        echo -ne "\r${CYAN}⏳${NC} Attempt $attempt/$max_attempts..."
        sleep 2
    done
    
    log_error "Database failed to become ready"
    log_error "Check docker logs: sudo docker compose logs db"
    exit 1
}

install_dependencies() {
    log_step "Installing Dependencies"
    
    local composer_flags="--no-dev --optimize-autoloader"
    
    if [ "${SEED_SAMPLE_DATA:-false}" = true ]; then
        log_info "Installing Composer dependencies (including dev for seeding)..."
        composer_flags="--optimize-autoloader"
    else
        log_info "Installing Composer dependencies..."
    fi
    
    sudo docker compose exec -T app composer install $composer_flags
    
    log_info "Installing NPM dependencies..."
    sudo docker compose exec -T app npm install
    
    log_info "Building frontend assets..."
    sudo docker compose exec -T app npm run build
    
    log_success "Dependencies installed"
}

finalize_installation() {
    log_step "Finalizing Installation"
    
    log_info "Generating application key..."
    sudo docker compose exec -T app php artisan key:generate
    
    if [ "$REUSE_DB" = true ]; then
        log_info "Running migrations on existing database..."
        sudo docker compose exec -T app php artisan migrate --force
    else
        log_info "Installing FreeScout..."
        sudo docker compose exec -T app php artisan freescout:install \
            --force \
            --email="$ADMIN_EMAIL" \
            --password="$ADMIN_PASS" \
            --first_name="Admin" \
            --last_name="User"
    fi
    
    log_info "Seeding themes..."
    sudo docker compose exec -T app php artisan db:seed --class=ThemeSeeder --force
    
    if [ "${SEED_SAMPLE_DATA:-false}" = true ]; then
        log_info "Seeding sample data..."
        sudo docker compose exec -T app php artisan db:seed --class=DatabaseSeeder --force
        
        log_info "Cleaning up dev dependencies..."
        sudo docker compose exec -T app composer install --no-dev --optimize-autoloader
    fi
    
    # Configure git safe directory
    cd "$DEFAULT_INSTALL_DIR"
    sudo git config --global --add safe.directory "$DEFAULT_INSTALL_DIR/src"
    
    log_success "Installation finalized"
}

show_completion_message() {
    echo ""
    echo -e "${CYAN}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║                                                            ║${NC}"
    echo -e "${CYAN}║                 ${GREEN}✓${NC} DEPLOYMENT COMPLETE ${GREEN}✓${NC}                     ${CYAN}║${NC}"
    echo -e "${CYAN}║                                                            ║${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${CYAN}Access Information:${NC}"
    echo -e "  URL:   ${GREEN}https://$DOMAIN_NAME${NC}"
    echo -e "  Email: ${GREEN}$ADMIN_EMAIL${NC}"
    
    if [ "$REUSE_DB" = true ] && [ "$ADMIN_PASS_PRESERVED" = true ]; then
        echo -e "  Pass:  ${YELLOW}(Existing password unchanged)${NC}"
    else
        echo -e "  Pass:  ${GREEN}$ADMIN_PASS${NC}"
    fi
    
    echo ""
    echo -e "${CYAN}Next Steps:${NC}"
    echo -e "  • To update: ${YELLOW}cd $DEFAULT_INSTALL_DIR && sudo ./update.sh${NC}"
    echo -e "  • View logs: ${YELLOW}sudo docker compose logs -f${NC}"
    echo -e "  • Stop:      ${YELLOW}sudo docker compose down${NC}"
    echo ""
}

#===============================================================================
# MAIN EXECUTION
#===============================================================================

main() {
    show_banner
    preflight_checks
    load_or_create_config
    
    # Set defaults for credentials
    DB_ROOT_PASS="${DB_ROOT_PASS:-$(openssl rand -hex 16)}"
    DB_USER="${DB_USER:-freescout}"
    DB_PASS="${DB_PASS:-$(openssl rand -hex 16)}"
    DB_NAME="${DB_NAME:-freescout}"
    ADMIN_EMAIL="${ADMIN_EMAIL:-admin@freescout.local}"
    ADMIN_PASS="${ADMIN_PASS:-$(openssl rand -hex 12)}"
    
    check_existing_installation
    
    if [ "$INTERACTIVE" = true ]; then
        if [ -n "${1:-}" ]; then
            GIT_REPO_URL=$1
            GIT_BRANCH=${2:-$DEFAULT_BRANCH}
        else
            interactive_setup
        fi
    fi
    
    # Validate required variables
    validate_required_var "DOMAIN_NAME" "${DOMAIN_NAME:-}"
    validate_required_var "DOCKER_SUBNET" "${DOCKER_SUBNET:-}"
    
    # Execute deployment
    decommission_existing
    setup_directories
    generate_dockerfile
    generate_nginx_config
    generate_ssl_certificates
    generate_docker_env
    generate_docker_compose
    generate_update_script
    clone_or_update_repo
    configure_laravel
    setup_storage_permissions
    build_and_launch_containers
    wait_for_database
    install_dependencies
    finalize_installation
    
    # Cleanup and success
    log_info "Pruning unused Docker resources..."
    sudo docker image prune -f >/dev/null 2>&1 || true
    
    show_completion_message
}

# Run main function
main "$@"
