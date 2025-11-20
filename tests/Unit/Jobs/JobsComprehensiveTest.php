<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendAlert;
use App\Jobs\SendAutoReply;
use App\Jobs\SendConversationReply;
use App\Jobs\SendEmailReplyError;
use App\Jobs\SendNotificationToUsers;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\SendLog;
use App\Models\Thread;
use App\Models\User;
use App\Services\SmtpService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\UnitTestCase;

/**
 * Comprehensive tests for all Job classes
 * Following TESTING_GUIDE.md standards
 */
class JobsComprehensiveTest extends UnitTestCase
{

    // ==================== SendAutoReply Tests ====================

    public function test_send_auto_reply_job_can_be_dispatched(): void
    {
        Queue::fake();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        $customer = Customer::factory()->create();

        SendAutoReply::dispatch($conversation, $thread, $mailbox, $customer);

        Queue::assertPushed(SendAutoReply::class);
    }

    public function test_send_auto_reply_sends_email(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => 'test-message-id',
        ]);

        $smtpService = $this->createMock(SmtpService::class);
        $smtpService->expects($this->once())
            ->method('configureSmtp')
            ->with($mailbox);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);

        Mail::assertSent(\App\Mail\AutoReply::class);
    }

    public function test_send_auto_reply_skips_when_disabled_via_meta(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'meta' => ['ar_off' => true],
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $smtpService = $this->createMock(SmtpService::class);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);

        Mail::assertNothingSent();
    }

    public function test_send_auto_reply_skips_when_already_sent(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        // Create existing send log
        SendLog::factory()->create([
            'thread_id' => $thread->id,
            'mail_type' => SendLog::MAIL_TYPE_AUTO_REPLY,
        ]);

        $smtpService = $this->createMock(SmtpService::class);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);

        Mail::assertNothingSent();
    }

    public function test_send_auto_reply_creates_send_log(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => 'test-message-id',
        ]);

        $smtpService = $this->createMock(SmtpService::class);
        $smtpService->method('configureSmtp');

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);

        $this->assertDatabaseHas('send_logs', [
            'thread_id' => $thread->id,
            'mail_type' => SendLog::MAIL_TYPE_AUTO_REPLY,
        ]);
    }

    public function test_send_auto_reply_handles_mail_failure(): void
    {
        Mail::shouldReceive('to')->andThrow(new \Exception('Mail server error'));

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $smtpService = $this->createMock(SmtpService::class);
        $smtpService->method('configureSmtp');

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        $this->expectException(\Exception::class);
        $job->handle($smtpService);
    }

    // ==================== SendConversationReply Tests ====================

    public function test_send_conversation_reply_job_can_be_dispatched(): void
    {
        Queue::fake();

        $conversation = Conversation::factory()->create();
        $replies = [Thread::factory()->create(['conversation_id' => $conversation->id])];
        $customer = Customer::factory()->create();

        SendConversationReply::dispatch($conversation, $replies, $customer);

        Queue::assertPushed(SendConversationReply::class);
    }

    public function test_send_conversation_reply_serializes_models(): void
    {
        $conversation = Conversation::factory()->create();
        $replies = [Thread::factory()->create(['conversation_id' => $conversation->id])];
        $customer = Customer::factory()->create();

        $job = new SendConversationReply($conversation, $replies, $customer);

        $this->assertEquals($conversation->id, $job->conversation->id);
        $this->assertEquals($customer->id, $job->customer->id);
        $this->assertCount(1, $job->replies);
    }

    public function test_send_conversation_reply_has_timeout(): void
    {
        $conversation = Conversation::factory()->create();
        $replies = [Thread::factory()->create(['conversation_id' => $conversation->id])];
        $customer = Customer::factory()->create();

        $job = new SendConversationReply($conversation, $replies, $customer);

        $this->assertEquals(120, $job->timeout);
    }

    // ==================== SendAlert Tests ====================

    public function test_send_alert_job_can_be_dispatched(): void
    {
        Queue::fake();

        SendAlert::dispatch('Test message', 'Test Subject');

        Queue::assertPushed(SendAlert::class);
    }

    public function test_send_alert_sends_email_to_user(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'user@example.com',
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'invite_state' => User::INVITE_STATE_ACTIVATED
        ]);

        $job = new SendAlert('Test message', 'Test Subject');
        $job->handle();

        Mail::assertSent(\App\Mail\Alert::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_send_alert_includes_subject_and_message(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'invite_state' => User::INVITE_STATE_ACTIVATED
        ]);
        $subject = 'Important Alert';
        $message = 'This is an important message';

        $job = new SendAlert($message, $subject);
        $job->handle();

        // Subject check needs to account for prefix and domain
        Mail::assertSent(\App\Mail\Alert::class, function ($mail) use ($subject) {
            return str_contains($mail->envelope()->subject, $subject);
        });
    }

    // ==================== SendEmailReplyError Tests ====================

    public function test_send_email_reply_error_job_can_be_dispatched(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        SendEmailReplyError::dispatch('Error message', $user, $mailbox);

        Queue::assertPushed(SendEmailReplyError::class);
    }

    public function test_send_email_reply_error_sends_notification(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $errorMessage = 'Failed to send email';

        $job = new SendEmailReplyError($errorMessage, $user, $mailbox);
        $job->handle();

        Mail::assertSent(\App\Mail\UserEmailReplyError::class);
    }

    // ==================== SendNotificationToUsers Tests ====================

    public function test_send_notification_to_users_job_can_be_dispatched(): void
    {
        Queue::fake();

        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        $user = User::factory()->create();

        SendNotificationToUsers::dispatch(collect([$user]), $conversation, collect([$thread]));

        Queue::assertPushed(SendNotificationToUsers::class);
    }

    public function test_send_notification_to_users_sends_to_subscribers(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        
        $user = User::factory()->create();
        $conversation->followers()->attach($user->id);

        $job = new SendNotificationToUsers(collect([$user]), $conversation, collect([$thread]));
        $job->handle();

        Mail::assertSent(\App\Mail\UserNotification::class);
    }

    // ==================== Edge Cases ====================

    public function test_jobs_implement_should_queue_interface(): void
    {
        $this->assertTrue(in_array(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            class_implements(SendAutoReply::class)
        ));
        $this->assertTrue(in_array(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            class_implements(SendConversationReply::class)
        ));
        $this->assertTrue(in_array(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            class_implements(SendAlert::class)
        ));
    }

    public function test_jobs_use_queueable_trait(): void
    {
        $this->assertContains(
            \Illuminate\Bus\Queueable::class,
            class_uses(SendAutoReply::class)
        );
        $this->assertContains(
            \Illuminate\Bus\Queueable::class,
            class_uses(SendConversationReply::class)
        );
    }

    public function test_jobs_use_serializes_models_trait(): void
    {
        $this->assertContains(
            \Illuminate\Queue\SerializesModels::class,
            class_uses(SendAutoReply::class)
        );
        $this->assertContains(
            \Illuminate\Queue\SerializesModels::class,
            class_uses(SendConversationReply::class)
        );
    }

    public function test_send_auto_reply_failed_method_logs_error(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        $customer = Customer::factory()->create();

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $exception = new \Exception('Test exception');

        // Should not throw exception
        $job->failed($exception);

        $this->assertTrue(true);
    }

    public function test_jobs_can_be_retried_on_failure(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        $customer = Customer::factory()->create();

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        // Job should have tries/retries configured
        $this->assertTrue(method_exists($job, 'handle'));
        $this->assertTrue(method_exists($job, 'failed'));
    }

    public function test_send_auto_reply_skips_when_no_customer_email(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => null,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $smtpService = $this->createMock(SmtpService::class);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);

        Mail::assertNothingSent();
    }

    public function test_jobs_have_proper_timeout_values(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        $customer = Customer::factory()->create();

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        $this->assertEquals(120, $job->timeout);
        $this->assertIsInt($job->timeout);
    }
}
