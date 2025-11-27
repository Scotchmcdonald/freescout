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
        // Jobs - Laravel queue jobs
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        // Failed jobs - Laravel failed queue jobs
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // Cache - Laravel cache storage
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        // Options - Application settings (key-value store)
        Schema::create('options', function (Blueprint $table) {
            $table->string('name', 255)->primary();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        // Modules - FreeScout modules/plugins
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('alias', 255)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('version', 11);
            $table->string('author', 255)->nullable();
            $table->boolean('active')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // Polycast events - broadcasting events
        Schema::create('polycast_events', function (Blueprint $table) {
            $table->id();
            $table->string('channel');
            $table->text('event');
            $table->longText('payload');
            $table->timestamps();

            $table->index('created_at');
        });

        // Translations - multilingual support
        Schema::create('ltm_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('status')->default(0);
            $table->string('locale');
            $table->string('group');
            $table->text('key');
            $table->text('value')->nullable();
            $table->string('hash')->nullable()->unique();
            $table->timestamps();

            $table->index('status');
            $table->index('locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ltm_translations');
        Schema::dropIfExists('polycast_events');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('options');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('jobs');
    }
};
