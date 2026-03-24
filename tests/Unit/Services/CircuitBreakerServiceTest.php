<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\CircuitBreakerService;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\PureUnitTestCase;

final class TestCircuitBreakerService extends CircuitBreakerService
{
    /** @var array<string, array<string, mixed>> */
    public array $states = [];

    /** @var array<int, array{service: string, state: string}> */
    public array $transitions = [];

    /** @var array<int, string> */
    public array $resetCalls = [];

    public ?bool $forceRecoveryDecision = null;

    public function setState(string $service, array $state): void
    {
        $this->states[$service] = array_merge([
            'service' => $service,
            'state' => self::STATE_CLOSED,
            'failure_count' => 0,
            'last_failure_at' => null,
            'opened_at' => null,
        ], $state);
    }

    public function exposeShouldAttemptRecovery(array $state): bool
    {
        return $this->shouldAttemptRecovery($state);
    }

    protected function getState(string $service): array
    {
        return $this->states[$service] ?? [
            'service' => $service,
            'state' => self::STATE_CLOSED,
            'failure_count' => 0,
            'last_failure_at' => null,
            'opened_at' => null,
        ];
    }

    protected function transitionTo(string $service, string $newState): void
    {
        $state = $this->getState($service);
        $state['state'] = $newState;

        if ($newState === self::STATE_OPEN) {
            $state['opened_at'] = now()->toDateTimeString();
        } elseif ($newState === self::STATE_CLOSED) {
            $state['opened_at'] = null;
            $state['failure_count'] = 0;
        }

        $this->states[$service] = $state;
        $this->transitions[] = ['service' => $service, 'state' => $newState];
    }

    protected function recordFailure(string $service): void
    {
        $state = $this->getState($service);
        $state['failure_count'] = (int) $state['failure_count'] + 1;
        $state['last_failure_at'] = now()->toDateTimeString();
        $this->states[$service] = $state;
    }

    protected function resetFailures(string $service): void
    {
        $state = $this->getState($service);
        $state['failure_count'] = 0;
        $state['last_failure_at'] = null;
        $this->states[$service] = $state;
        $this->resetCalls[] = $service;
    }

    protected function shouldAttemptRecovery(array $state): bool
    {
        if ($this->forceRecoveryDecision !== null) {
            return $this->forceRecoveryDecision;
        }

        return parent::shouldAttemptRecovery($state);
    }
}

class CircuitBreakerServiceTest extends PureUnitTestCase
{
    private ?Container $previousContainer = null;

    private mixed $previousFacadeApplication = null;

    private TestCircuitBreakerService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();

        $app = new Application(getcwd());
        $app->instance('config', new Repository([
            'services' => ['circuit_breaker' => ['threshold' => 2, 'timeout' => 60]],
        ]));

        Container::setInstance($app);
        Facade::setFacadeApplication($app);

        Log::swap(new class
        {
            /** @var array<int, string> */
            public array $warnings = [];

            /** @var array<int, string> */
            public array $infos = [];

            public function warning(string $message): void
            {
                $this->warnings[] = $message;
            }

            public function info(string $message): void
            {
                $this->infos[] = $message;
            }
        });

        $this->service = new TestCircuitBreakerService;
    }

    protected function tearDown(): void
    {
        Facade::setFacadeApplication($this->previousFacadeApplication);
        Container::setInstance($this->previousContainer);

        parent::tearDown();
    }

    public function test_is_open_returns_true_when_state_is_open(): void
    {
        $this->service->setState('google', ['state' => CircuitBreakerService::STATE_OPEN]);

        $this->assertTrue($this->service->isOpen('google'));
    }

    public function test_is_open_returns_false_when_state_is_closed(): void
    {
        $this->service->setState('google', ['state' => CircuitBreakerService::STATE_CLOSED]);

        $this->assertFalse($this->service->isOpen('google'));
    }

    public function test_call_executes_callback_when_circuit_is_closed(): void
    {
        $this->service->setState('google', ['state' => CircuitBreakerService::STATE_CLOSED]);

        $result = $this->service->call('google', fn (): string => 'ok');

        $this->assertSame('ok', $result);
    }

    public function test_call_throws_when_circuit_open_and_recovery_not_allowed(): void
    {
        $this->service->setState('google', ['state' => CircuitBreakerService::STATE_OPEN]);
        $this->service->forceRecoveryDecision = false;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Circuit breaker is open for service: google');

        $this->service->call('google', fn (): string => 'never');
    }

    public function test_call_transitions_to_half_open_when_recovery_is_allowed(): void
    {
        $this->service->setState('google', [
            'state' => CircuitBreakerService::STATE_OPEN,
            'failure_count' => 1,
        ]);
        $this->service->forceRecoveryDecision = true;

        $this->service->call('google', fn (): string => 'recovered');

        $this->assertSame(CircuitBreakerService::STATE_HALF_OPEN, $this->service->transitions[0]['state']);
    }

    public function test_call_resets_failures_on_success_when_previous_failure_count_above_zero(): void
    {
        $this->service->setState('google', [
            'state' => CircuitBreakerService::STATE_CLOSED,
            'failure_count' => 3,
        ]);

        $this->service->call('google', fn (): string => 'ok');

        $this->assertSame(['google'], $this->service->resetCalls);
        $this->assertSame(0, $this->service->states['google']['failure_count']);
    }

    public function test_call_increments_failure_count_and_rethrows_exception(): void
    {
        $this->service->setState('google', [
            'state' => CircuitBreakerService::STATE_CLOSED,
            'failure_count' => 0,
        ]);

        try {
            $this->service->call('google', function (): void {
                throw new RuntimeException('upstream failed');
            });
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('upstream failed', $e->getMessage());
        }

        $this->assertSame(1, $this->service->states['google']['failure_count']);
    }

    public function test_call_opens_circuit_when_failure_threshold_is_reached(): void
    {
        $this->service->setState('google', [
            'state' => CircuitBreakerService::STATE_CLOSED,
            'failure_count' => 1,
        ]);

        try {
            $this->service->call('google', function (): void {
                throw new RuntimeException('boom');
            });
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $this->assertSame(CircuitBreakerService::STATE_OPEN, $this->service->states['google']['state']);
    }

    public function test_reset_transitions_to_closed_and_resets_failures(): void
    {
        $this->service->setState('google', [
            'state' => CircuitBreakerService::STATE_OPEN,
            'failure_count' => 5,
            'opened_at' => now()->subMinute()->toDateTimeString(),
        ]);

        $this->service->reset('google');

        $this->assertSame(CircuitBreakerService::STATE_CLOSED, $this->service->states['google']['state']);
        $this->assertSame(0, $this->service->states['google']['failure_count']);
        $this->assertContains('google', $this->service->resetCalls);
    }

    public function test_get_recent_transitions_currently_returns_empty_array_placeholder(): void
    {
        $this->assertSame([], $this->service->getRecentTransitions('google'));
    }

    public function test_should_attempt_recovery_returns_true_when_opened_at_is_missing(): void
    {
        $state = [
            'opened_at' => null,
        ];

        $this->assertTrue($this->service->exposeShouldAttemptRecovery($state));
    }

    public function test_should_attempt_recovery_returns_false_when_timeout_not_elapsed(): void
    {
        $state = [
            'opened_at' => now()->subSeconds(20)->toDateTimeString(),
        ];

        $this->assertFalse($this->service->exposeShouldAttemptRecovery($state));
    }

    public function test_should_attempt_recovery_returns_true_when_timeout_elapsed(): void
    {
        $state = [
            'opened_at' => now()->subSeconds(120)->toDateTimeString(),
        ];

        $this->assertTrue($this->service->exposeShouldAttemptRecovery($state));
    }
}
