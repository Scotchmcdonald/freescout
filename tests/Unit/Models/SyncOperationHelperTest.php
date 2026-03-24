<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\SyncOperation;
use Illuminate\Support\Carbon;
use Tests\PureUnitTestCase;

final class TestSyncOperation extends SyncOperation
{
    public function getAttribute($key): mixed
    {
        if ($key === 'last_progress_at') {
            $value = $this->attributes[$key] ?? null;
            if ($value instanceof Carbon) {
                return $value;
            }

            return $value ? Carbon::parse((string) $value) : null;
        }

        return parent::getAttribute($key);
    }
}

class SyncOperationHelperTest extends PureUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-03-24 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function operation(array $attrs = []): TestSyncOperation
    {
        $model = new TestSyncOperation;
        $raw = [
            'status' => 'running',
            'total_items' => 0,
            'processed_items' => 0,
            'items_per_second' => 0,
            'last_progress_at' => Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'),
        ];

        foreach ($attrs as $key => $value) {
            $raw[$key] = $value instanceof Carbon
                ? $value->format('Y-m-d H:i:s')
                : $value;
        }

        $model->setRawAttributes($raw, true);

        return $model;
    }

    public function test_progress_percentage_handles_zero_and_rounding(): void
    {
        $zero = $this->operation(['total_items' => 0, 'processed_items' => 50]);
        $rounded = $this->operation(['total_items' => 3, 'processed_items' => 2]);

        $this->assertSame(0, $zero->progress_percentage);
        $this->assertSame(67, $rounded->progress_percentage);
    }

    public function test_is_stalled_follows_current_signed_diff_behavior(): void
    {
        $recent = $this->operation(['status' => 'running', 'last_progress_at' => Carbon::now()->subMinutes(4)]);
        $pastBeyondThreshold = $this->operation(['status' => 'running', 'last_progress_at' => Carbon::now()->subMinutes(6)]);
        $futureBeyondThreshold = $this->operation(['status' => 'running', 'last_progress_at' => Carbon::now()->addMinutes(6)]);
        $notRunning = $this->operation(['status' => 'paused', 'last_progress_at' => Carbon::now()->subMinutes(10)]);
        $none = $this->operation(['status' => 'running', 'last_progress_at' => null]);

        $this->assertFalse($recent->isStalled());
        $this->assertFalse($pastBeyondThreshold->isStalled());
        $this->assertTrue($futureBeyondThreshold->isStalled());
        $this->assertFalse($notRunning->isStalled());
        $this->assertFalse($none->isStalled());
    }

    public function test_estimated_time_remaining_formats_seconds_minutes_and_hours(): void
    {
        $none = $this->operation(['items_per_second' => 0, 'total_items' => 100, 'processed_items' => 10]);
        $seconds = $this->operation(['items_per_second' => 10, 'total_items' => 550, 'processed_items' => 50]);
        $minutes = $this->operation(['items_per_second' => 2, 'total_items' => 1000, 'processed_items' => 100]);
        $hours = $this->operation(['items_per_second' => 0.5, 'total_items' => 10000, 'processed_items' => 1000]);

        $this->assertNull($none->estimated_time_remaining);
        $this->assertSame('50s', $seconds->estimated_time_remaining);
        $this->assertSame('8m', $minutes->estimated_time_remaining);
        $this->assertSame('5h', $hours->estimated_time_remaining);
    }
}
