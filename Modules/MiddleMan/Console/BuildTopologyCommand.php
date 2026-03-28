<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Console;

use Illuminate\Console\Command;
use Modules\MiddleMan\Services\TopologyBuilder;

/**
 * Generates a JSON topology map of all Event → Listener relationships
 * across the application and all modules.
 *
 * Output can be piped into a file or consumed by a frontend graph renderer.
 */
class BuildTopologyCommand extends Command
{
    protected $signature = 'middleman:build-topology
                            {--output= : Path to write the JSON file (default: stdout)}
                            {--pretty : Pretty-print the JSON output}';

    protected $description = 'Build a topology map of Event → Listener relationships';

    public function handle(TopologyBuilder $builder): int
    {
        $this->components->info('Scanning EventServiceProviders...');

        $topology = $builder->build();

        $flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES;
        if ($this->option('pretty')) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $json = json_encode($topology, $flags);

        $outputPath = $this->option('output');
        if (is_string($outputPath) && $outputPath !== '') {
            $dir = dirname($outputPath);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($outputPath, $json);
            $this->components->info("Topology written to: {$outputPath}");
        } else {
            $this->line($json);
        }

        $this->components->info(sprintf(
            'Discovered %d events, %d listeners, %d edges.',
            (int) $topology['metadata']['total_events'], // @phpstan-ignore cast.int
            (int) $topology['metadata']['total_listeners'], // @phpstan-ignore cast.int
            (int) $topology['metadata']['total_edges'], // @phpstan-ignore cast.int
        ));

        return self::SUCCESS;
    }
}
