<?php

declare(strict_types=1);

/**
 * Architecture Tests - Module Boundaries
 * 
 * These tests enforce proper module isolation and dependency rules.
 * Based on SYSTEM_ARCHITECTURE.md Section 7 (Module Architecture)
 */

test('modules should not depend on other module implementations')
    ->expect('Modules\*\Services')
    ->not->toUse([
        'Modules\*\Services',  // Can't use services from other modules
    ])
    ->ignoring([
        'Modules\PIB\Services',  // Allow self-reference
        'Modules\Crm\Services',
        'Modules\AssetManagement\Services',
        'Modules\ContractManager\Services',
    ]);

test('module models are namespaced under Modules')
    ->expect('Modules\*\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

test('module events are properly namespaced')
    ->expect('Modules\*\Events')
    ->toUse('Illuminate\Foundation\Events\Dispatchable');

test('module jobs are queueable')
    ->expect('Modules\*\Jobs')
    ->toUse('Illuminate\Contracts\Queue\ShouldQueue')
    ->ignoring([
        'Modules\*\Jobs\*SyncJob',  // Some jobs are intentionally synchronous
        'Modules\EmailMigration\Jobs\CheckDnsPropagation', // Legacy naming
    ]);
