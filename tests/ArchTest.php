<?php

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

