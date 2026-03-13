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

        if ($this->option('update') && ! $this->option('dry-run')) {
            $this->updateRoster($analysis, $baseDir);
        }

        return Command::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $analysis
     */
    private function displaySummary(array $analysis, string $baseDir): void
    {
        /** @var array{total_files?: int, detection_reasons?: array<string, int>} $metadata */
        $metadata = $analysis['metadata'];
        /** @var array<int, mixed> $parallelSafe */
        $parallelSafe = $analysis[TestAnalyzer::CATEGORY_PARALLEL_SAFE];
        /** @var array<int, mixed> $nonParallel */
        $nonParallel = $analysis[TestAnalyzer::CATEGORY_NON_PARALLEL];
        /** @var array<int, mixed> $nonBatched */
        $nonBatched = $analysis[TestAnalyzer::CATEGORY_NON_BATCHED];

        $this->components->twoColumnDetail(
            '<fg=gray>Total test files</>',
            strval($metadata['total_files'] ?? 0)
        );

        $this->components->twoColumnDetail(
            '<fg=green>Parallel-safe</>',
            (string) count($parallelSafe)
        );

        $this->components->twoColumnDetail(
            '<fg=yellow>Non-parallel</>',
            (string) count($nonParallel)
        );

        $this->components->twoColumnDetail(
            '<fg=red>Non-batched</>',
            (string) count($nonBatched)
        );

        $this->newLine();

        // Show top detection reasons
        /** @var array<string, int> $detectionReasons */
        $detectionReasons = $metadata['detection_reasons'] ?? [];
        if (! empty($detectionReasons)) {
            $this->components->info('Top detection reasons:');
            arsort($detectionReasons);
            $topReasons = array_slice($detectionReasons, 0, 10, true);
            foreach ($topReasons as $reason => $count) {
                $this->line("  • {$reason}: <fg=cyan>{$count}</>");
            }
            $this->newLine();
        }

        // Show non-batched tests (most critical)
        if (! empty($nonBatched)) {
            $this->components->warn('Non-batched tests (run alone):');
            foreach ($nonBatched as $test) {
                /** @var array{file: string, reasons: list<string>} $test */
                $relative = str_replace($baseDir.'/', '', $test['file']);
                $reasons = $test['reasons'] ?? [];
                $reason = $reasons[0] ?? 'Unknown';
                $this->line("  <fg=red>•</> {$relative}");
                $this->line("    <fg=gray>{$reason}</>");
            }
            $this->newLine();
        }

        // Show non-parallel tests
        if (! empty($nonParallel) && $this->output->isVerbose()) {
            $this->components->warn('Non-parallel tests (run sequentially):');
            foreach ($nonParallel as $test) {
                /** @var array{file: string, reasons: list<string>} $test */
                $relative = str_replace($baseDir.'/', '', $test['file']);
                $reasons = $test['reasons'] ?? [];
                $reason = $reasons[0] ?? 'Unknown';
                $this->line("  <fg=yellow>•</> {$relative}");
                $this->line("    <fg=gray>{$reason}</>");
            }
            $this->newLine();
        }
    }

    /**
     * @param  array<string, mixed>  $analysis
     */
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
            /** @var list<array{file: string, reasons: list<string>, confidence: float}> $categoryTests */
            $categoryTests = $analysis[$category] ?? [];
            foreach ($categoryTests as $test) {
                $output['categories'][$category][] = [
                    'file' => str_replace($baseDir.'/', '', $test['file']),
                    'reasons' => $test['reasons'],
                    'confidence' => $test['confidence'],
                ];
            }
        }

        $this->line((string) json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param  array<string, mixed>  $analysis
     */
    private function generateReport(array $analysis, TestAnalyzer $analyzer, string $baseDir): void
    {
        $report = $analyzer->generateReport($analysis);
        $reportPath = $baseDir.'/tests/ISOLATION_REPORT.md';

        file_put_contents($reportPath, $report);

        $this->components->info('Report saved to: tests/ISOLATION_REPORT.md');
    }

    /**
     * @param  array<string, mixed>  $analysis
     */
    private function updateRoster(array $analysis, string $baseDir): void
    {
        $rosterPath = $baseDir.'/tests/test_roster.json';

        // Load existing roster for manual overrides
        /** @var array<string, mixed> $existingRoster */
        $existingRoster = [];
        if (file_exists($rosterPath)) {
            $contents = file_get_contents($rosterPath);
            $existingRoster = $contents !== false ? (array) (json_decode($contents, true) ?? []) : [];
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
        /** @var list<array{file: string}> $pSafe */
        $pSafe = $analysis[TestAnalyzer::CATEGORY_PARALLEL_SAFE] ?? [];
        foreach ($pSafe as $test) {
            $relative = str_replace($baseDir.'/', '', $test['file']);
            $roster['auto_detected']['parallel_safe'][] = $relative;
        }

        /** @var list<array{file: string, reasons: list<string>}> $nParallel */
        $nParallel = $analysis[TestAnalyzer::CATEGORY_NON_PARALLEL] ?? [];
        foreach ($nParallel as $test) {
            $relative = str_replace($baseDir.'/', '', $test['file']);
            $roster['auto_detected']['non_parallel'][] = [
                'file' => $relative,
                'reasons' => $test['reasons'],
            ];
        }

        /** @var list<array{file: string, reasons: list<string>}> $nBatched */
        $nBatched = $analysis[TestAnalyzer::CATEGORY_NON_BATCHED] ?? [];
        foreach ($nBatched as $test) {
            $relative = str_replace($baseDir.'/', '', $test['file']);
            $roster['auto_detected']['non_batched'][] = [
                'file' => $relative,
                'reasons' => $test['reasons'],
            ];
        }

        // Save roster
        file_put_contents($rosterPath, json_encode($roster, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->components->info('Test roster updated: tests/test_roster.json');
        $this->line('  • Parallel-safe: '.count($roster['auto_detected']['parallel_safe']));
        $this->line('  • Non-parallel: '.count($roster['auto_detected']['non_parallel']));
        $this->line('  • Non-batched: '.count($roster['auto_detected']['non_batched']));
    }
}
