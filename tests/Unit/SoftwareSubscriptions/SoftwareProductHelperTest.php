<?php

declare(strict_types=1);

namespace Tests\Unit\SoftwareSubscriptions;

use Modules\SoftwareSubscriptions\Models\SoftwareProduct;
use Tests\PureUnitTestCase;

class SoftwareProductHelperTest extends PureUnitTestCase
{
    private function product(array $attrs = []): SoftwareProduct
    {
        return new SoftwareProduct(array_merge([
            'default_price' => 12.5,
            'pricing_tiers' => null,
        ], $attrs));
    }

    public function test_calculate_price_uses_default_price_when_no_tiers_or_no_match(): void
    {
        $noTiers = $this->product(['default_price' => 10, 'pricing_tiers' => null]);
        $this->assertSame(30.0, $noTiers->calculatePrice(3));

        $unmatched = $this->product([
            'default_price' => 9,
            'pricing_tiers' => [
                ['min' => 10, 'max' => 20, 'price' => 5],
            ],
        ]);
        $this->assertSame(27.0, $unmatched->calculatePrice(3));
    }

    public function test_calculate_price_uses_applicable_tier_and_numeric_guard(): void
    {
        $product = $this->product([
            'default_price' => 10,
            'pricing_tiers' => [
                ['min' => 1, 'max' => 5, 'price' => 8],
                ['min' => 6, 'max' => 10, 'price' => '7.5'],
            ],
        ]);

        $this->assertSame(24.0, $product->calculatePrice(3));
        $this->assertSame(45.0, $product->calculatePrice(6));

        $badTier = $this->product([
            'default_price' => 10,
            'pricing_tiers' => [
                ['min' => 1, 'max' => 10, 'price' => 'not-numeric'],
            ],
        ]);
        $this->assertSame(0.0, $badTier->calculatePrice(2));
    }

    public function test_get_tier_for_quantity_returns_matching_tier_or_null(): void
    {
        $product = $this->product([
            'pricing_tiers' => [
                ['min' => 1, 'max' => 5, 'name' => 'starter'],
                ['min' => 6, 'max' => 15, 'name' => 'growth'],
            ],
        ]);

        $this->assertSame('starter', $product->getTierForQuantity(3)['name']);
        $this->assertSame('growth', $product->getTierForQuantity(10)['name']);
        $this->assertNull($product->getTierForQuantity(20));
        $this->assertNull($this->product(['pricing_tiers' => null])->getTierForQuantity(5));
    }
}
