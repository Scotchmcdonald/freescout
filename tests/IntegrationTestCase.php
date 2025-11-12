<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

abstract class IntegrationTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure clean transaction state
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
    }

    protected function tearDown(): void
    {
        // Force rollback of ALL pending transactions
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        
        parent::tearDown();
    }
}
