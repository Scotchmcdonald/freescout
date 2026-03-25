<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;

test('tsdm activation endpoint is rate limited to 10 requests per minute per ip', function () {
    $ip = '10.250.1.30';
    $code = 'INVALID-CODE-123';
    $limiterKey = sha1('|'.$ip);

    for ($i = 0; $i < 10; $i++) {
        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/tsdm/activate', [
                'code' => $code,
            ]);

        // Invalid code is expected; throttling should not trigger before threshold.
        expect($response->status())->toBeIn([404, 422]);
    }

    expect(Cache::has($limiterKey))->toBeTrue();
    expect((int) Cache::get($limiterKey))->toBe(10);
    expect(Cache::has($limiterKey.':timer'))->toBeTrue();

    $throttled = $this
        ->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson('/api/tsdm/activate', [
            'code' => $code,
        ]);

    $throttled->assertStatus(429);
    $throttled->assertHeader('Retry-After');
    expect((int) Cache::get($limiterKey))->toBe(10);
})->group('boundary');
