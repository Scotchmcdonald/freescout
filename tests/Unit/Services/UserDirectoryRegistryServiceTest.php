<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\UserProvider;
use App\Services\UserDirectoryRegistryService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\PureUnitTestCase;

/**
 * Pure-unit tests for UserDirectoryRegistryService.
 *
 * All providers are Mockery doubles — no framework boot needed.
 */
final class UserDirectoryRegistryServiceTest extends PureUnitTestCase
{
    private UserDirectoryRegistryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Wire Log facade for error handling paths
        $logChannel = Mockery::mock('LogChannel');
        $logChannel->shouldReceive('error')->byDefault();
        $logManager = Mockery::mock('Illuminate\Log\LogManager');
        $logManager->shouldReceive('channel')->andReturn($logChannel)->byDefault();
        $logManager->shouldReceive('error')->byDefault();
        Log::swap($logManager);

        $this->service = new UserDirectoryRegistryService();
    }

    protected function tearDown(): void
    {
        Log::clearResolvedInstances();
        parent::tearDown();
    }

    public function test_get_all_users_returns_empty_when_no_providers(): void
    {
        $result = $this->service->getAllUsers();

        $this->assertSame([], $result);
    }

    public function test_register_adds_provider(): void
    {
        $provider = $this->makeProvider('Google Workspace', [
            ['name' => 'Alice', 'email' => 'alice@example.com'],
        ]);

        $this->service->register($provider);

        $result = $this->service->getAllUsers();

        $this->assertCount(1, $result);
        $this->assertSame('Alice', $result[0]['name']);
        $this->assertSame('Google Workspace', $result[0]['source']);
    }

    public function test_get_all_users_merges_multiple_providers(): void
    {
        $google = $this->makeProvider('Google', [
            ['name' => 'Alice', 'email' => 'alice@g.com'],
        ]);
        $azure = $this->makeProvider('Azure AD', [
            ['name' => 'Bob', 'email' => 'bob@az.com'],
            ['name' => 'Carol', 'email' => 'carol@az.com'],
        ]);

        $this->service->register($google);
        $this->service->register($azure);

        $result = $this->service->getAllUsers();

        $this->assertCount(3, $result);
        $this->assertSame('Google', $result[0]['source']);
        $this->assertSame('Azure AD', $result[1]['source']);
        $this->assertSame('Azure AD', $result[2]['source']);
    }

    public function test_get_all_users_continues_on_provider_failure(): void
    {
        $failing = Mockery::mock(UserProvider::class);
        $failing->shouldReceive('getUsers')
            ->andThrow(new \RuntimeException('Connection timeout'));
        $failing->shouldReceive('getSourceName')
            ->andReturn('Broken LDAP');

        $working = $this->makeProvider('Google', [
            ['name' => 'Alice', 'email' => 'alice@g.com'],
        ]);

        $this->service->register($failing);
        $this->service->register($working);

        $result = $this->service->getAllUsers();

        $this->assertCount(1, $result);
        $this->assertSame('Alice', $result[0]['name']);
    }

    public function test_source_field_is_injected_into_every_user_record(): void
    {
        $provider = $this->makeProvider('AD', [
            ['name' => 'X'],
            ['name' => 'Y'],
        ]);

        $this->service->register($provider);
        $result = $this->service->getAllUsers();

        foreach ($result as $user) {
            $this->assertArrayHasKey('source', $user);
            $this->assertSame('AD', $user['source']);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $users
     */
    private function makeProvider(string $name, array $users): UserProvider
    {
        $provider = Mockery::mock(UserProvider::class);
        $provider->shouldReceive('getUsers')->andReturn($users);
        $provider->shouldReceive('getSourceName')->andReturn($name);

        return $provider;
    }
}
