<?php

declare(strict_types=1);

use Modules\ContractManager\Models\Contract;
use Modules\ContractManager\Models\Milestone;
use Modules\Crm\Models\Client;
use Modules\PIB\Models\Invoice;

function createMilestoneContract(Client $client, array $overrides = []): Contract
{
    return Contract::create(array_merge([
        'client_id' => $client->id,
        'title' => 'Milestone Project',
        'contract_number' => 'CON-MS-'.uniqid(),
        'status' => 'active',
        'start_date' => now(),
        'contract_type' => 'project',
        'monthly_amount' => 0,
    ], $overrides));
}

it('milestone completion generates partial invoice', function () {
    $client = Client::factory()->create(['name' => 'Milestone Invoice Client']);
    $contract = createMilestoneContract($client);

    $milestone = Milestone::create([
        'title' => 'Phase 1 - Setup',
        'contract_id' => $contract->id,
        'billing_amount' => 2500.00,
        'status' => 'pending',
        'sequence_order' => 1,
    ]);

    // Milestone not yet achieved — no invoice
    expect($milestone->canGenerateInvoice())->toBeFalse();

    // Achieve and approve the milestone
    $milestone->markAsAchieved();
    $milestone->approveForBilling();
    $milestone->refresh();

    expect($milestone->isAchieved())->toBeTrue();
    expect($milestone->client_approved)->toBeTrue();
    expect($milestone->canGenerateInvoice())->toBeTrue();

    // Generate the partial invoice
    $invoice = $milestone->generateInvoice();

    expect($invoice)->not->toBeNull();
    expect((float) $invoice->total_amount)->toEqual(2500.00);
    expect($invoice->special_notes)->toContain('Phase 1 - Setup');
    expect($milestone->fresh()->invoice_id)->toBe($invoice->id);

    // Cannot generate a second invoice for the same milestone
    expect($milestone->fresh()->canGenerateInvoice())->toBeFalse();
})->group('billing', 'revenue-assurance', 'project-milestones');

it('incomplete milestone no invoice', function () {
    $client = Client::factory()->create(['name' => 'Incomplete Milestone Client']);
    $contract = createMilestoneContract($client);

    $pendingMilestone = Milestone::create([
        'title' => 'Pending Work',
        'contract_id' => $contract->id,
        'billing_amount' => 1000.00,
        'status' => 'pending',
        'sequence_order' => 1,
    ]);

    $inProgressMilestone = Milestone::create([
        'title' => 'Work In Progress',
        'contract_id' => $contract->id,
        'billing_amount' => 2000.00,
        'status' => 'in_progress',
        'sequence_order' => 2,
    ]);

    $blockedMilestone = Milestone::create([
        'title' => 'Blocked Work',
        'contract_id' => $contract->id,
        'billing_amount' => 1500.00,
        'status' => 'blocked',
        'sequence_order' => 3,
    ]);

    // None of these should be invoiceable
    expect($pendingMilestone->canGenerateInvoice())->toBeFalse();
    expect($inProgressMilestone->canGenerateInvoice())->toBeFalse();
    expect($blockedMilestone->canGenerateInvoice())->toBeFalse();

    // generateInvoice should return null
    expect($pendingMilestone->generateInvoice())->toBeNull();
    expect($inProgressMilestone->generateInvoice())->toBeNull();
    expect($blockedMilestone->generateInvoice())->toBeNull();

    // Verify no invoices were created for this contract
    $invoiceCount = Invoice::where('contract_id', $contract->id)->count();
    expect($invoiceCount)->toBe(0);
})->group('billing', 'revenue-assurance', 'project-milestones');

it('all milestones sum to project total', function () {
    $client = Client::factory()->create(['name' => 'Milestone Sum Client']);
    $contract = createMilestoneContract($client, ['monthly_amount' => 10000.00]);
    $projectTotal = 10000.00;

    $milestones = [
        ['title' => 'Design Phase', 'billing_amount' => 2500.00, 'sequence_order' => 1],
        ['title' => 'Development Phase', 'billing_amount' => 4000.00, 'sequence_order' => 2],
        ['title' => 'Testing Phase', 'billing_amount' => 2000.00, 'sequence_order' => 3],
        ['title' => 'Deployment Phase', 'billing_amount' => 1500.00, 'sequence_order' => 4],
    ];

    foreach ($milestones as $ms) {
        Milestone::create(array_merge($ms, [
            'contract_id' => $contract->id,
            'status' => 'pending',
        ]));
    }

    $milestoneSum = Milestone::projectTotal($contract->id);
    expect($milestoneSum)->toEqual($projectTotal);

    // Verify individual amounts
    $allMilestones = Milestone::where('contract_id', $contract->id)->ordered()->get();
    expect($allMilestones)->toHaveCount(4);
    expect((float) $allMilestones->sum('billing_amount'))->toEqual($projectTotal);
})->group('billing', 'revenue-assurance', 'project-milestones');

it('milestone requires client approval before invoice', function () {
    $client = Client::factory()->create(['name' => 'Approval Required Client']);
    $contract = createMilestoneContract($client);

    $milestone = Milestone::create([
        'title' => 'Approval Required Phase',
        'contract_id' => $contract->id,
        'billing_amount' => 5000.00,
        'status' => 'pending',
        'sequence_order' => 1,
    ]);

    // Mark as achieved but NOT approved
    $milestone->markAsAchieved();
    $milestone->refresh();

    expect($milestone->isAchieved())->toBeTrue();
    expect($milestone->client_approved)->toBeFalse();
    expect($milestone->canGenerateInvoice())->toBeFalse();

    // Attempt to generate invoice without approval — should fail
    $invoice = $milestone->generateInvoice();
    expect($invoice)->toBeNull();

    // Now approve
    $milestone->approveForBilling();
    $milestone->refresh();

    expect($milestone->client_approved)->toBeTrue();
    expect($milestone->client_approved_at)->not->toBeNull();
    expect($milestone->canGenerateInvoice())->toBeTrue();

    // Now invoice generation should work
    $invoice = $milestone->generateInvoice();
    expect($invoice)->not->toBeNull();
    expect((float) $invoice->total_amount)->toEqual(5000.00);
})->group('billing', 'revenue-assurance', 'project-milestones');
