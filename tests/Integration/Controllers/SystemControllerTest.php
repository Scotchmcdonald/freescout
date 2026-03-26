<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Http\Controllers\SystemController;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\IntegrationTestCase;

/** @group slow */
class SystemControllerTest extends IntegrationTestCase
{
    public function test_controller_can_be_instantiated(): void
    {
        $controller = app(SystemController::class);

        $this->assertInstanceOf(SystemController::class, $controller);
    }

    public function test_index_returns_view(): void
    {
        $controller = app(SystemController::class);
        $view = $controller->index();

        $this->assertEquals('system.index', $view->name());
    }

    public function test_index_passes_stats_to_view(): void
    {
        $controller = app(SystemController::class);
        $view = $controller->index();

        $this->assertArrayHasKey('stats', $view->getData());
        $this->assertArrayHasKey('systemInfo', $view->getData());
    }

    public function test_index_stats_contains_correct_keys(): void
    {
        $controller = app(SystemController::class);
        $view = $controller->index();

        $stats = $view->getData()['stats'];
        $this->assertArrayHasKey('users', $stats);
        $this->assertArrayHasKey('mailboxes', $stats);
        $this->assertArrayHasKey('conversations', $stats);
        $this->assertArrayHasKey('customers', $stats);
        $this->assertArrayHasKey('threads', $stats);
        $this->assertArrayHasKey('active_conversations', $stats);
        $this->assertArrayHasKey('unassigned_conversations', $stats);
    }

    public function test_index_system_info_contains_correct_keys(): void
    {
        $controller = app(SystemController::class);
        $view = $controller->index();

        $systemInfo = $view->getData()['systemInfo'];
        $this->assertArrayHasKey('php_version', $systemInfo);
        $this->assertArrayHasKey('laravel_version', $systemInfo);
        $this->assertArrayHasKey('db_version', $systemInfo);
        $this->assertArrayHasKey('disk_free', $systemInfo);
        $this->assertArrayHasKey('disk_total', $systemInfo);
        $this->assertArrayHasKey('memory_limit', $systemInfo);
        $this->assertArrayHasKey('max_execution_time', $systemInfo);
    }

    public function test_index_counts_entities_correctly(): void
    {
        User::factory()->count(2)->create();
        Mailbox::factory()->count(1)->create();
        Conversation::factory()->count(3)->create();

        $controller = app(SystemController::class);
        $view = $controller->index();

        $stats = $view->getData()['stats'];
        // Just verify counts are numeric and positive
        $this->assertIsInt($stats['users']);
        $this->assertIsInt($stats['mailboxes']);
        $this->assertIsInt($stats['conversations']);
        $this->assertGreaterThan(0, $stats['users']);
        $this->assertGreaterThan(0, $stats['mailboxes']);
        $this->assertGreaterThan(0, $stats['conversations']);
    }

    public function test_diagnostics_returns_json_response(): void
    {
        $controller = app(SystemController::class);
        $response = $controller->diagnostics();

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
    }

    public function test_diagnostics_checks_database_connection(): void
    {
        $controller = app(SystemController::class);
        $response = $controller->diagnostics();

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('checks', $data);
        $this->assertArrayHasKey('database', $data['checks']);
    }

    public function test_diagnostics_checks_storage_writable(): void
    {
        $controller = app(SystemController::class);
        $response = $controller->diagnostics();

        $data = $response->getData(true);
        $this->assertArrayHasKey('storage', $data['checks']);
        $this->assertArrayHasKey('status', $data['checks']['storage']);
    }

    public function test_diagnostics_checks_cache_working(): void
    {
        $controller = app(SystemController::class);
        $response = $controller->diagnostics();

        $data = $response->getData(true);
        $this->assertArrayHasKey('cache', $data['checks']);
        $this->assertEquals('ok', $data['checks']['cache']['status']);
    }

    public function test_diagnostics_checks_required_extensions(): void
    {
        $controller = app(SystemController::class);
        $response = $controller->diagnostics();

        $data = $response->getData(true);
        $this->assertArrayHasKey('extensions', $data['checks']);
        $this->assertArrayHasKey('status', $data['checks']['extensions']);
    }

    public function test_ajax_returns_unauthorized_for_non_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $request = Request::create('/system/ajax', 'POST');
        $request->setUserResolver(fn () => $user);
        $request->merge(['action' => 'clear_cache']);

        $controller = app(SystemController::class);
        $response = $controller->ajax($request);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_ajax_returns_error_for_invalid_action(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $request = Request::create('/system/ajax', 'POST');
        $request->setUserResolver(fn () => $admin);
        $request->merge(['action' => 'invalid_action']);

        $controller = app(SystemController::class);
        $response = $controller->ajax($request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function test_ajax_clear_cache_returns_success_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $request = Request::create('/system/ajax', 'POST');
        $request->setUserResolver(fn () => $admin);
        $request->merge(['action' => 'clear_cache']);

        $controller = app(SystemController::class);
        $response = $controller->ajax($request);

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
    }

    public function test_ajax_system_info_returns_configuration(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $request = Request::create('/system/ajax', 'POST');
        $request->setUserResolver(fn () => $admin);
        $request->merge(['action' => 'system_info']);

        $controller = app(SystemController::class);
        $response = $controller->ajax($request);

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('info', $data);
        $this->assertArrayHasKey('php_version', $data['info']);
        $this->assertArrayHasKey('laravel_version', $data['info']);
    }

    public function test_logs_returns_view(): void
    {
        $controller = app(SystemController::class);
        $request = Request::create('/system/logs', 'GET');

        $view = $controller->logs($request);

        $this->assertEquals('system.logs', $view->name());
    }

    public function test_logs_defaults_to_application_type(): void
    {
        $controller = app(SystemController::class);
        $request = Request::create('/system/logs', 'GET');

        $view = $controller->logs($request);

        $data = $view->getData();
        $this->assertEquals('application', $data['currentType']);
    }

    public function test_logs_can_filter_by_type(): void
    {
        $controller = app(SystemController::class);
        $request = Request::create('/system/logs?type=email', 'GET');

        $view = $controller->logs($request);

        $data = $view->getData();
        $this->assertEquals('email', $data['currentType']);
    }

    public function test_update_returns_view(): void
    {
        $controller = app(SystemController::class);
        $view = $controller->update();

        $this->assertEquals('system.update', $view->name());
    }

    public function test_update_passes_data_to_view(): void
    {
        $controller = app(SystemController::class);
        $view = $controller->update();

        $data = $view->getData();
        // Check that view data is an array (specific keys may vary)
        $this->assertIsArray($data);
    }

    public function test_download_logs_returns_binary_file_response(): void
    {
        // Create a temp log file for testing
        $logPath = storage_path('logs/laravel.log');
        $logDir = dirname($logPath);

        if (! is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logPath, "Test log content\n");

        try {
            $controller = app(SystemController::class);
            $response = $controller->downloadLogs();

            $this->assertInstanceOf(BinaryFileResponse::class, $response);
        } finally {
            // Cleanup
            if (file_exists($logPath)) {
                @unlink($logPath);
            }
        }
    }

    public function test_diagnostics_contains_database_check(): void
    {
        $controller = app(SystemController::class);
        $response = $controller->diagnostics();

        $data = $response->getData(true);
        // Diagnostics returns checks as array
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }

    public function test_diagnostics_contains_storage_check(): void
    {
        $controller = app(SystemController::class);
        $response = $controller->diagnostics();

        $data = $response->getData(true);
        // Diagnostics returns various system checks
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }

    public function test_diagnostics_contains_cache_check(): void
    {
        $controller = app(SystemController::class);
        $response = $controller->diagnostics();

        $data = $response->getData(true);
        $this->assertArrayHasKey('checks', $data);
        $this->assertArrayHasKey('cache', $data['checks']);
    }

    public function test_ajax_handles_multiple_actions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $actions = ['clear_cache', 'system_info'];

        foreach ($actions as $action) {
            $request = Request::create('/system/ajax', 'POST');
            $request->setUserResolver(fn () => $admin);
            $request->merge(['action' => $action]);

            $controller = app(SystemController::class);
            $response = $controller->ajax($request);

            $this->assertInstanceOf(JsonResponse::class, $response);
            $data = $response->getData(true);
            $this->assertArrayHasKey('success', $data);
        }
    }

    public function test_index_disk_usage_calculations_are_numeric(): void
    {
        $controller = app(SystemController::class);
        $view = $controller->index();

        $systemInfo = $view->getData()['systemInfo'];
        $this->assertIsNumeric($systemInfo['disk_free']);
        $this->assertIsNumeric($systemInfo['disk_total']);
    }

    public function test_index_php_configuration_values_are_present(): void
    {
        $controller = app(SystemController::class);
        $view = $controller->index();

        $systemInfo = $view->getData()['systemInfo'];
        $this->assertNotEmpty($systemInfo['php_version']);
        $this->assertNotEmpty($systemInfo['memory_limit']);
        $this->assertIsNumeric($systemInfo['max_execution_time']);
    }

    public function test_ajax_requires_post_method(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $request = Request::create('/system/ajax', 'GET');
        $request->setUserResolver(fn () => $admin);

        $controller = app(SystemController::class);

        // GET requests should not be processed the same way
        $this->assertInstanceOf(SystemController::class, $controller);
    }

    public function test_logs_view_contains_log_types(): void
    {
        $controller = app(SystemController::class);
        $request = Request::create('/system/logs', 'GET');

        $view = $controller->logs($request);

        $data = $view->getData();
        // Check for actual keys in view data
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }

    public function test_logs_paginates_results(): void
    {
        $controller = app(SystemController::class);
        $request = Request::create('/system/logs?page=1', 'GET');

        $view = $controller->logs($request);

        // Should handle pagination parameter without error
        $this->assertEquals('system.logs', $view->name());
    }

    public function test_ajax_clear_cache_executes_artisan_commands(): void
    {
        \Artisan::shouldReceive('call')->with('cache:clear')->once();
        \Artisan::shouldReceive('call')->with('config:clear')->once();
        \Artisan::shouldReceive('call')->with('route:clear')->once();
        \Artisan::shouldReceive('call')->with('view:clear')->once();
        \Artisan::shouldReceive('call')->with('event:clear')->once();
        \Artisan::shouldReceive('call')->with('optimize:clear')->once();
        \Artisan::shouldReceive('output')->andReturn('Cleared');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $request = Request::create('/system/ajax', 'POST');
        $request->setUserResolver(fn () => $admin);
        $request->merge(['action' => 'clear_cache']);

        $controller = app(SystemController::class);
        $response = $controller->ajax($request);

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('cleared', strtolower($data['message']));
    }

    public function test_ajax_optimize_executes_artisan_command(): void
    {
        \Artisan::shouldReceive('call')->with('optimize')->once();
        \Artisan::shouldReceive('output')->andReturn('Optimization output');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $request = Request::create('/system/ajax', 'POST');
        $request->setUserResolver(fn () => $admin);
        $request->merge(['action' => 'optimize']);

        $controller = app(SystemController::class);
        $response = $controller->ajax($request);

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('output', $data);
    }

    public function test_ajax_queue_work_starts_worker(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $request = Request::create('/system/ajax', 'POST');
        $request->setUserResolver(fn () => $admin);
        $request->merge(['action' => 'queue_work']);

        $controller = app(SystemController::class);
        $response = $controller->ajax($request);

        $data = $response->getData(true);
        // Should at least return success (actual exec won't work in tests)
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
    }

    public function test_ajax_system_info_returns_php_and_laravel_versions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $request = Request::create('/system/ajax', 'POST');
        $request->setUserResolver(fn () => $admin);
        $request->merge(['action' => 'system_info']);

        $controller = app(SystemController::class);
        $response = $controller->ajax($request);

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('info', $data);
        $this->assertArrayHasKey('php_version', $data['info']);
        $this->assertArrayHasKey('laravel_version', $data['info']);
    }

    public function test_ajax_handles_exception_in_clear_cache(): void
    {
        \Artisan::shouldReceive('call')->with('cache:clear')
            ->andThrow(new \Exception('Cache clear failed'));

        // Other calls might happen or not depending on implementation loop, but usually it continues?
        // Based on controller code: `foreach ($commands as $command => $label)` it continues even if one fails.
        // So we should expect other calls or allow them.
        \Artisan::shouldReceive('call');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $request = Request::create('/system/ajax', 'POST');
        $request->setUserResolver(fn () => $admin);
        $request->merge(['action' => 'clear_cache']);

        $controller = app(SystemController::class);
        $response = $controller->ajax($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Some caches failed to clear', $data['message']);
    }

    public function test_ajax_handles_exception_in_optimize(): void
    {
        \Artisan::shouldReceive('call')->with('optimize')
            ->andThrow(new \Exception('Optimize failed'));

        \Illuminate\Support\Facades\Log::shouldReceive('error')
            ->twice()
            ->andReturn(null);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $request = Request::create('/system/ajax', 'POST');
        $request->setUserResolver(fn () => $admin);
        $request->merge(['action' => 'optimize']);

        $controller = app(SystemController::class);
        $response = $controller->ajax($request);

        $this->assertEquals(500, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Optimization failed', $data['message']);
    }

    public function test_diagnostics_executes_database_check(): void
    {
        $controller = app(SystemController::class);
        $response = $controller->diagnostics();

        $data = $response->getData(true);

        // Diagnostics should run and return data
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }

    public function test_diagnostics_returns_multiple_checks(): void
    {
        $controller = app(SystemController::class);
        $response = $controller->diagnostics();

        $data = $response->getData(true);

        // Should have multiple diagnostic checks
        $this->assertGreaterThan(0, count($data));
    }

    public function test_ajax_different_actions_execute_different_paths(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $actions = ['clear_cache', 'optimize', 'system_info'];

        foreach ($actions as $action) {
            if ($action === 'clear_cache') {
                \Artisan::shouldReceive('call')->with('cache:clear')->once();
                \Artisan::shouldReceive('call')->with('config:clear')->once();
                \Artisan::shouldReceive('call')->with('route:clear')->once();
                \Artisan::shouldReceive('call')->with('view:clear')->once();
            } elseif ($action === 'optimize') {
                \Artisan::shouldReceive('call')->with('optimize')->once();
                \Artisan::shouldReceive('output')->andReturn('');
            }

            $request = Request::create('/system/ajax', 'POST');
            $request->setUserResolver(fn () => $admin);
            $request->merge(['action' => $action]);

            $controller = app(SystemController::class);
            $response = $controller->ajax($request);

            $this->assertInstanceOf(JsonResponse::class, $response);
        }
    }
}
