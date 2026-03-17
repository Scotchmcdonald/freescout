<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use Illuminate\Contracts\Console\Kernel as KernelContract;
use Tests\UnitTestCase;

/** @group console */
class KernelAndEdgeCasesTest extends UnitTestCase
{
    public function test_expected_freescout_commands_are_registered(): void
    {
        $kernel = $this->app->make(KernelContract::class);
        $commands = array_keys($kernel->all());

        $this->assertContains('freescout:module-build', $commands);
        $this->assertContains('freescout:module-install', $commands);
        $this->assertContains('freescout:module-update', $commands);
        $this->assertContains('freescout:update', $commands);
        $this->assertContains('freescout:after-app-update', $commands);
    }
}
