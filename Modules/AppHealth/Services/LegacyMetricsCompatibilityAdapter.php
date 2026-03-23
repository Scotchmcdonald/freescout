<?php

declare(strict_types=1);

namespace Modules\AppHealth\Services;

use App\Services\MetricsService;

class LegacyMetricsCompatibilityAdapter
{
    public function __construct(private readonly MetricsService $metricsService) {}

    /**
     * @param  array<string, scalar>  $labels
     */
    public function recordCounter(string $name, int|float $value, array $labels = []): void
    {
        $this->metricsService->trackEvent('apphealth.counter.'.$name, [
            'value' => $value,
            'labels' => $labels,
        ]);
    }

    /**
     * @param  array<string, scalar>  $labels
     */
    public function recordObservation(string $name, float $value, array $labels = []): void
    {
        $this->metricsService->trackEvent('apphealth.observe.'.$name, [
            'value' => $value,
            'labels' => $labels,
        ]);
    }
}
