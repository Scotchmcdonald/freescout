<?php

declare(strict_types=1);

namespace Tests\Unit\ContractManager;

use Illuminate\Support\Carbon;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\ContractManager\Models\ContractSchedule;
use Tests\PureUnitTestCase;

final class TestBillingTemplate extends BillingTemplate
{
    public bool $saved = false;

    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }

    public function save(array $options = []): bool
    {
        $this->saved = true;

        return true;
    }

    public function getAttribute($key): mixed
    {
        if ($key === 'next_invoice_date') {
            $value = $this->attributes[$key] ?? null;
            if ($value instanceof Carbon) {
                return $value;
            }

            return $value ? Carbon::parse((string) $value) : null;
        }

        return parent::getAttribute($key);
    }
}

final class TestContractSchedule extends ContractSchedule
{
    public bool $saved = false;

    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }

    public function save(array $options = []): bool
    {
        $this->saved = true;

        return true;
    }

    public function getAttribute($key): mixed
    {
        if ($key === 'next_date') {
            $value = $this->attributes[$key] ?? null;
            if ($value instanceof Carbon) {
                return $value;
            }

            return $value ? Carbon::parse((string) $value) : null;
        }

        return parent::getAttribute($key);
    }
}

class BillingAndScheduleHelperTest extends PureUnitTestCase
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

    private function template(array $attrs = []): TestBillingTemplate
    {
        $model = new TestBillingTemplate;
        foreach ($attrs as $key => $value) {
            $model->{$key} = $value;
        }

        return $model;
    }

    private function schedule(array $attrs = []): TestContractSchedule
    {
        $model = new TestContractSchedule;
        foreach ($attrs as $key => $value) {
            $model->{$key} = $value;
        }

        return $model;
    }

    public function test_billing_template_product_and_status_helpers(): void
    {
        $active = $this->template(['product_type' => 'service_plan', 'status' => 'active']);
        $paused = $this->template(['product_type' => 'rent_to_own', 'status' => 'paused']);

        $this->assertSame('service_plan', $active->getProductType());
        $this->assertTrue($active->isActive());
        $this->assertFalse($active->isPaused());

        $this->assertSame('rent_to_own', $paused->getProductType());
        $this->assertFalse($paused->isActive());
        $this->assertTrue($paused->isPaused());
    }

    public function test_billing_template_is_due_requires_active_and_due_date(): void
    {
        $due = $this->template(['status' => 'active', 'next_invoice_date' => Carbon::parse('2026-03-24 00:00:00')]);
        $future = $this->template(['status' => 'active', 'next_invoice_date' => Carbon::parse('2026-03-30 00:00:00')]);
        $inactive = $this->template(['status' => 'paused', 'next_invoice_date' => Carbon::parse('2026-03-20 00:00:00')]);
        $missing = $this->template(['status' => 'active', 'next_invoice_date' => null]);

        $this->assertTrue($due->isDue());
        $this->assertFalse($future->isDue());
        $this->assertFalse($inactive->isDue());
        $this->assertFalse($missing->isDue());
    }

    public function test_billing_template_get_config_value_uses_default_when_missing(): void
    {
        $template = $this->template([
            'product_config' => [
                'limits' => ['users' => 25],
                'flags' => ['prorate' => true],
            ],
        ]);

        $this->assertSame(25, $template->getConfigValue('limits.users'));
        $this->assertTrue($template->getConfigValue('flags.prorate'));
        $this->assertSame('fallback', $template->getConfigValue('limits.missing', 'fallback'));
    }

    public function test_billing_template_advance_to_next_billing_date_for_each_cycle(): void
    {
        $monthly = $this->template(['billing_cycle' => 'monthly', 'next_invoice_date' => Carbon::parse('2026-03-24 00:00:00')]);
        $quarterly = $this->template(['billing_cycle' => 'quarterly', 'next_invoice_date' => Carbon::parse('2026-03-24 00:00:00')]);
        $semi = $this->template(['billing_cycle' => 'semi_annual', 'next_invoice_date' => Carbon::parse('2026-03-24 00:00:00')]);
        $annual = $this->template(['billing_cycle' => 'annual', 'next_invoice_date' => Carbon::parse('2026-03-24 00:00:00')]);
        $default = $this->template(['billing_cycle' => 'custom', 'next_invoice_date' => Carbon::parse('2026-03-24 00:00:00')]);

        $monthly->advanceToNextBillingDate();
        $quarterly->advanceToNextBillingDate();
        $semi->advanceToNextBillingDate();
        $annual->advanceToNextBillingDate();
        $default->advanceToNextBillingDate();

        $this->assertSame('2026-04-24', $monthly->next_invoice_date->format('Y-m-d'));
        $this->assertSame('2026-06-24', $quarterly->next_invoice_date->format('Y-m-d'));
        $this->assertSame('2026-09-24', $semi->next_invoice_date->format('Y-m-d'));
        $this->assertSame('2027-03-24', $annual->next_invoice_date->format('Y-m-d'));
        $this->assertSame('2026-04-24', $default->next_invoice_date->format('Y-m-d'));

        $this->assertTrue($monthly->saved);
        $this->assertTrue($default->saved);
    }

    public function test_billing_template_advance_sets_now_when_next_invoice_date_is_missing(): void
    {
        $template = $this->template(['billing_cycle' => 'monthly', 'next_invoice_date' => null]);

        $template->advanceToNextBillingDate();

        $this->assertSame('2026-04-24', $template->next_invoice_date->format('Y-m-d'));
    }

    public function test_contract_schedule_type_and_due_helpers(): void
    {
        $billingDue = $this->schedule([
            'schedule_type' => 'billing',
            'is_active' => true,
            'next_date' => Carbon::parse('2026-03-24 00:00:00'),
        ]);
        $renewalFuture = $this->schedule([
            'schedule_type' => 'renewal',
            'is_active' => true,
            'next_date' => Carbon::parse('2026-03-30 00:00:00'),
        ]);

        $this->assertTrue($billingDue->isBillingSchedule());
        $this->assertFalse($billingDue->isRenewalSchedule());
        $this->assertTrue($billingDue->isDue());

        $this->assertTrue($renewalFuture->isRenewalSchedule());
        $this->assertFalse($renewalFuture->isBillingSchedule());
        $this->assertFalse($renewalFuture->isDue());
    }

    public function test_contract_schedule_advance_to_next_for_each_frequency(): void
    {
        $monthly = $this->schedule(['frequency' => 'monthly', 'next_date' => Carbon::parse('2026-03-24 00:00:00')]);
        $quarterly = $this->schedule(['frequency' => 'quarterly', 'next_date' => Carbon::parse('2026-03-24 00:00:00')]);
        $semi = $this->schedule(['frequency' => 'semi_annual', 'next_date' => Carbon::parse('2026-03-24 00:00:00')]);
        $annual = $this->schedule(['frequency' => 'annual', 'next_date' => Carbon::parse('2026-03-24 00:00:00')]);
        $default = $this->schedule(['frequency' => 'custom', 'next_date' => Carbon::parse('2026-03-24 00:00:00')]);

        $monthly->advanceToNext();
        $quarterly->advanceToNext();
        $semi->advanceToNext();
        $annual->advanceToNext();
        $default->advanceToNext();

        $this->assertSame('2026-04-24', $monthly->next_date->format('Y-m-d'));
        $this->assertSame('2026-06-24', $quarterly->next_date->format('Y-m-d'));
        $this->assertSame('2026-09-24', $semi->next_date->format('Y-m-d'));
        $this->assertSame('2027-03-24', $annual->next_date->format('Y-m-d'));
        $this->assertSame('2026-04-24', $default->next_date->format('Y-m-d'));

        $this->assertNotNull($monthly->last_processed_at);
        $this->assertTrue($monthly->saved);
    }
}
