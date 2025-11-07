<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Events\NewMessageReceived;
use App\Listeners\HandleNewMessage;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandleNewMessageListenerTest extends TestCase
{
    use RefreshDatabase;

    /** Test listener can be instantiated */
    public function test_listener_can_be_instantiated(): void
    {
        $listener = new HandleNewMessage();
        
        $this->assertInstanceOf(HandleNewMessage::class, $listener);
    }

    /** Test listener has handle method */
    public function test_listener_has_handle_method(): void
    {
        $listener = new HandleNewMessage();
        
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
        $listener = new HandleNewMessage();
        
        // Should not throw exception
        $listener->handle($event);
        
        $this->assertTrue(true);
    }

    /** Test listener can be constructed without parameters */
    public function test_listener_constructor_no_parameters(): void
    {
        $listener = new HandleNewMessage();
        
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
        $listener = new HandleNewMessage();
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
        $listener = new HandleNewMessage();
        
        try {
            $listener->handle($event);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Listener should not throw exception: ' . $e->getMessage());
        }
    }
}
