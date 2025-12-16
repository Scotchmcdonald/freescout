#!/usr/bin/env bash

#===============================================================================
# FreeScout OrbStack Deployer (macOS + Cloudflare Tunnel)
# 
# Enterprise-grade deployment script with:
# - Bash strict mode (set -euo pipefail)
# - Trap handlers for cleanup
# - Progress indicators
# - Pre-flight validation
# - Docker BuildKit optimization
# - Self-signed SSL certificates
# - Cloudflare Tunnel integration
# - Idempotent re-deployment (safely re-run over existing installations)
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
readonly DEFAULT_INSTALL_DIR="$HOME/borealtek-ticketing"
readonly CONFIG_FILE="${SCRIPT_DIR}/deploy.conf"

# Color codes
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly CYAN='\033[0;36m'
readonly BLUE='\033[0;34m'
readonly MAGENTA='\033[0;35m'
readonly NC='\033[0m'

# State variables
INTERACTIVE=true
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

cleanup() {
    local exit_code=$?
    
    if [ "$CLEANUP_NEEDED" = true ]; then
        log_warning "Cleaning up after error..."
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
    
    # Check for Homebrew (informational)
    if ! command_exists brew; then
        log_warning "Homebrew not found. Assuming dependencies are met."
    fi
    
    # Check required tools
    local required_tools=("git" "curl" "openssl")
    for tool in "${required_tools[@]}"; do
        if ! command_exists "$tool"; then
            log_error "Missing tool: $tool"
            if command_exists brew; then
                log_info "Install with: brew install $tool"
            fi
            exit 1
        fi
    done
    
    # Check Docker (OrbStack or Docker Desktop)
    if ! command_exists docker; then
        log_error "Docker not found! Install OrbStack first."
        log_info "Download from: https://orbstack.dev"
        exit 1
    fi
    
    # Verify Docker is running
    if ! docker info >/dev/null 2>&1; then
        log_error "Docker is installed but not running"
        exit 1
    fi
    
    # Enable BuildKit
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
    echo -e "${CYAN}║    FreeScout OrbStack Deployer v${SCRIPT_VERSION} (macOS)         ║${NC}"
    echo -e "${CYAN}║                                                            ║${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
}

load_or_create_config() {
    if [ -f "$CONFIG_FILE" ]; then
        log_success "Configuration file found: $CONFIG_FILE"
        
        if [ -t 0 ]; then
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
                log_info "Please edit the file and paste your Cloudflare Tunnel Token, then run again"
                exit 0
            fi
        fi
    fi
}

create_config_template() {
    cat > "$CONFIG_FILE" <<EOF
#===============================================================================
# FreeScout OrbStack Deployment Configuration (macOS)
#===============================================================================

# Installation Settings
GIT_REPO_URL="$DEFAULT_REPO"
GIT_BRANCH="$DEFAULT_BRANCH"
DEFAULT_INSTALL_DIR="$DEFAULT_INSTALL_DIR"

# Domain & Cloudflare Tunnel
DOMAIN_NAME="devtickets.scotchmcdonald.dev"
CF_TUNNEL_TOKEN=""  # Get from Cloudflare Zero Trust Dashboard

# Database Settings
DB_ROOT_PASS="$(openssl rand -hex 16)"
DB_USER="freescout"
DB_PASS="$(openssl rand -hex 16)"
DB_NAME="freescout"

# Admin User
ADMIN_EMAIL="admin@scotchmcdonald.dev"
ADMIN_PASS="$(openssl rand -hex 12)"

# Google OAuth (Optional)
GOOGLE_CLIENT_ID=""
GOOGLE_CLIENT_SECRET=""
GOOGLE_ADMIN_EMAILS=""
GOOGLE_ALLOWED_DOMAINS=""
EOF
}

interactive_setup() {
    log_step "Interactive Setup"
    
    # Cloudflare configuration
    log_info "Cloudflare Configuration"
    read -rp "Domain Name [devtickets.scotchmcdonald.dev]: " input_domain
    DOMAIN_NAME="${input_domain:-devtickets.scotchmcdonald.dev}"
    
    while [ -z "${CF_TUNNEL_TOKEN:-}" ]; do
        echo -e "${YELLOW}Paste your Cloudflare Tunnel Token (starts with ey...):${NC}"
        read -r CF_TUNNEL_TOKEN
    done
    echo ""
    
    # Admin configuration
    log_info "Admin User"
    read -rp "Admin Email [admin@scotchmcdonald.dev]: " input_email
    ADMIN_EMAIL="${input_email:-admin@scotchmcdonald.dev}"
    read -rp "Admin Password [auto-generate]: " input_pass
    ADMIN_PASS="${input_pass:-$(openssl rand -hex 12)}"
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
    
    # Configuration summary
    echo "────────────────────────────────────────────────────────────"
    echo -e "CONFIGURATION SUMMARY:"
    echo -e "  Repository: ${GREEN}$DEFAULT_REPO${NC}"
    echo -e "  Branch:     ${GREEN}$DEFAULT_BRANCH${NC}"
    echo -e "  Domain:     ${GREEN}$DOMAIN_NAME${NC}"
    echo -e "  Tunnel:     ${GREEN}Configured${NC}"
    if [ -n "$GOOGLE_CLIENT_ID" ]; then
        echo -e "  Google:     ${GREEN}Configured${NC}"
    else
        echo -e "  Google:     ${YELLOW}Skipped${NC}"
    fi
    echo "────────────────────────────────────────────────────────────"
    echo ""
    read -rp "Press ENTER to start deployment (or Ctrl+C to cancel)..."
}

#===============================================================================
# DEPLOYMENT FUNCTIONS
#===============================================================================

setup_directories() {
    log_step "Setting Up Directory Structure"
    
    mkdir -p "$DEFAULT_INSTALL_DIR/nginx"
    cd "$DEFAULT_INSTALL_DIR"
    
    # Check if this is a re-deployment (docker-compose.yml exists)
    if [ -f "docker-compose.yml" ]; then
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
                log_info "Reusing existing data - stopping containers only..."
                docker compose down 2>/dev/null || true
                log_success "Containers stopped, data preserved"
                ;;
            2)
                log_warning "Nuking everything - all data will be lost!"
                read -p "Type 'yes' to confirm: " confirm
                if [ "$confirm" = "yes" ]; then
                    log_info "Stopping and removing containers and volumes..."
                    docker compose down -v --remove-orphans 2>/dev/null || true
                    log_info "Removing source code directory..."
                    rm -rf src
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
    fi
    
    log_success "Directories created"
}

generate_dockerfile() {
    log_step "Generating Dockerfile"
    
    cat > Dockerfile <<'EOF'
FROM serversideup/php:8.2-fpm-nginx

USER root

# Install system dependencies and Node.js 24.x LTS
RUN apt-get update && apt-get install -y gnupg git docker.io docker-compose-plugin && \
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
REDIS_PORT=6379
TUNNEL_TOKEN=${CF_TUNNEL_TOKEN}
EOF
    
    log_success "Docker .env generated"
}

generate_docker_compose() {
    log_step "Generating Docker Compose Configuration"
    
    # Detect Docker socket GID for permission handling
    # OrbStack typically uses same socket path as Docker Desktop
    local DOCKER_GID
    if [ -S "/var/run/docker.sock" ]; then
        DOCKER_GID=$(stat -f '%g' /var/run/docker.sock 2>/dev/null || echo "0")
        log_info "Docker socket GID detected: $DOCKER_GID"
    else
        DOCKER_GID="0"
        log_warning "Docker socket not found, using default GID: $DOCKER_GID"
    fi
    
    cat > docker-compose.yml <<EOF
services:
  app:
    build: .
    image: freescout-app
    restart: unless-stopped
    ports:
      - "127.0.0.1:8080:8080"  # Local only (tunnel handles public)
    environment:
      - PUID=$(id -u)
      - PGID=$(id -g)
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
      # OrbStack: Uses same socket path as standard Docker (/var/run/docker.sock)
      - /var/run/docker.sock:/var/run/docker.sock
    depends_on:
      db:
        condition: service_healthy
      redis:
        condition: service_started
    networks:
      - fs-net
    healthcheck:
      test: ["CMD", "curl", "-fk", "http://localhost:8080"]
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
    environment:
      - PHP_OPCACHE_ENABLE=1
      - ENABLE_CRON=true
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
    environment:
      - PHP_OPCACHE_ENABLE=1
    volumes:
      - ./src:/var/www/html
    depends_on:
      - app
      - db
      - redis
    networks:
      - fs-net

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
    
    log_success "Docker Compose config generated"
}

generate_update_script() {
    log_step "Generating Update Script"
    
    cat > update.sh <<EOF
#!/usr/bin/env bash
set -euo pipefail

echo "🔄 Updating FreeScout..."

cd src
git pull origin ${DEFAULT_BRANCH}
cd ..

echo "🐳 Rebuilding containers..."
docker compose build app
docker compose up -d

echo "🗄️  Running migrations..."
docker compose exec -T app php artisan migrate --force

echo "🧹 Clearing caches..."
docker compose exec -T app php artisan freescout:clear-cache

echo "✅ Update complete!"
EOF
    
    chmod +x update.sh
    log_success "Update script generated"
}

clone_or_update_repo() {
    log_step "Cloning/Updating Repository"
    
    if [ -d "src" ]; then
        log_info "Source folder exists. Syncing..."
        
        cd src
        git config --global --add safe.directory "$PWD"
        git remote set-url origin "$DEFAULT_REPO"
        git fetch origin
        
        if ! git checkout "$DEFAULT_BRANCH" 2>/dev/null; then
            git checkout -b "$DEFAULT_BRANCH" "origin/$DEFAULT_BRANCH"
        fi
        
        if ! git pull origin "$DEFAULT_BRANCH"; then
            log_error "Git pull failed! Local changes detected."
            
            if [ -t 0 ]; then
                echo ""
                echo "1) Discard local changes (git reset --hard)"
                echo "2) Nuke & Re-clone (Delete src and download fresh)"
                echo "3) Exit and fix manually"
                read -rp "Select [1-3]: " git_opt
                
                case "$git_opt" in
                    1)
                        log_info "Resetting to origin/$DEFAULT_BRANCH..."
                        git reset --hard "origin/$DEFAULT_BRANCH"
                        ;;
                    2)
                        log_warning "Nuking source directory..."
                        cd ..
                        rm -rf src
                        git clone -b "$DEFAULT_BRANCH" "$DEFAULT_REPO" src
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
        log_info "Cloning source..."
        git clone -b "$DEFAULT_BRANCH" "$DEFAULT_REPO" src
    fi
    
    log_success "Repository ready"
}

configure_laravel() {
    log_step "Configuring Laravel Environment"
    
    cp "src/.env.example" "src/.env"
    
    local env_file="src/.env"
    
    # Use BSD sed syntax for macOS
    sed -i '' "s|APP_URL=http://localhost|APP_URL=https://${DOMAIN_NAME}|g" "$env_file"
    sed -i '' "s/DB_HOST=127.0.0.1/DB_HOST=db/g" "$env_file"
    sed -i '' "s/DB_PASSWORD=/DB_PASSWORD=${DB_PASS}/g" "$env_file"
    sed -i '' "s/CACHE_STORE=database/CACHE_STORE=redis/g" "$env_file"
    sed -i '' "s/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/g" "$env_file"
    sed -i '' "s/APP_FORCE_HTTPS=false/APP_FORCE_HTTPS=true/g" "$env_file"
    
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
    
    # Trust Cloudflare proxies
    echo "TRUSTED_PROXIES=*" >> "$env_file"
    
    log_success "Laravel environment configured"
}

setup_storage_permissions() {
    log_step "Setting Up Storage & Permissions"
    
    # Clear potential cache files
    rm -f src/bootstrap/cache/*.php 2>/dev/null || true
    rm -rf src/storage/framework/cache/* 2>/dev/null || true
    rm -rf src/storage/framework/views/* 2>/dev/null || true
    rm -rf src/storage/framework/sessions/* 2>/dev/null || true
    
    # Create directories
    mkdir -p src/storage/framework/{cache,sessions,views,testing}
    mkdir -p src/storage/logs
    mkdir -p src/bootstrap/cache
    
    # Set permissive permissions for Docker
    chmod -R 777 src/storage src/bootstrap/cache
    
    log_success "Storage directories ready"
}

build_and_launch_containers() {
    log_step "Building & Launching Docker Containers"
    
    log_info "Building application image (with BuildKit)..."
    docker compose build app
    
    log_info "Starting all services..."
    docker compose up -d
    
    log_success "Containers launched"
}

wait_for_database() {
    log_step "Waiting for Database"
    
    local max_attempts=30
    local attempt=0
    
    while [ $attempt -lt $max_attempts ]; do
        if docker compose exec -T db mysqladmin ping -h localhost -u root -p"${DB_ROOT_PASS}" >/dev/null 2>&1; then
            log_success "Database is ready"
            return 0
        fi
        
        ((attempt++))
        echo -ne "\r${CYAN}⏳${NC} Attempt $attempt/$max_attempts..."
        sleep 2
    done
    
    log_error "Database failed to become ready"
    return 1
}

install_dependencies() {
    log_step "Installing Dependencies"
    
    log_info "Installing Composer dependencies..."
    docker compose exec -T -u root app composer install --no-dev --optimize-autoloader
    docker compose exec -T -u root app chown -R www-data:www-data /var/www/html/vendor /var/www/html/composer.lock
    
    log_info "Installing NPM dependencies..."
    docker compose exec -T -u root app npm install
    
    log_info "Building frontend assets..."
    docker compose exec -T -u root app npm run build
    
    log_success "Dependencies installed"
}

finalize_installation() {
    log_step "Finalizing Installation"
    
    log_info "Generating application key..."
    docker compose exec -T app php artisan key:generate
    
    log_info "Installing FreeScout..."
    docker compose exec -T app php artisan freescout:install \
        --force \
        --email="$ADMIN_EMAIL" \
        --password="$ADMIN_PASS" \
        --first_name="Admin" \
        --last_name="User"
    
    log_info "Seeding themes..."
    docker compose exec -T app php artisan db:seed --class=ThemeSeeder --force
    
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
    echo -e "  Pass:  ${GREEN}$ADMIN_PASS${NC}"
    echo ""
    echo -e "${CYAN}Cloudflare Tunnel Configuration:${NC}"
    echo -e "  1. Go to Cloudflare Zero Trust → Networks → Tunnels"
    echo -e "  2. Click your tunnel → Configure → Public Hostname"
    echo -e "  3. Add/Edit Public Hostname:"
    echo -e "     ${YELLOW}Service Type:${NC} HTTPS"
    echo -e "     ${YELLOW}URL:${NC}          https://app:8080"
    echo -e "     ${YELLOW}TLS Verify:${NC}   ${RED}Disabled${NC} (toggle 'No TLS Verify' ON)"
    echo -e "     ${YELLOW}Origin Name:${NC}  $DOMAIN_NAME"
    echo ""
    
    if [ -n "${GOOGLE_CLIENT_ID:-}" ]; then
        echo -e "${CYAN}Google OAuth Setup:${NC}"
        echo -e "  Add this redirect URI to Google Cloud Console:"
        echo -e "  ${GREEN}https://$DOMAIN_NAME/auth/google/callback${NC}"
        echo ""
    fi
    
    echo -e "${CYAN}Next Steps:${NC}"
    echo -e "  • Update:    ${YELLOW}cd $DEFAULT_INSTALL_DIR && ./update.sh${NC}"
    echo -e "  • View logs: ${YELLOW}docker compose logs -f${NC}"
    echo -e "  • Stop:      ${YELLOW}docker compose down${NC}"
    echo -e "  • Emergency: ${YELLOW}https://localhost:8080${NC} (accept cert warning)"
    echo ""
}

#===============================================================================
# MAIN EXECUTION
#===============================================================================

main() {
    show_banner
    preflight_checks
    load_or_create_config
    
    # Set defaults
    DB_ROOT_PASS="${DB_ROOT_PASS:-$(openssl rand -hex 16)}"
    DB_USER="${DB_USER:-freescout}"
    DB_PASS="${DB_PASS:-$(openssl rand -hex 16)}"
    DB_NAME="${DB_NAME:-freescout}"
    ADMIN_EMAIL="${ADMIN_EMAIL:-admin@scotchmcdonald.dev}"
    ADMIN_PASS="${ADMIN_PASS:-$(openssl rand -hex 12)}"
    
    if [ "$INTERACTIVE" = true ]; then
        interactive_setup
    fi
    
    # Validate required variables
    validate_required_var "DOMAIN_NAME" "${DOMAIN_NAME:-}"
    validate_required_var "CF_TUNNEL_TOKEN" "${CF_TUNNEL_TOKEN:-}"
    
    # Execute deployment
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
    
    # Cleanup
    log_info "Pruning unused Docker resources..."
    docker image prune -f >/dev/null 2>&1 || true
    
    show_completion_message
}

# Run main function
main "$@"
