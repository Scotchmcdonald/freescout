<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use App\Services\ImapService;
use Tests\FeatureTestCase;

/**
 * Comprehensive tests for MailboxController
 * Following TESTING_GUIDE.md - using test_ prefix, FeatureTestCase base class
 */
class MailboxControllerComprehensiveTest extends FeatureTestCase
{
    // ===== INDEX TESTS =====

    public function test_index_requires_authentication(): void
    {
        $response = $this->get(route('mailboxes.index'));
        
        $response->assertRedirect(route('login'));
    }

    public function test_index_returns_view_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->get(route('mailboxes.index'));
        
        $response->assertOk();
        $response->assertViewIs('mailboxes.index');
        $response->assertViewHas('mailboxes');
    }

    public function test_index_shows_all_mailboxes_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Mailbox::factory()->count(3)->create();
        
        $response = $this->actingAs($admin)->get(route('mailboxes.index'));
        
        $mailboxes = $response->viewData('mailboxes');
        $this->assertCount(3, $mailboxes);
    }

    public function test_index_shows_only_assigned_mailboxes_for_user(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        Mailbox::factory()->create(); // Not assigned
        
        $mailbox1->users()->attach($user->id);
        $mailbox2->users()->attach($user->id);
        
        $response = $this->actingAs($user)->get(route('mailboxes.index'));
        
        $mailboxes = $response->viewData('mailboxes');
        $this->assertCount(2, $mailboxes);
    }

    // ===== SHOW TESTS =====

    public function test_show_requires_authentication(): void
    {
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->get(route('mailboxes.show', $mailbox));
        
        $response->assertRedirect(route('login'));
    }

    public function test_show_returns_view_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($admin)->get(route('mailboxes.show', $mailbox));
        
        $response->assertOk();
        $response->assertViewIs('mailboxes.show');
        $response->assertViewHas('mailbox');
    }

    public function test_show_returns_view_for_assigned_user(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $response = $this->actingAs($user)->get(route('mailboxes.show', $mailbox));
        
        $response->assertOk();
    }

    public function test_show_forbids_non_assigned_user(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($user)->get(route('mailboxes.show', $mailbox));
        
        $response->assertForbidden();
    }

    public function test_show_includes_conversations(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($admin)->get(route('mailboxes.show', $mailbox));
        
        $response->assertViewHas('conversations');
    }

    public function test_show_includes_folders(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($admin)->get(route('mailboxes.show', $mailbox));
        
        $response->assertViewHas('folders');
    }

    // ===== SETTINGS TESTS =====

    public function test_settings_requires_authentication(): void
    {
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->get(route('mailboxes.settings', $mailbox));
        
        $response->assertRedirect(route('login'));
    }

    public function test_settings_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($user)->get(route('mailboxes.settings', $mailbox));
        
        $response->assertForbidden();
    }

    public function test_settings_returns_view_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($admin)->get(route('mailboxes.settings', $mailbox));
        
        $response->assertOk();
        $response->assertViewIs('mailboxes.settings');
        $response->assertViewHas('mailbox');
    }

    // ===== CREATE TESTS =====

    public function test_create_requires_authentication(): void
    {
        $response = $this->get(route('mailboxes.create'));
        
        $response->assertRedirect(route('login'));
    }

    public function test_create_requires_authorization(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        
        $response = $this->actingAs($user)->get(route('mailboxes.create'));
        
        $response->assertForbidden();
    }

    public function test_create_returns_view_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->get(route('mailboxes.create'));
        
        $response->assertOk();
        $response->assertViewIs('mailboxes.create');
        $response->assertViewHas('users');
    }

    public function test_create_includes_non_admin_users(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->count(3)->create(['role' => User::ROLE_USER]);
        
        $response = $this->actingAs($admin)->get(route('mailboxes.create'));
        
        $users = $response->viewData('users');
        $this->assertCount(3, $users);
    }

    // ===== STORE TESTS =====

    public function test_store_requires_authentication(): void
    {
        $response = $this->post(route('mailboxes.store'), [
            'name' => 'Test Mailbox',
            'email' => 'test@example.com',
        ]);
        
        $response->assertRedirect(route('login'));
    }

    public function test_store_requires_authorization(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        
        $response = $this->actingAs($user)->post(route('mailboxes.store'), [
            'name' => 'Test Mailbox',
            'email' => 'test@example.com',
        ]);
        
        $response->assertForbidden();
    }

    public function test_store_creates_mailbox_with_valid_data(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->post(route('mailboxes.store'), [
            'name' => 'Test Mailbox',
            'email' => 'test@example.com',
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('mailboxes', [
            'name' => 'Test Mailbox',
            'email' => 'test@example.com',
        ]);
    }

    public function test_store_validates_name_required(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->post(route('mailboxes.store'), [
            'email' => 'test@example.com',
        ]);
        
        $response->assertSessionHasErrors('name');
    }

    public function test_store_validates_name_max_length(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->post(route('mailboxes.store'), [
            'name' => str_repeat('a', 256),
            'email' => 'test@example.com',
        ]);
        
        $response->assertSessionHasErrors('name');
    }

    public function test_store_validates_email_required(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->post(route('mailboxes.store'), [
            'name' => 'Test Mailbox',
        ]);
        
        $response->assertSessionHasErrors('email');
    }

    public function test_store_validates_email_format(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->post(route('mailboxes.store'), [
            'name' => 'Test Mailbox',
            'email' => 'invalid-email',
        ]);
        
        $response->assertSessionHasErrors('email');
    }

    public function test_store_validates_email_unique(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Mailbox::factory()->create(['email' => 'existing@example.com']);
        
        $response = $this->actingAs($admin)->post(route('mailboxes.store'), [
            'name' => 'Test Mailbox',
            'email' => 'existing@example.com',
        ]);
        
        $response->assertSessionHasErrors('email');
    }

    // ===== UPDATE TESTS =====

    public function test_update_requires_authentication(): void
    {
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->put(route('mailboxes.update', $mailbox), [
            'name' => 'Updated Name',
        ]);
        
        $response->assertRedirect(route('login'));
    }

    public function test_update_requires_authorization(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($user)->put(route('mailboxes.update', $mailbox), [
            'name' => 'Updated Name',
        ]);
        
        $response->assertForbidden();
    }

    public function test_update_updates_mailbox_with_valid_data(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create(['name' => 'Original Name']);
        
        $response = $this->actingAs($admin)->put(route('mailboxes.update', $mailbox), [
            'name' => 'Updated Name',
            'email' => $mailbox->email,
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('mailboxes', [
            'id' => $mailbox->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_update_validates_name_required(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($admin)->put(route('mailboxes.update', $mailbox), [
            'name' => '',
            'email' => $mailbox->email,
        ]);
        
        $response->assertSessionHasErrors('name');
    }

    // ===== DESTROY TESTS =====

    public function test_destroy_requires_authentication(): void
    {
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->delete(route('mailboxes.destroy', $mailbox));
        
        $response->assertRedirect(route('login'));
    }

    public function test_destroy_requires_authorization(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($user)->delete(route('mailboxes.destroy', $mailbox));
        
        $response->assertForbidden();
    }

    public function test_destroy_deletes_mailbox(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($admin)->delete(route('mailboxes.destroy', $mailbox));
        
        $response->assertRedirect();
        $this->assertDatabaseMissing('mailboxes', ['id' => $mailbox->id]);
    }

    // ===== FETCH EMAILS TESTS =====

    public function test_fetch_emails_requires_authentication(): void
    {
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->post(route('mailboxes.fetch-emails', $mailbox));
        
        $response->assertRedirect(route('login'));
    }

    public function test_fetch_emails_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($user)->post(route('mailboxes.fetch-emails', $mailbox));
        
        $response->assertForbidden();
    }

    // ===== CONNECTION INCOMING TESTS =====

    public function test_connection_incoming_requires_authentication(): void
    {
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->get(route('mailboxes.connection-incoming', $mailbox));
        
        $response->assertRedirect(route('login'));
    }

    public function test_connection_incoming_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($user)->get(route('mailboxes.connection-incoming', $mailbox));
        
        $response->assertForbidden();
    }

    public function test_connection_incoming_returns_view_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($admin)->get(route('mailboxes.connection-incoming', $mailbox));
        
        $response->assertOk();
        $response->assertViewIs('mailboxes.connection_incoming');
    }

    // ===== SAVE CONNECTION INCOMING TESTS =====

    public function test_save_connection_incoming_requires_authentication(): void
    {
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->post(route('mailboxes.save-connection-incoming', $mailbox), [
            'in_server' => 'imap.example.com',
        ]);
        
        $response->assertRedirect(route('login'));
    }

    public function test_save_connection_incoming_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($user)->post(route('mailboxes.save-connection-incoming', $mailbox), [
            'in_server' => 'imap.example.com',
        ]);
        
        $response->assertForbidden();
    }

    // ===== CONNECTION OUTGOING TESTS =====

    public function test_connection_outgoing_requires_authentication(): void
    {
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->get(route('mailboxes.connection-outgoing', $mailbox));
        
        $response->assertRedirect(route('login'));
    }

    public function test_connection_outgoing_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($user)->get(route('mailboxes.connection-outgoing', $mailbox));
        
        $response->assertForbidden();
    }

    public function test_connection_outgoing_returns_view_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($admin)->get(route('mailboxes.connection-outgoing', $mailbox));
        
        $response->assertOk();
        $response->assertViewIs('mailboxes.connection_outgoing');
    }

    // ===== PERMISSIONS TESTS =====

    public function test_permissions_requires_authentication(): void
    {
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->get(route('mailboxes.permissions', $mailbox));
        
        $response->assertRedirect(route('login'));
    }

    public function test_permissions_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($user)->get(route('mailboxes.permissions', $mailbox));
        
        $response->assertForbidden();
    }

    public function test_permissions_returns_view_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($admin)->get(route('mailboxes.permissions', $mailbox));
        
        $response->assertOk();
        $response->assertViewIs('mailboxes.permissions');
    }

    // ===== UPDATE PERMISSIONS TESTS =====

    public function test_update_permissions_requires_authentication(): void
    {
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->post(route('mailboxes.update-permissions', $mailbox), [
            'users' => [],
        ]);
        
        $response->assertRedirect(route('login'));
    }

    public function test_update_permissions_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($user)->post(route('mailboxes.update-permissions', $mailbox), [
            'users' => [],
        ]);
        
        $response->assertForbidden();
    }

    public function test_update_permissions_syncs_users(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $user1 = User::factory()->create(['role' => User::ROLE_USER]);
        $user2 = User::factory()->create(['role' => User::ROLE_USER]);
        
        $response = $this->actingAs($admin)->post(route('mailboxes.update-permissions', $mailbox), [
            'permissions' => [
                $user1->id => 20,
                $user2->id => 20,
            ],
        ]);
        
        $response->assertRedirect();
        $this->assertEquals(2, $mailbox->fresh()->users()->count());
    }

    // ===== AUTO REPLY TESTS =====

    public function test_auto_reply_requires_authentication(): void
    {
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->get(route('mailboxes.auto-reply', $mailbox));
        
        $response->assertRedirect(route('login'));
    }

    public function test_auto_reply_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($user)->get(route('mailboxes.auto-reply', $mailbox));
        
        $response->assertForbidden();
    }

    public function test_auto_reply_returns_view_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($admin)->get(route('mailboxes.auto-reply', $mailbox));
        
        $response->assertOk();
        $response->assertViewIs('mailboxes.auto_reply');
    }

    // ===== AJAX TESTS =====

    public function test_ajax_requires_authentication(): void
    {
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->post(route('mailboxes.ajax', $mailbox), [
            'action' => 'test',
        ]);
        
        $response->assertRedirect(route('login'));
    }

    public function test_ajax_returns_json_response(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($admin)
            ->postJson(route('mailboxes.ajax', $mailbox), [
                'action' => 'test',
            ]);
        
        $response->assertJson([]);
    }

    // ===== EDGE CASES =====

    public function test_show_with_null_mailbox_returns_404(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->get('/mailboxes/99999');
        
        $response->assertNotFound();
    }

    public function test_update_with_empty_name_fails_validation(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->actingAs($admin)->put(route('mailboxes.update', $mailbox), [
            'name' => '',
            'email' => $mailbox->email,
        ]);
        
        $response->assertSessionHasErrors('name');
    }

    public function test_store_with_special_characters_in_name(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->post(route('mailboxes.store'), [
            'name' => 'Test & <Special> "Characters"',
            'email' => 'test@example.com',
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('mailboxes', [
            'name' => 'Test &  "Characters"',
        ]);
    }

    public function test_index_with_no_mailboxes_returns_empty_collection(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->get(route('mailboxes.index'));
        
        $mailboxes = $response->viewData('mailboxes');
        $this->assertCount(0, $mailboxes);
    }

    public function test_user_cannot_see_other_users_mailboxes(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        Mailbox::factory()->count(3)->create(); // Other mailboxes
        
        $response = $this->actingAs($user)->get(route('mailboxes.index'));
        
        $mailboxes = $response->viewData('mailboxes');
        $this->assertCount(1, $mailboxes);
    }
}
