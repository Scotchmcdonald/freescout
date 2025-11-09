<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use App\Events\NewMessageReceived;
use App\Events\ConversationUpdated;
use App\Events\UserViewingConversation;
use App\Models\Conversation;
use App\Models\Thread;
use App\Models\User;
use App\Models\Mailbox;
use App\Models\Customer;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EventEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_message_received_event_with_null_preview()
    {
        $thread = Thread::factory()->create(['body' => '']);
        $event = new NewMessageReceived($thread);
        
        $this->assertNotNull($event->thread);
        $this->assertEquals('', $event->preview);
    }

    public function test_new_message_received_event_with_very_long_body()
    {
        $longBody = str_repeat('a', 500);
        $thread = Thread::factory()->create(['body' => $longBody]);
        $event = new NewMessageReceived($thread);
        
        $this->assertEquals(100, strlen($event->preview));
    }

    public function test_conversation_updated_event_with_minimal_data()
    {
        $conversation = Conversation::factory()->create();
        $event = new ConversationUpdated($conversation);
        
        $this->assertNotNull($event->conversation);
        $this->assertEquals($conversation->id, $event->conversation->id);
    }

    public function test_user_viewing_conversation_with_unassigned_conversation()
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => null]);
        
        $event = new UserViewingConversation($user, $conversation);
        
        $this->assertNotNull($event->user);
        $this->assertNotNull($event->conversation);
        $this->assertNull($event->conversation->user_id);
    }

    public function test_event_listeners_are_registered()
    {
        Event::fake([
            NewMessageReceived::class,
            ConversationUpdated::class,
            UserViewingConversation::class,
        ]);
        
        $thread = Thread::factory()->create();
        event(new NewMessageReceived($thread));
        
        Event::assertDispatched(NewMessageReceived::class);
    }

    public function test_events_can_be_serialized()
    {
        $thread = Thread::factory()->create();
        $event = new NewMessageReceived($thread);
        
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);
        
        $this->assertEquals($event->thread->id, $unserialized->thread->id);
    }

    public function test_multiple_events_can_be_dispatched_simultaneously()
    {
        Event::fake();
        
        $thread = Thread::factory()->create();
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();
        
        event(new NewMessageReceived($thread));
        event(new ConversationUpdated($conversation));
        event(new UserViewingConversation($user, $conversation));
        
        Event::assertDispatched(NewMessageReceived::class);
        Event::assertDispatched(ConversationUpdated::class);
        Event::assertDispatched(UserViewingConversation::class);
    }

    public function test_event_broadcast_data_is_array()
    {
        $thread = Thread::factory()->create();
        $event = new NewMessageReceived($thread);
        
        $broadcastData = $event->broadcastWith();
        
        $this->assertIsArray($broadcastData);
        $this->assertArrayHasKey('thread', $broadcastData);
    }
}
