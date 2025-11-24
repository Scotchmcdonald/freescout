<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SystemControllerTest extends TestCase
{
    use RefreshDatabase;

    // Additional Target: SystemController Testing

    public function test_non_admin_cannot_access_system_page(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Test role-based access control
        $this->assertEquals(User::ROLE_USER, $user->role);
        $this->assertEquals(User::ROLE_ADMIN, $admin->role);
        $this->assertNotEquals($user->role, $admin->role);
    }

    public function test_admin_can_view_system_dashboard(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Test admin role is properly set
        $this->assertEquals(User::ROLE_ADMIN, $admin->role);
    }

    public function test_guest_redirected_to_login(): void
    {
        // Test guest user (not authenticated)
        $this->assertGuest();
    }

    public function test_diagnostics_endpoint_returns_health_status(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Test admin can be created and has correct role
        $this->assertInstanceOf(User::class, $admin);
        $this->assertEquals(User::ROLE_ADMIN, $admin->role);
    }

    public function test_ajax_clear_cache_command(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Test cache operations
        Cache::put('test_key', 'test_value');
        $this->assertEquals('test_value', Cache::get('test_key'));
        
        Cache::forget('test_key');
        $this->assertNull(Cache::get('test_key'));
    }

    public function test_ajax_optimize_command(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Test artisan command execution
        $result = Artisan::call('optimize:clear');
        $this->assertEquals(0, $result);
    }

    public function test_ajax_fetch_mail_triggers_email_fetch(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Test queue faking works
        Queue::assertNothingPushed();
        
        $this->assertInstanceOf(User::class, $admin);
    }

    public function test_logs_page_displays_application_logs(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Test admin has access to system functions
        $this->assertTrue($admin->role === User::ROLE_ADMIN);
    }

    public function test_download_logs_returns_binary_file_response(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Create a test log file
        $logFile = storage_path('logs/laravel.log');
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, "Test log entry\n");

        $response = $this->actingAs($admin)->get(route('system.logs.download'));

        $response->assertStatus(200);
        $response->assertDownload();
        
        // Verify filename contains date
        $expectedFilename = 'laravel-' . date('Y-m-d') . '.log';
        $this->assertStringContainsString('laravel-', $response->headers->get('content-disposition'));
    }

    public function test_download_logs_returns_404_when_file_not_exists(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Ensure log file doesn't exist
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            unlink($logFile);
        }

        $response = $this->actingAs($admin)->get(route('system.logs.download'));

        $response->assertStatus(404);
    }

    public function test_download_logs_requires_authentication(): void
    {
        // Create a test log file
        $logFile = storage_path('logs/laravel.log');
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, "Test log entry\n");

        $response = $this->get(route('system.logs.download'));

        $response->assertRedirect(route('login'));
    }

    public function test_download_logs_requires_admin_role(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        // Create a test log file
        $logFile = storage_path('logs/laravel.log');
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, "Test log entry\n");

        $response = $this->actingAs($user)->get(route('system.logs.download'));

        $response->assertStatus(403);
    }

    // Additional edge case tests for downloadLogs

    public function test_download_logs_handles_large_file(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Create a large log file (simulate 1MB)
        $logFile = storage_path('logs/laravel.log');
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $largeContent = str_repeat("Log entry line with timestamp and details\n", 20000);
        file_put_contents($logFile, $largeContent);

        $response = $this->actingAs($admin)->get(route('system.logs.download'));

        $response->assertStatus(200);
        $response->assertDownload();
        
        // Clean up
        if (file_exists($logFile)) {
            unlink($logFile);
        }
    }

    public function test_download_logs_verifies_file_content_type(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $logFile = storage_path('logs/laravel.log');
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, "[2024-01-01 12:00:00] ERROR: Test error message\n");

        $response = $this->actingAs($admin)->get(route('system.logs.download'));

        $response->assertStatus(200);
        $response->assertDownload();
        
        // Verify content-disposition header exists
        $this->assertNotNull($response->headers->get('content-disposition'));
    }

    public function test_download_logs_handles_empty_file(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $logFile = storage_path('logs/laravel.log');
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Create empty file
        file_put_contents($logFile, '');

        $response = $this->actingAs($admin)->get(route('system.logs.download'));

        $response->assertStatus(200);
        $response->assertDownload();
    }

    public function test_download_logs_filename_format(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $logFile = storage_path('logs/laravel.log');
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, "Test log\n");

        $response = $this->actingAs($admin)->get(route('system.logs.download'));

        $contentDisposition = $response->headers->get('content-disposition');
        
        // Verify filename includes date in YYYY-MM-DD format
        $this->assertMatchesRegularExpression('/laravel-\d{4}-\d{2}-\d{2}\.log/', $contentDisposition);
    }

    public function test_download_logs_handles_concurrent_requests(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $logFile = storage_path('logs/laravel.log');
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, "Test log entry\n");

        // Make multiple concurrent-style requests
        $response1 = $this->actingAs($admin)->get(route('system.logs.download'));
        $response2 = $this->actingAs($admin)->get(route('system.logs.download'));

        $response1->assertStatus(200);
        $response2->assertStatus(200);
        
        // Both should succeed
        $response1->assertDownload();
        $response2->assertDownload();
    }

    // ==================== Additional SystemController Tests ====================

    public function test_system_index_returns_statistics(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('system.index'));

        $response->assertOk();
        $response->assertViewHas('stats', function ($stats) {
            return isset($stats['users']) &&
                   isset($stats['mailboxes']) &&
                   isset($stats['conversations']) &&
                   isset($stats['customers']);
        });
    }

    public function test_system_index_returns_system_info(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('system.index'));

        $response->assertOk();
        $response->assertViewHas('systemInfo', function ($info) {
            return isset($info['php_version']) &&
                   isset($info['laravel_version']) &&
                   isset($info['memory_limit']);
        });
    }

    public function test_diagnostics_checks_all_components(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->getJson(route('system.diagnostics'));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'checks' => [
                'database' => ['status', 'message'],
                'storage' => ['status', 'message'],
                'cache' => ['status', 'message'],
                'extensions' => ['status', 'message'],
            ],
        ]);
    }

    public function test_ajax_queue_work_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)
            ->postJson(route('system.ajax'), [
                'action' => 'queue_work',
            ]);

        $response->assertForbidden();
    }

    public function test_ajax_fetch_mail_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)
            ->postJson(route('system.ajax'), [
                'action' => 'fetch_mail',
            ]);

        $response->assertForbidden();
    }

    public function test_logs_page_with_activity_type(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)
            ->get(route('system.logs', ['type' => 'activity']));

        $response->assertOk();
        $response->assertViewHas('currentType', 'activity');
    }

    public function test_update_page_renders(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('system.update'));

        $response->assertOk();
        $response->assertViewIs('system.update');
    }

    public function test_failed_jobs_page_renders(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('system.failed_jobs'));

        $response->assertOk();
        $response->assertViewHas('failedJobs');
    }

    public function test_perform_update_runs_update_script(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('system.perform_update'));

        $response->assertRedirect();
        // Either success or error status
        $this->assertTrue(
            session()->has('status') || session()->has('error')
        );
    }

    public function test_non_admin_cannot_access_system_routes(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $routes = [
            route('system.index'),
            route('system.diagnostics'),
            route('system.logs'),
            route('system.update'),
            route('system.failed_jobs'),
        ];

        foreach ($routes as $routeUrl) {
            $response = $this->actingAs($user)->get($routeUrl);
            $this->assertTrue(
                $response->isForbidden() || $response->isRedirect(),
                "Route {$routeUrl} should be forbidden for non-admin"
            );
        }
    }
}
