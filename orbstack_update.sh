#!/bin/bash

# Quick update script for OrbStack deployment
# Use this to pull latest code and restart containers without full rebuild

set -e

# ==========================================
# Load configuration from deploy.conf
# ==========================================
CONFIG_FILE="deploy.conf"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [ -f "$SCRIPT_DIR/$CONFIG_FILE" ]; then
    echo "📋 Loading configuration from $CONFIG_FILE..."
    source "$SCRIPT_DIR/$CONFIG_FILE"
    INSTALL_DIR="${INSTALL_DIR:-$HOME/borealtek-ticketing}"
else
    echo "⚠️  No deploy.conf found, using current directory..."
    INSTALL_DIR="$SCRIPT_DIR"
fi

echo "🔄 Updating Freescout at: $INSTALL_DIR"

# Navigate to project directory
cd "$INSTALL_DIR"

# Pull latest code
echo "📥 Pulling latest code from GitHub..."
git pull

# Pull latest Docker images (if any base image updates)
echo "🐳 Pulling latest Docker images..."
docker-compose pull

# Restart containers
echo "♻️  Restarting containers..."
docker-compose down
docker-compose up -d

# Run migrations if any
echo "🗄️  Running database migrations..."
docker-compose exec -T app php artisan migrate --force

# Clear caches
echo "🧹 Clearing caches..."
docker-compose exec -T app php artisan cache:clear
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan view:clear
docker-compose exec -T app php artisan route:clear

# Optimize
echo "⚡ Optimizing..."
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

echo "✅ Update complete!"
echo ""
echo "📊 Container status:"
docker-compose ps
