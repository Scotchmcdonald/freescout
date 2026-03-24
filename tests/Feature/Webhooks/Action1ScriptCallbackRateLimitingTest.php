<?php

declare(strict_types=1);

test('action1 script callback endpoint is rate limited to 30 requests per minute per ip', function () {
    $ip = '10.250.1.10';
    $token = str_repeat('a', 40);

    for ($i = 0; $i < 30; $i++) {
        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson("/api/action1/script-callback/{$token}", [
                'status' => 'ok',
                'output' => 'test output',
                'host' => 'endpoint-1',
                'user' => 'system',
            ]);

        // Unknown token is expected; throttling should not trigger before threshold.
        expect($response->status())->toBe(404);
    }

    $throttled = $this
        ->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson("/api/action1/script-callback/{$token}", [
            'status' => 'ok',
            'output' => 'test output',
            'host' => 'endpoint-1',
            'user' => 'system',
        ]);

    $throttled->assertStatus(429);
    $throttled->assertHeader('Retry-After');
})->group('boundary');
