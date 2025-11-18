<?php

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

// --- INITIALIZATION ---
$output = new ConsoleOutput();
$io = new SymfonyStyle(new ArgvInput(), $output);
$baseDir = realpath(__DIR__.'/..');

$io->title('Freescout Test Runner (File-by-File)');

// --- SUITE SELECTION ---
$availableSuites = [];
$testsDir = $baseDir . '/tests';

// 1. Scan for subdirectories in tests/
$dirs = glob($testsDir . '/*', GLOB_ONLYDIR);
foreach ($dirs as $dir) {
    $suiteName = basename($dir);
    $availableSuites[$suiteName] = 'tests/' . $suiteName;
}

// 2. Check for files in root of tests/ for "Misc"
$miscFinder = new Finder();
$miscFinder->files()->in($testsDir)->depth(0)->name('*Test.php');
if ($miscFinder->hasResults()) {
    $availableSuites['Misc'] = 'tests';
}

ksort($availableSuites);
$choices = array_keys($availableSuites);
$choices[] = 'All';

$selectedSuitesInput = [];
if (isset($_SERVER['argv'][1])) {
    $arg = $_SERVER['argv'][1];
    if (strtolower($arg) === 'a' || strtolower($arg) === 'all') {
        $selectedSuitesInput = ['All'];
    } elseif (is_numeric($arg)) {
        $index = (int)$arg;
        if (isset($choices[$index])) {
            $selectedSuitesInput = [$choices[$index]];
        } else {
            $io->error("Invalid suite index: $arg");
            exit(1);
        }
    } elseif (in_array($arg, $choices)) {
        $selectedSuitesInput = [$arg];
    }
}

if (empty($selectedSuitesInput)) {
    $selectedSuitesInput = $io->choice('Which test suite(s) would you like to run?', $choices, 'All', true);
}

$suitesToRun = in_array('All', $selectedSuitesInput) ? $availableSuites : array_intersect_key($availableSuites, array_flip($selectedSuitesInput));

// --- TEST DISCOVERY ---
$io->section('Discovering test files...');
$filesToRun = [];

$finderProgressBar = $io->createProgressBar(count($suitesToRun));
$finderProgressBar->setFormat(' %current%/%max% [%bar%] Discovering in %message%...');
$finderProgressBar->start();

foreach ($suitesToRun as $suiteName => $suiteDir) {
    $finderProgressBar->setMessage($suiteName);
    $absoluteSuiteDir = realpath($baseDir . '/' . $suiteDir);
    if (!$absoluteSuiteDir) continue;

    $finder = new Finder();
    $finder->files()->in($absoluteSuiteDir)->name('*Test.php')->sortByName();
    
    if ($suiteName === 'Misc') {
        $finder->depth(0);
    }

    foreach ($finder as $file) {
        $filesToRun[] = $file->getRealPath();
    }
    $finderProgressBar->advance();
}
$finderProgressBar->finish();
$io->newLine(2);
$io->info("Found ".count($filesToRun)." test files.");

// --- EXECUTION ---
$reportsDir = $baseDir.'/reports/test_runs_'.date('Y-m-d_His');
mkdir($reportsDir, 0777, true);

$io->section('Running Tests (Batched)');

$startTime = microtime(true);

$totalFiles = count($filesToRun);
// Dynamic batch size: ~5% of total files, min 5, max 25 to balance speed vs memory
$batchSize = max(5, min(25, (int)ceil($totalFiles * 0.05)));
$chunks = array_chunk($filesToRun, $batchSize);

$executionProgressBar = $io->createProgressBar($totalFiles);
$executionProgressBar->setFormat(" %current%/%max% [%bar%] %percent:3s%% | Elapsed: %elapsed:6s% | ETA: %estimated:-6s% | Mem: %memory:6s%\n %message%");
$executionProgressBar->start();

$allResultsOutput = '';
$runningStats = ['Tests' => 0, 'Assertions' => 0, 'Errors' => 0, 'Failures' => 0, 'Skipped' => 0, 'Incomplete' => 0];

foreach ($chunks as $chunkIndex => $chunkFiles) {
    $firstFile = basename($chunkFiles[0]);
    $statsMsg = sprintf(
        "<fg=green>Pass: %d</> | <fg=red>Fail: %d</> | <fg=red>Err: %d</> | <fg=yellow>Skip: %d</>",
        $runningStats['Tests'] - $runningStats['Failures'] - $runningStats['Errors'], // Approx pass count
        $runningStats['Failures'],
        $runningStats['Errors'],
        $runningStats['Skipped']
    );
    
    $executionProgressBar->setMessage("Batch " . ($chunkIndex + 1) . "/" . count($chunks) . " (starts with {$firstFile})\n " . $statsMsg);

    // Use --testdox for verbose output (listing tests instead of dots)
    $command = array_merge([$baseDir.'/vendor/bin/phpunit', '--testdox'], $chunkFiles);
    $process = new Process($command, $baseDir, null, null, 600);
    $process->run();
    
    $output = $process->getOutput();
    $logFileName = 'batch_' . ($chunkIndex + 1) . '.log';
    file_put_contents("{$reportsDir}/{$logFileName}", $output);
    $allResultsOutput .= $output . PHP_EOL;

    // Parse batch output to update stats
    // 1. Match standard PHPUnit summary line
    if (preg_match('/(Tests:.*)/', $output, $matches)) {
        $line = $matches[1];
        preg_match_all('/(Tests|Assertions|Errors|Failures|Risky|Skipped|Incomplete|PHPUnit Warnings): (\d+)/', $line, $statMatches, PREG_SET_ORDER);
        foreach ($statMatches as $match) {
            if (isset($runningStats[$match[1]])) {
                $runningStats[$match[1]] += (int)$match[2];
            }
        }
    } 
    // 2. Match "OK (X tests, Y assertions)"
    elseif (preg_match('/OK \((\d+) tests?, (\d+) assertions?\)/', $output, $matches)) {
        $runningStats['Tests'] += (int)$matches[1];
        $runningStats['Assertions'] += (int)$matches[2];
    }

    $executionProgressBar->advance(count($chunkFiles));
}
$executionProgressBar->finish();
$io->newLine(2);

// --- SUMMARY ---
$io->section('Test Results Summary');
$executionTime = microtime(true) - $startTime;
$io->writeln("Total Time: " . number_format($executionTime, 2) . "s");
$io->note("Detailed logs are available in: {$reportsDir}");

$summary = ['Tests' => 0, 'Assertions' => 0, 'Errors' => 0, 'Failures' => 0, 'Risky' => 0, 'Skipped' => 0, 'Incomplete' => 0, 'PHPUnit Warnings' => 0];
$failureDetails = '';

// Find all summary lines (e.g., "Tests: 123, Assertions: 456, ...")
preg_match_all('/(Tests:.*)/', $allResultsOutput, $summaryLines);
foreach ($summaryLines[0] as $line) {
    // Extract individual metrics from each summary line
    preg_match_all('/(Tests|Assertions|Errors|Failures|Risky|Skipped|Incomplete|PHPUnit Warnings): (\d+)/', $line, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        if (isset($summary[$match[1]])) {
            $summary[$match[1]] += (int)$match[2];
        }
    }
}

// Handle PHPUnit 10+ success output "OK (X tests, Y assertions)"
preg_match_all('/OK \((\d+) tests?, (\d+) assertions?\)/', $allResultsOutput, $okMatches, PREG_SET_ORDER);
foreach ($okMatches as $match) {
    $summary['Tests'] += (int)$match[1];
    $summary['Assertions'] += (int)$match[2];
}

// Extract failure blocks
preg_match_all('/There (was|were) \d+ (failure|error)s?:\n\n(.*?)(?=OK|FAILURES!|ERRORS!)/s', $allResultsOutput, $failureBlocks);
if (!empty($failureBlocks[3])) {
    $uniqueFailures = array_unique(array_map('trim', $failureBlocks[3]));
    $failureDetails = implode("\n\n--\n\n", $uniqueFailures);
}

$summaryString = "Totals: ";
foreach ($summary as $key => $value) {
    if ($value > 0) $summaryString .= "{$key}: {$value}, ";
}
$io->writeln('');
$io->writeln("<fg=white;options=bold>".rtrim($summaryString, ', ')."</>");

if ($summary['Failures'] > 0 || $summary['Errors'] > 0) {
    $io->section('Failure Details');
    $io->writeln($failureDetails);
    $io->error('Tests failed.');
    exit(1);
}

if (count($filesToRun) > 0 && $summary['Tests'] === 0) {
    $io->warning('No tests were executed. Check your configuration and filters.');
    exit(1);
}

$io->success('All tests passed!');
exit(0);
