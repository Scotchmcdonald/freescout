<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Records test failures and promotes flaky tests to higher isolation levels.
 * 
 * This command should be run after test execution to record failures
 * and automatically adjust test categorization based on failure patterns.
 */
class RecordTestFailures extends Command
{
    protected $signature = 'tests:record-failures 
        {report-dir : Path to the test reports directory}
        {--promote : Automatically promote flaky tests to higher isolation}
        {--threshold=3 : Number of failures before promotion}';

    protected $description = 'Record test failures and optionally promote flaky tests';

    public function handle(): int
    {
        $reportDir = $this->argument('report-dir');
        $baseDir = base_path();
        $rosterFile = $baseDir . '/tests/test_roster.json';
        
        if (!is_dir($reportDir)) {
            $this->error("Report directory not found: {$reportDir}");
            return Command::FAILURE;
        }
        
        // Load roster
        $roster = [];
        if (file_exists($rosterFile)) {
            $roster = json_decode(file_get_contents($rosterFile), true) ?? [];
        }
        
        if (!isset($roster['failure_history'])) {
            $roster['failure_history'] = [];
        }
        
        // Parse failure and timeout logs
        $failedTests = $this->parseFailedTests($reportDir);
        $timedOutTests = $this->parseTimedOutTests($reportDir);
        
        $this->info(sprintf('Found %d failures and %d timeouts', count($failedTests), count($timedOutTests)));
        
        // Record failures
        $timestamp = date('Y-m-d H:i:s');
        foreach (array_merge($failedTests, $timedOutTests) as $test) {
            $relative = str_replace($baseDir . '/', '', $test);
            
            if (!isset($roster['failure_history'][$relative])) {
                $roster['failure_history'][$relative] = [
                    'count' => 0,
                    'last_failure' => null,
                    'failures' => [],
                ];
            }
            
            $roster['failure_history'][$relative]['count']++;
            $roster['failure_history'][$relative]['last_failure'] = $timestamp;
            $roster['failure_history'][$relative]['failures'][] = [
                'date' => $timestamp,
                'type' => in_array($test, $timedOutTests) ? 'timeout' : 'failure',
            ];
            
            // Keep only last 10 failures
            if (count($roster['failure_history'][$relative]['failures']) > 10) {
                $roster['failure_history'][$relative]['failures'] = array_slice(
                    $roster['failure_history'][$relative]['failures'],
                    -10
                );
            }
        }
        
        // Auto-promote flaky tests
        if ($this->option('promote')) {
            $threshold = (int) $this->option('threshold');
            $promoted = $this->promoteFlaky($roster, $threshold, $baseDir);
            
            if ($promoted > 0) {
                $this->info("Promoted {$promoted} flaky tests to higher isolation");
            }
        }
        
        // Save updated roster
        file_put_contents($rosterFile, json_encode($roster, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        $this->info('Failure history updated in test_roster.json');
        
        // Display flaky tests
        $this->displayFlakyTests($roster);
        
        return Command::SUCCESS;
    }

    private function parseFailedTests(string $reportDir): array
    {
        $tests = [];
        $failureLog = $reportDir . '/failure.log';
        $errorLog = $reportDir . '/error.log';
        
        foreach ([$failureLog, $errorLog] as $log) {
            if (!file_exists($log)) {
                continue;
            }
            
            $content = file_get_contents($log);
            
            // Extract test file paths from log
            if (preg_match_all('/([\/\w]+Test\.php)/', $content, $matches)) {
                foreach ($matches[1] as $match) {
                    if (file_exists($match)) {
                        $tests[] = $match;
                    }
                }
            }
        }
        
        return array_unique($tests);
    }

    private function parseTimedOutTests(string $reportDir): array
    {
        $tests = [];
        $timeoutLog = $reportDir . '/timeout.log';
        
        if (!file_exists($timeoutLog)) {
            return $tests;
        }
        
        $content = file_get_contents($timeoutLog);
        
        if (preg_match_all('/([\/\w]+Test\.php)/', $content, $matches)) {
            foreach ($matches[1] as $match) {
                if (file_exists($match)) {
                    $tests[] = $match;
                }
            }
        }
        
        return array_unique($tests);
    }

    private function promoteFlaky(array &$roster, int $threshold, string $baseDir): int
    {
        $promoted = 0;
        
        foreach ($roster['failure_history'] as $file => $history) {
            if ($history['count'] < $threshold) {
                continue;
            }
            
            // Check if already in non_batched
            $inNonBatched = in_array($file, $roster['manual_overrides']['non_batched'] ?? []);
            if ($inNonBatched) {
                continue;
            }
            
            // Check current category
            $inNonParallel = in_array($file, $roster['manual_overrides']['non_parallel'] ?? []);
            
            // Check if mostly timeouts - promote to non_batched
            $timeouts = array_filter($history['failures'], fn($f) => $f['type'] === 'timeout');
            $isTimeout = count($timeouts) > count($history['failures']) / 2;
            
            if ($isTimeout || $history['count'] >= $threshold * 2) {
                // Promote to non_batched
                if (!isset($roster['manual_overrides']['non_batched'])) {
                    $roster['manual_overrides']['non_batched'] = [];
                }
                $roster['manual_overrides']['non_batched'][] = $file;
                
                // Remove from non_parallel if present
                if ($inNonParallel) {
                    $roster['manual_overrides']['non_parallel'] = array_filter(
                        $roster['manual_overrides']['non_parallel'],
                        fn($f) => $f !== $file
                    );
                }
                
                $this->line("  → <fg=red>{$file}</> promoted to non_batched ({$history['count']} failures)");
                $promoted++;
            } elseif (!$inNonParallel) {
                // Promote to non_parallel
                if (!isset($roster['manual_overrides']['non_parallel'])) {
                    $roster['manual_overrides']['non_parallel'] = [];
                }
                $roster['manual_overrides']['non_parallel'][] = $file;
                
                $this->line("  → <fg=yellow>{$file}</> promoted to non_parallel ({$history['count']} failures)");
                $promoted++;
            }
        }
        
        return $promoted;
    }

    private function displayFlakyTests(array $roster): void
    {
        $flaky = array_filter(
            $roster['failure_history'] ?? [],
            fn($h) => $h['count'] >= 2
        );
        
        if (empty($flaky)) {
            return;
        }
        
        $this->newLine();
        $this->components->warn('Flaky Tests (2+ failures):');
        
        uasort($flaky, fn($a, $b) => $b['count'] <=> $a['count']);
        
        foreach (array_slice($flaky, 0, 10, true) as $file => $history) {
            $this->line(sprintf(
                "  • %s: <fg=red>%d failures</> (last: %s)",
                $file,
                $history['count'],
                $history['last_failure']
            ));
        }
    }
}
