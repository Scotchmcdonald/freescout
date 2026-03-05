<?php

use Modules\ContractManager\Models\BillingTemplate;
use Modules\PIB\Models\Invoice;
use Modules\PIB\Resolvers\RentToOwnEntitlementResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Crm\Models\Client;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->resolver = new RentToOwnEntitlementResolver();
});

test('calculates full installment on first invoice', function () {
    $client = Client::factory()->create();

    $template = BillingTemplate::create([
        'client_id' => $client->id,
            'name' => 'Test Template',
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

    expect($result->amount)->toBe(250.00)
        ->and($result->quantity)->toBe(1)
        ->and($result->hasReachedGoal)->toBeFalse()
        ->and($result->breakdown[0]['description'])->toContain('0.0%');
});

test('calculates with partial payment history', function () {
    $client = Client::factory()->create();

    $template = BillingTemplate::create([
        'client_id' => $client->id,
            'name' => 'Test Template',
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
    expect($result->amount)->toBe(250.00)
        ->and($result->breakdown[0]['description'])->toContain('25.0%')
        ->and($result->hasReachedGoal)->toBeFalse();
});

test('calculates final partial payment', function () {
    $client = Client::factory()->create();

    $template = BillingTemplate::create([
        'client_id' => $client->id,
            'name' => 'Test Template',
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
    expect($result->amount)->toBe(250.00)
        ->and($result->breakdown[0]['description'])->toContain('95.0%')
        ->and($result->hasReachedGoal)->toBeFalse();
});

test('handles goal reached', function () {
    $client = Client::factory()->create();

    $template = BillingTemplate::create([
        'client_id' => $client->id,
            'name' => 'Test Template',
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

    expect($result->amount)->toBe(0.00)
        ->and($result->hasReachedGoal)->toBeTrue()
        ->and($result->breakdown[0]['description'])->toContain('Goal Reached');
});

test('simulates full 20 month payment cycle', function () {
    $client = Client::factory()->create();

    $template = BillingTemplate::create([
        'client_id' => $client->id,
            'name' => 'Test Template',
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
        expect($result->amount)->toBe(250.00, "Month {$month} should charge \$250")
            ->and($result->hasReachedGoal)->toBeFalse("Month {$month} should not have reached goal");

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
    expect($totalPaid)->toBe(5000.00);

    $finalResult = $this->resolver->calculate($template);
    expect($finalResult->hasReachedGoal)->toBeTrue()
        ->and($finalResult->amount)->toBe(0.00);
});

test('throws exception for missing config', function () {
    $client = Client::factory()->create();

    $template = BillingTemplate::create([
        'client_id' => $client->id,
            'name' => 'Test Template',
        'product_type' => 'rent_to_own',
        'product_config' => [], // Missing required config
        'billing_cycle' => 'monthly',
        'next_invoice_date' => today(),
        'status' => 'active',
    ]);

    $this->expectException(InvalidArgumentException::class);
    $this->resolver->calculate($template);
});
