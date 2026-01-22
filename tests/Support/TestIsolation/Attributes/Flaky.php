<?php

declare(strict_types=1);

namespace Tests\Support\TestIsolation\Attributes;

use Attribute;

/**
 * Mark a test as flaky - it sometimes fails intermittently.
 * 
 * Flaky tests are tracked and after enough failures may be automatically
 * promoted to NonParallel or NonBatched status.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Flaky
{
    public function __construct(
        public readonly string $reason = '',
        public readonly int $retries = 2
    ) {}
}
