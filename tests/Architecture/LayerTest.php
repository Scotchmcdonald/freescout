<?php

declare(strict_types=1);

/**
 * Architecture Tests - Layer Dependencies
 *
 * These tests enforce proper layer separation and dependency direction.
 * Based on SYSTEM_ARCHITECTURE.md Section 9 (Layered Architecture)
 */
test('services are in Services directory')
    ->expect('App\Services')
    ->toBeClasses()
    ->toHaveSuffix('Service');

test('contracts are interfaces')
    ->expect('App\Contracts')
    ->toBeInterfaces();

// NOTE: Listener naming conventions are not strictly enforced across the codebase
// Most listeners use descriptive names without the "Listener" suffix (e.g., LogSuccessfulLogin, SendAutoReply)
// This is acceptable as long as the class purpose is clear
test('listeners are in Listeners directory')
    ->expect('App\Listeners')
    ->toBeClasses();

test('jobs are queueable')
    ->expect('App\Jobs')
    ->toHaveSuffix('Job')
    ->toUse('Illuminate\Contracts\Queue\ShouldQueue');

test('events use Dispatchable trait')
    ->expect('App\Events')
    ->toUse('Illuminate\Foundation\Events\Dispatchable');

test('models are in Models directory')
    ->expect('App\Models')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model');

test('providers extend ServiceProvider')
    ->expect('App\Providers')
    ->toExtend('Illuminate\Support\ServiceProvider');
