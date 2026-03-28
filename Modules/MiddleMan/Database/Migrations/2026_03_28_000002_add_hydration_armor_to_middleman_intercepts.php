<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds Hydration Armor columns to middleman_intercepts:
 *
 *  status         — adds 'corrupted' as a valid value (existing VARCHAR, no change needed)
 *  resolution_notes — stores the exception message when an intercept becomes CORRUPTED,
 *                     giving operators diagnostic context without requiring log access.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('middleman_intercepts', function (Blueprint $table) {
            $table->text('resolution_notes')->nullable()->after('fired_by');
        });
    }

    public function down(): void
    {
        Schema::table('middleman_intercepts', function (Blueprint $table) {
            $table->dropColumn('resolution_notes');
        });
    }
};
