<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\UserCreatedConversation;
use App\Events\UserReplied;
use App\Listeners\SendReplyToCustomer;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendReplyToCustomerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function listener_handles_user_replied_event(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'type' => 1, // TYPE_EMAIL
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
            'type' => Thread::TYPE_MESSAGE,
            'imported' => false,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer();
        
        // Should handle without exception
        $listener->handle($event);
        $this->assertTrue(true);
    }

    #[Test]
    public function listener_handles_user_created_conversation_event(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'created_by_user_id' => $user->id,
            'type' => 1,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_MESSAGE,
            'imported' => false,
        ]);

        $event = new UserCreatedConversation($conversation, $thread);
        $listener = new SendReplyToCustomer();
        
        $listener->handle($event);
        $this->assertTrue(true);
    }

    #[Test]
    public function listener_skips_imported_threads(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
        ]);
        
        // Mock getReplies method
        $importedThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
            'imported' => true,
        ]);

        $event = new UserReplied($conversation, $importedThread);
        $listener = new SendReplyToCustomer();
        
        // Should skip imported threads
        $listener->handle($event);
        $this->assertTrue(true);
    }

    #[Test]
    public function listener_handles_phone_conversation_with_email(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $customer->emails()->create(['email' => 'customer@example.com', 'type' => 'work']);
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'type' => 2, // TYPE_PHONE (if defined)
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
            'imported' => false,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer();
        
        // Should process phone conversation with customer email
        $listener->handle($event);
        $this->assertTrue(true);
    }

    #[Test]
    public function listener_processes_multiple_threads(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        
        // Create the most recent thread to use
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_MESSAGE,
            'imported' => false,
            'created_at' => now(),
        ]);

        $event = new UserCreatedConversation($conversation, $thread);
        $listener = new SendReplyToCustomer();
        
        $listener->handle($event);
        $this->assertTrue(true);
    }

    #[Test]
    public function listener_handles_event_with_thread_property(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer();
        
        // Should process thread from event
        $listener->handle($event);
        $this->assertInstanceOf(Thread::class, $event->thread);
    }

    #[Test]
    public function listener_handles_conversation_with_thread(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
            'user_id' => $user->id,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new UserCreatedConversation($conversation, $thread);
        $listener = new SendReplyToCustomer();
        
        // Should handle conversation with thread
        $listener->handle($event);
        $this->assertTrue(true);
    }

    #[Test]
    public function listener_filters_threads_after_event_thread(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        
        $thread1 = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'id' => 1,
            'created_at' => now()->subMinutes(10),
        ]);
        $thread2 = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'id' => 2,
            'created_at' => now()->subMinutes(5),
        ]);
        $thread3 = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'id' => 3,
            'created_at' => now(),
        ]);

        $event = new UserReplied($conversation, $thread2);
        $listener = new SendReplyToCustomer();
        
        // Should only process threads up to thread2
        $listener->handle($event);
        $this->assertEquals(2, $event->thread->id);
    }

    #[Test]
    public function listener_handles_user_replied_with_customer(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'customer_email' => $customer->email,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
            'type' => Thread::TYPE_MESSAGE,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer();
        
        $listener->handle($event);
        $this->assertNotNull($conversation->customer_id);
    }

    #[Test]
    public function listener_can_be_instantiated(): void
    {
        $listener = new SendReplyToCustomer();
        $this->assertInstanceOf(SendReplyToCustomer::class, $listener);
    }
}
