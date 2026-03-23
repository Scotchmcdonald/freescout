<?php

declare(strict_types=1);

namespace Modules\AppHealth\Contracts;

use Modules\AppHealth\Models\ScalingScorecardSnapshot;

interface TriggerEvaluatorContract
{
    /**
     * @param  array<string, float|int>|null  $input
     * @return array<string, mixed>
     */
    public function evaluate(?array $input = null): array;

    /**
     * @param  array<string, float|int>|null  $input
     */
    public function persistDailyScorecard(?array $input = null): ScalingScorecardSnapshot;
}
