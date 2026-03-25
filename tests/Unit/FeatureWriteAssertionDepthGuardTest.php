<?php

declare(strict_types=1);

namespace Tests\Unit;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\PureUnitTestCase;

class FeatureWriteAssertionDepthGuardTest extends PureUnitTestCase
{
    /**
     * Transitional baseline for write-endpoint Feature files that do not include
     * side-effect assertions.
     *
     * @var array{owner:string, issue:string, rationale:string, expires:string, max_count:int}
     */
    private array $shallowWriteFeatureBaseline = [
        'owner' => 'QA/Platform',
        'issue' => 'phase-3-feature-meaningfulness',
        'rationale' => 'Temporary governance metadata for shallow-write baseline control during phase closeout.',
        'expires' => '2026-04-30',
        'max_count' => 0,
    ];

    public function test_shallow_write_feature_baseline_metadata_is_valid(): void
    {
        $meta = $this->shallowWriteFeatureBaseline;
        $errors = [];

        if (trim($meta['owner']) === '' || trim($meta['issue']) === '' || trim($meta['rationale']) === '' || trim($meta['expires']) === '') {
            $errors[] = 'shallowWriteFeatureBaseline requires owner, issue, rationale, and expires.';
        }

        if ($meta['max_count'] < 0) {
            $errors[] = 'shallowWriteFeatureBaseline max_count must be >= 0.';
        }

        try {
            $expiry = new \DateTimeImmutable($meta['expires']);
            $today = new \DateTimeImmutable('today');
            if ($today > $expiry) {
                $errors[] = "shallowWriteFeatureBaseline expired on {$meta['expires']}";
            }
        } catch (\Exception $e) {
            $errors[] = "shallowWriteFeatureBaseline has invalid expires date {$meta['expires']}";
        }

        $this->assertSame([], $errors, implode("\n", $errors));
    }

    public function test_feature_write_files_without_side_effect_assertions_do_not_increase(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $featureDir = $projectRoot.'/tests/Feature';
        $violations = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($featureDir));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname()) ?: '';
            if (! $this->containsWriteRequest($contents)) {
                continue;
            }

            if ($this->containsSideEffectAssertion($contents)) {
                continue;
            }

            $relativePath = str_replace($projectRoot.DIRECTORY_SEPARATOR, '', $file->getPathname());
            $violations[] = str_replace('\\', '/', $relativePath);
        }

        sort($violations);

        $maxCount = $this->shallowWriteFeatureBaseline['max_count'];
        $currentCount = count($violations);

        $this->assertLessThanOrEqual(
            $maxCount,
            $currentCount,
            "Shallow write-endpoint Feature files increased from baseline {$maxCount} to {$currentCount}.\n".
            "Add side-effect assertions (DB/Event/Queue/Mail/Notification/State) to new write tests.\n".
            implode("\n", $violations)
        );
    }

    private function containsWriteRequest(string $contents): bool
    {
        return preg_match('/->\s*(post|postJson|put|putJson|patch|patchJson|delete|deleteJson)\s*\(/i', $contents) === 1;
    }

    private function containsSideEffectAssertion(string $contents): bool
    {
        return preg_match(
            '/assertDatabase(?:Has|Missing|Count)|assertDispatched|assertPushed|assertQueued|assertSent|assertNotified|assertExists\(|assertMissing\(|->refresh\(|->fresh\(|Cache::has\(|Cache::get\(/i',
            $contents
        ) === 1;
    }
}
