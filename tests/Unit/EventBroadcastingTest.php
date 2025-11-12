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
use Illuminate\Support\Facades\Event;
use Tests\UnitTestCase;

class EventBroadcastingTest extends UnitTestCase
{

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
}
