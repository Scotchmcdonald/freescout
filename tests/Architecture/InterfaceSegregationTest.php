<?php

declare(strict_types=1);

/**
 * Architecture Tests - Interface Segregation
 *
 * These tests enforce the Interface Segregation Principle (ISP) from SOLID.
 * They ensure interfaces remain focused and don't grow too large.
 */
test('all interfaces have Interface suffix or descriptive focused names')
    ->expect('App\Contracts')
    ->toHaveSuffix('Interface')
    ->ignoring([
        'App\Contracts\Billing\CreditWriter',  // Focused interfaces use descriptive names
        'App\Contracts\Billing\CreditReader',
        'App\Contracts\EntitlementResolver',   // Core contracts can use descriptive names
        'App\Contracts\UserProvider',          // Laravel convention
    ]);

test('services should not bypass interfaces and use implementations directly')
    ->expect([
        'App\Http\Controllers',
        'App\Services',
        'Modules\*\Http\Controllers',
    ])
    ->not->toUse([
        'Modules\PIB\Services\ClientCreditService',  // Should use interfaces instead
    ]);
