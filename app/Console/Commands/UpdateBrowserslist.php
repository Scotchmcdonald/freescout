<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class UpdateBrowserslist extends Command
{
    protected $signature = 'browserslist:update';

    protected $description = 'Update caniuse-lite browserslist database to the latest version';

    public function handle(): int
    {
        $this->info('Updating browserslist (caniuse-lite)...');

        $result = Process::path(base_path())->run('npx update-browserslist-db@latest --yes');

        if ($result->successful()) {
            $this->info('Browserslist updated successfully.');

            $output = trim($result->output());
            if ($output !== '') {
                $this->line($output);
            }

            return Command::SUCCESS;
        }

        $this->error('Browserslist update failed.');

        $combined = trim($result->output()."\n".$result->errorOutput());
        if ($combined !== '') {
            $this->line($combined);
        }

        return Command::FAILURE;
    }
}
