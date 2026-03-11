<?php

namespace Modules\TreeScoutDeploymentManager\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use App\Models\Permission;
use Modules\TreeScoutDeploymentManager\Services\ActivationService;
use Modules\TreeScoutDeploymentManager\Services\GitProviderService;

class TreeScoutDeploymentManagerServiceProvider extends ServiceProvider
{
    protected string $moduleNamespace = 'Modules\TreeScoutDeploymentManager\Http\Controllers';

    // ------------------------------------------------------------------
    // Boot
    // ------------------------------------------------------------------

    public function boot(): void
    {
        // Permissions & Gates
        $this->registerPermissions();

        // Routing
        $this->registerRoutes();

        // Views
        $this->registerViews();

        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // Config
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'tsdm');

        // Inject dynamic relationship into CRM Client model using Core Blindness pattern.
        // If the CRM module is not loaded, this silently skips — no hard dependency.
        $this->app->booted(function () {
            if (class_exists(\Modules\Crm\Models\Client::class)) {
                \Modules\Crm\Models\Client::resolveRelationUsing(
                    'deploymentRecords',
                    function ($client) {
                        return $client->hasMany(
                            \Modules\TreeScoutDeploymentManager\Models\DeploymentRecord::class,
                            'client_id'
                        );
                    }
                );
            }
        });

        // Navigation
        $this->registerNavigation();
    }

    // ------------------------------------------------------------------
    // Register
    // ------------------------------------------------------------------

    public function register(): void
    {
        // Register EventServiceProvider
        $this->app->register(EventServiceProvider::class);

        // Bind services into the container
        $this->app->singleton(GitProviderService::class);
        $this->app->singleton(ActivationService::class, function ($app) {
            return new ActivationService($app->make(GitProviderService::class));
        });
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    protected function registerPermissions(): void
    {
        foreach ([
            'view_tsdm'              => 'View Deployment Manager',
            'manage_tsdm'            => 'Manage Deployment Manager',
            'issue_tsdm_activations' => 'Issue Activation Codes',
        ] as $key => $label) {
            try {
                Permission::register($key, $label);
            } catch (\Exception $e) {
                // Table may not exist during initial migrations
            }

            Gate::define($key, fn ($user) => $user->hasPermission($key));
        }
    }

    protected function registerRoutes(): void
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(__DIR__ . '/../Routes/web.php');

        Route::middleware('api')
            ->namespace($this->moduleNamespace)
            ->prefix('api')
            ->group(__DIR__ . '/../Routes/api.php');
    }

    protected function registerViews(): void
    {
        $sourcePath = __DIR__ . '/../resources/views';
        $viewPath   = resource_path('views/modules/tsdm');

        $this->publishes([$sourcePath => $viewPath], 'views');

        /** @var array<int, string> $viewPaths */
        $viewPaths = \Config::get('view.paths', []);
        $this->loadViewsFrom(
            array_merge(
                array_map(fn (string $p) => $p . '/modules/tsdm', $viewPaths),
                [$sourcePath]
            ),
            'tsdm'
        );
    }

    protected function registerNavigation(): void
    {
        if (!class_exists(\App\Services\Navigation\NavigationService::class)) {
            return;
        }

        $nav = $this->app->make(\App\Services\Navigation\NavigationService::class);

        $nav->registerDropdown('Deployments', [
            ['label' => 'Control Tower',  'route' => 'tsdm.dashboard'],
            ['label' => 'Deployments',    'route' => 'tsdm.deployments.index'],
            ['label' => 'Activations',    'route' => 'tsdm.activations.index'],
            ['label' => 'Settings',       'route' => 'tsdm.settings.index'],
        ], 'view_tsdm', 'icon-server', 'Tools');
    }
}
