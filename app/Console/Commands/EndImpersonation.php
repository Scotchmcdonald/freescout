<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EndImpersonation extends Command
{
    protected $signature = 'impersonate:end
        {--all : End all active impersonation sessions}
        {--user= : End impersonation for a specific admin user ID}';

    protected $description = 'Emergency: end stuck impersonation sessions by clearing session data';

    public function handle(): int
    {
        $driver = config('session.driver');

        if ($driver !== 'database') {
            $this->error("This command only supports the 'database' session driver. Current driver: {$driver}");
            $this->info("For file sessions, delete files in: " . storage_path('framework/sessions'));

            return self::FAILURE;
        }

        $table = config('session.table', 'sessions');
        $sessionKey = config('laravel-impersonate.session_key', 'impersonated_by');

        if ($this->option('all')) {
            $affected = DB::table($table)
                ->where('payload', 'LIKE', "%{$sessionKey}%")
                ->delete();

            Log::warning('Emergency: all impersonation sessions force-ended via artisan', [
                'sessions_cleared' => $affected,
            ]);

            $this->info("✓ Cleared {$affected} session(s) with active impersonation.");

            return self::SUCCESS;
        }

        $userId = $this->option('user');

        if ($userId) {
            // For database sessions, we need to search payloads.
            // The admin_id is stored in the serialized payload. We search for it
            // and delete matching sessions.
            $affected = DB::table($table)
                ->where('payload', 'LIKE', "%{$sessionKey}%")
                ->where('user_id', $userId)
                ->delete();

            if ($affected === 0) {
                // The session might be under the impersonated user's ID, not the admin's
                $affected = DB::table($table)
                    ->where('payload', 'LIKE', "%{$sessionKey}%")
                    ->delete();

                $this->warn("Could not target user {$userId} specifically. Cleared {$affected} impersonation session(s) globally.");
            } else {
                $this->info("✓ Cleared {$affected} session(s) for user {$userId}.");
            }

            Log::warning('Emergency: impersonation sessions force-ended via artisan', [
                'target_user_id' => $userId,
                'sessions_cleared' => $affected,
            ]);

            return self::SUCCESS;
        }

        // Interactive: show active impersonation sessions
        $sessions = DB::table($table)
            ->where('payload', 'LIKE', "%{$sessionKey}%")
            ->get(['id', 'user_id', 'last_activity']);

        if ($sessions->isEmpty()) {
            $this->info('No active impersonation sessions found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Session ID', 'User ID', 'Last Activity'],
            $sessions->map(fn ($s) => [
                substr($s->id, 0, 12) . '...',
                $s->user_id ?? 'N/A',
                date('Y-m-d H:i:s', $s->last_activity),
            ])
        );

        if ($this->confirm("Clear all {$sessions->count()} impersonation session(s)?")) {
            DB::table($table)
                ->where('payload', 'LIKE', "%{$sessionKey}%")
                ->delete();

            Log::warning('Emergency: impersonation sessions cleared interactively via artisan', [
                'sessions_cleared' => $sessions->count(),
            ]);

            $this->info("✓ Cleared {$sessions->count()} session(s).");
        }

        return self::SUCCESS;
    }
}
