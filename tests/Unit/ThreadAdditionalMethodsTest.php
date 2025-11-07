<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreadAdditionalMethodsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function thread_is_auto_responder_returns_false_for_normal_thread(): void
    {
        // Arrange
        $thread = Thread::factory()->create([
            'headers' => null,
        ]);

        // Act & Assert
        $this->assertFalse($thread->isAutoResponder());
    }

    /** @test */
    public function thread_is_auto_responder_returns_true_for_auto_reply_header(): void
    {
        // Arrange
        $thread = Thread::factory()->create([
            'headers' => "Auto-Submitted: auto-replied\nContent-Type: text/plain",
        ]);

        // Act & Assert
        $this->assertTrue($thread->isAutoResponder());
    }

    /** @test */
    public function thread_is_auto_responder_returns_true_for_precedence_auto_reply(): void
    {
        // Arrange
        $thread = Thread::factory()->create([
            'headers' => "Precedence: auto_reply\nContent-Type: text/plain",
        ]);

        // Act & Assert
        $this->assertTrue($thread->isAutoResponder());
    }

    /** @test */
    public function thread_is_bounce_returns_false_for_normal_thread(): void
    {
        // Arrange
        $thread = Thread::factory()->create([
            'meta' => null,
        ]);

        // Act & Assert
        $this->assertFalse($thread->isBounce());
    }

    /** @test */
    public function thread_is_bounce_returns_false_when_send_status_not_bounce(): void
    {
        // Arrange
        $thread = Thread::factory()->create([
            'meta' => [
                'send_status' => [
                    'is_bounce' => false,
                    'status' => 'sent',
                ],
            ],
        ]);

        // Act & Assert
        $this->assertFalse($thread->isBounce());
    }

    /** @test */
    public function thread_is_bounce_returns_true_when_marked_as_bounce(): void
    {
        // Arrange
        $thread = Thread::factory()->create([
            'meta' => [
                'send_status' => [
                    'is_bounce' => true,
                    'bounce_type' => 'hard',
                ],
            ],
        ]);

        // Act & Assert
        $this->assertTrue($thread->isBounce());
    }

    /** @test */
    public function thread_is_bounce_handles_empty_send_status(): void
    {
        // Arrange
        $thread = Thread::factory()->create([
            'meta' => [
                'send_status' => [],
            ],
        ]);

        // Act & Assert
        $this->assertFalse($thread->isBounce());
    }

    /** @test */
    public function thread_is_bounce_handles_meta_without_send_status(): void
    {
        // Arrange
        $thread = Thread::factory()->create([
            'meta' => [
                'other_data' => 'value',
            ],
        ]);

        // Act & Assert
        $this->assertFalse($thread->isBounce());
    }
}
