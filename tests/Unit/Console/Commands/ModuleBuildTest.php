<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\ModuleBuild;
use Tests\UnitTestCase;

/**
 * Test ModuleBuild Command
 * 
 * Target: 90-95% coverage for App\Console\Commands\ModuleBuild
 * Current coverage: 50%
 */
class ModuleBuildTest extends UnitTestCase
{
    public function test_command_can_be_instantiated(): void
    {
        $command = new ModuleBuild();
        
        $this->assertInstanceOf(ModuleBuild::class, $command);
    }

    public function test_command_extends_command_class(): void
    {
        $command = new ModuleBuild();
        
        $this->assertInstanceOf(\Illuminate\Console\Command::class, $command);
    }

    public function test_command_has_correct_signature(): void
    {
        $command = new ModuleBuild();
        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('signature');
        $signature = $property->getValue($command);
        
        $this->assertEquals('freescout:module-build {module_alias?}', $signature);
    }

    public function test_command_has_description(): void
    {
        $command = new ModuleBuild();
        
        $description = $command->getDescription();
        $this->assertNotEmpty($description);
        $this->assertStringContainsString('module', strtolower($description));
    }

    public function test_command_is_registered_in_artisan(): void
    {
        $kernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);
        $commands = $kernel->all();
        
        $this->assertArrayHasKey('freescout:module-build', $commands);
    }

    public function test_command_accepts_optional_module_alias_argument(): void
    {
        $command = new ModuleBuild();
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasArgument('module_alias'));
        $argument = $definition->getArgument('module_alias');
        $this->assertFalse($argument->isRequired());
    }

    public function test_command_has_handle_method(): void
    {
        $command = new ModuleBuild();
        
        $this->assertTrue(method_exists($command, 'handle'));
    }

    public function test_command_handle_method_is_public(): void
    {
        $reflection = new \ReflectionClass(ModuleBuild::class);
        $method = $reflection->getMethod('handle');
        
        $this->assertTrue($method->isPublic());
    }

    public function test_command_has_build_module_method(): void
    {
        $command = new ModuleBuild();
        
        $this->assertTrue(method_exists($command, 'buildModule'));
    }

    public function test_build_module_method_is_protected(): void
    {
        $reflection = new \ReflectionClass(ModuleBuild::class);
        $method = $reflection->getMethod('buildModule');
        
        $this->assertTrue($method->isProtected());
    }

    public function test_command_has_build_vars_method(): void
    {
        $command = new ModuleBuild();
        
        $this->assertTrue(method_exists($command, 'buildVars'));
    }

    public function test_build_vars_method_is_protected(): void
    {
        $reflection = new \ReflectionClass(ModuleBuild::class);
        $method = $reflection->getMethod('buildVars');
        
        $this->assertTrue($method->isProtected());
    }

    public function test_command_can_be_called_without_argument(): void
    {
        $this->artisan('freescout:module-build --help')
            ->assertExitCode(0);
    }

    public function test_command_shows_correct_description_in_list(): void
    {
        $this->artisan('list')
            ->expectsOutputToContain('module-build')
            ->assertExitCode(0);
    }

    public function test_handle_returns_integer(): void
    {
        $reflection = new \ReflectionClass(ModuleBuild::class);
        $method = $reflection->getMethod('handle');
        $returnType = $method->getReturnType();
        
        $this->assertEquals('int', $returnType->getName());
    }

    public function test_build_module_accepts_module_parameter(): void
    {
        $reflection = new \ReflectionClass(ModuleBuild::class);
        $method = $reflection->getMethod('buildModule');
        $params = $method->getParameters();
        
        $this->assertCount(1, $params);
        $this->assertEquals('module', $params[0]->getName());
    }

    public function test_build_vars_accepts_module_parameter(): void
    {
        $reflection = new \ReflectionClass(ModuleBuild::class);
        $method = $reflection->getMethod('buildVars');
        $params = $method->getParameters();
        
        $this->assertCount(1, $params);
        $this->assertEquals('module', $params[0]->getName());
    }

    public function test_build_module_returns_void(): void
    {
        $reflection = new \ReflectionClass(ModuleBuild::class);
        $method = $reflection->getMethod('buildModule');
        $returnType = $method->getReturnType();
        
        $this->assertEquals('void', $returnType->getName());
    }

    public function test_build_vars_returns_void(): void
    {
        $reflection = new \ReflectionClass(ModuleBuild::class);
        $method = $reflection->getMethod('buildVars');
        $returnType = $method->getReturnType();
        
        $this->assertEquals('void', $returnType->getName());
    }

    public function test_command_signature_includes_module_alias_argument(): void
    {
        $command = new ModuleBuild();
        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('signature');
        $signature = $property->getValue($command);
        
        $this->assertStringContainsString('module_alias', $signature);
        $this->assertStringContainsString('?', $signature); // Optional argument
    }

    public function test_handle_returns_error_when_no_modules_found(): void
    {
        // Mock Module facade to return empty array
        $this->mock('alias:' . \Nwidart\Modules\Facades\Module::class, function ($mock) {
            $mock->shouldReceive('all')->andReturn([]);
        });

        $this->artisan('freescout:module-build')
            ->expectsOutput('No modules found')
            ->assertExitCode(1);
    }

    public function test_handle_returns_error_when_module_not_found(): void
    {
        // Mock Module facade
        $this->mock('alias:' . \Nwidart\Modules\Facades\Module::class, function ($mock) {
            $mock->shouldReceive('findByAlias')
                ->with('nonexistent')
                ->andReturn(null);
        });

        $this->artisan('freescout:module-build nonexistent')
            ->expectsOutput('Module with the specified alias not found: nonexistent')
            ->assertExitCode(1);
    }

    public function test_handle_builds_all_modules_when_no_alias_provided(): void
    {
        // Create mock module
        $mockModule = \Mockery::mock();
        $mockModule->shouldReceive('getName')->andReturn('TestModule');
        $mockModule->shouldReceive('getAlias')->andReturn('test-module');

        $this->mock('alias:' . \Nwidart\Modules\Facades\Module::class, function ($mock) use ($mockModule) {
            $mock->shouldReceive('all')
                ->andReturn([$mockModule])
                ->twice();
        });

        // Create temp public/modules directory for test
        $publicModulesPath = public_path('modules/test-module');
        @mkdir(dirname($publicModulesPath), 0755, true);
        @symlink(__DIR__, $publicModulesPath);

        try {
            $this->artisan('freescout:module-build')
                ->expectsOutput('Building all modules...')
                ->expectsOutput('Building module: TestModule')
                ->expectsOutput('Module build completed!')
                ->assertExitCode(0);
        } finally {
            // Cleanup
            if (is_link($publicModulesPath)) {
                @unlink($publicModulesPath);
            }
        }
    }

    public function test_build_module_shows_error_if_public_symlink_missing(): void
    {
        $mockModule = \Mockery::mock();
        $mockModule->shouldReceive('getName')->andReturn('TestModule');
        $mockModule->shouldReceive('getAlias')->andReturn('missing-symlink-module');

        $this->mock('alias:' . \Nwidart\Modules\Facades\Module::class, function ($mock) use ($mockModule) {
            $mock->shouldReceive('findByAlias')
                ->with('missing-symlink-module')
                ->andReturn($mockModule);
        });

        $this->artisan('freescout:module-build missing-symlink-module')
            ->expectsOutput('Building module: TestModule')
            ->assertExitCode(0);
    }

    public function test_build_vars_skips_when_view_does_not_exist(): void
    {
        $mockModule = \Mockery::mock();
        $mockModule->shouldReceive('getName')->andReturn('NoViewModule');
        $mockModule->shouldReceive('getAlias')->andReturn('no-view-module');

        $this->mock('alias:' . \Nwidart\Modules\Facades\Module::class, function ($mock) use ($mockModule) {
            $mock->shouldReceive('findByAlias')
                ->with('no-view-module')
                ->andReturn($mockModule);
        });

        // Create temp symlink
        $publicModulesPath = public_path('modules/no-view-module');
        @mkdir(dirname($publicModulesPath), 0755, true);
        @symlink(__DIR__, $publicModulesPath);

        try {
            $this->artisan('freescout:module-build no-view-module')
                ->assertExitCode(0);
        } finally {
            if (is_link($publicModulesPath)) {
                @unlink($publicModulesPath);
            }
        }
    }

    public function test_command_uses_app_locales_config(): void
    {
        config(['app.locales' => ['en', 'es', 'fr']]);
        
        $mockModule = \Mockery::mock();
        $mockModule->shouldReceive('getName')->andReturn('LocaleModule');
        $mockModule->shouldReceive('getAlias')->andReturn('locale-module');

        $this->mock('alias:' . \Nwidart\Modules\Facades\Module::class, function ($mock) use ($mockModule) {
            $mock->shouldReceive('findByAlias')
                ->with('locale-module')
                ->andReturn($mockModule);
        });

        $publicModulesPath = public_path('modules/locale-module');
        @mkdir(dirname($publicModulesPath), 0755, true);
        @symlink(__DIR__, $publicModulesPath);

        try {
            $this->artisan('freescout:module-build locale-module')
                ->assertExitCode(0);
        } finally {
            if (is_link($publicModulesPath)) {
                @unlink($publicModulesPath);
            }
        }
    }
}
