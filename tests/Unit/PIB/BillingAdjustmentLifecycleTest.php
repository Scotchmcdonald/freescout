<?php

declare(strict_types=1);

namespace Tests\Unit\PIB;

use Illuminate\Support\Carbon;
use Modules\PIB\Models\BillingAdjustment;
use Tests\PureUnitTestCase;

final class TestBillingAdjustmentLifecycleModel extends BillingAdjustment
{
    /** @var array<string, mixed> */
    public array $lastUpdatePayload = [];

    public function update(array $attributes = [], array $options = []): bool
    {
        $this->lastUpdatePayload = $attributes;
        $this->setRawAttributes(array_merge($this->attributes, $attributes), true);

        return true;
    }
}

class BillingAdjustmentLifecycleTest extends PureUnitTestCase
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

    private function adjustment(array $attrs = []): TestBillingAdjustmentLifecycleModel
    {
        $model = new TestBillingAdjustmentLifecycleModel;
        $model->setRawAttributes(array_merge([
            'status' => 'pending',
        ], $attrs), true);

        return $model;
    }

    public function test_approve_updates_status_approver_and_timestamp(): void
    {
        $adjustment = $this->adjustment();
        $adjustment->approve(99);

        $this->assertSame('approved', $adjustment->lastUpdatePayload['status']);
        $this->assertSame(99, $adjustment->lastUpdatePayload['approved_by']);
        $this->assertInstanceOf(Carbon::class, $adjustment->lastUpdatePayload['approved_at']);
    }

    public function test_reject_updates_status_only(): void
    {
        $adjustment = $this->adjustment();
        $adjustment->reject();

        $this->assertSame(['status' => 'rejected'], $adjustment->lastUpdatePayload);
    }

    public function test_mark_applied_sets_status_and_applied_timestamp(): void
    {
        $adjustment = $this->adjustment();
        $adjustment->markApplied();

        $this->assertSame('applied', $adjustment->lastUpdatePayload['status']);
        $this->assertInstanceOf(Carbon::class, $adjustment->lastUpdatePayload['applied_at']);
    }
}
