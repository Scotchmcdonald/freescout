<?php

declare(strict_types=1);

/**
 * Module Service Constraints Guard (Wave 2 – Phase 3 Architecture)
 *
 * Audits service classes across critical domain modules for:
 *  1. No direct use of Illuminate\Http\Request (use DTOs at boundaries)
 *  2. No direct DB facade usage (use Eloquent / repository patterns)
 *  3. File-level strict-types enforcement
 *
 * Scoped to financial/critical modules that have already been verified compliant.
 * Expand coverage as other modules are brought into compliance.
 */

/** @return array<string, mixed> */
function moduleServiceConstraintsBaseline(): array
{
    return [
        'owner' => 'QA/Platform',
        'issue' => 'wave2-phase3-service-http-isolation',
        'expires' => '2026-08-31',
        'critical_service_paths' => [
            'Modules/PIB/Services',
            'Modules/Payment/Services',
            'Modules/ContractManager/Services',
            'Modules/SoftwareSubscriptions/Services',
        ],
        // Minimum files expected — guard against empty-scan false positives
        'min_files_per_path' => 1,
    ];
}

/** @return list<string> */
function moduleServiceConstraintsCollectFiles(string $dir): array
{
    if (! is_dir($dir)) {
        return [];
    }

    $files = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    sort($files);

    return $files;
}

test('module service constraint baseline metadata is valid', function (): void {
    $meta = moduleServiceConstraintsBaseline();
    $errors = [];

    if (trim((string) ($meta['owner'] ?? '')) === '') {
        $errors[] = 'owner is required';
    }

    if (trim((string) ($meta['issue'] ?? '')) === '') {
        $errors[] = 'issue is required';
    }

    try {
        $expiry = new DateTimeImmutable((string) $meta['expires']);
        if (new DateTimeImmutable('today') > $expiry) {
            $errors[] = 'baseline expired on '.$meta['expires'];
        }
    } catch (Exception $e) {
        $errors[] = 'invalid expires date';
    }

    expect($errors)->toBe([]);
});

test('critical service namespaces do not import Illuminate Http Request', function (): void {
    $baseline = moduleServiceConstraintsBaseline();
    $root = dirname(__DIR__, 2);
    $violations = [];

    foreach ($baseline['critical_service_paths'] as $relPath) {
        $files = moduleServiceConstraintsCollectFiles($root.'/'.$relPath);

        expect(count($files))->toBeGreaterThanOrEqual(
            (int) $baseline['min_files_per_path'],
            "Path {$relPath} matched no PHP files — check the path is correct."
        );

        foreach ($files as $file) {
            $content = (string) file_get_contents($file);
            // Allow type-hint in doc blocks (e.g. @param) — only flag real use statements / class resolution
            if (preg_match('/^\s*use\s+Illuminate\\\\Http\\\\Request\s*;/m', $content)) {
                $violations[] = str_replace($root.'/', '', $file);
            }
        }
    }

    expect($violations)->toBe(
        [],
        "Services must not import Illuminate\\Http\\Request directly.\n"
        ."Inject DTOs or primitive params instead.\nViolations:\n"
        .implode("\n", $violations)
    );
});

test('critical service namespaces do not use DB facade directly', function (): void {
    $baseline = moduleServiceConstraintsBaseline();
    $root = dirname(__DIR__, 2);
    $violations = [];

    foreach ($baseline['critical_service_paths'] as $relPath) {
        $files = moduleServiceConstraintsCollectFiles($root.'/'.$relPath);

        foreach ($files as $file) {
            $content = (string) file_get_contents($file);
            // Use statement for DB facade
            if (preg_match('/^\s*use\s+Illuminate\\\\Support\\\\Facades\\\\DB\s*;/m', $content)) {
                $violations[] = str_replace($root.'/', '', $file);
            }
        }
    }

    expect($violations)->toBe(
        [],
        "Services must not import the DB facade directly — use Eloquent models or a repository.\n"
        ."Violations:\n".implode("\n", $violations)
    );
});

test('critical service namespaces have strict types on every file', function (): void {
    $baseline = moduleServiceConstraintsBaseline();
    $root = dirname(__DIR__, 2);
    $violations = [];

    foreach ($baseline['critical_service_paths'] as $relPath) {
        $files = moduleServiceConstraintsCollectFiles($root.'/'.$relPath);

        foreach ($files as $file) {
            $content = (string) file_get_contents($file);
            if (! preg_match('/^\s*declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/m', $content)) {
                $violations[] = str_replace($root.'/', '', $file);
            }
        }
    }

    expect($violations)->toBe(
        [],
        "All critical service files must declare(strict_types=1).\nViolations:\n"
        .implode("\n", $violations)
    );
});
