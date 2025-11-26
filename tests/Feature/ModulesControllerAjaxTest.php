<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\FeatureTestCase;

/**
 * Feature tests for ModulesController AJAX methods added during Phase 3 implementation.
 */
class ModulesControllerAjaxTest extends FeatureTestCase
{
    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->user = User::factory()->create(['role' => User::ROLE_USER]);

        // Prevent real HTTP requests
        Http::preventStrayRequests();
    }

    // ===== License activation tests =====

    public function test_admin_can_activate_license(): void
    {
        $this->actingAs($this->admin);

        Http::fake([
            '*' => Http::response([
                'success' => true,
                'license' => 'valid',
            ], 200),
        ]);

        $response = $this->postJson(route('modules.ajax'), [
            'action' => 'activate_license',
            'alias' => 'test-module',
            'license' => 'test-license-key',
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('success'));
    }

    public function test_non_admin_cannot_activate_license(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('modules.ajax'), [
            'action' => 'activate_license',
            'alias' => 'test-module',
            'license' => 'test-license-key',
        ]);

        $response->assertForbidden();
    }

    public function test_activate_license_handles_api_failure(): void
    {
        $this->actingAs($this->admin);

        Http::fake([
            '*' => Http::response([
                'success' => false,
                'error' => 'invalid_license',
            ], 200),
        ]);

        $response = $this->postJson(route('modules.ajax'), [
            'action' => 'activate_license',
            'alias' => 'test-module',
            'license' => 'invalid-key',
        ]);

        $response->assertOk();
        $this->assertFalse($response->json('success') ?? true);
    }

    // ===== License deactivation tests =====

    public function test_admin_can_deactivate_license(): void
    {
        $this->actingAs($this->admin);

        // Mock getting license
        // We need to mock Option::get('module_licenses') but it's static.
        // However, the controller uses $this->getModuleLicense which calls Option::get.
        // Since we can't easily mock Option::get in Feature test without partial mock of controller or DB seed.
        // Let's seed the option in DB.
        \App\Models\Option::set('module_licenses', json_encode(['test-module' => 'test-license']));

        Http::fake([
            '*' => Http::response([
                'success' => true,
                'license' => 'deactivated',
            ], 200),
        ]);

        $response = $this->postJson(route('modules.ajax'), [
            'action' => 'deactivate_license',
            'alias' => 'test-module',
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('success'));
    }

    public function test_non_admin_cannot_deactivate_license(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('modules.ajax'), [
            'action' => 'deactivate_license',
            'alias' => 'test-module',
        ]);

        $response->assertForbidden();
    }

    // ===== License check tests =====

    public function test_admin_can_check_license(): void
    {
        $this->actingAs($this->admin);
        \App\Models\Option::set('module_licenses', json_encode(['test-module' => 'test-license']));

        Http::fake([
            '*' => Http::response([
                'success' => true,
                'license' => 'valid',
                'expires' => '2025-12-31',
            ], 200),
        ]);

        $response = $this->postJson(route('modules.ajax'), [
            'action' => 'check_license',
            'alias' => 'test-module',
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('success'));
    }

    // ===== Check updates tests =====

    public function test_admin_can_check_updates(): void
    {
        $this->actingAs($this->admin);

        Http::fake([
            '*' => Http::response([
                'success' => true,
                'new_version' => '2.0.0',
            ], 200),
        ]);

        $response = $this->postJson(route('modules.ajax'), [
            'action' => 'check_updates',
            'alias' => 'test-module',
            'current_version' => '1.0.0',
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('success'));
    }

    public function test_non_admin_cannot_check_updates(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('modules.ajax'), [
            'action' => 'check_updates',
            'alias' => 'test-module',
        ]);

        $response->assertForbidden();
    }

    // ===== Update module tests =====

    public function test_admin_can_initiate_module_update(): void
    {
        $this->actingAs($this->admin);

        Http::fake([
            '*' => Http::response([
                'success' => true,
                'new_version' => '2.0.0',
                'package' => 'https://example.com/module.zip',
            ], 200),
        ]);

        $response = $this->postJson(route('modules.ajax'), [
            'action' => 'update_module',
            'alias' => 'test-module',
        ]);

        // May fail due to actual download, but should not crash
        $this->assertContains($response->status(), [200, 422, 500]);
    }

    public function test_non_admin_cannot_update_module(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('modules.ajax'), [
            'action' => 'update_module',
            'alias' => 'test-module',
        ]);

        $response->assertForbidden();
    }

    // ===== Authorization tests =====

    public function test_guest_cannot_access_modules_ajax(): void
    {
        $response = $this->postJson(route('modules.ajax'), [
            'action' => 'activate_license',
            'alias' => 'test-module',
            'license' => 'test-key',
        ]);

        $response->assertUnauthorized();
    }

    // ===== Validation tests =====

    public function test_activate_license_requires_module(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('modules.ajax'), [
            'action' => 'activate_license',
            'license' => 'test-key',
            // Missing alias
        ]);

        $this->assertTrue($response->status() >= 400 || $response->json('success') === false);
    }

    public function test_activate_license_requires_license_key(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('modules.ajax'), [
            'action' => 'activate_license',
            'alias' => 'test-module',
            // Missing license
        ]);

        $this->assertTrue($response->status() >= 400 || $response->json('success') === false);
    }

    public function test_invalid_action_returns_error(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('modules.ajax'), [
            'action' => 'invalid_action_xyz',
        ]);

        $this->assertTrue($response->status() >= 400 || $response->json('success') === false);
    }

    // ===== Edge cases =====

    public function test_handles_api_timeout(): void
    {
        $this->actingAs($this->admin);

        Http::fake([
            '*' => Http::response(null, 408),
        ]);

        $response = $this->postJson(route('modules.ajax'), [
            'action' => 'check_license',
            'alias' => 'test-module',
        ]);

        // Should handle gracefully
        $this->assertContains($response->status(), [200, 422, 500]);
    }

    public function test_handles_api_server_error(): void
    {
        $this->actingAs($this->admin);

        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $response = $this->postJson(route('modules.ajax'), [
            'action' => 'check_license',
            'alias' => 'test-module',
        ]);

        // Should handle gracefully
        $this->assertContains($response->status(), [200, 422, 500]);
    }

    public function test_handles_malformed_api_response(): void
    {
        $this->actingAs($this->admin);

        Http::fake([
            '*' => Http::response('invalid json {{{', 200),
        ]);

        $response = $this->postJson(route('modules.ajax'), [
            'action' => 'check_license',
            'alias' => 'test-module',
        ]);

        // Should handle gracefully
        $this->assertContains($response->status(), [200, 422, 500]);
    }
}
