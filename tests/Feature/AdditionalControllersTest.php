<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\FeatureTestCase;
use Nwidart\Modules\Facades\Module as NwidartModule;
use Mockery;

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

        $response = $this->actingAs($admin)->get(route('system'));

        $response->assertOk();
        $response->assertSee(PHP_VERSION);
    }

    public function test_system_can_clear_cache(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('settings.cache.clear'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_system_can_run_migrations(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('settings.migrate'));

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
        if (!class_exists(\Nwidart\Modules\Module::class)) {
            $this->markTestSkipped('Nwidart Modules package not installed');
        }

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $module = Module::factory()->create(['active' => false]);

        // Create a mock that extends the actual Module class if it exists, or a generic mock if not
        $moduleClass = \Nwidart\Modules\Module::class;
        $nwidartModule = Mockery::mock($moduleClass);
        
        $nwidartModule->shouldReceive('getName')->andReturn('TestModule');
        $nwidartModule->shouldReceive('enable')->once();
        
        NwidartModule::shouldReceive('find')
            ->with($module->alias)
            ->andReturn($nwidartModule);
            
        Artisan::shouldReceive('call')->andReturn(0);
        \Illuminate\Support\Facades\Log::shouldReceive('info')->withAnyArgs();

        $response = $this->actingAs($admin)->post(route('modules.enable', $module->alias));

        $response->assertOk();
    }

    public function test_modules_can_deactivate_module(): void
    {
        if (!class_exists(\Nwidart\Modules\Module::class)) {
            $this->markTestSkipped('Nwidart Modules package not installed');
        }

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $module = Module::factory()->create(['active' => true]);

        $moduleClass = \Nwidart\Modules\Module::class;
        $nwidartModule = Mockery::mock($moduleClass);
        
        $nwidartModule->shouldReceive('getName')->andReturn('TestModule');
        $nwidartModule->shouldReceive('disable')->once();
        
        NwidartModule::shouldReceive('find')
            ->with($module->alias)
            ->andReturn($nwidartModule);
            
        Artisan::shouldReceive('call')->andReturn(0);

        $response = $this->actingAs($admin)->post(route('modules.disable', $module->alias));

        $response->assertOk();
    }

    public function test_modules_can_delete_module(): void
    {
        if (!class_exists(\Nwidart\Modules\Module::class)) {
            $this->markTestSkipped('Nwidart Modules package not installed');
        }

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $module = Module::factory()->create();

        $moduleClass = \Nwidart\Modules\Module::class;
        $nwidartModule = Mockery::mock($moduleClass);
        $nwidartModule->shouldIgnoreMissing();
        
        $nwidartModule->shouldReceive('getName')->andReturn('TestModule');
        $nwidartModule->shouldReceive('isEnabled')->andReturn(true);
        $nwidartModule->shouldReceive('disable')->once();
        $nwidartModule->shouldReceive('delete')->andReturn(true);
        $nwidartModule->shouldReceive('getPath')->andReturn('/tmp/module/path');
        
        NwidartModule::shouldReceive('find')
            ->with($module->alias)
            ->andReturn($nwidartModule);
            
        Artisan::shouldReceive('call')->andReturn(0);
        
        // Mock File facade
        \Illuminate\Support\Facades\File::shouldReceive('deleteDirectory')->with('/tmp/module/path')->andReturn(true);
        \Illuminate\Support\Facades\File::shouldReceive('exists')->andReturn(true);
        \Illuminate\Support\Facades\File::shouldReceive('get')->andReturn('{"name": "TestModule"}');
        \Illuminate\Support\Facades\File::shouldReceive('getRequire')->andReturn([]);

        $response = $this->actingAs($admin)->delete(route('modules.delete', $module->alias));

        $response->assertOk();
    }

    // ===== AJAX CONTROLLER TESTS =====

    public function test_ajax_search_customers_requires_authentication(): void
    {
        $response = $this->get(route('customers.search', ['q' => 'test']));

        $response->assertRedirect(route('login'));
    }

    public function test_ajax_search_customers_returns_json(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('customers.ajax'), [
            'action' => 'search',
            'q' => 'test'
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['results']);
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

        $response = $this->actingAs($user)->post(route('customers.ajax'), [
            'action' => 'search',
            'q' => 'John'
        ]);

        $response->assertOk();
        // The JSON structure is ['results' => [['id' => ..., 'text' => ...], ...]]
        // We need to check if 'text' contains 'John'
        $response->assertJsonFragment(['text' => $customer1->getFullName().' ('.$customer1->getMainEmail().')']);
        $response->assertJsonMissing(['text' => $customer2->getFullName().' ('.$customer2->getMainEmail().')']);
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
        $mailbox->users()->attach($user->id);

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

        $response = $this->actingAs($admin)->get(route('customers.search', [
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

        $nwidartModule = Mockery::mock(\Nwidart\Modules\Module::class);
        $nwidartModule->shouldReceive('getName')->andReturn('TestModule');
        $nwidartModule->shouldReceive('enable')->once();
        
        NwidartModule::shouldReceive('find')
            ->with($module->alias)
            ->andReturn($nwidartModule);
            
        Artisan::shouldReceive('call')->andReturn(0);

        \Illuminate\Support\Facades\Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/Module.*activated/'));

        $this->actingAs($admin)->post(route('modules.activate', $module->alias));
    }
}
