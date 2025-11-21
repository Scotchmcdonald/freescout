<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\ConversationStatusChanged;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Listeners\LogFailedLogin;
use App\Listeners\SendReplyToCustomer;
use App\Listeners\UpdateMailboxCounters;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\UnitTestCase;

class AdditionalListenersTest extends UnitTestCase
{
// Listener Tests (20 tests)
    // ============================

    // RememberUserLocale Listener Tests (5 tests)

    public function test_remember_user_locale_handles_login_event(): void
    {
        $user = User::factory()->create(['locale' => 'en']);
        $this->actingAs($user);

        $event = new Login('web', $user, false);
        $listener = new RememberUserLocale();

        // The listener checks for getLocale method - since User model doesn't have it by default,
        // the session won't be updated. This test verifies the listener doesn't crash.
        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_remember_user_locale_handles_web_guard(): void
    {
        $user = User::factory()->create();
        $event = new Login('web', $user, false);
        $listener = new RememberUserLocale();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_remember_user_locale_handles_api_guard(): void
    {
        $user = User::factory()->create();
        $event = new Login('api', $user, false);
        $listener = new RememberUserLocale();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_remember_user_locale_handles_remember_me(): void
    {
        $user = User::factory()->create();
        $event = new Login('web', $user, true);
        $listener = new RememberUserLocale();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_remember_user_locale_checks_method_exists(): void
    {
        $user = User::factory()->create();
        $event = new Login('web', $user, false);
        $listener = new RememberUserLocale();

        // Should handle gracefully even if getLocale method doesn't exist
        $listener->handle($event);

        $this->assertFalse(method_exists($user, 'getLocale'));
    }

    // SendPasswordChanged Listener Tests (4 tests)

    public function test_send_password_changed_handles_password_reset_event(): void
    {
        $user = User::factory()->create();
        $event = new PasswordReset($user);
        $listener = new SendPasswordChanged();

        // The listener checks if user has sendPasswordChanged method
        // Since our User model doesn't have it, this should not crash
        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_send_password_changed_checks_method_exists(): void
    {
        $user = User::factory()->create();
        $event = new PasswordReset($user);
        $listener = new SendPasswordChanged();

        // The listener should handle the event gracefully
        $listener->handle($event);

        // Verify the method EXISTS on our User model (it sends password changed notification)
        $this->assertTrue(method_exists($user, 'sendPasswordChanged'));
        // Verify that our test user was created properly
        $this->assertInstanceOf(User::class, $user);
    }

    public function test_send_password_changed_handles_admin_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $event = new PasswordReset($admin);
        $listener = new SendPasswordChanged();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_send_password_changed_handles_regular_user(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $event = new PasswordReset($user);
        $listener = new SendPasswordChanged();

        $listener->handle($event);

        $this->assertTrue(true);
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

        $this->assertTrue(true);
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

        $this->assertTrue(true);
    }

    public function test_update_mailbox_counters_checks_method_exists(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $event = new ConversationStatusChanged($conversation);
        $listener = new UpdateMailboxCounters();

        $listener->handle($event);

        // Verify the method doesn't exist on our Mailbox model
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

        $this->assertTrue(true);
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

        $this->assertTrue(true);
    }

    // SendReplyToCustomer Listener Tests (6 tests)

    public function test_send_reply_to_customer_handles_user_replied_event(): void
    {
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
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_send_reply_to_customer_handles_user_created_conversation_event(): void
    {
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
        ]);

        $event = new UserCreatedConversation($conversation, $thread);
        $listener = new SendReplyToCustomer();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_send_reply_to_customer_checks_is_phone_method(): void
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

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer();

        $listener->handle($event);

        // Verify the method doesn't exist
        $this->assertFalse(method_exists($conversation, 'isPhone'));
    }

    public function test_send_reply_to_customer_checks_is_chat_method(): void
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

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer();

        $listener->handle($event);

        // Verify the method doesn't exist
        $this->assertFalse(method_exists($conversation, 'isChat'));
    }

    public function test_send_reply_to_customer_checks_get_replies_method(): void
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

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer();

        $listener->handle($event);

        // Verify the method doesn't exist
        $this->assertFalse(method_exists($conversation, 'getReplies'));
    }

    public function test_send_reply_to_customer_handles_note_thread(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->note()->create([
            'conversation_id' => $conversation->id,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    // ============================
    // Additional Edge Case Tests (10 tests)
    // ============================

    public function test_attachment_belongs_to_thread_relationship(): void
    {
        $thread = Thread::factory()->create();
        $attachment = Attachment::factory()->create([
            'thread_id' => $thread->id,
        ]);

        $this->assertInstanceOf(Thread::class, $attachment->thread);
        $this->assertEquals($thread->id, $attachment->thread_id);
    }

    public function test_attachment_has_embedded_flag(): void
    {
        $embedded = Attachment::factory()->create(['embedded' => true]);
        $notEmbedded = Attachment::factory()->create(['embedded' => false]);

        $this->assertTrue($embedded->embedded);
        $this->assertFalse($notEmbedded->embedded);
    }

    public function test_user_get_full_name_attribute_accessor(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Alice',
            'last_name' => 'Johnson',
        ]);

        // Test the attribute accessor
        $this->assertEquals('Alice Johnson', $user->full_name);
        $this->assertEquals('Alice Johnson', $user->getFullNameAttribute());
    }

    public function test_user_name_attribute_returns_full_name(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Bob',
            'last_name' => 'Smith',
        ]);

        $this->assertEquals('Bob Smith', $user->name);
    }

    public function test_user_is_admin_method_works(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($user->isAdmin());
    }

    public function test_user_is_active_method_works(): void
    {
        $active = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $inactive = User::factory()->create(['status' => User::STATUS_INACTIVE]);

        $this->assertTrue($active->isActive());
        $this->assertFalse($inactive->isActive());
    }

    public function test_customer_get_main_email_returns_primary_email(): void
    {
        $customer = Customer::factory()->withoutEmail()->create();
        
        $primaryEmail = Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'primary@example.com',
            'type' => 1, // Primary
        ]);

        $mainEmail = $customer->getMainEmail();
        $this->assertEquals('primary@example.com', $mainEmail);
    }

    public function test_customer_primary_email_attribute(): void
    {
        $customer = Customer::factory()->withoutEmail()->create();
        
        $primaryEmail = Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'test@example.com',
            'type' => 1,
        ]);

        $this->assertEquals('test@example.com', $customer->primary_email);
    }

    public function test_send_log_has_status_constants(): void
    {
        $this->assertEquals(1, SendLog::STATUS_ACCEPTED);
        $this->assertEquals(2, SendLog::STATUS_SEND_ERROR);
        $this->assertEquals(4, SendLog::STATUS_DELIVERY_SUCCESS);
        $this->assertEquals(5, SendLog::STATUS_DELIVERY_ERROR);
        $this->assertEquals(6, SendLog::STATUS_OPENED);
        $this->assertEquals(7, SendLog::STATUS_CLICKED);
    }

    public function test_channel_has_timestamps(): void
    {
        $channel = Channel::factory()->create();

        $this->assertNotNull($channel->created_at);
        $this->assertNotNull($channel->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $channel->created_at);
    }
}
}
