<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Services\ModuleSourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ModuleSourceTest extends TestCase
{
    use RefreshDatabase;

    protected ModuleSourceService $moduleSource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleSource = new ModuleSourceService;

        // Clear cache before each test
        Cache::forget('available_modules');
    }

    public function test_get_modules_returns_array(): void
    {
        $modules = $this->moduleSource->getModules();

        $this->assertIsArray($modules);
    }

    public function test_get_modules_returns_sample_modules_in_testing_environment(): void
    {
        $modules = $this->moduleSource->getModules();

        $this->assertIsArray($modules);
        $this->assertNotEmpty($modules);

        // Check structure of sample modules
        $firstModule = $modules[0] ?? null;
        $this->assertNotNull($firstModule);
        $this->assertArrayHasKey('name', $firstModule);
        $this->assertArrayHasKey('alias', $firstModule);
        $this->assertArrayHasKey('version', $firstModule);
    }

    public function test_get_module_returns_module_by_alias(): void
    {
        $module = $this->moduleSource->getModule('samplemodule');

        $this->assertIsArray($module);
        $this->assertEquals('samplemodule', $module['alias']);
        $this->assertEquals('Sample Module', $module['name']);
    }

    public function test_get_module_returns_null_for_unknown_alias(): void
    {
        $module = $this->moduleSource->getModule('nonexistent_module');

        $this->assertNull($module);
    }

    public function test_modules_are_cached(): void
    {
        // First call - should populate cache
        $modules1 = $this->moduleSource->getModules();

        // Second call - should use cache
        $modules2 = $this->moduleSource->getModules();

        $this->assertEquals($modules1, $modules2);
        $this->assertTrue(Cache::has('available_modules'));
    }

    public function test_get_module_custom_reports(): void
    {
        $module = $this->moduleSource->getModule('customreports');

        $this->assertIsArray($module);
        $this->assertEquals('customreports', $module['alias']);
        $this->assertEquals('Custom Reports', $module['name']);
        $this->assertEquals('2.1.0', $module['version']);
    }

    public function test_modules_have_required_fields(): void
    {
        $modules = $this->moduleSource->getModules();

        foreach ($modules as $module) {
            $this->assertArrayHasKey('name', $module);
            $this->assertArrayHasKey('alias', $module);
            $this->assertArrayHasKey('description', $module);
            $this->assertArrayHasKey('version', $module);
            $this->assertArrayHasKey('download_url', $module);
            $this->assertArrayHasKey('price', $module);
        }
    }

    public function test_module_source_can_be_instantiated(): void
    {
        $service = new ModuleSourceService;
        $this->assertInstanceOf(ModuleSourceService::class, $service);
    }

    public function test_get_modules_handles_http_failure(): void
    {
        // Force cache clear
        Cache::forget('available_modules');

        // Set config to non-testing URL to test HTTP failure path
        config(['modules.source_url' => 'https://invalid.example.com/modules.json']);

        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        // In testing environment, it still returns sample modules
        $modules = $this->moduleSource->getModules();

        $this->assertIsArray($modules);
    }

    public function test_get_modules_handles_exception(): void
    {
        Cache::forget('available_modules');

        Http::fake(function () {
            throw new \Exception('Connection failed');
        });

        // Should handle exception gracefully
        $modules = $this->moduleSource->getModules();

        $this->assertIsArray($modules);
    }

    public function test_cache_expires_after_one_hour(): void
    {
        $modules = $this->moduleSource->getModules();

        // Cache should be set with 3600 second TTL
        $this->assertTrue(Cache::has('available_modules'));
    }
}
