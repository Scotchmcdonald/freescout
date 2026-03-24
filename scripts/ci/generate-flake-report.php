<?php

declare(strict_types=1);

/**
 * Phase 5 flaky trend report.
 *
 * This script aggregates recurring failure signatures from recent test logs
 * and normalizes mixed Pest/PHPUnit output for more reliable grouping.
 * It is intentionally non-blocking and always exits 0.
 */

final class FlakeReportGenerator
{
    public function run(): int
    {
        $options = getopt('', [
            'reports-dir::',
            'window::',
            'limit::',
            'lane::',
            'output::',
            'registry::',
        ]);

        $reportsDir = $this->normalizePath((string) ($options['reports-dir'] ?? 'reports'));
        $window = max(1, (int) ($options['window'] ?? 40));
        $limit = max(1, (int) ($options['limit'] ?? 20));
        $lane = (string) ($options['lane'] ?? 'all');
        $output = $this->normalizePath((string) ($options['output'] ?? ($reportsDir.'/flake-report-latest.md')));
        $registryPath = $this->normalizePath((string) ($options['registry'] ?? 'tests/quarantine/flaky-quarantine-registry.json'));

        if (! is_dir($reportsDir)) {
            mkdir($reportsDir, 0777, true);
        }

        $logFiles = $this->recentLogFiles($reportsDir, $window);
        $aggregates = [];
        $totalFailureEvents = 0;
        $logsWithFailures = 0;

        foreach ($logFiles as $logFile) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES) ?: [];
            $seenInLog = [];

            foreach ($lines as $line) {
                $event = $this->extractFailureEvent($line);
                if ($event === null) {
                    continue;
                }

                $totalFailureEvents++;
                $signature = $event['signature'];

                if (! isset($aggregates[$signature])) {
                    $aggregates[$signature] = [
                        'count' => 0,
                        'classes' => [],
                        'logs' => [],
                    ];
                }

                $aggregates[$signature]['count']++;
                if ($event['class'] !== '') {
                    $aggregates[$signature]['classes'][$event['class']] = true;
                }

                if (! isset($seenInLog[$signature])) {
                    $aggregates[$signature]['logs'][$logFile] = true;
                    $seenInLog[$signature] = true;
                }
            }

            if ($seenInLog !== []) {
                $logsWithFailures++;
            }
        }

        $flattened = $this->flattenAggregates($aggregates);

        usort($flattened, static function (array $a, array $b): int {
            if ($a['log_count'] === $b['log_count']) {
                return $b['count'] <=> $a['count'];
            }

            return $b['log_count'] <=> $a['log_count'];
        });

        $topFailures = array_slice($flattened, 0, $limit);
        $recurring = array_values(array_filter($flattened, static fn (array $row): bool => $row['log_count'] >= 2));

        $quarantineFiles = $this->readActiveQuarantineFiles($registryPath);

        foreach ($topFailures as &$row) {
            $likelyFiles = [];
            foreach ($row['classes'] as $className) {
                $resolved = $this->resolveLikelyTestFile($className);
                if ($resolved !== null) {
                    $likelyFiles[$resolved] = true;
                }
            }

            $row['likely_files'] = array_values(array_keys($likelyFiles));
            $row['has_active_quarantine'] = $this->hasAnyQuarantine($row['likely_files'], $quarantineFiles);
        }
        unset($row);

        file_put_contents($output, $this->buildReport(
            lane: $lane,
            logFiles: $logFiles,
            topFailures: $topFailures,
            recurringCount: count($recurring),
            totalFailureEvents: $totalFailureEvents,
            logsWithFailures: $logsWithFailures,
            activeQuarantineCount: count($quarantineFiles)
        ));

        echo 'Flake report generated: '.$output.PHP_EOL;
        echo 'Logs scanned: '.count($logFiles).PHP_EOL;
        echo 'Unique failure signatures: '.count($flattened).PHP_EOL;
        echo 'Recurring signatures: '.count($recurring).PHP_EOL;

        return 0;
    }

    /**
     * @param array<string, array{count:int, classes:array<string,bool>, logs:array<string,bool>}> $aggregates
     * @return list<array{signature:string,count:int,log_count:int,classes:list<string>}>
     */
    private function flattenAggregates(array $aggregates): array
    {
        $rows = [];

        foreach ($aggregates as $signature => $data) {
            $rows[] = [
                'signature' => $signature,
                'count' => $data['count'],
                'log_count' => count($data['logs']),
                'classes' => array_values(array_keys($data['classes'])),
            ];
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function recentLogFiles(string $reportsDir, int $window): array
    {
        $files = glob($reportsDir.'/test-results-*.log') ?: [];
        $filtered = [];

        foreach ($files as $file) {
            if (str_ends_with($file, '.ansi.log')) {
                continue;
            }

            if (str_contains($file, 'latest.log')) {
                continue;
            }

            $filtered[] = $file;
        }

        usort($filtered, static function (string $a, string $b): int {
            return filemtime($b) <=> filemtime($a);
        });

        return array_slice($filtered, 0, $window);
    }

    /**
     * @return array{signature:string,class:string}|null
     */
    private function extractFailureEvent(string $line): ?array
    {
        $trimmed = trim($line);
        if ($trimmed === '') {
            return null;
        }

        $trimmed = preg_replace('/\x1B\[[0-9;]*m/', '', $trimmed) ?: $trimmed;

        if (preg_match('/^(?:FAIL|FAILED)\s+(.+)$/', $trimmed, $matches) === 1) {
            $raw = $matches[1];

            return [
                'signature' => $this->normalizeSignature($raw),
                'class' => $this->extractTestClass($raw),
            ];
        }

        if (preg_match('/^\d+\)\s+(.+)$/', $trimmed, $matches) === 1) {
            $raw = $matches[1];

            return [
                'signature' => $this->normalizeSignature($raw),
                'class' => $this->extractTestClass($raw),
            ];
        }

        // Capture PHPUnit summary lines containing test class names.
        if (preg_match('/\bTests\\\\[A-Za-z0-9_\\\\]+\b.*\b(?:Error|Exception|Failure)\b/', $trimmed) === 1) {
            return [
                'signature' => $this->normalizeSignature($trimmed),
                'class' => $this->extractTestClass($trimmed),
            ];
        }

        return null;
    }

    private function normalizeSignature(string $value): string
    {
        $value = trim($value);

        // Remove unstable values that fragment signatures.
        $value = preg_replace('/\bat\s+line\s+\d+\b/i', 'at line N', $value) ?: $value;
        $value = preg_replace('/:[0-9]+(?=\s|$)/', ':N', $value) ?: $value;
        $value = preg_replace('/\b0x[0-9a-fA-F]+\b/', '0xADDR', $value) ?: $value;
        $value = preg_replace('/\b[0-9]{2,}\b/', 'N', $value) ?: $value;

        if (str_contains($value, '…')) {
            $value = str_replace('…', '', $value);
        }

        $value = preg_replace('/\s+/', ' ', trim($value)) ?: trim($value);

        if (strlen($value) > 180) {
            $value = substr($value, 0, 177).'...';
        }

        return $value;
    }

    private function extractTestClass(string $value): string
    {
        if (preg_match('/\b(Tests\\\\[A-Za-z0-9_\\\\]+)\b/', $value, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }

    private function resolveLikelyTestFile(string $className): ?string
    {
        if ($className === '' || ! str_starts_with($className, 'Tests\\')) {
            return null;
        }

        $path = str_replace('\\', '/', $className).'.php';
        $relative = preg_replace('/^Tests\//', 'tests/', $path) ?: $path;
        $candidate = $this->normalizePath($relative);

        if (file_exists($candidate)) {
            return $relative;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function readActiveQuarantineFiles(string $registryPath): array
    {
        if (! file_exists($registryPath)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($registryPath), true);
        if (! is_array($decoded) || ! isset($decoded['entries']) || ! is_array($decoded['entries'])) {
            return [];
        }

        $files = [];
        foreach ($decoded['entries'] as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            if (($entry['status'] ?? '') !== 'active') {
                continue;
            }

            $file = (string) ($entry['test_file'] ?? '');
            if ($file !== '') {
                $files[] = $file;
            }
        }

        return array_values(array_unique($files));
    }

    /**
     * @param list<string> $likelyFiles
     * @param list<string> $quarantineFiles
     */
    private function hasAnyQuarantine(array $likelyFiles, array $quarantineFiles): bool
    {
        foreach ($likelyFiles as $file) {
            if (in_array($file, $quarantineFiles, true)) {
                return true;
            }
        }

        return false;
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
     * @param list<array{signature:string,count:int,log_count:int,classes:list<string>,likely_files:list<string>,has_active_quarantine:bool}> $topFailures
     * @param list<string> $logFiles
     */
    private function buildReport(
        string $lane,
        array $logFiles,
        array $topFailures,
        int $recurringCount,
        int $totalFailureEvents,
        int $logsWithFailures,
        int $activeQuarantineCount
    ): string {
        $lines = [];
        $lines[] = '# Flake Trend Report';
        $lines[] = '';
        $lines[] = 'Generated: '.date('c');
        $lines[] = 'Lane: '.$lane;
        $lines[] = 'Log files scanned: '.count($logFiles);
        $lines[] = 'Logs with failures: '.$logsWithFailures;
        $lines[] = 'Failure events parsed: '.$totalFailureEvents;
        $lines[] = 'Recurring signatures (>=2 logs): '.$recurringCount;
        $lines[] = 'Active quarantine entries: '.$activeQuarantineCount;
        $lines[] = '';

        $flakeRate = count($logFiles) > 0 ? ($logsWithFailures / count($logFiles)) * 100 : 0.0;
        $lines[] = 'Estimated flake pressure: '.number_format($flakeRate, 2).'% of scanned logs contain failures.';
        $lines[] = '';

        $lines[] = '## Top Failure Signatures';
        $lines[] = '';
        if ($topFailures === []) {
            $lines[] = '- No failure signatures detected in scanned logs.';
            $lines[] = '';
        } else {
            $lines[] = '| Signature | Events | Logs | Quarantined | Likely Test Files |';
            $lines[] = '|---|---:|---:|---|---|';
            foreach ($topFailures as $row) {
                $escaped = str_replace('|', '\\|', $row['signature']);
                $quarantine = $row['has_active_quarantine'] ? 'yes' : 'no';
                $files = $row['likely_files'] === [] ? '-' : implode(', ', $row['likely_files']);
                $files = str_replace('|', '\\|', $files);
                $lines[] = '| '.$escaped.' | '.$row['count'].' | '.$row['log_count'].' | '.$quarantine.' | '.$files.' |';
            }
            $lines[] = '';

            $lines[] = '## Suggested Actions';
            $lines[] = '';
            foreach ($topFailures as $row) {
                if ($row['log_count'] < 2) {
                    continue;
                }

                if ($row['has_active_quarantine']) {
                    $lines[] = '- Monitor recurring quarantined signature: '.$row['signature'];
                    continue;
                }

                $candidateFile = $row['likely_files'][0] ?? 'unknown';
                $lines[] = '- Candidate quarantine needed (recurs across '.$row['log_count'].' logs): '.$row['signature'].' -> '.$candidateFile;
            }

            if (! array_filter($topFailures, static fn (array $row): bool => $row['log_count'] >= 2)) {
                $lines[] = '- No recurring signatures currently require quarantine consideration.';
            }
            $lines[] = '';
        }

        $lines[] = '## Logs Considered';
        $lines[] = '';
        foreach ($logFiles as $file) {
            $lines[] = '- '.$file;
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }
}

$generator = new FlakeReportGenerator;
exit($generator->run());
