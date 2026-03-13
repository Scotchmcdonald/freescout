<?php

declare(strict_types=1);

namespace Tests\Support\TestIsolation;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * Discovers problematic tests by actually running them with timeouts.
 *
 * This analyzer:
 * 1. Runs each test file individually with a timeout
 * 2. Detects tests that hang (timeout)
 * 3. Detects tests that fail when run in parallel (using ParaTest)
 * 4. Detects flaky tests by running multiple times
 * 5. Generates a roster for the test runner
 */
class HangDetector
{
    private string $baseDir;
    private SymfonyStyle $io;
    private array $results = [
        'hangs' => [],        // Tests that timeout
        'parallel_fails' => [], // Tests that fail only in parallel
        'flaky' => [],        // Tests that fail intermittently
        'normal' => [],       // Tests that are fine
    ];

    private int $hangTimeout = 30;     // Seconds before considering a test hung
    private int $flakyRuns = 3;        // Number of runs to detect flakiness
    private int $parallelProcesses = 4; // Processes for parallel detection
    private int $batchSize = 1;        // Tests per batch for detection (1 = individual, higher = faster but needs binary search)

    public function __construct(string $baseDir)
    {
        $this->baseDir = $baseDir;
        $output = new ConsoleOutput;
        $this->io = new SymfonyStyle(new ArrayInput([]), $output);
    }

    public function setHangTimeout(int $seconds): self
    {
        $this->hangTimeout = $seconds;

        return $this;
    }

    public function setFlakyRuns(int $runs): self
    {
        $this->flakyRuns = $runs;

        return $this;
    }

    public function setParallelProcesses(int $processes): self
    {
        $this->parallelProcesses = $processes;

        return $this;
    }

    public function setBatchSize(int $size): self
    {
        $this->batchSize = max(1, $size);

        return $this;
    }

    /**
     * Run full detection suite
     */
    public function detectAll(?array $testFiles = null, bool $quick = false): array
    {
        if ($testFiles === null) {
            $testFiles = $this->discoverTestFiles();
        }

        $this->io->title('Test Problem Detection');
        $this->io->text(sprintf('Analyzing %d test files...', count($testFiles)));

        // Phase 1: Detect hangs by running each test with timeout
        $this->io->section('Phase 1: Detecting Hanging Tests');
        $this->detectHangs($testFiles);

        // Remove known hangs from further analysis
        $nonHangingFiles = array_diff($testFiles, $this->results['hangs']);

        if (! $quick) {
            // Phase 2: Detect parallel failures
            $this->io->section('Phase 2: Detecting Parallel Conflicts');
            $this->detectParallelFailures($nonHangingFiles);

            // Remove parallel failures from flaky detection
            $normalFiles = array_diff($nonHangingFiles, array_keys($this->results['parallel_fails']));

            // Phase 3: Detect flaky tests
            $this->io->section('Phase 3: Detecting Flaky Tests');
            $this->detectFlaky($normalFiles);
        } else {
            $this->results['normal'] = $nonHangingFiles;
        }

        return $this->results;
    }

    /**
     * Detect tests that hang (timeout) when run individually or in batches
     * Uses binary search when batches timeout to find specific hanging tests
     */
    public function detectHangs(array $testFiles): array
    {
        $hangs = [];

        // Track statistics
        $stats = ['passed' => 0, 'failed' => 0, 'errors' => 0, 'skipped' => 0, 'hangs' => 0];

        if ($this->batchSize > 1) {
            // Batched detection with binary search
            $this->io->text(sprintf('Using batch size of %d with binary search on failures', $this->batchSize));
            $batches = array_chunk($testFiles, $this->batchSize);
            $progress = $this->io->createProgressBar(count($batches));
            $progress->setFormat(' %current%/%max% batches [%bar%] %percent:3s%% | %message%');
            $progress->setMessage('Starting...');
            $progress->start();

            foreach ($batches as $batchIndex => $batch) {
                $batchHangs = $this->detectHangsInBatch($batch, $stats);
                $hangs = array_merge($hangs, $batchHangs);
                $stats['hangs'] = count($hangs);

                // Update progress with stats
                $progress->setMessage($this->formatStats($stats));
                $progress->advance();
            }

            $progress->finish();
            $this->io->newLine(2);
        } else {
            // Individual detection (original behavior)
            $progress = $this->io->createProgressBar(count($testFiles));
            $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
            $progress->setMessage($this->formatStats($stats));
            $progress->start();

            foreach ($testFiles as $file) {
                $shortName = $this->getShortPath($file);

                $testStats = $this->runSingleTestWithStats($file);
                $stats['passed'] += $testStats['passed'];
                $stats['failed'] += $testStats['failed'];
                $stats['errors'] += $testStats['errors'];
                $stats['skipped'] += $testStats['skipped'];

                if ($testStats['hung']) {
                    $hangs[] = $file;
                    $stats['hangs']++;
                    $this->io->newLine();
                    $this->io->warning("HANG DETECTED: $shortName (timeout after {$this->hangTimeout}s)");
                }

                $progress->setMessage($this->formatStats($stats));
                $progress->advance();
            }

            $progress->finish();
            $this->io->newLine(2);
        }

        $this->results['hangs'] = $hangs;

        // Show final stats summary
        $this->displayStatsSummary($stats);

        if (count($hangs) > 0) {
            $this->io->warning(sprintf('Found %d hanging tests', count($hangs)));
        } else {
            $this->io->success('No hanging tests detected');
        }

        return $hangs;
    }

    /**
     * Display a formatted statistics summary table
     */
    private function displayStatsSummary(array $stats): void
    {
        $total = $stats['passed'] + $stats['failed'] + $stats['errors'] + $stats['skipped'];

        $this->io->definitionList(
            ['Total Tests' => $total],
            ['Passed' => "<fg=green>{$stats['passed']}</>"],
            ['Failed' => ($stats['failed'] > 0 ? "<fg=red>{$stats['failed']}</>" : $stats['failed'])],
            ['Errors' => ($stats['errors'] > 0 ? "<fg=red>{$stats['errors']}</>" : $stats['errors'])],
            ['Skipped' => ($stats['skipped'] > 0 ? "<fg=yellow>{$stats['skipped']}</>" : $stats['skipped'])],
            ['Hangs' => ($stats['hangs'] > 0 ? "<fg=magenta>{$stats['hangs']}</>" : $stats['hangs'])]
        );
    }

    /**
     * Format stats for progress bar display
     */
    private function formatStats(array $stats): string
    {
        $parts = [];

        if ($stats['passed'] > 0) {
            $parts[] = "P:{$stats['passed']}";
        }
        if ($stats['failed'] > 0) {
            $parts[] = "F:{$stats['failed']}";
        }
        if ($stats['errors'] > 0) {
            $parts[] = "E:{$stats['errors']}";
        }
        if ($stats['skipped'] > 0) {
            $parts[] = "S:{$stats['skipped']}";
        }
        if ($stats['hangs'] > 0) {
            $parts[] = "H:{$stats['hangs']}";
        }

        return empty($parts) ? 'Running...' : implode(' ', $parts);
    }

    /**
     * Run a single test file and return statistics
     */
    private function runSingleTestWithStats(string $file): array
    {
        $stats = ['passed' => 0, 'failed' => 0, 'errors' => 0, 'skipped' => 0, 'hung' => false];

        $process = new Process([
            'php', 'vendor/bin/phpunit',
            '--no-coverage',
            '--colors=never',
            $file,
        ], $this->baseDir);

        $process->setTimeout($this->hangTimeout);

        try {
            $process->run();
            $output = $process->getOutput();

            // Parse PHPUnit output for stats
            if (preg_match('/OK \((\d+) tests?/', $output, $m)) {
                $stats['passed'] = (int) $m[1];
            } elseif (preg_match('/Tests:\s*(\d+)/', $output, $m)) {
                $total = (int) $m[1];

                if (preg_match('/Failures:\s*(\d+)/', $output, $fm)) {
                    $stats['failed'] = (int) $fm[1];
                }
                if (preg_match('/Errors:\s*(\d+)/', $output, $em)) {
                    $stats['errors'] = (int) $em[1];
                }
                if (preg_match('/Skipped:\s*(\d+)/', $output, $sm)) {
                    $stats['skipped'] = (int) $sm[1];
                }

                $stats['passed'] = $total - $stats['failed'] - $stats['errors'] - $stats['skipped'];
            }
        } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException $e) {
            $stats['hung'] = true;
        }

        return $stats;
    }

    /**
     * Test if a single file hangs
     */
    private function testFileHangs(string $file): bool
    {
        $process = new Process([
            'php', 'vendor/bin/phpunit',
            '--no-coverage',
            '--colors=never',
            $file,
        ], $this->baseDir);

        $process->setTimeout($this->hangTimeout);

        try {
            $process->run();

            return false;
        } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException $e) {
            return true;
        }
    }

    /**
     * Detect hangs in a batch using binary search if the batch times out
     *
     * @param  array  $files  Test files to check
     * @param  array  &$stats  Reference to stats array for tracking pass/fail/etc.
     */
    private function detectHangsInBatch(array $files, array &$stats = []): array
    {
        if (count($files) === 0) {
            return [];
        }

        if (count($files) === 1) {
            // Single file - test with stats
            $testStats = $this->runSingleTestWithStats($files[0]);
            $stats['passed'] += $testStats['passed'];
            $stats['failed'] += $testStats['failed'];
            $stats['errors'] += $testStats['errors'];
            $stats['skipped'] += $testStats['skipped'];

            if ($testStats['hung']) {
                $this->io->newLine();
                $this->io->warning('HANG DETECTED: '.$this->getShortPath($files[0]));

                return $files;
            }

            return [];
        }

        // Test the whole batch
        // Scale timeout based on batch size (each test gets base timeout)
        $batchTimeout = $this->hangTimeout * count($files);

        $process = new Process(
            array_merge(
                ['php', 'vendor/bin/phpunit', '--no-coverage', '--colors=never'],
                $files
            ),
            $this->baseDir
        );

        $process->setTimeout($batchTimeout);

        try {
            $process->run();
            $output = $process->getOutput();

            // Parse stats from batch run
            if (preg_match('/OK \((\d+) tests?/', $output, $m)) {
                $stats['passed'] += (int) $m[1];
            } elseif (preg_match('/Tests:\s*(\d+)/', $output, $m)) {
                $total = (int) $m[1];
                $failed = 0;
                $errors = 0;
                $skipped = 0;

                if (preg_match('/Failures:\s*(\d+)/', $output, $fm)) {
                    $failed = (int) $fm[1];
                    $stats['failed'] += $failed;
                }
                if (preg_match('/Errors:\s*(\d+)/', $output, $em)) {
                    $errors = (int) $em[1];
                    $stats['errors'] += $errors;
                }
                if (preg_match('/Skipped:\s*(\d+)/', $output, $sm)) {
                    $skipped = (int) $sm[1];
                    $stats['skipped'] += $skipped;
                }

                $stats['passed'] += $total - $failed - $errors - $skipped;
            }

            // Batch passed - no hangs
            return [];
        } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException $e) {
            // Batch timed out - use binary search to find which test(s)
            $this->io->newLine();
            $this->io->text(sprintf('  → Batch of %d timed out, binary searching...', count($files)));

            $mid = (int) ceil(count($files) / 2);
            $firstHalf = array_slice($files, 0, $mid);
            $secondHalf = array_slice($files, $mid);

            $hangs = array_merge(
                $this->detectHangsInBatch($firstHalf, $stats),
                $this->detectHangsInBatch($secondHalf, $stats)
            );

            return $hangs;
        }
    }

    /**
     * Detect tests that pass individually but fail when run in parallel
     */
    public function detectParallelFailures(array $testFiles): array
    {
        $parallelFails = [];

        // First, get baseline by running tests individually
        $this->io->text('Getting baseline results (individual runs)...');
        $individualResults = $this->runTestsIndividually($testFiles);

        // Now run with ParaTest
        $this->io->text('Running tests in parallel...');
        $parallelResults = $this->runTestsInParallel($testFiles);

        // Compare results
        foreach ($parallelResults as $file => $parallelPassed) {
            $individualPassed = $individualResults[$file] ?? false;

            if ($individualPassed && ! $parallelPassed) {
                $parallelFails[$file] = 'Passes individually, fails in parallel';
                $this->io->warning('PARALLEL CONFLICT: '.$this->getShortPath($file));
            }
        }

        $this->results['parallel_fails'] = $parallelFails;

        if (count($parallelFails) > 0) {
            $this->io->warning(sprintf('Found %d tests with parallel conflicts', count($parallelFails)));
        } else {
            $this->io->success('No parallel conflicts detected');
        }

        return $parallelFails;
    }

    /**
     * Detect flaky tests by running multiple times
     */
    public function detectFlaky(array $testFiles): array
    {
        $flaky = [];

        $this->io->text(sprintf('Running tests %d times each to detect flakiness...', $this->flakyRuns));
        $progress = $this->io->createProgressBar(count($testFiles));
        $progress->start();

        foreach ($testFiles as $file) {
            $results = [];

            for ($i = 0; $i < $this->flakyRuns; $i++) {
                $process = new Process([
                    'php', 'vendor/bin/phpunit',
                    '--no-coverage',
                    '--colors=never',
                    $file,
                ], $this->baseDir);

                $process->setTimeout($this->hangTimeout);

                try {
                    $process->run();
                    $results[] = $process->isSuccessful();
                } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException $e) {
                    // Already detected as hanging, skip
                    break;
                }
            }

            // If we have mixed results (some pass, some fail), it's flaky
            if (count(array_unique($results)) > 1) {
                $passCount = count(array_filter($results));
                $flaky[$file] = sprintf(
                    'Passed %d/%d runs',
                    $passCount,
                    count($results)
                );
                $this->io->newLine();
                $this->io->warning('FLAKY: '.$this->getShortPath($file)." ({$flaky[$file]})");
            }

            $progress->advance();
        }

        $progress->finish();
        $this->io->newLine(2);

        $this->results['flaky'] = $flaky;

        // Everything else is normal
        $problematic = array_merge(
            $this->results['hangs'],
            array_keys($this->results['parallel_fails']),
            array_keys($this->results['flaky'])
        );
        $this->results['normal'] = array_values(array_diff($testFiles, $problematic));

        if (count($flaky) > 0) {
            $this->io->warning(sprintf('Found %d flaky tests', count($flaky)));
        } else {
            $this->io->success('No flaky tests detected');
        }

        return $flaky;
    }

    /**
     * Run tests individually and return pass/fail status for each
     */
    private function runTestsIndividually(array $testFiles): array
    {
        $results = [];
        $progress = $this->io->createProgressBar(count($testFiles));
        $progress->start();

        foreach ($testFiles as $file) {
            $process = new Process([
                'php', 'vendor/bin/phpunit',
                '--no-coverage',
                '--colors=never',
                $file,
            ], $this->baseDir);

            $process->setTimeout($this->hangTimeout);

            try {
                $process->run();
                $results[$file] = $process->isSuccessful();
            } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException $e) {
                $results[$file] = false;
            }

            $progress->advance();
        }

        $progress->finish();
        $this->io->newLine();

        return $results;
    }

    /**
     * Run tests in parallel using ParaTest and return pass/fail status
     */
    private function runTestsInParallel(array $testFiles): array
    {
        $results = [];

        // Create a temporary test suite file
        $suiteFile = $this->baseDir.'/tests/.parallel_detection_suite.xml';
        $this->createTestSuiteXml($testFiles, $suiteFile);

        // Run with ParaTest
        $process = new Process([
            'php', 'vendor/bin/paratest',
            '-p', (string) $this->parallelProcesses,
            '--configuration', $suiteFile,
            '--log-junit', $this->baseDir.'/tests/.parallel_results.xml',
            '--no-coverage',
        ], $this->baseDir);

        $process->setTimeout($this->hangTimeout * count($testFiles) / $this->parallelProcesses);

        try {
            $process->run();
        } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException $e) {
            $this->io->warning('Parallel run timed out');
        }

        // Parse JUnit results
        $results = $this->parseJUnitResults(
            $this->baseDir.'/tests/.parallel_results.xml',
            $testFiles
        );

        // Cleanup
        @unlink($suiteFile);
        @unlink($this->baseDir.'/tests/.parallel_results.xml');

        return $results;
    }

    /**
     * Create a PHPUnit XML configuration for specific test files
     */
    private function createTestSuiteXml(array $testFiles, string $outputPath): void
    {
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $phpunit = $xml->createElement('phpunit');
        $phpunit->setAttribute('bootstrap', 'bootstrap.php');
        $xml->appendChild($phpunit);

        $testsuites = $xml->createElement('testsuites');
        $phpunit->appendChild($testsuites);

        $suite = $xml->createElement('testsuite');
        $suite->setAttribute('name', 'Parallel Detection');
        $testsuites->appendChild($suite);

        foreach ($testFiles as $file) {
            $fileElement = $xml->createElement('file', $file);
            $suite->appendChild($fileElement);
        }

        $xml->save($outputPath);
    }

    /**
     * Parse JUnit XML results to get pass/fail status per file
     */
    private function parseJUnitResults(string $xmlPath, array $testFiles): array
    {
        $results = array_fill_keys($testFiles, true); // Assume pass if not in results

        if (! file_exists($xmlPath)) {
            return $results;
        }

        try {
            $xml = simplexml_load_file($xmlPath);

            foreach ($xml->testsuite as $suite) {
                $file = (string) $suite['file'];

                if (empty($file)) {
                    // Check nested testsuites
                    foreach ($suite->testsuite as $nestedSuite) {
                        $file = (string) $nestedSuite['file'];
                        if (! empty($file) && isset($results[$file])) {
                            $failures = (int) $nestedSuite['failures'];
                            $errors = (int) $nestedSuite['errors'];
                            $results[$file] = ($failures === 0 && $errors === 0);
                        }
                    }
                } elseif (isset($results[$file])) {
                    $failures = (int) $suite['failures'];
                    $errors = (int) $suite['errors'];
                    $results[$file] = ($failures === 0 && $errors === 0);
                }
            }
        } catch (\Exception $e) {
            $this->io->warning('Failed to parse JUnit results: '.$e->getMessage());
        }

        return $results;
    }

    /**
     * Discover all test files in the project
     */
    private function discoverTestFiles(): array
    {
        $testDirs = [$this->baseDir.'/tests'];

        foreach (glob($this->baseDir.'/Modules/*/Tests') as $moduleTestDir) {
            $testDirs[] = $moduleTestDir;
        }

        $finder = new Finder;
        $finder->files()
            ->in($testDirs)
            ->name('*Test.php')
            ->notPath('Browser')
            ->sortByName();

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }

        return $files;
    }

    /**
     * Get short path relative to base directory
     */
    private function getShortPath(string $path): string
    {
        return str_replace($this->baseDir.'/', '', $path);
    }

    /**
     * Generate a roster file from results
     */
    public function generateRoster(string $outputPath): void
    {
        $roster = [
            'generated_at' => date('Y-m-d H:i:s'),
            'detection_settings' => [
                'hang_timeout' => $this->hangTimeout,
                'flaky_runs' => $this->flakyRuns,
                'parallel_processes' => $this->parallelProcesses,
            ],
            'summary' => [
                'total_tests' => count($this->results['normal']) +
                                count($this->results['hangs']) +
                                count($this->results['parallel_fails']) +
                                count($this->results['flaky']),
                'normal' => count($this->results['normal']),
                'hangs' => count($this->results['hangs']),
                'parallel_fails' => count($this->results['parallel_fails']),
                'flaky' => count($this->results['flaky']),
            ],
            'categories' => [
                'parallel_safe' => array_map([$this, 'getShortPath'], $this->results['normal']),
                'non_parallel' => array_map(
                    fn ($file) => [
                        'file' => $this->getShortPath($file),
                        'reason' => $this->results['parallel_fails'][$file] ?? 'Parallel conflict detected',
                    ],
                    array_keys($this->results['parallel_fails'])
                ),
                'non_batched' => array_map(
                    fn ($file) => [
                        'file' => $this->getShortPath($file),
                        'reason' => 'Hangs when run with other tests',
                    ],
                    $this->results['hangs']
                ),
                'flaky' => array_map(
                    fn ($file) => [
                        'file' => $this->getShortPath($file),
                        'reason' => $this->results['flaky'][$file],
                    ],
                    array_keys($this->results['flaky'])
                ),
            ],
        ];

        file_put_contents($outputPath, json_encode($roster, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->io->success("Roster saved to: $outputPath");
    }
}
