<?php

namespace Tests;

abstract class FeatureTestCase extends TestCase
{
    // RefreshDatabase is inherited from the base TestCase.

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
