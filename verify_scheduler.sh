#!/bin/bash

# Verification script for Laravel Scheduler setup
# This script helps verify that the scheduler is properly configured

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${YELLOW}==================================================${NC}"
echo -e "${YELLOW}  Laravel Scheduler Verification${NC}"
echo -e "${YELLOW}==================================================${NC}"
echo ""

# Check if running in Docker
if [ -f /.dockerenv ]; then
    echo -e "${GREEN}✓ Running inside Docker container${NC}"
    
    # Check if cron is installed
    if command -v crond >/dev/null 2>&1; then
        echo -e "${GREEN}✓ Cron daemon (dcron) is installed${NC}"
    else
        echo -e "${RED}✗ Cron daemon not found${NC}"
        exit 1
    fi
    
    # Check crontab
    echo -e "\n${YELLOW}Current crontab:${NC}"
    crontab -l
    
    # Check if cron is running
    if pgrep crond >/dev/null 2>&1; then
        echo -e "\n${GREEN}✓ Cron daemon is running${NC}"
    else
        echo -e "\n${RED}✗ Cron daemon is not running${NC}"
    fi
    
else
    echo -e "${YELLOW}Running on host system (not in Docker)${NC}"
fi

# Test Laravel scheduler
echo -e "\n${YELLOW}Testing Laravel scheduler:${NC}"
cd /var/www/html || exit 1

php artisan schedule:list 2>/dev/null
if [ $? -eq 0 ]; then
    echo -e "\n${GREEN}✓ Laravel scheduler is configured${NC}"
else
    echo -e "\n${RED}✗ Laravel scheduler test failed${NC}"
    exit 1
fi

# Check for follow-up reminder command
echo -e "\n${YELLOW}Checking for follow-up reminder command:${NC}"
if php artisan schedule:list 2>/dev/null | grep -q "followup:send-reminders"; then
    echo -e "${GREEN}✓ Follow-up reminder command is scheduled${NC}"
else
    echo -e "${YELLOW}! Follow-up reminder command not found in schedule${NC}"
    echo -e "  This is normal if scheduler hasn't been set up yet"
fi

# Manual test
echo -e "\n${YELLOW}Running manual test of follow-up command:${NC}"
php artisan followup:send-reminders

echo -e "\n${GREEN}==================================================${NC}"
echo -e "${GREEN}  Verification Complete${NC}"
echo -e "${GREEN}==================================================${NC}"
