<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Roles Table
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique(); // e.g., 'MSP Admin', 'Client Admin'
                $table->string('label')->nullable();
                $table->timestamps();
            });
        }

        // 2. Permissions Table
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique(); // e.g., 'view_billing'
                $table->string('label')->nullable();
                $table->timestamps();
            });
        }

        // 3. Permission Role Pivot
        if (!Schema::hasTable('permission_role')) {
            Schema::create('permission_role', function (Blueprint $table) {
                $table->foreignId('permission_id')->constrained()->onDelete('cascade');
                $table->foreignId('role_id')->constrained()->onDelete('cascade');
                $table->primary(['permission_id', 'role_id']);
            });
        }

        // 4. Company Domains (for Auto-Enrollment)
        if (!Schema::hasTable('company_domains')) {
            Schema::create('company_domains', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
                $table->string('domain')->unique(); // e.g., 'charity-xyz.org'
                $table->timestamps();
            });
        }

        // 5. Company User Pivot (Multi-Company Access)
        if (!Schema::hasTable('company_user')) {
            Schema::create('company_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
                $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null');
                $table->enum('status', ['pending', 'approved', 'blocked'])->default('pending');
                $table->timestamps();

                $table->unique(['user_id', 'company_id']);
            });
        }

        // 6. Remove company_id from users if it exists
        if (Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('company_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'company_id')) {
             Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable();
            });
        }

        Schema::dropIfExists('company_user');
        Schema::dropIfExists('company_domains');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
