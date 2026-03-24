<?php

declare(strict_types=1);

/**
 * Phase 5 runtime budget guard.
 *
 * Usage:
 * php scripts/ci/check-test-lane-runtime-budgets.php --lane=unit --duration=31
 */

final class TestLaneRuntimeBudgetGuard
{
    /** @var array<string, int> */
    private const BUDGET_SECONDS = [
        'guards' => 30,
        'unit' => 30,
        'feature' => 90,
        'integration' => 90,
        'architecture' => 30,
    ];

    public function run(): int
    {
        $options = getopt('', [
            'lane:',
            'duration:',
            'history::',
            'reports-dir::',
            'window::',
            'severe-factor::',
        ]);

        $lane = (string) ($options['lane'] ?? '');
        $duration = isset($options['duration']) ? (float) $options['duration'] : -1.0;

        if ($lane === '' || ! array_key_exists($lane, self::BUDGET_SECONDS)) {
            fwrite(STDERR, "Invalid or missing --lane. Allowed: ".implode(', ', array_keys(self::BUDGET_SECONDS)).PHP_EOL);

            return 2;
        }

        if ($duration < 0) {
            fwrite(STDERR, "Invalid or missing --duration (seconds).".PHP_EOL);

            return 2;
        }

        $reportsDir = $this->normalizePath((string) ($options['reports-dir'] ?? 'reports'));
        $historyPath = $this->normalizePath((string) ($options['history'] ?? ($reportsDir.'/lane-runtime-history.jsonl')));
        $window = max(3, (int) ($options['window'] ?? 5));
        $severeFactor = max(1.0, (float) ($options['severe-factor'] ?? 1.5));

        if (! is_dir(dirname($historyPath))) {
            mkdir(dirname($historyPath), 0777, true);
        }

        $this->appendHistoryRecord($historyPath, [
            'timestamp' => date('c'),
            'lane' => $lane,
            'duration_seconds' => round($duration, 2),
            'budget_seconds' => self::BUDGET_SECONDS[$lane],
        ]);

        $laneDurations = $this->readLaneDurations($historyPath, $lane);
        $recent = array_slice($laneDurations, -$window);
        $budget = (float) self::BUDGET_SECONDS[$lane];
        $median = $recent === [] ? 0.0 : $this->quantile($recent, 0.50);
        $p95 = $recent === [] ? 0.0 : $this->quantile($recent, 0.95);

        $severeSpike = $duration > ($budget * $severeFactor);
        $sustainedRegression = count($recent) === $window && $median > $budget;
        $softBreach = $duration > $budget && ! $severeSpike;

        $reportPath = $reportsDir.'/lane-runtime-budget-'.$lane.'-latest.md';
        file_put_contents($reportPath, $this->buildReport(
            lane: $lane,
            budget: $budget,
            duration: $duration,
            sampleCount: count($laneDurations),
            windowCount: count($recent),
            windowSize: $window,
            median: $median,
            p95: $p95,
            severeSpike: $severeSpike,
            sustainedRegression: $sustainedRegression,
            softBreach: $softBreach,
            severeFactor: $severeFactor,
            historyPath: $historyPath
        ));

        echo "Lane: {$lane}".PHP_EOL;
        echo "Duration: ".number_format($duration, 2)."s (budget ".number_format($budget, 2)."s)".PHP_EOL;
        echo "Window median: ".number_format($median, 2)."s, p95: ".number_format($p95, 2)."s (window ".count($recent).")".PHP_EOL;
        echo "Report: {$reportPath}".PHP_EOL;

        if ($severeSpike) {
            fwrite(STDERR, "FAIL: severe lane runtime spike detected.".PHP_EOL);

            return 1;
        }

        if ($sustainedRegression) {
            fwrite(STDERR, "FAIL: sustained runtime regression detected from rolling median.".PHP_EOL);

            return 1;
        }

        if ($softBreach) {
            echo "WARN: current run exceeded budget but did not hit fail thresholds.".PHP_EOL;
        }

        return 0;
    }

    /**
     * @param array{timestamp:string, lane:string, duration_seconds:float, budget_seconds:int} $record
     */
    private function appendHistoryRecord(string $historyPath, array $record): void
    {
        file_put_contents($historyPath, json_encode($record, JSON_THROW_ON_ERROR).PHP_EOL, FILE_APPEND);
    }

    /**
     * @return list<float>
     */
    private function readLaneDurations(string $historyPath, string $lane): array
    {
        if (! file_exists($historyPath)) {
            return [];
        }

        $durations = [];
        $lines = file($historyPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if (! is_array($decoded)) {
                continue;
            }

            if (($decoded['lane'] ?? null) !== $lane) {
                continue;
            }

            $value = (float) ($decoded['duration_seconds'] ?? 0);
            if ($value > 0) {
                $durations[] = $value;
            }
        }

        return $durations;
    }

    private function normalizePath(string $path): string
    {
        if ($path === '') {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        return getcwd().'/'.ltrim($path, '/');
    }

    /**
     * @param list<float> $values
     */
    private function quantile(array $values, float $quantile): float
    {
        sort($values);
        $count = count($values);
        if ($count === 0) {
            return 0.0;
        }

        if ($count === 1) {
            return $values[0];
        }

        $position = ($count - 1) * $quantile;
        $lower = (int) floor($position);
        $upper = (int) ceil($position);
        if ($lower === $upper) {
            return $values[$lower];
        }

        $weight = $position - $lower;

        return ($values[$lower] * (1 - $weight)) + ($values[$upper] * $weight);
    }

    private function buildReport(
        string $lane,
        float $budget,
        float $duration,
        int $sampleCount,
        int $windowCount,
        int $windowSize,
        float $median,
        float $p95,
        bool $severeSpike,
        bool $sustainedRegression,
        bool $softBreach,
        float $severeFactor,
        string $historyPath
    ): string {
        $status = 'pass';
        if ($severeSpike || $sustainedRegression) {
            $status = 'fail';
        } elseif ($softBreach) {
            $status = 'warn';
        }

        $lines = [];
        $lines[] = '# Lane Runtime Budget Report';
        $lines[] = '';
        $lines[] = 'Generated: '.date('c');
        $lines[] = 'Lane: '.$lane;
        $lines[] = 'Status: '.$status;
        $lines[] = '';
        $lines[] = '| Metric | Value |';
        $lines[] = '|---|---:|';
        $lines[] = '| Current duration (s) | '.number_format($duration, 2).' |';
        $lines[] = '| Budget (s) | '.number_format($budget, 2).' |';
        $lines[] = '| Stored samples | '.$sampleCount.' |';
        $lines[] = '| Rolling window size | '.$windowSize.' |';
        $lines[] = '| Rolling sample count | '.$windowCount.' |';
        $lines[] = '| Rolling median (s) | '.number_format($median, 2).' |';
        $lines[] = '| Rolling p95 (s) | '.number_format($p95, 2).' |';
        $lines[] = '| Severe spike threshold (x budget) | '.number_format($severeFactor, 2).' |';
        $lines[] = '';
        $lines[] = '## Decision';
        $lines[] = '';
        if ($severeSpike) {
            $lines[] = '- Fail: current duration exceeded severe threshold.';
        } elseif ($sustainedRegression) {
            $lines[] = '- Fail: rolling median exceeded budget across full window.';
        } elseif ($softBreach) {
            $lines[] = '- Warn: current run exceeded budget but is not a severe/sustained failure.';
        } else {
            $lines[] = '- Pass: no severe spike and no sustained regression.';
        }
        $lines[] = '';
        $lines[] = 'History file: '.$historyPath;

        return implode(PHP_EOL, $lines).PHP_EOL;
    }
}

$guard = new TestLaneRuntimeBudgetGuard;
exit($guard->run());
