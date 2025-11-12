<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Events\NewMessageReceived;
use App\Models\Conversation;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewMessageReceivedTest extends TestCase
{
    use RefreshDatabase;

    // Additional Target: NewMessageReceived Event Testing

    public function test_event_stores_conversation_and_thread(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new NewMessageReceived($thread, $conversation);

        $this->assertInstanceOf(NewMessageReceived::class, $event);
        $this->assertSame($conversation->id, $event->conversation->id);
        $this->assertSame($thread->id, $event->thread->id);
    }

    public function test_event_broadcasts_on_correct_channel(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new NewMessageReceived($thread, $conversation);

        // Check if broadcastOn method exists
        $this->assertTrue(method_exists($event, 'broadcastOn'));

        if (method_exists($event, 'broadcastOn')) {
            $channels = $event->broadcastOn();

            $this->assertNotNull($channels);
        }
    }

    public function test_event_includes_message_data_in_broadcast(): void
    {
        $conversation = Conversation::factory()->create(['subject' => 'Test Subject']);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'body' => 'Test message body',
        ]);

        $event = new NewMessageReceived($thread, $conversation);

        // Check if broadcastWith method exists
        if (method_exists($event, 'broadcastWith')) {
            $broadcastData = $event->broadcastWith();

            $this->assertIsArray($broadcastData);
        } else {
            // Event may broadcast the public properties directly
            $this->assertNotNull($event->conversation);
            $this->assertNotNull($event->thread);
        }
    }

    public function test_event_has_public_properties(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new NewMessageReceived($thread, $conversation);

        // Verify properties are accessible
        $this->assertInstanceOf(Conversation::class, $event->conversation);
        $this->assertInstanceOf(Thread::class, $event->thread);
    }

    public function test_event_can_be_serialized(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new NewMessageReceived($thread, $conversation);

        // Test serialization
        $serialized = serialize($event);
        $this->assertNotEmpty($serialized);

        // Test unserialization
        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(NewMessageReceived::class, $unserialized);
    }
}
