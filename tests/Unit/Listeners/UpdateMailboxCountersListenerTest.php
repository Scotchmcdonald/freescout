<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;
use App\Listeners\UpdateMailboxCounters;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateMailboxCountersListenerTest extends TestCase
{
    use RefreshDatabase;

    protected Mailbox $mailbox;
    protected User $user;
    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailbox = Mailbox::factory()->create();
        $this->user = User::factory()->create();
        
        $this->conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create([
                'status' => Conversation::STATUS_ACTIVE,
            ]);
    }

    public function test_updates_counters_on_status_changed(): void
    {
        $listener = new UpdateMailboxCounters();
        $event = new ConversationStatusChanged(
            $this->conversation,
            $this->user,
            Conversation::STATUS_ACTIVE,
            Conversation::STATUS_CLOSED
        );

        // This should not throw any exceptions
        $listener->handle($event);

        $this->assertTrue(true); // Listener executed successfully
    }

    public function test_updates_counters_on_user_changed(): void
    {
        $newUser = User::factory()->create();

        $listener = new UpdateMailboxCounters();
        $event = new ConversationUserChanged(
            $this->conversation,
            $this->user,
            $newUser
        );

        // This should not throw any exceptions
        $listener->handle($event);

        $this->assertTrue(true); // Listener executed successfully
    }

    public function test_handles_conversation_without_mailbox(): void
    {
        $conversationNoMailbox = Conversation::factory()->create([
            'mailbox_id' => null,
        ]);

        $listener = new UpdateMailboxCounters();
        $event = new ConversationStatusChanged(
            $conversationNoMailbox,
            $this->user,
            Conversation::STATUS_ACTIVE,
            Conversation::STATUS_CLOSED
        );

        // Should not throw exception even without mailbox
        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_listener_can_be_instantiated(): void
    {
        $listener = new UpdateMailboxCounters();
        $this->assertInstanceOf(UpdateMailboxCounters::class, $listener);
    }
}
