#!/bin/bash
# FreeScout Master Setup Script
# This script provides a menu to set up the development environment,
# web server, and auto-start services.

set -e

# Get the directory of the script
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# --- Functions ---

print_header() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
}

print_success() { echo -e "${GREEN}✓ $1${NC}"; }
print_error() { echo -e "${RED}✗ $1${NC}"; }
print_info() { echo -e "${YELLOW}→ $1${NC}"; }

# --- Menu Functions ---

run_dev_environment_setup() {
    print_header "Running Development Environment Setup"
    if [ -f "$SCRIPT_DIR/_setup-dev-environment.sh" ]; then
        bash "$SCRIPT_DIR/_setup-dev-environment.sh"
    else
        print_error "Dev environment setup script not found!"
    fi
    echo ""
    print_success "Development environment setup complete."
}

run_web_server_setup() {
    print_header "Running Web Server Setup (Nginx)"
    if [ -f "$SCRIPT_DIR/_setup-webserver.sh" ]; then
        sudo bash "$SCRIPT_DIR/_setup-webserver.sh"
    else
        print_error "Web server setup script not found!"
    fi
    echo ""
    print_success "Web server setup complete."
}

run_startup_setup() {
    print_header "Running Auto-Start Services Setup (Supervisor)"
    if [ -f "$SCRIPT_DIR/_setup-startup.sh" ]; then
        sudo bash "$SCRIPT_DIR/_setup-startup.sh"
    else
        print_error "Startup setup script not found!"
    fi
    echo ""
    print_success "Auto-start services setup complete."
}

run_all() {
    run_dev_environment_setup
    run_web_server_setup
    run_startup_setup
}

# --- Main Menu ---

main_menu() {
    clear
    print_header "FreeScout Master Setup"
    echo "This script will guide you through setting up your FreeScout environment."
    echo ""
    echo "Please choose an option:"
    echo "  1) Setup Development Environment (PHP extensions, Composer, etc.)"
    echo "  2) Setup Web Server (Nginx)"
    echo "  3) Setup Auto-Start Services (Supervisor for queues/reverb)"
    echo "  --------------------------------------------------"
    echo "  4) Run ALL Setup Steps (1, 2, and 3)"
    echo "  --------------------------------------------------"
    echo "  q) Quit"
    echo ""

    read -rp "Enter your choice: " choice

    case $choice in
        1)
            run_dev_environment_setup
            ;;
        2)
            run_web_server_setup
            ;;
        3)
            run_startup_setup
            ;;
        4)
            run_all
            ;;
        q|Q)
            echo "Exiting setup."
            exit 0
            ;;
        *)
            print_error "Invalid option. Please try again."
            sleep 2
            ;;
    esac

    echo ""
    read -rp "Press Enter to return to the menu..."
    main_menu
}

# --- Script Execution ---

# Check for --all flag for non-interactive setup
if [ "$1" == "--all" ]; then
    run_all
    exit 0
fi

main_menu
