<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CacheService;
use Illuminate\Console\Command;
use Modules\Crm\Models\Client;
// use Modules\PIB\Services\EntitlementService; // Core Blindness

/**
 * Pre-warm frequently accessed caches for improved performance.
 */
class WarmCache extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:warm 
                            {--clients=100 : Number of clients to warm}
                            {--force : Force warming even if cache exists}';

    /**
     * The console command description.
     */
    protected $description = 'Pre-warm frequently accessed caches';

    /**
     * Execute the console command.
     */
    public function handle(CacheService $cache): int
    {
        $this->info('🔥 Warming cache...');

        $clientCount = (int) $this->option('clients');
        $force = $this->option('force');

        // Warm most active clients (by recent activity)
        $clients = Client::query()
            ->where('status', 'active')
            ->orderByDesc('last_activity_at')
            ->limit($clientCount)
            ->get();

        if ($clients->isEmpty()) {
            $this->warn('No active clients found to warm cache for.');
            return Command::SUCCESS;
        }

        $this->info("Found {$clients->count()} active clients");

        $bar = $this->output->createProgressBar($clients->count());
        $bar->start();

        $warmed = 0;
        $skipped = 0;

        foreach ($clients as $client) {
            try {
                // Check if already cached (unless force flag is set)
                if (!$force && $cache->has('billing', 'entitlement', $client->id, 'current')) {
                    $skipped++;
                } else {
                    // This will populate cache with fresh data
                    // Core Blindness: Dynamically resolve EntitlementService if PIB module is present
                    $serviceClass = '\Modules\PIB\Services\EntitlementService';
                    if (class_exists($serviceClass)) {
                        app($serviceClass)->getCurrentEntitlements($client->id);
                        $warmed++;
                    }
                }
            } catch (\Exception $e) {
                $this->error("\nFailed to warm cache for client {$client->id}: {$e->getMessage()}");
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("✅ Cache warming complete!");
        $this->line("   • Warmed: {$warmed} clients");
        $this->line("   • Skipped (already cached): {$skipped} clients");
        $this->line("   • Total processed: {$clients->count()} clients");

        return Command::SUCCESS;
    }
}
