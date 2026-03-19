<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class UnitTestCase extends TestCase
{
    use RefreshDatabase;

    // NOTE: RefreshDatabase is retained here because root Unit tests for models
    // and jobs still call factory()->create() and require DB isolation.
    // observers/listeners have been migrated to Integration in Phase 2.
    // tests/Unit/Controllers/ has been migrated to tests/Integration/Controllers/ (WS-C).
    // The remaining DB-touching Unit tests are documented temporary exceptions
    // per the WS-C exit criteria; a broader model/observer/listener migration is tracked separately.
}
