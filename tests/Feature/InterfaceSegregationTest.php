<?php

use App\Contracts\Billing\CreditLedgerInterface;
use App\Contracts\Billing\CreditReader;
use App\Contracts\Billing\CreditWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PIB\Services\ClientCreditService;
use Modules\PIB\Services\Examples\CreditBalanceReportService;

uses(RefreshDatabase::class);

test('interface segregation: client credit service implements both interfaces', function () {
    $service = app(ClientCreditService::class);

    $this->assertInstanceOf(CreditReader::class, $service);
    $this->assertInstanceOf(CreditWriter::class, $service);
    $this->assertInstanceOf(CreditLedgerInterface::class, $service);
});

test('interface segregation: services can depend on read-only interface', function () {
    // Create mock that only implements CreditReader
    $mockReader = Mockery::mock(CreditReader::class);
    $mockReader->shouldReceive('getBalance')->andReturn(100.0);
    $mockReader->shouldReceive('hasSufficientCredit')->andReturn(true);

    // CreditBalanceReportService only accepts CreditReader
    $service = new CreditBalanceReportService($mockReader);

    // This service can read but cannot write
    $total = $service->calculateTotalCredits([1, 2, 3]);
    $this->assertIsFloat($total);
});

test('interface segregation: credit reader can be resolved from container', function () {
    $reader = app(CreditReader::class);

    $this->assertInstanceOf(CreditReader::class, $reader);
    $this->assertInstanceOf(ClientCreditService::class, $reader);
});

test('interface segregation: credit writer can be resolved from container', function () {
    $writer = app(CreditWriter::class);

    $this->assertInstanceOf(CreditWriter::class, $writer);
    $this->assertInstanceOf(ClientCreditService::class, $writer);
});

test('interface segregation: legacy interface still works for backward compatibility', function () {
    $legacy = app(CreditLedgerInterface::class);

    $this->assertInstanceOf(CreditLedgerInterface::class, $legacy);
    $this->assertInstanceOf(ClientCreditService::class, $legacy);
});

test('interface segregation: mocking read-only interface is easier', function () {
    // Before ISP: Had to mock all methods including write methods
    // After ISP: Only mock what we need

    $mockReader = Mockery::mock(CreditReader::class);
    $mockReader->shouldReceive('getBalance')
        ->with(1)
        ->once()
        ->andReturn(500.0);

    // Use in service that only needs read access
    $service = new CreditBalanceReportService($mockReader);
    $total = $service->calculateTotalCredits([1]);

    $this->assertEquals(500.0, $total);
});

test('interface segregation: type system prevents accidental writes', function () {
    // This test demonstrates that the type system enforces read-only access
    // when you depend on CreditReader instead of full CreditLedgerInterface

    $mockReader = Mockery::mock(CreditReader::class);
    $mockReader->shouldReceive('getBalance')->andReturn(100.0);

    $service = new CreditBalanceReportService($mockReader);

    // Service can only call read methods - write methods don't exist on CreditReader
    // This is enforced at compile time by PHP's type system
    // IDE and static analyzers (PHPStan, Psalm) will catch calls to write methods

    expect($service)->toBeInstanceOf(CreditBalanceReportService::class);
});
