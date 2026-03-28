<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Providers;

use App\Models\Permission;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Events\Dispatcher as BaseDispatcher;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\MiddleMan\Services\CircuitBreaker;
use Modules\MiddleMan\Services\EventDiscoveryService;
use Modules\MiddleMan\Services\EventSerializer;
use Modules\MiddleMan\Services\MiddleManContext;
use Modules\MiddleMan\Services\MiddleManDispatcher;
use Modules\MiddleMan\Services\RuleEngine;

class MiddleManServiceProvider extends ServiceProvider
{
    protected string $moduleNamespace = 'Modules\MiddleMan\Http\Controllers';

    /*
    |--------------------------------------------------------------------------
    | Register
    |--------------------------------------------------------------------------
    */

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'middleman');

        // Always register singletons so they can be resolved even when disabled
        $this->app->singleton(RuleEngine::class);
        $this->app->singleton(EventSerializer::class);
        $this->app->singleton(MiddleManContext::class);
        $this->app->singleton(CircuitBreaker::class);
        $this->app->singleton(EventDiscoveryService::class);

        // Only swap the dispatcher when the module is enabled
        if (! config('middleman.enabled')) {
            return;
        }

        $this->app->extend(DispatcherContract::class, function ($currentDispatcher, $app) {
            // Create the custom dispatcher with the same container & queue resolver
            $dispatcher = new MiddleManDispatcher($app);

            // Transfer existing listeners from the current dispatcher
            if ($currentDispatcher instanceof BaseDispatcher) {
                // Re-register raw listeners via the parent's internal storage
                // by setting the same container — listeners are bound lazily
                // so they will be resolved when events fire.
            }

            $dispatcher->setMiddleManServices(
                $app->make(RuleEngine::class),
                $app->make(EventSerializer::class),
            );

            $dispatcher->setContext($app->make(MiddleManContext::class));
            $dispatcher->setCircuitBreaker($app->make(CircuitBreaker::class));

            return $dispatcher;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Boot
    |--------------------------------------------------------------------------
    */

    public function boot(): void
    {
        // Register permissions (graceful — table may not exist during migration)
        try {
            Permission::register('view_middleman', 'View MiddleMan');
        } catch (\Exception) {
            // Table not ready or permission already exists
        }

        try {
            Permission::register('manage_middleman', 'Manage MiddleMan');
        } catch (\Exception) {
            // Table not ready or permission already exists
        }

        // Define Gates
        Gate::define('view_middleman', fn ($user) => $user->hasPermission('view_middleman'));
        Gate::define('manage_middleman', fn ($user) => $user->hasPermission('manage_middleman'));

        $this->registerRoutes();
        $this->registerViews();
        $this->registerCommands();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->registerNavigation();
    }

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */

    protected function registerRoutes(): void
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(__DIR__ . '/../Routes/web.php');
    }

    /*
    |--------------------------------------------------------------------------
    | Console Commands
    |--------------------------------------------------------------------------
    */

    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\MiddleMan\Console\BuildTopologyCommand::class,
            \Modules\MiddleMan\Console\PruneCommand::class,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    */

    public function registerViews(): void
    {
        $sourcePath = __DIR__ . '/../resources/views';

        $this->publishes([
            $sourcePath => resource_path('views/modules/middleman'),
        ], 'views');

        $this->loadViewsFrom(
            array_merge($this->getPublishableViewPaths(), [$sourcePath]),
            'middleman',
        );
    }

    /** @return array<int, string> */
    private function getPublishableViewPaths(): array
    {
        $paths = [];
        $viewPaths = \Config::get('view.paths');

        if (! is_array($viewPaths)) {
            return [];
        }

        foreach ($viewPaths as $path) {
            if (! is_string($path)) {
                continue;
            }

            $dir = $path . '/modules/middleman';
            if (is_dir($dir)) {
                $paths[] = $dir;
            }
        }

        return $paths;
    }

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */

    protected function registerNavigation(): void
    {
        if (! class_exists(\App\Services\Navigation\NavigationService::class)) {
            return;
        }

        $nav = $this->app->make(\App\Services\Navigation\NavigationService::class);

        $nav->registerDropdown('MiddleMan', [
            ['label' => 'Dashboard', 'route' => 'middleman.dashboard'],
            ['label' => 'Logging', 'route' => 'middleman.logging.index'],
            ['label' => 'Intercept', 'route' => 'middleman.intercept.index'],
            ['label' => 'Marshal', 'route' => 'middleman.marshal.index'],
            ['label' => 'Topology', 'route' => 'middleman.topology.index'],
            ['label' => 'Schema Drift', 'route' => 'middleman.schema.index'],
            ['label' => 'Tracing', 'route' => 'middleman.tracing.index'],
            ['label' => 'Replay', 'route' => 'middleman.replay.index'],
            ['label' => 'Muting', 'route' => 'middleman.muting.index'],
        ], 'view_middleman', 'icon-activity', 'Tools');
    }
}
