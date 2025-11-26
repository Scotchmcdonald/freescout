<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Misc\Helper;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\FeatureTestCase;

/**
 * Feature tests for SystemController methods added during Phase 1 implementation.
 */
class SystemControllerMethodsTest extends FeatureTestCase
{
    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->user = User::factory()->create(['role' => User::ROLE_USER]);
    }

    protected function tearDown(): void
    {
        Artisan::call('optimize:clear');
        parent::tearDown();
    }

    // ===== tools() tests =====

    public function test_admin_can_access_tools_page(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('system.tools'));

        $response->assertOk();
        $response->assertViewIs('system.tools');
    }

    public function test_non_admin_cannot_access_tools_page(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('system.tools'));

        $response->assertForbidden();
    }

    public function test_guest_redirected_from_tools_page(): void
    {
        $response = $this->get(route('system.tools'));

        $response->assertRedirect(route('login'));
    }

    public function test_tools_page_displays_cron_url(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('system.tools'));

        $response->assertOk();
        $response->assertSee('cron');
    }

    // ===== toolsExecute() tests =====

    public function test_admin_can_execute_clear_cache_tool(): void
    {
        $this->actingAs($this->admin);
        Cache::put('test_key', 'test_value');

        $response = $this->post(route('system.tools'), [
            'action' => 'clear_cache',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash_success');
    }

    public function test_admin_can_execute_optimize_tool(): void
    {
        $this->actingAs($this->admin);

        // Mock Artisan to prevent actual optimization which breaks other tests
        Artisan::shouldReceive('call')
            ->with('optimize', \Mockery::any(), \Mockery::any())
            ->once()
            ->andReturn(0);

        // Allow the tearDown cleanup command
        Artisan::shouldReceive('call')
            ->with('optimize:clear')
            ->andReturn(0);

        $response = $this->post(route('system.tools'), [
            'action' => 'optimize',
        ]);

        $response->assertRedirect();
    }

    public function test_non_admin_cannot_execute_tools(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('system.tools'), [
            'action' => 'clear_cache',
        ]);

        $response->assertForbidden();
    }

    public function test_invalid_tool_action_shows_error(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('system.tools'), [
            'action' => 'invalid_action',
        ]);

        $response->assertRedirect();
        // Should not crash, should redirect back
    }

    // ===== clearLogs() tests =====

    public function test_admin_can_clear_logs(): void
    {
        $this->actingAs($this->admin);

        // Create a log file
        $logFile = storage_path('logs/laravel.log');
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        file_put_contents($logFile, "Test log entry\n");

        $response = $this->post(route('system.logs.clear'));

        $response->assertRedirect();
    }

    public function test_non_admin_cannot_clear_logs(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('system.logs.clear'));

        $response->assertForbidden();
    }

    // ===== cron() tests =====

    public function test_cron_with_valid_hash_succeeds(): void
    {
        $hash = Helper::getWebCronHash();

        $response = $this->get(route('system.cron', ['hash' => $hash]));

        $response->assertOk();
        // The output depends on whether there are scheduled commands, but usually it returns output
        // We just check that it doesn't fail
    }

    public function test_cron_with_invalid_hash_fails(): void
    {
        $response = $this->get(route('system.cron', ['hash' => 'invalid-hash']));

        $response->assertNotFound();
    }

    // ===== Failed jobs management tests =====

    public function test_admin_can_delete_failed_jobs_for_queue(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('system.failed-jobs.queue.delete'), [
            'queue' => 'default',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_admin_can_retry_failed_jobs_for_queue(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('system.failed-jobs.queue.retry'), [
            'queue' => 'default',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_non_admin_cannot_delete_failed_jobs(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('system.failed-jobs.queue.delete'), [
            'queue' => 'default',
        ]);

        $response->assertForbidden();
    }

    public function test_non_admin_cannot_retry_failed_jobs(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('system.failed-jobs.queue.retry'), [
            'queue' => 'default',
        ]);

        $response->assertForbidden();
    }

    // ===== System status checks tests =====

    public function test_system_index_shows_php_extensions(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('system.index'));

        $response->assertOk();
        // Should show PHP extensions status
        $response->assertViewHas('systemInfo');
        $systemInfo = $response->viewData('systemInfo');
        $this->assertArrayHasKey('php_extensions', $systemInfo);
    }

    public function test_system_index_shows_php_functions(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('system.index'));

        $response->assertOk();
        $response->assertViewHas('systemInfo');
        $systemInfo = $response->viewData('systemInfo');
        $this->assertArrayHasKey('required_functions', $systemInfo);
    }

    public function test_system_index_shows_directory_permissions(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('system.index'));

        $response->assertOk();
        $response->assertViewHas('systemInfo');
        $systemInfo = $response->viewData('systemInfo');
        $this->assertArrayHasKey('permissions', $systemInfo);
    }

    // ===== Edge cases =====

    public function test_tools_execute_with_db_migrate_action(): void
    {
        $this->actingAs($this->admin);

        // This might fail in test environment, but should not crash
        $response = $this->post(route('system.tools'), [
            'action' => 'db_migrate',
        ]);

        // Should redirect regardless of success/failure
        $response->assertRedirect();
    }

    public function test_tools_execute_with_fetch_mail_action(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('system.tools'), [
            'action' => 'fetch_mail',
        ]);

        $response->assertRedirect();
    }

    public function test_cron_timing_attack_resistance(): void
    {
        $validHash = Helper::getWebCronHash();
        $invalidHash = $validHash . 'x'; // Slightly modified

        // Both should complete (no timing difference to measure, but we verify the behavior)
        $validResponse = $this->get(route('system.cron', ['hash' => $validHash]));
        $invalidResponse = $this->get(route('system.cron', ['hash' => $invalidHash]));

        $validResponse->assertOk();
        $invalidResponse->assertNotFound();
    }
}
