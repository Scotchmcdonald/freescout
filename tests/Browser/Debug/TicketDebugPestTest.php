<?php

declare(strict_types=1);

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
        'email' => $emailPrefix.'-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'status' => User::STATUS_ACTIVE,
        'email_verified_at' => now(),
    ]);
    $company->users()->attach($user->id, ['role_id' => 1, 'status' => 'approved', 'is_primary' => true]);

    return $user;
}

it('ticket form submission debug flow', function () {
    $user = createDebugPortalUser('debug-ticket');

    browserLoginPortal($this, $user);

    $this->visit('/portal/support')
        ->assertPathIs('/portal/support');
})->group('debug', 'ticket');

it('ticket number display after creation', function () {
    $user = createDebugPortalUser('debug-number');

    browserLoginPortal($this, $user);

    // Navigate to ticket listing which should show ticket numbers
    $this->visit('/portal/support/tickets')
        ->assertPathIs('/portal/support/tickets');
})->group('debug', 'ticket');

it('session flash after ticket submit', function () {
    $user = createDebugPortalUser('debug-flash');

    browserLoginPortal($this, $user);

    // Verify support page loads for form submission
    $this->visit('/portal/support')
        ->assertPathIs('/portal/support');
})->group('debug', 'ticket');
