#!/bin/bash
# FreeScout Auto-Start Services Setup Script
# This script configures FreeScout to run automatically at system startup

set -e

echo "=========================================="
echo "FreeScout Auto-Start Setup"
echo "=========================================="
echo ""

# Check if running as root or with sudo
if [ "$EUID" -ne 0 ]; then 
    echo "❌ This script must be run with sudo"
    echo "Usage: sudo ./setup-startup.sh"
    exit 1
fi

# Get the actual user who invoked sudo
ACTUAL_USER="${SUDO_USER:-$USER}"

echo "📦 Installing Supervisor..."
apt-get update -qq
apt-get install -y supervisor

echo ""
echo "📝 Creating Supervisor configuration for Queue Workers..."
tee /etc/supervisor/conf.d/freescout-worker.conf > /dev/null <<'EOF'
[program:freescout-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
stopwaitsecs=3600
startsecs=0
EOF

echo "✅ Queue worker configuration created"

echo ""
echo "📝 Creating Supervisor configuration for Laravel Reverb..."
tee /etc/supervisor/conf.d/freescout-reverb.conf > /dev/null <<'EOF'
[program:freescout-reverb]
process_name=%(program_name)s
command=php /var/www/html/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/reverb.log
startsecs=0
EOF

echo "✅ Reverb configuration created"

echo ""
echo "🔐 Setting up permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

echo "✅ Permissions set"

echo ""
echo "🚀 Enabling and starting Supervisor..."
systemctl enable supervisor
systemctl start supervisor

echo ""
echo "🔄 Reloading Supervisor configuration..."
supervisorctl reread
supervisorctl update

echo ""
echo "▶️  Starting services..."
supervisorctl start freescout-worker:* || echo "⚠️  Workers already running or failed to start"
supervisorctl start freescout-reverb || echo "⚠️  Reverb already running or failed to start"

echo ""
echo "⏰ Setting up cron jobs for scheduled tasks..."
# Get existing crontab or empty string if none exists
EXISTING_CRON=$(crontab -l -u www-data 2>/dev/null || true)

# Check if scheduler cron already exists
if echo "$EXISTING_CRON" | grep -q "artisan schedule:run"; then
    echo "   Scheduler cron job already exists"
else
    # Add scheduler cron
    (echo "$EXISTING_CRON"; echo "* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1") | crontab -u www-data -
    echo "✅ Laravel scheduler cron job added"
fi

echo ""
echo "📊 Checking service status..."
supervisorctl status

echo ""
echo "=========================================="
echo "✅ Setup Complete!"
echo "=========================================="
echo ""
echo "Services configured to start automatically at boot:"
echo "  • Queue Workers (2 processes)"
echo "  • Laravel Reverb WebSocket Server"
echo "  • Laravel Scheduler (via cron)"
echo ""
echo "Useful commands:"
echo "  sudo supervisorctl status                          - Check service status"
echo "  sudo supervisorctl restart all                     - Restart all services"
echo "  sudo supervisorctl restart freescout-worker:*      - Restart workers"
echo "  sudo supervisorctl restart freescout-reverb        - Restart Reverb"
echo "  sudo tail -f /var/www/html/storage/logs/worker.log - View worker logs"
echo "  sudo tail -f /var/www/html/storage/logs/reverb.log - View Reverb logs"
echo ""
echo "To test auto-start after reboot:"
echo "  sudo reboot"
echo "  # After reboot:"
echo "  sudo supervisorctl status"
echo ""
