<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated: customers, emails, customer_channel, channel_customer
 *
 * Merged from:
 *  - 0001_01_01_000005_create_customers_tables.php
 *  - 2026_02_07_062020_add_default_hourly_rate_to_customers_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── customers ───────────────────────────────────────────────────
        if (! Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->string('first_name', 50)->nullable();
                $table->string('last_name', 50)->nullable();
                $table->string('photo_url')->nullable();
                $table->text('notes')->nullable();
                $table->string('company', 100)->nullable();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->boolean('is_non_profit')->default(false);
                $table->string('job_title', 100)->nullable();
                $table->unsignedTinyInteger('photo_type')->nullable();
                $table->string('age', 7)->nullable();
                $table->unsignedTinyInteger('gender')->nullable();
                $table->json('phones')->nullable();
                $table->json('websites')->nullable();
                $table->json('social_profiles')->nullable();
                $table->json('chats')->nullable();
                $table->text('background')->nullable();
                $table->text('address')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('zip', 12)->nullable();
                $table->string('country', 2)->nullable();
                $table->unsignedTinyInteger('channel')->nullable()->index();
                $table->string('channel_id')->nullable();
                $table->text('meta')->nullable();
                $table->decimal('default_hourly_rate', 10, 2)->nullable();
                $table->timestamps();
                $table->index(['first_name', 'last_name']);
            });
        } else {
            Schema::table('customers', function (Blueprint $table) {
                if (! Schema::hasColumn('customers', 'default_hourly_rate')) {
                    $table->decimal('default_hourly_rate', 10, 2)->nullable()->after('meta');
                }
            });
        }

        // ── emails ──────────────────────────────────────────────────────
        if (! Schema::hasTable('emails')) {
            Schema::create('emails', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->index();
                $table->string('email', 191)->unique();
                $table->unsignedTinyInteger('type')->default(1);
                $table->timestamps();
                $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            });
        }

        // ── customer_channel ────────────────────────────────────────────
        if (! Schema::hasTable('customer_channel')) {
            Schema::create('customer_channel', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id');
                $table->unsignedTinyInteger('channel');
                $table->string('channel_id')->unique();
                $table->timestamps();
                $table->index(['customer_id', 'channel']);
                $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            });
        }

        // ── channel_customer ────────────────────────────────────────────
        if (! Schema::hasTable('channel_customer')) {
            Schema::create('channel_customer', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('channel_id');
                $table->unsignedBigInteger('customer_id')->index();
                $table->timestamps();
                $table->unique(['channel_id', 'customer_id']);
                $table->foreign('channel_id')->references('id')->on('channels')->cascadeOnDelete();
                $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // WARNING: down() intentionally left non-destructive.
        // This migration consolidation is forward-only.
    }
};
