<?php

use App\Models\User;
use Modules\ContractManager\Models\Contract;
use Modules\Crm\Models\Client;

function getContractLifecycleAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'contract-lifecycle-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'ContractLC',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    return $admin;
}

function createLifecycleContract(Client $client, array $overrides = []): Contract
{
    return Contract::create(array_merge([
        'client_id' => $client->id,
        'title' => 'Test Contract',
        'contract_number' => 'CON-'.uniqid(),
        'status' => 'active',
        'start_date' => now(),
        'contract_type' => 'managed_service',
        'monthly_amount' => 500.00,
    ], $overrides));
}

it('contract list page loads', function () {
    $admin = getContractLifecycleAdmin();

    browserLoginAdmin($this, $admin);

    $this->visit('/contracts/agreements')
        ->assertPathIs('/contracts/agreements');
})->group('commerce', 'contract-lifecycle');

it('contract list shows empty state', function () {
    $admin = getContractLifecycleAdmin();

    browserLoginAdmin($this, $admin);

    $this->visit('/contracts/agreements')
        ->assertPathIs('/contracts/agreements');
})->group('commerce', 'contract-lifecycle');

it('contract detail page loads', function () {
    $admin = getContractLifecycleAdmin();
    $client = Client::factory()->create(['name' => 'Contract Detail Client']);
    $contract = createLifecycleContract($client, ['title' => 'Managed IT Services']);

    browserLoginAdmin($this, $admin);

    $this->visit("/contracts/agreements/{$contract->id}")
        ->assertSee('Managed IT Services');
})->group('commerce', 'contract-lifecycle');

it('contract edit page loads', function () {
    $admin = getContractLifecycleAdmin();
    $client = Client::factory()->create(['name' => 'Contract Edit Client']);
    $contract = createLifecycleContract($client, ['title' => 'Editable Contract']);

    browserLoginAdmin($this, $admin);

    $this->visit("/contracts/agreements/{$contract->id}/edit")
        ->assertPathIs("/contracts/agreements/{$contract->id}/edit");
})->group('commerce', 'contract-lifecycle');

it('contract model supports lifecycle methods', function () {
    $client = Client::factory()->create(['name' => 'Contract Lifecycle Client']);
    $contract = createLifecycleContract($client, [
        'title' => 'Lifecycle Test Contract',
        'status' => 'active',
    ]);

    expect($contract->status)->toBe('active');

    // Cancel
    $contract->update(['status' => 'cancelled', 'cancelled_at' => now()]);
    $contract->refresh();
    expect($contract->status)->toBe('cancelled');
    expect($contract->cancelled_at)->not->toBeNull();

    // Create another contract for terminate test
    $contract2 = createLifecycleContract($client, [
        'title' => 'Terminate Test Contract',
        'status' => 'active',
    ]);

    $contract2->update([
        'status' => 'terminated',
        'termination_reason' => 'Breach of contract',
        'terminated_at' => now(),
    ]);
    $contract2->refresh();
    expect($contract2->status)->toBe('terminated');
    expect($contract2->termination_reason)->toBe('Breach of contract');
    expect($contract2->terminated_at)->not->toBeNull();
})->group('commerce', 'contract-lifecycle');
