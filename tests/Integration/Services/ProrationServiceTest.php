<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use Modules\PIB\Services\ProrationService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * ProrationService Integration Tests
 * 
 * Tests mid-month proration calculations used for billing adjustments.
 * Accurate proration is critical for:
 * - Mid-cycle subscription starts
 * - Plan upgrades/downgrades
 * - Service cancellations
 * - Credit calculations
 */
#[Group('integration')]
#[Group('services')]
#[Group('financial')]
#[Group('billing')]
class ProrationServiceTest extends TestCase
{
    private ProrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProrationService::class);
    }

    /**
     * Test full month proration equals monthly rate.
     */
    public function test_full_month_equals_monthly_rate(): void
    {
        $monthlyRate = 100.00;
        $startDate = Carbon::create(2026, 1, 1);
        $endDate = Carbon::create(2026, 1, 31);

        $prorated = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        $this->assertEquals(100.00, $prorated);
    }

    /**
     * Test half month proration.
     */
    public function test_half_month_proration(): void
    {
        $monthlyRate = 100.00;
        // January has 31 days, starting from the 16th = 16 days remaining
        $startDate = Carbon::create(2026, 1, 16);
        $endDate = Carbon::create(2026, 1, 31);

        $prorated = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        // (100 / 31) * 16 = 51.61
        $expected = round((100.00 / 31) * 16, 2);
        $this->assertEquals($expected, $prorated);
    }

    /**
     * Test single day proration.
     */
    public function test_single_day_proration(): void
    {
        $monthlyRate = 100.00;
        $startDate = Carbon::create(2026, 1, 31);
        $endDate = Carbon::create(2026, 1, 31);

        $prorated = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        // (100 / 31) * 1 = 3.23
        $expected = round(100.00 / 31, 2);
        $this->assertEquals($expected, $prorated);
    }

    /**
     * Test February proration (shorter month).
     */
    public function test_february_proration(): void
    {
        $monthlyRate = 100.00;
        // February 2026 has 28 days
        $startDate = Carbon::create(2026, 2, 15);
        $endDate = Carbon::create(2026, 2, 28);

        $prorated = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        // (100 / 28) * 14 = 50.00
        $expected = round((100.00 / 28) * 14, 2);
        $this->assertEquals($expected, $prorated);
    }

    /**
     * Test leap year February proration.
     */
    public function test_leap_year_february_proration(): void
    {
        $monthlyRate = 100.00;
        // February 2028 has 29 days (leap year)
        $startDate = Carbon::create(2028, 2, 1);
        $endDate = Carbon::create(2028, 2, 29);

        $prorated = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        $this->assertEquals(100.00, $prorated);
    }

    /**
     * Test 30-day month proration.
     */
    public function test_30_day_month_proration(): void
    {
        $monthlyRate = 150.00;
        // April has 30 days
        $startDate = Carbon::create(2026, 4, 10);
        $endDate = Carbon::create(2026, 4, 30);

        $prorated = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        // (150 / 30) * 21 = 105.00
        $expected = round((150.00 / 30) * 21, 2);
        $this->assertEquals($expected, $prorated);
    }

    /**
     * Test daily rate calculation.
     */
    public function test_daily_rate_calculation(): void
    {
        $monthlyRate = 100.00;
        $referenceDate = Carbon::create(2026, 1, 15); // January has 31 days

        $dailyRate = $this->service->calculateDailyRate($monthlyRate, $referenceDate);

        // 100 / 31 = 3.2258
        $expected = round(100.00 / 31, 4);
        $this->assertEquals($expected, $dailyRate);
    }

    /**
     * Test daily rate varies by month length.
     */
    public function test_daily_rate_varies_by_month(): void
    {
        $monthlyRate = 100.00;
        
        $januaryRate = $this->service->calculateDailyRate($monthlyRate, Carbon::create(2026, 1, 1));
        $februaryRate = $this->service->calculateDailyRate($monthlyRate, Carbon::create(2026, 2, 1));
        $aprilRate = $this->service->calculateDailyRate($monthlyRate, Carbon::create(2026, 4, 1));

        // Shorter months have higher daily rates
        $this->assertGreaterThan($januaryRate, $februaryRate);
        $this->assertGreaterThan($aprilRate, $februaryRate);
    }

    /**
     * Test remainder of month calculation.
     */
    public function test_remainder_of_month_calculation(): void
    {
        $monthlyRate = 100.00;
        $startDate = Carbon::create(2026, 1, 25);

        $prorated = $this->service->calculateRemainderOfMonth($monthlyRate, $startDate);

        // 7 days remaining in January (25-31 inclusive)
        $expected = round((100.00 / 31) * 7, 2);
        $this->assertEquals($expected, $prorated);
    }

    /**
     * Test first day remainder equals full month.
     */
    public function test_first_day_remainder_equals_full_month(): void
    {
        $monthlyRate = 100.00;
        $startDate = Carbon::create(2026, 1, 1);

        $prorated = $this->service->calculateRemainderOfMonth($monthlyRate, $startDate);

        $this->assertEquals(100.00, $prorated);
    }

    /**
     * Test calculate for specific days.
     */
    public function test_calculate_for_days(): void
    {
        $monthlyRate = 100.00;
        $days = 10;
        $referenceDate = Carbon::create(2026, 1, 1);

        $prorated = $this->service->calculateForDays($monthlyRate, $days, $referenceDate);

        // (100 / 31) * 10 = 32.26
        $expected = round((100.00 / 31) * 10, 2);
        $this->assertEquals($expected, $prorated);
    }

    /**
     * Test zero days returns zero.
     */
    public function test_zero_days_returns_zero(): void
    {
        $prorated = $this->service->calculateForDays(100.00, 0, Carbon::create(2026, 1, 1));

        $this->assertEquals(0.00, $prorated);
    }

    /**
     * Test large monthly rate precision.
     */
    public function test_large_rate_precision(): void
    {
        $monthlyRate = 9999.99;
        $startDate = Carbon::create(2026, 1, 15);
        $endDate = Carbon::create(2026, 1, 31);

        $prorated = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        // Should be properly rounded
        $expected = round((9999.99 / 31) * 17, 2);
        $this->assertEquals($expected, $prorated);
    }

    /**
     * Test small monthly rate precision.
     */
    public function test_small_rate_precision(): void
    {
        $monthlyRate = 0.99;
        $startDate = Carbon::create(2026, 1, 15);
        $endDate = Carbon::create(2026, 1, 31);

        $prorated = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        $expected = round((0.99 / 31) * 17, 2);
        $this->assertEquals($expected, $prorated);
    }

    /**
     * Test negative date range throws exception.
     */
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

    /**
     * Test cross-month boundaries (should use start month's days).
     */
    public function test_uses_start_month_days(): void
    {
        $monthlyRate = 100.00;
        // Start in Jan (31 days), end in Feb (28 days)
        $startDate = Carbon::create(2026, 1, 25);
        $endDate = Carbon::create(2026, 2, 5);

        $prorated = $this->service->calculateProration($monthlyRate, $startDate, $endDate);

        // 12 days total, using January's 31 days as reference
        $expected = round((100.00 / 31) * 12, 2);
        $this->assertEquals($expected, $prorated);
    }

    /**
     * Test typical upgrade scenario (mid-month plan change).
     */
    public function test_upgrade_scenario(): void
    {
        // Client upgrades from $50/mo to $100/mo on Jan 15
        $oldPlanRate = 50.00;
        $newPlanRate = 100.00;
        $upgradeDate = Carbon::create(2026, 1, 15);
        $endOfMonth = Carbon::create(2026, 1, 31);
        $startOfMonth = Carbon::create(2026, 1, 1);

        // Credit for unused old plan: Jan 15-31 (17 days)
        $credit = $this->service->calculateProration($oldPlanRate, $upgradeDate, $endOfMonth);

        // Charge for new plan: Jan 15-31 (17 days)
        $charge = $this->service->calculateProration($newPlanRate, $upgradeDate, $endOfMonth);

        // Net charge should be the difference
        $netCharge = $charge - $credit;

        $this->assertGreaterThan(0, $netCharge);
        $this->assertEquals(round((50.00 / 31) * 17, 2), $netCharge);
    }

    /**
     * Test typical downgrade scenario with credit.
     */
    public function test_downgrade_scenario(): void
    {
        // Client downgrades from $100/mo to $50/mo on Jan 15
        $oldPlanRate = 100.00;
        $newPlanRate = 50.00;
        $downgradeDate = Carbon::create(2026, 1, 15);
        $endOfMonth = Carbon::create(2026, 1, 31);

        // Credit for unused old plan
        $credit = $this->service->calculateProration($oldPlanRate, $downgradeDate, $endOfMonth);

        // Charge for new plan
        $charge = $this->service->calculateProration($newPlanRate, $downgradeDate, $endOfMonth);

        // Net should be a credit (negative)
        $netCharge = $charge - $credit;

        $this->assertLessThan(0, $netCharge);
    }

    /**
     * Test cancellation credit calculation.
     */
    public function test_cancellation_credit(): void
    {
        // Client paid $100 for full month, cancels on Jan 10
        $monthlyRate = 100.00;
        $cancellationDate = Carbon::create(2026, 1, 10);
        $endOfMonth = Carbon::create(2026, 1, 31);

        // Credit for unused portion (Jan 11-31 = 21 days)
        $unusedStart = $cancellationDate->copy()->addDay();
        $credit = $this->service->calculateProration($monthlyRate, $unusedStart, $endOfMonth);

        // Used: 10 days, Unused: 21 days
        $expected = round((100.00 / 31) * 21, 2);
        $this->assertEquals($expected, $credit);
    }
}
