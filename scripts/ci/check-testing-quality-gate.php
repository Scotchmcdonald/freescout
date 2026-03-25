#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Consolidated testing quality gate.
 *
 * Checks (all configurable via env vars):
 *  1. Line coverage         — reads coverage-final.txt (text) or coverage XML
 *  2. Mutation MSI          — reads infection-extended-summary.json / infection-summary.json / .log
 *  3. Boundary inventory    — validation/auth/throttle keyword density in tests/
 *  4. Architecture health   — counts arch-test files present in tests/Architecture/
 *
 * Writes report to reports/testing-quality-gate-latest.md.
 * Exits non-zero if any mandatory check fails.
 */

const DEFAULT_MIN_COVERAGE     = 70.0;
const DEFAULT_MIN_MSI          = 40.0;
const DEFAULT_MIN_BOUNDARY     = 50;
const DEFAULT_MIN_ARCH_FILES   = 3;

$root        = dirname(__DIR__, 2);
$reportsDir  = $root . '/reports';
$reportFile  = $reportsDir . '/testing-quality-gate-latest.md';

// ── Inputs ─────────────────────────────────────────────────────────────────
$minCoverage   = envFloat('TEST_MIN_COVERAGE',           DEFAULT_MIN_COVERAGE);
$minMsi        = envFloat('TEST_MIN_MSI',                DEFAULT_MIN_MSI);
$minBoundary   = envInt('TEST_MIN_BOUNDARY_MATCHES',     DEFAULT_MIN_BOUNDARY);
$minArchFiles  = envInt('TEST_MIN_ARCH_FILES',           DEFAULT_MIN_ARCH_FILES);

// Optional timing context injected by the CI orchestrator
$phaseTimes = [
    'tests'    => envFloat('TIMING_TESTS_S',    0.0),
    'coverage' => envFloat('TIMING_COVERAGE_S', 0.0),
    'mutation' => envFloat('TIMING_MUTATION_S', 0.0),
];

// ── Metric Collection ──────────────────────────────────────────────────────
$coverage = parseCoverage($reportsDir);
$msi      = parseMsi($reportsDir);
$boundary = boundaryInventory($root . '/tests');
$arch     = archInventory($root . '/tests/Architecture');

// ── Gate Checks ────────────────────────────────────────────────────────────
$checks = [
    [
        'name'    => 'Line coverage',
        'actual'  => $coverage['value'],
        'minimum' => $minCoverage,
        'unit'    => '%',
        'status'  => $coverage['value'] !== null && $coverage['value'] >= $minCoverage,
        'detail'  => $coverage['value'] === null
            ? 'No coverage artifact found. Run: XDEBUG_MODE=coverage php artisan test'
            : 'source: ' . $coverage['source'],
    ],
    [
        'name'    => 'Mutation MSI',
        'actual'  => $msi['value'],
        'minimum' => $minMsi,
        'unit'    => '%',
        'status'  => $msi['value'] !== null && $msi['value'] >= $minMsi,
        'detail'  => $msi['value'] === null
            ? 'No mutation artifact found. Run: bash scripts/ci/check-mutation-tier2.sh'
            : 'source: ' . $msi['source'],
    ],
    [
        'name'    => 'Boundary inventory',
        'actual'  => (float) $boundary['matches'],
        'minimum' => (float) $minBoundary,
        'unit'    => ' hits',
        'status'  => $boundary['matches'] >= $minBoundary,
        'detail'  => 'validation/auth/throttle keywords across ' . $boundary['files'] . ' test files',
    ],
    [
        'name'    => 'Architecture test files',
        'actual'  => (float) $arch['files'],
        'minimum' => (float) $minArchFiles,
        'unit'    => ' files',
        'status'  => $arch['files'] >= $minArchFiles,
        'detail'  => 'PHP arch-test files in tests/Architecture/',
    ],
];

$allPass = true;
foreach ($checks as $c) {
    if (! $c['status']) {
        $allPass = false;
        break;
    }
}

// ── Report ─────────────────────────────────────────────────────────────────
if (! is_dir($reportsDir)) {
    mkdir($reportsDir, 0775, true);
}

$now   = date('c');
$lines = [];

$lines[] = '# Testing Quality Gate';
$lines[] = '';
$lines[] = '> Generated: ' . $now;
$lines[] = '';
$lines[] = '## KPI Checks';
$lines[] = '';
$lines[] = '| Check | Actual | Minimum | Status | Notes |';
$lines[] = '| :--- | ---: | ---: | :---: | :--- |';

foreach ($checks as $c) {
    $actual  = $c['actual'] === null ? 'n/a' : number_format($c['actual'], 2) . $c['unit'];
    $minimum = number_format($c['minimum'], 2) . $c['unit'];
    $status  = $c['status'] ? '✅ PASS' : '❌ FAIL';
    $lines[] = sprintf('| %s | %s | %s | %s | %s |', $c['name'], $actual, $minimum, $status, $c['detail']);
}

// ── Velocity section ───────────────────────────────────────────────────────
$lines[] = '';
$lines[] = '## Velocity (Phase Timing)';
$lines[] = '';
$lines[] = '| Phase | Time (s) | Budget | Status |';
$lines[] = '| :--- | ---: | ---: | :---: |';

$budgets = ['tests' => 150, 'coverage' => 600, 'mutation' => 3000];
foreach ($phaseTimes as $phase => $elapsed) {
    if ($elapsed <= 0) {
        $lines[] = sprintf('| %s | n/a | %ds | — |', $phase, $budgets[$phase]);
        continue;
    }
    $ok      = $elapsed <= $budgets[$phase];
    $status  = $ok ? '✅' : '⚠️ over budget';
    $lines[] = sprintf('| %s | %.1f | %ds | %s |', $phase, $elapsed, $budgets[$phase], $status);
}

// ── Architecture section ───────────────────────────────────────────────────
$lines[] = '';
$lines[] = '## Architecture Test Inventory';
$lines[] = '';
$lines[] = 'Files in `tests/Architecture/`: **' . $arch['files'] . '**';
$lines[] = '';
foreach ($arch['list'] as $f) {
    $lines[] = '- ' . $f;
}

// ── Boundary section ───────────────────────────────────────────────────────
$lines[] = '';
$lines[] = '## Boundary Coverage Snapshot';
$lines[] = '';
$lines[] = '| Namespace | Files | Boundary Hits |';
$lines[] = '| :--- | ---: | ---: |';
foreach ($boundary['namespaces'] as $ns => $data) {
    $lines[] = sprintf('| %s | %d | %d |', $ns, $data['files'], $data['hits']);
}

// ── Result banner ──────────────────────────────────────────────────────────
$lines[] = '';
$lines[] = '## Result';
$lines[] = '';
$lines[] = $allPass ? '### ✅ PASS' : '### ❌ FAIL — one or more checks below minimum threshold';
$lines[] = '';

$report = implode(PHP_EOL, $lines);
file_put_contents($reportFile, $report);

echo $report;
echo PHP_EOL . 'Report saved to: reports/testing-quality-gate-latest.md' . PHP_EOL;

if (! $allPass) {
    exit(1);
}

exit(0);

// ═══════════════════════════════════════════════════════════════════════════
// Helper functions
// ═══════════════════════════════════════════════════════════════════════════

function envFloat(string $key, float $default): float
{
    $v = getenv($key);
    return ($v !== false && trim($v) !== '') ? (float) $v : $default;
}

function envInt(string $key, int $default): int
{
    $v = getenv($key);
    return ($v !== false && trim($v) !== '') ? (int) $v : $default;
}

/**
 * @return array{value:float|null,source:string}
 */
function parseCoverage(string $reportsDir): array
{
    // 1. JSON from Pest/PHPUnit Coverage HTML summary
    foreach (['coverage-summary.json', 'clover.json'] as $f) {
        $path = $reportsDir . '/' . $f;
        if (! is_file($path)) {
            continue;
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (isset($data['totals']['lines']['percent'])) {
            return ['value' => (float) $data['totals']['lines']['percent'], 'source' => $f];
        }
    }

    // 2. Plain text coverage report
    $path = $reportsDir . '/coverage-final.txt';
    if (is_file($path)) {
        $content = (string) file_get_contents($path);
        if (preg_match('/Lines:\s*([0-9]+(?:\.[0-9]+)?)%/', $content, $m)) {
            return ['value' => (float) $m[1], 'source' => 'coverage-final.txt'];
        }
    }

    // 3. Clover XML
    $path = $reportsDir . '/clover.xml';
    if (is_file($path)) {
        $xml = @simplexml_load_file($path);
        if ($xml !== false) {
            $metrics = $xml->project->metrics;
            if ($metrics !== null) {
                $stmts    = (int) $metrics['statements'];
                $covered  = (int) $metrics['coveredstatements'];
                if ($stmts > 0) {
                    return ['value' => round($covered / $stmts * 100, 2), 'source' => 'clover.xml'];
                }
            }
        }
    }

    return ['value' => null, 'source' => 'none'];
}

/**
 * @return array{value:float|null,source:string}
 */
function parseMsi(string $reportsDir): array
{
    // 1. Preferred: JSON summary files written by Infection
    foreach (['infection-extended-summary.json', 'infection-summary.json'] as $f) {
        $path = $reportsDir . '/' . $f;
        if (! is_file($path)) {
            continue;
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (isset($data['stats']['msi'])) {
            return ['value' => (float) $data['stats']['msi'], 'source' => $f];
        }
    }

    // 2. Fallback: text log
    foreach (['infection-extended-summary.log', 'infection-summary.log', 'infection.log'] as $f) {
        $path = $reportsDir . '/' . $f;
        if (! is_file($path)) {
            continue;
        }
        $content = (string) file_get_contents($path);
        if (preg_match('/MSI\s*[:\(]\s*([0-9]+(?:\.[0-9]+)?)/i', $content, $m)) {
            return ['value' => (float) $m[1], 'source' => $f];
        }
    }

    return ['value' => null, 'source' => 'none'];
}

/**
 * @return array{files:int,matches:int,namespaces:array<string,array{files:int,hits:int}>}
 */
function boundaryInventory(string $testsDir): array
{
    if (! is_dir($testsDir)) {
        return ['files' => 0, 'matches' => 0, 'namespaces' => []];
    }

    $pattern   = '/\b(validation|authorize|authorization|throttle|rate\s*limit|rate[_-]?limiter|403|422|429)\b/i';
    $totalFiles = 0;
    $totalHits  = 0;

    // Top-level sub-dirs == namespaces for grouping
    $nsBuckets  = [];

    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testsDir));
    /** @var SplFileInfo $file */
    foreach ($rii as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $totalFiles++;
        $content = (string) file_get_contents($file->getPathname());
        $hits    = preg_match_all($pattern, $content, $m) ?: 0;
        $totalHits += $hits;

        // Determine namespace bucket from path relative to $testsDir
        $rel   = ltrim(str_replace($testsDir, '', $file->getPathname()), '/');
        $parts = explode('/', $rel);
        $ns    = count($parts) > 1 ? $parts[0] : 'root';

        if (! isset($nsBuckets[$ns])) {
            $nsBuckets[$ns] = ['files' => 0, 'hits' => 0];
        }
        $nsBuckets[$ns]['files']++;
        $nsBuckets[$ns]['hits'] += $hits;
    }

    // Sort by hits descending
    uasort($nsBuckets, fn($a, $b) => $b['hits'] <=> $a['hits']);

    return ['files' => $totalFiles, 'matches' => $totalHits, 'namespaces' => $nsBuckets];
}

/**
 * @return array{files:int,list:list<string>}
 */
function archInventory(string $archDir): array
{
    if (! is_dir($archDir)) {
        return ['files' => 0, 'list' => []];
    }

    $list = [];
    $rii  = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($archDir));
    foreach ($rii as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $list[] = basename($file->getPathname());
        }
    }
    sort($list);

    return ['files' => count($list), 'list' => $list];
}
