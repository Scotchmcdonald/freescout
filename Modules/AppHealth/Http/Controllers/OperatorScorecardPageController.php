<?php

declare(strict_types=1);

namespace Modules\AppHealth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Modules\AppHealth\Contracts\TriggerEvaluatorContract;
use Modules\AppHealth\Models\ScalingScorecardSnapshot;
use Modules\AppHealth\Services\TrendDeltaService;

class OperatorScorecardPageController extends Controller
{
    public function __construct(
        private readonly TriggerEvaluatorContract $evaluator,
        private readonly TrendDeltaService $trendService
    ) {}

    public function __invoke(): View
    {
        $latest = $this->latestSnapshot();
        $scorecard = $latest?->payload;

        if (! is_array($scorecard)) {
            $scorecard = $this->evaluator->evaluate();
        }

        $checks = is_array($scorecard['checks'] ?? null) ? $scorecard['checks'] : [];
        $trend = $this->trendService->weeklyDelta((int) ($scorecard['breach_count'] ?? 0));

        return view('apphealth::scorecard.index', [
            'scorecard' => $scorecard,
            'snapshot' => $latest,
            'checks' => $checks,
            'trend' => $trend,
            'observabilityLinks' => $this->observabilityLinks(),
        ]);
    }

    /**
     * @return array<int, array{label: string, href: string, external: bool}>
     */
    private function observabilityLinks(): array
    {
        $links = [];

        if (Route::has('admin.resilience.index')) {
            $links[] = [
                'label' => 'Resilience Dashboard',
                'href' => route('admin.resilience.index'),
                'external' => false,
            ];
        }

        $grafanaUrl = trim((string) config('apphealth.observability.grafana_url', ''));

        if ($grafanaUrl !== '') {
            $links[] = [
                'label' => 'Grafana Dashboards',
                'href' => $grafanaUrl,
                'external' => true,
            ];
        }

        $prometheusUrl = trim((string) config('apphealth.observability.prometheus_url', ''));

        if ($prometheusUrl !== '') {
            $links[] = [
                'label' => 'Prometheus',
                'href' => $prometheusUrl,
                'external' => true,
            ];
        }

        return $links;
    }

    private function latestSnapshot(): ?ScalingScorecardSnapshot
    {
        try {
            if (! Schema::hasTable('app_health_scaling_scorecard_snapshots')) {
                return null;
            }

            return ScalingScorecardSnapshot::query()->latest('snapshot_date')->first();
        } catch (\Throwable) {
            return null;
        }
    }
}
