<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('label');
            $table->string('scope')->default('internal')->after('is_super_admin'); // 'internal' or 'client'
            $table->integer('sort_order')->default(0)->after('scope');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['is_super_admin', 'scope', 'sort_order']);
        });
    }
};
