# FreeScout Development Reference

This guide consolidates development commands and implementation patterns for FreeScout.

## Quick Links
- [Module Management](#module-management)
- [Frontend Development](#frontend-development)
- [Database Management](#database-management)
- [Testing](#testing)
- [Feature Implementation](#feature-implementation)

---

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

---

## Feature Implementation Patterns

## Date: November 6, 2025

## Features Implemented

### 1. Mailbox Permissions
**Purpose:** Allow administrators to assign granular access levels to users for specific mailboxes

**Access Levels:**
- `10` - View Only: Can see conversations but not reply
- `20` - View & Reply: Can see and respond to conversations
- `30` - Full Admin: Can manage mailbox settings

**Routes:**
- `GET /mailboxes/{mailbox}/permissions` - View permissions page
- `POST /mailboxes/{mailbox}/permissions` - Update permissions

**Controller Methods:**
- `MailboxController::permissions()`
- `MailboxController::updatePermissions()`

**Files:**
- View: `resources/views/mailboxes/permissions.blade.php`
- Policy: `app/Policies/MailboxPolicy.php` (updated)

---

### 2. Mailbox Connection Settings
**Purpose:** Configure IMAP (incoming) and SMTP (outgoing) email connection settings

**Features:**
- Protocol selection (IMAP/POP3)
- Server, port, encryption settings
- Username/password (encrypted)
- Custom "From Name" support

**Routes:**
- `GET /mailbox/{mailbox}/connection/incoming` - Incoming settings
- `POST /mailbox/{mailbox}/connection/incoming` - Save incoming
- `GET /mailbox/{mailbox}/connection/outgoing` - Outgoing settings
- `POST /mailbox/{mailbox}/connection/outgoing` - Save outgoing

**Controller Methods:**
- `MailboxController::connectionIncoming()`
- `MailboxController::saveConnectionIncoming()`
- `MailboxController::connectionOutgoing()`
- `MailboxController::saveConnectionOutgoing()`

**Files:**
- Views: `resources/views/mailboxes/connection_incoming.blade.php`, `connection_outgoing.blade.php`

**Data Transformations:**
```php
// Protocol: 'imap' → 1, 'pop3' → 2
// Method: 'smtp' → 3, 'mail' → 1
// Encryption: 'none' → 0, 'ssl' → 1, 'tls' → 2
```

---

### 3. Auto-Reply Configuration
**Purpose:** Automatically respond to incoming emails with a custom message

**Features:**
- Enable/disable auto-reply
- Custom subject with variables ({%subject%}, {%mailbox_name%})
- Custom message body with variables
- Optional BCC recipient

**Routes:**
- `GET /mailboxes/{mailbox}/auto-reply` - View settings
- `POST /mailboxes/{mailbox}/auto-reply` - Save settings

**Controller Methods:**
- `MailboxController::autoReply()`
- `MailboxController::saveAutoReply()`

**Files:**
- View: `resources/views/mailboxes/auto_reply.blade.php`

**Available Variables:**
- Subject: `{%subject%}`, `{%mailbox_name%}`
- Message: `{%customer_name%}`, `{%mailbox_name%}`

---

### 4. Conversation Cloning
**Purpose:** Create a duplicate conversation from an existing thread

**Features:**
- Clones conversation with all attributes
- Copies thread content
- Duplicates attachments
- Maintains original customer and assignee

**Route:**
- `GET /mailbox/{mailbox}/clone-ticket/{thread}` - Clone conversation

**Controller Method:**
- `ConversationController::clone()`

**Model Update:**
- Added `Conversation::updateFolder()` method for automatic folder assignment

---

### 5. Customer Merging (Already Existed)
**Purpose:** Merge duplicate customer records

**Route:**
- `POST /customers/merge` - Merge customers

**Controller Method:**
- `CustomerController::merge()`

**Parameters:**
```json
{
    "source_id": 123,  // Customer to merge from
    "target_id": 456   // Customer to keep
}
```

---

## Features Marked as Unnecessary

### OAuth Integration ⚠️
**Reason:** Better implemented as a separate package. Not following Laravel 11 best practices for authentication.

### Translation Management UI ⚠️
**Reason:** Modern approach uses external services (Crowdin, Lokalise) or version control.

### Email Open Tracking ⚠️
**Reason:** Requires careful GDPR compliance. Should be optional module.

### System Tools Page ⚠️
**Reason:** Should be Artisan commands, not web UI (Laravel 11 best practice).

### Unauthenticated Attachments 🔒
**Reason:** Security vulnerability. Use signed URLs if temporary public access needed.

### User Setup via Hash Link ⚠️
**Reason:** Laravel Breeze provides modern registration/invite flows.

### Separate Chats View ⚠️
**Reason:** Can filter by conversation type. Unified interface preferred.

### Undo Reply ⚠️
**Reason:** Too complex, emails cannot be truly "unsent" from recipients.

---

## Testing Recommendations

1. **Permissions:** Test with users having different access levels (10, 20, 30)
2. **Connection Settings:** Verify data type transformations work correctly
3. **Auto-Reply:** Test variable substitution in subject and message
4. **Cloning:** Test with conversations that have attachments
5. **Merging:** Test with customers having overlapping emails

---

## Security Notes

- ✅ All passwords encrypted before storage
- ✅ Authentication required for all features
- ✅ Authorization checks via policies
- ✅ Input validation on all forms
- ✅ No public attachment access (security risk avoided)

---

## Navigation

Access these features from:
- **Mailbox Settings** → Permissions, Connection Settings, Auto Reply
- **Conversation View** → Clone ticket action
- **Customer Profile** → Merge button

---

## Files Modified

**Controllers:**
- `app/Http/Controllers/MailboxController.php` (+6 methods)
- `app/Http/Controllers/ConversationController.php` (+1 method)

**Models:**
- `app/Models/Conversation.php` (+1 method)

**Policies:**
- `app/Policies/MailboxPolicy.php` (updated with granular permissions)

**Routes:**
- `routes/web.php` (+8 routes)

**Views Created:**
- `resources/views/mailboxes/permissions.blade.php`
- `resources/views/mailboxes/auto_reply.blade.php`
- `resources/views/mailboxes/connection_incoming.blade.php`
- `resources/views/mailboxes/connection_outgoing.blade.php`

**Views Modified:**
- `resources/views/mailboxes/_partials/settings_nav.blade.php`

---

## Documentation Updated

- `docs/FEATURE_PARITY_ANALYSIS.md` - Complete analysis and implementation summary
