<?php

declare(strict_types=1);

namespace Tests\Unit\PIB;

use App\Models\User;
use Illuminate\Support\Carbon;
use Modules\PIB\Models\Invoice;
use Modules\PIB\Models\ServiceUsage;
use Tests\PureUnitTestCase;

final class TestInvoiceLifecycleModel extends Invoice
{
    public bool $saved = false;

    public function save(array $options = []): bool
    {
        $this->saved = true;

        return true;
    }
}

final class TestServiceUsageLifecycleModel extends ServiceUsage
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

class InvoiceServiceUsageLifecycleTest extends PureUnitTestCase
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

    private function invoice(array $attrs = []): TestInvoiceLifecycleModel
    {
        $model = new TestInvoiceLifecycleModel;
        $model->setRawAttributes(array_merge([
            'status' => Invoice::STATUS_DRAFT,
        ], $attrs), true);

        return $model;
    }

    private function usage(array $attrs = []): TestServiceUsageLifecycleModel
    {
        $model = new TestServiceUsageLifecycleModel;
        $model->setRawAttributes(array_merge([
            'status' => ServiceUsage::STATUS_PENDING,
        ], $attrs), true);

        return $model;
    }

    public function test_invoice_transition_to_valid_state_changes_status_and_saves(): void
    {
        $invoice = $this->invoice(['status' => Invoice::STATUS_DRAFT]);

        $invoice->transitionTo(Invoice::STATUS_FINALIZED);

        $this->assertTrue($invoice->saved);
        $this->assertSame(Invoice::STATUS_FINALIZED, $invoice->status);
    }

    public function test_invoice_transition_to_invalid_state_throws_and_does_not_save(): void
    {
        $invoice = $this->invoice(['status' => Invoice::STATUS_DRAFT]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot transition invoice from [draft] to [paid].');

        try {
            $invoice->transitionTo(Invoice::STATUS_PAID);
        } finally {
            $this->assertFalse($invoice->saved);
            $this->assertSame(Invoice::STATUS_DRAFT, $invoice->status);
        }
    }

    public function test_service_usage_approve_updates_status_approver_and_timestamp(): void
    {
        $usage = $this->usage(['status' => ServiceUsage::STATUS_PENDING]);
        $approver = new User;
        $approver->setRawAttributes(['id' => 77], true);

        $usage->approve($approver);

        $this->assertSame(ServiceUsage::STATUS_APPROVED, $usage->lastUpdatePayload['status']);
        $this->assertSame(77, $usage->lastUpdatePayload['approved_by']);
        $this->assertInstanceOf(Carbon::class, $usage->lastUpdatePayload['approved_at']);
    }

    public function test_service_usage_mark_as_billed_updates_status_and_invoice_id(): void
    {
        $usage = $this->usage(['status' => ServiceUsage::STATUS_APPROVED]);
        $invoice = new Invoice;
        $invoice->setRawAttributes(['id' => 501], true);

        $usage->markAsBilled($invoice);

        $this->assertSame(ServiceUsage::STATUS_BILLED, $usage->lastUpdatePayload['status']);
        $this->assertSame(501, $usage->lastUpdatePayload['invoice_id']);
    }
}
