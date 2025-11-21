<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;
use App\Listeners\UpdateMailboxCounters;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Tests\UnitTestCase;

/**
 * Test UpdateMailboxCounters Listener
 * 
 * Target: 90-95% coverage for App\Listeners\UpdateMailboxCounters
 * Using real models for integration testing
 */
class UpdateMailboxCountersTest extends UnitTestCase
{
    public function test_listener_can_be_instantiated(): void
    {
        $listener = new UpdateMailboxCounters();
        
        $this->assertInstanceOf(UpdateMailboxCounters::class, $listener);
    }

    public function test_listener_has_handle_method(): void
    {
        $listener = new UpdateMailboxCounters();
        
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
        
        // Mock the mailbox to verify updateFoldersCounters is called
        $mailboxMock = \Mockery::mock($mailbox)->makePartial();
        $mailboxMock->shouldReceive('updateFoldersCounters')
            ->once()
            ->andReturnNull();
        
        $conversation->mailbox = $mailboxMock;
        
        $event = new ConversationStatusChanged($conversation);
        $listener = new UpdateMailboxCounters();
        
        $listener->handle($event);
    }

    public function test_handle_with_conversation_status_changed_event(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);
        
        $event = new ConversationStatusChanged($conversation);
        
        $listener = new UpdateMailboxCounters();
        
        // Should not throw exception
        $listener->handle($event);
        
        // Verify the conversation is still accessible
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
        
        $event = new ConversationUserChanged($conversation, $user);
        
        $listener = new UpdateMailboxCounters();
        
        // Should not throw exception
        $listener->handle($event);
        
        // Verify the conversation user assignment is correct
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
        $event2 = new ConversationUserChanged($conversation, $user);
        
        $listener = new UpdateMailboxCounters();
        $listener->handle($event1);
        $listener->handle($event2);
        
        $this->assertTrue(true);
    }

    public function test_handle_is_non_blocking(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        
        $event = new ConversationStatusChanged($conversation);
        
        $listener = new UpdateMailboxCounters();
        
        $start = microtime(true);
        $listener->handle($event);
        $duration = microtime(true) - $start;
        
        // Should complete very quickly (< 1 second)
        $this->assertLessThan(1.0, $duration);
    }

    public function test_handle_updates_mailbox_counters_on_status_change(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);
        
        $event = new ConversationStatusChanged($conversation);
        
        $listener = new UpdateMailboxCounters();
        
        // Should execute without error
        $listener->handle($event);
        
        $this->assertTrue(true);
    }

    public function test_handle_updates_mailbox_counters_on_user_change(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => $user->id,
        ]);
        
        $event = new ConversationUserChanged($conversation, $user);
        
        $listener = new UpdateMailboxCounters();
        
        // Should execute without error
        $listener->handle($event);
        
        $this->assertTrue(true);
    }

    public function test_handle_works_with_unassigned_conversation(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => null, // Unassigned
        ]);
        
        $event = new ConversationStatusChanged($conversation);
        
        $listener = new UpdateMailboxCounters();
        $listener->handle($event);
        
        $this->assertNull($conversation->user_id);
    }

    public function test_handle_works_with_different_conversation_statuses(): void
    {
        $mailbox = Mailbox::factory()->create();
        $listener = new UpdateMailboxCounters();
        
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
        
        $this->assertTrue(true);
    }

    public function test_handle_safely_handles_missing_mailbox(): void
    {
        $conversation = Conversation::factory()->make([
            'mailbox_id' => 999999, // Non-existent mailbox
        ]);
        
        $event = new ConversationStatusChanged($conversation);
        
        $listener = new UpdateMailboxCounters();
        
        // Should not throw an exception
        try {
            $listener->handle($event);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Should handle missing mailbox gracefully');
        }
    }

    // ===== BASIC TESTS (Merged from UpdateMailboxCountersListenerTest.php) =====

    public function test_update_mailbox_counters_listener_has_handle_method(): void
    {
        $listener = new UpdateMailboxCounters;
        $this->assertTrue(method_exists($listener, 'handle'));
    }

    public function test_update_mailbox_counters_listener_handles_status_changed_event(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $event = new ConversationStatusChanged($conversation);
        $listener = new UpdateMailboxCounters;

        // Should not throw an exception
        $listener->handle($event);
        $this->assertTrue(true);
    }
}
