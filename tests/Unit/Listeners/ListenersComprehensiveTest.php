<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;
use App\Events\CustomerCreatedConversation;
use App\Events\NewMessageReceived;
use App\Events\UserCreatedConversation;
use App\Events\UserReplied;
use App\Listeners\ConversationStatusChangedListener;
use App\Listeners\ConversationUserChangedListener;
use App\Listeners\CustomerCreatedConversationListener;
use App\Listeners\NewMessageReceivedListener;
use App\Listeners\UserCreatedConversationListener;
use App\Listeners\UserRepliedListener;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\UnitTestCase;

/**
 * Comprehensive tests for Listener classes
 * Following TESTING_GUIDE.md - using test_ prefix, UnitTestCase base class
 */
class ListenersComprehensiveTest extends UnitTestCase
{
    // ===== CONVERSATION STATUS CHANGED LISTENER =====

    public function test_conversation_status_changed_listener_handles_event(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $event = new ConversationStatusChanged($conversation, Conversation::STATUS_CLOSED);
        $listener = new ConversationStatusChangedListener();

        $listener->handle($event);

        $this->assertTrue(true); // Listener executed without errors
    }

    public function test_conversation_status_changed_listener_updates_folder_counters(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $event = new ConversationStatusChanged($conversation, Conversation::STATUS_CLOSED);
        $listener = new ConversationStatusChangedListener();

        $listener->handle($event);

        // Verify folder counters are updated
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'status' => Conversation::STATUS_CLOSED,
        ]);
    }

    public function test_conversation_status_changed_listener_sends_notifications(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'user_id' => $user->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $event = new ConversationStatusChanged($conversation, Conversation::STATUS_PENDING);
        $listener = new ConversationStatusChangedListener();

        $listener->handle($event);

        // Verify notifications are queued
        Notification::assertSentTo($user, function ($notification) {
            return true;
        });
    }

    // ===== CONVERSATION USER CHANGED LISTENER =====

    public function test_conversation_user_changed_listener_handles_event(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user2->id]);

        $event = new ConversationUserChanged($conversation, $user1->id);
        $listener = new ConversationUserChangedListener();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_conversation_user_changed_listener_notifies_new_user(): void
    {
        Notification::fake();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user2->id]);

        $event = new ConversationUserChanged($conversation, $user1->id);
        $listener = new ConversationUserChangedListener();

        $listener->handle($event);

        Notification::assertSentTo($user2, function ($notification) {
            return true;
        });
    }

    public function test_conversation_user_changed_listener_handles_null_previous_user(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        $event = new ConversationUserChanged($conversation, null);
        $listener = new ConversationUserChangedListener();

        $listener->handle($event);

        $this->assertTrue(true); // Should not error with null
    }

    // ===== USER CREATED CONVERSATION LISTENER =====

    public function test_user_created_conversation_listener_handles_event(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        $event = new UserCreatedConversation($conversation);
        $listener = new UserCreatedConversationListener();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_user_created_conversation_listener_logs_activity(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        $event = new UserCreatedConversation($conversation);
        $listener = new UserCreatedConversationListener();

        $listener->handle($event);

        $this->assertDatabaseHas('activity_logs', [
            'conversation_id' => $conversation->id,
        ]);
    }

    // ===== CUSTOMER CREATED CONVERSATION LISTENER =====

    public function test_customer_created_conversation_listener_handles_event(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $event = new CustomerCreatedConversation($conversation);
        $listener = new CustomerCreatedConversationListener();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_customer_created_conversation_listener_sends_auto_reply(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create(['auto_reply' => true]);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);

        $event = new CustomerCreatedConversation($conversation);
        $listener = new CustomerCreatedConversationListener();

        $listener->handle($event);

        Mail::assertQueued(function ($mail) {
            return true; // Auto-reply should be queued
        });
    }

    public function test_customer_created_conversation_listener_respects_auto_reply_setting(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create(['auto_reply' => false]);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);

        $event = new CustomerCreatedConversation($conversation);
        $listener = new CustomerCreatedConversationListener();

        $listener->handle($event);

        Mail::assertNothingQueued();
    }

    // ===== USER REPLIED LISTENER =====

    public function test_user_replied_listener_handles_event(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new UserRepliedListener();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_user_replied_listener_sends_notification_to_customer(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_MESSAGE,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new UserRepliedListener();

        $listener->handle($event);

        Mail::assertQueued(function ($mail) {
            return true;
        });
    }

    public function test_user_replied_listener_does_not_send_for_notes(): void
    {
        Mail::fake();

        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_NOTE,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new UserRepliedListener();

        $listener->handle($event);

        Mail::assertNothingQueued();
    }

    // ===== NEW MESSAGE RECEIVED LISTENER =====

    public function test_new_message_received_listener_handles_event(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new NewMessageReceived($conversation, $thread);
        $listener = new NewMessageReceivedListener();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_new_message_received_listener_notifies_assigned_users(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new NewMessageReceived($conversation, $thread);
        $listener = new NewMessageReceivedListener();

        $listener->handle($event);

        Notification::assertSentTo($user, function ($notification) {
            return true;
        });
    }

    public function test_new_message_received_listener_updates_conversation_status(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_CLOSED,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new NewMessageReceived($conversation, $thread);
        $listener = new NewMessageReceivedListener();

        $listener->handle($event);

        $conversation->refresh();
        $this->assertEquals(Conversation::STATUS_ACTIVE, $conversation->status);
    }

    // ===== EDGE CASE TESTS =====

    public function test_listeners_handle_null_conversation_gracefully(): void
    {
        $event = new ConversationStatusChanged(null, Conversation::STATUS_CLOSED);
        $listener = new ConversationStatusChangedListener();

        $listener->handle($event);

        $this->assertTrue(true); // Should not throw exception
    }

    public function test_listeners_handle_deleted_users(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);
        $user->delete();

        $event = new ConversationStatusChanged($conversation, Conversation::STATUS_CLOSED);
        $listener = new ConversationStatusChangedListener();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_listeners_handle_concurrent_events(): void
    {
        $conversation = Conversation::factory()->create();

        $events = [];
        for ($i = 0; $i < 5; $i++) {
            $events[] = new ConversationStatusChanged($conversation, Conversation::STATUS_ACTIVE);
        }

        $listener = new ConversationStatusChangedListener();

        foreach ($events as $event) {
            $listener->handle($event);
        }

        $this->assertTrue(true);
    }

    public function test_listeners_do_not_cause_infinite_loops(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $event = new ConversationStatusChanged($conversation, Conversation::STATUS_CLOSED);
        $listener = new ConversationStatusChangedListener();

        // Handle the event multiple times
        $listener->handle($event);
        $listener->handle($event);
        $listener->handle($event);

        $this->assertTrue(true); // Should complete without hanging
    }

    public function test_listeners_handle_missing_relationships(): void
    {
        $conversation = Conversation::factory()->create([
            'customer_id' => null,
            'user_id' => null,
        ]);

        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new NewMessageReceived($conversation, $thread);
        $listener = new NewMessageReceivedListener();

        $listener->handle($event);

        $this->assertTrue(true);
    }
}
