<?php
/**
 * php artisan freescout:module-install modulealias.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ModuleSource;

class ModuleUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:module-update {module_alias?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all modules or a single module (if module_alias is set)';

    protected ModuleSource $moduleSource;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ModuleSource $moduleSource)
    {
        parent::__construct();
        $this->moduleSource = $moduleSource;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $install_all = false;
        $modules = [];

        // We have to clear modules cache first to update modules cache
        \Artisan::call('cache:clear');

        // Create a symlink for the module (or all modules)
        $module_alias = $this->argument('module_alias');
        
        $modules_directory = $this->moduleSource->getModules();

        $installed_modules = \Module::all();

        $counter = 0;
        $found = false;
        foreach ($modules_directory as $dir_module) {
            // Update single module.
            if ($module_alias && $dir_module['alias'] != $module_alias) {
                continue;
            }
            
            $found = true;

            // Detect if new version is available.
            foreach ($installed_modules as $module) {
                if ($module->getAlias() != $dir_module['alias'] /*|| !$module->active()*/) {
                    continue;
                }
                $dirVersion = $dir_module['version'] ?? '';
                $dirVersionStr = is_string($dirVersion) || is_int($dirVersion) || is_float($dirVersion) ? (string) $dirVersion : '';
                if (!empty($dirVersion) && version_compare($dirVersionStr, $module->get('version'), '>')) {

                    $dirAlias = $dir_module['alias'] ?? '';
                    $dirAliasStr = is_string($dirAlias) || is_int($dirAlias) || is_float($dirAlias) ? (string) $dirAlias : '';
                    $update_result = \App\Module::updateModule($dirAliasStr);

                    $this->info('['.$update_result['module_name'].' Module'.']');
                    if ($update_result['status'] == 'success') {
                        $this->line((string) $update_result['msg_success']);
                    } else {
                        $msg = $update_result['msg'];
                        if ($update_result['download_msg']) {
                            $msg .= ' ('.$update_result['download_msg'].')';
                        }
                        $this->error('ERROR: '.$msg);
                    }
                    if (!empty($update_result['output']) && trim((string)$update_result['output'])) {
                        $this->line((string)preg_replace("#\n#", "\n> ", '> '.trim((string)$update_result['output'])));
                    }

                    $counter++;
                }
            }
        }

        // Update custom modules.
        // Loop through each installed module.
        foreach ($installed_modules as $module) {
            // Skip if the module is in the source (already handled above)
            $inSource = false;
            foreach ($modules_directory as $dir_module) {
                if ($dir_module['alias'] == $module->getAlias()) {
                    $inSource = true;
                    break;
                }
            }
            if ($inSource) {
                continue;
            }

            // Get the URL for the latest version of the module
            $latest_version_number_url = $module->get('latestVersionUrl');
            if (! $latest_version_number_url) {
                continue;
            }

            // Create a new Guzzle HTTP client
            $client = new \GuzzleHttp\Client();

            try {
                // Send a GET request to the latest version URL
                $response = $client->request('GET', $latest_version_number_url, \App\Misc\Helper::setGuzzleDefaultOptions());

                // Get the latest version number from the response body
                $latest_version = trim((string) $response->getBody());

                if (empty($latest_version)) {
                    continue;
                } else {
                    // Get the current version of the module
                    $current_version = $module->get('version');
                }
            } catch (\Exception $e) {
                // If there's an exception, skip to the next iteration
                continue;
            }

            // If the latest version is greater than the current version
            if (version_compare($latest_version, $current_version, '>')) {
                // Update the module
                $update_result = \App\Module::updateModule($module->getAlias());

                // Print the module name and status
                $this->info('[' . $update_result['module_name'] . ' Module' . ']');
                if ($update_result['status'] == 'success') {
                    // If the update was successful, print the success message
                    $this->line((string) $update_result['msg_success']);
                } else {
                    // If the update failed, print the error message
                    $msg = $update_result['msg'];
                    if ($update_result['download_msg']) {
                        $msg .= ' (' . $update_result['download_msg'] . ')';
                    }
                    $this->error('ERROR: ' . $msg);
                }
                // If there's any output from the update, print it
                if (!empty($update_result['output']) && trim((string)$update_result['output'])) {
                    $this->line((string)preg_replace("#\n#", "\n> ", '> ' . trim((string)$update_result['output'])));
                }

                // Increment the counter
                $counter ++;
            }
        }

        if ($module_alias && !$found) {
            // Check if it's a custom module
             $isCustom = false;
             foreach ($installed_modules as $module) {
                 if ($module->getAlias() == $module_alias) {
                     $isCustom = true;
                     break;
                 }
             }
             if (!$isCustom) {
                $this->error('Module with the following alias not found: '.$module_alias);
             }
        } elseif (!$counter) {
            $this->line('All modules are up-to-date');
        }

        \Artisan::call('freescout:clear-cache');
    }
}
