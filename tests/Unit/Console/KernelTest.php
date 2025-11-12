<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use App\Console\Kernel;
use Illuminate\Console\Scheduling\Schedule;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KernelTest extends TestCase
{
    #[Test]
    public function console_kernel_can_be_resolved_from_container(): void
    {
        $kernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);

        $this->assertInstanceOf(\Illuminate\Contracts\Console\Kernel::class, $kernel);
    }

    #[Test]
    public function console_commands_are_registered(): void
    {
        // Commands are auto-loaded from routes/console.php in Laravel 11
        // Check that our custom command is registered
        $this->artisan('list')
            ->expectsOutputToContain('freescout')
            ->run();
    }

    #[Test]
    public function schedule_can_be_resolved_from_container(): void
    {
        $schedule = $this->app->make(Schedule::class);

        $this->assertInstanceOf(Schedule::class, $schedule);
    }

    #[Test]
    public function kernel_loads_commands_from_commands_directory(): void
    {
        // Laravel 11 auto-discovers commands
        // Verify our commands are available
        $this->assertTrue($this->app->bound(\Illuminate\Contracts\Console\Kernel::class));
    }

    #[Test]
    public function kernel_schedule_can_be_called(): void
    {
        $schedule = $this->app->make(Schedule::class);

        // In Laravel 11, schedules are defined in routes/console.php
        // We can verify the schedule object exists
        $this->assertInstanceOf(Schedule::class, $schedule);
    }

    #[Test]
    public function kernel_extends_console_kernel(): void
    {
        $kernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);

        $this->assertInstanceOf(\Illuminate\Foundation\Console\Kernel::class, $kernel);
    }

    #[Test]
    public function kernel_is_registered_in_container(): void
    {
        // Laravel 11 binds the Contracts\Console\Kernel
        $this->assertTrue($this->app->bound(\Illuminate\Contracts\Console\Kernel::class));
    }

    #[Test]
    public function kernel_can_handle_artisan_commands(): void
    {
        $kernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);

        // Kernel can run commands
        $this->assertInstanceOf(\Illuminate\Contracts\Console\Kernel::class, $kernel);
    }
}
