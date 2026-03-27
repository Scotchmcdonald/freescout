<?php

declare(strict_types=1);

namespace Tests\Unit;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\PureUnitTestCase;

class RootUnitIsolationGuardTest extends PureUnitTestCase
{
    /**
     * @var array<string, string>
     */
    private array $laravelBootAllowlist = [
        'tests/Unit/EnumBehaviourTest.php' => 'Enum labels use translation helpers and still require the framework container.',
    ];

    public function test_root_unit_tests_do_not_boot_laravel_unless_explicitly_allowlisted(): void
    {
        $unitRoot = dirname(__DIR__).'/Unit';
        $violations = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($unitRoot));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace(dirname(__DIR__, 2).'/', '', $file->getPathname());
            $contents = (string) file_get_contents($file->getPathname());

            $bootsLaravel = preg_match('/extends\s+TestCase\b/', $contents) === 1
                || preg_match('/uses\(Tests\\\\TestCase::class\)/', $contents) === 1
                || preg_match('/uses\(TestCase::class\)/', $contents) === 1;

            if ($bootsLaravel && ! array_key_exists($relativePath, $this->laravelBootAllowlist)) {
                $violations[] = $relativePath;
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Root unit tests must use PureUnitTestCase unless explicitly allowlisted. Violations:\n".implode("\n", $violations)
        );
    }
}
