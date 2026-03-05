<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Modules\Crm\Models\Company;

class PruneDemoAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:prune
        {--sandbox : Also remove persistent sandbox accounts (demo-*@sandbox.local)}
        {--force : Skip confirmation for sandbox account removal}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove expired demo accounts and their associated data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Finding expired demo accounts...');

        // Find demo users created more than 1 hour ago
        $query = User::where('is_demo', true)
            ->where('created_at', '<', now()->subHour());

        // Unless --sandbox flag, exclude persistent sandbox accounts
        if (! $this->option('sandbox')) {
            $query->where('email', 'not like', '%@sandbox.local');
        } elseif (! $this->option('force')) {
            if (! $this->confirm('This will also delete persistent sandbox accounts. Continue?')) {
                $this->info('Aborted.');
                return 0;
            }
        }

        $expiredUsers = $query->get();

        if ($expiredUsers->isEmpty()) {
            $this->info('No expired demo accounts found.');
            return 0;
        }

        $this->info("Found {$expiredUsers->count()} accounts to prune.");

        foreach ($expiredUsers as $user) {
            $this->processUserCleanup($user);
        }

        $this->info('Cleanup complete.');
        return 0;
    }

    private function processUserCleanup(User $user)
    {
        $this->comment("Cleaning up user {$user->email} (ID: {$user->id})...");

        // 1. Find companies owned/managed by this user
        $companies = Company::where('primary_contact_id', $user->id)->get();

        foreach ($companies as $company) {
            $this->line("  - Deleting company: {$company->name} (ID: {$company->id})");

            // Clean up Users tied to this company via pivot
            $this->cleanupClientUsers($company);

            try {
                $company->forceDelete();
            } catch (\Exception $e) {
                $company->delete();
            }
        }

        // 2. Delete the user
        try {
            $user->forceDelete();
        } catch (\Exception $e) {
            $user->delete();
        }
        
        $this->line("  - User deleted.");
    }

    /**
     * Remove company_user pivot records and external Users associated with a demo company.
     */
    private function cleanupClientUsers(Company $company): void
    {
        try {
            // Find users linked to this company via pivot
            $companyUsers = $company->users()->get();

            foreach ($companyUsers as $user) {
                $this->line("  - Detaching user: {$user->email}");
                // Detach from pivot
                $company->users()->detach($user->id);
                // If user is demo and has no other companies, delete them
                if ($user->is_demo && $user->companies()->count() === 0) {
                    try {
                        $user->forceDelete();
                    } catch (\Exception $e) {
                        $user->delete();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->warn("  - Failed to clean up company users: {$e->getMessage()}");
        }
    }
}
