<?php

declare(strict_types=1);

namespace Tests\Integration\PIB;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\Crm\Models\Client;
use Modules\PIB\Models\Invoice;
use Modules\PIB\Services\InvoiceGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\IntegrationTestCase;

class InvoiceGeneratorTest extends IntegrationTestCase
{
    private InvoiceGenerator $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new InvoiceGenerator;
    }

    #[DataProvider('periodEndProvider')]
    public function test_calculate_period_end_handles_supported_cycles(string $cycle, string $expected): void
    {
        $start = Carbon::parse('2026-01-15 00:00:00');

        $result = $this->invokeProtected($this->service, 'calculatePeriodEnd', [$start, $cycle]);

        $this->assertSame($expected, $result->format('Y-m-d H:i:s'));
    }

    /**
     * @return array<string, array{0:string, 1:string}>
     */
    public static function periodEndProvider(): array
    {
        return [
            'monthly' => ['monthly', '2026-02-14 23:59:59'],
            'quarterly' => ['quarterly', '2026-04-14 23:59:59'],
            'semi annual' => ['semi_annual', '2026-07-14 23:59:59'],
            'annual' => ['annual', '2027-01-14 23:59:59'],
            'annually alias' => ['annually', '2027-01-14 23:59:59'],
            'unknown defaults monthly' => ['weird', '2026-02-14 23:59:59'],
        ];
    }

    public function test_publish_rejects_non_draft_invoice(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'submitted']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only draft invoices can be published');

        $this->service->publish($invoice);
    }

    public function test_publish_moves_draft_to_submitted(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'draft']);

        $published = $this->service->publish($invoice);

        $this->assertSame('submitted', $published->status);
    }

    public function test_generate_invoice_number_increments_for_same_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-01 10:00:00'));

        Invoice::factory()->create(['created_at' => now(), 'updated_at' => now()]);

        $number = $this->invokeProtected($this->service, 'generateInvoiceNumber', [1]);

        $this->assertSame('INV-20260301-0002', $number);

        Carbon::setTestNow();
    }

    public function test_advance_next_invoice_date_updates_active_templates_by_cycle(): void
    {
        $template = BillingTemplate::factory()->create([
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'next_invoice_date' => Carbon::parse('2026-01-05'),
        ]);

        $this->invokeProtected($this->service, 'advanceNextInvoiceDate', [$template]);
        $template->refresh();

        $this->assertSame('2026-02-05', $template->next_invoice_date?->format('Y-m-d'));
    }

    public function test_advance_next_invoice_date_clears_terminated_templates(): void
    {
        $template = BillingTemplate::factory()->create([
            'status' => 'terminated',
            'billing_cycle' => 'annual',
            'next_invoice_date' => Carbon::parse('2026-01-05'),
        ]);

        $this->invokeProtected($this->service, 'advanceNextInvoiceDate', [$template]);
        $template->refresh();

        $this->assertNull($template->next_invoice_date);
    }

    public function test_generate_due_invoices_skips_unsupported_templates_and_continues_on_failure(): void
    {
        $supported = new BillingTemplate;
        $supported->id = 101;

        $unsupported = new class
        {
            public int $id = 202;
        };

        $generator = $this->getMockBuilder(InvoiceGenerator::class)
            ->onlyMethods(['getDueTemplates', 'generateFromTemplate'])
            ->getMock();

        $generator->expects($this->once())
            ->method('getDueTemplates')
            ->willReturn(new Collection([$supported, $unsupported]));

        $generator->expects($this->once())
            ->method('generateFromTemplate')
            ->with($supported)
            ->willThrowException(new \RuntimeException('boom'));

        $result = $generator->generateDueInvoices();

        $this->assertSame([], $result);
    }

    public function test_generate_from_template_creates_invoice_line_items_and_advances_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-01 09:00:00'));

        $client = Client::factory()->create();
        $template = BillingTemplate::factory()->create([
            'client_id' => $client->id,
            'product_type' => 'service_plan',
            'billing_cycle' => 'monthly',
            'next_invoice_date' => Carbon::parse('2026-02-01'),
            'product_config' => [
                'plan_name' => 'Gold Managed Plan',
                'base_price' => 250.0,
            ],
            'status' => 'active',
        ]);

        $invoice = $this->service->generateFromTemplate($template, Carbon::parse('2026-02-01'));

        $invoice->refresh()->load('lineItems');
        $template->refresh();

        $this->assertSame('draft', $invoice->status);
        $this->assertSame($template->id, $invoice->billing_template_id);
        $this->assertGreaterThan(0, $invoice->lineItems->count());
        $this->assertSame(250.0, (float) $invoice->total_amount);
        $this->assertSame('2026-03-01', $template->next_invoice_date?->format('Y-m-d'));

        Carbon::setTestNow();
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
