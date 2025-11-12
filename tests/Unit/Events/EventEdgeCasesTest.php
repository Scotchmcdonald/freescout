<?php

namespace Tests\Unit\Events;

use App\Events\ConversationUpdated;
use App\Events\NewMessageReceived;
use App\Events\UserViewingConversation;
use App\Models\Conversation;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\UnitTestCase;

class EventEdgeCasesTest extends UnitTestCase
{

    public function test_new_message_received_event_with_null_preview()
    {
        $thread = Thread::factory()->create(['body' => '']);
        $event = new NewMessageReceived($thread, $thread->conversation);

        $this->assertNotNull($event->thread);
        $this->assertEquals('', $event->broadcastWith()['preview']);
    }

    public function test_new_message_received_event_with_very_long_body()
    {
        $longBody = str_repeat('a', 500);
        $thread = Thread::factory()->create(['body' => $longBody]);
        $event = new NewMessageReceived($thread, $thread->conversation);

        $this->assertEquals(100, strlen($event->broadcastWith()['preview']));
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

        $event = new UserViewingConversation($conversation->id, $user);

        $this->assertNotNull($event->user);
        $this->assertEquals($conversation->id, $event->conversationId);
    }

    public function test_event_listeners_are_registered()
    {
        Event::fake([
            NewMessageReceived::class,
            ConversationUpdated::class,
            UserViewingConversation::class,
        ]);

        $thread = Thread::factory()->create();
        event(new NewMessageReceived($thread, $thread->conversation));

        Event::assertDispatched(NewMessageReceived::class);
    }

    public function test_events_can_be_serialized()
    {
        $thread = Thread::factory()->create();
        $event = new NewMessageReceived($thread, $thread->conversation);

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

        event(new NewMessageReceived($thread, $thread->conversation));
        event(new ConversationUpdated($conversation));
        event(new UserViewingConversation($conversation->id, $user));

        Event::assertDispatched(NewMessageReceived::class);
        Event::assertDispatched(ConversationUpdated::class);
        Event::assertDispatched(UserViewingConversation::class);
    }

    public function test_event_broadcast_data_is_array()
    {
        $thread = Thread::factory()->create();
        $event = new NewMessageReceived($thread, $thread->conversation);

        $broadcastData = $event->broadcastWith();

        $this->assertIsArray($broadcastData);
        $this->assertArrayHasKey('thread_id', $broadcastData);
    }
}
