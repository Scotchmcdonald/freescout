<?php

declare(strict_types=1);

function moduleLayerFiles(string $module): array
{
    $rootPath = dirname(__DIR__, 3);
    $roots = [
        "{$rootPath}/Modules/{$module}/Http/Controllers",
        "{$rootPath}/Modules/{$module}/Listeners",
        "{$rootPath}/Modules/{$module}/Jobs",
    ];

    $files = [];

    foreach ($roots as $root) {
        if (! is_dir($root)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $files[] = $file->getPathname();
        }
    }

    return $files;
}

function assertNoForbiddenServiceImports(string $module, array $forbiddenModuleServices): void
{
    $rootPrefix = dirname(__DIR__, 3).'/';
    $violations = [];

    foreach (moduleLayerFiles($module) as $file) {
        $contents = (string) file_get_contents($file);

        foreach ($forbiddenModuleServices as $forbiddenModule) {
            $pattern = '/^\s*use\s+Modules\\\\'.preg_quote($forbiddenModule, '/').'\\\\Services\\\\/m';
            if (preg_match($pattern, $contents) === 1) {
                $violations[] = str_replace($rootPrefix, '', $file);
                break;
            }
        }
    }

    expect($violations)->toBe([]);
}

test('ContractManager forbidden foreign service imports in controllers listeners jobs', function () {
    assertNoForbiddenServiceImports('ContractManager', ['GoogleAdmin', 'Action1', 'SoftwareSubscriptions']);
});

test('GoogleAdmin forbidden foreign service imports in controllers listeners jobs', function () {
    assertNoForbiddenServiceImports('GoogleAdmin', ['ContractManager', 'Action1', 'SoftwareSubscriptions']);
});

test('Action1 forbidden foreign service imports in controllers listeners jobs', function () {
    assertNoForbiddenServiceImports('Action1', ['ContractManager', 'GoogleAdmin', 'SoftwareSubscriptions']);
});

test('SoftwareSubscriptions forbidden foreign service imports in controllers listeners jobs', function () {
    assertNoForbiddenServiceImports('SoftwareSubscriptions', ['ContractManager', 'GoogleAdmin', 'Action1']);
});

test('forbidden layers do not instantiate foreign module services directly', function () {
    $rootPrefix = dirname(__DIR__, 3).'/';
    $modules = ['ContractManager', 'GoogleAdmin', 'Action1', 'SoftwareSubscriptions'];
    $violations = [];

    foreach ($modules as $module) {
        foreach (moduleLayerFiles($module) as $file) {
            $contents = (string) file_get_contents($file);

            if (preg_match('/new\s+\\?Modules\\\\(?!'.preg_quote($module, '/').')[A-Za-z0-9_]+\\\\Services\\\\[A-Za-z0-9_]+/m', $contents) === 1) {
                $violations[] = str_replace($rootPrefix, '', $file);
            }
        }
    }

    expect($violations)->toBe([]);
});

test('cross module service resolution via app make is not used in forbidden layers', function () {
    $rootPrefix = dirname(__DIR__, 3).'/';
    $modules = ['ContractManager', 'GoogleAdmin', 'Action1', 'SoftwareSubscriptions'];
    $violations = [];

    foreach ($modules as $module) {
        foreach (moduleLayerFiles($module) as $file) {
            $contents = (string) file_get_contents($file);

            if (preg_match('/app\(\s*\\?Modules\\\\(?!'.preg_quote($module, '/').')[A-Za-z0-9_]+\\\\Services\\\\[A-Za-z0-9_]+::class\s*\)/m', $contents) === 1) {
                $violations[] = str_replace($rootPrefix, '', $file);
            }
        }
    }

    expect($violations)->toBe([]);
});

test('GoogleAdmin user provider implements core user provider contract', function () {
    expect(Modules\GoogleAdmin\Services\GoogleUserProvider::class)
        ->toImplement(App\Contracts\UserProvider::class);
});

test('ContractManager billing template implements billing template contract', function () {
    expect(Modules\ContractManager\Models\BillingTemplate::class)
        ->toImplement(App\Contracts\BillingTemplateInterface::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// B-1 EXPANSION: Interface compliance enforcement for module service contracts
// ─────────────────────────────────────────────────────────────────────────────

test('Action1SyncService implements Action1SyncClient interface', function () {
    expect(Modules\Action1\Services\Action1SyncService::class)
        ->toImplement(Modules\Action1\Contracts\Action1SyncClient::class);
});

test('Action1RunService implements Action1RunClient interface', function () {
    expect(Modules\Action1\Services\Action1RunService::class)
        ->toImplement(Modules\Action1\Contracts\Action1RunClient::class);
});

test('Action1ManageService implements Action1ManageClient interface', function () {
    expect(Modules\Action1\Services\Action1ManageService::class)
        ->toImplement(Modules\Action1\Contracts\Action1ManageClient::class);
});

test('SoftwareSubscriptions AssetManagementAssetLookupProvider implements AssetLookupProvider', function () {
    expect(Modules\SoftwareSubscriptions\Services\AssetManagementAssetLookupProvider::class)
        ->toImplement(Modules\SoftwareSubscriptions\Contracts\AssetLookupProvider::class);
});

test('SoftwareSubscriptions CrmContactLookupProvider implements ContactLookupProvider', function () {
    expect(Modules\SoftwareSubscriptions\Services\CrmContactLookupProvider::class)
        ->toImplement(Modules\SoftwareSubscriptions\Contracts\ContactLookupProvider::class);
});

test('SoftwareSubscriptions CrmAssignableEmailLookupProvider implements AssignableEmailLookupProvider', function () {
    expect(Modules\SoftwareSubscriptions\Services\CrmAssignableEmailLookupProvider::class)
        ->toImplement(Modules\SoftwareSubscriptions\Contracts\AssignableEmailLookupProvider::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// B-1 EXPANSION: Extended cross-module service boundary enforcement
// Verifies no module in scope directly imports concrete services from financial
// or payment boundary modules via the forbidden-layers (controllers/listeners/jobs).
// ─────────────────────────────────────────────────────────────────────────────

test('ContractManager forbidden PIB and Payment concrete service imports in forbidden layers', function () {
    assertNoForbiddenServiceImports('ContractManager', ['PIB', 'Payment']);
});

test('SoftwareSubscriptions forbidden PIB Payment and CaseManager concrete service imports in forbidden layers', function () {
    assertNoForbiddenServiceImports('SoftwareSubscriptions', ['PIB', 'Payment', 'CaseManager']);
});

// ─────────────────────────────────────────────────────────────────────────────
// Phase 6 Rule 1: Services must not directly instantiate foreign module repositories
// ─────────────────────────────────────────────────────────────────────────────

test('module services do not directly instantiate foreign module repositories', function () {
    $violations = [];
    $moduleServiceDirs = glob(base_path('Modules/*/Services'), GLOB_ONLYDIR);

    foreach ($moduleServiceDirs as $serviceDir) {
        preg_match('#Modules/([^/]+)/Services#', $serviceDir, $m);
        $ownerModule = $m[1];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($serviceDir)) as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $contents = (string) file_get_contents($file->getPathname());
            if (preg_match_all('/new\s+\\\\?Modules\\\\([A-Za-z]+)\\\\Repositories\\\\/', $contents, $hits)) {
                foreach ($hits[1] as $foreignModule) {
                    if ($foreignModule !== $ownerModule) {
                        $relativePath = str_replace(base_path().'/', '', $file->getPathname());
                        $violations[] = "{$relativePath} instantiates Modules\\{$foreignModule}\\Repositories directly";
                    }
                }
            }
        }
    }

    expect($violations)->toBe([]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Phase 6 Rule 6D: Contract interface coverage assertion
// For every interface in app/Contracts/ implemented by a module service,
// the module's service provider MUST bind that interface in the container.
// ─────────────────────────────────────────────────────────────────────────────

test('module services implementing core contracts are bound in their service providers', function () {
    $projectRoot = dirname(__DIR__, 3);
    $contractDir = $projectRoot.'/app/Contracts';

    // Pre-existing gaps: services that implement a core contract but the provider
    // registers them inline (e.g., via a registry) instead of a container binding.
    // Shrink this list as providers are refactored to use proper $app->bind().
    $allowlist = [
        'Modules/GoogleAdmin/Services/GoogleUserProvider.php' => 'App\Contracts\UserProvider',
    ];

    // Collect all interface FQCNs from app/Contracts (recursive)
    $contractInterfaces = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($contractDir));
    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $contents = (string) file_get_contents($file->getPathname());
        if (preg_match('/namespace\s+([^;]+);/', $contents, $nsMatch)
            && preg_match('/interface\s+(\w+)/', $contents, $classMatch)) {
            $fqcn = $nsMatch[1].'\\'.$classMatch[1];
            $contractInterfaces[$fqcn] = true;
        }
    }

    $violations = [];

    foreach (glob($projectRoot.'/Modules/*/Services', GLOB_ONLYDIR) as $serviceDir) {
        preg_match('#Modules/([^/]+)/Services#', $serviceDir, $m);
        $module = $m[1];

        $providerFiles = glob($projectRoot."/Modules/{$module}/Providers/*.php");
        $providersContent = '';
        foreach ($providerFiles ?: [] as $pf) {
            $providersContent .= (string) file_get_contents($pf);
        }

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($serviceDir)) as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $contents = (string) file_get_contents($file->getPathname());

            // Find use-imported or inline-referenced interfaces this class implements
            if (! str_contains($contents, 'implements')) {
                continue;
            }

            // Extract the implements clause
            if (! preg_match('/class\s+\w+[^{]*implements\s+([^{]+)/s', $contents, $implMatch)) {
                continue;
            }

            $implementsList = $implMatch[1];
            // Split by comma and resolve each interface name
            $implementedNames = array_map('trim', explode(',', $implementsList));

            // Build a use-import map for the file
            $useMap = [];
            preg_match_all('/^\s*use\s+([\w\\\\]+?)(?:\s+as\s+(\w+))?\s*;/m', $contents, $useMatches, PREG_SET_ORDER);
            foreach ($useMatches as $um) {
                $alias = $um[2] ?? class_basename($um[1]);
                $useMap[$alias] = $um[1];
            }

            // Get namespace of current file
            $fileNs = '';
            if (preg_match('/namespace\s+([^;]+);/', $contents, $fnm)) {
                $fileNs = $fnm[1];
            }

            foreach ($implementedNames as $implName) {
                $implName = trim($implName);
                if ($implName === '') {
                    continue;
                }

                // Resolve to FQCN
                $fqcn = '';
                if (str_starts_with($implName, '\\')) {
                    $fqcn = ltrim($implName, '\\');
                } elseif (isset($useMap[$implName])) {
                    $fqcn = $useMap[$implName];
                } elseif ($fileNs !== '') {
                    $fqcn = $fileNs.'\\'.$implName;
                }

                // Only check interfaces from app/Contracts
                if (! isset($contractInterfaces[$fqcn])) {
                    continue;
                }

                $shortInterface = class_basename($fqcn);

                // Check if the provider binds this interface
                $isBound = str_contains($providersContent, $fqcn)
                    || str_contains($providersContent, $shortInterface.'::class');
                if (! $isBound) {
                    $relativePath = str_replace($projectRoot.'/', '', $file->getPathname());
                    // Skip allowlisted pre-existing gaps
                    if (isset($allowlist[$relativePath]) && $allowlist[$relativePath] === $fqcn) {
                        continue;
                    }
                    $violations[] = "{$relativePath} implements {$fqcn} but {$module} provider does not bind it";
                }
            }
        }
    }

    expect($violations)->toBe([]);
});
