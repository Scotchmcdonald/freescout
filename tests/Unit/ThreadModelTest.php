<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Thread;
use Tests\TestCase;

class ThreadModelTest extends TestCase
{
    public function test_thread_has_conversation_id(): void
    {
        $thread = new Thread(['conversation_id' => 123]);
        $this->assertEquals(123, $thread->conversation_id);
    }

    public function test_thread_has_user_id(): void
    {
        $thread = new Thread(['user_id' => 456]);
        $this->assertEquals(456, $thread->user_id);
    }

    public function test_thread_has_customer_id(): void
    {
        $thread = new Thread(['customer_id' => 789]);
        $this->assertEquals(789, $thread->customer_id);
    }

    public function test_thread_has_type(): void
    {
        $thread = new Thread(['type' => 1]);
        $this->assertEquals(1, $thread->type);
    }

    public function test_thread_has_body(): void
    {
        $thread = new Thread(['body' => 'Test message body']);
        $this->assertEquals('Test message body', $thread->body);
    }

    public function test_thread_has_from(): void
    {
        $thread = new Thread(['from' => 'user@example.com']);
        $this->assertEquals('user@example.com', $thread->from);
    }

    public function test_thread_has_to(): void
    {
        $thread = new Thread(['to' => 'support@example.com']);
        $this->assertEquals('support@example.com', $thread->to);
    }

    public function test_thread_has_cc(): void
    {
        $thread = new Thread(['cc' => 'cc@example.com']);
        $this->assertEquals('cc@example.com', $thread->cc);
    }

    public function test_thread_has_bcc(): void
    {
        $thread = new Thread(['bcc' => 'bcc@example.com']);
        $this->assertEquals('bcc@example.com', $thread->bcc);
    }

    public function test_thread_has_headers(): void
    {
        $thread = new Thread(['headers' => 'X-Custom: value']);
        $this->assertEquals('X-Custom: value', $thread->headers);
    }
}
