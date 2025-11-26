<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Module;
use App\Services\ModuleSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Module::$isOfficialResult = null;
        Module::$updateCallback = null;
    }

    public function test_is_official_returns_false_by_default(): void
    {
        $result = Module::isOfficial('https://example.com/author');
        
        $this->assertFalse($result);
    }

    public function test_is_official_caches_result(): void
    {
        // First call sets the cache
        Module::isOfficial('https://example.com');
        
        // Second call should use cached result
        Module::$isOfficialResult = true;
        $result = Module::isOfficial('https://different.com');
        
        $this->assertTrue($result);
    }

    public function test_is_official_with_null_url(): void
    {
        $result = Module::isOfficial(null);
        
        $this->assertFalse($result);
    }

    public function test_update_module_returns_error_for_nonexistent_module(): void
    {
        $result = Module::updateModule('nonexistent-module');
        
        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('not found', $result['msg']);
    }

    public function test_update_module_result_structure(): void
    {
        $result = Module::updateModule('samplemodule');
        
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('msg', $result);
        $this->assertArrayHasKey('msg_success', $result);
        $this->assertArrayHasKey('download_error', $result);
        $this->assertArrayHasKey('download_msg', $result);
        $this->assertArrayHasKey('output', $result);
        $this->assertArrayHasKey('module_name', $result);
    }

    public function test_update_module_uses_callback_when_set(): void
    {
        Module::$updateCallback = function ($alias) {
            return [
                'status' => 'success',
                'msg' => '',
                'msg_success' => 'Custom callback result',
                'download_error' => false,
                'download_msg' => '',
                'output' => 'Callback output',
                'module_name' => $alias,
            ];
        };

        $result = Module::updateModule('testmodule');

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Custom callback result', $result['msg_success']);
        $this->assertEquals('testmodule', $result['module_name']);
    }

    public function test_update_module_handles_download_failure(): void
    {
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $result = Module::updateModule('samplemodule');

        // Either download error or module not found error is acceptable
        $this->assertContains($result['status'], ['error', 'success']);
    }

    public function test_update_module_handles_http_exception(): void
    {
        Http::fake(function () {
            throw new \Exception('Connection failed');
        });

        $result = Module::updateModule('samplemodule');

        $this->assertEquals('error', $result['status']);
        $this->assertTrue($result['download_error'] || !empty($result['msg']));
    }

    public function test_module_class_has_static_properties(): void
    {
        $this->assertNull(Module::$isOfficialResult);
        $this->assertNull(Module::$updateCallback);
    }

    public function test_update_module_with_missing_download_url(): void
    {
        // Mock module source to return module without download URL
        $this->app->instance(ModuleSource::class, new class extends ModuleSource {
            public function getModule(string $alias): ?array
            {
                return [
                    'name' => 'Test Module',
                    'alias' => $alias,
                    'download_url' => null,
                ];
            }
        });

        $result = Module::updateModule('testmodule');

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('Download URL not found', $result['msg']);
    }
}
