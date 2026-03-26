<?php

declare(strict_types=1);

namespace Tests\Unit\SoftwareSubscriptions;

use Modules\SoftwareSubscriptions\Models\ClientSoftwareSubscription;
use Modules\SoftwareSubscriptions\Models\SoftwareAssignment;
use Modules\SoftwareSubscriptions\Models\SoftwareProduct;
use Tests\PureUnitTestCase;

if (! class_exists(StubSoftwareProduct::class)) {
final class StubSoftwareProduct extends SoftwareProduct
{
    protected static function booted(): void {}

    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }
}
}


if (! class_exists(StubClientSoftwareSubscription::class)) {
final class StubClientSoftwareSubscription extends ClientSoftwareSubscription
{
    protected static function booted(): void {}

    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }
}
}


if (! class_exists(StubSoftwareAssignment::class)) {
final class StubSoftwareAssignment extends SoftwareAssignment
{
    protected static function booted(): void {}

    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }
}
}


final class SoftwareSubscriptionModelsTest extends PureUnitTestCase
{
    // ─── SoftwareProduct.calculatePrice ──────────────────────────────────────

    public function test_product_calculate_price_flat_no_tiers(): void
    {
        $product = new StubSoftwareProduct;
        $product->setRawAttributes(['default_price' => 10.0, 'pricing_tiers' => null]);

        $this->assertSame(50.0, $product->calculatePrice(5));
    }

    public function test_product_calculate_price_empty_array_tiers(): void
    {
        $product = new StubSoftwareProduct;
        $product->setRawAttributes(['default_price' => 5.0, 'pricing_tiers' => json_encode([])]);

        $this->assertSame(15.0, $product->calculatePrice(3));
    }

    public function test_product_calculate_price_tier_found(): void
    {
        $tiers = [
            ['min' => 1, 'max' => 10, 'price' => 8.0],
            ['min' => 11, 'max' => 50, 'price' => 6.0],
        ];
        $product = new StubSoftwareProduct;
        $product->setRawAttributes(['default_price' => 10.0, 'pricing_tiers' => json_encode($tiers)]);

        $this->assertSame(48.0, $product->calculatePrice(6)); // tier 1 price 8 * 6
    }

    public function test_product_calculate_price_higher_tier_found(): void
    {
        $tiers = [
            ['min' => 1, 'max' => 10, 'price' => 8.0],
            ['min' => 11, 'max' => 50, 'price' => 6.0],
        ];
        $product = new StubSoftwareProduct;
        $product->setRawAttributes(['default_price' => 10.0, 'pricing_tiers' => json_encode($tiers)]);

        $this->assertSame(80.0, $product->calculatePrice(10)); // tier 1: 8 * 10
        $this->assertSame(66.0, $product->calculatePrice(11)); // tier 2: 6 * 11
    }

    public function test_product_calculate_price_no_tier_matches_uses_default(): void
    {
        $tiers = [
            ['min' => 10, 'max' => 20, 'price' => 5.0],
        ];
        $product = new StubSoftwareProduct;
        $product->setRawAttributes(['default_price' => 12.0, 'pricing_tiers' => json_encode($tiers)]);

        // quantity 5 doesn't match any tier (min=10), so fall through to default_price
        $this->assertSame(60.0, $product->calculatePrice(5));
    }

    // ─── SoftwareProduct.getTierForQuantity ───────────────────────────────────

    public function test_product_get_tier_for_quantity_null_when_no_tiers(): void
    {
        $product = new StubSoftwareProduct;
        $product->setRawAttributes(['pricing_tiers' => null]);

        $this->assertNull($product->getTierForQuantity(5));
    }

    public function test_product_get_tier_for_quantity_returns_matching_tier(): void
    {
        $tiers = [
            ['min' => 1, 'max' => 5, 'name' => 'Starter', 'price' => 9.0],
            ['min' => 6, 'max' => 20, 'name' => 'Business', 'price' => 7.0],
        ];
        $product = new StubSoftwareProduct;
        $product->setRawAttributes(['pricing_tiers' => json_encode($tiers)]);

        $tier = $product->getTierForQuantity(3);
        $this->assertNotNull($tier);
        $this->assertSame('Starter', $tier['name']);

        $tier2 = $product->getTierForQuantity(10);
        $this->assertNotNull($tier2);
        $this->assertSame('Business', $tier2['name']);
    }

    public function test_product_get_tier_for_quantity_null_when_no_match(): void
    {
        $tiers = [['min' => 10, 'max' => 20, 'price' => 5.0]];
        $product = new StubSoftwareProduct;
        $product->setRawAttributes(['pricing_tiers' => json_encode($tiers)]);

        $this->assertNull($product->getTierForQuantity(5));
    }

    // ─── ClientSoftwareSubscription.getEffectiveUnitPrice ────────────────────

    public function test_subscription_effective_price_included_is_zero(): void
    {
        $product = new StubSoftwareProduct;
        $product->setRawAttributes(['vendor_cost' => 10.0, 'default_price' => 15.0]);

        $sub = new StubClientSoftwareSubscription;
        $sub->setRawAttributes(['billing_behavior' => ClientSoftwareSubscription::BILLING_INCLUDED]);
        $sub->setRelation('product', $product);

        $this->assertSame(0.0, $sub->getEffectiveUnitPrice());
    }

    public function test_subscription_effective_price_direct_is_zero(): void
    {
        $product = new StubSoftwareProduct;
        $product->setRawAttributes(['vendor_cost' => 10.0, 'default_price' => 15.0]);

        $sub = new StubClientSoftwareSubscription;
        $sub->setRawAttributes(['billing_behavior' => ClientSoftwareSubscription::BILLING_DIRECT]);
        $sub->setRelation('product', $product);

        $this->assertSame(0.0, $sub->getEffectiveUnitPrice());
    }

    public function test_subscription_effective_price_passthrough_uses_vendor_cost(): void
    {
        $product = new StubSoftwareProduct;
        $product->setRawAttributes(['vendor_cost' => 8.5, 'default_price' => 15.0]);

        $sub = new StubClientSoftwareSubscription;
        $sub->setRawAttributes(['billing_behavior' => ClientSoftwareSubscription::BILLING_PASSTHROUGH]);
        $sub->setRelation('product', $product);

        $this->assertSame(8.5, $sub->getEffectiveUnitPrice());
    }

    public function test_subscription_effective_price_markup_applies_percentage(): void
    {
        $product = new StubSoftwareProduct;
        $product->setRawAttributes(['vendor_cost' => 10.0, 'default_price' => 15.0]);

        $sub = new StubClientSoftwareSubscription;
        $sub->setRawAttributes([
            'billing_behavior' => ClientSoftwareSubscription::BILLING_MARKUP,
            'markup_percentage' => 20.0,
        ]);
        $sub->setRelation('product', $product);

        // 10.0 * 1.20 = 12.0
        $this->assertSame(12.0, $sub->getEffectiveUnitPrice());
    }

    public function test_subscription_effective_price_fixed_uses_custom_price(): void
    {
        $product = new StubSoftwareProduct;
        $product->setRawAttributes(['vendor_cost' => 10.0, 'default_price' => 15.0]);

        $sub = new StubClientSoftwareSubscription;
        $sub->setRawAttributes([
            'billing_behavior' => ClientSoftwareSubscription::BILLING_FIXED,
            'custom_price' => 25.0,
        ]);
        $sub->setRelation('product', $product);

        $this->assertSame(25.0, $sub->getEffectiveUnitPrice());
    }

    public function test_subscription_effective_price_fixed_falls_back_to_default_price(): void
    {
        $product = new StubSoftwareProduct;
        $product->setRawAttributes(['vendor_cost' => 10.0, 'default_price' => 15.0]);

        $sub = new StubClientSoftwareSubscription;
        $sub->setRawAttributes([
            'billing_behavior' => ClientSoftwareSubscription::BILLING_FIXED,
            'custom_price' => null,
        ]);
        $sub->setRelation('product', $product);

        $this->assertSame(15.0, $sub->getEffectiveUnitPrice());
    }

    public function test_subscription_effective_price_default_behavior_uses_default_price(): void
    {
        $product = new StubSoftwareProduct;
        $product->setRawAttributes(['vendor_cost' => 10.0, 'default_price' => 12.0]);

        $sub = new StubClientSoftwareSubscription;
        $sub->setRawAttributes(['billing_behavior' => 'unknown_behavior']);
        $sub->setRelation('product', $product);

        $this->assertSame(12.0, $sub->getEffectiveUnitPrice());
    }

    // ─── ClientSoftwareSubscription.calculateTotalCost ───────────────────────

    public function test_subscription_total_cost_simple(): void
    {
        $product = new StubSoftwareProduct;
        $product->setRawAttributes(['vendor_cost' => 0.0, 'default_price' => 10.0]);

        $sub = new StubClientSoftwareSubscription;
        $sub->setRawAttributes([
            'billing_behavior' => ClientSoftwareSubscription::BILLING_FIXED,
            'custom_price' => 5.0,
            'assigned_count' => 4,
            'minimum_quantity' => 0,
            'custom_tiers' => null,
        ]);
        $sub->setRelation('product', $product);

        $this->assertSame(20.0, $sub->calculateTotalCost()); // 5 * 4
    }

    public function test_subscription_total_cost_uses_minimum_quantity_when_greater(): void
    {
        $product = new StubSoftwareProduct;
        $product->setRawAttributes(['vendor_cost' => 0.0, 'default_price' => 10.0]);

        $sub = new StubClientSoftwareSubscription;
        $sub->setRawAttributes([
            'billing_behavior' => ClientSoftwareSubscription::BILLING_FIXED,
            'custom_price' => 5.0,
            'assigned_count' => 2,
            'minimum_quantity' => 5,  // minimum > assigned → use 5
            'custom_tiers' => null,
        ]);
        $sub->setRelation('product', $product);

        $this->assertSame(25.0, $sub->calculateTotalCost()); // 5 * 5
    }

    public function test_subscription_total_cost_uses_custom_tiers(): void
    {
        $product = new StubSoftwareProduct;
        $product->setRawAttributes(['vendor_cost' => 0.0, 'default_price' => 10.0]);

        $tiers = [['min' => 1, 'max' => 100, 'price' => 3.0]];
        $sub = new StubClientSoftwareSubscription;
        $sub->setRawAttributes([
            'billing_behavior' => ClientSoftwareSubscription::BILLING_FIXED,
            'custom_price' => 5.0,
            'assigned_count' => 4,
            'minimum_quantity' => 0,
            'custom_tiers' => json_encode($tiers),
        ]);
        $sub->setRelation('product', $product);

        // custom_tiers override → tiered price: 3.0 * 4 = 12.0
        $this->assertSame(12.0, $sub->calculateTotalCost());
    }

    // ─── ClientSoftwareSubscription.available_licenses / predicates ──────────

    public function test_subscription_available_licenses_attribute(): void
    {
        $sub = new StubClientSoftwareSubscription;
        $sub->setRawAttributes(['purchased_quantity' => 10, 'assigned_count' => 4]);

        $this->assertSame(6, $sub->available_licenses);
    }

    public function test_subscription_available_licenses_never_negative(): void
    {
        $sub = new StubClientSoftwareSubscription;
        // assigned > purchased → clamped to 0
        $sub->setRawAttributes(['purchased_quantity' => 5, 'assigned_count' => 8]);

        $this->assertSame(0, $sub->available_licenses);
    }

    public function test_subscription_has_available_licenses_no_limit(): void
    {
        $sub = new StubClientSoftwareSubscription;
        $sub->setRawAttributes(['purchased_quantity' => 0, 'assigned_count' => 100]);

        $this->assertTrue($sub->hasAvailableLicenses());
    }

    public function test_subscription_has_available_licenses_with_room(): void
    {
        $sub = new StubClientSoftwareSubscription;
        $sub->setRawAttributes(['purchased_quantity' => 10, 'assigned_count' => 5]);

        $this->assertTrue($sub->hasAvailableLicenses());
    }

    public function test_subscription_has_available_licenses_full(): void
    {
        $sub = new StubClientSoftwareSubscription;
        $sub->setRawAttributes(['purchased_quantity' => 5, 'assigned_count' => 5]);

        $this->assertFalse($sub->hasAvailableLicenses());
    }

    public function test_subscription_is_over_assigned(): void
    {
        $subNormal = new StubClientSoftwareSubscription;
        $subNormal->setRawAttributes(['purchased_quantity' => 10, 'assigned_count' => 10]);
        $this->assertFalse($subNormal->isOverAssigned());

        $subOver = new StubClientSoftwareSubscription;
        $subOver->setRawAttributes(['purchased_quantity' => 5, 'assigned_count' => 8]);
        $this->assertTrue($subOver->isOverAssigned());
    }

    public function test_subscription_is_not_over_assigned_when_no_limit(): void
    {
        $sub = new StubClientSoftwareSubscription;
        $sub->setRawAttributes(['purchased_quantity' => 0, 'assigned_count' => 1000]);

        $this->assertFalse($sub->isOverAssigned());
    }

    // ─── SoftwareAssignment predicates ───────────────────────────────────────

    public function test_assignment_is_active_when_not_revoked(): void
    {
        $a = new StubSoftwareAssignment;
        $a->setRawAttributes(['revoked_at' => null]);

        $this->assertTrue($a->isActive());
    }

    public function test_assignment_is_not_active_when_revoked(): void
    {
        $a = new StubSoftwareAssignment;
        $a->setRawAttributes(['revoked_at' => '2025-01-01 00:00:00']);

        $this->assertFalse($a->isActive());
    }

    public function test_assignment_is_pending_deployment(): void
    {
        $a = new StubSoftwareAssignment;
        $a->setRawAttributes(['deployment_status' => SoftwareAssignment::DEPLOYMENT_PENDING]);

        $this->assertTrue($a->isPendingDeployment());
    }

    public function test_assignment_is_not_pending_when_deployed(): void
    {
        $a = new StubSoftwareAssignment;
        $a->setRawAttributes(['deployment_status' => SoftwareAssignment::DEPLOYMENT_COMPLETED]);

        $this->assertFalse($a->isPendingDeployment());
    }

    public function test_assignment_is_deployed(): void
    {
        $a = new StubSoftwareAssignment;
        $a->setRawAttributes(['deployment_status' => SoftwareAssignment::DEPLOYMENT_COMPLETED]);

        $this->assertTrue($a->isDeployed());
    }

    public function test_assignment_is_not_deployed_when_pending(): void
    {
        $a = new StubSoftwareAssignment;
        $a->setRawAttributes(['deployment_status' => SoftwareAssignment::DEPLOYMENT_PENDING]);

        $this->assertFalse($a->isDeployed());
    }

    public function test_assignment_deployment_constants_distinct(): void
    {
        $constants = [
            SoftwareAssignment::DEPLOYMENT_PENDING,
            SoftwareAssignment::DEPLOYMENT_IN_PROGRESS,
            SoftwareAssignment::DEPLOYMENT_COMPLETED,
            SoftwareAssignment::DEPLOYMENT_FAILED,
            SoftwareAssignment::DEPLOYMENT_NOT_REQUIRED,
        ];

        $this->assertCount(count($constants), array_unique($constants));
    }

    public function test_assignment_revocation_reason_constants_distinct(): void
    {
        $constants = [
            SoftwareAssignment::REVOKED_USER_DEACTIVATED,
            SoftwareAssignment::REVOKED_ASSET_RETIRED,
            SoftwareAssignment::REVOKED_LICENSE_REASSIGNED,
            SoftwareAssignment::REVOKED_SUBSCRIPTION_CANCELLED,
            SoftwareAssignment::REVOKED_MANUAL,
        ];

        $this->assertCount(5, array_unique($constants));
    }

    // ─── SoftwareSubscriptionSnapshot.booted immutability ────────────────────

    public function test_softwareproduct_constants_are_distinct(): void
    {
        $licenseTypes = [
            SoftwareProduct::LICENSE_PER_USER,
            SoftwareProduct::LICENSE_PER_DEVICE,
            SoftwareProduct::LICENSE_PER_SITE,
            SoftwareProduct::LICENSE_FLAT,
            SoftwareProduct::LICENSE_CONCURRENT,
        ];
        $this->assertCount(5, array_unique($licenseTypes));

        $pricingTypes = [
            SoftwareProduct::PRICING_FLAT,
            SoftwareProduct::PRICING_TIERED,
            SoftwareProduct::PRICING_VOLUME,
        ];
        $this->assertCount(3, array_unique($pricingTypes));
    }

    public function test_subscription_billing_constants_distinct(): void
    {
        $constants = [
            ClientSoftwareSubscription::BILLING_INCLUDED,
            ClientSoftwareSubscription::BILLING_PASSTHROUGH,
            ClientSoftwareSubscription::BILLING_MARKUP,
            ClientSoftwareSubscription::BILLING_FIXED,
            ClientSoftwareSubscription::BILLING_DIRECT,
        ];
        $this->assertCount(5, array_unique($constants));

        $statusConstants = [
            ClientSoftwareSubscription::STATUS_ACTIVE,
            ClientSoftwareSubscription::STATUS_SUSPENDED,
            ClientSoftwareSubscription::STATUS_CANCELLED,
            ClientSoftwareSubscription::STATUS_PENDING,
        ];
        $this->assertCount(4, array_unique($statusConstants));
    }
}
