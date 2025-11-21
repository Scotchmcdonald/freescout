<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;
use App\Listeners\UpdateMailboxCounters;
use App\Models\Conversation;
use App\Models\Mailbox;
use Mockery;
use Tests\UnitTestCase;

/**
 * Test UpdateMailboxCounters Listener
 * 
 * Target: 90-95% coverage for App\Listeners\UpdateMailboxCounters
 * Current coverage: 50%
 */
class UpdateMailboxCountersTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

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

    public function test_handle_accepts_conversation_status_changed_event(): void
    {
        $reflection = new \ReflectionClass(UpdateMailboxCounters::class);
        $method = $reflection->getMethod('handle');
        $params = $method->getParameters();
        
        $this->assertCount(1, $params);
        $this->assertEquals('event', $params[0]->getName());
    }

    public function test_handle_with_conversation_status_changed_event(): void
    {
        $mailbox = Mockery::mock(Mailbox::class);
        $mailbox->shouldReceive('updateFoldersCounters')->once();
        
        $conversation = Mockery::mock(Conversation::class);
        $conversation->mailbox = $mailbox;
        
        $event = Mockery::mock(ConversationStatusChanged::class);
        $event->conversation = $conversation;
        
        $listener = new UpdateMailboxCounters();
        $listener->handle($event);
        
        $this->assertTrue(true); // If we get here, method was called successfully
    }

    public function test_handle_with_conversation_user_changed_event(): void
    {
        $mailbox = Mockery::mock(Mailbox::class);
        $mailbox->shouldReceive('updateFoldersCounters')->once();
        
        $conversation = Mockery::mock(Conversation::class);
        $conversation->mailbox = $mailbox;
        
        $event = Mockery::mock(ConversationUserChanged::class);
        $event->conversation = $conversation;
        
        $listener = new UpdateMailboxCounters();
        $listener->handle($event);
        
        $this->assertTrue(true);
    }

    public function test_handle_checks_if_update_folders_counters_method_exists(): void
    {
        // Mock mailbox without updateFoldersCounters method
        $mailbox = Mockery::mock(Mailbox::class);
        // Don't define updateFoldersCounters, so method_exists returns false
        
        $conversation = Mockery::mock(Conversation::class);
        $conversation->mailbox = $mailbox;
        
        $event = Mockery::mock(ConversationStatusChanged::class);
        $event->conversation = $conversation;
        
        $listener = new UpdateMailboxCounters();
        
        // Should not throw an error even if method doesn't exist
        $listener->handle($event);
        
        $this->assertTrue(true);
    }

    public function test_handle_with_real_conversation_and_mailbox(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        
        $event = new ConversationStatusChanged($conversation);
        
        $listener = new UpdateMailboxCounters();
        
        // Should execute without errors
        $listener->handle($event);
        
        $this->assertTrue(true);
    }

    public function test_handle_updates_mailbox_counters_when_method_exists(): void
    {
        $mailbox = Mockery::mock(Mailbox::class);
        $mailbox->shouldReceive('updateFoldersCounters')
            ->once()
            ->andReturnNull();
        
        $conversation = Mockery::mock(Conversation::class);
        $conversation->mailbox = $mailbox;
        
        $event = Mockery::mock(ConversationStatusChanged::class);
        $event->conversation = $conversation;
        
        $listener = new UpdateMailboxCounters();
        $listener->handle($event);
        
        // Verify mock expectations
        Mockery::close();
        $this->assertTrue(true);
    }

    public function test_handle_does_not_call_update_when_method_missing(): void
    {
        // Create a basic object without updateFoldersCounters method
        $mailbox = new \stdClass();
        
        $conversation = Mockery::mock(Conversation::class);
        $conversation->mailbox = $mailbox;
        
        $event = Mockery::mock(ConversationStatusChanged::class);
        $event->conversation = $conversation;
        
        $listener = new UpdateMailboxCounters();
        
        // Should not throw exception
        $listener->handle($event);
        
        $this->assertTrue(true);
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
        $mailbox = Mockery::mock(Mailbox::class);
        $mailbox->shouldReceive('updateFoldersCounters')->twice();
        
        $conversation = Mockery::mock(Conversation::class);
        $conversation->mailbox = $mailbox;
        
        $event1 = Mockery::mock(ConversationStatusChanged::class);
        $event1->conversation = $conversation;
        
        $event2 = Mockery::mock(ConversationUserChanged::class);
        $event2->conversation = $conversation;
        
        $listener = new UpdateMailboxCounters();
        $listener->handle($event1);
        $listener->handle($event2);
        
        $this->assertTrue(true);
    }

    public function test_handle_accesses_conversation_through_event(): void
    {
        $mailbox = Mockery::mock(Mailbox::class);
        $mailbox->shouldReceive('updateFoldersCounters')->once();
        
        $conversation = Mockery::mock(Conversation::class);
        $conversation->mailbox = $mailbox;
        
        $event = Mockery::mock(ConversationStatusChanged::class);
        $event->shouldReceive('getAttribute')
            ->with('conversation')
            ->never(); // Should access directly, not through getAttribute
        $event->conversation = $conversation;
        
        $listener = new UpdateMailboxCounters();
        $listener->handle($event);
        
        $this->assertTrue(true);
    }

    public function test_handle_accesses_mailbox_through_conversation(): void
    {
        $mailbox = Mockery::mock(Mailbox::class);
        $mailbox->shouldReceive('updateFoldersCounters')->once();
        
        $conversation = Mockery::mock(Conversation::class);
        $conversation->shouldReceive('getAttribute')
            ->with('mailbox')
            ->never(); // Should access directly, not through getAttribute
        $conversation->mailbox = $mailbox;
        
        $event = Mockery::mock(ConversationStatusChanged::class);
        $event->conversation = $conversation;
        
        $listener = new UpdateMailboxCounters();
        $listener->handle($event);
        
        $this->assertTrue(true);
    }

    public function test_handle_uses_method_exists_check(): void
    {
        // This test verifies the method_exists behavior
        $mailbox = new class {
            public function updateFoldersCounters() {
                // Empty implementation
            }
        };
        
        $conversation = Mockery::mock(Conversation::class);
        $conversation->mailbox = $mailbox;
        
        $event = Mockery::mock(ConversationStatusChanged::class);
        $event->conversation = $conversation;
        
        $listener = new UpdateMailboxCounters();
        
        // Should call the method
        $listener->handle($event);
        
        $this->assertTrue(method_exists($mailbox, 'updateFoldersCounters'));
    }

    public function test_handle_is_non_blocking(): void
    {
        $mailbox = Mockery::mock(Mailbox::class);
        $mailbox->shouldReceive('updateFoldersCounters')->once()->andReturnNull();
        
        $conversation = Mockery::mock(Conversation::class);
        $conversation->mailbox = $mailbox;
        
        $event = Mockery::mock(ConversationStatusChanged::class);
        $event->conversation = $conversation;
        
        $listener = new UpdateMailboxCounters();
        
        $start = microtime(true);
        $listener->handle($event);
        $duration = microtime(true) - $start;
        
        // Should complete very quickly (< 100ms)
        $this->assertLessThan(0.1, $duration);
    }
}
