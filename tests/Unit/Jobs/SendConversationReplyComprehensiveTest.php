<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendConversationReply;
use App\Models\Conversation;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Tests\UnitTestCase;

class SendConversationReplyComprehensiveTest extends UnitTestCase
{

    public function test_job_stores_conversation_correctly(): void
    {
        $customer = \App\Models\Customer::factory()->create();
        $conversation = Conversation::factory()->create(['subject' => 'Test Subject']);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendConversationReply($conversation, $thread);

        $this->assertEquals($conversation->id, $job->conversation->id);
        $this->assertEquals('Test Subject', $job->conversation->subject);
    }

    public function test_job_stores_thread_correctly(): void
    {
        $customer = \App\Models\Customer::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'body' => 'Test reply body',
        ]);

        $job = new SendConversationReply($conversation, $thread);

        $this->assertEquals($thread->id, $job->thread->id);
        $this->assertEquals('Test reply body', $job->thread->body);
    }

    public function test_job_can_be_dispatched_to_queue(): void
    {
        Queue::fake();

        $customer = \App\Models\Customer::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        SendConversationReply::dispatch($conversation, $thread);

        Queue::assertPushed(SendConversationReply::class);
    }

    public function test_job_has_public_conversation_property(): void
    {
        $customer = \App\Models\Customer::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendConversationReply($conversation, $thread);

        $reflection = new \ReflectionClass($job);
        $property = $reflection->getProperty('conversation');

        $this->assertTrue($property->isPublic());
    }

    public function test_job_has_public_thread_property(): void
    {
        $customer = \App\Models\Customer::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendConversationReply($conversation, $thread);

        $reflection = new \ReflectionClass($job);
        $property = $reflection->getProperty('thread');

        $this->assertTrue($property->isPublic());
    }

    public function test_job_requires_all_parameters(): void
    {
        $customer = \App\Models\Customer::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendConversationReply($conversation, $thread);

        $this->assertNotNull($job->conversation);
        $this->assertNotNull($job->thread);
    }

    public function test_job_thread_belongs_to_conversation(): void
    {
        $customer = \App\Models\Customer::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendConversationReply($conversation, $thread);

        $this->assertEquals($job->conversation->id, $job->thread->conversation_id);
    }
}
