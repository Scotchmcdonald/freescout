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
        Schema::create('module_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('module_name', 100)->index();
            $table->string('action', 50)->index(); // install, update, enable, disable, delete
            $table->text('metadata')->nullable(); // JSON data: repo_url, version, error_message, etc.
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            
            // Add indexes for common queries
            $table->index(['module_name', 'action']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_activity_logs');
    }
};
