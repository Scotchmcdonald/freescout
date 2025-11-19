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

// --- COVERAGE SETUP ---
// Check for coverage flag and clean argv so it doesn't break suite selection
$withCoverage = false;
foreach ($_SERVER['argv'] as $key => $value) {
    if ($value === '--coverage') {
        $withCoverage = true;
        unset($_SERVER['argv'][$key]);
    }
}
// Re-index argv so suite selection logic (checking index 1) still works
$_SERVER['argv'] = array_values($_SERVER['argv']);

$coveragePartialsDir = $baseDir . '/reports/coverage_partials';
$finalCoverageDir = $baseDir . '/reports/coverage-report';

if ($withCoverage) {
    $io->note("Coverage mode enabled. This will slow down execution.");

    // Ensure drivers are available
    if (!extension_loaded('xdebug') && !extension_loaded('pcov')) {
        $io->warning("Coverage requested but no driver (Xdebug or PCOV) detected. Tests may run slow or fail to generate reports.");
    }
    
    // Clean/Create partials directory
    if (is_dir($coveragePartialsDir)) {
        // Delete existing .cov files
        array_map('unlink', glob("$coveragePartialsDir/*.cov"));
    } else {
        mkdir($coveragePartialsDir, 0777, true);
    }
}

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
$suiteNames = array_keys($availableSuites);

$selectedSuitesInput = [];
if (isset($_SERVER['argv'][1])) {
    $arg = $_SERVER['argv'][1];
    if (strtolower($arg) === 'a' || strtolower($arg) === 'all') {
        $selectedSuitesInput = ['All'];
    } elseif (is_numeric($arg)) {
        $index = (int)$arg;
        if (isset($suiteNames[$index])) {
            $selectedSuitesInput = [$suiteNames[$index]];
        } else {
            $io->error("Invalid suite index: $arg");
            exit(1);
        }
    } elseif (in_array($arg, $suiteNames)) {
        $selectedSuitesInput = [$arg];
    }
}

if (empty($selectedSuitesInput)) {
    $io->section('Available Test Suites');
    foreach ($suiteNames as $idx => $name) {
        $io->writeln(" [$idx] $name");
    }
    $io->newLine();
    
    $answer = $io->ask('Which test suite(s) would you like to run? (enter index, name, or "All")', 'All');
    
    if (strtolower($answer) === 'all' || strtolower($answer) === 'a') {
        $selectedSuitesInput = ['All'];
    } elseif (is_numeric($answer)) {
        $index = (int)$answer;
        if (isset($suiteNames[$index])) {
            $selectedSuitesInput = [$suiteNames[$index]];
        } else {
            $io->error("Invalid suite index: $answer");
            exit(1);
        }
    } elseif (in_array($answer, $suiteNames)) {
        $selectedSuitesInput = [$answer];
    } else {
        $io->error("Invalid selection: $answer");
        exit(1);
    }
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

// --- TEST ANALYSIS ---
$io->section('Analyzing test files...');
$totalTestCount = 0;
$fileTestCounts = [];

$analysisProgressBar = $io->createProgressBar(count($filesToRun));
$analysisProgressBar->setFormat(' %current%/%max% [%bar%] Analyzing %message%...');
$analysisProgressBar->start();

foreach ($filesToRun as $file) {
    $analysisProgressBar->setMessage(basename($file));
    $content = file_get_contents($file);
    
    // Heuristic to count tests: "public function test..." or "@test" annotation
    $count = preg_match_all('/public\s+function\s+test/', $content);
    $count += preg_match_all('/\*\s*@test/', $content);
    
    $fileTestCounts[$file] = $count;
    $totalTestCount += $count;
    $analysisProgressBar->advance();
}
$analysisProgressBar->finish();
$io->newLine(2);

$io->text("Found <info>{$totalTestCount}</info> tests in <info>" . count($filesToRun) . "</info> files.");

// File listing suppressed
$io->newLine();

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
$executionProgressBar->setFormat(" %current%/%max% [%custom_bar%] %percent:3s%% | Elapsed: %elapsed:6s% | ETA: %estimated:-6s% | Mem: %memory:6s%\n %message%");
$executionProgressBar->setMessage('', 'custom_bar');
$executionProgressBar->start();

$allResultsOutput = '';
$runningStats = ['Tests' => 0, 'Assertions' => 0, 'Errors' => 0, 'Failures' => 0, 'Skipped' => 0, 'Incomplete' => 0];

foreach ($chunks as $chunkIndex => $chunkFiles) {
    $firstFile = basename($chunkFiles[0]);
    
    // Calculate Results Bar
    $barWidth = 30;
    $currentStep = $executionProgressBar->getProgress() + count($chunkFiles);
    $progressRatio = min(1, $currentStep / $totalFiles);
    $filledChars = (int)round($barWidth * $progressRatio);
    $emptyChars = $barWidth - $filledChars;
    
    $totalTestsRunSoFar = $runningStats['Tests'];
    $barStr = "";
    
    if ($totalTestsRunSoFar > 0 && $filledChars > 0) {
        $counts = [
            'Pass' => $totalTestsRunSoFar - $runningStats['Failures'] - $runningStats['Errors'] - $runningStats['Skipped'] - $runningStats['Incomplete'],
            'Fail' => $runningStats['Failures'],
            'Err' => $runningStats['Errors'],
            'Skip' => $runningStats['Skipped'],
            'Inc' => $runningStats['Incomplete']
        ];
        
        $widths = [];
        foreach ($counts as $type => $count) {
            $widths[$type] = ($count / $totalTestsRunSoFar) * $filledChars;
        }
        
        $roundedWidths = array_map('round', $widths);
        $sumRounded = array_sum($roundedWidths);
        $diff = $filledChars - $sumRounded;
        
        // Adjust largest
        arsort($counts);
        $largestType = array_key_first($counts);
        $roundedWidths[$largestType] += $diff;
        
        // Stealing logic for visibility
        foreach ($counts as $type => $count) {
            if ($count > 0 && $roundedWidths[$type] == 0) {
                $donorType = null;
                $maxW = 0;
                foreach ($roundedWidths as $t => $w) {
                    if ($t !== $type && $w > $maxW) {
                        $maxW = $w;
                        $donorType = $t;
                    }
                }
                if ($donorType && $maxW > 0) {
                    $roundedWidths[$donorType]--;
                    $roundedWidths[$type]++;
                }
            }
        }
        
        $barStr .= "<fg=green>" . str_repeat("▓", max(0, (int)$roundedWidths['Pass'])) . "</>";
        $barStr .= "<fg=magenta>" . str_repeat("▓", max(0, (int)$roundedWidths['Fail'])) . "</>";
        $barStr .= "<fg=red>" . str_repeat("▓", max(0, (int)$roundedWidths['Err'])) . "</>";
        $barStr .= "<fg=yellow>" . str_repeat("▓", max(0, (int)$roundedWidths['Skip'])) . "</>";
        $barStr .= "<fg=cyan>" . str_repeat("▓", max(0, (int)$roundedWidths['Inc'])) . "</>";
    } else {
        if ($filledChars > 0) {
             $barStr .= "<fg=gray>" . str_repeat("▓", $filledChars) . "</>";
        }
    }
    $barStr .= "<fg=gray>" . str_repeat("░", max(0, (int)$emptyChars)) . "</>";

    $executionProgressBar->setMessage($barStr, 'custom_bar');

    $statsMsg = sprintf(
        "<fg=green>Pass: %d</> | <fg=magenta>Fail: %d</> | <fg=red>Err: %d</> | <fg=yellow>Skip: %d</>",
        $runningStats['Tests'] - $runningStats['Failures'] - $runningStats['Errors'] - $runningStats['Skipped'],
        $runningStats['Failures'],
        $runningStats['Errors'],
        $runningStats['Skipped']
    );
    
    $executionProgressBar->setMessage("Batch " . ($chunkIndex + 1) . "/" . count($chunks) . " (starts with {$firstFile})\n " . $statsMsg);

    // --- COMMAND PREPARATION ---
    $commandParts = [$baseDir.'/vendor/bin/phpunit', '--testdox'];

    if ($withCoverage) {
        // Tell PHPUnit to dump raw PHP coverage data for this specific batch
        $commandParts[] = '--coverage-php';
        $commandParts[] = $coveragePartialsDir . '/batch_' . ($chunkIndex + 1) . '.cov';
    }

    // Merge command parts with the files
    $command = array_merge($commandParts, $chunkFiles);
    
    $process = new Process($command, $baseDir, null, null, 600);
    $process->run();
    
    $output = $process->getOutput();
    $logFileName = 'batch_' . ($chunkIndex + 1) . '.log';
    file_put_contents("{$reportsDir}/{$logFileName}", $output);
    $allResultsOutput .= $output . PHP_EOL;

    // --- Log Errors and Failures separately ---
    $errorOutput = $process->getErrorOutput();
    if (!empty($errorOutput)) {
        file_put_contents("{$reportsDir}/error.log", "Batch " . ($chunkIndex + 1) . " STDERR:\n" . $errorOutput . "\n\n" . str_repeat('-', 40) . "\n\n", FILE_APPEND);
    }

    // Extract Failures from STDOUT
    if (preg_match('/There (?:was|were) \d+ failure(?:s)?:\s*(.*?)(?=\n(?:There (?:was|were) \d+ error|FAILURES!|ERRORS!)|$)/s', $output, $matches)) {
        $failureContent = "Batch " . ($chunkIndex + 1) . " Failures:\n" . trim($matches[1]) . "\n\n" . str_repeat('-', 40) . "\n\n";
        file_put_contents("{$reportsDir}/failure.log", $failureContent, FILE_APPEND);
    }

    // Extract Errors from STDOUT
    if (preg_match('/There (?:was|were) \d+ error(?:s)?:\s*(.*?)(?=\n(?:There (?:was|were) \d+ failure|FAILURES!|ERRORS!)|$)/s', $output, $matches)) {
        $errorContent = "Batch " . ($chunkIndex + 1) . " Errors:\n" . trim($matches[1]) . "\n\n" . str_repeat('-', 40) . "\n\n";
        file_put_contents("{$reportsDir}/error.log", $errorContent, FILE_APPEND);
    }

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

// --- COVERAGE MERGING ---
if ($withCoverage) {
    $io->section('Generating Coverage Report');
    
    // Check for phpcov binary (standard tool for merging phpunit coverage)
    $phpcovBin = $baseDir . '/vendor/bin/phpcov';
    
    if (file_exists($phpcovBin)) {
        $io->text('Merging partial coverage files...');
        
        // phpcov merge --html <output_directory> <directory>
        $mergeCommand = [
            'php',
            $phpcovBin, 
            'merge', 
            '--html', 
            $finalCoverageDir,
            $coveragePartialsDir
        ];

        $process = new Process($mergeCommand, $baseDir, null, null, 300);
        $process->run();

        if ($process->isSuccessful()) {
            $io->success("Coverage report generated successfully!");
            $io->writeln("View report here: file://{$finalCoverageDir}/index.html");
            
            // Clean up partials
            array_map('unlink', glob("$coveragePartialsDir/*"));
            rmdir($coveragePartialsDir);
        } else {
            $io->error("Failed to merge coverage reports.");
            $io->writeln("Command output: " . $process->getErrorOutput());
            $io->writeln($process->getOutput());
        }
    } else {
        $io->warning("The 'phpcov' binary was not found in vendor/bin.");
        $io->note("Partial coverage files have been saved to: {$coveragePartialsDir}");
        $io->note("To enable automatic merging, run: composer require --dev phpunit/phpcov");
    }
}

$io->success('All tests passed!');
exit(0);