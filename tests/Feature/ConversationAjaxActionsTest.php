<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationAjaxActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Mailbox $mailbox;
    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->mailbox = Mailbox::factory()->create();
        $this->mailbox->users()->attach($this->user);

        $this->conversation = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
        ]);
    }

    /** @test */
    public function can_change_conversation_status_via_ajax(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->postJson(route('conversations.ajax'), [
                'action' => 'change_status',
                'conversation_id' => $this->conversation->id,
                'status' => Conversation::STATUS_CLOSED,
            ]);

        // Assert
        $response->assertOk();
        $response->assertJson(['success' => true]);
        
        $this->conversation->refresh();
        $this->assertEquals(Conversation::STATUS_CLOSED, $this->conversation->status);
    }

    /** @test */
    public function can_change_conversation_assignee_via_ajax(): void
    {
        // Arrange
        $newUser = User::factory()->create();
        $this->mailbox->users()->attach($newUser);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson(route('conversations.ajax'), [
                'action' => 'change_user',
                'conversation_id' => $this->conversation->id,
                'user_id' => $newUser->id,
            ]);

        // Assert
        $response->assertOk();
        $response->assertJson(['success' => true]);
        
        $this->conversation->refresh();
        $this->assertEquals($newUser->id, $this->conversation->user_id);
    }

    /** @test */
    public function can_change_conversation_folder_via_ajax(): void
    {
        // Arrange
        $newFolder = Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson(route('conversations.ajax'), [
                'action' => 'change_folder',
                'conversation_id' => $this->conversation->id,
                'folder_id' => $newFolder->id,
            ]);

        // Assert
        $response->assertOk();
        $response->assertJson(['success' => true]);
        
        $this->conversation->refresh();
        $this->assertEquals($newFolder->id, $this->conversation->folder_id);
    }

    /** @test */
    public function can_soft_delete_conversation_via_ajax(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->postJson(route('conversations.ajax'), [
                'action' => 'delete',
                'conversation_id' => $this->conversation->id,
            ]);

        // Assert
        $response->assertOk();
        $response->assertJson(['success' => true]);
        
        $this->conversation->refresh();
        $this->assertEquals(3, $this->conversation->state); // Deleted state
    }

    /** @test */
    public function ajax_action_requires_conversation_id(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->postJson(route('conversations.ajax'), [
                'action' => 'change_status',
                'status' => Conversation::STATUS_CLOSED,
            ]);

        // Assert
        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Conversation ID required',
        ]);
    }

    /** @test */
    public function ajax_action_returns_error_for_invalid_action(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->postJson(route('conversations.ajax'), [
                'action' => 'invalid_action',
                'conversation_id' => $this->conversation->id,
            ]);

        // Assert
        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid action',
        ]);
    }

    /** @test */
    public function cannot_perform_ajax_actions_on_unauthorized_conversation(): void
    {
        // Arrange
        $regularUser = User::factory()->create([
            'role' => User::ROLE_USER, // Regular user, not admin
        ]);
        
        $otherMailbox = Mailbox::factory()->create();
        $otherConversation = Conversation::factory()->create([
            'mailbox_id' => $otherMailbox->id,
        ]);

        // Act - regular user tries to access conversation in mailbox they don't have access to
        $response = $this->actingAs($regularUser)
            ->postJson(route('conversations.ajax'), [
                'action' => 'change_status',
                'conversation_id' => $otherConversation->id,
                'status' => Conversation::STATUS_CLOSED,
            ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthorized',
        ]);
    }
}
