<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated: companies, company_domains, company_user, channels
 *
 * Merged from:
 *  - 0001_01_01_000009_create_companies_table.php
 *  - 0001_01_01_000010_create_rbac_tables.php (company_domains, company_user)
 *  - 0001_01_01_000004_create_channels_table.php
 *  - 2026_01_13_000003_add_payment_fields_to_companies_table.php
 *  - 2026_03_03_234842_merge_client_users_into_users_and_create_company_user_pivot.php
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── companies ───────────────────────────────────────────────────
        if (! Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('website')->nullable();
                $table->string('address')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('zip')->nullable();
                $table->string('country')->nullable();
                $table->unsignedBigInteger('primary_contact_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('client_id')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('sms_notifications_enabled')->default(false);
                $table->json('settings')->nullable();
                // CRM columns
                $table->string('vat_number')->nullable();
                $table->string('tax_id')->nullable();
                // Payment module columns
                $table->text('billing_address')->nullable();
                $table->string('billing_mode')->default('manual');
                $table->string('pricing_tier')->nullable()->default('standard');
                $table->string('scenario')->nullable();
                $table->decimal('margin_floor_percent', 5, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('companies', function (Blueprint $table) {
                if (! Schema::hasColumn('companies', 'vat_number')) {
                    $table->string('vat_number')->nullable();
                }
                if (! Schema::hasColumn('companies', 'tax_id')) {
                    $table->string('tax_id')->nullable();
                }
                if (! Schema::hasColumn('companies', 'billing_address')) {
                    $table->text('billing_address')->nullable();
                }
                if (! Schema::hasColumn('companies', 'billing_mode')) {
                    $table->string('billing_mode')->default('manual');
                }
                if (! Schema::hasColumn('companies', 'pricing_tier')) {
                    $table->string('pricing_tier')->nullable()->default('standard');
                }
                if (! Schema::hasColumn('companies', 'scenario')) {
                    $table->string('scenario')->nullable();
                }
                if (! Schema::hasColumn('companies', 'margin_floor_percent')) {
                    $table->decimal('margin_floor_percent', 5, 2)->default(0);
                }
                if (! Schema::hasColumn('companies', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // ── company_domains ─────────────────────────────────────────────
        if (! Schema::hasTable('company_domains')) {
            Schema::create('company_domains', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('domain')->unique();
                $table->timestamps();
                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            });
        }

        // ── company_user ────────────────────────────────────────────────
        if (! Schema::hasTable('company_user')) {
            Schema::create('company_user', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('role_id')->nullable();
                $table->enum('status', ['pending', 'approved', 'blocked'])->default('pending');
                $table->boolean('is_primary')->default(true);
                $table->unsignedBigInteger('client_id')->nullable();
                $table->unsignedBigInteger('manager_id')->nullable();
                $table->boolean('is_approver')->default(false);
                $table->decimal('approval_limit', 15, 2)->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'company_id']);
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
                $table->foreign('manager_id')->references('id')->on('users')->nullOnDelete();
            });
        } else {
            Schema::table('company_user', function (Blueprint $table) {
                if (! Schema::hasColumn('company_user', 'is_primary')) {
                    $table->boolean('is_primary')->default(true)->after('status');
                }
                if (! Schema::hasColumn('company_user', 'client_id')) {
                    $table->unsignedBigInteger('client_id')->nullable()->after('is_primary');
                }
                if (! Schema::hasColumn('company_user', 'manager_id')) {
                    $table->unsignedBigInteger('manager_id')->nullable()->after('client_id');
                }
                if (! Schema::hasColumn('company_user', 'is_approver')) {
                    $table->boolean('is_approver')->default(false)->after('manager_id');
                }
                if (! Schema::hasColumn('company_user', 'approval_limit')) {
                    $table->decimal('approval_limit', 15, 2)->nullable()->after('is_approver');
                }
            });
        }

        // ── channels ────────────────────────────────────────────────────
        if (! Schema::hasTable('channels')) {
            Schema::create('channels', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedTinyInteger('type')->index();
                $table->json('settings')->nullable();
                $table->boolean('active')->default(true)->index();
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
