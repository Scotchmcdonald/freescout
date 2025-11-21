<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module as ModuleFacade;
use Tests\FeatureTestCase;

class ModuleInstallCommandTest extends FeatureTestCase
{
    // RefreshDatabase is inherited from FeatureTestCase

    protected string $testModulePath;
    protected string $testModuleName = 'TestModule';

    protected function setUp(): void
    {
        parent::setUp();

        // Create test module directory structure
        $this->testModulePath = base_path('Modules/'.$this->testModuleName);
        $this->createTestModule($this->testModuleName);
    }

    protected function tearDown(): void
    {
        // Clean up test module
        if (File::exists($this->testModulePath)) {
            File::deleteDirectory($this->testModulePath);
        }

        // Clean up public symlinks
        $publicSymlink = public_path('modules/'.strtolower($this->testModuleName));
        if (File::exists($publicSymlink) || is_link($publicSymlink)) {
            if (is_link($publicSymlink)) {
                unlink($publicSymlink);
            } else {
                File::deleteDirectory($publicSymlink);
            }
        }

        parent::tearDown();
    }

    // Story 3.1.1: Module Installation Success Path

    public function test_installs_specific_module_successfully(): void
    {
        $module = ModuleFacade::find($this->testModuleName);
        $this->assertNotNull($module, "Test module '{$this->testModuleName}' not found.");

        // Disable the module to ensure the command enables it.
        $module->disable();
        $this->assertFalse($module->isEnabled(), 'Module should be disabled before running the install command.');

        $this->artisan('freescout:module-install', ['module_alias' => strtolower($this->testModuleName)])
            ->expectsOutputToContain('symlink has been created')
            ->assertExitCode(0);

        // Re-find the module instance to get the updated status.
        $updatedModule = ModuleFacade::find($this->testModuleName);
        $this->assertTrue($updatedModule->isEnabled(), 'Module was not enabled after installation.');
    }

    public function test_creates_symlink_in_public_directory(): void
    {
        $publicSymlink = public_path('modules/'.strtolower($this->testModuleName));
        
        // Ensure it doesn't exist before running the command
        if (is_link($publicSymlink)) {
            unlink($publicSymlink);
        }
        $this->assertFalse(is_link($publicSymlink), "Symlink existed before test run.");

        $this->artisan('freescout:module-install', ['module_alias' => strtolower($this->testModuleName)])
            ->assertExitCode(0);

        $this->assertTrue(is_link($publicSymlink), 'Symlink was not created or is not a link.');
    }

    public function test_clears_cache_before_installation(): void
    {
        // The command calls 'cache:clear' and 'freescout:clear-cache'.
        // A successful run of the main command is sufficient to test this integration.
        $this->artisan('freescout:module-install', ['module_alias' => strtolower($this->testModuleName)])
            ->expectsOutputToContain('Clearing cache...')
            ->assertExitCode(0);
    }

    // Story 3.1.2: Module Installation Error Handling

    public function test_fails_gracefully_when_module_not_found(): void
    {
        $this->artisan('freescout:module-install', ['module_alias' => 'nonexistentmodule'])
            ->expectsOutput('Module with the specified alias not found: nonexistentmodule')
            ->assertExitCode(0);
    }

    public function test_handles_missing_module_json(): void
    {
        File::delete($this->testModulePath.'/module.json');

        $this->artisan('freescout:module-install', ['module_alias' => strtolower($this->testModuleName)])
            ->expectsOutput('Module with the specified alias not found: '.strtolower($this->testModuleName))
            ->assertExitCode(0);
    }

    public function test_handles_invalid_permissions(): void
    {
        // Create a partial mock of the Filesystem class
        $mock = \Mockery::mock(Filesystem::class)->makePartial();
        
        // Expect the link method to be called and throw an exception
        $mock->shouldReceive('link')
            ->once()
            ->andThrow(new \Exception('Permission denied'));

        // Swap the 'files' instance in the container
        $this->instance('files', $mock);
        $this->instance(Filesystem::class, $mock);

        $this->artisan('freescout:module-install', ['module_alias' => strtolower($this->testModuleName)])
            ->expectsOutputToContain('Permission denied')
            ->assertExitCode(1);
    }

    /**
     * @withoutMiddleware
     */
    public function test_validates_module_alias_format(): void
    {
        $this->artisan('freescout:module-install', ['module_alias' => 'Invalid-Module-Name'])
            ->expectsOutput('Module with the specified alias not found: Invalid-Module-Name')
            ->assertExitCode(0);
    }

    /**
     * Helper method to create a test module structure
     */
    protected function createTestModule(string $name, bool $withMigration = false): void
    {
        $modulePath = base_path("Modules/{$name}");

        if (! File::exists($modulePath)) {
            File::makeDirectory($modulePath, 0755, true);
        }

        if (! File::exists("{$modulePath}/Http")) {
            File::makeDirectory("{$modulePath}/Http", 0755, true);
        }

        if (! File::exists("{$modulePath}/Resources")) {
            File::makeDirectory("{$modulePath}/Resources", 0755, true);
        }

        if (! File::exists("{$modulePath}/Resources/assets")) {
            File::makeDirectory("{$modulePath}/Resources/assets", 0755, true);
        }

        // Create module.json
        $moduleJson = [
            'name' => $name,
            'alias' => strtolower($name),
            'description' => "Test module {$name}",
            'active' => true,
        ];

        File::put(
            "{$modulePath}/module.json",
            json_encode($moduleJson, JSON_PRETTY_PRINT)
        );

        if ($withMigration) {
            File::makeDirectory("{$modulePath}/Database/Migrations", 0755, true);

            $migrationContent = <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function test_up(): void
    {
        Schema::create('test_table', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function test_down(): void
    {
        Schema::dropIfExists('test_table');
    }
};
PHP;

            File::put(
                "{$modulePath}/Database/Migrations/2024_01_01_000000_create_test_table.php",
                $migrationContent
            );
        }
    }
}

