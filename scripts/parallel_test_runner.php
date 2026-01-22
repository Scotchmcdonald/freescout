#!/usr/bin/env php
<?php

/**
 * Intelligent Test Runner with Parallel Support
 * 
 * Uses ParaTest for true parallel execution of safe tests,
 * runs problematic tests sequentially or in isolation.
 * 
 * Usage:
 *   php scripts/parallel_test_runner.php                    # Run all tests using roster
 *   php scripts/parallel_test_runner.php --detect           # Detect problematic tests (full)
 *   php scripts/parallel_test_runner.php --detect-quick     # Quick hang detection only
 *   php scripts/parallel_test_runner.php --detect-fast      # Fast batched detection (batch=20)
 *   php scripts/parallel_test_runner.php --parallel-only    # Run only parallel-safe tests
 *   php scripts/parallel_test_runner.php --sequential-only  # Run only sequential tests
 *   php scripts/parallel_test_runner.php --isolated-only    # Run only isolated tests
 *   php scripts/parallel_test_runner.php --filter=Pattern   # Filter tests by pattern
 *   php scripts/parallel_test_runner.php --processes=8      # Number of parallel processes
 *   php scripts/parallel_test_runner.php --timeout=60       # Timeout per test (seconds)
 *   php scripts/parallel_test_runner.php --batch-size=20    # Detection batch size (faster)
 *   php scripts/parallel_test_runner.php --coverage         # Generate coverage report
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tests\Support\TestIsolation\HangDetector;

// --- SIGNAL HANDLING ---
$currentProcess = null;

if (function_exists('pcntl_async_signals') && function_exists('pcntl_signal')) {
    pcntl_async_signals(true);
    
    $signalHandler = function ($signo) use (&$currentProcess) {
        echo "\n\nInterrupted. Cleaning up...\n";
        
        if ($currentProcess instanceof Process && $currentProcess->isRunning()) {
            $currentProcess->stop(3, SIGKILL);
        }
        
        exit(130);
    };
    
    pcntl_signal(SIGINT, $signalHandler);
    pcntl_signal(SIGTERM, $signalHandler);
}

// --- INITIALIZATION ---
$output = new ConsoleOutput();
$baseDir = realpath(__DIR__ . '/..');
$rosterFile = $baseDir . '/tests/test_roster.json';

// --- ARGUMENT PARSING ---
$options = [
    'detect' => false,
    'detect-quick' => false,
    'detect-fast' => false,
    'parallel-only' => false,
    'sequential-only' => false,
    'isolated-only' => false,
    'filter' => null,
    'processes' => null,  // null = auto-detect
    'timeout' => 30,
    'batch-size' => 1,    // 1 = individual tests, higher = faster with binary search
    'coverage' => false,
    'verbose' => false,
];

foreach ($_SERVER['argv'] as $key => $value) {
    if ($value === '--detect') {
        $options['detect'] = true;
        unset($_SERVER['argv'][$key]);
    } elseif ($value === '--detect-quick') {
        $options['detect-quick'] = true;
        unset($_SERVER['argv'][$key]);
    } elseif ($value === '--detect-fast') {
        $options['detect-fast'] = true;
        $options['batch-size'] = 20; // Default fast batch size
        unset($_SERVER['argv'][$key]);
    } elseif ($value === '--parallel-only') {
        $options['parallel-only'] = true;
        unset($_SERVER['argv'][$key]);
    } elseif ($value === '--sequential-only') {
        $options['sequential-only'] = true;
        unset($_SERVER['argv'][$key]);
    } elseif ($value === '--isolated-only') {
        $options['isolated-only'] = true;
        unset($_SERVER['argv'][$key]);
    } elseif (strpos($value, '--filter=') === 0) {
        $options['filter'] = substr($value, 9);
        unset($_SERVER['argv'][$key]);
    } elseif (strpos($value, '--processes=') === 0) {
        $options['processes'] = (int)substr($value, 12);
        unset($_SERVER['argv'][$key]);
    } elseif (strpos($value, '--timeout=') === 0) {
        $options['timeout'] = (int)substr($value, 10);
        unset($_SERVER['argv'][$key]);
    } elseif (strpos($value, '--batch-size=') === 0) {
        $options['batch-size'] = (int)substr($value, 13);
        unset($_SERVER['argv'][$key]);
    } elseif ($value === '--coverage') {
        $options['coverage'] = true;
        unset($_SERVER['argv'][$key]);
    } elseif ($value === '-v' || $value === '--verbose') {
        $options['verbose'] = true;
        unset($_SERVER['argv'][$key]);
    }
}
$_SERVER['argv'] = array_values($_SERVER['argv']);

$io = new SymfonyStyle(new ArgvInput(), $output);

// --- DETECT MODE ---
if ($options['detect'] || $options['detect-quick'] || $options['detect-fast']) {
    $io->title('Test Problem Detection');
    
    $detector = new HangDetector($baseDir);
    $detector->setHangTimeout($options['timeout']);
    $detector->setBatchSize($options['batch-size']);
    
    if ($options['processes']) {
        $detector->setParallelProcesses($options['processes']);
    }
    
    $isQuick = $options['detect-quick'] || $options['detect-fast'];
    $results = $detector->detectAll(null, $isQuick);
    $detector->generateRoster($rosterFile);
    
    exit(0);
}

// --- LOAD ROSTER ---
if (!file_exists($rosterFile)) {
    $io->warning('No test roster found. Running detection first...');
    
    $detector = new HangDetector($baseDir);
    $detector->setHangTimeout($options['timeout']);
    $results = $detector->detectAll(null, true); // Quick detection
    $detector->generateRoster($rosterFile);
}

$roster = json_decode(file_get_contents($rosterFile), true);
if (!$roster) {
    $io->error('Failed to load test roster');
    exit(1);
}

$io->title('Parallel Test Runner');

// Support both new format (summary) and old format (count from categories)
$categories = $roster['categories'] ?? $roster['auto_detected'] ?? [];
$summary = $roster['summary'] ?? [
    'normal' => count($categories['parallel_safe'] ?? []),
    'parallel_fails' => count($categories['non_parallel'] ?? []),
    'hangs' => count($categories['non_batched'] ?? []),
    'flaky' => count($categories['flaky'] ?? []),
];

$io->text([
    sprintf('Roster generated: %s', $roster['generated_at'] ?? 'Unknown'),
    sprintf('Parallel safe: %d tests', $summary['normal'] ?? 0),
    sprintf('Non-parallel: %d tests', $summary['parallel_fails'] ?? 0),
    sprintf('Non-batched: %d tests', $summary['hangs'] ?? 0),
    sprintf('Flaky: %d tests', $summary['flaky'] ?? 0),
]);
$io->newLine();

// --- PREPARE TEST LISTS ---
// Support both new format (categories) and old format (auto_detected)
$categories = $roster['categories'] ?? $roster['auto_detected'] ?? [];

$parallelTests = $categories['parallel_safe'] ?? [];
$sequentialTests = array_merge(
    array_column($categories['non_parallel'] ?? [], 'file'),
    array_column($categories['flaky'] ?? [], 'file')
);
$isolatedTests = array_column($categories['non_batched'] ?? [], 'file');

// Apply filter if specified
if ($options['filter']) {
    $pattern = $options['filter'];
    $parallelTests = array_filter($parallelTests, fn($f) => stripos($f, $pattern) !== false);
    $sequentialTests = array_filter($sequentialTests, fn($f) => stripos($f, $pattern) !== false);
    $isolatedTests = array_filter($isolatedTests, fn($f) => stripos($f, $pattern) !== false);
}

// Convert relative paths to absolute
$toAbsolute = fn($file) => $baseDir . '/' . $file;
$parallelTests = array_map($toAbsolute, $parallelTests);
$sequentialTests = array_map($toAbsolute, $sequentialTests);
$isolatedTests = array_map($toAbsolute, $isolatedTests);

// --- REPORTS DIRECTORY ---
$reportsDir = $baseDir . '/reports/test_runs_' . date('Y-m-d_His');
if (!is_dir($reportsDir)) {
    mkdir($reportsDir, 0777, true);
}

// --- STATS ---
$stats = [
    'passed' => 0,
    'failed' => 0,
    'errors' => 0,
    'skipped' => 0,
    'time' => 0,
];

$exitCode = 0;

// --- PHASE 1: PARALLEL TESTS ---
if (!$options['sequential-only'] && !$options['isolated-only'] && count($parallelTests) > 0) {
    $io->section(sprintf('Phase 1: Running %d Parallel-Safe Tests', count($parallelTests)));
    
    // Create temporary PHPUnit config for parallel tests
    $parallelConfig = createTempPhpunitConfig($parallelTests, $baseDir);
    
    // Determine process count
    $processes = $options['processes'] ?? (int)shell_exec('nproc 2>/dev/null || echo 4');
    
    $cmd = [
        'php', 'vendor/bin/paratest',
        '-p', (string)$processes,
        '--configuration', $parallelConfig,
        '--colors',
    ];
    
    if ($options['coverage']) {
        $cmd[] = '--coverage-html';
        $cmd[] = $reportsDir . '/coverage-parallel';
    }
    
    if ($options['verbose']) {
        $cmd[] = '-v';
    }
    
    $io->text(sprintf('Running with %d parallel processes...', $processes));
    
    $process = new Process($cmd, $baseDir);
    $currentProcess = $process;
    $process->setTimeout(null);
    
    $startTime = microtime(true);
    $process->run(function ($type, $buffer) use ($io, $options) {
        if ($options['verbose'] || $type === Process::ERR) {
            echo $buffer;
        } else {
            // Show progress dots
            if (preg_match('/[.FESRIW]/', $buffer)) {
                echo $buffer;
            }
        }
    });
    $elapsed = microtime(true) - $startTime;
    $currentProcess = null;
    
    $stats['time'] += $elapsed;
    
    // Parse output for stats
    $output = $process->getOutput();
    parsePhpunitStats($output, $stats);
    
    if (!$process->isSuccessful()) {
        $exitCode = 1;
        file_put_contents($reportsDir . '/parallel_failures.log', $output);
        $io->warning('Some parallel tests failed - see ' . $reportsDir . '/parallel_failures.log');
    } else {
        $io->success(sprintf('Parallel tests completed in %.2fs', $elapsed));
    }
    
    @unlink($parallelConfig);
    $io->newLine();
}

// --- PHASE 2: SEQUENTIAL TESTS ---
if (!$options['parallel-only'] && !$options['isolated-only'] && count($sequentialTests) > 0) {
    $io->section(sprintf('Phase 2: Running %d Sequential Tests', count($sequentialTests)));
    $io->text('These tests have parallel conflicts or are flaky...');
    
    $seqConfig = createTempPhpunitConfig($sequentialTests, $baseDir);
    
    $cmd = [
        'php', 'vendor/bin/phpunit',
        '--configuration', $seqConfig,
        '--colors',
    ];
    
    if ($options['coverage']) {
        $cmd[] = '--coverage-html';
        $cmd[] = $reportsDir . '/coverage-sequential';
    }
    
    $process = new Process($cmd, $baseDir);
    $currentProcess = $process;
    $process->setTimeout(null);
    
    $startTime = microtime(true);
    $process->run(function ($type, $buffer) {
        echo $buffer;
    });
    $elapsed = microtime(true) - $startTime;
    $currentProcess = null;
    
    $stats['time'] += $elapsed;
    
    $output = $process->getOutput();
    parsePhpunitStats($output, $stats);
    
    if (!$process->isSuccessful()) {
        $exitCode = 1;
        file_put_contents($reportsDir . '/sequential_failures.log', $output);
    } else {
        $io->success(sprintf('Sequential tests completed in %.2fs', $elapsed));
    }
    
    @unlink($seqConfig);
    $io->newLine();
}

// --- PHASE 3: ISOLATED TESTS ---
if (!$options['parallel-only'] && !$options['sequential-only'] && count($isolatedTests) > 0) {
    $io->section(sprintf('Phase 3: Running %d Isolated Tests', count($isolatedTests)));
    $io->text('These tests must run in complete isolation (known to hang otherwise)...');
    
    $progress = $io->createProgressBar(count($isolatedTests));
    $progress->start();
    
    $isolatedFailed = [];
    
    foreach ($isolatedTests as $file) {
        $shortName = str_replace($baseDir . '/', '', $file);
        
        $cmd = [
            'php', 'vendor/bin/phpunit',
            '--process-isolation',
            '--no-coverage',
            '--colors=never',
            $file
        ];
        
        $process = new Process($cmd, $baseDir);
        $currentProcess = $process;
        $process->setTimeout($options['timeout'] * 2); // Double timeout for isolated
        
        $startTime = microtime(true);
        try {
            $process->run();
            $elapsed = microtime(true) - $startTime;
            $stats['time'] += $elapsed;
            
            $output = $process->getOutput();
            parsePhpunitStats($output, $stats);
            
            if (!$process->isSuccessful()) {
                $exitCode = 1;
                $isolatedFailed[] = $shortName;
            }
        } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException $e) {
            $exitCode = 1;
            $stats['errors']++;
            $isolatedFailed[] = "$shortName (TIMEOUT)";
        }
        $currentProcess = null;
        
        $progress->advance();
    }
    
    $progress->finish();
    $io->newLine(2);
    
    if (!empty($isolatedFailed)) {
        file_put_contents($reportsDir . '/isolated_failures.log', implode("\n", $isolatedFailed));
        $io->warning('Some isolated tests failed - see ' . $reportsDir . '/isolated_failures.log');
    } else {
        $io->success('All isolated tests passed');
    }
}

// --- FINAL SUMMARY ---
$io->section('Summary');

$io->table(
    ['Metric', 'Value'],
    [
        ['Total Time', sprintf('%.2fs', $stats['time'])],
        ['Passed', $stats['passed']],
        ['Failed', $stats['failed']],
        ['Errors', $stats['errors']],
        ['Skipped', $stats['skipped']],
    ]
);

if ($exitCode === 0) {
    $io->success('All tests passed!');
} else {
    $io->error('Some tests failed');
    $io->text("See $reportsDir for detailed logs");
}

exit($exitCode);

// --- HELPER FUNCTIONS ---

function createTempPhpunitConfig(array $testFiles, string $baseDir): string
{
    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = true;
    
    $phpunit = $xml->createElement('phpunit');
    $phpunit->setAttribute('bootstrap', 'tests/bootstrap.php');
    $phpunit->setAttribute('colors', 'true');
    $phpunit->setAttribute('cacheDirectory', '.phpunit.cache');
    $xml->appendChild($phpunit);
    
    $testsuites = $xml->createElement('testsuites');
    $phpunit->appendChild($testsuites);
    
    $suite = $xml->createElement('testsuite');
    $suite->setAttribute('name', 'Dynamic');
    $testsuites->appendChild($suite);
    
    foreach ($testFiles as $file) {
        $fileElement = $xml->createElement('file', $file);
        $suite->appendChild($fileElement);
    }
    
    // Add source/coverage config
    $source = $xml->createElement('source');
    $phpunit->appendChild($source);
    $include = $xml->createElement('include');
    $source->appendChild($include);
    $dir = $xml->createElement('directory', 'app');
    $include->appendChild($dir);
    
    $tempFile = tempnam(sys_get_temp_dir(), 'phpunit_') . '.xml';
    $xml->save($tempFile);
    
    return $tempFile;
}

function parsePhpunitStats(string $output, array &$stats): void
{
    // PHPUnit 11 format: Tests: 10, Assertions: 25, Failures: 1, Errors: 0, Skipped: 2
    if (preg_match('/Tests:\s*(\d+)/', $output, $m)) {
        $stats['passed'] += (int)$m[1];
    }
    if (preg_match('/Failures:\s*(\d+)/', $output, $m)) {
        $stats['failed'] += (int)$m[1];
        $stats['passed'] -= (int)$m[1];
    }
    if (preg_match('/Errors:\s*(\d+)/', $output, $m)) {
        $stats['errors'] += (int)$m[1];
        $stats['passed'] -= (int)$m[1];
    }
    if (preg_match('/Skipped:\s*(\d+)/', $output, $m)) {
        $stats['skipped'] += (int)$m[1];
        $stats['passed'] -= (int)$m[1];
    }
    
    // Also handle "OK (10 tests, 25 assertions)"
    if (preg_match('/OK \((\d+) tests?/', $output, $m)) {
        $stats['passed'] += (int)$m[1];
    }
}
