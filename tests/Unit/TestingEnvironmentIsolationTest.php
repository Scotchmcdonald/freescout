<?php

declare(strict_types=1);

it('uses isolated in-memory sqlite for tests', function (): void {
    expect(app()->runningUnitTests())->toBeTrue()
        ->and(config('database.default'))->toBe('sqlite')
        ->and(config('database.connections.sqlite.database'))->toBe(':memory:')
        ->and(config('cache.default'))->toBe('array')
        ->and(config('session.driver'))->toBe('array')
        ->and(config('queue.default'))->toBe('sync');
});
