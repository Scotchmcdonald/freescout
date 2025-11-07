<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Events\ConversationUpdated;
use App\Events\CustomerCreatedConversation;
use App\Events\CustomerReplied;
use App\Events\NewMessageReceived;
use App\Events\UserViewingConversation;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use Tests\TestCase;

class EventsTest extends TestCase
{
    public function test_conversation_updated_event_has_conversation(): void
    {
        $conversation = new Conversation(['id' => 1]);
        $event = new ConversationUpdated($conversation);
        
        $this->assertSame($conversation, $event->conversation);
    }

    public function test_conversation_updated_broadcasts_on_correct_channel(): void
    {
        $conversation = new Conversation(['id' => 123, 'mailbox_id' => 456]);
        $event = new ConversationUpdated($conversation);
        
        $channels = $event->broadcastOn();
        $this->assertGreaterThanOrEqual(1, count($channels));
        $this->assertEquals('private-mailbox.456', $channels[0]->name);
    }

    public function test_customer_created_conversation_event_has_properties(): void
    {
        $conversation = new Conversation(['id' => 1]);
        $thread = new Thread(['id' => 2]);
        $customer = new Customer(['id' => 3]);
        
        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        
        $this->assertSame($conversation, $event->conversation);
        $this->assertSame($thread, $event->thread);
        $this->assertSame($customer, $event->customer);
    }

    public function test_customer_replied_event_has_thread(): void
    {
        $conversation = new Conversation(['id' => 1]);
        $thread = new Thread(['id' => 2]);
        $customer = new Customer(['id' => 3]);
        
        $event = new CustomerReplied($conversation, $thread, $customer);
        
        $this->assertSame($conversation, $event->conversation);
        $this->assertSame($thread, $event->thread);
        $this->assertSame($customer, $event->customer);
    }

    public function test_new_message_received_event_has_properties(): void
    {
        $thread = new Thread(['id' => 1]);
        $conversation = new Conversation(['id' => 456]);
        
        $event = new NewMessageReceived($thread, $conversation);
        
        $this->assertSame($thread, $event->thread);
        $this->assertSame($conversation, $event->conversation);
    }

    public function test_user_viewing_conversation_event_has_conversation_and_user_ids(): void
    {
        $user = new \App\Models\User(['id' => 456, 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'test@example.com']);
        $event = new UserViewingConversation(123, $user);
        
        $this->assertEquals(123, $event->conversationId);
        $this->assertSame($user, $event->user);
    }

    public function test_user_viewing_conversation_broadcasts_on_correct_channel(): void
    {
        $user = new \App\Models\User(['id' => 321, 'first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@example.com']);
        $event = new UserViewingConversation(789, $user);
        
        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertEquals('presence-conversation.789', $channels[0]->name);
    }

    /** Test ConversationUpdated broadcasts with correct data structure */
    public function test_conversation_updated_broadcasts_with_correct_data(): void
    {
        $conversation = new Conversation([
            'id' => 100,
            'number' => 50,
            'subject' => 'Test Subject',
            'status' => 1,
            'user_id' => 5,
            'customer_id' => 10,
            'mailbox_id' => 20,
        ]);
        
        $event = new ConversationUpdated($conversation, 'status_changed', ['old_status' => 2]);
        $broadcastData = $event->broadcastWith();
        
        $this->assertArrayHasKey('id', $broadcastData);
        $this->assertArrayHasKey('number', $broadcastData);
        $this->assertArrayHasKey('subject', $broadcastData);
        $this->assertArrayHasKey('status', $broadcastData);
        $this->assertArrayHasKey('update_type', $broadcastData);
        $this->assertArrayHasKey('user_id', $broadcastData);
        $this->assertArrayHasKey('customer_id', $broadcastData);
        $this->assertArrayHasKey('mailbox_id', $broadcastData);
        $this->assertArrayHasKey('meta', $broadcastData);
        $this->assertEquals('status_changed', $broadcastData['update_type']);
        $this->assertEquals(['old_status' => 2], $broadcastData['meta']);
    }

    /** Test ConversationUpdated broadcasts as correct event name */
    public function test_conversation_updated_broadcast_name(): void
    {
        $conversation = new Conversation(['id' => 1, 'mailbox_id' => 1]);
        $event = new ConversationUpdated($conversation);
        
        $this->assertEquals('conversation.updated', $event->broadcastAs());
    }

    /** Test ConversationUpdated broadcasts to user channel when assigned */
    public function test_conversation_updated_broadcasts_to_assigned_user(): void
    {
        $conversation = new Conversation([
            'id' => 1,
            'mailbox_id' => 5,
            'user_id' => 10,
        ]);
        
        $event = new ConversationUpdated($conversation);
        $channels = $event->broadcastOn();
        
        $this->assertCount(2, $channels);
        $this->assertEquals('private-mailbox.5', $channels[0]->name);
        $this->assertEquals('private-user.10', $channels[1]->name);
    }

    /** Test ConversationUpdated only broadcasts to mailbox when no user assigned */
    public function test_conversation_updated_broadcasts_only_to_mailbox_when_unassigned(): void
    {
        $conversation = new Conversation([
            'id' => 1,
            'mailbox_id' => 5,
            'user_id' => null,
        ]);
        
        $event = new ConversationUpdated($conversation);
        $channels = $event->broadcastOn();
        
        $this->assertCount(1, $channels);
        $this->assertEquals('private-mailbox.5', $channels[0]->name);
    }

    /** Test ConversationUpdated handles different update types */
    public function test_conversation_updated_handles_different_update_types(): void
    {
        $conversation = new Conversation(['id' => 1, 'mailbox_id' => 1]);
        
        $updateTypes = ['status_changed', 'assigned', 'new_thread', 'priority_changed'];
        
        foreach ($updateTypes as $type) {
            $event = new ConversationUpdated($conversation, $type);
            $broadcastData = $event->broadcastWith();
            
            $this->assertEquals($type, $broadcastData['update_type']);
        }
    }

    /** Test NewMessageReceived broadcast name */
    public function test_new_message_received_broadcast_name(): void
    {
        $thread = new Thread(['id' => 1]);
        $conversation = new Conversation(['id' => 1, 'mailbox_id' => 1]);
        
        $event = new NewMessageReceived($thread, $conversation);
        
        $this->assertEquals('message.new', $event->broadcastAs());
    }

    /** Test NewMessageReceived broadcasts with thread data */
    public function test_new_message_received_broadcasts_thread_data(): void
    {
        $mailbox = new Mailbox(['name' => 'Support Mailbox']);
        $mailbox->id = 5;
        
        $thread = new Thread([
            'type' => 1,
            'from' => 'test@example.com',
            'body' => 'This is a test message body',
        ]);
        $thread->id = 15;
        
        $conversation = new Conversation([
            'number' => 100,
            'subject' => 'Test Conversation',
            'mailbox_id' => 5,
        ]);
        $conversation->id = 20;
        $conversation->setRelation('mailbox', $mailbox);
        
        $event = new NewMessageReceived($thread, $conversation);
        $broadcastData = $event->broadcastWith();
        
        $this->assertEquals(15, $broadcastData['thread_id']);
        $this->assertEquals(20, $broadcastData['conversation_id']);
        $this->assertEquals(100, $broadcastData['conversation_number']);
        $this->assertEquals('Test Conversation', $broadcastData['conversation_subject']);
        $this->assertEquals(1, $broadcastData['thread_type']);
        $this->assertEquals('test@example.com', $broadcastData['from']);
        $this->assertEquals(5, $broadcastData['mailbox_id']);
        $this->assertEquals('Support Mailbox', $broadcastData['mailbox_name']);
    }

    /** Test NewMessageReceived creates preview from thread body */
    public function test_new_message_received_creates_preview(): void
    {
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support']);
        $longBody = str_repeat('Test message content ', 20); // Long message
        $thread = new Thread([
            'id' => 1,
            'body' => $longBody,
        ]);
        $conversation = new Conversation(['id' => 1, 'mailbox_id' => 1]);
        $conversation->setRelation('mailbox', $mailbox);
        
        $event = new NewMessageReceived($thread, $conversation);
        $broadcastData = $event->broadcastWith();
        
        $this->assertArrayHasKey('preview', $broadcastData);
        $this->assertLessThanOrEqual(100, mb_strlen($broadcastData['preview']));
    }

    /** Test UserViewingConversation broadcast name */
    public function test_user_viewing_conversation_broadcast_name(): void
    {
        $user = new \App\Models\User(['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com']);
        $event = new UserViewingConversation(10, $user);
        
        $this->assertEquals('user.viewing', $event->broadcastAs());
    }

    /** Test UserViewingConversation broadcasts with user data */
    public function test_user_viewing_conversation_broadcasts_user_data(): void
    {
        $user = new \App\Models\User([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);
        $user->id = 25; // Set id directly as it's not fillable
        
        $event = new UserViewingConversation(50, $user, true);
        $broadcastData = $event->broadcastWith();
        
        $this->assertEquals(50, $broadcastData['conversation_id']);
        $this->assertEquals(25, $broadcastData['user_id']);
        $this->assertEquals('John Doe', $broadcastData['user_name']);
        $this->assertEquals('john@example.com', $broadcastData['user_email']);
        $this->assertTrue($broadcastData['is_replying']);
        $this->assertArrayHasKey('timestamp', $broadcastData);
    }

    /** Test UserViewingConversation with isReplying false by default */
    public function test_user_viewing_conversation_is_replying_defaults_to_false(): void
    {
        $user = new \App\Models\User(['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com']);
        $event = new UserViewingConversation(10, $user);
        $broadcastData = $event->broadcastWith();
        
        $this->assertFalse($broadcastData['is_replying']);
    }

    /** Test UserViewingConversation with isReplying true */
    public function test_user_viewing_conversation_with_is_replying_true(): void
    {
        $user = new \App\Models\User(['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com']);
        $event = new UserViewingConversation(10, $user, true);
        
        $this->assertTrue($event->isReplying);
        
        $broadcastData = $event->broadcastWith();
        $this->assertTrue($broadcastData['is_replying']);
    }

    /** Test ConversationUpdated includes updated_at timestamp */
    public function test_conversation_updated_includes_timestamp(): void
    {
        $conversation = new Conversation([
            'id' => 1,
            'mailbox_id' => 1,
        ]);
        $conversation->updated_at = now(); // Set as Carbon instance
        
        $event = new ConversationUpdated($conversation);
        $broadcastData = $event->broadcastWith();
        
        $this->assertArrayHasKey('updated_at', $broadcastData);
        $this->assertNotNull($broadcastData['updated_at']);
        $this->assertIsString($broadcastData['updated_at']);
    }
}
