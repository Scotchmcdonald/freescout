<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ManageMspDemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'msp:demo {action : The action to perform (seed|clean)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy or Clean the MSP Demo Scenario data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->error('MspScenarioSeeder is missing. Command disabled.');
        return 1;
    }
}
