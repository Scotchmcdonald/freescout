<?php

declare(strict_types=1);

namespace Tests\Unit\PIB;

use Modules\PIB\Models\TimeEntry;
use Tests\PureUnitTestCase;

if (! class_exists(StubTimeEntry::class)) {
    final class StubTimeEntry extends TimeEntry
    {
        protected static function booted(): void {}

        public function getDateFormat(): string
        {
            return 'Y-m-d H:i:s';
        }
    }
}

final class TimeEntryTest extends PureUnitTestCase
{
    private function entry(int $durationMinutes, ?float $billingRate = null, ?int $serviceUsageId = null): StubTimeEntry
    {
        $attrs = ['duration_minutes' => $durationMinutes];
        if ($billingRate !== null) {
            $attrs['billing_rate'] = $billingRate;
        }
        if ($serviceUsageId !== null) {
            $attrs['service_usage_id'] = $serviceUsageId;
        }

        $e = new StubTimeEntry;
        $e->setRawAttributes($attrs);

        return $e;
    }

    // ── duration_hours ─────────────────────────────────────────────────

    public function test_duration_hours_for_sixty_minutes(): void
    {
        $this->assertSame(1.0, $this->entry(60)->duration_hours);
    }

    public function test_duration_hours_for_ninety_minutes(): void
    {
        $this->assertSame(1.5, $this->entry(90)->duration_hours);
    }

    public function test_duration_hours_for_thirty_minutes(): void
    {
        $this->assertSame(0.5, $this->entry(30)->duration_hours);
    }

    // ── total_amount ───────────────────────────────────────────────────

    public function test_total_amount_returns_null_when_no_billing_rate(): void
    {
        $this->assertNull($this->entry(60)->total_amount);
    }

    public function test_total_amount_is_hours_times_rate(): void
    {
        // 60 min = 1 hour × $100/hr = $100
        $entry = $this->entry(60, 100.0);
        $this->assertEqualsWithDelta(100.0, $entry->total_amount, 0.01);
    }

    public function test_total_amount_rounds_to_two_decimals(): void
    {
        // 90 min = 1.5 hr × $66.67/hr = $100.005 ≈ $100.01
        $entry = $this->entry(90, 66.67);
        $this->assertEqualsWithDelta(100.01, $entry->total_amount, 0.01);
    }

    // ── formatted_duration ─────────────────────────────────────────────

    public function test_formatted_duration_shows_hours_and_minutes(): void
    {
        $this->assertSame('1h 30m', $this->entry(90)->formatted_duration);
    }

    public function test_formatted_duration_only_hours_when_no_remainder(): void
    {
        $this->assertSame('2h', $this->entry(120)->formatted_duration);
    }

    public function test_formatted_duration_only_minutes_when_less_than_one_hour(): void
    {
        $this->assertSame('45m', $this->entry(45)->formatted_duration);
    }

    public function test_formatted_duration_for_zero_minutes(): void
    {
        $this->assertSame('0m', $this->entry(0)->formatted_duration);
    }

    // ── isInvoiced ─────────────────────────────────────────────────────

    public function test_is_invoiced_returns_true_when_service_usage_id_is_set(): void
    {
        $this->assertTrue($this->entry(60, null, 42)->isInvoiced());
    }

    public function test_is_invoiced_returns_false_when_service_usage_id_is_null(): void
    {
        $this->assertFalse($this->entry(60)->isInvoiced());
    }

    // ── work type constants ────────────────────────────────────────────

    public function test_work_type_constants_are_distinct(): void
    {
        $constants = [
            TimeEntry::WORK_TROUBLESHOOTING,
            TimeEntry::WORK_IMPLEMENTATION,
            TimeEntry::WORK_DOCUMENTATION,
            TimeEntry::WORK_TRAVEL,
            TimeEntry::WORK_MEETING,
            TimeEntry::WORK_RESEARCH,
        ];

        $this->assertSame(count($constants), count(array_unique($constants)));
    }
}
