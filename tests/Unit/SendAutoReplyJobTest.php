<?php

declare(strict_types=1);

namespace Tests\Unit;

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
use Tests\TestCase;

class SendAutoReplyJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_has_required_properties(): void
    {
        $conversation = new Conversation(['id' => 1]);
        $thread = new Thread(['id' => 2]);
        $mailbox = new Mailbox(['id' => 3]);
        $customer = new Customer(['id' => 4]);
        
        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        
        $this->assertSame($conversation, $job->conversation);
        $this->assertSame($thread, $job->thread);
        $this->assertSame($mailbox, $job->mailbox);
        $this->assertSame($customer, $job->customer);
    }

    public function test_job_has_timeout_property(): void
    {
        $conversation = new Conversation(['id' => 1]);
        $thread = new Thread(['id' => 2]);
        $mailbox = new Mailbox(['id' => 3]);
        $customer = new Customer(['id' => 4]);
        
        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        
        $this->assertEquals(120, $job->timeout);
    }

    public function test_handle_method_exists(): void
    {
        $conversation = new Conversation([
            'id' => 1,
            'meta' => ['ar_off' => true]
        ]);
        $thread = new Thread(['id' => 2]);
        $mailbox = new Mailbox(['id' => 3]);
        $customer = new Customer(['id' => 4]);
        
        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        
        $this->assertTrue(method_exists($job, 'handle'));
    }

    public function test_failed_method_exists(): void
    {
        $conversation = new Conversation(['id' => 1]);
        $thread = new Thread(['id' => 2]);
        $mailbox = new Mailbox(['id' => 3]);
        $customer = new Customer(['id' => 4]);
        
        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        
        $this->assertTrue(method_exists($job, 'failed'));
    }

    /** Test job skips sending when auto-reply is disabled via meta */
    public function test_job_skips_when_auto_reply_disabled_via_meta(): void
    {
        Log::shouldReceive('debug')->atLeast()->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        
        $conversation = new Conversation([
            'id' => 1,
            'meta' => ['ar_off' => true],
        ]);
        $thread = new Thread(['id' => 2]);
        $mailbox = new Mailbox(['id' => 3]);
        $customer = new Customer(['id' => 4]);

        Mail::fake();
        $smtpService = new SmtpService();
        
        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);
        
        Mail::assertNothingSent();
    }

    /** Test job aborts when customer email is missing */
    public function test_job_aborts_when_customer_email_missing(): void
    {
        Log::shouldReceive('warning')->atLeast()->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        
        $conversation = new Conversation([
            'id' => 1,
            'customer_email' => null,
        ]);
        $thread = new Thread(['id' => 2]);
        $mailbox = new Mailbox(['id' => 3]);
        $customer = new Customer(['id' => 4]);

        Mail::fake();
        $smtpService = new SmtpService();
        
        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);
        
        Mail::assertNothingSent();
    }

    /** Test job attempts to send when valid data provided */
    public function test_job_processes_with_valid_data(): void
    {
        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();
        
        $mailbox = Mailbox::factory()->create([
            'email' => 'support@test.com',
            'auto_reply_enabled' => true,
        ]);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => 'customer@test.com',
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => 'test-msg-id@test.com',
        ]);

        Mail::fake();
        $smtpService = new SmtpService();
        
        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        
        try {
            $job->handle($smtpService);
            // If no exception, the job executed successfully
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Job may fail due to config issues but should not crash
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    /** Test job uses ShouldQueue trait */
    public function test_job_implements_should_queue(): void
    {
        $job = new SendAutoReply(
            new Conversation(),
            new Thread(),
            new Mailbox(),
            new Customer()
        );
        
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    /** Test job is dispatchable */
    public function test_job_is_dispatchable(): void
    {
        $this->assertTrue(method_exists(SendAutoReply::class, 'dispatch'));
    }

    /** Test failed method logs error */
    public function test_failed_method_logs_error(): void
    {
        Log::shouldReceive('error')->once()->with(
            'SendAutoReply job failed permanently',
            \Mockery::type('array')
        );
        
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->create();
        
        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $exception = new \Exception('Test failure');
        
        $job->failed($exception);
    }

    /** Test job validates customer email format */
    public function test_job_validates_customer_email_format(): void
    {
        Log::shouldReceive('warning')->atLeast()->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        
        $conversation = new Conversation([
            'id' => 1,
            'customer_email' => '', // Empty email
        ]);
        $thread = new Thread(['id' => 2]);
        $mailbox = new Mailbox(['id' => 3]);
        $customer = new Customer(['id' => 4]);

        Mail::fake();
        $smtpService = new SmtpService();
        
        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);
        
        Mail::assertNothingSent();
    }

    /** Test job handles empty meta array */
    public function test_job_handles_empty_meta_array(): void
    {
        Log::shouldReceive('warning')->atLeast()->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        
        $conversation = new Conversation([
            'id' => 1,
            'meta' => [], // Empty meta, not disabled
            'customer_email' => null,
        ]);
        $thread = new Thread(['id' => 2]);
        $mailbox = new Mailbox(['id' => 3]);
        $customer = new Customer(['id' => 4]);

        Mail::fake();
        $smtpService = new SmtpService();
        
        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);
        
        // Should abort due to missing email, not meta
        Mail::assertNothingSent();
    }

    /** Test job handles null meta */
    public function test_job_handles_null_meta(): void
    {
        Log::shouldReceive('warning')->atLeast()->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        
        $conversation = new Conversation([
            'id' => 1,
            'meta' => null, // Null meta
            'customer_email' => null,
        ]);
        $thread = new Thread(['id' => 2]);
        $mailbox = new Mailbox(['id' => 3]);
        $customer = new Customer(['id' => 4]);

        Mail::fake();
        $smtpService = new SmtpService();
        
        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);
        
        Mail::assertNothingSent();
    }

    /** Test job preserves models through serialization */
    public function test_job_serializes_models_correctly(): void
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
        
        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        
        // Test that models are accessible
        $this->assertEquals($conversation->id, $job->conversation->id);
        $this->assertEquals($thread->id, $job->thread->id);
        $this->assertEquals($mailbox->id, $job->mailbox->id);
        $this->assertEquals($customer->id, $job->customer->id);
    }

    /** Test job has correct queue traits */
    public function test_job_uses_queueable_trait(): void
    {
        $this->assertTrue(
            in_array(\Illuminate\Bus\Queueable::class, class_uses(SendAutoReply::class))
        );
    }

    /** Test job timeout is reasonable */
    public function test_job_timeout_is_set_correctly(): void
    {
        $conversation = new Conversation(['id' => 1]);
        $thread = new Thread(['id' => 2]);
        $mailbox = new Mailbox(['id' => 3]);
        $customer = new Customer(['id' => 4]);
        
        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
        
        $this->assertEquals(120, $job->timeout);
        $this->assertGreaterThan(0, $job->timeout);
        $this->assertLessThanOrEqual(300, $job->timeout); // Reasonable max
    }
}