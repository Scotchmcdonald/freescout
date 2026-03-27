<?php

declare(strict_types=1);

/**
 * Ensures 100% Type Safety
 */
arch('all core namespaces have strict types')
    ->expect([
        'App\Models',
        'App\Http\Controllers',
        'App\Services',
        'App\Providers',
        'App\Jobs',
        'App\Http\Requests',
        'App\Policies',
        'App\DataTransferObjects',
        'App\Enums',
    ])
    ->toUseStrictTypes()
    ->ignoring(['App\Models\Traits']);

/**
 * Strict Boundary Enforcement
 */
arch('models do not use http')
    ->expect('App\Models')
    ->not->toUse('Illuminate\Http');

arch('events do not use http')
    ->expect('App\Events')
    ->not->toUse('Illuminate\Http');

arch('requests do not use models directly for mutations')
    ->expect('App\Http\Requests')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('policies only depend on models and auth')
    ->expect('App\Policies')
    ->not->toUse([
        'Illuminate\Support\Facades\DB',
        'Illuminate\Http\Request',
        'Illuminate\Http\Response'
    ]);

arch('jobs do not depend on http requests')
    ->expect('App\Jobs')
    ->not->toUse(['Illuminate\Http\Request', 'App\Http\Requests']);
