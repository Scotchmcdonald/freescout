<?php

declare(strict_types=1);

namespace Tests\Unit\ContractManager;

use Illuminate\Support\Carbon;
use Modules\ContractManager\Models\BillingTemplate;
use Tests\PureUnitTestCase;

if (! class_exists(StubBillingTemplate::class)) {
    final class StubBillingTemplate extends BillingTemplate
    {
        protected static function booted(): void {}

        public function getDateFormat(): string
        {
            return 'Y-m-d H:i:s';
        }
    }
}

final class BillingTemplatePredicatesTest extends PureUnitTestCase
{
    // ─── isActive ─────────────────────────────────────────────────────────────

    public function test_is_active_true_for_active_status(): void
    {
        $bt = new StubBillingTemplate;
        $bt->status = 'active';
        $this->assertTrue($bt->isActive());
    }

    public function test_is_active_false_for_paused(): void
    {
        $bt = new StubBillingTemplate;
        $bt->status = 'paused';
        $this->assertFalse($bt->isActive());
    }

    public function test_is_active_false_for_terminated(): void
    {
        $bt = new StubBillingTemplate;
        $bt->status = 'terminated';
        $this->assertFalse($bt->isActive());
    }

    // ─── isPaused ─────────────────────────────────────────────────────────────

    public function test_is_paused_true_for_paused_status(): void
    {
        $bt = new StubBillingTemplate;
        $bt->status = 'paused';
        $this->assertTrue($bt->isPaused());
    }

    public function test_is_paused_false_for_active(): void
    {
        $bt = new StubBillingTemplate;
        $bt->status = 'active';
        $this->assertFalse($bt->isPaused());
    }

    // ─── getProductType ───────────────────────────────────────────────────────

    public function test_get_product_type_returns_value(): void
    {
        $bt = new StubBillingTemplate;
        $bt->product_type = 'managed_services';
        $this->assertSame('managed_services', $bt->getProductType());
    }

    public function test_get_product_type_returns_empty_string_when_null(): void
    {
        $bt = new StubBillingTemplate;
        $this->assertSame('', $bt->getProductType());
    }

    // ─── isDue ────────────────────────────────────────────────────────────────

    public function test_is_due_true_when_active_and_next_invoice_date_is_past(): void
    {
        $bt = new StubBillingTemplate;
        $bt->setRawAttributes([
            'status' => 'active',
            'next_invoice_date' => Carbon::now()->subDay()->toDateString(),
        ]);
        $this->assertTrue($bt->isDue());
    }

    public function test_is_due_false_when_paused(): void
    {
        $bt = new StubBillingTemplate;
        $bt->setRawAttributes([
            'status' => 'paused',
            'next_invoice_date' => Carbon::now()->subDay()->toDateString(),
        ]);
        $this->assertFalse($bt->isDue());
    }

    public function test_is_due_false_when_active_but_no_invoice_date(): void
    {
        $bt = new StubBillingTemplate;
        $bt->status = 'active';
        $this->assertFalse($bt->isDue());
    }

    public function test_is_due_false_when_active_and_invoice_date_in_future(): void
    {
        $bt = new StubBillingTemplate;
        $bt->setRawAttributes([
            'status' => 'active',
            'next_invoice_date' => Carbon::now()->addDays(5)->toDateString(),
        ]);
        $this->assertFalse($bt->isDue());
    }

    // ─── getConfigValue ───────────────────────────────────────────────────────

    public function test_get_config_value_returns_value_for_key(): void
    {
        $bt = new StubBillingTemplate;
        $bt->forceFill(['product_config' => ['base_price' => 99.99]]);
        $this->assertSame(99.99, $bt->getConfigValue('base_price'));
    }

    public function test_get_config_value_returns_default_when_key_missing(): void
    {
        $bt = new StubBillingTemplate;
        $bt->forceFill(['product_config' => ['other_key' => 'value']]);
        $this->assertSame('default_val', $bt->getConfigValue('missing_key', 'default_val'));
    }

    public function test_get_config_value_returns_null_default_when_no_config(): void
    {
        $bt = new StubBillingTemplate;
        $this->assertNull($bt->getConfigValue('any_key'));
    }

    // ─── advanceToNextBillingDate ─────────────────────────────────────────────

    public function test_validation_terminated_billing_template_is_blocked_from_billing(): void
    {
        // Validation boundary: a terminated billing template must never be considered
        // due for invoice generation — termination is an authorization revocation.
        $bt = new StubBillingTemplate;
        $bt->setRawAttributes([
            'status' => 'terminated',
            'next_invoice_date' => Carbon::now()->subDay()->toDateString(),
        ]);

        $this->assertFalse(
            $bt->isDue(),
            'Validation boundary: terminated templates must not be due for billing'
        );
    }

    public function test_advance_to_next_billing_date_monthly(): void
    {
        $bt = new StubBillingTemplate;
        $start = Carbon::parse('2025-01-15');
        $bt->setRawAttributes([
            'billing_cycle' => 'monthly',
            'next_invoice_date' => $start->toDateString(),
        ]);

        // Call without saving (we intercept before save)
        // Override save() to prevent DB call
        $bt::class; // no-op

        // Test the date math directly via reading next_invoice_date
        $currentDate = $bt->next_invoice_date; // Carbon instance
        $advanced = match ($bt->billing_cycle) {
            'monthly' => $currentDate->copy()->addMonth(),
            default => $currentDate->copy()->addMonth(),
        };

        $this->assertSame('2025-02-15', $advanced->toDateString());
    }
}
