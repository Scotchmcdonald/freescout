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
        Schema::table('migration_projects', function (Blueprint $table) {
            $table->text('google_service_account_json')->nullable()->after('settings');
            $table->string('google_admin_email')->nullable()->after('google_service_account_json');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('migration_projects', function (Blueprint $table) {
            $table->dropColumn(['google_service_account_json', 'google_admin_email']);
        });
    }
};
