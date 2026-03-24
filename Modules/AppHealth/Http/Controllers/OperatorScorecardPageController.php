<?php

declare(strict_types=1);

namespace Modules\AppHealth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Routing\Router;
use Illuminate\View\View;
use Modules\AppHealth\Contracts\TriggerEvaluatorContract;
use Modules\AppHealth\Models\ScalingScorecardSnapshot;
use Modules\AppHealth\Services\TrendDeltaService;

class OperatorScorecardPageController extends Controller
{
    public function __construct(
        private readonly TriggerEvaluatorContract $evaluator,
        private readonly TrendDeltaService $trendService,
        private readonly Router $router,
        private readonly SchemaBuilder $schema
    ) {}

    public function __invoke(): View
    {
        $latest = $this->latestSnapshot();
        $scorecard = $latest?->payload;

        if (! is_array($scorecard)) {
            $scorecard = $this->evaluator->evaluate();
        }

        $checks = is_array($scorecard['checks'] ?? null) ? $scorecard['checks'] : [];
        $breachCount = is_numeric($scorecard['breach_count'] ?? null) ? (int) $scorecard['breach_count'] : 0;
        $trend = $this->trendService->weeklyDelta($breachCount);

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

        if ($this->router->has('admin.resilience.index')) {
            $links[] = [
                'label' => 'Resilience Dashboard',
                'href' => route('admin.resilience.index'),
                'external' => false,
            ];
        }

        $configuredGrafanaUrl = config('apphealth.observability.grafana_url', '');
        $grafanaUrl = trim(is_string($configuredGrafanaUrl) ? $configuredGrafanaUrl : '');

        if ($grafanaUrl !== '') {
            $links[] = [
                'label' => 'Grafana Dashboards',
                'href' => $grafanaUrl,
                'external' => true,
            ];
        }

        $configuredPrometheusUrl = config('apphealth.observability.prometheus_url', '');
        $prometheusUrl = trim(is_string($configuredPrometheusUrl) ? $configuredPrometheusUrl : '');

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
            if (! $this->schema->hasTable('app_health_scaling_scorecard_snapshots')) {
                return null;
            }

            return ScalingScorecardSnapshot::query()->latest('snapshot_date')->first();
        } catch (\Throwable) {
            return null;
        }
    }
}
