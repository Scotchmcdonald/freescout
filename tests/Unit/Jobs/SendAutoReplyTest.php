<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendAutoReply;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendAutoReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_can_be_instantiated(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        $this->assertInstanceOf(SendAutoReply::class, $job);
    }

    public function test_job_has_handle_method(): void
    {
        $this->assertTrue(method_exists(SendAutoReply::class, 'handle'));
    }

    public function test_job_requires_conversation_thread_mailbox_and_customer(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        $this->assertEquals($conversation->id, $job->conversation->id);
        $this->assertEquals($thread->id, $job->thread->id);
        $this->assertEquals($mailbox->id, $job->mailbox->id);
        $this->assertEquals($customer->id, $job->customer->id);
    }
}
