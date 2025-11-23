<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\Alert;
use App\Mail\AutoReply;
use App\Mail\ConversationReplyNotification;
use App\Mail\PasswordChanged;
use App\Mail\Test as TestMailable;
use App\Mail\UserEmailReplyError;
use App\Mail\UserInvite;
use App\Mail\UserNotification;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\UnitTestCase;

/**
 * Comprehensive tests for Mail Mailable Classes
 * Following TESTING_GUIDE.md - using test_ prefix, UnitTestCase base class
 */
class MailablesComprehensiveTest extends UnitTestCase
{
    // ===== AUTO_REPLY TESTS =====

    public function test_auto_reply_can_be_created(): void
    {
        $conversation = Conversation::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        
        $mailable = new AutoReply($conversation, $mailbox, $customer);
        
        $this->assertInstanceOf(AutoReply::class, $mailable);
        $this->assertEquals($conversation->id, $mailable->conversation->id);
        $this->assertEquals($mailbox->id, $mailable->mailbox->id);
        $this->assertEquals($customer->id, $mailable->customer->id);
    }

    public function test_auto_reply_has_default_subject(): void
    {
        $conversation = Conversation::factory()->create(['subject' => 'Test Subject']);
        $mailbox = Mailbox::factory()->create(['auto_reply_subject' => null]);
        $customer = Customer::factory()->create();
        
        $mailable = new AutoReply($conversation, $mailbox, $customer);
        $envelope = $mailable->envelope();
        
        $this->assertEquals('Re: Test Subject', $envelope->subject);
    }

    public function test_auto_reply_uses_custom_subject_when_set(): void
    {
        $conversation = Conversation::factory()->create();
        $mailbox = Mailbox::factory()->create(['auto_reply_subject' => 'Custom Auto Reply']);
        $customer = Customer::factory()->create();
        
        $mailable = new AutoReply($conversation, $mailbox, $customer);
        $envelope = $mailable->envelope();
        
        $this->assertEquals('Custom Auto Reply', $envelope->subject);
    }

    public function test_auto_reply_has_headers_property(): void
    {
        $conversation = Conversation::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $headers = ['X-Custom-Header' => 'value'];
        
        $mailable = new AutoReply($conversation, $mailbox, $customer, $headers);
        
        $this->assertEquals($headers, $mailable->headers);
    }

    public function test_auto_reply_can_be_sent(): void
    {
        Mail::fake();
        
        $conversation = Conversation::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        
        Mail::to('customer@example.com')->send(new AutoReply($conversation, $mailbox, $customer));
        
        Mail::assertSent(AutoReply::class);
    }

    // ===== CONVERSATION_REPLY_NOTIFICATION TESTS =====

    public function test_conversation_reply_notification_can_be_created(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create();
        $user = User::factory()->create();
        
        $mailable = new ConversationReplyNotification($conversation, $thread, $user);
        
        $this->assertInstanceOf(ConversationReplyNotification::class, $mailable);
        $this->assertEquals($conversation->id, $mailable->conversation->id);
        $this->assertEquals($thread->id, $mailable->thread->id);
        $this->assertEquals($user->id, $mailable->user->id);
    }

    public function test_conversation_reply_notification_has_subject(): void
    {
        $conversation = Conversation::factory()->create(['subject' => 'Notification Subject']);
        $thread = Thread::factory()->create();
        $user = User::factory()->create();
        
        $mailable = new ConversationReplyNotification($conversation, $thread, $user);
        $envelope = $mailable->envelope();
        
        $this->assertStringContainsString('Notification Subject', $envelope->subject);
    }

    public function test_conversation_reply_notification_can_be_sent(): void
    {
        Mail::fake();
        
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create();
        $user = User::factory()->create();
        
        Mail::to($user->email)->send(new ConversationReplyNotification($conversation, $thread, $user));
        
        Mail::assertSent(ConversationReplyNotification::class);
    }

    // ===== ALERT TESTS =====

    public function test_alert_can_be_created(): void
    {
        $message = 'Alert message';
        $subject = 'Alert subject';
        
        $mailable = new Alert($message, $subject);
        
        $this->assertInstanceOf(Alert::class, $mailable);
        $this->assertEquals($message, $mailable->alert_message);
        $this->assertEquals($subject, $mailable->alert_subject);
    }

    public function test_alert_has_correct_subject(): void
    {
        $mailable = new Alert('Message', 'Test Alert');
        $envelope = $mailable->envelope();
        
        $this->assertStringContainsString('Test Alert', $envelope->subject);
    }

    public function test_alert_can_be_sent(): void
    {
        Mail::fake();
        
        Mail::to('admin@example.com')->send(new Alert('Alert message', 'Alert'));
        
        Mail::assertSent(Alert::class);
    }

    // ===== PASSWORD_CHANGED TESTS =====

    public function test_password_changed_can_be_created(): void
    {
        $user = User::factory()->create();
        
        $mailable = new PasswordChanged($user);
        
        $this->assertInstanceOf(PasswordChanged::class, $mailable);
        $this->assertEquals($user->id, $mailable->user->id);
    }

    public function test_password_changed_has_subject(): void
    {
        $user = User::factory()->create();
        
        $mailable = new PasswordChanged($user);
        $envelope = $mailable->envelope();
        
        $this->assertNotEmpty($envelope->subject);
        $this->assertStringContainsString('Password', $envelope->subject);
    }

    public function test_password_changed_can_be_sent(): void
    {
        Mail::fake();
        
        $user = User::factory()->create();
        
        Mail::to($user->email)->send(new PasswordChanged($user));
        
        Mail::assertSent(PasswordChanged::class);
    }

    // ===== USER_INVITE TESTS =====

    public function test_user_invite_can_be_created(): void
    {
        $user = User::factory()->create();
        $password = 'test-password';
        
        $mailable = new UserInvite($user, $password);
        
        $this->assertInstanceOf(UserInvite::class, $mailable);
        $this->assertEquals($user->id, $mailable->user->id);
        $this->assertEquals($password, $mailable->password);
    }

    public function test_user_invite_has_subject(): void
    {
        $user = User::factory()->create();
        
        $mailable = new UserInvite($user, 'password');
        $envelope = $mailable->envelope();
        
        $this->assertNotEmpty($envelope->subject);
        $this->assertStringContainsString('Welcome', $envelope->subject);
    }

    public function test_user_invite_can_be_sent(): void
    {
        Mail::fake();
        
        $user = User::factory()->create();
        
        Mail::to($user->email)->send(new UserInvite($user, 'password123'));
        
        Mail::assertSent(UserInvite::class);
    }

    // ===== TEST_MAILABLE TESTS =====

    public function test_test_mailable_can_be_created(): void
    {
        $mailbox = Mailbox::factory()->create();
        $mailable = new TestMailable($mailbox);
        
        $this->assertInstanceOf(TestMailable::class, $mailable);
    }

    public function test_test_mailable_has_subject(): void
    {
        $mailbox = Mailbox::factory()->create();
        $mailable = new TestMailable($mailbox);
        $envelope = $mailable->envelope();
        
        $this->assertNotEmpty($envelope->subject);
    }

    public function test_test_mailable_can_be_sent(): void
    {
        Mail::fake();
        
        $mailbox = Mailbox::factory()->create();
        Mail::to('test@example.com')->send(new TestMailable($mailbox));
        
        Mail::assertSent(TestMailable::class);
    }

    // ===== USER_EMAIL_REPLY_ERROR TESTS =====

    public function test_user_email_reply_error_can_be_created(): void
    {
        $user = User::factory()->create();
        
        $mailable = new UserEmailReplyError($user);
        
        $this->assertInstanceOf(UserEmailReplyError::class, $mailable);
        $this->assertEquals($user->id, $mailable->user->id);
    }

    public function test_user_email_reply_error_has_subject(): void
    {
        $user = User::factory()->create();
        
        $mailable = new UserEmailReplyError($user);
        $envelope = $mailable->envelope();
        
        $this->assertNotEmpty($envelope->subject);
    }

    public function test_user_email_reply_error_can_be_sent(): void
    {
        Mail::fake();
        
        $user = User::factory()->create();
        
        Mail::to($user->email)->send(new UserEmailReplyError($user));
        
        Mail::assertSent(UserEmailReplyError::class);
    }

    // ===== USER_NOTIFICATION TESTS =====

    public function test_user_notification_can_be_created(): void
    {
        $conversation = Conversation::factory()->create();
        $threads = Thread::factory()->count(2)->create();
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        
        $mailable = new UserNotification($user, $conversation, $threads, $mailbox, [], []);
        
        $this->assertInstanceOf(UserNotification::class, $mailable);
        $this->assertEquals($conversation->id, $mailable->conversation->id);
        $this->assertCount(2, $mailable->threads);
        $this->assertEquals($user->id, $mailable->user->id);
    }

    public function test_user_notification_has_subject(): void
    {
        $conversation = Conversation::factory()->create(['subject' => 'Test Notification']);
        $threads = Thread::factory()->count(1)->create();
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        
        $mailable = new UserNotification($user, $conversation, $threads, $mailbox, [], []);
        $envelope = $mailable->envelope();
        
        $this->assertStringContainsString('Test Notification', $envelope->subject);
    }

    public function test_user_notification_can_be_sent(): void
    {
        Mail::fake();
        
        $conversation = Conversation::factory()->create();
        $threads = Thread::factory()->count(1)->create();
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        
        Mail::to($user->email)->send(new UserNotification($user, $conversation, $threads, $mailbox, [], []));
        
        Mail::assertSent(UserNotification::class);
    }

    public function test_user_notification_with_multiple_threads(): void
    {
        $conversation = Conversation::factory()->create();
        $threads = Thread::factory()->count(5)->create();
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        
        $mailable = new UserNotification($user, $conversation, $threads, $mailbox, [], []);
        
        $this->assertCount(5, $mailable->threads);
    }

    // ===== EDGE CASES =====

    public function test_auto_reply_with_empty_headers(): void
    {
        $conversation = Conversation::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        
        $mailable = new AutoReply($conversation, $mailbox, $customer, []);
        
        $this->assertEquals([], $mailable->headers);
    }

    public function test_alert_with_long_message(): void
    {
        $longMessage = str_repeat('Alert message. ', 100);
        
        $mailable = new Alert($longMessage, 'Subject');
        
        $this->assertEquals($longMessage, $mailable->alert_message);
    }

    public function test_user_invite_with_complex_password(): void
    {
        $user = User::factory()->create();
        $complexPassword = 'P@ssw0rd!#$%^&*()_+-=[]{}|;:,.<>?';
        
        $mailable = new UserInvite($user, $complexPassword);
        
        $this->assertEquals($complexPassword, $mailable->password);
    }

    public function test_mailables_are_queueable(): void
    {
        $conversation = Conversation::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        
        $mailable = new AutoReply($conversation, $mailbox, $customer);
        
        $this->assertObjectHasProperty('connection', $mailable);
        $this->assertObjectHasProperty('queue', $mailable);
    }

    public function test_mailables_serialize_models(): void
    {
        $user = User::factory()->create();
        
        $mailable = new PasswordChanged($user);
        $serialized = serialize($mailable);
        $unserialized = unserialize($serialized);
        
        $this->assertInstanceOf(PasswordChanged::class, $unserialized);
        $this->assertEquals($user->id, $unserialized->user->id);
    }

    public function test_multiple_mailables_can_be_sent_in_sequence(): void
    {
        Mail::fake();
        
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create();
        
        Mail::to($user->email)->send(new PasswordChanged($user));
        Mail::to($user->email)->send(new UserInvite($user, 'password'));
        Mail::to($user->email)->send(new ConversationReplyNotification($conversation, $thread, $user));
        
        Mail::assertSent(PasswordChanged::class);
        Mail::assertSent(UserInvite::class);
        Mail::assertSent(ConversationReplyNotification::class);
    }

    public function test_auto_reply_subject_with_special_characters(): void
    {
        $conversation = Conversation::factory()->create(['subject' => 'Test & <Special> "Subject"']);
        $mailbox = Mailbox::factory()->create(['auto_reply_subject' => null]);
        $customer = Customer::factory()->create();
        
        $mailable = new AutoReply($conversation, $mailbox, $customer);
        $envelope = $mailable->envelope();
        
        $this->assertStringContainsString('Test & <Special> "Subject"', $envelope->subject);
    }

    public function test_user_notification_with_empty_threads_collection(): void
    {
        $conversation = Conversation::factory()->create();
        $threads = collect([]);
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        
        $mailable = new UserNotification($user, $conversation, $threads, $mailbox, [], []);
        
        $this->assertCount(0, $mailable->threads);
    }
}
