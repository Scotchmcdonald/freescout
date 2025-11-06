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
}
