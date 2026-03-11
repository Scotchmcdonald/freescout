<?php

namespace Tests;

use Illuminate\Support\Facades\DB;

abstract class FeatureTestCase extends TestCase
{
    // RefreshDatabase is inherited from the base TestCase.

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
