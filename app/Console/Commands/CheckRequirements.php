<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckRequirements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:check-requirements';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check system requirements for FreeScout';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking FreeScout System Requirements');
        $this->newLine();

        $hasErrors = false;

        // Check PHP Version
        $this->comment('PHP Version');
        $phpVersion = phpversion();
        $minVersion = config('installer.core.minPhpVersion', '8.2.0');
        $versionOk = version_compare($phpVersion, $minVersion, '>=');

        $this->line(' ' . str_pad($phpVersion, 30, '.') . ' ' . ($versionOk ? '<fg=green>OK</>' : '<fg=red>FAILED (>= ' . $minVersion . ' required)</>'));

        if (!$versionOk) {
            $hasErrors = true;
        }

        $this->newLine();

        // Check PHP Extensions
        $this->comment('PHP Extensions');
        $extensions = $this->checkRequiredExtensions();
        $this->outputItems($extensions);

        if (in_array(false, $extensions, true)) {
            $hasErrors = true;
        }

        $this->newLine();

        // Check Required Functions
        $this->comment('Required Functions');
        $functions = $this->checkRequiredFunctions();
        $this->outputItems($functions);

        if (in_array(false, $functions, true)) {
            $hasErrors = true;
        }

        $this->newLine();

        // Check Directory Permissions
        $this->comment('Directory Permissions');
        $permissions = $this->checkDirectoryPermissions();
        $this->outputItems($permissions);

        if (in_array(false, $permissions, true)) {
            $hasErrors = true;
        }

        $this->newLine();

        if ($hasErrors) {
            $this->error('Some requirements are not met. Please fix the issues above.');
            return 1;
        }

        $this->info('All requirements met!');
        return 0;
    }

    /**
     * Check required PHP extensions.
     *
     * @return array<string, bool>
     */
    protected function checkRequiredExtensions(): array
    {
        $phpExtensions = [];
        $requiredExtensions = config('installer.requirements.php', []);

        // Add optional extensions
        $requiredExtensions[] = 'intl';
        $requiredExtensions[] = 'pcntl';

        foreach ($requiredExtensions as $extensionName) {
            // Handle alternative extensions (e.g., "pdo_mysql/pdo_pgsql")
            $alternatives = explode('/', $extensionName);
            if (count($alternatives) > 1) {
                $phpExtensions[$extensionName] = false;
                foreach ($alternatives as $alternative) {
                    if (extension_loaded(trim($alternative))) {
                        $phpExtensions[$extensionName] = true;
                        break;
                    }
                }
            } else {
                $phpExtensions[$extensionName] = extension_loaded($extensionName);
            }
        }

        return $phpExtensions;
    }

    /**
     * Check required PHP functions.
     *
     * @return array<string, bool>
     */
    protected function checkRequiredFunctions(): array
    {
        return [
            'proc_open' => function_exists('proc_open'),
            'proc_close' => function_exists('proc_close'),
            'fsockopen' => function_exists('fsockopen'),
            'symlink' => function_exists('symlink'),
            'shell_exec' => function_exists('shell_exec'),
            'fpassthru' => function_exists('fpassthru'),
            'iconv' => function_exists('iconv'),
        ];
    }

    /**
     * Check directory permissions.
     *
     * @return array<string, bool>
     */
    protected function checkDirectoryPermissions(): array
    {
        $permissions = [];
        $directories = config('installer.permissions', []);

        foreach ($directories as $directory => $permission) {
            $path = base_path($directory);
            $isWritable = is_dir($path) && is_writable($path);
            $permissions[$directory] = $isWritable;
        }

        return $permissions;
    }

    /**
     * Output items with status.
     *
     * @param array<string, bool> $items
     */
    protected function outputItems(array $items): void
    {
        foreach ($items as $item => $status) {
            $this->line(' ' . str_pad($item, 30, '.') . ' ' . ($status ? '<fg=green>OK</>' : '<fg=red>NOT FOUND</>'));
        }
    }
}
