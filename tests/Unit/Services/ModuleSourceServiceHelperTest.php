<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ModuleSourceService;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\PureUnitTestCase;

final class TestModuleSourceHelperService extends ModuleSourceService
{
    public function callGetSourceUrl(): string
    {
        return $this->getSourceUrl();
    }
}

class ModuleSourceServiceHelperTest extends PureUnitTestCase
{
    private ?Container $previousContainer = null;

    private mixed $previousFacadeApplication = null;

    private TestModuleSourceHelperService $service;

    private object $cacheStore;

    private object $httpClient;

    private object $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();

        $app = new Application(getcwd());
        $app->instance('config', new Repository([
            'modules' => [
                'source_url' => null,
            ],
        ]));
        $app['env'] = 'testing';

        Container::setInstance($app);
        Facade::setFacadeApplication($app);

        $this->cacheStore = new class
        {
            /** @var array<int, array{key: string, ttl: int}> */
            public array $rememberCalls = [];

            public function remember(string $key, int $ttl, callable $callback): array
            {
                $this->rememberCalls[] = ['key' => $key, 'ttl' => $ttl];

                return $callback();
            }
        };
        Cache::swap($this->cacheStore);

        $this->httpClient = new class
        {
            public bool $throw = false;

            public int $status = 200;

            /** @var mixed */
            public $modulesPayload = [];

            public function timeout(int $seconds): self
            {
                return $this;
            }

            public function get(string $url): object
            {
                if ($this->throw) {
                    throw new RuntimeException('upstream unreachable');
                }

                $status = $this->status;
                $payload = $this->modulesPayload;

                return new class($status, $payload)
                {
                    public function __construct(private int $status, private mixed $payload)
                    {
                    }

                    public function successful(): bool
                    {
                        return $this->status >= 200 && $this->status < 300;
                    }

                    public function status(): int
                    {
                        return $this->status;
                    }

                    public function json(string $key = null): mixed
                    {
                        if ($key === 'modules' && is_array($this->payload) && array_key_exists('modules', $this->payload)) {
                            return $this->payload['modules'];
                        }

                        return $this->payload;
                    }
                };
            }
        };
        Http::swap($this->httpClient);

        $this->logger = new class
        {
            /** @var array<int, string> */
            public array $warnings = [];

            /** @var array<int, string> */
            public array $errors = [];

            public function warning(string $message): void
            {
                $this->warnings[] = $message;
            }

            public function error(string $message): void
            {
                $this->errors[] = $message;
            }
        };
        Log::swap($this->logger);

        $this->service = new TestModuleSourceHelperService;
    }

    protected function tearDown(): void
    {
        Facade::setFacadeApplication($this->previousFacadeApplication);
        Container::setInstance($this->previousContainer);

        parent::tearDown();
    }

    public function test_get_source_url_returns_default_when_config_is_not_string(): void
    {
        $this->assertSame(
            'https://raw.githubusercontent.com/freescout-helpdesk/modules/main/modules.json',
            $this->service->callGetSourceUrl()
        );
    }

    public function test_get_source_url_returns_configured_value_when_present(): void
    {
        app('config')->set('modules.source_url', 'https://modules.example.test/list.json');

        $this->assertSame('https://modules.example.test/list.json', $this->service->callGetSourceUrl());
    }

    public function test_get_modules_uses_cache_and_returns_sample_modules_in_testing_environment(): void
    {
        $modules = $this->service->getModules();

        $this->assertCount(2, $modules);
        $this->assertSame('samplemodule', $modules[0]['alias']);
        $this->assertSame('customreports', $modules[1]['alias']);
        $this->assertSame([['key' => 'available_modules', 'ttl' => 3600]], $this->cacheStore->rememberCalls);
    }

    public function test_get_modules_returns_modules_from_successful_http_response_in_non_testing_env(): void
    {
        app()->instance('env', 'production');
        app('config')->set('modules.source_url', 'https://modules.example.test/list.json');
        $this->httpClient->status = 200;
        $this->httpClient->modulesPayload = [
            'modules' => [
                ['alias' => 'alpha', 'name' => 'Alpha'],
                ['alias' => 'beta', 'name' => 'Beta'],
            ],
        ];

        $modules = $this->service->getModules();

        $this->assertCount(2, $modules);
        $this->assertSame('alpha', $modules[0]['alias']);
        $this->assertSame([], $this->logger->warnings);
        $this->assertSame([], $this->logger->errors);
    }

    public function test_get_modules_returns_empty_when_successful_response_has_non_array_modules_key(): void
    {
        app()->instance('env', 'production');
        app('config')->set('modules.source_url', 'https://modules.example.test/list.json');
        $this->httpClient->status = 200;
        $this->httpClient->modulesPayload = ['modules' => 'invalid'];

        $modules = $this->service->getModules();

        $this->assertSame([], $modules);
    }

    public function test_get_modules_logs_warning_and_returns_empty_on_http_failure(): void
    {
        app()->instance('env', 'production');
        app('config')->set('modules.source_url', 'https://modules.example.test/list.json');
        $this->httpClient->status = 503;
        $this->httpClient->modulesPayload = [];

        $modules = $this->service->getModules();

        $this->assertSame([], $modules);
        $this->assertCount(1, $this->logger->warnings);
        $this->assertStringContainsString('Failed to fetch modules from source: 503', $this->logger->warnings[0]);
    }

    public function test_get_modules_logs_error_and_returns_empty_on_exception(): void
    {
        app()->instance('env', 'production');
        app('config')->set('modules.source_url', 'https://modules.example.test/list.json');
        $this->httpClient->throw = true;

        $modules = $this->service->getModules();

        $this->assertSame([], $modules);
        $this->assertCount(1, $this->logger->errors);
        $this->assertStringContainsString('Exception fetching modules: upstream unreachable', $this->logger->errors[0]);
    }
}

