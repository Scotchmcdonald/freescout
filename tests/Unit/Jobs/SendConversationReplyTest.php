<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendConversationReply;
use App\Models\Conversation;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendConversationReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_can_be_instantiated(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendConversationReply($conversation, $thread, 'test@example.com');

        $this->assertInstanceOf(SendConversationReply::class, $job);
    }

    public function test_job_has_handle_method(): void
    {
        $this->assertTrue(method_exists(SendConversationReply::class, 'handle'));
    }

    public function test_job_requires_conversation_thread_and_email(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendConversationReply($conversation, $thread, 'test@example.com');

        $this->assertEquals($conversation->id, $job->conversation->id);
        $this->assertEquals($thread->id, $job->thread->id);
        $this->assertEquals('test@example.com', $job->recipientEmail);
    }
}
