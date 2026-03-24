<?php

test('tsdm activation endpoint is rate limited to 10 requests per minute per ip', function () {
    $ip = '10.250.1.30';

    for ($i = 0; $i < 10; $i++) {
        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/tsdm/activate', [
                'code' => 'INVALID-CODE-123',
            ]);

        // Invalid code is expected; throttling should not trigger before threshold.
        expect($response->status())->toBeIn([404, 422]);
    }

    $throttled = $this
        ->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson('/api/tsdm/activate', [
            'code' => 'INVALID-CODE-123',
        ]);

    $throttled->assertStatus(429);
    $throttled->assertHeader('Retry-After');
})->group('boundary');
