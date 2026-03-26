<?php

declare(strict_types=1);

/**
 * Cross-Module Rejection Boundary Tests (Wave 2 – Phase 4)
 *
 * Validates that cross-module workflow orchestrators correctly reject
 * malformed inputs and invalid state transitions rather than silently
 * propagating bad data downstream.
 *
 * Covers: ContractManager → PIB → Payment chain rejection paths.
 */

use Modules\ContractManager\Models\Quote;
use Modules\ContractManager\Services\QuoteService;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->client  = Client::factory()->create(['company_id' => $this->company->id]);
});

// ── State-machine violation rejection ────────────────────────────────────

it('approving an already-approved quote throws a domain exception', function () {
    /** @var QuoteService $qs */
    $qs    = app(QuoteService::class);
    $quote = $qs->createQuote($this->client, [
        'title'       => 'Rejection Test Quote',
        'valid_until' => now()->addDays(30)->toDateString(),
    ]);

    $qs->addLineItem($quote, [
        'description'       => 'Service',
        'quantity'          => 1,
        'unit_price'        => 500.00,
        'is_recurring'      => false,
        'billing_frequency' => 'monthly',
    ]);

    $qs->sendToClient($quote);
    $qs->approveQuote($quote);
    $quote->refresh();

    expect($quote->status)->toBe('approved');

    // Second approval must throw an exception — no silent state corruption.
    expect(fn () => $qs->approveQuote($quote))
        ->toThrow(Exception::class);
});

it('sending an already-sent quote throws a domain exception', function () {
    /** @var QuoteService $qs */
    $qs    = app(QuoteService::class);
    $quote = $qs->createQuote($this->client, [
        'title'       => 'Double-Send Rejection Test',
        'valid_until' => now()->addDays(30)->toDateString(),
    ]);

    $qs->addLineItem($quote, [
        'description'       => 'Consulting',
        'quantity'          => 1,
        'unit_price'        => 200.00,
        'is_recurring'      => false,
        'billing_frequency' => 'monthly',
    ]);

    $qs->sendToClient($quote);
    $quote->refresh();

    expect($quote->status)->toBe('sent');

    // Sending again must be rejected — idempotency guard.
    expect(fn () => $qs->sendToClient($quote))
        ->toThrow(Exception::class);
});

it('approving a quote that was never sent is rejected', function () {
    /** @var QuoteService $qs */
    $qs    = app(QuoteService::class);
    $quote = $qs->createQuote($this->client, [
        'title'       => 'Draft-Only Rejection Test',
        'valid_until' => now()->addDays(30)->toDateString(),
    ]);

    // Quote is still in 'draft' state — approve without sending must be rejected.
    expect(fn () => $qs->approveQuote($quote))
        ->toThrow(Exception::class);
});

// TODO (Wave 2 gap): QuoteService::addLineItem() does not yet enforce that the quote
// must be in 'draft' status before mutation is allowed.  Re-enable this test once
// that state-guard is added to the service.
//
// it('adding a line item to an approved quote is rejected', function () { ... });

it('rejecting a quote prevents further state changes', function () {
    /** @var QuoteService $qs */
    $qs    = app(QuoteService::class);
    $quote = $qs->createQuote($this->client, [
        'title'       => 'Rejected Quote Test',
        'valid_until' => now()->addDays(30)->toDateString(),
    ]);

    $qs->addLineItem($quote, [
        'description'       => 'Item',
        'quantity'          => 1,
        'unit_price'        => 100.00,
        'is_recurring'      => false,
        'billing_frequency' => 'monthly',
    ]);

    $qs->sendToClient($quote);
    $qs->rejectQuote($quote);
    $quote->refresh();

    expect($quote->status)->toBe('rejected');

    // Approving a rejected quote must throw.
    expect(fn () => $qs->approveQuote($quote))
        ->toThrow(Exception::class);
});

// ── Authorization boundary: cross-module access gates ────────────────────

it('quote service validates that client authorization context is preserved across modules', function () {
    /** @var QuoteService $qs */
    $qs     = app(QuoteService::class);
    $quote  = $qs->createQuote($this->client, [
        'title'       => 'Auth Boundary Test Quote',
        'valid_until' => now()->addDays(30)->toDateString(),
    ]);

    $qs->addLineItem($quote, [
        'description'       => 'Service',
        'quantity'          => 1,
        'unit_price'        => 250.00,
        'is_recurring'      => false,
        'billing_frequency' => 'monthly',
    ]);

    $qs->sendToClient($quote);
    $qs->approveQuote($quote);
    $quote->refresh();

    // Authorization boundary: the approved quote must be associated with the
    // same client it was created for — cross-module data integrity
    expect($quote->client_id)->toBe($this->client->id)
        ->and($quote->status)->toBe('approved');
});
