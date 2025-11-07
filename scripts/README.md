# Development Scripts

This directory contains utility scripts for setting up and maintaining the FreeScout development environment.

## Available Scripts

### `setup-dev-environment.sh`

Automated setup script for development and testing dependencies.

**What it does:**
- Detects PHP version automatically
- Installs required PHP extensions:
  - `sqlite3` - For 10-20x faster test execution
  - `imap` - For email fetching functionality
  - `bcmath`, `curl`, `gd`, `intl`, `mbstring`, `mysql`, `xml`, `zip` - Core requirements
- Checks for Composer and Node.js installation
- Verifies all critical extensions

**Usage:**
```bash
# Run from project root
./scripts/setup-dev-environment.sh
```

**Benefits:**
- **Faster Tests**: SQLite in-memory database reduces test suite runtime from ~40s to ~5-10s
- **Consistent Setup**: Ensures all team members have the same dev environment
- **Time Savings**: Automates manual installation steps

**Requirements:**
- Ubuntu/Debian-based system
- sudo privileges
- PHP 8.2+ already installed

## After Running Setup

Follow these steps to complete your development environment:

```bash
# 1. Install PHP dependencies
composer install

# 2. Install frontend dependencies
npm install

# 3. Copy environment file
cp .env.example .env

# 4. Configure your .env file
nano .env

# 5. Generate application key
php artisan key:generate

# 6. Run migrations
php artisan migrate

# 7. Build frontend assets
npm run dev

# 8. Run tests (fast with SQLite)
php artisan test

# Or exclude slow IMAP integration tests
php artisan test --exclude-group=slow
```

## Test Performance Comparison

| Configuration | First Test | Full Suite | Improvement |
|--------------|-----------|------------|-------------|
| **MySQL** | 5.00s | ~40s | Baseline |
| **SQLite (in-memory)** | 0.12s | ~5-10s | **41x / 4-8x faster** |

## Notes

- **Production uses MySQL** - SQLite is only for testing
- **Migrations are compatible** with both databases
- **No production impact** - all changes are test-only optimizations
