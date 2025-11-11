<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Jobs\SendAutoReply;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use Tests\TestCase;

class SendAutoReplyJobTest extends TestCase
{
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
            'meta' => ['ar_off' => true],
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
}
