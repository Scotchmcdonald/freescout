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
     * Explicit allowlist for rare exceptions where Unit tests intentionally use
     * RefreshDatabase.
     *
     * Metadata is mandatory for every entry.
     *
    * @var array<string, array{owner:string, issue:string, rationale:string, expires:string}>
     */
    private array $allowlistedRelativePaths = [];

    public function test_allowlist_metadata_is_valid_and_not_expired(): void
    {
        $today = new \DateTimeImmutable('today');
        $errors = [];

        foreach ($this->allowlistedRelativePaths as $path => $meta) {
            if (trim($meta['owner']) === '' || trim($meta['issue']) === '' || trim($meta['rationale']) === '' || trim($meta['expires']) === '') {
                $errors[] = "{$path} has incomplete metadata (owner/issue/rationale/expires).";
                continue;
            }

            try {
                $expiry = new \DateTimeImmutable($meta['expires']);
            } catch (\Exception $e) {
                $errors[] = "{$path} has invalid expires date: {$meta['expires']}";
                continue;
            }

            if ($today > $expiry) {
                $errors[] = "{$path} allowlist entry expired on {$meta['expires']}";
            }
        }

        $this->assertSame([], $errors, implode("\n", $errors));
    }

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

            if (array_key_exists($normalizedPath, $this->allowlistedRelativePaths)) {
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
