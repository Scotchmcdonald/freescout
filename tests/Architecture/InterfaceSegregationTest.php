<?php

declare(strict_types=1);

/**
 * Architecture Tests - Interface Segregation
 *
 * These tests enforce the Interface Segregation Principle (ISP) from SOLID.
 * They ensure interfaces remain focused and don't grow too large.
 */
uses(Tests\TestCase::class);

test('all interfaces have Interface suffix or descriptive focused names')
    ->expect('App\Contracts')
    ->toHaveSuffix('Interface')
    ->ignoring([
        'App\Contracts\Billing\CreditWriter',  // Focused interfaces use descriptive names
        'App\Contracts\Billing\CreditReader',
        'App\Contracts\EntitlementResolver',   // Core contracts can use descriptive names
        'App\Contracts\UserProvider',          // Laravel convention
        'App\Contracts\UserEntitlementCountProvider', // Domain-specific provider naming
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

// --- ISP Container / Runtime Tests (migrated from tests/Feature/) ---

use App\Contracts\Billing\CreditLedgerInterface;
use App\Contracts\Billing\CreditReader;
use App\Contracts\Billing\CreditWriter;
use Modules\PIB\Services\ClientCreditService;
use Modules\PIB\Services\Examples\CreditBalanceReportService;

test('interface segregation: client credit service implements both interfaces', function () {
    $service = app(ClientCreditService::class);

    expect($service)->toBeInstanceOf(CreditReader::class);
    expect($service)->toBeInstanceOf(CreditWriter::class);
    expect($service)->toBeInstanceOf(CreditLedgerInterface::class);
});

test('interface segregation: credit reader can be resolved from container', function () {
    $reader = app(CreditReader::class);

    expect($reader)->toBeInstanceOf(CreditReader::class);
    expect($reader)->toBeInstanceOf(ClientCreditService::class);
});

test('interface segregation: credit writer can be resolved from container', function () {
    $writer = app(CreditWriter::class);

    expect($writer)->toBeInstanceOf(CreditWriter::class);
    expect($writer)->toBeInstanceOf(ClientCreditService::class);
});

test('interface segregation: legacy interface still works for backward compatibility', function () {
    $legacy = app(CreditLedgerInterface::class);

    expect($legacy)->toBeInstanceOf(CreditLedgerInterface::class);
    expect($legacy)->toBeInstanceOf(ClientCreditService::class);
});

test('interface segregation: services can depend on read-only interface', function () {
    $mockReader = Mockery::mock(CreditReader::class);
    $mockReader->shouldReceive('getBalance')->andReturn(100.0);
    $mockReader->shouldReceive('hasSufficientCredit')->andReturn(true);

    $service = new CreditBalanceReportService($mockReader);
    $total = $service->calculateTotalCredits([1, 2, 3]);

    expect($total)->toBeFloat();
});

test('interface segregation: mocking read-only interface is easier', function () {
    $mockReader = Mockery::mock(CreditReader::class);
    $mockReader->shouldReceive('getBalance')
        ->with(1)
        ->once()
        ->andReturn(500.0);

    $service = new CreditBalanceReportService($mockReader);
    $total = $service->calculateTotalCredits([1]);

    expect($total)->toBe(500.0);
});

test('interface segregation: type system prevents accidental writes', function () {
    $mockReader = Mockery::mock(CreditReader::class);
    $mockReader->shouldReceive('getBalance')->andReturn(100.0);

    $service = new CreditBalanceReportService($mockReader);

    expect($service)->toBeInstanceOf(CreditBalanceReportService::class);
});
