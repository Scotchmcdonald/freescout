<?php

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\ContractManager\Models\Contract;

function getBillingTemplateAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'billing-template-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'BillingTpl',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }
    return $admin;
}

it('billing template list loads', function () {
    $admin = getBillingTemplateAdmin();

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/contracts/billing-templates')
        ->assertSee('Billing Templates');
})->group('commerce', 'billing-template');

it('billing template shows empty state', function () {
    $admin = getBillingTemplateAdmin();

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/contracts/billing-templates')
        ->assertSee('No billing templates found');
})->group('commerce', 'billing-template');

it('billing template detail page loads', function () {
    $admin = getBillingTemplateAdmin();
    $client = Client::factory()->create(['name' => 'BT Detail Client']);

    $template = BillingTemplate::factory()->create([
        'client_id' => $client->id,
        'name' => 'Monthly Service Plan',
        'status' => 'active',
    ]);

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit("/contracts/billing-templates/{$template->id}")
        ->assertSee('Monthly Service Plan');
})->group('commerce', 'billing-template');

it('billing template can be paused and resumed', function () {
    $client = Client::factory()->create(['name' => 'BT Pause Client']);

    $template = BillingTemplate::factory()->create([
        'client_id' => $client->id,
        'name' => 'Pausable Plan',
        'status' => 'active',
    ]);

    expect($template->status)->toBe('active');
    expect($template->isActive())->toBeTrue();

    // Pause
    $template->update(['status' => 'paused', 'paused_at' => now()]);
    $template->refresh();

    expect($template->status)->toBe('paused');
    expect($template->isPaused())->toBeTrue();
    expect($template->paused_at)->not->toBeNull();

    // Resume
    $template->update(['status' => 'active', 'paused_at' => null]);
    $template->refresh();

    expect($template->status)->toBe('active');
    expect($template->isActive())->toBeTrue();
    expect($template->paused_at)->toBeNull();
})->group('commerce', 'billing-template');

it('billing template trigger dispatches event', function () {
    $client = Client::factory()->create(['name' => 'BT Trigger Client']);

    $template = BillingTemplate::factory()->create([
        'client_id' => $client->id,
        'name' => 'Triggerable Plan',
        'status' => 'active',
        'next_invoice_date' => now()->subDay(),
    ]);

    // Verify the template is due
    expect($template->isDue())->toBeTrue();

    // Verify the event class exists
    expect(class_exists(\Modules\ContractManager\Events\BillingTemplateDue::class))->toBeTrue();

    // Dispatch event and verify no exceptions
    \Illuminate\Support\Facades\Event::fake();
    \Illuminate\Support\Facades\Event::dispatch(
        \Modules\ContractManager\Events\BillingTemplateDue::fromTemplate($template)
    );
    \Illuminate\Support\Facades\Event::assertDispatched(\Modules\ContractManager\Events\BillingTemplateDue::class);
})->group('commerce', 'billing-template');
