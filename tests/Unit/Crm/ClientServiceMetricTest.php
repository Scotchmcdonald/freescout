<?php

declare(strict_types=1);

namespace Tests\Unit\Crm;

use Modules\Crm\Models\ClientServiceMetric;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Stub that removes DB dependency for date-cast attributes.
 */
if (! class_exists(StubMetric::class)) {
final class StubMetric extends ClientServiceMetric
{
    protected static function booted(): void {}

    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }
}
}


/**
 * Pure-unit tests for ClientServiceMetric computed attributes.
 *
 * All tests use the stub to avoid DB connections.
 */
final class ClientServiceMetricTest extends BaseTestCase
{
    private function make(array $attrs = []): StubMetric
    {
        $m = new StubMetric();
        foreach ($attrs as $key => $value) {
            $m->$key = $value;
        }

        return $m;
    }

    // ── getPeriodDateAttribute ─────────────────────────────────────────────

    public function test_period_date_creates_carbon_first_of_month(): void
    {
        $m = $this->make(['period_year' => 2026, 'period_month' => 3]);
        $date = $m->period_date;

        $this->assertSame(2026, $date->year);
        $this->assertSame(3, $date->month);
        $this->assertSame(1, $date->day);
    }

    public function test_period_date_january(): void
    {
        $m = $this->make(['period_year' => 2024, 'period_month' => 1]);
        $date = $m->period_date;

        $this->assertSame(2024, $date->year);
        $this->assertSame(1, $date->month);
    }

    // ── getFormattedPeriodAttribute ────────────────────────────────────────

    public function test_formatted_period_returns_month_and_year(): void
    {
        $m = $this->make(['period_year' => 2026, 'period_month' => 1]);
        // Format 'F Y' = "January 2026"
        $this->assertSame('January 2026', $m->formatted_period);
    }

    public function test_formatted_period_march_2025(): void
    {
        $m = $this->make(['period_year' => 2025, 'period_month' => 3]);
        $this->assertSame('March 2025', $m->formatted_period);
    }

    // ── getTotalTicketsAttribute ───────────────────────────────────────────

    public function test_total_tickets_sums_all_three_types(): void
    {
        $m = $this->make([
            'included_ticket_count' => 5,
            'ad_hoc_ticket_count' => 3,
            'emergency_ticket_count' => 2,
        ]);
        $this->assertSame(10, $m->total_tickets);
    }

    public function test_total_tickets_with_zeros(): void
    {
        $m = $this->make([
            'included_ticket_count' => 0,
            'ad_hoc_ticket_count' => 0,
            'emergency_ticket_count' => 7,
        ]);
        $this->assertSame(7, $m->total_tickets);
    }

    // ── getNetTicketChangeAttribute ────────────────────────────────────────

    public function test_net_ticket_change_positive(): void
    {
        $m = $this->make(['tickets_opened' => 10, 'tickets_closed' => 4]);
        $this->assertSame(6, $m->net_ticket_change);
    }

    public function test_net_ticket_change_negative(): void
    {
        $m = $this->make(['tickets_opened' => 2, 'tickets_closed' => 8]);
        $this->assertSame(-6, $m->net_ticket_change);
    }

    public function test_net_ticket_change_zero(): void
    {
        $m = $this->make(['tickets_opened' => 5, 'tickets_closed' => 5]);
        $this->assertSame(0, $m->net_ticket_change);
    }

    // ── formatMinutes (via formatted_avg_first_response) ──────────────────

    public function test_formatted_avg_first_response_null(): void
    {
        $m = $this->make(['avg_first_response_minutes' => null]);
        $this->assertNull($m->formatted_avg_first_response);
    }

    public function test_formatted_avg_first_response_minutes_only(): void
    {
        $m = $this->make(['avg_first_response_minutes' => 45]);
        $this->assertSame('45m', $m->formatted_avg_first_response);
    }

    public function test_formatted_avg_first_response_one_hour(): void
    {
        $m = $this->make(['avg_first_response_minutes' => 60]);
        $this->assertSame('1h 0m', $m->formatted_avg_first_response);
    }

    public function test_formatted_avg_first_response_hours_and_minutes(): void
    {
        $m = $this->make(['avg_first_response_minutes' => 125]);
        $this->assertSame('2h 5m', $m->formatted_avg_first_response);
    }

    public function test_formatted_avg_first_response_days(): void
    {
        // 1440 min = 24 hours = 1 day 0 hours
        $m = $this->make(['avg_first_response_minutes' => 1500]);
        $this->assertSame('1d 1h', $m->formatted_avg_first_response);
    }

    public function test_formatted_avg_first_response_multiple_days(): void
    {
        // 48h = 2 days 0 hours
        $m = $this->make(['avg_first_response_minutes' => 2880]);
        $this->assertSame('2d 0h', $m->formatted_avg_first_response);
    }

    // ── formatted_avg_resolution ──────────────────────────────────────────

    public function test_formatted_avg_resolution_null(): void
    {
        $m = $this->make(['avg_time_to_resolution_minutes' => null]);
        $this->assertNull($m->formatted_avg_resolution);
    }

    public function test_formatted_avg_resolution_minutes(): void
    {
        $m = $this->make(['avg_time_to_resolution_minutes' => 30]);
        $this->assertSame('30m', $m->formatted_avg_resolution);
    }
}
