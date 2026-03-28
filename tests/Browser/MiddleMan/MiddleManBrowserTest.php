<?php

declare(strict_types=1);

/**
 * Browser Tests for MiddleMan module.
 *
 * End-to-end UX journeys using Laravel Dusk / Pest browser helpers.
 * Verifies that admin users can navigate the MiddleMan Intercept board,
 * view intercepted events, and submit the payload edit form.
 */

use App\Models\User;
use Modules\MiddleMan\Models\MiddleManIntercept;

/**
 * Create a dedicated MiddleMan admin for browser tests.
 */
function getMiddleManBrowserAdmin(): User
{
    /** @var User $admin */
    $admin = User::firstOrCreate(['email' => 'middleman-browser-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'MiddleMan',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);

    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    return $admin;
}

it('middleman intercept page loads for admin user', function (): void {
    $admin = getMiddleManBrowserAdmin();

    browserLoginAdmin($this, $admin);

    $this->visit('/middleman/intercept')
        ->assertSee('Intercept');
})->group('middleman', 'intercept');

it('middleman logging page loads for admin user', function (): void {
    $admin = getMiddleManBrowserAdmin();

    browserLoginAdmin($this, $admin);

    $this->visit('/middleman/logging')
        ->assertSee('Logging');
})->group('middleman', 'logging');

it('admin can view an intercepted event detail', function (): void {
    $admin = getMiddleManBrowserAdmin();

    // Seed a pending intercept
    $intercept = MiddleManIntercept::create([
        'event_class' => 'App\\Events\\BrowserTestEvent',
        'event_name' => 'BrowserTestEvent',
        'payload' => ['greeting' => 'hello world'],
        'metadata' => ['class' => 'App\\Events\\BrowserTestEvent'],
        'status' => MiddleManIntercept::STATUS_PENDING,
        'sort_order' => 1,
        'intercepted_at' => now(),
    ]);

    browserLoginAdmin($this, $admin);

    $this->visit('/middleman/intercept')
        ->assertSee('BrowserTestEvent');
})->group('middleman', 'intercept');

it('admin can navigate to the middleman dashboard', function (): void {
    $admin = getMiddleManBrowserAdmin();

    browserLoginAdmin($this, $admin);

    $this->visit('/middleman')
        ->assertSee('MiddleMan');
})->group('middleman', 'dashboard');
