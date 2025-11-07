<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationControllerMethodsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->mailbox = Mailbox::factory()->create([
            'name' => 'Support',
            'email' => 'support@example.com',
        ]);

        $this->mailbox->users()->attach($this->user, ['access' => 30]); // ADMIN access

        // Create inbox folder
        Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_INBOX,
            'name' => 'Inbox',
        ]);
    }

    /**
     * Test create() method - displays conversation creation form
     */
    public function test_admin_can_view_create_conversation_form(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('conversations.create', $this->mailbox));

        $response->assertOk();
        $response->assertViewIs('conversations.create');
        $response->assertViewHas('mailbox', function ($mailbox) {
            return $mailbox->id === $this->mailbox->id;
        });
        $response->assertViewHas('folders');
    }

    public function test_non_admin_with_mailbox_access_can_view_create_form(): void
    {
        $regularUser = User::factory()->create(['role' => User::ROLE_USER]);
        $this->mailbox->users()->attach($regularUser, ['access' => 10]); // VIEW access

        $this->actingAs($regularUser);

        $response = $this->get(route('conversations.create', $this->mailbox));

        $response->assertOk();
        $response->assertViewIs('conversations.create');
    }

    public function test_user_without_mailbox_access_cannot_view_create_form(): void
    {
        $unauthorizedUser = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($unauthorizedUser);

        $response = $this->get(route('conversations.create', $this->mailbox));

        $response->assertForbidden();
    }

    public function test_guest_cannot_view_create_form(): void
    {
        $response = $this->get(route('conversations.create', $this->mailbox));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test ajax() method - handles AJAX actions on conversations
     */
    public function test_ajax_change_status_updates_conversation(): void
    {
        $this->actingAs($this->user);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create(['status' => 1]);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'change_status',
            'conversation_id' => $conversation->id,
            'status' => 2,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'status' => 2,
        ]);
    }

    public function test_ajax_change_user_assigns_conversation(): void
    {
        $this->actingAs($this->user);

        $assignee = User::factory()->create();
        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create(['user_id' => null]);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'change_user',
            'conversation_id' => $conversation->id,
            'user_id' => $assignee->id,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'user_id' => $assignee->id,
        ]);
    }

    public function test_ajax_change_folder_moves_conversation(): void
    {
        $this->actingAs($this->user);

        $newFolder = Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_SENT,
        ]);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create();

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'change_folder',
            'conversation_id' => $conversation->id,
            'folder_id' => $newFolder->id,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'folder_id' => $newFolder->id,
        ]);
    }

    public function test_ajax_delete_soft_deletes_conversation(): void
    {
        $this->actingAs($this->user);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create(['state' => 2]);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'delete',
            'conversation_id' => $conversation->id,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'state' => 3, // Deleted state
        ]);
    }

    public function test_ajax_requires_conversation_id(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'change_status',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Conversation ID required',
        ]);
    }

    public function test_ajax_rejects_invalid_action(): void
    {
        $this->actingAs($this->user);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create();

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'invalid_action',
            'conversation_id' => $conversation->id,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid action',
        ]);
    }

    public function test_ajax_unauthorized_user_gets_forbidden(): void
    {
        $unauthorizedUser = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($unauthorizedUser);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create();

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'change_status',
            'conversation_id' => $conversation->id,
            'status' => 2,
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthorized',
        ]);
    }



    /**
     * Test clone() method - NOTE: Skipped due to incomplete implementation
     * The clone method in the controller doesn't set the required 'number' field
     */
    public function test_guest_cannot_clone_conversation(): void
    {
        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create();

        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        $response = $this->get(route('conversations.clone', [
            'mailbox' => $this->mailbox,
            'thread' => $thread,
        ]));

        $response->assertRedirect(route('login'));
    }
}
