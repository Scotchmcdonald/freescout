<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Force rollback of ALL pending transactions
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        
        parent::tearDown();
    }
}
