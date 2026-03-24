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
    * @var array<string, array{owner:string, issue:string, rationale:string, expires:string}>
     */
    private array $allowlistedPathPrefixes = [];

    /**
     * Baseline of known legacy Unit tests still using RefreshDatabase under
     * allowlisted modules. Keep shrinking this list during migrations.
    * Metadata is mandatory for each entry.
     *
    * @var array<string, array{owner:string, issue:string, rationale:string, expires:string}>
     */
    private array $allowlistedRefreshDatabaseBaseline = [];

    /**
     * Baseline of known feature tests importing external API gateway services
     * without local Http::fake()/Http::preventStrayRequests() usage.
     *
     * Guard behavior: block new violations while allowing existing legacy files
     * to be remediated incrementally.
     *
    * @var array<string, array{owner:string, issue:string, rationale:string, expires:string}>
     */
    private array $allowlistedExternalHttpMockBaseline = [];

    /**
     * Guarded hotspot tests where mocking the gateway/service-under-test internals
     * causes false confidence. Keep this list explicit and narrow.
     *
    * @var array<string, array{pattern:string, owner:string, issue:string, rationale:string, expires:string}>
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
                if (! array_key_exists($normalizedPath, $this->allowlistedExternalHttpMockBaseline)) {
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

    public function test_allowlist_entries_have_metadata_and_are_not_expired(): void
    {
        $today = new \DateTimeImmutable('today');
        $errors = [];

        $metadataGroups = [
            'allowlistedPathPrefixes' => $this->allowlistedPathPrefixes,
            'allowlistedRefreshDatabaseBaseline' => $this->allowlistedRefreshDatabaseBaseline,
            'allowlistedExternalHttpMockBaseline' => $this->allowlistedExternalHttpMockBaseline,
        ];

        foreach ($metadataGroups as $groupName => $entries) {
            foreach ($entries as $path => $meta) {
                if (trim($meta['owner']) === '' || trim($meta['issue']) === '' || trim($meta['rationale']) === '' || trim($meta['expires']) === '') {
                    $errors[] = "{$groupName}: {$path} has incomplete metadata (owner/issue/rationale/expires).";
                    continue;
                }

                try {
                    $expiry = new \DateTimeImmutable($meta['expires']);
                } catch (\Exception $e) {
                    $errors[] = "{$groupName}: {$path} has invalid expires date {$meta['expires']}";
                    continue;
                }

                if ($today > $expiry) {
                    $errors[] = "{$groupName}: {$path} expired on {$meta['expires']}";
                }
            }
        }

        foreach ($this->guardedGatewayHotspotPatterns as $path => $meta) {
            if (trim($meta['pattern']) === '' || trim($meta['owner']) === '' || trim($meta['issue']) === '' || trim($meta['rationale']) === '' || trim($meta['expires']) === '') {
                $errors[] = "guardedGatewayHotspotPatterns: {$path} has incomplete metadata (pattern/owner/issue/rationale/expires).";
                continue;
            }

            try {
                $expiry = new \DateTimeImmutable($meta['expires']);
            } catch (\Exception $e) {
                $errors[] = "guardedGatewayHotspotPatterns: {$path} has invalid expires date {$meta['expires']}";
                continue;
            }

            if ($today > $expiry) {
                $errors[] = "guardedGatewayHotspotPatterns: {$path} expired on {$meta['expires']}";
            }
        }

        $this->assertSame(
            [],
            $errors,
            "Allowlist metadata errors:\n".implode("\n", $errors)
        );
    }

    private function isAllowlisted(string $path): bool
    {
        foreach (array_keys($this->allowlistedPathPrefixes) as $prefix) {
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
            || preg_match('/RefreshDatabase\s*::\s*class/', $contents) === 1;
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

        return preg_match($this->guardedGatewayHotspotPatterns[$path]['pattern'], $contents) === 1;
    }
}
