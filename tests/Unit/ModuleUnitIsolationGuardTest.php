<?php

declare(strict_types=1);

namespace Tests\Unit;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\UnitTestCase;

class ModuleUnitIsolationGuardTest extends UnitTestCase
{
    /**
     * Temporary allowlist while legacy module unit suites are migrated.
     * Keep this list explicit and shrink it over time.
     *
     * @var array<int, string>
     */
    private array $allowlistedPathPrefixes = [
        'Modules/PIB/Tests/Unit/',
        'Modules/SoftwareSubscriptions/Tests/Unit/', // migration in progress — B0 allowlist
    ];

    /**
     * Baseline of known legacy Unit tests still using RefreshDatabase under
     * allowlisted modules. Keep shrinking this list during migrations.
     *
     * @var array<int, string>
     */
    private array $allowlistedRefreshDatabaseBaseline = [
        'Modules/SoftwareSubscriptions/Tests/Unit/ClientSoftwareSubscriptionPestTest.php',
        'Modules/SoftwareSubscriptions/Tests/Unit/DiscoveryEventsPestTest.php',
        'Modules/SoftwareSubscriptions/Tests/Unit/OffboardingTicketListenerPestTest.php',
        'Modules/SoftwareSubscriptions/Tests/Unit/OffboardingTicketListenerTest.php',
        'Modules/SoftwareSubscriptions/Tests/Unit/OffboardingTicketSystemPestTest.php',
        'Modules/SoftwareSubscriptions/Tests/Unit/OffboardingTicketSystemTest.php',
        'Modules/SoftwareSubscriptions/Tests/Unit/SoftwareDiscoveryPestTest.php',
        'Modules/SoftwareSubscriptions/Tests/Unit/SoftwareProductPestTest.php',
        'Modules/SoftwareSubscriptions/Tests/Unit/SubscriptionCounterServicePestTest.php',
        'Modules/SoftwareSubscriptions/Tests/Unit/VendorCostReportPestTest.php',
        'Modules/SoftwareSubscriptions/Tests/Unit/VendorCostReportTest.php',
    ];

    /**
     * Baseline of known feature tests importing external API gateway services
     * without local Http::fake()/Http::preventStrayRequests() usage.
     *
     * Guard behavior: block new violations while allowing existing legacy files
     * to be remediated incrementally.
     *
     * @var array<int, string>
     */
    private array $allowlistedExternalHttpMockBaseline = [
        'Modules/Action1/Tests/Feature/SyncAction1DevicesJobPestTest.php',
        'Modules/GoogleAdmin/Tests/Feature/SyncGoogleChromebooksJobPestTest.php',
        'Modules/GoogleAdmin/Tests/Feature/SyncGoogleUsersJobPestTest.php',
        'Modules/GoogleAdmin/Tests/Feature/UserProvisioningActionPestTest.php',
    ];

    public function test_module_unit_tests_do_not_use_refresh_database_or_cross_module_persistence(): void
    {
        $unitRoot = base_path('Modules');

        $refreshDatabaseViolations = [];
        $newAllowlistedRefreshDatabaseViolations = [];
        $crossModulePersistenceViolations = [];
        $newExternalHttpMockViolations = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($unitRoot));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
            $normalizedPath = str_replace('\\', '/', $relativePath);

            if ($this->isFeatureTestWithExternalApiServiceWithoutHttpMock($normalizedPath, $contents = file_get_contents($file->getPathname()) ?: '')) {
                if (! in_array($normalizedPath, $this->allowlistedExternalHttpMockBaseline, true)) {
                    $newExternalHttpMockViolations[] = $normalizedPath;
                }
            }

            if (! str_contains($normalizedPath, '/Tests/Unit/')) {
                continue;
            }

            $usesRefreshDatabase = $this->usesRefreshDatabase($contents);

            if ($this->isAllowlisted($normalizedPath)) {
                if (
                    $usesRefreshDatabase
                    && ! in_array($normalizedPath, $this->allowlistedRefreshDatabaseBaseline, true)
                ) {
                    $newAllowlistedRefreshDatabaseViolations[] = $normalizedPath;
                }

                continue;
            }

            $module = $this->extractModuleName($normalizedPath);
            if ($module === null) {
                continue;
            }

            if ($usesRefreshDatabase) {
                $refreshDatabaseViolations[] = $normalizedPath;
            }

            if ($this->containsCrossModulePersistence($contents, $module)) {
                $crossModulePersistenceViolations[] = $normalizedPath;
            }
        }

        $this->assertSame(
            [],
            $refreshDatabaseViolations,
            "Module unit tests must not use RefreshDatabase unless allowlisted. Violations:\n".implode("\n", $refreshDatabaseViolations)
        );

        $this->assertSame(
            [],
            $newAllowlistedRefreshDatabaseViolations,
            "Allowlisted module Unit tests may only keep known legacy RefreshDatabase files. New violations:\n".implode("\n", $newAllowlistedRefreshDatabaseViolations)
        );

        $this->assertSame(
            [],
            $crossModulePersistenceViolations,
            "Module unit tests must not persist cross-module models directly unless allowlisted. Violations:\n".implode("\n", $crossModulePersistenceViolations)
        );

        $this->assertSame(
            [],
            $newExternalHttpMockViolations,
            "Module feature tests importing external API services must include Http::fake() or Http::preventStrayRequests(). New violations:\n".implode("\n", $newExternalHttpMockViolations)
        );
    }

    private function isAllowlisted(string $path): bool
    {
        foreach ($this->allowlistedPathPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function extractModuleName(string $path): ?string
    {
        if (! preg_match('#^Modules/([^/]+)/#', $path, $matches)) {
            return null;
        }

        return $matches[1] ?? null;
    }

    private function usesRefreshDatabase(string $contents): bool
    {
        return preg_match('/^\s*use\s+\\\\?Illuminate\\\\Foundation\\\\Testing\\\\RefreshDatabase\s*;/m', $contents) === 1
            || preg_match('/^\s*use\s+RefreshDatabase\s*;/m', $contents) === 1
            || preg_match('/RefreshDatabase::class/', $contents) === 1;
    }

    private function containsCrossModulePersistence(string $contents, string $module): bool
    {
        $moduleQuoted = preg_quote($module, '/');

        $pattern = '/Modules\\\\(?!'.$moduleQuoted.'\\\\)[A-Za-z0-9_]+\\\\(?:Models|Entities)\\\\[A-Za-z0-9_]+\s*::\s*(?:create|forceCreate|updateOrCreate|firstOrCreate|factory\s*\(\)\s*->\s*create)\s*\(/m';

        return preg_match($pattern, $contents) === 1;
    }

    private function isFeatureTestWithExternalApiServiceWithoutHttpMock(string $path, string $contents): bool
    {
        if (! str_contains($path, '/Tests/Feature/')) {
            return false;
        }

        $module = $this->extractModuleName($path);
        if ($module === null) {
            return false;
        }

        $serviceImportPatterns = [
            'Payment' => '/^\s*use\s+Modules\\\\Payment\\\\Services\\\\HelcimService\s*;/m',
            'GoogleAdmin' => '/^\s*use\s+Modules\\\\GoogleAdmin\\\\Services\\\\(?:GoogleWorkspaceService|GoogleUserProvider)\s*;/m',
            'CaseManager' => '/^\s*use\s+Modules\\\\CaseManager\\\\Services\\\\(?:GeminiClient|CaseManagerAiService|DecisionEngine)\s*;/m',
            'Action1' => '/^\s*use\s+Modules\\\\Action1\\\\Services\\\\(?:Action1Service|Action1SyncService|Action1ManageService|Action1RunService|MspScriptService)\s*;/m',
        ];

        if (! isset($serviceImportPatterns[$module])) {
            return false;
        }

        $importsExternalGatewayService = preg_match($serviceImportPatterns[$module], $contents) === 1;
        if (! $importsExternalGatewayService) {
            return false;
        }

        $hasHttpSafety = str_contains($contents, 'Http::fake(')
            || str_contains($contents, 'Http::preventStrayRequests(');

        return ! $hasHttpSafety;
    }
}
