<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('module')->nullable()->after('label');  // e.g., 'core', 'crm', 'pib'
            $table->string('group')->nullable()->after('module');  // e.g., 'Tickets', 'Users', 'Billing'
            $table->integer('sort_order')->default(0)->after('group');
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['module', 'group', 'sort_order']);
        });
    }
};
