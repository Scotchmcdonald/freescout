<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\ContractManager\Models\Contract;
use Modules\ContractManager\Services\BillingTemplateService;
use Modules\Crm\Models\Client;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\IntegrationTestCase;

class BillingTemplateServiceTest extends IntegrationTestCase
{
    private BillingTemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->service = new BillingTemplateService;
    }

    public function test_calculate_billable_amount_applies_discount_and_adjustment(): void
    {
        $template = BillingTemplate::factory()->create([
            'product_config' => [
                'discount_percent' => 10,
                'amount_adjustment' => 5.25,
            ],
        ]);

        $template->lineItems()->create([
            'product_type' => 'service_plan',
            'product_name' => 'Core',
            'description' => 'Core plan',
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
            'product_config' => [],
        ]);

        $template->lineItems()->create([
            'product_type' => 'service_plan',
            'product_name' => 'Addon',
            'description' => 'Addon',
            'quantity' => 1,
            'unit_price' => 50,
            'line_total' => 50,
            'product_config' => [],
        ]);

        $template->refresh()->load('lineItems');

        $amount = $this->service->calculateBillableAmount($template);

        // (150 * 0.9) + 5.25 = 140.25
        $this->assertSame(140.25, $amount);
    }

    public function test_get_entitlement_config_merges_only_service_plan_and_managed_services(): void
    {
        $template = BillingTemplate::factory()->create([
            'product_config' => ['base' => 'keep', 'tier' => 'silver'],
        ]);

        $template->lineItems()->create([
            'product_type' => 'service_plan',
            'product_name' => 'Plan',
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
            'product_config' => ['service_window' => '24x7'],
        ]);

        $template->lineItems()->create([
            'product_type' => 'managed_services',
            'product_name' => 'Managed',
            'quantity' => 1,
            'unit_price' => 40,
            'line_total' => 40,
            'product_config' => ['managed_level' => 'gold'],
        ]);

        $template->lineItems()->create([
            'product_type' => 'hardware',
            'product_name' => 'Hardware',
            'quantity' => 1,
            'unit_price' => 10,
            'line_total' => 10,
            'product_config' => ['hardware_ignored' => true],
        ]);

        $template->refresh()->load('lineItems');

        $config = $this->service->getEntitlementConfig($template);

        $this->assertSame('keep', $config['base']);
        $this->assertSame('silver', $config['tier']);
        $this->assertSame('24x7', $config['service_window']);
        $this->assertSame('gold', $config['managed_level']);
        $this->assertArrayNotHasKey('hardware_ignored', $config);
    }

    public function test_trigger_billing_rejects_inactive_template(): void
    {
        $template = BillingTemplate::factory()->create([
            'status' => 'paused',
            'next_invoice_date' => Carbon::parse('2026-01-01'),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot trigger billing for inactive template.');

        $this->service->triggerBilling($template);
    }

    public function test_trigger_billing_rejects_template_with_inactive_contract(): void
    {
        $client = Client::factory()->create();
        $contract = Contract::query()->create([
            'client_id' => $client->id,
            'contract_number' => 'CT-TERM-001',
            'status' => 'terminated',
            'start_date' => Carbon::parse('2026-01-01'),
        ]);

        $template = BillingTemplate::factory()->create([
            'client_id' => $client->id,
            'contract_id' => $contract->id,
            'status' => 'active',
            'next_invoice_date' => Carbon::parse('2026-01-01'),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot trigger billing for inactive contract.');

        $this->service->triggerBilling($template);
    }

    public function test_trigger_billing_advances_next_invoice_date(): void
    {
        $client = Client::factory()->create();
        $contract = Contract::query()->create([
            'client_id' => $client->id,
            'contract_number' => 'CT-ACT-001',
            'status' => 'active',
            'start_date' => Carbon::parse('2026-01-01'),
        ]);

        $template = BillingTemplate::factory()->create([
            'client_id' => $client->id,
            'contract_id' => $contract->id,
            'status' => 'active',
            'billing_cycle' => 'quarterly',
            'next_invoice_date' => Carbon::parse('2026-01-10'),
        ]);

        $updated = $this->service->triggerBilling($template);

        $this->assertSame('2026-04-10', $updated->next_invoice_date?->format('Y-m-d'));
    }

    #[DataProvider('nextBillingDateProvider')]
    public function test_calculate_next_billing_date_branches(string $cycle, string $expected): void
    {
        $result = $this->invokeProtected($this->service, 'calculateNextBillingDate', [
            Carbon::parse('2026-01-15'),
            $cycle,
        ]);

        $this->assertSame($expected, $result->format('Y-m-d'));
    }

    /**
     * @return array<string, array{0:string, 1:string}>
     */
    public static function nextBillingDateProvider(): array
    {
        return [
            'monthly' => ['monthly', '2026-02-15'],
            'quarterly' => ['quarterly', '2026-04-15'],
            'semi annual' => ['semi_annual', '2026-07-15'],
            'annual' => ['annual', '2027-01-15'],
            'unknown defaults monthly' => ['unexpected', '2026-02-15'],
        ];
    }

    public function test_client_billing_summary_calculates_monthly_equivalent_and_lists_templates(): void
    {
        $client = Client::factory()->create();

        $monthly = BillingTemplate::factory()->create([
            'client_id' => $client->id,
            'billing_cycle' => 'monthly',
            'next_invoice_date' => Carbon::parse('2026-02-05'),
            'status' => 'active',
            'name' => 'Monthly Plan',
        ]);

        $monthly->lineItems()->create([
            'product_type' => 'service_plan',
            'product_name' => 'Monthly core',
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
            'product_config' => [],
        ]);

        $quarterly = BillingTemplate::factory()->create([
            'client_id' => $client->id,
            'billing_cycle' => 'quarterly',
            'next_invoice_date' => Carbon::parse('2026-02-01'),
            'status' => 'active',
            'name' => 'Quarterly Plan',
        ]);

        $quarterly->lineItems()->create([
            'product_type' => 'service_plan',
            'product_name' => 'Quarterly core',
            'quantity' => 1,
            'unit_price' => 300,
            'line_total' => 300,
            'product_config' => [],
        ]);

        $annual = BillingTemplate::factory()->create([
            'client_id' => $client->id,
            'billing_cycle' => 'annual',
            'next_invoice_date' => Carbon::parse('2026-03-01'),
            'status' => 'active',
            'name' => 'Annual Plan',
        ]);

        $annual->lineItems()->create([
            'product_type' => 'service_plan',
            'product_name' => 'Annual core',
            'quantity' => 1,
            'unit_price' => 1200,
            'line_total' => 1200,
            'product_config' => [],
        ]);

        $summary = $this->service->getClientBillingSummary($client);

        $this->assertSame(3, $summary['templates_count']);
        $this->assertSame(100.0, $summary['monthly_recurring']);
        $this->assertSame(300.0, $summary['quarterly_recurring']);
        $this->assertSame(1200.0, $summary['annual_recurring']);
        $this->assertSame(300.0, $summary['monthly_equivalent']);
        $this->assertSame('2026-02-01', Carbon::parse((string) $summary['next_billing'])->format('Y-m-d'));
        $this->assertCount(3, $summary['templates']);
    }

    /**
     * @param  list<mixed>  $args
     */
    private function invokeProtected(object $target, string $method, array $args = []): mixed
    {
        $reflection = new \ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($target, $args);
    }
}
