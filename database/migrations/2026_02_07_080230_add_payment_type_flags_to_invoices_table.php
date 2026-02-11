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
        Schema::table('pib_invoices', function (Blueprint $table) {
            $table->boolean('is_final_payment')
                ->default(false)
                ->after('status')
                ->comment('Marks invoice as final payment for rent-to-own');
            
            $table->boolean('is_buyout')
                ->default(false)
                ->after('is_final_payment')
                ->comment('Marks invoice as early buyout payment');
            
            $table->text('special_notes')
                ->nullable()
                ->after('is_buyout')
                ->comment('Special notes for invoice (e.g., final payment, ownership transfer)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pib_invoices', function (Blueprint $table) {
            $table->dropColumn(['is_final_payment', 'is_buyout', 'special_notes']);
        });
    }
};
