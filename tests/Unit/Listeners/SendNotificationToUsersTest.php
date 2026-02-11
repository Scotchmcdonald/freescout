<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\ConversationUserChanged;
use App\Events\CustomerCreatedConversation;
use App\Events\CustomerReplied;
use App\Events\UserAddedNote;
use App\Events\UserCreatedConversation;
use App\Events\UserReplied;
use App\Jobs\SendNotificationToUsersJob;
use App\Listeners\SendNotificationToUsers;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\UnitTestCase;

class SendNotificationToUsersTest extends UnitTestCase
{

    public function test_listener_handles_user_replied_event(): void
    {
        Queue::fake();
        
        $assignedUser = User::factory()->create();
        $replyingUser = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($assignedUser->id);
        
        $conversation = Conversation::factory()->create([
            'status' => 1,
            'mailbox_id' => $mailbox->id,
            'user_id' => $assignedUser->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $replyingUser->id,
            'imported' => false,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendNotificationToUsers();
        
        $listener->handle($event);
        
        // Verify job was dispatched when users should be notified
        Queue::assertPushed(SendNotificationToUsersJob::class);
    }

    public function test_listener_handles_user_added_note_event(): void
    {
        Queue::fake();
        
        $assignedUser = User::factory()->create();
        $noteUser = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($assignedUser->id);
        
        $conversation = Conversation::factory()->create([
            'status' => 1,
            'mailbox_id' => $mailbox->id,
            'user_id' => $assignedUser->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $noteUser->id,
            'type' => Thread::TYPE_NOTE,
            'imported' => false,
        ]);

        $event = new UserAddedNote($conversation, $thread);
        $listener = new SendNotificationToUsers();
        
        $listener->handle($event);
        
        Queue::assertPushed(SendNotificationToUsersJob::class);
    }

    public function test_listener_handles_user_created_conversation_event(): void
    {
        Queue::fake();
        
        $assignedUser = User::factory()->create();
        $creatorUser = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($assignedUser->id);
        
        $conversation = Conversation::factory()->create([
            'status' => 1,
            'created_by_user_id' => $creatorUser->id,
            'mailbox_id' => $mailbox->id,
            'user_id' => $assignedUser->id,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new UserCreatedConversation($conversation, $thread);
        $listener = new SendNotificationToUsers();
        
        $listener->handle($event);
        
        Queue::assertPushed(SendNotificationToUsersJob::class);
    }

    public function test_listener_handles_customer_created_conversation_event(): void
    {
        Queue::fake();
        
        $assignedUser = User::factory()->create();
        $customer = Customer::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($assignedUser->id);
        
        $conversation = Conversation::factory()->create([
            'status' => 1, // Not spam
            'customer_id' => $customer->id,
            'mailbox_id' => $mailbox->id,
            'user_id' => $assignedUser->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'imported' => false,
        ]);

        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendNotificationToUsers();
        
        $listener->handle($event);
        
        Queue::assertPushed(SendNotificationToUsersJob::class);
    }

    public function test_listener_handles_conversation_user_changed_event(): void
    {
        Queue::fake();
        
        $assignedUser = User::factory()->create();
        $assigningUser = User::factory()->create(); // Different user who causes the assignment
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($assignedUser->id);
        
        $conversation = Conversation::factory()->create([
            'status' => 1,
            'user_id' => $assignedUser->id,
            'mailbox_id' => $mailbox->id,
        ]);

        // The assigningUser causes the change, assignedUser receives notification
        $event = new ConversationUserChanged($conversation, null, null, $assigningUser);
        $listener = new SendNotificationToUsers();
        
        $listener->handle($event);
        
        // Assigned user should be notified when someone else assigns the conversation
        Queue::assertPushed(SendNotificationToUsersJob::class);
    }

    public function test_listener_handles_customer_replied_event(): void
    {
        Queue::fake();
        
        $assignedUser = User::factory()->create();
        $customer = Customer::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($assignedUser->id);
        
        $conversation = Conversation::factory()->create([
            'status' => 1,
            'customer_id' => $customer->id,
            'mailbox_id' => $mailbox->id,
            'user_id' => $assignedUser->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_CUSTOMER,
            'imported' => false,
        ]);

        $event = new CustomerReplied($conversation, $thread, $customer);
        $listener = new SendNotificationToUsers();
        
        $listener->handle($event);
        
        Queue::assertPushed(SendNotificationToUsersJob::class);
    }

    public function test_listener_skips_spam_conversations_for_customer_events(): void
    {
        Queue::fake();
        
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
        
        $listener->handle($event);
        
        // Should NOT dispatch job for spam conversations
        Queue::assertNothingPushed();
    }

    public function test_listener_skips_imported_threads(): void
    {
        Queue::fake();
        
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['status' => 1]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
            'imported' => true,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendNotificationToUsers();
        
        $listener->handle($event);
        
        // Should NOT dispatch job for imported threads
        Queue::assertNothingPushed();
    }

    public function test_listener_handles_events_without_causing_exceptions(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['status' => 1]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $user->id,
            'imported' => false,
        ]);

        $listener = new SendNotificationToUsers();
        
        // All event types should execute without throwing exceptions
        $listener->handle(new UserReplied($conversation, $thread));
        $listener->handle(new UserAddedNote($conversation, $thread));
        $listener->handle(new UserCreatedConversation($conversation, $thread));
        $listener->handle(new CustomerCreatedConversation($conversation, $thread, $customer));
        $listener->handle(new ConversationUserChanged($conversation, null, null, $user));
        $listener->handle(new CustomerReplied($conversation, $thread, $customer));
        
        // If we got here, no exceptions were thrown
        $this->expectNotToPerformAssertions();
    }

    public function test_listener_excludes_caused_by_user_from_notifications(): void
    {
        Queue::fake();
        
        $replyingUser = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($replyingUser->id);
        
        $conversation = Conversation::factory()->create([
            'status' => 1,
            'mailbox_id' => $mailbox->id,
            'user_id' => $replyingUser->id, // Assigned to the same user who replies
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $replyingUser->id,
            'imported' => false,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendNotificationToUsers();
        
        $listener->handle($event);
        
        // Should not notify user when they caused the event
        Queue::assertNothingPushed();
    }
}
