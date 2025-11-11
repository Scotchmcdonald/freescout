<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Facades\Module;

class ModuleBuild extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:module-build {module_alias?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build module or all modules (if module_alias is empty)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $moduleAlias = $this->argument('module_alias');
        $buildAll = false;

        if (!$moduleAlias) {
            $modules = Module::all();

            if (empty($modules)) {
                $this->error('No modules found');
                return 1;
            }

            $buildAll = true;
            $this->info('Building all modules...');
        }

        if ($buildAll) {
            $modules = Module::all();
            foreach ($modules as $module) {
                $this->buildModule($module);
            }
        } else {
            $module = Module::findByAlias($moduleAlias);
            if (!$module) {
                $this->error("Module with the specified alias not found: {$moduleAlias}");
                return 1;
            }
            $this->buildModule($module);
        }

        $this->info('Module build completed!');
        return 0;
    }

    /**
     * Build a specific module.
     */
    protected function buildModule($module): void
    {
        $this->line("Building module: {$module->getName()}");

        // Check if public symlink exists
        $publicSymlink = public_path('modules') . DIRECTORY_SEPARATOR . $module->getAlias();
        if (!file_exists($publicSymlink)) {
            $this->error("Public symlink [{$publicSymlink}] not found. Run module installation command first: php artisan freescout:module-install");
            return;
        }

        // Build module variables/configuration
        $this->buildVars($module);
    }

    /**
     * Build module variables file.
     */
    protected function buildVars($module): void
    {
        try {
            $params = [
                'locales' => config('app.locales', []),
            ];

            $filesystem = new Filesystem();
            $filePath = public_path("modules/{$module->getAlias()}/js/vars.js");

            // Check if the view exists
            $viewPath = "{$module->getAlias()}::js/vars";
            if (!view()->exists($viewPath)) {
                $this->comment("View {$viewPath} not found, skipping vars.js generation");
                return;
            }

            $compiled = view($viewPath, $params)->render();

            if ($compiled) {
                // Ensure directory exists
                $directory = dirname($filePath);
                if (!is_dir($directory)) {
                    $filesystem->makeDirectory($directory, 0755, true);
                }

                $filesystem->put($filePath, $compiled);
                $this->info("Created: {$filePath}");
            }
        } catch (\Exception $e) {
            $this->error("Error building vars for {$module->getName()}: " . $e->getMessage());
        }
    }
}
