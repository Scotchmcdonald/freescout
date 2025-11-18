<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Facades\Module as ModuleFacade;
use Nwidart\Modules\Laravel\LaravelFileRepository;
use Nwidart\Modules\Module;

/**
 * Adds backward compatibility methods for nwidart/laravel-modules
 * that were removed in newer versions but are still used in our codebase.
 */
class ModuleCompatibilityServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Add getAlias() method to Module class
        // Returns the alias from module.json or falls back to lowercase module name
        Module::macro('getAlias', function () {
            /** @var \Nwidart\Modules\Module $this */
            $json = $this->json();
            return $json->get('alias') ?? $this->getLowerName();
        });

        // Add findByAlias() method to Repository
        LaravelFileRepository::macro('findByAlias', function (string $alias) {
            /** @var LaravelFileRepository $this */
            foreach ($this->all() as $module) {
                if ($module->get('alias') === $alias || $module->getLowerName() === $alias) {
                    return $module;
                }
            }
            return null;
        });
    }
}
