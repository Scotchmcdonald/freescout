<?php

declare(strict_types=1);

/**
 * Architecture Tests - Naming Conventions
 *
 * These tests enforce consistent naming conventions across the codebase.
 * Based on SYSTEM_ARCHITECTURE.md coding standards.
 */
test('controllers have Controller suffix')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

test('services have Service suffix')
    ->expect('App\Services')
    ->toHaveSuffix('Service');

test('repositories have Repository suffix')
    ->expect('App\Repositories')
    ->toHaveSuffix('Repository');

test('jobs have Job suffix')
    ->expect([
        'App\Jobs',
        'Modules\*\Jobs',
    ])
    ->toHaveSuffix('Job')
    ->ignoring([
        'Modules\EmailMigration\Jobs\CheckDnsPropagation',  // Legacy naming - technical debt
        'Modules\Payment\Jobs\ProcessDueInvoices',          // Legacy naming - technical debt
        'Modules\Payment\Jobs\ProcessInvoicePayment',       // Legacy naming - technical debt
    ]);

// NOTE: Listener naming conventions vary across the codebase
// Many listeners use descriptive verb-based names (e.g., LogSuccessfulLogin, SendAutoReply)
// This is acceptable and often more readable than adding "Listener" suffix
test('listeners are in Listeners directory')
    ->expect([
        'App\Listeners',
        'Modules\*\Listeners',
    ])
    ->toBeClasses();
test('events use Dispatchable trait')
    ->expect([
        'App\Events',
        'Modules\*\Events',
    ])
    ->toUse('Illuminate\Foundation\Events\Dispatchable');

test('exceptions have Exception suffix')
    ->expect('App\Exceptions')
    ->toHaveSuffix('Exception')
    ->toExtend('Exception');

test('middleware has descriptive names')
    ->expect('App\Http\Middleware')
    ->toBeClasses();

test('policies have Policy suffix')
    ->expect('App\Policies')
    ->toHaveSuffix('Policy');

test('facades extend Facade base class')
    ->expect('App\Facades')
    ->toExtend('Illuminate\Support\Facades\Facade');

test('data transfer objects are in DataTransferObjects directory')
    ->expect('App\DataTransferObjects')
    ->toBeClasses()
    ->toBeReadonly(); // DTOs should be readonly in PHP 8.2+

test('enums are in Enums directory')
    ->expect('App\Enums')
    ->toBeEnums();
