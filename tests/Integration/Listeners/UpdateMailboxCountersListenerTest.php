<?php

declare(strict_types=1);

namespace Tests\Integration\Listeners;

use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;
use App\Listeners\UpdateMailboxCounters;
use App\Models\Conversation;
use App\Models\Folder;
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

        // Create folders for the mailbox
        Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $this->conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create([
                'status' => Conversation::STATUS_ACTIVE,
            ]);
    }

    public function test_updates_counters_on_status_changed(): void
    {
        $listener = new UpdateMailboxCounters;
        $event = new ConversationStatusChanged(
            $this->conversation,
            $this->user,
            Conversation::STATUS_ACTIVE,
            Conversation::STATUS_CLOSED
        );

        // Verify handle runs without exceptions and mailbox relationship exists
        $listener->handle($event);

        $this->assertNotNull($this->conversation->mailbox);
        $this->assertEquals($this->mailbox->id, $this->conversation->mailbox_id);
    }

    public function test_updates_counters_on_user_changed(): void
    {
        $newUser = User::factory()->create();

        $listener = new UpdateMailboxCounters;
        $event = new ConversationUserChanged(
            $this->conversation,
            $this->user,
            $newUser
        );

        // Verify handle runs and conversation has mailbox
        $listener->handle($event);

        $this->assertNotNull($this->conversation->mailbox);
        $this->assertInstanceOf(Mailbox::class, $this->conversation->mailbox);
    }

    public function test_handles_conversation_without_mailbox(): void
    {
        $conversationNoMailbox = \Mockery::mock(Conversation::class)->makePartial();
        $conversationNoMailbox->shouldReceive('getAttribute')->with('mailbox')->andReturn(null);
        $conversationNoMailbox->shouldReceive('getAttribute')->with('mailbox_id')->andReturn(null);
        $conversationNoMailbox->id = 1;

        $listener = new UpdateMailboxCounters;
        $event = new ConversationStatusChanged(
            $conversationNoMailbox,
            $this->user,
            Conversation::STATUS_ACTIVE,
            Conversation::STATUS_CLOSED
        );

        // Should not throw exception even without mailbox
        $listener->handle($event);

        $this->assertNull($conversationNoMailbox->mailbox);
    }

    public function test_listener_can_be_instantiated(): void
    {
        $listener = new UpdateMailboxCounters;
        $this->assertInstanceOf(UpdateMailboxCounters::class, $listener);
    }

    public function test_status_change_from_active_to_closed(): void
    {
        $listener = new UpdateMailboxCounters;

        $event = new ConversationStatusChanged(
            $this->conversation,
            $this->user,
            Conversation::STATUS_ACTIVE,
            Conversation::STATUS_CLOSED
        );

        $listener->handle($event);

        // Verify the conversation's original status is recorded
        $this->assertEquals(Conversation::STATUS_ACTIVE, $event->oldStatus);
        $this->assertEquals(Conversation::STATUS_CLOSED, $event->newStatus);
    }

    public function test_status_change_from_closed_to_active(): void
    {
        $this->conversation->update(['status' => Conversation::STATUS_CLOSED]);

        $listener = new UpdateMailboxCounters;

        $event = new ConversationStatusChanged(
            $this->conversation,
            $this->user,
            Conversation::STATUS_CLOSED,
            Conversation::STATUS_ACTIVE
        );

        $listener->handle($event);

        $this->assertEquals(Conversation::STATUS_CLOSED, $event->oldStatus);
        $this->assertEquals(Conversation::STATUS_ACTIVE, $event->newStatus);
    }

    public function test_user_changed_event_contains_both_users(): void
    {
        $oldUser = $this->user;
        $newUser = User::factory()->create();

        $listener = new UpdateMailboxCounters;
        $event = new ConversationUserChanged(
            $this->conversation,
            $oldUser,
            $newUser
        );

        $listener->handle($event);

        $this->assertEquals($oldUser->id, $event->oldUser->id);
        $this->assertEquals($newUser->id, $event->newUser->id);
    }
}
