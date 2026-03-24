<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\CacheService;
use Exception;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use Tests\PureUnitTestCase;

final class FakeTaggedCacheBucket
{
    /** @param list<string> $tags */
    public function __construct(private FakeCacheManager $manager, private array $tags) {}

    private function tagKey(string $key): string
    {
        return implode('|', $this->tags)."::{$key}";
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $tagKey = $this->tagKey($key);
        if (array_key_exists($tagKey, $this->manager->taggedStore)) {
            return $this->manager->taggedStore[$tagKey];
        }

        $value = $callback();
        $this->manager->taggedStore[$tagKey] = $value;

        return $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->manager->taggedStore[$this->tagKey($key)] ?? $default;
    }

    public function put(string $key, mixed $value, int $ttl): bool
    {
        $this->manager->taggedStore[$this->tagKey($key)] = $value;

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($this->tagKey($key), $this->manager->taggedStore);
    }

    public function forget(string $key): bool
    {
        $tagKey = $this->tagKey($key);
        if (! array_key_exists($tagKey, $this->manager->taggedStore)) {
            return false;
        }

        unset($this->manager->taggedStore[$tagKey]);

        return true;
    }

    public function flush(): bool
    {
        $prefix = implode('|', $this->tags).'::';
        foreach (array_keys($this->manager->taggedStore) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset($this->manager->taggedStore[$key]);
            }
        }

        return true;
    }
}

final class FakeCacheManager
{
    /** @var array<string, mixed> */
    public array $store = [];

    /** @var array<string, mixed> */
    public array $taggedStore = [];

    /** @var list<string> */
    public array $forgotten = [];

    /** @var list<string> */
    public array $foreverKeys = [];

    public function __construct(private bool $supportsTags) {}

    public function getStore(): object
    {
        if ($this->supportsTags) {
            return new class
            {
                public function tags(array $tags): void {}
            };
        }

        return new class {};
    }

    /** @param list<string> $tags */
    public function tags(array $tags): FakeTaggedCacheBucket
    {
        return new FakeTaggedCacheBucket($this, $tags);
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        if (array_key_exists($key, $this->store)) {
            return $this->store[$key];
        }

        $value = $callback();
        $this->store[$key] = $value;

        return $value;
    }

    public function put(string $key, mixed $value, int $ttl): bool
    {
        $this->store[$key] = $value;

        return true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->store);
    }

    public function forget(string $key): bool
    {
        $this->forgotten[] = $key;
        if (! array_key_exists($key, $this->store)) {
            return false;
        }

        unset($this->store[$key]);

        return true;
    }

    public function forever(string $key, mixed $value): bool
    {
        $this->foreverKeys[] = $key;
        $this->store[$key] = $value;

        return true;
    }
}

class CacheServiceTest extends PureUnitTestCase
{
    private ?Container $previousContainer = null;

    private mixed $previousFacadeApplication = null;

    private FakeCacheManager $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();

        $app = new Application(getcwd());
        Container::setInstance($app);
        Facade::setFacadeApplication($app);

        Log::swap(new class
        {
            public function debug(string $message, array $context = []): void {}

            public function warning(string $message, array $context = []): void {}

            public function info(string $message, array $context = []): void {}
        });
    }

    protected function tearDown(): void
    {
        Facade::setFacadeApplication($this->previousFacadeApplication);
        Container::setInstance($this->previousContainer);

        parent::tearDown();
    }

    private function makeService(bool $supportsTags): CacheService
    {
        $this->cache = new FakeCacheManager($supportsTags);
        Cache::swap($this->cache);

        return new CacheService;
    }

    public function test_remember_without_tags_caches_and_returns_callback_value(): void
    {
        $service = $this->makeService(false);
        $calls = 0;

        $first = $service->remember('crm', 'client', 10, 'summary', 60, function () use (&$calls): string {
            $calls++;

            return 'value-1';
        });
        $second = $service->remember('crm', 'client', 10, 'summary', 60, function () use (&$calls): string {
            $calls++;

            return 'value-2';
        });

        $this->assertSame('value-1', $first);
        $this->assertSame('value-1', $second);
        $this->assertSame(1, $calls);
    }

    public function test_remember_with_tags_stores_direct_key_copy_and_registry_key(): void
    {
        $service = $this->makeService(true);

        $value = $service->remember('crm', 'client', 25, 'balance', 120, fn (): int => 999);

        $this->assertSame(999, $value);
        $this->assertSame(999, $this->cache->store['crm:client:25:balance']);
        $this->assertArrayHasKey('cache_registry:crm:client:25', $this->cache->store);
    }

    public function test_put_without_tags_stores_value_and_updates_registry_once(): void
    {
        $service = $this->makeService(false);

        $first = $service->put('billing', 'invoice', 42, 'total', 1000, 300);
        $second = $service->put('billing', 'invoice', 42, 'total', 1100, 300);

        $this->assertTrue($first);
        $this->assertTrue($second);
        $this->assertSame(1100, $this->cache->store['billing:invoice:42:total']);

        $registry = $this->cache->store['cache_registry:billing:invoice:42'];
        $this->assertSame(['billing:invoice:42:total'], $registry);
    }

    public function test_put_with_tags_requires_both_tagged_and_direct_storage_success(): void
    {
        $service = $this->makeService(true);

        $result = $service->put('asset', 'device', 5, 'health', 'ok', 60);

        $this->assertTrue($result);
        $this->assertSame('ok', $this->cache->store['asset:device:5:health']);
    }

    public function test_get_without_tags_returns_default_when_missing(): void
    {
        $service = $this->makeService(false);

        $result = $service->get('crm', 'client', 99, 'missing', 'fallback');

        $this->assertSame('fallback', $result);
    }

    public function test_get_with_tags_prefers_tagged_value_then_falls_back_to_direct_key(): void
    {
        $service = $this->makeService(true);

        $this->cache->store['crm:client:1:summary'] = 'direct';
        $fromFallback = $service->get('crm', 'client', 1, 'summary', 'none');
        $this->assertSame('direct', $fromFallback);

        $this->cache->tags(['crm:client:1'])->put('crm:client:1:summary', 'tagged', 60);
        $fromTagged = $service->get('crm', 'client', 1, 'summary', 'none');
        $this->assertSame('tagged', $fromTagged);
    }

    public function test_has_without_tags_checks_direct_key_only(): void
    {
        $service = $this->makeService(false);
        $this->cache->store['crm:client:7:summary'] = 'x';

        $this->assertTrue($service->has('crm', 'client', 7, 'summary'));
        $this->assertFalse($service->has('crm', 'client', 7, 'missing'));
    }

    public function test_has_with_tags_checks_tagged_or_direct_key(): void
    {
        $service = $this->makeService(true);
        $this->cache->store['crm:client:7:summary'] = 'direct';

        $this->assertTrue($service->has('crm', 'client', 7, 'summary'));

        unset($this->cache->store['crm:client:7:summary']);
        $this->cache->tags(['crm:client:7'])->put('crm:client:7:summary', 'tagged', 60);

        $this->assertTrue($service->has('crm', 'client', 7, 'summary'));
    }

    public function test_forget_without_tags_removes_key_and_unregisters_from_registry(): void
    {
        $service = $this->makeService(false);
        $service->put('crm', 'client', 8, 'summary', 'value', 60);

        $result = $service->forget('crm', 'client', 8, 'summary');

        $this->assertTrue($result);
        $this->assertFalse(isset($this->cache->store['crm:client:8:summary']));
        $this->assertFalse(isset($this->cache->store['cache_registry:crm:client:8']));
    }

    public function test_forget_with_tags_returns_true_if_tagged_or_direct_forget_succeeds(): void
    {
        $service = $this->makeService(true);
        $this->cache->store['crm:client:9:summary'] = 'value';

        $result = $service->forget('crm', 'client', 9, 'summary');

        $this->assertTrue($result);
    }

    public function test_forget_without_tags_returns_false_when_key_does_not_exist(): void
    {
        $service = $this->makeService(false);

        $result = $service->forget('crm', 'client', 999, 'missing');

        $this->assertFalse($result);
    }

    public function test_flush_entity_without_tags_forgets_all_registered_keys_and_registry_entry(): void
    {
        $service = $this->makeService(false);
        $service->put('crm', 'client', 10, 'a', 'one', 60);
        $service->put('crm', 'client', 10, 'b', 'two', 60);

        $service->flushEntity('crm', 'client', 10);

        $this->assertFalse(isset($this->cache->store['crm:client:10:a']));
        $this->assertFalse(isset($this->cache->store['crm:client:10:b']));
        $this->assertFalse(isset($this->cache->store['cache_registry:crm:client:10']));
    }

    public function test_flush_entity_with_tags_forgets_mirrored_direct_keys_and_flushes_tag(): void
    {
        $service = $this->makeService(true);
        $service->put('crm', 'client', 11, 'a', 'one', 60);
        $service->put('crm', 'client', 11, 'b', 'two', 60);

        $service->flushEntity('crm', 'client', 11);

        $this->assertFalse(isset($this->cache->store['crm:client:11:a']));
        $this->assertFalse(isset($this->cache->store['crm:client:11:b']));
        $this->assertFalse(isset($this->cache->store['cache_registry:crm:client:11']));
        $this->assertSame([], array_filter(array_keys($this->cache->taggedStore), fn (string $k): bool => str_contains($k, 'crm:client:11')));
    }

    public function test_flush_entity_handles_non_array_registry_values_gracefully(): void
    {
        $service = $this->makeService(false);
        $this->cache->store['cache_registry:crm:client:12'] = 'invalid';

        $service->flushEntity('crm', 'client', 12);

        $this->assertFalse(isset($this->cache->store['cache_registry:crm:client:12']));
    }

    public function test_warm_multiple_counts_successes_and_skips_exceptions(): void
    {
        $service = $this->makeService(false);

        $warmed = $service->warmMultiple('crm', 'client', [1, 2, 3], 'summary', 60, function (int $id): string {
            if ($id === 2) {
                throw new Exception('failed');
            }

            return "ok-{$id}";
        });

        $this->assertSame(2, $warmed);
        $this->assertSame('ok-1', $this->cache->store['crm:client:1:summary']);
        $this->assertFalse(isset($this->cache->store['crm:client:2:summary']));
        $this->assertSame('ok-3', $this->cache->store['crm:client:3:summary']);
    }

    public function test_warm_multiple_with_empty_entity_ids_returns_zero(): void
    {
        $service = $this->makeService(false);

        $warmed = $service->warmMultiple('crm', 'client', [], 'summary', 60, fn (int $id): string => 'x');

        $this->assertSame(0, $warmed);
    }

    public function test_remember_without_attribute_builds_three_part_key(): void
    {
        $service = $this->makeService(false);

        $value = $service->remember('crm', 'client', 13, null, 60, fn (): string => 'ok');

        $this->assertSame('ok', $value);
        $this->assertArrayHasKey('crm:client:13', $this->cache->store);
    }

    public function test_get_and_has_without_attribute_use_three_part_key(): void
    {
        $service = $this->makeService(false);
        $service->put('crm', 'client', 14, null, 'hello', 60);

        $this->assertSame('hello', $service->get('crm', 'client', 14, null, 'fallback'));
        $this->assertTrue($service->has('crm', 'client', 14, null));
    }

    public function test_flush_domain_flushes_using_domain_tag(): void
    {
        $service = $this->makeService(true);
        $this->cache->tags(['crm'])->put('x', 'y', 60);

        $service->flushDomain('crm');

        $this->assertSame([], $this->cache->taggedStore);
    }

    public function test_ttl_constants_have_expected_values(): void
    {
        $this->assertSame(86400, CacheService::TTL_USER_PERMISSIONS);
        $this->assertSame(300, CacheService::TTL_CLIENT_ENTITLEMENTS);
        $this->assertSame(60, CacheService::TTL_CREDIT_BALANCE);
        $this->assertSame(300, CacheService::TTL_ASSET_COUNT);
        $this->assertSame(3600, CacheService::TTL_RATE_LIMITER);
        $this->assertSame(300, CacheService::TTL_QUERY_RESULTS);
        $this->assertSame(60, CacheService::TTL_HOT_DATA);
    }
}
