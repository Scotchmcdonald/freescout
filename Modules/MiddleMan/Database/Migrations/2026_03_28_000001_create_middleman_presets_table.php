<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('middleman_presets', function (Blueprint $table) {
            $table->id();
            $table->string('event_class', 255)->index();
            $table->string('name', 255);
            $table->json('payload');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->index(['event_class', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('middleman_presets');
    }
};
