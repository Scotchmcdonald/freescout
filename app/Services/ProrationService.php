<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;

/**
 * ProrationService
 * 
 * Handles mid-month proration calculations using day-weighted formula.
 * 
 * Formula: (monthlyRate / daysInMonth) * daysUsed
 * 
 * Usage:
 * $prorated = app(ProrationService::class)->calculateProration(
 *     monthlyRate: 100.00,
 *     startDate: Carbon::parse('2026-01-15'),
 *     endDate: Carbon::parse('2026-01-31')
 * );
 */
class ProrationService
{
    /**
     * Calculate prorated amount based on days used in month
     * 
     * @param float $monthlyRate Full monthly rate
     * @param Carbon $startDate Start date of service
     * @param Carbon $endDate End date of service (typically end of month)
     * @return float Prorated amount (rounded to 2 decimal places)
     */
    public function calculateProration(float $monthlyRate, Carbon $startDate, Carbon $endDate): float
    {
        // Ensure we're working with copies to avoid modifying originals
        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->startOfDay();
        
        // Get days in the month of start date
        $daysInMonth = $start->daysInMonth;
        
        // Calculate days used (inclusive) - add 1 because both start and end days count
        $daysUsed = $start->diffInDays($end, false) + 1;
        
        // Ensure days used is positive
        if ($daysUsed < 0) {
            throw new \InvalidArgumentException('End date must be on or after start date');
        }
        
        // Calculate proration
        $proratedAmount = ($monthlyRate / $daysInMonth) * $daysUsed;
        
        // Round to 2 decimal places for currency
        return round($proratedAmount, 2);
    }

    /**
     * Calculate daily rate for a monthly rate
     * 
     * @param float $monthlyRate Full monthly rate
     * @param Carbon $referenceDate Date to use for determining days in month
     * @return float Daily rate (rounded to 4 decimal places for precision)
     */
    public function calculateDailyRate(float $monthlyRate, Carbon $referenceDate): float
    {
        $daysInMonth = $referenceDate->daysInMonth;
        return round($monthlyRate / $daysInMonth, 4);
    }

    /**
     * Calculate proration for remainder of month starting from a given date
     * 
     * @param float $monthlyRate Full monthly rate
     * @param Carbon $startDate Start date (e.g., subscription start date)
     * @return float Prorated amount for remainder of month
     */
    public function calculateRemainderOfMonth(float $monthlyRate, Carbon $startDate): float
    {
        $endOfMonth = $startDate->copy()->endOfMonth();
        return $this->calculateProration($monthlyRate, $startDate, $endOfMonth);
    }

    /**
     * Calculate proration for a specific number of days
     * 
     * @param float $monthlyRate Full monthly rate
     * @param int $days Number of days to calculate for
     * @param Carbon $referenceDate Date to use for determining days in month
     * @return float Prorated amount
     */
    public function calculateForDays(float $monthlyRate, int $days, Carbon $referenceDate): float
    {
        $dailyRate = $this->calculateDailyRate($monthlyRate, $referenceDate);
        return round($dailyRate * $days, 2);
    }
}
