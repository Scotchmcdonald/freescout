<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Misc\WpApi;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Nwidart\Modules\Facades\Module;
use Symfony\Component\Console\Output\BufferedOutput;

class ModulesController extends Controller
{
    /**
     * Display a listing of modules.
     */
    public function index(): View|ViewFactory
    {
        $flashes = [];
        $flash = Cache::get('modules_flash');
        if ($flash) {
            if (is_array($flash) && ! isset($flash['text'])) {
                $flashes = $flash;
            } else {
                $flashes[] = $flash;
            }
            Cache::forget('modules_flash');
        }

        // Get local modules
        $modules = Module::all();
        $modulesData = [];

        foreach ($modules as $module) {
            $modulesData[] = [
                'name' => $module->getName(),
                'alias' => $module->getLowerName(),
                'description' => $module->getDescription(),
                'enabled' => $module->isEnabled(),
                'version' => $module->get('version', '1.0.0'),
                'path' => $module->getPath(),
                'license' => $this->getModuleLicense($module->getLowerName()),
                'activated' => $this->isLicenseActivated($module->getLowerName()),
            ];
        }

        // Get remote modules from WpApi
        $remoteModules = [];
        if (Cache::has('modules_directory')) {
            $remoteModules = Cache::get('modules_directory');
        }

        if (empty($remoteModules)) {
            $remoteModules = WpApi::getModules();
            if (! empty($remoteModules)) {
                Cache::put('modules_directory', $remoteModules, now()->addMinutes(15));
            }
        }

        return view('modules.index', [
            'modules' => $modulesData,
            'remoteModules' => $remoteModules,
            'flashes' => $flashes,
        ]);
    }

    /**
     * Enable a module.
     */
    public function enable(Request $request, string $alias): JsonResponse
    {
        /** @var \Nwidart\Modules\Module|null $module */
        $module = Module::find($alias);

        if (! $module) {
            return response()->json([
                'status' => 'error',
                'message' => __('Module not found'),
            ], 404);
        }

        try {
            $module->enable();

            \Illuminate\Support\Facades\Log::info("Module {$module->getName()} activated");

            $outputLog = new BufferedOutput;

            // Run module install command which handles migrations and symlinks
            Artisan::call('freescout:module-install', ['module_alias' => $module->getName()], $outputLog);
            $output = $outputLog->fetch();

            // Clear cache
            Artisan::call('cache:clear');
            Artisan::call('config:clear');

            $msg = __(':name module enabled successfully', ['name' => $module->getName()]);

            // Store flash message for the next request
            $flash = [
                'text' => '<strong>'.$msg.'</strong><pre class="margin-top">'.$output.'</pre>',
                'unescaped' => true,
                'type' => 'success',
            ];
            Cache::forever('modules_flash', $flash);

            return response()->json([
                'status' => 'success',
                'message' => $msg,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Disable a module.
     */
    public function disable(Request $request, string $alias): JsonResponse
    {
        /** @var \Nwidart\Modules\Module|null $module */
        $module = Module::find($alias);

        if (! $module) {
            return response()->json([
                'status' => 'error',
                'message' => __('Module not found'),
            ], 404);
        }

        try {
            $module->disable();

            $outputLog = new BufferedOutput;

            // Clear cache
            Artisan::call('freescout:clear-cache', [], $outputLog);
            $output = $outputLog->fetch();

            $msg = __(':name module disabled successfully', ['name' => $module->getName()]);

            // Store flash message for the next request
            $flash = [
                'text' => '<strong>'.$msg.'</strong><pre class="margin-top">'.$output.'</pre>',
                'unescaped' => true,
                'type' => 'success',
            ];
            Cache::forever('modules_flash', $flash);

            return response()->json([
                'status' => 'success',
                'message' => $msg,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a module.
     */
    public function delete(Request $request, string $alias): JsonResponse
    {
        /** @var \Nwidart\Modules\Module|null $module */
        $module = Module::find($alias);

        if (! $module) {
            return response()->json([
                'status' => 'error',
                'message' => __('Module not found'),
            ], 404);
        }

        try {
            // Disable module first
            if ($module->isEnabled()) {
                $module->disable();
            }

            // Delete module directory
            File::deleteDirectory($module->getPath());

            // Clear cache
            Artisan::call('cache:clear');
            Artisan::call('config:clear');

            return response()->json([
                'status' => 'success',
                'message' => __(':name module deleted successfully', ['name' => $module->getName()]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Install a module from the marketplace or GitHub.
     */
    public function install(Request $request): \Illuminate\Http\RedirectResponse
    {
        $githubUrl = $request->input('github_url');

        if ($githubUrl) {
            $githubToken = $request->input('github_token');
            $githubUrlStr = is_string($githubUrl) || is_int($githubUrl) || is_float($githubUrl) ? (string) $githubUrl : '';
            $githubTokenStr = ($githubToken && (is_string($githubToken) || is_int($githubToken) || is_float($githubToken))) ? (string) $githubToken : null;
            return $this->installFromGithub($githubUrlStr, $githubTokenStr);
        }

        $alias = $request->input('alias');

        if (! $alias) {
            return redirect()->back()->with('error', __('Module alias is required'));
        }

        // Get module details from WpApi
        $remoteModules = WpApi::getModules();
        $moduleInfo = null;

        foreach ($remoteModules as $module) {
            if (is_array($module) && isset($module['alias']) && $module['alias'] === $alias) {
                    $moduleInfo = $module;
                    break;
                }
        }

        if (! is_array($moduleInfo)) {
            return redirect()->back()->with('error', __('Module not found in marketplace'));
        }

        $downloadUrl = isset($moduleInfo['download_url']) && is_string($moduleInfo['download_url']) ? $moduleInfo['download_url'] : null;

        if (! $downloadUrl) {
            return redirect()->back()->with('error', __('Could not determine download URL for this module'));
        }

        $aliasStr = is_string($alias) || is_int($alias) || is_float($alias) ? (string) $alias : '';
        return $this->installFromUrl($downloadUrl, $aliasStr);
    }

    private function installFromGithub(string $url, ?string $token = null): \Illuminate\Http\RedirectResponse
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return redirect()->back()->with('error', __('Invalid GitHub URL'));
        }

        // Extract repo name
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path)) {
             return redirect()->back()->with('error', __('Invalid GitHub URL path'));
        }
        $parts = explode('/', trim($path, '/'));
        if (count($parts) < 2) {
            return redirect()->back()->with('error', __('Invalid GitHub URL format'));
        }
        $repoName = end($parts);
        $repoName = preg_replace('/\.git$/', '', strval($repoName));
        
        // Build authenticated URL if token provided
        if ($token) {
            $parsedUrl = parse_url($url);
            if (!is_array($parsedUrl)) {
                 return redirect()->back()->with('error', __('Invalid GitHub URL'));
            }
            // Ensure .git suffix for authenticated clone
            $path = $parsedUrl['path'] ?? '';
            if (!str_ends_with($path, '.git')) {
                $path .= '.git';
            }
            $url = sprintf(
                '%s://%s@%s%s',
                $parsedUrl['scheme'] ?? 'https',
                urlencode($token),
                $parsedUrl['host'] ?? '',
                $path
            );
        }
        
        // Convert kebab-case to PascalCase for module name if needed, 
        // but usually we clone into the repo name and let the module.json define the name.
        // However, Nwidart modules expects the folder name to match the module name in module.json usually.
        // Let's clone into a temp dir first to read module.json? 
        // Or just clone into Modules/$repoName and hope for the best.
        // Let's try to be smart and convert "crm-module" to "Crm".
        
        $moduleName = \Illuminate\Support\Str::studly(strval($repoName));
        // Remove "Module" suffix if present to avoid "CrmModuleModule" but keep if it's just "Module"
        if (str_ends_with($moduleName, 'Module') && strlen($moduleName) > 6) {
            $moduleName = substr($moduleName, 0, -6);
        }

        $targetPath = base_path("Modules/$moduleName");

        if (File::exists($targetPath)) {
            return redirect()->back()->with('error', __('Module directory already exists: :path', ['path' => $targetPath]));
        }

        try {
            // Use git clone
            $process = new \Symfony\Component\Process\Process(['git', 'clone', $url, $targetPath]);
            $process->setTimeout(120); // 2 minutes timeout
            $process->run();

            if (! $process->isSuccessful()) {
                $errorOutput = $process->getErrorOutput();
                $standardOutput = $process->getOutput();
                $fullError = trim($errorOutput ?: $standardOutput);
                
                // Log the full error but show a sanitized version (don't expose token)
                \Log::error('Git clone failed', [
                    'target' => $targetPath,
                    'stderr' => $errorOutput,
                    'stdout' => $standardOutput,
                    'exit_code' => $process->getExitCode(),
                ]);
                
                // Remove token from error message if present
                $sanitizedError = preg_replace('/https:\/\/[^@]+@/', 'https://*****@', $fullError);
                
                if (empty($sanitizedError)) {
                    $sanitizedError = __('Git clone failed with exit code :code', ['code' => $process->getExitCode()]);
                }
                
                throw new \Exception(__('Git clone failed: :error', ['error' => $sanitizedError]));
            }

            // Clear cache
            Artisan::call('cache:clear');
            Artisan::call('config:clear');

            // Try to find the module
            $module = Module::find($moduleName);

            $module->enable();
            
            // Run install command
            $outputLog = new BufferedOutput;
            Artisan::call('freescout:module-install', ['module_alias' => $module->getName()], $outputLog);
            
            Artisan::call('cache:clear');
            
            return redirect()->back()->with('success', __('Module installed from GitHub successfully'));

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function installFromUrl(string $downloadUrl, string $alias): \Illuminate\Http\RedirectResponse
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'mod_');

        try {
            // Download the file
            $response = Http::timeout(120)->sink($tempFile)->get($downloadUrl);

            if (! $response->successful()) {
                throw new \Exception(__('Failed to download module'));
            }

            // Unzip
            $zip = new \ZipArchive;
            if ($zip->open($tempFile) === true) {
                $extractPath = base_path('Modules');

                if (! File::isDirectory($extractPath)) {
                    File::makeDirectory($extractPath, 0755, true);
                }

                $zip->extractTo($extractPath);
                $zip->close();

                // Clean up temp file
                if (file_exists($tempFile)) {
                    try {
                        unlink($tempFile);
                    } catch (\Exception $unlinkError) {
                        \Illuminate\Support\Facades\Log::warning('Failed to cleanup temp file: '.$unlinkError->getMessage());
                    }
                }

                // Clear cache to ensure new module is detected
                Artisan::call('cache:clear');
                Artisan::call('config:clear');

                // Try to find and enable the module
                $module = Module::find($alias);

                $module->enable();

                // Run install command
                $outputLog = new BufferedOutput;
                Artisan::call('freescout:module-install', ['module_alias' => $module->getName()], $outputLog);

                // Clear cache again
                Artisan::call('cache:clear');

                return redirect()->back()->with('success', __('Module installed and enabled successfully'));
            } else {
                throw new \Exception(__('Failed to open zip file'));
            }

        } catch (\Exception $e) {
            if (file_exists($tempFile)) {
                try {
                    unlink($tempFile);
                } catch (\Exception $unlinkError) {
                    \Illuminate\Support\Facades\Log::warning('Failed to cleanup temp file: '.$unlinkError->getMessage());
                }
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * AJAX handler for module operations.
     */
    public function ajax(Request $request): JsonResponse
    {
        $action = $request->input('action');
        $alias = $request->input('alias');

        /** @var \App\Models\User|null $user */
        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        switch ($action) {
            case 'activate_license':
                return $this->ajaxActivateLicense($request);

            case 'deactivate_license':
                return $this->ajaxDeactivateLicense($request);

            case 'check_license':
                return $this->ajaxCheckLicense($request);

            case 'check_updates':
                return $this->ajaxCheckUpdates($request);

            case 'update_module':
                return $this->ajaxUpdateModule($request);

            case 'refresh_modules':
                Cache::forget('modules_directory');

                return response()->json(['success' => true, 'message' => __('Module list refreshed')]);

            default:
                return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
        }
    }

    /**
     * Activate a module license.
     */
    protected function ajaxActivateLicense(Request $request): JsonResponse
    {
        $alias = $request->input('alias');
        $license = $request->input('license');

        if (! $alias || ! $license) {
            return response()->json(['success' => false, 'message' => __('Module alias and license are required')]);
        }
        
        $aliasStr = is_string($alias) || is_int($alias) || is_float($alias) ? (string) $alias : '';
        $licenseStr = is_string($license) || is_int($license) || is_float($license) ? (string) $license : '';

        $result = WpApi::activateLicense([
            'module_alias' => $aliasStr,
            'license' => $licenseStr,
            'url' => url('/'),
        ]);

        if (! empty($result['license']) && $result['license'] === 'valid') {
            // Store the license
            $this->saveModuleLicense($aliasStr, $licenseStr);

            return response()->json([
                'success' => true,
                'message' => __('License activated successfully'),
                'data' => $result,
            ]);
        }

        $error = WpApi::getLastError();

        return response()->json([
            'success' => false,
            'message' => $error['message'] ?? __('License activation failed'),
        ]);
    }

    /**
     * Deactivate a module license.
     */
    protected function ajaxDeactivateLicense(Request $request): JsonResponse
    {
        $alias = $request->input('alias');

        if (! $alias) {
            return response()->json(['success' => false, 'message' => __('Module alias is required')]);
        }
        
        $aliasStr = is_string($alias) || is_int($alias) || is_float($alias) ? (string) $alias : '';

        $license = $this->getModuleLicense($aliasStr);

        if (! $license) {
            return response()->json(['success' => false, 'message' => __('No license found for this module')]);
        }

        $result = WpApi::deactivateLicense([
            'module_alias' => $aliasStr,
            'license' => $license,
            'url' => url('/'),
        ]);

        // Remove license from storage
        $this->removeModuleLicense($aliasStr);

        return response()->json([
            'success' => true,
            'message' => __('License deactivated successfully'),
        ]);
    }

    /**
     * Check a module license.
     */
    protected function ajaxCheckLicense(Request $request): JsonResponse
    {
        $alias = $request->input('alias');

        if (! $alias) {
            return response()->json(['success' => false, 'message' => __('Module alias is required')]);
        }
        
        $aliasStr = is_string($alias) || is_int($alias) || is_float($alias) ? (string) $alias : '';

        $license = $this->getModuleLicense($aliasStr);

        if (! $license) {
            return response()->json([
                'success' => true,
                'activated' => false,
                'message' => __('No license found'),
            ]);
        }

        $result = WpApi::checkLicense([
            'module_alias' => $aliasStr,
            'license' => $license,
            'url' => url('/'),
        ]);

        $activated = ! empty($result['license']) && $result['license'] === 'valid';

        return response()->json([
            'success' => true,
            'activated' => $activated,
            'data' => $result,
        ]);
    }

    /**
     * Check for module updates.
     */
    protected function ajaxCheckUpdates(Request $request): JsonResponse
    {
        $updates = [];

        $modules = Module::all();
        foreach ($modules as $module) {
            $alias = $module->getLowerName();
            $currentVersion = $module->get('version', '1.0.0');
            $modulePath = $module->getPath();

            // Check GitHub updates if it's a git repo
            if (File::isDirectory($modulePath . '/.git')) {
                $gitUpdate = $this->checkGithubUpdate($modulePath, $currentVersion);
                if ($gitUpdate) {
                    $updates[$alias] = $gitUpdate;
                }
                continue;
            }

            $result = WpApi::getVersion([
                'module_alias' => $alias,
            ]);

            if (! empty($result['version'])) {
                $resultVersion = is_string($result['version']) || is_int($result['version']) || is_float($result['version']) ? (string) $result['version'] : '0.0.0';
                if (version_compare($resultVersion, $currentVersion, '>')) {
                    $updates[$alias] = [
                        'current' => $currentVersion,
                        'available' => $result['version'],
                        'download_url' => $result['download_url'] ?? null,
                        'type' => 'marketplace',
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'updates' => $updates,
            'count' => count($updates),
        ]);
    }

    /**
     * Check for updates from GitHub.
     * 
     * @return array{current: string, available: string, commits_behind: int, type: string, branch: string}|null
     */
    private function checkGithubUpdate(string $modulePath, string $currentVersion): ?array
    {
        try {
            // Get current branch
            $process = new \Symfony\Component\Process\Process(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], $modulePath);
            $process->run();
            if (!$process->isSuccessful()) {
                return null;
            }
            $branch = trim($process->getOutput());

            // Get current commit hash
            $process = new \Symfony\Component\Process\Process(['git', 'rev-parse', 'HEAD'], $modulePath);
            $process->run();
            if (!$process->isSuccessful()) {
                return null;
            }
            $localHash = trim($process->getOutput());

            // Fetch latest from remote (without pulling)
            $process = new \Symfony\Component\Process\Process(['git', 'fetch', 'origin', $branch], $modulePath);
            $process->setTimeout(30);
            $process->run();
            if (!$process->isSuccessful()) {
                return null;
            }

            // Get remote commit hash
            $process = new \Symfony\Component\Process\Process(['git', 'rev-parse', "origin/$branch"], $modulePath);
            $process->run();
            if (!$process->isSuccessful()) {
                return null;
            }
            $remoteHash = trim($process->getOutput());

            // Compare hashes
            if ($localHash !== $remoteHash) {
                // Count commits behind
                $process = new \Symfony\Component\Process\Process(
                    ['git', 'rev-list', '--count', "$localHash..origin/$branch"],
                    $modulePath
                );
                $process->run();
                $commitsBehind = (int) trim($process->getOutput());

                return [
                    'current' => $currentVersion . ' (' . substr($localHash, 0, 7) . ')',
                    'available' => $currentVersion . ' (' . substr($remoteHash, 0, 7) . ')',
                    'commits_behind' => $commitsBehind,
                    'type' => 'github',
                    'branch' => $branch,
                ];
            }
            
        } catch (\Exception $e) {
            \Log::error('GitHub update check failed', [
                'path' => $modulePath,
                'error' => $e->getMessage(),
            ]);
        }
        
        return null;
    }

    /**
     * Update a module.
     */
    protected function ajaxUpdateModule(Request $request): JsonResponse
    {
        $alias = $request->input('alias');

        if (! $alias) {
            return response()->json(['success' => false, 'message' => __('Module alias is required')]);
        }

        $aliasStr = is_string($alias) || is_int($alias) || is_float($alias) ? (string) $alias : '';
        $module = Module::find($aliasStr);

        // Check if it's a git repo
        if (File::isDirectory($module->getPath() . '/.git')) {
            return $this->updateFromGithub($module);
        }

        $result = WpApi::getVersion([
            'module_alias' => $alias,
        ]);

        if (empty($result['download_url'])) {
            return response()->json(['success' => false, 'message' => __('Update URL not available')]);
        }

        $downloadUrl = is_string($result['download_url']) || is_int($result['download_url']) || is_float($result['download_url']) ? (string) $result['download_url'] : '';
        $tempFile = tempnam(sys_get_temp_dir(), 'mod_update_');

        try {
            $response = Http::timeout(120)->sink($tempFile)->get($downloadUrl);

            if (! $response->successful()) {
                throw new \Exception(__('Failed to download update'));
            }

            $modulePath = $module->getPath();

            // Disable module during update
            $wasEnabled = $module->isEnabled();
            if ($wasEnabled) {
                $module->disable();
            }

            // Backup current module
            $backupPath = storage_path('app/module_backups/'.$aliasStr.'_'.date('Y-m-d_His'));
            if (! File::isDirectory(dirname($backupPath))) {
                File::makeDirectory(dirname($backupPath), 0755, true);
            }
            File::copyDirectory($modulePath, $backupPath);

            // Extract update
            $zip = new \ZipArchive;
            if ($zip->open($tempFile) === true) {
                File::deleteDirectory($modulePath);
                $zip->extractTo(dirname($modulePath));
                $zip->close();
            }

            if (file_exists($tempFile)) {
                try {
                    unlink($tempFile);
                } catch (\Exception $unlinkError) {
                    \Illuminate\Support\Facades\Log::warning('Failed to cleanup temp file: '.$unlinkError->getMessage());
                }
            }

            // Re-enable module if it was enabled
            if ($wasEnabled) {
                $refoundModule = ($aliasStr !== '') ? Module::find($aliasStr) : null;
                if ($refoundModule !== null) {
                    $refoundModule->enable();

                    // Run module install for migrations
                    $outputLog = new BufferedOutput;
                    Artisan::call('freescout:module-install', ['module_alias' => $module->getName()], $outputLog);
                }
            }

            // Clear cache
            Artisan::call('cache:clear');

            return response()->json([
                'success' => true,
                'message' => __('Module updated successfully'),
                'new_version' => $result['version'] ?? '',
            ]);

        } catch (\Exception $e) {
            if (file_exists($tempFile)) {
                try {
                    unlink($tempFile);
                } catch (\Exception $unlinkError) {
                    \Illuminate\Support\Facades\Log::warning('Failed to cleanup temp file: '.$unlinkError->getMessage());
                }
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update module from GitHub.
     */
    private function updateFromGithub(\Nwidart\Modules\Module $module): JsonResponse
    {
        try {
            $path = $module->getPath();
            
            // Stash any local changes to avoid merge conflicts
            $stashProcess = new \Symfony\Component\Process\Process(['git', 'stash'], $path);
            $stashProcess->setTimeout(30);
            $stashProcess->run();
            $hasStash = str_contains($stashProcess->getOutput(), 'Saved working directory');
            
            // Fetch and pull
            $process = new \Symfony\Component\Process\Process(['git', 'pull'], $path);
            $process->setTimeout(120);
            $process->run();
            
            if (!$process->isSuccessful()) {
                // Try to restore stashed changes even on failure
                if ($hasStash) {
                    $restoreProcess = new \Symfony\Component\Process\Process(['git', 'stash', 'pop'], $path);
                    $restoreProcess->run();
                }
                throw new \Exception(__('Git pull failed: :error', ['error' => $process->getErrorOutput()]));
            }
            
            // Restore stashed changes if any
            if ($hasStash) {
                $restoreProcess = new \Symfony\Component\Process\Process(['git', 'stash', 'pop'], $path);
                $restoreProcess->run();
                
                // If stash pop fails (conflicts), provide helpful message
                if (!$restoreProcess->isSuccessful()) {
                    $message = __('Module updated, but your local changes conflicted. Please resolve conflicts in: :path', ['path' => $path]);
                    return response()->json(['success' => true, 'message' => $message, 'warning' => true]);
                }
            }
            
            // Check for pending migrations
            $hasMigrations = File::isDirectory($path . '/Database/Migrations');
            
            // Run install command (includes migrations)
            $outputLog = new BufferedOutput;
            Artisan::call('freescout:module-install', ['module_alias' => $module->getName()], $outputLog);
            
            Artisan::call('cache:clear');
            
            $message = __('Module updated from GitHub successfully');
            if ($hasMigrations) {
                $message .= '. ' . __('Database migrations have been run.');
            }
            if ($hasStash) {
                $message .= ' ' . __('Your local changes were preserved.');
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Get module license from storage.
     */
    protected function getModuleLicense(string $alias): ?string
    {
        $licenses = $this->getAllLicenses();

        return $licenses[$alias] ?? null;
    }

    /**
     * Save module license to storage.
     */
    protected function saveModuleLicense(string $alias, string $license): void
    {
        $licenses = $this->getAllLicenses();
        $licenses[$alias] = $license;
        $this->saveLicenses($licenses);
    }

    /**
     * Remove module license from storage.
     */
    protected function removeModuleLicense(string $alias): void
    {
        $licenses = $this->getAllLicenses();
        unset($licenses[$alias]);
        $this->saveLicenses($licenses);
    }

    /**
     * Check if module license is activated.
     */
    protected function isLicenseActivated(string $alias): bool
    {
        return $this->getModuleLicense($alias) !== null;
    }

    /**
     * Get all licenses from storage.
     *
     * @return array<string, string>
     */
    protected function getAllLicenses(): array
    {
        $licensesJson = \App\Models\Option::get('module_licenses');
        if ($licensesJson) {
            $licensesJsonStr = is_string($licensesJson) || is_int($licensesJson) || is_float($licensesJson) ? (string) $licensesJson : '';
            $decoded = json_decode($licensesJsonStr, true);

            if (is_array($decoded)) {
                $result = [];
                foreach ($decoded as $key => $value) {
                    if (is_string($value) || is_int($value) || is_float($value)) {
                        $result[$key] = (string) $value;
                    }
                }
                return $result;
            }
        }

        return [];
    }

    /**
     * Save all licenses to storage.
     *
     * @param  array<string, string>  $licenses
     */
    protected function saveLicenses(array $licenses): void
    {
        \App\Models\Option::set('module_licenses', json_encode($licenses));
    }
}

