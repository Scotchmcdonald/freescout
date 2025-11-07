<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SystemController extends Controller
{
    /**
     * Display system status and tools.
     */
    public function index(): View
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

        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_version' => DB::select('SELECT VERSION() as version')[0]->version ?? 'Unknown',
            'disk_free' => disk_free_space('/'),
            'disk_total' => disk_total_space('/'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
        ];

        return view('system.index', compact('stats', 'systemInfo'));
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
        $requiredExtensions = ['mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'ctype', 'json'];
        $missingExtensions = [];

        foreach ($requiredExtensions as $ext) {
            if (! extension_loaded($ext)) {
                $missingExtensions[] = $ext;
            }
        }

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

        if (! $request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        switch ($action) {
            case 'clear_cache':
                try {
                    Artisan::call('cache:clear');
                    Artisan::call('config:clear');
                    Artisan::call('route:clear');
                    Artisan::call('view:clear');

                    return response()->json([
                        'success' => true,
                        'message' => 'All caches cleared successfully.',
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to clear cache: '.$e->getMessage(),
                    ], 500);
                }

            case 'optimize':
                try {
                    Artisan::call('optimize');

                    return response()->json([
                        'success' => true,
                        'message' => 'Application optimized successfully.',
                        'output' => Artisan::output(),
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Optimization failed: '.$e->getMessage(),
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

            default:
                return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
        }
    }

    /**
     * View application logs.
     */
    public function logs(Request $request): View
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
                $activityLogs = \App\Models\ActivityLog::with(['causer'])
                    ->latest()
                    ->paginate(50);
                    
                $data = ['activityLogs' => $activityLogs];
                break;
        }
        
        $data['currentType'] = $type;

        return view('system.logs', $data);
    }
}
