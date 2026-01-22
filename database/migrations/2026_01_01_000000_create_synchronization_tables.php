<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Processed Events (Idempotency)
        Schema::create('processed_events', function (Blueprint $table) {
            $table->string('event_id')->index();
            $table->string('handler_class');
            $table->timestamp('processed_at');
            $table->unique(['event_id', 'handler_class'], 'processed_events_unique');
        });

        // 2. Sync Operations
        Schema::create('sync_operations', function (Blueprint $table) {
            $table->id();
            $table->string('operation_type'); // 'google_users', 'action1_devices', etc.
            $table->string('source'); // 'GoogleAdmin', 'Action1'
            $table->string('status')->default('running'); // running, completed, failed, stalled, paused
            $table->integer('total_items')->default(0);
            $table->integer('processed_items')->default(0);
            $table->integer('failed_items')->default(0);
            $table->integer('success_items')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_progress_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('checkpoint_data')->nullable(); // For resume capability
            $table->json('failures')->nullable(); // Array of failed items with reasons
            $table->decimal('items_per_second', 8, 2)->nullable();
            $table->timestamps();
            
            $table->index(['status', 'started_at']);
            $table->index('operation_type');
        });

        // 3. Reconciliation Runs
        Schema::create('reconciliation_runs', function (Blueprint $table) {
            $table->id();
            $table->string('run_type', 50); // 'weekly', 'manual', 'on-demand'
            $table->enum('status', ['running', 'completed', 'failed', 'partial'])->default('running');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            
            // Scope and coverage
            $table->json('scope')->nullable(); // Which clients/assets were checked
            $table->integer('items_checked')->default(0);
            
            // Discrepancy counts
            $table->integer('total_discrepancies')->default(0);
            $table->integer('auto_corrected')->default(0);
            $table->integer('manual_review_required')->default(0);
            $table->integer('critical_issues')->default(0);
            
            // Results summary
            $table->decimal('success_rate', 5, 2)->nullable(); // Percentage
            $table->text('summary')->nullable();
            $table->json('metadata')->nullable(); // Additional context
            
            // Performance metrics
            $table->integer('duration_seconds')->nullable();
            $table->string('triggered_by')->nullable(); // 'scheduler', 'admin', 'api'
            
            $table->timestamps();
            
            // Indexes for common queries
            $table->index('status');
            $table->index('started_at');
            $table->index('run_type');
            $table->index(['status', 'started_at']);
        });

        // 4. Reconciliation Discrepancies
        Schema::create('reconciliation_discrepancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_run_id')->constrained()->onDelete('cascade');
            
            // Discrepancy details
            $table->string('entity_type', 50); // 'asset', 'user', 'license', 'subscription'
            $table->string('entity_id')->nullable(); // ID of the affected entity
            $table->string('field_name', 100); // Which field has the discrepancy
            
            // Value comparison
            $table->text('expected_value')->nullable();
            $table->text('actual_value')->nullable();
            $table->text('source_system')->nullable(); // 'google', 'action1', 'billing'
            
            // Classification
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('resolution_status', ['pending', 'auto_corrected', 'manual_review', 'resolved', 'ignored'])->default('pending');
            
            // Resolution tracking
            $table->text('resolution_action')->nullable(); // What was done to resolve
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution_notes')->nullable();
            
            // Additional context
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('reconciliation_run_id');
            $table->index('entity_type');
            $table->index('resolution_status');
            $table->index('severity');
            $table->index(['resolution_status', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_discrepancies');
        Schema::dropIfExists('reconciliation_runs');
        Schema::dropIfExists('sync_operations');
        Schema::dropIfExists('processed_events');
    }
};
