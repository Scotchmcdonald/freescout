# FreeScout Quick Reference

## Development Commands

### Module Management
```bash
# Create a new module
php artisan module:make ModuleName

# List all modules
php artisan module:list

# Enable a module
php artisan module:enable ModuleName

# Disable a module
php artisan module:disable ModuleName

# Run module migrations
php artisan module:migrate ModuleName

# Seed module data
php artisan module:seed ModuleName
```

### Frontend Development
```bash
# Development server with HMR
npm run dev

# Production build with code splitting
npm run build

# Run frontend tests
npm test

# Run tests with UI
npm run test:ui

# Generate coverage report
npm run test:coverage
```

### Backend Testing
```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter ConversationTest

# Run with coverage
php artisan test --coverage
```

### Cache Management
```bash
# Clear all caches
php artisan optimize:clear

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Clear specific cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Queue Management
```bash
# Start queue worker
php artisan queue:work

# Monitor queue
php artisan queue:monitor

# Restart queue workers
php artisan queue:restart

# Clear failed jobs
php artisan queue:flush
```

### Email Management
```bash
# Fetch emails manually
php artisan freescout:fetch-emails

# Test SMTP connection
php artisan tinker
>>> Mail::raw('Test', fn($msg) => $msg->to('test@example.com')->subject('Test'))
```

### Real-Time (Reverb)
```bash
# Start Reverb server
php artisan reverb:start

# Start with specific host/port
php artisan reverb:start --host=0.0.0.0 --port=8080
```

### Code Quality
```bash
# Run PHP CS Fixer (Pint)
./vendor/bin/pint

# Run Larastan (PHPStan)
./vendor/bin/phpstan analyse
```

## File Locations

### Configuration
- **Environment**: `.env`
- **Modules**: `config/modules.php`
- **Broadcasting**: `config/broadcasting.php`
- **Mail**: `config/mail.php`

### Application
- **Controllers**: `app/Http/Controllers/`
- **Models**: `app/Models/`
- **Services**: `app/Services/`
- **Events**: `app/Events/`
- **Jobs**: `app/Jobs/`

### Frontend
- **JavaScript**: `resources/js/`
- **CSS**: `resources/css/`
- **Views**: `resources/views/`
- **Compiled Assets**: `public/build/`

### Modules
- **Module Directory**: `Modules/`
- **Module Status**: `modules_statuses.json`

### Testing
- **PHPUnit Tests**: `tests/Feature/`, `tests/Unit/`
- **Vitest Tests**: `tests/javascript/`
- **Test Setup**: `tests/setup.js`

### Documentation
- **Progress**: `docs/PROGRESS.md`
- **Deployment**: `docs/DEPLOYMENT.md`
- **Session Summary**: `docs/SESSION_SUMMARY.md`
- **Frontend Guide**: `docs/FRONTEND_MODERNIZATION.md`

### Logs
- **Application**: `storage/logs/laravel.log`
- **Queue Worker**: `storage/logs/worker.log` (if configured)
- **Reverb**: `storage/logs/reverb.log` (if configured)

## Key URLs (Local Development)

- **Application**: http://localhost
- **Reverb WebSocket**: http://localhost:8080
- **Module Management**: http://localhost/modules
- **System Logs**: http://localhost/system/logs
- **User Management**: http://localhost/users

## Common Tasks

### Add a New Module Feature
1. Create route in `Modules/YourModule/routes/web.php`
2. Create controller in `Modules/YourModule/app/Http/Controllers/`
3. Create view in `Modules/YourModule/resources/views/`
4. Register service provider in `module.json`

### Deploy Updates
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
sudo supervisorctl restart freescout-worker:*
sudo supervisorctl restart freescout-reverb
```

### Troubleshoot Issues
```bash
# Check logs
tail -f storage/logs/laravel.log

# Check queue status
php artisan queue:monitor

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo()

# Clear everything
php artisan optimize:clear
composer dump-autoload

# Restart services
sudo supervisorctl restart all
```

## Environment Variables Cheat Sheet

### Critical Production Variables
```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_DATABASE=freescout

QUEUE_CONNECTION=database

REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=

MAIL_MAILER=smtp
IMAP_HOST=imap.gmail.com
```

## Performance Tips

1. **Always cache in production**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

2. **Monitor queue processing**:
   ```bash
   php artisan queue:monitor
   ```

3. **Use code splitting** - Heavy libraries load on demand

4. **Monitor logs regularly**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

5. **Keep dependencies updated**:
   ```bash
   composer update
   npm update
   ```

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong `APP_KEY` generated
- [ ] Database credentials secure
- [ ] File permissions correct (755/775)
- [ ] SSL certificate valid
- [ ] Security headers configured
- [ ] CSRF protection enabled
- [ ] Rate limiting configured
- [ ] Regular backups scheduled

## Backup Commands

```bash
# Backup database
mysqldump -u user -p database > backup_$(date +%Y%m%d).sql

# Backup files
tar -czf backup_files_$(date +%Y%m%d).tar.gz storage/app

# Restore database
mysql -u user -p database < backup_YYYYMMDD.sql

# Restore files
tar -xzf backup_files_YYYYMMDD.tar.gz -C /
```
