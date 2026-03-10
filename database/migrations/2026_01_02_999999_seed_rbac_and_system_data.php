<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed RBAC roles, permissions, and assignments.
 * Also cleans up legacy Action1 OAuth options.
 *
 * This migration is idempotent — uses updateOrInsert for all seeds.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Seed alert types for CaseManager AI pipeline ────────────────
        if (Schema::hasTable('alert_types')) {
            DB::table('alert_types')->updateOrInsert(
                ['code' => 'casemanager_api_error'],
                [
                    'name' => 'AI Pipeline Failure',
                    'category' => 'ai',
                    'description' => 'Fires when the CaseManager AI triage pipeline fails due to an API error, circuit breaker trip, or timeout. The affected case requires manual routing.',
                    'severity' => 'error',
                    'default_channels' => json_encode(['mail', 'database']),
                    'is_user_configurable' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            DB::table('alert_types')->updateOrInsert(
                ['code' => 'casemanager_model_deprecation'],
                [
                    'name' => 'Gemini Model Deprecation Detected',
                    'category' => 'ai',
                    'description' => 'Fires when a configured Gemini model is deprecated, sunset (removed from API), or when a significantly newer version is available.',
                    'severity' => 'warning',
                    'default_channels' => json_encode(['mail', 'database']),
                    'is_user_configurable' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        // ── Clean up legacy Action1 OAuth options ───────────────────────
        if (Schema::hasTable('options')) {
            DB::table('options')->whereIn('name', [
                'action1_oauth_client_id',
                'action1_client_secret',
                'action1_access_token',
                'action1_token_expires_at',
                'action1_region',
            ])->delete();
        }
    }

    public function down(): void
    {
        // WARNING: down() intentionally left non-destructive.
        // This migration consolidation is forward-only.
    }
};
