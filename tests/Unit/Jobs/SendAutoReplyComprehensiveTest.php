<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendAutoReply;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendAutoReplyComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_stores_conversation_correctly(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        $this->assertEquals($conversation->id, $job->conversation->id);
        $this->assertEquals($conversation->mailbox_id, $job->conversation->mailbox_id);
    }

    public function test_job_stores_thread_correctly(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_CUSTOMER,
        ]);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        $this->assertEquals($thread->id, $job->thread->id);
        $this->assertEquals($thread->type, $job->thread->type);
    }

    public function test_job_stores_mailbox_correctly(): void
    {
        $mailbox = Mailbox::factory()->create(['name' => 'Support Mailbox']);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        $this->assertEquals($mailbox->id, $job->mailbox->id);
        $this->assertEquals('Support Mailbox', $job->mailbox->name);
    }

    public function test_job_stores_customer_correctly(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        $this->assertEquals($customer->id, $job->customer->id);
        $this->assertEquals('customer@example.com', $job->customer->email);
    }

    public function test_job_can_be_dispatched_to_queue(): void
    {
        Queue::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        SendAutoReply::dispatch($conversation, $thread, $mailbox, $customer);

        Queue::assertPushed(SendAutoReply::class);
    }

    public function test_job_has_public_conversation_property(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        $reflection = new \ReflectionClass($job);
        $property = $reflection->getProperty('conversation');

        $this->assertTrue($property->isPublic());
    }

    public function test_job_has_public_thread_property(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        $reflection = new \ReflectionClass($job);
        $property = $reflection->getProperty('thread');

        $this->assertTrue($property->isPublic());
    }

    public function test_job_has_public_mailbox_property(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        $reflection = new \ReflectionClass($job);
        $property = $reflection->getProperty('mailbox');

        $this->assertTrue($property->isPublic());
    }

    public function test_job_has_public_customer_property(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        $reflection = new \ReflectionClass($job);
        $property = $reflection->getProperty('customer');

        $this->assertTrue($property->isPublic());
    }
}
