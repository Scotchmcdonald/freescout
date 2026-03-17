<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Modules\Payment\Services\HelcimService;

it('enforces payment gateway request contract headers and payload', function () {
    config()->set('services.helcim.api_url', 'https://api.helcim.com/v2');
    config()->set('services.helcim.api_token', 'helcim-test-token');

    Http::fake([
        'https://api.helcim.com/v2/card-transactions' => Http::response([
            'transactionId' => 'txn-123',
            'status' => 'approved',
        ], 200),
    ]);

    $gateway = new class(app(\App\Services\RateLimiterService::class), app(\App\Services\CircuitBreakerService::class)) extends HelcimService
    {
        public function transactionProbe(array $payload): array
        {
            $response = $this->makeApiCall('card-transactions', function () use ($payload) {
                return $this->client()->post("{$this->apiUrl}/card-transactions", $payload);
            });

            return (array) $response->json();
        }
    };

    $result = $gateway->transactionProbe([
        'amount' => 99.95,
        'currency' => 'USD',
        'cardToken' => 'card_tok_abc',
        'invoiceNumber' => 'INV-1001',
    ]);

    expect($result)->toMatchArray([
        'transactionId' => 'txn-123',
        'status' => 'approved',
    ]);

    Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
        return $request->url() === 'https://api.helcim.com/v2/card-transactions'
            && $request->hasHeader('api-token', 'helcim-test-token')
            && $request->hasHeader('Accept', 'application/json')
            && $request['amount'] === 99.95
            && $request['currency'] === 'USD'
            && $request['cardToken'] === 'card_tok_abc'
            && $request['invoiceNumber'] === 'INV-1001';
    });
});

it('surfaces gateway failure status for adapter-level handling', function () {
    config()->set('services.helcim.api_url', 'https://api.helcim.com/v2');
    config()->set('services.helcim.api_token', 'helcim-test-token');

    Http::fake([
        'https://api.helcim.com/v2/card-transactions' => Http::response([
            'message' => 'declined',
        ], 422),
    ]);

    $gateway = new class(app(\App\Services\RateLimiterService::class), app(\App\Services\CircuitBreakerService::class)) extends HelcimService
    {
        public function transactionStatusProbe(): void
        {
            $this->makeApiCall('card-transactions', function () {
                return $this->client()->post("{$this->apiUrl}/card-transactions", ['amount' => 50]);
            });
        }
    };

    expect(fn () => $gateway->transactionStatusProbe())
        ->toThrow(\Illuminate\Http\Client\RequestException::class);
});
