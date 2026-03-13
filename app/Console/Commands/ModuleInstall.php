<?php

declare(strict_types=1);
/**
 * php artisan freescout:module-install modulealias.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ModuleInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:module-install {module_alias?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install module or all modules (if module_alias is empty): run migrations and create a symlink';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $this->call('cache:clear');

            $module_alias = $this->argument('module_alias');

            if ($module_alias) {
                try {
                    $module = \Module::find($module_alias);
                } catch (\Exception $e) {
                    $module = null;
                }

                if (! $module) {
                    $this->error('Module with the specified alias not found: '.$module_alias);

                    return 0; // Not a failure, just an informational message.
                }

                return $this->installModule($module);
            }

            $modules = \Module::all();
            if (empty($modules)) {
                $this->info('No modules found.');

                return 0;
            }

            $moduleNames = array_map(fn ($m) => $m->getName(), $modules);
            if ($this->confirm('Install all modules ('.implode(', ', $moduleNames).')?')) {
                foreach ($modules as $module) {
                    if ($this->installModule($module) !== 0) {
                        return 1; // Stop on first error
                    }
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return 1;
        }
    }

    protected function installModule(\Nwidart\Modules\Module $module): int
    {
        $this->line('Installing module: '.$module->getName());

        try {
            $module->enable();

            $this->call('module:migrate', ['module' => $module->getName(), '--force' => true]);

            $this->createModulePublicSymlink($module);

            $this->line('Clearing cache...');
            $this->call('freescout:clear-cache', [
                '--doNotCacheConfig' => app()->runningUnitTests(),
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return 1;
        }

        return 0;
    }

    /**
     * Create a public symlink for the module.
     *
     * @param  \Nwidart\Modules\Module  $module
     * @return void
     *
     * @throws \Exception
     */
    public function createModulePublicSymlink($module)
    {
        $target = $module->getExtraPath('Resources/assets');
        $link = public_path('modules/'.$module->getLowerName());

        if (! file_exists($target)) {
            return;
        }

        if (file_exists($link) || is_link($link)) {
            app('files')->delete($link);
        }

        app('files')->link($target, $link);
        $this->info('The ['.$link.'] symlink has been created.');
    }
}
