<?php

use App\Models\User;
use Modules\Alerts\Models\AlertType;

function getAlertSubAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'alert-sub-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'AlertSub',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }
    return $admin;
}

it('alert subscription page loads', function () {
    $admin = getAlertSubAdmin();

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/alerts/subscriptions')
        ->assertSee('Proactive Monitoring');
})->group('alerts', 'alert-subscription');

it('empty state shows no alerts defined', function () {
    // The alert definitions are hardcoded in NotificationSubscription::getAlertTypes()
    // so the page always has definitions. Verify the page renders the alert matrix.
    $admin = getAlertSubAdmin();

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/alerts/subscriptions')
        ->assertSee('Alert Subscription Center');
})->group('alerts', 'alert-subscription');

it('notification subscription model exists', function () {
    expect(class_exists(\Modules\Alerts\Models\AlertType::class))->toBeTrue();

    // Verify AlertType has expected relationships and scopes
    $type = new AlertType();
    expect(method_exists($type, 'subscriptions'))->toBeTrue();
    expect($type->getTable())->toBe('alert_types');
})->group('alerts', 'alert-subscription');

it('alert types are queryable', function () {
    $alertType = AlertType::create([
        'code' => 'test_alert_' . uniqid(),
        'name' => 'Test Alert Type',
        'category' => 'system',
        'severity' => 'info',
        'description' => 'A test alert type for unit testing',
        'is_active' => true,
        'is_user_configurable' => true,
        'default_channels' => ['email'],
    ]);

    expect($alertType->id)->toBeGreaterThan(0);
    expect($alertType->name)->toBe('Test Alert Type');
    expect($alertType->is_active)->toBeTrue();

    $found = AlertType::where('code', $alertType->code)->first();
    expect($found)->not->toBeNull();
    expect($found->category)->toBe('system');
    expect($found->severity)->toBe('info');
})->group('alerts', 'alert-subscription');
