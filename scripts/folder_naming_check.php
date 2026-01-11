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

// --- 1. Check Root 'Modules' -> 'modules' ---
if (is_dir("$root/Modules")) {
    $issues[] = "[CRITICAL] Root directory 'Modules' is PascalCase (should be lowercase 'modules').";
    $fixes[] = [
        'desc' => "Rename 'Modules' to 'modules' and update configs",
        'action' => function() use ($root) {
            echo "-> Renaming Modules to modules...\n";
            passthru("cd $root && git mv Modules modules", $ret);
            if ($ret !== 0) {
                echo "[ERROR] git mv Modules modules failed. Check if git is clean.\n";
                return;
            }

            // Update composer.json
            echo "-> Updating composer.json...\n";
            $compPath = "$root/composer.json";
            if (file_exists($compPath)) {
                $c = file_get_contents($compPath);
                $c = str_replace('"Modules\\\\": "Modules/"', '"Modules\\\\": "modules/"', $c);
                file_put_contents($compPath, $c);
            }

            // Update phpstan.neon
            echo "-> Updating phpstan.neon...\n";
            $psPath = "$root/phpstan.neon";
            if (file_exists($psPath)) {
                $c = file_get_contents($psPath);
                $patterns = [
                    '/- Modules\b/', 
                    '/paths:\s*- Modules/'
                ];
                $c = str_replace('- Modules', '- modules', $c);
                file_put_contents($psPath, $c);
            }

            // Update config/modules.php
            echo "-> Updating config/modules.php...\n";
            $modConf = "$root/config/modules.php";
            if (file_exists($modConf)) {
                $c = file_get_contents($modConf);
                if (strpos($c, "'paths' => [") === false) {
                     echo "[WARN] Could not find 'paths' array in config/modules.php. Please manually verify.\n";
                }
                // Attempt to replace or inform
                if (strpos($c, "base_path('Modules')") !== false) {
                    $c = str_replace("base_path('Modules')", "base_path('modules')", $c);
                    file_put_contents($modConf, $c);
                } else {
                     echo "[INFO] No explicit base_path('Modules') found to replace. Ensure config uses 'modules' folder.\n";
                }
            }
        }
    ];
}

// --- 2. Check 'tests/javascript' -> 'tests/JavaScript' ---
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

// --- 3. Check for Docs Collision ---
$dirs = scandir($root);
$hasDocs = in_array('docs', $dirs);
$hasUpperDocs = in_array('Docs', $dirs);
if ($hasDocs && $hasUpperDocs) {
    $issues[] = "[COLLISION] Both 'docs' and 'Docs' directories exist.";
    // No auto fix for collision, too risky
    echo "[WARN] Manual intervention required for 'docs'/'Docs' collision.\n";
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
