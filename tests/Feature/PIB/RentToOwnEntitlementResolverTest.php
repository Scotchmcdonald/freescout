<?php

declare(strict_types=1);

namespace Tests\Feature\PIB;

use Modules\PIB\Models\BillingTemplate;
use Modules\PIB\Models\Invoice;
use Modules\PIB\Resolvers\RentToOwnEntitlementResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RentToOwnEntitlementResolverTest
 * 
 * Tests Rent-To-Own billing calculations including 20-month simulation
 */
class RentToOwnEntitlementResolverTest extends TestCase
{
    use RefreshDatabase;

    private RentToOwnEntitlementResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->resolver = new RentToOwnEntitlementResolver();
    }

    public function test_calculates_full_installment_on_first_invoice(): void
    {
        $client = \App\Models\User::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'rent_to_own',
            'product_config' => [
                'goal_amount' => 5000.00,
                'monthly_installment' => 250.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        $result = $this->resolver->calculate($template);

        $this->assertEquals(250.00, $result->amount);
        $this->assertEquals(1, $result->quantity);
        $this->assertFalse($result->hasReachedGoal);
        $this->assertStringContainsString('0.0%', $result->breakdown[0]['description']);
    }

    public function test_calculates_with_partial_payment_history(): void
    {
        $client = \App\Models\User::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'rent_to_own',
            'product_config' => [
                'goal_amount' => 5000.00,
                'monthly_installment' => 250.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        // Create 5 paid invoices = $1250 paid
        for ($i = 0; $i < 5; $i++) {
            Invoice::create([
                'client_id' => $client->id,
                'billing_template_id' => $template->id,
                'invoice_number' => 'INV-TEST-' . ($i + 1),
                'status' => 'paid',
                'invoice_date' => today()->subMonths(5 - $i),
                'due_date' => today()->subMonths(5 - $i)->addDays(30),
                'subtotal' => 250.00,
                'tax_amount' => 0.00,
                'total_amount' => 250.00,
                'paid_at' => today()->subMonths(5 - $i),
            ]);
        }

        $result = $this->resolver->calculate($template);

        // Expected: Still $250, 25% complete (1250/5000)
        $this->assertEquals(250.00, $result->amount);
        $this->assertStringContainsString('25.0%', $result->breakdown[0]['description']);
        $this->assertFalse($result->hasReachedGoal);
    }

    public function test_calculates_final_partial_payment(): void
    {
        $client = \App\Models\User::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'rent_to_own',
            'product_config' => [
                'goal_amount' => 5000.00,
                'monthly_installment' => 250.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        // Create 19 paid invoices = $4750 paid
        // Remaining: $250, but only $250 needed (exactly one payment left)
        for ($i = 0; $i < 19; $i++) {
            Invoice::create([
                'client_id' => $client->id,
                'billing_template_id' => $template->id,
                'invoice_number' => 'INV-TEST-' . ($i + 1),
                'status' => 'paid',
                'invoice_date' => today()->subMonths(19 - $i),
                'due_date' => today()->subMonths(19 - $i)->addDays(30),
                'subtotal' => 250.00,
                'tax_amount' => 0.00,
                'total_amount' => 250.00,
                'paid_at' => today()->subMonths(19 - $i),
            ]);
        }

        $result = $this->resolver->calculate($template);

        // Expected: $250 (final payment), 95% complete
        $this->assertEquals(250.00, $result->amount);
        $this->assertStringContainsString('95.0%', $result->breakdown[0]['description']);
        $this->assertFalse($result->hasReachedGoal);
    }

    public function test_handles_goal_reached(): void
    {
        $client = \App\Models\User::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'rent_to_own',
            'product_config' => [
                'goal_amount' => 5000.00,
                'monthly_installment' => 250.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        // Create 20 paid invoices = $5000 paid (goal reached)
        for ($i = 0; $i < 20; $i++) {
            Invoice::create([
                'client_id' => $client->id,
                'billing_template_id' => $template->id,
                'invoice_number' => 'INV-TEST-' . ($i + 1),
                'status' => 'paid',
                'invoice_date' => today()->subMonths(20 - $i),
                'due_date' => today()->subMonths(20 - $i)->addDays(30),
                'subtotal' => 250.00,
                'tax_amount' => 0.00,
                'total_amount' => 250.00,
                'paid_at' => today()->subMonths(20 - $i),
            ]);
        }

        $result = $this->resolver->calculate($template);

        $this->assertEquals(0.00, $result->amount);
        $this->assertTrue($result->hasReachedGoal);
        $this->assertStringContainsString('Goal Reached', $result->breakdown[0]['description']);
    }

    public function test_simulates_full_20_month_payment_cycle(): void
    {
        $client = \App\Models\User::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'rent_to_own',
            'product_config' => [
                'goal_amount' => 5000.00,
                'monthly_installment' => 250.00,
            ],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        $totalPaid = 0.0;

        // Simulate 20 months of payments
        for ($month = 1; $month <= 20; $month++) {
            $result = $this->resolver->calculate($template);

            // Should charge $250 each month until goal is reached
            $this->assertEquals(250.00, $result->amount, "Month {$month} should charge \$250");
            $this->assertFalse($result->hasReachedGoal, "Month {$month} should not have reached goal");

            // Create paid invoice
            Invoice::create([
                'client_id' => $client->id,
                'billing_template_id' => $template->id,
                'invoice_number' => 'INV-SIM-' . $month,
                'status' => 'paid',
                'invoice_date' => today()->subMonths(20 - $month),
                'due_date' => today()->subMonths(20 - $month)->addDays(30),
                'subtotal' => 250.00,
                'tax_amount' => 0.00,
                'total_amount' => 250.00,
                'paid_at' => today()->subMonths(20 - $month),
            ]);

            $totalPaid += 250.00;
        }

        // After 20 payments, goal should be reached
        $this->assertEquals(5000.00, $totalPaid);

        $finalResult = $this->resolver->calculate($template);
        $this->assertTrue($finalResult->hasReachedGoal);
        $this->assertEquals(0.00, $finalResult->amount);
    }

    public function test_throws_exception_for_missing_config(): void
    {
        $client = \App\Models\User::factory()->create();

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'rent_to_own',
            'product_config' => [], // Missing required config
            'billing_cycle' => 'monthly',
            'next_invoice_date' => today(),
            'status' => 'active',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->resolver->calculate($template);
    }
}
