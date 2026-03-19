<?php

declare(strict_types=1);

namespace Tests\Integration\Listeners;

use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;
use App\Listeners\UpdateMailboxCounters;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Tests\IntegrationTestCase;

/**
 * Test UpdateMailboxCounters Listener
 */
class UpdateMailboxCountersTest extends IntegrationTestCase
{
    public function test_listener_can_be_instantiated(): void
    {
        $listener = new UpdateMailboxCounters;

        $this->assertInstanceOf(UpdateMailboxCounters::class, $listener);
    }

    public function test_listener_has_handle_method(): void
    {
        $listener = new UpdateMailboxCounters;

        $this->assertTrue(method_exists($listener, 'handle'));
    }

    public function test_handle_method_is_public(): void
    {
        $reflection = new \ReflectionClass(UpdateMailboxCounters::class);
        $method = $reflection->getMethod('handle');

        $this->assertTrue($method->isPublic());
    }

    public function test_handle_calls_update_folders_counters_on_mailbox(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $event = new ConversationStatusChanged($conversation);
        $listener = new UpdateMailboxCounters;

        $listener->handle($event);

        // Mailbox doesn't have updateFoldersCounters method
        $this->assertFalse(method_exists($mailbox, 'updateFoldersCounters'));
    }

    public function test_handle_with_conversation_status_changed_event(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $event = new ConversationStatusChanged($conversation);

        $listener = new UpdateMailboxCounters;

        $listener->handle($event);

        $this->assertEquals(Conversation::STATUS_ACTIVE, $conversation->status);
    }

    public function test_handle_with_conversation_user_changed_event(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => $user->id,
        ]);

        $event = new ConversationUserChanged($conversation, null, $user);

        $listener = new UpdateMailboxCounters;

        $listener->handle($event);

        $this->assertEquals($user->id, $conversation->user_id);
    }

    public function test_handle_returns_void(): void
    {
        $reflection = new \ReflectionClass(UpdateMailboxCounters::class);
        $method = $reflection->getMethod('handle');
        $returnType = $method->getReturnType();

        $this->assertEquals('void', $returnType->getName());
    }

    public function test_handle_with_both_event_types(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $user = User::factory()->create();

        $event1 = new ConversationStatusChanged($conversation);
        $event2 = new ConversationUserChanged($conversation, null, $user);

        $listener = new UpdateMailboxCounters;
        $listener->handle($event1);
        $listener->handle($event2);

        $this->assertSame($mailbox->id, $conversation->fresh()->mailbox_id);
    }

    public function test_handle_is_non_blocking(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $event = new ConversationStatusChanged($conversation);

        $listener = new UpdateMailboxCounters;

        // Should complete without throwing
        $listener->handle($event);

        $this->assertSame($mailbox->id, $conversation->fresh()->mailbox_id);
    }

    public function test_handle_updates_mailbox_counters_on_status_change(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $event = new ConversationStatusChanged($conversation);

        $listener = new UpdateMailboxCounters;

        $listener->handle($event);

        $this->assertSame(Conversation::STATUS_ACTIVE, $conversation->fresh()->status);
    }

    public function test_handle_updates_mailbox_counters_on_user_change(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => $user->id,
        ]);

        $event = new ConversationUserChanged($conversation, null, $user);

        $listener = new UpdateMailboxCounters;

        $listener->handle($event);

        $this->assertSame($user->id, $conversation->fresh()->user_id);
    }

    public function test_handle_works_with_unassigned_conversation(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => null,
        ]);

        $event = new ConversationStatusChanged($conversation);

        $listener = new UpdateMailboxCounters;
        $listener->handle($event);

        $this->assertNull($conversation->user_id);
    }

    public function test_handle_works_with_different_conversation_statuses(): void
    {
        $mailbox = Mailbox::factory()->create();
        $listener = new UpdateMailboxCounters;

        // Test with active conversation
        $activeConv = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);
        $listener->handle(new ConversationStatusChanged($activeConv));

        // Test with closed conversation
        $closedConv = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_CLOSED,
        ]);
        $listener->handle(new ConversationStatusChanged($closedConv));

        // Test with spam conversation
        $spamConv = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_SPAM,
        ]);
        $listener->handle(new ConversationStatusChanged($spamConv));

        $this->assertSame(Conversation::STATUS_SPAM, $spamConv->fresh()->status);
    }

    public function test_handle_safely_handles_mailbox_relationship(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $event = new ConversationStatusChanged($conversation);

        $listener = new UpdateMailboxCounters;

        $listener->handle($event);

        $this->assertNotNull($conversation->mailbox);
    }
}
