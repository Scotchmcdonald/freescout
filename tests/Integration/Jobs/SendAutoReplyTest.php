<?php

declare(strict_types=1);

namespace Tests\Integration\Jobs;

use App\Jobs\SendAutoReplyJob as SendAutoReply;
use App\Mail\AutoReply;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\SendLog;
use App\Models\Thread;
use App\Services\SmtpService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\IntegrationTestCase;

class SendAutoReplyTest extends IntegrationTestCase
{
    public function test_job_can_be_instantiated(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        $this->assertInstanceOf(SendAutoReply::class, $job);
        $this->assertEquals($conversation->id, $job->conversation->id);
        $this->assertEquals($thread->id, $job->thread->id);
        $this->assertEquals($mailbox->id, $job->mailbox->id);
        $this->assertIsArray($job->senderInfo);
        $this->assertEquals($customer->getMainEmail(), $job->senderInfo['email']);
    }

    public function test_job_aborts_when_auto_reply_disabled_via_meta(): void
    {
        Mail::fake();
        Log::spy();

        $conversation = Conversation::factory()->create([
            'meta' => ['ar_off' => true],
            'customer_email' => 'customer@example.com',
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();

        $smtpService = $this->createMock(SmtpService::class);
        $smtpService->expects($this->never())->method('configureSmtp');

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);

        Mail::assertNothingSent();
        Log::shouldHaveReceived('debug')
            ->with('Auto-reply disabled via meta', \Mockery::type('array'))
            ->once();
    }

    public function test_job_aborts_when_no_customer_email(): void
    {
        Mail::fake();
        Log::spy();

        $conversation = Conversation::factory()->create([
            'customer_email' => null,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();

        $smtpService = $this->createMock(SmtpService::class);
        $smtpService->expects($this->never())->method('configureSmtp');

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);

        Mail::assertNothingSent();
        Log::shouldHaveReceived('warning')
            ->with('SendAutoReply job aborted: no customer email', \Mockery::type('array'))
            ->once();
    }

    public function test_job_sends_auto_reply_email(): void
    {
        Mail::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create([
            'email' => 'support@example.com',
        ]);
        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => 'customer@example.com',
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => 'original-message-id@example.com',
        ]);

        $smtpService = $this->createMock(SmtpService::class);
        $smtpService->expects($this->once())->method('configureSmtp')->with($mailbox);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);

        Mail::assertSent(AutoReply::class, function ($mail) {
            return $mail->hasTo('customer@example.com');
        });

        Log::shouldHaveReceived('info')
            ->with('Auto-reply email sent successfully', \Mockery::type('array'))
            ->once();
    }

    public function test_job_creates_send_log_on_success(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => 'customer@example.com',
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $smtpService = $this->createMock(SmtpService::class);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);

        $this->assertDatabaseHas('send_logs', [
            'thread_id' => $thread->id,
            'email' => 'customer@example.com',
            'mail_type' => 3, // SendLog::MAIL_TYPE_AUTO_REPLY
            'status' => 1, // SendLog::STATUS_ACCEPTED
        ]);
    }

    public function test_job_generates_correct_message_id(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_email' => 'customer@example.com',
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'id' => 123,
        ]);

        $smtpService = $this->createMock(SmtpService::class);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);

        $sendLog = SendLog::where('thread_id', $thread->id)->first();
        $this->assertNotNull($sendLog);
        $this->assertStringStartsWith('auto-reply-123-', $sendLog->message_id);
        $this->assertStringContainsString('@example.com', $sendLog->message_id);
    }

    public function test_job_configures_smtp_for_mailbox(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
        ]);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_email' => 'customer@example.com',
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $smtpService = $this->createMock(SmtpService::class);
        $smtpService->expects($this->once())
            ->method('configureSmtp')
            ->with($this->equalTo($mailbox));

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);
    }

    public function test_job_can_be_queued(): void
    {
        Queue::fake();

        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();

        SendAutoReply::dispatch($conversation, $thread, $mailbox, $customer);

        Queue::assertPushed(SendAutoReply::class);
    }

    public function test_job_has_timeout_configured(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        $this->assertEquals(120, $job->timeout);
    }

    public function test_job_respects_ar_off_meta_flag(): void
    {
        Mail::fake();

        $conversation = Conversation::factory()->create([
            'meta' => ['ar_off' => 1],
            'customer_email' => 'customer@example.com',
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();

        $smtpService = $this->createMock(SmtpService::class);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('send_logs', [
            'thread_id' => $thread->id,
        ]);
    }

    public function test_job_skips_if_already_sent_for_thread(): void
    {
        Mail::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_email' => 'customer@example.com',
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        // Create existing send log
        SendLog::create([
            'thread_id' => $thread->id,
            'message_id' => 'previous-auto-reply@example.com',
            'email' => 'customer@example.com',
            'mail_type' => 3, // SendLog::MAIL_TYPE_AUTO_REPLY
            'status' => 1,
        ]);

        $smtpService = $this->createMock(SmtpService::class);
        $smtpService->expects($this->never())->method('configureSmtp');

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);

        Mail::assertNothingSent();
        Log::shouldHaveReceived('debug')
            ->with('Auto-reply already sent for this thread', \Mockery::type('array'))
            ->once();
    }

    public function test_job_handles_email_sending_exception(): void
    {
        // Do NOT call Mail::fake() here as we need to mock the exception behavior
        Log::spy();

        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => 'customer@example.com',
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $smtpService = $this->createMock(SmtpService::class);
        $smtpService->expects($this->once())->method('configureSmtp');

        // Mock Mail facade to throw exception
        Mail::shouldReceive('to')
            ->andReturnSelf();
        Mail::shouldReceive('send')
            ->andThrow(new \Exception('SMTP Error'));

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        try {
            $job->handle($smtpService);
        } catch (\Exception $e) {
            $this->assertEquals('SMTP Error', $e->getMessage());
        }

        // Should log error
        Log::shouldHaveReceived('error')
            ->with('SendAutoReply job failed', \Mockery::type('array'))
            ->once();

        // Should create send log with error status
        $this->assertDatabaseHas('send_logs', [
            'thread_id' => $thread->id,
            'mail_type' => 3,
            'status' => 2, // SendLog::STATUS_SEND_ERROR
        ]);
    }

    public function test_job_handles_empty_string_customer_email(): void
    {
        Mail::fake();
        Log::spy();

        $conversation = Conversation::factory()->create([
            'customer_email' => '',
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();

        $smtpService = $this->createMock(SmtpService::class);
        $smtpService->expects($this->never())->method('configureSmtp');

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);

        Mail::assertNothingSent();
        Log::shouldHaveReceived('warning')
            ->with('SendAutoReply job aborted: no customer email', \Mockery::type('array'))
            ->once();
    }

    public function test_job_generates_message_id_with_localhost_fallback(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create(['email' => 'no-at-symbol']);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_email' => 'customer@example.com',
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'id' => 999,
        ]);

        $smtpService = $this->createMock(SmtpService::class);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);

        $sendLog = SendLog::where('thread_id', $thread->id)->first();
        $this->assertNotNull($sendLog);
        $this->assertStringContainsString('@localhost', $sendLog->message_id);
    }

    public function test_job_includes_headers_in_auto_reply(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_email' => 'customer@example.com',
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => 'original-msg-123@example.com',
        ]);

        $smtpService = $this->createMock(SmtpService::class);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);

        Mail::assertSent(AutoReply::class, function ($mail) {
            return isset($mail->headers['In-Reply-To']) &&
                   isset($mail->headers['References']);
        });
    }
}
