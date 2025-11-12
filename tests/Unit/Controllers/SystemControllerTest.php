<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Http\Controllers\SystemController;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\UnitTestCase;

class SystemControllerTest extends UnitTestCase
{

    public function test_controller_can_be_instantiated(): void
    {
        $controller = new SystemController;

        $this->assertInstanceOf(SystemController::class, $controller);
    }

    public function test_index_returns_view(): void
    {
        $controller = new SystemController;
        $view = $controller->index();

        $this->assertEquals('system.index', $view->name());
    }

    public function test_index_passes_stats_to_view(): void
    {
        $controller = new SystemController;
        $view = $controller->index();

        $this->assertArrayHasKey('stats', $view->getData());
        $this->assertArrayHasKey('systemInfo', $view->getData());
    }

    public function test_index_stats_contains_correct_keys(): void
    {
        $controller = new SystemController;
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
        $controller = new SystemController;
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

        $controller = new SystemController;
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
        $controller = new SystemController;
        $response = $controller->diagnostics();

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
    }

    public function test_diagnostics_checks_database_connection(): void
    {
        $controller = new SystemController;
        $response = $controller->diagnostics();

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('checks', $data);
        $this->assertArrayHasKey('database', $data['checks']);
    }

    public function test_diagnostics_checks_storage_writable(): void
    {
        $controller = new SystemController;
        $response = $controller->diagnostics();

        $data = $response->getData(true);
        $this->assertArrayHasKey('storage', $data['checks']);
        $this->assertArrayHasKey('status', $data['checks']['storage']);
    }

    public function test_diagnostics_checks_cache_working(): void
    {
        $controller = new SystemController;
        $response = $controller->diagnostics();

        $data = $response->getData(true);
        $this->assertArrayHasKey('cache', $data['checks']);
        $this->assertEquals('ok', $data['checks']['cache']['status']);
    }

    public function test_diagnostics_checks_required_extensions(): void
    {
        $controller = new SystemController;
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

        $controller = new SystemController;
        $response = $controller->ajax($request);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_ajax_returns_error_for_invalid_action(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $request = Request::create('/system/ajax', 'POST');
        $request->setUserResolver(fn () => $admin);
        $request->merge(['action' => 'invalid_action']);

        $controller = new SystemController;
        $response = $controller->ajax($request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function test_ajax_clear_cache_returns_success_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $request = Request::create('/system/ajax', 'POST');
        $request->setUserResolver(fn () => $admin);
        $request->merge(['action' => 'clear_cache']);

        $controller = new SystemController;
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

        $controller = new SystemController;
        $response = $controller->ajax($request);

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('info', $data);
        $this->assertArrayHasKey('php_version', $data['info']);
        $this->assertArrayHasKey('laravel_version', $data['info']);
    }

    public function test_logs_returns_view(): void
    {
        $controller = new SystemController;
        $request = Request::create('/system/logs', 'GET');

        $view = $controller->logs($request);

        $this->assertEquals('system.logs', $view->name());
    }

    public function test_logs_defaults_to_application_type(): void
    {
        $controller = new SystemController;
        $request = Request::create('/system/logs', 'GET');

        $view = $controller->logs($request);

        $data = $view->getData();
        $this->assertEquals('application', $data['currentType']);
    }

    public function test_logs_can_filter_by_type(): void
    {
        $controller = new SystemController;
        $request = Request::create('/system/logs?type=email', 'GET');

        $view = $controller->logs($request);

        $data = $view->getData();
        $this->assertEquals('email', $data['currentType']);
    }
}
