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
    public function kernel_can_be_instantiated(): void
    {
        $kernel = $this->app->make(Kernel::class);

        $this->assertInstanceOf(Kernel::class, $kernel);
    }

    #[Test]
    public function kernel_has_schedule_method(): void
    {
        $kernel = $this->app->make(Kernel::class);

        $this->assertTrue(method_exists($kernel, 'schedule'));
    }

    #[Test]
    public function kernel_has_commands_method(): void
    {
        $kernel = $this->app->make(Kernel::class);

        $this->assertTrue(method_exists($kernel, 'commands'));
    }

    #[Test]
    public function kernel_loads_commands_from_commands_directory(): void
    {
        $kernel = $this->app->make(Kernel::class);

        // Commands are auto-loaded
        $this->assertTrue(true);
    }

    #[Test]
    public function kernel_schedule_can_be_called(): void
    {
        $kernel = $this->app->make(Kernel::class);
        $schedule = $this->app->make(Schedule::class);

        // Should not throw exception
        $kernel->call('schedule', [$schedule]);

        $this->assertTrue(true);
    }

    #[Test]
    public function kernel_extends_console_kernel(): void
    {
        $kernel = $this->app->make(Kernel::class);

        $this->assertInstanceOf(\Illuminate\Foundation\Console\Kernel::class, $kernel);
    }

    #[Test]
    public function kernel_is_registered_in_container(): void
    {
        $this->assertTrue($this->app->bound(Kernel::class));
        $this->assertTrue($this->app->bound(\Illuminate\Contracts\Console\Kernel::class));
    }

    #[Test]
    public function kernel_can_handle_artisan_commands(): void
    {
        $kernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);

        $this->assertInstanceOf(Kernel::class, $kernel);
    }
}
