<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationReplyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected Mailbox $mailbox;
    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->mailbox = Mailbox::factory()->create();
        $this->mailbox->users()->attach($this->user);

        Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $customer = Customer::factory()->create();

        $this->conversation = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $customer->id,
            'subject' => 'Test Conversation',
            'status' => Conversation::STATUS_ACTIVE,
            'state' => 2,
        ]);
    }

    /**
     * Test user can reply to conversation.
     */
    public function test_user_can_reply_to_conversation(): void
    {
        // Arrange
        $replyData = [
            'body' => 'This is a reply to the conversation',
            'status' => Conversation::STATUS_ACTIVE,
        ];

        // Act
        $response = $this->actingAs($this->user)->post(
            route('conversations.reply', $this->conversation),
            $replyData
        );

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('threads', [
            'conversation_id' => $this->conversation->id,
            'body' => 'This is a reply to the conversation',
            'type' => 1, // User message type
        ]);
    }

    /**
     * Test reply validates body is required.
     */
    public function test_reply_validates_body_is_required(): void
    {
        // Act
        $response = $this->actingAs($this->user)->post(
            route('conversations.reply', $this->conversation),
            ['body' => '']
        );

        // Assert
        $response->assertSessionHasErrors('body');
    }

    /**
     * Test reply with status change.
     */
    public function test_reply_can_change_conversation_status(): void
    {
        // Arrange
        $this->conversation->update(['status' => Conversation::STATUS_ACTIVE]);

        $replyData = [
            'body' => 'Closing this conversation',
            'status' => Conversation::STATUS_CLOSED,
        ];

        // Act
        $response = $this->actingAs($this->user)->post(
            route('conversations.reply', $this->conversation),
            $replyData
        );

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('conversations', [
            'id' => $this->conversation->id,
            'status' => Conversation::STATUS_CLOSED,
        ]);
    }

    /**
     * Test reply assigns conversation to user.
     */
    public function test_reply_assigns_conversation_to_replying_user(): void
    {
        // Arrange
        $this->conversation->update(['user_id' => null]);

        $replyData = [
            'body' => 'I am taking this conversation',
        ];

        // Act
        $response = $this->actingAs($this->user)->post(
            route('conversations.reply', $this->conversation),
            $replyData
        );

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('conversations', [
            'id' => $this->conversation->id,
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test unauthorized user cannot reply to conversation.
     */
    public function test_unauthorized_user_cannot_reply_to_conversation(): void
    {
        // Arrange
        $unauthorizedUser = User::factory()->create();

        $replyData = [
            'body' => 'Unauthorized reply',
        ];

        // Act
        $response = $this->actingAs($unauthorizedUser)->post(
            route('conversations.reply', $this->conversation),
            $replyData
        );

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseMissing('threads', [
            'body' => 'Unauthorized reply',
        ]);
    }

    /**
     * Test guest cannot reply to conversation.
     */
    public function test_guest_cannot_reply_to_conversation(): void
    {
        // Act
        $response = $this->post(
            route('conversations.reply', $this->conversation),
            ['body' => 'Guest reply']
        );

        // Assert
        $response->assertRedirect(route('login'));
    }

    /**
     * Test reply updates conversation last_reply_at timestamp.
     */
    public function test_reply_updates_conversation_timestamp(): void
    {
        // Arrange
        $originalTime = $this->conversation->last_reply_at;

        sleep(1); // Ensure time difference

        $replyData = [
            'body' => 'New reply',
        ];

        // Act
        $response = $this->actingAs($this->user)->post(
            route('conversations.reply', $this->conversation),
            $replyData
        );

        // Assert
        $response->assertRedirect();
        $this->conversation->refresh();
        $this->assertNotEquals($originalTime, $this->conversation->last_reply_at);
    }

    /**
     * Test conversation update changes status.
     */
    public function test_conversation_update_changes_status(): void
    {
        // Arrange
        $this->conversation->update(['status' => Conversation::STATUS_ACTIVE]);

        $updateData = [
            'status' => Conversation::STATUS_CLOSED,
        ];

        // Act
        $response = $this->actingAs($this->user)->patchJson(
            route('conversations.update', $this->conversation),
            $updateData
        );

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('conversations', [
            'id' => $this->conversation->id,
            'status' => Conversation::STATUS_CLOSED,
        ]);
    }

    /**
     * Test conversation update assigns user.
     */
    public function test_conversation_update_assigns_user(): void
    {
        // Arrange
        $this->conversation->update(['user_id' => null]);

        $updateData = [
            'user_id' => $this->user->id,
        ];

        // Act
        $response = $this->actingAs($this->user)->patchJson(
            route('conversations.update', $this->conversation),
            $updateData
        );

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('conversations', [
            'id' => $this->conversation->id,
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test unauthorized user cannot update conversation.
     */
    public function test_unauthorized_user_cannot_update_conversation(): void
    {
        // Arrange
        $unauthorizedUser = User::factory()->create();

        $updateData = [
            'status' => Conversation::STATUS_CLOSED,
        ];

        // Act
        $response = $this->actingAs($unauthorizedUser)->patch(
            route('conversations.update', $this->conversation),
            $updateData
        );

        // Assert
        $response->assertForbidden();
    }
}
