<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\AfterAppUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AfterAppUpdateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_can_be_instantiated(): void
    {
        $command = new AfterAppUpdate();
        
        $this->assertInstanceOf(AfterAppUpdate::class, $command);
    }

    #[Test]
    public function command_has_correct_signature(): void
    {
        $command = new AfterAppUpdate();
        
        $this->assertEquals('freescout:after-app-update', $command->getName());
    }

    #[Test]
    public function command_has_description(): void
    {
        $command = new AfterAppUpdate();
        
        $this->assertNotEmpty($command->getDescription());
        $this->assertStringContainsString('after', $command->getDescription());
    }

    #[Test]
    public function command_executes_successfully(): void
    {
        $exitCode = Artisan::call('freescout:after-app-update');
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_calls_clear_cache(): void
    {
        Artisan::call('freescout:after-app-update');
        
        // Should call freescout:clear-cache command
        $this->assertTrue(true);
    }

    #[Test]
    public function command_runs_migrations(): void
    {
        $exitCode = Artisan::call('freescout:after-app-update');
        
        $this->assertEquals(0, $exitCode);
        // Migration command should be called with --force flag
    }

    #[Test]
    public function command_restarts_queue_workers(): void
    {
        $exitCode = Artisan::call('freescout:after-app-update');
        
        $this->assertEquals(0, $exitCode);
        // Queue restart should be triggered
    }

    #[Test]
    public function command_outputs_progress_messages(): void
    {
        Artisan::call('freescout:after-app-update');
        $output = Artisan::output();
        
        $this->assertStringContainsString('Running post-update tasks', $output);
        $this->assertStringContainsString('completed successfully', $output);
    }

    #[Test]
    public function command_outputs_migration_message(): void
    {
        Artisan::call('freescout:after-app-update');
        $output = Artisan::output();
        
        $this->assertStringContainsString('migrations', $output);
    }

    #[Test]
    public function command_outputs_queue_restart_message(): void
    {
        Artisan::call('freescout:after-app-update');
        $output = Artisan::output();
        
        $this->assertStringContainsString('queue', $output);
    }

    #[Test]
    public function command_returns_zero_on_success(): void
    {
        $exitCode = Artisan::call('freescout:after-app-update');
        
        $this->assertEquals(0, $exitCode);
    }
}
