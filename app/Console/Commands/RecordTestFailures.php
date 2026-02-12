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
        /** @var array<string, mixed> $roster */
        $roster = [];
        if (file_exists($rosterFile)) {
            $contents = file_get_contents($rosterFile);
            $roster = $contents !== false ? (array) (json_decode($contents, true) ?? []) : [];
        }
        
        if (!isset($roster['failure_history'])) {
            $roster['failure_history'] = [];
        }
        /** @var array<string, array{count: int, last_failure: ?string, failures: list<array{date: string, type: string}>}> $failureHistory */
        $failureHistory = &$roster['failure_history'];
        
        // Parse failure and timeout logs
        $failedTests = $this->parseFailedTests($reportDir);
        $timedOutTests = $this->parseTimedOutTests($reportDir);
        
        $this->info(sprintf('Found %d failures and %d timeouts', count($failedTests), count($timedOutTests)));
        
        // Record failures
        $timestamp = date('Y-m-d H:i:s');
        foreach (array_merge($failedTests, $timedOutTests) as $test) {
            $relative = str_replace($baseDir . '/', '', $test);
            
            if (!isset($failureHistory[$relative])) {
                $failureHistory[$relative] = [
                    'count' => 0,
                    'last_failure' => null,
                    'failures' => [],
                ];
            }
            
            $failureHistory[$relative]['count']++;
            $failureHistory[$relative]['last_failure'] = $timestamp;
            $failureHistory[$relative]['failures'][] = [
                'date' => $timestamp,
                'type' => in_array($test, $timedOutTests) ? 'timeout' : 'failure',
            ];
            
            // Keep only last 10 failures
            if (count($failureHistory[$relative]['failures']) > 10) {
                $failureHistory[$relative]['failures'] = array_slice(
                    $failureHistory[$relative]['failures'],
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

    /**
     * @return array<int, string>
     */
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
            if ($content === false) {
                continue;
            }
            
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

    /**
     * @return array<int, string>
     */
    private function parseTimedOutTests(string $reportDir): array
    {
        $tests = [];
        $timeoutLog = $reportDir . '/timeout.log';
        
        if (!file_exists($timeoutLog)) {
            return $tests;
        }
        
        $content = file_get_contents($timeoutLog);
        if ($content === false) {
            return $tests;
        }
        
        if (preg_match_all('/([\/\w]+Test\.php)/', $content, $matches)) {
            foreach ($matches[1] as $match) {
                if (file_exists($match)) {
                    $tests[] = $match;
                }
            }
        }
        
        return array_unique($tests);
    }

    /**
     * @param array<string, mixed> $roster
     */
    private function promoteFlaky(array &$roster, int $threshold, string $baseDir): int
    {
        $promoted = 0;
        
        /** @var array<string, array{count: int, last_failure: ?string, failures: list<array{date: string, type: string}>}> $failureHistory */
        $failureHistory = $roster['failure_history'] ?? [];
        /** @var array<string, list<string>> $manualOverrides */
        $manualOverrides = &$roster['manual_overrides'];

        foreach ($failureHistory as $file => $history) {
            if ($history['count'] < $threshold) {
                continue;
            }
            
            // Check if already in non_batched
            /** @var list<string> $nonBatchedList */
            $nonBatchedList = $manualOverrides['non_batched'] ?? [];
            $inNonBatched = in_array($file, $nonBatchedList);
            if ($inNonBatched) {
                continue;
            }
            
            // Check current category
            /** @var list<string> $nonParallelList */
            $nonParallelList = $manualOverrides['non_parallel'] ?? [];
            $inNonParallel = in_array($file, $nonParallelList);
            
            // Check if mostly timeouts - promote to non_batched
            $timeouts = array_filter($history['failures'], fn($f) => $f['type'] === 'timeout');
            $isTimeout = count($timeouts) > count($history['failures']) / 2;
            
            if ($isTimeout || $history['count'] >= $threshold * 2) {
                // Promote to non_batched
                if (!isset($manualOverrides['non_batched'])) {
                    $manualOverrides['non_batched'] = [];
                }
                $manualOverrides['non_batched'][] = $file;
                
                // Remove from non_parallel if present
                if ($inNonParallel) {
                    $manualOverrides['non_parallel'] = array_filter(
                        $nonParallelList,
                        fn($f) => $f !== $file
                    );
                }
                
                $this->line("  → <fg=red>{$file}</> promoted to non_batched ({$history['count']} failures)");
                $promoted++;
            } elseif (!$inNonParallel) {
                // Promote to non_parallel
                if (!isset($manualOverrides['non_parallel'])) {
                    $manualOverrides['non_parallel'] = [];
                }
                $manualOverrides['non_parallel'][] = $file;
                
                $this->line("  → <fg=yellow>{$file}</> promoted to non_parallel ({$history['count']} failures)");
                $promoted++;
            }
        }
        
        return $promoted;
    }

    /**
     * @param array<string, mixed> $roster
     */
    private function displayFlakyTests(array $roster): void
    {
        /** @var array<string, array{count: int, last_failure: ?string, failures: list<array{date: string, type: string}>}> $failureHistory */
        $failureHistory = $roster['failure_history'] ?? [];
        $flaky = array_filter(
            $failureHistory,
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
                $history['last_failure'] ?? 'unknown'
            ));
        }
    }
}
