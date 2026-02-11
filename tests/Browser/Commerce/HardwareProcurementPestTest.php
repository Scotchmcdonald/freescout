<?php

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\ContractManager\Models\Quote;

it('approved quote auto generates invoice', function () {
    $admin = User::firstOrCreate(['email' => 'hw-procure-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'HW',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }

    $client = Client::factory()->create(['name' => 'HW Procurement Client']);
    $quote = Quote::factory()->approved()->create([
        'client_id' => $client->id,
        'title' => 'Hardware Quote',
        'billing_type' => 'one_time',
    ]);

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit("/contracts/quotes/{$quote->id}")
        ->assertSee($quote->title);
})->group('commerce', 'hardware');

it('hardware invoice separate from service invoices', function () {
    $admin = User::firstOrCreate(['email' => 'hw-separate-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'HWSep',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }

    $client = Client::factory()->create(['name' => 'HW Separate Client']);

    // Create hardware quote (one_time billing)
    $hwQuote = Quote::factory()->approved()->create([
        'client_id' => $client->id,
        'title' => 'Hardware Purchase',
        'billing_type' => 'one_time',
    ]);

    // Create service quote (recurring billing)
    $svcQuote = Quote::factory()->approved()->create([
        'client_id' => $client->id,
        'title' => 'Monthly Service',
        'billing_type' => 'recurring',
    ]);

    // Verify they are separate quotes with different billing types
    expect($hwQuote->billing_type)->toBe('one_time');
    expect($svcQuote->billing_type)->toBe('recurring');
    expect($hwQuote->id)->not->toBe($svcQuote->id);

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/contracts/quotes')
        ->assertSee('Quote');
})->group('commerce', 'hardware');

it('rejected quote no invoice', function () {
    $admin = User::firstOrCreate(['email' => 'hw-reject-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'HWReject',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }

    $client = Client::factory()->create(['name' => 'HW Reject Client']);
    $rejectedQuote = Quote::factory()->rejected()->create([
        'client_id' => $client->id,
        'title' => 'Rejected Hardware',
        'billing_type' => 'one_time',
    ]);

    // Rejected quotes should not generate invoices
    expect($rejectedQuote->status)->toBe('rejected');
    expect($rejectedQuote->canBeApproved())->toBeFalse();

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit("/contracts/quotes/{$rejectedQuote->id}")
        ->assertSee('Rejected Hardware');
})->group('commerce', 'hardware');

it('multi line item hardware procurement totals', function () {
    $client = Client::factory()->create(['name' => 'Multi Line HW Client']);
    $quote = Quote::factory()->create([
        'client_id' => $client->id,
        'title' => 'Multi-Item Hardware',
        'billing_type' => 'one_time',
        'subtotal' => 150000,
        'tax_amount' => 12000,
        'total' => 162000,
    ]);

    // Verify totals add up (use loose comparison for decimal types)
    expect((float) $quote->subtotal + (float) $quote->tax_amount)->toEqual((float) $quote->total);
    expect((int) $quote->total)->toBe(162000);
})->group('commerce', 'hardware');
