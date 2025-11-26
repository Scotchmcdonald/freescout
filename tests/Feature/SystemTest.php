<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);
    }

    public function test_admin_can_view_system_status_page(): void
    {
        // Act
        $response = $this->actingAs($this->admin)->get(route('system'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('system.index');
        $response->assertSee('System Dashboard');
        $response->assertSee('PHP Version');
        $response->assertSee('Laravel Version');
        $response->assertSee(PHP_VERSION);
        $response->assertSee(app()->version());
        $response->assertViewHas('stats');
        $response->assertViewHas('systemInfo');
    }

    public function test_system_status_page_displays_php_version(): void
    {
        // Act
        $response = $this->actingAs($this->admin)->get(route('system'));

        // Assert
        $response->assertOk();
        $response->assertSee(PHP_VERSION);
    }

    public function test_system_status_page_displays_laravel_version(): void
    {
        // Act
        $response = $this->actingAs($this->admin)->get(route('system'));

        // Assert
        $response->assertOk();
        $response->assertSee(app()->version());
    }

    public function test_admin_can_run_system_diagnostics(): void
    {
        // Act
        $response = $this->actingAs($this->admin)->get(route('system.diagnostics'));

        // Assert
        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'success',
            'checks' => [
                'database',
                'storage',
                'cache',
                'extensions',
            ],
        ]);
    }

    public function test_non_admin_cannot_view_system_status(): void
    {
        // Act
        $response = $this->actingAs($this->user)->get(route('system'));

        // Assert
        $response->assertForbidden();
    }

    public function test_admin_can_view_system_logs(): void
    {
        // Act
        $response = $this->actingAs($this->admin)->get(route('system.logs'));

        // Assert
        $response->assertOk();
    }

    public function test_non_admin_cannot_access_system_logs(): void
    {
        // Act
        $response = $this->actingAs($this->user)->get(route('system.logs'));

        // Assert
        $response->assertForbidden();
    }

    public function test_admin_can_clear_cache_via_ajax(): void
    {
        // Arrange
        $this->actingAs($this->admin);

        // Act
        $response = $this->post(route('system.ajax'), [
            'action' => 'clear_cache',
        ]);

        // Assert
        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_admin_can_optimize_application_via_ajax(): void
    {
        // Arrange
        $this->actingAs($this->admin);

        // Mock Artisan to prevent actual optimization
        \Illuminate\Support\Facades\Artisan::shouldReceive('call')
            ->with('optimize')
            ->once();

        \Illuminate\Support\Facades\Artisan::shouldReceive('output')
            ->andReturn('Optimized.');

        // Act
        $response = $this->post(route('system.ajax'), [
            'action' => 'optimize',
        ]);

        // Assert
        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_admin_can_get_system_info_via_ajax(): void
    {
        // Arrange
        $this->actingAs($this->admin);

        // Act
        $response = $this->post(route('system.ajax'), [
            'action' => 'system_info',
        ]);

        // Assert
        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'success',
            'info' => [
                'php_version',
                'laravel_version',
                'db_connection',
                'cache_driver',
            ],
        ]);
    }

    public function test_non_admin_cannot_execute_system_ajax_commands(): void
    {
        // Arrange
        $this->actingAs($this->user);

        // Act
        $response = $this->post(route('system.ajax'), [
            'action' => 'clear_cache',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invalid_ajax_action_returns_error(): void
    {
        // Arrange
        $this->actingAs($this->admin);

        // Act
        $response = $this->post(route('system.ajax'), [
            'action' => 'invalid_action',
        ]);

        // Assert
        $response->assertStatus(400);
        $response->assertJson(['success' => false]);
    }
}
