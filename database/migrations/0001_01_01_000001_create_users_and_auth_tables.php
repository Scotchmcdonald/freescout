<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated: users, password_reset_tokens, sessions
 *
 * Merged from:
 *  - 0001_01_01_000000_create_users_table.php
 *  - 2026_02_16_140414_add_is_demo_to_users_table.php
 *  - 2026_03_04_001156_add_sender_details_and_client_user_to_core_tables.php (users cols)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── users ───────────────────────────────────────────────────────
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('first_name', 20);
                $table->string('last_name', 30);
                $table->string('email', 191)->unique();
                $table->string('google_id')->nullable()->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->unsignedTinyInteger('role')->default(1)->index();
                $table->string('timezone')->default('UTC');
                $table->string('photo_url')->nullable();
                $table->string('avatar')->nullable();
                $table->unsignedTinyInteger('type')->default(1);
                $table->unsignedTinyInteger('invite_state')->default(1);
                $table->string('invite_hash', 100)->nullable();
                $table->string('emails', 100)->nullable();
                $table->string('job_title', 100)->nullable();
                $table->string('phone', 60)->nullable();
                $table->unsignedTinyInteger('time_format')->default(24);
                $table->boolean('enable_kb_shortcuts')->default(true);
                $table->boolean('locked')->default(false);
                $table->unsignedTinyInteger('status')->default(1)->index();
                $table->string('locale', 5)->default('en');
                $table->string('theme', 50)->nullable();
                $table->boolean('dark_mode')->default(true);
                $table->text('permissions')->nullable();
                $table->boolean('is_demo')->default(false);
                $table->string('sender_email')->nullable();
                $table->string('sender_name')->nullable();
                $table->unsignedBigInteger('client_user_id')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        } else {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'is_demo')) {
                    $table->boolean('is_demo')->default(false)->after('permissions');
                }
                if (! Schema::hasColumn('users', 'sender_email')) {
                    $table->string('sender_email')->nullable()->after('is_demo');
                }
                if (! Schema::hasColumn('users', 'sender_name')) {
                    $table->string('sender_name')->nullable()->after('sender_email');
                }
                if (! Schema::hasColumn('users', 'client_user_id')) {
                    $table->unsignedBigInteger('client_user_id')->nullable()->after('sender_name');
                }
            });
        }

        // Deferred FK: client_user_id → users.id (self-referential)
        if (Schema::hasColumn('users', 'client_user_id')) {
            $this->addForeignKeyIfMissing('users', 'client_user_id', 'users', 'id', 'users_client_user_id_foreign', 'set null');
        }

        // ── password_reset_tokens ───────────────────────────────────────
        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // ── sessions ────────────────────────────────────────────────────
        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index()->constrained()->cascadeOnDelete();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        // WARNING: down() intentionally left non-destructive.
        // This migration consolidation is forward-only.
    }

    /**
     * Safely add a foreign key if it does not already exist.
     */
    private function addForeignKeyIfMissing(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn,
        string $fkName,
        string $onDelete = 'cascade'
    ): void {
        try {
            $fkExists = collect(Schema::getForeignKeys($table))
                ->contains(fn ($fk) => in_array($column, $fk['columns']));

            if (! $fkExists) {
                Schema::table($table, function (Blueprint $t) use ($column, $referencedTable, $referencedColumn, $fkName, $onDelete) {
                    $t->foreign($column, $fkName)
                        ->references($referencedColumn)
                        ->on($referencedTable)
                        ->onDelete($onDelete);
                });
            }
        } catch (\Throwable) {
            // Gracefully skip if FK check not supported (e.g. SQLite)
        }
    }
};
