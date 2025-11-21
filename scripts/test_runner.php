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

// --- ARGUMENT PARSING ---
// Check for coverage flag and filter flag, clean argv
$withCoverage = false;
$filterPattern = null;
foreach ($_SERVER['argv'] as $key => $value) {
    if ($value === '--coverage') {
        $withCoverage = true;
        unset($_SERVER['argv'][$key]);
    } elseif (strpos($value, '--filter=') === 0) {
        $filterPattern = substr($value, 9); // Extract after '--filter='
        unset($_SERVER['argv'][$key]);
    }
}
// Re-index argv so suite selection logic (checking index 1) still works
$_SERVER['argv'] = array_values($_SERVER['argv']);

// Coverage directories will be set later inside the test run directory
$coveragePartialsDir = null;
$finalCoverageDir = null;

if ($withCoverage) {
    $io->note("Coverage mode enabled. This will slow down execution.");

    // Ensure drivers are available
    if (!extension_loaded('xdebug') && !extension_loaded('pcov')) {
        $io->warning("Coverage requested but no driver (Xdebug or PCOV) detected. Tests may run slow or fail to generate reports.");
    }
}

// --- CACHE CLEARING (Silent) ---
$process = new Process(['php', 'artisan', 'optimize:clear'], $baseDir);
$process->run();

$io->title('Freescout Test Runner (File-by-File)');

// --- CACHE CLEARING ---
$io->section('Clearing Caches');
if ($process->isSuccessful()) {
    $io->success('Caches cleared.');
} else {
    $io->warning('Failed to clear caches: ' . $process->getErrorOutput());
}

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
$io->section('Discovering and Analyzing Test Files');
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
$finderProgressBar->setFormat(' %current%/%max% [%bar%] %message%');
$finderProgressBar->setMessage("Reviewed <info>" . count($suitesToRun) . "/" . count($suitesToRun) . "</info> folders for tests.");
$finderProgressBar->finish();
$io->newLine();

// --- TEST ANALYSIS ---
$totalTestCount = 0;
$fileTestCounts = [];

$analysisProgressBar = $io->createProgressBar(count($filesToRun));
$analysisProgressBar->setFormat(' %current%/%max% [%bar%] Analyzing %message%...');
$analysisProgressBar->start();

foreach ($filesToRun as $file) {
    $analysisProgressBar->setMessage(basename($file));
    $content = file_get_contents($file);
    
    // Apply filter if specified
    if ($filterPattern !== null) {
        $patterns = array_map('trim', explode('|', $filterPattern));
        $matchesFilter = false;
        
        foreach ($patterns as $pattern) {
            // Check if pattern matches filename or any test method
            if (stripos(basename($file), $pattern) !== false) {
                $matchesFilter = true;
                break;
            }
            if (preg_match('/public\s+function\s+(test[a-zA-Z0-9_]*' . preg_quote($pattern, '/') . '[a-zA-Z0-9_]*)/i', $content)) {
                $matchesFilter = true;
                break;
            }
        }
        
        if (!$matchesFilter) {
            unset($filesToRun[array_search($file, $filesToRun)]);
            $analysisProgressBar->advance();
            continue;
        }
    }
    
    // Heuristic to count tests: "public function test..." or "@test" annotation
    // More accurate counting by filtering out false positives
    $lines = explode("\n", $content);
    $count = 0;
    $inAnonymousClass = false;
    
    foreach ($lines as $line) {
        // Track anonymous class context
        if (preg_match('/new\s+(?:class|#?\[.*?\]\s*class)\s*[(\{]/', $line)) {
            $inAnonymousClass = true;
        }
        
        // Skip methods in anonymous classes
        if ($inAnonymousClass) {
            if (preg_match('/^\s*}[;,)]/', $line)) {
                $inAnonymousClass = false;
            }
            continue;
        }
        
        // Count actual test methods (not in anonymous classes)
        if (preg_match('/^\s*public\s+function\s+test[a-zA-Z0-9_]*\s*\(/', $line)) {
            $count++;
        }
        
        // Count @test annotations in docblocks
        if (preg_match('/^\s*\*\s*@test\s*$/', $line)) {
            $count++;
        }
    }
    
    $fileTestCounts[$file] = $count;
    $totalTestCount += $count;
    $analysisProgressBar->advance();
}
$filesToRun = array_values($filesToRun); // Re-index after filtering
$totalFilesAnalyzed = count($filesToRun);
$analysisProgressBar->setFormat(' %current%/%max% [%bar%] %message%');
$analysisProgressBar->setMessage("Analyzed <info>{$totalFilesAnalyzed}/{$totalFilesAnalyzed}</info> test files, estimated ~<info>{$totalTestCount}</info> test methods.");
$analysisProgressBar->finish();
$io->newLine();

if ($filterPattern !== null) {
    $io->text("Filter applied: <comment>{$filterPattern}</comment>");
}

// File listing suppressed
$io->newLine();

// --- EXECUTION ---
$reportsDir = $baseDir.'/reports/test_runs_'.date('Y-m-d_His');
mkdir($reportsDir, 0777, true);

// Set coverage directories inside the test run directory
if ($withCoverage) {
    $coveragePartialsDir = $reportsDir . '/coverage_partials';
    $finalCoverageDir = $reportsDir . '/coverage-report';
    mkdir($coveragePartialsDir, 0777, true);
}

$io->section('Running Tests (Batched)');

$startTime = microtime(true);

$totalFiles = count($filesToRun);
// Dynamic batch size: ~5% of total files, min 5, max 25 to balance speed vs memory
$batchSize = max(5, min(25, (int)ceil($totalFiles * 0.033)));
$chunks = array_chunk($filesToRun, $batchSize);

$executionProgressBar = $io->createProgressBar($totalFiles);

// Add custom placeholder for remaining time
$executionProgressBar->setPlaceholderFormatterDefinition('remaining', function ($bar) {
    if (!$bar->getMaxSteps()) {
        return '0 s';
    }
    if (!$bar->getProgress()) {
        return '?';
    }
    
    $elapsed = time() - $bar->getStartTime();
    $rate = $bar->getProgress() / $elapsed;
    $remaining = ($bar->getMaxSteps() - $bar->getProgress()) / $rate;
    
    // Format as "X min, Y s" or just "Y s" for under 60 seconds
    $mins = floor($remaining / 60);
    $secs = (int)($remaining % 60);
    
    if ($mins > 0) {
        return sprintf('%d min, %d s', $mins, $secs);
    }
    return sprintf('%d s', $secs);
});

$executionProgressBar->setFormat(" %current%/%max% [%custom_bar%] %percent:3s%% | Elapsed: %elapsed:6s% | ETA: %remaining:-6s% | Mem: %memory:6s%\n %message%");
$executionProgressBar->setMessage('', 'custom_bar');
$executionProgressBar->setMessage('Initializing...'); // Set initial message
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
        $barStr .= "<fg=#FFA500>" . str_repeat("▓", max(0, (int)$roundedWidths['Fail'])) . "</>";
        $barStr .= "<fg=red>" . str_repeat("▓", max(0, (int)$roundedWidths['Err'])) . "</>";
        $barStr .= "<fg=blue>" . str_repeat("▓", max(0, (int)$roundedWidths['Skip'])) . "</>";
        $barStr .= "<fg=yellow>" . str_repeat("▓", max(0, (int)$roundedWidths['Inc'])) . "</>";
    } else {
        if ($filledChars > 0) {
             $barStr .= "<fg=gray>" . str_repeat("▓", $filledChars) . "</>";
        }
    }
    $barStr .= "<fg=gray>" . str_repeat("░", max(0, (int)$emptyChars)) . "</>";

    $executionProgressBar->setMessage($barStr, 'custom_bar');

    $statsMsg = sprintf(
        "<fg=green>Pass: %d</> | <fg=#FFA500>Fail: %d</> | <fg=red>Err: %d</> | <fg=blue>Skip: %d</> | <fg=yellow>Inc: %d</>",
        $runningStats['Tests'] - $runningStats['Failures'] - $runningStats['Errors'] - $runningStats['Skipped'] - $runningStats['Incomplete'],
        $runningStats['Failures'],
        $runningStats['Errors'],
        $runningStats['Skipped'],
        $runningStats['Incomplete']
    );
    
    $executionProgressBar->setMessage("Batch " . ($chunkIndex + 1) . "/" . count($chunks) . " (starts with {$firstFile})\n " . $statsMsg);

    // --- COMMAND PREPARATION ---
    $commandParts = [$baseDir.'/vendor/bin/phpunit', '--testdox'];

    if ($withCoverage) {
        // Tell PHPUnit to dump raw PHP coverage data for this specific batch
        $commandParts[] = '--coverage-php';
        $commandParts[] = $coveragePartialsDir . '/batch_' . ($chunkIndex + 1) . '.cov';
    }
    
    // Add filter if specified (PHPUnit will filter by test name)
    if ($filterPattern !== null) {
        $commandParts[] = '--filter';
        // Convert pipe-separated to PHPUnit regex: (pattern1|pattern2|pattern3)
        $phpunitFilter = '(' . str_replace('|', '|', $filterPattern) . ')';
        $commandParts[] = $phpunitFilter;
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
        // Check if it's purely warnings/notices/deprecations
        // If the output contains "Fatal error" or "Parse error", it stays in error.log
        // Otherwise if it contains "Warning", "Deprecated", "Notice", put in warnings.log
        $isWarning = false;
        if (preg_match('/(?:PHP )?(?:Warning|Deprecated|Notice):/i', $errorOutput) && !preg_match('/(?:PHP )?(?:Fatal|Parse) error:/i', $errorOutput)) {
            $isWarning = true;
        }

        if ($isWarning) {
            file_put_contents("{$reportsDir}/warnings.log", "Batch " . ($chunkIndex + 1) . " STDERR (Warnings):\n" . $errorOutput . "\n\n" . str_repeat('-', 40) . "\n\n", FILE_APPEND);
        } else {
            file_put_contents("{$reportsDir}/error.log", "Batch " . ($chunkIndex + 1) . " STDERR:\n" . $errorOutput . "\n\n" . str_repeat('-', 40) . "\n\n", FILE_APPEND);
        }
    }

    // Strategy 1: Standard PHPUnit Summary Blocks
    $foundSummary = false;
    
    // Extract Failures from STDOUT (Standard Format)
    if (preg_match('/There (?:was|were) \d+ failure(?:s)?:\s*(.*?)(?=\n(?:There (?:was|were) \d+ (?:error|risky|skipped|incomplete)|FAILURES!|ERRORS!|OK)|$)/s', $output, $matches)) {
        $failureContent = "Batch " . ($chunkIndex + 1) . " Failures:\n" . trim($matches[1]) . "\n\n" . str_repeat('-', 40) . "\n\n";
        file_put_contents("{$reportsDir}/failure.log", $failureContent, FILE_APPEND);
        $foundSummary = true;
    }

    // Extract Errors from STDOUT (Standard Format)
    if (preg_match('/There (?:was|were) \d+ error(?:s)?:\s*(.*?)(?=\n(?:There (?:was|were) \d+ (?:failure|risky|skipped|incomplete)|FAILURES!|ERRORS!|OK)|$)/s', $output, $matches)) {
        $errorContent = "Batch " . ($chunkIndex + 1) . " Errors:\n" . trim($matches[1]) . "\n\n" . str_repeat('-', 40) . "\n\n";
        file_put_contents("{$reportsDir}/error.log", $errorContent, FILE_APPEND);
        $foundSummary = true;
    }

    // Extract Skipped from STDOUT
    if (preg_match('/There (?:was|were) \d+ skipped test(?:s)?:\s*(.*?)(?=\n(?:There (?:was|were) \d+ (?:error|failure|risky|incomplete)|FAILURES!|ERRORS!|OK)|$)/s', $output, $matches)) {
        $skippedContent = "Batch " . ($chunkIndex + 1) . " Skipped:\n" . trim($matches[1]) . "\n\n" . str_repeat('-', 40) . "\n\n";
        file_put_contents("{$reportsDir}/skipped.log", $skippedContent, FILE_APPEND);
    }

    // Extract Incomplete from STDOUT
    if (preg_match('/There (?:was|were) \d+ incomplete test(?:s)?:\s*(.*?)(?=\n(?:There (?:was|were) \d+ (?:error|failure|risky|skipped)|FAILURES!|ERRORS!|OK)|$)/s', $output, $matches)) {
        $incompleteContent = "Batch " . ($chunkIndex + 1) . " Incomplete:\n" . trim($matches[1]) . "\n\n" . str_repeat('-', 40) . "\n\n";
        file_put_contents("{$reportsDir}/incomplete.log", $incompleteContent, FILE_APPEND);
    }

    // Extract Risky from STDOUT
    if (preg_match('/There (?:was|were) \d+ risky test(?:s)?:\s*(.*?)(?=\n(?:There (?:was|were) \d+ (?:error|failure|skipped|incomplete)|FAILURES!|ERRORS!|OK)|$)/s', $output, $matches)) {
        $riskyContent = "Batch " . ($chunkIndex + 1) . " Risky:\n" . trim($matches[1]) . "\n\n" . str_repeat('-', 40) . "\n\n";
        file_put_contents("{$reportsDir}/risky.log", $riskyContent, FILE_APPEND);
    }

    // Extract PHPUnit test runner warnings
    if (preg_match('/There (?:was|were) (\d+) PHPUnit test runner warning(?:s)?:\s*(.*?)(?=\n(?:There (?:was|were)|OK, but|FAILURES!|ERRORS!|Tests:)|$)/s', $output, $matches)) {
        $warningContent = "Batch " . ($chunkIndex + 1) . " - PHPUnit Warnings (" . $matches[1] . "):\n" . trim($matches[2]) . "\n\n" . str_repeat('-', 40) . "\n\n";
        file_put_contents("{$reportsDir}/warnings.log", $warningContent, FILE_APPEND);
    }

    // Extract Deprecations from STDOUT
    if (preg_match_all('/Deprecation\s+Triggered[^\n]*\n.*?(?=\n\n|There (?:was|were)|$)/s', $output, $matches)) {
        foreach ($matches[0] as $deprecation) {
            $deprecationContent = "Batch " . ($chunkIndex + 1) . ":\n" . trim($deprecation) . "\n\n" . str_repeat('-', 40) . "\n\n";
            file_put_contents("{$reportsDir}/deprecation.log", $deprecationContent, FILE_APPEND);
        }
    }

    // Strategy 2: TestDox Inline Blocks (Fallback/Supplement)
    // Match lines starting with specific symbols followed by test name
    // Symbols: ✘ (Fail/Error), ⚠ (Risky), ↩ (Skipped), ∅ (Incomplete)
    // IMPORTANT: Only capture tests that have actual error details (indicated by │ lines following)
    
    $testDoxPatterns = [
        '✘' => ['file' => 'failure.log'],
        '⚠' => ['file' => 'risky.log'],
        '↩' => ['file' => 'skipped.log'],
        '∅' => ['file' => 'incomplete.log'],
    ];

    foreach ($testDoxPatterns as $symbol => $config) {
        // Regex to find the symbol, the test name, and REQUIRED details block
        // We look for the symbol at the start of a line (after whitespace)
        // Then the test name.
        // Then a block of lines that start with whitespace and │ (REQUIRED)
        // This prevents matching test names that just happen to match the symbol (like a test called "Warning")
        
        $pattern = '/^\s*' . $symbol . '\s+([^\n]+)\n((?:\s+│.*?\n)+)/ms';
        
        if (preg_match_all($pattern, $output, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $testName = trim($match[1]);
                $details = trim($match[2]);
                
                $targetLog = $config['file'];
                
                // Refine target for ✘
                if ($symbol === '✘') {
                    // If details contain "Error" or "Exception", treat as error.log
                    // Otherwise failure.log
                    if (str_contains($details, 'Error') || str_contains($details, 'Exception')) {
                        $targetLog = 'error.log';
                    }
                }
                
                $entry = "Batch " . ($chunkIndex + 1) . " - {$testName}\n";
                if ($details) {
                    $entry .= $details . "\n";
                } else {
                    $entry .= "(No details provided in output)\n";
                }
                $entry .= str_repeat('-', 40) . "\n\n";
                
                file_put_contents("{$reportsDir}/{$targetLog}", $entry, FILE_APPEND);
            }
        }
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
// Finish the progress bar and clear it to prevent duplicate display
$executionProgressBar->clear();
$executionProgressBar->finish();
$io->newLine(1);

// --- SUMMARY ---
$io->section('Test Results Summary');
$executionTime = microtime(true) - $startTime;
$io->writeln("Total Time: " . number_format($executionTime, 2) . "s");

$io->section('Log Files');
$logFiles = ['error.log', 'failure.log', 'skipped.log', 'incomplete.log', 'risky.log', 'warnings.log', 'deprecation.log'];
foreach ($logFiles as $file) {
    if (file_exists("{$reportsDir}/{$file}")) {
        $io->writeln(" - {$reportsDir}/{$file}");
    }
}
$io->newLine();

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

$summaryString = "<options=bold>Totals:</> ";
foreach ($summary as $key => $value) {
    if ($value > 0) {
        $color = 'default';
        switch ($key) {
            case 'Errors':
            case 'Failures':
                $color = 'red';
                break;
            case 'Risky':
            case 'Incomplete':
            case 'PHPUnit Warnings':
                $color = 'yellow';
                break;
            case 'Skipped':
                $color = 'blue';
                break;
            case 'Tests':
            case 'Assertions':
                $color = 'green';
                break;
        }
        $summaryString .= "<fg={$color}>{$key}: {$value}</>, ";
    }
}
$io->writeln('');
$io->writeln(rtrim($summaryString, ', '));

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
        
        // Generate Clover XML report
        $mergeCloverCommand = [
            $phpcovBin, 
            'merge', 
            $coveragePartialsDir,
            '--clover',
            $reportsDir . '/coverage.xml',
        ];

        $process = new Process($mergeCloverCommand, $baseDir, null, null, 300);
        $process->run();

        if ($process->isSuccessful()) {
            $io->writeln("Clover XML: {$reportsDir}/coverage.xml");
        } else {
            $io->warning("Failed to generate Clover XML report.");
            $io->writeln("Error: " . $process->getErrorOutput());
        }
        
        // Generate HTML report
        $io->text('Generating HTML coverage report...');
        $mergeHtmlCommand = [
            $phpcovBin, 
            'merge', 
            $coveragePartialsDir,
            '--html', 
            $finalCoverageDir,
        ];

        $process = new Process($mergeHtmlCommand, $baseDir, null, null, 300);
        $process->run();

        if ($process->isSuccessful()) {
            $io->success("Coverage reports generated successfully!");
            $io->writeln("HTML report: file://{$finalCoverageDir}/index.html");
            
            // Clean up partials
            array_map('unlink', glob("$coveragePartialsDir/*"));
            rmdir($coveragePartialsDir);
        } else {
            $io->error("Failed to merge HTML coverage reports.");
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