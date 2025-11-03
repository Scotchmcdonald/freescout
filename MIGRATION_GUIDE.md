# Laravel 11 Migration Guide for FreeScout

## Overview

This guide provides step-by-step instructions for migrating FreeScout from Laravel 5.5 to Laravel 11, including PHP 8.2+ compatibility and elimination of the overrides system.

## Prerequisites

- PHP 8.2 or higher
- Composer 2.x
- MySQL 8.0+ or MariaDB 10.4+
- Node.js 20+ LTS
- Git

## Migration Path

Due to the significant version jump (5.5 → 11.0), we have two approaches:

### Approach 1: Incremental Upgrade (Safer, Slower)
Follow Laravel's upgrade path through each major version:
5.5 → 5.6 → 5.7 → 5.8 → 6.0 → 7.0 → 8.0 → 9.0 → 10.0 → 11.0

**Pros**: Less risk of breaking changes, easier to debug
**Cons**: Time-consuming (2-3 months), must deal with deprecated features at each step
**Recommended for**: Production applications with complex customizations

### Approach 2: Fresh Start (Faster, Higher Risk)
Create a new Laravel 11 application and port the FreeScout code:

**Pros**: Clean codebase, modern structure, no legacy code
**Cons**: More initial work, higher risk of missing functionality
**Recommended for**: This project (due to extensive overrides making incremental upgrade nearly impossible)

## Recommended Approach: Fresh Start

Given the 269 override files and complex autoloading setup, we recommend:

1. Create a new Laravel 11 application
2. Port FreeScout's application code
3. Migrate database structure
4. Update dependencies
5. Test thoroughly

## Step-by-Step Migration

### Phase 1: Setup New Laravel 11 Application

```bash
# Create new Laravel 11 app
composer create-project laravel/laravel freescout-modern "11.*"
cd freescout-modern

# Copy FreeScout's .env.example and configure
cp ../freescout/.env.example .env.example
php artisan key:generate
```

### Phase 2: Port Application Structure

#### 2.1 Copy Application Code
```bash
# Copy app directory (excluding overrides logic)
cp -r ../freescout/app/* ./app/

# Copy routes
cp -r ../freescout/routes/* ./routes/

# Copy config files (will need updating)
cp -r ../freescout/config/* ./config/

# Copy database migrations
cp -r ../freescout/database/* ./database/

# Copy resources
cp -r ../freescout/resources/* ./resources/

# Copy public assets
cp -r ../freescout/public/* ./public/

# Copy tests
cp -r ../freescout/tests/* ./tests/
```

#### 2.2 Copy Modules System
```bash
# Copy Modules directory
cp -r ../freescout/Modules ./
```

### Phase 3: Update Composer Dependencies

Create a modern composer.json:

```json
{
    "name": "freescout-helpdesk/freescout",
    "description": "Free self-hosted helpdesk and shared mailbox",
    "keywords": ["helpdesk", "help desk", "shared mailbox"],
    "license": "AGPL-3.0-or-later",
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "laravel/tinker": "^2.9",
        
        "doctrine/dbal": "^3.8",
        "egulias/email-validator": "^4.0",
        "enshrined/svg-sanitize": "^0.20",
        "html2text/html2text": "^4.3",
        "mews/purifier": "^3.4",
        "nwidart/laravel-modules": "^11.0",
        "ramsey/uuid": "^4.7",
        "spatie/laravel-activitylog": "^4.8",
        "tormjens/eventy": "^0.8",
        "webklex/php-imap": "^5.3",
        
        "barryvdh/laravel-translation-manager": "^0.6",
        "opcodesio/log-viewer": "^3.0",
        "livewire/livewire": "^3.0",
        "symfony/polyfill-mbstring": "^1.29"
    },
    "require-dev": {
        "barryvdh/laravel-debugbar": "^3.13",
        "fakerphp/faker": "^1.23",
        "laravel/pint": "^1.13",
        "larastan/larastan": "^2.9",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.0",
        "phpunit/phpunit": "^11.0"
    }
}
```

**Key Changes**:
- Removed all overridden packages
- Updated to modern versions
- Replaced abandoned packages:
  - `fzaninotto/faker` → `fakerphp/faker`
  - `rap2hpoutre/laravel-log-viewer` → `opcodesio/log-viewer`
  - `chumper/zipper` → Use Laravel's Filesystem
  - `lord/laroute` → `tightenco/ziggy` (for JS routes)
  - `devfactory/minify` → Use Vite

### Phase 4: Update Configuration Files

#### 4.1 Update config/app.php
```php
<?php

return [
    'name' => env('APP_NAME', 'FreeScout'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
    ],
    
    // ... service providers
];
```

#### 4.2 Update config/database.php
Laravel 11 uses modern database configuration:

```php
'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'url' => env('DB_URL'),
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'freescout'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'unix_socket' => env('DB_SOCKET', ''),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
        'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
        'prefix' => env('DB_PREFIX', ''),
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => env('DB_ENGINE', 'InnoDB'),
        'options' => extension_loaded('pdo_mysql') ? array_filter([
            PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        ]) : [],
    ],
],
```

### Phase 5: Update Application Code

#### 5.1 Update Models

**Old (Laravel 5.5)**:
```php
class User extends Model
{
    protected $fillable = ['name', 'email', 'password'];
}
```

**New (Laravel 11)**:
```php
class User extends Model
{
    protected $fillable = ['name', 'email', 'password'];
    
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
```

#### 5.2 Update Controllers

**Old (Laravel 5.5)**:
```php
class TicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = Ticket::all();
        return view('tickets.index', compact('tickets'));
    }
}
```

**New (Laravel 11)**:
```php
class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $tickets = Ticket::all();
        return view('tickets.index', compact('tickets'));
    }
}
```

#### 5.3 Update Routes

Laravel 11 uses a streamlined routing structure:

**routes/web.php**:
```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::middleware(['auth'])->group(function () {
    Route::resource('tickets', TicketController::class);
    Route::resource('conversations', ConversationController::class);
});
```

### Phase 6: Refactor Override Logic

For each override, implement using modern Laravel patterns:

#### Example: Custom Model Behavior
**Old**: Override `Illuminate\Database\Eloquent\Model.php`
```php
// overrides/laravel/framework/src/Illuminate/Database/Eloquent/Model.php
public function customMethod() {
    // Custom logic
}
```

**New**: Use Model Boot or Macro
```php
// app/Providers/AppServiceProvider.php
use Illuminate\Database\Eloquent\Model;

public function boot(): void
{
    Model::preventLazyLoading(! app()->isProduction());
    
    // Add custom method via macro
    Model::macro('customMethod', function () {
        return 'custom logic';
    });
}
```

#### Example: Custom Request Handling
**Old**: Override `Illuminate\Http\Request.php`

**New**: Use Middleware
```php
// app/Http/Middleware/CustomRequest.php
class CustomRequestMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Custom logic here
        
        return $next($request);
    }
}
```

#### Example: Custom Mail Transport
**Old**: Override `Illuminate\Mail\TransportManager.php`

**New**: Register Custom Driver
```php
// app/Providers/AppServiceProvider.php
use Illuminate\Mail\MailManager;

public function boot(): void
{
    Mail::extend('custom', function (array $config) {
        return new CustomTransport($config);
    });
}
```

### Phase 7: Update Middleware

Laravel 11 has streamlined middleware:

```php
// bootstrap/app.php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\CustomMiddleware::class,
        ]);
        
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->create();
```

### Phase 8: Update Database Migrations

Ensure migrations use modern syntax:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('body');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
```

### Phase 9: Testing

#### 9.1 Update PHPUnit Configuration
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         bootstrap="vendor/autoload.php"
         colors="true"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/11.0/phpunit.xsd">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
</phpunit>
```

#### 9.2 Run Tests
```bash
php artisan test
```

### Phase 10: Asset Compilation

Replace Webpack Mix with Vite:

**vite.config.js**:
```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
```

Update package.json:
```json
{
    "scripts": {
        "dev": "vite",
        "build": "vite build"
    },
    "devDependencies": {
        "vite": "^5.0",
        "laravel-vite-plugin": "^1.0"
    }
}
```

### Phase 11: Code Quality Tools

#### Add Laravel Pint
```bash
composer require laravel/pint --dev

# Run formatting
./vendor/bin/pint
```

#### Add Larastan
```bash
composer require larastan/larastan --dev

# Create phpstan.neon
cat > phpstan.neon << 'EOF'
includes:
    - ./vendor/larastan/larastan/extension.neon

parameters:
    level: 6
    paths:
        - app
EOF

# Run analysis
./vendor/bin/phpstan analyse
```

## Troubleshooting

### Common Issues

#### 1. Namespace Changes
Laravel 11 uses proper namespacing. Update imports:
```php
// Old
use App\User;

// New
use App\Models\User;
```

#### 2. Deprecated Methods
Many helper methods have been removed. Use facades:
```php
// Old
$request->session()->get('key');

// New
session('key');
// or
use Illuminate\Support\Facades\Session;
Session::get('key');
```

#### 3. Database Connection
Update .env for new database options:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=freescout
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

## Deployment Checklist

- [ ] Update server PHP to 8.2+
- [ ] Update Composer to 2.x
- [ ] Run composer install
- [ ] Run migrations: `php artisan migrate`
- [ ] Clear caches: `php artisan optimize:clear`
- [ ] Build assets: `npm run build`
- [ ] Set permissions on storage/
- [ ] Update supervisor config for queues
- [ ] Test all critical functionality
- [ ] Monitor error logs

## Rollback Plan

1. Keep old version running in parallel
2. Use database snapshots before migration
3. Keep rollback scripts ready
4. Test rollback procedure before production

## Resources

- [Laravel 11 Documentation](https://laravel.com/docs/11.x)
- [Laravel 11 Upgrade Guide](https://laravel.com/docs/11.x/upgrade)
- [PHP 8.2 Migration Guide](https://www.php.net/manual/en/migration82.php)
- [Larastan Documentation](https://github.com/larastan/larastan)
- [Laravel Pint Documentation](https://laravel.com/docs/11.x/pint)
