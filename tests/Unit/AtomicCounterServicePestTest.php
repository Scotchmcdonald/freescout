<?php

use App\Services\AtomicCounterService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->service = new AtomicCounterService;
    // Create test counter with dynamic ID
    $this->counterId = DB::table('test_counters')->insertGetId([
        'count' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

test('increment increases counter', function () {
    $newValue = $this->service->increment('test_counters', ['id' => $this->counterId], 'count');

    expect($newValue)->toBe(1);

    $dbValue = DB::table('test_counters')->where('id', $this->counterId)->value('count');
    expect($dbValue)->toBe(1);
});

test('increment by custom amount', function () {
    $newValue = $this->service->increment('test_counters', ['id' => $this->counterId], 'count', 5);

    expect($newValue)->toBe(5);
});

test('decrement decreases counter', function () {
    // Set initial value to 10
    DB::table('test_counters')->where('id', $this->counterId)->update(['count' => 10]);

    $newValue = $this->service->decrement('test_counters', ['id' => $this->counterId], 'count');

    expect($newValue)->toBe(9);
});

test('decrement by custom amount', function () {
    DB::table('test_counters')->where('id', $this->counterId)->update(['count' => 100]);

    $newValue = $this->service->decrement('test_counters', ['id' => $this->counterId], 'count', 25);

    expect($newValue)->toBe(75);
});

test('get returns current value', function () {
    DB::table('test_counters')->where('id', $this->counterId)->update(['count' => 42]);

    $value = $this->service->get('test_counters', ['id' => $this->counterId], 'count');

    expect($value)->toBe(42);
});

test('get returns zero for nonexistent', function () {
    // Generate a random ID that definitely doesn't exist (e.g., max int)
    $nonExistentId = 999999999;
    $value = $this->service->get('test_counters', ['id' => $nonExistentId], 'count');

    expect($value)->toBe(0);
});

test('set updates counter value', function () {
    $this->service->set('test_counters', ['id' => $this->counterId], 'count', 100);

    $value = DB::table('test_counters')->where('id', $this->counterId)->value('count');
    expect($value)->toBe(100);
});

test('multiple increments', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->service->increment('test_counters', ['id' => $this->counterId], 'count');
    }

    $value = $this->service->get('test_counters', ['id' => $this->counterId], 'count');
    expect($value)->toBe(10);
});
