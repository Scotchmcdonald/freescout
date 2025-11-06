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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 20);
            $table->string('last_name', 30);
            $table->string('email', 191)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->unsignedTinyInteger('role')->default(1)->index(); // 1: user, 2: admin
            $table->string('timezone')->default('UTC');
            $table->string('photo_url')->nullable();
            $table->unsignedTinyInteger('type')->default(1); // team/user
            $table->unsignedTinyInteger('invite_state')->default(1);
            $table->string('invite_hash', 100)->nullable();
            $table->string('emails', 100)->nullable();
            $table->string('job_title', 100)->nullable();
            $table->string('phone', 60)->nullable();
            $table->unsignedTinyInteger('time_format')->default(24);
            $table->boolean('enable_kb_shortcuts')->default(true);
            $table->boolean('locked')->default(false);
            $table->unsignedTinyInteger('status')->default(1)->index();
            $table->string('locale', 5)->default('en');
            $table->text('permissions')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
