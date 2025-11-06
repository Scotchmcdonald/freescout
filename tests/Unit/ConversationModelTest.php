<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Conversation;
use Tests\TestCase;

class ConversationModelTest extends TestCase
{
    public function test_conversation_status_constants(): void
    {
        $this->assertEquals(1, Conversation::STATUS_ACTIVE);
        $this->assertEquals(2, Conversation::STATUS_PENDING);
        $this->assertEquals(3, Conversation::STATUS_CLOSED);
        $this->assertEquals(4, Conversation::STATUS_SPAM);
    }

    public function test_conversation_has_number(): void
    {
        $conversation = new Conversation(['number' => 12345]);
        $this->assertEquals(12345, $conversation->number);
    }

    public function test_conversation_has_subject(): void
    {
        $conversation = new Conversation(['subject' => 'Test Subject']);
        $this->assertEquals('Test Subject', $conversation->subject);
    }

    public function test_conversation_has_status(): void
    {
        $conversation = new Conversation(['status' => Conversation::STATUS_ACTIVE]);
        $this->assertEquals(Conversation::STATUS_ACTIVE, $conversation->status);
    }

    public function test_conversation_has_mailbox_id(): void
    {
        $conversation = new Conversation(['mailbox_id' => 123]);
        $this->assertEquals(123, $conversation->mailbox_id);
    }

    public function test_conversation_has_customer_id(): void
    {
        $conversation = new Conversation(['customer_id' => 456]);
        $this->assertEquals(456, $conversation->customer_id);
    }

    public function test_conversation_has_user_id(): void
    {
        $conversation = new Conversation(['user_id' => 789]);
        $this->assertEquals(789, $conversation->user_id);
    }

    public function test_conversation_has_threads_count(): void
    {
        $conversation = new Conversation(['threads_count' => 5]);
        $this->assertEquals(5, $conversation->threads_count);
    }

    public function test_conversation_has_preview(): void
    {
        $conversation = new Conversation(['preview' => 'Message preview']);
        $this->assertEquals('Message preview', $conversation->preview);
    }

    public function test_conversation_has_last_reply_at(): void
    {
        $date = now();
        $conversation = new Conversation(['last_reply_at' => $date]);
        $this->assertEquals($date->toDateTimeString(), $conversation->last_reply_at->toDateTimeString());
    }

    public function test_conversation_has_closed_at(): void
    {
        $date = now();
        $conversation = new Conversation(['closed_at' => $date]);
        $this->assertEquals($date->toDateTimeString(), $conversation->closed_at->toDateTimeString());
    }
}
