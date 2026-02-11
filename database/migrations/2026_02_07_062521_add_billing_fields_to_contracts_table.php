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
            $table->unsignedBigInteger('billing_template_id')->nullable()->after('title');
            $table->decimal('price_override', 10, 2)->nullable()->after('billing_template_id');
            $table->decimal('monthly_amount', 10, 2)->nullable()->after('price_override');
            
            $table->foreign('billing_template_id')
                  ->references('id')
                  ->on('cm_billing_templates')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cm_contracts', function (Blueprint $table) {
            $table->dropForeign(['billing_template_id']);
            $table->dropColumn(['billing_template_id', 'price_override', 'monthly_amount']);
        });
    }
};
