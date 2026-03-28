<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Services;

use Illuminate\Contracts\Container\Container;
use ReflectionClass;
use Throwable;

/**
 * Builds a visual topology map of Event → Listener relationships.
 *
 * Data sources:
 *  1. EventServiceProvider::$listen arrays (app + all modules)
 *  2. PHP Reflection on discovered event classes (constructor dependencies)
 *  3. Listener class inspection (which events they handle)
 *
 * Output: JSON structure with `nodes` and `edges` arrays suitable for
 * rendering with D3.js, Cytoscape, or any graph visualization library.
 */
final class TopologyBuilder
{
    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * Build the complete topology graph.
     *
     * @return array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>, metadata: array<string, mixed>}
     */
    public function build(): array
    {
        $mappings = $this->collectEventListenerMappings();
        $nodes = [];
        $edges = [];
        /** @var array<string, string> $nodeIndex */
        $nodeIndex = [];

        foreach ($mappings as $eventClass => $listeners) {
            // Create event node
            $eventNode = $this->makeEventNode($eventClass, $nodeIndex);
            if ($eventNode !== null) {
                $nodes[] = $eventNode;
                $nodeIndex[$eventClass] = $eventNode['id'];
            }

            foreach ($listeners as $listenerClass) {
                // Create listener node
                $listenerNode = $this->makeListenerNode($listenerClass, $nodeIndex);
                if ($listenerNode !== null) {
                    $nodes[] = $listenerNode;
                    $nodeIndex[$listenerClass] = $listenerNode['id'];
                }

                // Create edge: Event → Listener
                $edges[] = [
                    'source' => $nodeIndex[$eventClass] ?? $eventClass,
                    'target' => $nodeIndex[$listenerClass] ?? $listenerClass,
                    'type' => 'listens_to',
                ];
            }
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'total_events' => count(array_filter($nodes, fn (array $n) => $n['type'] === 'event')),
                'total_listeners' => count(array_filter($nodes, fn (array $n) => $n['type'] === 'listener')),
                'total_edges' => count($edges),
            ],
        ];
    }

    /**
     * Collect all Event → Listener[] mappings from EventServiceProviders.
     *
     * @return array<string, list<string>>
     */
    private function collectEventListenerMappings(): array
    {
        $mappings = [];

        // Discover all EventServiceProvider classes
        $providerClasses = $this->discoverEventServiceProviders();

        foreach ($providerClasses as $providerClass) {
            try {
                $ref = new ReflectionClass($providerClass); // @phpstan-ignore argument.type

                // Check for $listen property
                if ($ref->hasProperty('listen')) {
                    $prop = $ref->getProperty('listen');
                    $prop->setAccessible(true);

                    $provider = $this->container->make($providerClass);
                    $listen = $prop->getValue($provider);

                    if (is_array($listen)) {
                        foreach ($listen as $event => $listeners) {
                            $mappings[$event] = array_merge(
                                $mappings[$event] ?? [],
                                is_array($listeners) ? $listeners : [$listeners],
                            );
                        }
                    }
                }

                // Check for listens() method (Laravel 11+ convention)
                if ($ref->hasMethod('listens')) {
                    $method = $ref->getMethod('listens');
                    if ($method->isPublic() && $method->getNumberOfParameters() === 0) {
                        $provider = $provider ?? $this->container->make($providerClass);
                        $listens = $provider->listens();
                        if (is_array($listens)) {
                            foreach ($listens as $event => $listeners) {
                                $mappings[$event] = array_merge(
                                    $mappings[$event] ?? [],
                                    is_array($listeners) ? $listeners : [$listeners],
                                );
                            }
                        }
                    }
                }
            } catch (Throwable) {
                // Skip providers that cannot be resolved
            }
        }

        // De-duplicate listeners per event
        foreach ($mappings as $event => $listeners) {
            $mappings[$event] = array_values(array_unique($listeners));
        }

        /** @var array<string, list<string>> $mappings */
        return $mappings;
    }

    /**
     * Discover all EventServiceProvider classes across app and modules.
     *
     * @return list<string>
     */
    private function discoverEventServiceProviders(): array
    {
        $providers = [];

        // Core app provider
        $coreProvider = 'App\\Providers\\EventServiceProvider';
        if (class_exists($coreProvider)) {
            $providers[] = $coreProvider;
        }

        // Module providers
        $modulesPath = base_path('Modules');
        if (is_dir($modulesPath)) {
            $dirs = glob($modulesPath.'/*/Providers/EventServiceProvider.php');
            if ($dirs !== false) {
                foreach ($dirs as $file) {
                    // Extract module name from path
                    if (preg_match('#Modules/([^/]+)/Providers/EventServiceProvider\.php$#', $file, $matches)) {
                        $class = "Modules\\{$matches[1]}\\Providers\\EventServiceProvider";
                        if (class_exists($class)) {
                            $providers[] = $class;
                        }
                    }
                }
            }
        }

        return $providers;
    }

    /**
     * Detect which module a class belongs to.
     */
    private function detectModule(string $className): string
    {
        if (str_starts_with($className, 'Modules\\')) {
            $parts = explode('\\', $className);

            return $parts[1] ?? 'Unknown';
        }

        if (str_starts_with($className, 'App\\')) {
            return 'App';
        }

        return 'Vendor';
    }

    /**
     * @param array<string, mixed> $index
     * @return array<string, mixed>|null
     */
    private function makeEventNode(string $class, array &$index): ?array
    {
        if (isset($index[$class])) {
            return null; // Already created
        }

        $node = [
            'id' => 'event:'.$class,
            'label' => class_basename($class),
            'type' => 'event',
            'fqcn' => $class,
            'module' => $this->detectModule($class),
            'exists' => class_exists($class),
        ];

        // If class exists, extract constructor parameters for context
        if ($node['exists']) {
            try {
                $ref = new ReflectionClass($class); // @phpstan-ignore argument.type
                $constructor = $ref->getConstructor();
                $node['parameters'] = $constructor !== null
                    ? array_map(fn (\ReflectionParameter $p): array => [
                        'name' => $p->getName(),
                        'type' => $this->getParameterTypeName($p),
                    ], $constructor->getParameters())
                    : [];
            } catch (Throwable) {
                $node['parameters'] = [];
            }
        }

        $index[$class] = $node['id'];

        return $node;
    }

    /**
     * @param array<string, mixed> $index
     * @return array<string, mixed>|null
     */
    private function makeListenerNode(string $class, array &$index): ?array
    {
        if (isset($index[$class])) {
            return null; // Already created
        }

        $node = [
            'id' => 'listener:'.$class,
            'label' => class_basename($class),
            'type' => 'listener',
            'fqcn' => $class,
            'module' => $this->detectModule($class),
            'exists' => class_exists($class),
        ];

        $index[$class] = $node['id'];

        return $node;
    }

    private function getParameterTypeName(\ReflectionParameter $param): string
    {
        $type = $param->getType();

        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }

        if ($type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType) {
            return implode('|', array_map(
                fn (\ReflectionType $t): string => $t instanceof \ReflectionNamedType ? $t->getName() : (string) $t,
                $type->getTypes(),
            ));
        }

        return 'mixed';
    }
}
