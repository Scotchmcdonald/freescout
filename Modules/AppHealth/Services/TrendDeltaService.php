<?php

declare(strict_types=1);

namespace Modules\AppHealth\Services;

use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Modules\AppHealth\Models\ScalingScorecardSnapshot;

/**
 * Computes week-over-week breach count trends from persisted scorecard snapshots.
 *
 * Uses the consecutive-breach-week count to determine whether the Stage A
 * gate condition from SCALING_PLAYBOOK.md (2 consecutive breach weeks) is met.
 */
class TrendDeltaService
{
    public function __construct(private readonly SchemaBuilder $schema) {}

    /**
     * Compute weekly trend relative to the current breach count.
     *
     * @return array{
     *     delta_7d: int,
     *     delta_14d: int,
     *     direction: string,
     *     consecutive_breach_weeks: int,
     *     consecutive_weeks_required: int,
     *     gate_condition_met: bool
     * }
     */
    public function weeklyDelta(int $currentBreachCount): array
    {
        $configuredRequired = config('apphealth.playbook.consecutive_breach_weeks_required', 2);
        $required = is_numeric($configuredRequired) ? (int) $configuredRequired : 2;

        if (! $this->snapshotsTableExists()) {
            return [
                'delta_7d' => 0,
                'delta_14d' => 0,
                'direction' => 'stable',
                'consecutive_breach_weeks' => 0,
                'consecutive_weeks_required' => $required,
                'gate_condition_met' => false,
            ];
        }

        $prior7 = $this->avgBreachCountInWindow(7, 14);
        $prior14 = $this->avgBreachCountInWindow(14, 21);

        $delta7d = $prior7 !== null ? $currentBreachCount - (int) round($prior7) : 0;
        $delta14d = $prior14 !== null ? $currentBreachCount - (int) round($prior14) : 0;

        $direction = 'stable';

        if ($delta7d > 0) {
            $direction = 'worsening';
        } elseif ($delta7d < 0) {
            $direction = 'improving';
        }

        $consecutiveBreachWeeks = $this->countConsecutiveBreachWeeks();

        return [
            'delta_7d' => $delta7d,
            'delta_14d' => $delta14d,
            'direction' => $direction,
            'consecutive_breach_weeks' => $consecutiveBreachWeeks,
            'consecutive_weeks_required' => $required,
            'gate_condition_met' => $consecutiveBreachWeeks >= $required,
        ];
    }

    /**
     * Return the average breach_count for snapshots whose snapshot_date falls within
     * [$daysStart, $daysEnd) days ago (both measured from today).
     */
    private function avgBreachCountInWindow(int $daysStart, int $daysEnd): ?float
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, ScalingScorecardSnapshot> $rows */
        $rows = ScalingScorecardSnapshot::query()
            ->whereBetween('snapshot_date', [
                now()->subDays($daysEnd)->toDateString(),
                now()->subDays($daysStart)->toDateString(),
            ])
            ->get(['breach_count']);

        if ($rows->isEmpty()) {
            return null;
        }

        return $rows->avg('breach_count');
    }

    /**
     * Count how many of the most-recent weekly snapshots (one per week, checking newest first)
     * had breach_count >= 2 (the warning threshold).
     */
    private function countConsecutiveBreachWeeks(): int
    {
        $consecutive = 0;

        // Look back up to 8 weeks, sampling one snapshot per week
        for ($weeksAgo = 0; $weeksAgo < 8; $weeksAgo++) {
            $weekStart = now()->subWeeks($weeksAgo)->startOfWeek()->toDateString();
            $weekEnd = now()->subWeeks($weeksAgo)->endOfWeek()->toDateString();

            /** @var ScalingScorecardSnapshot|null $snapshot */
            $snapshot = ScalingScorecardSnapshot::query()
                ->whereBetween('snapshot_date', [$weekStart, $weekEnd])
                ->orderByDesc('snapshot_date')
                ->first(['breach_count']);

            if ($snapshot === null) {
                break;
            }

            if ($snapshot->breach_count >= 2) {
                $consecutive++;
            } else {
                break;
            }
        }

        return $consecutive;
    }

    private function snapshotsTableExists(): bool
    {
        try {
            return $this->schema->hasTable('app_health_scaling_scorecard_snapshots');
        } catch (\Throwable) {
            return false;
        }
    }
}
