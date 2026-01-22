#!/usr/bin/env php
<?php

/**
 * FOLDER NAMING STANDARD CHECKER
 * Implementation of /docs/FOLDER_NAMING_PLAN.md
 */

$root = realpath(__DIR__ . '/..');
$issues = [];
$fixes = [];

echo "Scanning workspace in $root...\n\n";

// NOTE: Root 'Modules' folder is intentionally PascalCase per Laravel/nwidart-modules convention.
// This is an exception to the general lowercase folder naming rule.

// --- 1. Check 'tests/javascript' -> 'tests/JavaScript' ---
if (is_dir("$root/tests/javascript")) {
    $issues[] = "[CRITICAL] Directory 'tests/javascript' is lowercase (should be PascalCase 'tests/JavaScript').";
    $fixes[] = [
        'desc' => "Rename 'tests/javascript' to 'tests/JavaScript' and update vitest config",
        'action' => function() use ($root) {
            echo "-> Renaming tests/javascript to tests/JavaScript...\n";
            passthru("cd $root && git mv tests/javascript tests/JavaScript", $ret);
             if ($ret !== 0) {
                echo "[ERROR] git mv tests/javascript tests/JavaScript failed. Check if git is clean.\n";
                return;
            }

            // Update vitest.config.js
            echo "-> Inspecting vitest.config.js...\n";
            $vPath = "$root/vitest.config.js";
            if (file_exists($vPath)) {
                $c = file_get_contents($vPath);
                if (strpos($c, "tests/javascript") !== false) {
                    $c = str_replace("tests/javascript", "tests/JavaScript", $c);
                    file_put_contents($vPath, $c);
                    echo "-> Updated vitest.config.js\n";
                }
            }
        }
    ];
}

// --- 2. Check for Docs Collision ---
$dirs = scandir($root);
$hasDocs = in_array('docs', $dirs);
$hasUpperDocs = in_array('Docs', $dirs);
if ($hasDocs && $hasUpperDocs) {
    $issues[] = "[COLLISION] Both 'docs' and 'Docs' directories exist.";
    // No auto fix for collision, too risky
    echo "[WARN] Manual intervention required for 'docs'/'Docs' collision.\n";
}

// --- 3. Check for 'Resources' vs 'resources' collision in Modules ---
// Modules should use lowercase 'resources' folder for views/lang/assets
if (is_dir("$root/Modules")) {
    $modules = array_filter(scandir("$root/Modules"), fn($d) => $d !== '.' && $d !== '..' && is_dir("$root/Modules/$d"));
    foreach ($modules as $module) {
        $modulePath = "$root/Modules/$module";
        $hasResources = is_dir("$modulePath/Resources");
        $hasLowerResources = is_dir("$modulePath/resources");
        
        if ($hasResources && $hasLowerResources) {
            $issues[] = "[COLLISION] Module '$module' has both 'Resources' and 'resources' directories.";
            $fixes[] = [
                'desc' => "Remove empty 'Modules/$module/Resources' folder (views should be in lowercase 'resources')",
                'action' => function() use ($modulePath, $module) {
                    $resourcesPath = "$modulePath/Resources";
                    // Only remove if empty or contains only empty subdirectories
                    $isEmpty = true;
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($resourcesPath, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::CHILD_FIRST
                    );
                    foreach ($iterator as $file) {
                        if ($file->isFile()) {
                            $isEmpty = false;
                            break;
                        }
                    }
                    if ($isEmpty) {
                        echo "-> Removing empty 'Modules/$module/Resources' folder...\n";
                        passthru("rm -rf " . escapeshellarg($resourcesPath), $ret);
                        if ($ret === 0) {
                            echo "-> Removed successfully.\n";
                        } else {
                            echo "[ERROR] Failed to remove 'Modules/$module/Resources'.\n";
                        }
                    } else {
                        echo "[WARN] 'Modules/$module/Resources' contains files. Manual merge required.\n";
                    }
                }
            ];
        } elseif ($hasResources && !$hasLowerResources) {
            $issues[] = "[NAMING] Module '$module' uses 'Resources' instead of 'resources'.";
            echo "[INFO] Consider renaming 'Modules/$module/Resources' to 'resources' for consistency.\n";
        }
    }
}


// --- 4. Report and Prompt ---
if (empty($issues)) {
    echo "✅ No major directory naming issues found.\n";
    exit(0);
}

echo "⚠️  Found Issues:\n";
foreach ($issues as $i) {
    echo "  $i\n";
}

if (empty($fixes)) {
    echo "\nNo automatic fixes available.\n";
    exit(1);
}

echo "\nProposed Corrective Actions:\n";
foreach ($fixes as $i => $f) {
    echo "  " . ($i+1) . ". " . $f['desc'] . "\n";
}

echo "\nDo you want to apply these fixes now? [y/N] ";
$handle = fopen ("php://stdin","r");
$line = trim(fgets($handle));
if(strtolower($line) !== 'y'){
    echo "Aborted by user.\n";
    exit(0);
}

echo "\nApplying fixes...\n-------------------\n";
foreach ($fixes as $f) {
    $f['action']();
}
echo "-------------------\n";
echo "Done. Please check 'git status' and run 'composer dump-autoload'.\n";
