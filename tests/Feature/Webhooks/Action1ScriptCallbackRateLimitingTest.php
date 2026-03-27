<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Action1ScriptCallbackController;
use Illuminate\Support\Facades\Cache;

test('action1 script callback endpoint is rate limited to 30 requests per minute per ip', function () {
    $ip = '10.250.1.10';
    $token = str_repeat('a', 40);
    $cacheKey = Action1ScriptCallbackController::CACHE_PREFIX.$token;

    Cache::put($cacheKey, [
        'status' => 'pending',
        'script_id' => 12345,
        'org_id' => 987,
        'minted_at' => now()->toIso8601String(),
    ], Action1ScriptCallbackController::TOKEN_TTL);

    for ($i = 0; $i < 30; $i++) {
        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson("/api/action1/script-callback/{$token}", [
                'status' => 'ok',
                'output' => 'test output',
                'host' => 'endpoint-1',
                'user' => 'system',
            ]);

        expect($response->status())->toBeIn([200, 200]);
    }

    $record = Cache::get($cacheKey);

    expect($record)->toBeArray();
    expect($record['status'] ?? null)->toBe('received');
    expect($record['script_id'] ?? null)->toBe(12345);
    expect($record['org_id'] ?? null)->toBe(987);
    expect($record['output'] ?? null)->toBe('test output');

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

    $recordAfterThrottle = Cache::get($cacheKey);

    expect($recordAfterThrottle)->toBeArray();
    expect($recordAfterThrottle['status'] ?? null)->toBe('received');
})->group('boundary');

test('action1 script callback rejects malformed tokens before touching cache', function () {
    $response = $this->postJson('/api/action1/script-callback/bad-token!!', [
        'status' => 'ok',
        'output' => 'ignored',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'ok' => false,
            'message' => 'Invalid token format.',
        ]);

    expect(Cache::get(Action1ScriptCallbackController::CACHE_PREFIX.'bad-token!!'))->toBeNull();
})->group('boundary');

test('action1 script callback truncates oversized output payloads and preserves token metadata', function () {
    $token = str_repeat('b', 40);
    $cacheKey = Action1ScriptCallbackController::CACHE_PREFIX.$token;
    $oversizedOutput = str_repeat('x', 70000);

    Cache::put($cacheKey, [
        'status' => 'pending',
        'script_id' => 444,
        'org_id' => 555,
        'minted_at' => now()->toIso8601String(),
    ], Action1ScriptCallbackController::TOKEN_TTL);

    $this->postJson("/api/action1/script-callback/{$token}", [
        'status' => 'ok',
        'output' => $oversizedOutput,
        'host' => 'endpoint-2',
        'user' => 'system',
    ])->assertOk();

    $record = Cache::get($cacheKey);

    expect($record)->toBeArray()
        ->and($record['status'] ?? null)->toBe('received')
        ->and($record['script_id'] ?? null)->toBe(444)
        ->and($record['org_id'] ?? null)->toBe(555)
        ->and($record['output'] ?? null)->toEndWith('…[truncated]')
        ->and(strlen((string) ($record['output'] ?? '')))->toBeGreaterThan(65536);
})->group('boundary');
