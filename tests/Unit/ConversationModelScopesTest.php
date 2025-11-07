<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationModelScopesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function conversation_is_active_returns_true_for_active_status(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $this->assertTrue($conversation->isActive());
    }

    /** @test */
    public function conversation_is_active_returns_false_for_non_active_status(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_CLOSED,
        ]);

        $this->assertFalse($conversation->isActive());
    }

    /** @test */
    public function conversation_is_closed_returns_true_for_closed_status(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_CLOSED,
        ]);

        $this->assertTrue($conversation->isClosed());
    }

    /** @test */
    public function conversation_is_closed_returns_false_for_non_closed_status(): void
    {
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $this->assertFalse($conversation->isClosed());
    }
}
