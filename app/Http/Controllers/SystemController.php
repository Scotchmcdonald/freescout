<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use App\Models\Theme;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\BufferedOutput;

class SystemController extends Controller
{
    /**
     * Display system status and tools.
     */
    public function index(): View|Factory
    {
        $stats = [
            'users' => User::count(),
            'mailboxes' => Mailbox::count(),
            'conversations' => Conversation::count(),
            'customers' => Customer::count(),
            'threads' => Thread::count(),
            'active_conversations' => Conversation::where('status', 1)->count(),
            'unassigned_conversations' => Conversation::whereNull('user_id')->where('status', 1)->count(),
        ];

        // Get database version based on driver
        $dbVersion = 'Unknown';
        try {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'mysql') {
                $dbVersion = DB::select('SELECT VERSION() as version')[0]->version ?? 'Unknown';
            } elseif ($driver === 'sqlite') {
                $dbVersion = DB::select('SELECT sqlite_version() as version')[0]->version ?? 'Unknown';
            } elseif ($driver === 'pgsql') {
                $dbVersion = DB::select('SELECT version()')[0]->version ?? 'Unknown';
            }
        } catch (\Exception $e) {
            $dbVersion = 'Unknown';
        }

        // PHP extensions check
        $phpExtensions = $this->checkRequiredExtensions();

        // Required functions check
        $requiredFunctions = $this->checkRequiredFunctions();

        // Directory permissions check
        $permissions = $this->checkDirectoryPermissions();

        // Check if .env is writable
        $envIsWritable = is_writable(base_path('.env'));

        // Check public symlink
        $publicSymlinkExists = file_exists(public_path('storage'));

        /** @var array<string, mixed> $systemInfo */
        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_version' => $dbVersion,
            'disk_free' => disk_free_space('/'),
            'disk_total' => disk_total_space('/'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'last_run_fetch' => cache()->get('last_run_fetch'),
            'last_run_queue' => cache()->get('last_run_queue'),
            'php_extensions' => $phpExtensions,
            'required_functions' => $requiredFunctions,
            'permissions' => $permissions,
            'env_is_writable' => $envIsWritable,
            'public_symlink_exists' => $publicSymlinkExists,
        ];

        // Get application update info
        $updateInfo = $this->checkForAppUpdates();

        $viewName = 'system.index';

        return view($viewName, compact('stats', 'systemInfo', 'updateInfo'));
    }

    /**
     * Check required PHP extensions.
     *
     * @return array<string, bool>
     */
    protected function checkRequiredExtensions(): array
    {
        $required = [
            'mbstring',
            'openssl',
            'pdo',
            'tokenizer',
            'xml',
            'ctype',
            'json',
            'curl',
            'gd',
            'imap',
            'zip',
            'fileinfo',
        ];

        $result = [];
        foreach ($required as $ext) {
            $result[$ext] = extension_loaded($ext);
        }

        return $result;
    }

    /**
     * Check required PHP functions.
     *
     * @return array<string, bool>
     */
    protected function checkRequiredFunctions(): array
    {
        $required = [
            'proc_open',
            'proc_close',
            'proc_get_status',
            'shell_exec',
            'putenv',
            'symlink',
        ];

        /** @var array<string> $disabledFunctions */
        $disabledFunctions = array_map('trim', explode(',', ini_get('disable_functions') ?: ''));

        $result = [];
        foreach ($required as $func) {
            $result[$func] = ! in_array($func, $disabledFunctions, true);
        }

        return $result;
    }

    /**
     * Check directory permissions.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function checkDirectoryPermissions(): array
    {
        $directories = [
            'storage' => storage_path(),
            'storage/app' => storage_path('app'),
            'storage/framework' => storage_path('framework'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
            'public' => public_path(),
        ];

        $result = [];
        foreach ($directories as $name => $path) {
            $result[$name] = [
                'path' => $path,
                'writable' => is_writable($path),
                'exists' => file_exists($path),
            ];
        }

        return $result;
    }

    /**
     * System tools page.
     */
    public function tools(Request $request): View|Factory
    {
        $output = cache()->get('tools_execute_output');
        if ($output) {
            cache()->forget('tools_execute_output');
        }

        $cronHash = self::getWebCronHash();

        return view('system.tools', [
            'output' => $output,
            'cronHash' => $cronHash,
        ]);
    }

    /**
     * Execute tools action.
     */
    public function toolsExecute(Request $request): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
            abort(403);
        }

        $outputLog = new BufferedOutput;

        switch ($request->input('action')) {
            case 'clear_cache':
                Artisan::call('cache:clear', [], $outputLog);
                Artisan::call('config:clear', [], $outputLog);
                Artisan::call('route:clear', [], $outputLog);
                Artisan::call('view:clear', [], $outputLog);
                break;

            case 'fetch_emails':
                $params = [];
                if ($request->filled('days')) {
                    $daysInput = $request->input('days');
                    $params['--days'] = is_numeric($daysInput) ? intval($daysInput) : 0;
                }
                Artisan::call('freescout:fetch-emails', $params, $outputLog);
                break;

            case 'migrate_db':
                Artisan::call('migrate', ['--force' => true], $outputLog);
                break;

            case 'optimize':
                // Ensure theme directories exist before optimizing
                $themes = Theme::all();
                foreach ($themes as $theme) {
                    $themePath = base_path('themes/' . $theme->name . '/views');
                    if (!File::exists($themePath)) {
                        File::makeDirectory($themePath, 0755, true);
                    }
                }
                Artisan::call('optimize', [], $outputLog);
                break;
        }

        $output = $outputLog->fetch();

        if ($output) {
            cache()->forever('tools_execute_output', $output);
        }

        return redirect()->route('system.tools')
            ->withInput($request->only(['days']))
            ->with('flash_success', 'Action executed successfully.');
    }

    /**
     * Run system diagnostics.
     */
    public function diagnostics(): JsonResponse
    {
        $checks = [];

        // Database connection
        try {
            DB::connection()->getPdo();
            $checks['database'] = ['status' => 'ok', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            $checks['database'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        // Storage writable
        $storagePath = storage_path();
        $checks['storage'] = [
            'status' => is_writable($storagePath) ? 'ok' : 'error',
            'message' => is_writable($storagePath) ? 'Storage directory is writable' : 'Storage directory is not writable',
        ];

        // Cache working
        try {
            cache()->put('test_key', 'test_value', 60);
            $value = cache()->get('test_key');
            $checks['cache'] = [
                'status' => $value === 'test_value' ? 'ok' : 'error',
                'message' => $value === 'test_value' ? 'Cache is working' : 'Cache test failed',
            ];
        } catch (\Exception $e) {
            $checks['cache'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        // Required PHP extensions
        $phpExtensions = $this->checkRequiredExtensions();
        $missingExtensions = array_keys(array_filter($phpExtensions, fn ($loaded) => ! $loaded));

        $checks['extensions'] = [
            'status' => empty($missingExtensions) ? 'ok' : 'warning',
            'message' => empty($missingExtensions)
                ? 'All required PHP extensions are loaded'
                : 'Missing extensions: '.implode(', ', $missingExtensions),
        ];

        return response()->json([
            'success' => true,
            'checks' => $checks,
        ]);
    }

    /**
     * Execute system commands via AJAX.
     */
    public function ajax(Request $request): JsonResponse
    {
        $action = $request->input('action');

        /** @var \App\Models\User $user */
        $user = $request->user();
        if (! $user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        switch ($action) {
            case 'clear_cache':
                $results = [];
                $commands = [
                    'cache:clear' => 'Application Cache',
                    'config:clear' => 'Configuration Cache',
                    'route:clear' => 'Route Cache',
                    'view:clear' => 'Compiled Views',
                    'event:clear' => 'Event Cache',
                    'optimize:clear' => 'Optimization Files',
                ];

                $hasError = false;

                foreach ($commands as $command => $label) {
                    try {
                        Artisan::call($command);
                        $results[$label] = [
                            'status' => 'ok',
                            'message' => trim(Artisan::output()) ?: "$label cleared.",
                        ];
                    } catch (\Exception $e) {
                        $hasError = true;
                        $results[$label] = [
                            'status' => 'error',
                            'message' => $e->getMessage(),
                        ];
                    }
                }

                return response()->json([
                    'success' => !$hasError,
                    'message' => $hasError ? 'Some caches failed to clear.' : 'All caches cleared successfully.',
                    'details' => $results,
                ]);

            case 'optimize':
                try {
                    // Ensure theme directories exist before optimizing to prevent errors
                    $themes = Theme::all();
                    foreach ($themes as $theme) {
                        $themePath = base_path('themes/' . $theme->name . '/views');
                        if (!File::exists($themePath)) {
                            File::makeDirectory($themePath, 0755, true);
                        }
                    }
                    
                    // Ensure module view directories exist
                    $modulesPath = base_path('Modules');
                    if (File::exists($modulesPath)) {
                        $modules = File::directories($modulesPath);
                        foreach ($modules as $modulePath) {
                            $moduleName = basename($modulePath);
                            
                            // Create Resources/views directory if it doesn't exist
                            $moduleViewsPath = $modulePath . '/Resources/views';
                            if (!File::exists($moduleViewsPath)) {
                                File::makeDirectory($moduleViewsPath, 0755, true);
                            }
                            
                            // Create resources/views/modules/{module} symlink/directory
                            $publicModuleViewPath = resource_path('views/modules/' . strtolower($moduleName));
                            if (!File::exists($publicModuleViewPath)) {
                                File::makeDirectory($publicModuleViewPath, 0755, true);
                            }
                        }
                    }

                    Artisan::call('optimize');

                    return response()->json([
                        'success' => true,
                        'message' => 'Application optimized successfully.',
                        'output' => Artisan::output(),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Optimization failed: ' . $e->getMessage());
                    Log::error($e->getTraceAsString());
                    return response()->json([
                        'success' => false,
                        'message' => 'Optimization failed: '.$e->getMessage(),
                    ], 500);
                }

            case 'rebuild_npm':
                try {
                    // Increase time limit for build process
                    set_time_limit(300);
                    
                    $basePath = base_path();
                    $output = [];
                    $returnVar = 0;
                    
                    // Try to find npm
                    $npm = 'npm';
                    // Common paths for npm if not in PATH
                    $possiblePaths = [
                        '/usr/bin/npm',
                        '/usr/local/bin/npm',
                        '/root/.nvm/versions/node/v*/bin/npm', // NVM support
                    ];
                    
                    // Check if npm is in PATH
                    exec('which npm', $whichOutput, $whichReturn);
                    if ($whichReturn !== 0) {
                        foreach ($possiblePaths as $path) {
                            $glob = glob($path);
                            if (!empty($glob)) {
                                $npm = $glob[0];
                                break;
                            }
                        }
                    }

                    // Run build command
                    $command = "cd {$basePath} && {$npm} run build 2>&1";
                    exec($command, $output, $returnVar);
                    
                    $outputStr = implode("\n", $output);

                    if ($returnVar !== 0) {
                        throw new \Exception("Build failed with exit code {$returnVar}. Output: {$outputStr}");
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'Assets rebuilt successfully.',
                        'output' => $outputStr,
                    ]);
                } catch (\Exception $e) {
                    Log::error('NPM Build failed: ' . $e->getMessage());
                    return response()->json([
                        'success' => false,
                        'message' => 'Build failed: ' . $e->getMessage(),
                    ], 500);
                }

            case 'queue_work':
                try {
                    // Start queue worker in background
                    exec('php artisan queue:work --daemon > /dev/null 2>&1 &');

                    return response()->json([
                        'success' => true,
                        'message' => 'Queue worker started.',
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to start queue worker: '.$e->getMessage(),
                    ], 500);
                }

            case 'fetch_mail':
                try {
                    // Trigger mail fetching command
                    Artisan::call('freescout:fetch-emails');

                    return response()->json([
                        'success' => true,
                        'message' => 'Email fetching completed.',
                        'output' => Artisan::output(),
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Email fetching failed: '.$e->getMessage(),
                    ], 500);
                }

            case 'system_info':
                $info = [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'db_connection' => config('database.default'),
                    'cache_driver' => config('cache.default'),
                    'queue_connection' => config('queue.default'),
                    'session_driver' => config('session.driver'),
                    'timezone' => config('app.timezone'),
                    'locale' => config('app.locale'),
                ];

                return response()->json([
                    'success' => true,
                    'info' => $info,
                ]);

            case 'cancel_job':
                try {
                    $jobId = $request->input('job_id');
                    DB::table('jobs')->where('id', $jobId)->delete();

                    return response()->json([
                        'success' => true,
                        'message' => 'Job cancelled successfully.',
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to cancel job: '.$e->getMessage(),
                    ], 500);
                }

            case 'retry_job':
                try {
                    $jobId = $request->input('job_id');
                    DB::table('jobs')->where('id', $jobId)->update(['available_at' => time()]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Job retry queued.',
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to retry job: '.$e->getMessage(),
                    ], 500);
                }

            default:
                return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
        }
    }

    /**
     * View application logs.
     */
    public function logs(Request $request): View|Factory
    {
        $type = $request->get('type', 'application');

        $data = [];

        switch ($type) {
            case 'application':
                $logFile = storage_path('logs/laravel.log');
                $lines = [];

                if (file_exists($logFile)) {
                    $content = file_get_contents($logFile);
                    $content = $content !== false ? $content : '';
                    $lines = array_slice(explode("\n", $content), -100); // Last 100 lines
                }

                $data = ['lines' => $lines];
                break;

            case 'email':
                // Get recent email send logs
                $sendLogs = \App\Models\SendLog::with(['user', 'customer'])
                    ->latest()
                    ->paginate(50);

                $data = ['sendLogs' => $sendLogs];
                break;

            case 'activity':
                // Get recent activity logs
                $activityLogs = ActivityLog::with(['causer'])
                    ->latest()
                    ->paginate(50);

                $data = ['activityLogs' => $activityLogs];
                break;

            case 'login':
                // Get login-related activity logs
                $loginLogs = ActivityLog::with(['causer'])
                    ->where('log_name', ActivityLog::NAME_USER)
                    ->whereIn('description', [
                        ActivityLog::DESCRIPTION_USER_LOGIN,
                        ActivityLog::DESCRIPTION_USER_LOGIN_FAILED,
                        ActivityLog::DESCRIPTION_USER_LOCKED,
                        ActivityLog::DESCRIPTION_USER_LOGOUT,
                    ])
                    ->latest()
                    ->paginate(50);

                $data = ['loginLogs' => $loginLogs];
                break;
        }

        $data['currentType'] = $type;
        $data['availableLogs'] = ActivityLog::getAvailableLogs();

        $viewName = 'system.logs';

        return view($viewName, $data);
    }

    /**
     * Clear logs.
     */
    public function clearLogs(Request $request): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
            abort(403);
        }

        $type = $request->input('type', 'application');

        switch ($type) {
            case 'application':
                $logFile = storage_path('logs/laravel.log');
                if (file_exists($logFile)) {
                    file_put_contents($logFile, '');
                }
                break;

            case 'activity':
                $logName = $request->input('log_name');
                if ($logName) {
                    ActivityLog::where('log_name', $logName)->delete();
                }
                break;
        }

        return redirect()->route('system.logs', ['type' => $type])
            ->with('success', 'Logs cleared successfully.');
    }

    /**
     * Check for updates.
     */
    public function update(): View|Factory
    {
        $updateInfo = $this->checkForAppUpdates();
        
        return view('system.update', [
            'update_available' => !empty($updateInfo),
            'update_info' => $updateInfo,
        ]);
    }
    
    /**
     * Check for application updates from git repository.
     * 
     * @return array{current_version: mixed, current_commit: string, remote_commit?: string, commits_behind?: int, branch: string, latest_message?: string, has_update: bool}|null
     */
    private function checkForAppUpdates(): ?array
    {
        try {
            $appPath = base_path();
            
            // Get current version from config
            $currentVersion = config('app.version', '1.0.0');
            
            // Get current branch
            $process = new \Symfony\Component\Process\Process(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], $appPath);
            $process->run();
            if (!$process->isSuccessful()) {
                return null;
            }
            $branch = trim($process->getOutput());

            // Get current commit hash
            $process = new \Symfony\Component\Process\Process(['git', 'rev-parse', 'HEAD'], $appPath);
            $process->run();
            if (!$process->isSuccessful()) {
                return null;
            }
            $localHash = trim($process->getOutput());
            $localHashShort = substr($localHash, 0, 7);
            
            // Get repository URL
            $process = new \Symfony\Component\Process\Process(['git', 'config', '--get', 'remote.origin.url'], $appPath);
            $process->run();
            $repoUrl = trim($process->getOutput());
            
            // Convert git URL to web URL for commit links
            $commitBaseUrl = null;
            if (preg_match('/github\.com[:\/](.+?)(\.git)?$/', $repoUrl, $matches)) {
                $repoPath = rtrim($matches[1], '.git');
                $commitBaseUrl = "https://github.com/{$repoPath}/commit";
            } elseif (preg_match('/gitlab\.com[:\/](.+?)(\.git)?$/', $repoUrl, $matches)) {
                $repoPath = rtrim($matches[1], '.git');
                $commitBaseUrl = "https://gitlab.com/{$repoPath}/-/commit";
            } elseif (preg_match('/bitbucket\.org[:\/](.+?)(\.git)?$/', $repoUrl, $matches)) {
                $repoPath = rtrim($matches[1], '.git');
                $commitBaseUrl = "https://bitbucket.org/{$repoPath}/commits";
            }

            // Fetch latest from remote (without pulling)
            $process = new \Symfony\Component\Process\Process(['git', 'fetch', 'origin', $branch], $appPath);
            $process->setTimeout(30);
            $process->run();
            if (!$process->isSuccessful()) {
                return null;
            }

            // Get remote commit hash
            $process = new \Symfony\Component\Process\Process(['git', 'rev-parse', "origin/$branch"], $appPath);
            $process->run();
            if (!$process->isSuccessful()) {
                return null;
            }
            $remoteHash = trim($process->getOutput());
            $remoteHashShort = substr($remoteHash, 0, 7);

            // Compare hashes
            if ($localHash !== $remoteHash) {
                // Count commits behind
                $process = new \Symfony\Component\Process\Process(
                    ['git', 'rev-list', '--count', "$localHash..origin/$branch"],
                    $appPath
                );
                $process->run();
                $commitsBehind = (int) trim($process->getOutput());

                // Get latest commit message
                $process = new \Symfony\Component\Process\Process(
                    ['git', 'log', '--format=%s', '-n', '1', "origin/$branch"],
                    $appPath
                );
                $process->run();
                $latestCommitMessage = trim($process->getOutput());

                return [
                    'current_version' => $currentVersion,
                    'current_commit' => $localHashShort,
                    'current_commit_url' => $commitBaseUrl ? "{$commitBaseUrl}/{$localHash}" : null,
                    'remote_commit' => $remoteHashShort,
                    'remote_commit_url' => $commitBaseUrl ? "{$commitBaseUrl}/{$remoteHash}" : null,
                    'commits_behind' => $commitsBehind,
                    'branch' => $branch,
                    'latest_message' => $latestCommitMessage,
                    'has_update' => true,
                ];
            }
            
            // No updates available - return current info without updates
            return [
                'current_version' => $currentVersion,
                'current_commit' => $localHashShort,
                'current_commit_url' => $commitBaseUrl ? "{$commitBaseUrl}/{$localHash}" : null,
                'branch' => $branch,
                'commits_behind' => 0,
                'has_update' => false,
            ];
            
        } catch (\Exception $e) {
            Log::error('App update check failed', [
                'error' => $e->getMessage(),
            ]);
        }
        
        return null;
    }
    
    /**
     * Get app update banner data for admins.
     */
    public function checkUpdateBanner(Request $request): JsonResponse
    {
        // Only check once per hour
        if (Cache::has('app_update_banner')) {
            $cached = Cache::get('app_update_banner');
            return response()->json($cached);
        }
        
        $updateInfo = $this->checkForAppUpdates();
        
        // Only show banner if there's an actual update (not just info)
        $hasUpdate = $updateInfo && $updateInfo['has_update'] === true;
        
        $result = [
            'has_update' => $hasUpdate,
            'update_info' => $hasUpdate ? $updateInfo : null,
        ];
        
        Cache::put('app_update_banner', $result, now()->addHour());
        
        return response()->json($result);
    }
    
    /**
     * Perform git pull to update application.
     */
    public function pullUpdate(Request $request): JsonResponse
    {
        try {
            $appPath = base_path();
            
            // Stash any local changes
            $stashProcess = new \Symfony\Component\Process\Process(['git', 'stash'], $appPath);
            $stashProcess->setTimeout(30);
            $stashProcess->run();
            $hasStash = str_contains($stashProcess->getOutput(), 'Saved working directory');
            
            // Pull latest changes
            $process = new \Symfony\Component\Process\Process(['git', 'pull'], $appPath);
            $process->setTimeout(120);
            $process->run();
            
            if (!$process->isSuccessful()) {
                // Try to restore stashed changes even on failure
                if ($hasStash) {
                    $restoreProcess = new \Symfony\Component\Process\Process(['git', 'stash', 'pop'], $appPath);
                    $restoreProcess->run();
                }
                throw new \Exception('Git pull failed: ' . $process->getErrorOutput());
            }
            
            // Restore stashed changes if any
            if ($hasStash) {
                $restoreProcess = new \Symfony\Component\Process\Process(['git', 'stash', 'pop'], $appPath);
                $restoreProcess->run();
                
                if (!$restoreProcess->isSuccessful()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Update downloaded, but local changes conflicted. Please resolve conflicts manually.',
                        'warning' => true,
                        'needs_migration' => true,
                    ]);
                }
            }
            
            // Clear update cache
            Cache::forget('app_update_banner');
            Cache::forget('app_update_checked');
            
            return response()->json([
                'success' => true,
                'message' => 'Application updated successfully. Please run migrations if needed.',
                'needs_migration' => true,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download application logs.
     */
    public function downloadLogs(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $logFile = storage_path('logs/laravel.log');

        if (! file_exists($logFile)) {
            abort(404, 'Log file not found');
        }

        return response()->download($logFile, 'laravel-'.date('Y-m-d').'.log');
    }

    /**
     * List failed jobs.
     */
    public function failedJobs(): View|Factory
    {
        $failedJobs = DB::table('failed_jobs')->orderBy('failed_at', 'desc')->paginate(50);

        return view('system.failed_jobs', compact('failedJobs'));
    }

    /**
     * Retry a failed job.
     */
    public function retryFailedJob(Request $request, string $uuid): JsonResponse
    {
        try {
            Artisan::call('queue:retry', ['id' => [$uuid]]);

            return response()->json([
                'success' => true,
                'message' => 'Job retry queued successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry job: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a failed job.
     */
    public function deleteFailedJob(Request $request, string $uuid): JsonResponse
    {
        try {
            Artisan::call('queue:forget', ['id' => [$uuid]]);

            return response()->json([
                'success' => true,
                'message' => 'Failed job deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete job: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete all failed jobs for a queue.
     */
    public function deleteFailedJobsForQueue(Request $request): JsonResponse
    {
        try {
            $queue = $request->input('queue');
            DB::table('failed_jobs')->where('queue', $queue)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Failed jobs deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete jobs: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retry all failed jobs for a queue.
     */
    public function retryFailedJobsForQueue(Request $request): JsonResponse
    {
        try {
            $queue = $request->input('queue');
            $jobs = DB::table('failed_jobs')->where('queue', $queue)->get();

            foreach ($jobs as $job) {
                Artisan::call('queue:retry', ['id' => [$job->uuid]]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Failed jobs retried successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry jobs: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Perform system update (migrations, cache clear).
     */
    public function performUpdate(Request $request): RedirectResponse
    {
        try {
            // Increase memory limit
            ini_set('memory_limit', '256M');

            Artisan::call('freescout:update', ['--force' => true]);

            return back()->with('status', 'Update script ran successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Update failed: '.$e->getMessage());
        }
    }

    /**
     * Web cron endpoint for HTTP-based cron triggering.
     */
    public function cron(Request $request, string $hash): \Illuminate\Http\Response
    {
        // Verify the hash matches using a secure comparison
        $expectedHash = self::getWebCronHash();

        if (! hash_equals($expectedHash, $hash)) {
            abort(404);
        }

        $outputLog = new BufferedOutput;
        Artisan::call('schedule:run', [], $outputLog);
        $output = $outputLog->fetch();

        return response($output, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Get the web cron hash using a secure algorithm.
     */
    public static function getWebCronHash(): string
    {
        $appKey = config('app.key');
        $appKeyStr = is_string($appKey) || is_int($appKey) || is_float($appKey) ? (string) $appKey : '';
        return hash_hmac('sha256', 'cron', $appKeyStr);
    }
}
