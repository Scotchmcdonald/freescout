<?php

declare(strict_types=1);

namespace Tests\Unit\PIB;

use Modules\PIB\Models\InvoiceLineItem;
use Tests\PureUnitTestCase;

final class StubInvoiceLineItem extends InvoiceLineItem
{
    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }
}

final class InvoiceLineItemTest extends PureUnitTestCase
{
    private function lineItem(float $quantity, float $unitPrice): StubInvoiceLineItem
    {
        $item = new StubInvoiceLineItem();
        $item->setRawAttributes(['quantity' => $quantity, 'unit_price' => $unitPrice]);

        return $item;
    }

    public function test_calculate_total_multiplies_quantity_by_unit_price(): void
    {
        $this->assertSame(50.0, $this->lineItem(5, 10)->calculateTotal());
    }

    public function test_calculate_total_rounds_to_two_decimals(): void
    {
        // 3 * 1.005 = 3.015 → rounds to 3.02
        $item = new StubInvoiceLineItem();
        $item->setRawAttributes(['quantity' => 3, 'unit_price' => 1.005]);
        $result = $item->calculateTotal();
        $this->assertEqualsWithDelta(3.02, $result, 0.001);
    }

    public function test_calculate_total_is_zero_when_quantity_is_zero(): void
    {
        $this->assertSame(0.0, $this->lineItem(0, 99.99)->calculateTotal());
    }

    public function test_calculate_total_handles_fractional_quantities(): void
    {
        // 2.5 * 40.00 = 100.00
        $this->assertSame(100.0, $this->lineItem(2.5, 40.0)->calculateTotal());
    }

    public function test_calculate_total_handles_large_values(): void
    {
        $this->assertSame(12500.0, $this->lineItem(100, 125)->calculateTotal());
    }
}
