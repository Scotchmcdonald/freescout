<?php

use App\Models\User;
use App\Models\ActivityLog;

function getAction1AuditAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'action1-audit-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'Action1Audit',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }
    return $admin;
}

it('action1 audit page loads', function () {
    $admin = getAction1AuditAdmin();

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/action1/audit')
        ->assertSee('Activity Log');
})->group('admin', 'action1-audit');

it('audit shows empty state', function () {
    $admin = getAction1AuditAdmin();

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/action1/audit')
        ->assertSee('No activity recorded');
})->group('admin', 'action1-audit');

it('activity log factory creates entries', function () {
    $entry = ActivityLog::factory()->create([
        'log_name' => 'default',
        'description' => 'Test activity entry',
    ]);

    expect($entry)->toBeInstanceOf(ActivityLog::class);
    expect($entry->id)->toBeGreaterThan(0);
    expect($entry->log_name)->toBe('default');
    expect($entry->description)->toBe('Test activity entry');

    $found = ActivityLog::find($entry->id);
    expect($found)->not->toBeNull();
    expect($found->description)->toBe('Test activity entry');
})->group('admin', 'action1-audit');
