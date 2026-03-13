<?php

declare(strict_types=1);

namespace Tests\Integration\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Modules\Payment\Jobs\ProcessInvoicePayment;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentMethod;
use Modules\PIB\Models\Invoice;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * ProcessInvoicePayment Job Integration Tests
 *
 * Tests job instantiation and basic properties.
 * Note: Full integration tests require the Invoice model to have
 * proper company relationship and total accessor, which need to be
 * added for complete payment processing workflow.
 */
#[Group('integration')]
#[Group('jobs')]
#[Group('payment')]
#[Group('financial')]
class ProcessInvoicePaymentJobIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Client $client;
    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createRequiredTables();

        $this->company = Company::factory()->create();
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);

        $this->paymentMethod = PaymentMethod::factory()->default()->create([
            'company_id' => $this->company->id,
        ]);
    }

    private function createRequiredTables(): void
    {
        Schema::dropIfExists('pib_invoices');

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
            $table->string('currency', 3)->default('USD');
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    private function createInvoice(array $attributes = []): Invoice
    {
        $data = array_merge([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'invoice_number' => 'INV-'.uniqid(),
            'status' => 'pending',
            'invoice_date' => today(),
            'due_date' => today()->addDays(30),
            'subtotal' => 100.00,
            'tax_amount' => 0.00,
            'total_amount' => 100.00,
            'currency' => 'USD',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes);

        $id = DB::table('pib_invoices')->insertGetId($data);

        return Invoice::find($id);
    }

    /**
     * Test job can be instantiated with invoice.
     */
    public function test_job_can_be_instantiated(): void
    {
        $invoice = $this->createInvoice();

        $job = new ProcessInvoicePayment($invoice);

        $this->assertInstanceOf(ProcessInvoicePayment::class, $job);
        $this->assertSame($invoice->id, $job->invoice->id);
    }

    /**
     * Test job has correct retry configuration.
     */
    public function test_job_retry_configuration(): void
    {
        $invoice = $this->createInvoice();
        $job = new ProcessInvoicePayment($invoice);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(120, $job->timeout);
        $this->assertEquals(300, $job->backoff);
    }

    /**
     * Test job accepts optional payment method id.
     */
    public function test_job_accepts_payment_method_id(): void
    {
        $invoice = $this->createInvoice();
        $job = new ProcessInvoicePayment($invoice, $this->paymentMethod->id);

        $this->assertEquals($this->paymentMethod->id, $job->paymentMethodId);
    }

    /**
     * Test job accepts options array.
     */
    public function test_job_accepts_options(): void
    {
        $invoice = $this->createInvoice();
        $options = ['test_mode' => true, 'custom_reference' => 'TEST-123'];

        $job = new ProcessInvoicePayment($invoice, null, $options);

        $this->assertEquals($options, $job->options);
    }

    /**
     * Test invoice can be serialized in job.
     */
    public function test_job_serializes_invoice(): void
    {
        $invoice = $this->createInvoice();
        $job = new ProcessInvoicePayment($invoice);

        // Serialize and unserialize to test serialization
        $serialized = serialize($job);
        $unserialized = unserialize($serialized);

        $this->assertEquals($invoice->id, $unserialized->invoice->id);
    }

    /**
     * Test payment model scopes work correctly.
     */
    public function test_payment_model_scopes(): void
    {
        $invoice = $this->createInvoice();

        // Create successful payment
        Payment::factory()->create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'status' => 'successful',
        ]);

        // Create pending payment
        Payment::factory()->create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'status' => 'pending',
        ]);

        // Create failed payment for another invoice
        $otherInvoice = $this->createInvoice();
        Payment::factory()->create([
            'company_id' => $this->company->id,
            'invoice_id' => $otherInvoice->id,
            'status' => 'failed',
        ]);

        // Test scopes
        $successfulPayments = Payment::forInvoice($invoice->id)->successful()->get();
        $this->assertCount(1, $successfulPayments);

        $allForInvoice = Payment::forInvoice($invoice->id)->get();
        $this->assertCount(2, $allForInvoice);

        $pendingPayments = Payment::pending()->get();
        $this->assertCount(1, $pendingPayments);
    }

    /**
     * Test payment method validation scopes.
     */
    public function test_payment_method_scopes(): void
    {
        // Create expired payment method
        PaymentMethod::factory()->expired()->create([
            'company_id' => $this->company->id,
        ]);

        // Create inactive payment method
        PaymentMethod::factory()->inactive()->create([
            'company_id' => $this->company->id,
        ]);

        // Default method created in setUp should be active and not expired
        $activeDefault = PaymentMethod::where('company_id', $this->company->id)
            ->active()
            ->default()
            ->notExpired()
            ->first();

        $this->assertNotNull($activeDefault);
        $this->assertEquals($this->paymentMethod->id, $activeDefault->id);
    }

    /**
     * Test idempotency check works with existing successful payment.
     */
    public function test_idempotency_detection(): void
    {
        $invoice = $this->createInvoice();

        // No existing payment
        $existingPayment = Payment::forInvoice($invoice->id)
            ->successful()
            ->first();
        $this->assertNull($existingPayment);

        // Create successful payment
        Payment::factory()->create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'status' => 'successful',
        ]);

        // Now should find existing payment
        $existingPayment = Payment::forInvoice($invoice->id)
            ->successful()
            ->first();
        $this->assertNotNull($existingPayment);
    }

    /**
     * Test payment method belongs to company validation.
     */
    public function test_payment_method_company_validation(): void
    {
        $otherCompany = Company::factory()->create();
        $otherPaymentMethod = PaymentMethod::factory()->default()->create([
            'company_id' => $otherCompany->id,
        ]);

        // Should not find other company's payment method when filtering
        $companyPaymentMethods = PaymentMethod::where('company_id', $this->company->id)
            ->active()
            ->default()
            ->notExpired()
            ->get();

        $this->assertFalse($companyPaymentMethods->contains($otherPaymentMethod));
    }

    /**
     * Test invoice total amount retrieval.
     */
    public function test_invoice_total_amount(): void
    {
        $invoice = $this->createInvoice(['total_amount' => 250.00]);

        $this->assertEquals(250.00, $invoice->total_amount);
    }

    /**
     * Test invoice status checks.
     */
    public function test_invoice_status_checks(): void
    {
        $pendingInvoice = $this->createInvoice(['status' => 'pending']);
        $paidInvoice = $this->createInvoice(['status' => 'paid']);
        $draftInvoice = $this->createInvoice(['status' => 'draft']);

        $this->assertFalse($pendingInvoice->isPaid());
        $this->assertTrue($paidInvoice->isPaid());
        $this->assertTrue($draftInvoice->isDraft());
    }

    /**
     * Test company has payment methods relationship.
     */
    public function test_company_payment_methods_relationship(): void
    {
        $companyMethods = $this->company->paymentMethods()
            ->active()
            ->default()
            ->notExpired()
            ->get();

        $this->assertCount(1, $companyMethods);
        $this->assertEquals($this->paymentMethod->id, $companyMethods->first()->id);
    }
}
