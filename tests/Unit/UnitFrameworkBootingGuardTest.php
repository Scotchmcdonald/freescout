<?php

declare(strict_types=1);

namespace Tests\Unit;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\UnitTestCase;

class UnitFrameworkBootingGuardTest extends UnitTestCase
{
    /**
     * Transitional baseline: current Unit files still booting framework test cases.
     *
     * This guard freezes the baseline and fails if the count increases.
     *
     * @var array{owner:string, issue:string, rationale:string, expires:string, max_count:int}
     */
    private array $frameworkBootingBaseline = [
        'owner' => 'QA/Platform',
        'issue' => 'phase-2-unit-purification',
        'rationale' => 'Legacy Unit framework-booting files remain while PureUnit migrations continue.',
        'expires' => '2026-04-30',
        'max_count' => 4,
    ];

    public function test_framework_booting_unit_baseline_metadata_is_valid(): void
    {
        $meta = $this->frameworkBootingBaseline;
        $errors = [];

        if (trim($meta['owner']) === '' || trim($meta['issue']) === '' || trim($meta['rationale']) === '' || trim($meta['expires']) === '') {
            $errors[] = 'frameworkBootingBaseline requires owner, issue, rationale, and expires.';
        }

        if ($meta['max_count'] < 0) {
            $errors[] = 'frameworkBootingBaseline max_count must be >= 0.';
        }

        try {
            $expiry = new \DateTimeImmutable($meta['expires']);
            $today = new \DateTimeImmutable('today');
            if ($today > $expiry) {
                $errors[] = "frameworkBootingBaseline expired on {$meta['expires']}";
            }
        } catch (\Exception $e) {
            $errors[] = "frameworkBootingBaseline has invalid expires date {$meta['expires']}";
        }

        $this->assertSame([], $errors, implode("\n", $errors));
    }

    public function test_framework_booting_unit_test_count_does_not_increase(): void
    {
        $unitDir = base_path('tests/Unit');
        $violations = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($unitDir));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            if ($file->getFilename() === 'UnitFrameworkBootingGuardTest.php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname()) ?: '';
            $isFrameworkBooting = preg_match(
                '/extends\s+(?:UnitTestCase|TestCase|FeatureTestCase|IntegrationTestCase)\b|uses\s*\(\s*Tests\\\\UnitTestCase::class\s*\)/',
                $contents
            ) === 1;

            if (! $isFrameworkBooting) {
                continue;
            }

            $relativePath = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
            $violations[] = str_replace('\\', '/', $relativePath);
        }

        sort($violations);

        $maxCount = $this->frameworkBootingBaseline['max_count'];
        $currentCount = count($violations);

        $this->assertLessThanOrEqual(
            $maxCount,
            $currentCount,
            "Framework-booting Unit tests increased from baseline {$maxCount} to {$currentCount}.\n".
            "Migrate new Unit tests to PureUnitTestCase.\n".
            implode("\n", array_slice($violations, 0, 50))
        );
    }
}
