<?php

declare(strict_types=1);

namespace Tests\Unit\PIB;

use Illuminate\Support\Carbon;
use Modules\PIB\Models\BillingAdjustment;
use Modules\PIB\Models\ReconciliationDiscrepancy;
use Tests\PureUnitTestCase;

final class TestBillingAdjustment extends BillingAdjustment
{
    public function getAttribute($key): mixed
    {
        if ($key === 'applied_at') {
            $value = $this->attributes[$key] ?? null;
            if ($value instanceof Carbon) {
                return $value;
            }

            return $value ? Carbon::parse((string) $value) : null;
        }

        return parent::getAttribute($key);
    }
}

class DiscrepancyAndAdjustmentHelperTest extends PureUnitTestCase
{
    private function discrepancy(array $attrs = []): ReconciliationDiscrepancy
    {
        return new ReconciliationDiscrepancy(array_merge([
            'resolution_status' => 'pending',
            'severity' => 'low',
        ], $attrs));
    }

    private function adjustment(array $attrs = []): TestBillingAdjustment
    {
        $model = new TestBillingAdjustment;
        $model->setRawAttributes(array_merge([
            'status' => 'pending',
            'adjustment_type' => 'asset_count',
            'old_value' => 10,
            'new_value' => 12,
            'applied_at' => null,
        ], $attrs), true);

        return $model;
    }

    public function test_discrepancy_status_predicates(): void
    {
        $this->assertTrue($this->discrepancy(['resolution_status' => 'resolved'])->isResolved());
        $this->assertTrue($this->discrepancy(['resolution_status' => 'auto_corrected'])->isResolved());
        $this->assertFalse($this->discrepancy(['resolution_status' => 'pending'])->isResolved());

        $this->assertTrue($this->discrepancy(['resolution_status' => 'pending'])->requiresManualReview());
        $this->assertTrue($this->discrepancy(['resolution_status' => 'manual_review'])->requiresManualReview());
        $this->assertFalse($this->discrepancy(['resolution_status' => 'resolved'])->requiresManualReview());

        $this->assertTrue($this->discrepancy(['severity' => 'critical'])->isCritical());
        $this->assertFalse($this->discrepancy(['severity' => 'high'])->isCritical());
    }

    public function test_discrepancy_severity_and_resolution_info_mappings(): void
    {
        $critical = $this->discrepancy(['severity' => 'critical'])->getSeverityInfo();
        $medium = $this->discrepancy(['severity' => 'medium'])->getSeverityInfo();
        $unknown = $this->discrepancy(['severity' => 'other'])->getSeverityInfo();

        $this->assertSame('danger', $critical['color']);
        $this->assertSame('Medium', $medium['label']);
        $this->assertSame('unknown', $unknown['severity']);

        $pending = $this->discrepancy(['resolution_status' => 'pending'])->getResolutionInfo();
        $resolved = $this->discrepancy(['resolution_status' => 'resolved'])->getResolutionInfo();
        $unknownStatus = $this->discrepancy(['resolution_status' => 'other'])->getResolutionInfo();

        $this->assertSame('Pending Review', $pending['label']);
        $this->assertSame('success', $resolved['color']);
        $this->assertSame('unknown', $unknownStatus['status']);
    }

    public function test_billing_adjustment_helpers_cover_value_type_and_state_checks(): void
    {
        $this->assertSame(2.0, $this->adjustment(['old_value' => 10, 'new_value' => 12])->value_change);
        $this->assertSame(-3.5, $this->adjustment(['old_value' => 10.5, 'new_value' => 7])->value_change);
        $this->assertSame(5.0, $this->adjustment(['old_value' => null, 'new_value' => 5])->value_change);

        $this->assertSame('Asset Count Correction', $this->adjustment(['adjustment_type' => 'asset_count'])->getTypeLabel());
        $this->assertSame('Rate Change (Backdated)', $this->adjustment(['adjustment_type' => 'rate_change'])->getTypeLabel());
        $this->assertSame('Custom Type', $this->adjustment(['adjustment_type' => 'custom_type'])->getTypeLabel());

        $this->assertTrue($this->adjustment(['status' => 'pending'])->canBeApproved());
        $this->assertFalse($this->adjustment(['status' => 'approved'])->canBeApproved());

        $this->assertTrue($this->adjustment(['status' => 'approved', 'applied_at' => null])->canBeApplied());
        $this->assertFalse($this->adjustment(['status' => 'approved', 'applied_at' => '2026-03-20 00:00:00'])->canBeApplied());
        $this->assertFalse($this->adjustment(['status' => 'pending', 'applied_at' => null])->canBeApplied());
    }
}
