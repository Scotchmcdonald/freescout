<?php

declare(strict_types=1);

namespace Modules\AppHealth\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\AppHealth\Contracts\TriggerEvaluatorContract;

class EvaluateScalingTriggersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(TriggerEvaluatorContract $evaluator): void
    {
        if (! config('apphealth.trigger_evaluation_enabled', true)) {
            return;
        }

        $evaluator->persistDailyScorecard();
    }
}
