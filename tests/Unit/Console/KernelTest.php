<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Tests\UnitTestCase;

/**
 * Smoke test: verifies FreeScout console commands are registered.
 * More granular command registration tests are in KernelAndEdgeCasesTest.
 */
class KernelTest extends UnitTestCase
{
    public function test_freescout_commands_are_visible_in_artisan_list(): void
    {
        $this->artisan('list')
            ->expectsOutputToContain('freescout')
            ->run();
    }
}
