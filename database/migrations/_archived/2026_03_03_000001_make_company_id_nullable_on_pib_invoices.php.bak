<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // pib_invoices
        Schema::table('pib_invoices', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });

        // payments (Payment module)
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'company_id')) {
            Schema::table('payments', function (Blueprint $table) {
                // Drop FK if it exists
                try {
                    $table->dropForeign(['company_id']);
                } catch (\Throwable) {
                    // FK may already be absent
                }
                $table->unsignedBigInteger('company_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('pib_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }
};
