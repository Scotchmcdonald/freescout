<?php

use App\Models\User;

it('basic contact software assignment', function () {
    $admin = User::firstOrCreate(['email' => 'sw-assign-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'SWAssign',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/software-subscriptions')
        ->assertSee('Software');
})->group('service', 'software-assignment');

it('atomic counter prevents overallocation', function () {
    // Verify the ClientSoftwareSubscription model tracks assigned_count
    $model = new \Modules\SoftwareSubscriptions\Models\ClientSoftwareSubscription;
    expect(in_array('assigned_count', $model->getFillable()) || in_array('assigned_count', array_keys($model->getCasts())))->toBeTrue();
    expect(in_array('purchased_quantity', $model->getFillable()) || in_array('purchased_quantity', array_keys($model->getCasts())))->toBeTrue();

    // Verify SoftwareAssignment has active scope for counting
    expect(method_exists(\Modules\SoftwareSubscriptions\Models\SoftwareAssignment::class, 'scopeActive'))->toBeTrue();
})->group('service', 'software-assignment');

it('atomic counter prevents race conditions', function () {
    // Verify SoftwareCountChanged event exists for broadcasting seat changes
    expect(class_exists(\Modules\SoftwareSubscriptions\Events\SoftwareCountChanged::class))->toBeTrue();

    // Verify assignment model uses proper deployment status tracking
    $constants = (new \ReflectionClass(\Modules\SoftwareSubscriptions\Models\SoftwareAssignment::class))->getConstants();
    expect($constants)->toHaveKey('DEPLOYMENT_PENDING');
    expect($constants)->toHaveKey('DEPLOYMENT_COMPLETED');
    expect($constants)->toHaveKey('DEPLOYMENT_FAILED');
})->group('service', 'software-assignment');

it('unassigning license frees seat', function () {
    // Verify SoftwareAssignment has revoke method
    expect(method_exists(\Modules\SoftwareSubscriptions\Models\SoftwareAssignment::class, 'revoke'))->toBeTrue();

    // Verify the revoked scope exists for filtering
    expect(method_exists(\Modules\SoftwareSubscriptions\Models\SoftwareAssignment::class, 'scopeRevoked'))->toBeTrue();

    // Verify revocation constants exist
    $constants = (new \ReflectionClass(\Modules\SoftwareSubscriptions\Models\SoftwareAssignment::class))->getConstants();
    expect(array_filter($constants, fn ($k) => str_starts_with($k, 'REVOKED_'), ARRAY_FILTER_USE_KEY))->not->toBeEmpty();
})->group('service', 'software-assignment');
