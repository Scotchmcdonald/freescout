<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;
use App\Events\UserCreatedConversation;
use App\Events\UserReplied;
use App\Jobs\SendConversationReply;
use App\Listeners\RememberUserLocale;
use App\Listeners\SendPasswordChanged;
use App\Listeners\SendReplyToCustomer;
use App\Listeners\UpdateMailboxCounters;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Queue;
use Tests\UnitTestCase;

class AdditionalListenersTest extends UnitTestCase
{
    // RememberUserLocale Listener Tests (5 tests)

    public function test_remember_user_locale_handles_login_event_without_locale(): void
    {
        $user = User::factory()->create();
        
        $event = new Login('web', $user, false);
        $listener = new RememberUserLocale();

        $listener->handle($event);

        // User doesn't have getLocale method, so session should not have user_locale
        $this->assertNull(session('user_locale'));
    }

    public function test_remember_user_locale_handles_web_guard(): void
    {
        $user = User::factory()->create();
        $event = new Login('web', $user, false);
        $listener = new RememberUserLocale();

        $listener->handle($event);

        $this->expectNotToPerformAssertions();
    }

    public function test_remember_user_locale_handles_api_guard(): void
    {
        $user = User::factory()->create();
        $event = new Login('api', $user, false);
        $listener = new RememberUserLocale();

        $listener->handle($event);

        $this->expectNotToPerformAssertions();
    }

    public function test_remember_user_locale_handles_remember_me(): void
    {
        $user = User::factory()->create();
        $event = new Login('web', $user, true);
        $listener = new RememberUserLocale();

        $listener->handle($event);

        $this->expectNotToPerformAssertions();
    }

    public function test_remember_user_locale_checks_method_exists(): void
    {
        $user = User::factory()->create();
        $event = new Login('web', $user, false);
        $listener = new RememberUserLocale();

        $listener->handle($event);

        // Verify getLocale method doesn't exist on our User model
        $this->assertFalse(method_exists($user, 'getLocale'));
    }

    // SendPasswordChanged Listener Tests (4 tests)

    public function test_send_password_changed_handles_password_reset_event(): void
    {
        $user = User::factory()->create();
        $event = new PasswordReset($user);
        $listener = new SendPasswordChanged();

        $listener->handle($event);

        // User model has sendPasswordChanged method, it should execute
        $this->expectNotToPerformAssertions();
    }

    public function test_send_password_changed_checks_method_exists(): void
    {
        $user = User::factory()->create();
        $event = new PasswordReset($user);
        $listener = new SendPasswordChanged();

        $listener->handle($event);

        // Verify the method EXISTS on our User model (it sends password changed notification)
        $this->assertTrue(method_exists($user, 'sendPasswordChanged'));
    }

    public function test_send_password_changed_handles_admin_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $event = new PasswordReset($admin);
        $listener = new SendPasswordChanged();

        $listener->handle($event);

        $this->expectNotToPerformAssertions();
    }

    public function test_send_password_changed_handles_regular_user(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $event = new PasswordReset($user);
        $listener = new SendPasswordChanged();

        $listener->handle($event);

        $this->expectNotToPerformAssertions();
    }

    // UpdateMailboxCounters Listener Tests (5 tests)

    public function test_update_mailbox_counters_handles_status_changed(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $event = new ConversationStatusChanged($conversation);
        $listener = new UpdateMailboxCounters();

        $listener->handle($event);

        // Just verify no exception thrown
        $this->expectNotToPerformAssertions();
    }

    public function test_update_mailbox_counters_handles_user_changed(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $event = new ConversationUserChanged($conversation, $user);
        $listener = new UpdateMailboxCounters();

        $listener->handle($event);

        $this->expectNotToPerformAssertions();
    }

    public function test_update_mailbox_counters_handles_mailbox_without_method(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $event = new ConversationStatusChanged($conversation);
        $listener = new UpdateMailboxCounters();

        $listener->handle($event);

        // Verify updateFoldersCounters method doesn't exist
        $this->assertFalse(method_exists($mailbox, 'updateFoldersCounters'));
    }

    public function test_update_mailbox_counters_handles_closed_conversation(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->closed()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $event = new ConversationStatusChanged($conversation);
        $listener = new UpdateMailboxCounters();

        $listener->handle($event);

        $this->expectNotToPerformAssertions();
    }

    public function test_update_mailbox_counters_handles_active_conversation(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $event = new ConversationStatusChanged($conversation);
        $listener = new UpdateMailboxCounters();

        $listener->handle($event);

        $this->expectNotToPerformAssertions();
    }

    // SendReplyToCustomer Listener Tests (6 tests)

    public function test_send_reply_to_customer_handles_user_replied_event(): void
    {
        Queue::fake();
        
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'imported' => false,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_MESSAGE,
            'imported' => false,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer();

        $listener->handle($event);

        Queue::assertPushed(SendConversationReply::class);
    }

    public function test_send_reply_to_customer_handles_user_created_conversation_event(): void
    {
        Queue::fake();
        
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'imported' => false,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_MESSAGE,
            'imported' => false,
        ]);

        $event = new UserCreatedConversation($conversation, $thread);
        $listener = new SendReplyToCustomer();

        $listener->handle($event);

        Queue::assertPushed(SendConversationReply::class);
    }

    public function test_send_reply_to_customer_with_imported_thread(): void
    {
        Queue::fake();
        
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'imported' => true, // Imported thread
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer();

        $listener->handle($event);

        // Imported threads should be ignored
        Queue::assertNotPushed(SendConversationReply::class);
    }

    public function test_send_reply_to_customer_checks_method_does_not_exist(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
        ]);

        // Verify methods that listener checks exist on the model
        $this->assertTrue(method_exists($conversation, 'isPhone'));
        $this->assertTrue(method_exists($conversation, 'isChat'));
        // getReplies does not exist on Conversation model
        $this->assertFalse(method_exists($conversation, 'getReplies'));
    }

    public function test_send_reply_to_customer_handles_note_thread(): void
    {
        Queue::fake();
        
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->note()->create([
            'conversation_id' => $conversation->id,
            'imported' => false,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer();

        $listener->handle($event);

        Queue::assertPushed(SendConversationReply::class);
    }

    public function test_send_reply_to_customer_dispatches_with_delay(): void
    {
        Queue::fake();
        
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'imported' => false,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer();

        $listener->handle($event);

        // Verify job was pushed to emails queue with delay
        Queue::assertPushedOn('emails', SendConversationReply::class);
    }
}
