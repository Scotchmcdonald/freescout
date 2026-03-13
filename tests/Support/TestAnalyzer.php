<?php

declare(strict_types=1);

namespace Tests\Support;

use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Finder\Finder;
use Tests\Attributes\Flaky;
use Tests\Attributes\NonBatched;
use Tests\Attributes\NonParallel;

/**
 * Analyzes test files to detect patterns that indicate parallel/batch conflicts.
 *
 * This analyzer uses multiple detection strategies:
 * 1. Explicit attributes (#[NonParallel], #[NonBatched], #[Flaky])
 * 2. PHPUnit attributes (#[RunInSeparateProcess], #[RunTestsInSeparateProcesses])
 * 3. PHPUnit groups (#[Group('isolated')], #[Group('slow')])
 * 4. Code pattern heuristics (database locks, concurrency, process spawning)
 * 5. Historical failure data
 */
class TestAnalyzer
{
    /**
     * Categories for test isolation
     */
    public const CATEGORY_PARALLEL_SAFE = 'parallel_safe';
    public const CATEGORY_NON_PARALLEL = 'non_parallel';
    public const CATEGORY_NON_BATCHED = 'non_batched';

    /**
     * Groups that automatically categorize tests
     */
    private const ISOLATED_GROUPS = ['isolated', 'non-parallel', 'sequential'];
    private const NON_BATCHED_GROUPS = ['non-batched', 'singleton', 'alone'];

    /**
     * Code patterns that suggest isolation needs
     */
    private const NON_PARALLEL_PATTERNS = [
        // Database file operations
        '/database_path\s*\([\'"].*?\.sqlite/',
        '/unlink\s*\(\s*database_path/',
        '/touch\s*\(\s*database_path/',
        // Global state modification
        '/Config::set\s*\(/',
        '/config\s*\(\s*\[/',
        '/putenv\s*\(/',
        '/ini_set\s*\(/',
        // Cache flush (affects shared state)
        '/Cache::flush\s*\(/',
        '/Artisan::call\s*\(\s*[\'"]cache:clear/',
        '/Artisan::call\s*\(\s*[\'"]optimize:clear/',
        // Route/config cache modification
        '/Artisan::call\s*\(\s*[\'"]route:cache/',
        '/Artisan::call\s*\(\s*[\'"]config:cache/',
    ];

    private const NON_BATCHED_PATTERNS = [
        // Process spawning
        '/new\s+Process\s*\(/',
        '/Process::fromShellCommandline/',
        '/proc_open\s*\(/',
        '/exec\s*\(/',
        '/shell_exec\s*\(/',
        '/passthru\s*\(/',
        '/system\s*\(/',
        // Concurrency testing
        '/pcntl_fork\s*\(/',
        '/parallel\\\\/',
        // Migration/schema changes
        '/Artisan::call\s*\(\s*[\'"]migrate/',
        '/Schema::drop/',
        '/Schema::create/',
        // Sleep/timing tests
        '/sleep\s*\(\s*[2-9]\d*/',  // sleep > 1 second
        '/usleep\s*\(\s*[1-9]\d{6,}/',  // usleep > 1 second
        // File locking
        '/flock\s*\(/',
        '/LOCK_EX/',
    ];

    private string $baseDir;
    private array $testDirs;

    public function __construct(string $baseDir)
    {
        $this->baseDir = $baseDir;
        $this->testDirs = $this->discoverTestDirectories();
    }

    /**
     * Analyze all tests and return categorization
     */
    public function analyze(): array
    {
        $results = [
            self::CATEGORY_PARALLEL_SAFE => [],
            self::CATEGORY_NON_PARALLEL => [],
            self::CATEGORY_NON_BATCHED => [],
            'metadata' => [
                'analyzed_at' => date('Y-m-d H:i:s'),
                'total_files' => 0,
                'detection_reasons' => [],
            ],
        ];

        $finder = new Finder;
        $finder->files()
            ->in($this->testDirs)
            ->name('*Test.php')
            ->notPath('Browser')
            ->sortByName();

        foreach ($finder as $file) {
            $filePath = $file->getRealPath();
            $results['metadata']['total_files']++;

            $analysis = $this->analyzeFile($filePath);
            $category = $analysis['category'];

            $results[$category][] = [
                'file' => $filePath,
                'reasons' => $analysis['reasons'],
                'confidence' => $analysis['confidence'],
            ];

            // Track detection reasons for reporting
            foreach ($analysis['reasons'] as $reason) {
                $results['metadata']['detection_reasons'][$reason] =
                    ($results['metadata']['detection_reasons'][$reason] ?? 0) + 1;
            }
        }

        return $results;
    }

    /**
     * Analyze a single test file
     */
    public function analyzeFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $reasons = [];
        $confidence = 0.0;

        // Check explicit attributes via reflection (if class can be loaded)
        $classAnalysis = $this->analyzeClassAttributes($filePath, $content);
        if ($classAnalysis['category'] === self::CATEGORY_NON_BATCHED) {
            return [
                'category' => self::CATEGORY_NON_BATCHED,
                'reasons' => $classAnalysis['reasons'],
                'confidence' => 1.0,
            ];
        }
        if ($classAnalysis['category'] === self::CATEGORY_NON_PARALLEL) {
            $reasons = array_merge($reasons, $classAnalysis['reasons']);
            $confidence = max($confidence, 0.9);
        }

        // Check PHPUnit attributes in source code
        $phpunitAnalysis = $this->analyzePHPUnitAttributes($content);
        if ($phpunitAnalysis['is_non_batched']) {
            return [
                'category' => self::CATEGORY_NON_BATCHED,
                'reasons' => array_merge($reasons, $phpunitAnalysis['reasons']),
                'confidence' => 1.0,
            ];
        }
        if ($phpunitAnalysis['is_non_parallel']) {
            $reasons = array_merge($reasons, $phpunitAnalysis['reasons']);
            $confidence = max($confidence, 0.9);
        }

        // Check code patterns
        $patternAnalysis = $this->analyzeCodePatterns($content);
        if ($patternAnalysis['is_non_batched']) {
            $reasons = array_merge($reasons, $patternAnalysis['reasons']);
            $confidence = max($confidence, $patternAnalysis['confidence']);

            // High confidence pattern match for non-batched
            if ($patternAnalysis['confidence'] >= 0.8) {
                return [
                    'category' => self::CATEGORY_NON_BATCHED,
                    'reasons' => $reasons,
                    'confidence' => $patternAnalysis['confidence'],
                ];
            }
        }
        if ($patternAnalysis['is_non_parallel']) {
            $reasons = array_merge($reasons, $patternAnalysis['reasons']);
            $confidence = max($confidence, $patternAnalysis['confidence']);
        }

        // Determine final category
        if ($confidence >= 0.7 && count($reasons) > 0) {
            // Check if reasons suggest non-batched
            $nonBatchedReasons = array_filter(
                $reasons,
                fn ($r) => str_contains($r, 'Process') ||
                str_contains($r, 'concurren') ||
                str_contains($r, 'migrate') ||
                str_contains($r, 'fork')
            );

            if (count($nonBatchedReasons) > 0) {
                return [
                    'category' => self::CATEGORY_NON_BATCHED,
                    'reasons' => $reasons,
                    'confidence' => $confidence,
                ];
            }

            return [
                'category' => self::CATEGORY_NON_PARALLEL,
                'reasons' => $reasons,
                'confidence' => $confidence,
            ];
        }

        return [
            'category' => self::CATEGORY_PARALLEL_SAFE,
            'reasons' => $reasons ?: ['No isolation indicators detected'],
            'confidence' => 1.0 - $confidence,
        ];
    }

    /**
     * Analyze class attributes via reflection
     */
    private function analyzeClassAttributes(string $filePath, string $content): array
    {
        $reasons = [];
        $category = self::CATEGORY_PARALLEL_SAFE;

        // Extract class name from file
        $className = $this->extractClassName($content);
        if (! $className) {
            return ['category' => $category, 'reasons' => $reasons];
        }

        // Try to load and reflect the class
        try {
            // We need to check if class is already loaded or can be autoloaded
            if (! class_exists($className, false)) {
                // Don't autoload, just check source code for attributes
                return $this->analyzeAttributesFromSource($content);
            }

            $reflection = new ReflectionClass($className);

            // Check class-level attributes
            foreach ($reflection->getAttributes() as $attribute) {
                $attrName = $attribute->getName();

                if ($attrName === NonBatched::class) {
                    $instance = $attribute->newInstance();
                    $reasons[] = 'Explicit #[NonBatched]'.($instance->reason ? ": {$instance->reason}" : '');

                    return ['category' => self::CATEGORY_NON_BATCHED, 'reasons' => $reasons];
                }

                if ($attrName === NonParallel::class) {
                    $instance = $attribute->newInstance();
                    $reasons[] = 'Explicit #[NonParallel]'.($instance->reason ? ": {$instance->reason}" : '');
                    $category = self::CATEGORY_NON_PARALLEL;
                }

                if ($attrName === Flaky::class) {
                    $instance = $attribute->newInstance();
                    $reasons[] = 'Marked as #[Flaky]'.($instance->reason ? ": {$instance->reason}" : '');
                    // Flaky tests should be non-parallel at minimum
                    if ($category === self::CATEGORY_PARALLEL_SAFE) {
                        $category = self::CATEGORY_NON_PARALLEL;
                    }
                }
            }

            // Check method-level attributes
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (! str_starts_with($method->getName(), 'test')) {
                    continue;
                }

                foreach ($method->getAttributes() as $attribute) {
                    $attrName = $attribute->getName();

                    if ($attrName === NonBatched::class) {
                        return ['category' => self::CATEGORY_NON_BATCHED, 'reasons' => ['Method-level #[NonBatched]']];
                    }

                    if ($attrName === NonParallel::class && $category === self::CATEGORY_PARALLEL_SAFE) {
                        $category = self::CATEGORY_NON_PARALLEL;
                        $reasons[] = 'Method-level #[NonParallel]';
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fall back to source analysis if reflection fails
            return $this->analyzeAttributesFromSource($content);
        }

        return ['category' => $category, 'reasons' => $reasons];
    }

    /**
     * Analyze attributes directly from source code
     */
    private function analyzeAttributesFromSource(string $content): array
    {
        $reasons = [];
        $category = self::CATEGORY_PARALLEL_SAFE;

        // Check for NonBatched attribute
        if (preg_match('/#\[NonBatched(?:\([\'"]([^\'"]*)[\'"])?/', $content, $matches)) {
            $reason = 'Explicit #[NonBatched]';
            if (! empty($matches[1])) {
                $reason .= ": {$matches[1]}";
            }

            return ['category' => self::CATEGORY_NON_BATCHED, 'reasons' => [$reason]];
        }

        // Check for NonParallel attribute
        if (preg_match('/#\[NonParallel(?:\([\'"]([^\'"]*)[\'"])?/', $content, $matches)) {
            $reason = 'Explicit #[NonParallel]';
            if (! empty($matches[1])) {
                $reason .= ": {$matches[1]}";
            }
            $category = self::CATEGORY_NON_PARALLEL;
            $reasons[] = $reason;
        }

        // Check for Flaky attribute
        if (preg_match('/#\[Flaky(?:\([\'"]([^\'"]*)[\'"])?/', $content, $matches)) {
            $reason = 'Marked as #[Flaky]';
            if (! empty($matches[1])) {
                $reason .= ": {$matches[1]}";
            }
            if ($category === self::CATEGORY_PARALLEL_SAFE) {
                $category = self::CATEGORY_NON_PARALLEL;
            }
            $reasons[] = $reason;
        }

        return ['category' => $category, 'reasons' => $reasons];
    }

    /**
     * Analyze PHPUnit-specific attributes
     */
    private function analyzePHPUnitAttributes(string $content): array
    {
        $reasons = [];
        $isNonParallel = false;
        $isNonBatched = false;

        // Check for RunTestsInSeparateProcesses (class-level)
        if (preg_match('/#\[RunTestsInSeparateProcesses\]/', $content)) {
            $reasons[] = 'Uses #[RunTestsInSeparateProcesses]';
            $isNonBatched = true;
        }

        // Check for RunInSeparateProcess (method-level, but affects whole file behavior)
        if (preg_match_all('/#\[RunInSeparateProcess\]/', $content, $matches)) {
            $count = count($matches[0]);
            $reasons[] = "Has {$count} methods with #[RunInSeparateProcess]";
            $isNonParallel = true;

            // Many separate process methods suggest the whole file needs isolation
            if ($count >= 3) {
                $isNonBatched = true;
            }
        }

        // Check for isolated group
        if (preg_match('/#\[Group\([\'"]('.implode('|', self::ISOLATED_GROUPS).')[\'"]\)\]/', $content, $matches)) {
            $reasons[] = "In group: {$matches[1]}";
            $isNonParallel = true;
        }

        // Check for non-batched group
        if (preg_match('/#\[Group\([\'"]('.implode('|', self::NON_BATCHED_GROUPS).')[\'"]\)\]/', $content, $matches)) {
            $reasons[] = "In group: {$matches[1]}";
            $isNonBatched = true;
        }

        // Check for PreserveGlobalState(false) - suggests process isolation
        if (preg_match('/#\[PreserveGlobalState\(false\)\]/', $content)) {
            $reasons[] = 'Uses #[PreserveGlobalState(false)]';
            $isNonParallel = true;
        }

        // Check for @runInSeparateProcess docblock annotation (legacy)
        if (preg_match('/@runInSeparateProcess/', $content)) {
            $reasons[] = 'Uses @runInSeparateProcess annotation';
            $isNonParallel = true;
        }

        // Check for @runTestsInSeparateProcesses docblock annotation (legacy)
        if (preg_match('/@runTestsInSeparateProcesses/', $content)) {
            $reasons[] = 'Uses @runTestsInSeparateProcesses annotation';
            $isNonBatched = true;
        }

        return [
            'is_non_parallel' => $isNonParallel,
            'is_non_batched' => $isNonBatched,
            'reasons' => $reasons,
        ];
    }

    /**
     * Analyze code patterns that suggest isolation needs
     */
    private function analyzeCodePatterns(string $content): array
    {
        $reasons = [];
        $isNonParallel = false;
        $isNonBatched = false;
        $confidence = 0.0;

        // Check for non-batched patterns
        foreach (self::NON_BATCHED_PATTERNS as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $patternDesc = $this->describePattern($pattern);
                $reasons[] = "Code pattern: {$patternDesc}";
                $isNonBatched = true;
                $confidence = max($confidence, 0.85);
            }
        }

        // Check for non-parallel patterns
        foreach (self::NON_PARALLEL_PATTERNS as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $patternDesc = $this->describePattern($pattern);
                $reasons[] = "Code pattern: {$patternDesc}";
                $isNonParallel = true;
                $confidence = max($confidence, 0.75);
            }
        }

        // Check for "Concurrency" in class name or test methods
        if (preg_match('/class\s+\w*Concurrency\w*Test/', $content)) {
            $reasons[] = 'Class name suggests concurrency testing';
            $isNonBatched = true;
            $confidence = max($confidence, 0.9);
        }

        // Check for file-based SQLite database setup
        if (preg_match('/testing\.sqlite|test\.sqlite|\.sqlite.*database_path/', $content)) {
            $reasons[] = 'Uses file-based SQLite database';
            $isNonBatched = true;
            $confidence = max($confidence, 0.8);
        }

        return [
            'is_non_parallel' => $isNonParallel,
            'is_non_batched' => $isNonBatched,
            'reasons' => $reasons,
            'confidence' => $confidence,
        ];
    }

    /**
     * Convert regex pattern to human-readable description
     */
    private function describePattern(string $pattern): string
    {
        $descriptions = [
            '/database_path/' => 'database file operations',
            '/unlink/' => 'file deletion',
            '/touch/' => 'file creation',
            '/Config::set/' => 'runtime config modification',
            '/config\s*\(\s*\[/' => 'runtime config modification',
            '/putenv/' => 'environment variable modification',
            '/ini_set/' => 'PHP ini modification',
            '/Cache::flush/' => 'cache flush',
            '/optimize:clear/' => 'cache clearing',
            '/route:cache/' => 'route cache modification',
            '/config:cache/' => 'config cache modification',
            '/new\s+Process/' => 'process spawning',
            '/Process::fromShellCommandline/' => 'shell process spawning',
            '/proc_open/' => 'process spawning',
            '/exec\s*\(/' => 'shell execution',
            '/shell_exec/' => 'shell execution',
            '/passthru/' => 'shell execution',
            '/system\s*\(/' => 'shell execution',
            '/pcntl_fork/' => 'process forking',
            '/parallel/' => 'parallel processing',
            '/migrate/' => 'database migration',
            '/Schema::drop/' => 'schema modification',
            '/Schema::create/' => 'schema modification',
            '/sleep\s*\(/' => 'long sleep (>1s)',
            '/usleep/' => 'long usleep (>1s)',
            '/flock/' => 'file locking',
            '/LOCK_EX/' => 'exclusive file locking',
        ];

        foreach ($descriptions as $key => $desc) {
            if (str_contains($pattern, trim($key, '/'))) {
                return $desc;
            }
        }

        return 'unknown pattern';
    }

    /**
     * Extract fully qualified class name from file content
     */
    private function extractClassName(string $content): ?string
    {
        $namespace = '';
        $class = '';

        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = $matches[1];
        }

        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            $class = $matches[1];
        }

        if ($class) {
            return $namespace ? "{$namespace}\\{$class}" : $class;
        }

        return null;
    }

    /**
     * Discover all test directories
     */
    private function discoverTestDirectories(): array
    {
        $dirs = [$this->baseDir.'/tests'];

        foreach (glob($this->baseDir.'/Modules/*/Tests') as $moduleTestDir) {
            $dirs[] = $moduleTestDir;
        }

        return array_filter($dirs, 'is_dir');
    }

    /**
     * Generate a summary report
     */
    public function generateReport(array $analysis): string
    {
        $report = [];
        $report[] = '# Test Isolation Analysis Report';
        $report[] = "Generated: {$analysis['metadata']['analyzed_at']}";
        $report[] = '';
        $report[] = '## Summary';
        $report[] = "- Total test files: {$analysis['metadata']['total_files']}";
        $report[] = '- Parallel-safe: '.count($analysis[self::CATEGORY_PARALLEL_SAFE]);
        $report[] = '- Non-parallel: '.count($analysis[self::CATEGORY_NON_PARALLEL]);
        $report[] = '- Non-batched: '.count($analysis[self::CATEGORY_NON_BATCHED]);
        $report[] = '';

        if (! empty($analysis['metadata']['detection_reasons'])) {
            $report[] = '## Detection Reasons';
            arsort($analysis['metadata']['detection_reasons']);
            foreach ($analysis['metadata']['detection_reasons'] as $reason => $count) {
                $report[] = "- {$reason}: {$count}";
            }
            $report[] = '';
        }

        if (! empty($analysis[self::CATEGORY_NON_BATCHED])) {
            $report[] = '## Non-Batched Tests (run alone)';
            foreach ($analysis[self::CATEGORY_NON_BATCHED] as $test) {
                $relative = str_replace($this->baseDir.'/', '', $test['file']);
                $reasons = implode(', ', $test['reasons']);
                $report[] = "- `{$relative}`: {$reasons}";
            }
            $report[] = '';
        }

        if (! empty($analysis[self::CATEGORY_NON_PARALLEL])) {
            $report[] = '## Non-Parallel Tests (run sequentially)';
            foreach ($analysis[self::CATEGORY_NON_PARALLEL] as $test) {
                $relative = str_replace($this->baseDir.'/', '', $test['file']);
                $reasons = implode(', ', $test['reasons']);
                $report[] = "- `{$relative}`: {$reasons}";
            }
            $report[] = '';
        }

        return implode("\n", $report);
    }
}
