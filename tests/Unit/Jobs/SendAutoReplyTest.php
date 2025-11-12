<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendAutoReply;
use App\Mail\AutoReply;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\SendLog;
use App\Models\Thread;
use App\Services\SmtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendAutoReplyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function job_can_be_instantiated(): void
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
        $this->assertEquals($customer->id, $job->customer->id);
    }

    #[Test]
    public function job_aborts_when_auto_reply_disabled_via_meta(): void
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

    #[Test]
    public function job_aborts_when_no_customer_email(): void
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

    #[Test]
    public function job_sends_auto_reply_email(): void
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

        Mail::assertSent(AutoReply::class, function ($mail) use ($customer) {
            return $mail->hasTo('customer@example.com');
        });

        Log::shouldHaveReceived('info')
            ->with('Auto-reply email sent successfully', \Mockery::type('array'))
            ->once();
    }

    #[Test]
    public function job_creates_send_log_on_success(): void
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

        $this->assertDatabaseHas('send_log', [
            'thread_id' => $thread->id,
            'email' => 'customer@example.com',
            'mail_type' => 3, // SendLog::MAIL_TYPE_AUTO_REPLY
            'status' => 1, // SendLog::STATUS_ACCEPTED
            'customer_id' => $customer->id,
        ]);
    }

    #[Test]
    public function job_generates_correct_message_id(): void
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

    #[Test]
    public function job_configures_smtp_for_mailbox(): void
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

    #[Test]
    public function job_can_be_queued(): void
    {
        Queue::fake();

        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();

        SendAutoReply::dispatch($conversation, $thread, $mailbox, $customer);

        Queue::assertPushed(SendAutoReply::class);
    }

    #[Test]
    public function job_has_timeout_configured(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        $this->assertEquals(120, $job->timeout);
    }

    #[Test]
    public function job_respects_ar_off_meta_flag(): void
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
        $this->assertDatabaseMissing('send_log', [
            'thread_id' => $thread->id,
        ]);
    }
}
