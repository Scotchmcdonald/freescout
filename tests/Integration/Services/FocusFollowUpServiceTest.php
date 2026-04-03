<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Models\Conversation;
use App\Models\User;
use App\Services\FocusFollowUpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FocusFollowUpServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_due_for_user_returns_only_due_today_or_overdue_for_the_waiting_user(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-03 10:00:00'));

        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $overdue = Conversation::factory()->create([
            'waiting_on_user_id' => $targetUser->id,
            'next_follow_up' => now()->subDay(),
        ]);

        $dueToday = Conversation::factory()->create([
            'waiting_on_user_id' => $targetUser->id,
            'next_follow_up' => now()->endOfDay()->subHour(),
        ]);

        Conversation::factory()->create([
            'waiting_on_user_id' => $targetUser->id,
            'next_follow_up' => now()->addDay(),
        ]);

        Conversation::factory()->create([
            'waiting_on_user_id' => $otherUser->id,
            'next_follow_up' => now()->subHour(),
        ]);

        Conversation::factory()->create([
            'waiting_on_user_id' => $targetUser->id,
            'next_follow_up' => now()->subHours(2),
            'status' => Conversation::STATUS_CLOSED,
        ]);

        $result = app(FocusFollowUpService::class)->getDueForUser($targetUser);

        $this->assertSame(
            [$overdue->id, $dueToday->id],
            $result->pluck('id')->values()->all()
        );

        Carbon::setTestNow();
    }

    public function test_get_current_week_digest_groups_by_waiting_user_and_sorts_by_follow_up_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-06 09:00:00')); // Monday

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $aSecond = Conversation::factory()->create([
            'waiting_on_user_id' => $userA->id,
            'next_follow_up' => now()->addDays(2),
        ]);
        $aFirst = Conversation::factory()->create([
            'waiting_on_user_id' => $userA->id,
            'next_follow_up' => now()->addDay(),
        ]);

        Conversation::factory()->create([
            'waiting_on_user_id' => $userA->id,
            'next_follow_up' => now()->addDays(8),
        ]);

        $bOnly = Conversation::factory()->create([
            'waiting_on_user_id' => $userB->id,
            'next_follow_up' => now()->addDays(3),
        ]);

        $grouped = app(FocusFollowUpService::class)->getCurrentWeekDigestGroupedByWaitingOn();

        $this->assertTrue($grouped->has((string) $userA->id) || $grouped->has($userA->id));
        $this->assertTrue($grouped->has((string) $userB->id) || $grouped->has($userB->id));

        $groupA = $grouped->get($userA->id) ?? $grouped->get((string) $userA->id);
        $groupB = $grouped->get($userB->id) ?? $grouped->get((string) $userB->id);

        $this->assertSame([$aFirst->id, $aSecond->id], $groupA->pluck('id')->all());
        $this->assertSame([$bOnly->id], $groupB->pluck('id')->all());

        Carbon::setTestNow();
    }
}
