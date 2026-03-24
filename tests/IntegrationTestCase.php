<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class IntegrationTestCase extends TestCase
{
    use RefreshDatabase;

    // RefreshDatabase is explicitly declared here (not inherited via TestCase).
    // Integration tests are DB-backed by policy.

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
