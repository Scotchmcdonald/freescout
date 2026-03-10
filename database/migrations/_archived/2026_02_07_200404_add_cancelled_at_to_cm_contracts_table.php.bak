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
            if (!Schema::hasColumn('cm_contracts', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('terminated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cm_contracts', function (Blueprint $table) {
            if (Schema::hasColumn('cm_contracts', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }
        });
    }
};
