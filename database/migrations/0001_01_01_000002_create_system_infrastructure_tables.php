<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated: themes, jobs, failed_jobs, cache, cache_locks, options, modules,
 *               polycast_events, ltm_translations, api_rate_limit_tracking,
 *               circuit_breaker_states, test_counters
 *
 * Merged from:
 *  - 0001_01_01_000001_create_themes_table.php
 *  - 0001_01_01_000002_create_system_tables.php
 *  - 2026_01_15_000004_create_test_counters_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── themes ──────────────────────────────────────────────────────
        if (! Schema::hasTable('themes')) {
            Schema::create('themes', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('title');
                $table->json('config');
                $table->boolean('is_system')->default(false);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        // ── jobs ────────────────────────────────────────────────────────
        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        // ── failed_jobs ─────────────────────────────────────────────────
        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        // ── cache ───────────────────────────────────────────────────────
        if (! Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        // ── cache_locks ─────────────────────────────────────────────────
        if (! Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }

        // ── options ─────────────────────────────────────────────────────
        if (! Schema::hasTable('options')) {
            Schema::create('options', function (Blueprint $table) {
                $table->string('name', 255)->primary();
                $table->longText('value')->nullable();
                $table->timestamps();
            });
        }

        // ── modules ─────────────────────────────────────────────────────
        if (! Schema::hasTable('modules')) {
            Schema::create('modules', function (Blueprint $table) {
                $table->id();
                $table->string('alias', 255)->unique();
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->string('version', 11);
                $table->string('author', 255)->nullable();
                $table->boolean('active')->default(false);
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }

        // ── polycast_events ─────────────────────────────────────────────
        if (! Schema::hasTable('polycast_events')) {
            Schema::create('polycast_events', function (Blueprint $table) {
                $table->id();
                $table->string('channel');
                $table->text('event');
                $table->longText('payload');
                $table->timestamps();
                $table->index('created_at');
            });
        }

        // ── ltm_translations ────────────────────────────────────────────
        if (! Schema::hasTable('ltm_translations')) {
            Schema::create('ltm_translations', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('status')->default(0)->index();
                $table->string('locale')->index();
                $table->string('group');
                $table->text('key');
                $table->text('value')->nullable();
                $table->string('hash')->nullable()->unique();
                $table->timestamps();
            });
        }

        // ── api_rate_limit_tracking ─────────────────────────────────────
        if (! Schema::hasTable('api_rate_limit_tracking')) {
            Schema::create('api_rate_limit_tracking', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->integer('attempts')->default(0);
                $table->timestamp('reset_at')->index();
                $table->timestamps();
            });
        }

        // ── circuit_breaker_states ──────────────────────────────────────
        if (! Schema::hasTable('circuit_breaker_states')) {
            Schema::create('circuit_breaker_states', function (Blueprint $table) {
                $table->string('service')->primary();
                $table->enum('state', ['closed', 'open', 'half_open'])->default('closed');
                $table->integer('failure_count')->default(0);
                $table->timestamp('last_failure_at')->nullable();
                $table->timestamp('opened_at')->nullable();
                $table->timestamps();
            });
        }

        // ── test_counters ───────────────────────────────────────────────
        if (! Schema::hasTable('test_counters')) {
            Schema::create('test_counters', function (Blueprint $table) {
                $table->id();
                $table->integer('count')->default(0);
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
