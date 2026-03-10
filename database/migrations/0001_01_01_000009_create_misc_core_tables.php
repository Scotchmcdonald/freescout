<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated: saved_searches, milestones, notification_subscriptions, billing_templates
 *
 * Merged from:
 *  - 0001_01_01_000008_create_saved_searches_table.php
 *  - 2026_01_16_011530_create_milestones_table.php
 *  - 2026_02_10_000001_add_billing_fields_to_milestones.php
 *  - 2026_01_16_024012_create_notification_subscriptions_table.php
 *  - 2026_02_07_015945_create_billing_templates_table.php
 *
 * NOTE: Cross-module FKs on milestones (contract_id → cm_contracts, invoice_id → pib_invoices)
 *       are deferred to the cross-module FK migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── saved_searches ──────────────────────────────────────────────
        if (! Schema::hasTable('saved_searches')) {
            Schema::create('saved_searches', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('name', 255);
                $table->text('query')->nullable();
                $table->json('filters')->nullable();
                $table->boolean('is_default')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->index(['user_id', 'is_default']);
                $table->index(['user_id', 'sort_order']);
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        // ── milestones ──────────────────────────────────────────────────
        if (! Schema::hasTable('milestones')) {
            Schema::create('milestones', function (Blueprint $table) {
                $table->id();
                $table->string('project_type')->nullable();
                $table->unsignedBigInteger('project_id')->nullable();
                $table->string('title');
                $table->text('description')->nullable();
                $table->integer('sequence_order')->default(0);
                $table->enum('status', ['pending', 'in_progress', 'achieved', 'blocked', 'skipped'])->default('pending');
                $table->decimal('progress_percentage', 5, 2)->default(0.00);
                $table->timestamp('target_date')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedBigInteger('assigned_to')->nullable();
                $table->json('metadata')->nullable();
                $table->text('notes')->nullable();
                $table->text('blockers')->nullable();
                // Billing fields (cross-module FKs deferred)
                $table->decimal('billing_amount', 12, 2)->nullable();
                $table->boolean('client_approved')->default(false);
                $table->timestamp('client_approved_at')->nullable();
                $table->unsignedBigInteger('contract_id')->nullable();
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['project_type', 'project_id']);
                $table->index('status');
                $table->index('sequence_order');
                $table->index(['status', 'target_date']);
                $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            });
        } else {
            Schema::table('milestones', function (Blueprint $table) {
                if (! Schema::hasColumn('milestones', 'billing_amount')) {
                    $table->decimal('billing_amount', 12, 2)->nullable()->after('blockers');
                }
                if (! Schema::hasColumn('milestones', 'client_approved')) {
                    $table->boolean('client_approved')->default(false)->after('billing_amount');
                }
                if (! Schema::hasColumn('milestones', 'client_approved_at')) {
                    $table->timestamp('client_approved_at')->nullable()->after('client_approved');
                }
                if (! Schema::hasColumn('milestones', 'contract_id')) {
                    $table->unsignedBigInteger('contract_id')->nullable()->after('client_approved_at');
                }
                if (! Schema::hasColumn('milestones', 'invoice_id')) {
                    $table->unsignedBigInteger('invoice_id')->nullable()->after('contract_id');
                }
            });
        }

        // ── notification_subscriptions ──────────────────────────────────
        if (! Schema::hasTable('notification_subscriptions')) {
            Schema::create('notification_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('alert_type')->index();
                $table->json('channels')->nullable();
                $table->string('frequency')->default('immediate');
                $table->json('thresholds')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['user_id', 'alert_type']);
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        // ── billing_templates (standalone, not cm_billing_templates) ────
        if (! Schema::hasTable('billing_templates')) {
            Schema::create('billing_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('base_price', 10, 2);
                $table->string('billing_frequency');
                $table->text('description')->nullable();
                $table->json('features')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // WARNING: down() intentionally left non-destructive.
        // This migration consolidation is forward-only.
    }
};
