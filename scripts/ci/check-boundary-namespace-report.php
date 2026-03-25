#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Boundary Coverage Report (Phase 4)
 *
 * Produces a per-namespace breakdown of validation / authorization / throttle
 * test coverage signals, plus lists files with zero boundary coverage so
 * developers know exactly where to add edge-case tests.
 *
 * Usage:
 *   php scripts/ci/check-boundary-namespace-report.php [--min-density=0.1] [--fail-on-empty]
 *
 * Flags:
 *   --min-density=N     Minimum boundary hits per file in each namespace (default 0, warning only)
 *   --fail-on-empty     Exit non-zero when any namespace has no boundary hits at all
 *
 * Output:
 *   reports/boundary-coverage-latest.md
 */

$root       = dirname(__DIR__, 2);
$testsDir   = $root . '/tests';
$reportsDir = $root . '/reports';
$reportFile = $reportsDir . '/boundary-coverage-latest.md';

// ── CLI args ───────────────────────────────────────────────────────────────
$minDensity  = 0.0;
$failOnEmpty = false;

foreach (array_slice($argv ?? [], 1) as $arg) {
    if (str_starts_with($arg, '--min-density=')) {
        $minDensity = (float) substr($arg, strlen('--min-density='));
    }
    if ($arg === '--fail-on-empty') {
        $failOnEmpty = true;
    }
}

// ── Boundary keywords ──────────────────────────────────────────────────────
$boundaryPattern = '/\b('
    . 'validation|validate|validated|'
    . 'authorize|authorization|403|'
    . 'throttle|429|rate.?limit(?:er)?|'
    . 'forbidden|unauthorized|unauthenticated|'
    . 'assertForbidden|assertUnauthorized|'
    . 'assertStatus\(\s*(?:403|422|429|401)\s*\)|'
    . 'assertJson.*errors'
    . ')\b/i';

// ── Scan ───────────────────────────────────────────────────────────────────
if (! is_dir($testsDir)) {
    fwrite(STDERR, "tests/ directory not found at $testsDir\n");
    exit(1);
}

/** @var array<string,array{files:list<string>,boundary_files:list<string>,hits:int,zero_files:list<string>}> $namespaces */
$namespaces = [];
$totalFiles = 0;
$totalHits  = 0;

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testsDir));
/** @var SplFileInfo $fileInfo */
foreach ($rii as $fileInfo) {
    if (! $fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
        continue;
    }

    $totalFiles++;
    $rel   = ltrim(str_replace($testsDir, '', $fileInfo->getPathname()), '/');
    $parts = explode('/', $rel);
    $ns    = count($parts) > 1 ? $parts[0] : 'root';

    // Deeper sub-namespace grouping (up to 2 levels)
    $subNs = $ns;
    if (count($parts) > 2) {
        $subNs = $parts[0] . '/' . $parts[1];
    }

    if (! isset($namespaces[$subNs])) {
        $namespaces[$subNs] = ['files' => [], 'boundary_files' => [], 'hits' => 0, 'zero_files' => []];
    }

    $content  = (string) file_get_contents($fileInfo->getPathname());
    $hitCount = preg_match_all($boundaryPattern, $content, $m) ?: 0;

    $namespaces[$subNs]['files'][] = $rel;
    $totalHits += $hitCount;

    if ($hitCount > 0) {
        $namespaces[$subNs]['hits'] += $hitCount;
        $namespaces[$subNs]['boundary_files'][] = $rel;
    } else {
        $namespaces[$subNs]['zero_files'][] = $rel;
    }
}

// Sort by namespace name for stable output
ksort($namespaces);

// ── Aggregate stats ────────────────────────────────────────────────────────
$emptyNamespaces = [];
foreach ($namespaces as $ns => $data) {
    $fileCount = count($data['files']);
    $density   = $fileCount > 0 ? $data['hits'] / $fileCount : 0.0;
    if ($data['hits'] === 0 || $density < $minDensity) {
        $emptyNamespaces[] = $ns;
    }
}

$allPass = ! ($failOnEmpty && count($emptyNamespaces) > 0);

// ── Report ─────────────────────────────────────────────────────────────────
if (! is_dir($reportsDir)) {
    mkdir($reportsDir, 0775, true);
}

$now   = date('c');
$lines = [];

$lines[] = '# Boundary Coverage Report';
$lines[] = '';
$lines[] = '> Generated: ' . $now;
$lines[] = '> Pattern: validation · authorization · throttle · 401/403/422/429';
$lines[] = '';
$lines[] = '## Summary';
$lines[] = '';
$lines[] = sprintf('- Total PHP test files scanned: **%d**', $totalFiles);
$lines[] = sprintf('- Total boundary keyword hits: **%d**', $totalHits);
$lines[] = sprintf('- Namespaces (sub-dirs): **%d**', count($namespaces));
$lines[] = sprintf('- Namespaces with zero boundary hits: **%d**', count($emptyNamespaces));
$lines[] = '';

$lines[] = '## Namespace Breakdown';
$lines[] = '';
$lines[] = '| Namespace | Files | Boundary Files | Total Hits | Density (hits/file) | Status |';
$lines[] = '| :--- | ---: | ---: | ---: | ---: | :---: |';

foreach ($namespaces as $ns => $data) {
    $fileCount = count($data['files']);
    $bFiles    = count($data['boundary_files']);
    $density   = $fileCount > 0 ? round($data['hits'] / $fileCount, 2) : 0.0;
    $ok        = $data['hits'] > 0 && $density >= $minDensity;
    $status    = $ok ? '✅' : '⚠️';
    $lines[]   = sprintf('| %s | %d | %d | %d | %.2f | %s |', $ns, $fileCount, $bFiles, $data['hits'], $density, $status);
}

$lines[] = '';
$lines[] = '## Zero-Hit Files (Boundary Gap Candidates)';
$lines[] = '';
$lines[] = 'Files with **no** boundary keyword matches—highest priority for new edge-case tests:';
$lines[] = '';

$zeroCap = 0;
foreach ($namespaces as $ns => $data) {
    if (count($data['zero_files']) === 0) {
        continue;
    }
    $lines[] = '### ' . $ns;
    $lines[] = '';
    foreach (array_slice($data['zero_files'], 0, 10) as $zf) {
        $lines[] = '- `' . $zf . '`';
        $zeroCap++;
        if ($zeroCap >= 60) {
            break 2;
        }
    }
    if (count($data['zero_files']) > 10) {
        $lines[] = sprintf('- _(and %d more)_', count($data['zero_files']) - 10);
    }
    $lines[] = '';
}

$lines[] = '## Result';
$lines[] = '';
$lines[] = $allPass ? '✅ PASS' : '❌ FAIL — namespaces with insufficient boundary coverage: ' . implode(', ', $emptyNamespaces);
$lines[] = '';

$report = implode(PHP_EOL, $lines);
file_put_contents($reportFile, $report);

echo $report;
echo PHP_EOL . 'Report saved to: reports/boundary-coverage-latest.md' . PHP_EOL;

if (! $allPass) {
    exit(1);
}

exit(0);
