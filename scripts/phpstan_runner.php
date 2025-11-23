<?php

/**
 * PHPStan Runner Script
 * 
 * Usage:
 * php scripts/phpstan_runner.php [command] [options]
 * 
 * Commands:
 *   analyse (default)   Run PHPStan analysis
 *   bodyscan            Run analysis across all levels (0-8) and report error counts
 *   baseline            Generate a new baseline file
 * 
 * Options:
 *   --level=X           Set the analysis level (default: from phpstan.neon or 5)
 *   --memory-limit=X    Set memory limit (default: 2G)
 *   --no-progress       Disable progress bar
 *   --debug             Show debug output
 */

$command = $argv[1] ?? 'analyse';
$options = parseOptions(array_slice($argv, 2));

// Default configuration
$memoryLimit = $options['memory-limit'] ?? '2G';
$level = $options['level'] ?? null; // If null, uses phpstan.neon config
$noProgress = isset($options['no-progress']);
$debug = isset($options['debug']);

echo "PHPStan Runner\n";
echo "==============\n";

switch ($command) {
    case 'bodyscan':
        runBodyscan($memoryLimit);
        break;
        
    case 'baseline':
        generateBaseline($memoryLimit);
        break;
        
    case 'analyse':
    default:
        runAnalysis($level, $memoryLimit, $noProgress);
        break;
}

function parseOptions(array $args): array {
    $options = [];
    foreach ($args as $arg) {
        if (str_starts_with($arg, '--')) {
            $parts = explode('=', substr($arg, 2), 2);
            $key = $parts[0];
            $value = $parts[1] ?? true;
            $options[$key] = $value;
        }
    }
    return $options;
}

function runAnalysis(?string $level, string $memoryLimit, bool $noProgress): void {
    $cmd = "vendor/bin/phpstan analyse --memory-limit={$memoryLimit}";
    
    if ($level !== null) {
        $cmd .= " --level={$level}";
    }
    
    if ($noProgress) {
        $cmd .= " --no-progress";
    }
    
    echo "Running analysis...\n";
    echo "Command: $cmd\n\n";
    
    passthru($cmd, $returnVar);
    
    if ($returnVar !== 0) {
        echo "\nAnalysis failed with errors.\n";
        exit($returnVar);
    }
    
    echo "\nAnalysis passed!\n";
}

function runBodyscan(string $memoryLimit): void {
    echo "Running Bodyscan (Levels 0-9)...\n\n";
    
    $results = [];
    $totalErrors = 0;
    
    for ($i = 0; $i <= 9; $i++) {
        echo "Checking Level $i... ";
        
        // Run with error format json to parse easily, or just count lines of output
        // Using --no-progress to keep output clean
        $cmd = "vendor/bin/phpstan analyse --level={$i} --memory-limit={$memoryLimit} --no-progress --error-format=json";
        
        exec($cmd, $output, $returnVar);
        $rawOutput = implode("\n", $output);
        
        // Find the start of JSON
        $jsonStart = strpos($rawOutput, '{');
        if ($jsonStart !== false) {
            $jsonOutput = substr($rawOutput, $jsonStart);
            $data = json_decode($jsonOutput, true);
        } else {
            $data = null;
        }
        
        $errorCount = 0;
        if (is_array($data)) {
            if (isset($data['totals']['file_errors'])) {
                $errorCount = $data['totals']['file_errors'];
            } elseif (isset($data['totals']['errors'])) {
                $errorCount = $data['totals']['errors'];
            } elseif (isset($data['files'])) {
                 // Fallback if totals not present
                 foreach ($data['files'] as $file) {
                     $errorCount += $file['errors'];
                 }
            }
        } else {
            echo "Failed to parse JSON output.\n";
            if (isset($options['debug'])) {
                echo "Raw output:\n$rawOutput\n";
            }
            $output = [];
            continue;
        }
        
        $results[$i] = $errorCount;
        echo "$errorCount errors\n";
        
        // Clear output for next iteration
        $output = [];
    }
    
    echo "\nBodyscan Results:\n";
    echo "-----------------\n";
    foreach ($results as $level => $count) {
        printf("Level %d: %d errors\n", $level, $count);
    }
    
    echo "\nNote: Higher levels include errors from lower levels.\n";
}

function generateBaseline(string $memoryLimit): void {
    echo "Generating Baseline...\n";
    
    $cmd = "vendor/bin/phpstan analyse --memory-limit={$memoryLimit} --generate-baseline";
    
    echo "Command: $cmd\n\n";
    
    passthru($cmd, $returnVar);
    
    if ($returnVar === 0) {
        echo "\nBaseline generated successfully!\n";
    } else {
        echo "\nFailed to generate baseline.\n";
        exit($returnVar);
    }
}
