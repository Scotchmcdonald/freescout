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

it('keeps partial success payloads in adapter response shape without coercion', function () {
    config()->set('services.helcim.api_url', 'https://api.helcim.com/v2');
    config()->set('services.helcim.api_token', 'helcim-test-token');

    Http::fake([
        'https://api.helcim.com/v2/card-transactions' => Http::response([
            // Partial payload intentionally missing transactionId
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

    $result = $gateway->transactionProbe(['amount' => 20.50]);

    expect($result)->toHaveKey('status')
        ->and($result)->not->toHaveKey('transactionId')
        ->and($result['status'])->toBe('approved');
});

it('maps malformed gateway payloads to deterministic adapter exceptions', function () {
    config()->set('services.helcim.api_url', 'https://api.helcim.com/v2');
    config()->set('services.helcim.api_token', 'helcim-test-token');

    Http::fake([
        'https://api.helcim.com/v2/card-transactions' => Http::response('NOT_JSON_PAYLOAD', 200),
    ]);

    $gateway = new class(app(\App\Services\RateLimiterService::class), app(\App\Services\CircuitBreakerService::class)) extends HelcimService
    {
        public function strictTransactionProbe(array $payload): void
        {
            $response = $this->makeApiCall('card-transactions', function () use ($payload) {
                return $this->client()->post("{$this->apiUrl}/card-transactions", $payload);
            });

            $decoded = $response->json();
            if (! is_array($decoded) || ! array_key_exists('transactionId', $decoded)) {
                throw new RuntimeException('Malformed gateway response');
            }
        }
    };

    expect(fn () => $gateway->strictTransactionProbe(['amount' => 10]))
        ->toThrow(RuntimeException::class, 'Malformed gateway response');
});

it('preserves retry-after contract details when gateway throttles', function () {
    config()->set('services.helcim.api_url', 'https://api.helcim.com/v2');
    config()->set('services.helcim.api_token', 'helcim-test-token');

    Http::fake([
        'https://api.helcim.com/v2/card-transactions' => Http::response([
            'message' => 'too many requests',
        ], 429, ['Retry-After' => '7']),
    ]);

    $gateway = new class(app(\App\Services\RateLimiterService::class), app(\App\Services\CircuitBreakerService::class)) extends HelcimService
    {
        public function throttledProbe(): void
        {
            $this->makeApiCall('card-transactions', function () {
                return $this->client()->post("{$this->apiUrl}/card-transactions", ['amount' => 12]);
            });
        }
    };

    try {
        $gateway->throttledProbe();
        $this->fail('Expected RequestException was not thrown');
    } catch (\Illuminate\Http\Client\RequestException $exception) {
        expect($exception->response->status())->toBe(429)
            ->and($exception->response->header('Retry-After'))->toBe('7');
    }
});

it('enforces refund endpoint contract with correct transactionType payload and auth headers', function () {
    config()->set('services.helcim.api_url', 'https://api.helcim.com/v2');
    config()->set('services.helcim.api_token', 'helcim-refund-test-token');

    Http::fake([
        'https://api.helcim.com/v2/card-transactions' => Http::response([
            'transactionId' => 'refund-txn-456',
            'status' => 'approved',
        ], 200),
    ]);

    $gateway = new class(app(\App\Services\RateLimiterService::class), app(\App\Services\CircuitBreakerService::class)) extends HelcimService
    {
        public function refundProbe(string $transactionId, float $amount): array
        {
            $response = $this->makeApiCall('card-transactions', function () use ($transactionId, $amount) {
                return $this->client()->post("{$this->apiUrl}/card-transactions", [
                    'transactionId' => $transactionId,
                    'amount' => $amount,
                    'transactionType' => 'refund',
                ]);
            });

            return (array) $response->json();
        }
    };

    $result = $gateway->refundProbe('orig-txn-123', 49.99);

    expect($result)->toMatchArray([
        'transactionId' => 'refund-txn-456',
        'status' => 'approved',
    ]);

    // Verify the refund request carries the correct auth header AND refund payload
    Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
        return $request->url() === 'https://api.helcim.com/v2/card-transactions'
            && $request->hasHeader('api-token', 'helcim-refund-test-token')
            && $request['transactionId'] === 'orig-txn-123'
            && (float) $request['amount'] === 49.99
            && $request['transactionType'] === 'refund';
    });
});

it('maps refund 422 rejection to request exception without silencing the failure', function () {
    config()->set('services.helcim.api_url', 'https://api.helcim.com/v2');
    config()->set('services.helcim.api_token', 'helcim-refund-fail-token');

    Http::fake([
        'https://api.helcim.com/v2/card-transactions' => Http::response([
            'message' => 'Transaction already fully refunded',
        ], 422),
    ]);

    $gateway = new class(app(\App\Services\RateLimiterService::class), app(\App\Services\CircuitBreakerService::class)) extends HelcimService
    {
        public function refundProbe(string $transactionId, float $amount): void
        {
            $this->makeApiCall('card-transactions', function () use ($transactionId, $amount) {
                return $this->client()->post("{$this->apiUrl}/card-transactions", [
                    'transactionId' => $transactionId,
                    'amount' => $amount,
                    'transactionType' => 'refund',
                ]);
            });
        }
    };

    // A 422 refund rejection must throw — adapters must never silently discard
    // gateway rejections for financial operations
    expect(fn () => $gateway->refundProbe('orig-txn-789', 25.00))
        ->toThrow(\Illuminate\Http\Client\RequestException::class);
});

it('treats non-scalar transactionId in successful payload as malformed adapter contract', function () {
    config()->set('services.helcim.api_url', 'https://api.helcim.com/v2');
    config()->set('services.helcim.api_token', 'helcim-test-token');

    Http::fake([
        'https://api.helcim.com/v2/card-transactions' => Http::response([
            // Mutation: transactionId must be scalar, not nested object
            'transactionId' => ['value' => 'txn-123'],
            'status' => 'approved',
        ], 200),
    ]);

    $gateway = new class(app(\App\Services\RateLimiterService::class), app(\App\Services\CircuitBreakerService::class)) extends HelcimService
    {
        public function strictTransactionProbe(array $payload): void
        {
            $response = $this->makeApiCall('card-transactions', function () use ($payload) {
                return $this->client()->post("{$this->apiUrl}/card-transactions", $payload);
            });

            $decoded = $response->json();
            if (! is_array($decoded) || ! isset($decoded['transactionId']) || ! is_scalar($decoded['transactionId'])) {
                throw new RuntimeException('Malformed gateway response');
            }
        }
    };

    expect(fn () => $gateway->strictTransactionProbe(['amount' => 10]))
        ->toThrow(RuntimeException::class, 'Malformed gateway response');
});
