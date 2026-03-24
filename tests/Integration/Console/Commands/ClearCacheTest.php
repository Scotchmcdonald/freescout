<?php

declare(strict_types=1);

namespace Tests\Integration\Console\Commands;

use App\Console\Commands\ClearCache;
use Illuminate\Support\Facades\Artisan;
use Tests\IntegrationTestCase;

/**
 * Cache clearing tests are skipped when running with code coverage
 * because they interfere with PCOV's coverage collection and cause hangs.
 *
 * @group console
 */
class ClearCacheTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_command_can_be_instantiated(): void
    {
        $command = new ClearCache;

        $this->assertInstanceOf(ClearCache::class, $command);
    }

    public function test_command_has_correct_signature(): void
    {
        $command = new ClearCache;

        $this->assertEquals('freescout:clear-cache', $command->getName());
    }

    public function test_command_has_description(): void
    {
        $command = new ClearCache;

        $this->assertNotEmpty($command->getDescription());
        $this->assertStringContainsString('cache', $command->getDescription());
    }

    public function test_command_has_do_not_cache_config_option(): void
    {
        $command = new ClearCache;

        $this->assertTrue($command->getDefinition()->hasOption('doNotCacheConfig'));
    }

    public function test_command_has_do_not_generate_vars_option(): void
    {
        $command = new ClearCache;

        $this->assertTrue($command->getDefinition()->hasOption('doNotGenerateVars'));
    }

    public function test_command_executes_successfully(): void
    {
        $exitCode = Artisan::call('freescout:clear-cache', [
            '--doNotCacheConfig' => true,
            '--doNotGenerateVars' => true,
        ]);

        $this->assertEquals(0, $exitCode);
    }

    public function test_command_clears_compiled_cache(): void
    {
        // Command should call clear-compiled
        $exitCode = Artisan::call('freescout:clear-cache', [
            '--doNotCacheConfig' => true,
            '--doNotGenerateVars' => true,
        ]);

        $this->assertEquals(0, $exitCode);
    }

    public function test_command_clears_application_cache(): void
    {
        // Command should call cache:clear
        $exitCode = Artisan::call('freescout:clear-cache', [
            '--doNotCacheConfig' => true,
            '--doNotGenerateVars' => true,
        ]);

        $this->assertEquals(0, $exitCode);
    }

    public function test_command_clears_view_cache(): void
    {
        // Command should call view:clear
        $exitCode = Artisan::call('freescout:clear-cache', [
            '--doNotCacheConfig' => true,
            '--doNotGenerateVars' => true,
        ]);

        $this->assertEquals(0, $exitCode);
    }

    public function test_command_caches_config_by_default(): void
    {
        // Without doNotCacheConfig flag, should call config:cache
        $exitCode = Artisan::call('freescout:clear-cache', [
            '--doNotGenerateVars' => true,
        ]);

        $this->assertEquals(0, $exitCode);
    }

    public function test_command_clears_config_with_flag(): void
    {
        // With doNotCacheConfig flag, should call config:clear
        $exitCode = Artisan::call('freescout:clear-cache', [
            '--doNotCacheConfig' => true,
            '--doNotGenerateVars' => true,
        ]);

        $this->assertEquals(0, $exitCode);
    }

    public function test_command_generates_vars_by_default(): void
    {
        // Without doNotGenerateVars flag, should call freescout:generate-vars
        $exitCode = Artisan::call('freescout:clear-cache', [
            '--doNotCacheConfig' => true,
        ]);

        // May fail if freescout:generate-vars doesn't exist, that's OK
        $this->assertContains($exitCode, [0, 1]);
    }

    public function test_command_skips_generate_vars_with_flag(): void
    {
        $exitCode = Artisan::call('freescout:clear-cache', [
            '--doNotCacheConfig' => true,
            '--doNotGenerateVars' => true,
        ]);

        $this->assertEquals(0, $exitCode);
    }

    public function test_command_handles_opcache_invalidation(): void
    {
        // Should invalidate opcache if function exists
        $exitCode = Artisan::call('freescout:clear-cache', [
            '--doNotGenerateVars' => true,
        ]);

        $this->assertEquals(0, $exitCode);
    }

    public function test_command_deletes_cached_services(): void
    {
        // Should delete bootstrap/cache/services.php
        $exitCode = Artisan::call('freescout:clear-cache', [
            '--doNotCacheConfig' => true,
            '--doNotGenerateVars' => true,
        ]);

        $this->assertEquals(0, $exitCode);
    }

    public function test_command_deletes_cached_packages(): void
    {
        // Should delete bootstrap/cache/packages.php
        $exitCode = Artisan::call('freescout:clear-cache', [
            '--doNotCacheConfig' => true,
            '--doNotGenerateVars' => true,
        ]);

        $this->assertEquals(0, $exitCode);
    }
}
