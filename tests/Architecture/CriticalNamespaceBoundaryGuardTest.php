<?php

declare(strict_types=1);

/**
 * Phase 4 Wave 1: Critical Namespace Boundary Guard
 *
 * This guard freezes current architectural debt in critical namespaces and
 * prevents new coupling from being introduced while remediation continues.
 */

/** @return array<string, mixed> */
function phase4Wave1BoundaryBaseline(): array
{
    return [
        'owner' => 'QA/Platform',
        'issue' => 'phase-4-architecture-and-type-coverage-wave-1',
        'expires' => '2026-05-31',
        // App HTTP controllers importing module models (legacy compatibility points)
        'app_controllers_module_model_imports_max' => 5,
        // App HTTP controllers importing module services (integration gateways)
        'app_controllers_module_service_imports_max' => 4,
        // App services importing module models should remain zero
        'app_services_module_model_imports_max' => 0,
        // Module services importing app controllers should remain zero
        'module_services_app_controller_imports_max' => 0,
    ];
}

/** @return array<int, string> */
function collectUniqueUseImports(string $relativeDir, string $usePattern): array
{
    $root = dirname(__DIR__, 2);
    $dir = $root.'/'.trim($relativeDir, '/');

    if (! is_dir($dir)) {
        return [];
    }

    $matches = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    foreach ($it as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $contents = (string) file_get_contents($file->getPathname());

        if (preg_match_all($usePattern, $contents, $hits)) {
            foreach ($hits[0] as $import) {
                $matches[$import] = true;
            }
        }
    }

    $imports = array_keys($matches);
    sort($imports);

    return $imports;
}

test('phase 4 wave 1 baseline metadata is valid', function (): void {
    $meta = phase4Wave1BoundaryBaseline();
    $errors = [];

    if (trim((string) $meta['owner']) === '' || trim((string) $meta['issue']) === '' || trim((string) $meta['expires']) === '') {
        $errors[] = 'phase4Wave1BoundaryBaseline requires owner, issue, and expires.';
    }

    $countKeys = [
        'app_controllers_module_model_imports_max',
        'app_controllers_module_service_imports_max',
        'app_services_module_model_imports_max',
        'module_services_app_controller_imports_max',
    ];

    foreach ($countKeys as $key) {
        if (! isset($meta[$key]) || (int) $meta[$key] < 0) {
            $errors[] = "phase4Wave1BoundaryBaseline {$key} must be >= 0.";
        }
    }

    try {
        $expiry = new DateTimeImmutable((string) $meta['expires']);
        $today = new DateTimeImmutable('today');
        if ($today > $expiry) {
            $errors[] = "phase4Wave1BoundaryBaseline expired on {$meta['expires']}";
        }
    } catch (Exception $e) {
        $errors[] = "phase4Wave1BoundaryBaseline has invalid expires date {$meta['expires']}";
    }

    expect($errors)->toBe([]);
});

test('app controllers do not increase module model imports', function (): void {
    $baseline = phase4Wave1BoundaryBaseline();

    $imports = collectUniqueUseImports(
        'app/Http/Controllers',
        '~^\s*use\s+Modules\\\\[^;]+\\\\Models\\\\[^;]+;~m'
    );

    expect(count($imports))->toBeLessThanOrEqual(
        (int) $baseline['app_controllers_module_model_imports_max'],
        "App controllers module model imports increased above baseline.\n".implode("\n", $imports)
    );
});

test('app controllers do not increase module service imports', function (): void {
    $baseline = phase4Wave1BoundaryBaseline();

    $imports = collectUniqueUseImports(
        'app/Http/Controllers',
        '~^\s*use\s+Modules\\\\[^;]+\\\\Services\\\\[^;]+;~m'
    );

    expect(count($imports))->toBeLessThanOrEqual(
        (int) $baseline['app_controllers_module_service_imports_max'],
        "App controllers module service imports increased above baseline.\n".implode("\n", $imports)
    );
});

test('app services keep zero module model imports', function (): void {
    $baseline = phase4Wave1BoundaryBaseline();

    $imports = collectUniqueUseImports(
        'app/Services',
        '~^\s*use\s+Modules\\\\[^;]+\\\\Models\\\\[^;]+;~m'
    );

    expect(count($imports))->toBeLessThanOrEqual(
        (int) $baseline['app_services_module_model_imports_max'],
        "App services imported module models.\n".implode("\n", $imports)
    );
});

test('module services keep zero app controller imports', function (): void {
    $baseline = phase4Wave1BoundaryBaseline();

    $root = dirname(__DIR__, 2);
    $matches = [];

    foreach (glob($root.'/Modules/*/Services', GLOB_ONLYDIR) ?: [] as $serviceDir) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($serviceDir));

        foreach ($it as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = (string) file_get_contents($file->getPathname());
            if (preg_match_all('~^\s*use\s+App\\\\Http\\\\Controllers\\\\[^;]+;~m', $contents, $hits)) {
                foreach ($hits[0] as $import) {
                    $matches[$import] = true;
                }
            }
        }
    }

    $imports = array_keys($matches);
    sort($imports);

    expect(count($imports))->toBeLessThanOrEqual(
        (int) $baseline['module_services_app_controller_imports_max'],
        "Module services imported app controllers.\n".implode("\n", $imports)
    );
});
