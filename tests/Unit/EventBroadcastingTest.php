<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Events\CustomerCreatedConversation;
use App\Events\CustomerReplied;
use App\Events\NewMessageReceived;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EventBroadcastingTest extends TestCase
{
    use RefreshDatabase;

    /** Test CustomerCreatedConversation event can be dispatched */
    public function test_customer_created_conversation_event_dispatched(): void
    {
        Event::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->for($mailbox)->for($customer)->create();
        $thread = Thread::factory()->for($conversation)->create();

        event(new CustomerCreatedConversation($conversation, $thread, $customer));

        Event::assertDispatched(CustomerCreatedConversation::class);
    }

    /** Test CustomerReplied event can be dispatched */
    public function test_customer_replied_event_dispatched(): void
    {
        Event::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->for($mailbox)->for($customer)->create();
        $thread = Thread::factory()->for($conversation)->create();

        event(new CustomerReplied($conversation, $thread, $customer));

        Event::assertDispatched(CustomerReplied::class);
    }

    /** Test NewMessageReceived event can be dispatched */
    public function test_new_message_received_event_dispatched(): void
    {
        Event::fake();

        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->for($conversation)->create();

        event(new NewMessageReceived($thread, $conversation));

        Event::assertDispatched(NewMessageReceived::class);
    }

    /** Test event contains correct data */
    public function test_event_contains_correct_conversation_data(): void
    {
        Event::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->for($mailbox)->for($customer)->create();
        $thread = Thread::factory()->for($conversation)->create();

        event(new CustomerCreatedConversation($conversation, $thread, $customer));

        Event::assertDispatched(function (CustomerCreatedConversation $event) use ($conversation, $thread, $customer) {
            return $event->conversation->id === $conversation->id
                && $event->thread->id === $thread->id
                && $event->customer->id === $customer->id;
        });
    }

    /** Test event contains correct thread data */
    public function test_event_contains_correct_thread_data(): void
    {
        Event::fake();

        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->for($conversation)->create();

        event(new NewMessageReceived($thread, $conversation));

        Event::assertDispatched(function (NewMessageReceived $event) use ($thread) {
            return $event->thread->id === $thread->id;
        });
    }

    /** Test multiple events can be dispatched */
    public function test_multiple_events_can_be_dispatched(): void
    {
        Event::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->for($mailbox)->for($customer)->create();
        $thread1 = Thread::factory()->for($conversation)->create();
        $thread2 = Thread::factory()->for($conversation)->create();

        event(new CustomerCreatedConversation($conversation, $thread1, $customer));
        event(new CustomerReplied($conversation, $thread2, $customer));

        Event::assertDispatched(CustomerCreatedConversation::class);
        Event::assertDispatched(CustomerReplied::class);
        Event::assertDispatchedTimes(CustomerCreatedConversation::class, 1);
        Event::assertDispatchedTimes(CustomerReplied::class, 1);
    }

    /** Test event is not dispatched when not triggered */
    public function test_event_not_dispatched_when_not_triggered(): void
    {
        Event::fake();

        // Create models but don't dispatch event
        $thread = Thread::factory()->create();

        Event::assertNotDispatched(NewMessageReceived::class);
    }

    /** Test event listeners are registered */
    public function test_event_listeners_are_registered(): void
    {
        $listeners = Event::getListeners(NewMessageReceived::class);

        $this->assertNotEmpty($listeners, 'NewMessageReceived event should have listeners');
    }

    /** Test CustomerCreatedConversation event properties are accessible */
    public function test_customer_created_conversation_event_properties(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->for($mailbox)->for($customer)->create();
        $thread = Thread::factory()->for($conversation)->create();

        $event = new CustomerCreatedConversation($conversation, $thread, $customer);

        $this->assertInstanceOf(Conversation::class, $event->conversation);
        $this->assertInstanceOf(Thread::class, $event->thread);
        $this->assertInstanceOf(Customer::class, $event->customer);
    }

    /** Test CustomerReplied event properties are accessible */
    public function test_customer_replied_event_properties(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->for($mailbox)->for($customer)->create();
        $thread = Thread::factory()->for($conversation)->create();

        $event = new CustomerReplied($conversation, $thread, $customer);

        $this->assertInstanceOf(Conversation::class, $event->conversation);
        $this->assertInstanceOf(Thread::class, $event->thread);
        $this->assertInstanceOf(Customer::class, $event->customer);
    }

    /** Test NewMessageReceived event properties are accessible */
    public function test_new_message_received_event_properties(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->for($conversation)->create();

        $event = new NewMessageReceived($thread, $conversation);

        $this->assertInstanceOf(Thread::class, $event->thread);
    }

    /** Test events can be serialized for queue */
    public function test_events_can_be_serialized(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->for($conversation)->create();
        $event = new NewMessageReceived($thread, $conversation);

        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(NewMessageReceived::class, $unserialized);
        $this->assertEquals($thread->id, $unserialized->thread->id);
    }

    /** Test ConversationUpdated event can be dispatched */
    public function test_conversation_updated_event_dispatched(): void
    {
        Event::fake();

        $conversation = Conversation::factory()->create();

        event(new \App\Events\ConversationUpdated($conversation));

        Event::assertDispatched(\App\Events\ConversationUpdated::class);
    }

    /** Test ConversationUpdated event contains correct data */
    public function test_conversation_updated_event_contains_correct_data(): void
    {
        Event::fake();

        $conversation = Conversation::factory()->create();

        event(new \App\Events\ConversationUpdated($conversation, 'status_changed'));

        Event::assertDispatched(function (\App\Events\ConversationUpdated $event) use ($conversation) {
            return $event->conversation->id === $conversation->id
                && $event->updateType === 'status_changed';
        });
    }

    /** Test UserViewingConversation event can be dispatched */
    public function test_user_viewing_conversation_event_dispatched(): void
    {
        Event::fake();

        $user = \App\Models\User::factory()->create();
        $conversation = Conversation::factory()->create();

        event(new \App\Events\UserViewingConversation($conversation->id, $user));

        Event::assertDispatched(\App\Events\UserViewingConversation::class);
    }

    /** Test UserViewingConversation event contains user */
    public function test_user_viewing_conversation_contains_user(): void
    {
        Event::fake();

        $user = \App\Models\User::factory()->create();

        event(new \App\Events\UserViewingConversation(123, $user, true));

        Event::assertDispatched(function (\App\Events\UserViewingConversation $event) use ($user) {
            return $event->user->id === $user->id
                && $event->conversationId === 123
                && $event->isReplying === true;
        });
    }

    /** Test events implement ShouldBroadcast interface */
    public function test_events_implement_should_broadcast(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->for($conversation)->create();

        $newMessageEvent = new NewMessageReceived($thread, $conversation);
        $conversationUpdatedEvent = new \App\Events\ConversationUpdated($conversation);
        $user = \App\Models\User::factory()->create();
        $userViewingEvent = new \App\Events\UserViewingConversation($conversation->id, $user);

        $this->assertInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcast::class, $newMessageEvent);
        $this->assertInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcast::class, $conversationUpdatedEvent);
        $this->assertInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcast::class, $userViewingEvent);
    }

    /** Test events have broadcast channels */
    public function test_events_have_broadcast_channels(): void
    {
        $conversation = Conversation::factory()->create();
        $conversationUpdatedEvent = new \App\Events\ConversationUpdated($conversation);
        $user = \App\Models\User::factory()->create();
        $userViewingEvent = new \App\Events\UserViewingConversation($conversation->id, $user);

        $this->assertNotEmpty($conversationUpdatedEvent->broadcastOn());
        $this->assertNotEmpty($userViewingEvent->broadcastOn());
        $this->assertIsArray($conversationUpdatedEvent->broadcastOn());
        $this->assertIsArray($userViewingEvent->broadcastOn());
    }

    /** Test ConversationUpdated can be serialized */
    public function test_conversation_updated_can_be_serialized(): void
    {
        $conversation = Conversation::factory()->create();
        $event = new \App\Events\ConversationUpdated($conversation, 'assigned', ['user_id' => 10]);

        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(\App\Events\ConversationUpdated::class, $unserialized);
        $this->assertEquals($conversation->id, $unserialized->conversation->id);
        $this->assertEquals('assigned', $unserialized->updateType);
        $this->assertEquals(['user_id' => 10], $unserialized->meta);
    }

    /** Test UserViewingConversation can be serialized */
    public function test_user_viewing_conversation_can_be_serialized(): void
    {
        $user = \App\Models\User::factory()->create();
        $event = new \App\Events\UserViewingConversation(456, $user, true);

        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(\App\Events\UserViewingConversation::class, $unserialized);
        $this->assertEquals(456, $unserialized->conversationId);
        $this->assertEquals($user->id, $unserialized->user->id);
        $this->assertTrue($unserialized->isReplying);
    }
}
