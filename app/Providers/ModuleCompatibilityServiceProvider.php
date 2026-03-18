<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Laravel\LaravelFileRepository;
use Nwidart\Modules\Laravel\Module as LaravelModule;
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
    public function register(): void {}

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Guard against missing module.json files during discovery
        // This fixes test isolation issues where TestModule may be discovered but lack a module.json
        LaravelFileRepository::macro('all', function () {
            /** @var LaravelFileRepository $this */
            $modules = [];
            $paths = glob($this->getPath().'/*');
            if ($paths === false) {
                return $modules;
            }

            foreach ($paths as $modulePath) {
                if (! is_dir($modulePath)) {
                    continue;
                }

                $json = $modulePath.'/module.json';
                if (! file_exists($json)) {
                    // Skip modules without module.json to avoid FileNotFoundException
                    continue;
                }

                try {
                    $moduleJson = File::json($json);
                    $name = $moduleJson['name'] ?? basename($modulePath);
                    if (! is_string($name) || $name === '') {
                        $name = basename($modulePath);
                    }

                    $module = new LaravelModule(app(), $name, $modulePath);
                    $modules[$module->getName()] = $module;
                } catch (\Exception $e) {
                    // Log but skip modules with corruption/parsing errors
                    logger()->warning("Failed to load module at {$modulePath}: {$e->getMessage()}");
                }
            }

            return $modules;
        });

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
            $modules = $this->all();

            foreach ($modules as $module) {
                if ($module->get('alias') === $alias || $module->getLowerName() === $alias) {
                    return $module;
                }
            }

            return null;
        });
    }
}
