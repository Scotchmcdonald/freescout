# Production Deployment Guide

## Pre-Deployment Checklist

### 1. Environment Configuration

**Required Environment Variables:**
```bash
# Application
APP_NAME="FreeScout"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=freescout
DB_USERNAME=freescout_user
DB_PASSWORD=your_secure_password

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# IMAP Configuration
IMAP_HOST=imap.gmail.com
IMAP_PORT=993
IMAP_ENCRYPTION=ssl
IMAP_USERNAME=your-email@gmail.com
IMAP_PASSWORD=your-app-password

# Queue
QUEUE_CONNECTION=database

# Broadcasting (Reverb)
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

### 2. Server Requirements

- **PHP**: 8.2 or higher
- **Composer**: 2.x
- **Node.js**: 18.x or higher
- **MySQL**: 8.0 or MariaDB 10.6+
- **Web Server**: Nginx or Apache
- **Process Manager**: Supervisor (for queue workers)
- **SSL Certificate**: Required for production

### 3. PHP Extensions Required

**Production Extensions:**
```bash
# Check installed extensions
php -m | grep -E 'bcmath|ctype|fileinfo|json|mbstring|openssl|pdo_mysql|tokenizer|xml|imap|zip|gd|curl|intl'
```

**Development/Testing Extensions (Optional but Recommended):**
```bash
# For faster test suite execution with in-memory database
php -m | grep -i sqlite

# If not installed (Ubuntu/Debian):
sudo apt-get install -y php8.3-sqlite3  # Replace 8.3 with your PHP version

# Verify installation
php -m | grep -i sqlite
# Should show: pdo_sqlite, sqlite3
```

**Why SQLite for Testing?**
- **10-20x faster test execution** (in-memory database vs MySQL)
- First test in each suite: ~5 seconds (MySQL) → ~0.5 seconds (SQLite)
- Total suite runtime: ~40 seconds → ~5-10 seconds
- Production still uses MySQL - SQLite is only for testing

## Deployment Steps

### Step 1: Clone Repository

```bash
cd /var/www
git clone https://github.com/your-org/freescout.git html
cd html
```

### Step 2: Install Dependencies

```bash
# PHP dependencies
composer install --optimize-autoloader --no-dev

# Node dependencies
npm ci

# Build production assets
npm run build
```

### Step 3: Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Edit with your production values
nano .env

# Generate application key
php artisan key:generate
```

### Step 4: Set Permissions

```bash
# Set ownership
chown -R www-data:www-data /var/www/html

# Set permissions
chmod -R 755 /var/www/html
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache
```

### Step 5: Database Setup

```bash
# Run migrations
php artisan migrate --force

# Seed initial data (if needed)
php artisan db:seed --force
```

### Step 6: Optimize Application

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

### Step 7: Configure Queue Worker

Create supervisor configuration at `/etc/supervisor/conf.d/freescout-worker.conf`:

```ini
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
```

Start supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start freescout-worker:*
```

### Step 8: Configure Laravel Reverb (WebSocket Server)

Create supervisor configuration at `/etc/supervisor/conf.d/freescout-reverb.conf`:

```ini
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
```

Start Reverb:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start freescout-reverb
```

### Step 9: Configure Cron Jobs

Add to crontab (`sudo crontab -e -u www-data`):

```bash
# Laravel Scheduler
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1

# Fetch Emails (every 5 minutes)
*/5 * * * * cd /var/www/html && php artisan freescout:fetch-emails >> /dev/null 2>&1
```

### Step 10: Configure Web Server

#### Nginx Configuration

Create `/etc/nginx/sites-available/freescout`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com;
    root /var/www/html/public;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # WebSocket proxy for Reverb
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Increase upload size
    client_max_body_size 100M;
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/freescout /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## Post-Deployment Verification

### 1. Health Checks

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo()

# Test queue workers
php artisan queue:monitor
sudo supervisorctl status freescout-worker:*

# Test Reverb
sudo supervisorctl status freescout-reverb
curl http://localhost:8080
```

### 2. Functionality Tests

- [ ] User login
- [ ] Email fetching (check logs)
- [ ] Sending emails
- [ ] Creating conversations
- [ ] Replying to conversations
- [ ] Auto-replies
- [ ] Real-time notifications
- [ ] File uploads
- [ ] Search functionality
- [ ] User management
- [ ] Module system

### 3. Performance Tests

```bash
# Check response times
curl -w "@curl-format.txt" -o /dev/null -s https://your-domain.com

# Monitor queue processing
php artisan queue:monitor

# Check logs
tail -f storage/logs/laravel.log
```

### 4. Security Checks

- [ ] SSL certificate valid
- [ ] HTTPS redirect working
- [ ] Security headers present
- [ ] Database credentials secure
- [ ] File permissions correct
- [ ] Debug mode OFF
- [ ] Error reporting OFF

## Monitoring & Maintenance

### Log Files

```bash
# Application logs
tail -f storage/logs/laravel.log

# Queue worker logs
tail -f storage/logs/worker.log

# Reverb logs
tail -f storage/logs/reverb.log

# Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

### Backup Strategy

**Daily Backups:**
```bash
#!/bin/bash
# /usr/local/bin/backup-freescout.sh

BACKUP_DIR="/backups/freescout"
DATE=$(date +%Y%m%d_%H%M%S)

# Backup database
mysqldump -u freescout_user -p'password' freescout > "$BACKUP_DIR/db_$DATE.sql"

# Backup files
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" /var/www/html/storage/app

# Keep only last 30 days
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

Add to cron:
```bash
0 2 * * * /usr/local/bin/backup-freescout.sh
```

### Updates & Maintenance

**Updating the Application:**
```bash
# Pull latest code
git pull origin main

# Install dependencies
composer install --optimize-autoloader --no-dev
npm ci && npm run build

# Run migrations
php artisan migrate --force

# Clear and cache
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo supervisorctl restart freescout-worker:*
sudo supervisorctl restart freescout-reverb
```

## Troubleshooting

### Common Issues

**Queue not processing:**
```bash
sudo supervisorctl restart freescout-worker:*
php artisan queue:restart
```

**WebSocket not connecting:**
```bash
sudo supervisorctl restart freescout-reverb
# Check firewall
sudo ufw allow 8080/tcp
```

**Email not sending:**
```bash
php artisan tinker
>>> Mail::raw('Test', function($msg) { $msg->to('test@example.com')->subject('Test'); })
```

**Permissions errors:**
```bash
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
sudo chmod -R 775 /var/www/html/storage
```

## Rollback Plan

In case of critical issues:

```bash
# 1. Stop services
sudo supervisorctl stop freescout-worker:*
sudo supervisorctl stop freescout-reverb

# 2. Restore database
mysql -u freescout_user -p'password' freescout < /backups/freescout/db_YYYYMMDD.sql

# 3. Restore files
tar -xzf /backups/freescout/files_YYYYMMDD.tar.gz -C /

# 4. Clear caches
php artisan config:clear
php artisan cache:clear

# 5. Restart services
sudo supervisorctl start freescout-worker:*
sudo supervisorctl start freescout-reverb
```

## Support & Documentation

- **GitHub Issues**: https://github.com/your-org/freescout/issues
- **Documentation**: https://github.com/your-org/freescout/wiki
- **Laravel Docs**: https://laravel.com/docs/11.x

---

**Deployment Date**: ____________________  
**Deployed By**: ____________________  
**Version**: ____________________
