<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated: processed_events, sync_operations, reconciliation_runs,
 *               reconciliation_discrepancies
 *
 * Merged from:
 *  - 2026_01_01_000000_create_synchronization_tables.php
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── processed_events ────────────────────────────────────────────
        if (! Schema::hasTable('processed_events')) {
            Schema::create('processed_events', function (Blueprint $table) {
                $table->string('event_id')->index();
                $table->string('handler_class');
                $table->timestamp('processed_at');
                $table->unique(['event_id', 'handler_class']);
            });
        }

        // ── sync_operations ─────────────────────────────────────────────
        if (! Schema::hasTable('sync_operations')) {
            Schema::create('sync_operations', function (Blueprint $table) {
                $table->id();
                $table->string('operation_type');
                $table->string('source');
                $table->string('status')->default('running');
                $table->integer('total_items')->default(0);
                $table->integer('processed_items')->default(0);
                $table->integer('failed_items')->default(0);
                $table->integer('success_items')->default(0);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('last_progress_at')->nullable();
                $table->text('error_message')->nullable();
                $table->json('checkpoint_data')->nullable();
                $table->json('failures')->nullable();
                $table->decimal('items_per_second', 8, 2)->nullable();
                $table->timestamps();
                $table->index(['status', 'started_at']);
                $table->index('operation_type');
            });
        }

        // ── reconciliation_runs ─────────────────────────────────────────
        if (! Schema::hasTable('reconciliation_runs')) {
            Schema::create('reconciliation_runs', function (Blueprint $table) {
                $table->id();
                $table->string('run_type', 50);
                $table->enum('status', ['running', 'completed', 'failed', 'partial'])->default('running');
                $table->timestamp('started_at');
                $table->timestamp('completed_at')->nullable();
                $table->json('scope')->nullable();
                $table->integer('items_checked')->default(0);
                $table->integer('total_discrepancies')->default(0);
                $table->integer('auto_corrected')->default(0);
                $table->integer('manual_review_required')->default(0);
                $table->integer('critical_issues')->default(0);
                $table->decimal('success_rate', 5, 2)->nullable();
                $table->text('summary')->nullable();
                $table->json('metadata')->nullable();
                $table->integer('duration_seconds')->nullable();
                $table->string('triggered_by')->nullable();
                $table->timestamps();
                $table->index('status');
                $table->index('started_at');
                $table->index('run_type');
                $table->index(['status', 'started_at']);
            });
        }

        // ── reconciliation_discrepancies ────────────────────────────────
        if (! Schema::hasTable('reconciliation_discrepancies')) {
            Schema::create('reconciliation_discrepancies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reconciliation_run_id')->index();
                $table->string('entity_type', 50)->index();
                $table->string('entity_id')->nullable();
                $table->string('field_name', 100);
                $table->text('expected_value')->nullable();
                $table->text('actual_value')->nullable();
                $table->text('source_system')->nullable();
                $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium')->index();
                $table->enum('resolution_status', ['pending', 'auto_corrected', 'manual_review', 'resolved', 'ignored'])->default('pending')->index();
                $table->text('resolution_action')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->unsignedBigInteger('resolved_by')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['resolution_status', 'severity']);
                $table->foreign('reconciliation_run_id')->references('id')->on('reconciliation_runs')->cascadeOnDelete();
                $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // WARNING: down() intentionally left non-destructive.
        // This migration consolidation is forward-only.
    }
};
