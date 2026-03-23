<?php

declare(strict_types=1);

namespace Modules\AppHealth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\AppHealth\Contracts\TriggerEvaluatorContract;
use Modules\AppHealth\Models\ScalingScorecardSnapshot;

class ScalingScorecardController extends Controller
{
    public function __construct(private readonly TriggerEvaluatorContract $evaluator) {}

    public function __invoke(): JsonResponse
    {
        $latest = ScalingScorecardSnapshot::query()->latest('snapshot_date')->first();

        if (! $latest) {
            return response()->json([
                'source' => 'live-evaluation',
                'scorecard' => $this->evaluator->evaluate(),
            ]);
        }

        return response()->json([
            'source' => 'snapshot',
            'snapshot_date' => $latest->snapshot_date?->toDateString(),
            'scorecard' => $latest->payload,
        ]);
    }
}
