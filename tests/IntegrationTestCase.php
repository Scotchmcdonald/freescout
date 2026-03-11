<?php

namespace Tests;

use Illuminate\Support\Facades\DB;

abstract class IntegrationTestCase extends TestCase
{
    // RefreshDatabase is inherited from the base TestCase.

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
