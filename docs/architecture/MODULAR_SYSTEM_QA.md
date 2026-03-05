# Modular Architecture — Architect's Q&A
**Audience:** Incoming architects  
**Last Updated:** March 2, 2026

This document answers the five onboarding question sets directly, with concrete references to the actual code.

---

## 1. The Modular Blueprint

### How is modularity enforced?

**`nwidart/laravel-modules` v12**, not a custom namespace implementation.

The root `composer.json` registers the `Modules\` namespace under `autoload.psr-4`:

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Modules\\": "Modules/"
    }
}
```

The `config/modules.php` file configures nwidart with auto-discovery enabled from `base_path('Modules')`. Module enable/disable state is persisted in [`modules_statuses.json`](../../modules_statuses.json). The `wikimedia/composer-merge-plugin` is also used so that each module can carry its own `composer.json` if it needs local dependencies.

### What defines a "Module"?

**Domain-driven**, not infrastructure-driven. Each module owns a cohesive business domain. For the full module listing with component inventories, see **[APP_OVERVIEW.md](APP_OVERVIEW.md)**.

There is no `Api`, `Admin`, or `Web` infrastructure module. Routing concerns (API vs web, auth middleware) are handled within each domain module's `routes.php` and service provider.

### The Shared Core — what lives in `app/`?

The `app/` directory is the **FreeScout core** — the helpdesk foundation that all modules build on. The rule:

> **Anything that the ticket/conversation/customer/mailbox workflow needs to function with zero modules enabled belongs in `app/`.** Everything else belongs in a module.

Specifically, `app/` owns:
- **Models**: `Conversation`, `Thread`, `Customer`, `User`, `Mailbox`, `Folder`, `Attachment`, `Permission`, `Role`, `Subscription`
- **Events**: All domain events that modules may *listen to* (`ConversationStatusChanged`, `UserReplied`, `NewMessageReceived`, etc.)
- **Contracts / Interfaces**: Interfaces that core services expose for modules to implement (e.g., `app/Contracts/BillingTemplateInterface.php`, `app/Contracts/EntitlementResolver.php`)
- **Services**: IMAP, SMTP, Navigation, UserDirectoryRegistry
- **Routes**: Core web and API routes (`routes/web.php`, `routes/api.php`)

Modules may **not** be imported by core. The dependency direction is strictly: **Module → Core, never Core → Module**. See the "Core Blindness" section in [`ARCHITECTURE_OVERVIEW.md`](ARCHITECTURE_OVERVIEW.md).

---

## 2. Communication & Dependencies

### Internal Dependencies — can Module A call Module B directly?

**Yes, but it is discouraged and must follow the "Core Blindness" rule.**

The enforced pattern is:

| Pattern | Allowed? | How |
|---|---|---|
| Feature module reads a core Model | ✅ Yes | Direct import (`use App\Models\Conversation`) |
| Feature module extends a core Contract | ✅ Yes | Implement `app/Contracts/*Interface` |
| Feature module calls another feature module's Service | ⚠️ Guarded | Only via `class_exists()` check and runtime resolution |
| Core imports a module class | ❌ Never | Violates Core Blindness |

In practice, cross-module calls use defensive guards:

```php
// In CrmServiceProvider — calling ClientPortal from Crm
$registryClass = "Modules\\ClientPortal\\Services\\PortalTabRegistry";
if (!class_exists($registryClass)) {
    return;
}
$tabRegistry = app($registryClass);
```

Feature modules that *depend on* another module declare it in `module.json`:

```json
// Modules/PIB/module.json
"requires": ["Crm"]
```

There is **no formal Bridge/Contract layer** between modules today. The `app/Contracts/` directory serves as the Contract layer between core and modules, but direct module-to-module contracts are not yet systematically enforced.

### Event-Driven or Direct?

**Both**, with a clear preference for events.

**Laravel Events** (`Illuminate\Support\Facades\Event`) are used for domain state changes — modules register listeners in their own `ServiceProvider::boot()`:

```php
// CrmServiceProvider registers listeners for core events
Event::listen(
    ConversationStatusChanged::class,
    [ConversationEventListener::class, 'handleStatusChanged']
);
```

**Eventy** (`tormjens/eventy`) is a WordPress-style action/filter hook library used for **UI composition** and **contextual data injection** — analogous to WordPress's `add_action()` / `add_filter()`. Modules use this to inject menu items, JavaScript, settings sections, and search filters into the core UI without the core knowing about them:

```php
// CrmServiceProvider hooks into core UI slots
\Eventy::addFilter('settings.sections', function($sections) {
    $sections['customer-fields'] = ['title' => __('Customer Fields'), ...];
    return $sections;
});

\Eventy::addAction('menu.manage.after_mailboxes', function($mailbox) {
    echo View::make('crm::partials/menu')->render();
});
```

Eventy calls are the **primary mechanism** for module visibility in the UI. Direct cross-module method calls exist but are minimized.

### Database Isolation

**No isolation — single shared schema, single connection.**

All modules write to the same database. There are no table prefixes, connection partitions, or schema boundaries between modules. The logical boundary is enforced by code convention (a model in `Modules\PIB` owns its tables) and documented in the Data Ownership table in [`ARCHITECTURE_OVERVIEW.md`](ARCHITECTURE_OVERVIEW.md).

The trade-off acknowledged in that document: the `clients` table (owned by CRM) was at one point receiving financial columns that belong in PIB — this is the primary anti-pattern to watch for.

---

## 3. The "Discovery" Mechanics

### Service Providers — how are they registered?

**Auto-discovered by nwidart from `module.json`**, not manually listed in `bootstrap/app.php`.

Each module's `module.json` declares its provider:

```json
// Modules/Crm/module.json
"providers": [
    "Modules\\Crm\\Providers\\CrmServiceProvider"
]
```

nwidart reads all `module.json` files on boot, checks `modules_statuses.json` to see if the module is enabled, and registers the declared providers automatically. There are three core providers in `app/Providers/`:

- `AppServiceProvider` — singletons, observer registration, auth policies, Eventy filters
- `EventServiceProvider` — all core event→listener mappings
- `ModuleCompatibilityServiceProvider` — macros that patch nwidart v12 API differences (adds `getAlias()` and `findByAlias()` back onto the Module and Repository classes)

### Migrations & Assets

**Migrations live inside each module.** They are loaded in the module's `ServiceProvider::boot()`:

```php
// CrmServiceProvider::boot()
$this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
```

Running migrations works exactly like standard Laravel — `php artisan migrate` discovers and runs all registered migration paths, including module paths. There is no `module:migrate` per-module command needed in normal operation.

To run migrations for a specific module in isolation:

```bash
php artisan migrate --path=Modules/Crm/Database/Migrations
```

Assets (JS/CSS) are registered via Eventy hooks and compiled through the root `vite.config.js`. Module public assets land in `public/modules/{module-alias}/`.

### Routing Hierarchy — how are URI conflicts prevented?

Each module loads its own `routes.php` which nwidart boots after the core route files. The module routes file is declared in `module.json` under `"files": ["start.php"]` or loaded directly by the service provider.

URI collision is prevented by convention, not by enforcement:
- Core routes in `routes/web.php` own general paths (`/dashboard`, `/conversations`, `/customers`, etc.)
- Module routes use a domain-specific prefix: `/crm/`, `/pib/`, `/assets/`, `/portal/`, etc.
- All module routes wrap their prefix with `\App\Misc\Helper::getSubdirectory()` to support subdirectory installs

There is no automatic conflict detection. If two modules claim the same URI, the one whose service provider boots last wins silently.

---

## 4. Testing & DX

### Can I run tests for a single module in isolation?

**Yes.** The `phpunit.xml` defines a `Modules` test suite that scans `Modules/*/tests`, but individual modules can be run directly:

```bash
# Run all tests
php artisan test

# Run a specific module's tests only
php artisan test --testsuite=Modules
php artisan test Modules/Crm/Tests/

# Run a single test file
php artisan test Modules/Crm/Tests/Feature/ClientTest.php
```

Each module has its own `Tests/Feature/`, `Tests/Unit/`, and `Tests/Integration/` directories. The global `tests/` directory contains the core suite organized the same way.

**Database for tests:** `phpunit.xml` defaults to SQLite in-memory for speed.  
- SQLite: ~10–20× faster, best for TDD; some MySQL-specific queries may fail  
- MySQL: full fidelity; switch by uncommenting the MySQL env block in `phpunit.xml`  
- Parallel test execution is supported via `brianium/paratest`

### Code Generation — artisan scaffolding or copy-paste?

**Both options exist.**

nwidart provides standard module scaffolding:

```bash
# Scaffold a new module skeleton
php artisan module:make NewModuleName

# Generate a model within a module
php artisan module:make-model MyModel NewModuleName

# Generate a controller
php artisan module:make-controller MyController NewModuleName
```

There are also project-specific artisan commands in `app/Console/Commands/`:

| Command | Purpose |
|---|---|
| `ModuleInstall` | Install a module from a package or path |
| `ModuleUpdate` | Update an existing module |
| `ModuleBuild` | Build/compile module assets |
| `ModuleGitStatus` | Show git status across all module repos |

In practice today, the process for creating a *net-new domain module* is: run `module:make`, then manually add the standard directories (`Services/`, `Events/`, `Listeners/`, `Tests/`) that nwidart does not generate. There is no project-specific "full module scaffold" command.

### The "God Class" — where is the current pain point?

**`Modules/Crm/Providers/CrmServiceProvider.php` at 1,111 lines.**

It is doing everything: loading config, views, migrations, registering routes, defining Gates, registering permissions, registering navigation items, registering widgets, attaching ~30 Eventy action/filter hooks, registering event listeners, composing view data. It is the single-file boot sequence for the entire CRM domain.

There is no intentional second-place; the `CrmServiceProvider` is an outlier. A decomposition plan would split it along these lines:

- `CrmServiceProvider` — boot orchestrator only (thin, calls other registrars)
- `CrmHooksProvider` — all Eventy `addAction` / `addFilter` calls
- `CrmAuthProvider` — Gates and Permissions
- `CrmEventServiceProvider` — event listener registration
- `CrmNavigationProvider` — widget and nav registration

Until that refactor happens, treat `CrmServiceProvider` as a read-heavy class and plan for long diffs when touching it.

---

## 5. Deployment & CI/CD

### Seeding — how are complex module interdependencies handled?

The root `DatabaseSeeder` runs the minimum set needed to boot a fresh install:

```
ThemeSeeder → RbacSeeder → UserSeeder
```

Scenario/demo seeders exist for integration testing and onboarding demos:

| Seeder | Purpose |
|---|---|
| `ExampleSilverCompanySeeder` | Creates a representative MSP client with entitlements |
| `DemoUserSeeder` | Demo user accounts |
| `ClientPortalTestSeeder` | Portal-accessible user accounts |
| `RbacSeeder` | Default roles and permissions |

Modules do **not** have their own `module:seed` command. Module seeders are referenced from the root seeders or run manually:

```bash
php artisan db:seed --class=Database\\Seeders\\ExampleSilverCompanySeeder
```

There is no formal "seed order enforcement" mechanism. Inter-module seeder dependencies are resolved by call order within `DatabaseSeeder::run()`. If you add a module that needs reference data from another module's tables, add it explicitly after the dependency's seeder.

### Environment — `.env` requirements by module

See the **[Developer Getting Started guide](DEVELOPER_GETTING_STARTED.md#module-specific-environment-variables)** for the full list of module-specific env variables. No module has its own separate database connection or `.env` file at runtime — all variables go in the root `.env`.

---

> **Quick Reference**: Setup commands, artisan operations, and test runners are in **[DEVELOPER_GETTING_STARTED.md](DEVELOPER_GETTING_STARTED.md)**.
