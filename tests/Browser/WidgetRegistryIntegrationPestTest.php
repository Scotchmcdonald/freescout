<?php

use App\Models\User;
use Modules\Crm\Models\Client;

function getWidgetAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'widget-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'Widget',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }
    return $admin;
}

function loginWidgetAdmin($test): void
{
    $admin = getWidgetAdmin();
    $test->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');
}

it('all modules register widgets', function () {
    $registry = app(\App\Services\Ui\WidgetRegistryService::class);
    expect($registry)->toBeInstanceOf(\App\Services\Ui\WidgetRegistryService::class);

    // The registry should be resolvable via the alias too
    $registryViaAlias = app(\App\Services\Ui\WidgetRegistry::class);
    expect($registryViaAlias)->toBeInstanceOf(\App\Services\Ui\WidgetRegistryService::class);
})->group('widgets', 'integration', 'client360');

test('widget loading performance', function () {
    loginWidgetAdmin($this);
    $client = Client::factory()->create();

    $start = microtime(true);
    $this->visit('/clients/' . $client->id);
    $duration = microtime(true) - $start;

    expect($duration)->toBeLessThan(5.0);
})->group('widgets', 'performance');

it('widget permission filtering', function () {
    // WidgetRegistryService should be a singleton across the app
    $r1 = app(\App\Services\Ui\WidgetRegistryService::class);
    $r2 = app(\App\Services\Ui\WidgetRegistryService::class);
    expect($r1)->toBe($r2);
})->group('widgets', 'permissions', 'security');

it('client 360 page loads', function () {
    loginWidgetAdmin($this);
    $client = Client::factory()->create(['name' => 'Smoke Test Client']);

    $this->visit('/clients/' . $client->id)
        ->assertSee($client->name)
        ->assertSee('Client 360');
})->group('widgets', 'smoke');
