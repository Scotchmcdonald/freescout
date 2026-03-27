<?php

declare(strict_types=1);

/**
 * Type Safety for all Modules
 */
arch('all module classes have strict types')
    ->expect('Modules')
    ->toUseStrictTypes()
    ->ignoring([
        'Modules\*\Tests',
        'Modules\*\Database',
        'Modules\*\Providers', // some old providers might not have it, let's include providers if we can.
    ]);

/**
 * Module Boundary Rules:
 * Domain models shouldn't handle HTTP directly.
 */
arch('module models do not use http')
    ->expect('Modules\*\Models')
    ->not->toUse('Illuminate\Http');

