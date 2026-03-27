<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Permission;
use App\Models\Thread;
use App\Models\User;
use App\Policies\ClientPolicy;
use App\Policies\ClientUserPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\FolderPolicy;
use App\Policies\MailboxPolicy;
use App\Policies\ThreadPolicy;
use App\Services\Ui\WidgetRegistryService;
use App\Widgets\Dashboard\AdminDashboardWidget;
use App\Widgets\Dashboard\AgentDashboardWidget;
use App\Widgets\Dashboard\FinanceDashboardWidget;
use App\Widgets\Dashboard\ReporterDashboardWidget;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerTestingEventyFallback();

        $this->app->singleton(WidgetRegistryService::class, function ($app) {
            return new WidgetRegistryService;
        });

        // Alias bindings for backward compatibility & test resolution
        $this->app->alias(WidgetRegistryService::class, 'App\Services\Ui\WidgetRegistry');

        // Canonical EntitlementEngine singleton — lives in PIB module.
        $this->app->singleton(\Modules\PIB\Services\EntitlementEngineService::class, function ($app) {
            return new \Modules\PIB\Services\EntitlementEngineService;
        });
        $this->app->alias(\Modules\PIB\Services\EntitlementEngineService::class, \App\Services\EntitlementEngine::class);

        // Fallback bindings keep credit interfaces resolvable even when module provider
        // registration order differs across parallel workers.
        $this->app->bind(\App\Contracts\Billing\CreditWriter::class, \Modules\PIB\Services\ClientCreditService::class);
        $this->app->bind(\App\Contracts\Billing\CreditReader::class, \Modules\PIB\Services\ClientCreditService::class);
        $this->app->bind(\App\Contracts\Billing\CreditLedgerInterface::class, \Modules\PIB\Services\ClientCreditService::class);

        $this->app->singleton(\App\Services\UserDirectoryRegistryService::class, function ($app) {
            return new \App\Services\UserDirectoryRegistryService;
        });

        $this->app->singleton(\App\Services\Navigation\NavigationService::class, function ($app) {
            return new \App\Services\Navigation\NavigationService;
        });

        // Register class aliases for backward compatibility
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('Helper', \App\Misc\Helper::class);
    }

    /**
     * Provide a null Eventy binding for test subprocesses.
     *
     * Some console subprocesses spawned by integration tests do not run
     * through Tests\TestCase, so they may miss the usual test-only Eventy mock.
     */
    protected function registerTestingEventyFallback(): void
    {
        if (! $this->app->environment('testing') || $this->app->bound('eventy')) {
            return;
        }

        $nullEventy = new class
        {
            public function addFilter($tag, $callback, $priority = 10, $accepted_args = 1)
            {
                return true;
            }

            public function addAction($tag, $callback, $priority = 10, $accepted_args = 1)
            {
                return true;
            }

            public function filter($tag, $value)
            {
                return $value;
            }

            public function action($tag, ...$args)
            {
                return null;
            }
        };

        $this->app->instance('eventy', $nullEventy);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerDataImportSettingsSectionFilter();

        // ── Production database destruction guard ────────────────────────────
        // Blocks migrate:fresh, db:wipe, and migrate:reset from running when
        // APP_ENV=production. These commands drop all data and must never run
        // against a live database. This cannot be bypassed without explicitly
        // changing APP_ENV first.
        if ($this->app->isProduction()) {
            $destructiveCommands = ['migrate:fresh', 'db:wipe', 'migrate:reset'];

            Event::listen(\Illuminate\Console\Events\CommandStarting::class, function (\Illuminate\Console\Events\CommandStarting $event) use ($destructiveCommands) {
                if (in_array($event->command, $destructiveCommands, true)) {
                    fwrite(
                        STDERR,
                        PHP_EOL
                        .'  [FATAL] Destructive command "'.$event->command.'" is blocked in production.'.PHP_EOL
                        .'  Set APP_ENV to "local" or "testing" before running this command.'.PHP_EOL
                        .PHP_EOL
                    );
                    exit(1);
                }
            });
        }

        // Register billing UI component namespace (x-billing::tabs, x-billing::tab-panel)
        Blade::componentNamespace('App\\View\\Components\\Billing', 'billing');

        // Add REGEXP support for SQLite (testing env)
        if (DB::connection() instanceof \Illuminate\Database\SQLiteConnection) {
            DB::connection()->getPdo()->sqliteCreateFunction('REGEXP', function ($pattern, $value) {
                mb_regex_encoding('UTF-8');

                return (mb_ereg($pattern, $value) !== false) ? 1 : 0;
            });
        }

        // Register navigation items
        $nav = $this->app->make(\App\Services\Navigation\NavigationService::class);
        if ($nav) {
            $nav->registerItem('Milestones', 'milestones.index', null, 'icon-milestone', 'Projects');
            $nav->registerItem('Knowledge Base', 'knowledgebase.index', null, 'icon-knowledge', 'Knowledge');
        }

        // Register authorization policies
        // Super-admin wildcard bypass: if ANY of the user's RBAC roles
        // has `is_super_admin = true`, they pass every gate check.
        // Falls back to legacy `role === ROLE_ADMIN` during transition.
        Gate::before(function ($user, $ability) {
            if ($user instanceof \App\Models\User) {
                return $user->isAdmin() ? true : null;
            }

            return null;
        });

        // Dynamically register a Gate for every RBAC permission in the database.
        // This ensures `can:permission_name` middleware works for all permissions
        // without requiring per-module Gate::define() calls.
        $this->registerDynamicGates();

        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(Mailbox::class, MailboxPolicy::class);
        Gate::policy(Thread::class, ThreadPolicy::class);
        Gate::policy(Folder::class, FolderPolicy::class);

        // Register rate limiters for webhooks
        $this->configureWebhookRateLimiters();

        // Client Portal policies for data isolation
        Gate::policy(\Modules\Crm\Models\Client::class, ClientPolicy::class);
        // ClientUserPolicy now operates on User instances (ClientUser merged into User)
        Gate::policy(User::class, ClientUserPolicy::class);

        // Monitor queue health
        Event::listen(JobProcessed::class, function (JobProcessed $event) {
            Cache::put('last_run_queue', now()->timestamp);
        });

        // Register role-differentiated dashboard widgets
        if ($this->app->bound(\Modules\WidgetRegistry\Services\WidgetRegistryService::class)) {
            /** @var \Modules\WidgetRegistry\Services\WidgetRegistryService $registry */
            $registry = $this->app->make(\Modules\WidgetRegistry\Services\WidgetRegistryService::class);
            $registry->register(new AdminDashboardWidget);
            $registry->register(new FinanceDashboardWidget);
            $registry->register(new AgentDashboardWidget);
            $registry->register(new ReporterDashboardWidget);
        }
    }

    /**
     * Configure rate limiters for webhook endpoints
     */
    protected function configureWebhookRateLimiters(): void
    {
        // Google webhooks: 60 requests per minute per IP
        RateLimiter::for('google_webhooks', function ($request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Action1 webhooks: 60 requests per minute per IP
        RateLimiter::for('action1_webhooks', function ($request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Action1 script phone-home callbacks: 30 per minute per IP.
        // Scripts POST their output exactly once; the low limit prevents token enumeration.
        RateLimiter::for('action1_script_callbacks', function ($request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }

    /**
     * Register a Gate::define for every RBAC permission in the database.
     *
     * This is called during boot() and ensures that `can:permission_name`
     * middleware resolves correctly for all permissions. The Gate::before
     * callback handles the super-admin bypass, so these definitions only
     * need to check the user's RBAC roles.
     */
    protected function registerDynamicGates(): void
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('permissions')) {
                return;
            }

            /** @var \Illuminate\Support\Collection<int, string> $permissions */
            $permissions = Permission::pluck('name');

            foreach ($permissions as $permissionName) {
                $permissionName = (string) $permissionName;

                // Skip if a gate with this name is already defined (e.g., by a module)
                if (Gate::has($permissionName)) {
                    continue;
                }

                Gate::define($permissionName, function (User $user) use ($permissionName) {
                    return $user->hasPermission($permissionName);
                });
            }
        } catch (\Exception $e) {
            // Silently fail during migrations or when DB is not yet available
            Log::debug('[RBAC] Could not register dynamic gates: '.$e->getMessage());
        }
    }

    /**
     * Register Data Import settings section filter when Eventy is available.
     *
     * Using a guarded container resolution avoids parallel test bootstrap
     * flakes caused by facade root mismatch during provider registration order.
     */
    protected function registerDataImportSettingsSectionFilter(): void
    {
        if (! $this->app->bound('eventy')) {
            return;
        }

        try {
            $eventy = $this->app->make('eventy');
        } catch (\Throwable) {
            return;
        }

        if (! is_object($eventy) || ! is_callable([$eventy, 'addFilter'])) {
            return;
        }

        $eventy->addFilter('settings.sections', function ($sections) {
            $sections['data_import'] = [
                'title' => __('Data Import'),
                'route' => 'settings.data_import',
                'icon' => 'upload',
                'order' => 800,
            ];

            return $sections;
        });
    }
}
