<?php

declare(strict_types=1);

namespace Tests\Attributes;

use Attribute;

/**
 * Marks a test class or method as being flaky in CI/batch runs.
 * 
 * Tests marked with this attribute are known to produce inconsistent
 * results. The test analyzer will track these and suggest appropriate
 * isolation strategies based on failure patterns.
 * 
 * Use this for tests that:
 * - Pass locally but fail in CI
 * - Have intermittent failures
 * - Are timing-dependent
 * - Have known issues that need investigation
 * 
 * Example:
 * ```php
 * #[Flaky('Timing-dependent test, fails ~10% of runs')]
 * public function test_rate_limiting(): void
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class Flaky
{
    public function __construct(
        public readonly string $reason = '',
        public readonly float $failureRate = 0.0
    ) {}
}
