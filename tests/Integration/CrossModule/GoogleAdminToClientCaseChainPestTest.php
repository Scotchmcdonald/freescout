<?php

declare(strict_types=1);

/**
 * Phase 7B — GoogleAdmin → Crm → CaseManager Chain Test
 *
 * Proves that:
 *   1. GoogleUserSynced event creates a Customer via the Crm listener seam.
 *   2. A Conversation linked to that Customer can have a CaseRecord attached.
 *   3. The end-to-end identity chain (Google identity → Customer → Case) is intact.
 *
 * The CaseManager HandleConversationCreated listener is queued and depends on AI
 * services, so we verify the chain by directly creating the CaseRecord after proving
 * the GoogleAdmin→Crm seam fires the real listener.
 */

use App\DataTransferObjects\GoogleUserSyncedData;
use App\Models\Conversation;
use App\Models\Customer;
use Modules\CaseManager\Models\CaseRecord;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Modules\GoogleAdmin\Events\GoogleUserSynced;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->client = Client::factory()->create(['company_id' => $this->company->id]);
});

it('GoogleUserSynced creates customer and case history is accessible via conversation chain', function () {
    $uniqueEmail = 'chain-test-'.uniqid().'@example.com';

    $dto = new GoogleUserSyncedData(
        clientId: $this->client->id,
        email: $uniqueEmail,
        firstName: 'ChainTest',
        lastName: 'GoogleUser',
        googleId: 'google-chain2-'.uniqid(),
        suspended: false,
        orgUnitPath: '/Engineering',
        metadata: [],
    );

    // Seam 1: GoogleAdmin → Crm — dispatch through the real event system
    event(new GoogleUserSynced($dto));

    // Verify Customer was created by the CrmServiceProvider listener
    $customer = Customer::where('first_name', 'ChainTest')
        ->where('last_name', 'GoogleUser')
        ->first();

    expect($customer)->not->toBeNull('GoogleUserSyncedListener should create a Customer');

    // Verify email record exists
    $this->assertDatabaseHas('emails', ['email' => $uniqueEmail]);

    // Seam 2: Create a Conversation for this customer (simulates the helpdesk flow)
    $conversation = Conversation::factory()->create([
        'customer_id' => $customer->id,
        'customer_email' => $uniqueEmail,
        'subject' => 'Network connectivity issue',
    ]);

    // Seam 3: CaseManager creates a CaseRecord from the Conversation
    // (In production, HandleConversationCreated listener does this via the queue)
    $caseRecord = CaseRecord::create([
        'conversation_id' => $conversation->id,
        'state' => 'new',
    ]);

    // Verify the end-to-end chain: Google identity → Customer → Conversation → CaseRecord
    expect($caseRecord)->not->toBeNull()
        ->and($caseRecord->conversation_id)->toBe($conversation->id)
        ->and($conversation->customer_id)->toBe($customer->id);

    // Verify the CaseRecord is accessible from the conversation relationship
    $caseFromConversation = CaseRecord::where('conversation_id', $conversation->id)->first();
    expect($caseFromConversation)->not->toBeNull()
        ->and($caseFromConversation->id)->toBe($caseRecord->id)
        ->and($caseFromConversation->state)->toBe('new');
});

it('chain fails gracefully when Google user already exists in Crm', function () {
    $uniqueEmail = 'existing-user-'.uniqid().'@example.com';

    // Pre-create customer using Customer::create(email, data) signature
    $existing = Customer::create($uniqueEmail, [
        'first_name' => 'Existing',
        'last_name' => 'User',
    ]);

    $dto = new GoogleUserSyncedData(
        clientId: $this->client->id,
        email: $uniqueEmail,
        firstName: 'Existing',
        lastName: 'User',
        googleId: 'google-existing-'.uniqid(),
        suspended: false,
        orgUnitPath: '/Support',
        metadata: [],
    );

    // Should not throw — existing user is updated, not duplicated
    event(new GoogleUserSynced($dto));

    // Exactly one customer with this email (no duplicate)
    $emailCount = \App\Models\Email::where('email', $uniqueEmail)->count();
    expect($emailCount)->toBe(1);
});
