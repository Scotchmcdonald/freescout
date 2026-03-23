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
    private array $allowlistedPathPrefixes = [];

    /**
     * Baseline of known legacy Unit tests still using RefreshDatabase under
     * allowlisted modules. Keep shrinking this list during migrations.
     * Each entry must carry an @expires date; the guard fails if date has passed.
     *
     * @var array<string, string> path => expiry date (Y-m-d)
     */
    private array $allowlistedRefreshDatabaseBaseline = [];

    /**
     * Baseline of known feature tests importing external API gateway services
     * without local Http::fake()/Http::preventStrayRequests() usage.
     *
     * Guard behavior: block new violations while allowing existing legacy files
     * to be remediated incrementally.
     *
     * @var array<int, string>
     */
    private array $allowlistedExternalHttpMockBaseline = [];

    /**
     * Guarded hotspot tests where mocking the gateway/service-under-test internals
     * causes false confidence. Keep this list explicit and narrow.
     *
     * @var array<string, string>
     */
    private array $guardedGatewayHotspotPatterns = [];

    public function test_module_unit_tests_do_not_use_refresh_database_or_cross_module_persistence(): void
    {
        $unitRoot = base_path('Modules');

        $refreshDatabaseViolations = [];
        $newAllowlistedRefreshDatabaseViolations = [];
        $crossModulePersistenceViolations = [];
        $crossModuleResolveViolations = [];
        $newExternalHttpMockViolations = [];
        $newGatewayOverMockViolations = [];

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

            if ($this->isGatewayOverMockHotspot($normalizedPath, $contents)) {
                $newGatewayOverMockViolations[] = $normalizedPath;
            }

            if (! str_contains($normalizedPath, '/Tests/Unit/')) {
                continue;
            }

            $usesRefreshDatabase = $this->usesRefreshDatabase($contents);

            if ($this->isAllowlisted($normalizedPath)) {
                if (
                    $usesRefreshDatabase
                    && ! array_key_exists($normalizedPath, $this->allowlistedRefreshDatabaseBaseline)
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

            // Phase 6 Rule 5: Unit tests must not use app()->make() or resolve()
            // for cross-module services — this boots the container and creates
            // implicit coupling equivalent to cross-module DB persistence.
            $this->detectCrossModuleResolve($contents, $module, $normalizedPath, $crossModuleResolveViolations);
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
            $crossModuleResolveViolations,
            "Module unit tests must not use app()->make() or resolve() for cross-module services. Violations:\n".implode("\n", $crossModuleResolveViolations)
        );

        $this->assertSame(
            [],
            $newExternalHttpMockViolations,
            "Module feature tests importing external API services must include Http::fake() or Http::preventStrayRequests(). New violations:\n".implode("\n", $newExternalHttpMockViolations)
        );

        $this->assertSame(
            [],
            $newGatewayOverMockViolations,
            "Gateway hotspot tests must not mock external service internals directly. New violations:\n".implode("\n", $newGatewayOverMockViolations)
        );
    }

    /**
     * Phase 6C: Allowlist entries must carry expiry dates.
     * If an entry's expiry date has passed, this guard fails — forcing resolution
     * or explicit extension with updated justification.
     */
    public function test_allowlist_entries_have_not_expired(): void
    {
        $today = new \DateTimeImmutable('today');
        $expired = [];

        foreach ($this->allowlistedRefreshDatabaseBaseline as $path => $expiryDate) {
            $expiry = new \DateTimeImmutable($expiryDate);
            if ($today > $expiry) {
                $expired[] = "{$path} expired on {$expiryDate}";
            }
        }

        $this->assertSame(
            [],
            $expired,
            "The following allowlist entries have expired and must be resolved or extended:\n".implode("\n", $expired)
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

    /**
     * Phase 6 Rule 5: Detect app()->make() or resolve() calls that resolve
     * cross-module service classes inside unit tests.
     *
     * @param  array<int, string>  $violations
     */
    private function detectCrossModuleResolve(string $contents, string $module, string $path, array &$violations): void
    {
        $appMakePattern = '/(?:app\(\)\s*->make|app\s*\(|resolve)\s*\(\s*\\\\?Modules\\\\([A-Za-z0-9_]+)\\\\/';

        if (preg_match_all($appMakePattern, $contents, $hits)) {
            foreach ($hits[1] as $resolvedModule) {
                if ($resolvedModule !== '' && $resolvedModule !== $module) {
                    $violations[] = "{$path} resolves Modules\\{$resolvedModule} via app/resolve in unit scope";
                }
            }
        }
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

    private function isGatewayOverMockHotspot(string $path, string $contents): bool
    {
        if (! isset($this->guardedGatewayHotspotPatterns[$path])) {
            return false;
        }

        return preg_match($this->guardedGatewayHotspotPatterns[$path], $contents) === 1;
    }
}
