# Legacy FreeScout Code (Laravel 5.5)

This directory contains the original Laravel 5.5 codebase for reference only.

**⚠️ DO NOT USE THIS CODE FOR DEVELOPMENT ⚠️**

## What's Here
- Laravel 5.5.40 application
- PHP 7.1+ compatible code
- 269 override files in `overrides/`
- Legacy dependencies in `vendor/`
- Original composer.json saved as `composer.json.legacy`
- Original composer.lock saved as `composer.lock.legacy`

## Modern Version
The modernized Laravel 11 version is in the repository root (`../`).

## Why Archived?
- **269 override files** made incremental upgrades impossible
- **PHP 7.1 and Laravel 5.5 are EOL** (security risk)
- **Autoloader hijacking** via regex manipulation in post-install scripts
- Modern version built with clean architecture and Laravel 11 best practices

## Reference Usage
When porting functionality, refer to these files to understand:
- Business logic implementation
- Database schema (see `database/migrations/`)
- Email handling (see `app/Mail/` and `overrides/swiftmailer/`)
- Authentication flow (see `app/Http/Controllers/Auth/`)
- IMAP functionality (see `overrides/webklex/`)
- Module system (see `Modules/` and `app/Module.php`)

## Key Files for Reference
- `app/` - Application business logic
- `database/migrations/` - Database schema
- `routes/web.php` - Web routes
- `routes/channels.php` - Broadcast channels
- `config/` - Configuration files
- `overrides/` - Custom vendor file modifications
- `Modules/` - Module system

## Documentation
See the root directory for comprehensive modernization documentation:
- `UPGRADE_PLAN.md` - Upgrade strategy analysis
- `IMPLEMENTATION_ROADMAP.md` - 13-week execution plan
- `MIGRATION_GUIDE.md` - Technical migration guide
- `OVERRIDES_ANALYSIS.md` - Complete inventory of overrides
- `DEPENDENCY_STRATEGY.md` - Package upgrade strategy

---

**Archived**: November 2025
**Original Version**: FreeScout 1.8.195
**Framework**: Laravel 5.5.40
**PHP Requirement**: >=7.1.0
