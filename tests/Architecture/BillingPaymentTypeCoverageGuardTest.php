<?php

declare(strict_types=1);

/**
 * Phase 4 Wave 2: Type-Coverage Guard for Critical Billing/Payment Domain.
 *
 * This guard enforces a quantifiable type-safety threshold in the most critical
 * financial service namespaces.
 */

/** @return array<string, mixed> */
function phase4Wave2TypeCoverageBaseline(): array
{
    return [
        'owner' => 'QA/Platform',
        'issue' => 'phase-4-architecture-and-type-coverage-wave-2',
        'expires' => '2026-08-31',
        // Critical domain group — extended in Wave 2 to include ContractManager and SoftwareSubscriptions
        'target_paths' => [
            'Modules/PIB/Services',
            'Modules/Payment/Services',
            'Modules/ContractManager/Services',
            'Modules/SoftwareSubscriptions/Services',
        ],
        // Require strict-types coverage to stay at 100% for this critical domain.
        'min_strict_types_percent' => 100.0,
        'min_files_scanned' => 12,
    ];
}

/** @return array<int, string> */
function phase4Wave2CollectPhpFiles(array $relativeDirs): array
{
    $root = dirname(__DIR__, 2);
    $files = [];

    foreach ($relativeDirs as $relativeDir) {
        $dir = $root.'/'.trim($relativeDir, '/');
        if (! is_dir($dir)) {
            continue;
        }

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($it as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
    }

    sort($files);

    return $files;
}

test('phase 4 wave 2 type baseline metadata is valid', function (): void {
    $meta = phase4Wave2TypeCoverageBaseline();
    $errors = [];

    if (trim((string) $meta['owner']) === '' || trim((string) $meta['issue']) === '' || trim((string) $meta['expires']) === '') {
        $errors[] = 'phase4Wave2TypeCoverageBaseline requires owner, issue, and expires.';
    }

    if ((float) $meta['min_strict_types_percent'] < 0 || (float) $meta['min_strict_types_percent'] > 100) {
        $errors[] = 'min_strict_types_percent must be between 0 and 100.';
    }

    if ((int) $meta['min_files_scanned'] < 1) {
        $errors[] = 'min_files_scanned must be >= 1.';
    }

    try {
        $expiry = new DateTimeImmutable((string) $meta['expires']);
        $today = new DateTimeImmutable('today');
        if ($today > $expiry) {
            $errors[] = "phase4Wave2TypeCoverageBaseline expired on {$meta['expires']}";
        }
    } catch (Exception $e) {
        $errors[] = "phase4Wave2TypeCoverageBaseline has invalid expires date {$meta['expires']}";
    }

    expect($errors)->toBe([]);
});

test('critical billing payment services maintain strict types coverage threshold', function (): void {
    $baseline = phase4Wave2TypeCoverageBaseline();
    $paths = $baseline['target_paths'];

    $files = phase4Wave2CollectPhpFiles($paths);

    expect(count($files))->toBeGreaterThanOrEqual(
        (int) $baseline['min_files_scanned'],
        'Critical domain scan matched fewer files than expected; verify target_paths.'
    );

    $strictFiles = [];
    $nonStrictFiles = [];

    foreach ($files as $file) {
        $contents = (string) file_get_contents($file);
        if (preg_match('/^\s*declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/m', $contents) === 1) {
            $strictFiles[] = $file;
        } else {
            $nonStrictFiles[] = $file;
        }
    }

    $strictPercent = count($files) > 0
        ? round((count($strictFiles) / count($files)) * 100, 2)
        : 100.0;

    expect($strictPercent)->toBeGreaterThanOrEqual(
        (float) $baseline['min_strict_types_percent'],
        "Strict-types coverage dropped below threshold.\n".
        'Strict files: '.count($strictFiles).' / '.count($files)." ({$strictPercent}%).\n".
        "Missing strict_types:\n".implode("\n", $nonStrictFiles)
    );
});
