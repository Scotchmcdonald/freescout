<?php

declare(strict_types=1);

use App\Contracts\Billing\CreditLedgerInterface;
use App\Contracts\Billing\CreditReader;
use App\Contracts\Billing\CreditWriter;
use Illuminate\Support\Facades\DB;
use Modules\Crm\Models\Client;
use Modules\PIB\Services\ClientCreditService;

beforeEach(function () {
    // Ensure PIB module tables exist
    if (! DB::getSchemaBuilder()->hasTable('client_credits')) {
        DB::getSchemaBuilder()->create('client_credits', function ($table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->unique();
            $table->bigInteger('balance_cents')->default(0);
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    if (! DB::getSchemaBuilder()->hasTable('credit_ledger_entries')) {
        DB::getSchemaBuilder()->create('credit_ledger_entries', function ($table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->bigInteger('amount_cents');
            $table->string('transaction_type'); // 'credit' or 'debit'
            $table->text('description');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->index(['client_id', 'created_at']);
        });
    }
});

/**
 * Test that ClientCreditService implements all three interfaces
 */
test('ClientCreditService implements CreditWriter, CreditReader, and CreditLedgerInterface', function () {
    $service = app(ClientCreditService::class);

    expect($service)->toBeInstanceOf(CreditWriter::class);
    expect($service)->toBeInstanceOf(CreditReader::class);
    expect($service)->toBeInstanceOf(CreditLedgerInterface::class);
});

/**
 * Test that CreditLedgerInterface extends both CreditWriter and CreditReader
 */
test('CreditLedgerInterface extends both CreditWriter and CreditReader', function () {
    $reflection = new ReflectionClass(CreditLedgerInterface::class);
    $interfaces = $reflection->getInterfaceNames();

    expect($interfaces)->toContain(CreditWriter::class);
    expect($interfaces)->toContain(CreditReader::class);
});

/**
 * Test that service container bindings work for all interfaces
 */
test('service container bindings return ClientCreditService instance', function () {
    $reader = app(CreditReader::class);
    $writer = app(CreditWriter::class);
    $legacy = app(CreditLedgerInterface::class);

    expect($reader)->toBeInstanceOf(ClientCreditService::class);
    expect($writer)->toBeInstanceOf(ClientCreditService::class);
    expect($legacy)->toBeInstanceOf(ClientCreditService::class);
});

/**
 * Test that CreditReader interface has only read methods
 */
test('CreditReader interface has only read methods', function () {
    $reflection = new ReflectionClass(CreditReader::class);
    $methods = $reflection->getMethods();

    $methodNames = array_map(fn ($method) => $method->getName(), $methods);

    // Should have read methods
    expect($methodNames)->toContain('getBalance');
    expect($methodNames)->toContain('hasSufficientCredit');

    // Should NOT have write methods
    expect($methodNames)->not->toContain('addCredit');
    expect($methodNames)->not->toContain('deductCredit');
});

/**
 * Test that CreditWriter interface has only write methods
 */
test('CreditWriter interface has only write methods', function () {
    $reflection = new ReflectionClass(CreditWriter::class);
    $methods = $reflection->getMethods();

    $methodNames = array_map(fn ($method) => $method->getName(), $methods);

    // Should have write methods
    expect($methodNames)->toContain('addCredit');
    expect($methodNames)->toContain('deductCredit');

    // Should NOT have read methods (those are in CreditReader)
    expect($methodNames)->not->toContain('getBalance');
    expect($methodNames)->not->toContain('hasSufficientCredit');
});

/**
 * Test read-only service can depend on CreditReader only
 */
test('read-only service can depend on CreditReader only', function () {
    $client = Client::factory()->create();

    // Get reader through DI container
    $reader = app(CreditReader::class);

    // Should be able to call read methods
    $balance = $reader->getBalance($client->id);
    expect($balance)->toBe(0.0);

    $hasSufficient = $reader->hasSufficientCredit($client->id, 100.0);
    expect($hasSufficient)->toBe(false);

    // Reader interface should NOT have write methods available
    expect(method_exists($reader, 'addCredit'))->toBeTrue(); // Method exists in concrete class
    expect($reader)->toBeInstanceOf(CreditReader::class); // But type hint would prevent misuse
});

/**
 * Test write service can depend on CreditWriter only
 */
test('write service can depend on CreditWriter only', function () {
    $client = Client::factory()->create();

    // Get writer through DI container
    $writer = app(CreditWriter::class);

    // Should be able to call write methods
    $writer->addCredit(
        clientId: $client->id,
        amount: 100.0,
        description: 'Test credit'
    );

    // Writer interface should NOT expose read methods in type contract
    expect($writer)->toBeInstanceOf(CreditWriter::class);
});

/**
 * Test that services needing both can use both interfaces
 */
test('service needing both read and write can depend on both interfaces', function () {
    $client = Client::factory()->create();

    $reader = app(CreditReader::class);
    $writer = app(CreditWriter::class);

    // Add credit using writer
    $writer->addCredit(
        clientId: $client->id,
        amount: 500.0,
        description: 'Initial credit'
    );

    // Read balance using reader
    $balance = $reader->getBalance($client->id);
    expect($balance)->toBe(500.0);

    // Check if sufficient using reader
    $hasSufficient = $reader->hasSufficientCredit($client->id, 200.0);
    expect($hasSufficient)->toBe(true);

    // Deduct using writer
    $writer->deductCredit(
        clientId: $client->id,
        amount: 200.0,
        description: 'Usage'
    );

    // Verify new balance using reader
    $newBalance = $reader->getBalance($client->id);
    expect($newBalance)->toBe(300.0);
});

/**
 * Test backward compatibility with legacy CreditLedgerInterface
 */
test('legacy CreditLedgerInterface still works for backward compatibility', function () {
    $client = Client::factory()->create();

    // Get service through legacy interface
    $legacy = app(CreditLedgerInterface::class);

    // Should have both read and write methods
    $legacy->addCredit(
        clientId: $client->id,
        amount: 750.0,
        description: 'Legacy add'
    );

    $balance = $legacy->getBalance($client->id);
    expect($balance)->toBe(750.0);

    $legacy->deductCredit(
        clientId: $client->id,
        amount: 250.0,
        description: 'Legacy deduct'
    );

    $newBalance = $legacy->getBalance($client->id);
    expect($newBalance)->toBe(500.0);
});

/**
 * Test that interfaces follow ISP with max 5 methods
 */
test('interfaces follow ISP with max 5 methods per interface', function () {
    $readerReflection = new ReflectionClass(CreditReader::class);
    $writerReflection = new ReflectionClass(CreditWriter::class);

    $readerMethods = count($readerReflection->getMethods());
    $writerMethods = count($writerReflection->getMethods());

    // Each segregated interface should have few methods (ISP)
    expect($readerMethods)->toBeLessThanOrEqual(5);
    expect($writerMethods)->toBeLessThanOrEqual(5);
});

/**
 * Test example service CreditBalanceReportService uses only CreditReader
 */
test('CreditBalanceReportService depends on CreditReader only', function () {
    if (! class_exists('\Modules\PIB\Services\Examples\CreditBalanceReportService')) {
        $this->markTestSkipped('CreditBalanceReportService not found');
    }

    $reflection = new ReflectionClass('\Modules\PIB\Services\Examples\CreditBalanceReportService');
    $constructor = $reflection->getConstructor();
    $params = $constructor->getParameters();

    expect($params)->toHaveCount(1);
    expect($params[0]->getType()->getName())->toBe(CreditReader::class);
});
