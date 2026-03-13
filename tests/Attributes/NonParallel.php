<?php

declare(strict_types=1);

namespace Tests\Attributes;

use Attribute;

/**
 * Marks a test class or method as not safe for parallel execution.
 *
 * Tests marked with this attribute will be run in a sequential batch,
 * but not in parallel with other tests. They may still be batched
 * together with other non-parallel tests.
 *
 * Use this for tests that:
 * - Share database state that could conflict
 * - Use global/static state
 * - Rely on specific timing or ordering
 * - Use RunInSeparateProcess internally
 * - Access shared filesystem resources
 *
 * Example:
 * ```php
 * #[NonParallel('Uses shared database counter')]
 * class CounterTest extends TestCase
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class NonParallel
{
    public function __construct(
        public readonly string $reason = ''
    ) {}
}
