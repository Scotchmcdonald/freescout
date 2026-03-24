<?php

declare(strict_types=1);

namespace Tests\Unit\PIB;

use Modules\PIB\Models\ClientCredit;
use Modules\PIB\Models\InvoiceLineItem;
use Modules\PIB\Models\ServiceUsage;
use Tests\PureUnitTestCase;

final class TestServiceUsage extends ServiceUsage
{
    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }
}

final class TestInvoiceLineItem extends InvoiceLineItem
{
    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }
}

final class TestClientCredit extends ClientCredit
{
    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }
}

class UsageAndCreditHelperTest extends PureUnitTestCase
{
    private function usage(array $attrs = []): TestServiceUsage
    {
        $model = new TestServiceUsage;
        foreach ($attrs as $key => $value) {
            $model->{$key} = $value;
        }

        return $model;
    }

    public function test_service_types_contains_expected_values_without_duplicates(): void
    {
        $types = ServiceUsage::serviceTypes();

        $this->assertContains(ServiceUsage::TYPE_LABOR, $types);
        $this->assertContains(ServiceUsage::TYPE_CONSULTATION, $types);
        $this->assertContains(ServiceUsage::TYPE_DEVELOPMENT, $types);
        $this->assertContains(ServiceUsage::TYPE_TRAINING, $types);
        $this->assertContains(ServiceUsage::TYPE_SUPPORT, $types);
        $this->assertContains(ServiceUsage::TYPE_PROJECT_MANAGEMENT, $types);
        $this->assertContains(ServiceUsage::TYPE_OTHER, $types);
        $this->assertSame(count($types), count(array_unique($types)));
    }

    public function test_service_usage_calculate_total_with_explicit_and_default_rate(): void
    {
        $explicit = $this->usage(['hours' => 2.5, 'hourly_rate' => 120]);
        $default = $this->usage(['hours' => 1.25, 'hourly_rate' => null]);

        $this->assertSame(300.0, $explicit->calculateTotal());
        $this->assertSame(187.5, $default->calculateTotal());
    }

    public function test_service_usage_status_predicates(): void
    {
        $draft = $this->usage(['status' => ServiceUsage::STATUS_DRAFT]);
        $pending = $this->usage(['status' => ServiceUsage::STATUS_PENDING]);
        $approved = $this->usage(['status' => ServiceUsage::STATUS_APPROVED]);
        $billed = $this->usage(['status' => ServiceUsage::STATUS_BILLED]);

        $this->assertTrue($draft->isDraft());
        $this->assertFalse($draft->isPending());

        $this->assertTrue($pending->isPending());
        $this->assertTrue($approved->isApproved());
        $this->assertTrue($billed->isBilled());
    }

    public function test_service_usage_permission_helpers(): void
    {
        $draft = $this->usage(['status' => ServiceUsage::STATUS_DRAFT]);
        $pending = $this->usage(['status' => ServiceUsage::STATUS_PENDING]);
        $approved = $this->usage(['status' => ServiceUsage::STATUS_APPROVED]);
        $billed = $this->usage(['status' => ServiceUsage::STATUS_BILLED]);

        $this->assertTrue($draft->canEdit());
        $this->assertTrue($pending->canEdit());
        $this->assertFalse($approved->canEdit());
        $this->assertFalse($billed->canEdit());

        $this->assertTrue($draft->canDelete());
        $this->assertTrue($approved->canDelete());
        $this->assertFalse($billed->canDelete());

        $this->assertTrue($pending->canApprove());
        $this->assertFalse($draft->canApprove());
        $this->assertFalse($approved->canApprove());
    }

    public function test_invoice_line_item_calculate_total_rounds_to_two_decimals(): void
    {
        $item = new TestInvoiceLineItem([
            'quantity' => 1.255,
            'unit_price' => 99.999,
        ]);

        $this->assertSame(125.5, $item->calculateTotal());
    }

    public function test_client_credit_balance_accessor_and_mutator_convert_cents_and_dollars(): void
    {
        $credit = new TestClientCredit(['balance_cents' => 12345]);
        $this->assertSame(123.45, $credit->balance);

        $credit->balance = 19.995;

        $this->assertSame(2000, $credit->balance_cents);
        $this->assertSame(20.0, $credit->balance);
    }
}
