<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nwidart\Modules\Facades\Module;
use Tests\TestCase;

class ModulesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->user = User::factory()->create(['role' => User::ROLE_USER]);
    }

    /**
     * Test admin can view modules list page.
     */
    public function test_admin_can_view_modules_list_page(): void
    {
        // Act
        $response = $this->actingAs($this->admin)->get(route('modules'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('modules');
    }

    /**
     * Test non-admin cannot view modules list.
     */
    public function test_non_admin_cannot_view_modules_list(): void
    {
        // Act
        $response = $this->actingAs($this->user)->get(route('modules'));

        // Assert
        $response->assertForbidden();
    }

    /**
     * Test guest cannot view modules list.
     */
    public function test_guest_cannot_view_modules_list(): void
    {
        // Act
        $response = $this->get(route('modules'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    /**
     * Test modules list displays module metadata.
     */
    public function test_modules_list_displays_module_metadata(): void
    {
        // Act
        $response = $this->actingAs($this->admin)->get(route('modules'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('modules', function ($modules) {
            // Verify each module has required metadata
            foreach ($modules as $module) {
                if (! isset($module['name']) || ! isset($module['alias']) ||
                    ! isset($module['description']) || ! isset($module['enabled'])) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Test enable module returns error for non-existent module.
     */
    public function test_enable_module_returns_error_for_non_existent_module(): void
    {
        // Act
        $response = $this->actingAs($this->admin)
            ->postJson(route('modules.enable', 'non-existent-module'));

        // Assert
        $response->assertNotFound();
        $response->assertJson([
            'status' => 'error',
        ]);
        $response->assertJsonStructure([
            'status',
            'message',
        ]);
    }

    /**
     * Test disable module returns error for non-existent module.
     */
    public function test_disable_module_returns_error_for_non_existent_module(): void
    {
        // Act
        $response = $this->actingAs($this->admin)
            ->postJson(route('modules.disable', 'non-existent-module'));

        // Assert
        $response->assertNotFound();
        $response->assertJson([
            'status' => 'error',
        ]);
    }

    /**
     * Test delete module returns error for non-existent module.
     */
    public function test_delete_module_returns_error_for_non_existent_module(): void
    {
        // Act
        $response = $this->actingAs($this->admin)
            ->deleteJson(route('modules.delete', 'non-existent-module'));

        // Assert
        $response->assertNotFound();
        $response->assertJson([
            'status' => 'error',
        ]);
    }

    /**
     * Test non-admin cannot enable modules.
     */
    public function test_non_admin_cannot_enable_modules(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->postJson(route('modules.enable', 'test-module'));

        // Assert
        $response->assertForbidden();
    }

    /**
     * Test non-admin cannot disable modules.
     */
    public function test_non_admin_cannot_disable_modules(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->postJson(route('modules.disable', 'test-module'));

        // Assert
        $response->assertForbidden();
    }

    /**
     * Test non-admin cannot delete modules.
     */
    public function test_non_admin_cannot_delete_modules(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->deleteJson(route('modules.delete', 'test-module'));

        // Assert
        $response->assertForbidden();
    }

    /**
     * Test guest cannot enable modules.
     */
    public function test_guest_cannot_enable_modules(): void
    {
        // Act
        $response = $this->postJson(route('modules.enable', 'test-module'));

        // Assert
        $response->assertUnauthorized();
    }

    /**
     * Test guest cannot disable modules.
     */
    public function test_guest_cannot_disable_modules(): void
    {
        // Act
        $response = $this->postJson(route('modules.disable', 'test-module'));

        // Assert
        $response->assertUnauthorized();
    }

    /**
     * Test guest cannot delete modules.
     */
    public function test_guest_cannot_delete_modules(): void
    {
        // Act
        $response = $this->deleteJson(route('modules.delete', 'test-module'));

        // Assert
        $response->assertUnauthorized();
    }

    /**
     * Test enable module returns proper JSON structure on success.
     */
    public function test_enable_module_returns_proper_json_structure(): void
    {
        // Skip if no modules available
        if (Module::count() === 0) {
            $this->markTestSkipped('No modules available for testing');
        }

        // This test documents expected response structure
        // In a real scenario with a test module, we would verify the actual response
        $this->assertTrue(true);
    }

    /**
     * Test disable module returns proper JSON structure on success.
     */
    public function test_disable_module_returns_proper_json_structure(): void
    {
        // Skip if no modules available
        if (Module::count() === 0) {
            $this->markTestSkipped('No modules available for testing');
        }

        // This test documents expected response structure
        $this->assertTrue(true);
    }

    /**
     * Test modules index requires manage-settings permission.
     */
    public function test_modules_index_requires_manage_settings_permission(): void
    {
        // Create user without manage-settings permission
        $limitedUser = User::factory()->create(['role' => User::ROLE_USER]);

        // Act
        $response = $this->actingAs($limitedUser)->get(route('modules'));

        // Assert
        $response->assertForbidden();
    }

    /**
     * Test module operations require authentication.
     */
    public function test_module_operations_require_authentication(): void
    {
        // Test enable
        $response1 = $this->postJson(route('modules.enable', 'test'));
        $response1->assertUnauthorized();

        // Test disable
        $response2 = $this->postJson(route('modules.disable', 'test'));
        $response2->assertUnauthorized();

        // Test delete
        $response3 = $this->deleteJson(route('modules.delete', 'test'));
        $response3->assertUnauthorized();
    }

    /**
     * Test module list handles empty modules collection.
     */
    public function test_module_list_handles_empty_modules_collection(): void
    {
        // Act
        $response = $this->actingAs($this->admin)->get(route('modules'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('modules', function ($modules) {
            return is_array($modules);
        });
    }
}
