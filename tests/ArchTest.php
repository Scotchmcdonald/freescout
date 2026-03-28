<?php

declare(strict_types=1);

test('globals')
    ->expect(['dd', 'dump', 'ray'])
    ->not->toBeUsed();

test('controllers')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed()
    ->ignoring('App\Http\Controllers');

test('models')
    ->expect('App\Models')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->ignoring('App\Models\QueryBuilders');

test('strict types')
    ->expect('App')
    ->toUseStrictTypes()
    ->ignoring('App\Events');

test('controllers cannot call DB facade directly')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB');

test('modules use strict types')
    ->expect('Modules')
    ->toUseStrictTypes();

test('app jobs implement ShouldQueue')
    ->expect('App\Jobs')
    ->toImplement('Illuminate\Contracts\Queue\ShouldQueue');

test('jobs do not use HTTP request or response facades')
    ->expect('App\Jobs')
    ->not->toUse([
        'Illuminate\Support\Facades\Request',
        'Illuminate\Http\Request',
    ]);
