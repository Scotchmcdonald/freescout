<?php

declare(strict_types=1);

/**
 * Architecture Tests – Action & Domain Layer Guards
 *
 * Enforces architectural rules for Action classes, Mailables, Middleware,
 * Notifications, Observers, and DataTransferObjects:
 *
 *  1. Actions must have the Action suffix
 *  2. Actions must not use Session or Cookie facades (stateless domain operations)
 *  3. Middleware classes are in the Middleware namespace
 *  4. Mailables extend Illuminate\Mail\Mailable
 *  5. Observers have suffix Observer
 *  6. DTOs are classes (not interfaces/traits)
 *  7. Notifications extend Illuminate\Notifications\Notification
 */
test('action classes have Action suffix')
    ->expect('App\Actions')
    ->toHaveSuffix('Action');

test('actions do not use Session facade (stateless domain operations)')
    ->expect('App\Actions')
    ->not->toUse('Illuminate\Support\Facades\Session');

test('actions do not use Cookie facade (stateless domain operations)')
    ->expect('App\Actions')
    ->not->toUse('Illuminate\Support\Facades\Cookie');

test('mailables extend Mailable')
    ->expect('App\Mail')
    ->toExtend('Illuminate\Mail\Mailable');

test('middleware classes are classes')
    ->expect('App\Http\Middleware')
    ->toBeClasses();

test('observers have Observer suffix')
    ->expect('App\Observers')
    ->toHaveSuffix('Observer');

test('data transfer objects are classes')
    ->expect('App\DataTransferObjects')
    ->toBeClasses();

test('notifications extend Notification')
    ->expect('App\Notifications')
    ->toExtend('Illuminate\Notifications\Notification');

test('module controllers have Controller suffix')
    ->expect('Modules\*\Http\Controllers')
    ->toHaveSuffix('Controller');

test('module providers extend ServiceProvider')
    ->expect('Modules\*\Providers')
    ->toExtend('Illuminate\Support\ServiceProvider');
