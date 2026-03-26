<?php

declare(strict_types=1);

namespace Tests\Unit\SoftwareSubscriptions;

use Modules\SoftwareSubscriptions\Models\ClientSoftwareSubscription;
use Tests\PureUnitTestCase;

// ── Stub ──────────────────────────────────────────────────────────────────────

if (! class_exists(StubClientSoftwareSubscription::class)) {
final class StubClientSoftwareSubscription extends ClientSoftwareSubscription
{
    protected static function booted(): void {}
}
}


// ── Test class ────────────────────────────────────────────────────────────────

final class ClientSoftwareSubscriptionTest extends PureUnitTestCase
{
    // ── getAvailableLicensesAttribute ─────────────────────────────────

    public function test_available_licenses_positive_difference(): void
    {
        $s = new StubClientSoftwareSubscription();
        $s->setRawAttributes(['purchased_quantity' => 10, 'assigned_count' => 3]);
        $this->assertSame(7, $s->available_licenses);
    }

    public function test_available_licenses_is_zero_when_fully_assigned(): void
    {
        $s = new StubClientSoftwareSubscription();
        $s->setRawAttributes(['purchased_quantity' => 5, 'assigned_count' => 5]);
        $this->assertSame(0, $s->available_licenses);
    }

    public function test_available_licenses_clamps_to_zero_when_over_assigned(): void
    {
        $s = new StubClientSoftwareSubscription();
        $s->setRawAttributes(['purchased_quantity' => 3, 'assigned_count' => 7]);
        $this->assertSame(0, $s->available_licenses);
    }

    // ── hasAvailableLicenses ──────────────────────────────────────────

    public function test_has_available_licenses_when_purchased_quantity_is_zero(): void
    {
        // Zero purchased_quantity means unlimited
        $s = new StubClientSoftwareSubscription();
        $s->setRawAttributes(['purchased_quantity' => 0, 'assigned_count' => 100]);
        $this->assertTrue($s->hasAvailableLicenses());
    }

    public function test_has_available_licenses_when_slots_remain(): void
    {
        $s = new StubClientSoftwareSubscription();
        $s->setRawAttributes(['purchased_quantity' => 10, 'assigned_count' => 5]);
        $this->assertTrue($s->hasAvailableLicenses());
    }

    public function test_has_no_available_licenses_when_fully_used(): void
    {
        $s = new StubClientSoftwareSubscription();
        $s->setRawAttributes(['purchased_quantity' => 5, 'assigned_count' => 5]);
        $this->assertFalse($s->hasAvailableLicenses());
    }

    // ── isOverAssigned ────────────────────────────────────────────────

    public function test_is_not_over_assigned_when_purchased_quantity_is_zero(): void
    {
        $s = new StubClientSoftwareSubscription();
        $s->setRawAttributes(['purchased_quantity' => 0, 'assigned_count' => 999]);
        $this->assertFalse($s->isOverAssigned());
    }

    public function test_is_over_assigned_when_assigned_exceeds_purchased(): void
    {
        $s = new StubClientSoftwareSubscription();
        $s->setRawAttributes(['purchased_quantity' => 3, 'assigned_count' => 5]);
        $this->assertTrue($s->isOverAssigned());
    }

    public function test_is_not_over_assigned_when_within_limit(): void
    {
        $s = new StubClientSoftwareSubscription();
        $s->setRawAttributes(['purchased_quantity' => 10, 'assigned_count' => 8]);
        $this->assertFalse($s->isOverAssigned());
    }

    // ── constants ─────────────────────────────────────────────────────

    public function test_billing_constants_are_distinct(): void
    {
        $billings = [
            ClientSoftwareSubscription::BILLING_INCLUDED,
            ClientSoftwareSubscription::BILLING_PASSTHROUGH,
            ClientSoftwareSubscription::BILLING_MARKUP,
            ClientSoftwareSubscription::BILLING_FIXED,
            ClientSoftwareSubscription::BILLING_DIRECT,
        ];
        $this->assertSame(count($billings), count(array_unique($billings)));
    }

    public function test_status_constants_are_distinct(): void
    {
        $statuses = [
            ClientSoftwareSubscription::STATUS_ACTIVE,
            ClientSoftwareSubscription::STATUS_SUSPENDED,
            ClientSoftwareSubscription::STATUS_CANCELLED,
            ClientSoftwareSubscription::STATUS_PENDING,
        ];
        $this->assertSame(count($statuses), count(array_unique($statuses)));
    }
}
