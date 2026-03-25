<?php

declare(strict_types=1);

namespace Tests\Integration\Actions;

use App\Actions\Conversations\UpdateFollowUpDateAction;
use App\Enums\ConversationStatus;
use App\Enums\ThreadType;
use App\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateFollowUpDateActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_clears_follow_up_when_closing_conversation(): void
    {
        $conversation = Conversation::factory()->create([
            'follow_up_date' => now()->addDays(2),
            'follow_up_reminded_at' => now()->subHour(),
        ]);

        (new UpdateFollowUpDateAction)->execute($conversation, [
            'status' => ConversationStatus::Closed->value,
            'type' => ThreadType::MESSAGE->value,
        ]);

        $this->assertNull($conversation->fresh()->follow_up_date);
        $this->assertNull($conversation->fresh()->follow_up_reminded_at);
    }

    public function test_execute_keeps_follow_up_unchanged_for_internal_notes(): void
    {
        $existingDate = now()->addDays(7)->startOfDay();
        $conversation = Conversation::factory()->create([
            'follow_up_date' => $existingDate,
            'follow_up_reminded_at' => now()->subHour(),
        ]);

        (new UpdateFollowUpDateAction)->execute($conversation, [
            'type' => ThreadType::NOTE->value,
        ]);

        $this->assertEquals($existingDate->toDateTimeString(), $conversation->fresh()->follow_up_date?->toDateTimeString());
        $this->assertNotNull($conversation->fresh()->follow_up_reminded_at);
    }

    public function test_execute_sets_default_follow_up_for_replies_when_missing(): void
    {
        config()->set('app.default_follow_up_days', 5);
        $conversation = Conversation::factory()->create([
            'follow_up_date' => null,
            'follow_up_reminded_at' => now()->subHour(),
        ]);

        (new UpdateFollowUpDateAction)->execute($conversation, [
            'type' => ThreadType::MESSAGE->value,
        ]);

        $this->assertEquals(
            now()->addDays(5)->startOfDay()->toDateTimeString(),
            $conversation->fresh()->follow_up_date?->toDateTimeString()
        );
        $this->assertNull($conversation->fresh()->follow_up_reminded_at);
    }
}