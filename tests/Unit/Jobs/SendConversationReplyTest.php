<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendConversationReply;
use App\Models\Conversation;
use App\Models\Thread;
use Tests\UnitTestCase;

class SendConversationReplyTest extends UnitTestCase
{

    public function test_job_can_be_instantiated(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        $customer = \App\Models\Customer::factory()->create(['email' => 'test@example.com']);

        $job = new SendConversationReply($conversation, [$thread], $customer);

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
        $customer = \App\Models\Customer::factory()->create(['email' => 'test@example.com']);

        $job = new SendConversationReply($conversation, [$thread], $customer);

        $this->assertEquals($conversation->id, $job->conversation->id);
        $this->assertCount(1, $job->replies);
        $this->assertEquals($customer->id, $job->customer->id);
    }
}
