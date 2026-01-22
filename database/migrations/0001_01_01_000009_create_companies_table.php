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
        // 3. Companies (Primary Organization Entity)
        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                // Identity
                $table->id();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('website')->nullable();
                
                // Address
                $table->string('address')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('zip')->nullable();
                $table->string('country')->nullable();
                
                // Relationships
                $table->unsignedBigInteger('primary_contact_id')->nullable();
                $table->foreignId('customer_id')->nullable(); // Legacy/Customer binding if needed (though customers usually belong to companies)
                $table->unsignedBigInteger('client_id')->nullable(); // Legacy link to 'clients' table
                
                // Metadata
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('sms_notifications_enabled')->default(false);
                $table->json('settings')->nullable();

                $table->timestamps();
                $table->softDeletes();
            });
        } elseif (!Schema::hasColumn('companies', 'is_active')) {
             Schema::table('companies', function (Blueprint $table) {
                $table->boolean('is_active')->default(true);
                $table->string('scenario')->nullable();
                $table->string('pricing_tier')->default('standard');
                $table->decimal('margin_floor_percent', 5, 2)->default(20.00);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
