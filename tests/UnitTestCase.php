<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class UnitTestCase extends TestCase
{
    use RefreshDatabase;
}
