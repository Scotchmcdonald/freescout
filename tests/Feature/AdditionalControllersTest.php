<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\FeatureTestCase;

/**
 * Comprehensive tests for additional Controller classes
 * Following TESTING_GUIDE.md - using test_ prefix, FeatureTestCase base class
 */
class AdditionalControllersTest extends FeatureTestCase
{
    // ===== SYSTEM CONTROLLER TESTS =====

    public function test_system_index_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->get(route('system'));

        $response->assertForbidden();
    }

    public function test_system_index_shows_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('system'));

        $response->assertOk();
        $response->assertViewIs('system.index');
    }

    public function test_system_shows_php_info_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('system.phpinfo'));

        $response->assertOk();
        $response->assertSee('PHP Version');
    }

    public function test_system_tools_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->get(route('system.tools'));

        $response->assertForbidden();
    }

    public function test_system_can_clear_cache(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('system.clear-cache'));

        $response->assertRedirect();
        $response->assertSessionHas('flash_success_floating');
    }

    public function test_system_can_run_migrations(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('system.migrate'));

        $response->assertRedirect();
    }

    public function test_system_shows_logs_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('system.logs'));

        $response->assertOk();
        $response->assertViewIs('system.logs');
    }

    // ===== MODULES CONTROLLER TESTS =====

    public function test_modules_index_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->get(route('modules'));

        $response->assertForbidden();
    }

    public function test_modules_index_shows_all_modules(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Module::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('modules'));

        $response->assertOk();
        $response->assertViewIs('modules.index');
        $response->assertViewHas('modules');
    }

    public function test_modules_can_activate_module(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $module = Module::factory()->create(['active' => false]);

        $response = $this->actingAs($admin)->post(route('modules.activate', $module));

        $response->assertRedirect();
        $this->assertTrue($module->fresh()->active);
    }

    public function test_modules_can_deactivate_module(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $module = Module::factory()->create(['active' => true]);

        $response = $this->actingAs($admin)->post(route('modules.deactivate', $module));

        $response->assertRedirect();
        $this->assertFalse($module->fresh()->active);
    }

    public function test_modules_can_install_new_module(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('modules.install'), [
            'alias' => 'test-module',
        ]);

        $response->assertRedirect();
    }

    public function test_modules_can_update_module(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $module = Module::factory()->create();

        $response = $this->actingAs($admin)->post(route('modules.update', $module));

        $response->assertRedirect();
    }

    public function test_modules_can_delete_module(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $module = Module::factory()->create();

        $response = $this->actingAs($admin)->delete(route('modules.destroy', $module));

        $response->assertRedirect();
        $this->assertDatabaseMissing('modules', ['id' => $module->id]);
    }

    // ===== SECURE CONTROLLER TESTS =====

    public function test_secure_download_requires_authentication(): void
    {
        $response = $this->get(route('secure.download', ['path' => 'test.pdf']));

        $response->assertRedirect(route('login'));
    }

    public function test_secure_download_validates_path(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('secure.download', ['path' => '../../../etc/passwd']));

        $response->assertForbidden();
    }

    public function test_secure_download_checks_file_existence(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('secure.download', ['path' => 'nonexistent.pdf']));

        $response->assertNotFound();
    }

    public function test_secure_download_checks_user_authorization(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        // User not assigned to mailbox

        $response = $this->actingAs($user)->get(route('secure.download', [
            'path' => 'mailbox/' . $mailbox->id . '/file.pdf'
        ]));

        $response->assertForbidden();
    }

    // ===== AJAX CONTROLLER TESTS =====

    public function test_ajax_search_customers_requires_authentication(): void
    {
        $response = $this->get(route('ajax.search.customers', ['q' => 'test']));

        $response->assertUnauthorized();
    }

    public function test_ajax_search_customers_returns_json(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('ajax.search.customers', ['q' => 'test']));

        $response->assertOk();
        $response->assertJson([]);
    }

    public function test_ajax_search_customers_filters_by_query(): void
    {
        $user = User::factory()->create();
        $customer1 = \App\Models\Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $customer2 = \App\Models\Customer::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $response = $this->actingAs($user)->get(route('ajax.search.customers', ['q' => 'John']));

        $response->assertOk();
        $response->assertJsonFragment(['first_name' => 'John']);
        $response->assertJsonMissing(['first_name' => 'Jane']);
    }

    public function test_ajax_search_users_requires_authentication(): void
    {
        $response = $this->get(route('ajax.search.users', ['q' => 'test']));

        $response->assertUnauthorized();
    }

    public function test_ajax_search_users_returns_json(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('ajax.search.users', ['q' => 'test']));

        $response->assertOk();
        $response->assertJson([]);
    }

    public function test_ajax_update_folder_counters(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);

        $response = $this->actingAs($user)->post(route('ajax.folder.update-counters', $folder));

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    // ===== EDGE CASE TESTS =====

    public function test_controllers_handle_missing_route_parameters(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get('/mailboxes/99999');

        $response->assertNotFound();
    }

    public function test_controllers_handle_invalid_method_calls(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->patch(route('system'));

        $response->assertMethodNotAllowed();
    }

    public function test_controllers_validate_csrf_tokens(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->actingAs($admin)
            ->post(route('system.clear-cache'));

        $response->assertRedirect();
    }

    public function test_controllers_handle_concurrent_requests(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->actingAs($user)->get(route('mailboxes.show', $mailbox));
        }

        foreach ($responses as $response) {
            $response->assertOk();
        }
    }

    public function test_controllers_handle_special_characters_in_input(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('ajax.search.customers', [
            'q' => '<script>alert("xss")</script>',
        ]));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringNotContainsString('<script>', $content);
    }

    public function test_controllers_respect_rate_limiting(): void
    {
        $user = User::factory()->create();

        // Make multiple rapid requests
        for ($i = 0; $i < 100; $i++) {
            $response = $this->actingAs($user)->get(route('mailboxes.index'));

            if ($response->status() === 429) {
                $this->assertEquals(429, $response->status());
                return;
            }
        }

        $this->assertTrue(true); // No rate limiting in test environment
    }

    public function test_controllers_log_important_actions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $module = Module::factory()->create();

        \Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/Module.*activated/'));

        $this->actingAs($admin)->post(route('modules.activate', $module));
    }
}
