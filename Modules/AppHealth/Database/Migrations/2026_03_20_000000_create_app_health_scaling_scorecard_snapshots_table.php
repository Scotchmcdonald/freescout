<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('app_health_scaling_scorecard_snapshots')) {
            return;
        }

        Schema::create('app_health_scaling_scorecard_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->date('snapshot_date')->unique();
            $table->string('overall_status', 32);
            $table->string('recommendation', 64);
            $table->unsignedSmallInteger('breach_count')->default(0);
            $table->json('payload');
            $table->timestamp('evaluated_at');
            $table->timestamps();

            $table->index(['snapshot_date', 'overall_status'], 'apphealth_snapshot_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_health_scaling_scorecard_snapshots');
    }
};
