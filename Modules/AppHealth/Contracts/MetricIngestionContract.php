<?php

declare(strict_types=1);

namespace Modules\AppHealth\Contracts;

interface MetricIngestionContract
{
    /**
     * Resolve runtime metric inputs for Stage A trigger evaluation.
     *
     * Returns a map of input keys to their float values. Missing keys fall back
     * to configured env values in TriggerEvaluationService.
     *
     * @return array<string, float>
     */
    public function fetchInputs(): array;
}
