<?php

declare(strict_types=1);

namespace Tests\Integration\Payment;

use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use App\Services\CircuitBreakerService;
use App\Services\RateLimiterService;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Modules\Crm\Models\Company;
use Modules\Payment\Exceptions\HelcimException;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Services\HelcimService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\IntegrationTestCase;

class HelcimServiceTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.helcim.api_url', 'https://api.helcim.com/v2');
        config()->set('services.helcim.api_token', 'unit-test-token');
        config()->set('services.helcim.timeout', 30);
    }

    public function test_constructor_uses_configured_api_token_and_timeout(): void
    {
        config()->set('services.helcim.api_token', 'configured-token');
        config()->set('services.helcim.timeout', 45);

        $service = $this->makeServiceWithPassThroughGuards();

        $this->assertSame('configured-token', $this->readProperty($service, 'apiToken'));
        $this->assertSame(45, $this->readProperty($service, 'timeout'));
    }

    public function test_constructor_throws_when_api_token_is_missing_outside_console(): void
    {
        config()->set('services.helcim.api_token', '');

        $rateLimiter = $this->createMock(RateLimiterService::class);
        $circuitBreaker = $this->createMock(CircuitBreakerService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Helcim API token is not configured');

        $this->withNonConsoleContainer(function () use ($rateLimiter, $circuitBreaker): void {
            new HelcimService($rateLimiter, $circuitBreaker);
        });
    }

    public function test_make_api_call_uses_company_key_with_rate_limiter_and_circuit_breaker(): void
    {
        $rateLimiter = $this->createMock(RateLimiterService::class);
        $circuitBreaker = $this->createMock(CircuitBreakerService::class);

        $circuitBreaker
            ->expects($this->once())
            ->method('call')
            ->with('helcim', $this->callback('is_callable'))
            ->willReturnCallback(fn (string $service, callable $callback) => $callback());

        $rateLimiter
            ->expects($this->once())
            ->method('attempt')
            ->with('helcim_api:company:7:card-tokens', 60, 60, $this->callback('is_callable'))
            ->willReturnCallback(fn (string $key, int $max, int $decay, callable $callback) => $callback());

        $service = new HelcimService($rateLimiter, $circuitBreaker);

        $result = $this->invokeProtected($service, 'makeApiCall', [
            'card-tokens',
            fn (): string => 'ok',
            7,
        ]);

        $this->assertSame('ok', $result);
    }

    public function test_make_api_call_uses_global_key_when_company_missing(): void
    {
        $rateLimiter = $this->createMock(RateLimiterService::class);
        $circuitBreaker = $this->createMock(CircuitBreakerService::class);

        $circuitBreaker
            ->method('call')
            ->willReturnCallback(fn (string $service, callable $callback) => $callback());

        $rateLimiter
            ->expects($this->once())
            ->method('attempt')
            ->with('helcim_api:global:customers', 60, 60, $this->callback('is_callable'))
            ->willReturnCallback(fn (string $key, int $max, int $decay, callable $callback) => $callback());

        $service = new HelcimService($rateLimiter, $circuitBreaker);

        $result = $this->invokeProtected($service, 'makeApiCall', [
            'customers',
            fn (): string => 'global-ok',
            null,
        ]);

        $this->assertSame('global-ok', $result);
    }

    public function test_calculate_convenience_fee_returns_zero_when_disabled(): void
    {
        config()->set('services.helcim.convenience_fee_enabled', false);

        $service = $this->makeServiceWithPassThroughGuards();

        $fee = $this->invokeProtected($service, 'calculateConvenienceFee', [100.0, null]);

        $this->assertSame(0.0, $fee);
    }

    public function test_calculate_convenience_fee_returns_zero_for_debit_card(): void
    {
        config()->set('services.helcim.convenience_fee_enabled', true);
        config()->set('services.helcim.convenience_fee_percent', 2.9);
        config()->set('services.helcim.convenience_fee_flat', 0.3);

        $service = $this->makeServiceWithPassThroughGuards();
        $debit = new PaymentMethod(['card_type' => 'debit']);

        $fee = $this->invokeProtected($service, 'calculateConvenienceFee', [100.0, $debit]);

        $this->assertSame(0.0, $fee);
    }

    public function test_calculate_convenience_fee_applies_percent_and_flat_rounding(): void
    {
        config()->set('services.helcim.convenience_fee_enabled', true);
        config()->set('services.helcim.convenience_fee_percent', 2.9);
        config()->set('services.helcim.convenience_fee_flat', 0.3);

        $service = $this->makeServiceWithPassThroughGuards();

        $fee = $this->invokeProtected($service, 'calculateConvenienceFee', [12.34, null]);

        // round((12.34 * 0.029) + 0.30, 2) = 0.66
        $this->assertSame(0.66, $fee);
    }

    #[DataProvider('cardBrandProvider')]
    public function test_detect_card_brand_matches_patterns(string $cardNumber, string $expectedBrand): void
    {
        $service = $this->makeServiceWithPassThroughGuards();

        $brand = $this->invokeProtected($service, 'detectCardBrand', [$cardNumber]);

        $this->assertSame($expectedBrand, $brand);
    }

    /**
     * @return array<string, array{0:string, 1:string}>
     */
    public static function cardBrandProvider(): array
    {
        return [
            'visa' => ['4111 1111 1111 1111', 'Visa'],
            'mastercard' => ['5105105105105100', 'MasterCard'],
            'amex' => ['378282246310005', 'American Express'],
            'discover' => ['6011111111111117', 'Discover'],
            'jcb' => ['3530111333300000', 'JCB'],
            'unknown' => ['9111111111111111', 'Unknown'],
        ];
    }

    public function test_verify_webhook_signature_handles_valid_invalid_and_missing_secret(): void
    {
        $service = $this->makeServiceWithPassThroughGuards();
        $payload = '{"event":"payment.completed","id":"123"}';

        config()->set('services.helcim.webhook_secret', 'secret-key');
        $valid = hash_hmac('sha256', $payload, 'secret-key');
        $invalid = hash_hmac('sha256', $payload, 'other-key');

        $this->assertTrue($service->verifyWebhookSignature($payload, $valid));
        $this->assertFalse($service->verifyWebhookSignature($payload, $invalid));

        config()->set('services.helcim.webhook_secret', '');
        $this->assertFalse($service->verifyWebhookSignature($payload, $valid));
    }

    public function test_get_or_create_helcim_customer_returns_existing_customer_id_when_present(): void
    {
        $service = $this->makeServiceWithPassThroughGuards();
        $company = Company::factory()->create();

        PaymentMethod::factory()->create([
            'company_id' => $company->id,
            'helcim_customer_id' => 'CUST-EXISTING',
            'status' => 'active',
            'is_active' => true,
        ]);

        $customerId = $this->invokeProtected($service, 'getOrCreateHelcimCustomer', [$company]);

        $this->assertSame('CUST-EXISTING', $customerId);
    }

    public function test_get_or_create_helcim_customer_creates_customer_when_missing(): void
    {
        $service = $this->makeServiceWithPassThroughGuards();
        $company = Company::factory()->create();

        Http::preventStrayRequests();
        Http::fake([
            'https://api.helcim.com/v2/customers' => Http::response([
                'customerId' => 'CUST-NEW-123',
            ], 200),
        ]);

        $customerId = $this->invokeProtected($service, 'getOrCreateHelcimCustomer', [$company]);

        $this->assertSame('CUST-NEW-123', $customerId);
    }

    public function test_handle_error_response_throws_helcim_exception_with_status_code(): void
    {
        $service = $this->makeServiceWithPassThroughGuards();

        Http::preventStrayRequests();
        Http::fake([
            'https://example.test/error' => Http::response([
                'message' => 'Gateway unavailable',
            ], 503),
        ]);

        $response = app(HttpFactory::class)->post('https://example.test/error');

        $this->expectException(HelcimException::class);
        $this->expectExceptionCode(503);
        $this->expectExceptionMessage('Gateway unavailable');

        $this->invokeProtected($service, 'handleErrorResponse', [$response, 'Default message']);
    }

    private function makeServiceWithPassThroughGuards(): HelcimService
    {
        $rateLimiter = $this->createMock(RateLimiterService::class);
        $circuitBreaker = $this->createMock(CircuitBreakerService::class);

        $rateLimiter
            ->method('attempt')
            ->willReturnCallback(fn (string $key, int $max, int $decay, callable $callback) => $callback());

        $circuitBreaker
            ->method('call')
            ->willReturnCallback(fn (string $service, callable $callback) => $callback());

        return new HelcimService($rateLimiter, $circuitBreaker);
    }

    private function withNonConsoleContainer(callable $callback): void
    {
        $originalContainer = Container::getInstance();
        $fakeApp = new class($this->app->basePath()) extends Application
        {
            public function runningInConsole(): bool
            {
                return false;
            }
        };

        $fakeApp->instance('config', $this->app['config']);
        Container::setInstance($fakeApp);

        try {
            $callback();
        } finally {
            Container::setInstance($originalContainer);
        }
    }

    private function readProperty(object $target, string $property): mixed
    {
        $reflection = new \ReflectionProperty($target, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($target);
    }

    /**
     * @param  list<mixed>  $args
     */
    private function invokeProtected(object $target, string $method, array $args = []): mixed
    {
        $reflection = new \ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($target, $args);
    }
}
