<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add tracing and replay columns to middleman_logs
        Schema::table('middleman_logs', function (Blueprint $table) {
            $table->uuid('correlation_id')->nullable()->index()->after('metadata');
            $table->uuid('causation_id')->nullable()->index()->after('correlation_id');
            $table->boolean('is_replay')->default(false)->after('causation_id');
            $table->boolean('has_schema_drift')->default(false)->index()->after('is_replay');
        });

        // Create the schema baselines table for drift detection
        Schema::create('middleman_schemas', function (Blueprint $table) {
            $table->id();
            $table->string('event_class', 255)->index();
            $table->json('schema');
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['event_class', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('middleman_schemas');

        Schema::table('middleman_logs', function (Blueprint $table) {
            $table->dropIndex(['correlation_id']);
            $table->dropIndex(['causation_id']);
            $table->dropIndex(['has_schema_drift']);
            $table->dropColumn([
                'correlation_id',
                'causation_id',
                'is_replay',
                'has_schema_drift',
            ]);
        });
    }
};
