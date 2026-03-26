<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\SyncOperation;
use PHPUnit\Framework\TestCase;

final class StubSyncOperation extends SyncOperation
{
    protected static function booted(): void {}

    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }
}

final class SyncOperationTest extends TestCase
{
    private function op(array $attrs): StubSyncOperation
    {
        $o = new StubSyncOperation();
        $o->setRawAttributes($attrs);

        return $o;
    }

    // ── getProgressPercentageAttribute ────────────────────────────────

    public function test_progress_percentage_is_zero_when_total_is_zero(): void
    {
        $o = $this->op(['total_items' => 0, 'processed_items' => 0]);
        $this->assertSame(0, $o->progress_percentage);
    }

    public function test_progress_percentage_calculates_correctly(): void
    {
        $o = $this->op(['total_items' => 100, 'processed_items' => 50]);
        $this->assertSame(50, $o->progress_percentage);
    }

    public function test_progress_percentage_rounds_to_int(): void
    {
        // 1/3 = 0.333... → round = 0, cast to int = 0
        $o = $this->op(['total_items' => 3, 'processed_items' => 1]);
        $this->assertSame(33, $o->progress_percentage);
    }

    public function test_progress_percentage_is_100_when_complete(): void
    {
        $o = $this->op(['total_items' => 200, 'processed_items' => 200]);
        $this->assertSame(100, $o->progress_percentage);
    }

    // ── getEstimatedTimeRemainingAttribute ────────────────────────────

    public function test_eta_returns_null_when_items_per_second_is_zero(): void
    {
        $o = $this->op(['items_per_second' => '0', 'total_items' => 100, 'processed_items' => 50]);
        $this->assertNull($o->estimated_time_remaining);
    }

    public function test_eta_returns_null_when_total_items_is_zero(): void
    {
        $o = $this->op(['items_per_second' => '10', 'total_items' => 0, 'processed_items' => 0]);
        $this->assertNull($o->estimated_time_remaining);
    }

    public function test_eta_returns_seconds_when_less_than_60(): void
    {
        // 10 remaining / 1/s = 10 seconds
        $o = $this->op(['items_per_second' => '1', 'total_items' => 20, 'processed_items' => 10]);
        $this->assertSame('10s', $o->estimated_time_remaining);
    }

    public function test_eta_returns_minutes_when_60_to_3600_seconds(): void
    {
        // 120 remaining / 1/s = 120 seconds → round(120/60) = 2m
        $o = $this->op(['items_per_second' => '1', 'total_items' => 200, 'processed_items' => 80]);
        $this->assertSame('2m', $o->estimated_time_remaining);
    }

    public function test_eta_returns_hours_when_over_3600_seconds(): void
    {
        // 3600 remaining / 1/s = 3600 seconds → 1.0h
        $o = $this->op(['items_per_second' => '1', 'total_items' => 4000, 'processed_items' => 400]);
        $this->assertSame('1h', $o->estimated_time_remaining);
    }
}
