<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendEmailReplyError;
use App\Models\Mailbox;
use App\Models\SendLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\UnitTestCase;

class SendEmailReplyErrorTest extends UnitTestCase
{

    public function test_job_has_required_properties(): void
    {
        $from = 'test@example.com';
        $user = User::factory()->make(['id' => 1]);
        $mailbox = Mailbox::factory()->make(['id' => 2]);

        $job = new SendEmailReplyError($from, $user, $mailbox);

        $this->assertEquals($from, $job->from);
        $this->assertSame($user, $job->user);
        $this->assertSame($mailbox, $job->mailbox);
    }

    public function test_job_has_timeout_property(): void
    {
        $from = 'test@example.com';
        $user = User::factory()->make(['id' => 1]);
        $mailbox = Mailbox::factory()->make(['id' => 2]);

        $job = new SendEmailReplyError($from, $user, $mailbox);

        $this->assertEquals(120, $job->timeout);
    }

    public function test_handle_method_exists(): void
    {
        $from = 'test@example.com';
        $user = User::factory()->make(['id' => 1]);
        $mailbox = Mailbox::factory()->make(['id' => 2]);

        $job = new SendEmailReplyError($from, $user, $mailbox);

        $this->assertTrue(method_exists($job, 'handle'));
    }

    #[Test]
    public function job_uses_should_queue_interface(): void
    {
        $job = new SendEmailReplyError('test@example.com', User::factory()->create(), Mailbox::factory()->create());

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    #[Test]
    public function job_has_dispatchable_trait(): void
    {
        $traits = class_uses(SendEmailReplyError::class);

        $this->assertContains('Illuminate\Foundation\Bus\Dispatchable', $traits);
    }

    #[Test]
    public function job_has_queueable_trait(): void
    {
        $traits = class_uses(SendEmailReplyError::class);

        $this->assertContains('Illuminate\Bus\Queueable', $traits);
    }

    #[Test]
    public function job_logs_notification_attempt(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Sending email reply error notification', \Mockery::type('array'));

        Log::shouldReceive('error')->never();

        Mail::fake();

        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        
        $job = new SendEmailReplyError('test@example.com', $user, $mailbox);
        $job->handle();
    }

    #[Test]
    public function job_creates_send_log_on_success(): void
    {
        Log::shouldReceive('info')->once();
        Mail::fake();

        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        
        $job = new SendEmailReplyError('success@example.com', $user, $mailbox);
        $job->handle();

        $this->assertDatabaseHas('send_logs', [
            'email' => 'success@example.com',
            'user_id' => $user->id,
            'mail_type' => SendLog::MAIL_TYPE_WRONG_USER_EMAIL_MESSAGE,
            'status' => SendLog::STATUS_ACCEPTED,
        ]);
    }

    #[Test]
    public function job_creates_send_log_with_null_thread_and_message(): void
    {
        Log::shouldReceive('info')->once();
        Mail::fake();

        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        
        $job = new SendEmailReplyError('test@example.com', $user, $mailbox);
        $job->handle();

        $sendLog = SendLog::where('email', 'test@example.com')->first();

        $this->assertNull($sendLog->thread_id);
        $this->assertNull($sendLog->message_id);
    }

    #[Test]
    public function job_logs_error_on_exception(): void
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')
            ->once()
            ->with('Error sending email reply error notification', \Mockery::type('array'));

        // Force an exception by mocking Mail to throw
        Mail::shouldReceive('to')->andThrow(new \Exception('Mail error'));

        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        
        $job = new SendEmailReplyError('error@example.com', $user, $mailbox);

        try {
            $job->handle();
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertTrue(true);
    }

    #[Test]
    public function job_creates_send_log_with_error_status_on_exception(): void
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        Mail::shouldReceive('to')->andThrow(new \Exception('Test error message'));

        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        
        $job = new SendEmailReplyError('exception@example.com', $user, $mailbox);

        try {
            $job->handle();
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertDatabaseHas('send_logs', [
            'email' => 'exception@example.com',
            'status' => SendLog::STATUS_SEND_ERROR,
            'status_message' => 'Test error message',
        ]);
    }

    #[Test]
    public function job_rethrows_exception_after_logging(): void
    {
        $this->expectException(\Exception::class);

        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        Mail::shouldReceive('to')->andThrow(new \Exception('Test error'));

        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        
        $job = new SendEmailReplyError('test@example.com', $user, $mailbox);
        $job->handle();
    }

    #[Test]
    public function job_uses_correct_mail_type_constant(): void
    {
        Log::shouldReceive('info')->once();
        Mail::fake();

        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        
        $job = new SendEmailReplyError('test@example.com', $user, $mailbox);
        $job->handle();

        $sendLog = SendLog::where('email', 'test@example.com')->first();

        $this->assertEquals(SendLog::MAIL_TYPE_WRONG_USER_EMAIL_MESSAGE, $sendLog->mail_type);
    }

    #[Test]
    public function job_can_be_dispatched(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        SendEmailReplyError::dispatch('dispatch@example.com', $user, $mailbox);

        $this->assertTrue(true);
    }
}
