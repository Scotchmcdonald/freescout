<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\View\Factory as ViewFactory;

class ModulesController extends Controller
{
    public function __construct()
    {
        // Middleware is now handled by the route group
    }

    /**
     * Display a listing of modules.
     */
    public function index(): View|ViewFactory
    {
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

        return view('modules.index', [
            'modules' => $modulesData,
        ]);
    }

    /**
     * Enable a module.
     */
    public function enable(Request $request, string $alias): \Illuminate\Http\JsonResponse
    {
        /** @var \Nwidart\Modules\Module|null $module */
        $module = Module::find($alias);

        if (!$module) {
            return response()->json([
                'status' => 'error',
                'message' => __('Module not found'),
            ], 404);
        }

        try {
            $module->enable();
            
            // Run module migrations
            Artisan::call('module:migrate', ['module' => $module->getName()]);
            
            // Clear cache
            Artisan::call('cache:clear');
            Artisan::call('config:clear');

            return response()->json([
                'status' => 'success',
                'message' => __(':name module enabled successfully', ['name' => $module->getName()]),
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

        if (!$module) {
            return response()->json([
                'status' => 'error',
                'message' => __('Module not found'),
            ], 404);
        }

        try {
            $module->disable();
            
            // Clear cache
            Artisan::call('cache:clear');
            Artisan::call('config:clear');

            return response()->json([
                'status' => 'success',
                'message' => __(':name module disabled successfully', ['name' => $module->getName()]),
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

        if (!$module) {
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
}
