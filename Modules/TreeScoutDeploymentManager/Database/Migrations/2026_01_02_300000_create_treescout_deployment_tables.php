<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated TreeScoutDeploymentManager module tables:
 *   tsdm_deployment_records, tsdm_deployed_modules,
 *   tsdm_deployment_activations, tsdm_activation_audit_log
 *
 * Merged from:
 *  - 2026_02_28_000000_create_treescout_deployment_tables.php
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── tsdm_deployment_records ─────────────────────────────────────
        if (! Schema::hasTable('tsdm_deployment_records')) {
            Schema::create('tsdm_deployment_records', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->string('name')->comment('Human label e.g. "Acme Production"');
                $table->string('environment')->default('production')->comment('production | staging | development');
                $table->string('git_provider')->default('gitlab')->comment('gitlab | github');
                $table->string('git_project_id')->nullable()->comment('GitLab project ID or GitHub repo slug');
                $table->string('server_ip', 45)->nullable();
                $table->string('server_fingerprint', 128)->nullable()->comment('SHA-256 of hostname+IP for IP pinning');
                $table->enum('status', ['pending', 'active', 'suspended', 'revoked'])->default('pending');
                $table->timestamp('last_seen_at')->nullable();
                $table->string('app_version')->nullable()->comment('freescout core version installed');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['client_id', 'status']);
                $table->index('status');
            });
        }

        // ── tsdm_deployed_modules ───────────────────────────────────────
        if (! Schema::hasTable('tsdm_deployed_modules')) {
            Schema::create('tsdm_deployed_modules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('deployment_record_id');
                $table->string('module_name');
                $table->string('module_version')->nullable();
                $table->enum('status', ['active', 'disabled', 'error'])->default('active');
                $table->timestamp('installed_at')->nullable();
                $table->timestamp('last_updated_at')->nullable();
                $table->timestamps();
                $table->foreign('deployment_record_id')->references('id')->on('tsdm_deployment_records')->cascadeOnDelete();
                $table->unique(['deployment_record_id', 'module_name']);
            });
        }

        // ── tsdm_deployment_activations ─────────────────────────────────
        if (! Schema::hasTable('tsdm_deployment_activations')) {
            Schema::create('tsdm_deployment_activations', function (Blueprint $table) {
                $table->id();
                $table->string('activation_code', 32)->unique();
                $table->unsignedBigInteger('deployment_record_id');
                $table->unsignedBigInteger('issued_by_user_id')->nullable();
                $table->json('requested_scopes')->nullable();
                $table->timestamp('expires_at');
                $table->timestamp('used_at')->nullable();
                $table->string('redeemed_from_ip', 45)->nullable();
                $table->text('issued_token_encrypted')->nullable();
                $table->string('label')->nullable()->comment('e.g. "Initial install for Acme Production"');
                $table->timestamps();
                $table->foreign('deployment_record_id')->references('id')->on('tsdm_deployment_records')->cascadeOnDelete();
                $table->index(['activation_code', 'used_at', 'expires_at'], 'tsdm_activations_code_used_expires_idx');
                $table->index('deployment_record_id', 'tsdm_activations_deployment_idx');
            });
        }

        // ── tsdm_activation_audit_log ───────────────────────────────────
        if (! Schema::hasTable('tsdm_activation_audit_log')) {
            Schema::create('tsdm_activation_audit_log', function (Blueprint $table) {
                $table->id();
                $table->string('activation_code', 32)->index();
                $table->string('attempt_ip', 45)->nullable();
                $table->enum('outcome', ['success', 'invalid_code', 'expired', 'already_used', 'provider_error']);
                $table->text('error_detail')->nullable();
                $table->timestamp('attempted_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        // WARNING: down() intentionally left non-destructive.
        // This migration consolidation is forward-only.
    }
};
