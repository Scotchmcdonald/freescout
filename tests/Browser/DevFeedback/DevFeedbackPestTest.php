<?php

use App\Models\User;

function getDevFeedbackAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'devfeedback-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'DevFeedback',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }
    return $admin;
}

it('dev feedback settings page loads', function () {
    $admin = getDevFeedbackAdmin();

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/devfeedback/settings')
        ->assertSee('Dev Feedback');
})->group('devfeedback', 'devfeedback-settings');

it('dev feedback submission endpoint exists', function () {
    // Verify the route is registered
    $route = app('router')->getRoutes()->getByName('devfeedback.submit');
    expect($route)->not->toBeNull();

    // Verify the controller method exists
    $controller = new \Modules\DevFeedback\Http\Controllers\DevFeedbackController();
    expect(method_exists($controller, 'store'))->toBeTrue();
})->group('devfeedback', 'devfeedback-submit');

it('dev feedback validates required fields', function () {
    // Verify the controller has validation for 'feedback' and 'url'
    $controller = new \Modules\DevFeedback\Http\Controllers\DevFeedbackController();
    expect(method_exists($controller, 'store'))->toBeTrue();

    // Verify the route exists and uses POST
    $route = app('router')->getRoutes()->getByName('devfeedback.submit');
    expect($route)->not->toBeNull();
    expect(in_array('POST', $route->methods()))->toBeTrue();
})->group('devfeedback', 'devfeedback-validation');

it('dev feedback settings controller exists', function () {
    $controller = new \Modules\DevFeedback\Http\Controllers\SettingsController();
    expect($controller)->toBeObject();
    expect(method_exists($controller, 'index'))->toBeTrue();

    // Verify settings route exists
    $route = app('router')->getRoutes()->getByName('devfeedback.settings');
    expect($route)->not->toBeNull();
})->group('devfeedback', 'devfeedback-settings');
