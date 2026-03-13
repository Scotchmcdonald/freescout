<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Generate application variables
 *
 * Note: Legacy functionality. Previously generated JS variables.
 * In the simplified/modern version, this primarily handles config caching.
 */
class GenerateVars extends Command
{
    protected $signature = 'freescout:generate-vars';

    protected $description = 'Generate application variables (Legacy Wrapper)';

    public function handle(): int
    {
        // Regenerate config cache as this is the primary remaining utility
        $this->call('config:cache');

        $this->info('Application variables generated successfully.');

        return self::SUCCESS;
    }
}
