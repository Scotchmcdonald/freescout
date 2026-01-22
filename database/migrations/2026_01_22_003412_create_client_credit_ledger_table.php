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
        // Drop the duplicate table if it exists
        Schema::dropIfExists('payment_client_credit_ledgers');

        // Ensure client_credit_ledger exists and has correct columns
        if (!Schema::hasTable('client_credit_ledger')) {
            Schema::create('client_credit_ledger', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
                $table->decimal('amount', 10, 2);
                $table->decimal('balance_before', 10, 2)->default(0);
                $table->decimal('balance_after', 10, 2);
                $table->string('type'); // credit, debit
                $table->string('description')->nullable();
                $table->nullableMorphs('reference');
                $table->timestamps();
                
                $table->index(['client_id', 'created_at']);
            });
        } else {
            Schema::table('client_credit_ledger', function (Blueprint $table) {
                if (!Schema::hasColumn('client_credit_ledger', 'balance_before')) {
                    $table->decimal('balance_before', 10, 2)->default(0)->after('amount');
                }
                // Ensure reference columns exist (morphs)
                if (!Schema::hasColumn('client_credit_ledger', 'reference_id')) {
                    $table->nullableMorphs('reference');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_credit_ledger', function (Blueprint $table) {
            if (Schema::hasColumn('client_credit_ledger', 'balance_before')) {
                $table->dropColumn('balance_before');
            }
        });
    }
};
