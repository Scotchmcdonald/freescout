#!/bin/bash

#===============================================================================
# Environment Switcher for FreeScout
# 
# Easily toggle between Linux development and Docker deployment environments
#
# Usage:
#   ./scripts/env-switch.sh linux   # Switch to local Linux development
#   ./scripts/env-switch.sh docker  # Switch to Docker deployment
#   ./scripts/env-switch.sh status  # Show current environment
#===============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

log_info() {
    echo -e "${CYAN}ℹ${NC}  $*"
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

show_usage() {
    echo ""
    echo -e "${CYAN}FreeScout Environment Switcher${NC}"
    echo ""
    echo "Usage: $0 <command>"
    echo ""
    echo "Commands:"
    echo "  linux    Switch to local Linux development environment"
    echo "  docker   Switch to Docker deployment environment"
    echo "  status   Show current environment configuration"
    echo ""
    echo "This script manages the .env file by swapping between:"
    echo "  .env.linux  - For local development (DB_HOST=127.0.0.1, REDIS_HOST=127.0.0.1)"
    echo "  .env.docker - For Docker deployment (DB_HOST=db, REDIS_HOST=redis)"
    echo ""
}

detect_current_env() {
    if [ ! -f "$PROJECT_ROOT/.env" ]; then
        echo "none"
        return
    fi
    
    local db_host=$(grep -E "^DB_HOST=" "$PROJECT_ROOT/.env" | cut -d'=' -f2)
    
    case "$db_host" in
        "127.0.0.1"|"localhost")
            echo "linux"
            ;;
        "db"|"mysql"|"mariadb")
            echo "docker"
            ;;
        *)
            echo "unknown"
            ;;
    esac
}

show_status() {
    local current_env=$(detect_current_env)
    
    echo ""
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${CYAN}              FreeScout Environment Status${NC}"
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
    echo ""
    
    case "$current_env" in
        "linux")
            echo -e "  Current Environment: ${GREEN}Linux Development${NC}"
            ;;
        "docker")
            echo -e "  Current Environment: ${YELLOW}Docker Deployment${NC}"
            ;;
        "none")
            echo -e "  Current Environment: ${RED}No .env file found${NC}"
            ;;
        *)
            echo -e "  Current Environment: ${RED}Unknown (custom configuration)${NC}"
            ;;
    esac
    
    echo ""
    
    if [ -f "$PROJECT_ROOT/.env" ]; then
        echo "  Key Configuration Values:"
        echo "  ─────────────────────────"
        echo "  DB_HOST:    $(grep -E "^DB_HOST=" "$PROJECT_ROOT/.env" | cut -d'=' -f2)"
        echo "  REDIS_HOST: $(grep -E "^REDIS_HOST=" "$PROJECT_ROOT/.env" | cut -d'=' -f2)"
        echo "  APP_ENV:    $(grep -E "^APP_ENV=" "$PROJECT_ROOT/.env" | cut -d'=' -f2)"
        echo "  APP_DEBUG:  $(grep -E "^APP_DEBUG=" "$PROJECT_ROOT/.env" | cut -d'=' -f2)"
    fi
    
    echo ""
    echo "  Available Configurations:"
    echo "  ─────────────────────────"
    [ -f "$PROJECT_ROOT/.env.linux" ] && echo -e "  ${GREEN}✓${NC} .env.linux exists" || echo -e "  ${RED}✗${NC} .env.linux missing"
    [ -f "$PROJECT_ROOT/.env.docker" ] && echo -e "  ${GREEN}✓${NC} .env.docker exists" || echo -e "  ${RED}✗${NC} .env.docker missing"
    echo ""
}

switch_to_linux() {
    log_info "Switching to Linux development environment..."
    
    if [ ! -f "$PROJECT_ROOT/.env.linux" ]; then
        log_error ".env.linux not found. Please create it first."
        exit 1
    fi
    
    # Backup current .env if it exists and differs
    if [ -f "$PROJECT_ROOT/.env" ]; then
        local current_env=$(detect_current_env)
        if [ "$current_env" = "docker" ]; then
            cp "$PROJECT_ROOT/.env" "$PROJECT_ROOT/.env.docker"
            log_info "Backed up current Docker config to .env.docker"
        fi
    fi
    
    cp "$PROJECT_ROOT/.env.linux" "$PROJECT_ROOT/.env"
    log_success "Switched to Linux development environment"
    
    echo ""
    log_info "Services expected:"
    echo "  • MySQL/MariaDB on 127.0.0.1:3306"
    echo "  • Redis on 127.0.0.1:6379"
    echo ""
    log_info "Run these commands to start developing:"
    echo "  php artisan serve"
    echo "  npm run dev"
    echo "  php artisan queue:work"
    echo "  php artisan reverb:start"
    echo ""
}

switch_to_docker() {
    log_info "Switching to Docker deployment environment..."
    
    if [ ! -f "$PROJECT_ROOT/.env.docker" ]; then
        log_error ".env.docker not found. Please create it first."
        exit 1
    fi
    
    # Backup current .env if it exists and differs
    if [ -f "$PROJECT_ROOT/.env" ]; then
        local current_env=$(detect_current_env)
        if [ "$current_env" = "linux" ]; then
            cp "$PROJECT_ROOT/.env" "$PROJECT_ROOT/.env.linux"
            log_info "Backed up current Linux config to .env.linux"
        fi
    fi
    
    cp "$PROJECT_ROOT/.env.docker" "$PROJECT_ROOT/.env"
    log_success "Switched to Docker deployment environment"
    
    echo ""
    log_info "Docker containers expected:"
    echo "  • db (MariaDB container)"
    echo "  • redis (Redis container)"
    echo "  • reverb (WebSocket container)"
    echo ""
    log_info "Use deployment scripts:"
    echo "  ./deployment/docker_deploy.sh    # For Linux servers"
    echo "  ./deployment/orbstack_deploy.sh  # For macOS with OrbStack"
    echo ""
}

# Main
case "${1:-}" in
    linux|dev|local)
        switch_to_linux
        ;;
    docker|deploy|production)
        switch_to_docker
        ;;
    status|info|"")
        show_status
        ;;
    -h|--help|help)
        show_usage
        ;;
    *)
        log_error "Unknown command: $1"
        show_usage
        exit 1
        ;;
esac
