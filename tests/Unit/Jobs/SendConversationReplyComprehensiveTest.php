<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendConversationReply;
use App\Models\Conversation;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendConversationReplyComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_stores_conversation_correctly(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['subject' => 'Test Subject']);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendConversationReply($conversation, $thread, $user);

        $this->assertEquals($conversation->id, $job->conversation->id);
        $this->assertEquals('Test Subject', $job->conversation->subject);
    }

    public function test_job_stores_thread_correctly(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'body' => 'Test reply body',
        ]);

        $job = new SendConversationReply($conversation, $thread, $user);

        $this->assertEquals($thread->id, $job->thread->id);
        $this->assertEquals('Test reply body', $job->thread->body);
    }

    public function test_job_stores_user_correctly(): void
    {
        $user = User::factory()->create(['email' => 'agent@example.com']);
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendConversationReply($conversation, $thread, $user);

        $this->assertEquals($user->id, $job->user->id);
        $this->assertEquals('agent@example.com', $job->user->email);
    }

    public function test_job_can_be_dispatched_to_queue(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        SendConversationReply::dispatch($conversation, $thread, $user);

        Queue::assertPushed(SendConversationReply::class);
    }

    public function test_job_has_public_conversation_property(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendConversationReply($conversation, $thread, $user);

        $reflection = new \ReflectionClass($job);
        $property = $reflection->getProperty('conversation');

        $this->assertTrue($property->isPublic());
    }

    public function test_job_has_public_thread_property(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendConversationReply($conversation, $thread, $user);

        $reflection = new \ReflectionClass($job);
        $property = $reflection->getProperty('thread');

        $this->assertTrue($property->isPublic());
    }

    public function test_job_has_public_user_property(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendConversationReply($conversation, $thread, $user);

        $reflection = new \ReflectionClass($job);
        $property = $reflection->getProperty('user');

        $this->assertTrue($property->isPublic());
    }

    public function test_job_requires_all_three_parameters(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendConversationReply($conversation, $thread, $user);

        $this->assertNotNull($job->conversation);
        $this->assertNotNull($job->thread);
        $this->assertNotNull($job->user);
    }

    public function test_job_thread_belongs_to_conversation(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendConversationReply($conversation, $thread, $user);

        $this->assertEquals($job->conversation->id, $job->thread->conversation_id);
    }
}
