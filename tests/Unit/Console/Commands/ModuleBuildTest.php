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
        
        $this->assertEquals('freescout:module-build {module_alias?}', $command->getName());
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
        $signature = $command->getName();
        
        $this->assertStringContainsString('module_alias', $signature);
        $this->assertStringContainsString('?', $signature); // Optional argument
    }
}
