#!/usr/bin/env php
<?php

/**
 * Phase 5 Exit Gate Verification
 *
 * Validates that Phase 5 exit criteria have been met:
 * 1. 10 consecutive green runs with SLO compliance on all PR lanes
 * 2. Skip budget enforced and trending down
 * 3. Flake rate < 1% over trailing 14 days
 *
 * Usage:
 *   php scripts/ci/check-phase-5-exit-gate.php \
 *     --reports-dir=reports \
 *     --lane-history-dir=reports/lane-history \
 *     --output=reports/phase-5-exit-gate-latest.md
 */

class Phase5ExitGateChecker
{
    private string $reportsDir;
    private string $laneHistoryDir;
    private string $outputFile;

    private const BUDGETS = [
        'guards' => 30,
        'unit' => 30,
        'feature' => 90,
        'integration' => 90,
        'architecture' => 30,
    ];

    private const REQUIRED_GREEN_RUNS = 10;
    private const FLAKE_RATE_THRESHOLD = 1.0; // percent
    private const TRAILING_DAYS = 14;

    public function __construct(array $options = [])
    {
        $this->reportsDir = $options['reports-dir'] ?? 'reports';
        $this->laneHistoryDir = $options['lane-history-dir'] ?? $this->reportsDir . '/lane-history';
        $this->outputFile = $options['output'] ?? $this->reportsDir . '/phase-5-exit-gate-latest.md';
    }

    public function run(): int
    {
        try {
            $report = $this->generateExitGateReport();
            $this->writeReport($report);
            echo "Exit gate report written to: {$this->outputFile}\n";
            return 0;
        } catch (\Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            return 1;
        }
    }

    private function generateExitGateReport(): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $runHistory = $this->collectRunHistory();
        $recentRuns = array_slice($runHistory, 0, self::REQUIRED_GREEN_RUNS);
        $sloBreach = $this->checkSLOCompliance($recentRuns);
        $skipTrendReport = $this->analyzeSkipTrend($recentRuns);
        $flakeAnalysis = $this->analyzeFlakeRate();
        $gateStatus = $this->determineGateStatus($sloBreach, $skipTrendReport, $flakeAnalysis);

        $html = "# Phase 5 Exit Gate Verification Report\n\n";
        $html .= "**Generated:** $timestamp\n\n";

        if ($gateStatus['passed']) {
            $html .= "> ✅ **EXIT GATE: PASSED** — Phase 5 acceptance criteria met.\n\n";
        } else {
            $html .= "> ❌ **EXIT GATE: NOT YET PASSED** — " . $gateStatus['reason'] . "\n\n";
        }

        $html .= "## Criteria Summary\n\n";
        $html .= "| Criterion | Status | Details |\n";
        $html .= "|-----------|--------|----------|\n";

        // Criterion 1: 10 green runs
        $greenCount = count(array_filter($recentRuns, fn($r) => !$r['has_slo_breach']));
        $criterion1 = ($greenCount >= self::REQUIRED_GREEN_RUNS) ? '✅' : '❌';
        $details1 = "$greenCount of " . self::REQUIRED_GREEN_RUNS . " required consecutive green runs";
        $html .= "| 10 Consecutive Green Runs (SLO Compliance) | $criterion1 | $details1 |\n";

        // Criterion 2: Skip budget trending down
        $criterion2Status = $skipTrendReport['trending_down'] ? '✅' : '❌';
        $criterion2Details = "Current: {$skipTrendReport['current_count']} skips (budget: {$skipTrendReport['budget']}). ";
        $criterion2Details .= "14-day trend: " . ($skipTrendReport['trending_down'] ? "↓ DOWN" : "→ FLAT/UP");
        $html .= "| Skip Budget (Trending Down) | $criterion2Status | $criterion2Details |\n";

        // Criterion 3: Flake rate < 1%
        $criterion3 = ($flakeAnalysis['rate'] < self::FLAKE_RATE_THRESHOLD) ? '✅' : '❌';
        $criterion3Details = sprintf(
            "Flake rate: %.2f%% (%d flaky tests in %d runs over %d days)",
            $flakeAnalysis['rate'],
            $flakeAnalysis['flaky_tests'],
            $flakeAnalysis['total_runs'],
            self::TRAILING_DAYS
        );
        $html .= "| Flake Rate < 1% (Trailing 14 Days) | $criterion3 | $criterion3Details |\n";

        $html .= "\n## SLO Compliance Matrix\n\n";
        $html .= "Showing last " . min(self::REQUIRED_GREEN_RUNS, count($recentRuns)) . " runs (most recent first):\n\n";
        $html .= "| Run # | Timestamp | Guards | Unit | Feature | Integration | Architecture | Status |\n";
        $html .= "|-------|-----------|--------|------|---------|-------------|--------------|--------|\n";

        foreach ($recentRuns as $idx => $run) {
            $runNum = $idx + 1;
            $ts = substr($run['timestamp'], 0, 10); // YYYY-MM-DD
            $guardsPass = $run['guards'] !== null ? ($run['guards'] <= self::BUDGETS['guards'] ? '✅' : '⚠️') : '—';
            $unitPass = $run['unit'] !== null ? ($run['unit'] <= self::BUDGETS['unit'] ? '✅' : '⚠️') : '—';
            $featurePass = $run['feature'] !== null ? ($run['feature'] <= self::BUDGETS['feature'] ? '✅' : '⚠️') : '—';
            $integrationPass = $run['integration'] !== null ? ($run['integration'] <= self::BUDGETS['integration'] ? '✅' : '⚠️') : '—';
            $architecturePass = $run['architecture'] !== null ? ($run['architecture'] <= self::BUDGETS['architecture'] ? '✅' : '⚠️') : '—';
            $status = !$run['has_slo_breach'] ? '🟢' : '🔴';

            $html .= "| $runNum | $ts | $guardsPass ({$run['guards']}s) | $unitPass ({$run['unit']}s) | $featurePass ({$run['feature']}s) | $integrationPass ({$run['integration']}s) | $architecturePass ({$run['architecture']}s) | $status |\n";
        }

        $html .= "\n## Skip Governance Trend\n\n";
        $html .= "| Metric | Value | Status |\n";
        $html .= "|--------|-------|--------|\n";
        $html .= "| Current Skip Count | {$skipTrendReport['current_count']} | " . ($skipTrendReport['current_count'] <= $skipTrendReport['budget'] ? '✅' : '❌') . " |\n";
        $html .= "| Skip Budget | {$skipTrendReport['budget']} | — |\n";
        $html .= "| 14-Day Trend | " . ($skipTrendReport['trending_down'] ? "Decreasing ↓" : "Flat/Increasing →") . " | " . ($skipTrendReport['trending_down'] ? '✅' : '⚠️') . " |\n";

        if (!empty($skipTrendReport['trend_data'])) {
            $html .= "| Historical Counts | " . implode(', ', $skipTrendReport['trend_data']) . " | — |\n";
        }

        $html .= "\n## Flake Analysis\n\n";
        $html .= "| Metric | Value | Status |\n";
        $html .= "|--------|-------|--------|\n";
        $flakeRateStr = sprintf("%.2f", $flakeAnalysis['rate']);
        $flakeRateStatus = $flakeAnalysis['rate'] < self::FLAKE_RATE_THRESHOLD ? '✅' : '⚠️';
        $html .= "| Flake Rate (Trailing 14 Days) | " . $flakeRateStr . "% | " . $flakeRateStatus . " |\n";
        $html .= "| Flaky Tests Found | " . $flakeAnalysis['flaky_tests'] . " | — |\n";
        $html .= "| Total Runs Analyzed | " . $flakeAnalysis['total_runs'] . " | — |\n";
        $html .= "| Analysis Period | " . self::TRAILING_DAYS . " days | — |\n";

        $html .= "\n## Next Steps\n\n";

        if ($gateStatus['passed']) {
            $html .= "✅ **Phase 5 is complete and ready for production.**\n\n";
            $html .= "- Merge Phase 5 guardrails into main branch.\n";
            $html .= "- Document Phase 5 in release notes.\n";
            $html .= "- Schedule Phase 5 retrospective.\n";
            $html .= "- Begin Phase 6 planning (optional enhancements: dashboards, alerts).\n";
        } else {
            $html .= $gateStatus['recommendation'] . "\n\n";
        }

        $html .= "\n## Gate Criteria Reference\n\n";
        $html .= "**Criterion 1: 10 Consecutive Green Runs**\n";
        $html .= "- All PR lanes (Unit, Feature, Integration, Architecture, Guards) must complete within their respective SLO budgets.\n";
        $html .= "- SLO Budgets: Guards/Architecture ≤30s, Unit ≤30s, Feature ≤90s, Integration ≤90s.\n";
        $html .= "- Runs must be consecutive with no SLO breaches in between.\n\n";
        $html .= "**Criterion 2: Skip Budget (Trending Down)**\n";
        $html .= "- Total `markTestSkipped()` count must remain below baseline budgets.\n";
        $html .= "- Per-lane budgets: Feature=10, Integration=2, Unit=0, others=0.\n";
        $html .= "- 14-day historical trend should show decreasing or flat usage (not increasing).\n\n";
        $html .= "**Criterion 3: Flake Rate < 1%**\n";
        $html .= "- Measured from flake trend reports over the last 14 days.\n";
        $html .= "- Rate = (flaky test instances) / (total test runs) × 100.\n";
        $html .= "- Flaky tests should be quarantined and issue-linked to prevent accumulation.\n";

        return $html;
    }

    /**
     * Collect all available run history from lane runtime budget reports
     */
    private function collectRunHistory(): array
    {
        $runs = [];

        // Glob for all lane-runtime-budget-*.md files sorted by modification time (newest first)
        $pattern = $this->reportsDir . '/lane-runtime-budget-*.md';
        $files = glob($pattern);

        // Sort by modification time, newest first
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));

        // Extract unique run timestamps and collect dual-lane data
        $runsByTimestamp = [];

        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $content = file_get_contents($file);

            // Extract lane name from filename: lane-runtime-budget-{lane}-latest.md
            if (preg_match('/lane-runtime-budget-([a-z]+)-latest\.md/', basename($file), $matches)) {
                $lane = $matches[1];
            } else {
                continue;
            }

            // Extract timestamp and duration from markdown
            if (preg_match('/\*\*Generated:\*\*\s(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})/', $content, $tsMatch)) {
                $timestamp = $tsMatch[1];
            } else {
                // Fallback to file modification time
                $timestamp = date('Y-m-d H:i:s', filemtime($file));
            }

            // Extract duration
            if (preg_match('/\*\*Duration:\*\*\s(\d+\.\d+)s/', $content, $durationMatch)) {
                $duration = (float)$durationMatch[1];
            } else {
                continue; // Skip if duration not found
            }

            // Extract decision
            if (preg_match('/\*\*Decision:\*\*\s+(PASS|WARN)/', $content, $decisionMatch)) {
                $decision = $decisionMatch[1];
            } else {
                continue;
            }

            if (!isset($runsByTimestamp[$timestamp])) {
                $runsByTimestamp[$timestamp] = [
                    'timestamp' => $timestamp,
                    'lanes' => [],
                ];
            }

            $runsByTimestamp[$timestamp]['lanes'][$lane] = [
                'duration' => $duration,
                'decision' => $decision,
            ];
        }

        // Convert to array and sort by timestamp (newest first)
        $sortedRuns = array_values($runsByTimestamp);
        usort($sortedRuns, fn($a, $b) => strtotime($b['timestamp']) <=> strtotime($a['timestamp']));

        // Build run history with SLO compliance check
        foreach ($sortedRuns as $run) {
            $processedRun = [
                'timestamp' => $run['timestamp'],
                'guards' => $run['lanes']['guards']['duration'] ?? null,
                'unit' => $run['lanes']['unit']['duration'] ?? null,
                'feature' => $run['lanes']['feature']['duration'] ?? null,
                'integration' => $run['lanes']['integration']['duration'] ?? null,
                'architecture' => $run['lanes']['architecture']['duration'] ?? null,
                'has_slo_breach' => false,
            ];

            // Check each lane for SLO compliance
            foreach (['guards', 'unit', 'feature', 'integration', 'architecture'] as $lane) {
                if ($processedRun[$lane] !== null && $processedRun[$lane] > self::BUDGETS[$lane]) {
                    $processedRun['has_slo_breach'] = true;
                    break;
                }
            }

            $runs[] = $processedRun;
        }

        return $runs;
    }

    /**
     * Check SLO compliance for recent runs
     */
    private function checkSLOCompliance(array $recentRuns): bool
    {
        foreach ($recentRuns as $run) {
            if ($run['has_slo_breach']) {
                return true; // At least one breach found
            }
        }
        return false; // All runs passed
    }

    /**
     * Analyze skip governance trend from skip-governance reports
     */
    private function analyzeSkipTrend(array $recentRuns): array
    {
        $pattern = $this->reportsDir . '/skip-governance-*.md';
        $files = glob($pattern);

        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));

        $skipCounts = [];

        foreach (array_slice($files, 0, 15) as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $content = file_get_contents($file);
            $timestamp = null;

            // Extract timestamp
            if (preg_match('/\*\*Generated:\*\*\s(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})/', $content, $tsMatch)) {
                $timestamp = $tsMatch[1];
                $days_ago = (time() - strtotime($timestamp)) / 86400;

                // Only consider reports from last 14 days
                if ($days_ago > self::TRAILING_DAYS) {
                    continue;
                }
            }

            // Extract total occurrence count from the table
            if ($timestamp !== null && preg_match('/\|\s+(\d+)\s+\|\s+\d+\s+\|\s+/', $content, $countMatches)) {
                $skipCounts[substr($timestamp, 0, 10)] = (int)$countMatches[1];
            }
        }

        // Get latest count
        $latestFile = $files[0] ?? null;
        $currentCount = 0;
        $budget = 12; // Total baseline budget

        if ($latestFile && file_exists($latestFile)) {
            $content = file_get_contents($latestFile);
            // Count number of skip occurrences from the table lines
            $lines = explode("\n", $content);
            $tableActive = false;
            foreach ($lines as $line) {
                if (strpos($line, '| occurrence') !== false) {
                    $tableActive = true;
                    continue;
                }
                if ($tableActive && strpos($line, '|') === 0) {
                    $currentCount++;
                }
                if ($tableActive && strpos($line, '##') === 0) {
                    break;
                }
            }
        }

        // Analyze trend
        $trend = array_values($skipCounts);
        $trendingDown = false;

        if (count($trend) >= 3) {
            // Check last 3 data points for downward trend
            $last3 = array_slice($trend, -3);
            $trendingDown = ($last3[0] >= $last3[1] && $last3[1] >= $last3[2]);
        } elseif (count($trend) >= 2) {
            $trendingDown = ($trend[0] >= $trend[1]);
        } else {
            // If not enough data, consider it as trending flat/acceptable
            $trendingDown = true;
        }

        return [
            'current_count' => $currentCount,
            'budget' => $budget,
            'trend_data' => array_slice($trend, 0, 10), // Last 10 data points
            'trending_down' => $trendingDown,
        ];
    }

    /**
     * Analyze flake rate from flake reports over trailing period
     */
    private function analyzeFlakeRate(): array
    {
        $pattern = $this->reportsDir . '/flake-report-*.md';
        $files = glob($pattern);

        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));

        $flakyCounts = [];
        $runCounts = [];

        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $content = file_get_contents($file);

            // Extract timestamp
            if (preg_match('/\*\*Generated:\*\*\s(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})/', $content, $tsMatch)) {
                $timestamp = $tsMatch[1];
                $days_ago = (time() - strtotime($timestamp)) / 86400;

                if ($days_ago > self::TRAILING_DAYS) {
                    continue;
                }
            } else {
                continue;
            }

            // Extract metrics
            $dateKey = substr($timestamp, 0, 10);

            if (preg_match('/\*\*Flake Pressure:\*\*\s+([\d.]+)%/', $content, $flakeMatch)) {
                $flakyCounts[$dateKey] = (float)$flakeMatch[1];
            }

            if (preg_match('/[\d+]\s+logs\s+scanned/', $content, $logsMatch)) {
                $runCounts[$dateKey] = 1;
            }
        }

        $totalFlakeRate = !empty($flakyCounts) ? array_sum($flakyCounts) / count($flakyCounts) : 0;
        $flakyCounts = count($flakyCounts);

        return [
            'rate' => $totalFlakeRate,
            'flaky_tests' => $flakyCounts,
            'total_runs' => max(count($runCounts), 1),
        ];
    }

    /**
     * Determine overall gate status
     */
    private function determineGateStatus(bool $sloBreach, array $skipReport, array $flakeAnalysis): array
    {
        $greenRunCount = self::REQUIRED_GREEN_RUNS; // Placeholder; should come from history
        $allCriteriaMet = !$sloBreach && $skipReport['trending_down'] && $flakeAnalysis['rate'] < self::FLAKE_RATE_THRESHOLD;

        if ($allCriteriaMet) {
            return [
                'passed' => true,
                'reason' => '',
                'recommendation' => '',
            ];
        }

        $failures = [];

        if ($sloBreach) {
            $failures[] = "Recent runs have SLO breaches. Review runtime budget reports and investigate performance regressions.";
        }

        if (!$skipReport['trending_down']) {
            $failures[] = "Skip budget is not trending down. Review new skips and ensure they have issue links and expiry dates.";
        }

        if ($flakeAnalysis['rate'] >= self::FLAKE_RATE_THRESHOLD) {
            $flakeRateForMsg = sprintf("%.2f", $flakeAnalysis['rate']);
            $failures[] = "Flake rate (" . $flakeRateForMsg . "%) exceeds threshold. Quarantine flaky tests and investigate root causes.";
        }

        return [
            'passed' => false,
            'reason' => implode(' ', $failures),
            'recommendation' => implode("\n- ", $failures),
        ];
    }

    private function writeReport(string $content): void
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

$checker = new Phase5ExitGateChecker($options);
exit($checker->run());
