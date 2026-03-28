<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('middleman_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_class', 255)->index();
            $table->string('event_name', 255)->index();
            $table->json('payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('fired_at')->index();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event_class', 'fired_at']);
        });

        Schema::create('middleman_intercepts', function (Blueprint $table) {
            $table->id();
            $table->string('event_class', 255)->index();
            $table->string('event_name', 255)->index();
            $table->json('payload')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status', 20)->default('pending')->index(); // pending, fired, discarded
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('intercepted_at');
            $table->timestamp('fired_at')->nullable();
            $table->unsignedBigInteger('fired_by')->nullable();
            $table->timestamps();

            $table->foreign('fired_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['status', 'sort_order']);
        });

        Schema::create('middleman_audit_trail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('action', 50);           // e.g. 'intercept_fired', 'payload_edited', 'rule_created'
            $table->string('subject_type', 255)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('middleman_audit_trail');
        Schema::dropIfExists('middleman_intercepts');
        Schema::dropIfExists('middleman_logs');
    }
};
