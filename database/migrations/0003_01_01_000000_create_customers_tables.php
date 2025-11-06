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
        // Customers - contact information
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 50)->nullable();
            $table->string('last_name', 50)->nullable();
            $table->string('photo_url')->nullable();
            $table->text('notes')->nullable();
            $table->string('company', 100)->nullable();
            $table->string('job_title', 100)->nullable();
            $table->unsignedTinyInteger('photo_type')->nullable();
            $table->string('age', 7)->nullable();
            $table->unsignedTinyInteger('gender')->nullable();
            $table->json('phones')->nullable(); // modernized to JSON type
            $table->json('websites')->nullable();
            $table->json('social_profiles')->nullable();
            $table->json('chats')->nullable();
            $table->text('background')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip', 12)->nullable();
            $table->string('country', 2)->nullable();
            $table->unsignedTinyInteger('channel')->nullable();
            $table->string('channel_id')->nullable();
            $table->text('meta')->nullable();
            $table->timestamps();

            // Search indexes
            $table->index(['first_name', 'last_name']);
            $table->index('channel');
        });

        // Customer emails - one customer can have multiple email addresses
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('email', 191)->unique();
            $table->unsignedTinyInteger('type')->default(1); // 1=work, 2=home, 3=other
            $table->timestamps();

            $table->index('customer_id');
            $table->index('email');
        });

        // Customer channels - multichannel support (email, chat, phone, etc.)
        Schema::create('customer_channel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('channel');
            $table->string('channel_id')->unique();
            $table->timestamps();

            $table->index(['customer_id', 'channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_channel');
        Schema::dropIfExists('emails');
        Schema::dropIfExists('customers');
    }
};
