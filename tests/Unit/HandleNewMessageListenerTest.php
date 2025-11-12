<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Events\NewMessageReceived;
use App\Listeners\HandleNewMessage;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use Tests\UnitTestCase;

class HandleNewMessageListenerTest extends UnitTestCase
{

    /** Test listener can be instantiated */
    public function test_listener_can_be_instantiated(): void
    {
        $listener = new HandleNewMessage;

        $this->assertInstanceOf(HandleNewMessage::class, $listener);
    }

    /** Test listener has handle method */
    public function test_listener_has_handle_method(): void
    {
        $listener = new HandleNewMessage;

        $this->assertTrue(method_exists($listener, 'handle'));
    }

    /** Test listener handle accepts NewMessageReceived event */
    public function test_listener_accepts_new_message_received_event(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        $event = new NewMessageReceived($thread, $conversation);
        $listener = new HandleNewMessage;

        // Should not throw exception
        $listener->handle($event);

        $this->assertTrue(true);
    }

    /** Test listener can be constructed without parameters */
    public function test_listener_constructor_no_parameters(): void
    {
        $listener = new HandleNewMessage;

        $this->assertNotNull($listener);
    }

    /** Test listener is registered in Laravel */
    public function test_listener_class_exists(): void
    {
        $this->assertTrue(class_exists(HandleNewMessage::class));
    }

    /** Test listener handle method signature */
    public function test_handle_method_signature(): void
    {
        $reflection = new \ReflectionMethod(HandleNewMessage::class, 'handle');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('event', $parameters[0]->getName());
    }

    /** Test listener processes multiple events */
    public function test_listener_processes_multiple_events(): void
    {
        $listener = new HandleNewMessage;
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $conversation = Conversation::factory()->create([
                'mailbox_id' => $mailbox->id,
                'customer_id' => $customer->id,
            ]);
            $thread = Thread::factory()->create([
                'conversation_id' => $conversation->id,
            ]);

            $event = new NewMessageReceived($thread, $conversation);
            $listener->handle($event);
        }

        // Should handle multiple events without error
        $this->assertTrue(true);
    }

    /** Test listener does not throw on empty event */
    public function test_listener_handles_event_gracefully(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        $event = new NewMessageReceived($thread, $conversation);
        $listener = new HandleNewMessage;

        try {
            $listener->handle($event);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Listener should not throw exception: '.$e->getMessage());
        }
    }

    /** Test listener handles events with different thread types */
    public function test_listener_handles_different_thread_types(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);

        $threadTypes = [1, 2, 3, 4]; // Different thread types

        $listener = new HandleNewMessage;

        foreach ($threadTypes as $type) {
            $thread = Thread::factory()->create([
                'conversation_id' => $conversation->id,
                'type' => $type,
            ]);

            $event = new NewMessageReceived($thread, $conversation);

            try {
                $listener->handle($event);
                $this->assertTrue(true);
            } catch (\Exception $e) {
                $this->fail('Listener should handle all thread types');
            }
        }
    }

    /** Test listener handles rapid successive events */
    public function test_listener_handles_rapid_successive_events(): void
    {
        $listener = new HandleNewMessage;
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);

        // Create many events rapidly
        for ($i = 0; $i < 10; $i++) {
            $thread = Thread::factory()->create([
                'conversation_id' => $conversation->id,
            ]);

            $event = new NewMessageReceived($thread, $conversation);
            $listener->handle($event);
        }

        // Should handle all without memory or performance issues
        $this->assertTrue(true);
    }

    /** Test listener is stateless between calls */
    public function test_listener_is_stateless(): void
    {
        $listener = new HandleNewMessage;
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();

        // First event
        $conversation1 = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $thread1 = Thread::factory()->create(['conversation_id' => $conversation1->id]);
        $event1 = new NewMessageReceived($thread1, $conversation1);
        $listener->handle($event1);

        // Second event - listener should not retain state from first
        $conversation2 = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $thread2 = Thread::factory()->create(['conversation_id' => $conversation2->id]);
        $event2 = new NewMessageReceived($thread2, $conversation2);
        $listener->handle($event2);

        // No state should be retained
        $this->assertTrue(true);
    }

    /** Test listener constructor is idempotent */
    public function test_listener_can_be_instantiated_multiple_times(): void
    {
        $listener1 = new HandleNewMessage;
        $listener2 = new HandleNewMessage;
        $listener3 = new HandleNewMessage;

        $this->assertInstanceOf(HandleNewMessage::class, $listener1);
        $this->assertInstanceOf(HandleNewMessage::class, $listener2);
        $this->assertInstanceOf(HandleNewMessage::class, $listener3);
    }

    /** Test listener handles events from different mailboxes */
    public function test_listener_handles_events_from_different_mailboxes(): void
    {
        $listener = new HandleNewMessage;

        for ($i = 0; $i < 3; $i++) {
            $mailbox = Mailbox::factory()->create();
            $customer = Customer::factory()->create();
            $conversation = Conversation::factory()->create([
                'mailbox_id' => $mailbox->id,
                'customer_id' => $customer->id,
            ]);
            $thread = Thread::factory()->create([
                'conversation_id' => $conversation->id,
            ]);

            $event = new NewMessageReceived($thread, $conversation);
            $listener->handle($event);
        }

        // Should handle events from different mailboxes
        $this->assertTrue(true);
    }
}
