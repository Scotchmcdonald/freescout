# Developer Getting Started
**Audience:** New engineers (including incoming architects)  
**Last Updated:** February 28, 2026

---

## Prerequisites

| Tool | Version | Notes |
|---|---|---|
| PHP | 8.2+ | `php-sqlite3` extension required for tests |
| Composer | 2.x | |
| Node.js | 20+ LTS | For Vite asset compilation |
| Docker + Docker Compose | any recent | Recommended for local setup |
| MySQL | 8.0+ | Or use SQLite for dev |

---

## Local Setup (Docker — Recommended)

```bash
# 1. Clone the repository
git clone <repo-url> freescout-modern
cd freescout-modern

# 2. Bootstrap module repositories
#    The core repo does NOT include module code (Modules/ is gitignored).
#    This step clones all module repos from the BorealTek GitHub org.
#    Requires: jq  (sudo apt install jq / brew install jq)
export REPO_TOKEN="ghp_your_github_token"   # needed for private repos
./scripts/setup-modules.sh                  # installs 'full' profile (all modules)
#  -- or for a specific client profile: --
./scripts/setup-modules.sh core-msp         # see deployment/modules.manifest.json

# 3. Copy environment file
cp .env.example .env

# 4. Start the containers
docker compose up -d

# 5. Install PHP dependencies
docker compose exec app composer install

# 6. Generate application key
docker compose exec app php artisan key:generate

# 7. Run all migrations (core + modules)
docker compose exec app php artisan migrate

# 8. Seed baseline data (roles, themes, a default admin user)
docker compose exec app php artisan db:seed

# 9. Install JS dependencies and compile assets
npm install
npm run dev        # or: npm run build for production assets
```

The app will be available at **http://localhost** (or the port configured in `docker-compose.yml`).

Default admin credentials after seeding come from `database/seeders/UserSeeder.php`.  
Check that file or look for the `ADMIN_EMAIL` / `ADMIN_PASSWORD` env vars.

---

## Local Setup (Without Docker)

```bash
# After cloning the repo, bootstrap modules first:
export REPO_TOKEN="ghp_your_github_token"
./scripts/setup-modules.sh

# Then:
composer install
php artisan key:generate

# Configure your .env DB_* variables, then:
php artisan migrate
php artisan db:seed

npm install && npm run dev

# Start a local dev server
php artisan serve
```

---

## Module-Specific Environment Variables

Some modules require API credentials in `.env`. Each such module ships a `.env.example` inside its own directory. Check them:

```bash
find Modules -name '.env.example' | xargs -I{} cat {}
```

The most common one to configure for full functionality is **Payment** (Helcim gateway):

```dotenv
HELCIM_API_TOKEN=your_token
HELCIM_ACCOUNT_ID=your_account_id
HELCIM_WEBHOOK_SECRET=your_webhook_secret
HELCIM_API_URL=https://api.helcim.com/v2
```

These go in the **root `.env`** — not in a per-module file.

---

## Running Tests

```bash
# All tests (SQLite in-memory — fast)
php artisan test

# All tests in parallel
php artisan test --parallel

# One test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Modules

# One module
php artisan test Modules/Crm/Tests/

# One file
php artisan test Modules/Crm/Tests/Feature/ClientTest.php

# With coverage (requires PCOV or Xdebug)
composer test
```

The `phpunit.xml` defines four suites: `Feature`, `Integration`, `Unit`, and `Modules`.  
Integration tests that hit real external services are tagged `@group integration` and excluded by default.

---

## Working With Modules

### Check module status

```bash
php artisan module:list
```

### Enable / disable a module

```bash
php artisan module:enable Crm
php artisan module:disable DevFeedback
```

This writes to [`modules_statuses.json`](../../modules_statuses.json). Commit the change if it should persist for the team.

### Scaffold a new module

```bash
php artisan module:make MyModule
```

This creates the skeleton in `Modules/MyModule/`. After generation, manually add:

```
Modules/MyModule/
├── Services/          ← domain service classes
├── Events/            ← domain events
├── Listeners/         ← event listeners
└── Tests/
    ├── Feature/
    ├── Integration/
    └── Unit/
```

Register your provider in `module.json` and declare any modules you depend on:

```json
{
    "name": "MyModule",
    "alias": "mymodule",
    "providers": ["Modules\\MyModule\\Providers\\MyModuleServiceProvider"],
    "requires": ["Crm"]
}
```

### Run module migrations

```bash
# All pending (includes module migrations)
php artisan migrate

# One module only
php artisan migrate --path=Modules/MyModule/Database/Migrations
```

---

## Architecture Rules (TL;DR for New Engineers)

1. **Core Blindness**: `app/` never imports from `Modules/`. The dependency arrow points one way: Module → Core.

2. **UI Extension via Eventy**: To inject UI elements into core pages, use the `tormjens/eventy` hook system (`\Eventy::addAction()` / `\Eventy::addFilter()`), not direct Blade include changes.

3. **Cross-Module Calls**: Use `class_exists()` guards when calling another module from your module. Declare hard dependencies in `module.json` under `"requires"`.

4. **Events for State Changes**: Fire a Laravel event for any domain state change that other modules might care about. Register your listener in your own service provider.

5. **Data Ownership**: Tables belong to the module that semantically owns that data. Do not add financial/billing columns to the `clients` table (CRM). Financial data goes in PIB.

See [`MODULAR_SYSTEM_QA.md`](MODULAR_SYSTEM_QA.md) for the full architectural rationale behind each rule.

---

## Useful Artisan Commands

| Command | Purpose |
|---|---|
| `php artisan module:list` | Show all modules and enabled status |
| `php artisan module:make <Name>` | Scaffold a new module |
| `php artisan module:enable <Name>` | Enable a module |
| `php artisan module:disable <Name>` | Disable a module |
| `php artisan migrate` | Run all pending migrations (core + modules) |
| `php artisan db:seed` | Run the baseline seeders |
| `php artisan route:list` | Dump all registered routes (core + modules) |
| `php artisan queue:work` | Process queued jobs |
| `php artisan test` | Run the test suite |
| `php artisan tinker` | REPL with full app context |
| `php artisan cache:clear` | Clear application cache |

---

## Key Files to Read First

| File | Why |
|---|---|
| [`docs/architecture/APP_OVERVIEW.md`](APP_OVERVIEW.md) | What the app is and how it's organized |
| [`docs/architecture/MODULAR_SYSTEM_QA.md`](MODULAR_SYSTEM_QA.md) | Deep answers on the modular architecture |
| [`docs/architecture/MODULE_REPO_MANAGEMENT.md`](MODULE_REPO_MANAGEMENT.md) | How module repos are structured, profiles, and client deployment strategy |
| [`docs/architecture/ARCHITECTURE_OVERVIEW.md`](ARCHITECTURE_OVERVIEW.md) | Core principles, data ownership table, cross-module patterns |
| [`deployment/modules.manifest.json`](../../deployment/modules.manifest.json) | Canonical list of all module repos and deployment profiles |
| [`modules_statuses.json`](../../modules_statuses.json) | Which modules are active right now |
| [`Modules/Crm/Providers/CrmServiceProvider.php`](../../Modules/Crm/Providers/CrmServiceProvider.php) | The largest, most instructive service provider (1111 lines — be patient) |
| [`config/modules.php`](../../config/modules.php) | nwidart configuration |
| [`phpunit.xml`](../../phpunit.xml) | Test suite and database configuration |

---

## Common Gotchas

**Migrations not running for a new module?**  
Verify `$this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');` is in your `ServiceProvider::boot()`.

**Module not loading?**  
Check `modules_statuses.json` — the module must be `true`. Also verify `"providers"` is set in `module.json`.

**Routes not found?**  
nwidart loads module routes after the core routes. If a module route conflicts with a core route, the core route wins. Prefix all module URIs distinctly.

**Tests failing with "Table not found" on SQLite?**  
Use `RefreshDatabase` trait in your test. SQLite in-memory doesn't persist between test runs unless the trait is present.

**Eventy hooks not firing?**  
Hooks must be registered in the module's `ServiceProvider::boot()`, not `register()`. The `boot()` phase is when other service providers are available.
