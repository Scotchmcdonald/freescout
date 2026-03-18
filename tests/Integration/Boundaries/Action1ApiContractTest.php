<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Action1\Enums\Action1Role;
use Modules\Action1\Services\Action1SyncService;

beforeEach(function () {
    config()->set('action1.region', 'us');
    config()->set('action1.roles.sync.client_id', 'sync-client-id');
    config()->set('action1.roles.sync.client_secret', 'sync-client-secret');

    Cache::forget(Action1Role::Sync->tokenCacheKey());
});

it('respects Action1 token and paginated endpoint contracts', function () {
    Http::fake([
        'https://app.action1.com/api/3.0/oauth2/token' => Http::response([
            'access_token' => 'token-sync-123',
            'expires_in' => 3600,
        ], 200),
        'https://app.action1.com/api/3.0/endpoints/managed/org-123*' => Http::sequence()
            ->push([
                'items' => [
                    ['id' => 'e-1', 'name' => 'Alpha'],
                    ['id' => 'e-2', 'name' => 'Bravo'],
                ],
                'total_items' => 3,
            ], 200)
            ->push([
                'items' => [
                    ['id' => 'e-3', 'name' => 'Charlie'],
                ],
                'total_items' => 3,
            ], 200),
    ]);

    $service = app(Action1SyncService::class);

    $endpoints = $service->listEndpoints('org-123', 2);

    expect($endpoints)->toHaveCount(3)
        ->and($endpoints[0]['id'])->toBe('e-1')
        ->and($endpoints[2]['id'])->toBe('e-3');

    Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
        return $request->url() === 'https://app.action1.com/api/3.0/oauth2/token'
            && $request['client_id'] === 'sync-client-id'
            && $request['client_secret'] === 'sync-client-secret';
    });

    Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
        return str_starts_with($request->url(), 'https://app.action1.com/api/3.0/endpoints/managed/org-123')
            && $request->hasHeader('Authorization', 'Bearer token-sync-123')
            && $request->data()['limit'] === 2
            && $request->data()['from'] === 0;
    });

    Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
        return str_starts_with($request->url(), 'https://app.action1.com/api/3.0/endpoints/managed/org-123')
            && $request->hasHeader('Authorization', 'Bearer token-sync-123')
            && $request->data()['limit'] === 2
            && $request->data()['from'] === 2;
    });
});

it('surfaces Action1 contract violations on non-success responses', function () {
    Http::fake([
        'https://app.action1.com/api/3.0/oauth2/token' => Http::response([
            'access_token' => 'token-sync-123',
            'expires_in' => 3600,
        ], 200),
        'https://app.action1.com/api/3.0/organizations' => Http::response([
            'message' => 'rate limit',
        ], 429, ['Retry-After' => '30']),
    ]);

    $service = app(Action1SyncService::class);

    expect(fn () => $service->listOrganizations())
        ->toThrow(RuntimeException::class, 'Action1 API rate limit exceeded');
});

it('maps malformed token payloads to typed runtime exceptions', function () {
    Http::fake([
        'https://app.action1.com/api/3.0/oauth2/token' => Http::response('not-json', 200),
    ]);

    $service = app(Action1SyncService::class);

    expect(fn () => $service->listOrganizations())
        ->toThrow(RuntimeException::class, 'No access_token in Action1 response');
});

it('handles partial endpoint payloads without crashing and keeps typed output', function () {
    Http::fake([
        'https://app.action1.com/api/3.0/oauth2/token' => Http::response([
            'access_token' => 'token-sync-123',
            'expires_in' => 3600,
        ], 200),
        'https://app.action1.com/api/3.0/endpoints/managed/org-partial*' => Http::response([
            // Missing total_items and includes malformed items
            'items' => ['bad-item', ['id' => 'ok-1', 'name' => 'Valid Endpoint']],
        ], 200),
    ]);

    $service = app(Action1SyncService::class);

    $endpoints = $service->listEndpoints('org-partial', 50);

    expect($endpoints)->toHaveCount(1)
        ->and($endpoints[0]['id'])->toBe('ok-1');
});

it('surfaces retry-after value in Action1 throttling exceptions', function () {
    Http::fake([
        'https://app.action1.com/api/3.0/oauth2/token' => Http::response([
            'access_token' => 'token-sync-123',
            'expires_in' => 3600,
        ], 200),
        'https://app.action1.com/api/3.0/organizations' => Http::response([
            'message' => 'slow down',
        ], 429, ['Retry-After' => '45']),
    ]);

    $service = app(Action1SyncService::class);

    expect(fn () => $service->listOrganizations())
        ->toThrow(RuntimeException::class, 'retry after 45 seconds');
});
