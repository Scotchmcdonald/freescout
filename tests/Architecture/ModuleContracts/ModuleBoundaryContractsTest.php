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
