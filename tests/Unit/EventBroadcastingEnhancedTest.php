<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Events\ConversationUpdated;
use App\Events\NewMessageReceived;
use App\Events\UserViewingConversation;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Broadcasting\PresenceChannel;
use Tests\TestCase;

class EventBroadcastingEnhancedTest extends TestCase
{
    public function test_new_message_received_has_correct_properties(): void
    {
        $thread = new Thread(['id' => 789]);
        $conversation = new Conversation(['id' => 456, 'mailbox_id' => 123]);

        $event = new NewMessageReceived($thread, $conversation);

        $this->assertSame($thread, $event->thread);
        $this->assertSame($conversation, $event->conversation);
    }

    public function test_new_message_received_broadcast_as_returns_correct_name(): void
    {
        $thread = new Thread(['id' => 1]);
        $conversation = new Conversation(['id' => 2]);

        $event = new NewMessageReceived($thread, $conversation);

        $this->assertEquals('message.new', $event->broadcastAs());
    }

    public function test_new_message_received_broadcast_with_contains_thread_data(): void
    {
        $customer = new Customer;
        $customer->id = 100;
        $customer->first_name = 'John';
        $customer->last_name = 'Doe';

        $user = new User;
        $user->id = 200;
        $user->first_name = 'Jane';
        $user->last_name = 'Smith';

        $mailbox = new Mailbox;
        $mailbox->id = 300;
        $mailbox->name = 'Support';

        $thread = new Thread([
            'type' => 1, // Integer type
            'from' => 'test@example.com',
            'body' => '<p>Test message body with HTML</p>',
        ]);
        $thread->id = 1;
        $thread->setRelation('customer', $customer);
        $thread->setRelation('user', $user);

        $conversation = new Conversation([
            'number' => 12345,
            'subject' => 'Test Subject',
            'mailbox_id' => 300,
        ]);
        $conversation->id = 2;
        $conversation->setRelation('mailbox', $mailbox);

        $event = new NewMessageReceived($thread, $conversation);

        $data = $event->broadcastWith();

        $this->assertIsArray($data);
        $this->assertEquals(1, $data['thread_id']);
        $this->assertEquals(2, $data['conversation_id']);
        $this->assertEquals(12345, $data['conversation_number']);
        $this->assertEquals('Test Subject', $data['conversation_subject']);
        $this->assertEquals(1, $data['thread_type']);
        $this->assertEquals('test@example.com', $data['from']);
        $this->assertEquals('Test message body with HTML', $data['preview']);
        $this->assertEquals('John Doe', $data['customer_name']);
        $this->assertEquals('Jane Smith', $data['user_name']);
        $this->assertEquals(300, $data['mailbox_id']);
        $this->assertEquals('Support', $data['mailbox_name']);
    }

    public function test_new_message_received_broadcast_with_truncates_long_preview(): void
    {
        $thread = new Thread([
            'id' => 1,
            'body' => str_repeat('a', 200),
        ]);
        $conversation = new Conversation([
            'id' => 2,
            'number' => 1,
            'subject' => 'Test',
            'mailbox_id' => 1,
        ]);
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Test']);
        $conversation->setRelation('mailbox', $mailbox);

        $event = new NewMessageReceived($thread, $conversation);

        $data = $event->broadcastWith();

        $this->assertEquals(100, mb_strlen($data['preview']));
    }

    public function test_conversation_updated_broadcast_as_returns_correct_name(): void
    {
        $conversation = new Conversation(['id' => 1]);

        $event = new ConversationUpdated($conversation);

        $this->assertEquals('conversation.updated', $event->broadcastAs());
    }

    public function test_conversation_updated_broadcast_with_contains_conversation_data(): void
    {
        $conversation = new Conversation([
            'number' => 456,
            'subject' => 'Test Conversation',
            'status' => 1, // Integer status
        ]);
        $conversation->id = 123;

        $event = new ConversationUpdated($conversation, 'status_changed', ['old_status' => 0, 'new_status' => 1]);

        $data = $event->broadcastWith();

        $this->assertIsArray($data);
        $this->assertEquals(123, $data['id']);
        $this->assertEquals(456, $data['number']);
        $this->assertEquals('Test Conversation', $data['subject']);
        $this->assertEquals(1, $data['status']);
        $this->assertEquals('status_changed', $data['update_type']);
        $this->assertArrayHasKey('meta', $data);
        $this->assertEquals(['old_status' => 0, 'new_status' => 1], $data['meta']);
    }

    public function test_user_viewing_conversation_broadcast_as_returns_correct_name(): void
    {
        $user = new User(['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com']);

        $event = new UserViewingConversation(123, $user);

        $this->assertEquals('user.viewing', $event->broadcastAs());
    }

    public function test_user_viewing_conversation_broadcast_with_contains_user_data(): void
    {
        $user = new User;
        $user->id = 456;
        $user->first_name = 'Jane';
        $user->last_name = 'Smith';
        $user->email = 'jane@example.com';

        $event = new UserViewingConversation(789, $user);

        $data = $event->broadcastWith();

        $this->assertIsArray($data);
        $this->assertEquals(456, $data['user_id']);
        $this->assertEquals('jane@example.com', $data['user_email']);
        $this->assertEquals('Jane Smith', $data['user_name']);
        $this->assertEquals(789, $data['conversation_id']);
    }

    public function test_user_viewing_conversation_broadcasts_on_presence_channel(): void
    {
        $user = new User(['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com']);

        $event = new UserViewingConversation(456, $user);

        $channels = $event->broadcastOn();

        $this->assertIsArray($channels);
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PresenceChannel::class, $channels[0]);
        $this->assertEquals('presence-conversation.456', $channels[0]->name);
    }
}
