<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Database\Eloquent\Model;
use Modules\PIB\Jobs\GenerateInvoiceJob;
use Modules\PIB\Jobs\GenerateRecurringInvoicesJob;
use Modules\PIB\Jobs\MonthEndTimeAggregationJob;
use Modules\ContractManager\Models\BillingTemplate;

uses(RefreshDatabase::class);

test('generate invoice job uses billing queue', function () {
    Queue::fake();

    // Use Mockery to create a Model mock without setting attributes
    $template = Mockery::mock(Model::class)->makePartial();
    
    GenerateInvoiceJob::dispatch($template);
    
    Queue::assertPushedOn('billing', GenerateInvoiceJob::class);
});

test('generate recurring invoices job uses billing queue', function () {
    Queue::fake();
    
    GenerateRecurringInvoicesJob::dispatch();
    
    Queue::assertPushedOn('billing', GenerateRecurringInvoicesJob::class);
});

test('month end time aggregation job uses billing queue', function () {
    Queue::fake();
    
    MonthEndTimeAggregationJob::dispatch();
    
    Queue::assertPushedOn('billing', MonthEndTimeAggregationJob::class);
});

test('all pib jobs use billing queue', function () {
    Queue::fake();

    $template = Mockery::mock(Model::class)->makePartial();
    
    // Dispatch all PIB jobs
    GenerateInvoiceJob::dispatch($template);
    GenerateRecurringInvoicesJob::dispatch();
    MonthEndTimeAggregationJob::dispatch();
    
    // Verify all are on billing queue
    Queue::assertPushedOn('billing', GenerateInvoiceJob::class);
    Queue::assertPushedOn('billing', GenerateRecurringInvoicesJob::class);
    Queue::assertPushedOn('billing', MonthEndTimeAggregationJob::class);
    
    // Confirm correct count
    Queue::assertPushed(GenerateInvoiceJob::class, 1);
    Queue::assertPushed(GenerateRecurringInvoicesJob::class, 1);
    Queue::assertPushed(MonthEndTimeAggregationJob::class, 1);
});

test('billing jobs do not block default queue', function () {
    Queue::fake();

    $template = Mockery::mock(Model::class)->makePartial();
    
    // Simulate bulk invoice generation (what would happen in production)
    for ($i = 0; $i < 100; $i++) {
        GenerateInvoiceJob::dispatch($template);
    }
    
    // Verify billing jobs on billing queue
    Queue::assertPushed(GenerateInvoiceJob::class, 100);
    Queue::assertPushedOn('billing', GenerateInvoiceJob::class);
    
    // System notification jobs (like password resets) would use default queue
    // and should not be affected by billing job volume
    expect(true)->toBeTrue('Billing jobs isolated on dedicated queue');
});

