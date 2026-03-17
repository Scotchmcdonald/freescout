<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class UnitTestCase extends TestCase
{
    use RefreshDatabase;

    // TODO (WS-C): RefreshDatabase is temporarily declared here for backward compatibility
    // while DB-heavy unit tests are migrated to the Integration layer.
    // Remove this trait once all factory()->create() usages in tests/Unit/ are eliminated
    // or converted to factory()->make() / mocked equivalents.
    // Tracking: docs/development/WIP/TESTING_SUITE_REFACTOR_PLAN_2026-03-17.md WS-C
}
