<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('milestones', function (Blueprint $table) {
            $table->decimal('billing_amount', 12, 2)->nullable()->after('progress_percentage');
            $table->boolean('client_approved')->default(false)->after('billing_amount');
            $table->timestamp('client_approved_at')->nullable()->after('client_approved');
            $table->unsignedBigInteger('contract_id')->nullable()->after('client_approved_at');
            $table->unsignedBigInteger('invoice_id')->nullable()->after('contract_id');

            $table->foreign('contract_id')->references('id')->on('cm_contracts')->nullOnDelete();
            $table->foreign('invoice_id')->references('id')->on('pib_invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('milestones', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropColumn(['billing_amount', 'client_approved', 'client_approved_at', 'contract_id', 'invoice_id']);
        });
    }
};
