<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Carbon\Carbon;
use Modules\PIB\Services\ProrationService;
use Tests\PureUnitTestCase;

class ProrationServiceTest extends PureUnitTestCase
{
    private ProrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProrationService;
    }

    public function test_full_month_equals_monthly_rate(): void
    {
        $monthlyRate = 100.00;
        $startDate = Carbon::create(2026, 1, 1);
        $endDate = Carbon::create(2026, 1, 31);

        $prorated = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        $this->assertEquals(100.00, $prorated);
    }

    public function test_half_month_proration(): void
    {
        $monthlyRate = 100.00;
        $startDate = Carbon::create(2026, 1, 16);
        $endDate = Carbon::create(2026, 1, 31);

        $prorated = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        $expected = round((100.00 / 31) * 16, 2);
        $this->assertEquals($expected, $prorated);
    }

    public function test_single_day_proration(): void
    {
        $monthlyRate = 100.00;
        $startDate = Carbon::create(2026, 1, 31);
        $endDate = Carbon::create(2026, 1, 31);

        $prorated = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        $expected = round(100.00 / 31, 2);
        $this->assertEquals($expected, $prorated);
    }

    public function test_february_proration(): void
    {
        $monthlyRate = 100.00;
        $startDate = Carbon::create(2026, 2, 15);
        $endDate = Carbon::create(2026, 2, 28);

        $prorated = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        $expected = round((100.00 / 28) * 14, 2);
        $this->assertEquals($expected, $prorated);
    }

    public function test_leap_year_february_proration(): void
    {
        $monthlyRate = 100.00;
        $startDate = Carbon::create(2028, 2, 1);
        $endDate = Carbon::create(2028, 2, 29);

        $prorated = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        $this->assertEquals(100.00, $prorated);
    }

    public function test_30_day_month_proration(): void
    {
        $monthlyRate = 150.00;
        $startDate = Carbon::create(2026, 4, 10);
        $endDate = Carbon::create(2026, 4, 30);

        $prorated = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        $expected = round((150.00 / 30) * 21, 2);
        $this->assertEquals($expected, $prorated);
    }

    public function test_daily_rate_calculation(): void
    {
        $monthlyRate = 100.00;
        $referenceDate = Carbon::create(2026, 1, 15);

        $dailyRate = $this->service->calculateDailyRate($monthlyRate, $referenceDate);

        $expected = round(100.00 / 31, 4);
        $this->assertEquals($expected, $dailyRate);
    }

    public function test_daily_rate_varies_by_month(): void
    {
        $monthlyRate = 100.00;

        $januaryRate = $this->service->calculateDailyRate($monthlyRate, Carbon::create(2026, 1, 1));
        $februaryRate = $this->service->calculateDailyRate($monthlyRate, Carbon::create(2026, 2, 1));
        $aprilRate = $this->service->calculateDailyRate($monthlyRate, Carbon::create(2026, 4, 1));

        $this->assertGreaterThan($januaryRate, $februaryRate);
        $this->assertGreaterThan($aprilRate, $februaryRate);
    }

    public function test_remainder_of_month_calculation(): void
    {
        $monthlyRate = 100.00;
        $startDate = Carbon::create(2026, 1, 25);

        $prorated = $this->service->calculateRemainderOfMonth($monthlyRate, $startDate);

        $expected = round((100.00 / 31) * 7, 2);
        $this->assertEquals($expected, $prorated);
    }

    public function test_first_day_remainder_equals_full_month(): void
    {
        $monthlyRate = 100.00;
        $startDate = Carbon::create(2026, 1, 1);

        $prorated = $this->service->calculateRemainderOfMonth($monthlyRate, $startDate);

        $this->assertEquals(100.00, $prorated);
    }

    public function test_calculate_for_days(): void
    {
        $monthlyRate = 100.00;
        $days = 10;
        $referenceDate = Carbon::create(2026, 1, 1);

        $prorated = $this->service->calculateForDays($monthlyRate, $days, $referenceDate);

        $expected = round((100.00 / 31) * 10, 2);
        $this->assertEquals($expected, $prorated);
    }

    public function test_zero_days_returns_zero(): void
    {
        $prorated = $this->service->calculateForDays(100.00, 0, Carbon::create(2026, 1, 1));

        $this->assertEquals(0.00, $prorated);
    }

    public function test_large_rate_precision(): void
    {
        $monthlyRate = 9999.99;
        $startDate = Carbon::create(2026, 1, 15);
        $endDate = Carbon::create(2026, 1, 31);

        $prorated = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        $expected = round((9999.99 / 31) * 17, 2);
        $this->assertEquals($expected, $prorated);
    }

    public function test_small_rate_precision(): void
    {
        $monthlyRate = 0.99;
        $startDate = Carbon::create(2026, 1, 15);
        $endDate = Carbon::create(2026, 1, 31);

        $prorated = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        $expected = round((0.99 / 31) * 17, 2);
        $this->assertEquals($expected, $prorated);
    }

    public function test_negative_date_range_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('End date must be on or after start date');

        $this->service->calculateProration(
            100.00,
            Carbon::create(2026, 1, 31),
            Carbon::create(2026, 1, 1)
        );
    }

    public function test_uses_start_month_days(): void
    {
        $monthlyRate = 100.00;
        $startDate = Carbon::create(2026, 1, 25);
        $endDate = Carbon::create(2026, 2, 5);

        $prorated = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        $expected = round((100.00 / 31) * 12, 2);
        $this->assertEquals($expected, $prorated);
    }

    public function test_upgrade_scenario(): void
    {
        $oldPlanRate = 50.00;
        $newPlanRate = 100.00;
        $upgradeDate = Carbon::create(2026, 1, 15);
        $endOfMonth = Carbon::create(2026, 1, 31);

        $credit = $this->service->calculateProration($oldPlanRate, $upgradeDate, $endOfMonth);
        $charge = $this->service->calculateProration($newPlanRate, $upgradeDate, $endOfMonth);
        $netCharge = $charge - $credit;

        $this->assertGreaterThan(0, $netCharge);
        $this->assertEquals(round((50.00 / 31) * 17, 2), $netCharge);
    }

    public function test_downgrade_scenario(): void
    {
        $oldPlanRate = 100.00;
        $newPlanRate = 50.00;
        $downgradeDate = Carbon::create(2026, 1, 15);
        $endOfMonth = Carbon::create(2026, 1, 31);

        $credit = $this->service->calculateProration($oldPlanRate, $downgradeDate, $endOfMonth);
        $charge = $this->service->calculateProration($newPlanRate, $downgradeDate, $endOfMonth);
        $netCharge = $charge - $credit;

        $this->assertLessThan(0, $netCharge);
    }

    public function test_cancellation_credit(): void
    {
        $monthlyRate = 100.00;
        $cancellationDate = Carbon::create(2026, 1, 10);
        $endOfMonth = Carbon::create(2026, 1, 31);

        $unusedStart = $cancellationDate->copy()->addDay();
        $credit = $this->service->calculateProration($monthlyRate, $unusedStart, $endOfMonth);

        $expected = round((100.00 / 31) * 21, 2);
        $this->assertEquals($expected, $credit);
    }
}
