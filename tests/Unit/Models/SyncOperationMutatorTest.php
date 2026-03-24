<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\SyncOperation;
use Illuminate\Support\Carbon;
use Tests\PureUnitTestCase;

final class TestSyncOperationMutatorModel extends SyncOperation
{
    /** @var array<string, mixed> */
    public array $lastUpdatePayload = [];

    public function update(array $attributes = [], array $options = []): bool
    {
        $this->lastUpdatePayload = $attributes;
        $this->setRawAttributes(array_merge($this->attributes, $attributes), true);

        return true;
    }

    public function getAttribute($key): mixed
    {
        if (in_array($key, ['started_at', 'last_progress_at', 'completed_at'], true)) {
            $value = $this->attributes[$key] ?? null;
            if ($value instanceof Carbon) {
                return $value;
            }

            return $value ? Carbon::parse((string) $value) : null;
        }

        return parent::getAttribute($key);
    }
}

class SyncOperationMutatorTest extends PureUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-03-24 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function operation(array $attrs = []): TestSyncOperationMutatorModel
    {
        $model = new TestSyncOperationMutatorModel;
        $model->setRawAttributes(array_merge([
            'status' => 'running',
            'processed_items' => 40,
            'success_items' => 35,
            'failed_items' => 5,
            'total_items' => 100,
            'started_at' => Carbon::now()->subMinutes(10)->format('Y-m-d H:i:s'),
            'last_progress_at' => Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'),
            'failures' => '[]',
            'items_per_second' => 0,
        ], $attrs), true);

        return $model;
    }

    public function test_update_progress_sets_counts_timestamp_and_throughput(): void
    {
        $op = $this->operation();
        $op->updateProgress(60, 55, 5);

        $this->assertSame(60, $op->lastUpdatePayload['processed_items']);
        $this->assertSame(55, $op->lastUpdatePayload['success_items']);
        $this->assertSame(5, $op->lastUpdatePayload['failed_items']);
        $this->assertInstanceOf(Carbon::class, $op->lastUpdatePayload['last_progress_at']);
        $this->assertIsFloat($op->lastUpdatePayload['items_per_second']);
    }

    public function test_record_failure_appends_failure_entry_and_failed_count(): void
    {
        $op = $this->operation(['failures' => '[{"item":"A","reason":"old","failed_at":"2026-03-24T12:00:00+00:00"}]']);
        $op->recordFailure('B', 'timeout');

        $this->assertCount(2, $op->lastUpdatePayload['failures']);
        $this->assertSame('B', $op->lastUpdatePayload['failures'][1]['item']);
        $this->assertSame('timeout', $op->lastUpdatePayload['failures'][1]['reason']);
        $this->assertSame(2, $op->lastUpdatePayload['failed_items']);
    }

    public function test_checkpoint_and_terminal_state_mutators(): void
    {
        $op = $this->operation();
        $op->saveCheckpoint(['cursor' => 'abc']);
        $this->assertSame(['cursor' => 'abc'], $op->lastUpdatePayload['checkpoint_data']);

        $op->markCompleted();
        $this->assertSame('completed', $op->lastUpdatePayload['status']);
        $this->assertInstanceOf(Carbon::class, $op->lastUpdatePayload['completed_at']);

        $op->markFailed('fatal');
        $this->assertSame('failed', $op->lastUpdatePayload['status']);
        $this->assertSame('fatal', $op->lastUpdatePayload['error_message']);

        $op->markStalled();
        $this->assertSame(['status' => 'stalled'], $op->lastUpdatePayload);

        $op->resume();
        $this->assertSame('running', $op->lastUpdatePayload['status']);
        $this->assertInstanceOf(Carbon::class, $op->lastUpdatePayload['last_progress_at']);
    }
}
