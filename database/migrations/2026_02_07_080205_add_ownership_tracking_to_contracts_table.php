<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cm_contracts', function (Blueprint $table) {
            // Only add fields that don't exist
            if (!Schema::hasColumn('cm_contracts', 'ownership_status')) {
                $table->enum('ownership_status', ['renting', 'owned'])
                    ->default('renting')
                    ->after('status')
                    ->comment('Current ownership status of rent-to-own asset');
            }
            
            if (!Schema::hasColumn('cm_contracts', 'ownership_transferred_at')) {
                $table->timestamp('ownership_transferred_at')
                    ->nullable()
                    ->after('ownership_status')
                    ->comment('When ownership was transferred to client');
            }
            
            if (!Schema::hasColumn('cm_contracts', 'missed_payments_count')) {
                $table->integer('missed_payments_count')
                    ->default(0)
                    ->after('ownership_transferred_at')
                    ->comment('Count of missed rental payments');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cm_contracts', function (Blueprint $table) {
            if (Schema::hasColumn('cm_contracts', 'ownership_status')) {
                $table->dropColumn('ownership_status');
            }
            if (Schema::hasColumn('cm_contracts', 'ownership_transferred_at')) {
                $table->dropColumn('ownership_transferred_at');
            }
            if (Schema::hasColumn('cm_contracts', 'missed_payments_count')) {
                $table->dropColumn('missed_payments_count');
            }
        });
    }
};
