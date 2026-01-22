<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use Modules\PIB\Services\ClientCreditService;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * ClientCreditService Integration Tests
 * 
 * Tests credit balance management for clients in the billing system.
 * This service handles applying and managing credits that can be
 * used to offset invoice amounts.
 * 
 * Critical for:
 * - Accurate credit tracking
 * - Credit application to invoices
 * - Audit trail for financial reconciliation
 */
#[Group('integration')]
#[Group('services')]
#[Group('pib')]
#[Group('financial')]
class ClientCreditServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClientCreditService $service;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Drop and recreate test tables required by ClientCreditService
        Schema::dropIfExists('client_credit_ledger');
        Schema::dropIfExists('client_credits');
        
        Schema::create('client_credits', function ($table) {
            $table->id();
            $table->foreignId('client_id');
            $table->integer('balance_cents')->default(0);
            $table->timestamps();
        });
        
        Schema::create('client_credit_ledger', function ($table) {
            $table->id();
            $table->foreignId('client_id');
            $table->decimal('amount', 10, 2);
            $table->string('transaction_type');
            $table->string('description');
            $table->string('reference_type')->nullable();
            $table->integer('reference_id')->nullable();
            $table->decimal('balance_after', 10, 2);
            $table->timestamps();
        });
        
        $company = Company::factory()->create();
        $this->client = Client::factory()->create(['company_id' => $company->id]);
        $this->service = app(ClientCreditService::class);
        
        // Initialize credit record for client
        DB::table('client_credits')->insert([
            'client_id' => $this->client->id,
            'balance_cents' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test can get credit balance for client.
     */
    public function test_can_get_credit_balance(): void
    {
        $balance = $this->service->getBalance($this->client->id);
        
        $this->assertIsFloat($balance);
        $this->assertEquals(0.00, $balance);
    }

    /**
     * Test can add credit to client.
     */
    public function test_can_add_credit(): void
    {
        $this->service->addCredit($this->client->id, 100.00, 'Initial credit');
        
        $balance = $this->service->getBalance($this->client->id);
        
        $this->assertEquals(100.00, $balance);
    }

    /**
     * Test can add multiple credits.
     */
    public function test_can_add_multiple_credits(): void
    {
        $this->service->addCredit($this->client->id, 50.00, 'First credit');
        $this->service->addCredit($this->client->id, 75.00, 'Second credit');
        $this->service->addCredit($this->client->id, 25.00, 'Third credit');
        
        $balance = $this->service->getBalance($this->client->id);
        
        $this->assertEquals(150.00, $balance);
    }

    /**
     * Test can deduct credit to reduce balance.
     */
    public function test_can_deduct_credit(): void
    {
        $this->service->addCredit($this->client->id, 100.00, 'Initial credit');
        $this->service->deductCredit($this->client->id, 40.00, 'Applied to invoice #123');
        
        $balance = $this->service->getBalance($this->client->id);
        
        $this->assertEquals(60.00, $balance);
    }

    /**
     * Test cannot deduct more credit than available.
     */
    public function test_cannot_deduct_more_than_available(): void
    {
        $this->service->addCredit($this->client->id, 50.00, 'Initial credit');
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient credit balance');
        
        $this->service->deductCredit($this->client->id, 75.00, 'Too much');
    }

    /**
     * Test credit ledger is maintained.
     */
    public function test_ledger_is_maintained(): void
    {
        $this->service->addCredit($this->client->id, 100.00, 'Refund for overcharge');
        $this->service->deductCredit($this->client->id, 30.00, 'Applied to invoice #456');
        
        $ledger = $this->service->getLedger($this->client->id);
        
        $this->assertCount(2, $ledger);
        
        // Verify both entries exist (order may vary by implementation)
        $types = array_column($ledger, 'transaction_type');
        $this->assertContains('credit', $types);
        $this->assertContains('debit', $types);
        
        // Find and verify each entry
        foreach ($ledger as $entry) {
            if ($entry->transaction_type === 'credit') {
                $this->assertEquals(100.00, (float) $entry->amount);
                $this->assertEquals('Refund for overcharge', $entry->description);
            } else {
                $this->assertEquals(-30.00, (float) $entry->amount);
                $this->assertEquals('Applied to invoice #456', $entry->description);
            }
        }
    }

    /**
     * Test client isolation - credits don't affect other clients.
     */
    public function test_client_credit_isolation(): void
    {
        $company = Company::factory()->create();
        $client2 = Client::factory()->create(['company_id' => $company->id]);
        
        // Initialize credit for client2
        DB::table('client_credits')->insert([
            'client_id' => $client2->id,
            'balance_cents' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->service->addCredit($this->client->id, 100.00, 'Client 1 credit');
        $this->service->addCredit($client2->id, 50.00, 'Client 2 credit');
        
        $balance1 = $this->service->getBalance($this->client->id);
        $balance2 = $this->service->getBalance($client2->id);
        
        $this->assertEquals(100.00, $balance1);
        $this->assertEquals(50.00, $balance2);
    }

    /**
     * Test atomic credit operations.
     */
    public function test_atomic_credit_operations(): void
    {
        // Add initial credit
        $this->service->addCredit($this->client->id, 1000.00, 'Initial balance');
        
        $initialBalance = $this->service->getBalance($this->client->id);
        
        // Multiple sequential deductions
        $this->service->deductCredit($this->client->id, 100.00, 'Op 1');
        $this->service->deductCredit($this->client->id, 100.00, 'Op 2');
        $this->service->deductCredit($this->client->id, 100.00, 'Op 3');
        
        $finalBalance = $this->service->getBalance($this->client->id);
        
        // All operations should be accounted for
        $this->assertEquals(700.00, $finalBalance);
        $this->assertEquals($initialBalance - 300.00, $finalBalance);
    }

    /**
     * Test zero amount credit is rejected.
     */
    public function test_zero_amount_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->addCredit($this->client->id, 0.00, 'Zero credit');
    }

    /**
     * Test negative amount credit is rejected.
     */
    public function test_negative_amount_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->addCredit($this->client->id, -50.00, 'Negative credit');
    }

    /**
     * Test credit balance precision for financial calculations.
     */
    public function test_credit_precision(): void
    {
        $this->service->addCredit($this->client->id, 33.33, 'Precise amount 1');
        $this->service->addCredit($this->client->id, 33.33, 'Precise amount 2');
        $this->service->addCredit($this->client->id, 33.34, 'Precise amount 3');
        
        $balance = $this->service->getBalance($this->client->id);
        
        $this->assertEquals(100.00, $balance);
    }

    /**
     * Test can check if client has sufficient credit.
     */
    public function test_has_sufficient_credit(): void
    {
        $this->service->addCredit($this->client->id, 100.00, 'Initial');
        
        $this->assertTrue($this->service->hasSufficientCredit($this->client->id, 50.00));
        $this->assertTrue($this->service->hasSufficientCredit($this->client->id, 100.00));
        $this->assertFalse($this->service->hasSufficientCredit($this->client->id, 150.00));
    }

    /**
     * Test ledger entries include timestamp.
     */
    public function test_ledger_includes_timestamp(): void
    {
        $this->service->addCredit($this->client->id, 50.00, 'Time-tracked credit');
        
        $ledger = $this->service->getLedger($this->client->id);
        
        $this->assertNotNull($ledger[0]->created_at);
    }

    /**
     * Test add credit with reference.
     */
    public function test_add_credit_with_reference(): void
    {
        $this->service->addCredit(
            $this->client->id,
            200.00,
            'Payment overage',
            'Payment',
            42
        );
        
        $ledger = $this->service->getLedger($this->client->id);
        
        $this->assertEquals('Payment', $ledger[0]->reference_type);
        $this->assertEquals(42, $ledger[0]->reference_id);
    }

    /**
     * Test balance after is recorded in ledger.
     */
    public function test_balance_after_tracked(): void
    {
        $this->service->addCredit($this->client->id, 100.00, 'First');
        $this->service->addCredit($this->client->id, 50.00, 'Second');
        
        $ledger = $this->service->getLedger($this->client->id);
        
        // Verify balance tracking in ledger entries - balances should show progression
        $balances = array_map(fn($entry) => (float) $entry->balance_after, $ledger);
        
        // After first credit: 100.00, after second credit: 150.00
        $this->assertContains(100.00, $balances);
        $this->assertContains(150.00, $balances);
    }
}
