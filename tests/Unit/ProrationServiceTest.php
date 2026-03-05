<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\PIB\Services\ProrationService;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * ProrationServiceTest
 * 
 * Tests proration calculations for mid-month billing changes
 */
class ProrationServiceTest extends TestCase
{
    private ProrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new ProrationService();
    }

    public function test_calculates_full_month_proration(): void
    {
        $monthlyRate = 100.00;
        $startDate = Carbon::parse('2026-01-01');
        $endDate = Carbon::parse('2026-01-31');

        $result = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        // Full month: 31 days in January
        $this->assertEquals(100.00, $result);
    }

    public function test_calculates_half_month_proration(): void
    {
        $monthlyRate = 100.00;
        $startDate = Carbon::parse('2026-01-15');
        $endDate = Carbon::parse('2026-01-31');

        $result = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        // 17 days (Jan 15-31 inclusive) out of 31 = 54.84
        $expected = (100.00 / 31) * 17;
        $this->assertEquals(round($expected, 2), $result);
    }

    public function test_calculates_single_day_proration(): void
    {
        $monthlyRate = 100.00;
        $startDate = Carbon::parse('2026-01-31');
        $endDate = Carbon::parse('2026-01-31');

        $result = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        // 1 day out of 31 = 3.23
        $expected = 100.00 / 31;
        $this->assertEquals(round($expected, 2), $result);
    }

    public function test_calculates_february_proration(): void
    {
        $monthlyRate = 100.00;
        $startDate = Carbon::parse('2026-02-01');
        $endDate = Carbon::parse('2026-02-28');

        $result = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        // February 2026 has 28 days (not leap year)
        $this->assertEquals(100.00, $result);
    }

    public function test_calculates_leap_year_february_proration(): void
    {
        $monthlyRate = 100.00;
        $startDate = Carbon::parse('2024-02-01');
        $endDate = Carbon::parse('2024-02-29');

        $result = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        // February 2024 has 29 days (leap year)
        $this->assertEquals(100.00, $result);
    }

    public function test_calculates_mid_month_start(): void
    {
        $monthlyRate = 310.00;
        $startDate = Carbon::parse('2026-01-15');
        $endDate = Carbon::parse('2026-01-31');

        $result = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        // 17 days out of 31 days
        // 310 / 31 = 10 per day
        // 10 * 17 = 170
        $this->assertEquals(170.00, $result);
    }

    public function test_calculates_daily_rate(): void
    {
        $monthlyRate = 100.00;
        $referenceDate = Carbon::parse('2026-01-01');

        $dailyRate = $this->service->calculateDailyRate($monthlyRate, $referenceDate);

        // 100 / 31 days = 3.2258...
        $this->assertEquals(3.2258, $dailyRate);
    }

    public function test_calculates_remainder_of_month(): void
    {
        $monthlyRate = 100.00;
        $startDate = Carbon::parse('2026-01-15');

        $result = $this->service->calculateRemainderOfMonth($monthlyRate, $startDate);

        // From Jan 15 to Jan 31 = 17 days
        $expected = (100.00 / 31) * 17;
        $this->assertEquals(round($expected, 2), $result);
    }

    public function test_calculates_for_specific_days(): void
    {
        $monthlyRate = 100.00;
        $days = 10;
        $referenceDate = Carbon::parse('2026-01-01');

        $result = $this->service->calculateForDays($monthlyRate, $days, $referenceDate);

        // (100 / 31) * 10 = 32.26
        $expected = (100.00 / 31) * 10;
        $this->assertEquals(round($expected, 2), $result);
    }

    public function test_rounds_to_two_decimal_places(): void
    {
        $monthlyRate = 99.99;
        $startDate = Carbon::parse('2026-01-15');
        $endDate = Carbon::parse('2026-01-20');

        $result = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        // Should be rounded to 2 decimal places
        $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', (string) $result);
    }

    public function test_handles_different_month_lengths(): void
    {
        $monthlyRate = 100.00;

        // January: 31 days
        $jan = $this->service->calculateDailyRate($monthlyRate, Carbon::parse('2026-01-01'));
        
        // February: 28 days
        $feb = $this->service->calculateDailyRate($monthlyRate, Carbon::parse('2026-02-01'));
        
        // April: 30 days
        $apr = $this->service->calculateDailyRate($monthlyRate, Carbon::parse('2026-04-01'));

        // Daily rate should vary based on month length
        $this->assertNotEquals($jan, $feb);
        $this->assertNotEquals($feb, $apr);
        $this->assertNotEquals($jan, $apr);

        // February should have highest daily rate (fewer days)
        $this->assertGreaterThan($jan, $feb);
        $this->assertGreaterThan($apr, $feb);
    }

    public function test_proration_accuracy_for_billing_scenario(): void
    {
        // Real-world scenario: Client starts mid-month
        $monthlyRate = 550.00; // Silver Plan with assets
        $startDate = Carbon::parse('2026-01-15'); // Started on Jan 15
        $endDate = Carbon::parse('2026-01-31'); // Bill through end of month

        $proratedAmount = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        // 17 days out of 31
        $expected = (550.00 / 31) * 17;
        $this->assertEquals(round($expected, 2), $proratedAmount);

        // Next month should be full amount
        $fullMonth = $this->service->calculateProration(
            $monthlyRate,
            Carbon::parse('2026-02-01'),
            Carbon::parse('2026-02-28')
        );
        $this->assertEquals(550.00, $fullMonth);
    }
}
