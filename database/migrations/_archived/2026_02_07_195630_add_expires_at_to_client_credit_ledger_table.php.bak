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
        Schema::table('client_credit_ledger', function (Blueprint $table) {
            if (!Schema::hasColumn('client_credit_ledger', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('created_by_user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_credit_ledger', function (Blueprint $table) {
            if (Schema::hasColumn('client_credit_ledger', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
        });
    }
};
