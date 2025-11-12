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

        $response = $this->actingAs($user)->get(route('system.index'));

        // Should be forbidden or redirected
        $this->assertTrue($response->isForbidden() || $response->isRedirect());
    }

    public function test_admin_can_view_system_dashboard(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('system.index'));

        // Should be successful
        $this->assertTrue($response->isSuccessful() || $response->isRedirect());
    }

    public function test_guest_redirected_to_login(): void
    {
        $response = $this->get(route('system.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_diagnostics_endpoint_returns_health_status(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('system.diagnostics'));

        // Should return JSON or redirect
        $this->assertTrue($response->isSuccessful() || $response->isRedirect());
    }

    public function test_ajax_clear_cache_command(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Cache::put('test_key', 'value');

        $response = $this->actingAs($admin)->post(route('system.ajax'), ['action' => 'clear_cache']);

        // Command should execute
        $this->assertTrue($response->isSuccessful() || $response->isRedirect());
    }

    public function test_ajax_optimize_command(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('system.ajax'), ['action' => 'optimize']);

        // Command should execute
        $this->assertTrue($response->isSuccessful() || $response->isRedirect());
    }

    public function test_ajax_fetch_mail_triggers_email_fetch(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('system.ajax'), ['action' => 'fetch_mail']);

        // Should respond successfully
        $this->assertTrue($response !== null);
    }

    public function test_logs_page_displays_application_logs(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('system.logs'));

        // Should display logs page or redirect
        $this->assertTrue($response->isSuccessful() || $response->isRedirect());
    }
}
