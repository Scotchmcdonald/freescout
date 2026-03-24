<?php

declare(strict_types=1);

/**
 * Phase 5 Wave 2 quarantine registry guard.
 *
 * Registry path default: tests/quarantine/flaky-quarantine-registry.json
 * Report path default: reports/quarantine-registry-latest.md
 */
final class QuarantineRegistryGuard
{
    public function run(): int
    {
        $options = getopt('', ['registry::', 'tests-dir::', 'reports-dir::']);

        $registryPath = $this->normalizePath((string) ($options['registry'] ?? 'tests/quarantine/flaky-quarantine-registry.json'));
        $testsDir = $this->normalizePath((string) ($options['tests-dir'] ?? 'tests'));
        $reportsDir = $this->normalizePath((string) ($options['reports-dir'] ?? 'reports'));

        if (! file_exists($registryPath)) {
            fwrite(STDERR, 'Registry file not found: '.$registryPath.PHP_EOL);

            return 2;
        }

        if (! is_dir($testsDir)) {
            fwrite(STDERR, 'Tests directory not found: '.$testsDir.PHP_EOL);

            return 2;
        }

        if (! is_dir($reportsDir)) {
            mkdir($reportsDir, 0777, true);
        }

        $decoded = json_decode((string) file_get_contents($registryPath), true);
        if (! is_array($decoded)) {
            fwrite(STDERR, 'Registry JSON is invalid.'.PHP_EOL);

            return 2;
        }

        $entries = $decoded['entries'] ?? [];
        if (! is_array($entries)) {
            fwrite(STDERR, 'Registry must contain an entries array.'.PHP_EOL);

            return 2;
        }

        $taggedFiles = $this->collectTaggedFiles($testsDir);
        $violations = [];

        $activeEntries = [];
        foreach ($entries as $index => $entry) {
            if (! is_array($entry)) {
                $violations[] = "Entry #{$index} is not an object.";
                continue;
            }

            $entryId = (string) ($entry['id'] ?? 'entry-'.$index);
            $status = strtolower((string) ($entry['status'] ?? 'active'));
            $owner = trim((string) ($entry['owner'] ?? ''));
            $issue = trim((string) ($entry['issue'] ?? ''));
            $expires = trim((string) ($entry['expires'] ?? ''));
            $testFile = trim((string) ($entry['test_file'] ?? ''));
            $reason = trim((string) ($entry['reason'] ?? ''));

            if ($owner === '') {
                $violations[] = "{$entryId}: missing owner";
            }

            if ($issue === '') {
                $violations[] = "{$entryId}: missing issue";
            }

            if ($reason === '') {
                $violations[] = "{$entryId}: missing reason";
            }

            if ($testFile === '') {
                $violations[] = "{$entryId}: missing test_file";
            } elseif (! file_exists($this->normalizePath($testFile))) {
                $violations[] = "{$entryId}: test_file does not exist ({$testFile})";
            }

            $expiryError = $this->validateExpiry($expires);
            if ($expiryError !== null) {
                $violations[] = "{$entryId}: {$expiryError}";
            } elseif ($status === 'active' && $this->isExpired($expires)) {
                $violations[] = "{$entryId}: active quarantine expired on {$expires}";
            }

            if ($status !== 'active' && $status !== 'resolved') {
                $violations[] = "{$entryId}: unsupported status {$status} (use active|resolved)";
            }

            if ($status === 'active' && $testFile !== '') {
                $activeEntries[] = [
                    'id' => $entryId,
                    'test_file' => $testFile,
                ];
            }
        }

        $activeFiles = array_map(static fn (array $entry): string => $entry['test_file'], $activeEntries);
        $activeFiles = array_values(array_unique($activeFiles));

        foreach ($taggedFiles as $file) {
            if (! in_array($file, $activeFiles, true)) {
                $violations[] = 'Tagged quarantine test not present as active registry entry: '.$file;
            }
        }

        foreach ($activeFiles as $file) {
            if (! in_array($file, $taggedFiles, true)) {
                $violations[] = 'Active registry entry missing quarantine tag in test file: '.$file;
            }
        }

        $reportPath = $reportsDir.'/quarantine-registry-latest.md';
        file_put_contents($reportPath, $this->buildReport(
            registryPath: $registryPath,
            entryCount: count($entries),
            activeCount: count($activeEntries),
            taggedCount: count($taggedFiles),
            taggedFiles: $taggedFiles,
            violations: $violations
        ));

        echo 'Registry entries: '.count($entries).PHP_EOL;
        echo 'Active entries: '.count($activeEntries).PHP_EOL;
        echo 'Tagged files: '.count($taggedFiles).PHP_EOL;
        echo 'Report: '.$reportPath.PHP_EOL;

        if ($violations !== []) {
            fwrite(STDERR, 'FAIL: quarantine registry violations detected.'.PHP_EOL);

            return 1;
        }

        echo 'PASS: quarantine registry policy satisfied.'.PHP_EOL;

        return 0;
    }

    /**
     * @return list<string>
     */
    private function collectTaggedFiles(string $testsDir): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testsDir));
        $root = rtrim(getcwd() ?: '', '/').'/';

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $path = $fileInfo->getPathname();
            $contents = (string) file_get_contents($path);

            if (
                preg_match('/@group\s+flaky-triage\b/i', $contents) === 1
                || preg_match('/@flaky\b/i', $contents) === 1
                || preg_match('/quarantine\s*[:=]/i', $contents) === 1
            ) {
                $relative = str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
                $files[] = $relative;
            }
        }

        sort($files);

        return array_values(array_unique($files));
    }

    private function validateExpiry(string $value): ?string
    {
        if ($value === '') {
            return 'missing expires date';
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if (! $date || $date->format('Y-m-d') !== $value) {
            return 'invalid expires format (expected YYYY-MM-DD)';
        }

        return null;
    }

    private function isExpired(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if (! $date) {
            return true;
        }

        return $date < new DateTimeImmutable('today');
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
     * @param  list<string>  $taggedFiles
     * @param  list<string>  $violations
     */
    private function buildReport(
        string $registryPath,
        int $entryCount,
        int $activeCount,
        int $taggedCount,
        array $taggedFiles,
        array $violations
    ): string {
        $lines = [];
        $lines[] = '# Quarantine Registry Report';
        $lines[] = '';
        $lines[] = 'Generated: '.date('c');
        $lines[] = 'Registry: '.$registryPath;
        $lines[] = '';
        $lines[] = '| Metric | Value |';
        $lines[] = '|---|---:|';
        $lines[] = '| Registry entries | '.$entryCount.' |';
        $lines[] = '| Active entries | '.$activeCount.' |';
        $lines[] = '| Tagged test files | '.$taggedCount.' |';
        $lines[] = '';
        $lines[] = '## Tagged Files';
        $lines[] = '';

        if ($taggedFiles === []) {
            $lines[] = '- None';
        } else {
            foreach ($taggedFiles as $file) {
                $lines[] = '- '.$file;
            }
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

$guard = new QuarantineRegistryGuard;
exit($guard->run());
