<?php

declare(strict_types=1);

namespace Tests\Attributes;

use Attribute;

/**
 * Marks a test class or method as requiring complete isolation.
 *
 * Tests marked with this attribute will be run completely alone,
 * not batched with any other tests. This is the strictest isolation level.
 *
 * Use this for tests that:
 * - Are known to hang when batched with others
 * - Cause flaky behavior in subsequent tests
 * - Modify global PHP state (extensions, ini settings)
 * - Spawn external processes that may conflict
 * - Have timing-sensitive assertions
 * - Perform database migrations or schema changes
 *
 * Example:
 * ```php
 * #[NonBatched('Spawns concurrent processes that conflict')]
 * class ConcurrencyTest extends TestCase
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class NonBatched
{
    public function __construct(
        public readonly string $reason = ''
    ) {}
}
