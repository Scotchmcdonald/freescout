<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ModuleSourceService;
use Tests\PureUnitTestCase;

final class TestModuleSourceService extends ModuleSourceService
{
    /** @var array<int, array<string, mixed>> */
    public array $modules = [];

    /** @return array<int, array<string, mixed>> */
    public function callGetSampleModules(): array
    {
        return $this->getSampleModules();
    }

    /** @return array<int, array<string, mixed>> */
    public function getModules(): array
    {
        return $this->modules;
    }
}

class ModuleSourceServiceTest extends PureUnitTestCase
{
    private TestModuleSourceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TestModuleSourceService;
    }

    public function test_get_sample_modules_returns_expected_seed_data(): void
    {
        $modules = $this->service->callGetSampleModules();

        $this->assertCount(2, $modules);
        $this->assertSame('samplemodule', $modules[0]['alias']);
        $this->assertSame('Custom Reports', $modules[1]['name']);
        $this->assertSame('Free', $modules[0]['price']);
    }

    public function test_get_module_returns_matching_module_by_alias(): void
    {
        $this->service->modules = $this->service->callGetSampleModules();

        $module = $this->service->getModule('customreports');

        $this->assertNotNull($module);
        $this->assertSame('Custom Reports', $module['name']);
        $this->assertSame('2.1.0', $module['version']);
    }

    public function test_get_module_returns_null_when_alias_is_missing(): void
    {
        $this->service->modules = $this->service->callGetSampleModules();

        $module = $this->service->getModule('does-not-exist');

        $this->assertNull($module);
    }
}
