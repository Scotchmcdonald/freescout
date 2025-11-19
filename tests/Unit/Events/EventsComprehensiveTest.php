<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Events\ConversationStatusChanged;
use App\Events\ConversationUpdated;
use App\Events\ConversationUserChanged;
use App\Events\CustomerCreatedConversation;
use App\Events\CustomerReplied;
use App\Events\NewMessageReceived;
use App\Events\UserAddedNote;
use App\Events\UserCreatedConversation;
use App\Events\UserDeleted;
use App\Events\UserReplied;
use App\Events\UserViewingConversation;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\UnitTestCase;

/**
 * Comprehensive tests for Event Classes
 * Following TESTING_GUIDE.md - using test_ prefix, UnitTestCase base class
 */
class EventsComprehensiveTest extends UnitTestCase
{
    // ===== USER_DELETED EVENT TESTS =====

    public function test_user_deleted_event_can_be_created(): void
    {
        $deletedUser = User::factory()->create();
        $byUser = User::factory()->create();
        
        $event = new UserDeleted($deletedUser, $byUser);
        
        $this->assertInstanceOf(UserDeleted::class, $event);
        $this->assertEquals($deletedUser->id, $event->deleted_user->id);
        $this->assertEquals($byUser->id, $event->by_user->id);
    }

    public function test_user_deleted_event_uses_dispatchable(): void
    {
        Event::fake();
        
        $deletedUser = User::factory()->create();
        $byUser = User::factory()->create();
        
        UserDeleted::dispatch($deletedUser, $byUser);
        
        Event::assertDispatched(UserDeleted::class);
    }

    public function test_user_deleted_event_has_deleted_user_property(): void
    {
        $deletedUser = User::factory()->create();
        $byUser = User::factory()->create();
        
        $event = new UserDeleted($deletedUser, $byUser);
        
        $this->assertInstanceOf(User::class, $event->deleted_user);
    }

    public function test_user_deleted_event_has_by_user_property(): void
    {
        $deletedUser = User::factory()->create();
        $byUser = User::factory()->create();
        
        $event = new UserDeleted($deletedUser, $byUser);
        
        $this->assertInstanceOf(User::class, $event->by_user);
    }

    // ===== CONVERSATION_STATUS_CHANGED EVENT TESTS =====

    public function test_conversation_status_changed_event_can_be_created(): void
    {
        $conversation = Conversation::factory()->create();
        
        $event = new ConversationStatusChanged($conversation);
        
        $this->assertInstanceOf(ConversationStatusChanged::class, $event);
        $this->assertEquals($conversation->id, $event->conversation->id);
    }

    public function test_conversation_status_changed_event_uses_dispatchable(): void
    {
        Event::fake();
        
        $conversation = Conversation::factory()->create();
        
        ConversationStatusChanged::dispatch($conversation);
        
        Event::assertDispatched(ConversationStatusChanged::class);
    }

    // ===== CONVERSATION_USER_CHANGED EVENT TESTS =====

    public function test_conversation_user_changed_event_can_be_created(): void
    {
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();
        
        $event = new ConversationUserChanged($conversation, $user);
        
        $this->assertInstanceOf(ConversationUserChanged::class, $event);
        $this->assertEquals($conversation->id, $event->conversation->id);
        $this->assertEquals($user->id, $event->user->id);
    }

    public function test_conversation_user_changed_event_uses_dispatchable(): void
    {
        Event::fake();
        
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();
        
        ConversationUserChanged::dispatch($conversation, $user);
        
        Event::assertDispatched(ConversationUserChanged::class);
    }

    // ===== USER_ADDED_NOTE EVENT TESTS =====

    public function test_user_added_note_event_can_be_created(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create();
        
        $event = new UserAddedNote($conversation, $thread);
        
        $this->assertInstanceOf(UserAddedNote::class, $event);
        $this->assertEquals($conversation->id, $event->conversation->id);
        $this->assertEquals($thread->id, $event->thread->id);
    }

    public function test_user_added_note_event_uses_dispatchable(): void
    {
        Event::fake();
        
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create();
        
        UserAddedNote::dispatch($conversation, $thread);
        
        Event::assertDispatched(UserAddedNote::class);
    }

    // ===== USER_CREATED_CONVERSATION EVENT TESTS =====

    public function test_user_created_conversation_event_can_be_created(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create();
        
        $event = new UserCreatedConversation($conversation, $thread);
        
        $this->assertInstanceOf(UserCreatedConversation::class, $event);
        $this->assertEquals($conversation->id, $event->conversation->id);
        $this->assertEquals($thread->id, $event->thread->id);
    }

    public function test_user_created_conversation_event_uses_dispatchable(): void
    {
        Event::fake();
        
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create();
        
        UserCreatedConversation::dispatch($conversation, $thread);
        
        Event::assertDispatched(UserCreatedConversation::class);
    }

    // ===== USER_REPLIED EVENT TESTS =====

    public function test_user_replied_event_can_be_created(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create();
        
        $event = new UserReplied($conversation, $thread);
        
        $this->assertInstanceOf(UserReplied::class, $event);
        $this->assertEquals($conversation->id, $event->conversation->id);
        $this->assertEquals($thread->id, $event->thread->id);
    }

    public function test_user_replied_event_uses_dispatchable(): void
    {
        Event::fake();
        
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create();
        
        UserReplied::dispatch($conversation, $thread);
        
        Event::assertDispatched(UserReplied::class);
    }

    // ===== CUSTOMER_CREATED_CONVERSATION EVENT TESTS =====

    public function test_customer_created_conversation_event_can_be_created(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create();
        $customer = Customer::factory()->create();
        
        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        
        $this->assertInstanceOf(CustomerCreatedConversation::class, $event);
        $this->assertEquals($conversation->id, $event->conversation->id);
        $this->assertEquals($thread->id, $event->thread->id);
        $this->assertEquals($customer->id, $event->customer->id);
    }

    public function test_customer_created_conversation_event_uses_dispatchable(): void
    {
        Event::fake();
        
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create();
        $customer = Customer::factory()->create();
        
        CustomerCreatedConversation::dispatch($conversation, $thread, $customer);
        
        Event::assertDispatched(CustomerCreatedConversation::class);
    }

    // ===== CUSTOMER_REPLIED EVENT TESTS =====

    public function test_customer_replied_event_can_be_created(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create();
        $customer = Customer::factory()->create();
        
        $event = new CustomerReplied($conversation, $thread, $customer);
        
        $this->assertInstanceOf(CustomerReplied::class, $event);
        $this->assertEquals($conversation->id, $event->conversation->id);
        $this->assertEquals($thread->id, $event->thread->id);
        $this->assertEquals($customer->id, $event->customer->id);
    }

    public function test_customer_replied_event_uses_dispatchable(): void
    {
        Event::fake();
        
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create();
        $customer = Customer::factory()->create();
        
        CustomerReplied::dispatch($conversation, $thread, $customer);
        
        Event::assertDispatched(CustomerReplied::class);
    }

    // ===== USER_VIEWING_CONVERSATION EVENT TESTS =====

    public function test_user_viewing_conversation_event_can_be_created(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        
        $event = new UserViewingConversation($user, $conversation);
        
        $this->assertInstanceOf(UserViewingConversation::class, $event);
        $this->assertEquals($user->id, $event->user->id);
        $this->assertEquals($conversation->id, $event->conversation->id);
    }

    public function test_user_viewing_conversation_event_uses_dispatchable(): void
    {
        Event::fake();
        
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        
        UserViewingConversation::dispatch($user, $conversation);
        
        Event::assertDispatched(UserViewingConversation::class);
    }

    // ===== NEW_MESSAGE_RECEIVED EVENT TESTS =====

    public function test_new_message_received_event_can_be_created(): void
    {
        $mailbox = Mailbox::factory()->create();
        $messageData = ['from' => 'test@example.com', 'subject' => 'Test'];
        
        $event = new NewMessageReceived($mailbox, $messageData);
        
        $this->assertInstanceOf(NewMessageReceived::class, $event);
        $this->assertEquals($mailbox->id, $event->mailbox->id);
        $this->assertEquals($messageData, $event->message_data);
    }

    public function test_new_message_received_event_uses_dispatchable(): void
    {
        Event::fake();
        
        $mailbox = Mailbox::factory()->create();
        $messageData = ['from' => 'test@example.com'];
        
        NewMessageReceived::dispatch($mailbox, $messageData);
        
        Event::assertDispatched(NewMessageReceived::class);
    }

    public function test_new_message_received_event_has_mailbox_property(): void
    {
        $mailbox = Mailbox::factory()->create();
        $messageData = ['from' => 'test@example.com'];
        
        $event = new NewMessageReceived($mailbox, $messageData);
        
        $this->assertInstanceOf(Mailbox::class, $event->mailbox);
    }

    public function test_new_message_received_event_has_message_data_property(): void
    {
        $mailbox = Mailbox::factory()->create();
        $messageData = ['from' => 'test@example.com', 'subject' => 'Test Subject'];
        
        $event = new NewMessageReceived($mailbox, $messageData);
        
        $this->assertIsArray($event->message_data);
        $this->assertEquals('test@example.com', $event->message_data['from']);
    }

    // ===== CONVERSATION_UPDATED EVENT TESTS =====

    public function test_conversation_updated_event_can_be_created(): void
    {
        $conversation = Conversation::factory()->create();
        $changes = ['status' => 'closed'];
        
        $event = new ConversationUpdated($conversation, $changes);
        
        $this->assertInstanceOf(ConversationUpdated::class, $event);
        $this->assertEquals($conversation->id, $event->conversation->id);
        $this->assertEquals($changes, $event->changes);
    }

    public function test_conversation_updated_event_uses_dispatchable(): void
    {
        Event::fake();
        
        $conversation = Conversation::factory()->create();
        $changes = ['status' => 'closed'];
        
        ConversationUpdated::dispatch($conversation, $changes);
        
        Event::assertDispatched(ConversationUpdated::class);
    }

    public function test_conversation_updated_event_with_empty_changes(): void
    {
        $conversation = Conversation::factory()->create();
        $changes = [];
        
        $event = new ConversationUpdated($conversation, $changes);
        
        $this->assertEquals([], $event->changes);
    }

    // ===== EDGE CASES AND INTEGRATION =====

    public function test_multiple_events_can_be_dispatched(): void
    {
        Event::fake();
        
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create();
        
        UserReplied::dispatch($conversation, $thread);
        UserAddedNote::dispatch($conversation, $thread);
        
        Event::assertDispatched(UserReplied::class);
        Event::assertDispatched(UserAddedNote::class);
    }

    public function test_event_can_be_dispatched_with_listener(): void
    {
        Event::fake();
        
        $conversation = Conversation::factory()->create();
        
        ConversationStatusChanged::dispatch($conversation);
        
        Event::assertDispatched(ConversationStatusChanged::class, function ($event) use ($conversation) {
            return $event->conversation->id === $conversation->id;
        });
    }

    public function test_events_maintain_model_properties(): void
    {
        $user = User::factory()->create(['first_name' => 'John']);
        $deletedUser = User::factory()->create(['first_name' => 'Jane']);
        
        $event = new UserDeleted($deletedUser, $user);
        
        $this->assertEquals('Jane', $event->deleted_user->first_name);
        $this->assertEquals('John', $event->by_user->first_name);
    }

    public function test_event_dispatch_does_not_modify_models(): void
    {
        $conversation = Conversation::factory()->create(['subject' => 'Original']);
        
        ConversationStatusChanged::dispatch($conversation);
        
        $this->assertEquals('Original', $conversation->fresh()->subject);
    }
}
