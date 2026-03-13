<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Events\ConversationUpdated;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\UnitTestCase;

class ConversationUpdatedTest extends UnitTestCase
{
    public function test_broadcast_as_returns_correct_event_name(): void
    {
        $conversation = Conversation::factory()->create(['id' => 1]);
        $event = new ConversationUpdated($conversation);

        $this->assertEquals('conversation.updated', $event->broadcastAs());
    }

    public function test_broadcast_on_includes_mailbox_channel(): void
    {
        $mailbox = Mailbox::factory()->create(['id' => 5]);
        $conversation = Conversation::factory()->create([
            'id' => 1,
            'mailbox_id' => $mailbox->id,
        ]);
        $event = new ConversationUpdated($conversation);

        $channels = $event->broadcastOn();

        $this->assertIsArray($channels);
        $this->assertContainsOnlyInstancesOf(PrivateChannel::class, $channels);

        // Find mailbox channel
        $mailboxChannel = collect($channels)->first(function ($channel) {
            return str_contains($channel->name, 'mailbox.');
        });

        $this->assertNotNull($mailboxChannel);
        $this->assertStringContainsString('mailbox.5', $mailboxChannel->name);
    }

    public function test_broadcast_on_includes_user_channel_when_conversation_assigned(): void
    {
        $mailbox = Mailbox::factory()->create(['id' => 5]);
        $user = User::factory()->create(['id' => 10]);
        $conversation = Conversation::factory()->create([
            'id' => 1,
            'mailbox_id' => $mailbox->id,
            'user_id' => $user->id,
        ]);
        $event = new ConversationUpdated($conversation);

        $channels = $event->broadcastOn();

        $this->assertCount(2, $channels);

        // Find user channel
        $userChannel = collect($channels)->first(function ($channel) {
            return str_contains($channel->name, 'user.');
        });

        $this->assertNotNull($userChannel);
        $this->assertStringContainsString('user.10', $userChannel->name);
    }

    public function test_broadcast_on_excludes_user_channel_when_conversation_unassigned(): void
    {
        $mailbox = Mailbox::factory()->create(['id' => 5]);
        $conversation = Conversation::factory()->create([
            'id' => 1,
            'mailbox_id' => $mailbox->id,
            'user_id' => null,
        ]);
        $event = new ConversationUpdated($conversation);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);

        $userChannel = collect($channels)->first(function ($channel) {
            return str_contains($channel->name, 'user.');
        });

        $this->assertNull($userChannel);
    }

    public function test_broadcast_with_includes_conversation_data(): void
    {
        $mailbox = Mailbox::factory()->create(['id' => 3]);
        $user = User::factory()->create(['id' => 5]);
        // Customer factory usually creates a customer, but we need specific ID 10.
        // Assuming Customer model exists and has factory.
        $customer = \App\Models\Customer::factory()->create(['id' => 10]);

        $conversation = Conversation::factory()->create([
            'id' => 1,
            'number' => 123,
            'subject' => 'Test Subject',
            'status' => 1,
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'mailbox_id' => $mailbox->id,
        ]);
        $event = new ConversationUpdated($conversation, 'status_changed', ['old_status' => 2]);

        $data = $event->broadcastWith();

        $this->assertIsArray($data);
        $this->assertEquals(1, $data['id']);
        $this->assertEquals(123, $data['number']);
        $this->assertEquals('Test Subject', $data['subject']);
        $this->assertEquals(1, $data['status']);
        $this->assertEquals('status_changed', $data['update_type']);
        $this->assertEquals(5, $data['user_id']);
        $this->assertEquals(10, $data['customer_id']);
        $this->assertEquals(3, $data['mailbox_id']);
        $this->assertEquals(['old_status' => 2], $data['meta']);
    }

    public function test_broadcast_with_includes_updated_at_timestamp(): void
    {
        $conversation = Conversation::factory()->create([
            'id' => 1,
            'updated_at' => now(),
        ]);
        $event = new ConversationUpdated($conversation);

        $data = $event->broadcastWith();

        $this->assertArrayHasKey('updated_at', $data);
        $this->assertNotNull($data['updated_at']);
        $this->assertIsString($data['updated_at']);
    }

    public function test_event_accepts_different_update_types(): void
    {
        $conversation = Conversation::factory()->create(['id' => 1]);

        $statusEvent = new ConversationUpdated($conversation, 'status_changed');
        $this->assertEquals('status_changed', $statusEvent->broadcastWith()['update_type']);

        $assignedEvent = new ConversationUpdated($conversation, 'assigned');
        $this->assertEquals('assigned', $assignedEvent->broadcastWith()['update_type']);

        $threadEvent = new ConversationUpdated($conversation, 'new_thread');
        $this->assertEquals('new_thread', $threadEvent->broadcastWith()['update_type']);
    }

    public function test_event_accepts_optional_meta_data(): void
    {
        $conversation = Conversation::factory()->create(['id' => 1]);
        $meta = ['custom_field' => 'custom_value', 'count' => 5];

        $event = new ConversationUpdated($conversation, 'custom_update', $meta);

        $data = $event->broadcastWith();
        $this->assertEquals($meta, $data['meta']);
    }

    public function test_event_has_default_update_type(): void
    {
        $conversation = Conversation::factory()->create(['id' => 1]);

        $event = new ConversationUpdated($conversation);

        $data = $event->broadcastWith();
        $this->assertEquals('status_changed', $data['update_type']);
    }

    public function test_event_meta_can_be_null(): void
    {
        $conversation = Conversation::factory()->create(['id' => 1]);

        $event = new ConversationUpdated($conversation, 'status_changed', null);

        $data = $event->broadcastWith();
        $this->assertNull($data['meta']);
    }
}
