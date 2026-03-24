<?php

declare(strict_types=1);

/**
 * Phase 5 flaky trend report.
 *
 * This script aggregates recurring failure signatures from recent test logs.
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
        ]);

        $reportsDir = $this->normalizePath((string) ($options['reports-dir'] ?? 'reports'));
        $window = max(1, (int) ($options['window'] ?? 40));
        $limit = max(1, (int) ($options['limit'] ?? 20));
        $lane = (string) ($options['lane'] ?? 'all');
        $output = $this->normalizePath((string) ($options['output'] ?? ($reportsDir.'/flake-report-latest.md')));

        if (! is_dir($reportsDir)) {
            mkdir($reportsDir, 0777, true);
        }

        $logFiles = $this->recentLogFiles($reportsDir, $window);
        $failures = [];

        foreach ($logFiles as $logFile) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES) ?: [];
            foreach ($lines as $line) {
                $signature = $this->extractFailureSignature($line);
                if ($signature === null) {
                    continue;
                }

                $failures[$signature] = ($failures[$signature] ?? 0) + 1;
            }
        }

        arsort($failures);
        $topFailures = array_slice($failures, 0, $limit, true);

        file_put_contents($output, $this->buildReport($lane, $logFiles, $topFailures));

        echo 'Flake report generated: '.$output.PHP_EOL;
        echo 'Logs scanned: '.count($logFiles).PHP_EOL;
        echo 'Unique failure signatures: '.count($failures).PHP_EOL;

        return 0;
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

    private function extractFailureSignature(string $line): ?string
    {
        $trimmed = trim($line);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^FAILED\s+(.+)$/', $trimmed, $matches) === 1) {
            return $this->normalizeSignature($matches[1]);
        }

        if (preg_match('/^FAIL\s+(.+)$/', $trimmed, $matches) === 1) {
            return $this->normalizeSignature($matches[1]);
        }

        if (preg_match('/^\d+\)\s+(.+)$/', $trimmed, $matches) === 1) {
            return $this->normalizeSignature($matches[1]);
        }

        return null;
    }

    private function normalizeSignature(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?: trim($value);

        return $value;
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
     * @param array<string,int> $topFailures
     * @param list<string> $logFiles
     */
    private function buildReport(string $lane, array $logFiles, array $topFailures): string
    {
        $lines = [];
        $lines[] = '# Flake Trend Report';
        $lines[] = '';
        $lines[] = 'Generated: '.date('c');
        $lines[] = 'Lane: '.$lane;
        $lines[] = 'Log files scanned: '.count($logFiles);
        $lines[] = '';

        $lines[] = '## Top Failure Signatures';
        $lines[] = '';
        if ($topFailures === []) {
            $lines[] = '- No failure signatures detected in scanned logs.';
            $lines[] = '';
        } else {
            $lines[] = '| Signature | Count |';
            $lines[] = '|---|---:|';
            foreach ($topFailures as $signature => $count) {
                $escaped = str_replace('|', '\\|', $signature);
                $lines[] = '| '.$escaped.' | '.$count.' |';
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
