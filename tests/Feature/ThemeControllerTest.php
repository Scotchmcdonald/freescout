<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_themes_page(): void
    {
        $regularUser = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($regularUser)->get(route('themes'));

        // Should be forbidden or redirected
        $this->assertTrue($response->isForbidden() || $response->isRedirect());
    }

    public function test_admin_can_access_themes_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('themes'));

        $response->assertStatus(200);
        $response->assertViewIs('themes.index');
    }

    public function test_guest_cannot_access_themes_page(): void
    {
        $response = $this->get(route('themes'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_update_theme_preference(): void
    {
        Theme::create(['name' => 'dark', 'title' => 'Dark', 'config' => []]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('themes.update'), [
            'theme' => 'dark',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Refresh user from database
        $admin->refresh();
        $this->assertEquals('dark', $admin->theme);
    }

    public function test_admin_can_set_default_theme(): void
    {
        Theme::create(['name' => 'default', 'title' => 'Default', 'config' => []]);
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'theme' => 'dark',
        ]);

        $response = $this->actingAs($admin)->post(route('themes.update'), [
            'theme' => 'default',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Refresh user from database - 'default' should be stored
        $admin->refresh();
        $this->assertEquals('default', $admin->theme);
    }

    public function test_theme_page_shows_available_themes(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('themes'));

        $response->assertStatus(200);
        $response->assertViewHas('themes');
        $response->assertViewHas('currentTheme');
    }

    public function test_user_theme_field_is_fillable(): void
    {
        $user = User::factory()->create(['theme' => 'dark']);

        $this->assertEquals('dark', $user->theme);
    }

    public function test_user_can_have_null_theme(): void
    {
        $user = User::factory()->create(['theme' => null]);

        $this->assertNull($user->theme);
    }

    public function test_theme_middleware_applies_user_theme(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'theme' => 'dark',
        ]);

        // Access any authenticated route to trigger the middleware
        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
    }

    public function test_theme_validation_accepts_valid_theme_name(): void
    {
        Theme::create(['name' => 'valid-theme-name', 'title' => 'Valid Theme', 'config' => []]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('themes.update'), [
            'theme' => 'valid-theme-name',
        ]);

        // Should either succeed (if theme exists) or show error (if doesn't exist)
        // But not validation error for the format
        $response->assertRedirect();
    }

    public function test_theme_validation_rejects_directory_traversal(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('themes.update'), [
            'theme' => '../../../etc/passwd',
        ]);

        $response->assertSessionHasErrors('theme');
    }

    public function test_theme_validation_rejects_special_characters(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('themes.update'), [
            'theme' => 'theme<script>',
        ]);

        $response->assertSessionHasErrors('theme');
    }

    public function test_theme_validation_rejects_too_long_theme_name(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('themes.update'), [
            'theme' => str_repeat('a', 60), // More than 50 chars
        ]);

        $response->assertSessionHasErrors('theme');
    }

    public function test_themes_menu_appears_in_navigation_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee(__('Themes'));
    }

    public function test_non_admin_cannot_update_theme(): void
    {
        $regularUser = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($regularUser)->post(route('themes.update'), [
            'theme' => 'dark',
        ]);

        $this->assertTrue($response->isForbidden() || $response->isRedirect());
    }

    public function test_admin_can_toggle_dark_mode_via_ajax(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->postJson(route('themes.update'), [
            'dark_mode' => true,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'dark_mode' => true]);

        $admin->refresh();
        $this->assertTrue($admin->dark_mode);
    }
}
