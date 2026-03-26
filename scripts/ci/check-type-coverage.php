#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Type Declaration Density Audit.
 *
 * Scans app/ and Modules/ for PHP class files and measures what percentage of
 * public + protected method signatures carry:
 *   - Explicit return type declarations
 *   - Explicit parameter type declarations
 *
 * This is a signal of type-safety health independent of PHPStan's correctness
 * checks. High density means new contributors are guided by types, refactoring
 * is safer, and IDE tooling is more effective.
 *
 * Reports to:
 *   reports/type-coverage-latest.txt
 *   reports/type-coverage-summary.json
 *
 * Threshold:  TYPE_COVERAGE_MIN env var, default 80%
 * Exit code:  0 = pass, 1 = below threshold
 */

$root       = dirname(__DIR__, 2);
$reportsDir = $root . '/reports';
$minPct     = (float) (getenv('TYPE_COVERAGE_MIN') ?: '80');

$scanDirs = [
    $root . '/app',
    $root . '/Modules',
];

// ── Scan ──────────────────────────────────────────────────────────────────
$totalMethods    = 0;
$typedMethods    = 0;
$totalParams     = 0;
$typedParams     = 0;
$namespaceStats  = [];
$violations      = [];

foreach ($scanDirs as $dir) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        /** @var SplFileInfo $file */
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $filePath = $file->getPathname();
        $code     = (string) file_get_contents($filePath);

        // Derive a short namespace key from path (e.g. "app/Services" or "Modules/Payment/Services")
        $relPath   = ltrim(str_replace($root, '', $filePath), '/');
        $parts     = explode('/', $relPath);
        $nsKey     = implode('/', array_slice($parts, 0, min(3, count($parts) - 1)));

        try {
            $tokens = token_get_all($code, TOKEN_PARSE);
        } catch (ParseError $e) {
            continue; // skip unparseable files
        }

        $inClassBody = 0; // brace depth when class body starts
        $classDepth  = 0;
        $braceDepth  = 0;
        $inClass     = false;

        $methodsInFile = analyzeMethodSignatures($code);

        foreach ($methodsInFile as $method) {
            $totalMethods++;
            $hasReturn = $method['hasReturn'];
            if ($hasReturn) {
                $typedMethods++;
            }

            foreach ($method['params'] as $param) {
                $totalParams++;
                if ($param['hasType']) {
                    $typedParams++;
                } else {
                    $violations[] = [
                        'file'   => $relPath,
                        'method' => $method['name'],
                        'param'  => $param['name'],
                    ];
                }
            }

            if (! $hasReturn && $method['name'] !== '__construct' && $method['name'] !== '__destruct') {
                $violations[] = [
                    'file'   => $relPath,
                    'method' => $method['name'],
                    'param'  => '(return type)',
                ];
            }
        }

        if ($methodsInFile !== []) {
            $namespaceStats[$nsKey] = $namespaceStats[$nsKey] ?? ['methods' => 0, 'typed' => 0, 'params' => 0, 'typedParams' => 0];
            $namespaceStats[$nsKey]['methods']     += count($methodsInFile);
            $namespaceStats[$nsKey]['typed']       += array_sum(array_column($methodsInFile, 'hasReturn')) === 1 ? 1 : 0;
            $namespaceStats[$nsKey]['params']      += array_sum(array_map(fn ($m) => count($m['params']), $methodsInFile));
            $namespaceStats[$nsKey]['typedParams'] += array_sum(array_map(fn ($m) => array_sum(array_column($m['params'], 'hasType')), $methodsInFile));
        }
    }
}

// ── Score ─────────────────────────────────────────────────────────────────
// Overall score: ratio of (typed return + typed param) over (total return slots + total param slots)
$totalSlots = $totalMethods + $totalParams;
$typedSlots = $typedMethods + $typedParams;
$pct        = $totalSlots > 0 ? round($typedSlots / $totalSlots * 100, 2) : 0.0;

$pass = $pct >= $minPct;

// ── Report ─────────────────────────────────────────────────────────────────
$lines   = [];
$lines[] = '# Type Declaration Density Report';
$lines[] = '';
$lines[] = sprintf('> Generated: %s', date('c'));
$lines[] = '';
$lines[] = '## Summary';
$lines[] = '';
$lines[] = sprintf('| Metric | Value |');
$lines[] = '| :--- | ---: |';
$lines[] = sprintf('| Methods scanned | %d |', $totalMethods);
$lines[] = sprintf('| Methods with return type | %d |', $typedMethods);
$lines[] = sprintf('| Parameters scanned | %d |', $totalParams);
$lines[] = sprintf('| Parameters with type | %d |', $typedParams);
$lines[] = sprintf('| **Overall type coverage** | **%.1f%%** |', $pct);
$lines[] = sprintf('| Minimum required | %.1f%% |', $minPct);
$lines[] = sprintf('| Status | %s |', $pass ? '✅ PASS' : '❌ FAIL');
$lines[] = '';

// Top violations (up to 20)
if ($violations !== []) {
    $lines[] = '## Top Missing Type Declarations (first 20)';
    $lines[] = '';
    $lines[] = '| File | Method | Missing |';
    $lines[] = '| :--- | :--- | :--- |';
    foreach (array_slice($violations, 0, 20) as $v) {
        $lines[] = sprintf('| %s | `%s` | `%s` |', $v['file'], $v['method'], $v['param']);
    }
    $lines[] = '';
}

$lines[] = sprintf('## Result: %s', $pass ? '✅ PASS' : '❌ FAIL');

$report = implode(PHP_EOL, $lines);
@mkdir($reportsDir, 0755, true);
file_put_contents($reportsDir . '/type-coverage-latest.txt', $report);

// Machine-readable
file_put_contents($reportsDir . '/type-coverage-summary.json', json_encode([
    'type_coverage'     => $pct,
    'minimum'           => $minPct,
    'total_methods'     => $totalMethods,
    'typed_methods'     => $typedMethods,
    'total_params'      => $totalParams,
    'typed_params'      => $typedParams,
    'generated_at'      => date('c'),
], JSON_PRETTY_PRINT));

echo $report . PHP_EOL;
echo 'Report saved → reports/type-coverage-latest.txt' . PHP_EOL;

if (! $pass) {
    fprintf(STDERR, "❌ Type coverage %.1f%% is below the %.1f%% minimum.\n", $pct, $minPct);
    exit(1);
}

exit(0);

// ── Helpers ───────────────────────────────────────────────────────────────

/**
 * Analyze public/protected method signatures in a PHP source string.
 *
 * Returns an array of:
 *   [ 'name' => string, 'hasReturn' => bool, 'params' => [['name'=>string,'hasType'=>bool]] ]
 *
 * @return list<array{name:string,hasReturn:bool,params:list<array{name:string,hasType:bool}>}>
 */
function analyzeMethodSignatures(string $code): array
{
    $methods = [];

    // Match public/protected function declarations (not abstract interfaces)
    // Pattern: optional(public|protected) [static] function name(...)[ : type]
    $pattern = '/(?:(?:public|protected)\s+)?(?:static\s+)?function\s+(\w+)\s*\(([^)]*)\)\s*(?::\s*[\w\\\\|?<>]+\s*)?(?:\{|;)/';

    if (preg_match_all($pattern, $code, $matches, PREG_SET_ORDER) === false) {
        return [];
    }

    foreach ($matches as $match) {
        $name      = $match[1];
        $paramStr  = trim($match[2]);
        $fullSig   = $match[0];

        // Detect return type: presence of ": type" between ) and { or ;
        $hasReturn = (bool) preg_match('/\)\s*:\s*[\w\\\\|?]/', $fullSig);

        $params = [];
        if ($paramStr !== '') {
            foreach (explode(',', $paramStr) as $param) {
                $param = trim($param);
                if ($param === '') {
                    continue;
                }
                // A typed param looks like: "Type $var" or "?Type $var" or "Type|Other $var"
                // An untyped one is just "$var" or "...$var"
                $hasType = (bool) preg_match('/^[?\\\\]?[a-zA-Z]/', $param);
                // Extract the variable name
                preg_match('/\$(\w+)/', $param, $vm);
                $paramName = $vm[1] ?? $param;
                $params[]  = ['name' => $paramName, 'hasType' => $hasType];
            }
        }

        $methods[] = [
            'name'      => $name,
            'hasReturn' => $hasReturn,
            'params'    => $params,
        ];
    }

    return $methods;
}
