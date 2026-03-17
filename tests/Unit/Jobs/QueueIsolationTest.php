<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Queue;
use Modules\PIB\Jobs\GenerateInvoiceJob;
use Modules\PIB\Jobs\GenerateRecurringInvoicesJob;
use Modules\PIB\Jobs\MonthEndTimeAggregationJob;

test('generate invoice job uses billing queue', function () {
    Queue::fake();

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

    GenerateInvoiceJob::dispatch($template);
    GenerateRecurringInvoicesJob::dispatch();
    MonthEndTimeAggregationJob::dispatch();

    Queue::assertPushedOn('billing', GenerateInvoiceJob::class);
    Queue::assertPushedOn('billing', GenerateRecurringInvoicesJob::class);
    Queue::assertPushedOn('billing', MonthEndTimeAggregationJob::class);

    Queue::assertPushed(GenerateInvoiceJob::class, 1);
    Queue::assertPushed(GenerateRecurringInvoicesJob::class, 1);
    Queue::assertPushed(MonthEndTimeAggregationJob::class, 1);
});
