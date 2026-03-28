<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Services;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Symfony\Component\Finder\Finder;
use Throwable;

/**
 * Scans configured directories for event classes and extracts
 * constructor signatures via Reflection for the Marshalling UI.
 */
final class EventScanner
{
    /**
     * @return array<int, array{class: string, name: string, parameters: array<int, array{name: string, type: string, required: bool, default: mixed}>}>
     */
    public function discover(): array
    {
        $events = [];
        /** @var array<int|string, mixed> $scanPaths */
        $scanPaths = config('middleman.scan_paths', ['app/Events']);

        foreach ($scanPaths as $pattern) {
            $resolved = $this->resolveGlobPaths((string) $pattern); // @phpstan-ignore cast.string
            foreach ($resolved as $dir) {
                if (! is_dir($dir)) {
                    continue;
                }

                $events = array_merge($events, $this->scanDirectory($dir));
            }
        }

        // De-duplicate by class name
        $unique = [];
        foreach ($events as $event) {
            $unique[$event['class']] = $event;
        }

        // Sort by class name
        ksort($unique);

        return array_values($unique);
    }

    /**
     * Get constructor parameters for a specific event class.
     *
     * @return array<int, array{name: string, type: string, required: bool, default: mixed}>
     */
    public function getParameters(string $eventClass): array
    {
        if (! class_exists($eventClass)) {
            return [];
        }

        try {
            $ref = new ReflectionClass($eventClass);
            $constructor = $ref->getConstructor();

            if ($constructor === null) {
                return [];
            }

            return array_map(
                fn (ReflectionParameter $param): array => $this->describeParameter($param),
                $constructor->getParameters(),
            );
        } catch (Throwable) {
            return [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Internal
    |--------------------------------------------------------------------------
    */

    /** @return array<int, array{class: string, name: string, parameters: array<int, array{name: string, type: string, required: bool, default: mixed}>}> */
    private function scanDirectory(string $dir): array
    {
        $events = [];

        $finder = new Finder;
        $finder->files()->name('*.php')->in($dir);

        foreach ($finder as $file) {
            $class = $this->classFromFile($file->getRealPath());

            if ($class === null || ! class_exists($class)) {
                continue;
            }

            try {
                $ref = new ReflectionClass($class);

                // Skip abstract classes and interfaces
                if ($ref->isAbstract() || $ref->isInterface()) {
                    continue;
                }

                $events[] = [
                    'class' => $class,
                    'name' => $ref->getShortName(),
                    'parameters' => $this->getParameters($class),
                ];
            } catch (Throwable) {
                // Skip classes that can't be reflected
            }
        }

        return $events;
    }

    private function classFromFile(string $filePath): ?string
    {
        $contents = file_get_contents($filePath);

        if ($contents === false) {
            return null;
        }

        $namespace = null;
        $class = null;

        if (preg_match('/namespace\s+([^;\s]+)/m', $contents, $nsMatch)) {
            $namespace = $nsMatch[1];
        }

        if (preg_match('/class\s+(\w+)/m', $contents, $clsMatch)) {
            $class = $clsMatch[1];
        }

        if ($namespace && $class) {
            return $namespace.'\\'.$class;
        }

        return $class;
    }

    /** @return array{name: string, type: string, required: bool, default: mixed} */
    private function describeParameter(ReflectionParameter $param): array
    {
        $type = $param->getType();
        $typeName = 'mixed';

        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();
        }

        $desc = [
            'name' => $param->getName(),
            'type' => $typeName,
            'required' => ! $param->isOptional(),
            'default' => null,
        ];

        if ($param->isDefaultValueAvailable()) {
            try {
                $desc['default'] = $param->getDefaultValue();
            } catch (Throwable) {
                $desc['default'] = null;
            }
        }

        // Identify Eloquent model types for the UI to render a search/ID field
        if (class_exists($typeName) && is_subclass_of($typeName, \Illuminate\Database\Eloquent\Model::class)) {
            $desc['is_model'] = true;
            $desc['model_class'] = $typeName;
        }

        return $desc;
    }

    /**
     * Resolve glob-style paths like "Modules/ * /Events" to actual directories.
     *
     * @return string[]
     */
    private function resolveGlobPaths(string $pattern): array
    {
        $basePath = base_path($pattern);

        if (! str_contains($pattern, '*')) {
            return [$basePath];
        }

        $result = glob($basePath, GLOB_ONLYDIR);

        return $result !== false ? $result : [];
    }
}
