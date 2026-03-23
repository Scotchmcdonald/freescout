<?php

declare(strict_types=1);

namespace Tests\Unit;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\UnitTestCase;

class RefreshDatabaseUsageGuardTest extends UnitTestCase
{
    /**
     * Explicit allowlist for rare exceptions where Unit tests intentionally use RefreshDatabase.
     * Keep this list short and documented in the hardening plan when changed.
     *
     * @var array<int, string>
     */
    private array $allowlistedRelativePaths = [];

    public function test_unit_suite_disallows_explicit_refresh_database_usage_without_allowlist(): void
    {
        $unitDir = base_path('tests/Unit');

        $violations = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($unitDir));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            if ($file->getFilename() === 'RefreshDatabaseUsageGuardTest.php') {
                continue;
            }

            $relativePath = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
            $normalizedPath = str_replace('\\', '/', $relativePath);

            if (in_array($normalizedPath, $this->allowlistedRelativePaths, true)) {
                continue;
            }

            $contents = file_get_contents($file->getPathname()) ?: '';

            $usesImport = preg_match('/^\s*use\s+\\?Illuminate\\\\Foundation\\\\Testing\\\\RefreshDatabase\s*;/m', $contents) === 1;
            $usesTrait = preg_match('/^\s*use\s+RefreshDatabase\s*;/m', $contents) === 1;
            $usesPestTrait = preg_match('/RefreshDatabase::class/', $contents) === 1;

            if ($usesImport || $usesTrait || $usesPestTrait) {
                $violations[] = $normalizedPath;
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Unit tests must not explicitly use RefreshDatabase unless allowlisted. Violations: \n".implode("\n", $violations)
        );
    }
}
