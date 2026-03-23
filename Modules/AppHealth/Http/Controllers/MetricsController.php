<?php

declare(strict_types=1);

namespace Modules\AppHealth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Modules\AppHealth\Contracts\MetricRecorderContract;

class MetricsController extends Controller
{
    public function __construct(private readonly MetricRecorderContract $metrics) {}

    public function __invoke(): Response
    {
        if (! config('apphealth.metrics_enabled', true)) {
            return response('Metrics disabled.', 503, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return response($this->metrics->renderPrometheus(), 200, ['Content-Type' => 'text/plain; version=0.0.4; charset=UTF-8']);
    }
}
