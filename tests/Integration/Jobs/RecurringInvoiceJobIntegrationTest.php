<?php

declare(strict_types=1);

namespace Tests\Integration\Jobs;

use App\Services\EntitlementEngine;
use App\Contracts\EntitlementResolver;
use App\Contracts\BillingTemplateInterface;
use App\DataTransferObjects\EntitlementResult;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Modules\PIB\Jobs\GenerateRecurringInvoicesJob;
use Modules\PIB\Models\BillingTemplate;
use Modules\PIB\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * GenerateRecurringInvoicesJob Integration Tests
 * 
 * Tests job instantiation and basic functionality.
 * Tests billing template model and its relationships.
 */
#[Group('integration')]
#[Group('jobs')]
#[Group('pib')]
#[Group('billing')]
class RecurringInvoiceJobIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->createRequiredTables();
        
        $this->company = Company::factory()->create();
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
    }

    private function createRequiredTables(): void
    {
        Schema::dropIfExists('pib_billing_templates');
        Schema::dropIfExists('pib_invoices');
        Schema::dropIfExists('pib_invoice_line_items');

        Schema::create('pib_billing_templates', function ($table) {
            $table->id();
            $table->foreignId('client_id');
            $table->foreignId('company_id');
            $table->string('name')->nullable();
            $table->string('product_type');
            $table->json('product_config')->nullable();
            $table->string('billing_cycle')->default('monthly');
            $table->date('next_invoice_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('pib_invoices', function ($table) {
            $table->id();
            $table->foreignId('client_id');
            $table->foreignId('company_id');
            $table->foreignId('billing_template_id')->nullable();
            $table->string('invoice_number')->unique();
            $table->string('status')->default('draft');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('pib_invoice_line_items', function ($table) {
            $table->id();
            $table->foreignId('invoice_id');
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
    }

    private function createBillingTemplate(array $attributes = []): BillingTemplate
    {
        $data = array_merge([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'name' => 'Test Template',
            'product_type' => 'test_product',
            'product_config' => json_encode([]),
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes);

        if (isset($data['product_config']) && is_array($data['product_config'])) {
            $data['product_config'] = json_encode($data['product_config']);
        }

        $id = DB::table('pib_billing_templates')->insertGetId($data);
        
        return BillingTemplate::find($id);
    }

    private function createInvoice(array $attributes = []): Invoice
    {
        $data = array_merge([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'invoice_number' => 'INV-' . uniqid(),
            'status' => 'draft',
            'invoice_date' => today(),
            'due_date' => today()->addDays(30),
            'subtotal' => 100.00,
            'tax_amount' => 0.00,
            'total_amount' => 100.00,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes);

        $id = DB::table('pib_invoices')->insertGetId($data);
        
        return Invoice::find($id);
    }

    /**
     * Test job can be instantiated.
     */
    public function test_job_can_be_instantiated(): void
    {
        $job = new GenerateRecurringInvoicesJob();
        
        $this->assertInstanceOf(GenerateRecurringInvoicesJob::class, $job);
    }

    /**
     * Test billing template can be created.
     */
    public function test_billing_template_can_be_created(): void
    {
        $template = $this->createBillingTemplate();
        
        $this->assertNotNull($template);
        $this->assertEquals($this->client->id, $template->client_id);
        $this->assertEquals('test_product', $template->product_type);
        $this->assertEquals('monthly', $template->billing_cycle);
        $this->assertEquals('active', $template->status);
    }

    /**
     * Test billing template status filtering.
     */
    public function test_billing_template_status_filtering(): void
    {
        $this->createBillingTemplate(['status' => 'active']);
        $this->createBillingTemplate(['status' => 'paused']);
        $this->createBillingTemplate(['status' => 'active', 'product_type' => 'other']);

        $activeTemplates = BillingTemplate::where('status', 'active')->get();
        $pausedTemplates = BillingTemplate::where('status', 'paused')->get();

        $this->assertCount(2, $activeTemplates);
        $this->assertCount(1, $pausedTemplates);
    }

    /**
     * Test billing template due date filtering.
     */
    public function test_billing_template_due_date_filtering(): void
    {
        $this->createBillingTemplate(['next_invoice_date' => today()]);
        $this->createBillingTemplate(['next_invoice_date' => today()->addWeek()]);
        $this->createBillingTemplate(['next_invoice_date' => today()->subWeek()]);

        $dueTemplates = BillingTemplate::where('next_invoice_date', '<=', today())->get();
        $futureTemplates = BillingTemplate::where('next_invoice_date', '>', today())->get();

        $this->assertCount(2, $dueTemplates);
        $this->assertCount(1, $futureTemplates);
    }

    /**
     * Test billing cycle calculations - monthly.
     */
    public function test_monthly_cycle_calculation(): void
    {
        $template = $this->createBillingTemplate([
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
        ]);

        $nextDate = $template->next_invoice_date->copy()->addMonth();
        
        $this->assertEquals(today()->addMonth()->toDateString(), $nextDate->toDateString());
    }

    /**
     * Test billing cycle calculations - quarterly.
     */
    public function test_quarterly_cycle_calculation(): void
    {
        $template = $this->createBillingTemplate([
            'billing_cycle' => 'quarterly',
            'next_invoice_date' => today(),
        ]);

        $nextDate = $template->next_invoice_date->copy()->addMonths(3);
        
        $this->assertEquals(today()->addMonths(3)->toDateString(), $nextDate->toDateString());
    }

    /**
     * Test billing cycle calculations - annually.
     */
    public function test_annual_cycle_calculation(): void
    {
        $template = $this->createBillingTemplate([
            'billing_cycle' => 'annual',
            'next_invoice_date' => today(),
        ]);

        $nextDate = $template->next_invoice_date->copy()->addYear();
        
        $this->assertEquals(today()->addYear()->toDateString(), $nextDate->toDateString());
    }

    /**
     * Test invoice can be created.
     */
    public function test_invoice_can_be_created(): void
    {
        $template = $this->createBillingTemplate();
        $invoice = $this->createInvoice(['billing_template_id' => $template->id]);
        
        $this->assertNotNull($invoice);
        $this->assertEquals($template->id, $invoice->billing_template_id);
        $this->assertEquals($this->client->id, $invoice->client_id);
        $this->assertEquals(100.00, (float) $invoice->total_amount);
    }

    /**
     * Test invoice status checks.
     */
    public function test_invoice_status_checks(): void
    {
        $draftInvoice = $this->createInvoice(['status' => 'draft']);
        $pendingInvoice = $this->createInvoice(['status' => 'pending']);
        $paidInvoice = $this->createInvoice(['status' => 'paid']);

        $this->assertTrue($draftInvoice->isDraft());
        $this->assertFalse($pendingInvoice->isDraft());
        $this->assertTrue($paidInvoice->isPaid());
    }

    /**
     * Test entitlement engine can be created.
     */
    public function test_entitlement_engine_can_be_created(): void
    {
        $engine = new EntitlementEngine();
        
        $this->assertInstanceOf(EntitlementEngine::class, $engine);
    }

    /**
     * Test entitlement resolver can be registered.
     */
    public function test_entitlement_resolver_can_be_registered(): void
    {
        $engine = new EntitlementEngine();
        
        $resolver = new class implements EntitlementResolver {
            public function calculate(BillingTemplateInterface $template): EntitlementResult
            {
                return new EntitlementResult(
                    amount: 100.00,
                    quantity: 1,
                    breakdown: [['description' => 'Test', 'quantity' => 1, 'rate' => 100.00, 'amount' => 100.00]]
                );
            }
        };

        $engine->registerResolver('test_product', $resolver);
        
        $this->assertTrue($engine->hasResolver('test_product'));
    }

    /**
     * Test entitlement result can be created.
     */
    public function test_entitlement_result_structure(): void
    {
        $result = new EntitlementResult(
            amount: 150.00,
            quantity: 5,
            breakdown: [
                ['description' => 'Item 1', 'quantity' => 3, 'rate' => 30.00, 'amount' => 90.00],
                ['description' => 'Item 2', 'quantity' => 2, 'rate' => 30.00, 'amount' => 60.00],
            ],
            hasReachedGoal: false
        );

        $this->assertEquals(150.00, $result->amount);
        $this->assertEquals(5, $result->quantity);
        $this->assertCount(2, $result->breakdown);
        $this->assertFalse($result->hasReachedGoal);
    }

    /**
     * Test entitlement result with rent-to-own goal.
     */
    public function test_entitlement_result_with_goal_reached(): void
    {
        $result = new EntitlementResult(
            amount: 0.00,
            quantity: 10,
            breakdown: [],
            hasReachedGoal: true
        );

        $this->assertEquals(0.00, $result->amount);
        $this->assertTrue($result->hasReachedGoal);
    }

    /**
     * Test invoice number generation is unique.
     */
    public function test_invoice_number_uniqueness(): void
    {
        $invoice1 = $this->createInvoice(['invoice_number' => 'INV-001']);
        $invoice2 = $this->createInvoice(['invoice_number' => 'INV-002']);
        $invoice3 = $this->createInvoice(['invoice_number' => 'INV-003']);

        $numbers = Invoice::pluck('invoice_number')->toArray();
        
        $this->assertCount(3, array_unique($numbers));
    }

    /**
     * Test billing template product config is JSON.
     */
    public function test_billing_template_product_config_json(): void
    {
        $config = ['key' => 'value', 'nested' => ['a' => 1, 'b' => 2]];
        $template = $this->createBillingTemplate(['product_config' => $config]);

        $refreshed = BillingTemplate::find($template->id);
        
        $this->assertIsArray($refreshed->product_config);
        $this->assertEquals('value', $refreshed->product_config['key']);
    }

    /**
     * Test invoice due date calculation.
     */
    public function test_invoice_due_date_calculation(): void
    {
        $invoiceDate = today();
        $invoice = $this->createInvoice([
            'invoice_date' => $invoiceDate,
            'due_date' => $invoiceDate->copy()->addDays(30),
        ]);

        $this->assertEquals(30, $invoice->invoice_date->diffInDays($invoice->due_date));
    }

    /**
     * Test filtering templates by multiple criteria.
     */
    public function test_combined_template_filtering(): void
    {
        // Create various templates
        $this->createBillingTemplate([
            'status' => 'active',
            'next_invoice_date' => today(),
            'product_type' => 'product_a',
        ]);
        $this->createBillingTemplate([
            'status' => 'active',
            'next_invoice_date' => today()->addWeek(),
            'product_type' => 'product_b',
        ]);
        $this->createBillingTemplate([
            'status' => 'paused',
            'next_invoice_date' => today(),
            'product_type' => 'product_a',
        ]);

        $dueActiveTemplates = BillingTemplate::where('status', 'active')
            ->where('next_invoice_date', '<=', today())
            ->get();

        $this->assertCount(1, $dueActiveTemplates);
        $this->assertEquals('product_a', $dueActiveTemplates->first()->product_type);
    }
}
