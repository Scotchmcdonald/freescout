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
     * Display module activity logs.
     */
    public function activityLog(): View|ViewFactory
    {
        $logs = \App\Models\ModuleActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('modules.activity', compact('logs'));
    }

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
            $commitHash = $this->getModuleCommitHash($module->getPath());
            $githubUrl = $this->getModuleGithubUrl($module->getPath());
            $commitUrl = $githubUrl && $commitHash ? $githubUrl . '/commit/' . $commitHash : null;
            
            $modulesData[] = [
                'name' => $module->getName(),
                'alias' => $module->getLowerName(),
                'description' => $module->getDescription(),
                'enabled' => $module->isEnabled(),
                'version' => $module->get('version', '1.0.0'),
                'commit' => $commitHash,
                'commit_url' => $commitUrl,
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

            // Log activity
            $this->logActivity($module->getName(), 'enable');

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

            // Log activity
            $this->logActivity($module->getName(), 'disable');

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
            $moduleName = $module->getName();
            
            // Disable module first
            if ($module->isEnabled()) {
                $module->disable();
            }

            // Delete module directory
            File::deleteDirectory($module->getPath());

            // Clear cache
            Artisan::call('cache:clear');
            Artisan::call('config:clear');

            // Log activity
            $this->logActivity($moduleName, 'delete');

            return response()->json([
                'status' => 'success',
                'message' => __(':name module deleted successfully', ['name' => $moduleName]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the install module form.
     */
    public function showInstallForm(): \Illuminate\Contracts\View\View
    {
        $repositories = config('modules_catalog.repositories', []);
        $encryptedToken = \App\Models\Option::get('github_personal_access_token');
        $savedToken = $encryptedToken ? \Illuminate\Support\Facades\Crypt::decryptString($encryptedToken) : null;
        
        return view('modules.install', [
            'repositories' => $repositories,
            'savedToken' => $savedToken,
        ]);
    }

    /**
     * Save GitHub Personal Access Token (encrypted).
     */
    public function saveGithubToken(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $encrypted = \Illuminate\Support\Facades\Crypt::encryptString($request->token);
        \App\Models\Option::set('github_personal_access_token', $encrypted);

        return response()->json([
            'success' => true,
            'message' => __('GitHub token saved securely'),
        ]);
    }

    /**
     * Clear saved GitHub Personal Access Token.
     */
    public function clearGithubToken(): \Illuminate\Http\JsonResponse
    {
        \App\Models\Option::remove('github_personal_access_token');

        return response()->json([
            'success' => true,
            'message' => __('GitHub token cleared'),
        ]);
    }

    /**
     * Test connection to a repository.
     */
    public function testConnection(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $url = $request->input('url');
        $token = $request->input('token');
        
        if (!$url) {
            return response()->json(['message' => __('Repository URL is required')], 400);
        }

        try {
            // Parse repo info
            if (preg_match('/github\.com[\/:]([^\/]+)\/([^\/\.]+)/', $url, $matches)) {
                $owner = $matches[1];
                $repo = preg_replace('/\.git$/', '', $matches[2]);
                
                // Test API access
                $headers = ['Accept' => 'application/vnd.github.v3+json'];
                if ($token) {
                    $headers['Authorization'] = 'Bearer ' . $token;
                }
                
                $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                    ->timeout(10)
                    ->get("https://api.github.com/repos/{$owner}/{$repo}");
                
                if ($response->status() === 404) {
                    return response()->json([
                        'success' => false,
                        'message' => __("Repository ':repo' not found. Please check:", ['repo' => "{$owner}/{$repo}"]),
                        'suggestions' => [
                            __('Verify the repository exists and is spelled correctly'),
                            __('Check that you have access permissions'),
                            __('For private repos, provide a valid Personal Access Token'),
                        ]
                    ], 404);
                }
                
                if ($response->status() === 401 || $response->status() === 403) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Authentication failed'),
                        'suggestions' => [
                            __('This appears to be a private repository'),
                            __('Provide a Personal Access Token with repo access'),
                            __('Verify your token has not expired'),
                        ]
                    ], 403);
                }
                
                if (!$response->successful()) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Connection failed: :error', ['error' => $response->status()])
                    ], 500);
                }
                
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'message' => __('✓ Connection successful'),
                    'repo_info' => [
                        'name' => $data['full_name'] ?? '',
                        'description' => $data['description'] ?? '',
                        'default_branch' => $data['default_branch'] ?? 'main',
                        'private' => $data['private'] ?? false,
                    ]
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => __('Invalid repository URL format')
            ], 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Connection test failed: :error', ['error' => $e->getMessage()])
            ], 500);
        }
    }

    /**
     * Preview a module from GitHub repository.
     * Fetches module.json and README.md to display metadata and documentation.
     */
    public function previewModule(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'repo_url' => 'required|string',
            'branch' => 'nullable|string',
        ]);

        try {
            $repoUrl = $request->input('repo_url');
            $branch = $request->input('branch', 'main');

            // Parse GitHub URL to extract owner/repo
            if (preg_match('#github\.com[:/]([^/]+)/([^/\.]+)#', $repoUrl, $matches)) {
                $owner = $matches[1];
                $repo = $matches[2];
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('Invalid GitHub repository URL')
                ], 400);
            }

            // Build GitHub API headers
            $headers = ['Accept' => 'application/vnd.github.v3+json'];
            $token = \App\Models\Option::get('github_personal_access_token');
            if ($token) {
                try {
                    $decryptedToken = \Illuminate\Support\Facades\Crypt::decryptString($token);
                    $headers['Authorization'] = 'token ' . $decryptedToken;
                } catch (\Exception $e) {
                    // Token decryption failed, proceed without auth
                }
            }

            // Fetch module.json
            $moduleJsonUrl = "https://api.github.com/repos/{$owner}/{$repo}/contents/module.json?ref={$branch}";
            $moduleJsonResponse = \Illuminate\Support\Facades\Http::withHeaders($headers)->get($moduleJsonUrl);

            $moduleInfo = null;
            if ($moduleJsonResponse->successful()) {
                $content = $moduleJsonResponse->json();
                if (isset($content['content'])) {
                    $decoded = base64_decode($content['content']);
                    $moduleInfo = json_decode($decoded, true);
                }
            }

            // Fetch README.md
            $readmeUrl = "https://api.github.com/repos/{$owner}/{$repo}/readme?ref={$branch}";
            $readmeResponse = \Illuminate\Support\Facades\Http::withHeaders($headers)->get($readmeUrl);

            $readmeContent = null;
            if ($readmeResponse->successful()) {
                $content = $readmeResponse->json();
                if (isset($content['content'])) {
                    $readmeContent = base64_decode($content['content']);
                }
            }

            // Fetch composer.json for dependencies
            $composerUrl = "https://api.github.com/repos/{$owner}/{$repo}/contents/composer.json?ref={$branch}";
            $composerResponse = \Illuminate\Support\Facades\Http::withHeaders($headers)->get($composerUrl);

            $composerInfo = null;
            if ($composerResponse->successful()) {
                $content = $composerResponse->json();
                if (isset($content['content'])) {
                    $decoded = base64_decode($content['content']);
                    $composerInfo = json_decode($decoded, true);
                }
            }

            return response()->json([
                'success' => true,
                'module_info' => $moduleInfo,
                'readme' => $readmeContent,
                'composer_info' => $composerInfo,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to fetch module preview: :error', ['error' => $e->getMessage()])
            ], 500);
        }
    }

    /**
     * Check if global deploy key exists.
     */
    public function checkDeployKey(): \Illuminate\Http\JsonResponse
    {
        $key = \App\Models\Option::get('ssh_deploy_key');
        return response()->json(['exists' => !empty($key)]);
    }

    /**
     * Save SSH deploy key (encrypted).
     */
    public function saveDeployKey(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        // Basic validation of SSH key format
        if (!str_contains($request->key, 'BEGIN') || !str_contains($request->key, 'PRIVATE KEY')) {
            return response()->json([
                'message' => __('Invalid SSH private key format')
            ], 400);
        }

        $encrypted = \Illuminate\Support\Facades\Crypt::encryptString($request->key);
        \App\Models\Option::set('ssh_deploy_key', $encrypted);

        return response()->json([
            'success' => true,
            'message' => __('Deploy key saved securely'),
        ]);
    }


    /**
     * Install a module from the marketplace or GitHub.
     */
    public function install(Request $request): \Illuminate\Http\RedirectResponse
    {
        $githubUrl = $request->input('github_url');

        if ($githubUrl) {
            $githubToken = $request->input('github_token');
            $githubCommit = $request->input('github_commit');
            $githubBranch = $request->input('github_branch');
            $githubUrlStr = is_string($githubUrl) || is_int($githubUrl) || is_float($githubUrl) ? (string) $githubUrl : '';
            $githubTokenStr = ($githubToken && (is_string($githubToken) || is_int($githubToken) || is_float($githubToken))) ? (string) $githubToken : null;
            $githubCommitStr = ($githubCommit && (is_string($githubCommit) || is_int($githubCommit) || is_float($githubCommit))) ? (string) $githubCommit : null;
            $githubBranchStr = ($githubBranch && (is_string($githubBranch) || is_int($githubBranch) || is_float($githubBranch))) ? (string) $githubBranch : null;
            return $this->installFromGithub($githubUrlStr, $githubTokenStr, $githubCommitStr, $githubBranchStr);
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

    private function installFromGithub(string $url, ?string $token = null, ?string $commit = null, ?string $branch = null): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $isAjax = request()->wantsJson() || request()->ajax();
        
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            $message = __('Invalid GitHub URL');
            return $isAjax 
                ? response()->json(['message' => $message], 400)
                : redirect()->back()->with('error', $message);
        }

        // Extract repo name
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path)) {
            $message = __('Invalid GitHub URL path');
            return $isAjax 
                ? response()->json(['message' => $message], 400)
                : redirect()->back()->with('error', $message);
        }
        $parts = explode('/', trim($path, '/'));
        if (count($parts) < 2) {
            $message = __('Invalid GitHub URL format');
            return $isAjax 
                ? response()->json(['message' => $message], 400)
                : redirect()->back()->with('error', $message);
        }
        $repoName = end($parts);
        $repoName = preg_replace('/\.git$/', '', strval($repoName));
        
        // Build authenticated URL if token provided
        if ($token) {
            $parsedUrl = parse_url($url);
            if (!is_array($parsedUrl)) {
                $message = __('Invalid GitHub URL');
                return $isAjax 
                    ? response()->json(['message' => $message], 400)
                    : redirect()->back()->with('error', $message);
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
            $message = __('Module directory already exists: :path', ['path' => $targetPath]);
            return $isAjax 
                ? response()->json(['message' => $message], 400)
                : redirect()->back()->with('error', $message);
        }

        try {
            // Check if SSH URL and handle deploy key
            $sshKeyFile = null;
            if (preg_match('/^git@|^ssh:\/\//', $url)) {
                // Get deploy key from options
                $encryptedKey = \App\Models\Option::get('ssh_deploy_key');
                if (!$encryptedKey) {
                    $message = __('SSH URL detected but no deploy key is configured. Please add a deploy key in the settings.');
                    return $isAjax 
                        ? response()->json(['message' => $message], 400)
                        : redirect()->back()->with('error', $message);
                }
                
                $deployKey = \Illuminate\Support\Facades\Crypt::decryptString($encryptedKey);
                
                // Create temporary key file
                $sshKeyFile = tempnam(sys_get_temp_dir(), 'git_key_');
                file_put_contents($sshKeyFile, $deployKey);
                chmod($sshKeyFile, 0600);
            }
            
            // Build clone command with optional branch
            $cloneCmd = ['git', 'clone'];
            if ($branch) {
                $cloneCmd[] = '--branch';
                $cloneCmd[] = $branch;
            }
            $cloneCmd[] = $url;
            $cloneCmd[] = $targetPath;
            
            // Use git clone
            $process = new \Symfony\Component\Process\Process($cloneCmd);
            $process->setTimeout(120); // 2 minutes timeout
            
            // Set SSH key if needed
            if ($sshKeyFile) {
                $process->setEnv([
                    'GIT_SSH_COMMAND' => "ssh -i {$sshKeyFile} -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null"
                ]);
            }
            
            $process->run();

            // Clean up SSH key file
            if ($sshKeyFile && file_exists($sshKeyFile)) {
                unlink($sshKeyFile);
            }

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
                $sanitizedError = preg_replace('/https:\/\/[^@]+@/', 'https://*****@/', $fullError);
                
                // Provide helpful error messages
                $suggestions = [];
                if (str_contains($fullError, 'Repository not found') || str_contains($fullError, '404')) {
                    $suggestions[] = __('Verify the repository exists and is spelled correctly');
                    $suggestions[] = __('Check that you have access permissions');
                }
                if (str_contains($fullError, 'authentication') || str_contains($fullError, 'Permission denied')) {
                    $suggestions[] = __('For private repositories, ensure your credentials are correct');
                    $suggestions[] = __('For SSH URLs, verify your deploy key has been added to the repository');
                }
                if (str_contains($fullError, 'timeout') || str_contains($fullError, 'timed out')) {
                    $suggestions[] = __('Network timeout - check your internet connection');
                    $suggestions[] = __('Try again in a few moments');
                }
                
                $errorMsg = __('❌ Git clone failed: :error', ['error' => $sanitizedError ?: __('Unknown error')]);
                if (!empty($suggestions)) {
                    $errorMsg .= "\n\n" . __('Suggestions:') . "\n• " . implode("\n• ", $suggestions);
                }
                
                throw new \Exception($errorMsg);
            }
            
            // Checkout specific commit if provided
            if ($commit) {
                $checkoutProcess = new \Symfony\Component\Process\Process(['git', 'checkout', $commit], $targetPath);
                $checkoutProcess->setTimeout(30);
                $checkoutProcess->run();
                
                if (!$checkoutProcess->isSuccessful()) {
                    // Clean up the cloned directory on checkout failure
                    File::deleteDirectory($targetPath);
                    throw new \Exception(__('Git checkout to commit :commit failed: :error', [
                        'commit' => $commit,
                        'error' => $checkoutProcess->getErrorOutput()
                    ]));
                }
            }

            // Validate module health
            $healthCheck = $this->validateModuleHealth($targetPath);
            if (!$healthCheck['success']) {
                // Clean up on health check failure
                File::deleteDirectory($targetPath);
                $errorMsg = __('Module health check failed:') . "\n• " . implode("\n• ", $healthCheck['errors']);
                throw new \Exception($errorMsg);
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
            
            // Log successful installation
            $this->logActivity($moduleName, 'install', [
                'repo_url' => $url,
                'branch' => $branch,
                'commit' => $commit,
                'method' => 'github',
            ]);
            
            $message = __('Module installed from GitHub successfully');
            return $isAjax 
                ? response()->json(['message' => $message, 'success' => true])
                : redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            // Log failed installation
            $this->logActivity($moduleName ?? 'unknown', 'install', [
                'repo_url' => $url,
                'error' => $e->getMessage(),
                'failed' => true,
            ]);
            
            $message = $e->getMessage();
            return $isAjax 
                ? response()->json(['message' => $message], 500)
                : redirect()->back()->with('error', $message);
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

            case 'reset_module':
                return $this->ajaxResetModule($request);

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

                // Get GitHub URL for commit links
                $githubUrl = $this->getModuleGithubUrl($modulePath);
                
                return [
                    'current' => $currentVersion . ' (' . substr($localHash, 0, 7) . ')',
                    'available' => $currentVersion . ' (' . substr($remoteHash, 0, 7) . ')',
                    'current_commit' => substr($localHash, 0, 7),
                    'remote_commit' => substr($remoteHash, 0, 7),
                    'remote_commit_url' => $githubUrl ? $githubUrl . '/commit/' . substr($remoteHash, 0, 7) : null,
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
     * Reset a module (delete and re-clone from GitHub).
     */
    protected function ajaxResetModule(Request $request): JsonResponse
    {
        $alias = $request->input('alias');

        if (! $alias) {
            return response()->json(['success' => false, 'message' => __('Module alias is required')]);
        }

        $aliasStr = is_string($alias) || is_int($alias) || is_float($alias) ? (string) $alias : '';
        $module = Module::find($aliasStr);

        // Check if it's a git repo
        if (File::isDirectory($module->getPath() . '/.git')) {
            return $this->resetFromGithub($module);
        }

        return response()->json(['success' => false, 'message' => __('Module is not a Git repository')]);
    }

    /**
     * Update module from GitHub.
     */
    private function updateFromGithub(\Nwidart\Modules\Module $module): JsonResponse
    {
        try {
            $path = $module->getPath();
            
            // Get current branch
            $branchProcess = new \Symfony\Component\Process\Process(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], $path);
            $branchProcess->run();
            $branch = $branchProcess->isSuccessful() ? trim($branchProcess->getOutput()) : 'master';
            
            // Fetch latest changes
            $fetchProcess = new \Symfony\Component\Process\Process(['git', 'fetch', 'origin', $branch], $path);
            $fetchProcess->setTimeout(60);
            $fetchProcess->run();
            
            if (!$fetchProcess->isSuccessful()) {
                throw new \Exception(__('Git fetch failed: :error', ['error' => $fetchProcess->getErrorOutput()]));
            }
            
            // Reset hard to origin branch - this will discard all local changes and always succeed
            $resetProcess = new \Symfony\Component\Process\Process(['git', 'reset', '--hard', "origin/$branch"], $path);
            $resetProcess->setTimeout(30);
            $resetProcess->run();
            
            if (!$resetProcess->isSuccessful()) {
                throw new \Exception(__('Git reset failed: :error', ['error' => $resetProcess->getErrorOutput()]));
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
            $message .= ' ' . __('All local changes were discarded to ensure a clean update.');
            
            // Get the new commit hash after update
            $newCommit = $this->getModuleCommitHash($path);
            $githubUrl = $this->getModuleGithubUrl($path);
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'new_commit' => $newCommit,
                'new_commit_url' => $githubUrl ? $githubUrl . '/commit/' . $newCommit : null,
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Reset module from GitHub (delete and re-clone).
     */
    private function resetFromGithub(\Nwidart\Modules\Module $module): JsonResponse
    {
        $tempPath = null;
        
        try {
            $path = $module->getPath();
            $moduleName = $module->getName();
            
            // Get GitHub URL before moving
            $githubUrl = $this->getModuleGithubUrl($path);
            if (!$githubUrl) {
                throw new \Exception(__('Cannot determine GitHub URL for this module'));
            }
            
            // Get current branch
            $branchProcess = new \Symfony\Component\Process\Process(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], $path);
            $branchProcess->run();
            $branch = $branchProcess->isSuccessful() ? trim($branchProcess->getOutput()) : 'master';
            
            // Check if we need authentication (stored token)
            $hasToken = false;
            $token = null;
            
            // Try to extract token from git config
            $configProcess = new \Symfony\Component\Process\Process(['git', 'config', '--get', 'credential.helper'], $path);
            $configProcess->run();
            if ($configProcess->isSuccessful()) {
                // Check for stored credentials
                $urlProcess = new \Symfony\Component\Process\Process(['git', 'config', '--get', 'remote.origin.url'], $path);
                $urlProcess->run();
                if ($urlProcess->isSuccessful()) {
                    $remoteUrl = trim($urlProcess->getOutput());
                    // Extract token if it's in the URL (https://token@github.com/...)
                    if (preg_match('/https:\/\/([^@]+)@github\.com/', $remoteUrl, $matches)) {
                        $token = $matches[1];
                        $hasToken = true;
                    }
                }
            }
            
            // Move module to temporary location instead of deleting
            $tempPath = sys_get_temp_dir() . '/module_backup_' . $moduleName . '_' . time();
            if (File::isDirectory($path)) {
                if (!File::moveDirectory($path, $tempPath)) {
                    throw new \Exception(__('Failed to backup module to temporary directory'));
                }
            }
            
            // Get parent directory (Modules/)
            $modulesDir = dirname($path);
            
            // Prepare clone URL with token if available
            $cloneUrl = $githubUrl;
            if ($hasToken && $token) {
                $cloneUrl = str_replace('https://github.com/', "https://{$token}@github.com/", $githubUrl);
            }
            
            // Clone fresh from GitHub
            $cloneProcess = new \Symfony\Component\Process\Process(
                ['git', 'clone', '-b', $branch, $cloneUrl, $moduleName],
                $modulesDir
            );
            $cloneProcess->setTimeout(120);
            $cloneProcess->run();
            
            if (!$cloneProcess->isSuccessful()) {
                // Restore backup on failure
                if ($tempPath && File::isDirectory($tempPath)) {
                    File::moveDirectory($tempPath, $path);
                    $tempPath = null;
                }
                throw new \Exception(__('Git clone failed: :error', ['error' => $cloneProcess->getErrorOutput()]));
            }
            
            // Successfully cloned, safe to delete backup
            if ($tempPath && File::isDirectory($tempPath)) {
                File::deleteDirectory($tempPath);
                $tempPath = null;
            }
            
            // Run install command
            $outputLog = new BufferedOutput;
            Artisan::call('freescout:module-install', ['module_alias' => $moduleName], $outputLog);
            
            Artisan::call('cache:clear');
            
            // Get the new commit hash
            $newCommit = $this->getModuleCommitHash($path);
            $newGithubUrl = $this->getModuleGithubUrl($path);
            
            return response()->json([
                'success' => true,
                'message' => __('Module reset and re-installed from GitHub successfully'),
                'new_commit' => $newCommit,
                'new_commit_url' => $newGithubUrl ? $newGithubUrl . '/commit/' . $newCommit : null,
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
     * Get the git commit hash for a module.
     */
    protected function getModuleCommitHash(string $modulePath): ?string
    {
        try {
            $process = new \Symfony\Component\Process\Process(['git', 'rev-parse', '--short', 'HEAD'], $modulePath);
            $process->run();
            
            if ($process->isSuccessful()) {
                return trim($process->getOutput());
            }
        } catch (\Exception $e) {
            // Module might not be a git repository
            return null;
        }
        
        return null;
    }
    
    /**
     * Get the GitHub URL for a module from git remote.
     */
    protected function getModuleGithubUrl(string $modulePath): ?string
    {
        try {
            $process = new \Symfony\Component\Process\Process(['git', 'remote', 'get-url', 'origin'], $modulePath);
            $process->run();
            
            if ($process->isSuccessful()) {
                $remoteUrl = trim($process->getOutput());
                
                // Convert git URL to HTTPS GitHub URL
                // Handle: git@github.com:user/repo.git or https://github.com/user/repo.git
                if (preg_match('/github\.com[:\/]([^\/]+\/[^\/]+?)(\.git)?$/', $remoteUrl, $matches)) {
                    return 'https://github.com/' . $matches[1];
                }
            }
        } catch (\Exception $e) {
            return null;
        }
        
        return null;
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

    /**
     * Log a module activity to the database.
     *
     * @param  string  $moduleName
     * @param  string  $action  One of: install, update, enable, disable, delete
     * @param  array<string, mixed>  $metadata  Additional context (repo_url, version, error, etc.)
     */
    protected function logActivity(string $moduleName, string $action, array $metadata = []): void
    {
        try {
            \App\Models\ModuleActivityLog::create([
                'user_id' => auth()->id(),
                'module_name' => $moduleName,
                'action' => $action,
                'metadata' => $metadata,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // Log silently to avoid breaking the main operation
            \Illuminate\Support\Facades\Log::warning('Failed to log module activity', [
                'module' => $moduleName,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate module health after installation.
     * Checks for required files and basic structure.
     *
     * @param  string  $modulePath
     * @return array{success: bool, errors: array<string>}
     */
    protected function validateModuleHealth(string $modulePath): array
    {
        $errors = [];

        // Check if module.json exists
        $moduleJsonPath = $modulePath . '/module.json';
        if (!File::exists($moduleJsonPath)) {
            $errors[] = 'module.json file is missing';
        } else {
            // Validate JSON
            $content = File::get($moduleJsonPath);
            $decoded = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = 'module.json contains invalid JSON: ' . json_last_error_msg();
            }
        }

        // Check if composer.json exists (optional but recommended)
        $composerJsonPath = $modulePath . '/composer.json';
        if (!File::exists($composerJsonPath)) {
            \Illuminate\Support\Facades\Log::info("Module at {$modulePath} does not have composer.json (optional)");
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
        ];
    }
}

