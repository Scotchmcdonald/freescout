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
        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            
            // Project association (polymorphic for flexibility)
            $table->string('project_type')->nullable(); // e.g., 'quote', 'migration', 'onboarding'
            $table->unsignedBigInteger('project_id')->nullable();
            
            // Milestone details
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('sequence_order')->default(0); // Display order in stepper
            
            // Status and progress
            $table->enum('status', ['pending', 'in_progress', 'achieved', 'blocked', 'skipped'])->default('pending');
            $table->decimal('progress_percentage', 5, 2)->default(0.00); // 0.00 to 100.00
            
            // Dates
            $table->timestamp('target_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Assignment and tracking
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            
            // Metadata
            $table->json('metadata')->nullable(); // For custom fields, tags, etc.
            $table->text('notes')->nullable();
            $table->text('blockers')->nullable(); // Description of what's blocking progress
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['project_type', 'project_id']);
            $table->index('status');
            $table->index('sequence_order');
            $table->index(['status', 'target_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milestones');
    }
};
