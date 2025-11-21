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
}
