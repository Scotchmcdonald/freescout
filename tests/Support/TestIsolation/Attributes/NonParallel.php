<?php

declare(strict_types=1);

namespace Tests\Support\TestIsolation\Attributes;

use Attribute;

/**
 * Mark a test class or method as non-parallel safe.
 * 
 * Tests with this attribute will be run sequentially (not in parallel with other tests)
 * but can still be batched together with other non-parallel tests.
 * 
 * Use this when tests have shared resource conflicts but don't hang.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class NonParallel
{
    public function __construct(
        public readonly string $reason = ''
    ) {}
}
