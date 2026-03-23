<?php

declare(strict_types=1);

namespace Modules\AppHealth\Contracts;

interface MetricRecorderContract
{
    /**
     * @param  array<string, scalar>  $labels
     */
    public function increment(string $name, int|float $value = 1, array $labels = []): void;

    /**
     * @param  array<string, scalar>  $labels
     */
    public function observe(string $name, float $value, array $labels = []): void;

    /**
     * @param  array<string, scalar>  $labels
     */
    public function timing(string $name, float $milliseconds, array $labels = []): void;

    /**
     * Record a value into histogram buckets for accurate Prometheus quantile calculation.
     *
     * @param  array<string, scalar>  $labels
     */
    public function recordHistogram(string $name, float $value, array $labels = []): void;

    public function renderPrometheus(): string;
}
