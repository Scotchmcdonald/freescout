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

class ConversationTest extends TestCase
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

        $this->mailbox->users()->attach($this->user);

        // Create folders
        Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_INBOX,
            'name' => 'Inbox',
        ]);
    }

    public function test_user_can_view_conversations_list(): void
    {
        $this->actingAs($this->user);

        // Create multiple conversations
        $conv1 = Conversation::factory()
            ->for($this->mailbox)
            ->create([
                'subject' => 'First Support Request',
                'state' => 2,
            ]);
        $conv2 = Conversation::factory()
            ->for($this->mailbox)
            ->create([
                'subject' => 'Second Support Request',
                'state' => 2,
            ]);

        $response = $this->get(route('conversations.index', $this->mailbox));

        $response->assertOk();
        $response->assertViewIs('conversations.index');
        $response->assertSee($this->mailbox->name);
        $response->assertSee('First Support Request');
        $response->assertSee('Second Support Request');
        $response->assertViewHas('conversations', function ($conversations) use ($conv1, $conv2) {
            return $conversations->contains($conv1) && $conversations->contains($conv2);
        });
    }

    public function test_user_can_create_conversation(): void
    {
        $this->actingAs($this->user);

        $customer = Customer::factory()->create();
        $customerEmail = \App\Models\Email::factory()->create([
            'customer_id' => $customer->id,
            'type' => 1, // Primary
        ]);

        $response = $this->post(route('conversations.store', $this->mailbox), [
            'customer_id' => $customer->id,
            'subject' => 'Test Conversation',
            'body' => 'This is a test message',
            'to' => [$customerEmail->email],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('conversations', [
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $customer->id,
            'subject' => 'Test Conversation',
        ]);
    }

    public function test_user_can_view_conversation(): void
    {
        $this->actingAs($this->user);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create([
                'subject' => 'Important Issue',
            ]);

        // Create threads for the conversation
        $thread1 = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'body' => 'Initial customer message',
            'state' => 2,
        ]);
        $thread2 = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'body' => 'Agent response',
            'state' => 2,
        ]);

        $response = $this->get(route('conversations.show', $conversation));

        $response->assertOk();
        $response->assertViewIs('conversations.show');
        $response->assertSee($conversation->subject);
        $response->assertSee('Initial customer message');
        $response->assertSee('Agent response');
        $response->assertViewHas('conversation', function ($c) use ($conversation) {
            return $c->id === $conversation->id;
        });
    }

    public function test_user_can_reply_to_conversation(): void
    {
        $this->actingAs($this->user);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create();

        $response = $this->post(route('conversations.reply', $conversation), [
            'body' => 'This is a reply',
            'to' => [$conversation->customer->email],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('threads', [
            'conversation_id' => $conversation->id,
            'body' => 'This is a reply',
            'created_by_user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_update_conversation_status(): void
    {
        $this->actingAs($this->user);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create(['status' => Conversation::STATUS_ACTIVE]);

        $response = $this->patch(route('conversations.update', $conversation), [
            'status' => Conversation::STATUS_CLOSED,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'status' => Conversation::STATUS_CLOSED,
        ]);
    }

    public function test_user_can_assign_conversation(): void
    {
        $this->actingAs($this->user);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create();

        $assignee = User::factory()->create();
        $this->mailbox->users()->attach($assignee);

        $response = $this->patch(route('conversations.update', $conversation), [
            'user_id' => $assignee->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'user_id' => $assignee->id,
        ]);
    }

    public function test_user_cannot_view_conversation_in_unauthorized_mailbox(): void
    {
        $this->actingAs($this->user);

        $otherMailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()
            ->for($otherMailbox)
            ->create();

        $response = $this->get(route('conversations.show', $conversation));

        $response->assertForbidden();
    }

    public function test_conversation_increments_thread_count(): void
    {
        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create(['threads_count' => 0]);

        Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        $conversation->refresh();

        // Note: This test assumes you have an observer or event to increment threads_count
        // If not implemented yet, this test will fail and serve as a TODO
        $this->assertEquals(1, $conversation->threads_count);
    }

    public function test_closed_conversation_shows_correct_badge(): void
    {
        $this->actingAs($this->user);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create(['status' => Conversation::STATUS_CLOSED]);

        $response = $this->get(route('conversations.show', $conversation));

        $response->assertSee('Closed');
    }
}
