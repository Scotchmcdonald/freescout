<?php

declare(strict_types=1);

namespace Tests\Unit\Payment;

use Modules\Payment\Models\PaymentMethod;
use Tests\PureUnitTestCase;

// ── Stub ──────────────────────────────────────────────────────────────────────

if (! class_exists(StubPaymentMethod::class)) {
    final class StubPaymentMethod extends PaymentMethod
    {
        protected static function booted(): void {}

        public function getDateFormat(): string
        {
            return 'Y-m-d H:i:s';
        }
    }
}

// ── Test class ────────────────────────────────────────────────────────────────

final class PaymentMethodTest extends PureUnitTestCase
{
    // ── getMaskedCardNumber ───────────────────────────────────────────

    public function test_masked_card_number_with_last_four(): void
    {
        $m = new StubPaymentMethod;
        $m->setRawAttributes(['last_four' => '4242']);
        $this->assertSame('****4242', $m->getMaskedCardNumber());
    }

    public function test_masked_card_number_without_last_four(): void
    {
        $m = new StubPaymentMethod;
        $m->setRawAttributes(['last_four' => null]);
        $this->assertSame('****', $m->getMaskedCardNumber());
    }

    // ── getDisplayName ────────────────────────────────────────────────

    public function test_display_name_with_brand_and_last_four(): void
    {
        $m = new StubPaymentMethod;
        $m->setRawAttributes(['card_brand' => 'Visa', 'last_four' => '1234']);
        $this->assertSame('Visa ending in 1234', $m->getDisplayName());
    }

    public function test_display_name_with_null_brand_defaults_to_card(): void
    {
        $m = new StubPaymentMethod;
        $m->setRawAttributes(['card_brand' => null, 'last_four' => '9999']);
        $this->assertSame('Card ending in 9999', $m->getDisplayName());
    }

    // ── isExpired ─────────────────────────────────────────────────────

    public function test_is_expired_when_expires_at_is_in_past(): void
    {
        $m = new StubPaymentMethod;
        $m->setRawAttributes(['expires_at' => now()->subDay()->format('Y-m-d H:i:s')]);
        $this->assertTrue($m->isExpired());
    }

    public function test_is_not_expired_when_expires_at_is_in_future(): void
    {
        $m = new StubPaymentMethod;
        $m->setRawAttributes(['expires_at' => now()->addYear()->format('Y-m-d H:i:s')]);
        $this->assertFalse($m->isExpired());
    }

    public function test_is_not_expired_when_expires_at_is_null(): void
    {
        $m = new StubPaymentMethod;
        $m->setRawAttributes(['expires_at' => null]);
        $this->assertFalse($m->isExpired());
    }

    // ── isNearExpiration ──────────────────────────────────────────────

    public function test_is_near_expiration_when_within_window(): void
    {
        $m = new StubPaymentMethod;
        // Expires in 15 days — within default 30-day window
        $m->setRawAttributes(['expires_at' => now()->addDays(15)->format('Y-m-d H:i:s')]);
        $this->assertTrue($m->isNearExpiration());
    }

    public function test_is_not_near_expiration_when_outside_window(): void
    {
        $m = new StubPaymentMethod;
        // Expires in 60 days — outside default 30-day window
        $m->setRawAttributes(['expires_at' => now()->addDays(60)->format('Y-m-d H:i:s')]);
        $this->assertFalse($m->isNearExpiration());
    }

    public function test_is_not_near_expiration_when_already_expired(): void
    {
        $m = new StubPaymentMethod;
        $m->setRawAttributes(['expires_at' => now()->subDay()->format('Y-m-d H:i:s')]);
        $this->assertFalse($m->isNearExpiration());
    }

    public function test_is_not_near_expiration_when_expires_at_is_null(): void
    {
        $m = new StubPaymentMethod;
        $m->setRawAttributes(['expires_at' => null]);
        $this->assertFalse($m->isNearExpiration());
    }

    public function test_is_near_expiration_respects_custom_days(): void
    {
        $m = new StubPaymentMethod;
        // Expires in 45 days — inside 60-day window
        $m->setRawAttributes(['expires_at' => now()->addDays(45)->format('Y-m-d H:i:s')]);
        $this->assertTrue($m->isNearExpiration(60));
    }

    // ── isValid ───────────────────────────────────────────────────────

    public function test_is_valid_when_active_not_expired_and_has_token(): void
    {
        $m = new StubPaymentMethod;
        $m->setRawAttributes([
            'is_active' => true,
            'status' => 'active',
            'expires_at' => now()->addYear()->format('Y-m-d H:i:s'),
            'helcim_card_token' => 'tok_abc123',
        ]);
        $this->assertTrue($m->isValid());
    }

    public function test_is_not_valid_when_inactive(): void
    {
        $m = new StubPaymentMethod;
        $m->setRawAttributes([
            'is_active' => false,
            'status' => 'active',
            'expires_at' => now()->addYear()->format('Y-m-d H:i:s'),
            'helcim_card_token' => 'tok_abc123',
        ]);
        $this->assertFalse($m->isValid());
    }

    public function test_is_not_valid_when_expired(): void
    {
        $m = new StubPaymentMethod;
        $m->setRawAttributes([
            'is_active' => true,
            'status' => 'active',
            'expires_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'helcim_card_token' => 'tok_abc123',
        ]);
        $this->assertFalse($m->isValid());
    }

    public function test_is_not_valid_without_card_token(): void
    {
        $m = new StubPaymentMethod;
        $m->setRawAttributes([
            'is_active' => true,
            'status' => 'active',
            'expires_at' => now()->addYear()->format('Y-m-d H:i:s'),
            'helcim_card_token' => null,
        ]);
        $this->assertFalse($m->isValid());
    }
}
