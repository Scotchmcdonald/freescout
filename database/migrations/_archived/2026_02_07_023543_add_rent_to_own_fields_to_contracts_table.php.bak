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
            $table->enum('contract_type', ['standard', 'rent_to_own'])->default('standard')->after('title');
            $table->decimal('purchase_price', 12, 2)->nullable()->after('contract_type');
            $table->decimal('monthly_rental_fee', 12, 2)->nullable()->after('purchase_price');
            $table->string('asset_description')->nullable()->after('monthly_rental_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cm_contracts', function (Blueprint $table) {
            $table->dropColumn(['contract_type', 'purchase_price', 'monthly_rental_fee', 'asset_description']);
        });
    }
};
