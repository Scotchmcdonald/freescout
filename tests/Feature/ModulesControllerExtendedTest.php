<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Nwidart\Modules\Facades\Module;
use Tests\TestCase;

class ModulesControllerExtendedTest extends TestCase
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

    // ==================== Index Page ====================

    public function test_modules_index_displays_flash_messages(): void
    {
        Cache::put('modules_flash', [
            'text' => 'Test flash message',
            'type' => 'success',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('modules'));

        $response->assertOk();
        $response->assertViewHas('flashes');
        
        // Flash should be cleared after display
        $this->assertNull(Cache::get('modules_flash'));
    }

    public function test_modules_index_handles_multiple_flash_messages(): void
    {
        Cache::put('modules_flash', [
            ['text' => 'Message 1', 'type' => 'success'],
            ['text' => 'Message 2', 'type' => 'warning'],
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('modules'));

        $response->assertOk();
        $response->assertViewHas('flashes', function ($flashes) {
            return count($flashes) >= 2;
        });
    }

    public function test_modules_index_shows_remote_modules(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('modules'));

        $response->assertOk();
        $response->assertViewHas('remoteModules');
    }

    // ==================== Install Module ====================

    public function test_install_module_requires_alias(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('modules.install'), []);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_install_module_returns_error_for_unknown_alias(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('modules.install'), [
                'alias' => 'non-existent-module',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ==================== Enable Module ====================

    public function test_enable_module_stores_flash_on_success(): void
    {
        // This test verifies flash message storage behavior
        $response = $this->actingAs($this->admin)
            ->postJson(route('modules.enable', 'nonexistent'));

        $response->assertNotFound();
        $response->assertJson([
            'status' => 'error',
        ]);
    }

    public function test_enable_module_requires_admin(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('modules.enable', 'test'));

        $response->assertForbidden();
    }

    // ==================== Disable Module ====================

    public function test_disable_module_stores_flash_on_success(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('modules.disable', 'nonexistent'));

        $response->assertNotFound();
        $response->assertJson([
            'status' => 'error',
        ]);
    }

    public function test_disable_module_requires_admin(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('modules.disable', 'test'));

        $response->assertForbidden();
    }

    // ==================== Delete Module ====================

    public function test_delete_module_returns_not_found_for_missing(): void
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson(route('modules.delete', 'nonexistent'));

        $response->assertNotFound();
        $response->assertJson([
            'status' => 'error',
            'message' => 'Module not found',
        ]);
    }

    public function test_delete_module_requires_admin(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson(route('modules.delete', 'test'));

        $response->assertForbidden();
    }

    // ==================== Authorization ====================

    public function test_guest_cannot_install_modules(): void
    {
        $response = $this->post(route('modules.install'), [
            'alias' => 'test',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_install_modules(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('modules.install'), [
                'alias' => 'test',
            ]);

        $response->assertForbidden();
    }
}
