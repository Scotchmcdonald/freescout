<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Billing\Database\Seeders\MspScenarioSeeder;

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
    public function handle()
    {
        $action = $this->argument('action');
        $seeder = new MspScenarioSeeder();
        
        // Inject command context into seeder so it can output info
        $seeder->setCommand($this);

        if ($action === 'seed') {
            $this->info('Deploying MSP Demo Scenario...');
            $seeder->run();
        } elseif ($action === 'clean') {
            $this->info('Cleaning MSP Demo Scenario...');
            $seeder->reverse();
        } else {
            $this->error('Invalid action. Use "seed" or "clean".');
            return 1;
        }

        return 0;
    }
}
