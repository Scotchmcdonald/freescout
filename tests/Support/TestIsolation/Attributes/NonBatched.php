<?php

declare(strict_types=1);

namespace Tests\Support\TestIsolation\Attributes;

use Attribute;

/**
 * Mark a test class or method as non-batchable.
 *
 * Tests with this attribute must be run completely in isolation - one at a time,
 * in a separate PHPUnit process. Use this for tests that:
 * - Hang when run with other tests
 * - Have severe state pollution issues
 * - Modify global PHP settings
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class NonBatched
{
    public function __construct(
        public readonly string $reason = ''
    ) {}
}
