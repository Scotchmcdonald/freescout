<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FocusFollowUpService
{
    /**
     * @return Collection<int, Conversation>
     */
    public function getDueForUser(User $user, ?Carbon $reference = null): Collection
    {
        $reference ??= now();

        return Conversation::query()
            ->with(['customer', 'waitingOnUser'])
            ->where('waiting_on_user_id', $user->id)
            ->where('state', Conversation::STATE_PUBLISHED)
            ->where('status', '!=', Conversation::STATUS_CLOSED)
            ->whereNotNull('next_follow_up')
            ->where('next_follow_up', '<=', $reference->copy()->endOfDay())
            ->orderBy('next_follow_up')
            ->get();
    }

    /**
     * @return Collection<int|string, EloquentCollection<int, Conversation>>
     */
    public function getCurrentWeekDigestGroupedByWaitingOn(?Carbon $reference = null): Collection
    {
        $reference ??= now();

        $startOfWeek = $reference->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $reference->copy()->endOfWeek(Carbon::SUNDAY);

        return Conversation::query()
            ->with(['customer', 'waitingOnUser'])
            ->whereNotNull('waiting_on_user_id')
            ->whereNotNull('next_follow_up')
            ->whereBetween('next_follow_up', [$startOfWeek, $endOfWeek])
            ->orderBy('next_follow_up')
            ->get()
            ->groupBy('waiting_on_user_id')
            ->map(fn (Collection $tickets) => $tickets->sortBy('next_follow_up')->values());
    }
}
