<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Nwidart\Modules\Facades\Module;
use Symfony\Component\Console\Output\BufferedOutput;
use App\Services\ModuleSource;

class ModulesController extends Controller
{
    protected ModuleSource $moduleSource;

    public function __construct(ModuleSource $moduleSource)
    {
        $this->moduleSource = $moduleSource;
    }

    /**
     * Display a listing of modules.
     */
    public function index(): View|ViewFactory
    {
        $flashes = [];
        $flash = Cache::get('modules_flash');
        if ($flash) {
            if (is_array($flash) && !isset($flash['text'])) {
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
            ];
        }

        // Get remote modules from ModuleSource
        $remoteModules = $this->moduleSource->getModules();

        return view('modules.index', [
            'modules' => $modulesData,
            'remoteModules' => $remoteModules,
            'flashes' => $flashes,
        ]);
    }

    /**
     * Enable a module.
     */
    public function enable(Request $request, string $alias): \Illuminate\Http\JsonResponse
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

            $outputLog = new BufferedOutput();
            
            // Run module install command which handles migrations and symlinks
            Artisan::call('freescout:module-install', ['module_alias' => $module->getName()], $outputLog);
            $output = $outputLog->fetch();

            // Clear cache
            Artisan::call('cache:clear');
            Artisan::call('config:clear');

            $msg = __(':name module enabled successfully', ['name' => $module->getName()]);
            
            // Store flash message for the next request
            $flash = [
                'text'      => '<strong>'.$msg.'</strong><pre class="margin-top">'.$output.'</pre>',
                'unescaped' => true,
                'type'      => 'success',
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
    public function disable(Request $request, string $alias): \Illuminate\Http\JsonResponse
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

            $outputLog = new BufferedOutput();
            
            // Clear cache
            Artisan::call('freescout:clear-cache', [], $outputLog);
            $output = $outputLog->fetch();

            $msg = __(':name module disabled successfully', ['name' => $module->getName()]);

            // Store flash message for the next request
            $flash = [
                'text'      => '<strong>'.$msg.'</strong><pre class="margin-top">'.$output.'</pre>',
                'unescaped' => true,
                'type'      => 'success',
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
    public function delete(Request $request, string $alias): \Illuminate\Http\JsonResponse
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
     * Install a module from the source.
     */
    public function install(Request $request)
    {
        $alias = $request->input('alias');
        
        if (!$alias) {
            return redirect()->back()->with('error', __('Module alias is required'));
        }

        // Get module details to find download URL
        $moduleInfo = $this->moduleSource->getModule($alias);

        if (!$moduleInfo) {
            return redirect()->back()->with('error', __('Module not found in source'));
        }

        $downloadUrl = $moduleInfo['download_url'] ?? null;

        if (!$downloadUrl) {
            return redirect()->back()->with('error', __('Could not determine download URL for this module'));
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'mod_');

        try {
            // Download the file
            $response = Http::timeout(120)->sink($tempFile)->get($downloadUrl);

            if (!$response->successful()) {
                throw new \Exception(__('Failed to download module'));
            }

            // Unzip
            $zip = new \ZipArchive;
            if ($zip->open($tempFile) === TRUE) {
                $extractPath = base_path('Modules');
                
                if (!File::isDirectory($extractPath)) {
                    File::makeDirectory($extractPath, 0755, true);
                }

                $zip->extractTo($extractPath);
                $zip->close();
                
                // Clean up temp file
                @unlink($tempFile);
                
                // Clear cache to ensure new module is detected
                Artisan::call('cache:clear');
                Artisan::call('config:clear');
                
                // Try to find and enable the module
                $module = Module::find($alias);
                
                if ($module) {
                    $module->enable();
                    
                    // Run install command
                    $outputLog = new BufferedOutput();
                    Artisan::call('freescout:module-install', ['module_alias' => $module->getName()], $outputLog);
                    
                    // Clear cache again
                    Artisan::call('cache:clear');
                    
                    return redirect()->back()->with('success', __('Module installed and enabled successfully'));
                } else {
                     return redirect()->back()->with('success', __('Module installed but could not be enabled automatically. Please check the list.'));
                }

            } else {
                throw new \Exception(__('Failed to open zip file'));
            }

        } catch (\Exception $e) {
            if (file_exists($tempFile)) @unlink($tempFile);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
