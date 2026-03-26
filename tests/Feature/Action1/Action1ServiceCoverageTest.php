<?php

declare(strict_types=1);

use App\Models\Option;
use App\Services\CircuitBreakerService;
use App\Services\RateLimiterService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Action1\Services\Action1Service;

beforeEach(function () {
    Cache::flush();

    Option::updateOrCreate(['name' => 'action1_oauth_client_id'], ['value' => 'client-id']);
    Option::updateOrCreate(['name' => 'action1_client_secret'], ['value' => 'client-secret']);
    Option::updateOrCreate(['name' => 'action1_region'], ['value' => 'us']);

    $this->service = new Action1Service(
        app(RateLimiterService::class),
        app(CircuitBreakerService::class),
    );
});

it('authenticates once and reuses the cached token', function () {
    Http::fake([
        'https://app.action1.com/api/3.0/oauth2/token' => Http::response([
            'access_token' => 'cached-token',
            'expires_in' => 3600,
        ], 200),
    ]);

    $first = $this->service->authenticate();
    $second = $this->service->authenticate();

    expect($first)->toBe('cached-token')
        ->and($second)->toBe('cached-token')
        ->and(Option::getValue('action1_access_token'))->toBe('cached-token')
        ->and(Option::getValue('action1_token_expires_at'))->not->toBeNull();

    Http::assertSentCount(1);
});

it('uses region-specific endpoints when listing organizations', function () {
    Option::updateOrCreate(['name' => 'action1_region'], ['value' => 'eu']);

    Http::fake([
        'https://app.eu.action1.com/api/3.0/oauth2/token' => Http::response([
            'access_token' => 'eu-token',
            'expires_in' => 3600,
        ], 200),
        'https://app.eu.action1.com/api/3.0/organizations' => Http::response([
            'items' => [
                ['id' => 'org-1', 'name' => 'Acme'],
            ],
            'total_items' => 1,
        ], 200),
    ]);

    $organizations = $this->service->listOrganizations();

    expect($organizations)->toHaveCount(1)
        ->and($organizations[0]['id'])->toBe('org-1');

    Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
        return $request->url() === 'https://app.eu.action1.com/api/3.0/organizations'
            && $request->hasHeader('Authorization', 'Bearer eu-token');
    });
});

it('returns null when an endpoint is not found', function () {
    Http::fake([
        'https://app.action1.com/api/3.0/oauth2/token' => Http::response([
            'access_token' => 'token-404',
            'expires_in' => 3600,
        ], 200),
        'https://app.action1.com/api/3.0/endpoints/managed/org-1/missing-endpoint' => Http::response([], 404),
    ]);

    $endpoint = $this->service->getEndpoint('org-1', 'missing-endpoint');

    expect($endpoint)->toBeNull();
});

it('schedules a run-once script with parameters', function () {
    Http::fake([
        'https://app.action1.com/api/3.0/oauth2/token' => Http::response([
            'access_token' => 'run-token',
            'expires_in' => 3600,
        ], 200),
        'https://app.action1.com/api/3.0/automations/schedules/org-99' => Http::response([
            'id' => 'automation-123',
            'status' => 'scheduled',
        ], 200),
    ]);

    $result = $this->service->runScript(
        'org-99',
        'endpoint-7',
        'script-55',
        'Run diagnostics',
        [['name' => 'DiagnosticID', 'value' => 'abc123', 'type' => 'string']]
    );

    expect($result['id'])->toBe('automation-123');

    Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
        $data = $request->data();

        return $request->url() === 'https://app.action1.com/api/3.0/automations/schedules/org-99'
            && $data['name'] === 'Run diagnostics'
            && $data['actions'][0]['script_id'] === 'script-55'
            && $data['actions'][0]['parameters'][0]['name'] === 'DiagnosticID'
            && $data['scope']['endpoints'] === ['endpoint-7']
            && $data['schedule']['type'] === 'once';
    });
});

it('returns pending automation status when no instances exist yet', function () {
    Http::fake([
        'https://app.action1.com/api/3.0/oauth2/token' => Http::response([
            'access_token' => 'status-token',
            'expires_in' => 3600,
        ], 200),
        'https://app.action1.com/api/3.0/automations/instances/org-42*' => Http::response([
            'items' => [],
        ], 200),
    ]);

    $status = $this->service->getAutomationStatus('org-42', 'automation-77');

    expect($status['status'])->toBe('pending')
        ->and($status['details'])->toContain('No execution instance created yet');
});

it('returns latest automation execution stats when instances exist', function () {
    Http::fake([
        'https://app.action1.com/api/3.0/oauth2/token' => Http::response([
            'access_token' => 'status-token',
            'expires_in' => 3600,
        ], 200),
        'https://app.action1.com/api/3.0/automations/instances/org-42*' => Http::response([
            'items' => [[
                'id' => 'instance-1',
                'status' => 'completed',
                'created_at' => '2026-03-25T19:00:00Z',
                'finished_at' => '2026-03-25T19:01:00Z',
                'stats' => [
                    'total' => 3,
                    'succeeded' => 2,
                    'failed' => 1,
                ],
            ]],
        ], 200),
    ]);

    $status = $this->service->getAutomationStatus('org-42', 'automation-77');

    expect($status['status'])->toBe('completed')
        ->and($status['instance_id'])->toBe('instance-1')
        ->and($status['affected_endpoints'])->toBe(3)
        ->and($status['succeeded'])->toBe(2)
        ->and($status['failed'])->toBe(1);
});

it('surfaces retry-after value for rate-limited api responses', function () {
    Http::fake([
        'https://app.action1.com/api/3.0/oauth2/token' => Http::response([
            'access_token' => 'limit-token',
            'expires_in' => 3600,
        ], 200),
        'https://app.action1.com/api/3.0/organizations' => Http::response([
            'message' => 'slow down',
        ], 429, ['Retry-After' => '45']),
    ]);

    expect(fn () => $this->service->listOrganizations())
        ->toThrow(Exception::class, 'retry after 45 seconds');
});

it('throws when oauth credentials are missing', function () {
    Option::whereIn('name', ['action1_oauth_client_id', 'action1_client_secret'])->delete();

    expect(fn () => $this->service->authenticate())
        ->toThrow(Exception::class, 'Action1 OAuth2 credentials not configured');
});
