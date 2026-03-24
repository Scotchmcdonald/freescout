<?php

declare(strict_types=1);

/**
 * Phase 5 skip-governance guard.
 *
 * Policy:
 * - Existing baseline skips are tracked with owner/issue/rationale/expires metadata.
 * - Untracked markTestSkipped usages fail and must be explicitly allowlisted.
 * - Expired skips fail.
 */

final class SkipGovernanceGuard
{
    /**
    * @var array{max_count:int, lane_budgets:array<string,int>, allowlist:array<string,array{owner:string,issue:string,rationale:string,expires:string}>}
     */
    private const BASELINE = [
        'max_count' => 12,
        'lane_budgets' => [
            'Unit' => 0,
            'Feature' => 10,
            'Integration' => 2,
            'Browser' => 0,
            'Other' => 0,
        ],
        'allowlist' => [
            'tests/Integration/CrossModule/WorkflowContractTest.php:41' => [
                'owner' => 'QA/Platform',
                'issue' => 'phase-5-skip-governance-baseline',
                'rationale' => 'Cross-module workflow test is gated by optional module availability.',
                'expires' => '2026-06-30',
            ],
            'tests/Integration/Workflows/PaymentToPibFinancialPipelineTest.php:50' => [
                'owner' => 'QA/Platform',
                'issue' => 'phase-5-skip-governance-baseline',
                'rationale' => 'PIB workflow integration remains optional in non-PIB environments.',
                'expires' => '2026-06-30',
            ],
            'tests/Feature/SmokeTest.php:23' => [
                'owner' => 'QA/Platform',
                'issue' => 'phase-5-skip-governance-baseline',
                'rationale' => 'Smoke test depends on runtime class presence across install modes.',
                'expires' => '2026-06-30',
            ],
            'tests/Feature/Security/SecurityAuthorizationPestTest.php:150' => [
                'owner' => 'QA/Platform',
                'issue' => 'phase-5-skip-governance-baseline',
                'rationale' => 'Authorization scenario is environment-sensitive when SavedSearch model is absent.',
                'expires' => '2026-06-30',
            ],
            'tests/Feature/System/SystemHealthPestTest.php:47' => [
                'owner' => 'QA/Platform',
                'issue' => 'phase-5-skip-governance-baseline',
                'rationale' => 'Queue listener trigger is unstable in test runtime and tracked for remediation.',
                'expires' => '2026-06-30',
            ],
            'tests/Feature/InterfaceSegregation/CreditLedgerInterfacesTest.php:241' => [
                'owner' => 'QA/Platform',
                'issue' => 'phase-5-skip-governance-baseline',
                'rationale' => 'Contract test path depends on optional credit report service binding.',
                'expires' => '2026-06-30',
            ],
            'tests/Feature/Modules/SoftwareSubscriptions/AtomicCounterPestTest.php:51' => [
                'owner' => 'QA/Platform',
                'issue' => 'phase-5-skip-governance-baseline',
                'rationale' => 'Scenario requires both SoftwareSubscriptions and CRM modules enabled.',
                'expires' => '2026-06-30',
            ],
            'tests/Feature/Modules/ModulesManagementPestTest.php:32' => [
                'owner' => 'QA/Platform',
                'issue' => 'phase-5-skip-governance-baseline',
                'rationale' => 'Module management test requires nwidart package in runtime container.',
                'expires' => '2026-06-30',
            ],
            'tests/Feature/Modules/ModulesManagementPestTest.php:57' => [
                'owner' => 'QA/Platform',
                'issue' => 'phase-5-skip-governance-baseline',
                'rationale' => 'Module management test requires nwidart package in runtime container.',
                'expires' => '2026-06-30',
            ],
            'tests/Feature/Modules/ModulesManagementPestTest.php:83' => [
                'owner' => 'QA/Platform',
                'issue' => 'phase-5-skip-governance-baseline',
                'rationale' => 'Module management test requires nwidart package in runtime container.',
                'expires' => '2026-06-30',
            ],
            'tests/Feature/Modules/ModulesManagementPestTest.php:115' => [
                'owner' => 'QA/Platform',
                'issue' => 'phase-5-skip-governance-baseline',
                'rationale' => 'Module management test requires nwidart package in runtime container.',
                'expires' => '2026-06-30',
            ],
            'tests/Feature/Modules/ModulesManagementPestTest.php:147' => [
                'owner' => 'QA/Platform',
                'issue' => 'phase-5-skip-governance-baseline',
                'rationale' => 'Module management test requires nwidart package in runtime container.',
                'expires' => '2026-06-30',
            ],
        ],
    ];

    public function run(): int
    {
        $options = getopt('', ['tests-dir::', 'reports-dir::']);
        $testsDir = $this->normalizePath((string) ($options['tests-dir'] ?? 'tests'));
        $reportsDir = $this->normalizePath((string) ($options['reports-dir'] ?? 'reports'));

        if (! is_dir($testsDir)) {
            fwrite(STDERR, "Tests directory not found: {$testsDir}".PHP_EOL);

            return 2;
        }

        if (! is_dir($reportsDir)) {
            mkdir($reportsDir, 0777, true);
        }

        $occurrences = $this->collectMarkTestSkippedOccurrences($testsDir);
        $violations = [];
        $laneCounts = ['Unit' => 0, 'Feature' => 0, 'Integration' => 0, 'Browser' => 0, 'Other' => 0];

        if (count($occurrences) > self::BASELINE['max_count']) {
            $violations[] = sprintf(
                'Total markTestSkipped count increased: current=%d baseline_max=%d',
                count($occurrences),
                self::BASELINE['max_count']
            );
        }

        $knownLocations = array_column($occurrences, 'location');

        foreach (self::BASELINE['allowlist'] as $location => $metadata) {
            if (trim($metadata['owner']) === '' || trim($metadata['issue']) === '' || trim($metadata['rationale']) === '' || trim($metadata['expires']) === '') {
                $violations[] = 'Baseline allowlist entry has incomplete metadata (owner/issue/rationale/expires): '.$location;
                continue;
            }

            $expiryState = $this->validateExpiryDate($metadata['expires']);
            if ($expiryState !== null) {
                $violations[] = 'Baseline allowlist entry expired or invalid: '.$location.' ('.$expiryState.')';
            }

            if (! in_array($location, $knownLocations, true)) {
                $violations[] = 'Stale allowlist entry no longer present in test code: '.$location;
            }
        }

        foreach ($occurrences as $occurrence) {
            $lane = $this->laneFromPath($occurrence['path']);
            $laneCounts[$lane]++;

            if (! isset(self::BASELINE['allowlist'][$occurrence['location']])) {
                $violations[] = 'Untracked skip entry: '.$occurrence['location'].' (add explicit allowlist metadata with owner/issue/rationale/expires).';
            }
        }

        foreach (self::BASELINE['lane_budgets'] as $lane => $budget) {
            $current = $laneCounts[$lane] ?? 0;
            if ($current > $budget) {
                $violations[] = "Lane skip budget exceeded for {$lane}: current={$current} budget={$budget}";
            }
        }

        $reportPath = $reportsDir.'/skip-governance-latest.md';
        file_put_contents($reportPath, $this->buildReport($occurrences, $laneCounts, $violations));

        echo 'Skip occurrences: '.count($occurrences).PHP_EOL;
        echo 'Report: '.$reportPath.PHP_EOL;

        if ($violations !== []) {
            fwrite(STDERR, "FAIL: skip governance violations detected.".PHP_EOL);

            return 1;
        }

        echo "PASS: skip governance policy satisfied.".PHP_EOL;

        return 0;
    }

    /**
     * @return list<array{path:string,line:int,location:string,reason:string,context:string}>
     */
    private function collectMarkTestSkippedOccurrences(string $testsDir): array
    {
        $results = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testsDir));
        $root = rtrim(getcwd() ?: '', '/').'/';

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $fullPath = $fileInfo->getPathname();
            $relativePath = str_starts_with($fullPath, $root) ? substr($fullPath, strlen($root)) : $fullPath;
            $lines = file($fullPath, FILE_IGNORE_NEW_LINES) ?: [];

            foreach ($lines as $index => $line) {
                if (preg_match('/\bmarkTestSkipped\s*\(/', $line) !== 1) {
                    continue;
                }

                $lineNumber = $index + 1;
                $start = max(0, $index - 3);
                $contextLines = array_slice($lines, $start, 7);
                $context = implode(PHP_EOL, $contextLines);
                $reason = '';
                if (preg_match('/markTestSkipped\s*\(\s*[\"\']([^\"\']+)[\"\']/', $line, $matches) === 1) {
                    $reason = trim($matches[1]);
                }

                $results[] = [
                    'path' => $relativePath,
                    'line' => $lineNumber,
                    'location' => $relativePath.':'.$lineNumber,
                    'reason' => $reason,
                    'context' => $context,
                ];
            }
        }

        usort($results, static function (array $a, array $b): int {
            return strcmp($a['location'], $b['location']);
        });

        return $results;
    }

    private function validateExpiryDate(string $value): ?string
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if (! $date || $date->format('Y-m-d') !== $value) {
            return 'invalid expiry format';
        }

        $today = new DateTimeImmutable('today');
        if ($date < $today) {
            return 'expired '.$value;
        }

        return null;
    }

    private function laneFromPath(string $path): string
    {
        if (str_starts_with($path, 'tests/Unit/')) {
            return 'Unit';
        }
        if (str_starts_with($path, 'tests/Feature/')) {
            return 'Feature';
        }
        if (str_starts_with($path, 'tests/Integration/')) {
            return 'Integration';
        }
        if (str_starts_with($path, 'tests/Browser/')) {
            return 'Browser';
        }

        return 'Other';
    }

    private function normalizePath(string $path): string
    {
        if ($path === '') {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        return getcwd().'/'.ltrim($path, '/');
    }

    /**
    * @param list<array{path:string,line:int,location:string,reason:string,context:string}> $occurrences
     * @param array<string,int> $laneCounts
     * @param list<string> $violations
     */
    private function buildReport(array $occurrences, array $laneCounts, array $violations): string
    {
        $lines = [];
        $lines[] = '# Skip Governance Report';
        $lines[] = '';
        $lines[] = 'Generated: '.date('c');
        $lines[] = '';
        $lines[] = '| Metric | Value |';
        $lines[] = '|---|---:|';
        $lines[] = '| Total markTestSkipped occurrences | '.count($occurrences).' |';
        $lines[] = '| Baseline max count | '.self::BASELINE['max_count'].' |';
        $lines[] = '';

        $lines[] = '## Lane Counts';
        $lines[] = '';
        $lines[] = '| Lane | Count | Budget |';
        $lines[] = '|---|---:|---:|';
        foreach (self::BASELINE['lane_budgets'] as $lane => $budget) {
            $lines[] = '| '.$lane.' | '.($laneCounts[$lane] ?? 0).' | '.$budget.' |';
        }
        $lines[] = '';

        $lines[] = '## Current Occurrences';
        $lines[] = '';
        foreach ($occurrences as $occurrence) {
            $reason = $occurrence['reason'] !== '' ? ' - '.$occurrence['reason'] : '';
            $lines[] = '- '.$occurrence['location'].$reason;
        }
        $lines[] = '';

        $lines[] = '## Violations';
        $lines[] = '';
        if ($violations === []) {
            $lines[] = '- None';
        } else {
            foreach ($violations as $violation) {
                $lines[] = '- '.$violation;
            }
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }
}

$guard = new SkipGovernanceGuard;
exit($guard->run());
