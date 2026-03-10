<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidates the redundant created_by / created_by_user_id columns in
 * client_credit_ledger into a single created_by_user_id column.
 *
 * Background:
 *   - The original migration added created_by (int, FK to users).
 *   - A later refactor introduced created_by_user_id as a more explicit name
 *     but never removed the old column, leaving both in the schema.
 *   - This migration standardises on created_by_user_id, migrates any data
 *     that only existed in created_by, and removes the redundant column.
 *
 * Rollback restores created_by and drops created_by_user_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Add the new canonical column (if it doesn't already exist).
        if (! Schema::hasColumn('client_credit_ledger', 'created_by_user_id')) {
            Schema::table('client_credit_ledger', function (Blueprint $table): void {
                $table->unsignedBigInteger('created_by_user_id')->nullable()->after('created_by');
                $table->foreign('created_by_user_id', 'ccl_created_by_user_id_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        // 2. Migrate data: copy created_by → created_by_user_id where the new
        //    column is still null (preserves any rows already populated).
        DB::statement(
            'UPDATE client_credit_ledger
             SET created_by_user_id = created_by
             WHERE created_by IS NOT NULL
               AND created_by_user_id IS NULL'
        );

        // 3. Drop the legacy FK constraint and column.
        Schema::table('client_credit_ledger', function (Blueprint $table): void {
            // Use column-based syntax so it works on both MySQL and SQLite.
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
    }

    public function down(): void
    {
        // Restore created_by from created_by_user_id.
        Schema::table('client_credit_ledger', function (Blueprint $table): void {
            $table->unsignedBigInteger('created_by')->nullable()->after('reference_id');
            $table->foreign('created_by', 'client_credit_ledger_created_by_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        DB::statement(
            'UPDATE client_credit_ledger
             SET created_by = created_by_user_id
             WHERE created_by_user_id IS NOT NULL
               AND created_by IS NULL'
        );

        Schema::table('client_credit_ledger', function (Blueprint $table): void {
            $table->dropForeign(['created_by_user_id']);
            $table->dropColumn('created_by_user_id');
        });
    }
};
