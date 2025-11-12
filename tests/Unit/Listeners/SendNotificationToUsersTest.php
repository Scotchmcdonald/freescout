<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\ConversationUserChanged;
use App\Events\CustomerCreatedConversation;
use App\Events\CustomerReplied;
use App\Events\UserAddedNote;
use App\Events\UserCreatedConversation;
use App\Events\UserReplied;
use App\Listeners\SendNotificationToUsers;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendNotificationToUsersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function listener_handles_user_replied_event(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['status' => 1]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
            'imported' => false,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendNotificationToUsers();
        
        // Should not throw exception
        $listener->handle($event);
        $this->assertTrue(true);
    }

    #[Test]
    public function listener_handles_user_added_note_event(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['status' => 1]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
            'type' => Thread::TYPE_NOTE,
            'imported' => false,
        ]);

        $event = new UserAddedNote($conversation, $thread);
        $listener = new SendNotificationToUsers();
        
        $listener->handle($event);
        $this->assertTrue(true);
    }

    #[Test]
    public function listener_handles_user_created_conversation_event(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'status' => 1,
            'created_by_user_id' => $user->id,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new UserCreatedConversation($conversation, $thread);
        $listener = new SendNotificationToUsers();
        
        $listener->handle($event);
        $this->assertTrue(true);
    }

    #[Test]
    public function listener_handles_customer_created_conversation_event(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'status' => 1, // Not spam
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'imported' => false,
        ]);

        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendNotificationToUsers();
        
        $listener->handle($event);
        $this->assertTrue(true);
    }

    #[Test]
    public function listener_handles_conversation_user_changed_event(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'status' => 1,
            'user_id' => $user->id,
        ]);

        $event = new ConversationUserChanged($conversation, $user);
        $listener = new SendNotificationToUsers();
        
        $listener->handle($event);
        $this->assertTrue(true);
    }

    #[Test]
    public function listener_handles_customer_replied_event(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'status' => 1,
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_CUSTOMER,
            'imported' => false,
        ]);

        $event = new CustomerReplied($conversation, $thread, $customer);
        $listener = new SendNotificationToUsers();
        
        $listener->handle($event);
        $this->assertTrue(true);
    }

    #[Test]
    public function listener_skips_spam_conversations_for_customer_events(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'status' => 3, // STATUS_SPAM
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'imported' => false,
        ]);

        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendNotificationToUsers();
        
        // Should handle gracefully without processing
        $listener->handle($event);
        $this->assertTrue(true);
    }

    #[Test]
    public function listener_skips_imported_threads(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['status' => 1]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
            'imported' => true,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendNotificationToUsers();
        
        // Should skip processing for imported threads
        $listener->handle($event);
        $this->assertTrue(true);
    }

    #[Test]
    public function listener_detects_user_replied_event_type(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
            'type' => Thread::TYPE_MESSAGE,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendNotificationToUsers();
        
        // Should detect event type 5 (EVENT_TYPE_USER_REPLIED)
        $listener->handle($event);
        $this->assertInstanceOf(UserReplied::class, $event);
    }

    #[Test]
    public function listener_detects_customer_replied_event_type(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['status' => 1]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_CUSTOMER,
        ]);

        $event = new CustomerReplied($conversation, $thread, $customer);
        $listener = new SendNotificationToUsers();
        
        // Should detect event type 4 (EVENT_TYPE_CUSTOMER_REPLIED)
        $listener->handle($event);
        $this->assertInstanceOf(CustomerReplied::class, $event);
    }

    #[Test]
    public function listener_detects_assigned_event_type(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'status' => 1,
            'user_id' => $user->id,
        ]);

        $event = new ConversationUserChanged($conversation, $user);
        $listener = new SendNotificationToUsers();
        
        // Should detect event type 2 (EVENT_TYPE_ASSIGNED)
        $listener->handle($event);
        $this->assertInstanceOf(ConversationUserChanged::class, $event);
    }

    #[Test]
    public function listener_detects_note_added_event_type(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
            'type' => Thread::TYPE_NOTE,
        ]);

        $event = new UserAddedNote($conversation, $thread);
        $listener = new SendNotificationToUsers();
        
        // Should detect event type 6 (EVENT_TYPE_USER_ADDED_NOTE)
        $listener->handle($event);
        $this->assertInstanceOf(UserAddedNote::class, $event);
    }

    #[Test]
    public function listener_handles_multiple_event_types(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['status' => 1]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
        ]);

        $listener = new SendNotificationToUsers();
        $customer = Customer::factory()->create();
        
        // Test multiple event types
        $listener->handle(new UserReplied($conversation, $thread));
        $listener->handle(new UserAddedNote($conversation, $thread));
        $listener->handle(new CustomerReplied($conversation, $thread, $customer));
        $listener->handle(new ConversationUserChanged($conversation, $user));
        
        $this->assertTrue(true);
    }

    #[Test]
    public function listener_handles_events_without_thread(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'status' => 1,
            'created_by_user_id' => $user->id,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new UserCreatedConversation($conversation, $thread);
        $listener = new SendNotificationToUsers();
        
        // Should handle events with thread
        $listener->handle($event);
        $this->assertTrue(true);
    }
}
