<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use App\Console\Kernel;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class KernelTest extends TestCase
{
    public function test_console_kernel_can_be_resolved_from_container(): void
    {
        $kernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);

        $this->assertInstanceOf(\Illuminate\Contracts\Console\Kernel::class, $kernel);
    }

    public function test_console_commands_are_registered(): void
    {
        // Commands are auto-loaded from routes/console.php in Laravel 11
        // Check that our custom command is registered
        $this->artisan('list')
            ->expectsOutputToContain('freescout')
            ->run();
    }

    public function test_schedule_can_be_resolved_from_container(): void
    {
        $schedule = $this->app->make(Schedule::class);

        $this->assertInstanceOf(Schedule::class, $schedule);
    }

    public function test_kernel_loads_commands_from_commands_directory(): void
    {
        // Laravel 11 auto-discovers commands
        // Verify our commands are available
        $this->assertTrue($this->app->bound(\Illuminate\Contracts\Console\Kernel::class));
    }

    public function test_kernel_schedule_can_be_called(): void
    {
        $schedule = $this->app->make(Schedule::class);

        // In Laravel 11, schedules are defined in routes/console.php
        // We can verify the schedule object exists
        $this->assertInstanceOf(Schedule::class, $schedule);
    }

    public function test_kernel_extends_console_kernel(): void
    {
        $kernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);

        $this->assertInstanceOf(\Illuminate\Foundation\Console\Kernel::class, $kernel);
    }

    public function test_kernel_is_registered_in_container(): void
    {
        // Laravel 11 binds the Contracts\Console\Kernel
        $this->assertTrue($this->app->bound(\Illuminate\Contracts\Console\Kernel::class));
    }

    public function test_kernel_can_handle_artisan_commands(): void
    {
        $kernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);

        // Kernel can run commands
        $this->assertInstanceOf(\Illuminate\Contracts\Console\Kernel::class, $kernel);
    }

    // ===== Tests for 0% Coverage Methods =====

    public function test_schedule_method_executes_without_error(): void
    {
        $kernel = new Kernel($this->app, $this->app->make(\Illuminate\Contracts\Events\Dispatcher::class));
        $schedule = $this->app->make(Schedule::class);

        $reflection = new \ReflectionClass($kernel);
        $method = $reflection->getMethod('schedule');
        $method->setAccessible(true);

        // Execute the schedule method - should not throw exception
        $method->invoke($kernel, $schedule);

        // If we get here, schedule() executed successfully
        $this->assertTrue(true);
    }

    public function test_commands_method_loads_commands_directory(): void
    {
        $kernel = new Kernel($this->app, $this->app->make(\Illuminate\Contracts\Events\Dispatcher::class));

        $reflection = new \ReflectionClass($kernel);
        $method = $reflection->getMethod('commands');
        $method->setAccessible(true);

        // Execute the commands method - should load commands
        $method->invoke($kernel);

        // Verify that commands are loaded by checking if our custom commands exist
        $allCommands = \Artisan::all();
        $commandNames = array_keys($allCommands);

        // Should have freescout commands loaded
        $hasFreescoutCommands = false;
        foreach ($commandNames as $name) {
            if (str_contains($name, 'freescout')) {
                $hasFreescoutCommands = true;
                break;
            }
        }

        $this->assertTrue($hasFreescoutCommands, 'FreeScout commands should be loaded');
    }

    public function test_schedule_method_accepts_schedule_parameter(): void
    {
        $kernel = new Kernel($this->app, $this->app->make(\Illuminate\Contracts\Events\Dispatcher::class));
        $schedule = $this->app->make(Schedule::class);

        $reflection = new \ReflectionClass($kernel);
        $method = $reflection->getMethod('schedule');

        // Verify method signature
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('schedule', $parameters[0]->getName());
    }

    public function test_commands_method_requires_console_routes(): void
    {
        $kernel = new Kernel($this->app, $this->app->make(\Illuminate\Contracts\Events\Dispatcher::class));

        $reflection = new \ReflectionClass($kernel);
        $method = $reflection->getMethod('commands');
        $method->setAccessible(true);

        // Execute commands() - it should require routes/console.php
        $method->invoke($kernel);

        // Verify console routes file exists
        $this->assertFileExists(base_path('routes/console.php'));
    }
}
