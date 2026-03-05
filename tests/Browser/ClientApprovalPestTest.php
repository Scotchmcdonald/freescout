<?php

use App\Models\User;
use Modules\ContractManager\Models\Quote;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Modules\ClientPortal\Models\ApprovalRequest;

function createApprovalPortalUser(): array
{
    $company = Company::factory()->create(['is_active' => true]);
    $client = Client::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    $user = User::factory()->create([
        'type' => 2,
        'email' => 'approval-' . uniqid() . '@example.com',
        'password' => \Illuminate\Support\Facades\Hash::make('password'),
        'status' => User::STATUS_ACTIVE,
        'email_verified_at' => now(),
    ]);
    $company->users()->attach($user->id, ['role_id' => 1, 'status' => 'approved', 'is_primary' => true]);
    return [$user, $client, $company];
}

test('client can approve quote', function () {
    [$user, $client] = createApprovalPortalUser();

    $quote = Quote::factory()->create([
        'client_id' => $client->id,
        'status' => 'draft',
        'title' => 'Quote to Approve',
        'quote_number' => 'Q-' . uniqid(),
    ]);

    $quote->update(['status' => 'sent', 'sent_at' => now()]);
    
    $approval = ApprovalRequest::create([
        'client_id' => $client->id,
        'approvable_type' => Quote::class,
        'approvable_id' => $quote->id,
        'request_type' => 'quote_approval',
        'status' => 'pending',
        'title' => "Approval needed: {$quote->quote_number} - {$quote->title}",
        'metadata' => ['amount' => $quote->total],
    ]);

    // Login
    $browser = $this->visit('/portal/login')
        ->assertVisible('input[name="email"]')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->waitForText('Client Portal'); 

    $browser = $this->visit("/portal/approvals/{$approval->id}")
        ->waitForText('Approve Request')
        ->click('[dusk="approve-request-button"]');
        
    $browser->assertVisible('form[action*="/approve"]')
        ->type('textarea[name="notes"]', 'Approving this.')
        ->click('form[action*="/approve"] button[type="submit"]');

    $browser->waitForText('approved successfully')
        ->assertSee('approved successfully');

})->group('portal', 'approval');

test('client can reject quote', function () {
    [$user, $client] = createApprovalPortalUser();

    $quote = Quote::factory()->create([
        'client_id' => $client->id,
        'status' => 'draft',
        'title' => 'Quote to Reject',
        'quote_number' => 'Q-' . uniqid(),
    ]);

    $quote->update(['status' => 'sent', 'sent_at' => now()]);
     
    $approval = ApprovalRequest::create([
        'client_id' => $client->id,
        'approvable_type' => Quote::class,
        'approvable_id' => $quote->id,
        'request_type' => 'quote_approval',
        'status' => 'pending',
        'title' => "Approval needed: {$quote->quote_number} - {$quote->title}",
        'metadata' => ['amount' => $quote->total],
    ]);

    $browser = $this->visit('/portal/login')
        ->assertVisible('input[name="email"]')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->waitForText('Client Portal');

    $browser = $this->visit("/portal/approvals/{$approval->id}")
        ->waitForText('Reject Request')
        ->click('[dusk="reject-request-button"]');
             
    $browser->assertVisible('form[action*="/reject"]')
        ->type('#reject_notes', 'Too expensive.')
        ->click('form[action*="/reject"] button[type="submit"]');
        
    $browser->waitForText('rejected')
        ->assertSee('rejected');

})->group('portal', 'rejection');
