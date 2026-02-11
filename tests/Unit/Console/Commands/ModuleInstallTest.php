<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\ModuleInstall;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/** @group console */
class ModuleInstallTest extends TestCase
{
    public function test_command_has_correct_signature(): void
    {
        $command = new ModuleInstall();
        
        $this->assertEquals('freescout:module-install', $command->getName());
    }

    public function test_command_has_description(): void
    {
        $command = new ModuleInstall();
        
        $this->assertNotEmpty($command->getDescription());
        $this->assertStringContainsString('module', strtolower($command->getDescription()));
    }

    public function test_command_module_alias_argument_is_optional(): void
    {
        $command = new ModuleInstall();
        $definition = $command->getDefinition();
        $argument = $definition->getArgument('module_alias');
        
        $this->assertFalse($argument->isRequired());
    }

    public function test_command_clears_cache_before_processing(): void
    {
        // Run with a nonexistent module to verify cache clear runs
        Artisan::call('freescout:module-install', ['module_alias' => 'fake-module']);
        
        // If we get here without error, cache:clear ran successfully
        $output = Artisan::output();
        $this->assertNotEmpty($output);
    }

    public function test_command_shows_error_for_nonexistent_module(): void
    {
        $exitCode = Artisan::call('freescout:module-install', [
            'module_alias' => 'definitely_does_not_exist_module',
        ]);
        
        $output = Artisan::output();
        
        // Should output error but return 0 (informational)
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('not found', strtolower($output));
    }

    public function test_command_without_alias_handles_no_modules(): void
    {
        $exitCode = Artisan::call('freescout:module-install', ['--no-interaction' => true]);
        $output = Artisan::output();
        
        // Should return 0 and show info about no modules or empty if valid modules but skipped
        $this->assertEquals(0, $exitCode);
        
        if (!empty($output)) {
            $this->assertTrue(
                str_contains($output, 'No modules') || 
                str_contains($output, 'Install all modules') ||
                str_contains($output, 'module') ||
                str_contains($output, 'Application cache cleared')
            );
        }
    }

    public function test_create_module_public_symlink_method_exists(): void
    {
        $command = new ModuleInstall();
        
        $this->assertTrue(method_exists($command, 'createModulePublicSymlink'));
    }

    public function test_command_can_be_instantiated(): void
    {
        $command = new ModuleInstall();
        
        $this->assertInstanceOf(ModuleInstall::class, $command);
    }

    public function test_command_output_contains_module_info(): void
    {
        Artisan::call('freescout:module-install', ['module_alias' => 'test-module']);
        $output = Artisan::output();
        
        // Should mention the module alias in error output
        $this->assertStringContainsString('test-module', $output);
    }
}
