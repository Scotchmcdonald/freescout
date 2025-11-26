<?php

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

// --- SIGNAL HANDLING ---
$currentProcess = null;

if (function_exists('pcntl_async_signals') && function_exists('pcntl_signal')) {
    pcntl_async_signals(true);
    
    $signalHandler = function ($signo) use (&$currentProcess) {
        // We can't use $io here easily as it might not be initialized or in scope depending on where we are
        // So we use basic echo
        echo "\n\nScript interrupted. Cleaning up...\n";
        
        if ($currentProcess instanceof Process && $currentProcess->isRunning()) {
            echo "Stopping active process...\n";
            $currentProcess->stop(3, SIGKILL);
        }
        
        exit(130);
    };
    
    pcntl_signal(SIGINT, $signalHandler);
    pcntl_signal(SIGTERM, $signalHandler);
}

// --- INITIALIZATION ---
$output = new ConsoleOutput();
$baseDir = realpath(__DIR__.'/..');
$configFile = $baseDir . '/tests/runner_config.json';

// --- ARGUMENT PARSING ---
$calibrate = false;
$defaultTarget = 20.0;
$targetSeconds = $defaultTarget;
$targetSpecified = false;
$withCoverage = false;
$filterPattern = null;

foreach ($_SERVER['argv'] as $key => $value) {
    if ($value === '--calibrate') {
        $calibrate = true;
        unset($_SERVER['argv'][$key]);
    } elseif (strpos($value, '--target=') === 0) {
        $targetSeconds = (float)substr($value, 9);
        $targetSpecified = true;
        unset($_SERVER['argv'][$key]);
    } elseif ($value === '--coverage') {
        $withCoverage = true;
        unset($_SERVER['argv'][$key]);
    } elseif (strpos($value, '--filter=') === 0) {
        $filterPattern = substr($value, 9);
        unset($_SERVER['argv'][$key]);
    }
}
$_SERVER['argv'] = array_values($_SERVER['argv']);

// Initialize IO after argument cleanup so ArgvInput sees the clean arguments
$io = new SymfonyStyle(new ArgvInput(), $output);

// --- CONFIG LOADING ---
$config = [
    'target_seconds' => $targetSeconds, // Start with default or CLI value
    'file_times' => [],
    'batches' => []
];

if (file_exists($configFile)) {
    $loadedConfig = json_decode(file_get_contents($configFile), true);
    if (is_array($loadedConfig)) {
        $config = array_merge($config, $loadedConfig);
    }
} else {
    $calibrate = true; // Force calibration if no config
}

// If user explicitly specified a target, it overrides the loaded config
if ($targetSpecified) {
    $config['target_seconds'] = $targetSeconds;
}

if ($calibrate) {
    $io->title("Calibrating Test Runner (Target: {$config['target_seconds']}s)");
} else {
    $io->title("Smart Test Runner (Target: {$config['target_seconds']}s)");
}

// --- PERMISSIONS FIX ---
$io->section('Checking Permissions');
$needsFix = false;

// Check root ownership
$stat = stat($baseDir);
$owner = posix_getpwuid($stat['uid'])['name'];
$group = posix_getgrgid($stat['gid'])['name'];

if ($owner !== 'www-data' || $group !== 'www-data') {
    $needsFix = true;
    $io->text("Root directory owner/group is $owner:$group (expected www-data:www-data).");
}

// Check writability of critical directories
$criticalPaths = ['/storage', '/bootstrap/cache'];
foreach ($criticalPaths as $path) {
    if (!is_writable($baseDir . $path)) {
        $needsFix = true;
        $io->text("Directory $path is not writable.");
    }
}

if ($needsFix) {
    $io->text('Fixing permissions...');
    $commands = [
        "sudo chown -R www-data:www-data $baseDir",
        "sudo chmod -R 755 $baseDir",
        "sudo setfacl -R -m u:dev:rwx $baseDir"
    ];
    foreach ($commands as $cmd) {
        passthru($cmd, $returnVar);
    }
    $io->success('Permissions fixed.');
} else {
    $io->success('Permissions appear correct. Skipping recursive fix.');
}

// --- CACHE CLEARING ---
$io->section('Clearing Cache');
$process = new Process(['php', 'artisan', 'optimize:clear'], $baseDir);
$currentProcess = $process;
$process->run();
$currentProcess = null;

if ($process->isSuccessful()) {
    $io->success('Cache cleared.');
} else {
    $io->warning('Failed to clear cache: ' . $process->getErrorOutput());
}

// --- TEST DISCOVERY ---
$io->section('Discovering Test Files');
$finder = new Finder();
$finder->files()
    ->in($baseDir . '/tests')
    ->name('*Test.php')
    ->notPath('Browser') // Exclude Browser tests
    ->sortByName();
$allFiles = [];
foreach ($finder as $file) {
    $allFiles[] = $file->getRealPath();
}
$io->text("Found " . count($allFiles) . " test files.");

// --- REPORTS INIT ---
$reportsDir = $baseDir.'/reports/test_runs_'.date('Y-m-d_His');
if (!is_dir($reportsDir)) {
    mkdir($reportsDir, 0777, true);
}

// --- HELPER: LOGGING ---
function logBatchResults($output, $reportsDir, $batchId) {
    // Extract Failures
    if (preg_match_all('/There (?:was|were) \d+ failure(?:s)?:\s*(.*?)(?=\n(?:There (?:was|were) \d+ (?:error|risky|skipped|incomplete)|FAILURES!|ERRORS!|OK)|$)/s', $output, $matches)) {
        foreach ($matches[1] as $match) {
            file_put_contents("{$reportsDir}/failure.log", "Batch {$batchId} Failures:\n" . trim($match) . "\n\n" . str_repeat('-', 40) . "\n\n", FILE_APPEND);
        }
    }

    // Extract Errors
    if (preg_match_all('/There (?:was|were) \d+ error(?:s)?:\s*(.*?)(?=\n(?:There (?:was|were) \d+ (?:failure|risky|skipped|incomplete)|FAILURES!|ERRORS!|OK)|$)/s', $output, $matches)) {
        foreach ($matches[1] as $match) {
            file_put_contents("{$reportsDir}/error.log", "Batch {$batchId} Errors:\n" . trim($match) . "\n\n" . str_repeat('-', 40) . "\n\n", FILE_APPEND);
        }
    }

    // Extract Pre-Test Errors (PHPUnit 11)
    if (preg_match_all('/These before-first-test methods errored:\s*(.*?)(?=\n(?:FAILURES!|ERRORS!|OK)|$)/s', $output, $matches)) {
        foreach ($matches[1] as $match) {
            file_put_contents("{$reportsDir}/error.log", "Batch {$batchId} Pre-Test Errors:\n" . trim($match) . "\n\n" . str_repeat('-', 40) . "\n\n", FILE_APPEND);
        }
    }

    // Extract Skipped
    if (preg_match_all('/There (?:was|were) \d+ skipped test(?:s)?:\s*(.*?)(?=\n(?:There (?:was|were) \d+ (?:error|failure|risky|incomplete)|FAILURES!|ERRORS!|OK)|$)/s', $output, $matches)) {
        foreach ($matches[1] as $match) {
            file_put_contents("{$reportsDir}/skipped.log", "Batch {$batchId} Skipped:\n" . trim($match) . "\n\n" . str_repeat('-', 40) . "\n\n", FILE_APPEND);
        }
    }

    // Extract Incomplete
    if (preg_match_all('/There (?:was|were) \d+ incomplete test(?:s)?:\s*(.*?)(?=\n(?:There (?:was|were) \d+ (?:error|failure|risky|skipped)|FAILURES!|ERRORS!|OK)|$)/s', $output, $matches)) {
        foreach ($matches[1] as $match) {
            file_put_contents("{$reportsDir}/incomplete.log", "Batch {$batchId} Incomplete:\n" . trim($match) . "\n\n" . str_repeat('-', 40) . "\n\n", FILE_APPEND);
        }
    }

    // Extract Risky
    if (preg_match_all('/There (?:was|were) \d+ risky test(?:s)?:\s*(.*?)(?=\n(?:There (?:was|were) \d+ (?:error|failure|skipped|incomplete)|FAILURES!|ERRORS!|OK)|$)/s', $output, $matches)) {
        foreach ($matches[1] as $match) {
            file_put_contents("{$reportsDir}/risky.log", "Batch {$batchId} Risky:\n" . trim($match) . "\n\n" . str_repeat('-', 40) . "\n\n", FILE_APPEND);
        }
    }

    // Extract PHPUnit Warnings
    if (preg_match_all('/There (?:was|were) (\d+) PHPUnit test runner warning(?:s)?:\s*(.*?)(?=\n(?:There (?:was|were)|OK, but|FAILURES!|ERRORS!|Tests:)|$)/s', $output, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            file_put_contents("{$reportsDir}/warnings.log", "Batch {$batchId} - PHPUnit Warnings ({$match[1]}):\n" . trim($match[2]) . "\n\n" . str_repeat('-', 40) . "\n\n", FILE_APPEND);
        }
    }

    // Extract Deprecations
    if (preg_match_all('/Deprecation\s+Triggered[^\n]*\n.*?(?=\n\n|There (?:was|were)|$)/s', $output, $matches)) {
        foreach ($matches[0] as $deprecation) {
            file_put_contents("{$reportsDir}/deprecation.log", "Batch {$batchId}:\n" . trim($deprecation) . "\n\n" . str_repeat('-', 40) . "\n\n", FILE_APPEND);
        }
    }
    
    // TestDox Fallback (for symbols)
    $testDoxPatterns = [
        '✘' => ['file' => 'failure.log'],
        '⚠' => ['file' => 'risky.log'],
        '↩' => ['file' => 'skipped.log'],
        '∅' => ['file' => 'incomplete.log'],
    ];

    foreach ($testDoxPatterns as $symbol => $config) {
        $pattern = '/^\s*' . $symbol . '\s+([^\n]+)\n((?:\s+│.*?\n)+)/ms';
        if (preg_match_all($pattern, $output, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $testName = trim($match[1]);
                $details = trim($match[2]);
                $targetLog = $config['file'];
                
                if ($symbol === '✘' && (str_contains($details, 'Error') || str_contains($details, 'Exception'))) {
                    $targetLog = 'error.log';
                }
                
                file_put_contents("{$reportsDir}/{$targetLog}", "Batch {$batchId} - {$testName}\n{$details}\n" . str_repeat('-', 40) . "\n\n", FILE_APPEND);
            }
        }
    }
}

// --- HELPER: UPDATE PROGRESS BAR ---
function updateProgressBar($progressBar, $runningStats, $totalFiles, $currentStep, $message = '') {
    $barWidth = 30;
    $progressRatio = min(1, $currentStep / $totalFiles);
    $filledChars = (int)round($barWidth * $progressRatio);
    $emptyChars = $barWidth - $filledChars;
    
    $barStr = "";
    
    if (($runningStats['Tests'] > 0 || $runningStats['TimedOut'] > 0) && $filledChars > 0) {
        $counts = [
            'Pass' => $runningStats['Tests'] - $runningStats['Failures'] - $runningStats['Errors'] - $runningStats['Skipped'] - $runningStats['Incomplete'],
            'Fail' => $runningStats['Failures'],
            'Err' => $runningStats['Errors'],
            'Skip' => $runningStats['Skipped'],
            'Inc' => $runningStats['Incomplete'],
            'Time' => $runningStats['TimedOut']
        ];
        
        $totalForBar = array_sum($counts);
        if ($totalForBar == 0) $totalForBar = 1;

        // Filter out zero counts
        $activeCounts = array_filter($counts, function($c) { return $c > 0; });
        $numActive = count($activeCounts);
        
        $widths = array_fill_keys(array_keys($counts), 0);
        
        if ($numActive > 0) {
            if ($filledChars < $numActive) {
                // Not enough space for all. Prioritize largest counts.
                arsort($activeCounts);
                $topTypes = array_slice(array_keys($activeCounts), 0, $filledChars);
                foreach ($topTypes as $type) {
                    $widths[$type] = 1;
                }
            } else {
                // Enough space. Give 1 to each active type.
                foreach (array_keys($activeCounts) as $type) {
                    $widths[$type] = 1;
                }
                
                $remainingChars = $filledChars - $numActive;
                if ($remainingChars > 0) {
                    // Distribute remaining proportionally
                    $totalActive = array_sum($activeCounts);
                    $fractionalWidths = [];
                    
                    foreach ($activeCounts as $type => $count) {
                        $fractionalWidths[$type] = ($count / $totalActive) * $remainingChars;
                    }
                    
                    // Round and adjust
                    $roundedExtras = array_map('round', $fractionalWidths);
                    $sumExtras = array_sum($roundedExtras);
                    $diff = $remainingChars - $sumExtras;
                    
                    // Adjust largest of the extras
                    arsort($activeCounts); // Sort by count to find largest
                    $largestType = array_key_first($activeCounts);
                    $roundedExtras[$largestType] += $diff;
                    
                    foreach ($roundedExtras as $type => $extra) {
                        $widths[$type] += $extra;
                    }
                }
            }
        }
        
        $barStr .= "<fg=green>" . str_repeat("▓", max(0, (int)$widths['Pass'])) . "</>";
        $barStr .= "<fg=#FFA500>" . str_repeat("▓", max(0, (int)$widths['Fail'])) . "</>";
        $barStr .= "<fg=red>" . str_repeat("▓", max(0, (int)$widths['Err'])) . "</>";
        $barStr .= "<fg=blue>" . str_repeat("▓", max(0, (int)$widths['Skip'])) . "</>";
        $barStr .= "<fg=yellow>" . str_repeat("▓", max(0, (int)$widths['Inc'])) . "</>";
        $barStr .= "<fg=magenta>" . str_repeat("▓", max(0, (int)$widths['Time'])) . "</>";
    } else {
        if ($filledChars > 0) {
             $barStr .= "<fg=gray>" . str_repeat("▓", $filledChars) . "</>";
        }
    }
    $barStr .= "<fg=gray>" . str_repeat("░", max(0, (int)$emptyChars)) . "</>";

    $progressBar->setMessage($barStr, 'custom_bar');

    $statsMsg = sprintf(
        "<fg=green>Pass: %d</> | <fg=#FFA500>Fail: %d</> | <fg=red>Err: %d</> | <fg=blue>Skip: %d</> | <fg=yellow>Inc: %d</> | <fg=magenta>T/O: %d</>",
        $runningStats['Tests'] - $runningStats['Failures'] - $runningStats['Errors'] - $runningStats['Skipped'] - $runningStats['Incomplete'],
        $runningStats['Failures'],
        $runningStats['Errors'],
        $runningStats['Skipped'],
        $runningStats['Incomplete'],
        $runningStats['TimedOut']
    );
    
    if ($message === '') {
        $progressBar->setMessage(" " . $statsMsg);
    } else {
        $progressBar->setMessage($message . "\n " . $statsMsg);
    }
}

// --- CALIBRATION LOGIC ---
if ($calibrate) {
    $io->section('Running Calibration (Individual Files)');
    $config['file_times'] = [];
    $config['batches'] = [];
    $config['target_seconds'] = $targetSeconds;

    $progressBar = $io->createProgressBar(count($allFiles));
    $progressBar->setFormat(" %current%/%max% [%custom_bar%] %percent:3s%% | Elapsed: %elapsed:6s% | ETA: %remaining:-6s% | Mem: %memory:6s%\n %message%");
    $progressBar->setMessage('', 'custom_bar');
    $progressBar->setMessage('Starting calibration...');
    $progressBar->start();

    $runningStats = ['Tests' => 0, 'Assertions' => 0, 'Errors' => 0, 'Failures' => 0, 'Skipped' => 0, 'Incomplete' => 0, 'TimedOut' => 0];
    $allResultsOutput = '';

    foreach ($allFiles as $index => $file) {
        $result = runBatch([$file], $io, $baseDir, $reportsDir, $runningStats, $allResultsOutput, $targetSeconds);
        
        if (isset($result['duration'])) {
            $config['file_times'][$file] = $result['duration'];
        } else {
            $config['file_times'][$file] = 0.1; // Fallback
        }
        
        updateProgressBar($progressBar, $runningStats, count($allFiles), $index + 1, basename($file));
        $progressBar->advance();
    }
    updateProgressBar($progressBar, $runningStats, count($allFiles), count($allFiles), '');
    $progressBar->finish();
    $io->newLine();
    
    // Generate Initial Batches
    $io->section('Generating Batches');
    $batches = [];
    $currentBatch = [];
    $currentBatchTime = 0;
    
    foreach ($allFiles as $file) {
        $time = $config['file_times'][$file] ?? 0.1;
        
        // If single file is larger than target, it must be its own batch
        if ($time > $targetSeconds) {
            if (!empty($currentBatch)) {
                $batches[] = $currentBatch;
                $currentBatch = [];
                $currentBatchTime = 0;
            }
            $batches[] = [$file];
            continue;
        }
        
        if ($currentBatchTime + $time > $targetSeconds + 1.0) { // Tolerance
            $batches[] = $currentBatch;
            $currentBatch = [];
            $currentBatchTime = 0;
        }
        
        $currentBatch[] = $file;
        $currentBatchTime += $time;
    }
    if (!empty($currentBatch)) {
        $batches[] = $currentBatch;
    }
    
    $config['batches'] = $batches;
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    $io->success("Calibration complete. Config saved to runner_config.json");
    
    $io->section('Created Files');
    $io->writeln(" - tests/" . basename($configFile));
    $io->writeln(" - reports/" . basename($reportsDir) . "/");
    foreach(glob($reportsDir . '/*.log') as $log) {
        $io->writeln("   - " . basename($log));
    }
    
    exit(0);
}

// --- NORMAL RUN LOGIC ---

// 1. Identify New Files
$knownFiles = [];
foreach ($config['batches'] as $batch) {
    foreach ($batch as $file) {
        $knownFiles[] = $file;
    }
}
$newFiles = array_diff($allFiles, $knownFiles);

// 2. Prepare Batches
$batchesToRun = [];
foreach ($config['batches'] as $batch) {
    $validBatch = array_intersect($batch, $allFiles);
    if (!empty($validBatch)) {
        $batchesToRun[] = array_values($validBatch);
    }
}

// Add new files as individual batches for now
foreach ($newFiles as $file) {
    $batchesToRun[] = [$file];
}

// 3. Execution
$io->section('Running Batches');
$progressBar = $io->createProgressBar(count($batchesToRun));
$progressBar->setFormat(" %current%/%max% [%custom_bar%] %percent:3s%% | Elapsed: %elapsed:6s% | ETA: %remaining:-6s% | Mem: %memory:6s%\n %message%");
$progressBar->setMessage('', 'custom_bar');
$progressBar->setMessage('Starting batches...');
$progressBar->start();

$runningStats = ['Tests' => 0, 'Assertions' => 0, 'Errors' => 0, 'Failures' => 0, 'Skipped' => 0, 'Incomplete' => 0, 'TimedOut' => 0];
$allResultsOutput = '';

// Recursive function to run a batch
function runBatch($files, $io, $baseDir, $reportsDir, &$runningStats, &$allResultsOutput, $targetSeconds, $depth = 0) {
    // Calculate timeout: 5 * target, but at least 10s
    $timeout = max(10, $targetSeconds * 5);
    
    $junitFile = $reportsDir . '/batch_' . md5(implode('', $files) . microtime()) . '.xml';
    $command = array_merge([$baseDir.'/vendor/bin/phpunit', '--testdox', '--log-junit', $junitFile], $files);
    
    $process = new Process($command, $baseDir, null, null, $timeout);
    
    global $currentProcess;
    $currentProcess = $process;
    
    try {
        $startTime = microtime(true);
        $process->run();
        $currentProcess = null;
        
        $duration = microtime(true) - $startTime;
        
        $output = $process->getOutput();
        $errorOutput = $process->getErrorOutput();
        $allResultsOutput .= $output . PHP_EOL;
        
        // Log detailed results
        $batchId = ($depth > 0 ? "Split-{$depth}-" : "") . substr(md5(implode('', $files)), 0, 8);
        logBatchResults($output, $reportsDir, $batchId);
        
        // Log Stderr
        if (!empty($errorOutput)) {
            $isWarning = false;
            if (preg_match('/(?:PHP )?(?:Warning|Deprecated|Notice):/i', $errorOutput) && !preg_match('/(?:PHP )?(?:Fatal|Parse) error:/i', $errorOutput)) {
                $isWarning = true;
            }
            if ($isWarning) {
                file_put_contents("{$reportsDir}/warnings.log", "Batch {$batchId} STDERR (Warnings):\n" . $errorOutput . "\n\n" . str_repeat('-', 40) . "\n\n", FILE_APPEND);
            } else {
                file_put_contents("{$reportsDir}/error.log", "Batch {$batchId} STDERR:\n" . $errorOutput . "\n\n" . str_repeat('-', 40) . "\n\n", FILE_APPEND);
            }
        }
        
        // Parse stats
        $batchHasError = false;
        if (preg_match_all('/(Tests:.*)/', $output, $matches)) {
            foreach ($matches[1] as $line) {
                preg_match_all('/(Tests|Assertions|Errors|Failures|Risky|Skipped|Incomplete|PHPUnit Warnings): (\d+)/', $line, $statMatches, PREG_SET_ORDER);
                foreach ($statMatches as $match) {
                    if (isset($runningStats[$match[1]])) {
                        $runningStats[$match[1]] += (int)$match[2];
                    }
                    if (($match[1] === 'Errors' || $match[1] === 'Failures') && (int)$match[2] > 0) {
                        $batchHasError = true;
                    }
                }
            }
        }

        if ($batchHasError) {
            file_put_contents("{$reportsDir}/batch_{$batchId}_full_output.log", $output);
        }

        if (preg_match_all('/OK \((\d+) tests?, (\d+) assertions?\)/', $output, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $runningStats['Tests'] += (int)$match[1];
                $runningStats['Assertions'] += (int)$match[2];
            }
        }
        
        // Parse JUnit XML for file times
        $updatedTimes = [];
        if (file_exists($junitFile)) {
            $xml = @simplexml_load_file($junitFile);
            if ($xml) {
                foreach ($xml->xpath('//testsuite[@file]') as $suite) {
                    $file = (string)$suite['file'];
                    $time = (float)$suite['time'];
                    $updatedTimes[$file] = $time;
                }
            }
            @unlink($junitFile);
        }
        
        // Return duration and success
        return ['success' => true, 'duration' => $duration, 'files' => $files, 'updated_times' => $updatedTimes];
        
    } catch (ProcessTimedOutException $e) {
        $runningStats['TimedOut']++;
        $msg = "Batch timed out (Depth $depth). Splitting...";
        file_put_contents("{$reportsDir}/timeout.log", $msg . "\nFiles:\n" . implode("\n", $files) . "\n\n", FILE_APPEND);
        
        // Split batch
        if (count($files) <= 1) {
            // Cannot split further
            $msg = "Single file timed out: " . $files[0];
            file_put_contents("{$reportsDir}/timeout.log", $msg . "\n\n", FILE_APPEND);
            return ['success' => false, 'files' => $files];
        }
        
        $chunks = array_chunk($files, ceil(count($files) / 2));
        $results = [];
        foreach ($chunks as $chunk) {
            $results[] = runBatch($chunk, $io, $baseDir, $reportsDir, $runningStats, $allResultsOutput, $targetSeconds, $depth + 1);
        }
        
        // Flatten results to return the new batch structure
        $newBatches = [];
        $mergedUpdatedTimes = [];
        
        foreach ($results as $res) {
            if (isset($res['new_batches'])) {
                foreach ($res['new_batches'] as $b) $newBatches[] = $b;
            } else {
                $newBatches[] = $res; // It was a single run result
            }
            
            if (isset($res['updated_times'])) {
                $mergedUpdatedTimes = array_merge($mergedUpdatedTimes, $res['updated_times']);
            }
        }
        return ['success' => true, 'new_batches' => $newBatches, 'updated_times' => $mergedUpdatedTimes];
    }
}

$finalBatches = [];
$batchRuntimes = [];

foreach ($batchesToRun as $index => $batch) {
    $result = runBatch($batch, $io, $baseDir, $reportsDir, $runningStats, $allResultsOutput, $config['target_seconds']);
    
    if (isset($result['updated_times'])) {
        foreach ($result['updated_times'] as $file => $time) {
            $config['file_times'][$file] = $time;
        }
    }
    
    if (isset($result['new_batches'])) {
        // The batch was split
        foreach ($result['new_batches'] as $b) {
            if (isset($b['files'])) {
                $finalBatches[] = $b['files'];
                if (isset($b['duration'])) {
                    $batchRuntimes[] = $b['duration'];
                }
            }
        }
    } else {
        $finalBatches[] = $batch;
        if (isset($result['duration'])) {
            $batchRuntimes[] = $result['duration'];
        }
    }
    updateProgressBar($progressBar, $runningStats, count($batchesToRun), $index + 1, "Batch " . ($index + 1));
    $progressBar->advance();
}
updateProgressBar($progressBar, $runningStats, count($batchesToRun), count($batchesToRun), '');
$progressBar->finish();
$io->newLine();

// --- REBALANCING ---
$io->section('Rebalancing Batches');
$newConfigBatches = [];
$currentBatch = [];
$currentBatchTime = 0;

// Reconstruct batches using updated file times
// This merges small batches and respects duration-based splits
// It does NOT explicitly prevent merging files that split due to interaction,
// but it's the best "estimate" approach as requested.

$allFilesFlat = [];
foreach ($finalBatches as $batch) {
    foreach ($batch as $file) {
        $allFilesFlat[] = $file;
    }
}
// Ensure we don't lose any files (though finalBatches should have them all)
$allFilesFlat = array_unique($allFilesFlat);

// Sort files? No, keep existing order to minimize context switching churn, 
// or maybe sort by directory? Let's keep the order they came out in $finalBatches
// which preserves the split order.

foreach ($allFilesFlat as $file) {
    $time = $config['file_times'][$file] ?? 0.1;
    
    // If single file is larger than target, it must be its own batch
    if ($time > $config['target_seconds']) {
        if (!empty($currentBatch)) {
            $newConfigBatches[] = $currentBatch;
            $currentBatch = [];
            $currentBatchTime = 0;
        }
        $newConfigBatches[] = [$file];
        continue;
    }
    
    if ($currentBatchTime + $time > $config['target_seconds'] + 1.0) { // Tolerance
        $newConfigBatches[] = $currentBatch;
        $currentBatch = [];
        $currentBatchTime = 0;
    }
    
    $currentBatch[] = $file;
    $currentBatchTime += $time;
}
if (!empty($currentBatch)) {
    $newConfigBatches[] = $currentBatch;
}

$originalBatchesJson = json_encode($config['batches']);
$newBatchesJson = json_encode($newConfigBatches);

$config['batches'] = $newConfigBatches;
file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));

if ($originalBatchesJson !== $newBatchesJson) {
    $io->success("Recalibration complete. Config saved to runner_config.json");
} else {
    $io->success("Run complete.");
}

// --- CREATED FILES ---
$io->section('Created Files');
$io->writeln(" - tests/" . basename($configFile));
$io->writeln(" - reports/" . basename($reportsDir) . "/");
foreach(glob($reportsDir . '/*.log') as $log) {
    $io->writeln("   - " . basename($log));
}

// --- SUMMARY ---
$io->section('Status');
$io->writeln("Tests: {$runningStats['Tests']}");
$io->writeln("Failures: {$runningStats['Failures']}");
$io->writeln("Errors: {$runningStats['Errors']}");
$io->writeln("Timeouts: {$runningStats['TimedOut']}");

if ($runningStats['Failures'] > 0 || $runningStats['Errors'] > 0) {
    exit(1);
}
exit(0);
