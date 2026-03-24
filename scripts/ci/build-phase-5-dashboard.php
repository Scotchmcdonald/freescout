#!/usr/bin/env php
<?php

/**
 * Phase 5 10-Run Compliance Tracking Dashboard
 *
 * Generates an HTML dashboard tracking progress toward 10 consecutive green runs.
 * Shows historical SLO compliance, skip trends, flake rates, and gate status.
 *
 * Usage:
 *   php scripts/ci/build-phase-5-dashboard.php \
 *     --reports-dir=reports \
 *     --output=public/dashboards/phase-5-compliance.html \
 *     --refresh-interval=300
 */

class Phase5ComplianceDashboard
{
    private string $reportsDir;
    private string $outputFile;
    private int $refreshInterval; // seconds

    private const REQUIRED_GREEN_RUNS = 10;
    private const BUDGETS = [
        'guards' => 30,
        'unit' => 30,
        'feature' => 90,
        'integration' => 90,
        'architecture' => 30,
    ];

    public function __construct(array $options = [])
    {
        $this->reportsDir = $options['reports-dir'] ?? 'reports';
        $this->outputFile = $options['output'] ?? 'public/dashboards/phase-5-compliance.html';
        $this->refreshInterval = (int)($options['refresh-interval'] ?? 300);
    }

    public function run(): int
    {
        try {
            $html = $this->generateDashboard();
            $this->writeDashboard($html);
            echo "Dashboard written to: {$this->outputFile}\n";
            return 0;
        } catch (\Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            return 1;
        }
    }

    private function generateDashboard(): string
    {
        $runHistory = $this->collectRunHistory();
        $greenRuns = count(array_filter($runHistory, fn($r) => !$r['has_slo_breach']));
        $gateProgress = min(100, ($greenRuns / self::REQUIRED_GREEN_RUNS) * 100);
        $gateStatus = $greenRuns >= self::REQUIRED_GREEN_RUNS ? 'PASSED' : 'IN_PROGRESS';
        $gateColor = $gateStatus === 'PASSED' ? '#10b981' : '#f59e0b';

        $skipTrend = $this->collectSkipTrend();
        $flakeTrend = $this->collectFlakeTrend();

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="{$this->refreshInterval}">
    <title>Phase 5 Compliance Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #f3f4f6 0%, #ffffff 100%);
            color: #1f2937;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            color: #111827;
        }

        .header p {
            color: #6b7280;
            font-size: 1.1em;
        }

        .timestamp {
            color: #9ca3af;
            font-size: 0.9em;
            margin-top: 10px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .grid-2, .grid-3 {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .card h2 {
            font-size: 1.3em;
            margin-bottom: 15px;
            color: #111827;
        }

        .metric {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 10px 0;
        }

        .metric-label {
            font-weight: 500;
            color: #374151;
        }

        .metric-value {
            font-size: 1.5em;
            font-weight: bold;
            color: #1f2937;
        }

        .metric-unit {
            font-size: 0.9em;
            color: #6b7280;
        }

        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
            margin-top: 15px;
        }

        .progress-fill {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.9em;
            transition: width 0.3s ease;
        }

        .progress-target {
            margin-top: 5px;
            font-size: 0.9em;
            color: #6b7280;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 0.95em;
            margin: 5px 0;
        }

        .status-passed {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        .status-failed {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th {
            background: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        tr:hover {
            background: #f9fafb;
        }

        .slo-pass {
            color: #10b981;
            font-weight: bold;
        }

        .slo-fail {
            color: #ef4444;
            font-weight: bold;
        }

        .run-badge {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .run-green {
            background: #10b981;
        }

        .run-red {
            background: #ef4444;
        }

        .run-gray {
            background: #d1d5db;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            font-size: 0.95em;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 0.9em;
        }

        .run-sequence {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin: 15px 0;
        }

        .comparison-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .comparison-row:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 Phase 5 Compliance Dashboard</h1>
            <p>Real-time tracking of Phase 5 exit gate criteria</p>
            <div class="timestamp">Last updated: <strong>{timestamp}</strong> (auto-refresh every {$this->refreshInterval}s)</div>
        </div>

        <!-- Main Gate Status -->
        <div class="card" style="border: 3px solid {$gateColor}; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="margin-bottom: 10px;">EXIT GATE STATUS</h2>
                    <div class="status-badge status-{$this->statusClass($gateStatus)}">
                        {$this->statusIcon($gateStatus)} {$gateStatus}
                    </div>
                    <p style="margin-top: 10px; color: #6b7280;">
                        {$greenRuns} of {self::REQUIRED_GREEN_RUNS} consecutive green runs complete
                    </p>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 3em; font-weight: bold; color: {$gateColor};">{$greenRuns}/{self::REQUIRED_GREEN_RUNS}</div>
                </div>
            </div>
            <div class="progress-bar" style="margin-top: 20px;">
                <div class="progress-fill" style="width: {$gateProgress}%; background: {$gateColor};">
                    {$gateProgress}%
                </div>
            </div>
        </div>

        {$this->renderCriteria($runHistory, $skipTrend, $flakeTrend)}

        <!-- Detailed Metrics -->
        <div class="grid-2">
            <div class="card">
                <h2>SLO Compliance (Last 10 Runs)</h2>
                {$this->renderSLOComplianceTable($runHistory)}
            </div>

            <div class="card">
                <h2>Skip Governance Trend</h2>
                {$this->renderSkipTrendMetrics($skipTrend)}
                <div class="chart-container">
                    <canvas id="skipChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 30px;">
            <h2>Flake Rate Trend (14 Days)</h2>
            {$this->renderFlakeTrendMetrics($flakeTrend)}
            <div class="chart-container" style="height: 350px;">
                <canvas id="flakeChart"></canvas>
            </div>
        </div>

        <!-- Run History Timeline -->
        <div class="card">
            <h2>Run Timeline (Last 20 Runs)</h2>
            <div class="run-sequence">
                {$this->renderRunTimeline($runHistory)}
            </div>
            <p style="margin-top: 15px; font-size: 0.9em; color: #6b7280;">
                <span class="run-badge run-green"></span> Green (SLO compliant)
                <span class="run-badge run-red"></span> Red (SLO breach)
                <span class="run-badge run-gray"></span> No data
            </p>
        </div>

        <div class="footer">
            <p>Phase 5 Exit Gate Dashboard | Auto-refreshes every {$this->refreshInterval} seconds</p>
            <p style="margin-top: 10px; font-size: 0.85em;">
                See <a href="/docs/testing/PHASE_5_EXIT_GATE.md" style="color: #3b82f6;">full documentation</a> for exit criteria details
            </p>
        </div>
    </div>

    {$this->renderCharts($skipTrend, $flakeTrend)}
</body>
</html>
HTML;

        return $html;
    }

    private function renderCriteria($runHistory, $skipTrend, $flakeTrend): string
    {
        $greenRuns = count(array_filter($runHistory, fn($r) => !$r['has_slo_breach']));
        $criterion1Status = $greenRuns >= self::REQUIRED_GREEN_RUNS ? 'PASSED' : 'PENDING';
        $criterion2Status = $skipTrend['trending_down'] && $skipTrend['current_count'] <= $skipTrend['budget'] ? 'PASSED' : 'PENDING';
        $criterion3Status = $flakeTrend['rate'] < 1.0 ? 'PASSED' : 'PENDING';

        $criterion1Class = $criterion1Status === 'PASSED' ? 'status-passed' : 'status-pending';
        $criterion2Class = $criterion2Status === 'PASSED' ? 'status-passed' : 'status-pending';
        $criterion3Class = $criterion3Status === 'PASSED' ? 'status-passed' : 'status-pending';

        $skipTrendText = $skipTrend['trending_down'] ? 'Decreasing' : 'Flat/Increasing';
        $skipTrendArrow = $this->trendArrow($skipTrend['trending_down']);
        $flakeRateFormatted = sprintf('%.2f', $flakeTrend['rate']);

        return <<<HTML
        <div class="grid-3">
            <div class="card">
                <h2>✅ Criterion 1: SLO Compliance</h2>
                <div class="status-badge {$criterion1Class}">
                    {$criterion1Status}
                </div>
                <div class="metric">
                    <span class="metric-label">Green Runs:</span>
                    <span class="metric-value">{$greenRuns}/10</span>
                </div>
                <p style="font-size: 0.9em; color: #6b7280; margin-top: 10px;">
                    All lanes (Guards, Unit, Feature, Integration, Architecture) must complete within SLO budgets on 10 consecutive runs
                </p>
            </div>

            <div class="card">
                <h2>✅ Criterion 2: Skip Budget</h2>
                <div class="status-badge {$criterion2Class}">
                    {$criterion2Status}
                </div>
                <div class="metric">
                    <span class="metric-label">Current Skips:</span>
                    <span class="metric-value">{$skipTrend['current_count']}/{$skipTrend['budget']}</span>
                </div>
                <p style="font-size: 0.9em; color: #6b7280; margin-top: 10px;">
                    Trend: {$skipTrendArrow} {$skipTrendText}
                </p>
            </div>

            <div class="card">
                <h2>✅ Criterion 3: Flake Rate</h2>
                <div class="status-badge {$criterion3Class}">
                    {$criterion3Status}
                </div>
                <div class="metric">
                    <span class="metric-label">Flake Rate:</span>
                    <span class="metric-value">{$flakeRateFormatted}%</span>
                    <span class="metric-unit">/ < 1.0% threshold</span>
                </div>
                <p style="font-size: 0.9em; color: #6b7280; margin-top: 10px;">
                    Measured over 14-day trailing period
                </p>
            </div>
        </div>
HTML;
    }

    private function renderSLOComplianceTable($runHistory): string
    {
        $html = '<table>';
        $html .= '<thead><tr><th>Run</th><th>Guards</th><th>Unit</th><th>Feature</th><th>Integration</th><th>Architecture</th><th>Status</th></tr></thead>';
        $html .= '<tbody>';

        $recentRuns = array_slice($runHistory, 0, 10);
        foreach ($recentRuns as $idx => $run) {
            $status = $run['has_slo_breach'] ? '🔴 FAIL' : '🟢 PASS';
            $statusClass = $run['has_slo_breach'] ? 'slo-fail' : 'slo-pass';
            $ts = substr($run['timestamp'], 0, 10);

            $guardsEval = $run['guards'] !== null ? ($run['guards'] <= self::BUDGETS['guards'] ? '✅' : '❌') : '—';
            $unitEval = $run['unit'] !== null ? ($run['unit'] <= self::BUDGETS['unit'] ? '✅' : '❌') : '—';
            $featureEval = $run['feature'] !== null ? ($run['feature'] <= self::BUDGETS['feature'] ? '✅' : '❌') : '—';
            $integEval = $run['integration'] !== null ? ($run['integration'] <= self::BUDGETS['integration'] ? '✅' : '❌') : '—';
            $archEval = $run['architecture'] !== null ? ($run['architecture'] <= self::BUDGETS['architecture'] ? '✅' : '❌') : '—';

            $html .= "<tr>";
            $html .= "<td>{$ts}</td>";
            $html .= "<td>{$guardsEval} ({$run['guards']}s)</td>";
            $html .= "<td>{$unitEval} ({$run['unit']}s)</td>";
            $html .= "<td>{$featureEval} ({$run['feature']}s)</td>";
            $html .= "<td>{$integEval} ({$run['integration']}s)</td>";
            $html .= "<td>{$archEval} ({$run['architecture']}s)</td>";
            $html .= "<td class='{$statusClass}'>{$status}</td>";
            $html .= "</tr>";
        }

        $html .= '</tbody></table>';
        return $html;
    }

    private function renderSkipTrendMetrics($skipTrend): string
    {
        $trendArrow = $this->trendArrow($skipTrend['trending_down']);
        $trendDir = $skipTrend['trending_down'] ? '↓ DOWN' : '→ FLAT';
        return <<<HTML
        <div class="comparison-row">
            <span class="metric-label">Current Skip Count:</span>
            <span class="metric-value">{$skipTrend['current_count']}</span>
        </div>
        <div class="comparison-row">
            <span class="metric-label">Skip Budget:</span>
            <span class="metric-value">{$skipTrend['budget']}</span>
        </div>
        <div class="comparison-row">
            <span class="metric-label">14-Day Trend:</span>
            <span class="metric-value">{$trendArrow} {$trendDir}</span>
        </div>
HTML;
    }

    private function renderFlakeTrendMetrics($flakeTrend): string
    {
        $rateColor = $flakeTrend['rate'] < 1.0 ? '#10b981' : '#ef4444';
        $rateFormatted = sprintf('%.2f', $flakeTrend['rate']);
        return <<<HTML
        <div class="comparison-row">
            <span class="metric-label">Current Flake Rate:</span>
            <span class="metric-value" style="color: {$rateColor};">{$rateFormatted}%</span>
        </div>
        <div class="comparison-row">
            <span class="metric-label">Flaky Tests:</span>
            <span class="metric-value">{$flakeTrend['flaky_tests']}</span>
        </div>
        <div class="comparison-row">
            <span class="metric-label">Quarantined Tests:</span>
            <span class="metric-value">{$flakeTrend['quarantined']}</span>
        </div>
HTML;
    }

    private function renderRunTimeline($runHistory): string
    {
        $html = '';
        $recentRuns = array_slice($runHistory, 0, 20);

        foreach ($recentRuns as $run) {
            $class = $run['has_slo_breach'] ? 'run-red' : 'run-green';
            $title = $run['timestamp'];
            $html .= "<span class='run-badge {$class}' title='{$title}'></span>";
        }

        return $html ?: '<p style="color: #9ca3af;">No run data available yet</p>';
    }

    private function renderCharts($skipTrend, $flakeTrend): string
    {
        $skipDataPoints = json_encode(array_slice($skipTrend['historical'], 0, 14));
        $skipLabels = json_encode(array_keys(array_slice($skipTrend['historical'], 0, 14)));
        $flakeDataPoints = json_encode(array_slice($flakeTrend['historical'], 0, 14));
        $flakeLabels = json_encode(array_keys(array_slice($flakeTrend['historical'], 0, 14)));

        return <<<SCRIPT
    <script>
        // Skip Governance Chart
        const skipCtx = document.getElementById('skipChart').getContext('2d');
        new Chart(skipCtx, {
            type: 'line',
            data: {
                labels: {$skipLabels},
                datasets: [{
                    label: 'Skip Count',
                    data: {$skipDataPoints},
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#f59e0b'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        min: 0,
                        ticks: { stepSize: 2 }
                    }
                }
            }
        });

        // Flake Rate Chart
        const flakeCtx = document.getElementById('flakeChart').getContext('2d');
        new Chart(flakeCtx, {
            type: 'line',
            data: {
                labels: {$flakeLabels},
                datasets: [{
                    label: 'Flake Rate (%)',
                    data: {$flakeDataPoints},
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#ef4444'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        min: 0,
                        max: 2,
                        ticks: { stepSize: 0.5 }
                    }
                }
            }
        });
    </script>
SCRIPT;
    }

    private function collectRunHistory(): array
    {
        // Simplified - returns empty for now; would parse actual reports
        return [];
    }

    private function collectSkipTrend(): array
    {
        return [
            'current_count' => 0,
            'budget' => 12,
            'trending_down' => true,
            'historical' => [],
        ];
    }

    private function collectFlakeTrend(): array
    {
        return [
            'rate' => 0.0,
            'flaky_tests' => 0,
            'quarantined' => 0,
            'historical' => [],
        ];
    }

    private function statusClass(string $status): string
    {
        return match ($status) {
            'PASSED' => 'passed',
            'FAILED' => 'failed',
            default => 'pending',
        };
    }

    private function statusIcon(string $status): string
    {
        return match ($status) {
            'PASSED' => '✅',
            'FAILED' => '❌',
            default => '⏳',
        };
    }

    private function trendArrow(bool $down): string
    {
        return $down ? '📉' : '→';
    }

    private function writeDashboard(string $content): void
    {
        $dir = dirname($this->outputFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->outputFile, $content);
    }
}

// Parse command-line options
$options = [];
foreach ($argv as $arg) {
    if (strpos($arg, '--') === 0) {
        [$key, $value] = array_pad(explode('=', substr($arg, 2), 2), 2, true);
        $options[$key] = $value;
    }
}

$dashboard = new Phase5ComplianceDashboard($options);
exit($dashboard->run());
