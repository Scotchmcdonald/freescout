<?php

declare(strict_types=1);

namespace Tests\Integration\Console;

use Tests\IntegrationTestCase;

/**
 * Smoke test: verifies FreeScout console commands are registered.
 * More granular command registration tests are in KernelAndEdgeCasesTest.
 */
class KernelTest extends IntegrationTestCase
{
    public function test_freescout_commands_are_visible_in_artisan_list(): void
    {
        $this->artisan('list')
            ->expectsOutputToContain('freescout')
            ->run();
    }
}
