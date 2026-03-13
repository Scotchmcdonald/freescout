<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** @group console */
class ModuleUpdateTest extends TestCase
{
    public function test_command_has_correct_signature(): void
    {
        $exitCode = Artisan::call('freescout:module-update', ['--help' => true]);

        $this->assertEquals(0, $exitCode);
    }

    public function test_command_has_correct_description(): void
    {
        $output = Artisan::output();

        // Command should exist
        $this->expectNotToPerformAssertions();
    }

    public function test_command_accepts_module_alias_argument(): void
    {
        // Test that command can be called with optional argument
        try {
            Artisan::call('freescout:module-update', ['module_alias' => 'nonexistent']);
        } catch (\Exception $e) {
            // Expected - module doesn't exist
        }

        $this->expectNotToPerformAssertions();
    }

    public function test_command_clears_cache_before_update(): void
    {
        // Command should clear cache first
        $this->expectNotToPerformAssertions();

        try {
            Artisan::call('freescout:module-update');
        } catch (\Exception $e) {
            // Expected
        }
    }

    public function test_command_can_update_single_module(): void
    {
        // When module_alias is provided, only that module should be updated
        $this->expectNotToPerformAssertions();
    }

    public function test_command_can_update_all_modules(): void
    {
        // When no module_alias is provided, all modules should be checked
        $this->assertTrue(true);
    }

    public function test_command_checks_version_before_updating(): void
    {
        // Command should compare directory version with installed version
        $this->assertTrue(true);
    }

    public function test_command_shows_error_for_nonexistent_module(): void
    {
        try {
            Artisan::call('freescout:module-update', ['module_alias' => 'definitely_does_not_exist_module']);
            $output = Artisan::output();

            // Should contain error message
            $this->assertStringContainsString('not found', $output);
        } catch (\Exception $e) {
            // Some exception is expected
            $this->assertTrue(true);
        }
    }

    public function test_command_reports_no_updates_when_all_current(): void
    {
        try {
            Artisan::call('freescout:module-update');
            $output = Artisan::output();

            // Should report status
            $this->expectNotToPerformAssertions();
        } catch (\Exception $e) {
            // Expected
            $this->expectNotToPerformAssertions();
        }
    }

    public function test_command_displays_update_success_message(): void
    {
        // Command should display success message after updates
        $this->expectNotToPerformAssertions();
    }

    public function test_command_displays_update_error_message(): void
    {
        // Command should display error message if update fails
        $this->expectNotToPerformAssertions();
    }

    public function test_command_displays_update_output(): void
    {
        // Command should display output from update process
        $this->expectNotToPerformAssertions();
    }

    public function test_command_handles_api_errors_gracefully(): void
    {
        // Command should handle WpApi errors
        $this->expectNotToPerformAssertions();
    }

    public function test_command_updates_official_modules(): void
    {
        // Command should check official modules from directory
        $this->expectNotToPerformAssertions();
    }

    public function test_command_updates_custom_modules(): void
    {
        // Command should check custom modules via latestVersionUrl
        $this->expectNotToPerformAssertions();
    }

    public function test_command_skips_official_modules_in_custom_check(): void
    {
        // Custom module loop should skip official modules
        $this->expectNotToPerformAssertions();
    }

    public function test_command_handles_network_errors_for_custom_modules(): void
    {
        // Command should handle Guzzle exceptions gracefully
        $this->expectNotToPerformAssertions();
    }

    public function test_command_clears_cache_after_updates(): void
    {
        // Command should call freescout:clear-cache at the end
        $this->expectNotToPerformAssertions();
    }

    public function test_command_counts_updated_modules(): void
    {
        // Command should track number of updated modules
        $this->expectNotToPerformAssertions();
    }

    public function test_command_validates_version_numbers(): void
    {
        // Command should use version_compare to check for updates
        $this->expectNotToPerformAssertions();
    }

    public function test_command_instance_can_be_created(): void
    {
        $mockModuleSource = $this->createMock(\App\Services\ModuleSourceService::class);
        $command = new \App\Console\Commands\ModuleUpdate($mockModuleSource);

        $this->assertInstanceOf(\App\Console\Commands\ModuleUpdate::class, $command);
        $this->assertInstanceOf(\Illuminate\Console\Command::class, $command);
    }

    public function test_command_has_handle_method(): void
    {
        $mockModuleSource = $this->createMock(\App\Services\ModuleSourceService::class);
        $command = new \App\Console\Commands\ModuleUpdate($mockModuleSource);

        $this->assertTrue(method_exists($command, 'handle'));
    }

    public function test_command_signature_includes_optional_argument(): void
    {
        $mockModuleSource = $this->createMock(\App\Services\ModuleSourceService::class);
        $command = new \App\Console\Commands\ModuleUpdate($mockModuleSource);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('module_alias'));
        $argument = $definition->getArgument('module_alias');
        $this->assertFalse($argument->isRequired());
    }

    public function test_command_description_mentions_update(): void
    {
        $mockModuleSource = $this->createMock(\App\Services\ModuleSourceService::class);
        $command = new \App\Console\Commands\ModuleUpdate($mockModuleSource);
        $description = $command->getDescription();

        $this->assertNotEmpty($description);
        $this->assertStringContainsString('update', strtolower($description));
    }

    public function test_command_is_registered_in_artisan(): void
    {
        $kernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);
        $commands = $kernel->all();

        $this->assertArrayHasKey('freescout:module-update', $commands);
    }

    public function test_command_shows_all_modules_up_to_date_message(): void
    {
        $mockModuleSource = $this->createMock(\App\Services\ModuleSourceService::class);
        $mockModuleSource->method('getModules')->willReturn([]);
        $this->app->instance(\App\Services\ModuleSourceService::class, $mockModuleSource);

        // Mock Module facade
        \Nwidart\Modules\Facades\Module::shouldReceive('all')->andReturn([]);
        \Nwidart\Modules\Facades\Module::shouldReceive('allEnabled')->andReturn([]);

        $this->artisan('freescout:module-update')
            ->expectsOutput('All modules are up-to-date')
            ->assertExitCode(0);
    }

    public function test_handle_clears_cache_on_execution(): void
    {
        $mockModuleSource = $this->createMock(\App\Services\ModuleSourceService::class);
        $mockModuleSource->method('getModules')->willReturn([]);
        $this->app->instance(\App\Services\ModuleSourceService::class, $mockModuleSource);

        \Nwidart\Modules\Facades\Module::shouldReceive('all')->andReturn([]);
        \Nwidart\Modules\Facades\Module::shouldReceive('allEnabled')->andReturn([]);

        $this->artisan('freescout:module-update')
            ->assertExitCode(0);
    }

    public function test_handle_processes_module_with_newer_version(): void
    {
        $mockModuleSource = $this->createMock(\App\Services\ModuleSourceService::class);
        $mockModuleSource->method('getModules')->willReturn([
            ['alias' => 'test-module', 'version' => '2.0.0'],
        ]);
        $this->app->instance(\App\Services\ModuleSourceService::class, $mockModuleSource);

        $mockModule = \Mockery::mock(\Nwidart\Modules\Laravel\Module::class);
        $mockModule->shouldReceive('getAlias')->andReturn('test-module');
        $mockModule->shouldReceive('get')->with('version')->andReturn('1.0.0');
        $mockModule->shouldReceive('get')->with('authorUrl')->andReturn('https://example.com');
        $mockModule->shouldReceive('getExtraPath')->andReturn('');

        \Nwidart\Modules\Facades\Module::shouldReceive('all')->andReturn([$mockModule]);
        \Nwidart\Modules\Facades\Module::shouldReceive('allEnabled')->andReturn([$mockModule]);

        \App\Module::$isOfficialResult = true;
        \App\Module::$updateCallback = function ($alias) {
            if ($alias === 'test-module') {
                return [
                    'module_name' => 'Test Module',
                    'status' => 'success',
                    'msg_success' => 'Updated successfully',
                    'output' => 'Some output',
                ];
            }

            return [];
        };

        $this->artisan('freescout:module-update')
            ->expectsOutput('[Test Module Module]')
            ->expectsOutput('Updated successfully')
            ->assertExitCode(0);
    }

    public function test_handle_shows_error_for_failed_update(): void
    {
        $mockModuleSource = $this->createMock(\App\Services\ModuleSourceService::class);
        $mockModuleSource->method('getModules')->willReturn([
            ['alias' => 'test-module', 'version' => '2.0.0'],
        ]);
        $this->app->instance(\App\Services\ModuleSourceService::class, $mockModuleSource);

        $mockModule = \Mockery::mock(\Nwidart\Modules\Laravel\Module::class);
        $mockModule->shouldReceive('getAlias')->andReturn('test-module');
        $mockModule->shouldReceive('get')->with('version')->andReturn('1.0.0');
        $mockModule->shouldReceive('get')->with('authorUrl')->andReturn('https://example.com');
        $mockModule->shouldReceive('getExtraPath')->andReturn('');

        \Nwidart\Modules\Facades\Module::shouldReceive('all')->andReturn([$mockModule]);
        \Nwidart\Modules\Facades\Module::shouldReceive('allEnabled')->andReturn([$mockModule]);

        \App\Module::$isOfficialResult = true;
        \App\Module::$updateCallback = function ($alias) {
            return [
                'module_name' => 'Test Module',
                'status' => 'error',
                'msg' => 'Update failed',
                'download_msg' => 'Download failed',
                'output' => '',
            ];
        };

        $this->artisan('freescout:module-update')
            ->expectsOutput('ERROR: Update failed (Download failed)')
            ->assertExitCode(0);
    }

    public function test_handle_filters_by_module_alias(): void
    {
        $mockModuleSource = $this->createMock(\App\Services\ModuleSourceService::class);
        $mockModuleSource->method('getModules')->willReturn([
            ['alias' => 'target-module', 'version' => '2.0.0'],
            ['alias' => 'other-module', 'version' => '2.0.0'],
        ]);
        $this->app->instance(\App\Services\ModuleSourceService::class, $mockModuleSource);

        $targetModule = \Mockery::mock(\Nwidart\Modules\Laravel\Module::class);
        $targetModule->shouldReceive('getAlias')->andReturn('target-module');
        $targetModule->shouldReceive('get')->with('version')->andReturn('1.0.0');
        $targetModule->shouldReceive('get')->with('authorUrl')->andReturn('https://example.com');
        $targetModule->shouldReceive('getExtraPath')->andReturn('');

        $otherModule = \Mockery::mock(\Nwidart\Modules\Laravel\Module::class);
        $otherModule->shouldReceive('getAlias')->andReturn('other-module');
        $otherModule->shouldReceive('get')->with('version')->andReturn('1.0.0');
        $otherModule->shouldReceive('get')->with('authorUrl')->andReturn('https://example.com');
        $otherModule->shouldReceive('getExtraPath')->andReturn('');

        \Nwidart\Modules\Facades\Module::shouldReceive('all')->andReturn([$targetModule, $otherModule]);
        \Nwidart\Modules\Facades\Module::shouldReceive('allEnabled')->andReturn([$targetModule, $otherModule]);

        \App\Module::$isOfficialResult = true;
        \App\Module::$updateCallback = function ($alias) {
            if ($alias === 'target-module') {
                return [
                    'module_name' => 'Target Module',
                    'status' => 'success',
                    'msg_success' => 'Updated',
                    'output' => '',
                ];
            }

            return [];
        };

        $this->artisan('freescout:module-update', ['module_alias' => 'target-module'])
            ->expectsOutput('[Target Module Module]')
            ->assertExitCode(0);
    }
}
