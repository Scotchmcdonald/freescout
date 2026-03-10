<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated: roles, permissions, permission_role, role_user
 *
 * Merged from:
 *  - 0001_01_01_000010_create_rbac_tables.php (base roles, permissions, permission_role)
 *  - 2026_03_03_000001_create_role_user_table.php
 *  - 2026_03_03_000002_add_rbac_columns_to_roles_table.php
 *  - 2026_03_03_000003_add_rbac_columns_to_permissions_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── roles ───────────────────────────────────────────────────────
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('label')->nullable();
                $table->boolean('is_super_admin')->default(false);
                $table->string('scope')->default('internal');
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        } else {
            Schema::table('roles', function (Blueprint $table) {
                if (! Schema::hasColumn('roles', 'is_super_admin')) {
                    $table->boolean('is_super_admin')->default(false)->after('label');
                }
                if (! Schema::hasColumn('roles', 'scope')) {
                    $table->string('scope')->default('internal')->after('is_super_admin');
                }
                if (! Schema::hasColumn('roles', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('scope');
                }
            });
        }

        // ── permissions ─────────────────────────────────────────────────
        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('label')->nullable();
                $table->string('module')->nullable();
                $table->string('group')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        } else {
            Schema::table('permissions', function (Blueprint $table) {
                if (! Schema::hasColumn('permissions', 'module')) {
                    $table->string('module')->nullable()->after('label');
                }
                if (! Schema::hasColumn('permissions', 'group')) {
                    $table->string('group')->nullable()->after('module');
                }
                if (! Schema::hasColumn('permissions', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('group');
                }
            });
        }

        // ── permission_role ─────────────────────────────────────────────
        if (! Schema::hasTable('permission_role')) {
            Schema::create('permission_role', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->primary(['permission_id', 'role_id']);
                $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
                $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            });
        }

        // ── role_user ───────────────────────────────────────────────────
        if (! Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('role_id');
                $table->primary(['user_id', 'role_id']);
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // WARNING: down() intentionally left non-destructive.
        // This migration consolidation is forward-only.
    }
};
