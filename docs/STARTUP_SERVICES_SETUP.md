# FreeScout Auto-Start Services Setup

This guide will help you set up FreeScout to run automatically at system startup using systemd services and supervisor.

## Overview

FreeScout requires several services to run continuously:
1. **Web Server** (Nginx/Apache) - Serves the application
2. **Queue Workers** - Process background jobs (emails, notifications)
3. **Laravel Reverb** - WebSocket server for real-time updates
4. **Scheduled Tasks** - Cron jobs for periodic tasks

## Option 1: Using Supervisor (Recommended)

Supervisor is a process control system that keeps your services running.

### Step 1: Install Supervisor

```bash
sudo apt-get update
sudo apt-get install -y supervisor
```

### Step 2: Create Queue Worker Configuration

Create `/etc/supervisor/conf.d/freescout-worker.conf`:

```bash
sudo tee /etc/supervisor/conf.d/freescout-worker.conf > /dev/null <<'EOF'
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
```

### Step 3: Create Reverb WebSocket Server Configuration

Create `/etc/supervisor/conf.d/freescout-reverb.conf`:

```bash
sudo tee /etc/supervisor/conf.d/freescout-reverb.conf > /dev/null <<'EOF'
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
```

### Step 4: Enable and Start Services

```bash
# Enable supervisor to start at boot
sudo systemctl enable supervisor

# Reload supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start the services
sudo supervisorctl start freescout-worker:*
sudo supervisorctl start freescout-reverb

# Check status
sudo supervisorctl status
```

### Step 5: Setup Scheduled Tasks (Cron)

Add cron jobs for the www-data user:

```bash
sudo crontab -e -u www-data
```

Add these lines:

```bash
# Laravel Scheduler (runs every minute, checks for scheduled tasks)
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1

# Fetch Emails (every 5 minutes) - if not using queue-based fetching
*/5 * * * * cd /var/www/html && php artisan freescout:fetch-emails >> /dev/null 2>&1
```

### Managing Services with Supervisor

```bash
# Check status
sudo supervisorctl status

# Start all services
sudo supervisorctl start all

# Stop all services
sudo supervisorctl stop all

# Restart all services
sudo supervisorctl restart all

# Restart specific service
sudo supervisorctl restart freescout-worker:*
sudo supervisorctl restart freescout-reverb

# View logs
sudo tail -f /var/www/html/storage/logs/worker.log
sudo tail -f /var/www/html/storage/logs/reverb.log
```

---

## Option 2: Using Systemd Services Directly

If you prefer systemd over supervisor, you can create native systemd services.

### Step 1: Create Queue Worker Service

Create `/etc/systemd/system/freescout-worker@.service`:

```bash
sudo tee /etc/systemd/system/freescout-worker@.service > /dev/null <<'EOF'
[Unit]
Description=FreeScout Queue Worker %i
After=network.target mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=3
WorkingDirectory=/var/www/html
ExecStart=/usr/bin/php /var/www/html/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
StandardOutput=append:/var/www/html/storage/logs/worker-%i.log
StandardError=append:/var/www/html/storage/logs/worker-%i.log

[Install]
WantedBy=multi-user.target
EOF
```

### Step 2: Create Reverb WebSocket Service

Create `/etc/systemd/system/freescout-reverb.service`:

```bash
sudo tee /etc/systemd/system/freescout-reverb.service > /dev/null <<'EOF'
[Unit]
Description=FreeScout Laravel Reverb WebSocket Server
After=network.target mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=3
WorkingDirectory=/var/www/html
ExecStart=/usr/bin/php /var/www/html/artisan reverb:start --host=0.0.0.0 --port=8080
StandardOutput=append:/var/www/html/storage/logs/reverb.log
StandardError=append:/var/www/html/storage/logs/reverb.log

[Install]
WantedBy=multi-user.target
EOF
```

### Step 3: Create Scheduler Service

Create `/etc/systemd/system/freescout-scheduler.service`:

```bash
sudo tee /etc/systemd/system/freescout-scheduler.service > /dev/null <<'EOF'
[Unit]
Description=FreeScout Laravel Scheduler
After=network.target mysql.service

[Service]
Type=oneshot
User=www-data
Group=www-data
WorkingDirectory=/var/www/html
ExecStart=/usr/bin/php /var/www/html/artisan schedule:run

[Install]
WantedBy=multi-user.target
EOF
```

### Step 4: Create Scheduler Timer

Create `/etc/systemd/system/freescout-scheduler.timer`:

```bash
sudo tee /etc/systemd/system/freescout-scheduler.timer > /dev/null <<'EOF'
[Unit]
Description=Run FreeScout Laravel Scheduler every minute
Requires=freescout-scheduler.service

[Timer]
OnBootSec=1min
OnUnitActiveSec=1min
Unit=freescout-scheduler.service

[Install]
WantedBy=timers.target
EOF
```

### Step 5: Enable and Start All Services

```bash
# Reload systemd daemon
sudo systemctl daemon-reload

# Enable services to start at boot
sudo systemctl enable freescout-worker@1.service
sudo systemctl enable freescout-worker@2.service
sudo systemctl enable freescout-reverb.service
sudo systemctl enable freescout-scheduler.timer

# Start services now
sudo systemctl start freescout-worker@1.service
sudo systemctl start freescout-worker@2.service
sudo systemctl start freescout-reverb.service
sudo systemctl start freescout-scheduler.timer

# Check status
sudo systemctl status freescout-worker@1.service
sudo systemctl status freescout-worker@2.service
sudo systemctl status freescout-reverb.service
sudo systemctl status freescout-scheduler.timer
```

### Managing Systemd Services

```bash
# Check status
sudo systemctl status freescout-worker@1
sudo systemctl status freescout-reverb
sudo systemctl list-timers | grep freescout

# Start/Stop/Restart
sudo systemctl start freescout-worker@1
sudo systemctl stop freescout-worker@1
sudo systemctl restart freescout-worker@1

# View logs
sudo journalctl -u freescout-worker@1 -f
sudo journalctl -u freescout-reverb -f
sudo tail -f /var/www/html/storage/logs/worker-1.log
sudo tail -f /var/www/html/storage/logs/reverb.log

# Disable (prevent auto-start)
sudo systemctl disable freescout-worker@1
sudo systemctl disable freescout-reverb
```

---

## Option 3: Web Server Configuration (Nginx/Apache)

Ensure your web server starts automatically.

### Nginx

```bash
# Enable Nginx
sudo systemctl enable nginx

# Start Nginx
sudo systemctl start nginx

# Check status
sudo systemctl status nginx
```

### Apache

```bash
# Enable Apache
sudo systemctl enable apache2  # or httpd on some systems

# Start Apache
sudo systemctl start apache2

# Check status
sudo systemctl status apache2
```

---

## Verification & Testing

### Test Automatic Startup

```bash
# Reboot the system
sudo reboot

# After reboot, check all services are running
sudo supervisorctl status  # If using supervisor

# OR

sudo systemctl status freescout-worker@1
sudo systemctl status freescout-worker@2
sudo systemctl status freescout-reverb
sudo systemctl list-timers | grep freescout
```

### Monitor Logs

```bash
# Supervisor logs
sudo tail -f /var/www/html/storage/logs/worker.log
sudo tail -f /var/www/html/storage/logs/reverb.log

# Systemd logs
sudo journalctl -u freescout-worker@1 -f
sudo journalctl -u freescout-reverb -f

# Laravel logs
sudo tail -f /var/www/html/storage/logs/laravel.log
```

### Health Checks

```bash
# Check queue workers are processing
php artisan queue:work --once

# Check if jobs are in queue
php artisan queue:monitor

# Test WebSocket connection
# In browser console:
# Echo.channel('test').listen('.test', (e) => console.log(e));

# Check scheduled tasks
php artisan schedule:list
```

---

## Troubleshooting

### Services Not Starting

**Check permissions:**
```bash
# Ensure www-data owns storage and bootstrap/cache
sudo chown -R www-data:www-data /var/www/html/storage
sudo chown -R www-data:www-data /var/www/html/bootstrap/cache
sudo chmod -R 775 /var/www/html/storage
sudo chmod -R 775 /var/www/html/bootstrap/cache
```

**Check logs:**
```bash
# Supervisor
sudo tail -100 /var/log/supervisor/supervisord.log

# Systemd
sudo journalctl -xe
```

### Queue Workers Dying

**Check database connection:**
```bash
php artisan queue:work database --once
```

**Increase memory limit in supervisor/systemd config:**
```ini
command=php -d memory_limit=512M /var/www/html/artisan queue:work database
```

### Reverb Not Accessible

**Check if port 8080 is open:**
```bash
sudo netstat -tulpn | grep 8080
sudo ss -tulpn | grep 8080
```

**Check firewall:**
```bash
sudo ufw status
sudo ufw allow 8080/tcp
```

### Cron Jobs Not Running

**Verify crontab:**
```bash
sudo crontab -l -u www-data
```

**Check cron logs:**
```bash
grep CRON /var/log/syslog
```

**Test manually:**
```bash
sudo -u www-data php /var/www/html/artisan schedule:run
```

---

## Performance Tuning

### Multiple Queue Workers

For high-traffic installations, increase worker count:

**Supervisor:**
```ini
numprocs=4  # Increase from 2 to 4
```

**Systemd:**
```bash
sudo systemctl enable freescout-worker@3.service
sudo systemctl enable freescout-worker@4.service
sudo systemctl start freescout-worker@3.service
sudo systemctl start freescout-worker@4.service
```

### Queue Priorities

Create separate queues for different job types:

```ini
# High priority worker
command=php /var/www/html/artisan queue:work database --queue=high,default,low

# Default worker
command=php /var/www/html/artisan queue:work database --queue=default,low
```

### Resource Limits

Set resource limits in systemd:

```ini
[Service]
MemoryLimit=512M
CPUQuota=50%
```

---

## Security Considerations

1. **Run as www-data:** Never run as root
2. **Limit permissions:** Only www-data should write to storage/
3. **Monitor logs:** Set up log rotation and monitoring
4. **Firewall rules:** Restrict Reverb port (8080) to localhost if not needed externally
5. **SSL/TLS:** Use HTTPS for web interface, WSS for WebSockets

---

## Quick Setup Script

Here's a complete setup script you can run:

```bash
#!/bin/bash
# Quick FreeScout Startup Setup Script

set -e

echo "Installing Supervisor..."
sudo apt-get update
sudo apt-get install -y supervisor

echo "Creating Supervisor configurations..."
sudo tee /etc/supervisor/conf.d/freescout-worker.conf > /dev/null <<'EOF'
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

sudo tee /etc/supervisor/conf.d/freescout-reverb.conf > /dev/null <<'EOF'
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

echo "Setting up permissions..."
sudo chown -R www-data:www-data /var/www/html/storage
sudo chown -R www-data:www-data /var/www/html/bootstrap/cache
sudo chmod -R 775 /var/www/html/storage
sudo chmod -R 775 /var/www/html/bootstrap/cache

echo "Enabling and starting Supervisor..."
sudo systemctl enable supervisor
sudo systemctl start supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start freescout-worker:*
sudo supervisorctl start freescout-reverb

echo "Setting up cron jobs..."
sudo crontab -l -u www-data 2>/dev/null | { cat; echo "* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1"; } | sudo crontab -u www-data -

echo "Checking status..."
sudo supervisorctl status

echo ""
echo "✅ Setup complete!"
echo ""
echo "Services are now configured to start automatically at boot."
echo ""
echo "Useful commands:"
echo "  sudo supervisorctl status                 - Check service status"
echo "  sudo supervisorctl restart all            - Restart all services"
echo "  sudo tail -f /var/www/html/storage/logs/worker.log  - View worker logs"
echo "  sudo tail -f /var/www/html/storage/logs/reverb.log  - View Reverb logs"
```

Save this as `setup-startup.sh` and run:

```bash
chmod +x setup-startup.sh
./setup-startup.sh
```

---

## Summary

**Recommended Approach:** Use **Supervisor** (Option 1) for simplicity and reliability.

**What starts automatically after setup:**
- ✅ Queue workers (2 processes)
- ✅ Laravel Reverb WebSocket server
- ✅ Scheduled tasks (via cron)
- ✅ All services restart on failure
- ✅ All services start on system boot

**Next Steps:**
1. Choose your preferred option (Supervisor recommended)
2. Run the setup commands
3. Test with `sudo reboot`
4. Monitor logs to ensure everything works
