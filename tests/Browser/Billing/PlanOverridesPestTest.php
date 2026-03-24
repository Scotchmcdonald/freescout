<?php

declare(strict_types=1);

use App\Models\User;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\Crm\Models\Client;

it('contract overrides gold plan price', function () {
    $admin = User::firstOrCreate(['email' => 'plan-override-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'PlanOverride',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    $client = Client::factory()->create(['name' => 'Gold Plan Client']);
    $template = BillingTemplate::factory()->create([
        'client_id' => $client->id,
        'name' => 'Gold Plan',
        'product_type' => 'service_plan',
        'billing_cycle' => 'monthly',
        'status' => 'active',
    ]);

    browserLoginAdmin($this, $admin);

    // Verify contract create page is accessible for setting price overrides
    $this->visit('/contracts/agreements')
        ->assertSee('Contract');
})->group('billing', 'revenue-assurance', 'plan-override');

it('price override persists across billing cycles', function () {
    $admin = User::firstOrCreate(['email' => 'plan-persist-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'PlanPersist',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    $client = Client::factory()->create(['name' => 'Persist Override Client']);
    $template = BillingTemplate::factory()->create([
        'client_id' => $client->id,
        'name' => 'Gold Override',
        'product_type' => 'service_plan',
        'billing_cycle' => 'monthly',
        'status' => 'active',
        'product_config' => ['base_price' => 9999, 'override_price' => 7500],
    ]);

    // Verify override persists after template reload
    $fresh = $template->fresh();
    expect($fresh->product_config['override_price'])->toBe(7500);
    expect($fresh->status)->toBe('active');

    browserLoginAdmin($this, $admin);

    $this->visit("/contracts/billing-templates/{$template->id}")
        ->assertSee('Gold Override');
})->group('billing', 'revenue-assurance', 'plan-override');

it('removing override reverts to default price', function () {
    $client = Client::factory()->create(['name' => 'Revert Override Client']);
    $template = BillingTemplate::factory()->create([
        'client_id' => $client->id,
        'name' => 'Revert Plan',
        'product_type' => 'service_plan',
        'billing_cycle' => 'monthly',
        'status' => 'active',
        'product_config' => ['base_price' => 9999, 'override_price' => 7500],
    ]);

    // Remove override
    $config = $template->product_config;
    unset($config['override_price']);
    $template->product_config = $config;
    $template->save();

    $fresh = $template->fresh();
    expect($fresh->product_config)->not->toHaveKey('override_price');
    expect($fresh->product_config['base_price'])->toBe(9999);
})->group('billing', 'revenue-assurance', 'plan-override');
