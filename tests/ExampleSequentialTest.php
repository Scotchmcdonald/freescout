<?php

declare(strict_types=1);

namespace Tests;

use Tests\TestCase;

/**
 * Example test demonstrating the @sequential annotation.
 * 
 * Tests marked with @sequential will be run sequentially after
 * all parallel tests have completed. This is useful for tests
 * that have race conditions or require exclusive access to
 * shared resources.
 * 
 * @sequential
 */
class ExampleSequentialTest extends TestCase
{
    public function test_example_sequential_operation(): void
    {
        // This test will run sequentially, not in parallel
        $this->assertTrue(true);
    }

    public function test_another_sequential_operation(): void
    {
        // All tests in this class will run sequentially
        $this->assertTrue(true);
    }
}
