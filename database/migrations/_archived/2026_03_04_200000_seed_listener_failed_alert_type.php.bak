<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed the generic 'listener.failed' alert type for the ResilientListener trait.
 *
 * Any ShouldQueue listener that uses the ResilientListener trait will dispatch
 * alerts with this type code when all retries are exhausted.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('alert_types')) {
            return;
        }

        $now = now();

        DB::table('alert_types')->updateOrInsert(
            ['code' => 'listener.failed'],
            [
                'code'                 => 'listener.failed',
                'name'                 => 'Queued Listener Failed',
                'category'             => 'system',
                'description'          => 'Fires when a queued event listener fails permanently after exhausting all retry attempts. Indicates an event that was not processed and may require manual intervention.',
                'severity'             => 'error',
                'default_channels'     => json_encode(['mail', 'database']),
                'is_user_configurable' => true,
                'email_template'       => null,
                'slack_template'       => null,
                'is_active'            => true,
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('alert_types')) {
            return;
        }

        DB::table('alert_types')
            ->where('code', 'listener.failed')
            ->delete();
    }
};
