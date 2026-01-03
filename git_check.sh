#!/bin/bash

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m' # No Color

echo -e "${BLUE}${BOLD}Checking Git Status for Project and Modules...${NC}\n"

# 1. Check Root Repository
if [ -d ".git" ]; then
    echo -e "${BOLD}ROOT APPLICATION${NC}"
    echo "========================================="
    git status
    echo ""
else
    echo -e "${RED}Root directory is not a git repository.${NC}\n"
fi

# 2. Check Modules
if [ -d "Modules" ]; then
    # Check if there are any directories in Modules
    found_git_module=false
    
    for dir in Modules/*; do
        if [ -d "$dir" ] && [ -d "$dir/.git" ]; then
            found_git_module=true
            module_name=$(basename "$dir")
            echo -e "${BOLD}MODULE: ${module_name}${NC}"
            echo "========================================="
            (cd "$dir" && git status)
            echo ""
        fi
    done

    if [ "$found_git_module" = false ]; then
         echo -e "No git repositories found in Modules folder.\n"
    fi
else
    echo -e "Modules directory not found.\n"
fi
