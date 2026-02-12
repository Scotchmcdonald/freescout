<?php

use App\Models\User;
use Modules\ContractManager\Models\Quote;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\ClientUser;
use Modules\ClientPortal\Models\ApprovalRequest;

test('client can approve quote', function () {
    // Setup
    $client = Client::factory()->create(['status' => 'active']);
    $clientUser = ClientUser::factory()->create([
        'client_id' => $client->id,
        'email' => 'approval-' . uniqid() . '@example.com',
        'password' => \Illuminate\Support\Facades\Hash::make('password'),
        'email_verified_at' => now(), 
        'is_active' => true,
    ]);
    
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
        ->type('email', $clientUser->email)
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
     $client = Client::factory()->create(['status' => 'active']);
     $clientUser = ClientUser::factory()->create([
         'client_id' => $client->id,
         'email' => 'rejection-' . uniqid() . '@example.com',
         'password' => \Illuminate\Support\Facades\Hash::make('password'),
         'email_verified_at' => now(), 
         'is_active' => true,
     ]);
     
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
        ->type('email', $clientUser->email)
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
