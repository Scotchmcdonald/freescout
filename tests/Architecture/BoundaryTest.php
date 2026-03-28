<?php

declare(strict_types=1);

/**
 * Architecture Tests – API Boundary Guards
 *
 * Ensures that the outer HTTP boundary (controllers) does not reach past the
 * service layer directly into the database, and that module boundaries are
 * respected by not allowing direct cross-module controller imports.
 */
arch('controllers do not use raw SQL or DB::statement for mutations')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('module controllers do not import from other module controllers')
    ->expect('Modules\*\Http\Controllers')
    ->not->toUse('Modules\*\Http\Controllers')
    ->ignoring([
        'Modules\PIB\Http\Controllers',
        'Modules\ContractManager\Http\Controllers',
        'Modules\Payment\Http\Controllers',
        'Modules\ClientPortal\Http\Controllers',
    ]);
