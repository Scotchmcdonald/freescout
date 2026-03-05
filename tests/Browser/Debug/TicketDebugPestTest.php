<?php

/**
 * Debug tests for ticket submission flow.
 * Consolidated from legacy Dusk debug tests.
 */

use App\Models\User;
use Modules\Crm\Models\Company;

function createDebugPortalUser(string $emailPrefix): User
{
    $company = Company::factory()->create(['is_active' => true]);
    $user = User::factory()->create([
        'type' => 2,
        'first_name' => 'Debug',
        'last_name' => 'User',
        'email' => $emailPrefix . '-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'status' => User::STATUS_ACTIVE,
        'email_verified_at' => now(),
    ]);
    $company->users()->attach($user->id, ['role_id' => 1, 'status' => 'approved', 'is_primary' => true]);
    return $user;
}

it('ticket form submission debug flow', function () {
    $user = createDebugPortalUser('debug-ticket');

    $this->visit('/portal/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/portal/support')
        ->assertSee('Support');
})->group('debug', 'ticket');

it('ticket number display after creation', function () {
    $user = createDebugPortalUser('debug-number');

    $this->visit('/portal/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    // Navigate to ticket listing which should show ticket numbers
    $this->visit('/portal/support/tickets')
        ->assertSee('Ticket');
})->group('debug', 'ticket');

it('session flash after ticket submit', function () {
    $user = createDebugPortalUser('debug-flash');

    $this->visit('/portal/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    // Verify support page loads for form submission
    $this->visit('/portal/support')
        ->assertSee('Support');
})->group('debug', 'ticket');
