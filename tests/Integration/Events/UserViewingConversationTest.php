<?php

declare(strict_types=1);

namespace Tests\Integration\Events;

use App\Events\UserViewingConversation;
use App\Models\User;
use Illuminate\Broadcasting\PresenceChannel;
use Tests\IntegrationTestCase;

class UserViewingConversationTest extends IntegrationTestCase
{
    public function test_broadcast_as_returns_correct_event_name(): void
    {
        $user = User::factory()->create(['id' => 1]);
        $event = new UserViewingConversation(5, $user);

        $this->assertEquals('user.viewing', $event->broadcastAs());
    }

    public function test_broadcast_on_returns_presence_channel(): void
    {
        $user = User::factory()->create(['id' => 1]);
        $conversationId = 42;

        $event = new UserViewingConversation($conversationId, $user);

        $channels = $event->broadcastOn();

        $this->assertIsArray($channels);
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PresenceChannel::class, $channels[0]);
    }

    public function test_broadcast_on_targets_correct_conversation_channel(): void
    {
        $user = User::factory()->create(['id' => 1]);
        $conversationId = 42;

        $event = new UserViewingConversation($conversationId, $user);

        $channels = $event->broadcastOn();
        $channel = $channels[0];

        $this->assertStringContainsString('conversation.42', $channel->name);
    }

    public function test_broadcast_with_includes_conversation_id(): void
    {
        $user = User::factory()->create(['id' => 1]);
        $conversationId = 42;

        $event = new UserViewingConversation($conversationId, $user);

        $data = $event->broadcastWith();

        $this->assertArrayHasKey('conversation_id', $data);
        $this->assertEquals(42, $data['conversation_id']);
    }

    public function test_broadcast_with_includes_user_details(): void
    {
        $user = User::factory()->create([
            'id' => 10,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
        ]);

        $event = new UserViewingConversation(42, $user);

        $data = $event->broadcastWith();

        $this->assertArrayHasKey('user_id', $data);
        $this->assertEquals(10, $data['user_id']);

        $this->assertArrayHasKey('user_name', $data);
        $this->assertEquals('John Doe', $data['user_name']);

        $this->assertArrayHasKey('user_email', $data);
        $this->assertEquals('john.doe@example.com', $data['user_email']);
    }

    public function test_broadcast_with_includes_is_replying_flag(): void
    {
        $user = User::factory()->create(['id' => 1]);

        $viewingEvent = new UserViewingConversation(42, $user, false);
        $viewingData = $viewingEvent->broadcastWith();

        $this->assertArrayHasKey('is_replying', $viewingData);
        $this->assertFalse($viewingData['is_replying']);

        $replyingEvent = new UserViewingConversation(42, $user, true);
        $replyingData = $replyingEvent->broadcastWith();

        $this->assertTrue($replyingData['is_replying']);
    }

    public function test_broadcast_with_includes_timestamp(): void
    {
        $user = User::factory()->create(['id' => 1]);

        $event = new UserViewingConversation(42, $user);

        $data = $event->broadcastWith();

        $this->assertArrayHasKey('timestamp', $data);
        $this->assertNotNull($data['timestamp']);
        $this->assertIsString($data['timestamp']);
    }

    public function test_event_defaults_is_replying_to_false(): void
    {
        $user = User::factory()->create(['id' => 1]);

        $event = new UserViewingConversation(42, $user);

        $data = $event->broadcastWith();

        $this->assertFalse($data['is_replying']);
    }

    public function test_event_can_track_replying_state(): void
    {
        $user = User::factory()->create(['id' => 1]);

        $event = new UserViewingConversation(42, $user, true);

        $this->assertTrue($event->isReplying);
    }

    public function test_event_stores_conversation_id(): void
    {
        $user = User::factory()->create(['id' => 1]);
        $conversationId = 123;

        $event = new UserViewingConversation($conversationId, $user);

        $this->assertEquals(123, $event->conversationId);
    }

    public function test_event_stores_user_instance(): void
    {
        $user = User::factory()->create([
            'id' => 1,
            'first_name' => 'Jane',
        ]);

        $event = new UserViewingConversation(42, $user);

        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals('Jane', $event->user->first_name);
    }

    public function test_multiple_users_viewing_same_conversation(): void
    {
        $user1 = User::factory()->create(['id' => 1, 'first_name' => 'Alice']);
        $user2 = User::factory()->create(['id' => 2, 'first_name' => 'Bob']);

        $event1 = new UserViewingConversation(42, $user1);
        $event2 = new UserViewingConversation(42, $user2);

        $data1 = $event1->broadcastWith();
        $data2 = $event2->broadcastWith();

        // Both target same conversation
        $this->assertEquals(42, $data1['conversation_id']);
        $this->assertEquals(42, $data2['conversation_id']);

        // But different users
        $this->assertEquals(1, $data1['user_id']);
        $this->assertEquals(2, $data2['user_id']);
    }
}
