<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Jobs\SendAutoReplyJob as SendAutoReply;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use Tests\PureUnitTestCase;

class SendAutoReplyJobTest extends PureUnitTestCase
{
    public function test_job_has_required_properties(): void
    {
        $conversation = new Conversation(['id' => 1]);
        $thread = new Thread(['id' => 2]);
        $mailbox = new Mailbox(['id' => 3]);
        $senderInfo = ['email' => 'customer@example.com', 'name' => 'Test Customer'];

        $job = new SendAutoReply($conversation, $thread, $mailbox, $senderInfo);

        $this->assertSame($conversation, $job->conversation);
        $this->assertSame($thread, $job->thread);
        $this->assertSame($mailbox, $job->mailbox);
        $this->assertIsArray($job->senderInfo);
        $this->assertArrayHasKey('email', $job->senderInfo);
        $this->assertArrayHasKey('name', $job->senderInfo);
    }

    public function test_job_has_timeout_property(): void
    {
        $conversation = new Conversation(['id' => 1]);
        $thread = new Thread(['id' => 2]);
        $mailbox = new Mailbox(['id' => 3]);
        $senderInfo = ['email' => 'customer@example.com', 'name' => 'Test Customer'];

        $job = new SendAutoReply($conversation, $thread, $mailbox, $senderInfo);

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
        $senderInfo = ['email' => 'customer@example.com', 'name' => 'Test Customer'];

        $job = new SendAutoReply($conversation, $thread, $mailbox, $senderInfo);

        $this->assertTrue(method_exists($job, 'handle'));
    }

    public function test_failed_method_exists(): void
    {
        $conversation = new Conversation(['id' => 1]);
        $thread = new Thread(['id' => 2]);
        $mailbox = new Mailbox(['id' => 3]);
        $senderInfo = ['email' => 'customer@example.com', 'name' => 'Test Customer'];

        $job = new SendAutoReply($conversation, $thread, $mailbox, $senderInfo);

        $this->assertTrue(method_exists($job, 'failed'));
    }

    public function test_constructor_maps_customer_model_without_db(): void
    {
        $conversation = new Conversation(['id' => 1]);
        $thread = new Thread(['id' => 2]);
        $mailbox = new Mailbox(['id' => 3]);

        $customer = \Mockery::mock(Customer::class);
        $customer->shouldReceive('getMainEmail')->once()->andReturn('customer.main@example.com');
        $customer->shouldReceive('getFullName')->once()->andReturn('Mapped Customer');

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        $this->assertSame($customer, $job->customer);
        $this->assertSame([
            'email' => 'customer.main@example.com',
            'name' => 'Mapped Customer',
        ], $job->senderInfo);
    }
}
