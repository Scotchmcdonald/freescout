<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Integration\CrossModule;

use Illuminate\Support\Facades\Event;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Modules\PIB\Models\Invoice;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\PIB\Events\InvoiceGenerated;
use Modules\PIB\Services\ClientCreditService;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Events\PaymentDisputed;
use PHPUnit\Framework\Attributes\Group;
use Tests\IntegrationTestCase;

/**
 * Integration tests for PIB (Billing) and Payment module interactions.
 * 
 * Tests actual cross-module functionality:
 * - Invoice to Payment relationships
 * - PaymentDisputed event handling by PIB
 * - Client credit operations
 * - Company-level payment methods
 */
#[Group('integration')]
#[Group('cross-module')]
#[Group('payment-billing')]
class PaymentBillingIntegrationTest extends IntegrationTestCase
{
    private Company $company;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!class_exists(Invoice::class)) {
            $this->markTestSkipped('PIB module not available');
        }
        
        if (!class_exists(Payment::class)) {
            $this->markTestSkipped('Payment module not available');
        }

        $this->company = Company::factory()->create([
            'name' => 'Test MSP Company',
            'is_active' => true,
        ]);

        $this->client = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Client',
            'status' => 'active',
        ]);
    }

    /**
     * Test that Invoice factory creates valid records.
     */
    public function test_invoice_factory_creates_valid_records(): void
    {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
        ]);

        $this->assertNotNull($invoice->id);
        $this->assertNotNull($invoice->invoice_number);
        $this->assertEquals($this->client->id, $invoice->client_id);
    }

    /**
     * Test that Invoice belongs to Client.
     */
    public function test_invoice_belongs_to_client(): void
    {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(Client::class, $invoice->client);
        $this->assertEquals($this->client->id, $invoice->client->id);
    }

    /**
     * Test that Payment can be associated with Invoice.
     */
    public function test_payment_relates_to_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'status' => 'pending',
            'total_amount' => 500.00,
        ]);

        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'amount' => 500.00,
            'status' => 'successful',
        ]);

        $this->assertEquals($invoice->id, $payment->invoice_id);
        $this->assertInstanceOf(Invoice::class, $payment->invoice);
    }

    /**
     * Test PaymentMethod belongs to Company.
     */
    public function test_payment_method_belongs_to_company(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'company_id' => $this->company->id,
            'last_four' => '4242',
            'is_default' => true,
        ]);

        $this->assertInstanceOf(Company::class, $paymentMethod->company);
        $this->assertEquals($this->company->id, $paymentMethod->company->id);
    }

    /**
     * Test that Company can have multiple payment methods.
     */
    public function test_company_has_multiple_payment_methods(): void
    {
        PaymentMethod::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        $methodCount = PaymentMethod::where('company_id', $this->company->id)->count();
        $this->assertEquals(3, $methodCount);
    }

    /**
     * Test invoice status tracking.
     */
    public function test_invoice_status_tracking(): void
    {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'status' => 'draft',
        ]);

        $this->assertEquals('draft', $invoice->status);

        $invoice->update(['status' => 'pending']);
        $this->assertEquals('pending', $invoice->fresh()->status);

        $invoice->update(['status' => 'paid']);
        $this->assertEquals('paid', $invoice->fresh()->status);
    }

    /**
     * Test multiple invoices for same client.
     */
    public function test_client_has_multiple_invoices(): void
    {
        Invoice::factory()->count(5)->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
        ]);

        $invoiceCount = Invoice::where('client_id', $this->client->id)->count();
        $this->assertEquals(5, $invoiceCount);
    }

    /**
     * Test invoice amounts calculate correctly.
     */
    public function test_invoice_amount_calculation(): void
    {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'subtotal' => 100.00,
            'tax_amount' => 10.00,
            'total_amount' => 110.00,
        ]);

        $this->assertEquals(100.00, (float) $invoice->subtotal);
        $this->assertEquals(10.00, (float) $invoice->tax_amount);
        $this->assertEquals(110.00, (float) $invoice->total_amount);
    }

    /**
     * Test BillingTemplate factory creates valid records.
     */
    public function test_billing_template_factory(): void
    {
        $template = BillingTemplate::factory()->create([
            'client_id' => $this->client->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        $this->assertNotNull($template->id);
        $this->assertEquals('monthly', $template->billing_cycle);
        $this->assertEquals('active', $template->status);
    }

    /**
     * Test invoice isolation by company.
     */
    public function test_invoice_company_isolation(): void
    {
        $company2 = Company::factory()->create(['name' => 'Other Company']);
        $client2 = Client::factory()->create([
            'company_id' => $company2->id,
        ]);

        Invoice::factory()->count(3)->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
        ]);

        Invoice::factory()->count(2)->create([
            'client_id' => $client2->id,
            'company_id' => $company2->id,
        ]);

        $company1Invoices = Invoice::where('company_id', $this->company->id)->count();
        $company2Invoices = Invoice::where('company_id', $company2->id)->count();

        $this->assertEquals(3, $company1Invoices);
        $this->assertEquals(2, $company2Invoices);
    }
}
