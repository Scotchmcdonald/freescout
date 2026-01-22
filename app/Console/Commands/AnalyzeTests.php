<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tests\Support\TestAnalyzer;

/**
 * Discovers and categorizes tests for optimal batch execution.
 * 
 * This command analyzes all test files and generates a test roster
 * that categorizes tests into three groups:
 * - parallel_safe: Can run in parallel with other tests
 * - non_parallel: Must run sequentially but can be batched
 * - non_batched: Must run completely alone
 */
class AnalyzeTests extends Command
{
    protected $signature = 'tests:analyze 
        {--update : Update the test roster configuration}
        {--report : Generate a detailed markdown report}
        {--json : Output raw JSON analysis}
        {--dry-run : Show what would be detected without updating config}';

    protected $description = 'Analyze tests to discover isolation requirements for batch execution';

    public function handle(): int
    {
        $baseDir = base_path();
        $analyzer = new TestAnalyzer($baseDir);
        
        $this->info('Analyzing test files...');
        $this->newLine();
        
        $analysis = $analyzer->analyze();
        
        // Display summary
        $this->displaySummary($analysis, $baseDir);
        
        if ($this->option('json')) {
            $this->outputJson($analysis, $baseDir);
            return Command::SUCCESS;
        }
        
        if ($this->option('report')) {
            $this->generateReport($analysis, $analyzer, $baseDir);
        }
        
        if ($this->option('update') && !$this->option('dry-run')) {
            $this->updateRoster($analysis, $baseDir);
        }
        
        return Command::SUCCESS;
    }

    private function displaySummary(array $analysis, string $baseDir): void
    {
        $this->components->twoColumnDetail(
            '<fg=gray>Total test files</>',
            $analysis['metadata']['total_files']
        );
        
        $this->components->twoColumnDetail(
            '<fg=green>Parallel-safe</>',
            count($analysis[TestAnalyzer::CATEGORY_PARALLEL_SAFE])
        );
        
        $this->components->twoColumnDetail(
            '<fg=yellow>Non-parallel</>',
            count($analysis[TestAnalyzer::CATEGORY_NON_PARALLEL])
        );
        
        $this->components->twoColumnDetail(
            '<fg=red>Non-batched</>',
            count($analysis[TestAnalyzer::CATEGORY_NON_BATCHED])
        );
        
        $this->newLine();
        
        // Show top detection reasons
        if (!empty($analysis['metadata']['detection_reasons'])) {
            $this->components->info('Top detection reasons:');
            arsort($analysis['metadata']['detection_reasons']);
            $topReasons = array_slice($analysis['metadata']['detection_reasons'], 0, 10, true);
            foreach ($topReasons as $reason => $count) {
                $this->line("  • {$reason}: <fg=cyan>{$count}</>");
            }
            $this->newLine();
        }
        
        // Show non-batched tests (most critical)
        if (!empty($analysis[TestAnalyzer::CATEGORY_NON_BATCHED])) {
            $this->components->warn('Non-batched tests (run alone):');
            foreach ($analysis[TestAnalyzer::CATEGORY_NON_BATCHED] as $test) {
                $relative = str_replace($baseDir . '/', '', $test['file']);
                $reason = $test['reasons'][0] ?? 'Unknown';
                $this->line("  <fg=red>•</> {$relative}");
                $this->line("    <fg=gray>{$reason}</>");
            }
            $this->newLine();
        }
        
        // Show non-parallel tests
        if (!empty($analysis[TestAnalyzer::CATEGORY_NON_PARALLEL]) && $this->output->isVerbose()) {
            $this->components->warn('Non-parallel tests (run sequentially):');
            foreach ($analysis[TestAnalyzer::CATEGORY_NON_PARALLEL] as $test) {
                $relative = str_replace($baseDir . '/', '', $test['file']);
                $reason = $test['reasons'][0] ?? 'Unknown';
                $this->line("  <fg=yellow>•</> {$relative}");
                $this->line("    <fg=gray>{$reason}</>");
            }
            $this->newLine();
        }
    }

    private function outputJson(array $analysis, string $baseDir): void
    {
        // Convert to relative paths for JSON output
        $output = [
            'metadata' => $analysis['metadata'],
            'categories' => [
                'parallel_safe' => [],
                'non_parallel' => [],
                'non_batched' => [],
            ],
        ];
        
        foreach ([TestAnalyzer::CATEGORY_PARALLEL_SAFE, TestAnalyzer::CATEGORY_NON_PARALLEL, TestAnalyzer::CATEGORY_NON_BATCHED] as $category) {
            foreach ($analysis[$category] as $test) {
                $output['categories'][$category][] = [
                    'file' => str_replace($baseDir . '/', '', $test['file']),
                    'reasons' => $test['reasons'],
                    'confidence' => $test['confidence'],
                ];
            }
        }
        
        $this->line(json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function generateReport(array $analysis, TestAnalyzer $analyzer, string $baseDir): void
    {
        $report = $analyzer->generateReport($analysis);
        $reportPath = $baseDir . '/tests/ISOLATION_REPORT.md';
        
        file_put_contents($reportPath, $report);
        
        $this->components->info("Report saved to: tests/ISOLATION_REPORT.md");
    }

    private function updateRoster(array $analysis, string $baseDir): void
    {
        $rosterPath = $baseDir . '/tests/test_roster.json';
        
        // Load existing roster for manual overrides
        $existingRoster = [];
        if (file_exists($rosterPath)) {
            $existingRoster = json_decode(file_get_contents($rosterPath), true) ?? [];
        }
        
        // Build new roster
        $roster = [
            'version' => '1.0',
            'generated_at' => date('Y-m-d H:i:s'),
            'auto_detected' => [
                'parallel_safe' => [],
                'non_parallel' => [],
                'non_batched' => [],
            ],
            'manual_overrides' => $existingRoster['manual_overrides'] ?? [
                'parallel_safe' => [],
                'non_parallel' => [],
                'non_batched' => [],
            ],
            'failure_history' => $existingRoster['failure_history'] ?? [],
        ];
        
        // Populate auto-detected lists with relative paths
        foreach ($analysis[TestAnalyzer::CATEGORY_PARALLEL_SAFE] as $test) {
            $relative = str_replace($baseDir . '/', '', $test['file']);
            $roster['auto_detected']['parallel_safe'][] = $relative;
        }
        
        foreach ($analysis[TestAnalyzer::CATEGORY_NON_PARALLEL] as $test) {
            $relative = str_replace($baseDir . '/', '', $test['file']);
            $roster['auto_detected']['non_parallel'][] = [
                'file' => $relative,
                'reasons' => $test['reasons'],
            ];
        }
        
        foreach ($analysis[TestAnalyzer::CATEGORY_NON_BATCHED] as $test) {
            $relative = str_replace($baseDir . '/', '', $test['file']);
            $roster['auto_detected']['non_batched'][] = [
                'file' => $relative,
                'reasons' => $test['reasons'],
            ];
        }
        
        // Save roster
        file_put_contents($rosterPath, json_encode($roster, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        $this->components->info("Test roster updated: tests/test_roster.json");
        $this->line("  • Parallel-safe: " . count($roster['auto_detected']['parallel_safe']));
        $this->line("  • Non-parallel: " . count($roster['auto_detected']['non_parallel']));
        $this->line("  • Non-batched: " . count($roster['auto_detected']['non_batched']));
    }
}
