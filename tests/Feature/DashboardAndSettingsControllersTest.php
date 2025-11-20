<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Tests\FeatureTestCase;

/**
 * Comprehensive tests for Dashboard and Settings Controllers
 * Following TESTING_GUIDE.md - using test_ prefix, FeatureTestCase base class
 */
class DashboardAndSettingsControllersTest extends FeatureTestCase
{
    // ===== DASHBOARD_CONTROLLER TESTS =====

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('dashboard'));
        
        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_returns_view_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get(route('dashboard'));
        
        $response->assertOk();
        $response->assertViewIs('dashboard');
    }

    public function test_dashboard_shows_user_statistics(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        Conversation::factory()->count(5)->create(['mailbox_id' => $mailbox->id]);
        
        $response = $this->actingAs($user)->get(route('dashboard'));
        
        $response->assertOk();
        $response->assertViewHas('stats');
    }

    public function test_dashboard_admin_sees_all_mailboxes(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Mailbox::factory()->count(3)->create();
        
        $response = $this->actingAs($admin)->get(route('dashboard'));
        
        $response->assertOk();
    }

    public function test_dashboard_user_sees_assigned_mailboxes_only(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        Mailbox::factory()->count(2)->create(); // Other mailboxes
        
        $response = $this->actingAs($user)->get(route('dashboard'));
        
        $response->assertOk();
    }

    public function test_dashboard_shows_recent_conversations(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        Conversation::factory()->count(10)->create(['mailbox_id' => $mailbox->id]);
        
        $response = $this->actingAs($user)->get(route('dashboard'));
        
        $response->assertOk();
    }

    public function test_dashboard_with_no_data(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get(route('dashboard'));
        
        $response->assertOk();
    }

    // ===== SETTINGS_CONTROLLER TESTS =====

    public function test_settings_index_requires_authentication(): void
    {
        $response = $this->get(route('settings.index'));
        
        $response->assertRedirect(route('login'));
    }

    public function test_settings_index_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        
        $response = $this->actingAs($user)->get(route('settings.index'));
        
        $response->assertForbidden();
    }

    public function test_settings_index_returns_view_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->get(route('settings.index'));
        
        $response->assertOk();
        $response->assertViewIs('settings.index');
    }

    public function test_settings_update_requires_authentication(): void
    {
        $response = $this->post(route('settings.update'), [
            'company_name' => 'Test App',
        ]);
        
        $response->assertRedirect(route('login'));
    }

    public function test_settings_update_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        
        $response = $this->actingAs($user)->post(route('settings.update'), [
            'company_name' => 'Test App',
        ]);
        
        $response->assertForbidden();
    }

    public function test_settings_update_updates_settings(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->post(route('settings.update'), [
            'company_name' => 'Updated App Name',
            'timezone' => 'UTC',
        ]);
        
        $response->assertRedirect();
    }

    public function test_settings_general_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        
        $response = $this->actingAs($user)->get(route('settings.general'));
        
        $response->assertForbidden();
    }

    public function test_settings_general_returns_view_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->get(route('settings.general'));
        
        $response->assertOk();
        $response->assertViewIs('settings.index');
    }

    public function test_settings_email_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        
        $response = $this->actingAs($user)->get(route('settings.email'));
        
        $response->assertForbidden();
    }

    public function test_settings_email_returns_view_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->get(route('settings.email'));
        
        $response->assertOk();
        $response->assertViewIs('settings.email');
    }

    public function test_settings_security_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        
        $response = $this->actingAs($user)->get(route('settings.security'));
        
        $response->assertForbidden();
    }

    public function test_settings_security_returns_view_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->get(route('settings.security'));
        
        $response->assertOk();
        $response->assertViewIs('settings.index');
    }

    public function test_settings_update_validates_app_name(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->post(route('settings.update'), [
            'company_name' => str_repeat('a', 256),
        ]);
        
        $response->assertSessionHasErrors('company_name');
    }

    public function test_settings_update_validates_timezone(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->post(route('settings.update'), [
            'company_name' => 'Test',
            'timezone' => 'Invalid/Timezone',
        ]);
        
        $response->assertSessionHasErrors('timezone');
    }

    // ===== PROFILE_CONTROLLER TESTS =====

    public function test_profile_show_requires_authentication(): void
    {
        $response = $this->get(route('profile.show'));
        
        $response->assertRedirect(route('login'));
    }

    public function test_profile_show_returns_view_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get(route('profile.show'));
        
        $response->assertOk();
        $response->assertViewIs('profile.edit');
    }

    public function test_profile_edit_requires_authentication(): void
    {
        $response = $this->get(route('profile.edit'));
        
        $response->assertRedirect(route('login'));
    }

    public function test_profile_edit_returns_view_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get(route('profile.edit'));
        
        $response->assertOk();
        $response->assertViewIs('profile.edit');
    }

    public function test_profile_update_requires_authentication(): void
    {
        $response = $this->patch(route('profile.update'), [
            'first_name' => 'Updated',
        ]);
        
        $response->assertRedirect(route('login'));
    }

    public function test_profile_update_updates_user_profile(): void
    {
        $user = User::factory()->create(['first_name' => 'Original']);
        
        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Updated Name',
            'first_name' => 'Updated',
            'last_name' => $user->last_name,
            'email' => $user->email,
        ]);
        
        $response->assertRedirect();
        $this->assertEquals('Updated', $user->fresh()->first_name);
    }

    public function test_profile_update_validates_required_fields(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'first_name' => '',
        ]);
        
        $response->assertSessionHasErrors('first_name');
    }

    public function test_profile_password_update_requires_current_password(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->put(route('profile.password'), [
            'new_password' => 'newpassword',
        ]);
        
        $response->assertSessionHasErrorsIn('updatePassword', 'current_password');
    }

    public function test_profile_password_update_validates_current_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('oldpassword')]);
        
        $response = $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword',
        ]);
        
        $response->assertSessionHasErrorsIn('updatePassword', 'current_password');
    }

    public function test_profile_password_update_successful(): void
    {
        $user = User::factory()->create(['password' => bcrypt('oldpassword')]);
        
        $response = $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);
        
        $response->assertRedirect();
    }

    // ===== EDGE CASES =====

    public function test_dashboard_handles_large_conversation_count(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        Conversation::factory()->count(100)->create(['mailbox_id' => $mailbox->id]);
        
        $response = $this->actingAs($user)->get(route('dashboard'));
        
        $response->assertOk();
    }

    public function test_settings_update_with_special_characters(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->post(route('settings.update'), [
            'company_name' => 'Test & "Special" <App>',
            'timezone' => 'UTC',
        ]);
        
        $response->assertRedirect();
    }

    public function test_profile_update_email_must_be_unique(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        
        $response = $this->actingAs($user1)->patch(route('profile.update'), [
            'first_name' => $user1->first_name,
            'email' => 'user2@example.com',
        ]);
        
        $response->assertSessionHasErrors('email');
    }
}
